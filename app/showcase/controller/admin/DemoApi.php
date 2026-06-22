<?php
declare(strict_types=1);

namespace app\showcase\controller\admin;

use app\common\attribute\Permission;
use app\showcase\service\demo\FormDemoDataService;
use app\showcase\service\demo\FormDemoSubmitService;
use InvalidArgumentException;
use think\response\Json;

/**
 * Showcase 表单示例接口控制器
 */
#[Permission('表单示例接口', type: 'api', code: 'admin.demo_api', icon: 'ti ti-api', sort: 21)]
final class DemoApi extends Auth
{
    /**
     * 返回示例下拉或联动数据。
     */
    public function options(): Json
    {
        $dataset = (string) $this->request->param('dataset/s', '');
        $params = $this->request->param();
        $data = app(FormDemoDataService::class)->dataset($dataset, is_array($params) ? $params : []);

        if ($data === null) {
            return json([
                'code' => 0,
                'msg' => '未知数据集',
                'data' => [],
            ]);
        }

        return json([
            'code' => 1,
            'msg' => '获取成功',
            'data' => $data,
        ]);
    }

    /**
     * select_table 弹窗页。
     */
    public function selectTablePopup(): string
    {
        $dataset = (string) $this->request->param('dataset/s', 'members');
        $tableId = 'showcase_select_table_' . $dataset;

        $this->table->id($tableId)
            ->checkbox()
            ->page([
                'limit' => 10,
                'limits' => [10, 20, 50],
            ])
            ->search([
                [
                    'name' => 'keyword',
                    'placeholder' => '搜索昵称、手机号或部门',
                ],
            ])
            ->columns([
                ['id', 'ID', 'normal', [], ['width' => 80]],
                ['nickname', '昵称', 'normal', [], ['minWidth' => 120]],
                ['mobile', '手机号', 'normal', [], ['minWidth' => 150]],
                ['department', '部门', 'normal', [], ['minWidth' => 120]],
                ['status', '状态', 'status', ['禁用', '启用:green'], ['width' => 100]],
            ])
            ->data(function () use ($dataset) {
                $search = $this->request->param('_s/a', []);
                $keyword = trim((string) (($search['keyword'] ?? null) ?: $this->request->param('keyword/s', '')));
                $rows = app(FormDemoDataService::class)->dataset('select_table_' . $dataset, ['keyword' => $keyword]);

                return is_array($rows) ? $rows : [];
            })
            ->render();

        $this->page
            ->title('选择数据')
            ->row($this->table, ['class' => 'showcase-select-table-popup']);

        return $this->fetch('demo_api/select_table_popup');
    }

    /**
     * 处理示例提交。
     */
    public function submit(): Json
    {
        $exampleKey = (string) $this->request->post('example_key', '');
        $payload = $this->request->post();
        unset($payload['example_key']);

        try {
            $result = app(FormDemoSubmitService::class)->handle($exampleKey, is_array($payload) ? $payload : []);
        } catch (InvalidArgumentException $exception) {
            return json([
                'code' => 0,
                'msg' => $exception->getMessage(),
                'data' => [
                    'example_key' => $exampleKey,
                    'field' => null,
                    'payload' => is_array($payload) ? $payload : [],
                ],
            ]);
        }

        return json($result);
    }

    /**
     * 处理 Showcase 上传演示。
     */
    public function upload(): Json
    {
        $from = (string) $this->request->param('_from', 'file');
        $context = (string) $this->request->param('upload_context', 'showcase');
        $scene = (string) $this->request->param('scene', 'default');

        return json([
            'code' => 1,
            'msg' => '上传成功',
            'data' => [
                'success' => [[
                    'id' => 'showcase-upload-' . substr(md5($from . '|' . $context . '|' . $scene), 0, 12),
                    'name' => $from === 'image' ? 'showcase-image.png' : 'showcase-file.pdf',
                    'url' => $from === 'image' ? '/static/img/none.png' : '/favicon.ico',
                    'sha1' => sha1($from . '|' . $context . '|' . $scene),
                ]],
                'error' => [],
                'stats' => [
                    'total' => 1,
                    'success' => 1,
                    'error' => 0,
                    'all_success' => true,
                    'all_failed' => false,
                ],
            ],
        ]);
    }

    /**
     * 返回图表示例数据。
     */
    public function chart(): Json
    {
        $dataset = (string) $this->request->param('dataset/s', 'line');
        $delay = (int) $this->request->param('delay/d', 120);

        if ($delay > 0) {
            usleep($delay * 1000);
        }

        $data = match ($dataset) {
            'line' => [
                'dataset' => 'line',
                'type' => 'line',
                'categories' => ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
                'series' => [
                    ['name' => '访问量', 'data' => [168, 182, 191, 214, 250, 278, 302]],
                    ['name' => '下单量', 'data' => [28, 32, 36, 42, 49, 56, 61]],
                ],
            ],
            'bar' => [
                'dataset' => 'bar',
                'type' => 'bar',
                'categories' => ['华东', '华南', '华北', '西南', '西北'],
                'series' => [
                    ['name' => '销售额', 'data' => [520, 468, 420, 390, 360]],
                ],
            ],
            'pie' => [
                'dataset' => 'pie',
                'type' => 'pie',
                'series' => [
                    ['name' => '自然搜索', 'value' => 335],
                    ['name' => '广告投放', 'value' => 310],
                    ['name' => '私域运营', 'value' => 234],
                    ['name' => '老客直访', 'value' => 135],
                ],
            ],
            'scatter' => [
                'dataset' => 'scatter',
                'type' => 'scatter',
                'series' => [
                    ['name' => '样本 A', 'data' => [[12, 32], [16, 40], [18, 52], [22, 65], [28, 78]]],
                    ['name' => '样本 B', 'data' => [[10, 28], [14, 36], [21, 48], [25, 66], [30, 72]]],
                ],
            ],
            'empty' => [
                'dataset' => 'empty',
                'type' => 'line',
                'categories' => [],
                'series' => [],
            ],
            default => null,
        };

        if ($data === null) {
            return json([
                'code' => 0,
                'msg' => '未知图表数据集',
                'data' => [],
            ]);
        }

        return json([
            'code' => 1,
            'msg' => '获取成功',
            'data' => $data,
        ]);
    }

    /**
     * 返回地图图表示例数据。
     */
    public function chartMap(): Json
    {
        $dataset = (string) $this->request->param('dataset/s', 'demo_region');

        $data = match ($dataset) {
            'demo_region' => [
                'map' => [
                    'data' => [
                        'regions' => [
                            ['code' => '1001', 'value' => 92],
                            ['code' => '1002', 'value' => 76],
                            ['code' => '1003', 'value' => 58],
                        ],
                        'visualMap' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                        'overlays' => [[
                            'type' => 'effectScatter',
                            'data' => [
                                ['code' => '1001', 'value' => 92],
                                ['code' => '1002', 'value' => 76],
                            ],
                        ]],
                    ],
                ],
            ],
            'china_guangdong' => [
                'map' => [
                    'data' => [
                        'regions' => [
                            ['code' => '440100', 'value' => 96],
                            ['code' => '440300', 'value' => 98],
                            ['code' => '440600', 'value' => 82],
                            ['code' => '441900', 'value' => 88],
                        ],
                        'visualMap' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
            default => null,
        };

        if ($data === null) {
            return json([
                'code' => 0,
                'msg' => '未知地图数据集',
                'data' => [],
            ]);
        }

        return json([
            'code' => 1,
            'msg' => '获取成功',
            'data' => $data,
        ]);
    }
}
