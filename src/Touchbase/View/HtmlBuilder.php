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


class HtmlBuilder extends \Touchbase\Core\Object
{

	private $allowInvalidTags = false;  
	private $allowInvalidAttributes = false;
	private $htmlMode = "HTML5";

	//HTML5 All Tags
	private $validTags = array("HTML5"=>array("a","abbr","address","area","article","aside","audio","b","base","bdi","bdo","blockquote",
										"body","br","button","canvas","caption","cite","code","col","colgroup","command","datalist",
										"dd","del","details","dfn","div","dl","dt","em","embed","fieldset","figcaption","figure",
										"footer","form","h1","h2","h3","h4","h5","h6","head","header","hgroup","hr","html","i","iframe",
										"img","input","ins","keygen","kbd","label","legend","li","link","map","mark","menu","meta",
										"meter","nav","noscript","object","ol","optgroup","option","output","p","param","pre","progress",
										"q","rp","rt","ruby","s","samp","script","section","select","small","source","span","strong","style",
										"sub","summary","sup","table","tbody","td","textarea","tfoot","th","thead","time","title","tr","track",
										"u","ul","var","video","wbr"));
										
	//HTML5 Void Tags
	private $voidTags = array("HTML5"=>array("area", "base", "br", "col", "command", "embed", "hr", "img", "input", "keygen", "link", "meta", 
											 "param", "source", "track", "wbr"));
	
	//HTML5 Global Attributes
	private $globalAttributes = array("HTML5"=>array("accesskey", "class", "contenteditable", "contextmenu", "dir", "draggable", "dropzone", "hidden",
											   		 "id", "lang", "spellcheck", "style", "tabindex", "title"));
	
	//HTML5 Event Attributes
	private $eventAttributes = array("HTML5"=>array("WINDOW"=>array("onafterprint", "onbeforeprint", "onbeforeonload", "onblur", "onerror", "onfocus",
																	"onhashchange", "onload", "onmessage", "onoffline", "ononline", "onpagehide",
																	"onpageshow", "onpopstate", "onredo", "onresize", "onstorage", "onundo"),
													"FORM"=> array("onblur", "onchange", "oncontextmenu", "onfocus", "onformchange", "onforminput",
													  			   "oninput", "oninvalid", "onselect", "onsubmit"),
													"KEYBOARD"=>array("onkeydown", "onkeypress", "onkeyup"),
													"MOUSE"=> array("onclick", "ondblclick", "ondrag", "ondragend", "ondragenter", "ondragleave",
													   				"ondragover", "ondragstart", "ondrop", "onmousedown", "onmousemove", "onmouseout",
													   				"onmouseup", "onmousewheel", "onscroll"),
													"MEDIA"=> array("onabort", "oncanplay", "oncanplaythrough", "ondurationchange", "onemptied",
													   				"onended", "onerror", "onloadeddata", "onloadedmetadata", "onloadstart",
													   				"onpause", "onplay", "onplaying", "onprogress", "onratechange", "onreadystatechange",
													   				"onseeked", "onseeking", "onstalled", "onsuspened", "ontimeupdate", "onvolumechange",
													   				"onwaiting")));
													   				
	//Tag Specific Attributes
	private $tagAttributes = array(
		"HTML"=>array("manifest", "xmlns"),
		"IMG"=>array("src","alt","height","ismap","usemap","width"),
		"A"=>array("href", "hreflang", "media", "rel", "target", "type"),
		"LINK"=>array("href", "hreflang", "media", "rel", "sizes", "type"),
		"STYLE"=>array("type", "media", "scoped"),
		"SCRIPT"=>array("async", "defer", "type", "charset", "src"),
		"META"=>array("charset", "content", "http-equiv", "name"),
		"FORM"=>array("accept","accept-charset","action","autocomplete","enctype","method","name","novalidate","target"),
		"INPUT"=>array("accept","align","alt","autocomplete","autofocus","checked","disabled","form","formaction","formenctype","formmethod","formnovalidate","formtarget","height","width","list","min","max","maxlength","multiple","name","pattern","placeholder","readonly","required","size","src","step","type","value"),
		"BUTTON"=>array("autofocus","disabled","form","formaction","formenctype","formmethod","formnovalidate","formtarget","name","type","value"),
		"TEXTAREA"=>array("autofocus","cols","disabled","form","maxlength","name","placeholder","readonly","required","rows","wrap"),
		"SELECT"=>array("autofocus", "disabled", "form", "multiple", "name", "size"),
		"OPTION"=>array("disabled","label","selected","value"),
		"LABEL"=>array("for", "form")
	);
	
	//CLOSE VOID TAGS	
	private $closeVoidTags = true;
	
	//Options
	private $tag;
	private $attributes= array();
	private $style;
	private $class;
	private $content;
	private $open;
	private $close;
												   
	public function __construct($tag, $content = false, $attributes = array(), $open = true, $close = true){
		if(!$this->allowInvalidTags && !in_array($tag, $this->validTags[$this->htmlMode])){
			//Invalid Tag!
			return false;
		}
		
		//Set Tag
		$this->tag = $tag;
		
		//Set Content
		$this->content = $content;
		
		//Set Attributes
		if(!empty($attributes)){
			$this->setAttributes($attributes);
		}
		
		//Set Options
		$this->open = !empty($open)?true:false;
		$this->close = !empty($close)?true:false;
		
		return $this;
	}	
	
	public static function make(){
		return call_user_func_array('SELF::create', func_get_args());
	}
	
	public static function make_r(){
		return self::make(func_get_args())->output();
	}
	
	public function output($escape = false){
		$returnContent = $this->build($this->open, $this->close);
		return ($escape)?htmlspecialchars($returnContent):$returnContent;
	}
	
	public function multiOutput($num = 1){
		$returnContent = $this->build($this->open, $this->close);	
		return str_repeat($returnContent, $num);
	}
	
	//Magic to remove the need to use ->output everytime.
	public function __toString(){
		return $this->output();
	}
	
	/* jQueryEsc Functions */
	public function addClass($className){
		$this->setAttributes("class", $className);
		return $this;
	}
	
	public function removeClass($className = false){
		if($className){
			$this->removeAttributeValue("class",$className);
		} else {
			$this->removeAttribute("class");
		}
		return $this;	
	}
	
	public function attr($get, $set = null){
		if(isset($set) || is_array($get)){
			$this->setAttributes($get, $set);
		} else {
			return @$this->attributes[$get];
		}
		return $this;
	}
	
	//public function setAttr($attr, $value = false){
	//	$this->setAttributes($attr, $value);
	//	return $this;
	//}
	public function allAttributes(){
		return $this->attributes;
	}
	
	public function removeAttr($attr){
		$this->removeAttributes($attr);
		return $this;
	}
	
	public function resetAttr(){
		$this->removeAttributes(null, true);
		return $this;
	}
	
	public function html($html){
		$this->content = "\n".$html."\n";
		return $this;
	}
	
	public function append($html){
		$this->content .= "\n".$html."\n";
		return $this;
	}
	
	public function prepend($html){
		$this->content = "\n".$html."\n" . $this->content;
		return $this;
	}
	
	public function css($get, $set = null){
		if(isset($set) || (!isset($set) && is_array($get))){
			$this->setStyle($get, $set);
		} else {
			return $this->style[$get];
		}
		
		return $this;
	}
	
	/* Processing Functions */
	protected function validate(){
		//This Checks whether attibutes are valid. -> also allowes data-*
		if(!empty($this->attributes)){
			foreach($this->attributes as $attrName => $attrValue){
				if(!in_array($attrName, $this->globalAttributes[$this->htmlMode]) && 
				   !$this->in_multiarray($attrName, $this->eventAttributes[$this->htmlMode]) &&
				   !in_array($attrName, $this->tagAttributes[strtoupper($this->tag)])){
					if(strpos($attrName, "data-") !== 0){
						unset($this->attributes[$attrName]);
					}
				}
			}
		}
	}

	protected function setAttributes($attr, $value = null){
		//To allow foreach.
		if(!is_array($attr)){
			$attr = array($attr => $value);
		}
		
		if(is_array($attr)){
			$attr = array_change_key_case($attr);
			
			//Style Special Case
			if(isset($attr['style'])){
				$this->css($this->styleStringToArray($attr['style']));
				unset($attr['style']);
			}
			
			//Look for class attrs and make sure they make the recursive array.
			if(isset($attr['class'])){
				if(!is_array($attr['class'])){
					$attr['class'] = explode(" ", $attr['class']);
				}
			}
			
			if($this->is_multiarray($attr)){
				$this->attributes = array_merge_recursive($this->attributes, $attr);
			} else {
				$this->attributes = array_merge($this->attributes, $attr);
			}
		}
	}

/*
	protected function setAttributes($attr, $value = false){
		if(is_array($attr)){
			$attr = array_change_key_case($attr);
			if(!empty($this->attributes)){
				if($this->is_multiarray($attr)){
					$this->attributes = array_merge_recursive($this->attributes, $attr);
				} else {
					$this->attributes = array_merge($this->attributes, $attr);
				}
			} else {
				$this->attributes = $attr;
			}
		} else {
			if(!empty($value)){
				$attr = strtolower($attr);
				$this->attributes[$attr] = $value;
			}
		}
	}
*/

	protected function removeAttributes($attr, $all = false){
		if($all){
			$this->attributes = array();
		} else {
			$attr = strtolower($attr);
			if(isset($this->attributes[$attr])){
				unset($this->attributes[$attr]);
			}
		}
	}
	
	protected function removeAttributeValue($attr, $value){
		$attr = strtolower($attr);
		if(is_array($this->attributes[$attr])){
			$this->attributes[$attr] = $this->array_remove_value($value, $this->attributes[$attr]);
		}
	}
	
	protected function setStyle($attr, $value = false){
		if(is_array($attr)){
			$attr = array_change_key_case($attr);
			if(!empty($this->style)){
				$this->style = array_merge($this->style, $attr);
			} else {
				$this->style = $attr;
			}
		} else {
			if(!empty($value)){
				$this->style[strtolower($attr)] = $value;
			}
		}
	}
	
	protected function styleStringToArray($str){
		//Multiple Statements
		$return = array();
		
		//Trim
		$str = trim($str);
		
		//Terminate String if Needed 
		if(strpos($str, ";") === false || $str[strlen($str)-1] != ";"){
			$str = $str.";";
		}
		
		//Explode :)
		$statements = explode(";", $str);
		foreach($statements as $statement){
			if(strpos($statement, ":") !== false){
				//Split at first : to avoid messing up ( url(Http://) )
				$styleParts = explode(":", $statement, 2);
				$return[trim($styleParts[0])] = trim($styleParts[1]);
			}
		}
		
		return $return;
	}
	
	protected function build($open, $close){
		$returnContent = '';
		$voidTag = in_array($this->tag, $this->voidTags[$this->htmlMode]);
		
		if($open){
			//Start Tag
			$returnContent .= '<'.$this->tag;
			
			//Add Attributes
			if(!$this->allowInvalidAttributes){
				$this->validate();
			}
			
			if(!empty($this->attributes)){
				foreach($this->attributes as $attr=>$value){			
					if(is_array($value)){
						$value = implode(" ", array_unique($value));
					}
					if(!empty($value) || $value === 0){
						$returnContent .= ' '.$attr.'="'.trim($value).'"';
					}
				}
			}
			
			//Add Inline style
			if(!empty($this->style)){
				$returnContent .= ' style="';
				foreach($this->style as $attr=>$value){
					$returnContent .= $attr.':'.$value.';';
				}
				$returnContent .= '"';
			}
			
			//Add Content
			if(!$voidTag && !empty($this->content)){
				if(is_object($this->content) && method_exists($this->content, "output")){
					$this->content = $this->content->output();
				}
			
				$returnContent .= '>'.$this->content;
			} else if(!$voidTag) {
				$returnContent .= '>';
 			}
		}
		if($close){
			//Close Tag
			if($voidTag){
				if($this->closeVoidTags){
					$returnContent .= '/>';
				} else {
					$returnContent .= '>';
				}
			} else {
				$returnContent .= '</'.$this->tag.'>';
			}
		}
		
		return $returnContent;	
	}
	
	private function array_remove_value($val, $arr){
		foreach ($arr as $key => $value){
			if ($arr[$key] == $val){
				unset($arr[$key]);
			}
		}
		return $arr;
	}
	
	private function is_multiarray($array) {
	    return (count($array) != count($array, 1));
	}
	
	private function in_multiarray($elem, $array) { 
	    // if the $array is an array or is an object 
	     if( is_array( $array ) || is_object( $array ) ) 
	     { 
	         // if $elem is in $array object 
	         if( is_object( $array ) ) 
	         { 
	             $temp_array = get_object_vars( $array ); 
	             if( in_array( $elem, $temp_array ) ) 
	                 return TRUE; 
	         } 
	         
	         // if $elem is in $array return true 
	         if( is_array( $array ) && in_array( $elem, $array ) ) 
	             return TRUE; 
	             
	         
	         // if $elem isn't in $array, then check foreach element 
	         foreach( $array as $array_element ) 
	         { 
	             // if $array_element is an array or is an object call the in_multiarray function to this element 
	             // if in_multiarray returns TRUE, than return is in array, else check next element 
	             if( ( is_array( $array_element ) || is_object( $array_element ) ) && $this->in_multiarray( $elem, $array_element ) ) 
	             { 
	                 return TRUE; 
	                 exit; 
	             } 
	         } 
	     } 
	     
	     // if isn't in array return FALSE 
	     return FALSE; 
	}

}

?>