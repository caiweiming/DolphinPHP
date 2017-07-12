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
                $this->success('登录成功', url('admin/index/index'));
            } else {
                $this->error($UserModel->getError());
            }
        } else {
            return is_signin() ? $this->redirect('admin/index/index') : $this->fetch();
        }
    }

    /**
     * 退出登录
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function signout()
    {
        session(null);
        cookie('uid', null);
        cookie('signin_token', null);

        return $this->redirect('signin');
    }
}
