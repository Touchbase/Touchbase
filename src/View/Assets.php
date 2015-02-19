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

use Touchbase\Control\Router;
use Touchbase\Data\StaticStore;
use Touchbase\Core\Config\ConfigTrait;
use Touchbase\Core\Config\Store as ConfigStore;

use Touchbase\Filesystem\File;

class Assets extends \Touchbase\Core\Object
{
	use ConfigTrait;
	
	const ASSETS_KEY = 'touchbase.key.assets';
	
	protected $documentTitle = [];
	protected $documentMeta = [];
	protected $documentCss = [];
	protected $documentScripts = [
		'head' => [],
		'body' => []
	];
	protected $documentExtra = array();
		
	/** 
	 *	Shared
	 *	@return \Touchbase\View\Assets
	 */
	public static function shared(){
		$instance = StaticStore::shared()->get(self::ASSETS_KEY, false);
		if(!$instance || is_null($instance)){		
			//Find Config
			$config = StaticStore::shared()->get(ConfigStore::CONFIG_KEY, null);
			
			//Save
			StaticStore::shared()->set(self::ASSETS_KEY, $instance = new self($config));
		}
		
		return $instance;
	}
	
	//Default Requirements
	public function __construct(ConfigStore $config = null){
		
		$this->setConfig($config);
	
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
		$this->includeScripts(HtmlBuilder::make_r('script', '(function(a,b,c){if(c in b&&b[c]){var d,e=a.location,f=/^(a|html)$/i;a.addEventListener("click",function(a){d=a.target;while(!f.test(d.nodeName))d=d.parentNode;"href"in d&&(d.href.indexOf("http")||~d.href.indexOf(e.host))&&(a.preventDefault(),e.href=d.href)},!1)}})(document,window.navigator,"standalone")'), true);
		
		//ADD MODERNIZR
		$this->includeScripts(BASE_SCRIPTS.'modernizr.js', true);
		
		//Set Default Title, if available...
		$this->pushTitle($this->config()->get("project")->get("name", null));
		
		//Include jQuery?
		if($jqVersion = $this->config()->get("assets")->get("jquery_version", false)){
			//Load From Google 
			$jqueryPath = File::buildPath($jqVersion, "jquery.min.js");

			$this->includeScripts(File::buildPath("//ajax.googleapis.com/ajax/libs/jquery/", $jqueryPath));
			
			//If That Fails Load Locally
			//TODO: This will never load. Sort it out!
			$jqueryFile = File::create([BASE_SCRIPTS, "jquery", $jqueryPath]);
			if($jqueryFile->exists()){
				$this->includeScripts(HtmlBuilder::make_r('script','window.jQuery||document.write(\'<script src="'.$jqueryFile->path().'"><\/script>\')'));
			}
		}
/*		
		//Custom Top Site Thumbnail.
		$previewFile = load()->newInstance('Filesystem\File', BASE_PATH.'topSitePreview.html');
		if($previewFile->exists()){
			$this->includeScripts(HtmlBuilder::make_r('script', 'if(window.navigator && window.navigator.loadPurpose === "preview"){window.location.href = "'.SITE_URL.'topSitePreview.html"}'), true);
		}
*/
	}
		
	/**
	 *	Include Meta
	 *	Add Meta Information In View
	 *	@param string $nameOrSnipit
	 *	@param BOOL $content
	 *	@return \Touchbase\View\Assets
	 */
	public function includeMeta($nameOrSnipit, $content = null){
		if(is_string($nameOrSnipit)){
			if(!empty($content)){
				$nameOrSnipit = array($nameOrSnipit => $content);
			}
			
			$this->documentMeta[] = $nameOrSnipit;
		} 
		return $this;
	}
	
	/**
	 *	Construct Meta
	 *	@return string
	 */
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
	
	/**
	 *	Clear Meta
	 *	@return \Touchbase\View\Assets
	 */
	public function clearMeta(){
		$this->documentMeta = array();
		
		return $this;
	}
	
	/**
	 *	Include Style
	 *	Add Style Information In View
	 *	@param string $file
	 *	@param string $media
	 *	@return \Touchbase\View\Assets
	 */
	public function includeStyle($file, $media = null){
		if(is_string($file)){
			if(!$this->isSnipit('css', $file)){
				//TODO: Check file exists
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
	
	/**
	 *	Construct Styles
	 *	@return string
	 */
	public function constructStyles(){
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
	
	/**
	 *	Clear Styles
	 *	@return \Touchbase\View\Assets
	 */
	public function clearStyles(){
		$this->documentCss = array();
		
		return $this;
	}
	
	/**
	 *	Include Scripts
	 *	Include JS Information In View
	 *	@param string $file 
	 *	@param BOOL $head - Pass true to include the script in the head tag
	 *	@return \Touchbase\View\Assets
	 */
	public function includeScript($file, $head = false){
		if(is_string($file)){
			if(!$this->isSnipit('js', $file)){
				//TODO: Check file exists
				//$file = Router::absoluteUrl($file);
			}

			if($head){
				if(!in_array($file, $this->documentScripts['head'])){
					$this->documentScripts['head'][] = $file;
				}
			} else {
				if(!in_array($file, $this->documentScripts['body'])){
					$this->documentScripts['body'][] = $file;
				}
			}
		}
		
		return $this;
	}
	
	/**
	 *	Construct Scripts
	 *	@param BOOL $head
	 *	@return string
	 */
	public function constructScripts($head = false){
		$return = array();
		
		foreach($this->documentScripts[($head?'head':'body')] as $js){
			if(!$this->isSnipit('js', $js)){
				$js = HtmlBuilder::make('script')->attr('type', 'text/javascript')->attr('src', Router::absoluteURL($js))->output();
			}
			
			$return[] = $js;
		}
		
		return $return;
	}
	
	/**
	 *	Clear Scripts
	 *	@return \Touchbase\View\Assets
	 */
	public function clearScripts(){
		$this->documentScripts = array();
		
		return $this;
	}
	
	/**
	 *	Include Extra
	 *	Add Extra Elements To Head
	 *	@param string $name
	 *	@param array $args
	 *	@return \Touchbase\View\Assets
	 */
	public function includeExtra($name, $args){
		//Head Only Allows Certain Elements. Since We Have Covered The Rest These Three Are Left
		if(in_array($name, array('base', 'link', 'noscript')) && is_array($args)){
			$this->documentExtra[][$name] = $args; 
		}
		
		return $this;
	}
	
	/**
	 *	Construct Extra
	 *	@return string
	 */
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
	
	/**
	 *	Clear Extra
	 *	@return \Touchbase\View\Assets
	 */
	public function clearExtra(){
		$this->documentExtra = array();
	
		return $this;
	}
	
	
	/**
	 *	Is Snipit
	 *	This method will determine whether the included item already contains the html tags required to display correctly
	 *	@param string $type
	 *	@param string $check
	 *	@return BOOL
	 */
	protected function isSnipit($type, $check){
		switch($type){
			case 'link':
			case 'css':
				return strpos($check, "<link") !== false || strpos($check, "<style") !== false;
			break;
			case 'js':
			case 'javascript':
					return strpos($check, "<script") !== false;
			break;
			case 'meta':
				return strpos($check, "<meta") !== false;
			break;
		}
		
		return false;
	}
	
	/**
	 *	Push Title
	 *	Adds n amount of titles to the documentTitle array
	 *	@return \Touchbase\View\Assets
	 */
	public function pushTitle(){
		$titles = func_num_args();
		for ($i = 0; $i < $titles; $i++) {
			$titleData = func_get_arg($i);
			if(is_array($titleData)){
				$this->documentTitle = array_merge($this->documentTitle, $titleData);
			} else {
				if(!in_array($titleData, $this->documentTitle)){
					array_push($this->documentTitle, $titleData);
				}
			}
		}
		
		return $this;
	}
	
	/**
	 *	Pop Title
	 *	@return string
	 */
	public function popTitle(){
		return array_pop($this->documentTitle);
	}
	
	/**
	 *	Clear Title
	 *	@return \Touchbase\View\Assets
	 */
	public function clearTitle(){
		$this->documentTitle = array();
		
		return $this;
	}
	
	/**
	 *	Construct Title
	 *	@param BOOL $reverse
	 *	@return string 
	 */
	public function contsructTitle($reverse = false){
		$titleData = (!$reverse)?array_reverse($this->documentTitle):$this->documentTitle;
		
		return implode(" ".$this->config()->get("web")->get("title_separator", "|")." ", array_map('ucfirst', $titleData)); 
	}
}