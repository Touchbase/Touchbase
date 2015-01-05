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

use \Touchbase\Control\Router;

class Assets extends \Touchbase\Core\Object
{

	protected $documentTitle = array();
	protected $documentMeta = array();
	protected $documentCss = array();
	protected $documentJs = array(
		'head' => array(),
		'body' => array()
	);
	protected $documentExtra = array();
	
	//Default Requirements
	public function __construct(Webpage $buffer){
	
		//Add Defualt Meta
		$this->includeMeta(HtmlBuilder::make('meta')->attr('charset','UTF-8')->output());
		$this->includeMeta(HtmlBuilder::make('meta')->attr('http-equiv','Content-type')->attr('content', 'text/html; charset=utf-8')->output());
		$this->includeMeta('generator', 'Touchbase - http://touchbase.williamgeorge.co.uk');
		
		//WebApp
		$this->includeMeta('HandheldFriendly', 'true');
		$this->includeMeta('MobileOptimized', '320');
		$this->includeMeta(HtmlBuilder::make('meta')->attr('http-equiv', 'cleartype')->attr('content', 'on')->output());
		$this->includeMeta('viewport', 'user-scalable=no, initial-scale=1.0, maximum-scale=1.0');
		$this->includeMeta('apple-mobile-web-app-status-bar-style', 'black-translucent');
		$this->includeMeta('apple-mobile-web-app-capable', 'yes');
		
		//Prevent Opening WebApp Links In Mobile Safari!
		$this->includeJs(HtmlBuilder::make_r('script', '(function(a,b,c){if(c in b&&b[c]){var d,e=a.location,f=/^(a|html)$/i;a.addEventListener("click",function(a){d=a.target;while(!f.test(d.nodeName))d=d.parentNode;"href"in d&&(d.href.indexOf("http")||~d.href.indexOf(e.host))&&(a.preventDefault(),e.href=d.href)},!1)}})(document,window.navigator,"standalone")'), true);
		
/*
		//Add Defualt Title
		$this->pushTitle();

		//ADD MODERNIZR
		$this->includeJs(SITE_THEME_JS.'modernizr.js', true);
		
		//JQUERY Add
		if(!(defined("JQUERY_DISABLE_LOAD") && JQUERY_DISABLE_LOAD)){
			if(!defined("JQUERY_VERSION")){
				define("JQUERY_VERSION", '1.8.1', true);
			}
		
			//Load From Google 
			$this->includeJs("//ajax.googleapis.com/ajax/libs/jquery/".JQUERY_VERSION."/jquery.min.js");
			//If That Fails Load Locally
			$this->includeJs(HtmlBuilder::make_r('script','window.jQuery||document.write(\'<script src="'.SITE_THEME_JS.'jquery-'.JQUERY_VERSION.'.min.js"><\/script>\')'));
		}
		
*/
		//Custom Top Site Thumbnail.
/*
		$previewFile = load()->newInstance('Filesystem\File', BASE_PATH.'topSitePreview.html');
		if($previewFile->exists()){
			$this->includeJs(HtmlBuilder::make_r('script', 'if(window.navigator && window.navigator.loadPurpose === "preview"){window.location.href = "'.SITE_ROOT.'topSitePreview.html"}'), true);
		}
		
		//Get Theme Requirments
		$themeRequire = load()->newInstance('Filesystem\File', BASE_PATH.'Themes/'.SITE_THEME.'/require.php');
		if($themeRequire->exists()){
			include($themeRequire->path);
		}
*/
	}
		
	//Include Meta Information In View
	public function includeMeta($nameOrSnipit, $content = false){
		if(is_string($nameOrSnipit)){
			if(!empty($content)){
				$nameOrSnipit = array($nameOrSnipit => $content);
			}
			
			$this->documentMeta[] = $nameOrSnipit;
		} 
		return $this;
	}
	
	//Get Meta
	public function constructMeta(){
		$return = array();
		
		foreach($this->documentMeta as $meta){
			if(is_array($meta)){
				$name = key($meta);
				$meta = current($meta);
			}
			
			if(!$this->isSnipit('meta', $meta)){
				$meta = HtmlBuilder::make('meta')->attr('name', $name)->attr('content', $meta)->output();
				
				//RESET
				unset($name);
			}
			
			$return[] = $meta;
		}
		
		return $return;
	}
	
	//Clear Meta Info
	public function clearMeta(){
		$this->documentMeta = array();
		
		return $this;
	}
	
	//Include Style Information In View
	public function includeStyle($file, $media = null){
		if(is_string($file)){
			if(!$this->isSnipit('css', $file)){
				//$file = Router::relativeUrl($file);
			}
			
			if(!empty($media)){
				$file = array($media => $file);
			}
			
			if(!in_array($file, $this->documentCss)){
				$this->documentCss[] = $file;
			}
		}
		return $this;
	}
	
	//Get Styles
	public function constructStyle(){
		$return = array();
		
		foreach($this->documentCss as $css){
			if(is_array($css)){
				$media = key($css);
				$css = current($css);
			}
			if(!$this->isSnipit('css', $css)){
				$link = HtmlBuilder::make('link')->attr("rel", "stylesheet")->attr('href', Router::absoluteURL($css))->attr("type", "text/css");
				if(isset($media)){
					$link->attr('media', $media);
					unset($media);
				}
				
				$css = $link->output();
			}
			
			$return[] = $css;
		}
		
		return $return;
	}
	
	//Clear Css Includes
	public function clearStyle(){
		$this->documentCss = array();
		
		return $this;
	}
	
	//Include JS Information In View
	public function includeJs($file, $head = false){
		if(is_string($file)){
			if(!$this->isSnipit('js', $file)){
				//$file = Router::absoluteUrl($file);
			}

			if($head){
				if(!in_array($file, $this->documentJs['head'])){
					$this->documentJs['head'][] = $file;
				}
			} else {
				if(!in_array($file, $this->documentJs['body'])){
					$this->documentJs['body'][] = $file;
				}
			}
		}

		return $this;
	}
	
	//Get Javascript
	public function constructJs($head = false){
		$return = array();
		
		foreach($this->documentJs[($head?'head':'body')] as $js){
			if(!$this->isSnipit('js', $js)){
				$js = HtmlBuilder::make('script')->attr('type', 'text/javascript')->attr('src', Router::absoluteURL($js))->output();
			}
			
			$return[] = $js;
		}
		
		return $return;
	}
	
	//Clear Javascript Includes
	public function clearJs(){
		$this->documentJs = array();
		
		return $this;
	}
	
	//Add Extra Elements To Head
	public function includeExtra($name, $args){
		//Head Only Allows Certain Elements. Since We Have Covered The Rest These Three Are Left
		if(in_array($name, array('base', 'link', 'noscript')) && is_array($args)){
			$this->documentExtra[][$name] = $args; 
		}
		
		return $this;
	}
	
	//Get Extra
	public function constructExtra(){
		$return = array();
		
		foreach($this->documentExtra as $extra){
			if(is_array($extra)){
				$name = key($extra);
				$attr = current($extra);
				
				$extra = HtmlBuilder::make($name)->attr($attr)->output();
				$return[] = $extra;
			}
		}
		
		return $return;
	}
	
	//Reset
	public function clearExtra(){
		$this->documentExtra = array();
	
		return $this;
	}
	
	
	//Check If Already Snipit
	protected function isSnipit($type, $check){
		switch($type){
			case 'link':
			case 'css':
				if(strpos($check, "<link") !== false || strpos($check, "<style") !== false){
					return true;
				}
			break;
			case 'meta':
				if(strpos($check, "<meta") !== false){
					return true;
				}
			break;
			case 'js':
			case 'javascript':
				if(strpos($check, "<script") !== false){
					return true;
				}
			break;
		}
		
		return false;
	}

	//Adds n amount of titles to the documentTitle array
	public function pushTitle(){
		$titles = func_num_args();
		for ($i = 0; $i < $titles; $i++) {
			$titleData = func_get_arg($i);
			if(is_array($titleData)){
				$this->documentTitle = array_merge($this->documentTitle, $titleData);
			} else {
				array_push($this->documentTitle, $titleData);
			}
		}
		
		return $this;
	}
	
	//Pop Current Title
	public function popTitle(){
		return array_pop($this->documentTitle);
	}
	
	//Reset Title
	public function clearTitle(){
		$this->documentTitle = array();
		
		return $this;
	}
	
	//Construct Title for Output
	public function contsructTitle($reverse = false){
		$titleData = (!$reverse)?array_reverse($this->documentTitle):$this->documentTitle;
		
		return implode(" ".(defined("SITE_TITLE_SEPARATOR")?SITE_TITLE_SEPARATOR:'|')." ", array_map('ucfirst', $titleData)); 
	}

}