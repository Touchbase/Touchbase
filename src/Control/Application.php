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
		parent::init();
		
		$reflector = new \ReflectionClass($this);
		$this->_applicationNamespace = $reflector->getNamespaceName();
		
		return $this;
	}
	
	public function handleRequest(HTTPRequest &$request, HTTPResponse &$response){	
		//Set Request/Response Into Var
		$this->request = &$request;
		
		if(!$this->baseInitCalled){
			user_error("init() method on class '$this' doesn't call Application::init(). Make sure that you have parent::init() included.", E_USER_WARNING);
		}
					
		if($application = $this->handleApplication()){
			
			$application ->setConfig($this->config())
						 ->init()
						 ->handleRequest($this->request, $response);
			
		} else {
									
			if($controller = $this->handleController()){
				$applicationNamespace = ltrim(strstr($this->_applicationNamespace, $needle = "\\") ?: $this->_applicationNamespace, $needle);
				define("APPLICATION_PATH", PROJECT_PATH.str_replace("\\", DIRECTORY_SEPARATOR, $applicationNamespace).DIRECTORY_SEPARATOR);
					
				$assetConfig = $this->config()->get("assets");
				$assetPath = $assetConfig->get("assets","assets/");
				$assetImageDir = $assetConfig->get("images","images/");
				$assetJavascriptDir = $assetConfig->get("js","js/");
				$assetStyleheetDir = $assetConfig->get("css","css/");
				$assetTemplatesDir = $assetConfig->get("templates","Templates/");
				
				define("BASE_ASSETS", Router::buildUrlPath(SITE_ROOT, $assetPath));
				define("BASE_IMAGES", Router::buildUrlPath(SITE_ROOT, $assetPath, $assetImageDir));
				define("BASE_STYLES", Router::buildUrlPath(SITE_ROOT, $assetPath, $assetStyleheetDir));
				define("BASE_SCRIPTS", Router::buildUrlPath(SITE_ROOT, $assetPath, $assetJavascriptDir));
				if(!defined("BASE_TEMPLATES")) define("BASE_TEMPLATES", Folder::buildFolderPath(PROJECT_PATH, $assetTemplatesDir));
						
				$assetPath = substr(md5(str_replace("\\", "/", $this->_applicationNamespace)."/".$assetPath), 0, 6)."/";
				define("APPLICATION_ASSETS", Router::buildUrlPath(SITE_ROOT, $assetPath));
				define("APPLICATION_IMAGES", Router::buildUrlPath(SITE_ROOT, $assetPath, $assetImageDir));
				define("APPLICATION_STYLES", Router::buildUrlPath(SITE_ROOT, $assetPath, $assetStyleheetDir));
				define("APPLICATION_SCRIPTS", Router::buildUrlPath(SITE_ROOT, $assetPath, $assetJavascriptDir));
				define("APPLICATION_TEMPLATES", Folder::buildFolderPath(APPLICATION_PATH, $assetTemplatesDir));
				
				$controller	->setConfig($this->config())
							->handleRequest($this->request, $response);
			}
		}
	}
	
	/**
	 *	Handle Application
	 *	Determines the application that should be loaded if available.
	 *	@return \Touchbase\Control\Application
	 */
	protected function handleApplication(){
		$application = NULL;
		
		//Applications - NAMESPACE\Applications\APP
		$firstUrlSegment = ucfirst(strtolower($this->request->urlSegment(0)));
		$applicationClass = $this->_applicationNamespace.'\Applications\\'.$firstUrlSegment.'\\'.$firstUrlSegment."App";
		if($firstUrlSegment && class_exists($applicationClass) && is_subclass_of($applicationClass, '\Touchbase\Control\Application')){
			$this->request->shift(1); //TODO: Hate this function.
			$application = $applicationClass::create();
		} else {
			$application = $this->defaultApplication();
		}
		
		return $application;
	}
	
	/**
	 *	Handle Controller
	 *	Determines the controller that should be loaded
	 *	@retunrn \Touchbase\Control\Controller
	 */
	protected function handleController(){
		$controller = NULL;
		
		//Controllers - APPLICATION\Controllers\PathTo\Controller
		$shiftCount = 0;
		$urlSegments = $this->request->urlSegments();
		
		//Remove Application Name From FirstSegment 
		// - This fixes an issue whereby you can't access any controllers when one exists with the same name as the application
		if(!empty($urlSegments)){
			$applicationName = ltrim(strrchr($this->_applicationNamespace, $needle = "\\") ?: $this->_applicationNamespace, $needle);
			if(strcasecmp($applicationName, $urlSegments[0]) === 0){
				unset($urlSegments[0]);
			}
		}
		
		do {
			$shiftCount = count($urlSegments);
			if($shiftCount > 0){
				if($controller = $this->getApplicationController(implode('\\', array_map(function($item){
					return ucfirst(strtolower($item));
				}, $urlSegments)))){
					$this->request->shift($shiftCount); //TODO: Hate this function.
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
		
		return $controller;
	}
	
	/**
	 *	Get Application Controller
	 *	This function will attempt to load a controller with the same name as the application.
	 *	@return \Touchbase\Control\Controller
	 */
	private function getApplicationController($controllerName){
		$controllerClass = $this->_applicationNamespace.'\Controllers\\'.$controllerName."Controller";
		if(class_exists($controllerClass) && is_subclass_of($controllerClass, '\Touchbase\Control\Controller')){
			return $controllerClass::create();
		}
	}
	
	/**
	 *	Default Application
	 *	If no application is defined in the URL - This application will load
	 *	NB. this should be overridden in the parent class
	 *	@return \Touchbase\Control\Application 
	 */
	public function defaultApplication(){
		return NULL;
	}
	
	/**
	 *	Default Controller
	 *	If no controller is defined in the URL - This application will load
	 *	NB. this should be overridden in the parent class
	 *	@return \Touchbase\Control\Controller 
	 */
	public function defaultController(){
		return NULL;
	}

}