<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2026 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\install\controller;

use app\install\service\DatabaseProvisionService;
use app\install\service\EnvironmentCheckService;
use app\install\service\InstallExecutorService;
use app\install\service\InstallProgressService;
use app\install\service\InstallWizardStateService;
use RuntimeException;
use app\install\validate\Wizard;
use think\facade\View;
use Throwable;

/**
 * 安装向导入口控制器
 */
class Index
{
    public function index(): string
    {
        $this->assignStepVars(1);

        return View::fetch('welcome');
    }

    public function environment(EnvironmentCheckService $service, InstallWizardStateService $stateService): string
    {
        $report = $service->check();
        if (($report['all_passed'] ?? false) === true) {
            $stateService->saveStep('environment', ['passed' => true]);
        }

        $this->assignStepVars(2);
        View::assign('report', $report);

        return View::fetch('environment');
    }

    public function database(InstallWizardStateService $stateService): string
    {
        $this->ensureStep($stateService, 'environment', '/install/index/environment');
        $this->assignStepVars(3);
        View::assign('values', $stateService->step('database'));

        return View::fetch('database');
    }

    public function admin(InstallWizardStateService $stateService): string
    {
        $this->ensureStep($stateService, 'database', '/install/index/database');
        $this->assignStepVars(4);
        View::assign('values', $stateService->step('admin'));

        return View::fetch('admin');
    }

    public function execute(
        InstallWizardStateService $stateService,
        InstallExecutorService $executorService,
        InstallProgressService $progressService
    ): string
    {
        $this->ensureStep($stateService, 'admin', '/install/index/admin');
        $progressService->reset();
        $this->assignStepVars(5);
        View::assign('databaseValues', $stateService->step('database'));
        View::assign('adminValues', $stateService->step('admin'));
        View::assign('progressState', $progressService->ensure($executorService->steps()));

        return View::fetch('executing');
    }

    public function testDatabase(Wizard $validate, DatabaseProvisionService $provisionService): \think\response\Json
    {
        $payload = request()->only(['hostname', 'hostport', 'database', 'username', 'password', 'prefix', 'create_database'], 'post');

        if (!$validate->scene('database')->check($payload)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        try {
            $provisionService->ensureReady($payload);
        } catch (Throwable $exception) {
            return json(['code' => 0, 'msg' => '数据库连接失败：' . $exception->getMessage()]);
        }

        return json([
            'code' => 1,
            'msg'  => !empty($payload['create_database']) ? '数据库连接正常，建库检查已完成' : '数据库连接正常',
        ]);
    }

    public function saveDatabase(
        Wizard $validate,
        InstallWizardStateService $stateService,
        DatabaseProvisionService $provisionService
    ): \think\response\Json {
        $payload = request()->only(['hostname', 'hostport', 'database', 'username', 'password', 'prefix', 'create_database'], 'post');

        if (!$validate->scene('database')->check($payload)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        try {
            $provisionService->ensureReady($payload);
        } catch (Throwable $exception) {
            return json(['code' => 0, 'msg' => '数据库连接失败：' . $exception->getMessage()]);
        }

        $stateService->saveStep('database', $payload);

        return json([
            'code' => 1,
            'msg'  => '数据库配置已保存',
            'url'  => '/install/index/admin',
        ]);
    }

    public function saveAdmin(Wizard $validate, InstallWizardStateService $stateService): \think\response\Json
    {
        $payload = request()->only(['username', 'nickname', 'password', 'password_confirm', 'email'], 'post');

        if (trim((string)($payload['username'] ?? '')) === '') {
            return json(['code' => 0, 'msg' => '管理员账号不能为空']);
        }

        if (!$validate->scene('admin')->check($payload)) {
            return json(['code' => 0, 'msg' => $validate->getError()]);
        }

        $stateService->saveStep('admin', $payload);

        return json([
            'code' => 1,
            'msg'  => '超级管理员配置已保存',
            'url'  => '/install/index/execute',
        ]);
    }

    public function runInstall(InstallWizardStateService $stateService, InstallExecutorService $executorService): \think\response\Json
    {
        $database = $stateService->step('database');
        $admin    = $stateService->step('admin');

        if ($database === [] || $admin === []) {
            return json(['code' => 0, 'msg' => '安装步骤不完整，请返回上一步重新填写']);
        }

        try {
            $executorService->execute($database, $admin, (string)request()->ip());
            $stateService->clear();
        } catch (Throwable $exception) {
            return json(['code' => 0, 'msg' => $exception->getMessage()]);
        }

        return json([
            'code' => 1,
            'msg'  => '安装成功',
        ]);
    }

    public function runInstallStep(
        InstallWizardStateService $stateService,
        InstallExecutorService $executorService,
        InstallProgressService $progressService
    ): \think\response\Json {
        $database = $stateService->step('database');
        $admin    = $stateService->step('admin');

        if ($database === [] || $admin === []) {
            return json(['code' => 0, 'msg' => '安装步骤不完整，请返回上一步重新填写']);
        }

        $progressState = $progressService->ensure($executorService->steps());
        $stepKey       = $progressService->nextStepKey();

        if ($stepKey === null) {
            $progressState = $progressState['finished'] ?? false ? $progressState : $progressService->markFinished();

            return json([
                'code'     => 1,
                'msg'      => '安装成功',
                'state'    => $progressState,
                'complete' => true,
            ]);
        }

        $progressService->markRunning($stepKey);

        try {
            switch ($stepKey) {
                case 'write_config':
                    $executorService->writeDatabaseConfig($database);
                    break;
                case 'init_connection':
                    $executorService->initializeConnection($database);
                    break;
                case 'import_database':
                    $executorService->importDatabase($database);
                    break;
                case 'configure_admin':
                    $executorService->configureAdmin($database, $admin, (string)request()->ip());
                    break;
                case 'write_lock':
                    $executorService->writeInstallLock($database, $admin);
                    $progressService->markComplete('write_lock');
                    $progressService->markRunning('clear_state');
                    $stateService->clear();
                    $progressState = $progressService->markComplete('clear_state');
                    $progressState = $progressService->markFinished();

                    return json([
                        'code'     => 1,
                        'msg'      => '安装成功',
                        'state'    => $progressState,
                        'complete' => true,
                    ]);
                case 'clear_state':
                    $stateService->clear();
                    break;
                default:
                    throw new RuntimeException('未知的安装步骤：' . $stepKey);
            }
        } catch (Throwable $exception) {
            return json([
                'code'  => 0,
                'msg'   => $exception->getMessage(),
                'state' => $progressService->markError($stepKey, $exception->getMessage()),
            ]);
        }

        $progressState = $progressService->markComplete($stepKey);
        if ($progressService->nextStepKey() === null) {
            $progressState = $progressService->markFinished();

            return json([
                'code'     => 1,
                'msg'      => '安装成功',
                'state'    => $progressState,
                'complete' => true,
            ]);
        }

        return json([
            'code'     => 1,
            'msg'      => '步骤执行成功',
            'state'    => $progressState,
            'complete' => false,
        ]);
    }

    private function ensureStep(InstallWizardStateService $stateService, string $step, string $fallback): void
    {
        if (!$stateService->hasStep($step)) {
            redirect($fallback)->send();
            exit;
        }
    }

    private function assignStepVars(int $currentStep): void
    {
        View::assign('wizardSteps', [
            1 => '欢迎使用',
            2 => '环境检测',
            3 => '数据库配置',
            4 => '配置管理员',
            5 => '执行安装',
        ]);
        View::assign('currentStep', $currentStep);
    }
}
