<?php

/**
 * 获取已上传的文件列表
 */
include "Uploader.class.php";

/* 判断类型 */
switch ($_GET['action']) {
    /* 列出文件 */
    case 'listvideo':
        $allowFiles = $CONFIG['videoAllowFiles'];
        $listSize = $CONFIG['fileManagerListSize'];
        $path = $CONFIG['fileManagerListPath'];
        break;
    /* 列出文件 */
    case 'listfile':
        $allowFiles = $CONFIG['fileManagerAllowFiles'];
        $listSize = $CONFIG['fileManagerListSize'];
        $path = $CONFIG['fileManagerListPath'];
        break;
    /* 列出图片 */
    case 'listimage':
    default:
        $allowFiles = $CONFIG['imageManagerAllowFiles'];
        $listSize = $CONFIG['imageManagerListSize'];
        $path = $CONFIG['imageManagerListPath'];
}
$allowFiles = explode('.', join("", $allowFiles));
if (!$allowFiles[0]) {
    unset($allowFiles[0]);
}

/* 获取参数 */
$size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
$start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
$end = $start + $size;

/* 获取我的列表 */
$db = \Phpcmf\Service::M()->db->table('attachment_data');
if ($this->member) {
    if ($this->member['is_admin']) {
        // 管理员显示全部
    } else {
        $db->where('uid', (int)$this->member['id']);
    }
} else {
    $db->where('uid', 0);
}
$db->whereIn('fileext', $allowFiles)->orderBy('inputtime desc')->limit($size,$start);
$data = $db->get()->getResultArray();
$files = [];
if ($data) {
    foreach ($data as $t) {
        if (isset($CONFIG['showFileExt']) && $CONFIG['showFileExt']) {
            $title = $t['filename'];
        } else {
            $title = strstr($t['filename'], '.', true);
        }

        $name = '';
        if (isset($this->config['imageAltValue']) && $this->config['imageAltValue'] == 'name') {
            $name = $title;
        }

        $files[] = array(
            'url'=> dr_get_file_url($t),
            'name'=> $title,
            'original'=> !$name ? UEDITOR_IMG_TITLE : $name,
            'mtime'=> $t['inputtime']
        );
    }
}

if (!count($files)) {
    return json_encode(array(
        "state" => "no match file",
        "list" => array(),
        "start" => $start,
        "total" => 0
    ), JSON_UNESCAPED_UNICODE);
}

$db = \Phpcmf\Service::M()->table('attachment_data');
if ($this->member) {
    if ($this->member['is_admin']) {
        // 管理员显示全部
    } else {
        $db->where('uid', (int)$this->member['id']);
    }
} else {
    $db->where('uid', 0);
}
$db->where_in('fileext', $allowFiles);
$total = $db->counts();

/* 返回数据 */
$result = json_encode(array(
    "state" => "SUCCESS",
    "list" => $files,
    "start" => $start,
    "total" => $total
), JSON_UNESCAPED_UNICODE);

return $result;