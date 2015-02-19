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
 *  @category 
 *  @date 11/01/2014
 */
 
namespace Touchbase\Data;

class Store extends \Touchbase\Core\Object implements StoreInterface, \IteratorAggregate, \ArrayAccess, \JsonSerializable
{
	const CHAIN = "touchbase.key.chain.store";
	
	/**
	 *	In Memory Store
	 */
	protected $_data = array();
	
	/**
	 *	Construct
	 */
	public function __contruct(array $data = array()){
		$this->_data = $data;
	}
	
	/**
	 *	Getter/Setter Methods
	 */
	public function get($name, $default = self::CHAIN){
		$default = ($default == self::CHAIN)?new Store():$default;
		return $this->exists($name)?$this->_data[$name]:$default;
	}
	
	public function set($name, $value = null){
		if(isset($value)){
			//Set Value
			if(!empty($value)){
				$this->_data[$name] = $value;
					
			//Delete Value
			} else if(isset($this->_data[$name])){
				unset($this->_data[$name]);
			}	
		}
		
		return $this;
	}
	
	/**
	 *	Helper Methods
	 */
	public function exists($name){
		return isset($this->_data[$name]);
	} 
	
	public function fromArray(array $array){
		foreach($array as $group => $data){
			if(is_array($data) && $data !== array_values($data)){
				$dataObj = new \Touchbase\Data\Store();
				$this->set($group, $dataObj->fromArray($data));
			} else {
				$this->set($group, $data);
			}
		}
		
		return $this;
	}

	/**
	 *	Array Access
	 */
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->_data[] = $value;
		} else {
			$this->_data[$offset] = $value;
		}
	}
	public function offsetExists($offset) {
		return isset($this->_data[$offset]);
	}
	public function offsetUnset($offset) {
		unset($this->_data[$offset]);
	}
	public function offsetGet($offset) {
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}
	
	/**
	*	Iterator Aggregate
	*/
	public function getIterator(){
		return new \ArrayIterator($this->_data);
	}
	
	/**
	*	Json Serializable
	*/
	public function jsonSerialize(){
		return $this->_data;
	}
}