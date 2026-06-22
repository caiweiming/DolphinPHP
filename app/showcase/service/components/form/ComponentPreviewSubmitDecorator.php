<?php
declare(strict_types=1);

namespace app\showcase\service\components\form;

/**
 * 组件页预览表单提交装饰器
 *
 * 统一为组件预览表单补齐 JSON 提交地址与示例提交所需的隐藏字段，
 * 避免每个 section 单独维护重复的 action/example_key/demo_title 配置。
 */
final class ComponentPreviewSubmitDecorator
{
    private const EXAMPLE_KEY = 'component.preview';

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    public function decorate(array $page): array
    {
        $componentTitle = trim((string) ($page['component']['title'] ?? '组件预览'));
        $sections = [];

        foreach ((array) ($page['sections'] ?? []) as $section) {
            if (!is_array($section)) {
                $sections[] = $section;
                continue;
            }

            $sectionTitle = trim((string) ($section['title'] ?? '示例'));
            $demoTitle = trim($componentTitle . ' - ' . $sectionTitle, ' -');
            $section['preview_html'] = $this->decorateHtml(
                (string) ($section['preview_html'] ?? ''),
                self::EXAMPLE_KEY,
                $demoTitle !== '' ? $demoTitle : '组件预览提交'
            );
            $sections[] = $section;
        }

        $page['sections'] = $sections;

        return $page;
    }

    private function decorateHtml(string $html, string $exampleKey, string $demoTitle): string
    {
        if ($html === '' || !str_contains($html, '<form')) {
            return $html;
        }

        $submitUrl = (string) dp_url('showcase/admin.demo_api/submit');

        $html = preg_replace('/action="[^"]*"/', 'action="' . htmlspecialchars($submitUrl, ENT_QUOTES, 'UTF-8') . '"', $html, 1) ?? $html;

        $hiddenInputs = '';

        if (!str_contains($html, 'name="example_key"')) {
            $hiddenInputs .= sprintf(
                '<input type="hidden" name="example_key" value="%s">',
                htmlspecialchars($exampleKey, ENT_QUOTES, 'UTF-8')
            );
        }

        if (!str_contains($html, 'name="demo_title"')) {
            $hiddenInputs .= sprintf(
                '<input type="hidden" name="demo_title" value="%s">',
                htmlspecialchars($demoTitle, ENT_QUOTES, 'UTF-8')
            );
        }

        if ($hiddenInputs === '') {
            return $html;
        }

        return preg_replace('/(<form\b[^>]*>)/', '$1' . $hiddenInputs, $html, 1) ?? $html;
    }
}
