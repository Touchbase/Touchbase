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
 *  @date 17/03/2014
 */
 
namespace Touchbase\Security\Auth\Provider;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Utils\Cookie;
use Touchbase\Security\AuthInterface;
use Touchbase\Security\Auth\AuthedUserInterface;
use Touchbase\Control\Session;

class CookieProvider extends BaseProvider 
{
	
	/* Public Methods */
	
	/**
	 *	Store User
	 *	@param AuthedUserInterface
	 *	@return BOOL
	 */
	public function storeUser(AuthedUserInterface $user){
		return parent::storeUser($user);
	}
	
	/**
	 *	Retrieve User From Cookie
	 *	@return AuthedUserInterface
	 */
	public function retrieveUserFromCookie(){
		return parent::retrieveUserFromCookie();
	}
	
	/**
	 *	Logout
	 *	@return BOOL
	 */
	public function logout(){
		if(!Cookie::delete(self::LOGIN_COOKIE_KEY)){
			\pre_r("Failed to delete cookie");
		}
	}
	
	/**
	 *	Cookie Hash
	 *	@return string
	 */
	public function cookieHash(AuthedUserInterface $user){
		$salt = $this->config()->get("auth")->get("cookie_hash", "8)pu1j[Juogi5263N6sS5s+fE5V/kw");
		return sha1($user->ID().$salt.Session::ID());
	}
}