### 作用
通过本中间件可限定用户在一段时间内的访问次数，可用于保护接口防爬防爆破的目的。

### 安装
```
composer require topthink/think-throttle
```
安装后会自动为项目生成 `conf/throttle.php` 配置文件，安装后组件不会自动启用，需要手动设置。

### 开启
组件以中间件的方式进行工作，因此它的开启与其他中间件一样，例如在全局中间件中使用 `app/middleware.php` :
```
<?php
return [
    \think\middleware\Throttle::class,
];
```
### 配置说明
在 `config/throttle.php` 配置选项:
```
<?php
// 中间件配置
return [
    // 缓存键前缀，防止键值与其他应用冲突
    'prefix' => 'throttle_',
    // 缓存的键，true 表示使用来源ip
    'key' => true,
    // 要被限制的请求类型, eg: GET POST PUT DELETE HEAD
    'visit_method' => ['GET'],
    // 设置访问频率，例如 '10/m' 指的是允许每分钟请求10次。值 null 表示不限制， eg: null 10/m  20/h  300/d 200/300
    'visit_rate' => '100/m',
    // 访问受限时返回的响应
    'visit_fail_response' => function (Throttle $throttle, Request $request, int $wait_seconds) {
        return Response::create('Too many requests, try again after ' . $wait_seconds . ' seconds.')->code(429);
    },
];
```

当配置项满足以下条件任何一个时，不会限制访问频率：
1. `key` 值为 `false` 或 `null`；
2. `visit_rate` 值为 `null`。

其中 `key` 用来设置缓存键的；而 `visit_rate` 用来设置访问频率，单位可以是秒，分，时，天，例如：`1/s`, `10/m`, `98/h`, `100/d` , 也可以是 `100/600` （600 秒内最多 100 次请求）。

### 灵活定制
示例一：针对用户个体做限制， `key` 的值可以设为函数，该函数返回新的缓存键值(需要Session支持)，例如：
```
'key' => function($throttle, $request) {
    $user_id = $request->session->get('user_id');
    return $user_id ;
},
```
实例二：也可以在回调函数里针对不同控制器和方法定制生成key，中间件会进行转换:
```
'key' => function($throttle, $request) {
    return '__CONTROLLER__/__ACTION__/__IP__';
},
```
或者直接设置:
```
'key' => '__CONTROLLER__/__ACTION__/__IP__',
```
PS：此示例需要本中间件在路由中间件后启用，这样预设的替换功能才会生效。

示例三：允许在闭包内修改本次访问频率或临时更换限流策略：
```
'key' => function($throttle, $request) {
    $throttle->setRate('5/m');                      // 设置频率
    $throttle->setDriverClass(CounterSlider::class);// 设置限流策略
    return true;
},
```

示例四：允许在路由定义中独立配置(1.3.x 版本支持)
```
Route::group(function() {
    //路由注册

})->middleware(\think\middleware\Throttle::class, [
    'visit_rate' => '20/m',
    'key' => '__CONTROLLER__/__ACTION__/__IP__',
]);
```
## 版本与 TP 适配关系
```
2.0.x -> thinkphp 8.0
1.x.x -> thinkphp 6.0/6.1
0.5.x -> thinkphp 5.1
```

## 更新日志
版本 2.0.x 的可从 1.x 无缝升级；

版本 1.3.x 的配置形式完全兼容版本 1.2.x 内容，可以无缝升级；

版本 1.2.x 的配置形式完全兼容版本 1.1.x 内容，可以无缝升级；

### 2.0.x 更新
- 适配 thinkphp 8.0；
- 所有 `php` 文件都采用 `declare(strict_types=1);` 强类型约束；

### 1.3.x 更新
- 可通过配置 `visit_fail_response` 自定义限流响应；
- 速率限制信息对响应头状态码20x生效；
- 强类型声明（有自行继承扩展的需注意）；
- 默认配置文件中去除 `visit_fail_code` 与 `visit_fail_text` 配置项，但代码中依然保留这两项配置的兼容；
- 新增响应体中设置速率限制的头部信息的开关；
- 配置文件添加设置算法驱动；
- 支持在路由定义中设置中间件配置；
- 修复漏桶算法中计算等待时间的错误问题；

### 1.2.x 更新
- 可对要限制的请求类型进行自定义设置
- 默认配置请求频率设置 '100/m'

### 1.1.x 更新
- 添加漏桶限流算法, 令牌桶算法, 计数固定窗口, 滑动窗口共四种限流策略；
- 公共数据改为静态属性，节省内存分配；
- 重构中间件接口，便于扩展更多的限流算法；
- 默认策略使用计数固定窗口的策略；
- 时间取毫秒，某些限流算法需要；
- 只使用一个缓存键完成计数固定窗口，减少缓存读取；
- 开放更多 `set*` 方法，支持链式操作；
- 禁止访问时，改用抛出 `HttpResponseException`；
