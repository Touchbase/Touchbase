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
 *  @date 23/12/2013
 */
 
namespace Touchbase\Control;

defined('TOUCHBASE') or die("Access Denied.");

class HTTPHeaders extends \Touchbase\Core\BaseObject
{
	/**
	 *	@var array
	 */
	protected $headers = array(
		"Content-Type" => "text/html; charset=utf-8",
	);
	
	/* Public Methods */
	
	/**
	 *	Get All Headers
	 *	@return array
	 */	
	public function getAllHeaders() {
		$headers = [];
		foreach($this->manipulateHeaders() as $header => $value) {
			$headers[] = $header.": ".$value;
		}
		
		return $headers;
	}
	
	/**
	 *	Get Header
	 *	@param string $header
	 *	@return string
	 */
	public function getHeader($header) {
		return $this->manipulateHeaders($header);			
	}
	
	/**
	 *	Add Headers
	 *	@param array $headers
	 *	@return BOOL
	 */
	public function addHeaders($headers){
		if(is_array($headers)){
			foreach($headers as $k => $v){
				$this->manipulateHeaders($k, $v);
			}
			return true;
		}
		return false;
	}
	
	/**
	 *	Add Header
	 *	@param string $header
	 *	@param string $value
	 *	@return \Touchbase\Control\HTTPHeaders
	 */
	public function addHeader($header, $value){
		return $this->manipulateHeaders($header, $value);
	}
	
	/**
	 *	Remove Header
	 *	@param string $header
	 *	@return BOOL
	 */
	public function removeHeader($header) {
		return $this->manipulateHeaders($header, "remove");
	}
	
	/* Private Methods */
	
	/**
	 *	Manipulate Headers
	 *	This is the interal method that handles the header configuration
	 *	@param array | string $name 
	 *	@param string | false $set
	 *	@return mixed
	 */
	private function manipulateHeaders($name = false, $set = false){
		if(!empty($name) && !empty($set)){
			//Set or Delete
			if($set == "remove"){
				if(isset($this->headers[$name])){
					unset($this->headers[$name]);
				}
			} else {
				$this->headers[$name] = $set;
			}
			
			return $this;
		} else if(!empty($name)){
			//Get one
			return (isset($this->headers[$name])) ? $this->headers[$name] : false;
		}
		
		return $this->headers;
	}

}