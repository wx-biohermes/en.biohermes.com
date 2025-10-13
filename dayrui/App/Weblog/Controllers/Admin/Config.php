<?php namespace Phpcmf\Controllers\Admin;

class Config extends \Phpcmf\App
{

    public function index() {

        $data = \Phpcmf\Service::R(WRITEPATH.'config/weblog.php');

        if (IS_AJAX_POST) {

            $post = \Phpcmf\Service::L('input')->post('data');

            \Phpcmf\Service::L('Config')->file(WRITEPATH.'config/weblog.php', '访客配置', 32)->to_require($post);

            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $data,
            'form' => dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '插件设置' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-cog'],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('config.html');
    }

}
