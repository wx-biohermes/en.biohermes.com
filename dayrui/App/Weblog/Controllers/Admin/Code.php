<?php namespace Phpcmf\Controllers\Admin;

class Code extends \Phpcmf\App
{

    public function index() {



        \Phpcmf\Service::V()->assign([
            'code' => '
{if defined(\'SC_HTML_FILE\')}
<script>
$.ajax({
     type: "get",
     url: "{SITE_URL}index.php?s=weblog&url={urlencode($my_web_url)}",
     dataType: "jsonp"
 });
</script>
{/if}
            ',
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '统计代码' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-code'],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('code.html');
    }

}
