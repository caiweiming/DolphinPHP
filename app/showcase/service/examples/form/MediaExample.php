<?php
declare(strict_types=1);

namespace app\showcase\service\examples\form;

use app\common\render\Form;

/**
 * Showcase 上传与媒体示例
 */
final class MediaExample
{
    public function render(): string
    {
        return Form::make('showcase_media_upload', '上传与媒体')
            ->action((string) dp_url('showcase/admin.demo_api/submit'))
            ->header(false)
            ->data([
                'demo_title' => '媒体上传演示',
                'cover' => [],
                'attachment' => [],
                'poster' => '',
                'example_key' => 'media.upload',
            ])
            ->items([
                ['text:*', 'demo_title', '演示标题', '请输入演示标题'],
                ['image', 'cover', '封面图', '上传一张封面图'],
                ['file', 'attachment', '附件', '上传一个文件'],
                ['cropper', 'poster', '裁剪海报', '上传并裁剪海报'],
                ['hidden', 'example_key', '', '', 'media.upload'],
            ])
            ->fetch();
    }
}
