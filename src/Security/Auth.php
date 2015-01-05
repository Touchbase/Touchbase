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
 
namespace Touchbase\Security;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Data\StaticStore;
use Touchbase\Security\AuthInterface;
use Touchbase\Security\Auth\AuthedUserInterface;
use Touchbase\Control\Session\Session;
use Touchbase\Core\Config\Store as ConfigStore;

class Auth 
{
	const AUTH_KEY = 'touchbase.key.auth';
	const AUTH_SESSION_KEY = 'touchbase.key.auth.session';
	
	/** 
	 *	Shared
	 *	@return \Touchbase\Security\Auth
	 */
	public static function shared(){
		$instance = StaticStore::shared()->get(self::AUTH_KEY, false);
		if(!$instance || is_null($instance)){
			$config = StaticStore::shared()->get(ConfigStore::CONFIG_KEY, false);
			
			//Validation 
			if(!$config instanceof ConfigStore){
				throw new \RuntimeException("No configuration settings found.");
			}
			
			
			$instanceName = $config->get("auth")->get("provider", "\Touchbase\Security\Auth\Provider\CookieProvider");
			$instance = new $instanceName;
			
			//Validation 
			if(!$instance instanceof AuthInterface){
				throw new \InvalidArgumentException("Auth provider must be an instance of \Touchbase\Security\AuthInterface");
			}
			
			//Configure
			$instance->configure($config);
			
			//Save
			StaticStore::shared()->set(self::AUTH_KEY, $instance);
		}
		
		return $instance;
	}
	
	public static function currentUser(){
		$currentUser = Session::get(self::AUTH_SESSION_KEY);
		if(!$currentUser){
			$currentUser = self::shared()->retrieveUserFromCookie();
		}
		
		return $currentUser;
	}

	public static function isAuthenticated(){
		$user = self::currentUser();
		if($user !== null){
			return true;
		}
	
		return false;
	}
	
	public static function authenticateUser(AuthedUserInterface $user){
		if(self::shared()->storeUser($user)){
			Session::set(self::AUTH_SESSION_KEY, $user);
			return true;
		}
		
		return false;
	}
	
	public static function logout(){
		Session::destroy();
		return self::shared()->logout();
	}
}