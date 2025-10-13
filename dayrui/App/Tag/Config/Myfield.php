<?php
// 自定义字段的支持函数
// \Phpcmf\Service::M('field')


// 初始化信息
function myfield_init_tag($relatedname, $relatedid) {
    return dr_return_data(1, 'ok', [
        'ismain' => 1,
        'name' => '关键词字段',
        'backurl' => \Phpcmf\Service::L('Router')->url('tag/home/index'), // 返回uri地址
    ]);
}
function myfield_tablename_tag($field, $siteid, $relatedname, $relatedid) {
    return $field['relatedid'].'_tag';
}


// 执行sql
function myfield_sql_tag($sql, $ismain) {
    $table = \Phpcmf\Service::M('field')->dbprefix(\Phpcmf\Service::M('field')->relatedid.'_tag');
    if (!\Phpcmf\Service::M('field')->db->tableExists($table)) {
        return;
    }
    \Phpcmf\Service::M('field')->db->simpleQuery(str_replace('{tablename}', $table, $sql));
    \Phpcmf\Service::M('field')->_table_field[] = $table;
}
// 字段是否存在
function myfield_field_tag($name) {
    // 主表
    $table = \Phpcmf\Service::M('field')->dbprefix(\Phpcmf\Service::M('field')->relatedid.'_tag');
    $rt = \Phpcmf\Service::M('field')->_field_exitsts('id', $name, $table, \Phpcmf\Service::M('field')->relatedid);
    if ($rt) {
        return 1;
    }
    return 0;
}
// 更新缓存
function myfield_cache_tag() {
    // 导航链接
    \Phpcmf\Service::M('cache')->sync_cache('tag', 'tag', 1); // 自动更新缓存
}