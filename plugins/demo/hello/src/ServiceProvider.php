<?php
declare(strict_types=1);

namespace Plugins\Demo\Hello;

use app\common\plugin\AbstractPluginServiceProvider;
use Plugins\Demo\Hello\Chart\DemoAreaType;
use Plugins\Demo\Hello\Command\HelloCommand;
use Plugins\Demo\Hello\Component\DemoComponent;
use Plugins\Demo\Hello\Form\DemoNoticeItem;
use Plugins\Demo\Hello\Middleware\TraceMiddleware;
use Plugins\Demo\Hello\Table\DemoBadgeItem;
use Plugins\Demo\Hello\Upload\DemoLocalDriver;

/**
 * 示例插件服务提供者
 */
class ServiceProvider extends AbstractPluginServiceProvider
{
    /**
     * 注册插件能力
     * @return void
     */
    protected function registerPlugin(): void
    {
        $this->registerFormItem('demo_notice', DemoNoticeItem::class);
        $this->registerTableItem('demo_badge', DemoBadgeItem::class);
        $this->registerChartType('demo_area', DemoAreaType::class);
        $this->registerComponentHandler('form', 'demo_hello', DemoComponent::class);
        $this->registerUploadDriver('demo_local', DemoLocalDriver::class);
        $this->registerCommand('demo:hello', HelloCommand::class);
        $this->registerMiddleware('demo_hello_trace', TraceMiddleware::class);
        $this->registerAdminSlot('topbar.tools', [
            'id'         => 'demo.hello.notifications',
            'type'       => 'fragment',
            'view'       => 'topbar/notifications_fragment.html',
            'sort'       => 120,
            'permission' => 'plugin.demo.hello',
            'assets'     => [
                'css'  => ['__PLUGIN_DEMO_HELLO__/shell-notifications.css'],
                'js'   => ['__PLUGIN_DEMO_HELLO__/shell-notifications.js'],
                'init' => [
                    'window.DolphinPluginDemoHello && window.DolphinPluginDemoHello.initShellNotifications && window.DolphinPluginDemoHello.initShellNotifications();',
                ],
            ],
        ]);
        $this->registerAdminSlot('user.menu', [
            'id'         => 'demo.hello.user_menu',
            'title'      => '打开示例插件',
            'icon'       => 'ti ti-layout-grid-add',
            'route'      => 'plugin/demo/hello',
            'sort'       => 120,
            'permission' => 'plugin.demo.hello',
        ]);
        $this->registerAdminWorkspaceCard([
            'id'         => 'demo.hello.workspace.summary',
            'title'      => '插件摘要',
            'region'     => 'top',
            'type'       => 'view',
            'sort'       => 80,
            'icon'       => 'ti ti-puzzle',
            'view'       => 'workspace/summary.html',
            'span'       => 3,
            'permission' => 'plugin.demo.hello',
            'assets'     => [
                'css' => ['__PLUGIN_DEMO_HELLO__/workspace.css'],
            ],
        ]);
    }
}
