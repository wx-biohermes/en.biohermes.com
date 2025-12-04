<?php

/**
 * 菜单配置
 */


return [

    'admin' => [

        'app' => [

            'left' => [
                'app-tag' => [
                    'name' => '关键词库',
                    'icon' => 'fa fa-tags',
                    'link' => [
                        [
                            'name' => '词库管理',
                            'icon' => 'fa fa-tags',
                            'uri' => 'tag/home/index',
                        ],
                        [
                            'name' => '插件设置',
                            'icon' => 'fa fa-cog',
                            'uri' => 'tag/config/index',
                        ],
                    ]
                ],
            ],

            



        ],

    ],
];