<?php

$code = (string)file_get_contents(CMSPATH.'Core/Hooks.php');
if (strpos($code, 'app_on') === false) {
    return dr_return_data(0, 'CMS主程序版本较低，无法安装本插件');
}

return dr_return_data(1, 'ok');