<?php

\Phpcmf\Hooks::on('module_content_after', function($data, $old) {
    // 内容发布或者修改之后
    $config = \Phpcmf\Service::M('tag', 'tag')->get_config();
    if ($data[1]['status'] == 9 && $config['auto_save']) {
        // 9表示审核通过的
        // 自动存储tag
        \Phpcmf\Service::M('tag', 'tag')->auto_save_tag($data);
    }
});

\Phpcmf\Hooks::on('cms_get_keywords', function($kw, $siteid) {
    // 系统获取关键词时的截取
    $obj = \Phpcmf\Service::M('tag', 'tag');
    if (method_exists($obj, 'get_keywords')) {
        $rt = $obj->get_keywords($kw);
        if ($rt) {
            return dr_return_data(1, implode(',', $rt));
        }
    }

    return false; // 表示可以执行下面的钩子
});