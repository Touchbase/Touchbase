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
 *  @category Config
 *  @date 11/01/2014
 */
 
namespace Touchbase\Core\Config;

class Store extends \Touchbase\Data\Store
{
	const CONFIG_KEY = 'touchbase.key.config';
	
	public function addConfig(\Touchbase\Data\Store $configData){
		$this->_merge($configData);
		return $this;
	}
	 
	public function _merge(\Touchbase\Data\Store $replacements, $recursive = true){
		foreach($replacements as $group => $data){
			if(!$this->exists($group)){
				$this->set($group, $data);
			} else {
				foreach($data as $key => $value){
					$cfg = $this->get($group);
					if($recursive && is_array($value) && $cfg->get($key)){
						$cfg->set($key, array_merge_recursive($cfg->get($key), $value));
					} else {
						$cfg->set($key, $value);
					}
				}
			}
		}
		
		return $this;
	}
}
