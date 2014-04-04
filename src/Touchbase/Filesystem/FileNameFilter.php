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
 *  @category Filesystem
 *  @date 23/12/2013
 */
 
namespace Touchbase\Filesystem;

defined('TOUCHBASE') or die("Access Denied.");

class FileNameSanitizer extends \Touchbase\Utils\Sanitizer {
	
	protected $defaultReplacements = array(
		'/\s/' => '_', // remove whitespace
		'/-/' => '_', // dashes to underscores
		'/[^A-Za-z0-9+._]+/' => '', // remove non-ASCII chars, only allow alphanumeric plus underscore and dot
		'/[\_]{2,}/' => '-', // remove duplicate underscores
		'/^[\.\-_]/' => '', // Remove all leading dots, dashes or underscores
	);

	function filter($name, $replacements = array()) {
		$ext = pathinfo($name, PATHINFO_EXTENSION);
		
		$transliterator = $this->getTransliterator();
		if($transliterator){
			$name = $transliterator->toASCII($name);
		}
		
		if(empty($replacements)){
			$replacements = $this->defaultReplacements;
		}
		
		foreach($replacements as $regex => $replace) {
			$name = preg_replace($regex, $replace, $name);
		}
		
		//Safeguard against empty file names
		$nameWithoutExt = pathinfo($name, PATHINFO_FILENAME);
		if(empty($nameWithoutExt)){
			$name = $this->getDefaultName().'.'.$ext;
		}
		
		return $name;
	}
	
	function getDefaultName() {
		return (string)uniqid();
	}
}
