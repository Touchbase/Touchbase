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

use Touchbase\Data\SessionStore;
use Touchbase\Control\Router;
use Touchbase\Control\Controller;
use Touchbase\Filesystem\File;
use Touchbase\Utils\SystemDetection;
use Touchbase\Utils\DOMDocument;

class WebpageResponse extends \Touchbase\Control\HTTPResponse
{
	/**
	 *	@var string
	 */
	protected $layout = "layout.tpl.php";
	
	/**
	 *	@var \Touchbase\Control\Controller
	 */
	protected $controller = null;
	
	/**
	 *	@var \Touchbase\View\AssetStore
	 */
	public $_assets;
	
	/**
	 *	@var \Touchbase\View\HTML
	 */
	private $_htmlTag = null;
	
	/**
	 *	@var \Touchbase\View\HTML
	 */
	private $_bodyTag = null;
	
	/* Public Methods */

	/**
	 *	__construct
	 *	@param \Touchbase\Control\Controller $controller
     *	@param array $assets
	 */
	public function __construct(Controller $controller, $assets = null){
		
		$this->controller = $controller;
        
        $this->_assets = AssetStore::create($this->controller->config(), $assets)->setController($this->controller);
		
		//Set Default Title, if available...
		$this->assets()->pushTitle($controller->config("project")->get("name", null));
				
		//Add Defualt Meta
		$this->assets()->includeMeta(HTML::meta()->attr('charset','UTF-8'));
		$this->assets()->includeMeta(HTML::meta()->attr('http-equiv','Content-type')->attr('content', 'text/html; charset=utf-8'));
		$this->assets()->includeMeta('generator', 'Touchbase - http://touchbase.io');
		
		//Include base url
		$this->assets()->includeExtra(HTML::base()->href(Router::buildPath(SITE_URL, $_SERVER['REQUEST_URI'])));
		
		//WebApp
		$this->assets()->includeMeta('HandheldFriendly', 'true');
		$this->assets()->includeMeta('MobileOptimized', '320');
		$this->assets()->includeMeta(HTML::meta()->attr('http-equiv', 'cleartype')->attr('content', 'on'));
		$this->assets()->includeMeta('viewport', 'user-scalable=no, initial-scale=1.0, maximum-scale=1.0');
		$this->assets()->includeMeta('apple-mobile-web-app-status-bar-style', 'black-translucent');
		$this->assets()->includeMeta('apple-mobile-web-app-capable', 'yes');
		$this->assets()->includeMeta('apple-mobile-web-app-title', $this->controller->config("project")->get("name", null));
		$this->assets()->includeMeta('mobile-web-app-capable', 'yes');
		
		//WebAppIcons
		$manifest = File::create([Router::pathForAssetUrl(BASE_IMAGES), 'icons', 'manifest.json']);
		if($manifest->exists()){
			$manifestData = json_decode($manifest->read());
			$manifestUrl = Router::urlForPath($manifest->folder->path);
			
			$this->assets()->includeExtra(HTML::link()->href(Router::buildPath($manifestUrl, $manifest->name))->rel("manifest"));
			
			//Launch Images
			
			//Icons
			foreach($manifestData->icons as $icon){
				$this->assets()->includeExtra(HTML::link()->href(Router::buildPath($manifestUrl, $icon->src))->sizes($icon->sizes)->rel("apple-touch-icon-precomposed"));
			}
		}
		
		//Prevent Opening WebApp Links In Mobile Safari!
		$this->assets()->includeScript(HTML::script('(function(a,b,c){if(c in b&&b[c]){var d,e=a.location,f=/^(a|html)$/i,g=a.documentElement;a.addEventListener("click",function(a){d=a.target;while(!f.test(d.nodeName))d=d.parentNode;"href"in d&&(d.href.indexOf("http")||~d.href.indexOf(e.host))&&(a.preventDefault(),e.href=d.href)},!1);g.className=g.className+" "+c}})(document,window.navigator,"standalone")'), true);
		
		//Add Base Scripts
		$assetConfig = $controller->config("assets");
		$assetPath = $assetConfig->get("assets","assets");
		$baseScripts = Router::buildPath(SITE_URL, $assetPath, $assetConfig->get("js","js"));
		
		//Include Modernizr
		$this->assets()->includeScript([$baseScripts, 'modernizr.min.js'], true);
		
		//Include jQuery?
		if($jqVersion = $controller->config("assets")->get("jquery_version", false)){
			//Load From Google 
			$jqueryPath = Router::buildPath($jqVersion, "jquery.min.js");
			$this->assets()->includeScript(Router::buildPath("//ajax.googleapis.com/ajax/libs/jquery/", $jqueryPath));
			
			//If That Fails Load Locally
			if(Router::pathForAssetUrl($jqueryUrl = Router::buildPath($baseScripts, "vendor", "jquery", $jqueryPath))){
				$this->assets()->includeScript(HTML::script('window.jQuery||document.write(\'<script src="'.$jqueryUrl.'"><\/script>\')'));
			}
		}
		
		//Include Google Analytics
		$googleAnalyticsCode = $controller->config("project")->get("analytics", null);
		if(isset($googleAnalyticsCode) && Router::isLive()){
			$this->assets()->includeScript("<script>
			  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			  ga('create', '{$googleAnalyticsCode}', 'auto');
			  ga('send', 'pageview');
			</script>");
		}
		
		if(Router::pathForAssetUrl($fastclickUrl = Router::buildPath($baseScripts, "vendor", "fastclick.js"))){
			$this->assets()->includeScript($fastclickUrl);
			$this->assets()->includeScript(HTML::script('if ("addEventListener" in document) {
				document.addEventListener("DOMContentLoaded", function() {
					FastClick.attach(document.body);
				}, false);
			}'));
		}
		
		$this->setLayout($this->layout);
		
		//HTML		
		$this->_htmlTag = HTML::html()->attr("lang", "en")->addClass('no-js');
		$this->_bodyTag = HTML::body();
	}
	
	/**
	 *	Set Layout
	 *	@param string $layout
	 *	@access public
	 *	@return \Touchbase\View\Webpage
	 */
	public function setLayout($layout){
		if(is_array($layout)){
			$layout = call_user_func_array("Touchbase\Filesystem\Filesystem::buildPath", $layout); 
		}
		
		$ext = pathinfo($layout)['extension'];
		$filename = $layout.(empty($ext)?".tpl.php":"");
					
		foreach($this->controller->templateSearchPaths() as $path){
			$layoutFile = File::create([$path, $filename]);
			if($layoutFile->exists()){
				$this->layout = $layoutFile->path;
				return $this;
			}
		}
		
		throw new \Exception("Layout Template Doesn't Exist: $filename");
	}
	
	/**
	 *	HTML Tag
	 *	@return \Touchbase\View\HTML
	 */
	public function htmlTag(){
		return $this->_htmlTag;
	}
	
	/**
	 *	Body Tag
	 *	@return \Touchbase\View\HTML
	 */
	public function bodyTag(){
		return $this->_bodyTag;
	}
	
	/**
	 *	Assets
	 *	@return \Touchbase\View\Assets
	 */
	public function assets(){
		return $this->_assets;
	}
	
	/**
	 *	Render
	 *	Output layout as a string
	 *	@return string
	 */
	public function render(){
		if(!$this->controller->request()->isAjax()){
			$this->body = $this->constructLayout();
		}
		
		$this->validateHtml($this->body);
		
		return parent::render();
	}
	
	/* Protected Methods */
	
	/**
	 *	Construct Head
	 *	@return string
	 */
	protected function constructHead(){		
		$head = "\r\n<!-- Header Information -->\r\n";
		
		//Print Page Title
		$head .= "\r\n<!-- SITE TITLE -->\r\n";
		$head .= HTML::title($this->assets()->contsructTitle());
		
		//Print Meta
		$head .= "\r\n<!-- META INFORMATION -->\r\n";
		$head .= implode("", $this->assets()->meta());
		
		//Print Styles
		$head .= "\r\n<!-- STYLE SHEETS -->\r\n";
		$head .= implode("", $this->assets()->styles());
		
		//Print Javascript
		$head .= "\r\n<!-- JAVASCRIPT INCLUDES -->\r\n";
		$head .= implode("", $this->assets()->scripts(true));
		
		//Print Extra
		$head .= "\r\n<!-- EXTRA INCLUDES -->\r\n";
		$head .= implode("", $this->assets()->extra());
		
		return $head;
	}
	
	/**
	 *	Construct Body
	 *	@return string
	 */
	protected function constructBody(){
		$body = "\r\n<!-- START CONTENT -->\r\n";
			
		$body .= Template::create([
			"BODY" => $this->body
		])->setController($this->controller)->renderWith($this->layout);

		$body .= "\r\n<!-- END CONTENT -->\r\n";
		
		$body .= "\r\n<!-- JAVASCRIPT INCLUDES -->\r\n";
		$body .= implode("", $this->assets()->scripts());
		
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
		$currentUrlClass = "url-" . str_replace("/", "-", trim($this->controller->request()->url(), "/") ?: "index");
		$html = $this->_htmlTag->addClass($this->createBrowserClassString())->addClass($currentUrlClass);
		
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
			$browser->browser.$browser->browserVersion,
			(strcasecmp(SystemDetection::shared()->os, "ios") == 0 && empty($browser->browser))?"webapp":""
		]);
	}

	/**
	 *	Validate Html
	 *	This method scans the outgoing HTML for any forms, if found it will save the form to the session in order to validate.
	 *	If any errors previously existed in the session, this method will apply `bootstrap` style css error classes.
	 *	@pararm string &$htmlDocument - The outgoing HTML string
	 *	@return VOID
	 */
	private function validateHtml(&$htmlDocument){
		
		if(!empty($htmlDocument)){
			$dom = new DOMDocument;
			$dom->loadHtml($htmlDocument, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NOENT | LIBXML_HTML_NOIMPLIED);
			
			//Automatically apply an active class to links that relate to the current URL			
			foreach($dom->getElementsByTagName('a') as $link){
				if(Router::isSiteUrl($href = Router::relativeURL($link->getAttribute("href")))){
					
					$currentClasses = explode(" ", $link->getAttribute("class"));
					
					if(strcasecmp($href, $this->controller->request()->url()) == 0){
						$currentClasses[] = 'active';
					}
					
					if(strpos($this->controller->request()->url(), $href) === 0){
						$currentClasses[] = 'child-active';
					}
					
					$link->setAttribute('class', implode(" ", $currentClasses));
				}
			}
            
			//Save the outgoing form elements for future validation.
			foreach($dom->getElementsByTagName('form') as $form){
				$savedom = new \DOMDocument;
							
				if($formAction = $form->getAttribute("action") && !Router::isSiteUrl($formAction)){
					continue;
				}
				
				//Add CSRF
				$rand = function_exists('random_bytes') ? random_bytes(32) : null;
				$rand = !$rand && function_exists('mcrypt_create_iv') ? mcrypt_create_iv(32, MCRYPT_DEV_URANDOM) : $rand;
				$rand = $rand ? $rand : openssl_random_pseudo_bytes(32);

				$csrfToken = bin2hex($rand);
				$formName = $form->getAttribute("name");
                $formNameToken = $formName . "_" . $csrfToken;
				
				$csrf = $dom->createDocumentFragment();
				$csrf->appendXML(HTML::input()->attr("type", "hidden")->attr("name", "tb_form_token")->attr("value", $formNameToken)->attr("readonly", true));	
				$form->insertBefore($csrf, $form->firstChild); //TODO: Should we assume the form will allways have content
				
				foreach(["input", "textarea", "select"] as $tag){
					foreach($form->getElementsByTagName($tag) as $input){
						$savedom->appendChild($savedom->importNode($input->cloneNode()));
						
						//Populate form with previous data
						if(($newValue = SessionStore::get("touchbase.key.session.post")->get($input->getAttribute("name"), false)) !== false){
							if(is_scalar($newValue) && $input->getAttribute("type") !== "hidden" && !$input->hasAttribute("readonly")){
								$input->setAttribute('value', $newValue);
							}
						}
						
						//Populate errors
						if($errorMessage = $this->controller->errors($formName)->get($input->getAttribute("name"), false)){
							$currentClasses = explode(" ", $input->parentNode->getAttribute("class"));
							foreach(["has-feedback", "has-error"] as $class){
								if(!in_array($class, $currentClasses)){
									$currentClasses[] = $class;
								}
							}
							
							$input->parentNode->setAttribute('class', implode(" ", $currentClasses));
							$input->setAttribute("data-error", $errorMessage);
						}
					}
				}
				
                SessionStore::recycle($formName, $formNameToken, base64_encode(gzdeflate($savedom->saveHTML(), 9)));
			}
				
			//Move body scripts to bottom
			$bodies = $dom->getElementsByTagName('body');
			$body = $bodies->item(0);
			if($body){
				foreach($body->getElementsByTagName('script') as $script){
					if($script->parentNode->nodeName === "body") break;
					$body->appendChild($dom->importNode($script));
				}
			}
			
			//Look for the special attribute that moves nodes. 
			//This is useful for moving modals from the template files to the bottom output.
			$xpath = new \DOMXPath($dom); $appendToBodyRef = NULL;
			foreach($xpath->query("//*[@tb-append]") as $element){
				$appendTo = $xpath->query($element->getAttribute("tb-append"))->item(0);
				$element->removeAttribute("tb-append");
				
				if($appendTo){
					if($appendTo->nodeName === "body"){
						//Special case to append above the included javascript files.
						if(!$appendToBodyRef) $appendToBodyRef = $xpath->query('/html/body/comment()[. = " END CONTENT "][1]')->item(0);
						$body->insertBefore($dom->importNode($element), $appendToBodyRef);
					} else {
						$appendTo->appendChild($dom->importNode($element));
					}
				}
			}
			
			//Save the HTML with the updates.
			if($this->controller->request()->isAjax() || !$this->controller->request()->isMainRequest()){
				//This will remove the doctype that's automatically appended.
				$htmlDocument = $dom->saveHTML($dom->documentElement);		
			} else {
				$htmlDocument = $dom->saveHTML();
			}
		}
	}
}