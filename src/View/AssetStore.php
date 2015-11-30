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
use Touchbase\Core\SynthesizeTrait;
use Touchbase\Core\Config\ConfigTrait;
use Touchbase\Core\Config\Store as ConfigStore;

use Touchbase\Filesystem\Filesystem;

class AssetStore extends \Touchbase\Data\Store
{
	use ConfigTrait;
	use SynthesizeTrait;
	
	const JS = 'js';
	const CSS = 'css';
	const IMG = 'images';
	const META = 'meta';
	const OTHER = 'other';
	
	/**
	 *	@var \Touchbase\Control\Controller
	 */
	protected $controller;
	
	/* Public Methods */
		
	/** 
	 *	Shared
	 *	@return \Touchbase\Data\StaticStore
	 */
	public static function shared(){
		trigger_error(sprintf("%s, no longer has a global shared object. Use ->webpage()->self();", __METHOD__), E_USER_DEPRECATED);
	}
	
	/**
	 *	__construct
	 *	@param \Touchbase\Core\Config\Store $config
	 */
	public function __construct(ConfigStore $config = null){
		
		$this->setConfig($config);

	}
		
	/**
	 *	Include Meta
	 *	Add Meta Information In View
	 *	@param string $nameOrSnipit
	 *	@param BOOL $content
	 *	@return \Touchbase\View\self
	 */
	public function includeMeta($nameOrSnipit, $content = null){
		
		if(!empty($content)){
			$nameOrSnipit = HTML::meta()->attr('name', $nameOrSnipit)->attr('content', $content);
		}
		
		$this->includeAsset(self::META, $nameOrSnipit);
		
		return $this;
	}
	
	/**
	 *	Construct Meta
	 *	@return string
	 */
	public function meta(){
		return $this->get(self::META, []);
	}
	
	/**
	 *	Clear Meta
	 *	@return \Touchbase\View\self
	 */
	public function clearMeta(){
		$this->delete(self::META);
		
		return $this;
	}
	
	/**
	 *	Include Style
	 *	Add Style Information In View
	 *	@param string $file
	 *	@param string $media
	 *	@return \Touchbase\View\self
	 */
	public function includeStyle($file, $media = null){
		$this->includeAsset(self::CSS, $file, [
			"media" => $media
		]);
		
		return $this;
	}
	
	/**
	 *	Construct Styles
	 *	@return string
	 */
	public function styles(){
		return $this->get(self::CSS, []);
	}
	
	/**
	 *	Clear Styles
	 *	@return \Touchbase\View\self
	 */
	public function clearStyles(){
		return $this->delete(self::CSS, []);
		
		return $this;
	}
	
	/**
	 *	Include Scripts
	 *	Include JS Information In View
	 *	@param string $file 
	 *	@param BOOL $head - Pass true to include the script in the head tag
	 *	@return \Touchbase\View\self
	 */
	public function includeScript($file, $head = false){
		$this->includeAsset(self::JS, $file, [
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
		return $this->get(self::JS)->get($head?'head':'body', []);
	}
	
	/**
	 *	Clear Scripts
	 *	@return \Touchbase\View\self
	 */
	public function clearScripts(){
		$this->delete(self::JS);
		
		return $this;
	}
	
	/**
	 *	Include Extra
	 *	Add Extra Elements To Head
	 *	@param string $name
	 *	@param array $args
	 *	@return \Touchbase\View\self
	 */
	public function includeExtra($name, $args = null){
		//Head Only Allows Certain Elements. Since We Have Covered The Rest These Three Are Left
		
		if($args){
			if(in_array($name, array('base', 'link', 'noscript')) && is_array($args)){
				$name = HTML::create($name)->attr($args);
			}
		}
		$this->includeAsset(self::OTHER, $name);
		
		return $this;
	}
	
	/**
	 *	Construct Extra
	 *	@return string
	 */
	public function extra(){
		return $this->get(self::OTHER, []);
	}
	
	/**
	 *	Clear Extra
	 *	@return \Touchbase\View\self
	 */
	public function clearExtra(){
		$this->delete(self::OTHER);
	
		return $this;
	}
		
	/**
	 *	Push Title
	 *	Adds n amount of titles to the documentTitle array
	 *	@return \Touchbase\View\self
	 */
	public function pushTitle(){
		$this->pushUnique("title", is_array(func_get_arg(0)) ? func_get_arg(0) : func_get_args());
		
		return $this;
	}
	
	/**
	 *	Pop Title
	 *	@return string
	 */
	public function popTitle(){
		return $this->pop("title");
	}
	
	/**
	 *	Clear Title
	 *	@return \Touchbase\View\self
	 */
	public function clearTitle(){
		$this->delete("title");
		
		return $this;
	}
	
	/**
	 *	Construct Title
	 *	@param BOOL $reverse
	 *	@return string 
	 */
	public function contsructTitle($reverse = false){
		$title = $this->get("title");
		$titleData = (!$reverse)?array_reverse($title):$title;
		
		return implode(" ".$this->config()->get("web")->get("title_separator", "|")." ", array_map('ucfirst', array_unique($titleData))); 
	}

	/**
	 *	Url For Path
	 *	@param string $path
	 *	@return string
	 */
	public static function urlForPath($path){
		$path = Router::relativeUrl($path);
		
		$assetMaps = StaticStore::shared()->get(ConfigStore::CONFIG_KEY)->get("assets")->get("asset_map");
		$assetMaps->uasort(function($a, $b) {
			return strlen($b) - strlen($a);
		});
		
		foreach($assetMaps as $assetMap => $searchPath){
			if(strcasecmp(substr($path, 0, strlen($searchPath)), $searchPath) === 0) {
				$path = Router::buildPath($assetMap, substr($path, strlen($searchPath)));
				break;
			}
		}
		
		return Router::absoluteUrl($path);
	}
	
	/**
	 *	Url For Asset
	 *	@param string $path
	 *	@return string
	 */
	public function urlForImage($image){
		return $this->urlForAsset(AssetStore::IMG, $image);
	}
	public function urlForScript($script){
		return $this->urlForAsset(AssetStore::JS, $script);
	}
	public function urlForStyle($stylesheet){
		return $this->urlForAsset(AssetStore::CSS, $stylesheet);
	}
	public function urlForAsset($assetType, $image){
		$asset = $this->assetIncludeSnipit($assetType, $image, []);
		
		if($assetType == AssetStore::CSS){
			return $asset->attr("href");
		}
		
		return $asset->attr("src");
	}
	
	/* Private Methods */
	
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
				$file = $this->assetIncludeSnipit($assetType, $file, $options);
			}
			
			if($file){
				if($assetType != self::JS){
					$this->pushUnique($assetType, $file);
				} else {
					$this->get($assetType)->pushUnique(@$options['body']?'body':'head', $file);
				}
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
			case self::CSS:
				return strpos($check, "<link") !== false || strpos($check, "<style") !== false;
			break;
			case self::JS:
				return strpos($check, "<script") !== false;
			break;
			case self::META:
				return strpos($check, "<meta") !== false;
			break;
			case self::OTHER:
				return strpos($check, "<link") !== false || strpos($check, "<base") !== false || strpos($check, "<noscript") !== false;
			break;
		}
		
		return false;
	}
	
	/**
	 *	Asset Include Snipit
	 *	This will find an asset and import it with the correct html tag
	 *	@param string $assetType
	 *	@param string $file
	 *	@param array $options
	 *	@return string - The html snipit
	 */
	private function assetIncludeSnipit($assetType, $file, $options){
		
		foreach($this->assetSearchPaths($assetType) as $path){
			if(Router::pathForAssetUrl($filePath = Router::buildPath($path, $file), $assetType)){
				switch($assetType){
					case self::CSS:
						return HTML::link()->attr($options)->attr([
							"rel" => "stylesheet",
							"type" => "text/css",
							"href" => $filePath
						]);
					break;
					case self::JS:
						return HTML::script()->attr($options)->attr([
							"type" => "text/javascript",
							"src" => $filePath
						]);
					break;
					case self::IMG:
						return HTML::img()->attr($options)->attr([
							"src" => $filePath
						]);
					break;
				}
			}
		}
		
		return NULL;
	}
	
	/**
	 *	Asset Search Paths
	 *	@param string $type - CSS | JS | IMAGES
	 *	@return Generator<string> - A file path to search for self
	 */
	private function assetSearchPaths($type){
		//Search order
		// - Application/self/{type}/Theme/
		// - Application/self/{type}
		// - Base/self/{type}/Theme
		// - Base/self/{type}
		
		$assetConfig = $this->config("assets");
		$assetsPath = $assetConfig->get("assets", "Assets");
		$typePath = $assetConfig->get($type, $type.DIRECTORY_SEPARATOR);
		
		$searchPaths[] = null; //This allows the use of an absolute path to be used when merging paths.
		if($this->controller){ 
			$searchPaths[] = Filesystem::buildPath($this->controller->applicationPath, $assetsPath, $typePath, $this->controller->theme());
			$searchPaths[] = Filesystem::buildPath($this->controller->applicationPath, $assetsPath, $typePath);
		}
		$searchPaths[] = Filesystem::buildPath(PROJECT_PATH, $assetsPath, $typePath, $this->controller->theme());
		$searchPaths[] = Filesystem::buildPath(PROJECT_PATH, $assetsPath, $typePath);
		
		foreach($searchPaths as $searchPath){
			yield $searchPath ? static::urlForPath($searchPath) : $searchPath;
		}
	}
}