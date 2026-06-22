<?php
declare(strict_types=1);

namespace app\showcase\service\examples\form;

use app\common\render\Form;
use app\showcase\service\registry\ShowcaseRegistryService;

/**
 * Showcase 表单示例构建工厂
 */
final class FormExampleFactory
{
    /**
     * 构建指定示例
     * @param string $key
     * @return array<string, mixed>
     */
    public function make(string $key): array
    {
        $registry = app(ShowcaseRegistryService::class);
        $example = $registry->findExample('form', $key);
        $builderClass = (string) ($example['builder'] ?? '');
        $builder = app($builderClass);

        Form::clearInstances();

        return array_merge($example, [
            'form_html' => $builder->render(),
        ]);
    }
}
