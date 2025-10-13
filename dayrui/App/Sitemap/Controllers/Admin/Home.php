<?php namespace Phpcmf\Controllers\Admin;

class Home extends \Phpcmf\Common
{

    // 站长工具设置
    public function index() {

        $post = \Phpcmf\Service::L('Input')->post('data', true);

        if (IS_AJAX_POST) {
            \Phpcmf\Service::M('sitemap', 'sitemap')->setConfig($post);
            \Phpcmf\Service::L('Input')->system_log('设置sitemap工具');
            dr_dir_delete(WRITEPATH.'app/sitemap/');
            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('Input')->get('page'));

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => \Phpcmf\Service::M('sitemap', 'sitemap')->getConfig(),
            'form' => dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    'sitemap' => ['sitemap/home/index', 'fa fa-sitemap'],
                    'help' => ['671'],
                ]
            ),
            'module' => \Phpcmf\Service::M('Module')->All(1),
            'is_xml_file' => is_file(WEBPATH.'sitemap.txt'),
        ]);
        \Phpcmf\Service::V()->display('sitemap.html');
    }


}
