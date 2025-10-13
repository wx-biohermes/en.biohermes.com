<?php namespace Phpcmf\Controllers;

class Home extends \Phpcmf\Common
{

    public function index() {
        header('Content-Type: text/plain');
        echo \Phpcmf\Service::M('sitemap', 'sitemap')->sitemap_txt(
            max(1, intval($_GET['p'])),
            dr_safe_filename($_GET['mid']),
            intval($_GET['catid'])
        );exit;
    }

    public function xml() {
        header('Content-Type: text/xml');
        echo \Phpcmf\Service::M('sitemap', 'sitemap')->sitemap_xml(
            max(1, intval($_GET['p'])),
            dr_safe_filename($_GET['mid']),
            intval($_GET['catid'])
        );exit;
    }

    public function tag() {
        header('Content-Type: text/plain');
        echo \Phpcmf\Service::M('sitemap', 'sitemap')->tag_txt();exit;
    }


}
