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

use Touchbase\Control\Router;
use Touchbase\Data\StaticStore;
use Touchbase\Core\Config\ConfigTrait;
use Touchbase\Core\Config\Store as ConfigStore;

use Touchbase\Filesystem\File;
use Touchbase\Filesystem\Folder;

class Assets extends \Touchbase\Core\Object
{
	use ConfigTrait;
	
	const ASSETS_KEY = 'touchbase.key.assets';
	const ASSET_JS = 'js';
	const ASSET_CSS = 'css';
	const ASSET_META = 'meta';
	const ASSET_OTHER = 'other';
	
	protected $documentTitle = [];	
	protected $documentAssets = [];
		
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
		
		//Set Default Title, if available...
		$this->pushTitle($this->config("project")->get("name", null));
		
		//Include jQuery?
		if($jqVersion = $this->config("assets")->get("jquery_version", false)){
			//Load From Google 
			$jqueryPath = Router::buildPath($jqVersion, "jquery.min.js");
			$this->includeScript(Router::buildPath("//ajax.googleapis.com/ajax/libs/jquery/", $jqueryPath));
			
			//If That Fails Load Locally
			if(static::pathForAssetUrl($jqueryUrl = Router::buildPath(BASE_SCRIPTS, "vendor", "jquery", $jqueryPath))){
				$this->includeScript(HTML::script('window.jQuery||document.write(\'<script src="'.$jqueryUrl.'"><\/script>\')'));
			}
		}
		
		if(static::pathForAssetUrl($fastclickUrl = Router::buildPath(BASE_SCRIPTS, "vendor", "fastclick.js"))){
			$this->includeScript($fastclickUrl);
			$this->includeScript(HTML::script('if ("addEventListener" in document) {
				document.addEventListener("DOMContentLoaded", function() {
					FastClick.attach(document.body);
				}, false);
			}'));
		}
	}
		
	/**
	 *	Include Meta
	 *	Add Meta Information In View
	 *	@param string $nameOrSnipit
	 *	@param BOOL $content
	 *	@return \Touchbase\View\Assets
	 */
	public function includeMeta($nameOrSnipit, $content = null){
		
		if(!empty($content)){
			$nameOrSnipit = HTML::meta()->attr('name', $nameOrSnipit)->attr('content', $content);
		}
		
		$this->includeAsset(self::ASSET_META, $nameOrSnipit);
		
		return $this;
	}
	
	/**
	 *	Construct Meta
	 *	@return string
	 */
	public function meta(){
		return @$this->documentAssets[self::ASSET_META]['head']?:[];
	}
	
	/**
	 *	Clear Meta
	 *	@return \Touchbase\View\Assets
	 */
	public function clearMeta(){
		$this->documentAssets[self::ASSET_META] = [];
		
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
		$this->includeAsset(self::ASSET_CSS, $file, [
			"media" => $media
		]);
		return $this;
	}
	
	/**
	 *	Construct Styles
	 *	@return string
	 */
	public function styles(){		
		return @$this->documentAssets[self::ASSET_CSS]['head']?:[];
	}
	
	/**
	 *	Clear Styles
	 *	@return \Touchbase\View\Assets
	 */
	public function clearStyles(){
		$this->documentAssets[self::ASSET_CSS] = [];
		
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
		$this->includeAsset(self::ASSET_JS, $file, [
			'body' => !$head
		]);
		
		return $this;
	}
	
	/**
	 *	Construct Scripts
	 *	@param BOOL $head
	 *	@return string
	 */
	public function scripts($head = false){
		return @$this->documentAssets[self::ASSET_JS][$head?'head':'body']?:[];
	}
	
	/**
	 *	Clear Scripts
	 *	@return \Touchbase\View\Assets
	 */
	public function clearScripts(){
		$this->documentAssets[self::ASSET_JS] = [];
		
		return $this;
	}
	
	/**
	 *	Include Extra
	 *	Add Extra Elements To Head
	 *	@param string $name
	 *	@param array $args
	 *	@return \Touchbase\View\Assets
	 */
	public function includeExtra($name, $args = null){
		//Head Only Allows Certain Elements. Since We Have Covered The Rest These Three Are Left
		
		if($args){
			if(in_array($name, array('base', 'link', 'noscript')) && is_array($args)){
				$name = HTML::create($name)->attr($args);
			}
		}
		$this->includeAsset(self::ASSET_OTHER, $name);
		
		return $this;
	}
	
	/**
	 *	Construct Extra
	 *	@return string
	 */
	public function extra(){
		return @$this->documentAssets[self::ASSET_OTHER]['head']?:[];
	}
	
	/**
	 *	Clear Extra
	 *	@return \Touchbase\View\Assets
	 */
	public function clearExtra(){
		$this->documentAssets[self::ASSET_OTHER] = [];
	
		return $this;
	}
		
	/**
	 *	Push Title
	 *	Adds n amount of titles to the documentTitle array
	 *	@return \Touchbase\View\Assets
	 */
	public function pushTitle(){
		foreach(func_get_args() as $title){
			if(empty($title)) continue;
			
			if(is_array($title)){
				$this->documentTitle = array_merge($this->documentTitle, $title);
			} else {
				if(!in_array($title, $this->documentTitle)){
					array_push($this->documentTitle, $title);
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

	/**
	 *	Url For Path
	 *	@param string $path
	 *	@return string
	 */
	public static function urlForPath($path){
	
		if(substr($path, 0, strlen(PROJECT_PATH)) == PROJECT_PATH) {
			$path = trim(substr($path, strlen(PROJECT_PATH)), "/");
		}
		
		$assetMaps = StaticStore::shared()->get(ConfigStore::CONFIG_KEY)->get("assets")->get("asset_map")->getIterator();
		$assetMaps->uasort(function($a, $b) {
			return strlen($b) - strlen($a);
		});
		
		foreach($assetMaps as $assetMap => $searchPath){
			if(substr($path, 0, strlen($searchPath)) == $searchPath) {
				$path = Router::buildPath($assetMap, substr($path, strlen($searchPath)));
				break;
			}
		}
		
		return Router::absoluteUrl($path);
	}
	
	/**
	 *	Asset Map For Path
	 *	@param string $path
	 *	@return string
	 */
	public static function assetMapForPath($path){
		return substr(md5($path), 0, 6);
	}
	
	/**
	 *	Path For Asset Map
	 *	@param string $assetMap
	 *	@return string
	 */
	public static function pathForAssetMap($assetMap){
		return StaticStore::shared()->get(ConfigStore::CONFIG_KEY)->get("assets")->get("asset_map")->get($assetMap, null);
	}
	
	
	/**
	 *	Path For Asset Url
	 *	@param string assetUrl
	 *	@return string
	 */
	public static function pathForAssetUrl($assetUrl){
		if(Router::isSiteUrl($assetUrl)){
			list($assetMapFragment, $assetUrl) = explode("/", Router::relativeUrl($assetUrl), 2);
			
			//Does the file exist?
			$file = File::create([PROJECT_PATH, static::pathForAssetMap($assetMapFragment), $assetUrl]);
			if($file->exists()){
				return $file->path;	
			}
			
			//Is it a folder?
			$folder = Folder::create([PROJECT_PATH, static::pathForAssetMap($assetMapFragment), $assetUrl]);
			if($folder->exists()){
				return $folder->path;	
			}
			
			return null;
		} 
		
		return $assetUrl;
	}
	
	/**
	 *	Include Asset
	 *	Helper method to include the asset
	 *	@param string $assetType
	 *	@param string $file
	 *	@param array $options
	 *	@return VOID
	 */
	private function includeAsset($assetType, $file, $options = []){
		//Construct File Path
		if(is_array($file)){
			$file = call_user_func_array("Touchbase\Control\Router::buildPath", $file);
		}
		
		if(is_object($file) && $file instanceof HTML){
			$file = $file->render();
		}
		
		if(is_string($file)){
			if(!$this->isSnipit($assetType, $file)){
				if(!static::pathForAssetUrl($file)) return;
				
				
				switch($assetType){
					case self::ASSET_CSS:
						$file = HTML::link()->attr($options)->attr([
							"rel" => "stylesheet",
							"type" => "text/css",
							"href" => Router::absoluteURL($file)
						])->render();
					break;
					case self::ASSET_JS:
						$file = HTML::script()->attr($options)->attr([
							"type" => "text/javascript",
							"src" => Router::absoluteURL($file)
						])->render();
					break;
					default:
						return;
				}
			}
			
			if(empty($this->documentAssets[$assetType])){
				$this->documentAssets[$assetType] = [];
				$this->documentAssets[$assetType]['head'] = [];
				
				if($assetType == self::ASSET_JS){
					$this->documentAssets[$assetType]['body'] = [];
				}
			}
			
			if(!in_array($file, $this->documentAssets[$assetType][@$options['body']?'body':'head'])){
				$this->documentAssets[$assetType][@$options['body']?'body':'head'][] = $file;
			}
		} 
	}
	
	/**
	 *	Is Snipit
	 *	This method will determine whether the included item already contains the html tags required to display correctly
	 *	@param string $assetType
	 *	@param string $check
	 *	@return BOOL
	 */
	private function isSnipit($assetType, $check){
		switch($assetType){
			case self::ASSET_CSS:
				return strpos($check, "<link") !== false || strpos($check, "<style") !== false;
			break;
			case self::ASSET_JS:
				return strpos($check, "<script") !== false;
			break;
			case self::ASSET_META:
				return strpos($check, "<meta") !== false;
			break;
			case self::ASSET_OTHER:
				return strpos($check, "<link") !== false || strpos($check, "<base") !== false || strpos($check, "<noscript") !== false;
			break;
		}
		
		return false;
	}
}