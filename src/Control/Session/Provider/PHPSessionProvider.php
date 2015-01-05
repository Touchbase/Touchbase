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
	public function configure(ConfigStore $config){
		$this->init();
	}

	/**
	 * @return $this
	 */
	public function init(){
		session_start();
		if(!isset($_SESSION['touchbase'])){
		  $_SESSION['touchbase'] = array();
		}
		
		return $this;
	}
	
	public function id(){
		return session_id();
	}
	
	/**
	 * @return $this
	 */
	public function regenerateId(){
		session_regenerate_id();
		
		return $this;
	}
	
	/**
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function get($key){
		return $this->exists($key) ? $_SESSION['touchbase'][$key] : null;
	}
	
	/**
	 * @param string $key
	 * @param mixed  $data
	 *
	 * @return $this
	 */
	public function set($key, $data){
		$_SESSION['touchbase'][$key] = $data;
		
		return $this;
	}
	
	/**
	 * @param string $key
	 *
	 * @return $this
	 */
	public function delete($key){
		unset($_SESSION['touchbase'][$key]);
		
		return $this;
	}
	
	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function exists($key){
		return isset($_SESSION['touchbase'][$key]);
	}
	
	/**
	 * @return $this
	 *
	 */
	public function destroy(){
		unset($_SESSION['touchbase']);
		
		return $this;
	}
}