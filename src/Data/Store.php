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

defined('TOUCHBASE') or die("Access Denied.");

class Store extends \Touchbase\Core\Object implements StoreInterface, \IteratorAggregate, \ArrayAccess, \JsonSerializable, \Countable
{
	const CHAIN_STORE = "touchbase.key.chain.store";
	
	/**
	 *	In Memory Store
	 */
	protected $_data = [];
	
	/**
	 *	Construct
	 */
	public function __construct($data = null){
		if($data){
			$this->fromArray($data);
		}
	}
	
	//TODO: This is a temp function to show the error messages.
	public function __toString(){
		return implode("<br />", array_values($this->_data));
	}
	
	/* Getter / Setters */
	
	/**
	 *	Get
	 *	Get a value from the array
	 *	@param string $property
	 *	@param mixed $default - Defaults to a new config store for chaining
	 *	@return mixed
	 */
	public function __get($property){
		return $this->get($property, null);
	}
	 
	public function get($name, $default = self::CHAIN_STORE){
		$default = ($default == self::CHAIN_STORE)?new Store():$default;
		return $this->exists($name)?$this->_data[$name]:$default;
	}	
	
	/**
	 *	Set
	 *	Add a key /value pair to the array
	 *	@param string $name
	 *	@param mixed $value - If null the key will be removed
	 *	@return \Touchbase\Data\Store
	 */
	public function __set($name, $value){
		return $this->set($name, $value);	
	}
	
	public function set($name, $value = null){
		//Set Value
		if(isset($value)){
			$this->_data[$name] = $value;
			
		} else if(is_array($name) || $name instanceof $this){
			
			foreach($name as $key => $value){
				$this->set($key, $value);
			}
			
		//Delete Value
		} else if(isset($this->_data[$name])){
			unset($this->_data[$name]);
		}
		
		return $this;
	}
	
	/**
	 *	Pop
	 *	Removes an element from the array and returns it
	 *	@param string $property
	 *	@param mixed $default
	 *	@return mixed
	 */
	public function pop($property, $default = null){
		$array = $this->get($property, []);
		
		if(!is_array($array)){
			throw new \InvalidArgumentException("Property is not an array.");
		}
		
		$result = array_pop($array);
		$this->set($property, $array);
		return $result ?: $default;
	}
	
	/**
	 *	Push
	 *	Pushes arbitrary variables to the array
	 *	@param string $name
	 *	@return mixed
	 */
	public function push($name /*, ...$values */){
		$array = $this->get($name, []);
		
		$values = func_get_args(); array_shift($values);
		foreach($values as $value){
			if(isset($value)){
				$array[] = $value;
			}
		}
		
		$this->set($name, $array);		
		return $array;
	}
	
	/**
	 *	Delete
	 *	Removes a property from the store.
	 *	@param string $name
	 *	@return VOID
	 */
	public function delete($name){
		$this->set($name, null);
	}
	
	/**
	 *	Helper Methods
	 */
	public function __isset($name){
		return $this->exists($name);
	}
	 
	public function exists($name){
		return isset($this->_data[$name]);
	} 
	
	public function fromArray($array){
		
		if(!is_array($array) && !is_object($array)){
			throw new \InvalidArgumentException(sprintf("%s only accepts traversable objects / arrays, %s given", __METHOD__, gettype($array)));
		}
		
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
	 *	Countable
	 */
	public function count(){
		return count($this->_data);
	}
	
	/**
	 *	Json Serializable
	 */
	public function jsonSerialize(){
		return $this->_data;
	}
}