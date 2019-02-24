<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\common\builder\aside;

use app\common\builder\ZBuilder;

/**
 * 侧栏构建器
 * @package app\common\builder\sidebar
 */
class Builder extends ZBuilder
{
    /**
     * @var bool 是否返回数据
     */
    private $_return = false;

    /**
     * @var string 当前tab
     */
    private $_curr_tab = '';

    /**
     * 架构函数
     */
    public function __construct()
    {
        // 如果动作为new，则重新创建侧栏内容
        if (static::$action == 'new') {
            static::$vars['aside'] = [];
        }
        parent::__construct();
    }

    /**
     * 设置Tab按钮列表
     * @param array $tab_list Tab列表 如：['tab1' => '标题', 'tab2' => '标题2']
     * @param string $curr_tab 当前tab名
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setTabNav($tab_list = [], $curr_tab = '')
    {
        if (!empty($tab_list)) {
            $tab_nav = [
                'tab_list' => $tab_list,
                'curr_tab' => $curr_tab,
            ];
            static::$vars['aside']['tab_nav'] = $tab_nav;

            foreach ($tab_list as $tab => $content) {
                if (!isset(static::$vars['aside']['tab_con'][$tab])) {
                    static::$vars['aside']['tab_con'][$tab] = [];
                }
            }
        }
        return $this;
    }

    /**
     * 追加Tab按钮列表
     * @param string $tab tab名称
     * @param string $content tab内容
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addTabNav($tab = '', $content = '')
    {
        if ($tab != '' && $content !='') {
            static::$vars['aside']['tab_nav']['tab_list'][$tab] = $content;
            if (!isset(static::$vars['aside']['tab_con'][$tab])) {
                static::$vars['aside']['tab_con'][$tab] = [];
            }
        }
        return $this;
    }

    /**
     * 设置当前tab
     * @param string $tab tab名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setCurrTab($tab = '')
    {
        if ($tab != '') {
            $this->_curr_tab = $tab;
        }
        return $this;
    }

    /**
     * 设置单个tab内容
     * @param string $tab tab名称
     * @param array $content tab内容
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setTabCon($tab = '', $content = [])
    {
        if ($tab != '' && !empty($content)) {
            $this->_return = true;
            foreach ($content as &$block) {
                $block = call_user_func_array([$this, 'addBlock'], $block);
            }
            $this->_return = false;
            static::$vars['aside']['tab_con'][$tab] = $content;
        }
        return $this;
    }

    /**
     * 一次性设置多个tab内容
     * @param array $content tab内容 ['tab' => ['block1', 'block2'..]]
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function setTabCons($content = [])
    {
        foreach ($content as $tab => &$item) {
            $this->setTabCon($tab, $item);
        }

        return $this;
    }

    /**
     * 追加tab内容
     * @param string $tab tab名称
     * @param array $content tab内容
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this
     */
    public function addTabCon($tab = '', $content = [])
    {
        if ($tab != '' && !empty($content)) {
            $this->_return = true;
            foreach ($content as &$block) {
                $block = call_user_func_array([$this, 'addBlock'], $block);
            }
            $this->_return = false;
            if (isset(static::$vars['aside']['tab_con'][$tab])) {
                static::$vars['aside']['tab_con'][$tab] = array_merge(static::$vars['aside']['tab_con'][$tab], $content);
            }
        }
        return $this;
    }

    /**
     * 添加区块
     * @param string $type 类型：recent/online/switch/html
     * @param string $title 标题
     * @param array $list 列表
     * @author 蔡伟明 <314013107@qq.com>
     * @return $this|array
     */
    public function addBlock($type = '', $title = '', $list = [])
    {
        if ($type != '') {
            if ($type == 'html') {
                $title = $this->display($title, $list);
            }
            $block = [
                'type'  => $type,
                'title' => $title,
                'list'  => $list
            ];

            if ($this->_return) {
                return $block;
            }

            static::$vars['aside']['blocks'][] = $block;
        }
        return $this;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        // 设置默认标签页
        if ($this->_curr_tab != '') {
            static::$vars['aside']['tab_nav']['curr_tab'] = $this->_curr_tab;
        }

        // 设置侧栏变量，供没有经过ZBuilder渲染页面的时候用
        $this->assign('aside', static::$vars['aside']);
    }
}
