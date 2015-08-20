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
 *  @category View
 *  @date 24/01/2014
 */
 
namespace Touchbase\View;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Filesystem\File;
use Touchbase\Filesystem\Filesystem;

class Template extends \Touchbase\Core\Object
{	
	/**
	 *	@var \Touchbase\Control\Controller
	 */
	protected $controller;
	
	/**
	 *	@var array
	 */
	private $vars = [];
	
	/* Public Methods */
	
	/**
	 *	__construct
	 *	@param array $vars
	 */
	public function __construct($vars = false){
		//Add variables to template.
		$this->assign($vars);
		
		//Set up store if not already
		if(!isset($GLOBALS['TemplateVars'])){
			$GLOBALS['TemplateVars'] = [];
		}
	}
	
	/**
	 *	Render With
	 *	@param string $templateFile - Template file location
	 *	@return string - Parsed template contents
	 */
	public function renderWith($templateFile){
		
		if(is_array($templateFile)){
			$templateFile = call_user_func_array("Touchbase\Filesystem\Filesystem::buildPath", $templateFile); 
		}
		
		foreach($this->controller->templateSearchPaths() as $path){
			$templateFileObj = File::create([$path, $templateFile]);
			if($templateFileObj->exists()){
				$templateFilePath = $templateFileObj->path;
				break;
			}
		}
		
		if(!isset($templateFilePath)) return false;
		
		$this->assign("controller", $this->controller);
		$this->assign("request", $this->controller->request());
		$this->assign("errors", $this->controller->errors);
		
		if($template = $this->readTemplate($templateFilePath)){
			/**
			 *	Auto CSS / JS include
			 */
			 
			//Find Filename
			$fileParts = pathinfo($templateFilePath);
			
			if(isset($this->controller)){
				$controllerName = strtolower($this->controller->controllerName);
				
				//CSS autoloader
				Assets::shared()->includeStyle([APPLICATION_STYLES, $controllerName . ".css"]);
				
				//JS autoloader
				Assets::shared()->includeScript([APPLICATION_SCRIPTS, $controllerName . ".js"]);
			}
			
			return $template;
		}
	
		return false;
	}
	
	/**
	 *	Assign
	 *	@param string $tplVar - Variable in template to replace 
	 *	@param string $value - Value of variable
	 *	@return VOID
	 */
	public function assign($tplVar, $value = false){
		if(!empty($tplVar)){
		  	if(is_array($tplVar)){
		  		foreach($tplVar as $var => $val){
					$this->assign($var,$val);
				}
		  	} else {
				$this->vars[$tplVar] = $value;
			}
		}
	}
	
	/* Private Methods */
	
	/**
	 *	Read Template
	 *	@param string $templateFile - file location of template
	 *	@return string - Template
	 */
	private function readTemplate($templateFile){
		if(file_exists($templateFile)){
			//extract any variables to be used in the included template
			if(isset($GLOBALS['TemplateVars'])) extract($GLOBALS['TemplateVars']);
			if($this->vars) extract($this->vars);
			
			//This starts an output buffer that parses contents into a string
			ob_start();
				if(!class_exists("Auth")) class_alias("\Touchbase\Security\Auth", "Auth");
				include($templateFile);
				$fileContents = ob_get_contents();
			ob_end_clean();
			
			//Run it through variable finder.
			return $this->variableFinder($fileContents, $templateFile);
		}
		return false;
	}
	
	/**
	 *	Variable Finder
	 *	This is the cleaver function that removes the need to use <?php print $varname; ?> in template.
	 *	All thats needed is $varname
	 *	
	 *	@param string $contents - contents of the template file
	 *	@return string
	 */
	private function variableFinder($contents, $templateFile){
		preg_match_all("/\\$([A-Za-z0-9\_\-\]\[\.\:\/]+)\\$/i", $contents, $templateVars);

		if(!empty($templateVars[1])){
			
			$replaceVar = function($needle, $replacement, &$haystack){
				//URL fix
				if(is_string($replacement) && substr($replacement, -1) === "/"){
					$haystack = str_replace($needle."/", $replacement, $haystack);
				}
				
				$haystack = str_replace($needle, $replacement, $haystack);
			};
			
			foreach($templateVars[1] as $k => $var) {
				$typevar = $type = false;
				
				//Replace Vars with Actual Vars
				if(array_key_exists($var, $this->vars)){
					$replaceVar($templateVars[0][$k], $this->vars[$var], $contents);
					unset($this->vars[$var]);
					continue;
				} else if($pos = strpos($var, "::") !== false){
					// Property Exists
					$property = substr($var, $pos+1);
					$controller = $this->controller;
					
					if(isset($controller::${$property})){
						$replaceVar($templateVars[0][$k], $controller::${$property}, $contents);
						continue;
					} 
				} else if(defined($var)){
					$replaceVar($templateVars[0][$k], constant($var), $contents);
					continue;
				} else if(array_key_exists($var, $GLOBALS['TemplateVars'])){
					$replaceVar($templateVars[0][$k], $GLOBALS['TemplateVars'][$var], $contents);
					continue;
				} else if(array_key_exists($var, $GLOBALS)){
					$replaceVar($templateVars[0][$k], $GLOBALS[$var], $contents);
					continue;
				} 
				
				//$_REQUEST, $_COOKIE, $_SESSION, Class Object Finder
				if(strpos($var, ".") !== false){
					$varParts = explode(".", $var);
					
					if(!in_array($varParts[0], ["SESSION", "COOKIE", "REQUEST"])){
						
						if(!empty($varParts[0])){
							$property = $this->vars[$varParts[0]];
						} else {
							$property = $this->controller;
						}
						
						unset($varParts[0]);
						while($part = array_shift($varParts)){
							if(method_exists($property, $part)){
								$property = $property->$part();
							} else if(isset($property->$part)){
								$property = $property->$part;
							} else {
								if(!method_exists($property, "__get")){
									trigger_error(sprintf("Trying to get property $%s of non-object", $part), E_USER_NOTICE);
								}
								
								$property = null;
								break;
							}
						}
						
						if($property && !is_object($property)){
							$replaceVar($templateVars[0][$k], $property, $contents);
							continue;
						}
					} else if(isset($GLOBALS["_".$varParts[0]])){
						$findVar = $GLOBALS["_".$varParts[0]];
						for($i=1; $i < count($varParts); $i++){
							if(isset($findVar[$varParts[$i]])){
								$findVar = $findVar[$varParts[$i]];
							} else {
								break;
							}
						}
						//We were successfull in finding the var
						if($i == count($varParts)){
							$replaceVar($templateVars[0][$k], $findVar, $contents);
							continue;
						}
					}
				}
				
				//Template Finder
				preg_match("/(.*)[\[]{1}(.*)[\]]{1}$/i", $var, $type);
				if(is_array($type) && count($type) > 1) {
					$moreContents = (new Template($this->vars))->setController($this->controller);
					$contents = str_replace($templateVars[0][$k], $moreContents->renderWith(Filesystem::buildPath(dirname($templateFile), $type[2] . ".tpl.php")), $contents);
				}
				
				//Replace Unfound Vars
				$replaceVar($templateVars[0][$k], "", $contents);
			}
		}
		
		//Save Unused Vars - Might be used in a parent template.
		$GLOBALS['TemplateVars'] = array_merge($GLOBALS['TemplateVars'], $this->vars);
		
		//Return String
	 	return $contents;
	}
}
