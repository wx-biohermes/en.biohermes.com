<?php namespace Phpcmf\Controllers\Admin;

class Config extends \Phpcmf\Common
{

	public function index() {

	    $data = \Phpcmf\Service::M('app')->get_config(APP_DIR);
        if (!$data) {
            $data = [];
        }

        if (IS_AJAX_POST) {
            $save = $data;
            $post = \Phpcmf\Service::L('input')->post('data');
            if (SITE_ID > 1) {
                $save[SITE_ID] = $post;
            } else {
                $save = $post;
                foreach ($this->site as $sid) {
                    if (isset($save[$sid]) && $save[$sid]) {
                        $save[$sid] = $data[$sid];
                    }
                }
            }

            \Phpcmf\Service::M('app')->save_config(APP_DIR, $save);

            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));

        if (SITE_ID > 1) {
            if (isset($data[SITE_ID])) {
                $data = $data[SITE_ID];
            } else {
                $data = [];
            }
        }

        if (!isset($data['field']) or !$data['field']) {
            $data['field'] = [];
        }

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $data,
            'form' => dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '插件设置' => [APP_DIR.'/config/index', 'fa fa-cog'],
                    //'help' => [564],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('config.html');

	}




}
