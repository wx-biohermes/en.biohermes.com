<?php namespace Phpcmf\Controllers\Admin;


class Content extends \Phpcmf\Common
{
    public function index() {

        $data = \Phpcmf\Service::L('category', 'module')->get_category("share");
        if (!$data) {
            $this->_admin_msg(0, dr_lang('没有创建共享栏目'), dr_url('category/index'));
        }

        list($url, $str) = \Phpcmf\Service::L('tree', 'module')->tree_category('share', $data);
        if (dr_strlen($str) < 10) {
            $this->_admin_msg(0, dr_lang('没有可用栏目操作权限'));
        }

        $open = 0;
        $file = WRITEPATH.'config/category.php';
        if (is_file($file)) {
            $config = require $file;
            if (isset($config['share']) && isset($config['share']['tree_open']) && $config['share']['tree_open']) {
                $open = 1;
            }
        }

        \Phpcmf\Service::V()->assign([
            'str' => $str,
            'url' => $url,
            'is_open' => $open
        ]);
        \Phpcmf\Service::V()->display('content.html');
    }

}
