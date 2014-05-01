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
 *  @date 23/12/2013
 */
 
namespace Touchbase\Control;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Core\Config\ConfigTrait;
use Touchbase\Control\Exception\HTTPResponseException;

class Router extends \Touchbase\Core\Object
{
	use ConfigTrait;

	private $urlRules = array(10 => array(
		'Security//$Action/$ID/$OtherID' => 'Security',
		'$Controller//$Action/$ID/$OtherID' => '*',
	), 20 => array(
		'admin//$action/$ID/$OtherID' => '->admin/security'
	));
	
	private $urlParams = array();
	private $testingServers = array();
	private $developmentServers = array();
	protected $enviromentMode; 
	
	public function __construct($enviromentMode = 'live'){
		$this->enviromentMode = $enviromentMode;
	}
	
	public function addRules($priority, $rules) {
		$this->urlRules[$priority] = isset($this->urlRules[$priority]) ? array_merge($rules, (array)$this->urlRules[$priority]) : $rules;
	}
	
	public function route(/*HTTPRequest*/ &$request, HTTPResponse &$response){
		//GET / POST etc.
		$requestMethod = (isset($_SERVER['X-HTTP-Method-Override'])) ? $_SERVER['X-HTTP-Method-Override'] : $_SERVER['REQUEST_METHOD'];
		
		// IIS will sometimes generate this.
		if(!empty($_SERVER['HTTP_X_ORIGINAL_URL'])) {
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
		}
		
		$url = preg_replace("/^\/".str_replace("/", "\/", WORKING_DIR)."/", '', $_SERVER["REQUEST_URI"]);
		if(strpos($url,'?') !== false) {
			list($url, $query) = explode('?', $url, 2);
			parse_str($query, $_GET);
			if ($_GET) $_REQUEST = array_merge((array)$_REQUEST, (array)$_GET);
		}
		
		// Pass back to the webserver for files that exist
		if(php_sapi_name() == 'cli-server' && file_exists(BASE_PATH.'public_html/'.$url) && is_file(BASE_PATH.'public_html/'.$url)){
			return false;
		}
		
		//Remove base folders from the URL if webroot is hosted in a subfolder
		if(substr(strtolower($url), 0, strlen(BASE_PATH)) == strtolower(BASE_PATH)){
			$url = substr($url, strlen(BASE_PATH));
		}
		
		$request = \Touchbase\Control\HTTPRequest::create($requestMethod, $url, $_GET);
		
		if(!$this->handleRequest($request, $response)){
			//Get Dispatchable
			$dispatchNamespace = $this->config()->get("project")->get("namespace", "");
			$dispatch = $dispatchNamespace.'\\'.$dispatchNamespace.'App';
			if(substr($dispatch, 0, 1) != '\\'){
				$dispatch = '\\' . $dispatch;
			}
			
			if(class_exists($dispatch)){
				$dispatch::create()	->setConfig($this->config())
									->init()
									->handleRequest($request, $response);
				//\pre_r("Final:", $response);
			} else {
				\pre_r("Could Not Load Project");
			}
		}
/*		
	
		//Send Request
		$result = $this->handleRequest($request, $response);
		
		//Handle Redirect
		if(is_string($result) && substr($result,0,9) == 'redirect:') {
			$response = load()->newInstance('Control\HTTPResponse');
			$response->redirect(substr($result, 9));
			$response->output();
		// Handle a controller
		} else if($result){
			if(is_object($result) && $result->is_a("HTTPResponse")){
				$response = $result;
			} else {
				$response = load()->newInstance('Control\HTTPResponse');
				$response->setBody($result);
			}
			$response->output();
		}
		
*/
	}
	
	private function handleRequest(HTTPRequest $request, HTTPResponse &$response){
		//Order Rules to obtain priority 
		krsort($this->urlRules);
		
		//2) Does the real path exist?
		define("SITE_THEME_IMAGES", SITE_ROOT.$this->config()->get("assets")->get("assets","assets/").$this->config()->get("assets")->get("images","images/"));
		
		//3) Have we mapped a path?
		$assetFilePath = $this->config()->get("assets")->get("asset_map")->get($request->urlSegment(), "");
		if($assetFilePath){
			$assetFile = \Touchbase\Filesystem\File::create(PROJECT_PATH.$assetFilePath.implode("/", $request->urlSegments(1)).'.'.$request->extension());
			$supportedAssets = [
				"css" => "text/css; charset=utf-8",
				"js" => "text/javascript; charset=utf-8",
				"png" => "image/png",
				"jpg" => "image/jpg",
				"gif" => "image/gif",
				"svg" => "image/svg+xml",
				"apk" => "application/vnd.android.package-archive",
				"ipa" => "application/octet-stream"
			];
			
			
			$contentType = @$supportedAssets[$request->extension()];
			if(isset($contentType) && $assetFile->exists()){
				$response->addHeader("Content-Type", $contentType);
				$response->addHeader('Content-Length', $assetFile->size()); //TODO: Should be done in response setBody!
				$result = $response->setBody($assetFile->read());
			}
		}
		
		return (isset($result))?$result:false;
		exit;
		
		//1) Does Controller Exist? 
		if($params = $request->match($pattern = "")){
			try {
				$controllerObj = new $params['Controller']();
				
				$result = $controllerObj->setConfig($this->config())
										->handleRequest($request, $response);
			} catch(HTTPResponseException $responseException) {
				$result = $responseException->getResponse();
			}	
		}
				
		foreach($this->urlRules as $priority => $rules) {
			foreach($rules as $pattern => $controller){
				//Does Our Pattern Match The Request??
				if(is_string($controller)) {
					if(substr($controller,0,2) == '->') $controller = array('Redirect' => substr($controller,2));
					else $controller = array('Controller' => $controller, 'Controller_Name' => false);
				} else if(is_array($controller)){
					if(count($controller) == 1 && !in_array(key($controller), array('Controller','Redirect'))){
						//Associative Array -> Filepath => ControllerName
						$controller = array('Controller' => key($controller), 'Controller_Name' => current($controller));
					} else if(count($controller) == 2 && isset($controller[0]) && isset($controller[1])){
						//Non Associative Array -> 0: Filepath, 1: ControllerName
						$controller = array('Controller' => $controller[0], 'Controller_Name' => $controller[1]);
					}
				}
			
				if(($arguments = $request->match($pattern, true)) !== false) {
				// controllerOptions provide some default arguments
					$arguments = array_merge($controller, $arguments);
					//debug()->write($arguments);
					
					// Find the controller
					$controller = (isset($arguments['Controller']))?$arguments['Controller']:false;
					$controllerName = (isset($arguments['Controller_Name']))?$arguments['Controller_Name']:false;
					
					// Pop additional tokens from the tokeniser if necessary
					if(isset($controller['_PopTokeniser'])) {
						$request->shift($controller['_PopTokeniser']);
					}

					// Handle redirections
					if(isset($arguments['Redirect'])) {
						return "redirect:" . $this->absoluteURL($arguments['Redirect'], true);
					} else {
						$this->urlParams = $arguments;
						$controllerObj = load()->newInstance($controller, false, $controllerName);
						//$controllerObj->setSession($session);

						try {
							$result = $controllerObj->handleRequest($request, $model);
						} catch(HTTPResponse_Exception $responseException) {
							$result = $responseException->getResponse();
						}
						if(!is_object($result) || $result instanceof HTTPResponse) return $result;
						
						user_error("Bad result from url ".$request->getURL()." handled by ".
								$controllerObj->toString()." controller: ".$result-toString(), E_USER_WARNING);						
					}
				}
			}
		}
	}

//URL SETTINGS
	
	public static function absoluteURL($url, $relativeToSiteBase = false) {
		if(!isset($_SERVER['REQUEST_URI'])) return false;
		
		if(strpos($url,'/') === false && !$relativeToSiteBase){
			$url = dirname($_SERVER['REQUEST_URI'] . 'x') . '/' . $url;
		}

	 	if(substr($url,0,4) != "http"){
	 		if($url[0] != "/"){
	 			$url = SITE_ROOT.$url;
	 		} else if(strpos($url, BASE_PATH) === 0){
	 			//Remove BASE_PATH and add SITE_ROOT
	 			$url = SITE_ROOT.substr($url, strlen(BASE_PATH));
	 		}
		}
		
		return $url;
	}
	public static function relativeUrl($url) {
		// Allow for the accidental inclusion of a // in the URL
		$url = preg_replace('#([^:])//#', '\\1/', $url);
		$url = trim($url);

		// Only bother comparing the URL to the absolute version if $url looks like a URL.
		if(preg_match('/^https?[^:]*:\/\//i',$url)) {
			$base1 = strtolower(SITE_ROOT);
			// If we are already looking at baseURL, return '' (substr will return false)
			if(strtolower($url) == $base1) return '';
			else if(strtolower(substr($url,0,strlen($base1))) == $base1) return substr($url,strlen($base1));
			// Convert http://www.mydomain.com/mysitedir to ''
			else if(substr($base1,-1)=="/" && strtolower($url) == substr($base1,0,-1)) return "";
		}
		
		// test for base folder, e.g. /var/www
		$base2 = strtolower(BASE_PATH);
		if(strtolower(substr($url,0,strlen($base2))) == $base2) return substr($url,strlen($base2));

		// Test for relative base url, e.g. mywebsite/ if the full URL is http://localhost/mywebsite/
		$base3 = strtolower(WORKING_DIR);
		if(strtolower(substr($url,0,strlen($base3))) == $base3) return substr($url,strlen($base3));
		
		// Nothing matched, fall back to returning the original URL
		return $url;
	}
	
	public static function isAbsoluteUrl($url) {
		$colonPosition = strpos($url, ':');
	  return (
	  	// Base check for existence of a host on a compliant URL
	  	parse_url($url, PHP_URL_HOST)
	  	// Check for more than one leading slash without a protocol.
			// While not a RFC compliant absolute URL, it is completed to a valid URL by some browsers,
			// and hence a potential security risk. Single leading slashes are not an issue though.
	  	|| preg_match('/\s*[\/]{2,}/', $url)
	  	|| (
	  		// If a colon is found, check if it's part of a valid scheme definition
		  	// (meaning its not preceded by a slash, hash or questionmark).
		  	// URLs in query parameters are assumed to be correctly urlencoded based on RFC3986,
		  	// in which case no colon should be present in the parameters.
	  		$colonPosition !== FALSE 
	  		&& !preg_match('![/?#]!', substr($url, 0, $colonPosition))
	  	)
	  	
	  );
	}

	public static function isRelativeUrl($url) {
		return (!self::isAbsoluteUrl($url));
	}
	
	public static function isSiteUrl($url) {
		$urlHost = parse_url($url, PHP_URL_HOST);
		$actualHost = parse_url(SITE_ROOT, PHP_URL_HOST);
		if($urlHost && $actualHost && strtolower($urlHost) == strtolower($actualHost)){
			return true;
		} else {
			return self::isRelativeUrl($url);
		}
	}
	
//Enviroment Settings	
	
	public static function isLive() {
		return true;
		return !($this->isDev() || $this->isTest());
	}
	public static function isDev(){
		return $this->checkDevEnviroment();
	}
	public static function isTest(){
		return $this->checkTestEnviroment();
	}

	private function isDevEnviroment($dontTouchDB = false) {
		// This variable is used to supress repetitions of the isDev security message below.
		static $firstTimeCheckingGetVar = true;

		$result = false;

		if(isset($_SESSION['isDev']) && $_SESSION['isDev']) $result = true;
		if(isset($this->enviromentMode) && $this->enviromentMode == 'dev') $result = true;

		// Use ?isDev=1 to get development access on the live server
		if(!$dontTouchDB && !$result && isset($_GET['isDev'])) {
			if(Security::database_is_ready()) {
				if($firstTimeCheckingGetVar && !Permission::check('ADMIN')){
					BasicAuth::requireLogin("Touchbase developer access. Use your CMS login", "ADMIN");
				}
				$_SESSION['isDev'] = $_GET['isDev'];
				$firstTimeCheckingGetVar = false;
				$result = $_GET['isDev'];
			} else {
				if($firstTimeCheckingGetVar && DB::connection_attempted()) {
	 				echo "<p style=\"padding: 3px; margin: 3px; background-color: orange; 
						color: white; font-weight: bold\">Sorry, you can't use ?isDev=1 until your
						Member and Group tables database are available.  Perhaps your database
						connection is failing?</p>";
					$firstTimeCheckingGetVar = false;
				}
			}
		}

		return $result;
	}
	
	private function isTestEnviroment() {
		// Use ?isTest=1 to get test access on the live server, or explicitly set your environment
		if(isset($_GET['isTest'])) {
			if(Security::database_is_ready()) {
				load()->getInstance('Security\BasicAuth')->requireLogin("Touchbase testing access. Use your CMS login", "ADMIN");
				$_SESSION['isTest'] = $_GET['isTest'];
			} else {
				return true;
			}
		}
		if($this->isDev()) return false;
		
		if(isset($this->enviromentMode)) {
			return $this->enviromentMode == 'test';
		}
		
		// Check if we are running on one of the test servers
		if(isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $this->testingServers))  {
			return true;
		}
		
		return false;
	}
	

	
}