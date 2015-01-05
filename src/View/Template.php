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

class Template extends \Touchbase\Core\Object
{

	private $vars = array();
	
	public function __construct($vars = false){
		//Add variables to template.
		$this->assign($vars);
		
		//Set up store if not already
		if(!isset($GLOBALS['TemplateVars'])){
			$GLOBALS['TemplateVars'] = array();
		}
	}
	
	/**
	 * @param String - Template file location
	 * @return String - Parsed template contents
	 */
	public function renderWith($templateFile){
		
		if($template = $this->readTemplate($templateFile)){
			/**
			 *	Auto CSS / JS include
			 */
			 
			//Find Filename
			$fileParts = pathinfo($templateFile);
			
			//Find Assets Path
			$assetsPath = BASE_PATH.'assets/';
			
/*
			//CSS autoloader
			if($cssPath = getRealPath(REQUEST_URI, 'assets/css')) {
				//${HTML}->assets->includeStyle($cssPath);
			}
			
			//JS autoloader
			if($jsPath = getRealPath(REQUEST_URI, 'assets/js')) {
				//${HTML}->assets->includeJs($jsPath);
			}
*/
			
			return $template;
		}
	
		return false;		
	}
	
	/**
	 * @param String $tplVar - Variable in template to replace 
	 * @param String $value - Value of variable
	 * @return false
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
		return false;	
	}
	
#praga mark - Internal Methods
	
	/**
	 *	@param String $templateFile - file location of template
	 *	@return String - Template
	 */
	private function readTemplate($templateFile){
		if(file_exists($templateFile)){			
			//extract any variables to be used in the included template
			if(isset($GLOBALS['TemplateVars'])) extract($GLOBALS['TemplateVars']);
			if($this->vars) extract($this->vars);
			
			//This starts an output buffer that parses contents into a string
			ob_start();
				include($templateFile);
				$fileContents = ob_get_contents();
			ob_end_clean();
			
			//Run it through variable finder.
			return $this->variableFinder($fileContents, $templateFile);	
		}
		return false;
	}
	
	/**
	 *	This is the cleaver function that removes the need to use <?php print $varname; ?> in template.
	 *	All thats needed is $varname
	 *	
	 *	@param $content - contents of template
	 *	@return String - Template
	 */
	private function variableFinder($contents, $templateFile){
		preg_match_all("/\\$([A-Za-z0-9\_\-\]\[\.]+)\\$/i", $contents, $templateVars);

		if(!empty($templateVars[1])){
			foreach($templateVars[1] as $k => $var) {
				$typevar = $type = false;
				
				//Replace Vars with Actual Vars
				if(array_key_exists($var, $this->vars)){
					$contents = str_replace("$".$var."$", $this->vars[$var], $contents);
					unset($this->vars[$var]);
					continue;
				} else if(defined($var)){
					$contents = str_replace("$".$var."$", constant($var), $contents);
					continue;
				} else if(array_key_exists($var, $GLOBALS['TemplateVars'])){
					$contents = str_replace("$".$var."$", $GLOBALS['TemplateVars'][$var], $contents);
					continue;
				} else if(array_key_exists($var, $GLOBALS)){
					$contents = str_replace("$".$var."$", $GLOBALS[$var], $contents);
					continue;
				}
				
				//$_REQUEST, $_COOKIE, $_SESSION Finder
				if(strpos($var, ".") !== false){
					$varParts = explode(".", $var);
					if(isset($GLOBALS["_".$varParts[0]])){
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
							$contents = str_replace("$".$var."$", $findVar, $contents);
						}
					}
				}
				
				//Template Finder
				preg_match("/(.*)[\[]{1}(.*)[\]]{1}$/i", $var, $type);
				if(is_array($type) && count($type) > 1 && defined($typevar = "TPL_".strtoupper($type[2])."_PATH")) {
					$moreContents = new Template($this->vars);
					$contents = str_replace($templateVars[0][$k], $moreContents->renderWith(getRealPath($type[1], constant($typevar))), $contents);
				}
				
				//Replace Unfound Vars
				$contents = str_replace("$".$var."$", "",$contents);
			}
		}
		
		//Save Unused Vars - Might be used in a parent template.
		$GLOBALS['TemplateVars'] = array_merge($GLOBALS['TemplateVars'], $this->vars);
	
		//Return String
	 	return $contents;
	}
}
