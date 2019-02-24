<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace app\admin\model;

use think\Model;
use think\facade\Request;

/**
 * 统一授权模型
 * @package app\admin\model
 */
class Access extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'admin_access';

    /**
     * 获取用户授权节点
     * @param int $uid 用户id
     * @param string $group 权限分组，可以以点分开模型名称和分组名称，如user.group
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|bool
     */
    public function getAuthNode($uid = 0, $group = '')
    {
        if ($uid == 0 || $group == '') {
            $this->error = '缺少参数';
            return false;
        }

        if (strpos($group, '.')) {
            list($module, $group) = explode('.', $group);
        } else {
            $module = Request::module();
        }

        $map = [
            'module' => $module,
            'group'  => $group,
            'uid'    => $uid
        ];

        return $this->where($map)->column('nid');
    }

    /**
     * 检查用户的某个节点是否授权
     * @param int $uid 用户id
     * @param string $group $group 权限分组，可以以点分开模型名称和分组名称，如user.group
     * @param int $node 需要检查的节点id
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    public function checkAuthNode($uid = 0, $group = '', $node = 0)
    {
        if ($uid == 0 || $group == '' || $node == 0) {
            $this->error = '缺少参数';
            return false;
        }

        // 获取该用户的所有授权节点
        $nodes = $this->getAuthNode($uid, $group);
        if (!$nodes) {
            $this->error = '该用户没有授权任何节点';
            return false;
        }

        $nodes = array_flip($nodes);
        if (isset($nodes[$node])) {
            return true;
        } else {
            $this->error = '未授权';
            return false;
        }
    }
}
