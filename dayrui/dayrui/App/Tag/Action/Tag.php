<?php

$system['table'] = $system['site'].'_tag';
$tableinfo = \Phpcmf\Service::L('cache')->get_data('table-'.$system['table']);
if (!$tableinfo) {
    $tableinfo = \Phpcmf\Service::M('Table')->get_field($system['table']);
    \Phpcmf\Service::L('cache')->set_data('table-'.$system['table'], $tableinfo, 36000);
}
if (!$tableinfo) {
    return $this->_return($system['return'], '表('.$system['table'].')结构不存在');
}

// 是否操作自定义where
if ($param['where']) {
    $where[] = [
        'adj' => 'SQL',
        'value' => urldecode($param['where'])
    ];
    unset($param['where']);
}

$table = \Phpcmf\Service::M()->dbprefix($system['table']);

if ($param['tag']) {
    $in = $tag = $sql = [];
    $array = explode(',', urldecode($param['tag']));
    foreach ($array as $name) {
        $name && $sql[] = '`name`="'.dr_safe_replace($name).'"';
    }
    $sql && $tag = $this->_query("SELECT code,id FROM {$table} WHERE ".implode(' OR ', $sql), $system);
    if ($tag) {
        $cache = \Phpcmf\Service::C()->get_cache('tag-'.$system['site']); // tag缓存
        foreach ($tag as $t) {
            $in[] = $t['id'];
            if ($cache[$t['code']]['childids']) {
                foreach ($cache[$t['code']]['childids'] as $i) {
                    $in[] = $i;
                }
            }
        }
    }
    if ($in) {
        $where[] = [
            'adj' => 'SQL',
            'value' => 'id IN ('.implode(',', $in).')',
        ];
    } else {
        $where[] = [
            'adj' => 'SQL',
            'value' => ' ('.implode(' OR ', $sql).')',
        ];
    }
    unset($param['tag']);
}
if (isset($where['tag'])) {
    unset($where['tag']);
}
$where = $this->_set_where_field_prefix($where, $tableinfo, $table); // 给条件字段加上表前缀
$system['field'] = $this->_set_select_field_prefix($system['field'], $tableinfo, $table); // 给显示字段加上表前缀
$system['order'] = $this->_set_order_field_prefix($system['order'], $tableinfo, $table); // 给排序字段加上表前缀
!$system['order'] && $system['order'] = 'displayorder asc';

$where = $this->_set_where_field_prefix($where, $tableinfo, $table); // 给条件字段加上表前缀
$sql_where = $this->_get_where($where); // sql的where子句

$sql = "SELECT ".($this->_return_sql ? 'count(*) as ct' : '*')." FROM {$table} ".($sql_where ? "WHERE $sql_where" : "")." ORDER BY ".$system['order']." LIMIT ".($system['num'] ? $system['num'] : 10);
$data = $this->_query($sql, $system);

// 没有查询到内容
if (!$data) {
    return $this->_return($system['return'], '没有查询到内容', $sql);
}

foreach ($data as $i => $t) {
    // 读缓存
    $data[$i]['url'] = '/';
    $url = \Phpcmf\Service::M('tag', 'tag')->get_tag_url($t['name']);
    if ($url) {
        $data[$i]['url'] = $url;
    }
}

// 存储缓存
$system['cache'] && $this->_save_cache_data($cache_name, [
    'data' => $data,
    'sql' => $sql,
    'total' => 0,
    'pages' => 0,
    'pagesize' => 0,
    'page_used' => $this->_page_used,
    'page_urlrule' => $this->_page_urlrule,
], $system['cache']);

return $this->_return($system['return'], $data, $sql);