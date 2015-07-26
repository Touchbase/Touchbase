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
 *  @package Parse
 *  @category Control
 *  @date 20/07/2014
 */
 
namespace Touchbase\API\Client\Provider;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\API\Client\ClientInterface;

use Touchbase\Control\Router;
use Touchbase\Core\Config\ConfigTrait;
use Touchbase\Core\Config\Store as ConfigStore;

class CurlClientProvider extends \Touchbase\Control\HTTPHeaders implements ClientInterface
{
	use ConfigTrait;
	
	/**
	 *	@var array
	 */
	protected $headers = [
		'Content-Type' => 'application/x-www-form-urlencoded'	
	];
	
	/**
	 *	@var mixed
	 */
	protected $_lastResponse;
	
	/**
	 *	@var integer
	 */
	protected $_lastResponseStatus;
	
	/**
	 *	@var string
	 */
	protected $_lastResponseContentType;
	
	
	/* Public Methods */
	
	/**
	 *	Configure
	 *	@param ConfigStore
	 *	@return VOID
	 */
	public function configure(ConfigStore $config){
		$this->setConfig($config);
	}
	
	/**
	 *	Content Type
	 *	@return string
	 */
	public function contentType(){
		return $this->_lastResponseContentType;
	}
	
	/**
	 *	Request
	 *	@param requestMethod
	 *	@param requestEndpoint
	 *	@param requestBody
	 *	@return VOID
	 */
	public function request($requestMethod, $requestEndpoint, $requestBody = NULL, $requestHeaders = []){
	
		if(!$requestMethod || !$requestEndpoint){
			throw new \InvalidArgumentException("Missing Arguments");
		}
		
		$this->addHeaders($requestHeaders);
		
		if(Router::isDev()){
			ob_start();
				\pre_r($requestMethod, $requestEndpoint, $requestBody, $this->getAllHeaders());
				$apiLog = ob_get_contents();
			ob_end_clean();
			error_log($apiLog);
		}
		
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $requestEndpoint);
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_USERAGENT, 'touchbase-php-library/2.0');
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLINFO_HEADER_OUT, true);
		curl_setopt($c, CURLOPT_HTTPHEADER, $this->getAllHeaders());
		curl_setopt($c, CURLOPT_CUSTOMREQUEST, strtoupper($requestMethod));
		curl_setopt($c, CURLOPT_POSTFIELDS, $requestBody);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false); 
	
		$this->_lastResponse = curl_exec($c);
		$this->_lastResponseStatus = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$this->_lastResponseContentType = curl_getinfo($c, CURLINFO_CONTENT_TYPE);
		
		$curlError = curl_error($c);
		if($curlError){
			throw new \RuntimeException(is_string($curlError)?$curlError:"Something Terrible Happened!");
		}
		
		return $this->_lastResponse;
	}
	
	public function cancel(){}
	
	public function cancelAll(){}
	
	/**
	 *	Last Response Code
	 *	@return Int
	 */
	public function lastResponseCode(){
		return $this->_lastResponseStatus;
	}
	
	/**
	 *	Last Response Message
	 *	@return mixed
	 */
	public function lastResponseMessage(){
		return $this->_lastResponse;
	}
	
	/**
	 *	Last Response Content Type
	 *	@return mixed
	 */
	public function lastResponseContentType(){
		return $this->_lastResponseContentType;
	}

	/**
	 *  Set Authorization Header With Username And Password
	 *  This function we send a basic authentication header
	 *  @return VOID
	 */
	public function setAuthorizationHeaderWithUsernameAndPasssword($username, $password){
		$this->addHeader("Authorization", "Basic ".base64_encode($username.":".$password));
		
		return $this;
	} 	
	
	/**
	 *  Set Authorization Header With Token
	 *  This function will set a token in the header
	 *  @return VOID
	 */
	public function setAuthorizationHeaderWithToken($token){
		 $this->addHeader("Authorization", "Bearer $token");
		 
		 return $this;
	}
	
	/**
	 *  Clear Authorization Header
	 *  This function will remove any authorization headers
	 *  @return VOID
	 */
	public function clearAuthorizationHeader(){
		$this->removeHeader("Authorization");
		
		return $this;
	}

}