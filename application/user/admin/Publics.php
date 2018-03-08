<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\user\admin;

use app\common\controller\Common;
use app\user\model\User as UserModel;
use app\user\model\Role as RoleModel;
use app\admin\model\Menu as MenuModel;
use think\Hook;

/**
 * 用户公开控制器，不经过权限认证
 * @package app\user\admin
 */
class Publics extends Common
{
    /**
     * 用户登录
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function signin()
    {
        if ($this->request->isPost()) {
            // 获取post数据
            $data = $this->request->post();
            $rememberme = isset($data['remember-me']) ? true : false;

            // 登录钩子
            $hook_result = Hook::listen('signin', $data);
            if (!empty($hook_result) && true !== $hook_result[0]) {
                $this->error($hook_result[0]);
            }

            // 验证数据
            $result = $this->validate($data, 'User.signin');
            if(true !== $result){
                // 验证失败 输出错误信息
                $this->error($result);
            }

            // 验证码
            if (config('captcha_signin')) {
                $captcha = $this->request->post('captcha', '');
                $captcha == '' && $this->error('请输入验证码');
                if(!captcha_check($captcha, '', config('captcha'))){
                    //验证失败
                    $this->error('验证码错误或失效');
                };
            }

            // 登录
            $UserModel = new UserModel;
            $uid = $UserModel->login($data['username'], $data['password'], $rememberme);
            if ($uid) {
                // 记录行为
                action_log('user_signin', 'admin_user', $uid, $uid);
                $this->jumpUrl();
            } else {
                $this->error($UserModel->getError());
            }
        } else {
            $hook_result = Hook::listen('signin_sso');
            if (!empty($hook_result) && true !== $hook_result[0]) {
                if (isset($hook_result[0]['url'])) {
                    $this->redirect($hook_result[0]['url']);
                }
                if (isset($hook_result[0]['error'])) {
                    $this->error($hook_result[0]['error']);
                }
            }

            if (is_signin()) {
                $this->jumpUrl();
            } else {
                return $this->fetch();
            }
        }
    }

    /**
     * 跳转到第一个有权限访问的url
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed|string
     */
    private function jumpUrl()
    {
        if (session('user_auth.role') == 1) {
            $this->success('登录成功', url('admin/index/index'));
        }

        $default_module = RoleModel::where('id', session('user_auth.role'))->value('default_module');
        $menu = MenuModel::get($default_module);
        if (!$menu) {
            $this->error('当前角色未指定默认跳转模块！');
        }

        if ($menu['url_type'] == 'link') {
            $this->success('登录成功', $menu['url_value']);
        }

        $menu_url = explode('/', $menu['url_value']);
        role_auth();
        $url = action('admin/ajax/getSidebarMenu', ['module_id' => $default_module, 'module' => $menu['module'], 'controller' => $menu_url[1]]);
        if ($url == '') {
            $this->error('权限不足');
        } else {
            $this->success('登录成功', $url);
        }
    }

    /**
     * 退出登录
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function signout()
    {
        $hook_result = Hook::listen('signout_sso');
        if (!empty($hook_result) && true !== $hook_result[0]) {
            if (isset($hook_result[0]['url'])) {
                $this->redirect($hook_result[0]['url']);
            }
            if (isset($hook_result[0]['error'])) {
                $this->error($hook_result[0]['error']);
            }
        }

        session(null);
        cookie('uid', null);
        cookie('signin_token', null);

        $this->redirect('signin');
    }
}
