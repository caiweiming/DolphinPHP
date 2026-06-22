<?php
declare(strict_types=1);

namespace app\showcase\service\examples\form;

use app\common\render\Form;

/**
 * Showcase 日期时间示例
 */
final class DatetimeExample
{
    public function render(): string
    {
        return Form::make('showcase_datetime_range', '日期时间与范围')
            ->action((string) dp_url('showcase/admin.demo_api/submit'))
            ->header(false)
            ->data([
                'demo_title' => '发布时间演示',
                'publish_date' => '2026-05-28',
                'publish_time' => '09:30:00',
                'active_window' => '2026-05-28 09:00:00 - 2026-05-31 18:00:00',
                'example_key' => 'datetime.range',
            ])
            ->items([
                ['text:*', 'demo_title', '演示标题', '请输入演示标题'],
                ['date', 'publish_date', '发布日期', '请选择日期'],
                ['time', 'publish_time', '发布时间', '请选择时间'],
                ['datetime_range', 'active_window', '活动时间段', '请选择时间范围'],
                ['hidden', 'example_key', '', '', 'datetime.range'],
            ])
            ->fetch();
    }
}
