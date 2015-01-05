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

use Touchbase\Utils\SystemDetection;
use Touchbase\Filesystem\File;

class Webpage extends \Touchbase\Core\Object
{

	protected $view;
	
	protected $layout = "layout.tpl.php";
	
	protected $body = null;
	
	public $assets;

	public function __construct(){
		//Add Requirments
		$this->assets = new Assets($this);
		$this->setLayout($this->layout);
	}
	
	public function setBody($body){
		$this->body = Template::create(array(
			"BODY" => $body
		))->renderWith($this->layout);
	}
	
	public function setLayout($layout){
		$ext = pathinfo($layout)['extension'];
		$filename = $layout.(empty($ext)?".tpl.php":"");
		
		$layoutFile = File::create(APPLICATION_TEMPLATES.$filename);
		if(!$layoutFile->exists()){
			$layoutFile = File::create(BASE_TEMPLATES.$filename);
			
			if(!$layoutFile->exists()){
				throw new \Exception("Layout Template Doesn't Exist.");
			}
		}
		
		$this->layout = $layoutFile->path;
	}
	
	/**
	 *	OutPut Function
	 */
	public function output(){
		return $this->constructLayout();
	}
		
	protected function constructHead(){		
		$head = "\r\n<!-- Header Information -->\r\n";
		
		//Print Page Title
		$head.= "\r\n<!-- SITE TITLE -->\r\n";
		$head.= HtmlBuilder::make('title', $this->assets->contsructTitle())->output();
		
		//Print Meta
		$head.= "\r\n<!-- META INFORMATION -->\r\n";
		$head.= implode("\n", $this->assets->constructMeta());
		
		//Print Styles
		$head.= "\r\n<!-- STYLE SHEETS -->\r\n";
		$head.= implode("\n", $this->assets->constructStyle());
		
		//Print Javascript
		$head.= "\r\n<!-- JAVASCRIPT INCLUDES -->\r\n";
		$head.= implode("\n", $this->assets->constructJs(true));
		
		//Print Extra
		$head.= "\r\n<!-- EXTRA INCLUDES -->\r\n";
		$head.= implode("\n", $this->assets->constructExtra());
		
		return $head;
	}
	
	protected function constructBody(){
		$body = "\r\n<!-- START CONTENT -->\r\n";
		$body .= '	<!--[if lt IE 7]><p class="chromeframe">You are using an outdated browser. <a href="http://browsehappy.com/">Upgrade your browser today</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to better experience this site.</p><![endif]-->';
			
		$body .= $this->body;

		$body .= "\r\n<!-- END CONTENT -->\r\n";
		
		$body .= "\r\n<!-- JAVASCRIPT INCLUDES -->\r\n";
		$body.= implode("\n", $this->assets->constructJs());
		
		return $body;
	}
	
	protected function constructLayout(){
		
		//HTML5 DocType
		$layout = "<!DOCTYPE html>\n";
		
		//HTML
		$html = HtmlBuilder::make('html')->attr("lang", "en")->addClass('no-js')->addClass($this->createBrowserClassString());
		
		//HEAD
		$head = HtmlBuilder::make('head')->html($this->constructHead());
		
		//BODY
		$body = HtmlBuilder::make('body')->html($this->constructBody());
		
		//COMBINE
		$layout .= $html->html($head."\n".$body)->output();
		
		return $layout;
	}
	
	
	//Return Browser Information
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