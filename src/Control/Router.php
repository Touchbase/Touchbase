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

use Touchbase\Data\StaticStore;
use Touchbase\Data\SessionStore;
use Touchbase\Filesystem\File;
use Touchbase\Filesystem\Folder;
use Touchbase\Security\Auth;
use Touchbase\Security\Permission;
use Touchbase\Control\Session;
use Touchbase\Control\HTTPRequest;
use Touchbase\Control\Exception\HTTPResponseException;
use Touchbase\Core\Config\ConfigTrait;
use Touchbase\Core\Config\Store as ConfigStore;

class Router extends \Touchbase\Core\BaseObject
{
	const ROUTE_HISTORY_KEY = "touchbase.key.route_history";
	
	/**
	 *	Dispatch
	 *	@var \Touchbase\Control\Application
	 */
	private static $dispatch;
	
	/* Public Methods */
	
	/**
	 *	Config
	 *	@param string $section
	 *	@return Touchbase\Core\Config\Store
	 */
	public static function config($section = null){		
		$config = StaticStore::shared()->get(ConfigStore::CONFIG_KEY, false);
		return $section ? $config->get($section) : $config;
	}

	/**
	 *	Route
	 *	@param HTTPRequest | string $request
	 *	@param HTTPResponse $response
	 *	@return VOID
	 */
	public static function route(/* HTTPRequest | string */ $request, HTTPResponse &$response = null){
		
		//GET / POST etc.
		$requestMethod = (isset($_SERVER['X-HTTP-Method-Override'])) ? $_SERVER['X-HTTP-Method-Override'] : $_SERVER['REQUEST_METHOD'];
		
		// IIS will sometimes generate this.
		if(!empty($_SERVER['HTTP_X_ORIGINAL_URL'])) {
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
		}
		
		$url = is_string($request) ? $request : preg_replace("/^\/".str_replace("/", "\/?", WORKING_DIR)."/", '', $_SERVER["REQUEST_URI"]);
		if(strpos($url, '?') !== false) {
			list($url, $query) = explode('?', $url, 2);
			parse_str($query, $_GET);
			if ($_GET) $_REQUEST = array_merge((array)$_REQUEST, (array)$_GET);
		}
		
		// Pass back to the webserver for files that exist
		if(php_sapi_name() == 'cli-server'){
			$realFile = File::create([BASE_PATH, 'public_html', $url]);
			if($realFile->isFile() && $realFile->exists()){
				//Can't return false this far up, read file instead.
				readfile($realFile->path);
				exit;
			}
		}
		
		//Remove base folders from the URL if webroot is hosted in a subfolder
		if(substr(strtolower($url), 0, strlen(BASE_PATH)) == strtolower(BASE_PATH)){
			$url = substr($url, strlen(BASE_PATH));
		}
		
		$response = $response ?: HTTPResponse::create();
		if(!($request instanceof HTTPRequest)){
			$request = HTTPRequest::create($requestMethod, $url)->setMainRequest(true);
		}
		
        SessionStore::ageFlash();
        
		if(!static::handleRequest($request, $response)){
			try {			
				//Get Dispatchable
				if(!static::$dispatch){
					$dispatchNamespace = static::config()->get("project")->get("namespace", "");
					$dispatch = $dispatchNamespace.'\\'.$dispatchNamespace.'App';
					if(substr($dispatch, 0, 1) != '\\'){
						$dispatch = '\\' . $dispatch;
					}
				
					if(class_exists($dispatch)){
						if(!static::$dispatch){
							static::$dispatch = $dispatch::create();
							
							static::$dispatch->setConfig(static::config())
											 ->init();
						}
					} else {
						$e = new HTTPResponseException("Could not load project", 404);
	
						//Error responses should be considered plain text for security reasons.
						$e->response()->setHeader('Content-Type', 'text/plain');
				 
						throw $e;
					}
				}
			
				static::$dispatch->handleRequest($request, $response);
			} catch(HTTPResponseException $e){
				$response->setStatusCode($e->getCode(), $e->getMessage());
				$response->setBody(static::$dispatch ? static::$dispatch->handleException($e) : $e->getMessage());
			}
		}
		
		//TODO: It would be good for this to reside in $response->render() - But would need access to $request.
		if(((!$response->isError() && !$response->hasFinished()) || $response->statusCode() === 401) && $request->isMainRequest() && !$request->isAjax()){
			if(strpos($response->getHeader("Content-Type"), "text/html") === 0){
				static::setRouteHistory(static::buildParams($request->url(), $_GET));
			}
		}
		
        if(static::isDev()){
            error_log(sprintf("%s [%d]: %s - %s", $requestMethod, $response->statusCode(), $url, $response->statusDescription()), 4);
        }
        
		return $response;
	}
		
//URL SETTINGS
	
	/**
	 *	Absolute URL
	 *	Given a relative URL this method will convert it to an absolute URL based on the current host
	 *	@param string $url
	 *	@param BOOL $relativeToSiteBase
	 *	@return string
	 */
	public static function absoluteURL($url, $relativeToSiteBase = false) {
		if(!isset($_SERVER['REQUEST_URI'])) return false;
			
		if(strpos($url,'/') === false && !$relativeToSiteBase){
			$url = dirname($_SERVER['REQUEST_URI'] . 'x') . '/' . $url;
		}
		
	 	if(strpos($url, "//") !== 0 && strpos(strtolower($url), "http") !== 0){
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
	 *	@param string $url
	 *	@return string
	 */
	public static function relativeUrl($url) {
		// Allow for the accidental inclusion of a // in the URL
		$url = preg_replace('#([^:])//#', '\\1/', $url);
		$url = trim($url);
		
		// Only bother comparing the URL to the absolute version if $url looks like a URL.
		if(preg_match('/^https?[^:]*:\/\//i',$url)) {
			$base1 = strtolower(SITE_URL);
			// If we are already looking at baseURL, return '/' (substr will return false)
			if(strtolower($url) == $base1) return '/';
			else if(strtolower(substr($url,0,strlen($base1))) == $base1) return substr($url,strlen($base1));
			// Convert http://www.mydomain.com/mysitedir to '/'
			else if(substr($base1,-1)=="/" && strtolower($url) == substr($base1,0,-1)) return "/";
		}
		
		// test for base folder, e.g. /var/www
		$base2 = Folder::buildPath(strtolower(BASE_PATH), "/");
		if(strtolower(substr($url,0,strlen($base2))) == $base2) return substr($url,strlen($base2));

		// Test for relative base url, e.g. mywebsite/ if the full URL is http://localhost/mywebsite/
		$base3 = WORKING_DIR;
		if(strtolower(substr($url,0,strlen($base3))) == $base3) return substr($url,strlen($base3));
		
		// Nothing matched, fall back to returning the original URL
		return $url;
	}
	
	/**
	 *	Is Absolute URL
	 *	This method will determine whether the passed url is absolute (eg. https://foo.com/bar/baz)
	 *	@param string $url
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
	 *	@param string $url
	 *	@return BOOL
	 */
	public static function isRelativeUrl($url) {
		return (!self::isAbsoluteUrl($url));
	}
	
	/**
	 *	Is Site URL
	 *	This method will determine whether the passed url belongs to the current site
	 *	@param string $url
	 *	@return BOOL
	 */
	public static function isSiteUrl($url) {
		if($url){
			$urlHost = parse_url($url, PHP_URL_HOST);
			$actualHost = parse_url(SITE_URL, PHP_URL_HOST);
			if($urlHost && $actualHost && strcasecmp($urlHost, $actualHost) === 0){
				return true;
			}
			
			return self::isRelativeUrl($url);
		}
		
		return false;
	}
	
	/**
	 *	Build Url
	 *	@param array $parsedUrl
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
		$paths = array_filter(func_get_args());
		
		$count = $totalArgs = func_num_args();
		return implode("/", array_map(function($component) use (&$count, $totalArgs){
			$component = str_replace("\\", "/", $component);
			$func = ($firstArg = $count--==$totalArgs)?"rtrim":(!$count?"ltrim":"trim");
			$isProtocol = strlen($component) > ($needle="://") && substr_compare($component, $needle, -strlen($needle)) === 0;
			$component = $func($component, " \t\n\r\0\x0B".($isProtocol?"":"/"));
			return ($isProtocol && $firstArg && $totalArgs > 1)?substr($component, 0, -1):$component;
		}, $paths));
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
			|| in_array(@$_SERVER['HTTP_HOST'], static::config()->get("servers")->get("development", []));
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
			|| in_array(@$_SERVER['HTTP_HOST'], static::config()->get("servers")->get("testing", []))
		);
	}
	
	/**
	 *	Route History
	 *	@return string
	 */
	public static function routeHistory(){
		return SessionStore::get(self::ROUTE_HISTORY_KEY, []);
	}
	
	/**
	 *	Clear Route History
	 *	@return VOID
	 */
	public static function clearRouteHistory(){
		SessionStore::delete(self::ROUTE_HISTORY_KEY);
	}
	
	/* Protected Methods */
	
	/**
	 *	Set Route History
	 *	@param string route
	 *	@return \Touchbase\Control\Router
	 */
	protected static function setRouteHistory($route){
		
		$routeHistory = static::routeHistory();
		if(end($routeHistory) != $route){
			SessionStore::push(self::ROUTE_HISTORY_KEY, $route);
		}
	}
	
	/**
	 *	Handle Request
	 *	This method will attempt to load a real resource located on the server if one exists.
	 *	@param \Touchbase\Control\HTTPRequest $request
	 *	@param \Touchbase\Control\HTTPResponse &$response
	 *	@return mixed
	 */
	private static function handleRequest(HTTPRequest $request, HTTPResponse &$response){

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
			$assetFilePath = static::pathForAssetMap($request->urlSegment());
			if($assetFilePath){	
				$assetFile = File::create([
					BASE_PATH,
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
				"woff2"=> "application/font-woff2",
				"apk" => "application/vnd.android.package-archive",
				"ipa" => "application/octet-stream"
			];
			
			if($assetFile->exists() && array_key_exists($assetFile->ext(), $supportedAssets)){
				$response->addHeader("Content-Type", $supportedAssets[$assetFile->ext()]);
				$response->addHeader("Content-Disposition", "attachment; filename=".$assetFile->name);
				$response->addHeader('Content-Length', $assetFile->size()); //TODO: Should be done in response setBody!

				if(php_sapi_name() != 'cli-server' && static::config("assets")->get("x_sendfile", false)){
					$response->addHeader("X-Sendfile", $assetFile->path);
				} else {
					$response->setBody($assetFile->read());
				}
				
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 *	Asset Map For Path
	 *	@param string $path
	 *	@return string
	 */
	public static function assetMapForPath($path){
		if(strpos($path, BASE_PATH) === 0) $path = substr($path, strlen(BASE_PATH));
		return substr(md5($path), 0, 6);
	}
	
	/**
	 *	Path For Asset Map
	 *	@param string $assetMap
	 *	@return string
	 */
	public static function pathForAssetMap($assetMap){
		return static::config("assets")->get("asset_map")->get($assetMap, null);
	}
	
	/**
	 *	Path For Asset Url
	 *	@param string assetUrl
	 *	@return string
	 */
	public static function pathForAssetUrl($assetUrl, $assetType = null){
		if(Router::isSiteUrl($assetUrl)){
			list($assetMapFragment, $assetUrl) = array_pad(explode("/", Router::relativeUrl($assetUrl), 2), 2, null);
			
			$file = File::create([BASE_PATH, static::pathForAssetMap($assetMapFragment), $assetUrl]);
			if($file->exists()){
				return $file->path;	
			}
			
			// //Is it a folder?
			// $folder = Folder::create([BASE_PATH, static::pathForAssetMap($assetMapFragment), $assetUrl]);
			// if($folder->exists()){
			// 	return $folder->path;	
			// }
			
			return null;
		}
		
		return $assetUrl;
	}

}