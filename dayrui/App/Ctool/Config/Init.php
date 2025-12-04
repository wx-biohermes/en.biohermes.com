<?php
if (!is_file(dr_get_app_dir('module').'Libraries/Category.php')) {
    $this->_admin_msg(0, '建站系统插件需要升级到最新版本');
}