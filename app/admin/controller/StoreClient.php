<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\service\StorePackageInstallerService;
use app\admin\service\StoreClientService;
use app\common\attribute\Permission;
use think\response\Json;
use Throwable;

/**
 * 官方商店客户端控制器
 */
#[Permission('官方商店', icon: 'ti ti-shopping-bag-search', sort: 105)]
final class StoreClient extends Auth
{
    /**
     * 商店客户端首页
     * @return string
     */
    public function index(): string
    {
        $this->assign([
            'storeClientBaseUrl' => (string)session('store_client.base_url', ''),
            'storeClientToken'   => (string)session('store_client.token', ''),
        ]);

        return $this->fetch('store_client/index');
    }

    /**
     * 登录远端商店
     * @return Json
     */
    public function login(): Json
    {
        try {
            $data = app(StoreClientService::class)->login(
                trim((string)$this->request->post('base_url')),
                trim((string)$this->request->post('email')),
                (string)$this->request->post('password')
            );
        } catch (Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        session('store_client.base_url', trim((string)$this->request->post('base_url')));
        session('store_client.token', (string)($data['token'] ?? ''));

        return json(['code' => 0, 'msg' => '登录成功']);
    }

    /**
     * 查询远端已购商品
     * @return Json
     */
    public function purchases(): Json
    {
        try {
            [$baseUrl, $token] = $this->requireConnection();
            $rows = app(StoreClientService::class)->purchases($baseUrl, $token);
        } catch (Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        return json(['code' => 0, 'data' => $rows]);
    }

    /**
     * 下载并安装远端已购商品
     * @return Json
     */
    public function install(): Json
    {
        $tempPath = '';

        try {
            [$baseUrl, $token] = $this->requireConnection();
            $type = trim((string)$this->request->post('product_type'));
            $name = trim((string)$this->request->post('product_name'));
            $productId = (int)$this->request->post('product_id/d', 0);
            $versionId = (int)$this->request->post('version_id/d', 0);

            if (!in_array($type, ['app', 'plugin'], true)) {
                throw new \RuntimeException('暂不支持该商品类型');
            }

            if ($productId <= 0 || $versionId <= 0) {
                throw new \RuntimeException('商品参数不完整');
            }

            $download = app(StoreClientService::class)->downloadPackage($baseUrl, $token, $productId, $versionId, $name !== '' ? $name : $type);
            $tempPath = $download['path'];
            $result = app(StorePackageInstallerService::class)->installDownloadedPackage(
                $type,
                $download['path'],
                $download['filename'],
                $this->getCurrentUserId()
            );
        } catch (Throwable $e) {
            if ($tempPath !== '' && is_file($tempPath)) {
                @unlink($tempPath);
            }

            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }

        if ($tempPath !== '' && is_file($tempPath)) {
            @unlink($tempPath);
        }

        return json(['code' => 0, 'msg' => '安装成功', 'data' => $result]);
    }

    /**
     * 获取当前已保存的商店连接信息
     * @return array{0:string,1:string}
     */
    private function requireConnection(): array
    {
        $baseUrl = trim((string)session('store_client.base_url', ''));
        $token = trim((string)session('store_client.token', ''));

        if ($baseUrl === '' || $token === '') {
            throw new \RuntimeException('请先连接官方商店');
        }

        return [$baseUrl, $token];
    }
}
