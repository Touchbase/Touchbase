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

class HTTPResponse extends HTTPHeaders
{

	//All Valid Headers
	protected $statusCodes = array (
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		426 => 'Upgrade Required',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended'
	);
	
	//Redirect Codes    
	protected $redirectCodes = array(
		301,
		302,
		303,
		304,
		305,
		307
	);
	
	protected $statusCode = 200;
	
	protected $statusDescription = "OK";
	
	protected $body = null;
	
	
	public function __construct($body = null, $statusCode = null, $statusDescription = null){
		$this->setBody($body);
		if(!empty($statusCode)){
			$this->setStatusCode($statusCode, $statusDescription);
		}
	}
	
	public function setStatusCode($statusCode, $statusDescription = null){
		if(isset($this->statusCodes[$statusCode])){
			$this->statusCode = $statusCode;
			$this->statusDescription = (!empty($statusDescription)?$statusDescription:$this->statusCodes[$statusCode]);
		}
		
		return $this;
	}
	
	public function getStatusCode(){
		return $this->statusCode;
	}
	
	public function output(){
		if(in_array($this->statusCode, $this->redirectCodes) && headers_sent($file, $line)) {
			//We want to redirect, Unfortunatly the headers have already been sent.	
			$url = $this->getHeader("Location");
			print '
			<p>Redirecting to <a href="'.$url.'" title="Please click this link if your browser does not redirect you">'.$url.'</a>
				(output started on '.$file.', line '.$line.')
			</p>
			<meta http-equiv="refresh" content="1; url='.$url.'" />
			<script type="text/javascript">
				setTimeout(\'window.location.href = "'.$url.'"\', 50);
			</script>';
		} else {

			if(!headers_sent()){
				header($_SERVER['SERVER_PROTOCOL']." ".$this->statusCode." ".$this->sanitize($this->statusDescription));
				foreach($this->getAllHeaders() as $header) {
					header($header, true, $this->statusCode);
				}
			}
						
			if(\Touchbase\Control\Router::isLive() && $this->isError() && !$this->body){
				print "ERROR: ".$this->statusCode." -> ".$this->sanitize($this->statusDescription);
			} else {
				print $this->body;
			}
		}

	}
	
	public function setBody($body){
		if(!empty($body)){
			if($body instanceof HTTPResponse){
				$this->body = $body->getBody();
			} else {
				$this->body = $body;
			}
			
			//There is an error in this. Could be todo with line endings
			//$this->setHeader('Content-Length', /* (function_exists('mb_strlen') ? mb_strlen($this->body,'8bit') : strlen($this->body)) */strlen($this->body));
		}
		
		return $this;
	}
	
	public function getBody(){
		return $this->body;
	}
	
	public function isError() {
		return $this->statusCode && ($this->statusCode < 200 || $this->statusCode > 399);
	}
	
	public function hasFinished(){
		return in_array($this->statusCode, array(301, 302, 401, 403));
	}
	
	public function redirect($destinationUrl, $statusCode = 302) {
		$statusCode = (in_array($statusCode, $this->redirectCodes)?$statusCode:302);
		$this->setStatusCode($statusCode);
		$this->addHeader('Location', $destinationUrl);
		
		return $this;
	}
	
	private function sanitize($str){
		return str_replace(array("\r","\n"), '', $str);
	}
}