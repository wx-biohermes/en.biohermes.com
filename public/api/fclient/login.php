<?php

$file = ROOTPATH.'api/fclient/sync.php';
if (!is_file($file)) {
    $this->_admin_msg(0, '子站客户端程序'.(IS_DEV ? $file : '').'未安装');
}

$sync = \Phpcmf\Service::R($file) ;
if (!$_GET['id'] || !$_GET['sync']) {
    $this->_admin_msg(0, '通信密钥验证为空');
} elseif ($_GET['id'] != md5($sync['id'])) {
    $this->_admin_msg(0, '通信ID验证失败');
} elseif ($_GET['sync'] != $sync['sn']) {
    $this->_admin_msg(0, '通信密钥验证失败');
}

$rid = intval($_GET['rid']);
!$rid && $rid = 1;
$prefix = \Phpcmf\Service::M()->dbprefix('');
$member = \Phpcmf\Service::M()->db->query('select * from '.$prefix.'member where id in(select uid from '.$prefix.'admin_role_index where roleid = '.$rid.') order by id asc limit 1')->getRowArray();
if (!$member) {
    $this->_admin_msg(0, '没有找到本站管理员账号', SELF);
}

\Phpcmf\Service::M('auth')->login_session($member);

$this->_admin_msg(1, '授权登录成功', SELF, 0);