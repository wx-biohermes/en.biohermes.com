<?php namespace Phpcmf\Controllers\Admin;

class Info extends \Phpcmf\App
{

    public function index() {

        $tid = intval($_GET['tid']);

        $date_to = dr_safe_replace($_GET['date_to']);
        $date_form = dr_safe_replace($_GET['date_form']);

        if ($date_to && $date_form) {
            $tid = 99;
            $date_form = strtotime($date_form.' 00:00:00');
            $date_to = strtotime($date_to.' 23:59:59');
        } else {
            switch ($tid) {

                case 3:
                    // 30天
                    $date_form=strtotime('-30 day');
                    $date_to=SYS_TIME;
                    break;

                case 2:
                    // 7天
                    $date_form=strtotime('-7 day');
                    $date_to=SYS_TIME;
                    break;

                case 1:
                    // 昨天的
                    $date_form=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
                    $date_to=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
                    break;

                default:
                    // 今天的
                    $date_form = mktime(0,0,0,date('m'),date('d'),date('Y'));
                    $date_to = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
                    break;
            }
        }

        $select = [
            0 => '今天',
            1 => '昨天',
            2 => '最近7天',
            3 => '最近30天',
        ];

        $where = 'inputtime BETWEEN '.$date_form.' AND '.$date_to;

        // 流量分析

        $s1 = \Phpcmf\Service::M()->table('app_web_log')->where($where.' AND mobile=0')->counts();
        $s2 = \Phpcmf\Service::M()->table('app_web_log')->where($where.' AND mobile=1')->counts();
        $pv = [
            'title' => [
                'text' => '总计：'.($s1 + $s2),
                'left' => 'center',
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{b} : {c} ({d}%)',
            ],
            'legend' => [
                'orient' => 'vertical',
                'left' => 'left',
                'data' => ['PC端', '移动端'],
            ],
            'series' => [
                'type' => 'pie',
                'radius' => '55%',
                'center' => ['50%', '60%'],
                'data' => [
                    [
                        'value' => $s1,
                        'name' => 'PC端',
                    ],
                    [
                        'value' => $s2,
                        'name' => '移动端',
                    ],
                ],
                'emphasis' => [
                    'itemStyle' => [
                        'shadowBlur' => 10,
                        'shadowOffsetX' => 0,
                        'shadowColor' => 'rgba(0, 0, 0, 0.5)',
                    ]
                ]
            ],
        ];



        $s1 = dr_count(\Phpcmf\Service::M()->table('app_web_log')->select('useragent')->where($where.' AND mobile=0')->group_by('useragent')->getAll());
        $s2 = dr_count(\Phpcmf\Service::M()->table('app_web_log')->select('useragent')->where($where.' AND mobile=1')->group_by('useragent')->getAll());
        $uv = [
            'title' => [
                'text' => '总计：'.($s1 + $s2),
                'left' => 'center',
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{b} : {c} ({d}%)',
            ],
            'legend' => [
                'orient' => 'vertical',
                'left' => 'left',
                'data' => ['PC端', '移动端'],
            ],
            'series' => [
                'type' => 'pie',
                'radius' => '55%',
                'center' => ['50%', '60%'],
                'data' => [
                    [
                        'value' => $s1,
                        'name' => 'PC端',
                    ],
                    [
                        'value' => $s2,
                        'name' => '移动端',
                    ],
                ],
                'emphasis' => [
                    'itemStyle' => [
                        'shadowBlur' => 10,
                        'shadowOffsetX' => 0,
                        'shadowColor' => 'rgba(0, 0, 0, 0.5)',
                    ]
                ]
            ],
        ];



        $s1 = dr_count(\Phpcmf\Service::M()->table('app_web_log')->select('inputip')->where($where.' AND mobile=0')->group_by('inputip')->getAll());
        $s2 = dr_count(\Phpcmf\Service::M()->table('app_web_log')->select('inputip')->where($where.' AND mobile=1')->group_by('inputip')->getAll());
        $ip = [
            'title' => [
                'text' => '总计：'.($s1 + $s2),
                'left' => 'center',
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{b} : {c} ({d}%)',
            ],
            'legend' => [
                'orient' => 'vertical',
                'left' => 'left',
                'data' => ['PC端', '移动端'],
            ],
            'series' => [
                'type' => 'pie',
                'radius' => '55%',
                'center' => ['50%', '60%'],
                'data' => [
                    [
                        'value' => $s1,
                        'name' => 'PC端',
                    ],
                    [
                        'value' => $s2,
                        'name' => '移动端',
                    ],
                ],
                'emphasis' => [
                    'itemStyle' => [
                        'shadowBlur' => 10,
                        'shadowOffsetX' => 0,
                        'shadowColor' => 'rgba(0, 0, 0, 0.5)',
                    ]
                ]
            ],
        ];



        $ip_top = \Phpcmf\Service::M()->table('app_web_log')->select('inputip as name, count(inputip) as ct')->where($where)->group_by('inputip')->order_by('ct desc')->getAll(10);
        $user_top = \Phpcmf\Service::M()->table('app_web_log')->select('uid, count(uid) as ct')->where($where.' AND uid>0 ')->group_by('uid')->order_by('ct desc')->getAll(10);
        $useragent_top = \Phpcmf\Service::M()->table('app_web_log')->select('useragent as name, count(useragent) as ct')->where($where)->group_by('useragent')->order_by('ct desc')->getAll(10);


        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '统计概况' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-industry'],
                ]
            ),
            'pv' => $pv,
            'uv' => $uv,
            'ip' => $ip,
            'select' => $select,
            'tid' => $tid,
            'ip_top' => $ip_top,
            'user_top' => $user_top,
            'useragent_top' => $useragent_top,
            'date_to' => dr_date($date_to, 'Y-m-d'),
            'date_form' => dr_date($date_form, 'Y-m-d'),
            'uriprefix' => APP_DIR.'/home',
        ]);
        \Phpcmf\Service::V()->display('info_index.html');
    }

}
