<?php
declare(strict_types=1);

namespace app\showcase\service\registry;

/**
 * Showcase 表单示例目录
 */
final class ShowcaseFormExampleCatalog
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'basic' => ['title' => '基础输入', 'summary' => '文本、数字、状态与基础排版'],
            'choice' => ['title' => '选择与联动', 'summary' => '下拉、单选、联动和远程搜索'],
            'datetime' => ['title' => '时间与日期', 'summary' => '日期、时间与范围选择'],
            'media' => ['title' => '上传与媒体', 'summary' => '图片、文件和裁剪'],
            'rich' => ['title' => '富文本与复杂结构', 'summary' => '编辑器、扩展项和组合结构'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function examples(): array
    {
        return [
            'basic.text' => [
                'key' => 'basic.text',
                'renderer' => 'form',
                'group' => 'basic',
                'title' => '基础输入组合',
                'summary' => '展示 text、textarea、number、switch 等常用字段。',
                'builder' => 'app\\showcase\\service\\examples\\form\\BasicInputExample',
                'doc_links' => ['docs/表单/text.md', 'docs/表单/textarea.md', 'docs/表单/number.md', 'docs/表单/switch.md'],
                'source_refs' => [
                    ['path' => 'app/showcase/service/examples/form/BasicInputExample.php', 'label' => '示例构建器', 'description' => '定义字段和默认值。'],
                    ['path' => 'app/showcase/controller/admin/Form.php', 'label' => '详情控制器', 'description' => '承接预览与详情页。'],
                ],
                'capabilities' => [
                    'submit' => true,
                    'ajax' => false,
                    'upload' => false,
                ],
            ],
            'choice.linkage' => [
                'key' => 'choice.linkage',
                'renderer' => 'form',
                'group' => 'choice',
                'title' => '选择与联动',
                'summary' => '展示 select、radio_group、linkage 和远程选项数据源。',
                'builder' => 'app\\showcase\\service\\examples\\form\\ChoiceLinkageExample',
                'doc_links' => ['docs/表单/select.md', 'docs/表单/radioGroup.md', 'docs/表单/linkage.md', 'docs/表单/数据源接口协议.md'],
                'source_refs' => [
                    ['path' => 'app/showcase/service/examples/form/ChoiceLinkageExample.php', 'label' => '示例构建器', 'description' => '定义联动字段。'],
                    ['path' => 'app/showcase/controller/admin/DemoApi.php', 'label' => '远程接口', 'description' => '提供联动和搜索假数据。'],
                ],
                'capabilities' => [
                    'submit' => true,
                    'ajax' => true,
                    'upload' => false,
                ],
            ],
            'datetime.range' => [
                'key' => 'datetime.range',
                'renderer' => 'form',
                'group' => 'datetime',
                'title' => '日期时间与范围',
                'summary' => '展示 date、time、datetime_range 的组合。',
                'builder' => 'app\\showcase\\service\\examples\\form\\DatetimeExample',
                'doc_links' => ['docs/表单/date.md', 'docs/表单/time.md', 'docs/表单/datetimeRange.md'],
                'source_refs' => [
                    ['path' => 'app/showcase/service/examples/form/DatetimeExample.php', 'label' => '示例构建器', 'description' => '定义日期时间字段。'],
                ],
                'capabilities' => [
                    'submit' => true,
                    'ajax' => false,
                    'upload' => false,
                ],
            ],
            'media.upload' => [
                'key' => 'media.upload',
                'renderer' => 'form',
                'group' => 'media',
                'title' => '上传与媒体',
                'summary' => '展示 image、file、cropper 的最小可运行链路。',
                'builder' => 'app\\showcase\\service\\examples\\form\\MediaExample',
                'doc_links' => ['docs/表单/image.md', 'docs/表单/file.md', 'docs/表单/cropper.md'],
                'source_refs' => [
                    ['path' => 'app/showcase/service/examples/form/MediaExample.php', 'label' => '示例构建器', 'description' => '定义上传类字段。'],
                    ['path' => 'app/showcase/service/demo/FormDemoSubmitService.php', 'label' => '提交服务', 'description' => '回显上传示例结果。'],
                ],
                'capabilities' => [
                    'submit' => true,
                    'ajax' => false,
                    'upload' => true,
                ],
            ],
            'rich.structure' => [
                'key' => 'rich.structure',
                'renderer' => 'form',
                'group' => 'rich',
                'title' => '富文本与复杂结构',
                'summary' => '展示 vditor、fieldset、transfer 等复杂结构。',
                'builder' => 'app\\showcase\\service\\examples\\form\\RichStructureExample',
                'doc_links' => ['docs/表单/vditor.md', 'docs/表单/fieldset.md', 'docs/表单/transfer.md'],
                'source_refs' => [
                    ['path' => 'app/showcase/service/examples/form/RichStructureExample.php', 'label' => '示例构建器', 'description' => '定义复杂结构字段。'],
                ],
                'capabilities' => [
                    'submit' => true,
                    'ajax' => true,
                    'upload' => false,
                ],
            ],
        ];
    }
}
