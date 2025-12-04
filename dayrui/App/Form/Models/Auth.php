<?php namespace Phpcmf\Model\Form;

// 权限验证
class Auth extends \Phpcmf\Model
{
    // 判断底部链接的显示权限
    public function is_auth($c, $m) {
        // $c 表示插件控制器名称，$m表示插件控制器方法

        /// 评论控制器
        if (strpos($c, '_comment')) {
            list($table, $b) = explode('_', $c);
            if (\Phpcmf\Service::C()->get_cache('app-comment-'.SITE_ID, 'form', $c)) {
                return 1;
            }
        }

        // 这里的程序体，有权限返回1，没权限返回0
        return 0;
    }

}