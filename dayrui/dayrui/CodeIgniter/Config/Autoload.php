<?php namespace Config;
/**
 * https://www.junke158.cn
 * 君科云CMS
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
	public $psr4 = [];

	public $classmap = [];
	
	public $files = [];
    
    public $helpers = [];

	//--------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();
		$this->psr4 = array_merge($this->psr4, [

            'App'                           => COREPATH,
            'Config'                        => FRAMEPATH.'Config',

        ]);
	}
}
