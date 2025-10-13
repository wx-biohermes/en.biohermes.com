<?php

// 自动加载识别文件

return [

    /**
     * 命名空间映射关系
     */
    'psr4' => [


    ],

    /**
     * 类名映射关系
     */
    'classmap' => [

        'Phpcmf\Admin\Form'        => dr_get_app_dir('form').'Control/Admin/Form.php',
        'Phpcmf\Member\Form'         => dr_get_app_dir('form').'Control/Member/Form.php',
        'Phpcmf\Home\Form'         => dr_get_app_dir('form').'Control/Home/Form.php',

    ],


];