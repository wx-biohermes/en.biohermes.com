<?php
// 自定义字段的支持函数


// 初始化信息
function myfield_init_form($relatedname, $relatedid) {

    // 网站表单 form-站点id, 表单id
    list($a, $siteid) = explode('-', $relatedname);
    $data = \Phpcmf\Service::M()->init(['db' => $siteid, 'table' => $siteid.'_form'])->get($relatedid);
    if (!$data) {
        return dr_return_data(0, dr_lang('表单【%s】不存在', $relatedid));
    }

    return dr_return_data(1, 'ok', [
        'data' => $data,
        'name' => '表单【'.$data['name'].'】字段',
        'backurl' => '', // 返回uri地址
    ]);
}
function myfield_tablename_form($field, $siteid, $relatedname, $relatedid) {

    // 网站表单 form-站点id, 表单id
    list($a, $siteid) = explode('-', $relatedname);
    $data = \Phpcmf\Service::M()->table($siteid.'_form')->get($relatedid);
    if (!$data) {
        return;
    }

    return $field['ismain'] ? $siteid.'_form_'.$data['table'] : $siteid.'_form_'.$data['table'].'_data_{tableid}';
}


// 网站表单字段
function myfield_sql_form($sql, $ismain) {
    $table = \Phpcmf\Service::M()->dbprefix(SITE_ID.'_form_'.\Phpcmf\Service::M('field')->data['table']); // 主表名称
    if (!\Phpcmf\Service::M()->db->tableExists($table)) {
        return;
    }
    if ($ismain) {
        // 更新主表 格式: 站点id_名称
        \Phpcmf\Service::M()->db->simpleQuery(str_replace('{tablename}', $table, $sql));
        \Phpcmf\Service::M('field')->_table_field[] = $table;
    } else {
        for ($i = 0; $i < 200; $i ++) {
            if (!\Phpcmf\Service::M()->db->query("SHOW TABLES LIKE '".$table.'_data_'.$i."'")->getRowArray()) {
                break;
            }
            \Phpcmf\Service::M()->db->simpleQuery(str_replace('{tablename}', $table.'_data_'.$i, $sql)); //执行更新语句
            \Phpcmf\Service::M('field')->_table_field[] = $table.'_data_'.$i;
        }
    }
}
// 字段是否存在
function myfield_field_form($name) {
    // 主表
    $table = \Phpcmf\Service::M()->dbprefix(SITE_ID.'_form_'.\Phpcmf\Service::M('field')->data['table']);
    $rt = \Phpcmf\Service::M('field')->_field_exitsts('id', $name, $table, SITE_ID);
    if ($rt) {
        return 1;
    }
    // 附表
    $rt = \Phpcmf\Service::M('field')->_field_exitsts('id', $name, $table.'_data_0', SITE_ID);
    if ($rt) {
        return 1;
    }
    return 0;
}
// 更新缓存
function myfield_cache_form() {
    // 网站表单 form-站点id, 表单id
    \Phpcmf\Service::M('cache')->sync_cache('form', 'form', 1); // 自动更新缓存
}