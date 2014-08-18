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

use Touchbase\Core\Config\ConfigTrait;
use Touchbase\Control\Exception\HTTPResponseException;

class RequestHandler extends \Touchbase\Core\Object 
{
	use ConfigTrait;

	//Current Request
	protected $response = null;
	protected $request = null;	
	
	//Default Internal Url Handling
	protected $urlHandlers = array(
		'$Action' => '$Action'
	);
	
	//Default Action
	protected $defaultAction = null;
	
	//AllowedActions Declare
	protected $allowedActions = null;
	
	//Called on Each controller until request is handled
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
					
					//debug()->write("Testing '$rule' with '".$request->remaining()."' on '".$this->toString());
					//\pre_r("Testing '$rule' with '".$request->remaining()."' on '$this'");
					
					if($params = $request->match($rule)){
						//debug()->write("Rule '$rule' matched to action '$action' on ".$this->toString());
						//\pre_r("Rule '$rule' matched to action '$action' on '$this'");
						
						if($action[0] == "$"){
							$action = $params[substr($action, 1)];
						}
						
						//TODO: MOVE THIS
						$this->setParams($request->getUrlParams());
						
						if($this->checkAccessAction($action)){
							
							if(!$action){
								user_error("Action not set; using default action method name 'index'", E_USER_NOTICE);
								$action = 'index';
							} else if(!is_string($action)){
								user_error("Non-string method name: ".var_export($action, true), E_USER_ERROR);
							}
							
							try {
								if(!$this->hasMethod($action)){
									return $this->throwHTTPError(404, "Action '$action' isn't available on class $this");
								}
								
								//Call Action
								$result = $this->$action($request);
							} catch (HTTPResponseException $responseException){
								$result = $responseException->getResponse();
							}
							
						} else {
							return $this->throwHTTPError(403, "Action '$action' isn't allowed on class $this");
						}
/*
						//Check Response is Error
						if($result instanceof HTTPResponse && $result->isError()){
							debug()->setColor("red")->write("Rule resulted in HTTP error");
							return $result;
						}
						
						if($this !== $result && !$request->isEmptyPattern($rule) && is_object($result) && $result instanceof RequestHandler){
							$return = $result->handleRequest($request, $model);	
							
							//Unsure What This Does!
							if(is_array($return)){
							//	$return = $this->customise($return);
								debug()->setColor("red")->write(__METHOD__." got an array parsed");
							}
							
							return $return;
						} else if ($request->allParsed()){
							return $result;
						} else {
							return $this->throwHTTPError(404, "I can't handle sub-URLs of a $this object");
						}
						
*/
						return $result;
					}
				}
			}
			
			//Update $handlerClass
			$handlerClass = get_parent_class($handlerClass);
		}
		
		//Nothing matches.
		return $this;
	}
	
	//Check that the $Action can be called form a URL
	protected function checkAccessAction($action){
		//Always Allow Index!
		if($action == 'index') return true;

		//Save original action
		//$actionOriginalCasting = $action; Maybe?
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
					debug()->setColor("red")->write($test);
					if((bool) $test){
						//Case 1: TRUE should always allow access
						return true;
					} else if(substr($test, 0 , 2) == '->'){
						//This is todo with custom methods -> MAY NOT NEED
						//return $this->{substr($test, 2)}();
					} else {
						//Case 3: Check if user has permission
					}
				} else if((($key = array_search($actionOrAll, $allowedActions, true)) !== false) && is_numeric($key)){
					//Case 4: Allow numeric array notation (search for array value as action instead of key)
					return true;
				}
			}
		}
				
		debug()->setColor("red")->write(__METHOD__." got to end");
	}
	
	//Checks whether the request handler has the specific action!
	protected function hasAction($action){
		$action = strtolower($action);
		$allowedActions = $this->getAllowedActions();
		
		if(is_array($allowedActions)){
			$isKey = !is_numeric($action) && array_key_exists($action, $allowedActions);
			$isValue = in_array($action, array_map('strtolower', $allowedActions));
			
			if($isKey || $isValue){
				return true;
			}
		} else {
			debug()->setColor("red")->write($action." led here");
		}
		
		return false; 
	}
	
	//Merge AllowedActions Between Classes and Return Them!
	protected function getAllowedActions(){
		static $mergedAllowedActions = null;

		if(is_null($mergedAllowedActions)){		
			$parent = "$this";
			$subClasses = array();
	
			while($parent != __CLASS__){
				$parent = get_parent_class($parent);
				$subClasses[] = $parent;
			}
					
			foreach($subClasses as $className){
				$parentVar = get_class_vars($className);
				if(!empty($parentVar['allowedActions']) && $parentVar['allowedActions'] != $this->allowedActions){
					$this->allowedActions = array_merge((array)$this->allowedActions, (array)$parentVar['allowedActions']);
				}
			}
			$mergedAllowedActions = true;
		}
		
		return $this->allowedActions;
	}
	
	//Set Params to allow use of $this->urlParamName;
	protected function setParams($params){
		if(is_array($params)){
			foreach($params as $paramName => $paramValue){
				$this->$paramName = $paramValue;
			}
		}
	}
	
	//HTTP ERROR HELPER
	protected function throwHTTPError($errorCode, $errorMessage = null){
		user_error("Exception: ".$errorMessage, E_USER_ERROR);
	
		$e = HTTPResponseException::create($errorMessage, $errorCode);
		
		//Error responses should be considered plain text for security reasons.
		$e->getResponse()->setHeader('Content-Type', 'text/plain');
		
		throw $e;
	}


	//These are needed.
	public function __isset($property){
		return $this->hasProperty($property);
	}
	
	public function __set($property, $value){
		if($this->hasMethod($method = "set$property")) {
			$this->$property = $this->$method($value);
		} else {
			$this->$property = $value;
		}
	}
	
	public function __get($property){
		if($this->hasMethod($method = "$property")) {
			return $this->$method();
		} else {
			return $this->$property;
		}
	}
	
}

?>