<?php namespace Phpcmf\Controllers\Admin;

class Mm extends \Phpcmf\Table
{
    private $phpfile = [];

    public function __construct()
    {
        parent::__construct();
        // 表单显示名称
        $this->name = dr_lang('扫描记录');
        // 模板前缀(避免混淆)
        $this->tpl_prefix = 'table_';
        // 入库字段，实例中用到两个字段录入
        $field = array(
            'file' => array(
                'ismain' => 1,
                'name' => dr_lang('文件'),
                'fieldname' => 'file',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => '100%',
                    ),
                    'validate' => array(
                        'required' => 1,
                    )
                )
            ),
            'error' => array(
                'ismain' => 1,
                'name' => dr_lang('可疑代码'),
                'fieldname' => 'error',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => '100%',
                    ),
                    'validate' => array(
                        'required' => 1,
                    )
                )
            ),
        );
        $list_field = [
            'inputtime' => array (
                'use' => '1', // 1是显示，0是不显示
                'name' => '时间', //显示名称
                'width' => '170', // 显示宽度
                'func' => 'datetime', // 回调函数见：http://help.xunruicms.com/463.html
                'center' => '1', // 1是居中，0是默认
            ),
            'file' => array (
                'use' => '1', // 1是显示，0是不显示
                'name' => '文件', //显示名称
                'width' => '', // 显示宽度
                'func' => '', // 回调函数见：http://help.xunruicms.com/463.html
                'center' => '0', // 1是居中，0是默认
            ),
            'error' => array (
                'use' => '1', // 1是显示，0是不显示
                'name' => '可疑代码', //显示名称
                'width' => '', // 显示宽度
                'func' => '', // 回调函数见：http://help.xunruicms.com/463.html
                'center' => '0', // 1是居中，0是默认
            ),
        ];
        // 初始化数据表
        $this->_init([
            'table' => 'app_safe_mm', // 表的名字
            'field' => $field, // 设置入库字段
            'list_field' => $list_field, // 设置入库字段
            'show_field' => 'file', // 表的主字段
            'order_by' => 'id desc', // 列表排序显示方式
        ]);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                        '木马文件扫描' => ['safe/mm/index', 'fa fa-bug'],
                        '扫描设置' => ['safe/mm/add', 'fa fa-cog'],
                        '自动扫描记录' => ['safe/mm/log_index', 'fa fa-table'],
                ])
        ]);
    }

    public function log_index() {
        list($tpl) = $this->_List(); // 完成table自动查询并分页显示动作
        // 侧链接，加一个a标签链接
        $this->mytable['link_tpl'] = '';
        \Phpcmf\Service::V()->assign([
            'mytable' => $this->mytable,
            'is_search' => 0,
        ]); // 设定显示模板
        \Phpcmf\Service::V()->display($tpl); // 设定显示模板
    }
    public function edit() {
        // 传入id到post方法表示修改此id提交的数据
        list($tpl) = $this->_Post(intval(\Phpcmf\Service::L('Input')->get('id')));
        \Phpcmf\Service::V()->display($tpl);
    }
    public function del() {
        $this->_Del(
            \Phpcmf\Service::L('Input')->get_post_ids(), // 获取批量删除id号
            null, // 删除之前的函数验证
            null, // 删除之后的函数处理
            \Phpcmf\Service::M()->dbprefix($this->init['table']) // 设定删除表名称
        );
    }
    public function index() {
        \Phpcmf\Service::V()->display('muma.html');
    }


    public function add() {

        $data = \Phpcmf\Service::M('app')->get_config('safe-mm');

        if (IS_AJAX_POST) {

            // 更新到后台表

            $post = \Phpcmf\Service::L('input')->post('data');
            \Phpcmf\Service::M('app')->save_config('safe-mm', $post);

            $this->_json(1, dr_lang('操作成功'));
        }

        $page = intval(\Phpcmf\Service::L('input')->get('page'));
        $run_time = '';
        if (is_file(WRITEPATH.'config/run_time.php')) {
            $run_time = file_get_contents(WRITEPATH.'config/run_time.php');
        }

        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'data' => $data,
            'form' => dr_form_hidden(['page' => $page]),
            'run_time' => $run_time,
        ]);
        \Phpcmf\Service::V()->display('mm.html');
    }

    // php文件个数
    public function php_count_index() {
        // 读取文件到缓存
        $this->_file_map(WEBPATH, 1);
        $this->_file_map(ROOTPATH.'config/');
        $this->_file_map(CONFIGPATH);
        if (is_file(MYPATH.'Dev.php')) {
            $this->_file_map(dr_get_app_list());
        }
        $this->_file_map(WRITEPATH);
        $this->_file_map(FCPATH);
        $this->_file_map(MYPATH);
        $this->_file_map(APPSPATH);

        $cache = [];
        $count = $this->phpfile ? count($this->phpfile) : 0;
        if ($count > 100) {
            $pagesize = ceil($count/100);
            for ($i = 1; $i <= 100; $i ++) {
                $cache[$i] = array_slice($this->phpfile, ($i - 1) * $pagesize, $pagesize);
            }
        } else {
            for ($i = 1; $i <= $count; $i ++) {
                $cache[$i] = array_slice($this->phpfile, ($i - 1), 1);
            }
        }

        // 存储文件
        \Phpcmf\Service::L('cache')->set_data('check-index', $cache, 3600);

        $this->_json($cache ? count($cache) : 0, 'ok');
    }

    public function php_check_index() {

        $page = max(1, intval($_GET['page']));
        $cache = \Phpcmf\Service::L('cache')->get_data('check-index');
        !$cache && $this->_json(0, '数据缓存不存在');

        $html = '';
        if ($page == 1) {
            $rs = \Phpcmf\Service::M('mm', 'safe')->init_mm()->index_title();
            if ($rs) {
                $html.= '<p class="p_error"><label class="rleft">'.$rs.'</label><label class="rright"><span class="error">seo标题不匹配</span></label></p>';
            }
        }

        $data = $cache[$page];
        if ($data) {
            foreach ($data as $filename) {

                // 避免自杀
                if (in_array(basename($filename), [
                    'Check_bom.php',
                    'error_exception.php',
                    'Mm.php'
                ])) {
                    continue;
                }

                $contents = file_get_contents ( $filename );

                $ok = "<span class='ok'>正常</span>";
                $class = '';
                if ($this->_is_bom($contents)) {
                    $ok = "<span class='error'>存在Bom字符</span>";
                    $class = ' p_error';
                } elseif ($rr = \Phpcmf\Service::M('mm', 'safe')->init_mm()->is_muma($contents)) {
                    $ok = "<span class='error'>可疑代码（".$rr."）</span>";
                    $class = ' p_error';
                } elseif (strpos($filename, APPSPATH) !== false && strpos($contents, '$_POST[')) {
                    if (strpos($contents, '=$_POST[') || strpos($contents, '= $_POST[')) {
                        $ok = "<span class='error'>POST可能不安全</span>";
                        $class = ' p_error';
                    } else {
                        $ok = "<span class='ok'>正常</span>";
                    }
                } elseif (strpos($filename, APPSPATH) !== false && strpos($contents, '$_GET[')) {
                    if (strpos($contents, '=$_GET[') || strpos($contents, '= $_GET[')) {
                        $ok = "<span class='error'>GET可能不安全</span>";
                        $class = ' p_error';
                    } else {
                        $ok = "<span class='ok'>正常</span>";
                    }
                }

                $html.= '<p class="'.$class.'"><label class="rleft">'.dr_safe_replace_path($filename).'</label><label class="rright">'.$ok.'</label></p>';
                if ($class) {
                    $html.= '<p class="rbf" style="display: none"><label class="rleft">'.$filename.'</label><label class="rright">'.$ok.'</label></p>';
                }
            }

            $this->_json($page + 1, $html);
        }


        // 完成
        \Phpcmf\Service::L('cache')->clear('check-index');
        $this->_json(100, '');
    }


    private function _is_bom($contents) {
        $charset [1] = substr ( $contents, 0, 1 );
        $charset [2] = substr ( $contents, 1, 1 );
        $charset [3] = substr ( $contents, 2, 1 );
        if (ord ( $charset [1] ) == 239 && ord ( $charset [2] ) == 187 && ord ( $charset [3] ) == 191) {
            return 1;
        }
        return 0;
    }

    private function _file_map($source_dir, $exit = 0) {
        if ($fp = opendir($source_dir)) {
            $source_dir	= rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            while (false !== ($file = readdir($fp))) {
                // Remove '.', '..', and hidden files [optional]
                if ($file === '.' || $file === '..') {
                    continue;
                }
                is_dir($source_dir.$file) && $file .= DIRECTORY_SEPARATOR;
                if (is_dir($source_dir.$file) && !$exit) {
                    $this->_file_map($source_dir.$file, $exit);
                } else {
                    trim(strtolower(strrchr($file, '.')), '.') == 'php' && $this->phpfile[] = $source_dir.$file;
                }
            }
            closedir($fp);
        }
    }

    public function test_index() {
        $note = '';
        $data = \Phpcmf\Service::L('input')->post('data');
        if (!$data) {
            $this->_json(0, dr_lang('参数错误'));
        } elseif (!$data['path']) {
            $note = dr_lang('目录留空时，采用系统默认全站目录');
        }


        $path = WEBPATH;
        if (strpos($path, 'public') !== false) {
            $path = dirname($path).'/';
        }

        if ($data['path']) {
            if (is_dir($data['path'])) {
                // 相对于根目录
                $path = $data['path'];
                $note = dr_lang('已使用自定义扫描目录');
            } else {
                // 在当前网站目录
                $note = dr_lang('自定义目录不可用');
            }
        }

        $this->_json(1, $note.'<br>'.dr_lang('扫描目录：%s', $path));
    }
}
