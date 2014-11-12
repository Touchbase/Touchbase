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
 *  @category Control
 *  @date 27/12/2013
 */
 
namespace Touchbase\Control;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Filesystem\Folder;

class Application extends Controller
{
	protected $_applicationNamespace;
	
	/**
	 *	Init
	 *	This method must return self
	 *	@return self
	 */
	public function init(){
		return $this;
	}
	
	public function handleRequest(HTTPRequest &$request, HTTPResponse &$response){	
		//Set Request/Response Into Var
		$this->request = &$request;
		
		$application = $controller = NULL;
		
		$reflector = new \ReflectionClass($this);
		$this->_applicationNamespace = $reflector->getNamespaceName();
		
		\touchbase_run_time($this->_applicationNamespace);
		
		//Applications - NAMESPACE\Applications\APP
		$firstUrlSegment = ucfirst(strtolower($request->urlSegment(0)));
		$applicationClass = $this->_applicationNamespace.'\Applications\\'.$firstUrlSegment.'\\'.$firstUrlSegment."App";
		if($firstUrlSegment && class_exists($applicationClass) && is_subclass_of($applicationClass, '\Touchbase\Control\Application')){
			$request->shift(1); //TODO: Hate this function.
			$application = $applicationClass::create();
		} else {
			$application = $this->defaultApplication();
		}

		if($application){
			
			$application ->setConfig($this->config())
						 ->init()
						 ->handleRequest($request, $response);
						 
			//\pre_r("Application:", $response);
		} else {
			
			//Controllers - APPLICATION\Controllers\PathTo\Controller
			$shiftCount = 0;
			$urlSegments = $request->urlSegments();
			do {
				$shiftCount = count($urlSegments);
				if($shiftCount > 0){
					if($controller = $this->getApplicationController(implode('\\', array_map(function($item){
						return ucfirst(strtolower($item));
					}, $urlSegments)))){
						$request->shift($shiftCount); //TODO: Hate this function.
						break;
					}
				}
			} while(array_pop($urlSegments));
			
			if(!$controller && !$controller = $this->defaultController()){
				//No Default Controller, try AppnameController
				
				//TODO: Whats Quicker?
				//\pre_r(basename(dirname($reflector->getFileName())));
				//\pre_r(basename(str_replace("\\",DIRECTORY_SEPARATOR, $this->_applicationNamespace)));
				$controller = $this->getApplicationController(basename(str_replace("\\", DIRECTORY_SEPARATOR, $this->_applicationNamespace)));
			}
						
			if($controller){
				define("APPLICATION_PATH", PROJECT_PATH.str_replace("\\", DIRECTORY_SEPARATOR, $this->_applicationNamespace).DIRECTORY_SEPARATOR);
					
				$assetConfig = $this->config()->get("assets");
				$assetPath = $assetConfig->get("assets","assets/");
				$assetImageDir = $assetConfig->get("images","images/");
				$assetJavascriptDir = $assetConfig->get("js","js/");
				$assetStyleheetDir = $assetConfig->get("css","css/");
				$assetTemplatesDir = $assetConfig->get("templates","Templates/");
				
				define("BASE_ASSETS", SITE_ROOT.Router::buildUrlPath($assetPath));
				define("BASE_IMAGES", SITE_ROOT.Router::buildUrlPath($assetPath, $assetImageDir));
				define("BASE_STYLES", SITE_ROOT.Router::buildUrlPath($assetPath, $assetStyleheetDir));
				define("BASE_SCRIPTS", SITE_ROOT.Router::buildUrlPath($assetPath, $assetJavascriptDir));
				define("BASE_TEMPLATES", Folder::buildFolderPath(PROJECT_PATH, $this->config()->get("project")->get("namespace", ""), $assetTemplatesDir));
						
				$assetPath = substr(md5(str_replace("\\", "/", $this->_applicationNamespace)."/".$assetPath), 0, 6)."/";
				define("APPLICATION_ASSETS", SITE_ROOT.Router::buildUrlPath($assetPath));
				define("APPLICATION_IMAGES", SITE_ROOT.Router::buildUrlPath($assetPath, $assetImageDir));
				define("APPLICATION_STYLES", SITE_ROOT.Router::buildUrlPath($assetPath, $assetStyleheetDir));
				define("APPLICATION_SCRIPTS", SITE_ROOT.Router::buildUrlPath($assetPath, $assetJavascriptDir));
				define("APPLICATION_TEMPLATES", Folder::buildFolderPath(APPLICATION_PATH, $assetTemplatesDir));
				
				$controller	->setConfig($this->config())
							->handleRequest($request, $response);
			}
			//\pre_r("Controller:",$response);
		}
		
		//return parent::handleRequest($request, $response);
	}
	
	private function getApplicationController($controllerName){
		$controllerClass = $this->_applicationNamespace.'\Controllers\\'.$controllerName."Controller";
		if(class_exists($controllerClass) && is_subclass_of($controllerClass, '\Touchbase\Control\Controller')){
			return $controllerClass::create();
		}
	}
	
	public function defaultApplication(){
		return NULL;
	}
	
	public function defaultController(){
		return NULL;
	}

}