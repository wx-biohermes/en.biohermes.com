<?php namespace Phpcmf\Controllers;

class Api extends \Phpcmf\App
{

    public function index() {


        // 强制将模板设置为后台
        \Phpcmf\Service::V()->admin();

        $pagesize = (int)\Phpcmf\Service::L('input')->get('pagesize');
        if (!$pagesize) {
            $pagesize = 10;
        }

        $param = $data = \Phpcmf\Service::L('input')->get('', true);

        if (IS_POST) {
            $ids = \Phpcmf\Service::L('input')->get_post_ids();
            if (!$ids) {
                $this->_json(0, dr_lang('没有选择项'));
            }
            $id = [];
            foreach ($ids as $i) {
                $id[] = (int)$i;
            }
            $builder = \Phpcmf\Service::M()->db->table(SITE_ID.'_tag');
            $builder->whereIn('id', $id);
            $mylist = $builder->orderBy('id DESC')->get()->getResultArray();
            if (!$mylist) {
                $this->_json(0, dr_lang('没有相关数据'));
            }

            $ids = [];
            foreach ($mylist as $t) {
                $ids[] = $t['name'];
            }


            $this->_json(1, dr_lang('操作成功'), $ids);
        }


        if ($data['search']) {

            $data['keyword'] = dr_safe_replace(urldecode($data['keyword']));
            if (isset($data['keyword']) && $data['keyword']) {
                $data['keyword'] = dr_safe_replace(urldecode($data['keyword']));
                $where[] = 'name LIKE "%'.$data['keyword'].'%"';
            }
        }

        $rules = $data;
        $rules['page'] = '{page}';

        \Phpcmf\Service::V()->assign([
            'param' => $data,
            'where' => $where ? urlencode(implode(' AND ', $where)) : '',
            'search' => dr_form_search_hidden(['search' => 1, 'is_iframe' => 1,  'pagesize' => $pagesize]),
            'urlrule' => dr_url('tag/api/index', $rules, '/index.php'),
            'pagesize' => $pagesize,
        ]);

        \Phpcmf\Service::V()->display('api_related.html');
    }



}
