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
 *  @date 26/03/2014
 */
 
namespace Touchbase\Utils;

use Touchbase\Data\StaticStore;

class SystemDetection extends \Touchbase\Core\Object
{
	/**
	 *	Browser Information
	 *	@var (String) - browser
	 *	@var (String) - browserVersion
	 */
	public $browser;
	public $browserVersion;
	
	/**
	 *	OS Information
	 *	@var (String) - os
	 *	@var (String) - osVersion
	 */
	public $os;
	public $osVersion;
	
	/**
	 *	Browser Information
	 *	@var (String) - platform
	 *	@var (String) - device
	 */
	public $platform;
	public $device;
	
	const DETECTION_KEY = 'touchbase.key.systemdetection';
	
	/** 
	 *	Shared
	 *	@return \Touchbase\Utils\SystemDetection
	 */
	public static function shared(){
		$instance = StaticStore::shared()->get(self::DETECTION_KEY, false);
		if(!$instance || is_null($instance)){
			$instance = new self();
			$instance->findBrowserInformation();
			StaticStore::shared()->set(self::DETECTION_KEY, $instance);
		}
		
		return $instance;
	}
	
/*
	public function compareBrowserInformation($check, $version, $operator = false){
		$browser = $this->findBrowserInformation();

		if(isset($browser[$check])){
			if(strpos($check, "version") !== false){
				return version_compare($browser[$check], $version, $operator);
			} else {
				switch($operator){
					case "!=":
					case "<>":
					case "ne":
						return $browser[$check] != $version;
					break;
					case "==":
					case "=":
					case "eq":
					default:
						return $browser[$check] == $version;
					break;
				}
			}
		}
	}
*/

	private function findBrowserInformation(){
		$browser = $version = $platform = $os = $osversion = $device = '';
		$userAgent = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"";
		if(!empty($userAgent)){	
			//Broswer Detection
			preg_match_all("/(Opera|Chrome|Version|Firefox|MSIE)[\/|\s](\d+(?:[\_|\.]\d+){1,2})\b/", $userAgent, $matches, PREG_SET_ORDER);
			if(!empty($matches)){
				list($browser, $version) = array($matches[0][1], str_replace(".","_",$matches[0][2]));
				$browser = ($browser=="Version")?"Safari":$browser;
				$browser = ($browser=="MSIE")?"IE":$browser;
				$browser = strtolower($browser);
			}
			
			//Platform Detection
			if( stripos($userAgent, 'windows') !== false ) {
				$platform = 'windows';
				$os = 'windows';
				//Lets Try To Refine
				if(preg_match('/(Windows 95|Win95|Windows_95)/i', $userAgent)){
					$osversion = '95';
				}else if(preg_match('/(Windows 98|Win98)/i', $userAgent)){
					$osversion = '98';
				}else if(preg_match('/(Windows NT 5.0|Windows 2000)/i', $userAgent)){
					$osversion = '2000';
				}else if(preg_match('/(Windows NT 5.1|Windows XP)/i', $userAgent)){
					$osversion = 'XP';
				}else if(stripos($userAgent, 'Windows NT 5.2') !== false){
					$osversion = '2003';
				}else if(preg_match('/(Windows NT 6.0|Windows Vista)/i', $userAgent)){
					$osversion = 'Vista';
				}else if(preg_match('/(Windows NT 6.1|Windows 7)/i', $userAgent)){
					$osversion = '7';
				}else if(preg_match('/(Windows NT 6.2|Windows 8)/i', $userAgent)){
					$osversion = '8';
				}else if(preg_match('/(Windows NT 4.0|WinNT4.0|WinNT|Windows NT)/i', $userAgent)){
					$osversion = 'NT';
				}else if(stripos($userAgent, 'Windows ME') !== false){
					$osversion = 'ME';
				}
			} else if( stripos($userAgent, 'iPad') !== false ) {
				$platform = 'iPad';
				$os = "iOS";
				if(preg_match('/[\bOS|\biOS] (\d+(?:\_\d+){1,2})\b/i', $userAgent, $matches)){
					$osversion = $matches[1];
				}
			} else if( stripos($userAgent, 'iPod') !== false ) {
				$platform = 'iPod';
				$os = "iOS";
				if(preg_match('/[\bOS|\biOS] (\d+(?:\_\d+){1,2})\b/i', $userAgent, $matches)){
					$osversion = $matches[1];
				}
			} else if( stripos($userAgent, 'iPhone') !== false ) {
				$platform = 'iPhone';
				$os = "iOS";
				if(preg_match('/[\bOS|\biOS] (\d+(?:\_\d+){1,2})\b/i', $userAgent, $matches)){
					$osversion = $matches[1];
				}
			} elseif( stripos($userAgent, 'mac') !== false ) {
				$platform = 'macintosh';
				
				//Mac OS Version
				if(preg_match('/\bOS X (\d+(?:[\_|\.]\d+){1,2})\b/i', $userAgent, $matches)){
					$osversion = str_replace(".","_",$matches[1]);
				}
				
				//Mac OS Name
				if(stripos($osversion, '10_3') !== false){
					$os = 'panther';
				}else if(stripos($osversion, '10_4') !== false){
					$os = 'tiger';
				}else if(stripos($osversion, '10_5') !== false){
					$os = 'leopard';
				}else if(stripos($osversion, '10_6') !== false){
					$os = 'snowLeopard';
				}else if(stripos($osversion, '10_7') !== false){
					$os = 'lion';
				}else if(stripos($osversion, '10_8') !== false){
					$os = 'mountainLion';
				}else if(stripos($osversion, '10_9') !== false){
					$os = 'mavericks';
				}
			} elseif( stripos($userAgent, 'android') !== false ) {
				$platform = 'android';
				$os = "android";
				if(preg_match('/\bAndroid (\d+(?:\.\d+){1,2})[;)]/i', $userAgent, $matches)){
					$osversion = str_replace(".","_",$matches[1]);
				}
			} elseif( stripos($userAgent, 'linux') !== false ) {
				$platform = 'linux';
			} else if( stripos($userAgent, 'Nokia') !== false ) {
				$platform = 'nokia';
			} else if( stripos($userAgent, 'BlackBerry') !== false ) {
				$platform = 'blackBerry';
			} elseif( stripos($userAgent,'FreeBSD') !== false ) {
				$platform = 'freeBSD';
			} elseif( stripos($userAgent,'OpenBSD') !== false ) {
				$platform = 'openBSD';
			} elseif( stripos($userAgent,'NetBSD') !== false ) {
				$platform = 'netBSD';
			} elseif( stripos($userAgent, 'OpenSolaris') !== false ) {
				$platform = 'openSolaris';
			} elseif( stripos($userAgent, 'SunOS') !== false ) {
				$platform = 'sunOS';
			} elseif( stripos($userAgent, 'OS\/2') !== false ) {
				$platform = 'oS/2';
			} elseif( stripos($userAgent, 'BeOS') !== false ) {
				$platform = 'BeOS';
			} elseif( stripos($userAgent, 'win') !== false ) {
				$platform = 'windowsCE';
			} elseif( stripos($userAgent, 'QNX') !== false ) {
				$platform = 'QNX';
			} elseif( preg_match('/(nuhk|Googlebot|Yammybot|Openbot|Slurp\/cat|msnbot|ia_archiver)/i', $userAgent) ) {
				$platform = 'spider';
			}
			
			//Device Detection
			$GenericPhones = array('acs-', 'alav', 'alca', 'amoi', 'audi', 'aste', 'avan', 'benq', 'bird', 'blac', 'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno', 'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-', 'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-', 'newt', 'noki', 'opwv', 'palm', 'pana', 'pant', 'pdxg', 'phil', 'play', 'pluc', 'port', 'prox', 'qtek', 'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar', 'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-', 'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'w3c ', 'wap-', 'wapa', 'wapi', 'wapp', 'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-');
			
			//Default Device
			$device = "desktop";
			
			if(!in_array($platform, array("apple", "windows", "iPad"))){
				if(preg_match('/up.browser|up.link|windows ce|iemobile|mini|mmp|symbian|midp|wap|phone|pocket|mobile|pda|psp/i',$userAgent) || 
					isset($_SERVER['HTTP_ACCEPT']) && stristr($_SERVER['HTTP_ACCEPT'],'text/vnd.wap.wml')||stristr($_SERVER['HTTP_ACCEPT'],'application/vnd.wap.xhtml+xml') ||
					isset($_SERVER['HTTP_X_WAP_PROFILE'])||isset($_SERVER['HTTP_PROFILE'])||isset($_SERVER['X-OperaMini-Features'])||isset($_SERVER['UA-pixels']) ||
					isset($GenericPhones[substr($userAgent,0,4)])){
					
					$device = "mobile";
				}
			}
		}
		
		$this->browser = $browser;
		$this->browserVersion = $version;
		$this->os = $os;
		$this->osVersion = $osversion;
		$this->platform = $platform;
		$this->device = $device;
	}
}