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
 *  @category Security
 *  @date 12/03/2014
 */
 
namespace Touchbase\Control;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Data\StaticStore;
use Touchbase\Control\Session\SessionInterface;
use Touchbase\Core\Config\Store as ConfigStore;

class Session 
{
	const SESSION_KEY = 'touchbase.key.session';
	
	/** 
	 *	Shared
	 *	@return \Touchbase\Security\Session
	 */
	public static function shared(){
		$instance = StaticStore::shared()->get(self::SESSION_KEY, false);
		if(!$instance || is_null($instance)){
			$config = StaticStore::shared()->get(ConfigStore::CONFIG_KEY, false);
			
			//Validation 
			if(!$config instanceof ConfigStore){
				$config = ConfigStore::create();
			}
			
			$instanceName = $config->get("session")->get("provider", "\Touchbase\Control\Session\Provider\PHPSessionProvider");
			$instance = new $instanceName;
			
			//Validation 
			if(!$instance instanceof SessionInterface){
				throw new \InvalidArgumentException("Session provider must be an instance of \Touchbase\Control\SessionInterface");
			}
			
			//Configure
			$instance->configure($config);
			
			//Save
			StaticStore::shared()->set(self::SESSION_KEY, $instance);
		}
		
		return $instance;
	}
	
	public static function __callStatic($name, $arguments){
		if(method_exists(self::shared(), $name)){
			return call_user_func_array([self::shared(), $name], $arguments);
		}
	}
}