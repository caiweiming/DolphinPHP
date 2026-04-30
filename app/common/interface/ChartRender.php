<?php
declare(strict_types=1);

namespace app\common\interface;

/**
 * 图表渲染器接口
 */
interface ChartRender extends ZRender
{
    /**
     * 初始化图表
     * @param string $id 图表标识
     * @return static
     */
    public function init(string $id): static;

    /**
     * 设置图表标题
     * @param string $value 标题
     * @param string $subtitle 副标题
     * @return static
     */
    public function title(string $value = '', string $subtitle = ''): static;

    /**
     * 设置图表类型
     * @param string $type 图表类型
     * @return static
     */
    public function type(string $type = 'line'): static;

    /**
     * 设置地图专项扩展
     * @param string $mapKey 地图扩展标识
     * @param array $data 地图数据契约
     * @return static
     */
    public function map(string $mapKey = '', array $data = []): static;

    /**
     * 设置地图专项数据
     * @param array $data 地图数据
     * @param bool $merge 是否合并已有数据
     * @return static
     */
    public function mapData(array $data = [], bool $merge = true): static;

    /**
     * 设置原生图表配置
     * @param array $option 图表配置
     * @param bool $replace 是否完全替换默认配置
     * @return static
     */
    public function option(array $option = [], bool $replace = false): static;

    /**
     * 设置图表数据源
     * @param string $url 数据请求地址
     * @param string $method 请求方法
     * @param array $params 请求参数
     * @return static
     */
    public function dataset(string $url = '', string $method = 'GET', array $params = []): static;
}
