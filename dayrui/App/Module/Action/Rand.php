<?php
// 模块数据

// 通过栏目识别共享模块目录
if ((!$dirname || $dirname == 'share') && $system['catid']) {
    $cat = dr_share_cat_value($system['catid']);
    if ($cat && $cat['mid']) {
        $dirname = $cat['mid'];
        unset($cat);
    }
}

if (!$dirname) {
    $return_data = $this->_return($system['return'], '模块参数module未填写');
    return;
}

$module = \Phpcmf\Service::L('cache')->get('module-'.$system['site'].'-'.$dirname);
if (!$module) {
    $return_data = $this->_return($system['return'], '模块('.$dirname.')未安装');
    return;
}


$tableinfo = \Phpcmf\Service::L('cache')->get('table-'.$system['site']);

// 没有表结构缓存时返回空
if (!$tableinfo) {
    $return_data = $this->_return($system['return'], '表结构缓存不存在');
    return;
}

$table = \Phpcmf\Service::M()->dbprefix(dr_module_table_prefix($module['dirname'], $system['site'])); // 模块主表`
if (!isset($tableinfo[$table])) {
    $return_data = $this->_return($system['return'], '表（'.$table.'）结构缓存不存在');
    return;
}

// 是否操作自定义where
if ($param['where']) {
    $where[] = [
        'adj' => 'SQL',
        'value' => urldecode($param['where'])
    ];
    unset($param['where']);
}

// 栏目筛选
if ($system['catid']) {
    $fwhere = [];
    if (strpos($system['catid'], ',') !== FALSE) {
        $temp = explode(',', $system['catid']);
        if ($temp) {
            $catids = [];
            foreach ($temp as $i) {
                $cat = dr_cat_value($system['site'], $module['mid'], $i);
                $catids = $cat['child'] ? array_merge($catids, $cat['catids']) : array_merge($catids, array($i));
            }
            $catids && $fwhere[] = '`catid` IN ('.implode(',', $catids).')';
        }
        unset($temp);
    } else {

        $cat = dr_cat_value($system['site'], $module['mid'], $system['catid']);
        if ($cat['child']) {
            $catids = explode(',', $cat['childids']);
            $fwhere[] = '`catid` IN ('.$cat['childids'].')';
        } else {
            $fwhere[] = '`catid` = '.(int)$system['catid'];
            $catids = [$system['catid']];
        }
    }

    // 副栏目判断
    if (isset($fields['catids']) && $fields['catids']['fieldtype'] = 'Catids') {
        foreach ($catids as $c) {
            if (version_compare(\Phpcmf\Service::M()->db->getVersion(), '5.7.0') < 0) {
                // 兼容写法
                $fwhere[] = '`catids` LIKE "%\"'.intval($c).'\"%"';
            } else {
                // 高版本写法
                $fwhere[] = "(`catids` <>'' AND JSON_CONTAINS (`catids`->'$[*]', '\"".intval($c)."\"', '$'))";
            }
        }
    }
    $fwhere && $where[] = [
        'adj' => 'SQL',
        'value' => urldecode(count($fwhere) == 1 ? $fwhere[0] : '('.implode(' OR ', $fwhere).')')
    ];
    unset($fwhere);
    unset($catids);
}

$sql_where = $this->_get_where($where); // sql的where子句

// 统计标签
if ($this->_return_sql) {
    $sql = "";
} else {
    if ($system['num']) {
        $pages = '';
        $sql_limit = "LIMIT {$system['num']}";
    }

    $system['order'] = $this->_set_orders_field_prefix($system['order'], $_order); // 给排序字段加上表前缀

    $sql = "SELECT t1.* FROM `".$table."` AS t1 JOIN
(
  SELECT ROUND(RAND() * ((SELECT MAX(id) FROM `".$table."`)-(SELECT MIN(id) FROM `".$table."`))+(SELECT MIN(id) FROM `".$table."`)) AS KeyId2
) AS t2
WHERE t1.id >= t2.KeyId2 ". ($sql_where ? " and  $sql_where" : "")."
ORDER BY t1.id $sql_limit";
}

$data = $this->_query($sql, $system);

// 缓存查询结果
if (is_array($data) && $data) {
    // 模块表的系统字段
    $fields['inputtime'] = ['fieldtype' => 'Date'];
    $fields['updatetime'] = ['fieldtype' => 'Date'];
    // 格式化显示自定义字段内容
    $dfield = \Phpcmf\Service::L('Field')->app($module['dirname']);
    foreach ($data as $i => $t) {
        $t['url'] = dr_url_rel(dr_url_prefix($t['url'], $dirname, $system['site'], $this->_is_mobile));
        $data[$i] = $dfield->format_value($fields, $t, 1);
    }
}

// 存储缓存
$system['cache'] && $this->_save_cache_data($cache_name, [
    'data' => $data,
    'sql' => $sql,
    'total' => $total,
    'pages' => $pages,
    'pagesize' => $pagesize,
    'page_used' => $this->_page_used,
    'page_urlrule' => $this->_page_urlrule,
], $system['cache']);

$return_data = $this->_return($system['return'], $data, $sql, $total, $pages, $pagesize);