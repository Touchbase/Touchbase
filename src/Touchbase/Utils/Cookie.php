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
 *  @category Utils
 *  @date 23/12/2013
 */
 
namespace Touchbase\Utils;

defined('TOUCHBASE') or die("Access Denied.");

class Cookie extends \Touchbase\Core\Object {

	const ONE_DAY = 86400;
	const SEVEN_DAYS = 604800;
	const THIRTY_DAYS = 2592000;
	const SIX_MONTHS = 15811200;
	const ONE_YEAR = 31536000;
	const LIFETIME = 1893456000; // 2030-01-01 00:00:00

	public static function set($name, $value, $expiry = self::ONE_YEAR, $path = '/', $domain = null, $httponly = false){
		if(headers_sent()) return;
	
		if(empty($name)) return;
		
		if(empty($value)){
			$expiry = -1;
		} else {
			if(is_numeric($expiry)){
				$expiry += time();
			} else {
				$expiry = \DateTime($expiry);
			}
		}
		
		if(empty($domain)){
			$domain = '.' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
		}
		
		$secure = SITE_PROTOCOL == 'https://';
		
		return setcookie($name, $value, $expiry, $path, $domain, $secure, $httponly);
	}
	
	public static function get($name){
		return @$_COOKIE[$name];	
	}
	
	public static function delete($name){
		return self::set($name, '');
	}
	
	public static function exists($name){
		return array_key_exists($name, $_COOKIE);
	}

}