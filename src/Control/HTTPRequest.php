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

class HTTPRequest extends HTTPHeaders
{
	
	protected $httpMethod;
	
	protected $_GET = array();
	
	protected $_POST = array();
	
	protected $url;
	
	protected $extension;
	
	protected $urlSegments = array();
	
	protected $data;
	
	protected $requestTime;
	
	/**
	 * @var array $allParams Contains an assiciative array of all
	 * arguments matched in all calls to {@link RequestHandler->handleRequest()}.
	 * It's a "historical record" that's specific to the current call of
	 * {@link handleRequest()}, and is only complete once the "last call" to that method is made.
	 */
	protected $allParams = array();
	
	/**
	 * @var array $urlParams Contains an associative array of all
	 * arguments matched in the current call from {@link RequestHandler->handleRequest()},
	 * as denoted with a "$"-prefix in the $url_handlers definitions.
	 * Contains different states throughout its lifespan, so just useful
	 * while processed in {@link RequestHandler} and to get the last
	 * processes arguments.
	 */
	protected $urlParams = array();
	
	protected $unshiftedButParsedParts = 0;
	
	
	public function __construct($httpMethod, $url, $get = null, $post = null, $data = null) {
		$this->requestTime = time();
	
		$this->httpMethod = $httpMethod;
		$this->setUrl($url);
		
		$this->_GET = (array)(isset($get)?$get:$_GET);
		$this->_POST = (array)(isset($post)?$post:array_merge_recursive($_POST, $_FILES));
		
		//(php://input) Standard Input Stream -> Contains POST and PUT data! 
		$this->data = isset($data)?$data:@file_get_contents('php://input');
		
		//Set Headers
		$this->addHeaders($this->parseRequestHeaders());
	}
	
	public function setUrl($url) {
		$this->url = $url;
		
		// Normalize URL if its relative (strictly speaking), or has leading slashes
		if(\Touchbase\Control\Router::isRelativeUrl($url) || preg_match('/^\//', $url)) {
			$this->url = preg_replace(array('/\/+/','/^\//', '/\/$/'),array('/','',''), $this->url);
		}
		if(preg_match('/^(.*)\.([A-Za-z][A-Za-z0-9]*)$/', $this->url, $matches)) {
			$this->url = $matches[1];
			$this->extension = $matches[2];
		}
		
		if($this->url){
			$this->urlSegments = preg_split('|/+|', $this->url);
		}
		
		return $this;
	}
	
	public function url(){
		return $this->url;
	}
	
	function match($pattern){
		$params = array();
		$shiftCount = 0;
		
		//User Defined Paths -
		if($pattern){
			$patternParts = explode('/', $pattern);
			$shiftCount = count($patternParts);
		
			foreach($patternParts as $i => $part) {
				$part = trim($part);
				
				// Match a variable
				if(isset($part[0]) && $part[0] == '$'){
					$lastChar = substr($part,-1);
					if($lastChar == '!' || $lastChar == '*'){
						if(!isset($this->urlSegments[$i])) return false;
						$params[substr($part,1,-1)] = ($lastChar == '*')?implode("/", $this->urlSegments):$this->urlSegments[$i];
					} else {
						$params[substr($part,1)] = @$this->urlSegments[$i];
					}
				}
			}
			
			$this->urlParams = $params;
		} else {		
			//Controllers - NAMESPACE\Controllers\PathTo\Controller
			$urlSegments = $this->urlSegments;
			do {
				$shiftCount = count($urlSegments);
				$controller = 'UserInsight\Controllers\\'.implode('\\', $urlSegments)."Controller";
				if(class_exists($controller) && is_subclass_of($controller, '\Touchbase\Control\Controller')){
					$params['Controller'] = $controller;
					break;
				}
			} while(array_pop($urlSegments));
		}
		
		$this->shift($shiftCount);
		// We keep track of pattern parts that we looked at but didn't shift off.
		// This lets us say that we have *parsed* the whole URL even when we haven't *shifted* it all
		$this->unshiftedButParsedParts = ($this->unshiftedButParsedParts > 0)?$this->unshiftedButParsedParts:0;
		
		// Load the arguments that actually have a value into $this->allParams
		// This ensures that previous values aren't overridden with blanks
		foreach($params as $k => $v) {
			if($v || !isset($this->allParams[$k])) $this->allParams[$k] = $v;
		}
		
		return $params;
	}

	function matches($pattern, $shiftOnSuccess = false) {
	
		// Check if a specific method is required
		if(preg_match('/^([A-Za-z]+) +(.*)$/', $pattern, $matches)) {
			$requiredMethod = $matches[1];
			if($requiredMethod != $this->httpMethod) return false;
			
			// If we get this far, we can match the URL pattern as usual.
			$pattern = $matches[2];
		}
				
		// Special case for the root URL controller
		if(!$pattern) {
			return ($this->urlSegments == array()) ? array('Matched' => true) : false;
		}

		// Check for the '//' marker that represents the "shifting point"
		$doubleSlashPoint = strpos($pattern, '//');
		if($doubleSlashPoint !== false) {
			$shiftCount = substr_count(substr($pattern,0,$doubleSlashPoint), '/') + 1;
			$pattern = str_replace('//', '/', $pattern);
			$patternParts = explode('/', $pattern);
		} else {
			$patternParts = explode('/', $pattern);
			$shiftCount = count($patternParts);
		}

		$matched = true;
		$arguments = array();
		foreach($patternParts as $i => $part) {
			$part = trim($part);

			// Match a variable
			if(isset($part[0]) && $part[0] == '$') {
				// A variable ending in ! is required
				if(substr($part,-1) == '!'){
					$varRequired = true;
					$varName = substr($part,1,-1);
					
				// A Variable ending in * is required and merges all segments after it 
				} else if(substr($part,-1) == '*'){
					$varRequired = true;
					$mergeRemaining = true;
					$varName = substr($part,1,-1);
					
				} else {
					$varRequired = false;
					$varName = substr($part,1);
				} 
				
				// Fail if a required variable isn't populated
				if($varRequired && !isset($this->urlSegments[$i])) return false;
				
				if(isset($mergeRemaining)){
					$leftUrlSegments = $this->urlSegments;
					$this->shift($i, $leftUrlSegments);
					$arguments[$varName] = implode("/", $leftUrlSegments);
					$shiftCount += count($leftUrlSegments);

					//No More Process
					break;
				}
				
				$arguments[$varName] = isset($this->urlSegments[$i])?$this->urlSegments[$i]:null;
				
				\pre_r($arguments['Controller']);
				
				if($part == '$Controller' && (!class_exists($arguments['Controller']) || !is_subclass_of($arguments['Controller'], 'Controller'))) {
					return false;
				}
				
			// Literal parts with extension
//			} else if(isset($this->urlSegments[$i]) && $this->urlSegments[$i] . '.' . $this->extension == $part) {
//				continue;
	
			// Literal parts must always be there
			} else if(!isset($this->urlSegments[$i]) || $this->urlSegments[$i] != $part) {
				return false;
			}
			
		}

		if($shiftOnSuccess) {
			$this->shift($shiftCount);
			// We keep track of pattern parts that we looked at but didn't shift off.
			// This lets us say that we have *parsed* the whole URL even when we haven't *shifted* it all
			if(isset($mergeRemaining)){
				//All Parsed if Remaining.
				$this->unshiftedButParsedParts = count($patternParts) - $shiftCount;
			}
			$this->unshiftedButParsedParts = ($this->unshiftedButParsedParts > 0)?$this->unshiftedButParsedParts:0;
		}

		$this->urlParams = $arguments;

		// Load the arguments that actually have a value into $this->allParams
		// This ensures that previous values aren't overridden with blanks
		foreach($arguments as $k => $v) {
			if($v || !isset($this->allParams[$k])) $this->allParams[$k] = $v;
		}
		
		if($arguments === array()) $arguments['_matched'] = true;
		return $arguments;
	}
	
	public function time(){
		return $this->requestTime;
	}
	
	//Variable Info
	private function requestGET($name){
		return (isset($this->_GET[$name]))?$this->_GET[$name]:null;
	}
	
	private function requestPOST($name){ 
		return (isset($this->_POST[$name]))?$this->_POST[$name]:null;
	}
	
	public function requestVAR($name){
		$_POST = $this->requestPOST($name);
		$_GET = $this->requestGET($name);
		return !empty($_POST)?$_POST:(!empty($_GET)?$_GET:null);
	}
	
	public function requestVARS(){
		return array_merge_recursive($this->_GET, $this->_POST);
	}
	
	public function getUrlParams($paramName = false){
		if(!empty($paramName)){
			if(isset($this->urlParams[$paramName])){
				return $this->urlParams[$paramName];
			}
			return false;
		}
		return $this->urlParams;
	}
	
	public function urlSegment($segment = 0){
		return (isset($this->urlSegments[$segment]))?$this->urlSegments[$segment]:"";
	}
	
	public function urlSegments($offset = 0, $length = null){
		return array_slice($this->urlSegments, $offset, $length);
	}
	
	public function remaining(){
		return implode("/", $this->urlSegments);
	}
	
	public function extension(){
		return $this->extension;
	}
	
	public function isEmptyPattern($pattern) {
		if(preg_match('/^([A-Za-z]+) +(.*)$/', $pattern, $matches)) {
			$pattern = $matches[2];
		}
		
		if(trim($pattern) == "") return true;
	}
	
	public function allParsed() {
		return count($this->urlSegments) <= $this->unshiftedButParsedParts;
	}
	
	//Check HTTP Method
	public function isGET() {
		return $this->httpMethod == 'GET';
	}
	
	public function isPOST() {
		return $this->httpMethod == 'POST';
	}
	
	public function isPUT() {
		return $this->httpMethod == 'PUT';
	}

	public function isDELETE() {
		return $this->httpMethod == 'DELETE';
	}	

	public function isHEAD() {
		return $this->httpMethod == 'HEAD';
	}
	
	public function isAjax(){
		return $this->requestVAR('ajax') || ($this->getHeader('X-Requested-With') && $this->getHeader('X-Requested-With') == "XMLHttpRequest");
	}
	
	/**
	 *	Shift 
	 *	This function shifts url segments off the array
	 *	@return (array)
	 */
	public function shift($count = 1, &$array = null) {		
		if(is_null($array)) $array = &$this->urlSegments;
		
		if($count > 1){
			$return = array();
			for($i = 0; $i < $count; $i++){
				$value = array_shift($array);
				
				if(!$value) break;
				$return[] = $value;
			}
	
			return $return;
		}
		
		return array_shift($array);
	}
	
	/**
	 *	Parse Request Headers
	 *	Get headers sent in to the server
	 *	@return array
	 */
	private function parseRequestHeaders(){
		$headers = array();
		foreach($_SERVER as $k=>$v){
			if(substr($k, 0, 5)=="HTTP_"){
				$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($k, 5)))));
        		$headers[$header] = $v;
			}
		}
		
		if(isset($_SERVER['CONTENT_TYPE'])){
			$headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
		}
		if(isset($_SERVER['CONTENT_LENGTH'])){
			$headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
		}

		return $headers;
	}
	
	/**
	 *	Client IP
	 *	Returns the real IP address of the user
	 *	@return (string)
	 */
	public function clientIp(){
		// If HTTP_CLIENT_IP is set, then give it priority
		if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		}
		
		// User is behind a proxy and check that we discard RFC1918 IP addresses
		// if they are behind a proxy then only figure out which IP belongs to the
		// user.  Might not need any more hackin if there is a squid reverse proxy
		// infront of apache.
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			// Put the IP's into an array which we shall work with shortly.
			$ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
			if($ip){
				array_unshift($ips, $ip); $ip = FALSE;
			}
				
			for ($i = 0; $i < count($ips); $i++) {
				// Skip RFC 1918 IP's 10.0.0.0/8, 172.16.0.0/12 and
				// 192.168.0.0/16
				if (!preg_match('/^(?:10|172\.(?:1[6-9]|2\d|3[01])|192\.168)\./', $ips[$i])) {
					if (ip2long($ips[$i]) != false) {
					$ip = $ips[$i];
						break;
					}
				}
			}
		}
		
		// Return with the found IP or the remote address
		return (!empty($ip) ? $ip : $_SERVER['REMOTE_ADDR']);
	}
	
}

?>