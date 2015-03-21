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
 *  @category 
 *  @date 14/03/2014
 */
 
namespace Touchbase\Data;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Control\Session;

final class SessionStore
{
	const FLASH_KEY = 'touchbase.key.store.flash';
	const STORE_SESSION_KEY = 'touchbase.key.store.session';
	
	/**
	 *	Determine if new request.
	 */
	private static $flushFlash = true;
	
	/**
	 *	Shared
	 *	@return \Touchbase\Data\Store
	 */
	public static function shared(){
		
		$store = Session::get(self::STORE_SESSION_KEY, new Store());
		if(!$store->count()){
			Session::set(self::STORE_SESSION_KEY, $store);
		}
		
		if(static::$flushFlash){
			static::$flushFlash = false;
			static::ageFlashedData($store);
		}
		
		return $store;
	}
	
	/**
	 *	__callStatic
	 *	@param string $name
	 *	@param array $arguments
	 *	@return mixed
	 */
	public static function __callStatic($name, $arguments){
		if(method_exists(static::shared(), $name)){
			return call_user_func_array([static::shared(), $name], $arguments);
		}
	}
	
	
	/* Public Methods */
	
	public static function flash($key, $value){
		static::shared()->set($key, $value);
		static::shared()->push(self::FLASH_KEY, $key);
		
		static::shared()->set(self::FLASH_KEY.".aged", array_diff(static::shared()->get(self::FLASH_KEY.".aged", []), [$key]));
	}
	
	public static function reflash(){
		//TODO: reflash sounds like a good idea.	
	}
	
	public static function flush(){
		static::shared()->delete(self::STORE_SESSION_KEY);
	}
	
	/* Private Methods */
	
	private static function ageFlashedData(Store $store){
		
		foreach($store->get(self::FLASH_KEY.".aged") as $flashKey){
			$store->delete($flashKey);
		}
		
		$store->set(self::FLASH_KEY.".aged", $store->get(self::FLASH_KEY, []));
		$store->set(self::FLASH_KEY, []);

	}	
	
	/**
	 * NO-OP
	 */
	final public function __construct(){}
	final protected function __clone(){}
}