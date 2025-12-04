<?php namespace Phpcmf\Controllers\Admin;

class Home extends \Phpcmf\Table
{

    public function __construct() {
        parent::__construct();
        // 支持附表存储
        $this->is_data = 0;
        // 模板前缀(避免混淆)
        $this->tpl_prefix = 'content_';
        // 表单显示名称
        $this->name = dr_lang('访客日志');
        $field = [
            'domain' => array(
                'ismain' => 1,
                'name' => dr_lang('域名'),
                'fieldname' => 'domain',
                'fieldtype' => 'Text',
            ),
            'method' => array(
                'ismain' => 1,
                'name' => dr_lang('请求'),
                'fieldname' => 'method',
                'fieldtype' => 'Text',
            ),
            'url' => array(
                'ismain' => 1,
                'name' => dr_lang('地址'),
                'fieldname' => 'url',
                'fieldtype' => 'Text',
            ),
            'inputip' => array(
                'ismain' => 1,
                'name' => dr_lang('IP'),
                'fieldname' => 'inputip',
                'fieldtype' => 'Text',
            ),
            'uid' => array(
                'ismain' => 1,
                'name' => dr_lang('uid'),
                'fieldname' => 'uid',
                'fieldtype' => 'Text',
            ),
            'param' => array(
                'ismain' => 1,
                'name' => dr_lang('参数内容'),
                'fieldname' => 'param',
                'fieldtype' => 'Text',
            ),
            'useragent' => array(
                'ismain' => 1,
                'name' => dr_lang('客户端信息'),
                'fieldname' => 'useragent',
                'fieldtype' => 'Text',
            ),
        ];
        // 初始化数据表
        $this->_init([
            'table' => 'app_web_log',
            'field' => $field,
            'date_field' => 'inputtime',
            'show_field' => 'domain',
            'order_by' => 'inputtime desc',
        ]);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '访客记录' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-eye'],
                    '查看' => ['hide:'.APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/show', 'fa fa-search'],
                ]
            ),
            'field' => $field
        ]);
    }

    // 查看列表
    public function index() {
        list($tpl) = $this->_List();
        \Phpcmf\Service::V()->display($tpl);
    }

    // 拉黑ip
    public function ip_add() {

        $ip = \Phpcmf\Service::L('Input')->get('ip');
        if (!$ip) {
            $this->_json(0, 'ip不能为空');
        }

        $config = \Phpcmf\Service::R(WRITEPATH.'config/weblog.php');
        if (strpos($config['not_ips'], $ip) !== false) {
            $this->_json(0, 'ip已经拉黑过了');
        }

        $config['not_ips'].= PHP_EOL.$ip;
        \Phpcmf\Service::L('Config')->file(WRITEPATH.'config/weblog.php', '访客配置', 32)->to_require($config);

        $this->_json(1, dr_lang('操作成功'));
    }

    public function useragent_add() {

        $useragent = \Phpcmf\Service::L('Input')->get('useragent');
        if (!$useragent) {
            $this->_json(0, 'useragent不能为空');
        }

        $config = \Phpcmf\Service::R(WRITEPATH.'config/weblog.php');
        if (strpos($config['not_useragent'], $useragent) !== false) {
            $this->_json(0, 'useragent已经拉黑过了');
        }

        $config['not_useragent'].= PHP_EOL.$useragent;
        \Phpcmf\Service::L('Config')->file(WRITEPATH.'config/weblog.php', '访客配置', 32)->to_require($config);

        $this->_json(1, dr_lang('操作成功'));
    }

    // 删除内容
    public function del() {
        if (!IS_DEV) {
            $this->_json(0, '为了安全起见，需要在开发者模式下才能删除记录', -1);
        }
        $this->_Del(\Phpcmf\Service::L('Input')->get_post_ids());
    }

    public function all_del() {
        if (!IS_DEV) {
            $this->_json(0, '为了安全起见，需要在开发者模式下才能删除记录', -1);
        }
        \Phpcmf\Service::M()->db->table('app_web_log')->truncate();
        $this->_json(1, '清空完毕');
    }

    // 查看内容
    public function show() {
        list($tpl, $data) = $this->_Show(intval(\Phpcmf\Service::L('Input')->get('id')));
        if (!$data) {
            $this->_admin_msg(0, '记录不存在');
        }
        \Phpcmf\Service::V()->assign([
            'data' => $data,
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 清理日志
    function clear_weblog() {
        file_put_contents(WRITEPATH.'weblog_ip.txt', "");
        $this->_admin_msg(1, 'ok');
    }

}
