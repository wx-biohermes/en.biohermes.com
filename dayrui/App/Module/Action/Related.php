<?php
// 模块的相关文章

if (!$param['tag']) {
    $return_data = $this->_return($system['return'], '没有传入tag参数的内容'); // 没有查询到内容
    return;
}

$sql = [];
$array = explode(',', urldecode($param['tag']));
$tfield = 'keywords';
if (isset($param['tfield']) && $param['tfield']) {
    $tfield = $param['tfield'];
    unset($param['tfield']);
}

if (!$system['num']) {
    $system['num'] = 10;
}
if (strpos($system['module'], ',') || $system['module'] == 'all') {
    $sql = [];
    $array = explode(',', urldecode($param['tag']));
    $tfield = 'keywords';
    if (isset($param['tfield']) && $param['tfield']) {
        $tfield = $param['tfield'];
        unset($param['tfield']);
    }
    foreach ($array as $name) {
        $name && $sql[] = '(`title` LIKE "%'.dr_safe_replace($name).'%" OR `'.$tfield.'` LIKE "%'.dr_safe_replace($name).'%")';
    }
    $sql && $where[] = [
        'adj' => 'SQL',
        'value' => '('.implode(' OR ', $sql).')'
    ];
} else {
    $in = '';
    $is_tag = 0;
    if (dr_is_app('tag')) {
        $config = \Phpcmf\Service::M('tag', 'tag')->get_config();
        if ($config['auto_save']) {
            $is_tag = 1;
        }
    }
    foreach ($array as $name) {
        if ($name) {
            $cfile = WRITEPATH.'tags/index_'.SITE_ID.'/'.$system['module'].'-'.md5($name).'.php';
            if (is_file($cfile)) {
                $in.= ','.trim((string)file_get_contents($cfile), ',');
                //$sql[] = '`'.XR_M()->dbprefix($system['site'].'_{xunruicms_mid}').'`.id in (select cid from `'.XR_M()->dbprefix($system['site'].'_tag_{xunruicms_mid}').'` where tid='.$tid.')';
            } else {
                $name = dr_safe_replace($name);
                if ($is_tag) {
                    // 入库tag
                    $obj = \Phpcmf\Service::M('tag', 'tag');
                    if (method_exists($obj, 'cfile')) {
                        \Phpcmf\Service::M('tag', 'tag')->cfile(SITE_ID, $name);
                        $this->_list_error[] = '关键词'.$name.'没有被索引或者无关联内容';
                    } else {
                        $this->_list_error[] = '索引不支持，请升级tag插件';
                    }
                    $in.= '-1';
                } else {
                    $sql[] = '(`title` LIKE \'%'.$name.'%\' OR `'.$tfield.'` LIKE \'%'.$name.'%\')';
                }

            }

        }
    }

    if ($in) {
        $arr = explode(',', trim($in, ','));
        $in = '0';
        $in = implode(',', dr_arraycut($arr, $system['num']));

        $where['my_related'] = [
            'adj' => 'SQL',
            'value' => ' id in ('.$in.')'
        ];
        //$form_attr = ' INNER JOIN `'.XR_M()->dbprefix($system['site'].'_tag_{xunruicms_mid}').'` on `'.XR_M()->dbprefix($system['site'].'_{xunruicms_mid}').'`.id = `'.XR_M()->dbprefix($system['site'].'_tag_{xunruicms_mid}').'`.cid';
    } elseif ($sql) {
        $where['my_related'] = [
            'adj' => 'SQL',
            'value' => '('.implode(' OR ', $sql).')'
        ];
    }
}


unset($param['tag']);
if (isset($where['tag'])) {
    unset($where['tag']);
}
// 跳转到module方法
if (strpos($system['module'], ',') || $system['module'] == 'all') {
    if (!$system['field']) {
        $system['field'] = 'id,title,url,'.$tfield;
    } elseif (strpos($system['field'], $tfield) === false) {
        $system['field'] = trim($system['field'], ',');
        $system['field'].= ','.$tfield;
    }
    require 'Modules.php';
} else {
    if (isset($where['my_related']) && $where['my_related']) {
        $where['my_related']['value'] = str_replace('{xunruicms_mid}', $system['module'], $where['my_related']['value']);
    }
    require 'Module.php';
}