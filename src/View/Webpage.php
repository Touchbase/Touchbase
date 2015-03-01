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
 *  @date 27/01/2014
 */
 
namespace Touchbase\View;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Utils\SystemDetection;
use Touchbase\Filesystem\File;

class Webpage extends \Touchbase\Core\Object
{
	/**
	 *	@var string
	 */
	protected $layout = "layout.tpl.php";
	
	/**
	 *	@var string
	 */
	protected $body = null;
	
	/**
	 *	@var \Touchbase\View\Assets
	 */
	public $assets;
	
	/**
	 *	@var string
	 */
	private $_htmlTag = null;
	private $_bodyTag = null;
	
	/* Public Methods */

	public function __construct(){
		//Add Requirments
		$this->assets = Assets::shared();
		$this->setLayout($this->layout);
		
		//HTML
		$this->_htmlTag = HTML::html()->attr("lang", "en")->addClass('no-js');
		$this->_bodyTag = HTML::body();
	}
	
	/**
	 * 	Set Body
	 * 
	 * 	@access public
	 *	@param string $body
	 *	@return VOID
	 */
	public function setBody($body){
		$this->body = Template::create(array(
			"BODY" => $body
		))->renderWith($this->layout);
	}
	
	/**
	 *	Set Layout
	 * 
	 *	@access public
	 *	@param string $layout
	 *	@return VOID
	 */
	public function setLayout($layout){
		$ext = pathinfo($layout)['extension'];
		$filename = $layout.(empty($ext)?".tpl.php":"");
			
		foreach([$filename, APPLICATION_TEMPLATES.$filename, BASE_TEMPLATES.$filename] as $path){
			$layoutFile = File::create($path);
			if($layoutFile->exists()){
				$this->layout = $layoutFile->path;
				return;
			}
		}
		
		throw new \Exception("Layout Template Doesn't Exist: $filename");
	}
	
	public function htmlTag(){
		return $this->_htmlTag;
	}
	
	public function bodyTag(){
		return $this->_bodyTag;
	}
	
	/**
	 *	Output Function
	 *	@return string
	 */
	public function output(){
		return $this->constructLayout();
	}
	
	/* Protected Methods */
	
	/**
	 *	Construct Head
	 *	@return string
	 */
	protected function constructHead(){		
		$head = "\r\n<!-- Header Information -->\r\n";
		
		//Print Page Title
		$head.= "\r\n<!-- SITE TITLE -->\r\n";
		$head.= HTML::title($this->assets->contsructTitle());
		
		//Print Meta
		$head.= "\r\n<!-- META INFORMATION -->\r\n";
		$head.= implode("", $this->assets->constructMeta());
		
		//Print Styles
		$head.= "\r\n<!-- STYLE SHEETS -->\r\n";
		$head.= implode("", $this->assets->constructStyles());
		
		//Print Javascript
		$head.= "\r\n<!-- JAVASCRIPT INCLUDES -->\r\n";
		$head.= implode("", $this->assets->constructScripts(true));
		
		//Print Extra
		$head.= "\r\n<!-- EXTRA INCLUDES -->\r\n";
		$head.= implode("", $this->assets->constructExtra());
		
		return $head;
	}
	
	/**
	 *	Construct Body
	 *	@return string
	 */
	protected function constructBody(){
		$body = "\r\n<!-- START CONTENT -->\r\n";
		$body .= '	<!--[if lt IE 7]><p class="chromeframe">You are using an outdated browser. <a href="http://browsehappy.com/">Upgrade your browser today</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to better experience this site.</p><![endif]-->';
			
		$body .= $this->body;

		$body .= "\r\n<!-- END CONTENT -->\r\n";
		
		$body .= "\r\n<!-- JAVASCRIPT INCLUDES -->\r\n";
		$body.= implode("", $this->assets->constructScripts());
		
		return $body;
	}
	
	/**
	 *	Constuct Layout
	 *	@return string
	 */
	protected function constructLayout(){
		
		//HTML5 DocType
		$layout = "<!DOCTYPE html>\n";
		
		//HTML
		$html = $this->_htmlTag->addClass($this->createBrowserClassString());
		
		//HEAD
		$head = HTML::head($this->constructHead());
		
		//BODY
		$body = $this->_bodyTag->content($this->constructBody());
		
		//COMBINE
		$layout .= $html->content($head."\n".$body)->render();
		
		return $layout;
	}
	
	/* Private Methods */
	
	/**
	 *	Create Browser Class String
	 *	This method will create a class string based on the browser information
	 *	@return string
	 */
	private function createBrowserClassString(){
		$browser = SystemDetection::shared();
		
		return implode(" ", [
			$browser->device,
			$browser->platform,
			$browser->os,
			$browser->os.$browser->osVersion,
			$browser->browser,
			$browser->browser.$browser->browserVersion
		]);
	}
}