<?php namespace Phpcmf\Controllers;

class Home extends \Phpcmf\App {

    public function index() {

        // 获取tag数据
        $tag = dr_safe_replace(urldecode(\Phpcmf\Service::L('Input')->get('name')));
        if (!$tag) {
            $this->juhe();
        } else {
            $this->tag($tag);
        }
    }

    public function tag($tag) {

        $config = \Phpcmf\Service::M('tag', 'tag')->get_config();

        if (is_numeric($tag)) {
            // 表示id
            $temp = \Phpcmf\Service::M()->table_site('tag')->get($tag);
            if ($temp) {
                $tag = $temp['pcode'];
            }
        }

        $name = 'tag_'.SITE_ID.'-'.$tag;
        list($data, $parent, $related) = \Phpcmf\Service::L('cache')->get_data($name);
        if (!$data) {
            $join = $config['catjoin'] ? $config['catjoin'] : '/';
            $tag = end(explode($join, $tag)); // 转换tag值

            $data = \Phpcmf\Service::M('Tag', 'tag')->tag_row($tag);
            // 格式化显示
            $field = \Phpcmf\Service::L('cache')->get('tag-'.SITE_ID.'-field');
            $field['content'] = [
                'ismain' => 1,
                'name' => dr_lang('描述信息'),
                'fieldname' => 'content',
                'fieldtype' => 'Ueditor',
                'setting' => array(
                    'option' => array(
                        'mode' => 1,
                        'height' => 300,
                        'width' => '100%',
                    ),
                )
            ];
            $data = \Phpcmf\Service::L('Field')->format_value($field, $data);
            $data['url'] = isset($data['myurl']) && $data['myurl'] ? $data['myurl'] : \Phpcmf\Service::M('tag', 'tag')->tag_url($data);
            $parent = $related = [];

            // 合并缓存数据
            $data['tags'] = [ $data['name'] ];

            // 是否存在子tag
            if ($config['child']) {
                $result2 = \Phpcmf\Service::M()->table(SITE_ID.'_tag')->where('pid', $data['id'])->order_by('displayorder DESC,id ASC')->getAll();
                if ($result2) {
                    $parent = [];
                    foreach ($result2 as $t) {
                        $t['url'] = isset($t['myurl']) && $t['myurl'] ? $t['myurl'] : \Phpcmf\Service::M('tag', 'tag')->tag_url($t);
                        $related[] = $t;
                        $data['tags'][] = $t['name'];
                    }
                } elseif ($data['pid']) {
                    // 本身就是子词
                    $parent = \Phpcmf\Service::M()->table(SITE_ID.'_tag')->get($data['pid']);
                    $parent['url'] = isset($parent['myurl']) && $parent['myurl'] ? $parent['myurl'] : \Phpcmf\Service::M('tag', 'tag')->tag_url($parent);
                    $result2 = \Phpcmf\Service::M()->table(SITE_ID.'_tag')->where('pid', $data['pid'])->order_by('displayorder DESC,id ASC')->getAll();
                    foreach ($result2 as $t) {
                        $t['url'] = isset($t['myurl']) && $t['myurl'] ? $t['myurl'] : \Phpcmf\Service::M('tag', 'tag')->tag_url($t);
                        $related[] = $t;
                        $data['tags'][] = $t['name'];
                    }
                }
            }

            $data['tags'] = implode(',', $data['tags']);
            SYS_CACHE && \Phpcmf\Service::L('cache')->set_data($name, [$data, $parent, $related], SYS_CACHE_SHOW * 3600);
        }

        if (!$data || !$data['tags']) {
            $this->goto_404_page(dr_lang('此标签[%s]不存在', $tag));
        }
        //!$data && $data = ['code' => $tag, 'name' => $tag, 'tags' => $tag];

        $data['page'] = max(1, (int)\Phpcmf\Service::L('Input')->get('page'));
        $data['join'] = SITE_SEOJOIN;

        $rep = new \php5replace($data);
        $meta_title = $data['page'] > 1 ? str_replace(array('[', ']'), '', (string)$config['seo_title']) : preg_replace('/\[.+\]/U', '', (string)$config['seo_title']);
        $meta_title = preg_replace_callback('#{([A-Z_]+)}#U', array($rep, 'php55_replace_var'), $meta_title);
        $meta_title = preg_replace_callback('#{([a-z_0-9]+)}#U', array($rep, 'php55_replace_data'), $meta_title);
        $meta_title = htmlspecialchars(dr_clearhtml($meta_title));

        $meta_keywords = preg_replace_callback('#{([A-Z_]+)}#U', array($rep, 'php55_replace_var'), (string)$config['seo_keywords']);
        $meta_keywords = preg_replace_callback('#{([a-z_0-9]+)}#U', array($rep, 'php55_replace_data'), $meta_keywords);
        $meta_keywords = htmlspecialchars(dr_clearhtml($meta_keywords));

        $meta_description = preg_replace_callback('#{([A-Z_]+)}#U', array($rep, 'php55_replace_var'), (string)$config['seo_description']);
        $meta_description = preg_replace_callback('#{([a-z_0-9]+)}#U', array($rep, 'php55_replace_data'), $meta_description);
        $meta_description = htmlspecialchars(dr_strcut(dr_clearhtml($meta_description), 200));

        \Phpcmf\Service::V()->assign(array(
            'tag' => $data,
            'parent' => $parent,
            'related' => $related,
            'urlrule' => \Phpcmf\Service::M('tag', 'tag')->tag_url($data, '[page]'),
            'meta_title' => $meta_title,
            'meta_keywords' => $meta_keywords,
            'meta_description' => $meta_description
        ));
        \Phpcmf\Service::V()->display('tag.html');
    }


    public function juhe() {

        $page = max(1, (int)\Phpcmf\Service::L('Input')->get('page'));
        $config = \Phpcmf\Service::M('tag', 'tag')->get_config();

        $rep = new \php5replace(['page' => $page, 'join'=>SITE_SEOJOIN]);
        $meta_title = $page > 1 ? str_replace(array('[', ']'), '', (string)$config['index_seo_title']) : preg_replace('/\[.+\]/U', '', (string)$config['index_seo_title']);
        $meta_title = preg_replace_callback('#{([A-Z_]+)}#U', array($rep, 'php55_replace_var'), $meta_title);
        $meta_title = preg_replace_callback('#{([a-z_0-9]+)}#U', array($rep, 'php55_replace_data'), $meta_title);
        $meta_title = htmlspecialchars(dr_clearhtml($meta_title));

        $meta_keywords = preg_replace_callback('#{([A-Z_]+)}#U', array($rep, 'php55_replace_var'), (string)$config['index_seo_keywords']);
        $meta_keywords = preg_replace_callback('#{([a-z_0-9]+)}#U', array($rep, 'php55_replace_data'), $meta_keywords);
        $meta_keywords = htmlspecialchars(dr_clearhtml($meta_keywords));

        $meta_description = preg_replace_callback('#{([A-Z_]+)}#U', array($rep, 'php55_replace_var'), (string)$config['index_seo_description']);
        $meta_description = preg_replace_callback('#{([a-z_0-9]+)}#U', array($rep, 'php55_replace_data'), $meta_description);
        $meta_description = htmlspecialchars(dr_strcut(dr_clearhtml($meta_description), 200));

        \Phpcmf\Service::V()->assign(array(
            'urlrule' => \Phpcmf\Service::M('tag', 'tag')->url('[page]'),
            'meta_title' => $meta_title,
            'meta_keywords' => $meta_keywords,
            'meta_description' => $meta_description
        ));
        \Phpcmf\Service::V()->display('index.html');
    }

}
