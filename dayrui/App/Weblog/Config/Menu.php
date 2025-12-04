<?php

/**
 * 菜单配置
 */


return [

    'admin' => [

        'app' => [
            'left' => [



                // 分组菜单
                'app-weblog' => [
                    'name' => '用户访问日志',
                    'icon' => 'fa fa-eye',
                    'link' => [
                        [
                            'name' => '统计概况',
                            'icon' => 'fa fa-industry',
                            'uri' => 'weblog/info/index',
                        ],
                        [
                            'name' => '统计配置',
                            'icon' => 'fa fa-cog',
                            'uri' => 'weblog/config/index',
                        ],
                        [
                            'name' => '统计代码',
                            'icon' => 'fa fa-code',
                            'uri' => 'weblog/code/index',
                        ],
                        [
                            'name' => '访问日志',
                            'icon' => 'fa fa-eye',
                            'uri' => 'weblog/home/index',
                        ],

                    ]
                ],


            ],
        ],

    ],
];