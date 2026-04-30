<?php
declare (strict_types=1);

namespace form\fieldset;

use app\common\abstract\FormItem;
use app\common\render\Form as FormRender;
use Exception;

/**
 * 字符集
 * @package form\fieldset
 */
class Item extends FormItem
{
    /**
     * 入口方法
     * @param mixed $params
     * @param FormRender|null $form
     * @return array
     * @throws Exception
     */
    public function handle(array $params = [], FormRender $form = null): array
    {
        foreach ($params['options'] as $key => $field) {
            $params['options'][$key] = $form->item($field, true);
        }
        return $params;
    }
}
