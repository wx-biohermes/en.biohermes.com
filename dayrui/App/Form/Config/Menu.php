<?php

/**
 * 菜单配置
 */


return [

    'admin' => [

        'config' => [

            'left' => [
                'config-content' => [
                    'name' => '内容设置',
                    'icon' => 'fa fa-navicon',
                    'link' => [
                        'app-form' => [
                            'name' => '表单系统',
                            'icon' => 'fa fa-table',
                            'uri' => 'form/form/index',
                        ]
                    ],
                ],
            ],


        ],



        'content' => [
            'name' => '内容',
            'icon' => 'fa fa-th-large',
            'displayorder' => '-1',
            'left' => [
                'content-module' => [
                    'name' => '内容管理',
                    'icon' => 'fa fa-th-large',
                    'link' => [
                    ]
                ],
                'content-verify' => [
                    'name' => '内容审核',
                    'icon' => 'fa fa-edit',
                    'link' => [
                    ]
                ],
            ],
        ],



    ],



];