<?php

/**
 *  Copyright (c) 2013 William George.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 *
 *  @author William George
 *  @package Touchbase
 *  @category Core
 *  @date 22/12/2013
 */
namespace {
	function pre_r(){
		$traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[0];
		$caller = basename($traces['file']).':'.$traces['line'];
		foreach(func_get_args() as $print){
			print "<pre>[$caller] ".htmlentities(print_r($print, true))."</pre>\n";
		}
	}
	
	function touchbase_run_time($debug){
		pre_r("$debug: " . number_format(((microtime(true) - PHP_START)) * 1000, 1) . "ms");
	}
}

namespace Touchbase {;

error_reporting(E_ALL | E_STRICT);

use Touchbase\Filesystem\File;
use Touchbase\Core\Config\Store as ConfigStore;
use Touchbase\Core\Config\ConfigTrait;
use Touchbase\Core\Config\Provider\IniConfigProvider;

class Init
{
	use ConfigTrait;

	protected $_autoLoader;
	
	protected $_request;
	protected $_response;
	
	protected $_router;
	
	public function __construct($autoLoader = null, $basePath = null){
		$this->_autoLoader = $autoLoader;
		
		//To Gain Access To Class Files
		defined('TOUCHBASE') or define('TOUCHBASE', true);
		
		if(!defined('TOUCHBASE_ENV')){
			//TODO: Implement this:
			define("TOUCHBASE_ENV", "production", true);
		}
		
		//Base Working Directory
		if(!defined('BASE_PATH')){
			if(is_null($basePath)){
				define('BASE_PATH', rtrim(realpath(dirname($_SERVER['DOCUMENT_ROOT'])), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
			} else {
				define('BASE_PATH', rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
			}
		}
		
		//Error Logging
		//$error = new \Touchbase\Debug\Error();
		
		//Configure Touchbase Project
		$this->setConfig($this->_configure(ConfigStore::create()));
		
		//Work out touchbase path
		if($this->_autoLoader instanceof \Composer\Autoload\ClassLoader){
			$prefixes = $this->_autoLoader->getPrefixesPsr4();
			foreach($prefixes as $prefix => $path){
				if(rtrim($prefix, "\\") == __NAMESPACE__){
					$nsBasePath = realpath($path[0]) . DIRECTORY_SEPARATOR;
					break;
				}
			}
		}
		if(!defined('TOUCHBASE_PATH')){
			define('TOUCHBASE_PATH', rtrim(realpath($nsBasePath), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
		}
		
		//Base Working Directory
		if(!defined('WORKING_DIR')) {
			// Determine the base URL by comparing SCRIPT_NAME to SCRIPT_FILENAME and getting common elements
			$path = realpath($_SERVER['SCRIPT_FILENAME']);
			
			if(substr($path, 0, strlen(BASE_PATH)) == BASE_PATH) {
				$urlSegmentToRemove = substr($path, strlen(BASE_PATH));
				if(substr($_SERVER['SCRIPT_NAME'], -strlen($urlSegmentToRemove)) == $urlSegmentToRemove) {
					$baseURL = substr($_SERVER['SCRIPT_NAME'], 0, -strlen($urlSegmentToRemove));
				}
			}
			define('WORKING_DIR', rtrim(isset($baseURL)?$baseURL:$this->config()->get("project")->get("working_dir", ""), "/")."/");
		}
		
		if(!defined('SITE_PROTOCOL')){
			$protocol = '';
			if(isset($_SERVER['HTTP_X_FORWARDED_PROTOCOL']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTOCOL']) == 'https'){
				$protocol = 'https://';
			} else if(isset($_SERVER['SSL']) || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')){
				$protocol = 'https://';
			} else {
				$protocol = 'http://';
			}
			
			define('SITE_PROTOCOL', $protocol);
		}
		
		if(!defined('SITE_ROOT')){
			define("SITE_ROOT", rtrim(SITE_PROTOCOL.$_SERVER['HTTP_HOST'].'/'.WORKING_DIR, '/').'/', true);
		}
		
/*
		\pre_r(BASE_PATH);
		\pre_r(TOUCHBASE_PATH);
		\pre_r(PROJECT_PATH);
		\pre_r(SITE_ROOT);
		\pre_r(TOUCHBASE_ENV);
*/
	}
	
	public function request(){
		if(empty($this->_request)){		
			 $this->_router = \Touchbase\Control\Router::create()->setConfig($this->config())
			 													 ->route($this->_request, $this->_response);
		}
		
		return $this->_request;
	}
	
	public function response(){
		if(empty($this->_response)){
			$this->_response = \Touchbase\Control\HTTPResponse::create();
			$this->request();
		}

		return $this->_response;
	}
	
	private function _configure(ConfigStore $config){
		
		$ns = $src = "";
		
		try {
			//Load Main Configuration File
			$configurationData = IniConfigProvider::create()->parseIniFile(File::create(BASE_PATH.'config.ini'));
			$config->addConfig($configurationData->getConfiguration());
			
			$ns  = $config->get("project")->get("namespace", "Project");
			$src = $config->get("project")->get("source", "src");
			
			//Load Extra Configuration Files
			$loadExtraConfig = function($files, $configFilePath = BASE_PATH) use (&$loadExtraConfig, &$config){
				if(!empty($files)){
					foreach($files as $condition => $file){
						
						$extraConfigFile = File::create($configFilePath.$file);
						
						if($extraConfigFile->exists()){
							$configurationData = IniConfigProvider::create()->parseIniFile($extraConfigFile);
							//Not a domain, path or environment - load always
							if(is_numeric($condition)){
								$config->addConfig($extraConfig = $configurationData->getConfiguration());
								$loadExtraConfig($extraConfig->get("config")->get("files", ""), $configFilePath.dirname($file)."/");
							
							//We want to match a certain condition
							} else {
								if( //Do we match the conditions?
									strpos($_SERVER['HTTP_HOST'], $condition) === 0 || //Are we a different domain?
									strpos($_SERVER["REQUEST_URI"], $condition) === 0 || //Are we a different working dir?
									strpos($_SERVER["SERVER_NAME"], $condition) === 0 || //Are we on a different server?
									(strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN' && $condition == "windows") || //Are we on a differnet os?
									TOUCHBASE_ENV == $condition // Are we on a different environment (dev/live)
								){
									$config->addConfig($extraConfig = $configurationData->getConfiguration());
									$loadExtraConfig($extraConfig->get("config")->get("files", ""), $configFilePath.dirname($file)."/");
								}
							}
						}
					}
				}
			};
			$loadExtraConfig($config->get("config")->get("files", ""));			
			
		} catch(\Exception $e){}
		
		if(!defined('PROJECT_PATH')){
			$psr0 = realpath(BASE_PATH . DIRECTORY_SEPARATOR . $src . DIRECTORY_SEPARATOR . $ns);
			$psr4 = realpath(BASE_PATH . DIRECTORY_SEPARATOR . $src);
			define('PROJECT_PATH', ($psr0 ?: $psr4) . DIRECTORY_SEPARATOR);
		}
		
		return $config;
	}
}
}