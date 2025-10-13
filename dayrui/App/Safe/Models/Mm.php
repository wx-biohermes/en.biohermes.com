<?php namespace Phpcmf\Model\Safe;

class Mm extends \Phpcmf\Model {

    private $arr;
    private $config;
    private $keys;

    public function catcher_data($url, $timeout = 0, $is_log = true, $ct = 0) {

        if (!$url) {
            return '';
        }

        // 获取本地文件
        if (strpos($url, 'file://')  === 0) {
            return file_get_contents($url);
        } elseif (strpos($url, '/')  === 0 && is_file(WEBPATH.$url)) {
            return file_get_contents(WEBPATH.$url);
        } elseif (!dr_is_url($url)) {
            if (CI_DEBUG && $is_log) {
                log_message('error', '获取远程数据失败['.$url.']：地址前缀要求是http开头');
            }
            return '';
        }

        // curl模式
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if (substr($url, 0, 8) == "https://") {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true); // 从证书中检查SSL加密算法是否存在
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Forwarded-For: 220.181.108.157'));

            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)');
            ///
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1 );
            // 最大执行时间
            $timeout && curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
            $errno = curl_errno($ch);
            if (CI_DEBUG && $errno && $is_log) {
                log_message('error', '获取远程数据失败['.$url.']：（'.$errno.'）'.curl_error($ch));
            }
            curl_close($ch);
            if ($code == 200) {
                return $data;
            } elseif ($errno == 35) {
                // 当服务器不支持时改为普通获取方式
            } else {
                if (!$ct) {
                    // 尝试重试
                    return dr_catcher_data($url, $timeout, $is_log, 1);
                } elseif (CI_DEBUG && $code && $is_log) {
                    log_message('error', '获取远程数据失败['.$url.']http状态：'.$code);
                }
                return '';
            }
        }

        //设置超时参数
        if ($timeout && function_exists('stream_context_create')) {
            // 解析协议
            $opt = [
                'http' => [
                    'method'  => 'GET',
                    'timeout' => $timeout,
                ],
                'https' => [
                    'method'  => 'GET',
                    'timeout' => $timeout,
                ]
            ];
            $ptl = substr($url, 0, 8) == "https://" ? 'https' : 'http';
            $data = file_get_contents($url, 0, stream_context_create([
                $ptl => $opt[$ptl]
            ]));
        } else {
            $data = file_get_contents($url);
        }

        return $data;
    }

    public function index_title() {
        $this->init_mm();
        if (isset($this->config['index_title']) && $this->config['index_title']) {
            return false;
        } else {
            $html = $this->catcher_data(SITE_URL, 10);
            if ($html && preg_match('/<title>(.+)<\/title>/iU', $html, $mt)) {
                if ($mt[1]) {
                    $seo = \Phpcmf\Service::L('Seo')->index();
                    if ($seo['meta_title'] != $mt[1]) {
                        // 通知
                        if ($this->config['email']) {
                            \Phpcmf\Service::M('member')->sendmail(
                                $this->config['email'],
                                "首页seo标题不匹配，可能被篡改",
                                "扫描seo标题是：".$mt[1].'<br>系统设置的实际标题是：'.$seo['meta_title']);
                        }
                        if ($this->config['phone']) {
                            \Phpcmf\Service::M('member')->sendsms_text(
                                $this->config['phone'],
                                "首页seo标题不匹配，可能被篡改"
                            ); // 用于发送文本内容
                        }
                        return $mt[1];
                    }
                }
            }
        }
        return false;
    }

    public function init_mm() {
        if ($this->config) {
            return $this;
        }
        $this->config = \Phpcmf\Service::M('app')->get_config('safe-mm');
        $this->keys = [
            'eval($_POST',
            'eval($_GET',
            'eval($_REQUEST',
            'set_time_limit(0);header(',
            'function papa($h)',
            'xysword',
            'IsSpider',
        ];
        if (isset($this->config['code']) && $this->config['code']) {
            $arr = explode(PHP_EOL, $this->config['code']);
            if ($arr) {
                foreach ($arr as $t) {
                    $t && $this->keys[] = $t;
                }
            }
        }
        return $this;
    }

    public function safe_path() {
        $this->init_mm();
        $path = WEBPATH;
        if (strpos($path, 'public') !== false) {
            $path = dirname($path).'/';
        }
        if ($this->config['path'] && is_dir($this->config['path'])) {
            return $this->config['path'];
        }
        return $path;
    }

    public function auto_safe() {
        $path = $this->safe_path();
        $this->arr = [];
        if (!$this->config['auto']) {
            return;
        }
        $this->index_title();
        $this->checkdir($path);
    }

    private function checkdir($basedir){
        if ($dh = @opendir($basedir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..'){
                    $ext = trim(strtolower(strrchr($file, '.')), '.');
                    if (!is_dir($basedir."/".$file)) {
                        $zfile = $basedir."/".$file;
                        // 避免自杀
                        if (in_array(basename($file), [
                            'Check_bom.php',
                            'error_exception.php',
                            'Mm.php'
                        ])) {
                            continue;
                        }
                        if (in_array($ext, ['php', 'html', 'js', 'phtml'])
                            and filesize($zfile) < 1024*1024*5
                            and $file != SELF) {
                            $rt = $this->is_muma(file_get_contents($zfile));
                            if ($rt) {
                                $this->table('app_safe_mm')->insert([
                                    'file' => $zfile,
                                    'error' => $rt,
                                    'inputtime' => SYS_TIME,
                                    'ts' => 0,
                                ]);
                                // 通知
                                if ($this->config['email']) {
                                    \Phpcmf\Service::M('member')->sendmail(
                                        $this->config['email'],
                                        "发现可疑文件",
                                        "扫描文件：".$file.'<br>可疑代码：'.$rt);
                                }
                                if ($this->config['phone']) {
                                    \Phpcmf\Service::M('member')->sendsms_text(
                                        $this->config['phone'],
                                        "系统检测到你的网站中含有可疑代码"
                                    ); // 用于发送文本内容
                                }
                            }
                        }
                    }else{
                        $dirname = $basedir."/".$file;
                        $this->checkdir($dirname);
                    }
                }
            }
            closedir($dh);
        }
    }
    public function is_muma($contents) {
        if (!$contents) {
            return 0;
        }

        foreach ($this->keys as $t) {
            if (stripos($contents, $t) !== false) {
                return $t;
            }
        }
        return 0;
    }

    // 缓存
    public function cache($siteid = SITE_ID) {

        $table = $this->dbprefix('app_safe_mm');
        if (!\Phpcmf\Service::M()->db->tableExists($table)) {
            $sql = "CREATE TABLE IF NOT EXISTS `{dbprefix}app_safe_mm` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `file` varchar(255) DEFAULT NULL COMMENT '文件',
    `error` varchar(255) DEFAULT NULL COMMENT '木马代码',
    `inputtime` int(10) unsigned DEFAULT NULL COMMENT '扫描时间',
    `ts` int(1) unsigned DEFAULT NULL COMMENT '是否通知',
    PRIMARY KEY (`id`),
    KEY `ts` (`ts`),
    KEY `inputtime` (`inputtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='木马扫描记录';";
            $this->query_all(str_replace('{dbprefix}',  $this->prefix, $sql));
        }

    }


}