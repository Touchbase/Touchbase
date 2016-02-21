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
    const RECYCLE_KEY = 'touchbase.key.store.recycle.';
	const STORE_SESSION_KEY = 'touchbase.key.store.session';
	
	/**
	 *	Determine if new request.
	 *	@var BOOL
	 */
	private static $flushFlash = true;
	
	/* Public Methods */
	
	/**
	 *	Shared
	 *	@return \Touchbase\Data\Store
	 */
	public static function shared(){
		
		$store = Session::get(self::STORE_SESSION_KEY, new Store());
		if(!$store->count()){
			Session::set(self::STORE_SESSION_KEY, $store);
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
	
    /**
	 *	Recycle
	 *	@param string $bin
     *	@param string $key
	 *	@param mixed $value
	 *	@return VOID
	 */
    public static function recycle($bin, $key, $value){
        SessionStore::set($key, $value);
        $count = SessionStore::unshift(self::RECYCLE_KEY . $bin, $key);
        
        if($count > 5){
            SessionStore::delete(SessionStore::pop(self::RECYCLE_KEY . $bin));
        }
    }
    
    /**
	 *	Consume
	 *	@param string $bin
     *	@param string $key
	 *	@return VOID
	 */
    public static function consume($bin, $key){
        $array = SessionStore::get(self::RECYCLE_KEY . $bin);
        foreach(array_keys($array, $key, true) as $removeKey){
            unset($array[$removeKey]);
        }
        
        SessionStore::set(self::RECYCLE_KEY . $bin, $array);
        SessionStore::delete($key);
    }
    
	/**
	 *	Flash
	 *	@param string $key
	 *	@param mixed $value
	 *	@return VOID
	 */
	public static function flash($key, $value){
		SessionStore::set($key, $value);
		SessionStore::push(self::FLASH_KEY, $key);
		
		SessionStore::set(self::FLASH_KEY.".aged", array_diff(SessionStore::get(self::FLASH_KEY.".aged", []), [$key]));
	}
	
	/**
	 *	Reflash
	 *	NB. Currently not implemented
	 *	@return VOID
	 */
	public static function reflash(){
		$aged = SessionStore::get(self::FLASH_KEY.".aged", []);
		$values = array_unique(array_merge(SessionStore::get(self::FLASH_KEY, []), $aged));
		SessionStore::set(self::FLASH_KEY, $values);
	}
	
	/**
	 *	Flush
	 *	@return VOID
	 */
	public static function flush(){
		SessionStore::delete(self::STORE_SESSION_KEY);
	}
	
    /**
	 *	Flush
	 *	@return VOID
	 */
    public static function ageFlash(){
        if(static::$flushFlash){
            static::$flushFlash = false;
            static::ageFlashedData(static::shared());
        }
    }
    
	/* Private Methods */
	
	/**
	 *	Age Flashed Data
	 *	@param \Touchbase\Data\Store $store
	 *	@return VOID
	 */
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