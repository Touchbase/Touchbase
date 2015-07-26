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

final class StaticStore
{
	/**
	 *	Static In Memory Store
	 *	@var \Touchbase\Data\StaticStore	
	 */
	private static $instance;
	
	/* Public Methods */
	
	/**
	 *	Shared
	 *	@return \Touchbase\Data\StaticStore
	 */
	public static function shared(){
		if(!static::$instance){
			static::$instance = new Store();
		}
		
		return static::$instance;
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
	 * NO-OP
	 */
	final public function __construct(){}
	final protected function __clone(){}
}