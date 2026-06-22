<?php
declare(strict_types=1);

namespace app\showcase\service;

use app\common\application\AppLifecycleContext;
use app\showcase\service\demo\ShowcaseTableDemoDataService;

final class Hook
{
    public static function install(AppLifecycleContext $context): void
    {
        app(ShowcaseTableDemoDataService::class)->resetDemoData();
    }

    public static function uninstall(AppLifecycleContext $context): void
    {
    }
}
