<?php namespace Phpcmf\Controllers\Admin;


// 内容模块tag操作类 基于 Ftable
class Home extends \Phpcmf\Table
{
    public $pid;
    public $pconfig;

    public function __construct() {
        parent::__construct();
        // 支持附表存储
        $this->is_data = 0;
        // 模板前缀(避免混淆)
        $this->tpl_prefix = 'tag_';
        // 单独模板命名
        $this->tpl_name = 'tag_content';
        // 模块显示名称
        $this->name = dr_lang('Tag');
        // pid
        $this->pid = intval(\Phpcmf\Service::L('input')->get('pid'));
        $myfield = [
            'name' => [
                'ismain' => 1,
                'name' => dr_lang('名称'),
                'fieldname' => 'name',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                    'is_right' => 2,
                )
            ],
            'displayorder' => array(
                'name' => dr_lang('权重值'),
                'ismain' => 1,
                'fieldtype' => 'Touchspin',
                'fieldname' => 'displayorder',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                        'max' => '999999999',
                        'min' => '0',
                        'step' => '1',
                        'show' => '1',
                        'value' => 0
                    ),
                    'validate' => array(
                        'tips' => dr_lang('权重值越高排列越靠前'),
                    )
                )
            ),
            'content' => [
                'ismain' => 1,
                'name' => dr_lang('描述信息'),
                'fieldname' => 'content',
                'fieldtype' => 'Ueditor',
                'setting' => array(
                    'option' => array(
                        'mode' => 1,
                        'height' => 300,
                        'width' => '100%',
                    ),
                )
            ],
        ];
        // 初始化数据表
        $this->_init([
            'table' => SITE_ID.'_tag',
            'field' => dr_array22array($myfield, \Phpcmf\Service::L('cache')->get('tag-'.SITE_ID.'-field')),
            'show_field' => 'name',
            'sys_field' => ['content'],
            'where_list' => 'pid='.$this->pid,
            'order_by' => 'id desc',
        ]);
        $this->pconfig = \Phpcmf\Service::M('tag', 'tag')->get_config();
        \Phpcmf\Service::V()->assign([
            'pid' => $this->pid,
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '关键词' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-tag'],
                    '添加' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    '修改' => ['hide:'.APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                    '批量添加' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/all_add', 'fa fa-plus-square-o'],
                    '自定义字段' => ['url:'.\Phpcmf\Service::L('Router')->url('field/index', ['rname' => 'tag', 'rid'=>SITE_ID]), 'fa fa-code'],
                    'help' => [63]
                ]
            ),
            'field' => $this->init['field'],
            'is_child' => isset($this->pconfig['child']) ? $this->pconfig['child'] : 0,
            'is_open_page' => isset($this->pconfig['open']) ? $this->pconfig['open'] : 0,
            'is_list_count' => isset($this->pconfig['list_count']) ? $this->pconfig['list_count'] : 0,
        ]);
    }

    // ========================

    public function module_del() {
        $tid = intval($_GET['tid']);
        $mid = dr_safe_filename($_GET['mid']);
        if (!$mid) {
            $this->_json(0, '模块mid不存在');
        }
        if (!$tid) {
            $this->_json(0, 'tag不存在');
        }
        $ids = \Phpcmf\Service::L('Input')->get_post_ids();
        if (!$ids) {
            $this->_json(0, '没有选择');
        }
        $table = SITE_ID.'_tag_'.$mid;
        foreach ($ids as $i) {
            $id = (int)$i;
            if ($id) {
                \Phpcmf\Service::M()->table($table)->where('tid', $tid)->where('cid', $id)->delete();
            }
        }
        $this->_json(1, dr_lang('成功删除%s个', dr_count($ids)));
    }
    public function module_all_del() {
        $tid = intval($_GET['tid']);
        $mid = dr_safe_filename($_GET['mid']);
        if (!$mid) {
            $this->_json(0, '模块mid不存在');
        }
        if (!$tid) {
            $this->_json(0, 'tag不存在');
        }
        $table = SITE_ID.'_tag_'.$mid;
        \Phpcmf\Service::M()->table($table)->where('tid', $tid)->delete();
        $this->_json(1, dr_lang('全部已删除'));
    }

    public function module_index() {

        $tid = intval($_GET['tid']);
        $mid = dr_safe_filename($_GET['mid']);
        if (!$mid) {
            $this->_html_msg(0, '模块mid不存在');
        }
        if (!$tid) {
            $this->_html_msg(0, 'tag不存在');
        }

        $this->_module_init($mid);
        $table = SITE_ID.'_tag_'.$mid;
        if (IS_POST) {
            $ids = \Phpcmf\Service::L('input')->get_post_ids();
            if (!$ids) {
                $this->_json(0, dr_lang('没有选择项'));
            }
            foreach ($ids as $i) {
                $id = (int)$i;
                if ($id) {
                    \Phpcmf\Service::M()->table($table)->replace([
                        'cid' => $id,
                        'tid' => $tid,
                    ]);
                }
            }

            $this->_json(1, dr_lang('成功关联%s个', dr_count($ids)));
        }

        $kw = dr_safe_keyword($_GET['kw']);
        $where = 'id in (select cid from '.\Phpcmf\Service::M()->dbprefix($table).' where tid='.$tid.')';
        if ($kw) {
            $where.= ' and (id LIKE "%'.$kw.'%" or title LIKE "%'.$kw.'%")';
        }

        \Phpcmf\Service::V()->assign([
            'kw' => $kw,
            'tid' => $tid,
            'mid' => $mid,
            'rurl' => dr_web_prefix('index.php?s=module&c=api&m=related&name=').'&site='.SITE_ID.'&module='.$mid.'&diy=&my=&pagesize=20&is_iframe=1',
            'urlrule' => dr_url('tag/home/module_index', ['is_iframe' => 1, 'tid' => $tid, 'mid' => $mid, 'kw' => $kw, 'page' => '[page]']),
            'table' => $table,
            'pszie' => SYS_ADMIN_PAGESIZE,
            'where' => urlencode($where),
        ]);
        \Phpcmf\Service::V()->display('module_list.html');
    }

    // 后台查看列表
    public function index() {

        list($tpl) = $this->_List();
        \Phpcmf\Service::V()->display($tpl);
    }

    public function all_del() {
        \Phpcmf\Service::M()->db->table(SITE_ID.'_tag')->truncate();
        $this->_json(1, '清空完毕');
    }

    public function clear_edit() {
        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        foreach ($module as $t) {
            $table = \Phpcmf\Service::M()->dbprefix(SITE_ID.'_tag_'.$t['dirname']);
            if (\Phpcmf\Service::M()->db->tableExists($table)) {
                \Phpcmf\Service::M()->db->table(SITE_ID . '_tag_' . $t['dirname'])->truncate();
            }
        }
        $this->_json(1, '清空完毕');
    }

    public function down_index() {
        $file = WRITEPATH.'app/tag.sql';
        if (is_file($file)) {
            \Phpcmf\Service::L('file')->down(
                $file,
                '',
                'tag.sql'
            );
        } else {
            $this->_html_msg(0, 'sql文件不存在');
        }
    }

    //一键更新词库地址
    public function index_edit() {

        $ct = (int)\Phpcmf\Service::L('input')->get('ct');
        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $psize = $this->pconfig['limit_index'] ? $this->pconfig['limit_index'] : 20; // 每页处理的数量

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->table_site('tag')->counts();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容生成'));
            }

            // 清空目录
            \Phpcmf\Service::M('tag', 'tag')->clear_tree_data();
            file_put_contents(WRITEPATH.'app/tag.sql', '');
            $url = dr_url('tag/home/'.\Phpcmf\Service::L('Router')->method);

            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
            /*
            $tpage = ceil($total / $psize); // 总页数


            if ($tpage > 50) {
                $sd = $url.'&total='.$total.'&ct=1&page='.($page+1);
                $zd = $url.'&total='.$total.'&page='.($page+1);
                $this->_html_msg(2, dr_lang('由于数据量多，这个过程就比较缓慢，手动导入SQL效率更快一些').'<br>
<a href="'.$sd.'">手动导入</a> &nbsp;&nbsp;
<a href="'.$zd.'">自动导入</a>
', '');
            } else {
                $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
            }
                */
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_html_msg(1, dr_lang('更新完成'));
            /*
            if ($ct) {
                $this->_html_msg(1, '请下载<a href="'.dr_url('tag/home/down_index').'">【SQL文件】</a>手动导入到本数据库中');
            } else {
                $this->_html_msg(1, dr_lang('更新完成'));
            }*/
        }

        $data = \Phpcmf\Service::M()->db->table(SITE_ID.'_tag')
            ->limit($psize, $psize * ($page - 1))
            ->orderBy('id DESC')
            ->get()->getResultArray();
        foreach ($data as $t) {
            \Phpcmf\Service::M('tag', 'tag')->save_index($t, $ct);
        }

        $url = dr_url('tag/home/'.\Phpcmf\Service::L('Router')->method, ['total' => $total, 'ct' => $ct, 'page' => $page + 1]);
        $this->_html_msg( 1, dr_lang('正在执行中【%s】...', "$tpage/$page"),
            $url,
            '每页执行'.$psize.'条数据'.($tpage > 100 ? '，由于数据量多，这个过程就比较缓慢' : '')
        );
    }
    //一键更新词库地址
    public function index2_edit() {

        $ct = (int)\Phpcmf\Service::L('input')->get('ct');
        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $psize = $this->pconfig['limit_index'] ? $this->pconfig['limit_index'] : 20; // 每页处理的数量

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->table_site('tag')->where('iscfile=1')->counts();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容生成'));
            }

            // 清空目录
            file_put_contents(WRITEPATH.'app/tag.sql', '');
            $url = dr_url('tag/home/'.\Phpcmf\Service::L('Router')->method);

            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
            /*
            $tpage = ceil($total / $psize); // 总页数


            if ($tpage > 50) {
                $sd = $url.'&total='.$total.'&ct=1&page='.($page+1);
                $zd = $url.'&total='.$total.'&page='.($page+1);
                $this->_html_msg(2, dr_lang('由于数据量多，这个过程就比较缓慢，手动导入SQL效率更快一些').'<br>
<a href="'.$sd.'">手动导入</a> &nbsp;&nbsp;
<a href="'.$zd.'">自动导入</a>
', '');
            } else {
                $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
            }
                */
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_html_msg(1, dr_lang('更新完成'));
            /*
            if ($ct) {
                $this->_html_msg(1, '请下载<a href="'.dr_url('tag/home/down_index').'">【SQL文件】</a>手动导入到本数据库中');
            } else {
                $this->_html_msg(1, dr_lang('更新完成'));
            }*/
        }

        $data = \Phpcmf\Service::M()->db->table(SITE_ID.'_tag')->where('iscfile=1')
            ->limit($psize, $psize * ($page - 1))
            ->orderBy('id DESC')
            ->get()->getResultArray();
        foreach ($data as $t) {
            \Phpcmf\Service::M('tag', 'tag')->save_index($t, $ct);
        }

        $url = dr_url('tag/home/'.\Phpcmf\Service::L('Router')->method, ['total' => $total, 'ct' => $ct, 'page' => $page + 1]);
        $this->_html_msg( 1, dr_lang('正在执行中【%s】...', "$tpage/$page"),
            $url,
            '每页执行'.$psize.'条数据'.($tpage > 100 ? '，由于数据量多，这个过程就比较缓慢' : '')
        );
    }

    // 一键更新词库地址
    public function url_edit() {

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = $this->pconfig['limit_update'] ? $this->pconfig['limit_update'] : 300; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->table_site('tag')->counts();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }

            // 清空目录
            \Phpcmf\Service::M('tag', 'tag')->clear_url_data();

            $url = dr_url('tag/home/'.\Phpcmf\Service::L('Router')->method);
            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            \Phpcmf\Service::M('cache')->sync_cache();
            $this->_html_msg(1, dr_lang('更新完成'));
        }

        $data = \Phpcmf\Service::M()->db->table(SITE_ID.'_tag')
            ->limit($psize, $psize * ($page - 1))
            ->orderBy('length(name) desc,displayorder DESC')
            ->get()->getResultArray();
        foreach ($data as $t) {
            \Phpcmf\Service::M('tag', 'tag')->save_tree($t);
        }

        $this->_html_msg( 1, dr_lang('正在执行中【%s】...', "$tpage/$page"),
            dr_url('tag/home/'.\Phpcmf\Service::L('Router')->method, ['total' => $total, 'page' => $page + 1]),
            ($tpage > 100 ? '由于数据量多，这个过程就比较缓慢，' : '').'每页执行'.$psize.'条数据'
        );
    }

    // 一键提取词语
    public function url_add() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $cpage = (int)\Phpcmf\Service::L('input')->get('cpage');
        $psize = $this->pconfig['limit_add'] ? $this->pconfig['limit_add'] : 1; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->table_site($mid)->counts();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容导入'));
            }

            $url = dr_url('tag/home/'.\Phpcmf\Service::L('Router')->method);
            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&mid='.$mid.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('导入完成'));
        }

        $this->_module_init($mid);

        $tfield = \Phpcmf\Service::M('tag', 'tag')->tag_field($mid);
        $data = \Phpcmf\Service::M()->db->table(SITE_ID.'_'.$mid)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        foreach ($data as $t) {
            if (isset($t[$tfield]) && $t[$tfield]) {
                $res = \Phpcmf\Service::M('Tag', 'tag')->auto_save_tag([1=>$t], $cpage, 10);
                if ($res) {
                    $this->_html_msg( 1, dr_lang('正在执行中【%s】...', "$tpage/$page"),
                        dr_url('tag/home/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid,'total' => $total, 'cpage' => $cpage + 1, 'page' => $page]),
                        '正在分组执行（'.($cpage+1).'）'
                    );
                }
            }
        }

        $this->_html_msg( 1, dr_lang('正在执行中【%s】...', "$tpage/$page"),
            dr_url('tag/home/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid,'total' => $total, 'page' => $page + 1]),
            ($tpage > 100 ? '由于数据量多，这个过程就比较缓慢，' : '').'每页执行'.$psize.'条数据'
        );
    }

    // 后台批量添加内容
    public function all_add() {

        $field = \Phpcmf\Service::L('cache')->get('tag-'.SITE_ID.'-field');
        if (IS_AJAX_POST) {
            $post = \Phpcmf\Service::L('input')->post('data', false);
            list($post, $return, $attach) = \Phpcmf\Service::L('Form')->validation(
                $post, 
                [],
                $field,
                []
            );
            // 输出错误
            if ($return) {
                $this->_json(0, $return['error'], ['field' => $return['name']]);
            }
            $rt = \Phpcmf\Service::M('Tag', 'tag')->save_all_data($this->pid, $_POST['all'], $post[1]);
            if (SYS_ATTACHMENT_DB && $attach) {
                \Phpcmf\Service::M('Attachment')->handle(
                    $this->member['id'],
                    \Phpcmf\Service::M()->dbprefix($this->init['table']),
                    $attach
                );
            }
            \Phpcmf\Service::M('cache')->sync_cache('tag', 'tag', 1);
            $this->_json($rt['code'], $rt['msg']);
        }

        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
            'myfield' => \Phpcmf\Service::L('Field')->toform(0, $field, []), 
            'reply_url' => \Phpcmf\Service::L('Router')->get_back(\Phpcmf\Service::L('Router')->uri('index')),
        ]);

        \Phpcmf\Service::V()->display('tag_all.html');
    }

    // 后台添加内容
    public function add() {
        list($tpl) = $this->_Post(0);
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display($tpl);exit;
    }

    // 后台修改内容
    public function edit() {
        list($tpl) = $this->_Post(intval(\Phpcmf\Service::L('Input')->get('id')));
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(),
        ]);
        \Phpcmf\Service::V()->display($tpl);exit;
    }

    // 后台批量保存排序值
    public function order_edit() {
        $this->_Display_Order(
            intval(\Phpcmf\Service::L('Input')->get('id')),
            intval(\Phpcmf\Service::L('Input')->get('value')),
            function ($r) {
                \Phpcmf\Service::M('cache')->sync_cache('tag', 'tag', 1);
            }
        );
    }

    // 后台删除内容
    public function del() {
        $this->_Del(
            \Phpcmf\Service::L('Input')->get_post_ids(),
            null,
            function ($r) {
                \Phpcmf\Service::M('cache')->sync_cache('tag', 'tag', 1);
            },
            \Phpcmf\Service::M()->dbprefix($this->init['table'])
        );
    }

    // ===========================

    /**
     * 保存内容
     * $id      内容id,新增为0
     * $data    提交内容数组,留空为自动获取
     * $func    格式化提交的数据
     * */
    protected function _Save($id = 0, $data = [], $old = [], $func = null, $func2 = null) {

        return parent::_Save($id, $data, $old,
            function ($id, $data, $old) {
                // 提交之前的判断
                $post = \Phpcmf\Service::L('Input')->post('data');
                if (\Phpcmf\Service::M('Tag', 'tag')->check_code($id, $post['code'])) {
                    return dr_return_data(0, dr_lang('别名已经存在'));
                } elseif (\Phpcmf\Service::M('Tag', 'tag')->check_name($id, $post['name'])) {
                    return dr_return_data(0, dr_lang('tag名称已经存在'));
                }
                $data[1]['pid'] = $old ? $old['pid'] : $this->pid;
                !$old && $data[1]['childids'] = ''; // 初始化字段
                $data[1]['code'] = $post['code'];
                $data[1]['hits'] = 0;
                !$data[1]['content'] && $data[1]['content'] = '';
                return dr_return_data(1, 'ok', $data);
            },
            function ($id, $data, $old) {
                // 提交之后
                $data[1]['pcode'] = \Phpcmf\Service::M('Tag', 'tag')->get_pcode($data[1]);
                // 更新
                \Phpcmf\Service::M('Tag', 'tag')->save_data($id, array(
                    'pcode' => $data[1]['pcode'],
                ));
                if ($data[1]['pid']) {
                    // 标记存在子菜单
                    \Phpcmf\Service::M()->table(SITE_ID.'_tag')->update($data[1]['pid'], array(
                        'childids' => 1,
                    ));
                }
                if (is_file(IS_USE_MODULE.'Models/Repair.php')) {
                    \Phpcmf\Service::M('Tag', 'tag')->save_index($data[1]);
                }
                \Phpcmf\Service::M('cache')->sync_cache('tag', 'tag', 1);
                return $data;
            }
        );
    }
}
