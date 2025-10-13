<?php

/**
 * 菜单配置
 */


return [

    'admin' => [

        'content' => [

            'left' => [


                // 集成单个菜单
                'content-module' => [
                    'link' => [
                        'app-ctool' => [
                            'name' => '内容维护工具',
                            'icon' => 'fa fa-wrench',
                            'uri' => 'ctool/module_content/index',
                        ],

                    ]
                ],



            ],



        ],




    ],



];