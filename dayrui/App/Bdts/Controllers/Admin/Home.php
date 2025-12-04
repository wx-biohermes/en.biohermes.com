<?php namespace Phpcmf\Controllers\Admin;

class Home extends \Phpcmf\Common
{

    public function __construct()
    {
        parent::__construct();
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '百度推送设置' => ['bdts/home/index', 'fa fa-internet-explorer'],
                    '手动推送' => ['add:bdts/home/url_add', 'fa fa-plus', '600px', '300px'],
                    '推送日志' => ['bdts/home/log_index', 'fa fa-calendar'],
                    'help' => ['672'],
                ]
            ),
        ]);
    }

    // 插件设置
    public function index() {

        if (IS_AJAX_POST) {

            $post = \Phpcmf\Service::L('Input')->post('data', true);
            if (isset($post['bdts']) && $post['bdts']) {
                $bdts = [];
                foreach ($post['bdts'] as $i => $t) {
                    if (isset($t['site'])) {
                        if (!$t['site']) {
                            $this->_json(0, dr_lang('域名必须填写'));
                        } elseif (strpos($t['site'], '://') !== false) {
                            $this->_json(0, dr_lang('域名不能带有://符号，请联系纯域名'));
                        }
                        $bdts[$i]['site'] = $t['site'];
                    } else {
                        if (!$t['token']) {
                            $this->_json(0, dr_lang('token必须填写'));
                        }
                        $bdts[$i-1]['token'] = $t['token'];
                    }
                }
                $post['bdts'] = $bdts;
            }

            \Phpcmf\Service::M('bdts', 'bdts')->setConfig($post);
            \Phpcmf\Service::L('Input')->system_log('设置百度推送工具');
            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('Input')->get('page'));

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => \Phpcmf\Service::M('bdts', 'bdts')->getConfig(),
            'form' => dr_form_hidden(['page' => $page]),
            'module' => \Phpcmf\Service::M('Module')->All(1),
        ]);
        \Phpcmf\Service::V()->display('config.html');
    }

    public function url_add() {

        if (IS_AJAX_POST) {
            $url = \Phpcmf\Service::L('input')->post('url');
            if (!$url) {
                $this->_json(0, dr_lang('URL不能为空'));
            }

            $rt = \Phpcmf\Service::M('bdts', 'bdts')->url_bdts($url, '手动');
            if (!$rt['code']) {
                $this->_json(0, $rt['msg']);
            }

            exit($this->_json(1, dr_lang('操作成功')));
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden()
        ]);
        \Phpcmf\Service::V()->display('url.html');
        exit;
    }

    public function log_index() {

        $data = $list = [];
        $file = WRITEPATH.'bdts_log.php';
        if (is_file(WRITEPATH.'bdts_log.php')) {
            if (filesize($file) > 1024*1024*2) {
                $list[] = '此日志文件大于2MB，请使用Ftp等工具查看此文件：'.$file;
            } else {
                $data = explode(PHP_EOL, str_replace(array(chr(13), chr(10)), PHP_EOL, file_get_contents($file)));
                $data = $data ? array_reverse($data) : [];
                unset($data[0]);
                $page = max(1, (int)\Phpcmf\Service::L('input')->get('page'));
                $limit = ($page - 1) * SYS_ADMIN_PAGESIZE;
                $i = $j = 0;
                foreach ($data as $v) {
                    if ($i >= $limit && $j < SYS_ADMIN_PAGESIZE && $v) {
                        $list[] = $v;
                        $j ++;
                    }
                    $i ++;
                }
            }

        }

        $total = $data ? max(0, count($data) - 1) : 0;

        \Phpcmf\Service::V()->assign(array(
            'list' => $list,
            'total' => $total,
            'mypages'	=> \Phpcmf\Service::L('input')->page(\Phpcmf\Service::L('Router')->url('bdts/home/log_index'), $total, 'admin')
        ));
        \Phpcmf\Service::V()->display('log.html');
    }

    public function del() {

        @unlink(WRITEPATH.'bdts_log.php');

        $this->_json(1, dr_lang('操作成功'));
    }

    public function add() {

        $mid = dr_safe_filename($_GET['mid']);
        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('所选数据不存在'));
        } elseif (!$mid) {
            $this->_json(0, dr_lang('模块参数不存在'));
        }

        $this->_module_init($mid);

        $data = \Phpcmf\Service::M()->table(SITE_ID.'_'.$mid)->where_in('id', $ids)->getAll();
        if (!$data) {
            $this->_json(0, dr_lang('所选数据为空'));
        }

        $ct = 0;
        foreach ($data as $t) {
            \Phpcmf\Service::M('bdts', 'bdts')->module_bdts($mid, $t['url'], 'edit');
            $ct++;
        }

        $this->_json(1, dr_lang('共批量%s个URL', $ct));
    }

}
