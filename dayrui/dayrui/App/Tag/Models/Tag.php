<?php namespace Phpcmf\Model\Tag;

// tag模型类

class Tag extends \Phpcmf\Model
{

    protected $tablename;
    protected $link_cache;

    protected $tag_field;


    public function __construct()
    {
        parent::__construct();
        $this->tablename = SITE_ID.'_tag';
        $this->link_cache = WRITEPATH.'tags/';
    }

    public function get_config($siteid = SITE_ID) {
        $config = \Phpcmf\Service::M('app')->get_config('tag');
        if ($siteid > 1 && isset($config[$siteid])) {
            return $config[$siteid];
        }
        return $config;
    }

    // 获取tag的关联字段
    public function tag_field($mid) {
        $config = $this->get_config();
        if (isset($config['field'][$mid]) && $config['field'][$mid]) {
            return $config['field'][$mid];
        }
        return 'keywords';
    }

    public function clear_data() {
        dr_dir_delete($this->link_cache.'tree_'.SITE_ID.'/');
        dr_dir_delete($this->link_cache.'url_'.SITE_ID.'/');
        dr_mkdirs($this->link_cache.'tree_'.SITE_ID.'/');
        dr_mkdirs($this->link_cache.'url_'.SITE_ID.'/');
        file_put_contents($this->link_cache.'data_'.SITE_ID.'.tag', '');
    }

    public function save_index($data, $ct = 0) {

        if (!$data) {
            return;
        }

        $name = trim((string)$data['name']);
        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        foreach ($module as $t) {
            $tfield = \Phpcmf\Service::M('tag', 'tag')->tag_field($t['dirname']);
            $sql = 'replace into '.$this->dbprefix(SITE_ID.'_tag_'.$t['dirname'])
                .' (cid, tid) select id,"'.$data['id'].'" from '
                .$this->dbprefix(SITE_ID.'_'.$t['dirname'])
                .' where FIND_IN_SET("'.$name.'", `'.$tfield.'`)';
            if ($ct) {
                file_put_contents(WRITEPATH.'app/tag.sql', $sql.PHP_EOL, FILE_APPEND);
            } else {
                $this->query($sql);
            }
            /*
            $list = $this->table_site($t['dirname'])->where('FIND_IN_SET("'.$name.'", `'.$tfield.'`)')->select('id')->getAll();
            if ($list) {
                foreach ($list as $a) {
                    $this->table_site('tag_'.$t['dirname'])->replace([
                        'cid' => $a['id'],
                        'tid' => $data['id'],
                    ]);
                }
            }*/
        }

        dr_mkdirs($this->link_cache.'index_'.SITE_ID.'/');
        $file = $this->link_cache.'index_'.SITE_ID.'/'.md5($name).'.php';
        file_put_contents($file, $data['id']);

    }

    public function save_tree($data) {

        if (!$data) {
            return;
        }

        dr_mkdirs($this->link_cache.'tree_'.SITE_ID.'/');
        $url = isset($data['myurl']) && $data['myurl'] ? $data['myurl'] : $this->tag_url($data);

        $data['name'] = trim((string)$data['name']);
        $this->_save_tag_cache(SITE_ID, $data, $url);

        $first = mb_substr($data['name'], 0, 1);
        $file = $this->link_cache.'tree_'.SITE_ID.'/'.md5($first).'.php';
        if (is_file($file)) {
            $treeArr = require $file;
        } else {
            $treeArr = [];
        }

        $len = mb_strlen($data['name']);
        $str = '$treeArr[';
        for ($i = 0; $i < $len; $i++) {
            $word = mb_substr($data['name'], $i, 1);
            $str.= '"'.addslashes($word).'"][';
        }

        $str = trim($str, '["');

        @eval($str.'=["ok" => 1];');

        file_put_contents($file, '<?php return '.var_export($treeArr, true).';');

        return $treeArr;


    }

    // 通过关键字获取tag
    public function tag_row($code) {

        // 首先查询
        $data = $this->db->table($this->tablename)->where('code', $code)->get()->getRowArray();
        if (!$data) {
            $data = $this->db->table($this->tablename)->where('name', $code)->get()->getRowArray();
        }
        //$data && $this->db->table($this->tablename)->where('id', (int)$data['id'])->set('hits', intval($data['hits'])+1)->update();

        return $data;
    }

    // 检查别名是否可用
    public function check_code($id, $value) {

        if (!$value) {
            return 1;
        }

        return $this->table($this->tablename)->is_exists($id, 'code', $value);
    }

    // 检查名称是否可用
    public function check_name($id, $value) {

        if (!$value) {
            return 1;
        }

        return $this->table($this->tablename)->is_exists($id, 'name', $value);
    }

    // 批量
    public function save_all_data($pid, $data, $my = []) {

        $c = 0;
        $py = \Phpcmf\Service::L('pinyin'); // 拼音转换类
        $names = explode(PHP_EOL, trim($data));
        foreach ($names as $t) {
            $t = trim($t);
            $yq = $this->table($this->tablename)->where('name', $t)->getRow();
            if ($yq) {
                // 已经存在
                if (is_file(IS_USE_MODULE.'Models/Repair.php')) {
                    $this->save_index($yq);
                }
                continue;
            } elseif ($this->check_name(0, $t)) {
                continue;
            }
            $cname = $py->result($t);
            $cname = str_replace(['\'', '"'], '', $cname);
            $count = $this->db->table($this->tablename)->where('code', $cname)->countAllResults();
            $code = $count ? $cname.$count : $cname;
            if ($this->db->table($this->tablename)->where('code', $code)->countAllResults()) {
                $code.= rand(0, 99999);
            }
            $pcode = $this->get_pcode(['pid' => $pid, 'code' => $code]);
            if ($pid) {
                // 标记存在子菜单
                $this->table($this->tablename)->update($pid, array(
                    'childids' => 1,
                ));
            }
            $save = array(
                'pid' => $pid,
                'name' => $t,
                'code' => $code,
                'pcode' => $pcode,
                'hits' => 0,
                'displayorder' => 0,
                'childids' => '',
                'content' => '',
            );
            if ($my) {
                foreach ($my as $i => $v) {
                    $save[$i] = $v;
                }
            }
            $rt = $this->table($this->tablename)->insert($save);
            if (!$rt['code']) {
                continue;
            }
            $save['id'] = $rt['code'];
            $this->save_tree($save);
            if (is_file(IS_USE_MODULE.'Models/Repair.php')) {
                $this->save_index($save);
            }
            $c++;
        }
        return dr_return_data(1, dr_lang('批量添加%s个', $c));
    }

    // save
    public function save_data($id, $data) {
        $this->table($this->tablename)->update($id, $data);
    }


    // 获取pcode
    public function get_pcode($data) {

        if (!$data['pid']) {
            return $data['code'];
        }

        $row = $this->table($this->tablename)->get($data['pid']);

        return trim($row['code'].'/'.$data['code'], '/');
    }

    // 内容自动存储到tag
    public function auto_save_tag($data) {

        $tfield = \Phpcmf\Service::M('tag', 'tag')->tag_field(MOD_DIR);

        $tag = $data[1][$tfield];
        if (!$tag) {
            return;
        }

        if (!dr_is_app('tag')) {
            return;
        }

        $arr = explode(',', $tag);
        foreach ($arr as $t) {
            if ($t) {
                $t = trim(dr_safe_replace($t));
                $yq = $this->table($this->tablename)->where('name', $t)->getRow();
                if ($yq) {
                    // 已经存在
                    if (is_file(IS_USE_MODULE.'Models/Repair.php')) {
                        $this->save_index($yq);
                    }
                    continue;
                } elseif (mb_strlen($t) > 50) {
                    continue;
                }
                $cname = \Phpcmf\Service::L('pinyin')->result($t); // 拼音转换类
                $cname = str_replace(['\'', '"'], '', $cname);
                $count = $this->db->table($this->tablename)->where('code', $cname)->countAllResults();
                $code = $count ? $cname.$count : $cname;
                if ($this->db->table($this->tablename)->where('code', $code)->countAllResults()) {
                    $code.= rand(0, 99999);
                }
                $pcode = $this->_get_tag_pcode(['pid' => 0, 'code' => $code]);
                $save = array(
                    'pid' => 0,
                    'name' => $t,
                    'code' => $code,
                    'pcode' => $pcode,
                    'hits' => 0,
                    'displayorder' => 0,
                    'childids' => '',
                    'content' => '',
                );
                $rt = $this->table($this->tablename)->insert($save);
                if ($rt['code']) {
                    $save['id'] = $rt['code'];
                    $this->save_tree($save);
                    if (is_file(IS_USE_MODULE.'Models/Repair.php')) {
                        $this->save_index($save);
                    }
                }
            }
        }

        // 更新数据
        IS_ADMIN && \Phpcmf\Service::M('cache')->update_data_cache();
    }

    public function tag_url($data, $page = 0) {
        if (!is_array($data)) {
            $data = [
                'id' => $data,
                'pcode' => $data,
                'name' => $data,
            ];
        }
        // PC端
        $cfg = $this->get_config();;
        if ($cfg['urlrule'] || $cfg['page_urlrule']) {
            $data['page'] = $page;
            $data['tag'] = $data['pcode'];
            $data['tag'] = str_replace('/', (string)$cfg['catjoin'], (string)$data['tag']);
            $url = ltrim($page ? $cfg['page_urlrule'] : $cfg['urlrule'], '/');
            return \Phpcmf\Service::L('router')->get_url_value(
                $data,
                $url,
                \Phpcmf\Service::L('router')->url_prefix('rewrite', [], [], SITE_FID)
            );
        } else {
            return \Phpcmf\Service::L('router')->url_prefix('php', [], [], SITE_FID) . 's=tag&name=' . $data['pcode'] . ($page ? '&page='.$page : '');
        }
    }

    public function url($page = 0) {
        // PC端
        $cfg = $this->get_config();;
        if ($page) {
            if ($cfg['index_page_urlrule']) {
                $data = [
                    'page' => $page,
                ];
                $url = ltrim($cfg['index_page_urlrule'], '/');
                return \Phpcmf\Service::L('router')->get_url_value($data, $url, \Phpcmf\Service::L('router')->url_prefix('rewrite', [], [], SITE_FID));
            } else {
                return \Phpcmf\Service::L('router')->url_prefix('php', [], [], SITE_FID) . 's=tag&page=' . $page;
            }
        } else {
            if ($cfg['index_urlrule']) {
                $data = [];
                $url = ltrim($cfg['index_urlrule'], '/');
                return \Phpcmf\Service::L('router')->get_url_value($data, $url, \Phpcmf\Service::L('router')->url_prefix('rewrite', [], [], SITE_FID));
            } else {
                return \Phpcmf\Service::L('router')->url_prefix('php', [], [], SITE_FID) . 's=tag';
            }
        }
    }

    // 缓存读取url
    public function get_tag_url($name, $mid = '') {

        if (!$name) {
            return IS_DEV ? '/#无name参数（关闭开发者模式将不会显示本词）' : '';
        }

        // 读缓存
        $file = $this->link_cache.'url_'.SITE_ID.'/'.md5($name);
        if (is_file($file)) {
            $url = file_get_contents($file);
            if ($url) {
                return $url;
            }
        }

        return IS_DEV ? '/#没有找到对应的URL（关闭开发者模式将不会显示本词）' : '';
    }

    // 获取pcode
    private function _get_tag_pcode($data) {

        if (!$data['pid']) {
            return $data['code'];
        }

        $row = $this->table($this->tablename)->get($data['pid']);

        return trim($row['code'].'/'.$data['code'], '/');
    }

    // 缓存链接，用于内链
    private function _save_tag_cache($siteid, $data, $url) {
        $name = $data['name'];
        dr_mkdirs($this->link_cache.'url_'.$siteid.'/');
        file_put_contents($this->link_cache.'url_'.$siteid.'/'.md5($name), $url);
        file_put_contents($this->link_cache.'data_'.SITE_ID.'.tag', json_encode([$name, $url]).PHP_EOL, FILE_APPEND);
        $data['url'] = $url;
        $data['siteid'] = $siteid;
        \Phpcmf\Hooks::trigger('tag_save', $data);
    }

    /**
     * 搜索词
     * @param string $txt
     * @return array
     */
    private function _search_words(string $txt, $arr) {
        $txtLength = mb_strlen($txt);
        $wordList = [];
        for($i = 0; $i < $txtLength; $i++){
            //检查字符是否存在词树内,传入检查文本、搜索开始位置、文本长度
            $word = $this->_check_word_tree($txt, $i, $txtLength, $arr);
            //存在词
            if($word){
                $wordList[] = $word;
            }
        }
        return $wordList;
    }

    /**
     * 检查词树是否合法
     * @param string $txt 检查文本
     * @param int $index 搜索文本位置索引
     * @param int $txtLength 文本长度
     * @return int 返回不合法字符个数
     */
    private function _check_word_tree(string $txt, int $index, int $txtLength, $treeArr)
    {
        $wordLength = 0;//字符个数
        $flag = 0;
        for($i = $index; $i < $txtLength; $i++){
            $txtWord = mb_substr($txt,$i,1); //截取需要检测的文本，和词库进行比对
            //如果搜索字不存在词库中直接停止循环。
            if(!isset($treeArr[$txtWord])) break;
            if(is_array($treeArr[$txtWord]) && !isset($treeArr[$txtWord]['ok'])){//检测还未到底
                $treeArr = $treeArr[$txtWord]; //继续搜索下一层tree
            }else{
                $flag = 1;
            }
            $wordLength++;
        }
        //没有检测到词，初始化字符长度
        $flag ?: $wordLength = 0;

        return mb_substr($txt, $index, $wordLength);
    }

    public function get_tag_data() {

        $data = file_get_contents($this->link_cache.'data_'.SITE_ID.'.tag');
        if (!$data) {
            return [];
        }

        $rt = [];
        $arr = explode(PHP_EOL, $data);
        foreach ($arr as $t) {
            if ($t) {
                $a = json_decode($t);
               if ($a) {
                   $rt[$a[0]] = $a[1];
               }
            }
        }

        return $rt;
    }

    // 提取关键词
    public function get_keywords($txt) {

        if (!$txt) {
            return [];
        }

        $rt = [];
        $len = mb_strlen(trim((string)$txt));
        for($i = 0; $i < $len; $i++){
            $word = mb_substr($txt, $i, 1);
            $file = $this->link_cache.'tree_'.SITE_ID.'/'.md5($word).'.php';
            if (is_file($file)) {
                $treeArr = require $file;
                // 开始找剩余的
                $list = $this->_search_words(mb_substr($txt, $i), $treeArr);
                if ($list) {
                    foreach ($list as $n) {
                        mb_strlen($n) >=2 && $rt[] = $n; // 找到了
                    }
                }
            }
        }

        if ($rt) {
            $rt = array_unique($rt);
        }

        return $rt;
    }

    // 内链
    public function neilian($txt, $blank = 1, $num = 0) {

        $name = 'tags_'.md5($txt);
        $data = \Phpcmf\Service::L('cache')->get_data($name);
        if (!$data) {
            $tags = $this->get_tag_data();
            if ($tags) {
                $txt = dr_content_link($tags, $txt, $num, $blank);
                // 缓存结果
                if (SYS_CACHE) {
                    if ($this->member && $this->member['is_admin']) {
                        // 管理员时不进行缓存
                        \Phpcmf\Service::L('cache')->init()->delete($name);
                    } else {
                        \Phpcmf\Service::L('cache')->set_data($name, $txt, SYS_CACHE_SHOW * 3600);
                    }
                }
            }
        } else {
            $txt = $data;
        }

        return $txt;
    }

    // 内链
    public function neilian2($txt, $blank = 1, $num = 0) {

        $name = 'tags2_'.md5($txt);
        $data = \Phpcmf\Service::L('cache')->get_data($name);
        if (!$data) {
            $rt = [];
            $len = mb_strlen($txt);
            for($i = 0; $i < $len; $i++){
                $word = mb_substr($txt, $i, 1);
                $file = $this->link_cache.'tree_'.SITE_ID.'/'.md5($word).'.php';
                if (is_file($file)) {
                    $treeArr = require $file;
                    // 开始找剩余的
                    $list = $this->_search_words(mb_substr($txt, $i), $treeArr);
                    if ($list) {
                        foreach ($list as $n) {
                            $url = $this->get_tag_url($n);
                            $rt[$n] = $url; // 找到了
                        }
                    }
                }
            }

            if ($rt) {
                $txt = dr_content_link($rt, $txt, $num, $blank);
                // 缓存结果
                if (SYS_CACHE) {
                    if ($this->member && $this->member['is_admin']) {
                        // 管理员时不进行缓存
                        \Phpcmf\Service::L('cache')->init()->delete($name);
                    } else {
                        \Phpcmf\Service::L('cache')->set_data($name, $txt, SYS_CACHE_SHOW * 3600);
                    }
                }
            }
        } else {
            $txt = $data;
        }

        return $txt;
    }

    // 缓存
    public function cache($siteid = SITE_ID) {

        $table = $this->dbprefix($siteid.'_tag');
        if (!\Phpcmf\Service::M()->db->tableExists($table)) {
            $sql = file_get_contents(dr_get_app_dir('tag').'Config/Install_site.sql');
            $this->query_all(str_replace('{dbprefix}',  $this->prefix.$siteid.'_', $sql));
        }

        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');
        foreach ($module as $t) {
            $table = $this->dbprefix($siteid.'_tag_'.$t['dirname']);
            if (!\Phpcmf\Service::M()->db->tableExists($table)) {
                $sql = "CREATE TABLE IF NOT EXISTS `".$table."` (
   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
   `cid` int(10) unsigned NOT NULL COMMENT '内容id',
   `tid` int(10) unsigned NOT NULL COMMENT 'tagid',
   PRIMARY KEY (`id`),
   UNIQUE KEY `cindex` (`cid`, `tid`),
   KEY `tid` (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='关键词库".$t['dirname']."索引表';";
                $this->query_all(str_replace('{dbprefix}',  $this->prefix.$siteid.'_', $sql));
            }
        }


        // 自定义字段
        $cache2 = [];
        $field = $this->db->table('field')->where('disabled', 0)->where('relatedid', $siteid)->where('relatedname', 'tag')->orderBy('displayorder ASC,id ASC')->get()->getResultArray();
        if ($field) {
            foreach ($field as $f) {
                $f['setting'] = dr_string2array($f['setting']);
                $cache2[$f['fieldname']] = $f;
            }
        }

        \Phpcmf\Service::L('cache')->set_file('tag-'.$siteid.'-field', $cache2);

        return $cache2;
    }
}