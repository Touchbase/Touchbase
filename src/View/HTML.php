<?php

/**
 *  Copyright (c) 2013 William George.
 *
 *  Permission is hereby granted,free of charge,to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"),to deal
 *  in the Software without restriction,including without limitation the rights
 *  to use,copy,modify,merge,publish,distribute,sublicense,and/or sell
 *  copies of the Software,and to permit persons to whom the Software is
 *  furnished to do so,subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS",WITHOUT WARRANTY OF ANY KIND,EXPRESS OR
 *  IMPLIED,INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,DAMAGES OR OTHER
 *  LIABILITY,WHETHER IN AN ACTION OF CONTRACT,TORT OR OTHERWISE,ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 *
 *  @author William George
 *  @package Touchbase
 *  @category View
 *  @date 28/02/2015
 */

namespace Touchbase\View;

defined('TOUCHBASE') or die("Access Denied.");

class HTML extends \Touchbase\Core\BaseObject
{
	/**
	 *	@var BOOL
	 */
	public static $allowInvalidTags = false;
	
	/**
	 *	@var BOOL
	 */
	public static $allowInvalidAttributes = false;
	
	/**
	 *	@var BOOL
	 */
	public static $closeVoidTags = true;

	/**
	 *	@var string
	 */
	protected $tag;
	
	/**
	 *	@var array
	 */
	protected $attributes = [];
	
	/**
	 *	@var string
	 */
	protected $content;
	
	/**
	 *	HTML5 All Tags
	 *	@var array
	 */
	private $validTags = ["a","abbr","address","area","article","aside","audio","b","base","bdi","bdo","blockquote",
			"body","br","button","canvas","caption","cite","code","col","colgroup","command","datalist",
			"dd","del","details","dfn","div","dl","dt","em","embed","fieldset","figcaption","figure",
			"footer","form","h1","h2","h3","h4","h5","h6","head","header","hgroup","hr","html","i","iframe",
			"img","input","ins","keygen","kbd","label","legend","li","link","map","mark","menu","meta",
			"meter","nav","noscript","object","ol","optgroup","option","output","p","param","pre","progress",
			"q","rp","rt","ruby","s","samp","script","section","select","small","source","span","strong","style",
			"sub","summary","sup","table","tbody","td","textarea","tfoot","th","thead","time","title","tr","track",
			"u","ul","var","video","wbr"];

	/**
	 *	HTML5 VOID Tags
	 *	@var array
	 */
	private $voidTags = ["area","base","br","col","command","embed","hr","img","input","keygen","link","meta","param","source","track","wbr"];

	/**
	 *	HTML5 Global Attributes
	 *	@var array
	 */
	private $validAttributes = [
		"GLOBAL"=>["accesskey","class","contenteditable","contextmenu","dir","draggable","dropzone","hidden","id","lang","spellcheck","style","tabindex","title","onblur","onchange","oncontextmenu","onfocus","onformchange","onforminput","oninput","oninvalid","onselect","onsubmit","onkeydown","onkeypress","onkeyup","onclick","ondblclick","ondrag","ondragend","ondragenter","ondragleave","ondragover","ondragstart","ondrop","onmousedown","onmousemove","onmouseout","onmouseup","onwheel","onscroll","onabort","oncanplay","oncanplaythrough","ondurationchange","onemptied","onended","onerror","onloadeddata","onloadedmetadata","onloadstart","onpause","onplay","onplaying","onprogress","onratechange","onreadystatechange","onseeked","onseeking","onstalled","onsuspened","ontimeupdate","onvolumechange","onwaiting"],
		"HTML"=>["manifest","xmlns"],
		"BODY"=>["onafterprint","onbeforeprint","onbeforeonload","onhashchange","onload","onmessage","onoffline","ononline","onpagehide","onpageshow","onpopstate","onredo","onresize","onstorage","onundo"],
		"IMG"=>["src","alt","height","ismap","usemap","width"],
		"A"=>["href","hreflang","media","rel","target","type"],
		"BASE"=>["href","target"],
		"LINK"=>["href","hreflang","media","rel","sizes","type"],
		"STYLE"=>["type","media","scoped"],
		"SCRIPT"=>["async","defer","type","charset","src"],
		"META"=>["charset","content","http-equiv","name"],
		"FORM"=>["accept","accept-charset","action","autocomplete","enctype","method","name","novalidate","target"],
		"INPUT"=>["accept","align","alt","autocomplete","autofocus","checked","disabled","form","formaction","formenctype","formmethod","formnovalidate","formtarget","height","width","list","min","max","maxlength","multiple","name","pattern","placeholder","readonly","required","size","src","step","type","value"],
		"BUTTON"=>["autofocus","disabled","form","formaction","formenctype","formmethod","formnovalidate","formtarget","name","type","value"],
		"TEXTAREA"=>["autofocus","cols","disabled","form","maxlength","name","placeholder","readonly","required","rows","wrap"],
		"SELECT"=>["autofocus","disabled","form","multiple","name","size"],
		"OPTION"=>["disabled","label","selected","value"],
		"LABEL"=>["for","form"]
	];
	
	/* Public Methods */
	
	/**
	 *	__construct
	 *	@param string $tag
	 *	@param string $content
	 *	@param array $attributes
	 */
	public function __construct($tag, $content = false, $attributes = []) {
		if(!static::$allowInvalidTags && !in_array($tag, $this->validTags)){
			return;
		}
		
		$this->tag = $tag;
		$this->content = $content;
		$this->setAttributes($attributes);
	}
	
	/**
	 *	__callStatic
	 *	Magic method to allow use of HTML::tagName()
	 *	@param string $tag
	 *	@param array $arguments
	 *	@return \Touchbase\View\HTML
	 */
	public static function __callStatic($tag, $arguments){
		return call_user_func_array('static::create', array_merge([$tag], $arguments));
	}
	
	/**
	 *	__call
	 *	Magic method to allow use ->href()->rel();
	 *	@param string $attr
	 *	@param array $arguments
	 *	@return \Touchbase\View\HTML
	 */
	public function __call($attr, array $arguments){
		return $this->attr($attr, implode(" ", $arguments));
	}
	
	/**
	 *	Open
	 *	This method will open the given tag
	 *	@param BOOL $escape
	 *	@return string
	 */
	public function open($escape = false){
		return $this->render(1, $escape, true, false);
	}
	
	/**
	 *	Close
	 *	This method will close the given tag
	 *	@param BOOL $escape
	 *	@return string
	 */	
	public function close($escape = false){
		return $this->render(1, $escape, false, true);
	}
	
	/**
	 *	Render
	 *	This method will output the full tag
	 *	@param int $repeat
	 *	@param BOOL $escape
	 *	@param BOOL $open
	 *	@param BOOL $close
	 *	@return string
	 */	
	public function render($repeat = 1, $escape = false, $open = true, $close = true) {
		$returnContent = str_repeat($this->build($open, $close), $repeat);
		return ($escape)?htmlspecialchars($returnContent):$returnContent;
	}

	/**
	 *	__toString
	 *	Magic to remove the need to use ->output everytime.
	 *	@return string
	 */
	public function __toString() {
		return $this->render();
	}

	/* jQueryEsc Methods */
	
	/**
	 *	Add Class
	 *	@param string $className
	 *	@return \Touchbase\View\HTML
	 */
	public function addClass($className) {
		$this->setAttributes("class", $className);
		return $this;
	}
	
	/**
	 *	Remove Class
	 *	@discussion If no class name is passed, all classes will be removed
	 *	@param string $className
	 *	@return \Touchbase\View\HTML
	 */
	public function removeClass($className = false) {
		if ($className) {
			$this->removeValueFromArray($this->attributes["class"], $className);
		} else {
			$this->removeAttr("class");
		}
		return $this;
	}
	
	/**
	 *	Attr
	 *	@param string $get
	 *	@param string $set
	 *	@return \Touchbase\View\HTML
	 */
	public function attr($get, $set = null) {
		if (isset($set) || is_array($get)) {
			$this->setAttributes($get, $set);
		} else {
			return @$this->attributes[$get];
		}
		return $this;
	}

	/**
	 *	All Attributes
	 *	@return array
	 */
	public function allAttributes() {
		return $this->attributes;
	}
	
	/**
	 *	Remove Attr
	 *	@param string $attr
	 *	@return \Touchbase\View\HTML
	 */
	public function removeAttr($attr) {
		$this->removeKeyFromArray($this->attributes, $attr);
		return $this;
	}

	/**
	 *	Reset Attr
	 *	@return \Touchbase\View\HTML
	 */
	public function resetAttr() {
		$this->removeKeyFromArray($this->attributes, null, true);
		return $this;
	}

	/**
	 *	Html
	 *	@param string $html
	 *	@return \Touchbase\View\HTML
	 */
	public function content($html) {
		$this->content = "\n".$html."\n";
		return $this;
	}

	/**
	 *	Append
	 *	@param string $html
	 *	@return \Touchbase\View\HTML
	 */
	public function append($html) {
		$this->content .= "\n".$html."\n";
		return $this;
	}

	/**
	 *	Prepend
	 *	@param string $html
	 *	@return \Touchbase\View\HTML
	 */
	public function prepend($html) {
		$this->content = "\n".$html."\n" . $this->content;
		return $this;
	}
	
	/**
	 *	CSS
	 *	This method add css attributes
	 *	@pararm array | string $get
	 *	@pararm string $set
	 *	@return mixed
	 */
	public function css($get, $set = null) {
		if(!isset($set) && is_array($get)){
			$this->setAttributes("style", $get);
		} else if(isset($set)){
			$this->setAttributes("style", [$get => $set]);
		} else {
			return @$this->attributes['style'][$get];
		}

		return $this;
	}

	/* Processing Functions */
	
	/**
	 *	Validate
	 *	This method will remove invalid attributes from the html tag
	 *	@return VOID
	 */
	protected function validate() {
		//This Checks whether attibutes are valid. -> also allowes data-*
		foreach($this->attributes as $attrName => $attrValue){
			if(!in_array($attrName, $this->validAttributes["GLOBAL"])
			&& (array_key_exists($tag = strtoupper($this->tag), $this->validAttributes) && !in_array($attrName, $this->validAttributes[$tag]))
			&& strpos($attrName, "data-") !== 0) {
				unset($this->attributes[$attrName]);
			}
		}
	}

	/**
	 *	Set Attributes
	 *	@param array | string $attr
	 *	@param string $value
	 *	@return VOID
	 */
	protected function setAttributes($attr, $value = null) {
		if(!is_array($attr)){
			$attr = [$attr => $value];
		}

		$attr = array_change_key_case($attr);

		//Style Special Case
		if(isset($attr['style'])){
			if(!is_array($attr['style'])){
				$attr['style'] = $this->styleStringToArray($attr['style']);
			}
			
			if(isset($this->attributes['style'])){
				foreach(array_keys($attr['style']) as $unsetKey){
					$this->removeKeyFromArray($this->attributes['style'], $unsetKey);
				}
			}
		}

		//Look for class attrs and make sure they make the recursive array.
		if (isset($attr['class']) && !is_array($attr['class'])) {
			$attr['class'] = explode(" ", $attr['class']);
		}
		
		$this->attributes = array_merge_recursive($this->attributes, $attr);
	}

	/**
	 *	Remove Key From Array
	 *	This method will remove a value from an array
	 *	@pararm array $array
	 *	@param string $key
	 *	@param BOOL $all
	 *	@return VOID
	 */
	protected function removeKeyFromArray(&$array, $key, $all = false) {
		if ($all) {
			$array = [];
		} else {
			$key = strtolower($key);
			if(isset($array[$key])){
				unset($array[$key]);
			}
		}
	}

	/**
	 *	Remove Value From Array
	 *	This method will remove a value from an array
	 *	@pararm array $array
	 *	@param mixed $value
	 *	@return VOID
	 */
	protected function removeValueFromArray(&$array, $value) {
		if(is_array($array)){
			foreach($array as $key => $val){
				if($val == $value) unset($array[$key]);
			}
		}
	}
	
	/**
	 *	Style String To Array
	 *	This method will return an array from a css style string
	 *	@param string $str
	 *	@return array
	 */
	protected function styleStringToArray($str) {
		//Multiple Statements
		$return = array();

		//Trim
		$str = trim($str);

		//Terminate String if Needed
		if (strpos($str, ";") === false || $str[strlen($str)-1] != ";") {
			$str = $str.";";
		}

		//Explode :)
		$statements = explode(";", $str);
		foreach ($statements as $statement) {
			if (strpos($statement, ":") !== false) {
				//Split at first : to avoid messing up ( url(Http://) )
				$styleParts = explode(":", $statement, 2);
				$return[trim($styleParts[0])] = trim($styleParts[1]);
			}
		}

		return $return;
	}

	/**
	 *	Build
	 *	@param BOOL $open
	 *	@param BOOL $close
	 *	@return string
	 */
	protected function build($open, $close) {
		$returnContent = '';
		$voidTag = in_array($this->tag, $this->voidTags);

		//Start Tag
		if ($open) {
			$returnContent .= '<'.$this->tag;

			//Add Attributes
			if (!static::$allowInvalidAttributes) {
				$this->validate();
			}

			if (!empty($this->attributes)) {
				foreach($this->attributes as $attr => $value){
					if (is_array($value)) {
						if($attr == "style"){
							$value = implode(' ', array_map(function($key, $val){ 
								return $key.':'.$val.';';
							}, array_keys($value), $value));
						} else {
							$value = implode(" ", array_unique($value));
						}
					}
					if (!empty($value) || $value === 0) {
						$returnContent .= ' '.$attr.'="'.trim($value).'"';
					}
				}
			}
			
			//Add Content
			if (!$voidTag && !empty($this->content)) {
				$returnContent .= ">".$this->content;
			} else if (!$voidTag) {
				$returnContent .= '>';
			}
		}
		
		//Close Tag
		if ($close) {
			if ($voidTag) {
				$returnContent .= (static::$closeVoidTags?"/":"").">\n";
			} else {
				$returnContent .= "</".$this->tag.">\n";
			}
		}
		
		return $returnContent;
	}
}