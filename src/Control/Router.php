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

use Touchbase\Filesystem\File;
use Touchbase\Core\Config\ConfigTrait;

use Touchbase\Security\Auth;
use Touchbase\Security\Permission;
use Touchbase\Control\Session;
use Touchbase\Control\HTTPRequest;
use Touchbase\Control\Exception\HTTPResponseException;
use Touchbase\View\Assets;

use Touchbase\Data\SessionStore;

class Router extends \Touchbase\Core\Object
{
	const ROUTE_HISTORY_KEY = "touchbase.key.route_history";
	
	use ConfigTrait {
		setConfig as traitSetConfig;
	}
	
	private $urlParams =[];
	private static $developmentServers = [];
	private static $testingServers = [];
	
	/**
	 *	Route
	 *	
	 *	@param HTTPRequest $request
	 *	@param HTTPResponse $response
	 *	@return VOID
	 */
	public function route(/*HTTPRequest*/ &$request, HTTPResponse &$response){
		//GET / POST etc.
		$requestMethod = (isset($_SERVER['X-HTTP-Method-Override'])) ? $_SERVER['X-HTTP-Method-Override'] : $_SERVER['REQUEST_METHOD'];
		
		// IIS will sometimes generate this.
		if(!empty($_SERVER['HTTP_X_ORIGINAL_URL'])) {
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
		}
		
		$url = preg_replace("/^\/".str_replace("/", "\/?", WORKING_DIR)."/", '', $_SERVER["REQUEST_URI"]);
		if(strpos($url, '?') !== false) {
			list($url, $query) = explode('?', $url, 2);
			parse_str($query, $_GET);
			if ($_GET) $_REQUEST = array_merge((array)$_REQUEST, (array)$_GET);
		}
		
		// Pass back to the webserver for files that exist
		if(php_sapi_name() == 'cli-server'){
			$realFile = File::create([BASE_PATH, 'public_html', $url]);
			if($realFile->isFile() && $realFile->exists()){
				//return false not working (PHP5.5.14), read file instead.
				readfile($realFile->path);
				return false;
			}
		}
		
		//Remove base folders from the URL if webroot is hosted in a subfolder
		if(substr(strtolower($url), 0, strlen(BASE_PATH)) == strtolower(BASE_PATH)){
			$url = substr($url, strlen(BASE_PATH));
		}
		
		
		$request = $request ?: HTTPRequest::create($requestMethod, $url)->setMainRequest(true);
		
		if(!$this->handleRequest($request, $response)){
			
			//Get Dispatchable
			$dispatchNamespace = $this->config()->get("project")->get("namespace", "");
			$dispatch = $dispatchNamespace.'\\'.$dispatchNamespace.'App';
			if(substr($dispatch, 0, 1) != '\\'){
				$dispatch = '\\' . $dispatch;
			}
			
			if(class_exists($dispatch)){
				if($request->isMainRequest() && !$request->isAjax()){
					self::setRouteHistory(static::buildParams($request->url() ?: "/", $_GET));
				}
				
				try {
				
					$dispatch::create()	->setConfig($this->config())
										->init()
										->handleRequest($request, $response);
					
				} catch(HTTPResponseException $e){
					$response = $e->response();
				}
			} else {
				\pre_r("Could Not Load Project");
			}
		}
	}
	
	/**
	 *	Handle Request
	 *	This method will attempt to load a real resource located on the server if one exists.
	 *	@return mixed
	 */
	private function handleRequest(HTTPRequest $request, HTTPResponse &$response){
		
		//TopSite Preview
		if(isset($_SERVER["HTTP_X_PURPOSE"]) && $_SERVER["HTTP_X_PURPOSE"] == "preview"){
			$assetFile = File::create([BASE_PATH, 'public_html', 'preview.html']);
			
		//Favicon
		} else if($request->urlSegment() == "favicon"){
			$assetFile = File::create([BASE_PATH, 'public_html', 'favicon.ico']);
			if(!$assetFile->exists()){
				//Write an empty favicon if one doesn't exist
				$assetFile->write(base64_decode("iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAFklEQVR42mNkoBAwjhowasCoAcPFAAAmmAARm5JBWgAAAABJRU5ErkJggg=="));
			}
			
		//Asset Map
		} else {
			$assetFilePath = Assets::pathForAssetMap($request->urlSegment());
			if($assetFilePath){	
				$assetFile = File::create([
					PROJECT_PATH,
					$assetFilePath,
					implode("/", $request->urlSegments(1)).'.'.$request->extension()
				]);
			}
		}
		
		if(isset($assetFile)){
			$supportedAssets = [
				"css" => "text/css; charset=utf-8",
				"js" => "text/javascript; charset=utf-8",
				"htc" => "text/x-component",
				"png" => "image/png",
				"jpg" => "image/jpg",
				"gif" => "image/gif",
				"svg" => "image/svg+xml",
				"ico" => "image/x-icon",
				"otf" => "application/font-otf",
				"eot" => "application/vnd.ms-fontobject",
				"ttf" => "application/font-ttf",
				"woff"=> "application/font-woff",
				"apk" => "application/vnd.android.package-archive",
				"ipa" => "application/octet-stream"
			];
			
			if($assetFile->exists() && array_key_exists($assetFile->ext(), $supportedAssets)){
				session_cache_limiter(false);
				
				$response->addHeader("Content-Type", $supportedAssets[$assetFile->ext()]);
				$response->addHeader("Content-Disposition", "attachment; filename=".$assetFile->name);
				$response->addHeader('Content-Length', $assetFile->size()); //TODO: Should be done in response setBody!

				if($this->config("assets")->get("x_sendfile", false) || (function_exists("apache_get_modules") && in_array('mod_xsendfile', apache_get_modules()))){
					$response->addHeader("X-Sendfile", $assetFile->path);
				} else {
					$response->setBody($assetFile->read());
				}
				
				return true;
			}
		}
		
		return false;
	}

//CONFIG OVERLOAD

	/**
	 *	Set Config
	 *	@return \Touchbase\Control\Router
	 */
	public function setConfig(\Touchbase\Core\Config\Store $config){
	
		$servers = $config->get("servers");
		static::$developmentServers = $servers->get("development", []);
		static::$developmentServers = $servers->get("testing", []);
		
		return $this->traitSetConfig($config);
	}

//URL SETTINGS
	
	/**
	 *	Absolute URL
	 *	Given a relative URL this method will convert it to an absolute URL based on the current host
	 *	@return string
	 */
	public static function absoluteURL($url, $relativeToSiteBase = false) {
		if(!isset($_SERVER['REQUEST_URI'])) return false;
			
		if(strpos($url,'/') === false && !$relativeToSiteBase){
			$url = dirname($_SERVER['REQUEST_URI'] . 'x') . '/' . $url;
		}
		
	 	if(strpos($url, "http") !== 0 && strpos($url, "//") !== 0){
	 		if(strpos($url, WORKING_DIR) === 0){
	 			//Remove WORKING_DIR and add SITE_URL
	 			$url = static::buildPath(SITE_URL, substr($url, strlen(WORKING_DIR)));
	 		} else {
		 		$url = static::buildPath(SITE_URL, $url);
		 	}
		}
		
		return $url;
	}
	
	/**
	 *	Relative URL
	 *	This method will take an absolute URL and return a relative one. (ie. strip the host)
	 *	@return string
	 */
	public static function relativeUrl($url) {
		// Allow for the accidental inclusion of a // in the URL
		$url = preg_replace('#([^:])//#', '\\1/', $url);
		$url = trim($url);

		// Only bother comparing the URL to the absolute version if $url looks like a URL.
		if(preg_match('/^https?[^:]*:\/\//i',$url)) {
			$base1 = static::buildPath(strtolower(SITE_URL), "/");
			// If we are already looking at baseURL, return '' (substr will return false)
			if(strtolower($url) == $base1) return '';
			else if(strtolower(substr($url,0,strlen($base1))) == $base1) return substr($url,strlen($base1));
			// Convert http://www.mydomain.com/mysitedir to ''
			else if(substr($base1,-1)=="/" && strtolower($url) == substr($base1,0,-1)) return "";
		}
		
		// test for base folder, e.g. /var/www
		$base2 = static::buildPath(strtolower(BASE_PATH), "/");
		if(strtolower(substr($url,0,strlen($base2))) == $base2) return substr($url,strlen($base2));

		// Test for relative base url, e.g. mywebsite/ if the full URL is http://localhost/mywebsite/
		$base3 = static::buildPath(strtolower(WORKING_DIR), "/");
		if(strtolower(substr($url,0,strlen($base3))) == $base3) return substr($url,strlen($base3));
		
		// Nothing matched, fall back to returning the original URL
		return $url;
	}
	
	/**
	 *	Is Absolute URL
	 *	This method will determine whether the passed url is absolute (eg. https://foo.com/bar/baz)
	 *	@return BOOL
	 */
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

	/**
	 *	Is Relative URL
	 *	This method will determine whether the passed url is relative (eg. /foo/bar/baz)
	 *	@return BOOL
	 */
	public static function isRelativeUrl($url) {
		return (!self::isAbsoluteUrl($url));
	}
	
	/**
	 *	Is Site URL
	 *	This method will determine whether the passed url belongs to the current site
	 *	@return BOOL
	 */
	public static function isSiteUrl($url) {
		$urlHost = parse_url($url, PHP_URL_HOST);
		$actualHost = parse_url(SITE_URL, PHP_URL_HOST);
		if($urlHost && $actualHost && strtolower($urlHost) == strtolower($actualHost)){
			return true;
		} else {
			return self::isRelativeUrl($url);
		}
	}
	
	/**
	 *	Build Url
	 *	@return string
	 */
	public static function buildUrl($parsedUrl){
		$scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . ':' . (!strcasecmp($parsedUrl['scheme'], 'mailto') ? '' : '//') : ''; 
		$host = isset($parsedUrl['host']) ? $parsedUrl['host'] : ''; 
		$port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : ''; 
		$user = isset($parsedUrl['user']) ? $parsedUrl['user'] : ''; 
		$pass = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass']  : ''; 
		$pass = ($user || $pass) ? "$pass@" : ''; 
		$path = isset($parsedUrl['path']) ? $parsedUrl['path'] : ''; 
		$query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : ''; 
		$fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : ''; 
		return "$scheme$user$pass$host$port$path$query$fragment"; 
	}
	
	/**
	 *	Build Path
	 *	@return string - example/file/path
	 */
	public static function buildUrlPath(){
		trigger_error(sprintf("%s, use `buildPath` instead.", __METHOD__), E_USER_DEPRECATED);
		return call_user_func_array("self::buildPath", func_get_args());
	}
	public static function buildPath(){
		$paths = func_get_args();
		
		$count = $totalArgs = func_num_args();
		return implode("/", array_filter(array_map(function($component) use (&$count, $totalArgs){
			$func = ($firstArg = $count--==$totalArgs)?"rtrim":(!$count?"ltrim":"trim");
			$isProtocol = strlen($component) > ($needle="://") && substr_compare($component, $needle, -strlen($needle)) === 0;
			$component = $func($component, " \t\n\r\0\x0B".($isProtocol?"":"/"));
			return ($isProtocol && $firstArg && $totalArgs > 1)?substr($component, 0, -1):$component;
		}, $paths))).($paths[$totalArgs - 1] === "/"?"/":"");
	}
	
	/**
	 *	Build Params
	 *	@param string $baseUrl - optional
	 *	@param array $params
	 *	@return string
	 */
	public static function buildParams(/* $baseUrl, array $params */){
		$params = func_get_args();
		$baseUrl = (count($params) > 1) ? array_shift($params) : "";
		
		if(empty($params) || empty($params[0])) return $baseUrl;
		
		$separator = (parse_url($baseUrl, PHP_URL_QUERY) == NULL) ? '?' : '&';
		return $baseUrl.$separator.http_build_query($params[0]);
	}
	
//Enviroment Settings

	/**
	 *	Is CLI
	 *	@return BOOL
	 */
	public static function isCLI(){
		return php_sapi_name() == "cli";
	}
	
	/**
	 *	Is Live
	 *	@return BOOL
	 */
	public static function isLive() {
		return !(static::isDev() || static::isTest());
	}
	
	/**
	 *	Is Dev
	 *	If you are running on a development server / environment, this will return true
	 *	@return BOOL
	 */
	public static function isDev(){
				
		if(isset($_GET['isDev']) && Auth::isAuthenticated() && Auth::currentUser()->can("runDiagnosticTools")){
			SESSION::set("isDevelopment", $_GET['isDev']);
		}
		
		return SESSION::get("isDevelopment") 
			|| TOUCHBASE_ENV == 'dev'
			|| in_array(@$_SERVER['HTTP_HOST'], self::$developmentServers);
	}
	
	/**
	 *	Is Test
	 *	Whilst running unit tests, this will be true.
	 *	@return BOOL
	 */
	public static function isTest(){
		
		if(isset($_GET['isTest']) && Auth::isAuthenticated() && Auth::currentUser()->can("runTestingTools")){
			SESSION::set("isTest", $_GET['isTest']);
		}
		
		return !static::isDev() && (
			SESSION::get("isTest")
			|| TOUCHBASE_ENV == 'test'
			|| in_array(@$_SERVER['HTTP_HOST'], self::$testingServers)
		);
	}
	
	/* Getters / Setters */
	
	/**
	 *	Set Route History
	 *	@return \Touchbase\Control\Router
	 */
	protected static function setRouteHistory($route){
		
		$routeHistory = static::routeHistory();
		if(end($routeHistory) != $route){
			SessionStore::push(self::ROUTE_HISTORY_KEY, $route);
		}
	}
	
	public static function routeHistory(){
		return SessionStore::get(self::ROUTE_HISTORY_KEY, []);
	}
	
	public static function clearRouteHistory(){
		SessionStore::delete(self::ROUTE_HISTORY_KEY);
	}
}