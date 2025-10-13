<?php namespace Phpcmf\Model\Form;

// 模型类
class Form extends \Phpcmf\Model
{

    public function __construct() {
        parent::__construct();
        $this->table = dr_site_table_prefix('form');
    }

    public function paytable($cache, $paytable, $siteid) {
        $form = $this->table($siteid.'_form')->getAll();
        if ($form) {
            foreach ($form as $t) {
                // 主表
                $table = $siteid.'_form_'.$t['table'];
                $prefix = $this->dbprefix($table);
                $cache[$prefix] = $this->db->getFieldNames($prefix);
                // 付款表
                $paytable['form-'.$siteid.'-'.$t['id']] = [
                    'table' => $table,
                    'name' => 'title',
                    'thumb' => 'thumb',
                    'url' => dr_web_prefix('index.php?s=form&c='.$t['table'].'&m=show&id='),
                    'username' => 'author',
                ];
            }
        }
        return [$cache, $paytable];
    }

    // 创建表单文件
    public function create_file($table, $call = 0) {

        $name = ucfirst($table);
        $path = dr_get_app_dir('form');
        $files = [
            $path.'Controllers/'.$name.'.php' => $path.'Code/$NAME$.php',
            $path.'Controllers/Member/'.$name.'.php' => $path.'Code/Member$NAME$.php',
            $path.'Controllers/Admin/'.$name.'.php' => $path.'Code/Admin$NAME$.php',
            $path.'Controllers/Admin/'.$name.'_verify.php' => $path.'Code/Admin$NAME$_verify.php',
        ];

        $ok = 0;
        foreach ($files as $file => $form) {
            if (!is_file($file)) {
                if (!is_dir(dirname($file))) {
                    dr_mkdirs(dirname($file));
                }
                $c = file_get_contents($form);
                $size = file_put_contents($file, str_replace('$NAME$', $name, $c));
                if (!$size && $call) {
                    unlink($file);
                    return dr_return_data(0, dr_lang('文件%s创建失败，无可写权限', str_replace(FCPATH, '', $file)));
                }
                $ok ++;
            }
        }
        
        return dr_return_data(1, $ok);
    }
    
    // 创建表单
    public function create($data) {

        $data['table'] = strtolower($data['table']);
        $rt = $this->insert([
            'name' => $data['name'],
            'table' => $data['table'],
            'setting' => '',
        ]);
        if (!$rt['code']) {
            return $rt;
        }
        
        // 创建文件
        $this->create_file($data['table']);
        
        // 创建表
        $this->create_form([
            'id' => $rt['code'],
            'name' => $data['name'],
            'table' => $data['table'],
        ]);

        return $rt;
    }

    // 导入
    public function import($data) {
		
		if (!is_array($data)) {
			return dr_return_data(0, dr_lang('导入格式不是数组格式'));
		} elseif (!$data['table']) {
            return dr_return_data(0, dr_lang('导入参数没有table参数'));
		} elseif (!$data['name']) {
            return dr_return_data(0, dr_lang('导入参数没有name参数'));
        } elseif ($this->table_site('form')->is_exists(0, 'table', $data['table'])) {
            return dr_return_data(0, dr_lang('数据表名称已经存在'));
        }

        $rt = $this->insert([
            'name' => $data['name'],
            'table' => $data['table'],
            'setting' => dr_array2string($data['setting']),
        ]);
        if (!$rt['code']) {
            return $rt;
        }
        $id = $rt['code'];
        // 导入字段
        foreach ($data['field'] as $t) {
            unset($t['id']);
            $t['relatedid'] = $id;
            $t['relatedname'] = 'form-'.SITE_ID;
            $r = parent::table('field')->insert($t);
            if (!$r['code']) {
                $this->db->table(dr_site_table_prefix('form'))->where('id', $id)->delete();
                $this->db->table('field')->where('relatedid', $t['relatedid'])->where('relatedname', $t['relatedname'])->delete();
                return $r;
            }
        }

        // 创建文件
        $this->create_file($data['table']);

        // 创建表
        $rt = \Phpcmf\Service::M('Table')->_query(str_replace('{table}', $this->dbprefix(dr_site_table_prefix('form').'_'.$data['table']), $data['sql']));

        if (!$rt['code']) {
            $this->db->table(dr_site_table_prefix('form'))->where('id', $id)->delete();
            $this->db->table('field')->where('relatedid', $id)->where('relatedname', 'form-'.SITE_ID)->delete();
            return $rt;
        }

        return dr_return_data(1, 'ok');
    }

    // 批量删除
    public function delete_form($ids) {

        foreach ($ids as $id) {
            $row = $this->table_site('form')->get(intval($id));
            if (!$row) {
                return dr_return_data(0, dr_lang('数据不存在(id:%s)', $id));
            }
            $rt = $this->table_site('form')->delete($id);
            if (!$rt['code']) {
                return dr_return_data(0, $rt['msg']);
            }
            $name = ucfirst($row['table']);
            $path = dr_get_app_dir('form');
            unlink($path.'Controllers/'.$name.'.php');
            unlink($path.'Controllers/Admin/'.$name.'.php');
            unlink($path.'Controllers/Member/'.$name.'.php');
            unlink($path.'Controllers/Admin/'.$name.'_verify.php');
            // 删除表数据
            $this->delete_form_table($row);
        }

        return dr_return_data(1, '');
    }

    // 从网站表单中更新菜单
    private function _menu($data) {

        foreach (['admin', 'admin_min'] as $table) {
            // 后台管理菜单
            $menu = $this->db->table($table.'_menu')->where('mark', 'form-' . $data['table'])->get()->getRowArray();
            if ($menu) {
                // 更新
                /*
                $this->db->table('admin_menu')->where('id', intval($menu['id']))->update([
                    'name' => dr_lang('%s管理', $data['name']),
                    'icon' => (string)$data['setting']['icon'],
                ]);*/
            } else {
                // 新增菜单
                $menu = $this->db->table($table.'_menu')->where('mark', 'content-module')->get()->getRowArray();
                if ($menu) {
                    \Phpcmf\Service::M('menu')->_add($table, $menu['id'], [
                        'name' => dr_lang('%s管理', $data['name']),
                        'icon' => dr_icon($data['setting']['icon']),
                        'uri' => 'form/' . $data['table'] . '/index',
                        'displayorder' => -1,
                    ], 'app-form-' . $data['table']);
                }
            }
            // 后台审核菜单
            $menu = $this->db->table($table.'_menu')->where('mark', 'verify-form-' . $data['table'])->get()->getRowArray();
            if ($menu) {
                // 更新
                /*
                $this->db->table('admin_menu')->where('id', intval($menu['id']))->update([
                    'name' => dr_lang('%s审核', $data['name']),
                    'icon' => $data['setting']['icon'],
                ]);*/
            } else {
                // 新增菜单
                $menu = $this->db->table($table.'_menu')->where('mark', 'content-verify')->get()->getRowArray();
                if ($menu) {
                    \Phpcmf\Service::M('menu')->_add($table, $menu['id'], [
                        'name' => dr_lang('%s审核', $data['name']),
                        'icon' => dr_icon($data['setting']['icon']),
                        'uri' => 'form/' . $data['table'] . '_verify/index',
                    ], 'app-form-verify-' . $data['table']);
                }
            }
        }
        if (isset($data['setting']['web']) && $data['setting']['web']) {
            return;
        }

        if ($this->is_table_exists('member_menu')) {

            // 用户菜单
            $menu = $this->db->table('member_menu')->where('mark', 'form-'.$data['table'])->get()->getRowArray();
            if ($menu) {
                // 更新
                $this->db->table('member_menu')->where('id', intval($menu['id']))->update([
                    'hidden' => $data['setting']['is_member'] ? 0 : 1,
                ]);
            } else {
                // 新增菜单
                $menu = $this->db->table('member_menu')->where('mark', 'content-module')->get()->getRowArray();
                if ($menu) {
                    \Phpcmf\Service::M('menu')->_add('member', $menu['id'], [
                        'name' => dr_lang('%s管理', $data['name']),
                        'icon' => (string)$data['setting']['icon'],
                        'uri' => 'form/'.$data['table'].'/index',
                    ], 'app-form-'.$data['table']);
                }
            }
        }
    }

    // 创建表单
    public function create_form($data) {

        $data['name'] = dr_safe_filename($data['name']);

        $pre = $this->dbprefix(SITE_ID.'_form');
        $sql = [
            "
			CREATE TABLE IF NOT EXISTS `".$pre.'_'.$data['table']."` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `uid` int(10) unsigned DEFAULT 0 COMMENT '录入者uid',
			  `author` varchar(100) DEFAULT NULL COMMENT '录入者账号',
			  `title` varchar(255) DEFAULT NULL COMMENT '主题',
			  `inputip` varchar(200) DEFAULT NULL COMMENT '录入者ip',
			  `inputtime` int(10) unsigned NOT NULL COMMENT '录入时间',
	          `status` tinyint(1) DEFAULT NULL COMMENT '状态值',
			  `displayorder` int(10) NOT NULL DEFAULT '0' COMMENT '排序值',
	          `tableid` smallint(5) unsigned NOT NULL COMMENT '附表id',
			  PRIMARY KEY `id` (`id`),
			  KEY `uid` (`uid`),
			  KEY `status` (`status`),
			  KEY `inputtime` (`inputtime`),
			  KEY `displayorder` (`displayorder`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='".$data['name']."表单表';"
            ,
            "CREATE TABLE IF NOT EXISTS `".$pre.'_'.$data['table']."_data_0` (
			  `id` int(10) unsigned NOT NULL,
			  `uid` int(10) unsigned DEFAULT 0 COMMENT '录入者uid',
			  UNIQUE KEY `id` (`id`),
			  KEY `uid` (`uid`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='".$data['name']."表单附表';"
        ];

        foreach ($sql as $s) {
            $this->db->simpleQuery(dr_format_create_sql($s));
        }

        // 默认字段
        $this->db->table('field')->insert(array(
            'name' => '主题',
            'fieldname' => 'title',
            'fieldtype' => 'Text',
            'relatedid' => $data['id'],
            'relatedname' => 'form-'.SITE_ID,
            'isedit' => 1,
            'ismain' => 1,
            'ismember' => 1,
            'issystem' => 1,
            'issearch' => 1,
            'disabled' => 0,
            'setting' => dr_array2string(array(
                'option' => array(
                    'width' => 300, // 表单宽度
                    'fieldtype' => 'VARCHAR', // 字段类型
                    'fieldlength' => '255' // 字段长度
                ),
                'validate' => array(
                    'xss' => 1, // xss过滤
                    'required' => 1, // 表示必填
                )
            )),
            'displayorder' => 0,
        ));
        $this->db->table('field')->insert(array(
            'name' => '作者',
            'fieldname' => 'author',
            'fieldtype' => 'Text',
            'relatedid' => $data['id'],
            'relatedname' => 'form-'.SITE_ID,
            'isedit' => 1,
            'ismain' => 1,
            'ismember' => 1,
            'issystem' => 1,
            'issearch' => 1,
            'disabled' => 0,
            'setting' => dr_array2string(array(
                'is_right' => 1,
                'option' => array(
                    'width' => 200, // 表单宽度
                    'fieldtype' => 'VARCHAR', // 字段类型
                    'fieldlength' => '255' // 字段长度
                ),
                'validate' => array(
                    'xss' => 1, // xss过滤
                )
            )),
            'displayorder' => 0,
        ));
    }

    // 删除表单
    public function delete_form_table($data) {

        $id = intval($data['id']);
        $pre = $this->dbprefix(SITE_ID.'_form');

        // 删除字段
        $this->db->table('field')->where('relatedid', $id)->where('relatedname', 'form-'.SITE_ID)->delete();

        // 删除表
        $table = $pre.'_'.$data['table'];
        $this->db->simpleQuery('DROP TABLE IF EXISTS `'.$table.'`');

        // 删除附表
        for ($i = 0; $i < 200; $i ++) {
            if (!$this->db->query("SHOW TABLES LIKE '".$table.'_data_'.$i."'")->getRowArray()) {
                break;
            }
            $this->db->simpleQuery('DROP TABLE IF EXISTS '.$table.'_data_'.$i);
        }

        // 删除菜单
        $this->table('admin_menu')->like('mark', 'form-'.$data['table'])->delete();
        $this->table('admin_menu')->like('mark', 'verify-form-'.$data['table'])->delete();
        $this->is_table_exists('member_menu') && $this->table('member_menu')->like('mark', 'form-'.$data['table'])->delete();

        // 删除记录
        $this->table(SITE_ID.'_form')->delete($id);

    }

    // 缓存
    public function cache($siteid = SITE_ID) {

        $table = dr_site_table_prefix('form', $siteid);
        if (!$this->is_table_exists($table)) {
            $sql = file_get_contents(dr_get_app_dir('form').'Config/Install_site.sql');
            $rt = $this->query_all(str_replace('{dbprefix}',  $this->dbprefix(dr_site_table_prefix('', $siteid)), $sql));
            if ($rt) {
                return dr_return_data(0, $rt);
            }
        }

        $data = $this->init(['table' => $table])->getAll();
        if ($data) {
            foreach ($data as $t) {
                $t['field'] = [];
                $t['setting'] = dr_string2array($t['setting']);
                // 排列table字段顺序
                $t['setting']['list_field'] = dr_list_field_order($t['setting']['list_field']);
                if (!$this->table('field')
                    ->where('relatedname', 'form-'.$siteid)
                    ->where('relatedid', intval($t['id']))
                    ->where('fieldname', 'author')->counts()) {
                    $this->db->table('field')->insert(array(
                        'name' => '作者',
                        'fieldname' => 'author',
                        'fieldtype' => 'Text',
                        'relatedid' => $t['id'],
                        'relatedname' => 'form-'.$siteid,
                        'isedit' => 1,
                        'ismain' => 1,
                        'ismember' => 1,
                        'issystem' => 1,
                        'issearch' => 1,
                        'disabled' => 0,
                        'setting' => dr_array2string(array(
                            'is_right' => 1,
                            'option' => array(
                                'width' => 200, // 表单宽度
                                'fieldtype' => 'VARCHAR', // 字段类型
                                'fieldlength' => '255' // 字段长度
                            ),
                            'validate' => array(
                                'xss' => 1, // xss过滤
                            )
                        )),
                        'displayorder' => 0,
                    ));
                }

                // 当前表单的自定义字段
                $field = $this->db->table('field')
                                ->where('disabled', 0)
                                ->where('relatedname', 'form-'.$siteid)
                                ->where('relatedid', intval($t['id']))
                                ->orderBy('displayorder ASC,id ASC')
                                ->get()->getResultArray();
                if ($field) {
                    foreach ($field as $fv) {
                        $fv['setting'] = dr_string2array($fv['setting']);
                        $t['field'][$fv['fieldname']] = $fv;
                    }
                }
                $cache[$t['table']] = $t;
                if (!$t['setting']['dev']) {
                    $this->_menu($t); // 更新菜单
                }
            }
        }

        \Phpcmf\Service::L('cache')->set_file('form-'.$siteid, $cache);
    }

    // 保存数据
    public function save_content($tid, $data, $data2 = []) {

        $data['status'] = isset($data['status']) ? intval($data['status']) : 1;
        $data['uid'] = isset($data['uid']) ? intval($data['uid']) : (int)$this->member['uid'];
        $data['author'] = isset($data['author']) ? trim($data['author']) : $this->member['username'];
        $data['inputip'] = isset($data['inputip']) ? $data['inputip'] : \Phpcmf\Service::L('input')->ip_info();
        $data['inputtime'] = isset($data['inputtime']) ? $data['inputtime'] : SYS_TIME;
        $data['tableid'] = 0;
        $data['displayorder'] = isset($data['displayorder']) ? $data['displayorder'] : 0;
        // 插入主表
        $rt = \Phpcmf\Service::M()->table_site("form_".$tid)->insert($data);
        if (!$rt['code']) {
            return $rt;
        }

        if ($data2) {
            // 如果要使用附表分表就 按一定量进行分表设置 比如50000
            $data['tableid'] = \Phpcmf\Service::M()->get_table_id($rt['code']);
            \Phpcmf\Service::M()->table_site("form_".$tid)->update($data['id'], ['tableid' => $data['tableid']]);
            if ($data['tableid'] > 0) {
                // 判断附表是否存在,不存在则创建
                $this->is_data_table("form_".$tid.'_data_', $data['tableid']);
            }
            $data2['id'] = $rt['code'];
            $data2['uid'] = (int)$data['uid'];
            // 插入附表
            $rt2 = \Phpcmf\Service::M()->table_site("form_".$tid."_data_".$data['tableid'])->insert($data2);
            if (!$rt2['code']) {
                // 删除主表
                \Phpcmf\Service::M()->table_site("form_".$tid)->delete($data['id']);
                return $rt2;
            }
        }

        return $rt;
    }

    // 网站表单内容地址
    public function show_url($form, $id, $page = 0) {

        return \Phpcmf\Service::L('router')->url_prefix('php', [], [], SITE_FID) . 's=form&c=' . $form . '&m=show&id=' . $id . ($page > 1 || strlen($page) > 1 ? '&page=' . $page : '');
    }

    // 网站表单提交地址
    public function post_url($form)
    {

        return \Phpcmf\Service::L('router')->url_prefix('php', [], [], SITE_FID) . 's=form&c=' . $form . '&m=post';
    }

    // 网站表单列表地址
    public function list_url($form, $page = 0)
    {

        return \Phpcmf\Service::L('router')->url_prefix('php', [], [], SITE_FID) . 's=form&c=' . $form . ($page > 1 || strlen($page) > 1 ? '&page=' . $page : '');
    }
}