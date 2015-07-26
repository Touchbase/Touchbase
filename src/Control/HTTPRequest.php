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
	/**
	 *	@var string
	 */
	protected $httpMethod;
	
	/**
	 *	@var BOOL
	 */
	private $_mainRequest = false;

	/**
	 *	@var string
	 */
	protected $_url;
	
	/**
	 *	@var string
	 */
	protected $_extension;
	
	/**
	 *	@var array
	 */
	protected $_GET = [];
	
	/**
	 *	@var array
	 */
	protected $_POST = [];
	
	/**
	 *	@var array
	 */
	protected $urlSegments = [];
	
	/**
	 *	@var mixed
	 */
	protected $data;
	
	/**
	 *	@var int
	 */
	protected $requestTime;
	
	/**
	 * @var array $allParams Contains an assiciative array of all
	 * arguments matched in all calls to {@link RequestHandler->handleRequest()}.
	 * It's a "historical record" that's specific to the current call of
	 * {@link handleRequest()}, and is only complete once the "last call" to that method is made.
	 */
	protected $allParams = [];
	
	/**
	 * @var array $_urlParams Contains an associative array of all
	 * arguments matched in the current call from {@link RequestHandler->handleRequest()},
	 * as denoted with a "$"-prefix in the $url_handlers definitions.
	 * Contains different states throughout its lifespan, so just useful
	 * while processed in {@link RequestHandler} and to get the last
	 * processes arguments.
	 */
	protected $_urlParams = [];
	
	/**
	 *	@var integer
	 */
	protected $unshiftedButParsedParts = 0;
	
	/* Public Methods */
	
	public function __construct($httpMethod, $url, $get = null, $post = null, $data = null) {
		$this->requestTime = time();
	
		$this->httpMethod = $httpMethod;
		$this->url = $url;
		
		$this->_GET = (array)$get?:$_GET;
		$this->_POST = (array)$post?:array_merge_recursive($_POST, $_FILES);
		
		//(php://input) Standard Input Stream -> Contains POST and PUT data! 
		$this->data = isset($data)?$data:@file_get_contents('php://input');
		
		//Set Headers
		$this->addHeaders($this->parseRequestHeaders());
	}
	
	/**
	 *	Match
	 *	Given the url pattern (eg. $Action/$ID/$OtherID) this method will attempt to match the variable to the segment in the URL
	 *	@param string $pattern
	 *	@return array
	 */
	public function match($pattern){
		$params = array();
		$shiftCount = 0;
		
		//User Defined Paths -
		if($pattern){
			$patternParts = explode('/', $pattern);
			$shiftCount = count($patternParts);
		
			foreach($patternParts as $i => $part) {
				$part = trim($part);
				
				// Match a variable
				if(isset($part[0]) && ($part[0] == '$' || $part[0] == '*')){
					$lastChar = substr($part,-1);
					if($lastChar == '!'){
						
						//This segment is required, if it doesn't returning false will throw a 404
						if(!isset($this->urlSegments[$i])) return false;
						$params[substr($part,1,-1)] = $this->urlSegments[$i];
						
					} else if($lastChar == "*"){
						if((strlen($part) > 1 && $part[0] != '$')) return false;
					
						$params[substr($part,1,-1) ?: "*"] = implode("/", $remainingSegments = $this->urlSegments($i));
						if(isset($remainingSegments)) $this->unshiftedButParsedParts = count($remainingSegments);
						
					} else if($part[0] == '$'){
						$params[substr($part,1)] = $this->urlSegment($i);
					}
				
				// Match a specific segment
				} else {
					
					//If the segment doesn't match, this is not the urlHandler to intercept.
					if($this->urlSegment($i) !== $part){
						return false;
					}
				}
			}
			
			$this->_urlParams = $params;
		} 
		
		$this->shift($shiftCount);
		// We keep track of pattern parts that we looked at but didn't shift off.
		// This lets us say that we have *parsed* the whole URL even when we haven't *shifted* it all
		$this->unshiftedButParsedParts = max($this->unshiftedButParsedParts, 0);
		
		// Load the arguments that actually have a value into $this->allParams
		// This ensures that previous values aren't overridden with blanks
		foreach($params as $k => $v) {
			if($v || !isset($this->allParams[$k])) $this->allParams[$k] = $v;
		}
		
		return $params;
	}
	
	/**
	 *	Time
	 *	The time since 1970 that the request was made
	 *	@return int
	 */
	public function time(){
		return $this->requestTime;
	}
	
	/**
	 *	Request VAR
	 *	@param string - $name
	 *	@return string
	 */
	public function requestVAR($name){
		trigger_error(sprintf("%s, use `_VAR` instead.", __METHOD__), E_USER_DEPRECATED);
		return $this->_VAR($name);
	}
	
	/**
	 *	_VAR
	 *	@param string - $name
	 *	@return string
	 */
	public function _VAR($name){
		
		if(isset($this->_POST[$name]) && !empty($post = $this->_POST[$name])){
			if(isset($_FILES[$name])){
				return $post;
			}
			
			if(is_scalar($post)){
				return filter_var($post, FILTER_SANITIZE_STRING);
			}
			
			return $post;
		}
		
		if(isset($this->_GET[$name]) && !empty($get = $this->_GET[$name])){
			return urldecode(filter_var($get, FILTER_SANITIZE_STRING));
		}
		
		return null;
	}
	
	/**
	 *	Request VARS
	 *	@return array
	 */
	public function requestVARS(){
		trigger_error(sprintf("%s, use `_VARS` instead.", __METHOD__), E_USER_DEPRECATED);
		return $this->_VARS($name);
	}
	
	/**
	 *	_VARS
	 *	@return array
	 */
	public function _VARS(){
		return array_merge_recursive($this->_GET, $this->_POST);
	}
		
	/**
	 *	URL Segment
	 *	Use thie method to return a specific URL segment
	 *	@param int $segment
	 *	@return string
	 */
	public function urlSegment($segment = 0){
		return (isset($this->urlSegments[$segment]))?$this->urlSegments[$segment]:"";
	}
	
	/**
	 *	URL Segments
	 *	Use this method to return a portion of the URL segments
	 *	@param int $offset
	 *	@param int $length
	 *	@return array
	 */
	public function urlSegments($offset = 0, $length = null){
		return array_slice($this->urlSegments, $offset, $length);
	}
	
	/**
	 *	Remaining
	 *	This method will return the remaining unparsed segments (eg /i/wasnt/parsed.png)
	 *	@return string
	 */
	public function remaining(){
		return implode("/", $this->urlSegments);
	}
	
	/**
	 *	Is Empty Pattern
	 *	@return BOOL
	 */
	public function isEmptyPattern($pattern) {
		if(preg_match('/^([A-Za-z]+) +(.*)$/', $pattern, $matches)) {
			$pattern = $matches[2];
		}
		
		return empty(trim($pattern));
	}
	
	/**
	 *	All Parsed
	 *	This method will return true if all of the url segments have been parsed
	 *	@return BOOL
	 */
	public function allParsed() {
		return count($this->urlSegments) <= $this->unshiftedButParsedParts;
	}
	
	//Check HTTP Method
	
	/**
	 *	Is Get
	 *	@return BOOL
	 */
	public function isGET() {
		return $this->httpMethod == 'GET';
	}
	
	/**
	 *	Is Post
	 *	@return BOOL
	 */
	public function isPOST() {
		return $this->httpMethod == 'POST';
	}
	
	/**
	 *	Is Put
	 *	@return BOOL
	 */
	public function isPUT() {
		return $this->httpMethod == 'PUT';
	}
	
	/**
	 *	Is Delete
	 *	@return BOOL
	 */
	public function isDELETE() {
		return $this->httpMethod == 'DELETE';
	}	
	
	/**
	 *	Is Head
	 *	@return BOOL
	 */
	public function isHEAD() {
		return $this->httpMethod == 'HEAD';
	}
	
	/**
	 *	Is Ajax
	 *	@access public
	 *	@return BOOL
	 */
	public function isAjax(){
		return $this->_VAR('ajax') || ($this->getHeader('X-Requested-With') && $this->getHeader('X-Requested-With') == "XMLHttpRequest");
	}
	
	/**
	 *	Shift 
	 *	This function shifts url segments off the array
	 *	@param int $count
	 *	@param array $array
	 *	@return array
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
	 *	Client IP
	 *	Returns the real IP address of the user
	 *	@return string
	 */
	public function clientIP(){
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
	
	/* Getters / Setters */
	
	/**
	 *	Set URL
	 *	@param string $url
	 *	@return \Touchbase\Control\HTTPRequest
	 */
	protected function setUrl($url) {
		$this->_url = $url;
		
		if(Router::isRelativeUrl($url)) {
			$this->_url = preg_replace(array('/\/+/', '/\/$/'), array('/',''), $this->_url);
		}
		
		if(preg_match('/^(.*)\.([A-Za-z][A-Za-z0-9]*)$/', $this->_url, $matches)) {
			$this->_url = $matches[1];
			$this->_extension = $matches[2];
		}
		
		if($this->_url){
			if(strpos($this->url, "/") === 0){
				$this->urlSegments = explode("/", substr($this->_url, 1));
			} else {
				$this->urlSegments = explode("/", $this->_url);
			}
		}
		
		return $this;
	}
	
	/**
	 *	Set Main Request
	 *	@param BOOL $mainRequest
	 *	@return \Touchbase\Control\HTTPRequest
	 */
	public function setMainRequest($mainRequest){
		static $mainRequestSet = null;
		if(is_null($mainRequestSet)){
			$this->_mainRequest = $mainRequest;
			$mainRequestSet = true;
		}
		
		return $this;
	}
	
	/**
	 *	Is Main Request
	 *	Use this method to determine whether the request originated from `Router::route()`
	 *	@return BOOL
	 */
	public function isMainRequest(){
		return $this->_mainRequest;
	}
	
	/**
	 *	URL
	 *	@return string
	 */
	public function url(){
		return $this->_url ?: "/";
	}
	
	/**
	 *	Extension
	 *	Returns the extension used in the URL if one is present
	 *	@return BOOL
	 */
	public function extension(){
		return $this->_extension;
	}
	
	/**
	 *	URL Params
	 *	This method will return either all of the params
	 *	@return array
	 */
	public function urlParams(){
		return $this->_urlParams;
	}
	
	/* Private Methods */

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
	
}

?>