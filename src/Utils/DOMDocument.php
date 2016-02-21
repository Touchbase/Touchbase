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
 *  @date 15/11/2015
 */

namespace Touchbase\Utils;

defined('TOUCHBASE') or die("Access Denied.");
defined("LIBXML_HTML_NOIMPLIED") or define("LIBXML_HTML_NOIMPLIED", 8192);

class DOMDocument extends \DOMDocument
{
	
	/**
	 *	@var BOOL
	 */
	private $appliedPreXML277Wrapper = false;
	
	/**
	 *	Construct
	 *	Using LIBXML for HTML5 causes a few errors, lets turn these off.
	 */
	public function __construct($version = null, $encoding = null){
		libxml_use_internal_errors(true);
		return parent::__construct($version, $encoding);
	}
	
	/**
	 *	Load HTML
	 *	Apply a wrapper fix if the version of LIBXML we use doesn't support LIBXML_HTML_NOIMPLIED
	 *	@param string $source
	 *	@param int $options
	 *	@return BOOL
	 */
	public function loadHTML($source, $options = 0){
		$this->recover = false;
		
		if(!empty($source)){
			if(ltrim($source)[0] !== "<" || $options & LIBXML_HTML_NOIMPLIED && version_compare(LIBXML_DOTTED_VERSION, "2.7.7", "<")){
				if(strpos($source, "<!DOCTYPE") !== 0){
					$source = '<wrapper id="TB_LIBXML_NOIMPLIED_WRAPPER">' . $source . '</wrapper>';
					$this->appliedPreXML277Wrapper = true;
				}
			}
		}
		
		return parent::loadHTML($source, $options);
	}
	
	/**
	 *	Save HTML
	 *	Remove the wrapper fix if the version of LIBXML we use doesn't support LIBXML_HTML_NOIMPLIED
	 *	@param DOMNode $node
	 *	@return string
	 */
	public function saveHTML(\DOMNode $node = NULL){
		
		if($this->appliedPreXML277Wrapper){
			if(!$node || $node->nodeName === "html" || $node->nodeName === "wrapper"){
				$xpath = new \DOMXPath($this);
				$wrapper = $xpath->query("//*[@id='TB_LIBXML_NOIMPLIED_WRAPPER']")->item(0);
				$wrapper = $wrapper->parentNode->removeChild($wrapper);
				
				while($this->firstChild){
					$this->removeChild($this->firstChild);
				}
				
				while($wrapper->firstChild){
					$this->appendChild($wrapper->firstChild);
				}
				
				//A HTML node was parsed in to remove the DOCTYPE, this misses our wrapper so we ignore the $node request.
				$node = NULL;
			}
		}
		
		return parent::saveHTML($node); 
	}
	
	/**
	 *	Get Elements By Tag Name
	 *	If we have applied the wrapper fix, the body element should be the wrapper instead.
	 *	@param string $name
	 *	@return \DOMNodeList
	 */
	public function getElementsByTagName($name){
		
		if($this->appliedPreXML277Wrapper && $name === "body"){
			return parent::getElementsByTagName("wrapper");
		}
		
		return parent::getElementsByTagName($name);
	}
}