<?php namespace Phpcmf\Controllers;

class Home extends \Phpcmf\Common {

    public function index() {
        \Phpcmf\Service::M('log',  'weblog')->html(\Phpcmf\Service::L('input')->get('url'));
    }

}