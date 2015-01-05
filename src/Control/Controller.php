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

class Controller extends RequestHandler
{
	
	protected $controllerStack = array();
	
	protected $urlHandlers = [
		'$Action/$ID/$OtherId'
	];
	
	//Default Action
	protected $defaultAction = 'handleAction';
	
	//Allowed Actions
	protected $allowedActions = [
		'handleAction',
		'index'
	];
	
	//A Helper function to check if all Inits are called on child classes.
	protected $baseInitCalled = false;
	protected function init(){
		$this->baseInitCalled = true;	
	}
	
	//Calls this controll and returns a HTTPResponse Object
	public function handleRequest(HTTPRequest &$request, HTTPResponse &$response){	
	
		//Set Request/Response Into Var
		$this->request = &$request;
		$this->response = &$response;
	
		//Init Controllers
		$this->init();
		if(!$this->baseInitCalled){
			user_error("init() method on class '$this' doesn't call Controller::init(). Make sure that you have parent::init() included.", E_USER_WARNING);
		}
	
		//If we had a redirection, cancel.
		if($response->hasFinished()){
			return $response;
		}
		
		//Pass through to RequestHandler
		$body = parent::handleRequest($request, $response);
				
		if($body instanceof HTTPResponse){
			$response = $body;
		} else {
			$response->setBody($body);
		}
		
		return $body;
	}
	
	//Proesses $Action and call the corosponding method if exists.
	protected function handleAction(HTTPRequest $request){
		$action = !empty($this->Action)?str_replace("-", "_", $this->Action):'index';
		
		//Check the action Exists
		if(!$this->hasAction($action)){
			$this->throwHTTPError(404, "The action '".$action."' does not exist in class $this");
		}
		
		//Check We Have Access
		if(!$this->checkAccessAction($action) || in_array(strtolower($action), array('run','init'))){
			$this->throwHTTPError(403, "The action '".$action."' isn't allowed on class $this");
		}

		return $this->{$action}($request);
	}
	
	/**
	 *	Template
	 *	Helper function
	 *	@return (\Touchbase\View\Template)
	 */
	public function template(array $templateArgs = NULL){
		return \Touchbase\View\Template::create($templateArgs);
	}
}

?>