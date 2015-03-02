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
 *  @date 27/12/2013
 */
 
namespace Touchbase\Control;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\View\Webpage;

class WebpageController extends Controller
{
	/**
	 *	@var \Touchbase\View\Webpage
	 */
	private $_webpage;
	
	/* Public Methods */
	
	public function init(){
		parent::init();
		
		//Create Webpage.
		$this->_webpage = new Webpage($this);
	}
	
	/**
	 *	Handle Request
	 *	@param HTTPRequest &$request
	 *	@param HTTPResponse &$response
	 *	@return string
	 */
	public function handleRequest(HTTPRequest &$request, HTTPResponse &$response){	
		
		//Pass through to Controller
		$body = parent::handleRequest($request, $response);
			
		if($body instanceof \Touchbase\Control\HTTPResponse){
			return $body;
		}
		
		if(isset(static::$name)){
			$this->_webpage->assets->pushTitle(static::$name);
		}
		
		//Set Webpage Response
		$this->_webpage->setBody($body);
		$this->response->setBody($this->_webpage->output());
		
		return $body;
	}
	
	/**
	 *	Webpage
	 *	@return \Touchbase\View\Webpage
	 */
	public function webpage(){
		return $this->_webpage;
	}

}