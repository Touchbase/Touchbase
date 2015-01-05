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
 
namespace Touchbase\Control\Exception;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Control\HTTPResponse;

class HTTPResponseException extends \Exception
{
	
	protected $_response;
	
	/**
	 *	@see HTTPResponse::__construct();
	 */
	public function __construct($body = null, $statusCode = null, $statusDescription = null) {
		if($body instanceof HTTPResponse) {
			$this->setResponse($body);
		} else {
			$this->setResponse(new HTTPResponse($body, $statusCode, $statusDescription));
		}
		
		parent::__construct($this->getResponse()->getBody(), $this->getResponse()->getStatusCode());
	}
	
	/**
	 *	@return HTTPResponse
	 */
	public function getResponse() {
		return $this->_response;
	}
	
	/**
	 *	@param HTTPResponse $response
	 */
	public function setResponse(HTTPResponse $response) {
		$this->_response = $response;
	}
	
}


?>