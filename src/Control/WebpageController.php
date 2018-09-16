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

use Touchbase\Data\SessionStore;
use Touchbase\View\WebpageResponse;
use Touchbase\Filesystem\File;
use Touchbase\Utils\Validation;
use Touchbase\Control\Router;
use Touchbase\Control\Session;
use Touchbase\Control\Exception\HTTPResponseException;

class WebpageController extends Controller
{	
	/**
	 *	@var array
	 */
	protected $allowedActions = [];
	
	/* Public Methods */
	
   	/**
	 *	Handle Request
	 *	@param HTTPRequest &$request
	 *	@param HTTPResponse &$response
	 *	@return string
	 */
	public function handleRequest(HTTPRequest &$request, HTTPResponse &$response){	
			
		//Pass through to Controller
		try {
            $assets = ($response instanceof WebpageResponse)?$response->assets():[];
			$response = new WebpageResponse($this, $assets);
			
			if($request->isPost()){
				$validation = Validation::create();
				$this->validatePostRequest($request, $response, $validation);
			}
			
			$body = parent::handleRequest($request, $response);
		} catch (\Exception $e){
			if(!$e instanceof HTTPResponseException){
				error_log(print_r($e, true));
				$e = new HTTPResponseException($e->getMessage(), 500);
				$e->response()->setHeader('Content-Type', 'text/plain');
			}
			
			$body = $this->handleException($e);
			
			//If handleException returns a redirect. Follow it.
			if($body instanceof \Touchbase\Control\HTTPResponse){
				//TODO: Should we always return the response?
				if($body->hasFinished()){
					return $response = $body;
				}
			}
			
			$response->setBody($body);	
			$response->setStatusCode($e->getCode(), $e->getMessage());
		}
		
		if($this->request()->isMainRequest()){			
			$applicationTitles = [];
			$application = $this->application;
			while($application != null){
				if(isset($application::$name)){
					$applicationTitles[] = $application::$name;
				}
				$application = $application->application;
			}
			
			$this->response()->assets()->pushTitle(array_reverse($applicationTitles));
			
			if(isset(static::$name)){
				$this->response()->assets()->pushTitle(static::$name);
			}
		}
		
		return $body;
	}
	
	/* Protected Methods */
	
	/**
	 *	Validate Post Request
	 *	This method adds default validation to input fields based on the served HTML.
	 *	If any errors are found, the redirect will be made on the response object with the errors.
	 *	@param \Touchbase\Control\HTTPRequest $request
	 *	@param \Touchbase\Control\HTTPResponse &$response
	 *	@param \Touchbase\Utils\Validation &$validation
	 *	@return VOID
	 */
	protected function validatePostRequest($request, &$response, &$validation){
        if(!$request->isMainRequest()) return;
		
		$formNameToken = $request->_VAR("tb_form_token");
        $formName = substr($formNameToken, 0, strrpos($formNameToken, "_"));
        
		if(isset($formNameToken) && $form = SessionStore::get($formNameToken, false)){
            SessionStore::consume($formName, $formNameToken);

			$data = $request->_VARS();
			
			libxml_use_internal_errors(true);
			$dom = new \DOMDocument;
			$dom->loadHtml(gzinflate(base64_decode($form)), LIBXML_NOWARNING | LIBXML_NOERROR);
			
			if($dom->documentElement->getAttribute("novalidate")){
				return;
			}
			
			$formValidation = Validation::create($formName);
			
			$privateFields = [];
            foreach(["input", "textarea", "select"] as $tag){
                foreach($dom->getElementsByTagName($tag) as $input){
                    if($input->hasAttributes() && $inputName = $input->getAttribute("name")){
                        $inputType = $input->getAttribute("type");					
                        $inputValidation = Validation::create($inputName);
                        
                        if($inputType === "password"){
                            $privateFields[] = $inputName;
                        }
                        
                        $inputValidation->type($inputType);
                        if($inputType === "file" && $input->hasAttribute("accept")){
                            $validTypes = explode(",", $input->getAttribute("accept"));
                            
                            //File Extentions
                            $fileExt = array_filter($validTypes, function($value){
                                return strpos($value, ".") === 0;
                            });
                            if(!empty($fileExt)){
                                $inputValidation->addRule(function($value) use ($fileExt){
                                    $name = $value['name'];
                                    if(is_array($name)){
        
                                        foreach($name as $nme){
                                            if(!in_array(pathinfo($nme, PATHINFO_EXTENSION), $fileExt)){
                                                return false;
                                            }
                                        }
                                        return true;
                                    }
                                    
                                    return in_array(pathinfo($name, PATHINFO_EXTENSION), $fileExt);
                                }, "A file uploaded did not have the correct extension");
                            }
                            
                            //Mime Types
                            $validTypes = array_diff($validTypes, $fileExt);
                            $implicitFileMime = array_filter($validTypes, function($value){
                                return strpos($value, "/*") !== false;
                            });
                            if(!empty($implicitFileMime)){
                                $inputValidation->addRule(function($value) use ($implicitFileMime){
                                    $tmpName = $value['tmp_name'];
                                    
                                    if(is_array($tmpName)){
                                        foreach($tmpName as $tmp){
                                            $mime = strstr(File::create($tmp)->mime(), "/", true)."/*";
                                            if(!in_array($mime, $implicitFileMime)){
                                                return false;	
                                            }
                                        }
                                        return true;
                                    }
                                    
                                    $mime = strstr(File::create($tmpName)->mime(), "/", true)."/*";
                                    return in_array($mime, $implicitFileMime);
                                }, "A file uploaded did not have the correct mime type");
                            }
                            
                            $validTypes = array_diff($validTypes, $implicitFileMime);
                            $fileMime = array_filter($validTypes, function($value){
                                return strpos($value, "/") !== false;
                            });
                            if(!empty($fileMime)){
                                $inputValidation->addRule(function($value) use ($fileMime){
                                    $tmpName = $value['tmp_name'];
                                    
                                    if(is_array($tmpName)){
                                        foreach($tmpName as $tmp){
                                            if(!in_array(File::create($tmp)->mime(), $fileMime)){
                                                return false;
                                            }
                                        }
                                        return true;
                                    }
                                    return in_array(File::create($tmpName)->mime(), $fileMime);
                                }, "A file uploaded did not have the correct mime type");
                            }
                        }
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
                        if($minLength = $input->getAttribute("minlength")){
                            $inputValidation->minLength($minLength);
                        }
                        if($maxLength = $input->getAttribute("maxlength")){
                            $inputValidation->maxLength($maxLength);
                        }
                        if(in_array($inputType, ["number", "range", "date", "datetime", "datetime-local", "month", "time", "week"])){
                            if($min = $input->getAttribute("min")){
                                $inputValidation->min($min, $inputType);
                            }
                            
                            if($max = $input->getAttribute("max")){
                                $inputValidation->max($max, $inputType);
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
            }
			
			if(!$validation->validate($data)){
                $response->withData(array_diff_key($data, array_flip($privateFields)));
				$response->redirect(-1)->withErrors($validation->errors, $formName);
			}
		} else {
			$response->redirect(-1)->withErrors(["Session timed out, please try again."], $formName);
		}
	}
}