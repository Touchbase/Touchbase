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
use Touchbase\Control\Router;
use Touchbase\Control\Session;
use Touchbase\Utils\Validation;
use Touchbase\Data\Store;
use Touchbase\Data\SessionStore;

class WebpageController extends Controller
{
	/**
	 *	@var \Touchbase\View\Webpage
	 */
	private $_webpage;
	protected $_errors;
	
	/* Public Methods */
	
	/**
	 *	Handle Request
	 *	@param HTTPRequest &$request
	 *	@param HTTPResponse &$response
	 *	@return string
	 */
	public function handleRequest(HTTPRequest &$request, HTTPResponse &$response){	
		
		if($request->isPost()){
			$validation = Validation::create();
			$this->validatePostRequest($request, $response, $validation);
		}
	
		//Pass through to Controller
		$body = parent::handleRequest($request, $response);
		
		if($request->isAjax()){
			return $body;
		}
			
		if($body instanceof \Touchbase\Control\HTTPResponse){
			return $body;
		}
		
		if(isset(static::$name)){
			$this->webpage()->assets->pushTitle(static::$name);
		}
		
		//Set Webpage Response
		$this->webpage()->setBody($body);
		
		//Set Response
		$htmlDocument = $this->webpage()->output();
		$this->validateHtml($htmlDocument);
		$this->response->setBody($htmlDocument);
		
		return $body;
	}
	
	/**
	 *	Webpage
	 *	@return \Touchbase\View\Webpage
	 */
	public function webpage(){
		if(!$this->_webpage){
			//Create Webpage.
			$this->_webpage = new Webpage($this);
		}
		
		return $this->_webpage;
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
	
	/* Protected Methods */
	
	protected function validatePostRequest($request, &$response, &$validation){
		
		$data = $request->_VARS();
		//Reverse Loop - Submit buttons are usually at the bottom.
		for(end($data); ($haystack=key($data)) !==null; prev($data)){
			if(stripos($haystack, $needle = "submit_") !== false){
				$formName = substr($haystack, strlen($needle));
				break;
			}
		}
		
		if(isset($formName) && $form = SessionStore::get($formName, false)){
			libxml_use_internal_errors(true);
			$dom = new \DOMDocument;
			$dom->loadHtml(gzinflate(base64_decode($form)), LIBXML_NOWARNING | LIBXML_NOERROR);
			
			$formValidation = Validation::create($formName);
			
			$privateFields = [];
			foreach($dom->getElementsByTagName('input') as $input){
				if($input->hasAttributes()){
					$inputName = $input->getAttribute("name");
					$inputType = $input->getAttribute("type");
					$inputValidation = Validation::create($inputName);
					
					if($inputType === "password"){
						$privateFields[] = $inputName;
					}
					
					$inputValidation->type($inputType);
					
					if($input->hasAttribute("required")){
						
						$errorMessage = null;
						if($placeholder = $input->getAttribute("placeholder")){
							$errorMessage = sprintf("Please complete the `%s` field", $placeholder);
						}
						
						$inputValidation->required($errorMessage);
					}
					if($input->hasAttribute("readonly")){
						$inputValidation->readonly($input->getAttribute("value"));
					}
					if($input->hasAttribute("disabled")){
						$inputValidation->disabled();
					}
					if($maxLength = $input->getAttribute("maxlength")){
						$inputValidation->maxLength($maxLength);
					}
					if(in_array($inputType, ["number", "range", "date", "datetime", "datetime-local", "month", "time", "week"])){
						if($min = $input->getAttribute("min")){
							$inputValidation->min($min);
						}
						
						if($min = $input->getAttribute("max")){
							$inputValidation->min($max);
						}
					}
					if($pattern = $input->getAttribute("pattern")){
						$inputValidation->pattern($pattern);
					}
					
					if(count($inputValidation)){
						$validation->addRule($formValidation->addRule($inputValidation));
					}
				}
			}	
			
			$response->withData(array_diff_key($data, array_flip($privateFields)));
			if(!$validation->validate($data)){
				$response->redirect(-1)->withErrors($validation->errors, $formName);
			}
		}
	}
	
	private function validateHtml(&$htmlDocument){
		libxml_use_internal_errors(true);
		$dom = new \DOMDocument;
		$dom->loadHtml($htmlDocument, LIBXML_NOWARNING | LIBXML_NOERROR);
		foreach($dom->getElementsByTagName('form') as $form){
			
			if($formAction = $form->getAttribute("action")){
				if(!Router::isSiteUrl($formAction)){
					return;
				}
			}
			$formName = $form->getAttribute("name");
			
			$savedom = new \DOMDocument;
			foreach(["input", "textarea", "select"] as $tag){
				foreach($form->getElementsByTagName($tag) as $input){ 
					//Populate form with previous data
					if($newValue = SessionStore::get("post", new Store())->get($input->getAttribute("name"), false)){
						$input->setAttribute('value', $newValue);
					}
					
					//Populate errors
					if($errorMessage = $this->errors($formName)->get($input->getAttribute("name"), false)){
						$currentClasses = explode(" ", $input->parentNode->getAttribute("class"));
						foreach(["has-feedback", "has-error"] as $class){
							if(!in_array($class, $currentClasses)){
								$currentClasses[] = $class;
							}
						}
						
						$input->parentNode->setAttribute('class', implode(" ", $currentClasses));
						$input->setAttribute("data-error", $errorMessage);
					}
					
					$savenode = $savedom->importNode($input->cloneNode(false), true);
					$savenode->removeAttribute("class");
					$savenode->removeAttribute("data-error");
					$savedom->appendChild($savenode);
				}
			}
			
			SessionStore::flash($formName, base64_encode(gzdeflate($savedom->saveHTML(), 9)));
		}
		
		$htmlDocument = $dom->saveHTML();
	}
}