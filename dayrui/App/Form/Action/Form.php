<?php
$mid = $system['form'];
// 表单参数为数字时按id读取
$form = \Phpcmf\Service::C()->get_cache('form-'.$system['site'], $mid);
// 判断是否存在
if (!$form) {
    return $this->_return($system['return'], "表单($mid)不存在"); // 参数判断
}

// 表结构缓存
$tableinfo = \Phpcmf\Service::L('cache')->get('table-'.$system['site']);
if (!$tableinfo) {
    // 没有表结构缓存时返回空
    return $this->_return($system['return'], '表结构缓存不存在');
}

$table = \Phpcmf\Service::M()->dbprefix(dr_form_table_prefix($form['table'], $system['site'])); // 主表
if (!isset($tableinfo[$table])) {
    return $this->_return($system['return'], '表（'.$table.'）结构缓存不存在');
}

// 默认条件
$where[] = array(
    'adj' => '',
    'name' => 'status',
    'value' => 1
);

// 是否操作自定义where
if ($param['where']) {
    $where[] = [
        'adj' => 'SQL',
        'value' => urldecode($param['where'])
    ];
    unset($param['where']);
}

// 将catid作为普通字段
if (isset($system['catid']) && $system['catid']) {
    $where[] = array(
        'adj' => '',
        'name' => 'catid',
        'value' => $system['catid']
    );
}

$fields = $form['field'];
$system['order'] = !$system['order'] ? 'inputtime_desc' : $system['order']; // 默认排序参数
$where = $this->_set_where_field_prefix($where, $tableinfo[$table], $table, $fields); // 给条件字段加上表前缀
$system['field'] = $this->_set_select_field_prefix($system['field'], $tableinfo[$table], $table); // 给显示字段加上表前缀

// 多表组合排序
$_order = [];
$_order[$table] = $tableinfo[$table];
$sql_from = $table; // sql的from子句
// 关联表
if ($system['join'] && $system['on']) {
    $rt = $this->_join_table($table, $system, $where, $_order, $sql_from);
    if (!$rt['code']) {
        return $this->_return($system['return'], $rt['msg']);
    }
    list($system, $where, $_order, $sql_from) = $rt['data'];
}

$total = 0;
$sql_limit = $pages = '';
$sql_where = $this->_get_where($where); // sql的where子句

// 统计标签
if ($this->_return_sql) {
    $sql = "SELECT _XUNRUICMS_RT_ FROM $sql_from ".($sql_where ? "WHERE $sql_where" : "")." ORDER BY NULL";
} else {
    if ($system['page']) {
        $page = $this->_get_page_id($system['page']);
        $pagesize = (int)$system['pagesize'];
        $pagesize = $pagesize ? $pagesize : 10;
        $sql = "SELECT count(*) as c FROM $sql_from " . ($sql_where ? "WHERE $sql_where" : "") . " ORDER BY NULL";
        $row = $this->_query($sql, $system, FALSE);
        $total = (int)$row['c'];
        // 没有数据时返回空
        if (!$total) {
            return $this->_return($system['return'], '没有查询到内容', $sql, 0);
        }
        $sql_limit = 'LIMIT ' . $pagesize * ($page - 1) . ',' . $pagesize;
        $pages = $this->_get_pagination($system['urlrule'], $pagesize, $total, $system['pagefile']);
    } elseif ($system['num']) {
        $sql_limit = "LIMIT {$system['num']}";
    }
    $system['order'] = $this->_set_orders_field_prefix($system['order'], $_order); // 给排序字段加上表前缀
    $sql = "SELECT " . $this->_get_select_field($system['field'] ? $system['field'] : "*") . " FROM $sql_from " . ($sql_where ? "WHERE $sql_where" : "") . " " . ($system['order'] ? "ORDER BY {$system['order']}" : "") . " $sql_limit";
}

$data = $this->_query($sql, $system);

if (is_array($data) && $data) {
    // 表的系统字段
    $fields['inputtime'] = ['fieldtype' => 'Date'];
    $dfield = \Phpcmf\Service::L('Field')->app('form');
    foreach ($data as $i => $t) {
        $data[$i] = $dfield->format_value($fields, $t, 1);
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
}

return $this->_return($system['return'], $data, $sql, $total, $pages, $pagesize);