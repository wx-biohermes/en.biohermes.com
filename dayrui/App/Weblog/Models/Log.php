<?php namespace Phpcmf\Model\Weblog;

class Log extends \Phpcmf\Model {

    // 开始运行
    public function run() {

        if (IS_API) {
            return;
        }

        // 自己的不统计
        if (APP_DIR == 'weblog') {
            return;
        }

        if (is_cli()) {
            return;
        }

        $this->_save_log(FC_NOW_URL);
    }

    public function html($url) {
        $this->_save_log($url);
    }

    // 禁止访问
    private function _not_read($config) {
        if ($config['not_type'] == 1) {
            echo $config['not_type_html'];
        } elseif ($config['not_type'] == 2) {
            header('Location: '.$config['not_type_url']);
        }
        exit;
    }

    private function _save_log($url) {

        $url = (string)$url;

        if (!$url) {
            return;
        } elseif (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
            return;
        } elseif (strpos($url, 'm=html') !== false) {
            return;
        } elseif (defined('SC_HTML_FILE')) {
            return;
        }

        // 过滤无用数据
        if (strpos($url, '?debugbar') !== false
            || strpos($url, 'c=api') !== false
            || strpos($url, 'index.php?s=weixin') !== false
            || strpos($url, 'ajax') !== false
            || strpos($url, '&c=html&m=') !== false
            || strpos($url, '&callback=jQuery') !== false
        ) {
            return;
        }

        $config = \Phpcmf\Service::R(WRITEPATH.'config/weblog.php');
        if (!$config['use']) {
            return;
        }

        // 禁止
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD' && $config['not_head']) {
            $this->_not_read($config);
            return;
        }

        // 过滤后台
        if (!$config['is_admin'] && IS_ADMIN) {
            return;
        }

        // 过滤url
        if ($config['not_urls']) {
            $arr = explode(PHP_EOL, $config['not_urls']);
            if ($arr) {
                foreach ($arr as $t) {
                    if ($t && stripos($url, trim($t)) !== false) {
                        return;
                    }
                }
            }
        }

        $useragent = str_replace(['"', "'"], '', (string)$_SERVER['HTTP_USER_AGENT']);
        !$useragent && $useragent = '未知';

        if ($config['not_useragent']) {
            $arr = explode(PHP_EOL, $config['not_useragent']);
            foreach ($arr as $t) {
                if ($t && stripos($useragent, trim($t)) !== false) {
                    $this->_not_read($config);
                    return;
                }
            }
        }

        // 过滤蜘蛛
        if (!$config['is_spider'] && (
                stripos($useragent, 'spider') !== false
                || stripos($useragent, 'bot.htm') !== false
                || stripos($useragent, 'robot') !== false
                || stripos($useragent, 'Bing') !== false
                || stripos($useragent, 'DotBot') !== false
                || stripos($useragent, 'MJ12bot') !== false
                || stripos($useragent, 'http://') !== false
                || stripos($useragent, 'https://') !== false
            )) {
            return;
        }

        $uid = (int)$this->_get_cookie('member_uid');
        if ($config['not_uids'] && dr_in_array($uid, $config['not_uids'])) {
            return;
        }

        $ip = $this->_ip_address();
        if ($config['not_ips']) {
            $arr = explode(PHP_EOL, $config['not_ips']);
            foreach ($arr as $t) {
                if ($t && trim($t) == $ip) {
                    $this->_not_read($config);
                    return;
                }
            }
        }

        if ($config['get_time'] && $config['get_total']
            && $this->table('app_web_log')
                ->where('inputip', $ip)
                ->where('method', 'GET')
                ->where('inputtime BETWEEN '.strtotime("-".intval($config['get_time'])." hour").' AND '.SYS_TIME)
                ->counts() > intval($config['get_total'])
        ) {

            file_put_contents(WRITEPATH.'weblog_ip.txt', PHP_EOL.PHP_EOL.date('Y-m-d H:i:s').' - '. $ip . ' - '. $uid.PHP_EOL, FILE_APPEND);
            $this->_not_read($config);
        }

        if ($config['post_time'] && $config['post_total']
            && $this->table('app_web_log')
                ->where('inputip', $ip)
                ->where('method', 'POST')
                ->where('inputtime BETWEEN '.strtotime("-".intval($config['post_time'])." hour").' AND '.SYS_TIME)
                ->counts() > intval($config['post_total'])
        ) {
            file_put_contents(WRITEPATH.'weblog_ip.txt', PHP_EOL.PHP_EOL.date('Y-m-d H:i:s').' - '. $ip . ' - '. $uid.PHP_EOL, FILE_APPEND);
            exit(json_encode([
                'code' => 0,
                'msg' => $config['not_type_html'] ? dr_clearhtml($config['not_type_html']) : '提交过于频繁，已列入恶意黑名单',
            ]));
        }


        $post = ($_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST);
        if (isset($post['password']) && $post['password']) {
            $post['password'] = '****';
        }
        if (isset($post['data']['password']) && $post['data']['password']) {
            $post['data']['password'] = '****';
        }

        !$config['max'] && $config['max'] = 500000;
        if ($config['max']) {
            $rs = $this->table('app_web_log')->counts();
            if ($rs > $config['max']) {
                $this->query('delete from `'.$this->dbprefix('app_web_log').'` order by id asc limit 1000');
            }
        }

        $data = [
            'domain' => strtolower($_SERVER['HTTP_HOST']),
            'uid' => $uid,
            'url' => $url,
            'method' => $_SERVER['REQUEST_METHOD'],
            'param' => dr_array2string($post),
            'httpinfo' => dr_array2string($_SERVER),
            'result' => '',
            'useragent' => $useragent,
            'mobile' => dr_is_mobile() ? 1 : 0,
            'inputip' => $ip,
            'inputtime' => SYS_TIME,
        ];

        $rt = $this->table('app_web_log')->insert($data);
        if ($rt['code']) {
            define('APP_WEBLOG_ID', $rt['code']);
        }
        /*
        if ($rt['code'] > 500000) {
            // 超过50w分表存储
            $now = \Phpcmf\Service::M()->dbprefix('app_web_log');
            $new = $now.'_'.dr_date(SYS_TIME, 'Y_m_d');
            \Phpcmf\Service::M()->query_all('create table `'.$new.'` select * from `'.$now.'`');
            \Phpcmf\Service::M()->query_all('TRUNCATE `'.$now.'`');
        }*/
    }

    // 结束时运行
    public function end($rt) {
        if (defined('APP_WEBLOG_ID') && APP_WEBLOG_ID) {
            $this->table('app_web_log')->update(APP_WEBLOG_ID, [
                'result' => dr_array2string($rt)
            ]);
        }
    }


    // 获取cookie
    private function _get_cookie($name) {
        $name = md5(SYS_KEY).'_'.dr_safe_replace($name);
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : false;
    }

    // 获取ip
    private function _ip_address() {

        if (getenv('HTTP_CLIENT_IP')) {
            $client_ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR')) {
            $client_ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR', true)) {
            $client_ip = getenv('REMOTE_ADDR', true);
        } else {
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }

        // 验证规范
        if (!preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $client_ip)) {
            $client_ip = '';
        }

        $client_ip = str_replace([",", '(', ')', ',', chr(13), PHP_EOL], '', $client_ip);
        return trim($client_ip);
    }
}