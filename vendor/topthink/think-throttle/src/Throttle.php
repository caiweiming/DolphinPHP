<?php
declare(strict_types=1);

namespace think\middleware;

use Closure;
use Psr\SimpleCache\CacheInterface;
use think\Cache;
use think\Config;
use think\Container;
use think\exception\HttpResponseException;
use think\middleware\throttle\CounterFixed;
use think\middleware\throttle\ThrottleAbstract;
use think\Request;
use think\Response;
use TypeError;
use function sprintf;

/**
 * 访问频率限制中间件
 * Class Throttle
 * @package think\middleware
 */
class Throttle
{
    /**
     * 默认配置参数
     * @var array
     */
    public static array $default_config = [
        'prefix' => 'throttle_',                    // 缓存键前缀，防止键与其他应用冲突
        'key'    => true,                           // 节流规则 true为自动规则
        'visit_method' => ['GET', 'HEAD'],          // 要被限制的请求类型
        'visit_rate' => null,                       // 节流频率 null 表示不限制 eg: 10/m  20/h  300/d
        'visit_enable_show_rate_limit' => true,     // 在响应体中设置速率限制的头部信息
        'visit_fail_code' => 429,                   // 访问受限时返回的http状态码，当没有visit_fail_response时生效
        'visit_fail_text' => 'Too Many Requests',   // 访问受限时访问的文本信息，当没有visit_fail_response时生效
        'visit_fail_response' => null,              // 访问受限时的响应信息闭包回调
        'driver_name' => CounterFixed::class,       // 限流算法驱动
    ];

    public static array $duration = [
        's' => 1,
        'm' => 60,
        'h' => 3600,
        'd' => 86400,
    ];

    /**
     * 缓存对象
     * @var CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * 配置参数
     * @var array
     */
    protected array $config = [];

    protected int $wait_seconds = 0;    // 下次合法请求还有多少秒
    protected int $now = 0;             // 当前时间戳
    protected int $max_requests = 0;    // 规定时间内允许的最大请求次数
    protected int $expire = 0;          // 规定时间
    protected int $remaining = 0;       // 规定时间内还能请求的次数

    /**
     * Throttle constructor.
     * @param Cache  $cache
     * @param Config $config
     */
    public function __construct(Cache $cache, Config $config)
    {
        $this->cache  = $cache;
        $this->config = array_merge(static::$default_config, $config->get('throttle', []));
    }

    /**
     * 请求是否允许
     * @param Request $request
     * @return bool
     */
    protected function allowRequest(Request $request): bool
    {
        // 若请求类型不在限制内
        if (!in_array($request->method(), $this->config['visit_method'])) {
            return true;
        }

        $key = $this->getCacheKey($request);
        if (null === $key) {
            return true;
        }
        [$max_requests, $duration] = $this->parseRate($this->config['visit_rate']);

        $micro_now = microtime(true);   // float

        $driver = Container::getInstance()->invokeClass($this->config['driver_name']);
        if (!($driver instanceof ThrottleAbstract)) {
            throw new TypeError('The throttle driver must extends ' . ThrottleAbstract::class);
        }
        $allow = $driver->allowRequest($key, $micro_now, $max_requests, $duration, $this->cache);

        if ($allow) {
            // 允许访问
            $this->now = (int) $micro_now;
            $this->expire = $duration;
            $this->max_requests = $max_requests;
            $this->remaining = $max_requests - $driver->getCurRequests();
            return true;
        }

        $this->wait_seconds = $driver->getWaitSeconds();
        return false;
    }

    /**
     * 处理限制访问
     * @param Request $request
     * @param Closure $next
     * @param array $params
     * @return Response
     */
    public function handle(Request $request, Closure $next, array $params=[]): Response
    {
        if ($params) {
            $this->config = array_merge($this->config, $params);
        }

        $allow = $this->allowRequest($request);
        if (!$allow) {
            // 访问受限
            throw $this->buildLimitException($this->wait_seconds, $request);
        }
        $response = $next($request);
        if (200 <= $response->getCode() && 300 > $response->getCode() && $this->config['visit_enable_show_rate_limit']) {
            // 将速率限制 headers 添加到响应中
            $response->header([
                'X-Rate-Limit-Limit' => $this->max_requests,
                'X-Rate-Limit-Remaining' => max($this->remaining, 0),
                'X-Rate-Limit-Reset' => $this->now + $this->expire,
            ]);
        }
        return $response;
    }

    /**
     * 生成缓存的 key
     * @param Request $request
     * @return null|string
     */
    protected function getCacheKey(Request $request): ?string
    {
        $key = $this->config['key'];

        if ($key instanceof Closure) {
            $key = Container::getInstance()->invokeFunction($key, [$this, $request]);
        }

        if ($key === null || $key === false || $this->config['visit_rate'] === null) {
            // 关闭当前限制
            return null;
        }

        if ($key === true) {
            $key = $request->ip();
        } elseif (is_string($key) && str_contains($key, '__')) {
            $key = str_replace(['__CONTROLLER__', '__ACTION__', '__IP__'], [$request->controller(), $request->action(), $request->ip()], $key);
        }

        return md5($this->config['prefix'] . $key . $this->config['driver_name']);
    }

    /**
     * 解析频率配置项
     * @param string $rate
     * @return int[]
     */
    protected function parseRate(string $rate): array
    {
        [$num, $period] = explode("/", $rate);
        $max_requests = (int) $num;
        $duration = static::$duration[$period] ?? (int) $period;
        return [$max_requests, $duration];
    }

    /**
     * 设置速率
     * @param string $rate '10/m'  '20/300'
     * @return $this
     */
    public function setRate(string $rate): self
    {
        $this->config['visit_rate'] = $rate;
        return $this;
    }

    /**
     * 设置缓存驱动
     * @param CacheInterface $cache
     * @return $this
     */
    public function setCache(CacheInterface $cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * 设置限流算法类
     * @param string $class_name
     * @return $this
     */
    public function setDriverClass(string $class_name): self
    {
        $this->config['driver_name'] = $class_name;
        return $this;
    }

    /**
     * 构建 Response Exception
     * @param int     $wait_seconds
     * @param Request $request
     * @return HttpResponseException
     */
    public function buildLimitException(int $wait_seconds, Request $request): HttpResponseException {
        $visitFail = $this->config['visit_fail_response'] ?? null;
        if ($visitFail instanceof Closure) {
            $response = Container::getInstance()->invokeFunction($visitFail, [$this, $request, $wait_seconds]);
            if (!$response instanceof Response) {
                throw new TypeError(sprintf('The closure must return %s instance', Response::class));
            }
        } else {
            $content = str_replace('__WAIT__', (string) $wait_seconds, $this->config['visit_fail_text']);
            $response = Response::create($content)->code($this->config['visit_fail_code']);
        }
        if ($this->config['visit_enable_show_rate_limit']) {
            $response->header(['Retry-After' => $wait_seconds]);
        }
        return new HttpResponseException($response);
    }

}
