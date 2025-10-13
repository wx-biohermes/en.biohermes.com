<?php namespace Phpcmf\Controllers\Admin;

class Module_content extends \Phpcmf\Common
{

    public function __construct() {
        parent::__construct();
        // 不是超级管理员
        if (!dr_in_array(1, $this->admin['roleid'])) {
            $this->_admin_msg(0, dr_lang('需要超级管理员账号操作'));
        }
    }

    public function index() {

        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');

        $bm = [];
        $tables = \Phpcmf\Service::M()->db->query('show table status')->getResultArray();
        foreach ($tables as $t) {
            $t['Name'] = str_replace('_data_0', '_data_[tableid]', $t['Name']);
            $bm[$t['Name']] = $t;
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'form' =>  dr_form_hidden(['page' => $page]),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '内容维护工具' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-wrench'],
                    'help' => [1142],
                ]
            ),
            'table' => \Phpcmf\Service::L('input')->get('table'),
            'field' => \Phpcmf\Service::L('input')->get('field'),
            'tables' => $bm,
            'module' => $module,
            'sql_cache' => \Phpcmf\Service::L('File')->get_sql_cache(),
        ]);

        \Phpcmf\Service::V()->display('module_content.html');
    }

    // 联动加载字段
    public function field_index() {

        $table = dr_safe_replace(\Phpcmf\Service::L('input')->get('table'));
        $table = str_replace('_data_[tableid]', '_data_0', $table);
        if (!$table) {
            $this->_json(0, dr_lang('表参数不能为空'));
        } elseif (!\Phpcmf\Service::M()->db->tableExists($table)) {
            $this->_json(0, dr_lang('表[%s]不存在', $table));
        }

        $fields = \Phpcmf\Service::M()->db->query('SHOW FULL COLUMNS FROM `'.$table.'`')->getResultArray();
        if (!$fields) {
            $this->_json(0, dr_lang('表[%s]没有可用字段', $table));
        }

        $msg = '<select name="fd" class="form-control">';
        $field = \Phpcmf\Service::L('input')->get('field');
        foreach ($fields as $t) {
            if ($t['Field'] != 'id') {
                $msg.= '<option value="'.$t['Field'].'" '.($field && $field == $t['Field'] ? ' selected' : '').'>'.$t['Field'].($t['Comment'] ? '（'.$t['Comment'].'）' : '').'</option>';
            }
        }
        $msg.= '</select>';

        $this->_json(1, $msg);
    }

    // 内容维护替换
    public function edit() {
        $bm = \Phpcmf\Service::L('input')->post('bm');
        if (!$bm) {
            $this->_json(0, dr_lang('表名不能为空'));
        }
        $tables = [];
        if (strpos($bm, '[tableid]')) {
            for ($i = 0; $i < 200; $i ++) {
                $table = str_replace('[tableid]', $i, $bm);
                if (!\Phpcmf\Service::M()->db->query("SHOW TABLES LIKE '".$table."'")->getRowArray()) {
                    break;
                }
                $tables[$table] = $this->_get_field($table);
            }
        } else {
            $tables[$bm] = $this->_get_field($bm);
        }

        $t1 = \Phpcmf\Service::L('input')->post('t1');
        $t2 = \Phpcmf\Service::L('input')->post('t2');
        $tw = \Phpcmf\Service::L('input')->post('tw');
        $fd = dr_safe_replace(\Phpcmf\Service::L('input')->post('fd'));

        if (!$fd) {
            $this->_json(0, dr_lang('待替换字段必须填写'));
        } elseif (!$t1) {
            $this->_json(0, dr_lang('被替换内容必须填写'));
        } elseif (!$tables) {
            $this->_json(0, dr_lang('表名称必须填写'));
        } elseif ($fd == 'id') {
            $this->_json(0, dr_lang('ID主键不支持替换'));
        }

        $count = 0;
        $replace = '`'.$fd.'`=REPLACE(`'.$fd.'`, \''.addslashes($t1).'\', \''.addslashes($t2).'\')';
        if ($tw) {
            $replace.= ' WHERE '.$tw;
        }

        foreach ($tables as $table => $fields) {

            if (!dr_in_array($fd, $fields)) {
                $this->_json(0, dr_lang('表[%s]字段[%s]不存在', $table, $fd));
            }

            \Phpcmf\Service::M()->db->query('UPDATE `'.$table.'` SET '.$replace);
            $count = \Phpcmf\Service::M()->db->affectedRows();
        }

        if ($count < 0) {
            $this->_json(0, dr_lang('执行错误'));
        }

        $this->_json(1, dr_lang('本次替换%s条数据', $count), 'UPDATE `'.$table.'` SET '.$replace);
    }

    private function _is_rp_field($f, $table) {
        if (in_array($f['fieldtype'], [
            'File',
            'Files',
            'Editor',
            'Ueditor'
            ]) || strpos($f['fieldtype'], 'editor') !== false) {
            if (\Phpcmf\Service::M()->is_field_exists($table, $f['fieldname'])) {
                return 1;
            }
        }

        return 0;
    }
    // 全库
    public function dball_edit() {

        $key = (int)\Phpcmf\Service::L('input')->get('key');
        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $tpage = (int)\Phpcmf\Service::L('input')->get('tpage');
        $name = 'dball_edit'.$this->uid.'_';

        $url = dr_url(APP_DIR.'/'.'module_content/'.\Phpcmf\Service::L('Router')->method, [
            'key' => $key
        ]);

        $sqls = \Phpcmf\Service::L('cache')->get_auth_data($name.$key);

        if (!$page) {
            // 计算数量
            $t1 = \Phpcmf\Service::L('input')->get('t1');
            $t2 = \Phpcmf\Service::L('input')->get('t2');
            if (!$t1) {
                $this->_json(0, dr_lang('替换内容不能为空'));
            }

            $data = [];

            // 站点表
            $data[] = [\Phpcmf\Service::M()->dbprefix('site'), 'setting'];

            // 模块表
            $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
            if ($module) {
                foreach ($module as $m) {
                    $mod = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$m['dirname']);
                    if ($mod) {
                        $table = \Phpcmf\Service::M()->dbprefix(SITE_ID.'_'.$m['dirname']);
                        foreach ($mod['field'] as $t) {
                            if ($t['ismain']) {
                                $this->_is_rp_field($t, $table) && $data[] = [ $table, $t['fieldname'] ];
                            } else {
                                for ($i = 0; $i < 200; $i ++) {
                                    if (!\Phpcmf\Service::M()->db->query("SHOW TABLES LIKE '".$table.'_data_'.$i."'")->getRowArray()) {
                                        break;
                                    }
                                    $this->_is_rp_field($t, $table.'_data_'.$i) && $data[] = [ $table.'_data_'.$i, $t['fieldname'] ];
                                }
                            }
                        }
                        if ($mod['category_data_field']) {
                            foreach ($mod['category_data_field'] as $t) {
                                if ($t['ismain']) {
                                    $this->_is_rp_field($t, $table.'_category_data') && $data[] = [ $table.'_category_data', $t['fieldname'] ];
                                }
                            }
                        }
                        if ($mod['category_field']) {
                            foreach ($mod['category_field'] as $t) {
                                if ($t['ismain']) {
                                    $this->_is_rp_field($t, $table.'_category') && $data[] = [ $table.'_category', $t['fieldname'] ];
                                }
                            }
                        }
                    }
                }
            }

            $mod = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-share');
            if ($mod['category_field']) {
                $table = \Phpcmf\Service::M()->dbprefix(SITE_ID.'_share');
                foreach ($mod['category_field'] as $t) {
                    if ($t['ismain']) {
                        $this->_is_rp_field($t, $table.'_category') && $data[] = [ $table.'_category', $t['fieldname'] ];
                    }
                }
            }

            $cache = array_chunk($data, 30);
            foreach ($cache as $i => $t) {
                \Phpcmf\Service::L('cache')->set_auth_data($name.'-'.($i+1), $t);
            }

            \Phpcmf\Service::L('cache')->set_auth_data($name, [$t1, $t2]);
            \Phpcmf\Service::L('cache')->set_auth_data($name.$key, '');

            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&cache='.$name.'&page=1&tpage='.dr_count($cache));
        }

        $value = \Phpcmf\Service::L('cache')->get_auth_data($name);
        $replace = \Phpcmf\Service::L('cache')->get_auth_data($name.'-'.$page);
        if (!$value) {
            $this->_html_msg(0, dr_lang('临时数据读取失败'));
        }

        // 更新完成
        if (!isset($replace[$page+1]) or $page > $tpage) {
            $this->_html_msg(1, dr_lang('替换完成').'<br><br><pre><script type="text/html" style="display:block">'.($sqls).'</script></pre>');
        }

        foreach ($replace as $t) {
            $sql = 'update `'.$t[0].'` set `'.$t[1].'`=REPLACE(`'.$t[1].'`, \''.addslashes($value[0]).'\', \''.addslashes($value[1]).'\')';

            \Phpcmf\Service::M()->db->query($sql);
            $sqls = $sqls.PHP_EOL.$sql.';';
            \Phpcmf\Service::L('cache')->set_auth_data($name.$key, $sqls);
        }

        $this->_html_msg(1, dr_lang('正在执行中【%s】...', "$tpage/$page"), $url.'&tpage='.$tpage.'&page='.($page+1));
    }
    public function all_edit() {
        $bm = \Phpcmf\Service::L('input')->post('bm');
        if (!$bm) {
            $this->_json(0, dr_lang('表名不能为空'));
        }
        $tables = [];
        if (strpos($bm, '[tableid]')) {
            for ($i = 0; $i < 200; $i ++) {
                $table = str_replace('[tableid]', $i, $bm);
                if (!\Phpcmf\Service::M()->db->query("SHOW TABLES LIKE '".$table."'")->getRowArray()) {
                    break;
                }
                $tables[$table] = $this->_get_field($table);
            }
        } else {
            $tables[$bm] = $this->_get_field($bm);
        }

        $t1 = \Phpcmf\Service::L('input')->post('t1');
        $t2 = \Phpcmf\Service::L('input')->post('t2');
        $ms = (int)\Phpcmf\Service::L('input')->post('ms');
        $fd = dr_safe_replace(\Phpcmf\Service::L('input')->post('fd'));

        if (!$fd) {
            $this->_json(0, dr_lang('待修改字段必须填写'));
        } elseif (!$tables) {
            $this->_json(0, dr_lang('表名称必须填写'));
        } elseif ($fd == 'id') {
            $this->_json(0, dr_lang('ID主键不支持替换'));
        }

        $count = 0;

        $where = '';
        if ($t1) {
            // 防范sql注入后期需要加强
            foreach (['outfile', 'dumpfile', '.php', 'union', ';'] as $kw) {
                if (strpos(strtolower($t1), $kw) !== false) {
                    $this->_json(0, dr_lang('存在非法SQL关键词：%s', $kw));
                }
            }
            $where = ' WHERE '.addslashes($t1);
        }

        if ($ms == 1) {
            // 之前
            $replace = '`'.$fd.'`=CONCAT(\''.addslashes($t2).'\', `'.$fd.'`)';
        } elseif ($ms == 2) {
            // 之后
            $replace = '`'.$fd.'`=CONCAT(`'.$fd.'`, \''.addslashes($t2).'\')';
        } else {
            // 替换
            $replace = '`'.$fd.'`=\''.addslashes($t2).'\'';
        }


        foreach ($tables as $table => $fields) {

            if (!dr_in_array($fd, $fields)) {
                $this->_json(0, dr_lang('表[%s]字段[%s]不存在', $table, $fd));
            }

            \Phpcmf\Service::M()->db->query('UPDATE `'.$table.'` SET '.$replace . $where);
            $count = \Phpcmf\Service::M()->db->affectedRows();
        }

        if ($count < 0) {
            $this->_json(0, dr_lang('执行错误'));
        }

        $this->_json(1, dr_lang('本次替换%s条数据', $count), 'UPDATE `'.$table.'` SET '.$replace . $where);
    }

    // 执行sql
    public function sql_edit() {

        $msg = '';
        $sqls = trim(\Phpcmf\Service::L('input')->post('sql'));
        $replace = [];
        $replace[0][] = '{dbprefix}';
        $replace[1][] = \Phpcmf\Service::M()->db->DBPrefix;
        $sql_data = explode(';SQL_FINECMS_EOL', trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', str_replace($replace[0], $replace[1], $sqls))));

        if ($sql_data) {
            foreach($sql_data as $query){
                if (!$query) {
                    continue;
                }
                $ret = '';
                $queries = explode('SQL_FINECMS_EOL', trim($query));
                foreach($queries as $query) {
                    $ret.= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
                }
                $sql = trim($ret);
                if (!$sql) {
                    continue;
                }
                $ck = 0;
                foreach (['select', 'create', 'drop', 'alter', 'insert', 'replace', 'update', 'delete'] as $key) {
                    if (strpos(strtolower($sql), $key) === 0) {
                        if (!IS_DEV && in_array($key, ['create', 'drop', 'delete', 'alter'])) {
                            $this->_json(0, dr_lang('为了安全起见，在开发者模式下才能运行%s语句', $key), -1);
                        }
                        $ck = 1;
                        break;
                    }
                }
                if (!$ck) {
                    $this->_json(0, dr_lang('存在不允许执行的SQL语句：%s', dr_strcut($sql, 20)));
                }
                foreach (['outfile', 'dumpfile', '.php', 'union'] as $kw) {
                    if (strpos(strtolower($sql), $kw) !== false) {
                        $this->_json(0, dr_lang('存在非法SQL关键词：%s', $kw));
                    }
                }
                if (stripos($sql, 'select') === 0) {
                    // 查询语句
                    $db = \Phpcmf\Service::M()->db->query($sql);
                    !$db && $this->_json(0, dr_lang('查询出错'));
                    $rt = $db->getResultArray();
                    if ($rt) {
                        $msg.= var_export($rt, true);
                    } else {
                        $rt = \Phpcmf\Service::M()->db->error();
                        \Phpcmf\Service::L('File')->add_sql_cache($sql);
                        $this->_json(0, $rt['message']);
                    }
                } else {
                    // 执行语句
                    $db = \Phpcmf\Service::M()->db->query($sql);
                    if (!$db) {
                        $rt = \Phpcmf\Service::M()->db->error();
                        $this->_json(0, '查询错误：'.$rt['message']);
                    }
                }
            }
            $this->_json(1, $msg ? $msg : dr_lang('执行完成'));
        } else {
            $this->_json(0, dr_lang('不存在的SQL语句'));
        }
    }

    // 获取可用字段
    private function _get_field($bm) {

        $fields = \Phpcmf\Service::M()->db->query('SHOW FULL COLUMNS FROM `'.$bm.'`')->getResultArray();
        if (!$fields) {
            $this->_json(0, dr_lang('表[%s]没有可用字段', $bm));
        }

        $rt = [];
        foreach ($fields as $t) {
            $rt[] = $t['Field'];
        }

        return $rt;
    }

    //////////////////////////////


    public function tag_edit() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 10; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $table = $this->content_model->mytable;

        $where = 'status = 9';
        $catid = \Phpcmf\Service::L('input')->get('catid');
        $catid = dr_string2array($catid);

        $url = dr_url(APP_DIR.'/'.'module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);

        // 获取生成栏目
        if ($catid) {
            $cat = [];
            foreach ($catid as $i) {
                if ($i) {
                    $cat[] = intval($i);
                    $icat = dr_cat_value($this->module['mid'], $i);
                    if ($icat['child']) {
                        $cat = dr_array2array($cat, explode(',', $icat['childids']));
                    }
                    $url.= '&catid[]='.intval($i);
                }
            }
            $cat && $where.= ' AND catid IN ('.implode(',', $cat).')';
        }

        $keyword = \Phpcmf\Service::L('input')->get('keyword');
        $keyword && $where.= ' AND (keywords="" or keywords is null)';
        $url.= '&keyword='.$keyword;

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table($table)->where($where)->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }
            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page=1', dr_lang('在使用网络分词接口时可能会很慢'));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }

		$update = [];
        $data = \Phpcmf\Service::M()->db->table($table)->where($where)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        // 更新完成
        if (!$data) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }
        foreach ($data as $t) {
            $tag = dr_get_keywords($t['title'].' '.$t['description']);
            if ($tag) {
				$update[] = [
					'id' => (int)$t['id'],
					'keywords'=> $tag,
				];
            }
        }
		$update && \Phpcmf\Service::M()->db->table($table)->updateBatch($update, 'id');

        $this->_html_msg(1, dr_lang('正在执行中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1), dr_lang('在使用网络分词接口时可能会很慢'));

    }

    public function tag_index() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);


        \Phpcmf\Service::V()->assign([
            'select' => \Phpcmf\Service::L('category', 'module')->select($this->module['dirname'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
            'todo_url' => dr_url(APP_DIR.'/'.'module_content/tag_edit', ['mid' => $mid])
        ]);
        \Phpcmf\Service::V()->display('module_content_tag.html');
    }

    // 提取描述信息
    public function desc_edit() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 100; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $table = $this->content_model->mytable;

        $where = 'status = 9';
        $catid = \Phpcmf\Service::L('input')->get('catid');
        $catid = dr_string2array($catid);

        $url = dr_url(APP_DIR.'/'.'module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);

        // 获取生成栏目
        if ($catid) {
            $cat = [];
            foreach ($catid as $i) {
                if ($i) {
                    $cat[] = intval($i);
                    $row = dr_cat_value($this->module['mid'], $i);
                    if ($row && $row['child']) {
                        $cat = dr_array2array($cat, explode(',', $row['childids']));
                    }
                    $url.= '&catid[]='.intval($i);
                }
            }
            $cat && $where.= ' AND catid IN ('.implode(',', $cat).')';
        }

        $nums = max(1, \Phpcmf\Service::L('input')->get('nums'));
        $keyword = \Phpcmf\Service::L('input')->get('keyword');
        $keyword && $where.= ' AND (description="" or description is null)';
        $url.= '&nums='.$nums;
        $url.= '&keyword='.$keyword;

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table($table)->where($where)->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }
            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }

		$update = [];
        $data = \Phpcmf\Service::M()->db->table($table)->where($where)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        if (!$data) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }
        foreach ($data as $row) {
            if (!$this->module['field']['content']['ismain']) {
                $row = \Phpcmf\Service::M()->db->table($table.'_data_'.$row['tableid'])->select('content,id')->where('id', $row['id'])->get()->getRowArray();
            }
            if ($row && $row['content']) {
				$update[] = [
					'id' => (int)$row['id'],
                    'description' => dr_get_description(dr_code2html($row['content']), $nums)
				];
            }
        }
		$update && \Phpcmf\Service::M()->db->table($table)->updateBatch($update, 'id');

        $this->_html_msg(1, dr_lang('正在执行中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1));

    }
    public function desc_index() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);


        \Phpcmf\Service::V()->assign([
            'select' => \Phpcmf\Service::L('category', 'module')->select($this->module['dirname'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
            'todo_url' => dr_url(APP_DIR.'/'.'module_content/desc_edit', ['mid' => $mid])
        ]);
        \Phpcmf\Service::V()->display('module_content_desc.html');
    }

    public function thumb_edit() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 100; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $table = $this->content_model->mytable;

        $where = 'status = 9';
        $catid = \Phpcmf\Service::L('input')->get('catid');

        $catid = dr_string2array($catid);

        $url = dr_url(APP_DIR.'/'.'module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);

        // 获取生成栏目
        if ($catid) {
            $cat = [];
            foreach ($catid as $i) {
                if ($i) {
                    $cat[] = intval($i);
                    $row = dr_cat_value($this->module['mid'], $i);
                    if ($row && $row['child']) {
                        $cat = dr_array2array($cat, explode(',', $row['childids']));
                    }
                    $url.= '&catid[]='.intval($i);
                }
            }
            $cat && $where.= ' AND catid IN ('.implode(',', $cat).')';
        }

        $thumb = \Phpcmf\Service::L('input')->get('thumb');
        $thumb && $where.= ' AND (thumb="" or thumb is null)';
        $url.= '&thumb='.$thumb;

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table($table)->where($where)->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }

            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }

		$update = [];
        $data = \Phpcmf\Service::M()->db->table($table)->where($where)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        // 更新完成
        if (!$data) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }
        foreach ($data as $row) {
            if (!$this->module['field']['content']['ismain']) {
                $row = \Phpcmf\Service::M()->db->table($table.'_data_'.$row['tableid'])->select('content,id')->where('id', $row['id'])->get()->getRowArray();
            }
            if ($row && $row['content'] && preg_match("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|png|webp))\\2/i", htmlspecialchars_decode($row['content']), $m)) {
				$update[] = [
					'id' => (int)$row['id'],
                    'thumb' => str_replace(['"', '\''], '', $m[3])
				];
            }
        }
		$update && \Phpcmf\Service::M()->db->table($table)->updateBatch($update, 'id');

        $this->_html_msg(1, dr_lang('正在执行中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1));
    }
    public function thumb_index() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        \Phpcmf\Service::V()->assign([
            'select' => \Phpcmf\Service::L('category', 'module')->select($this->module['dirname'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
            'todo_url' => dr_url(APP_DIR.'/'.'module_content/thumb_edit', ['mid' => $mid])
        ]);
        \Phpcmf\Service::V()->display('module_content_thumb.html');
    }

    public function xthumb_index() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        \Phpcmf\Service::V()->assign([
            'select' => \Phpcmf\Service::L('category', 'module')->select($this->module['dirname'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
            'todo_url' => dr_url(APP_DIR.'/'.'module_content/xthumb_edit', ['mid' => $mid])
        ]);
        \Phpcmf\Service::V()->display('module_content_xthumb.html');
    }
    public function xthumb_edit() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        if ($this->module['field']['thumb']['fieldtype'] != 'File') {
            $this->_html_msg(0, dr_lang('thumb字段是'.$this->module['field']['thumb']['fieldtype'].'类型，只对File类型有效'));
        }

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 10; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $table = $this->content_model->mytable;

        $where = 'status = 9';
        $catid = \Phpcmf\Service::L('input')->get('catid');
        $catid = dr_string2array($catid);

        $url = dr_url(APP_DIR.'/'.'module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);

        // 获取生成栏目
        if ($catid) {
            $cat = [];
            foreach ($catid as $i) {
                if ($i) {
                    $cat[] = intval($i);
                    $row = dr_cat_value($this->module['mid'], $i);
                    if ($row && $row['child']) {
                        $cat = dr_array2array($cat, explode(',', $row['childids']));
                    }
                    $url.= '&catid[]='.intval($i);
                }
            }
            $cat && $where.= ' AND catid IN ('.implode(',', $cat).')';
        }

        $where.= ' AND thumb like "http%"';

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table($table)->where($where)->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }

            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }

        $update = [];
        $data = \Phpcmf\Service::M()->db->table($table)->where($where)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        $attconfig = \Phpcmf\Service::M('Attachment')->get_attach_info(
            intval($this->module['field']['thumb']['setting']['option']['attachment']), $this->module['field']['thumb']['setting']['option']['image_reduce']
        );
        foreach ($data as $row) {
            $ext = $this->_get_image_ext($row['thumb']);
            if (!$ext) {
                continue;
            }
            // 下载远程文件
            $rt = \Phpcmf\Service::L('upload')->down_file([
                'url' => $row['thumb'],
                'timeout' => 5,
                'watermark' => 0,
                'attachment' => $attconfig,
                'file_ext' => $ext,
            ]);
            if ($rt['code']) {
                // 附件归档
                $att = \Phpcmf\Service::M('Attachment')->save_data($rt['data'], \Phpcmf\Service::M()->dbprefix($table).'-'.$row['id']);
                if ($att['code']) {
                    $update[] = [
                        'id' => (int)$row['id'],
                        'thumb' =>$att['code']
                    ];
                }
            }
        }
        $update && \Phpcmf\Service::M()->db->table($table)->updateBatch($update, 'id');

        $this->_html_msg(1, dr_lang('正在执行中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1));
    }
    // 获取远程附件扩展名
    protected function _get_image_ext($url) {

        if (strlen($url) > 300) {
            return '';
        }

        $arr = ['gif', 'jpg', 'jpeg', 'png', 'webp'];
        $ext = str_replace('.', '', trim(strtolower(strrchr($url, '.')), '.'));
        if ($ext && in_array($ext, $arr)) {
            return $ext; // 满足扩展名
        } elseif ($ext && strlen($ext) < 4) {
            //CI_DEBUG && log_message('error', '此路径不是远程图片：'.$url);
            return ''; // 表示不是图片扩展名了
        }

        foreach ($arr as $t) {
            if (stripos($url, $t) !== false) {
                return $t;
            }
        }

        $rt = getimagesize($url);
        if ($rt && $rt['mime']) {
            foreach ($arr as $t) {
                if (stripos($rt['mime'], $t) !== false) {
                    return $t;
                }
            }
        }

        CI_DEBUG && log_message('debug', '服务器无法获取远程图片的扩展名：'.dr_safe_replace($url));

        return '';
    }

    public function del() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 1; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $table = $this->content_model->mytable;

        $where = [];
        $catid = \Phpcmf\Service::L('input')->get('catid');
        $catid = dr_string2array($catid);

        $url = dr_url(APP_DIR.'/'.'module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);

        // 获取生成栏目
        if ($catid) {
            $cat = [];
            foreach ($catid as $i) {
                if ($i) {
                    $cat[] = intval($i);
                    $row = dr_cat_value($this->module['mid'], $i);
                    if ($row && $row['child']) {
                        $cat = dr_array2array($cat, explode(',', $row['childids']));
                    }
                    $url.= '&catid[]='.intval($i);
                }
            }
            $cat && $where[] = 'catid IN ('.implode(',', $cat).')';
        }

        $username = \Phpcmf\Service::L('input')->get('username');
        if ($username) {
            $where[] = 'uid in (select id from '.\Phpcmf\Service::M()->dbprefix('member').' where username="'.dr_safe_replace($username).'")';
            $url.= '&username='.$username;
        }

        $uid = (int)\Phpcmf\Service::L('input')->get('uid');
        if ($uid) {
            $where[] = 'uid='.$uid;
            $url.= '&uid='.$uid;
        }

        $id1 = (int)\Phpcmf\Service::L('input')->get('id1');
        $id2 = (int)\Phpcmf\Service::L('input')->get('id2');
        if ($id1 || $id2) {
            if (!$id2) {
                $where[] = 'id>'.$id1;
            } else {
                $where[] = '`id` BETWEEN '.$id1.' AND '.$id2;
            }
            $url.= '&id1='.$id1.'&id2='.$id2;
        }

        $sql = \Phpcmf\Service::L('input')->get('sql', true);
        if ($sql) {
            // 防范sql注入后期需要加强
            foreach (['outfile', 'dumpfile', '.php', 'union', ';'] as $kw) {
                if (strpos(strtolower($sql), $kw) !== false) {
                    $this->_html_msg(0, dr_lang('存在非法SQL关键词：%s', $kw));
                }
            }
            $where[] = addslashes($sql);
            $url.= '&sql='.$sql;
        }

        if (!$where) {
            $this->_html_msg(0, dr_lang('没有设置条件'));
        }

        $where = implode(' AND ', $where);
        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->db->table($table)->where($where)->countAllResults();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容'));
            }

            $this->_html_msg(1, dr_lang('正在删除中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('删除%s条数据完成', $total));
        }

        $data = \Phpcmf\Service::M()->db->table($table)->where($where)->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();
        foreach ($data as $row) {
            $this->content_model->delete_content($row['id']);
        }

        $this->_html_msg(1, dr_lang('正在删除中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1));
    }
    public function del_index() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        \Phpcmf\Service::V()->assign([
            'select' => \Phpcmf\Service::L('category', 'module')->select($this->module['dirname'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
            'todo_url' => dr_url(APP_DIR.'/'.'module_content/del', ['mid' => $mid])
        ]);
        \Phpcmf\Service::V()->display('module_content_del.html');
    }

    public function cat_edit() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        $page = (int)\Phpcmf\Service::L('input')->get('page');
        $psize = 100; // 每页处理的数量
        $total = (int)\Phpcmf\Service::L('input')->get('total');
        $table = $this->content_model->mytable;

        $toid = (int)\Phpcmf\Service::L('input')->get('toid');
        if (!$toid) {
            $this->_html_msg(0, dr_lang('目标栏目必须选择'));
        }

        $url = dr_url(APP_DIR.'/'.'module_content/'.\Phpcmf\Service::L('Router')->method, ['mid' => $mid]);
        $url.= '&toid='.$toid;
        $where = [];

        // 获取生成栏目
        $catid = \Phpcmf\Service::L('input')->get('catid');
        $catid = dr_string2array($catid);
        if ($catid) {
            $cat = [];
            foreach ($catid as $i) {
                if ($i) {
                    $cat[] = intval($i);
                    $row = dr_cat_value($this->module['mid'], $i);
                    if ($row && $row['child']) {
                        $cat = dr_array2array($cat, explode(',', $row['childids']));
                    }
                    $url.= '&catid[]='.intval($i);
                }
            }
            $cat && $where[] = 'catid IN ('.implode(',', $cat).')';
        }
        $sql = \Phpcmf\Service::L('input')->get('sql', true);
        if ($sql) {
            // 防范sql注入后期需要加强
            foreach (['outfile', 'dumpfile', '.php', 'union', ';'] as $kw) {
                if (strpos(strtolower($sql), $kw) !== false) {
                    $this->_html_msg(0, dr_lang('存在非法SQL关键词：%s', $kw));
                }
            }
            $where[] = addslashes($sql);
            $url.= '&sql='.$sql;
        }

        if (!$where) {
            $this->_html_msg(0, dr_lang('没有设置条件或者没有选择被变更的栏目'));
        }

        if ($where) {
            $where = implode(' AND ', $where);
        } else {
            $where = '';
        }

        if (!$page) {
            // 计算数量
            $total = \Phpcmf\Service::M()->table($table.'_index')->where($where)->counts();
            if (!$total) {
                $this->_html_msg(0, dr_lang('无可用内容更新'));
            }

            $this->_html_msg(1, dr_lang('正在执行中...'), $url.'&total='.$total.'&page='.($page+1));
        }

        $tpage = ceil($total / $psize); // 总页数

        // 更新完成
        if ($page > $tpage) {
            $this->_html_msg(1, dr_lang('更新完成'));
        }

        $db = \Phpcmf\Service::M()->db->table($table.'_index');
        $where && $db->where($where);
        $data = $db->limit($psize, $psize * ($page - 1))->orderBy('id DESC')->get()->getResultArray();

        $ids = [];
        foreach ($data as $row) {
            $ids[] = $row['id'];
        }

        $this->content_model->move_category($ids, $toid);

        $this->_html_msg(1, dr_lang('正在执行中【%s】...', "$tpage/$page"), $url.'&total='.$total.'&page='.($page+1));
    }
    public function cat_index() {

        $mid = dr_safe_filename(\Phpcmf\Service::L('input')->get('mid'));
        if (!$mid) {
            $this->_html_msg(0, dr_lang('mid参数不能为空'));
        }

        $this->_module_init($mid);

        \Phpcmf\Service::V()->assign([
            'select' => \Phpcmf\Service::L('category', 'module')->select($this->module['dirname'], 0, 'name=\'catid[]\' multiple style=\'height:200px\'', dr_lang('全部栏目')),
            'todo_url' => dr_url(APP_DIR.'/'.'module_content/cat_edit', ['mid' => $mid]),
            'select_post' => \Phpcmf\Service::L('category', 'module')->select(
                $this->module['dirname'],
                0,
                'name=\'toid\'',
                dr_lang('选择栏目'), 1, 1
            ),
        ]);
        \Phpcmf\Service::V()->display('module_content_cat.html');
    }

}
