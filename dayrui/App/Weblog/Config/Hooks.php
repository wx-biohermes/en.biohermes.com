<?php

\Phpcmf\Hooks::on('init', function() {
    \Phpcmf\Service::M('log',  'weblog')->run();
});

\Phpcmf\Hooks::on('cms_end', function($rt) {
    \Phpcmf\Service::M('log',  'weblog')->end($rt);
});