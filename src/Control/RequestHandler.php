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

use Touchbase\Security\Auth;
use Touchbase\Core\Config\ConfigTrait;
use Touchbase\Control\Exception\HTTPResponseException;

class RequestHandler extends \Touchbase\Core\Object 
{
	use ConfigTrait;

	/**
	 *	@var \Touchbase\Control\HTTPResponse
	 */
	protected $response = null;
	
	/**
	 *	@var \Touchbase\Control\HTTPRequest
	 */
	protected $request = null;	
	
	/**
	 *	Default Internal Url Handling
	 *	@var array
	 */
	protected $urlHandlers = array(
		'$Action' => '$Action'
	);
	
	/**
	 *	@var string | null
	 */
	protected $defaultAction = null;
	
	/**
	 *	@var array  | null
	 */
	protected $allowedActions = null;
	
	/* Public Methods */
	
	/**
	 *	Handle Request
	 *	Called on Each controller until request is handled
	 *	@param \Touchbase\Control\HTTPRequest &$request
	 *	@param \Touchbase\Control\HTTPResponse &$response
	 *	@return mixed $result
	 */
	public function handleRequest(HTTPRequest &$request, HTTPResponse &$response){

		$handlerClass = "$this";
		while($handlerClass != __CLASS__){
			$getClassUrlHandler = get_class_vars($handlerClass);
			$urlHandlers = $getClassUrlHandler['urlHandlers'];
			
			if(!empty($urlHandlers)){
				
				foreach($urlHandlers as $rule => $action){
					if(is_numeric($rule)){
						$rule = $action;
						$action = $this->defaultAction;
					}
					//\pre_r("Testing '$rule' with '".$request->remaining()."' on '$this'");
					
					if($params = $request->match($rule)){
						//\pre_r("Rule '$rule' matched to action '$action' on '$this'");
						
						if($action[0] == "$"){
							$action = $params[substr($action, 1)];
						}
						
						if(!$action){
							user_error("Action not set; using default action method name 'index'", E_USER_NOTICE);
							$action = 'index';
						} else if(!is_string($action)){
							user_error("Non-string method name: ".var_export($action, true), E_USER_ERROR);
						}
						
						if(!$this->hasMethod($action)){
							return $this->throwHTTPError(404, "Action '$action' isn't available on class $this");
						}
						
						if(!$this->isAllowed() || !$this->checkAccessAction($action)){	
							return $this->throwHTTPError(403, "Action '$action' isn't allowed on class $this");
						}
						
						if(!$request->allParsed()){
							return $this->throwHTTPError(404, "I can't handle sub-URLs of a $this object");
						}
						
						//Set Controller Vars and Call Action
						$this->setParams($request->urlParams());
						
						//Call Controller Init
						$this->init();
						if(!$this->baseInitCalled){
							user_error("init() method on class '$this' doesn't call Controller::init(). Make sure that you have parent::init() included.", E_USER_WARNING);
						}
						
						return $this->$action($request);
					}
				}
			}
			
			//Update $handlerClass
			$handlerClass = get_parent_class($handlerClass);
		}
		
		//Nothing matches.
		return $this;
	}
	
	/* Protected Methods */
	
	/**
	 *	Check Access Action
	 *	Check that the $Action can be called form a URL
	 *	@param string $action
	 *	@return BOOL
	 */
	protected function checkAccessAction($action){
		if($action == 'handleAction') return true;

		//Save original action
		$action = strtolower($action);
		$allowedActions = $this->getAllowedActions();
		if(!empty($allowedActions)){
			//Normalise Array
			$allowedActions = array_change_key_case($allowedActions, CASE_LOWER);
			foreach($allowedActions as $key => $value){
				if(is_int($key)){
					$allowedActions[$key] = strtolower($value);
				}
			}	

			//Check for specific action rules first, and fall back to global rules defined by asterisk!
			foreach(array($action, '*') as $actionOrAll){
				//Check if specific action is set:
				if(isset($allowedActions[$actionOrAll])){
					$test = $allowedActions[$actionOrAll];
					if($test === true){
						//Case 1: TRUE should always allow access
						return true;
					} else if(substr($test, 0 , 2) == '->'){
						//Fire a custom method to determine if access is allowed
						return $this->{substr($test, 2)}();
					} else if($test == '::isAuthenticated'){
						return Auth::isAuthenticated();
					} else {
						//Case 4: Check if user has permission
						return Auth::isAuthenticated() && Auth::currentUser()->can($test);
					}
				} else if((($key = array_search($actionOrAll, $allowedActions, true)) !== false) && is_numeric($key)){
					//Case 5: Allow numeric array notation (search for array value as action instead of key)
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 *	Has Action
	 *	Checks whether the request handler has the specific action!
	 *	@param string $action
	 *	@return BOOL
	 */
	protected function hasAction($action){
		$action = strtolower($action);
		$allowedActions = $this->getAllowedActions();
		
		if(is_array($allowedActions)){
			$isKey = !is_numeric($action) && array_key_exists($action, $allowedActions);
			$isValue = in_array($action, $allowedActions);
			
			if($isKey || $isValue){
				return $this->hasMethod($action);
			}
		}
		
		return false; 
	}
	
	/**
	 *	Get Allowed Actions
	 *	Merge AllowedActions Between Classes and Return Them!
	 *	@return VOID
	 */
	protected function getAllowedActions(){
		static $mergedAllowedActions = null;

		if(is_null($mergedAllowedActions)){		
			$parent = "$this";
			
			while($parent != __CLASS__){
				$parent = get_parent_class($parent);
				
				$allowedActions = (array) get_class_vars($parent)['allowedActions'];
				$this->allowedActions = array_merge((array)$this->allowedActions, $allowedActions);
			}
			$this->allowedActions = array_map("strtolower", $this->allowedActions);
			
			$mergedAllowedActions = true;
		}
		
		return $this->allowedActions;
	}
	
	/**
	 *	Set Params
	 *	Set Params to allow use of $this->urlParamName;
	 *	@param array $params
	 *	@return VOID
	 */
	protected function setParams(array $params){
		if(is_array($params)){
			foreach($params as $paramName => $paramValue){
				$this->$paramName = urldecode(filter_var($paramValue, FILTER_SANITIZE_STRING));
			}
		}
	}
	
	/**
	 *	Throw HTTP Error
	 *	@param int $erroCode
	 *	@param string $errorMessage
	 *	@throws \Touchbase\Control\Exception\HTTPResponseException
	 *	@return VOID
	 */
	protected function throwHTTPError($errorCode, $errorMessage = null){
		$e = new HTTPResponseException($errorMessage, $errorCode);
		
		//Error responses should be considered plain text for security reasons.
		$e->response()->setHeader('Content-Type', 'text/plain');
		
		throw $e;
	}
}

?>