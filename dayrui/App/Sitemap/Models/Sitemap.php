<?php namespace Phpcmf\Model\Sitemap;

class Sitemap extends \Phpcmf\Model {

    private $host;
    private $zzconfig;

    // 配置信息
    public function getConfig() {

        $this->host = trim(DOMAIN_NAME.WEB_DIR, '/');

        if ($this->zzconfig) {
            return $this->zzconfig;
        }

        if (is_file(WRITEPATH.'config/sitemap.php')) {
            $this->zzconfig = require WRITEPATH.'config/sitemap.php';
            return $this->zzconfig;
        }

        return [];
    }

    // 配置信息
    public function setConfig($data) {
        \Phpcmf\Service::L('Config')->file(WRITEPATH.'config/sitemap.php', '站长配置文件', 32)->to_require($data);
    }

    // tag地图
    public function tag_txt() {

        $config = $this->getConfig();
        $cache_file = '';
        if (isset($config['autotime']) && $config['autotime']) {
            dr_mkdirs(WRITEPATH.'app/sitemap/');
            $cache_file = WRITEPATH.'app/sitemap/tag_'.$this->host.'.txt';
            $time = filectime($cache_file);
            if (SYS_TIME - $time < 3600*intval($config['autotime'])) {
                // 没有超时，不生成
                if (is_file($cache_file)) {
                    return file_get_contents($cache_file);
                }
                $cache_file = '';
            }
        }

        $limit = intval($config['sitemap_limit']);
        !$limit && $limit = 1000;

        $txt = '';
        $data = $this->table_site('tag')->order_by('id desc')->getAll($limit);
        foreach ($data as $t) {
            $txt.= dr_url_prefix(\Phpcmf\Service::L('router')->tag_url($t)).PHP_EOL;
        }

        if ($cache_file) {
            file_put_contents($cache_file, $txt);
        }

        return $txt;
    }

    // 网站地图
    public function sitemap_xml($page = 1, $mid = '', $catid = 0) {

        $config = $this->getConfig();
        $module = $mid;
        $cache_file = '';
        if (!IS_DEV && isset($config['autotime']) && $config['autotime']) {
            dr_mkdirs(WRITEPATH.'app/sitemap/');
            $cache_file = WRITEPATH.'app/sitemap/xml_'.$module.'_'.$this->host.'_'.$page.'_'.$mid.'_'.$catid.'.txt';
            $time = filectime($cache_file);
            if (SYS_TIME - $time < 3600*intval($config['autotime'])) {
                // 没有超时，不生成
                if (is_file($cache_file)) {
                    return file_get_contents($cache_file);
                }
                $cache_file = '';
            }
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
        $xml.= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;
        if ($config['sitemap']) {
            // 判断站点id
            $site_domain = []; // 全网域名对应的站点id
            if (is_file(WRITEPATH.'config/domain_site.php')) {
                $site_domain = require WRITEPATH.'config/domain_site.php';
            }
            $siteid = max(1, intval($site_domain[$this->host]));
            // 显示数量
            $limit = intval($config['sitemap_limit']);
            $pagesize = intval($config['sitemap_pagesize']);


            if ($module) {
                // 单独模块
                $data = $this->_sitemap_module_data($siteid, [$module => $catid], $page, $pagesize, $limit);
                if ($data) {
                    foreach ($data as $t) {
                        $xml.= $t['xml'].PHP_EOL;
                    }
                }
            } else {
                // 首页
                if ($page == 1 && isset($config['sitemap_index']) && $config['sitemap_index']) {
                    $xml.= '    <url>'.PHP_EOL;
                    $xml.= '        <loc>'.htmlspecialchars(\Phpcmf\Service::IS_MOBILE() ? SITE_MURL : SITE_URL).'</loc>'.PHP_EOL;
                    $xml.= '        <lastmod>'.date('Y-m-d', SYS_TIME).'</lastmod>'.PHP_EOL;
                    $xml.= '        <changefreq>daily</changefreq>'.PHP_EOL;
                    $xml.= '        <priority>'.(isset($config['priority_index']) && $config['priority_index'] ? $config['priority_index'] : '1.0').'</priority>'.PHP_EOL;
                    $xml.= '    </url>'.PHP_EOL;
                }

                // 共享栏目
                if ($page == 1 && isset($config['sitemap_cat']) && $config['sitemap_cat']) {
                    $cat = \Phpcmf\Service::L('cache')->get('module-'.$siteid.'-share', 'category');
                    if (is_file(dr_get_app_dir('module').'Libraries/Category.php')) {
                        $cat = \Phpcmf\Service::L('category', 'module')->get_category('share');
                    }
                    if ($cat) {
                        foreach ($cat as $t) {
                            if ($t['tid'] == 2) {
                                continue;
                            }
                            $xml.= '    <url>'.PHP_EOL;
                            $xml.= '        <loc>'.htmlspecialchars(dr_url_prefix($t['url'], '', $siteid)).'</loc>'.PHP_EOL;
                            $xml.= '        <lastmod>'.date('Y-m-d', SYS_TIME).'</lastmod>'.PHP_EOL;
                            $xml.= '        <changefreq>daily</changefreq>'.PHP_EOL;
                            $xml.= '        <priority>'.(isset($config['priority_category']) && $config['priority_category'] ? $config['priority_category'] : '1.0').'</priority>'.PHP_EOL;
                            $xml.= '    </url>'.PHP_EOL;
                        }
                    }

                    // 独立
                    foreach ($config['sitemap'] as $mid => $t) {
                        $mod = \Phpcmf\Service::L('cache')->get('module-'.$siteid.'-'.$mid);
                        if (!$mod['share']) {
                            if (is_file(dr_get_app_dir('module').'Libraries/Category.php')) {
                                $c = \Phpcmf\Service::L('category', 'module')->get_category($mid, $siteid);
                                if ($c) {
                                    $mod['category'] = $c;
                                }
                            }
                            if ($mod['category']) {
                                foreach ($mod['category'] as $t) {
                                    if (!is_array($t) or $t['tid'] == 2) {
                                        continue;
                                    }
                                    $xml.= (dr_url_prefix($t['url'], '', $siteid)).PHP_EOL;
                                }
                            }
                        }
                        $config['sitemap'][$mid] = 0;
                    }
                }

                // 全站模块
                $data = $this->_sitemap_module_data($siteid, $config['sitemap'], $page, $pagesize, $limit);

                if ($data) {
                    /*
                    usort($data, function($a, $b) {
                        if ($a['time'] == $b['time'])
                            return 0;
                        return ($a['time'] > $b['time']) ? -1 : 1;
                    });*/
                    foreach ($data as $t) {
                        $xml.= $t['xml'].PHP_EOL;
                    }
                }
            }
        }

        $xml.= '</urlset>'.PHP_EOL;

        if ($cache_file) {
            file_put_contents($cache_file, $xml);
        }

        return $xml;
    }

    // 网站地图
    public function sitemap_txt($page = 1, $mid = '', $catid = 0) {


        $config = $this->getConfig();
        $module = $mid;
        $cache_file = '';
        if (!IS_DEV && isset($config['autotime']) && $config['autotime']) {
            dr_mkdirs(WRITEPATH.'app/sitemap/');
            $cache_file = WRITEPATH.'app/sitemap/txt_'.$module.'_'.$this->host.'_'.$page.'_'.$mid.'_'.$catid.'.txt';
            $time = filectime($cache_file);
            if (SYS_TIME - $time < 3600*intval($config['autotime'])) {
                // 没有超时，不生成
                if (is_file($cache_file)) {
                    return file_get_contents($cache_file);
                }
                $cache_file = '';
            }
        }

        /*$xml = '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
        $xml.= '<urlset>'.PHP_EOL;*/
        $xml = '';
        if ($config['sitemap']) {
            // 判断站点id
            $site_domain = []; // 全网域名对应的站点id
            if (is_file(WRITEPATH.'config/domain_site.php')) {
                $site_domain = require WRITEPATH.'config/domain_site.php';
            }
            $siteid = max(1, intval($site_domain[$this->host]));
            // 显示数量
            $limit = intval($config['sitemap_limit']);
            $pagesize = intval($config['sitemap_pagesize']);

            if ($module) {
                // 单独模块
                // 栏目
                if ($page == 1 && isset($config['sitemap_cat']) && $config['sitemap_cat']) {
                    $mod = \Phpcmf\Service::L('cache')->get('module-'.$siteid.'-'.$module);
                    if (!$mod['share']) {
                        if (is_file(dr_get_app_dir('module').'Libraries/Category.php')) {
                            $c = \Phpcmf\Service::L('category', 'module')->get_category($module, $siteid);
                            if ($c) {
                                $mod['category'] = $c;
                            }
                        }
                        if ($mod['category']) {
                            foreach ($mod['category'] as $t) {
                                if (!is_array($t) or $t['tid'] == 2) {
                                    continue;
                                }
                                $xml.= (dr_url_prefix($t['url'], $module, $siteid)).PHP_EOL;
                            }
                        }
                    }
                }
                $data = $this->_sitemap_module_data($siteid, [$module => $catid], $page, $pagesize, $limit);
                if ($data) {
                    foreach ($data as $t) {
                        $xml.= $t['txt'].PHP_EOL;
                    }
                }
            } else {
                // 全站模块
                // 首页
                if ($page == 1 && isset($config['sitemap_index']) && $config['sitemap_index']) {
                    $xml.= (\Phpcmf\Service::IS_MOBILE() ? SITE_MURL : SITE_URL).PHP_EOL;
                }

                // 栏目
                if ($page == 1 && isset($config['sitemap_cat']) && $config['sitemap_cat']) {
                    // 共享
                    $cat = \Phpcmf\Service::L('cache')->get('module-'.$siteid.'-share', 'category');
                    if (is_file(dr_get_app_dir('module').'Libraries/Category.php')) {
                        $cat = \Phpcmf\Service::L('category', 'module')->get_category('share');
                    }
                    if ($cat) {
                        foreach ($cat as $t) {
                            if ($t['tid'] == 2) {
                                continue;
                            }
                            $xml.= (dr_url_prefix($t['url'], '', $siteid)).PHP_EOL;
                        }
                    }
                    // 独立
                    foreach ($config['sitemap'] as $mid => $t) {
                        $mod = \Phpcmf\Service::L('cache')->get('module-'.$siteid.'-'.$mid);
                        if (!$mod['share']) {
                            if (is_file(dr_get_app_dir('module').'Libraries/Category.php')) {
                                $c = \Phpcmf\Service::L('category', 'module')->get_category($mid, $siteid);
                                if ($c) {
                                    $mod['category'] = $c;
                                }
                            }
                            if ($mod['category']) {
                                foreach ($mod['category'] as $t) {
                                    if (!is_array($t) or $t['tid'] == 2) {
                                        continue;
                                    }
                                    $xml.= (dr_url_prefix($t['url'], '', $siteid)).PHP_EOL;
                                }
                            }
                        }
                        $config['sitemap'][$mid] = 0;
                    }
                }

                $data = $this->_sitemap_module_data($siteid, $config['sitemap'], $page, $pagesize, $limit);
                if ($data) {
                    /*
                    usort($data, function($a, $b) {
                        if ($a['time'] == $b['time'])
                            return 0;
                        return ($a['time'] > $b['time']) ? -1 : 1;
                    });*/
                    foreach ($data as $t) {
                        $xml.= $t['txt'].PHP_EOL;
                    }
                }
            }

        }

        //$xml.= '</urlset>'.PHP_EOL;
        if ($cache_file) {
            file_put_contents($cache_file, $xml);
        }

        return $xml;
    }

    // 模块内容生成
    private function _sitemap_module_data($siteid, $mids, $page, $pagesize, $limit) {

        if (!$mids) {
            return [];
        }

        if ($limit) {
            if (!$pagesize) {
                $pagesize = $limit;
            }
            $tpage = ceil($limit / $pagesize); // 总页数
            if ($page > $tpage) {
                return [];
            }
        }

        $config = $this->getConfig();

        $arr = [];
        foreach ($mids as $mid => $catid) {
            $table = $siteid.'_'.$mid;
            if ($catid) {
                $cat = dr_cat_value($mid, $catid);
                if ($cat) {
                    if (isset($cat['is_ctable']) && $cat['is_ctable']) {
                        $pid = explode(',', $cat['pids']);
                        $tid = isset($pid[1]) ? $pid[1] : $cat['id'];
                        $table = $siteid.'_'.$mid.'_c'.$tid;
                    } else {
                        if ($config['where'][$mid]) {
                            $config['where'][$mid].= ' AND ';
                        }
                        $config['where'][$mid].= ' catid in('.$cat['childids'].')';
                    }
                }

            }
            if (!$this->is_table_exists($table)) {
                continue;
            }
            $where = '';
            if ($config['where'][$mid]) {
                $where = 'WHERE '.$config['where'][$mid];
            }

            $arr[] = 'SELECT url,updatetime,\''.$mid.'\' AS mid FROM `'.$this->dbprefix($table).'` '.$where;
        }

        $sql = 'SELECT url,updatetime,mid FROM ('.implode(' UNION ALL ', $arr).') as my order by updatetime desc';

        if ($pagesize) {
            $sql.=' limit '.(($page - 1) * $pagesize).','.$pagesize;
        }

        $data = [];

        $query = $this->db->query($sql);
        if ($query) {
            $rows = $query->getResultArray();
            if ($rows) {
                foreach ($rows as $t) {
                    $url = dr_url_prefix($t['url'], $mid, $siteid);
                    if (strpos($url, $this->host) === false) {
                        if (IS_DEV) {
                            $url.= '#此域名不属于当前域名，关闭开发者模式后将不显示此地址';
                        } else {
                            continue;
                        }
                    }
                    $xml = '';
                    $xml.= '    <url>'.PHP_EOL;
                    $xml.= '        <loc>'.htmlspecialchars($url).'</loc>'.PHP_EOL;
                    $xml.= '        <lastmod>'.date('Y-m-d', $t['updatetime']).'</lastmod>'.PHP_EOL;
                    $xml.= '        <changefreq>daily</changefreq>'.PHP_EOL;
                    $xml.= '        <priority>'.(isset($config['priority_show']) && $config['priority_show'] ? $config['priority_show'] : '1.0').'</priority>'.PHP_EOL;
                    $xml.= '    </url>'.PHP_EOL;

                    $data[] = [
                        'txt' => urldecode($url),
                        'xml' => $xml,
                        'time' => $t['updatetime'],
                    ];
                }
            }
        }

        return $data;
    }

}