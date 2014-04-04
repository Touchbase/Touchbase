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
 *  @category Utils
 *  @date 23/12/2013
 */
 
namespace Touchbase\Utils;

class Sanitizer extends \Touchbase\Utils\Object {
	
	//Transliterator converts non-standard chars into ASCII
	protected $useTransliterator = true;
	protected $transliterator;
	
	public function string($str, $allowed = null) {
		$allow = null;
		if (!empty($allowed)) {
			if(is_array($allowed)){
				foreach ($allowed as $value) {
					$allow .= "\\$value";
				}
			} else {
				$allow .= "\\$allowed";
			}
		}
		
		if(is_array($str)){
			foreach($str as $key => $val){
				$str[$key] = $this->string($val, $allowed);
			}
		} else {
			$transliterator = $this->getTransliterator();
			if($transliterator){
				$str = $transliterator->toASCII($str);
			}
		
			$str = preg_replace("/[^{$allow}a-zA-Z0-9]/", '', $str);
		}
		return $str;
	}
	
	public function html($str, $options = array()) {

		if(!is_array($options)){
			//Array merge needs $options to be an array | Lets ignore this users input.
			$options = array();
		}
		
		$options = array_merge(array(
			'remove' => false,
			'charset' => 'UTF-8',
			'quotes' => ENT_QUOTES,
			'double' => true
		), $options);

		if ($options['remove']) {
			$str = $this->stripAllTags($str);
		}

		return htmlentities($str, $options['quotes'], $options['charset'], $options['double']);
	}
		
	public function trimWhitespace($str) {
		$r = preg_replace('/[\n\r\t]+/', '', $str);
		return preg_replace('/\s{2,}/u', ' ', $r);
	}
	
	public function stripSpecificTags($str) {
		$params = func_get_args();

		for ($i = 1, $count = count($params); $i < $count; $i++) {
			$str = preg_replace('/<' . $params[$i] . '\b[^>]*>/i', '', $str);
			$str = preg_replace('/<\/' . $params[$i] . '[^>]*>/i', '', $str);
		}
		return $str;
	}	
	
	function stripAllTags($str){
	    $str = preg_replace(
	        array(
	          // Remove invisible content
	            '@<head[^>]*?>.*?</head>@siu',
	            '@<style[^>]*?>.*?</style>@siu',
	            '@<script[^>]*?.*?</script>@siu',
	            '@<object[^>]*?.*?</object>@siu',
	            '@<embed[^>]*?.*?</embed>@siu',
	            '@<applet[^>]*?.*?</applet>@siu',
	            '@<noframes[^>]*?.*?</noframes>@siu',
	            '@<noscript[^>]*?.*?</noscript>@siu',
	            '@<noembed[^>]*?.*?</noembed>@siu',
	          // Add line breaks before and after blocks
	            '@</?((address)|(blockquote)|(center)|(del))@iu',
	            '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
	            '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
	            '@</?((table)|(th)|(td)|(caption))@iu',
	            '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
	            '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
	            '@</?((frameset)|(frame)|(iframe))@iu',
	        ),
	        array(
	            ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
	            "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
	            "\n\$0", "\n\$0",
	        ),
	        $str);
	    return strip_tags($str);
	}
	
	
	//TRANSLITERATOR SETTINGS	
	private function getTransliterator() {
		if($this->transliterator === null && $this->useTransliterator) {
			$this->transliterator = load()->getInstance('Utils\Transliterator');
		} 
		return $this->transliterator;
	}
	
	public function setTransliterator($t) {
		$this->transliterator = $t;
	}
}
?>