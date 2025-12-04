<?php namespace Phpcmf\Home;

// 内容网站表单操作类 基于 Ftable
class Form extends \Phpcmf\Table
{
    public $cid; // 内容id
    public $form; // 表单信息

    // 上级公共类
    public function __construct() {
        parent::__construct();
        $this->_Extend_Init();
    }

    // 继承类初始化
    protected function _Extend_Init() {
        // 判断表单是否操作
        $cache = \Phpcmf\Service::L('cache')->get('form-'.SITE_ID);
        $this->form = $cache[\Phpcmf\Service::L('Router')->class];
        if (!$this->form) {
            $this->_msg(0, dr_lang('网站表单【%s】不存在',\Phpcmf\Service::L('Router')->class));
            exit;
        }
        // 支持附表存储
        $this->is_data = 1;
        // 模板前缀(避免混淆)
        $this->tpl_name = $this->form['table'];
        $this->tpl_prefix = 'form_';
        // 初始化数据表
        $this->_init([
            'table' => SITE_ID.'_form_'.\Phpcmf\Service::L('Router')->class,
            'field' => $this->form['field'],
            'show_field' => 'title',
        ]);
        // 写入模板
        \Phpcmf\Service::V()->assign([
            'form_name' => $this->form['name'],
            'form_table' => $this->form['table'],
        ]);
    }

    public function _get_auth_value($name, $member) {

        if (!$this->form['setting'][$name]) {
            return 0;
        }

        if (!$member) {
            $auth = [0];
        } else {
            $auth = $member['groupid'];
            if (!$auth) {
                $auth = [0]; // 没有用户组的视为游客
            }
        }

        $value = [];
        foreach ($auth as $k) {
            if (isset($this->form['setting'][$name][$k])) {
                $value[] = (int)$this->form['setting'][$name][$k];
            }
        }

        return $value ? max($value) : 0;
    }

    // ========================

    // 内容列表
    protected function _Home_List() {

        // 无权限访问表单
        /*
        if (!\Phpcmf\Service::M('member_auth')->form_auth($this->form['id'], 'show', $this->member)) {
            $this->_msg(0, dr_lang('您的用户组无权限访问表单'), $this->uid ? '' : dr_member_url('login/index'));
            return;
        }*/

        if (isset($this->form['setting']['web']) && $this->form['setting']['web']) {
            $this->_msg(0, dr_lang('无权限访问表单'));
            return;
        }

        // seo
        \Phpcmf\Service::V()->assign([
            'meta_title' => dr_lang($this->form['name']),
            'meta_keywords' => \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_KEYWORDS'),
            'meta_description' => \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_DESCRIPTION')
        ]);

        \Phpcmf\Service::V()->assign([
            'urlrule' => \Phpcmf\Service::L('Router')->form_list_url($this->form['table'], '[page]'),
        ]);
        \Phpcmf\Service::V()->display($this->_tpl_filename('list'));
    }

    // 添加内容
    protected function _Home_Post() {

        if (!$this->_get_auth_value('post_add', $this->member)) {
            $this->_msg(0, IS_DEV ? '需要在表单插件后台，开启发布权限' : dr_lang('无权限发布'));
        }

        // 是否有验证码
        $this->is_post_code = $this->_get_auth_value('post_code', $this->member);

        list($tpl) = $this->_Post(0);

        // seo
        \Phpcmf\Service::V()->assign([
            'meta_title' => dr_lang($this->form['name']),
            'meta_keywords' => \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_KEYWORDS'),
            'meta_description' => \Phpcmf\Service::C()->get_cache('site', SITE_ID, 'seo', 'SITE_DESCRIPTION')
        ]);

        \Phpcmf\Service::V()->assign([
            'form' =>  dr_form_hidden(),
            'rt_url' => $this->form['setting']['rt_url'] ? '' : dr_now_url(),
            'is_post_code' => $this->is_post_code,
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 显示内容
    protected function _Home_Show() {

        // 无权限访问表单
        /*
        if (!\Phpcmf\Service::M('member_auth')->form_auth($this->form['id'], 'show', $this->member)) {
            $this->_msg(0, dr_lang('您的用户组无权限访问表单'), $this->uid ? '' : dr_member_url('login/index'));
            return;
        }*/

        if (isset($this->form['setting']['web']) && $this->form['setting']['web']) {
            $this->_msg(0, dr_lang('无权限访问表单'));
            return;
        }

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        $name = 'from_'.$this->form['table'].'_show_id_'.$id;
        $cache = \Phpcmf\Service::L('cache')->get_data($name);
        if (!$cache) {
            list($tpl, $data) = $this->_Show($id);
            if (!$data) {
                $this->_msg(0, dr_lang('网站表单内容不存在'));
            }
            $data = $this->_Call_Show($data);
            $cache = [$tpl, $data ];
            // 缓存结果
            if ($data['uid'] != $this->uid && SYS_CACHE) {
                if ($this->member && $this->member['is_admin']) {
                    // 管理员时不进行缓存
                    \Phpcmf\Service::L('cache')->init()->delete($name);
                } else {
                    \Phpcmf\Service::L('cache')->set_data($name, $cache, SYS_CACHE_SHOW * 3600);
                }
            }
        } else {
            list($tpl, $data) = $cache;
        }

        if ($data['status'] != 1) {
            $this->_msg(0, dr_lang('内容正在审核中'));
        }

        \Phpcmf\Service::V()->assign($data);

        // seo
        $data['formname'] = dr_lang($this->form['name']);
        \Phpcmf\Service::V()->assign(\Phpcmf\Service::L('Seo')->get_seo_value($data, [
            'meta_title' => isset($this->form['setting']['seo']['title']) && $this->form['setting']['seo']['title'] ? $this->form['setting']['seo']['title'] : $data['title'].SITE_SEOJOIN.dr_lang($this->form['name']),
            'meta_keywords' => isset($this->form['setting']['seo']['keywords']) && $this->form['setting']['seo']['keywords'] ? $this->form['setting']['seo']['keywords'] : $data['title'].SITE_SEOJOIN.dr_lang($this->form['name']),
            'meta_description' => isset($this->form['setting']['seo']['description']) && $this->form['setting']['seo']['description'] ? $this->form['setting']['seo']['description'] : $data['title'].SITE_SEOJOIN.dr_lang($this->form['name']),
        ]));

        \Phpcmf\Service::V()->display($tpl);
    }


    // ===========================

    // 格式化保存数据 保存之前
    protected function _Format_Data($id, $data, $old) {

        /*
        if ($this->uid && IS_USE_MEMBER) {
            // 判断日发布量
            $day_post = \Phpcmf\Service::M('member_auth')->form_auth($this->form['id'], 'day_post', $this->member);
            if ($day_post && \Phpcmf\Service::M()->db
                    ->table($this->init['table'])
                    ->where('uid', $this->uid)
                    ->where('DATEDIFF(from_unixtime(inputtime),now())=0')
                    ->countAllResults() >= $day_post) {
                $this->_json(0, dr_lang('每天发布数量不能超过%s个', $day_post));
            }

            // 判断发布总量
            $total_post = \Phpcmf\Service::M('member_auth')->form_auth($this->form['id'], 'total_post', $this->member);
            if ($total_post && \Phpcmf\Service::M()->db
                    ->table($this->init['table'])
                    ->where('uid', $this->uid)
                    ->countAllResults() >= $total_post) {
                $this->_json(0, dr_lang('发布数量不能超过%s个', $total_post));
            }
        }*/

        // 审核状态
        $data[1]['status'] = $this->_get_auth_value('post_verify', $this->member) ? 0 : 1;

        // 默认数据
        $data[0]['uid'] = $data[1]['uid'] = (int)$this->member['uid'];
        $data[1]['inputip'] = \Phpcmf\Service::L('input')->ip_info();
        $data[1]['inputtime'] = SYS_TIME;
        $data[1]['tableid'] = $data[1]['displayorder'] = 0;

        return $data;
    }

    /**
     * 保存内容
     * $id      内容id,新增为0
     * $data    提交内容数组,留空为自动获取
     * $func    格式化提交的数据
     * */
    protected function _Save($id = 0, $data = [], $old = [], $func = null, $func2 = null) {

        return parent::_Save($id, $data, $old,
            function ($id, $data, $old) {
                // 挂钩点
                \Phpcmf\Hooks::trigger('form_post_before', dr_array2array($data[1], $data[0]));
                return dr_return_data(1, 'ok', $data);
            },
            function ($id, $data, $old) {
                // 挂钩点
                \Phpcmf\Hooks::trigger('form_post_after', dr_array2array($data[1], $data[0]));
                // 提醒通知用户 send_user
                if ($this->form['setting']['notice']['use']) {
                    if ($this->form['setting']['notice']['username']) {
                        $var = dr_array2array($data[1], $data[0]);
                        $fields = $this->form['field'];
                        $fields['inputtime'] = ['fieldtype' => 'Date'];
                        $var = \Phpcmf\Service::L('Field')->format_value($fields, $var, 1);
                        $arr = explode(',', $this->form['setting']['notice']['username']);
                        foreach ($arr as $autor) {
                            $user = dr_member_username_info($autor);
                            if (!$user) {
                                log_message('error', '网站表单【'.$this->form['name'].'】已开启通知提醒，但通知人['.$autor.']有误');
                            } else {
                                \Phpcmf\Service::L('Notice')->send_notice_user(
                                    'form_'.$this->form['table'].'_post',
                                    $user['id'],
                                    $var,
                                    $this->form['setting']['notice'],
                                    $this->form['setting']['notice']['is_send']
                                );
                            }
                        }
                    } else {
                        log_message('error', '网站表单【'.$this->form['name'].'】已开启通知提醒，但未设置通知人');
                    }
                }
                if (!$data[1]['status']) {
                    // 提醒
                    \Phpcmf\Service::M('member')->admin_notice(SITE_ID, 'content', $this->member, dr_lang('%s提交审核', $this->form['name']), 'form/'.$this->form['table'].'_verify/edit:id/'.$data[1]['id']);
                }
            }
        );
    }


    /**
     * 回调处理结果
     * $data
     * */
    protected function _Call_Post($data) {

        $data['url'] = $this->form['setting']['rt_url'] ? str_replace('{id}', $data[1]['id'], $this->form['setting']['rt_url']) : '';
        if ($data[1]['status']) {
            return dr_return_data($data[1]['id'], dr_lang($this->form['setting']['rt_text'] ? $this->form['setting']['rt_text'] : '操作成功'), $data);
        }

        return dr_return_data($data[1]['id'], dr_lang($this->form['setting']['rt_text2'] ? $this->form['setting']['rt_text2'] : '操作成功，等待管理员审核'), $data);
    }

    // 前端回调处理类
    protected function _Call_Show($data) {

        return $data;
    }
}
