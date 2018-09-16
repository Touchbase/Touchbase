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

namespace Touchbase\API;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\API\Client\ClientInterface;

use Touchbase\Filesystem\File;
use Touchbase\Data\StaticStore;
use Touchbase\Core\Config\ConfigTrait;
use Touchbase\Core\Config\Store as ConfigStore;
use Touchbase\Core\Config\Provider\IniConfigProvider;

class Client extends \Touchbase\Core\BaseObject
{
	use ConfigTrait;

	const CLIENT_KEY = 'touchbase.key.apiclient';
	
	/**
	 *	@var BOOL
	 */
	public static $shouldThrowErrors = true;
	
	/**
	 *	@var array
	 */
	public static $acceptableStatusCodes = null;
	
	/* Public Methods */
		
	/** 
	 *	Shared
	 *	@return \Parse\Control\Client
	 */
	public static function shared(){
		$instance = StaticStore::shared()->get(self::CLIENT_KEY, false);
		if(!$instance || is_null($instance)){
			$config = ConfigStore::create();
			try {
				//Load Main Configuration File
				$configurationData = IniConfigProvider::create()->parseIniFile(File::create('../config.ini'));
				$config->addConfig($configurationData->getConfiguration());
			} catch(\Exception $e){}
			
			//Validation 
			if(!$config instanceof ConfigStore){
				throw new \RuntimeException("No configuration settings found.");
			}
			
			$instanceName = $config->get("client")->get("provider", "\Touchbase\API\Client\Provider\CurlClientProvider");
			$instance = new $instanceName;
			
			//Validation 
			if(!$instance instanceof ClientInterface){
				throw new \InvalidArgumentException("Client provider must be an instance of \Touchbase\API\Client\ClientInterface");
			}
			
			//Configure
			$instance->configure($config);
			
			//Save
			StaticStore::shared()->set(self::CLIENT_KEY, $instance);
		}
		
		return $instance;
	}

	/**
	 *  Request
	 *	@static
	 *	@return mixed
	 */	
	public static function request($requestMethod, $requestEndpoint, $requestBody = NULL, $requestHeaders = []){
		
		$response = self::shared()->request($requestMethod, $requestEndpoint, $requestBody, $requestHeaders);
		
		if(strpos(self::shared()->contentType(), "application/json") !== false){
			$response = json_decode($response);	
		}
		
		if(static::$shouldThrowErrors){
			
			//Check HTTP Status Codes
			if(!in_array(static::lastResponseCode(), static::acceptableStatusCodes())){
				throw new \OutOfRangeException(sprintf("Expected status code in (%s), got %d", static::printAcceptableStatusCodes(), static::lastResponseCode()));
			}
			
			//Check Content Type
			if(!static::hasAcceptableContentType($requestHeaders, $acceptableStatusCodes)){
				throw new \UnexpectedValueException(sprintf("Expected content type %s, got %s", implode(",", $acceptableStatusCodes), static::lastResponseContentType()));
			}
		}
		
		return $response;
	}
	
	/**
	 *	Cancel
	 *	@return BOOL
	 */
	public static function cancel(){
		return self::shared()->cancel();
	}
	
	/**
	 *	Cancel All
	 *	@return BOOL
	 */
	public static function cancelAll(){
		return self::shared()->cancelAll();
	}
	
	/**
	 *  Acceptable Status Codes
	 *	If $shouldThrowErrors = true and the returned HTTP code is not in this list, an exception is thrown
	 *	Defaults to 200-300 if static::$acceptableStatusCodes is null
	 *	@static
	 *	@return array
	 */
	public static function acceptableStatusCodes(){
		return (static::$acceptableStatusCodes)?:range(200, 300);
	}
	
	/**
	 *  Acceptable Content Types
	 *	If $shouldThrowErrors = true and the returned content type is not in this list, an exception is thrown
	 *	Defaults null - allows all content types.
	 *	@param array - $requestHeaders - The headers used in the request
	 *	@static
	 *	@return array
	 */
	public static function acceptableContentTypes(array $requestHeaders){
		if($requestHeaders && array_key_exists("Accept", $requestHeaders)){
			return explode(",", $requestHeaders["Accept"]);
		}
		
		return NULL;
	}
	
	/**
	 *  Last Response Message
	 *	@static
	 *	@return mixed
	 */
	public static function lastResponseMessage(){
		return self::shared()->lastResponseMessage();
	}
	
	/**
	 *	Last Reponse Code
	 *	@static
	 *	@return Int
	 */
	public static function lastResponseCode(){
		return self::shared()->lastResponseCode();
	}
	
	/**
	 *  Last Response Content Type
	 *	@static
	 *	@return mixed
	 */
	public static function lastResponseContentType(){
		return self::shared()->lastResponseContentType();
	}
	
	/* Private Methods */
	
	/**
	 *  Print Acceptable Status Codes
	 *	Helper method to condense an array into ranges, in order to print them out.
	 *	@static
	 *	@return mixed
	 */
	private static function printAcceptableStatusCodes(){
		
		$rangeArray = array();
		$start = $end = current(static::acceptableStatusCodes());
		foreach(static::acceptableStatusCodes() as $range){
			if($range - $end > 1){
				$rangeArray[] = ($start == $end)?$start:$start."-".$end;
				$start = $range;
			}
			
			$end = $range;
		}
		$rangeArray[] = ($start == $end)?$start:$start."-".$end;
		
		return implode(",", $rangeArray);
	}
	
	/**
	 *  Has Acceptable Content Type
	 *	Helper method to determine whether the response has the correct content type.
	 *	@param array - $requestHeaders - The headers used in the request
	 *	@param array - &$acceptableStatusCodes - Returns the array used to check agains
	 *	@static
	 *	@return mixed
	 */
	private static function hasAcceptableContentType(array $requestHeaders, &$acceptableStatusCodes = null){
		$acceptableStatusCodes = static::acceptableContentTypes($requestHeaders);
		if(!$acceptableStatusCodes) return true;
		
		foreach($acceptableStatusCodes as $contentType){
			// If the media type remains unknown, the recipient SHOULD treat it as type "application/octet-stream".
			// @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec7.html
			if(strpos((static::lastResponseContentType())?:"application/octet-stream", $contentType) !== false){
				return true;
			}
		}
		
		return false;
	}
}