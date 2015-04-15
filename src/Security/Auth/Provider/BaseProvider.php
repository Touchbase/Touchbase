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
 *  @date 18/03/2014
 */
 
namespace Touchbase\Security\Auth\Provider;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Utils\Cookie;
use Touchbase\Security\Encryption;
use Touchbase\Security\AuthInterface;
use Touchbase\Security\Auth\StdAuthedUser;
use Touchbase\Security\Auth\AuthedUserInterface;
use Touchbase\Core\Config\ConfigTrait;
use Touchbase\Core\Config\Store as ConfigStore;

abstract class BaseProvider implements AuthInterface 
{
	use ConfigTrait;
	
	const LOGIN_COOKIE_KEY = "TOUCHBASE_TOKEN";
	
	/**
	 * @var int|string|\DateTime
	 */
	protected $_loginExpiry = 0; 
  
	/**
	 *	@var AuthedUserInterface
	 */
	protected $_user;	

	/**
	 *	Configure
	 *	@param ConfigStore
	 *	@return VOID
	 */
	public function configure(ConfigStore $config){
		$this->setConfig($config);
	}
	
	/**
	 *	Store User
	 *	@param AuthedUserInterface
	 *	@return BOOL
	 */
	public function storeUser(AuthedUserInterface $user){
		$this->_user = $user;
		
		$encryptedData = Encryption::encrypt(implode("|",[
			$user->ID(),
			$this->cookieHash($user),
			json_encode($user->userInfo())
		]));
		
		$cookieData = base64_encode(Encryption::encrypt(implode("|",[
			$user->username(),
			$encryptedData
		])));
		
		//Set Cookie;
		if(!Cookie::set(self::LOGIN_COOKIE_KEY, $cookieData, Cookie::ONE_DAY, WORKING_DIR, null, true)){
			\pre_r("Failed to set cookie");
		}
		
		return true;
	}
	
	/**
	 *	Retrieve User From Cookie
	 *	@return AuthedUserInterface
	 */
	public function retrieveUserFromCookie(){
		if(!Cookie::exists(self::LOGIN_COOKIE_KEY)) return NULL;
		
		try{
			list($username, $data) = explode('|', Encryption::decrypt(base64_decode(Cookie::get(self::LOGIN_COOKIE_KEY))), 2);
			list($id, $security, $userInfo) = explode('|', Encryption::decrypt($data), 3);
			
			$userInfo = json_decode($userInfo);
			$user = new StdAuthedUser($id, $username, $userInfo);
			
			if($security !== $this->cookieHash($user)){
				$user = NULL;
			}
		} catch(\Exception $e){
			$user = NULL;
		}
		
		return $user;
	}
}