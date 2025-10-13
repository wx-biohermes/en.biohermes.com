<?php namespace Phpcmf\Controllers\Admin;

class Api extends \Phpcmf\App
{

    public function test_kw() {

        $kw = 'iphone手机出现“白苹果”原因及解决办法，用苹果手机的可以看下';
        $rt = dr_get_keywords($kw);
        if (!$rt) {
            exit('测试失败：无法提取到关键词');
        }
        exit('原文：'.$kw.'<br>测试成功：'.$rt);

    }

}
