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

use Touchbase\Filesystem\Filesystem;
use Touchbase\Data\Store;
use Touchbase\Data\SessionStore;
use Touchbase\Control\Exception\HTTPResponseException;

class Controller extends RequestHandler
{	
	/**
	 *	@var array
	 */
	protected $urlHandlers = [
		'$Action/$ID/$OtherID'
	];
	
	/**
	 *	@var string
	 */
	protected $defaultAction = 'handleAction';
	
	/**
	 *	@var array
	 */
	protected $allowedActions = [
		'handleAction',
		'index'
	];
	
	/**
	 *	@var string
	 */
	protected $_controllerName;
	
	/**
	 *	@var \Touchbase\Control\application
	 */
	protected $_application;
	
	/**
	 *	@var string
	 */
	protected $_applicationPath;
	
	/**
	 *	@var array
	 */
	protected $_errors;
	
	/**
	 *	A Helper function to check if all Inits are called on child classes.
	 *	@var BOOL
	 */
	protected $baseInitCalled = false;
	
	/* Public Methods */
	
	/**
	 *	Is Allowed
	 *	This is a skeleton method that can be overriden by children to protect an entire class.
	 *	NB. Returning false for this function will throw a HTTP 403 error.
	 *	@return BOOL
	 */
	public function isAllowed(){
		return true;
	}
	
	/**
	 *	Request
	 *	@return \Touchbase\Control\HTTPRequest
	 */
	public function request(){
		return $this->request;
	}
	
	/**
	 *	Handle Request
	 *	Calls this controller and returns a HTTPResponse Object
	 *	@param \Touchbase\Control\HTTPRequest &$request
	 *	@param \Touchbase\Control\HTTPResponse &$response
	 *	@return string - The outgoing HTML
	 */
	public function handleRequest(HTTPRequest &$request, HTTPResponse &$response){	
	
		//Set Request/Response Into Var
		$this->request = &$request;
		$this->response = &$response;
	
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
	
	/**
	 *	Handle Exception
	 *	By default this function will just print the error. Override this function to change the behaviour
	 *	@return string
	 */
	public function handleException(HTTPResponseException $exception){
		return $this->application()->handleException($exception);
	}
	
	/**
	 *	Errors
	 *	@return \Touchbase\Data\Store
	 */
	public function errors($key = null){
		$errors = SessionStore::get("errors", new Store());
		if(isset($key)){
			return $errors->get($key);
		}
		return $errors;
	}
	
	/**
	 *	Template
	 *	Helper function
	 *	@return (\Touchbase\View\Template)
	 */
	public function template(array $templateArgs = NULL){
		return \Touchbase\View\Template::create($templateArgs)->setController($this);
	}
	
	/* Getters / Setter */
	
	/**
	 *	Set Application
	 *	@param \Touchbase\Control\Application $application
	 *	@return \Touchbase\Control\Controller self
	 */
	public function setApplication(Application $application){
		$this->_application = $application;
		return $this;
	}
	
	/**
	 *	Application
	 *	@return \Touchbase\Control\Application
	 */
	public function application(){
		return $this->_application;	
	}
	
	/**
	 *	Set Controller Name
	 *	This method provides the ability to set the controller name meaning reflection can be skipped.
	 *	@param string $controllerName
	 *	@return \Touchbase\Control\Controller self
	 */
	public function setControllerName($controllerName){
		$this->_controllerName = $controllerName;
		return $this;
	}
	
	/**
	 *	Controller Name
	 *	@return string - The name of the current controller
	 */
	public function controllerName(){
		if(isset($this->_controllerName)){
			return $this->_controllerName;
		}
		
		$reflector = new \ReflectionClass($this);
		$controllerName = $reflector->getShortName();
		
		if((($pos = strrpos($controllerName, $suffix = "Controller")) !== false)){
			$controllerName = substr_replace($controllerName, "", $pos, strlen($suffix));
		}
		
		return $this->_controllerName = $controllerName;
	}
	
	/**
	 *	Application Path
	 *	@return string - The filepath to the current application directory
	 */
	public function applicationPath(){
		if(isset($this->_applicationPath)){
			return $this->_applicationPath;
		}
		
		$reflector = new \ReflectionClass($this);
		$controllerNamespace = $reflector->getNamespaceName();
		$controllerNamespace = ltrim(strstr($controllerNamespace, $needle = "\\") ?: $controllerNamespace, $needle);
		
		$applicationNamespace = str_replace("\\Controllers", "", $controllerNamespace);
		return $this->_applicationPath = Filesystem::buildPath(PROJECT_PATH, str_replace("\\", DIRECTORY_SEPARATOR, $applicationNamespace));
	}
	
	/* Protected Methods */
	
	/**
	 *	Init
	 *	This method will be called after the class initialises.
	 *	NB. If super isn't called on subsequent child methods, an error will be thrown.
	 *	@return \Touchbase\Control\Controller self
	 */
	protected function init(){
		$this->baseInitCalled = true;
		
		return $this;
	}
	
	/**
	 *	Handle Action
	 *	Proesses $Action and call the corosponding method if exists.
	 *	@param \Touchbase\Control\HTTPRequest $request
	 *	@return mixed
	 */
	protected function handleAction(HTTPRequest $request){
		$action = !empty($this->Action)?str_replace("-", "_", $this->Action):'index';
		
		//Check the action Exists
		if(!$this->hasAction($action)){
			$this->throwHTTPError(404, "The action '".$action."' does not exist in class $this");
		}
		
		//Check We Have Access
		if(!$this->checkAccessAction($action) || in_array(strtolower($action), ['run', 'init'])){
			$this->throwHTTPError(403, "The action '".$action."' isn't allowed on class $this");
		}
		
		return $this->{$action}($request);
	}
}

?>