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
 *	@subcategory Session
 *  @date 23/12/2013
 */
 
namespace Touchbase\Control\Session\Provider;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Control\Session\SessionInterface;
use Touchbase\Core\Config\Store as ConfigStore;

class PHPSessionProvider implements SessionInterface
{
	
	/* Public Methods */
	
	/**
	 *	Configure
	 *	@param \Touchbase\Core\Config\Store $config
	 *	@return VOID
	 */
	public function configure(ConfigStore $config){
		$this->init();
	}

	/**
	 *	Init
	 *	@return $this
	 */
	public function init(){
		ini_set('session.use_cookies', 1);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.use_trans_sid', 0);
		ini_set('session.cookie_httponly', 1);
		
		session_set_cookie_params(0, WORKING_DIR);
		session_start();
		
		if(!isset($_SESSION['touchbase'])){
			$_SESSION['touchbase'] = array();
		}
		
		return $this;
	}
	
	/**
	 *	ID
	 *	@return $this
	 */
	public function ID(){
		return session_id();
	}
	
	/**
	 *	Regenerate ID
	 *	@return $this
	 */
	public function regenerateID(){
		session_regenerate_id(true);
		
		return $this;
	}
	
	/**
	 *	Get
	 *	@param string $key
	 *	@return mixed|null
	 */
	public function get($key, $default = null){
		return $this->exists($key) ? $_SESSION['touchbase'][$key] : $default;
	}
	
	/**
	 *	Set
	 *	@param string $key
	 *	@param mixed  $data
	 *	@return $this
	 */
	public function set($key, $data){
		$_SESSION['touchbase'][$key] = $data;
		
		return $this;
	}
	
	/**
	 *	Flash
	 *	@param string $key
	 *	@param mixed  $data
	 *	@return $this
	 */
	public function flash($key, $data){
		$_SESSION['touchbase.flash'][$key] = $data;
		
		return $this;
	}
	
	/**
	 *	Delete
	 *	@param string $key
	 *	@return $this
	 */
	public function delete($key){
		unset($_SESSION['touchbase'][$key]);
		
		return $this;
	}
	
	/**
	 *	Exists
	 *	@param string $key
	 *	@return bool
	 */
	public function exists($key){
		return isset($_SESSION['touchbase'][$key]);
	}
	
	/**
	 *	Destroy
	 *	@return $this
	 */
	public function destroy(){
		unset($_SESSION['touchbase']);
		
		return $this;
	}
}