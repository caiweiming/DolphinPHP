<?php
declare(strict_types=1);

namespace app\common\provider;

use think\Service;
use app\common\render\Chart as ChartRender;
use app\common\render\Form as FormRender;
use app\common\render\Table as TableRender;
use app\common\render\Page as PageRender;
use app\common\interface\ChartRender as ChartRenderInterface;
use app\common\interface\FormRender as FormRenderInterface;
use app\common\interface\TableRender as TableRenderInterface;
use app\common\interface\PageRender as PageRenderInterface;
use app\common\command\IconLib;
use app\common\command\MakeDpChartMap;
use app\common\command\MakeDpChartType;
use app\common\command\MakeDpFormItem;
use app\common\command\MakeDpTableItem;

/**
 * 渲染服务提供者
 */
class RenderServiceProvider extends Service
{
    /**
     * 服务注册
     */
    public function register(): void
    {
        // 注册图表渲染器
        $this->app->bind(ChartRenderInterface::class, ChartRender::class);
        $this->app->bind('chart.render', ChartRender::class);

        // 注册表单渲染器
        $this->app->bind(FormRenderInterface::class, FormRender::class);
        $this->app->bind('form.render', FormRender::class);
        
        // 注册表格渲染器
        $this->app->bind(TableRenderInterface::class, TableRender::class);
        $this->app->bind('table.render', TableRender::class);
        
        // 注册页面渲染器
        $this->app->bind(PageRenderInterface::class, PageRender::class);
        $this->app->bind('page.render', PageRender::class);
    }
    
    /**
     * 服务启动
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                IconLib::class,
                MakeDpChartMap::class,
                MakeDpChartType::class,
                MakeDpFormItem::class,
                MakeDpTableItem::class,
            ]);
        }
    }
}
