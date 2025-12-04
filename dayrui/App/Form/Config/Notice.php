<?php

/**
 *  通知动作注册配置
 *
 *  动作字符 => 动作名称
 *
 **/

$cfg = [

    'form_verify_1'      => '[所有]表单审核后通知表单作者',
    'form_verify_0'      => '[所有]表单被拒绝后通知表单作者',

];

$fm = \Phpcmf\Service::M()->table_site('form')->getAll();
if ($fm) {
    foreach ($fm as $f) {
        $cfg['form_'.$f['table'].'_verify_1'] = '['.$f['name'].']表单审核后通知表单作者';
        $cfg['form_'.$f['table'].'_verify_0'] = '['.$f['name'].']表单被拒绝后通知表单作者';
    }
}

return $cfg;