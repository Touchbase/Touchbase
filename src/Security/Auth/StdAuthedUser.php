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
 
namespace Touchbase\Security\Auth;

defined('TOUCHBASE') or die("Access Denied.");

use \Touchbase\Data\Store;
use \Touchbase\Security\PermissionInterface;
use \Touchbase\Security\PermissionTrait;

class StdAuthedUser extends \Touchbase\Core\Object implements AuthedUserInterface, PermissionInterface
{
	use PermissionTrait;
	
	/**
	 *	@var string
	 */
	protected $_ID;
	
	/**
	 *	@var string
	 */
	protected $_username;
	
	/**
	 *	@var array
	 */
	protected $_userInfo;
	
	/* Public Methods */
	
	/**
	 *	__construct
	 *	@param string $ID
	 *	@param string $username
	 *	@param array $userInfo
	 */
	public function __construct($ID = null, $username = null, $userInfo = null){
		$this->_ID = $ID;
	    $this->_username = $username;
	    
	    if(!$userInfo instanceof Store){
			$this->_userInfo = new Store();
			if(is_array($userInfo)){
				$this->_userInfo->fromArray($userInfo);
			}
	    } else {
		    $this->_userInfo = $userInfo;
	    }
	}

	/**
	 *	ID
	 *	@return string
	 */
	public function ID(){
		return $this->_ID;
	}
	
	/**
	 *	Username
	 *	@return string
	 */
	public function username(){
		return $this->_username;
	}

	/**
	 *	User Info
	 *	@return \Touchbase\Data\Store
	 */
	public function userInfo(){
		return $this->_userInfo;
	}
	
	/**
	 *	Has Entered Credentials
	 *	@return BOOL
	 */
	public function hasEnteredCredentials(){
		return $this->_userInfo->get("entered_credentials", false);
	}
}