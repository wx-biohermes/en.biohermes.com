<?php


/**
 * 统一返回json格式并退出程序
 */
function _json($code, $msg, $data = []){
    echo dr_array2string(dr_return_data($code, $msg, $data));exit;
}

/**
 * 数据返回统一格式
 */
function dr_return_data($code, $msg = '', $data = []) {
    return array(
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
    );
}


/**
 * 将数组转换为字符串
 *
 * @param	array	$data	数组
 * @return	string
 */
function dr_array2string($data) {
    return $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : '';
}

/**
 * 获取cms域名部分
 */
function dr_cms_domain_name($url) {

    $param = parse_url($url);
    if (isset($param['host']) && $param['host']) {
        return $param['host'];
    }

    return $url;
}

/**
 * 目录扫描
 *
 * @param	string	$source_dir		Path to source
 * @param	int	$directory_depth	Depth of directories to traverse
 *						(0 = fully recursive, 1 = current dir, etc)
 * @param	bool	$hidden			Whether to show hidden files
 * @return	array
 */
function dr_file_map($source_dir) {

    if ($fp = @opendir($source_dir)) {

        $filedata = [];
        $source_dir	= rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        while (FALSE !== ($file = readdir($fp))) {
            if ($file === '.' OR $file === '..'
                OR $file[0] === '.'
                OR !@is_file($source_dir.$file)) {
                continue;
            }
            $filedata[] = $file;
        }
        closedir($fp);
        return $filedata;
    }

    return FALSE;
}


// zip解压
function dr_unzip($zipfile, $path = '') {

    if (!class_exists('ZipArchive')) {
        exit('ZipArchive不支持');
    }

    !$path && $path = dirname($zipfile); // 当前目录

    $zip = new \ZipArchive;//新建一个ZipArchive的对象
    /*
    通过ZipArchive的对象处理zip文件
    $zip->open这个方法的参数表示处理的zip文件名。
    如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
    */
    if ($zip->open($zipfile) === TRUE) {
        $zip->extractTo($path);//假设解压缩到在当前路径下images文件夹的子文件夹php
        $zip->close();//关闭处理的zip文件
        return 1;
    }

    exit('解压失败');
}

// 远程下载资源
function dr_catcher_data($url, $timeout = 20) {

    if (!$url) {
        return '';
    }


    $ch = curl_init($url);
    if (substr($url, 0, 8) == "https://") {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true); // 从证书中检查SSL加密算法是否存在
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:40.0)' . 'Gecko/20100101 Firefox/40.0',
        'Accept: */*',
        'X-Requested-With: XMLHttpRequest',
        'Referer: '.$url,
        'Accept-Language: pt-BR,en-US;q=0.7,en;q=0.3',
    ));
    curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    ///
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1 );
    // 最大执行时间
    $timeout && curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    if ($errno) {
        exit('获取远程数据失败['.$url.']：（'.$errno.'）'.curl_error($ch));
    }
    curl_close($ch);
    if ($code == 200) {
        return $data;
    } else {
        if (CI_DEBUG && $code) {
            exit('获取远程数据失败['.$url.']http状态：'.$code);
        }
    }

}

function dr_now_url(){

    $pageURL = 'http';
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' && $pageURL.= 's';

    $pageURL.= '://';
    if (strpos($_SERVER['HTTP_HOST'], ':') !== FALSE) {
        $url = explode(':', $_SERVER['HTTP_HOST']);
        $url[0] ? $pageURL.= $_SERVER['HTTP_HOST'] : $pageURL.= $url[0];
    } else {
        $pageURL.= $_SERVER['HTTP_HOST'];
    }

    $pageURL.= $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];

    return $pageURL;
}