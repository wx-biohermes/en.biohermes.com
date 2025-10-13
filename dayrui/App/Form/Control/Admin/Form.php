<?php namespace Phpcmf\Admin;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 网站表单操作类 基于 Ftable
class Form extends \Phpcmf\Table
{
    public $form;
    protected $is_verify;

    // 上级公共类
    public function __construct() {
        parent::__construct();
        $this->_Extend_Init();
    }

    // 继承类初始化
    protected function _Extend_Init() {
        // 判断是否来自审核控制器
        $this->is_verify = strpos(\Phpcmf\Service::L('Router')->class, '_verify') !== false;
        // 判断表单是否操作
        $cache = \Phpcmf\Service::L('cache')->get('form-'.SITE_ID);
        $this->form = $cache[str_replace('_verify', '',\Phpcmf\Service::L('Router')->class)];
        if (!$this->form) {
            $this->_admin_msg(0, dr_lang('网站表单【%s】不存在', str_replace('_verify', '',\Phpcmf\Service::L('Router')->class)));
        }
        // 支持附表存储
        $this->is_data = 1;
        // 模板前缀(避免混淆)
        $this->tpl_prefix = 'share_form_';
        // 单独模板命名
        $this->tpl_name = $this->form['table'];
        // 表单显示名称
        $this->name = dr_lang('网站表单（%s）', $this->form['name']);
        $sysfield = ['inputtime', 'inputip', 'displayorder', 'uid'];
        if ($this->is_verify) {
            $sysfield[] = 'status';
            if (is_array($this->form['setting']['list_field'])) {
                $this->form['setting']['list_field']['status'] = [
                    'use' => '1', // 1是显示，0是不显示
                    'name' => dr_lang('状态'), //显示名称
                    'width' => '100', // 显示宽度
                    'func' => 'dr_form_status_name', // 回调函数见：http://help.xunruicms.com/463.html
                    'center' => '1', // 1是居中，0是默认
                ];
            }
        }
        // 初始化数据表
        $this->_init([
            'table' => SITE_ID.'_form_'.$this->form['table'],
            'field' => $this->form['field'],
            'sys_field' => $sysfield,
            'date_field' => 'inputtime',
            'show_field' => 'title',
            'list_field' => $this->form['setting']['list_field'],
            'order_by' => 'displayorder DESC,inputtime DESC',
            'where_list' => $this->is_verify ? 'status<>1' : 'status=1',
        ]);
        $menu = $this->is_verify ? \Phpcmf\Service::M('auth')->_admin_menu([
            '审核管理' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-edit'],
        ]) : \Phpcmf\Service::M('auth')->_admin_menu(
            [
                dr_lang('%s管理', $this->form['name']) => ['form/'.\Phpcmf\Service::L('Router')->class.'/index', dr_icon($this->form['setting']['icon'])],
                '添加' => ['form/'.\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                '修改' => ['hide:form/'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                '查看' => ['hide:form/'.\Phpcmf\Service::L('Router')->class.'/show_index', 'fa fa-search'],
            ]
        );
        \Phpcmf\Service::V()->assign([
            'menu' => $menu,
            'field' => $this->init['field'],
            'form_list' => $cache,
            'form_name' => $this->form['name'],
            'form_table' => $this->form['table'],
            'is_verify' => $this->is_verify,
        ]);
        if ($this->form['setting']['is_hide_search_bar']) {
            $this->is_show_search_bar = 0;
        }
    }

    // 后台查看表单列表
    protected function _Admin_List() {

        $this->is_ajax_list = true;
        list($tpl) = $this->_List();

        $this->mytable = [
            'foot_tpl' => '',
            'link_tpl' => '',
            'link_var' => 'html = html.replace(/\{id\}/g, row.id);
            html = html.replace(/\{cid\}/g, row.id);
            html = html.replace(/\{fid\}/g, "'.\Phpcmf\Service::L('Router')->class.'");',
        ];
        $uriprefix = APP_DIR.'/'.\Phpcmf\Service::L('Router')->class;
        if ($this->_is_admin_auth('del') || $this->_is_admin_auth('edit')) {
            $this->mytable['foot_tpl'].= '<label class="table_select_all"><input onclick="dr_table_select_all(this)" type="checkbox"><span></span></label>';
        }
        if ($this->_is_admin_auth('del')) {
            $this->mytable['foot_tpl'].= '<label><button type="button" onclick="dr_table_option(\''.dr_url($uriprefix.'/del').'\', \''.dr_lang('你确定要删除它们吗？').'\')" class="btn red btn-sm"> <i class="fa fa-trash"></i> '.dr_lang('删除').'</button></label>';
        }

        if ($this->_is_admin_auth('edit')) {
            $this->mytable['link_tpl'].= '<label><a href="'.dr_url($uriprefix.'/edit').'&id={id}" class="btn btn-xs red"> <i class="fa fa-edit"></i> '.dr_lang('修改').'</a></label>';
            if ($this->is_verify) {
                $this->mytable['foot_tpl'].= '<label><button type="button" onclick="dr_ajax_option(\''.dr_url($uriprefix.'/status_index', ['tid' => 0]).'\', \''.dr_lang('你确定要审核通过它们吗？').'\', 1)" class="btn blue btn-sm"> <i class="fa fa-check-square-o"></i> '.dr_lang('通过').'</button></label>
                <label><button type="button" onclick="dr_ajax_option(\''.dr_url($uriprefix.'/status_index', ['tid' => 1]).'\', \''.dr_lang('你确定要拒绝它们吗？').'\', 1)" class="btn yellow btn-sm"> <i class="fa fa-times-circle-o"></i> '.dr_lang('拒绝').'</button></label>';
            } else {
                $clink = $this->_app_clink('form');
                if ($clink) {
                    foreach ($clink as $a) {
                        if ($a['model'] && $a['check']
                            && method_exists($a['model'], $a['check']) && call_user_func(array($a['model'], $a['check']), APP_DIR, []) == 0) {
                            continue;
                        }
                        $this->mytable['link_tpl'].= ' <label><a class="btn '.$a['color'].' btn-xs" href="'.$a['url'].'"><i class="'.$a['icon'].'"></i> '.dr_lang($a['name']);
                        if ($a['field'] && \Phpcmf\Service::M()->is_field_exists($this->init['table'], $a['field'])) {
                            $this->mytable['link_tpl'].= '（{'.$a['field'].'}）';
                            $this->mytable['link_var'].= 'html = html.replace(/\{'.$a['field'].'\}/g, row.'.$a['field'].');';
                        }
                        $this->mytable['link_tpl'].= '</a></label>';
                    }
                }

                $cbottom = $this->_app_cbottom('form');
                if ($cbottom) {
                    $this->mytable['foot_tpl'].= '<label>
                    <div class="btn-group dropup">
                        <a class="btn  blue btn-sm dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true" aria-expanded="false" href="javascript:;"> '.dr_lang('批量').'
                            <i class="fa fa-angle-up"></i>
                        </a>
                        <ul class="dropdown-menu">';
                    foreach ($cbottom as $a) {
                        $this->mytable['foot_tpl'].= '<li>
                                <a href="'.urldecode($a['url']).'"> <i class="'.$a['icon'].'"></i> '.dr_lang($a['name']).' </a>
                            </li>';
                    }
                    $this->mytable['foot_tpl'].= '
                           
                        </ul>
                    </div>
                </label>';
                }
            }
        }

        \Phpcmf\Service::V()->assign([
            'mytable' => $this->mytable,
        ]);
        return \Phpcmf\Service::V()->display($tpl);
    }

    // 后台添加表单内容
    protected function _Admin_Add() {
        list($tpl) = $this->_Post(0);
        return \Phpcmf\Service::V()->display($tpl);
    }

    // 后台修改表单内容
    protected function _Admin_Edit() {

        $id = intval(\Phpcmf\Service::L('input')->get('id'));
        list($tpl, $data) = $this->_Post($id);

        if (!$data) {
            $this->_admin_msg(0, dr_lang('数据不存在: '.$id));
        } elseif ($this->is_verify && $data['status'] == 1) {
            $this->_admin_msg(0, dr_lang('已经通过了审核'));
        }

        \Phpcmf\Service::V()->display($tpl);
    }

    // 后台查看表单内容
    protected function _Admin_Show() {
        list($tpl, $data) = $this->_Show(intval(\Phpcmf\Service::L('input')->get('id')));
        if (!$data) {
            $this->_admin_msg(0, dr_lang('数据#%s不存在', $_GET['id']));
        }
        \Phpcmf\Service::V()->display($tpl);
    }

    // 后台删除表单内容
    protected function _Admin_Del() {
        $this->_Del(
            \Phpcmf\Service::L('input')->get_post_ids(),
            null,
            function ($rows) {
                // 对应删除提醒
                foreach ($rows as $t) {
                    \Phpcmf\Service::M('member')->delete_admin_notice('form/'.$this->form['table'].'_verify/edit:id/'.$t['id'], SITE_ID);
                    \Phpcmf\Service::M('member')->delete_admin_notice('form/'.$this->form['table'].'/edit:id/'.$t['id'], SITE_ID);
                    \Phpcmf\Service::L('cache')->clear('from_'.$this->form['table'].'_show_id_'.$t['id']);
                }

            },
            \Phpcmf\Service::M()->dbprefix($this->init['table'])
        );
    }

    // 后台批量审核
    protected function _Admin_Status() {

        $tid = intval(\Phpcmf\Service::L('input')->get('tid'));
        $ids = \Phpcmf\Service::L('input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, dr_lang('所选数据不存在'));
        }

        // 格式化
        $in = [];
        foreach ($ids as $i) {
            $i && $in[] = intval($i);
        }
        if (!$in) {
            $this->_json(0, dr_lang('所选数据不存在'));
        }

        $rows = \Phpcmf\Service::M()->db->table($this->init['table'])->whereIn('id', $in)->get()->getResultArray();
        if (!$rows) {
            $this->_json(0, dr_lang('所选数据不存在'));
        }

        foreach ($rows as $row) {
            if ($row['status'] != 1) {
                if ($tid) {
                    // 拒绝
                    $this->_verify_refuse($row);
                } else {
                    // 通过
                    $this->_verify($row);
                }
            }
        }

        $this->_json(1, dr_lang('操作成功'));
    }

    // 后台批量保存排序值
    protected function _Admin_Order() {
        $this->_Display_Order(
            intval(\Phpcmf\Service::L('input')->get('id')),
            intval(\Phpcmf\Service::L('input')->get('value'))
        );
    }

    // 格式化保存数据 保存之前
    protected function _Format_Data($id, $data, $old) {

        // 后台添加时默认通过
        if (!$id) {
            // !$this->is_verify &&
            $data[1]['status'] = 1;
            $data[1]['tableid'] = 0;
        }
        $data[1]['uid'] = intval($data[1]['uid']);
        $data[0]['uid'] = $data[1]['uid'];

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
                if (!$old) {
                    \Phpcmf\Hooks::trigger('form_post_before', dr_array2array($data[1], $data[0]));
                }
                return dr_return_data(1, 'ok', $data);
            },
            function ($id, $data, $old) {
                if ($this->is_verify) {
                    if ($data[1]['status'] == 1) {
                        // 审核通过时
                        $data[1]['status'] = 0;
                        $this->_verify($data[1]);
                    } elseif ($data[1]['status'] == 2) {
                        $data[1]['status'] = 0;
                        $this->_verify_refuse($data[1]);
                    }
                }
                \Phpcmf\Service::L('cache')->clear('from_'.$this->form['table'].'_show_id_'.$id);
                \Phpcmf\Service::M('member')->todo_admin_notice('form/'.$this->form['table'].'_verify/edit:id/'.$id, SITE_ID);// clear

                if (!$old) {
                    // 挂钩点
                    \Phpcmf\Hooks::trigger('form_post_after', dr_array2array($data[1], $data[0]));
                }
            }
        );
    }

    // 审核拒绝
    protected function _verify_refuse($row) {

        if ($row['status'] == 2) {
            return;
        }

        \Phpcmf\Service::M()->db->table($this->init['table'])->where('id', $row['id'])->update(['status' => 2]);

        // 任务执行成功
        \Phpcmf\Service::M('member')->todo_admin_notice('form/'.$this->form['table'].'_verify/edit:id/'.$row['id'], SITE_ID);
        // 提醒
        \Phpcmf\Service::M('member')->notice($row['uid'], 3, dr_lang('%s审核被拒绝', $this->form['name']));

        $row['form'] = $this->form;
        \Phpcmf\Service::L('Notice')->send_notice('form_verify_0', $row);
        \Phpcmf\Service::L('Notice')->send_notice('form_'.$this->form['table'].'_verify_0', $row);

        // 挂钩点 被拒绝
        \Phpcmf\Hooks::trigger('form_verify_0', $row);
    }

    // 审核通过
    protected function _verify($row) {

        if ($row['status'] == 1) {
            return;
        }

        // 增减金币
        $score = \Phpcmf\Service::M('member_auth')->form_auth($this->form['id'], 'score', $this->member);
        $score && \Phpcmf\Service::M('member')->add_score($row['uid'], $score, dr_lang('%s发布', $this->form['name']));

        // 增减经验
        $exp = \Phpcmf\Service::M('member_auth')->form_auth($this->form['id'], 'exp', $this->member);
        $exp && \Phpcmf\Service::M('member')->add_experience($row['uid'], $exp, dr_lang('%s发布', $this->form['name']));

        \Phpcmf\Service::M()->db->table($this->init['table'])->where('id', $row['id'])->update(['status' => 1]);

        // 任务执行成功
        \Phpcmf\Service::M('member')->todo_admin_notice('form/'.$this->form['table'].'_verify/edit:id/'.$row['id'], SITE_ID);

        // 提醒
        \Phpcmf\Service::M('member')->notice($row['uid'], 3, dr_lang('%s审核成功', $this->form['name']));

        $row['form'] = $this->form;
        \Phpcmf\Service::L('Notice')->send_notice('form_verify_1', $row);
        \Phpcmf\Service::L('Notice')->send_notice('form_'.$this->form['table'].'_verify_1', $row);

        // 挂钩点
        \Phpcmf\Hooks::trigger('form_verify', $row);
    }

    // 修改排序
    public function edit_order() {
        $this->_Admin_Order();
    }

    // 修改排序
    public function order_edit() {
        $this->_Admin_Order();
    }
}
