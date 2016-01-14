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

class Store extends \ArrayObject implements StoreInterface
{
	const CHAIN_STORE = "touchbase.key.chain.store";
	
	/* Public Methods */
	
	/**
	 *	Create
	 *	Reflection helper to init a class
	 *	@params (variable)
	 *	@return (class)
	 */
	public static function create(){
		$args = func_get_args();		
		$newClass = new \ReflectionClass(get_called_class());
		return $newClass->newInstanceArgs($args);
	}
	
	/**
	 *	Construct
	 *	@param array $data
	 */
	public function __construct($data = null){
		if($data){
			$this->fromArray($data);
		}
	}
	
	/**
	 *	__toString
	 *	TODO: This is a temp function to show the error messages.
	 *	@return string
	 */
	public function __toString(){
		return implode("<br />", array_values((array)$this));
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
	
		if($this->exists($name)) return $this[$name];
		
		if($default == self::CHAIN_STORE){
			$default = new Store();
			$this->set($name, $default);
		}
			
		return $default;
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
			$this[$name] = $value;
			
		} else if(is_array($name) || $name instanceof $this){
			
			foreach($name as $key => $value){
				$this->set($key, $value);
			}
			
		//Delete Value
		} else if(isset($this[$name])){
			unset($this[$name]);
		}
		
		return $this;
	}
	
	/**
	 *	Pop
	 *	Removes an element from the array and returns it
	 *	@param string $property
	 *	@param mixed $default
	 *	@throws \InvalidArgumentException if the value of the key `$property` is not an array
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
	 *	@param va_list | array $values
	 *	@return array
	 */
	public function push($name /*, ...$values */){
		$values = func_get_args(); array_shift($values);	
		return $this->pushHelper($name, $values);
	}
	public function pushUnique($name /*, ...$values */){
		$values = func_get_args(); array_shift($values);	
		return $this->pushHelper($name, $values, true);
	}
	private function pushHelper($name, $values, $unique = false){
		$array = $this->get($name, []);
		
		foreach($values as $value){
			if(is_array($value)){
				$array = array_merge($array, $value);
			} else if(isset($value)){
				$array[] = $value;
			}
		}
		
		$this->set($name, $unique ? array_unique($array) : $array);		
		return $this;
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
	 *	__isset
	 *	@param string $name
	 *	@return BOOL
	 */
	public function __isset($name){
		return $this->exists($name);
	}
	 
	/**
	 *	Exists
	 *	@param string $name
	 *	@return BOOL
	 */
	public function exists($name){
		return $name && isset($this[$name]);
	} 
	
	/**
	 *	From Array
	 *	@param array $array
	 *	@throws \InvalidArgumentException if a traverable object is not passed
	 *	@return \Touchbase\Data\Store - self
	 */
	public function fromArray($array){
		
		if(!is_array($array) && !is_object($array)){
			throw new \InvalidArgumentException(sprintf("%s only accepts traversable objects / arrays, %s given", __METHOD__, gettype($array)));
		}
		
		foreach($array as $group => $data){
			if(is_array($data) /* && $data !== array_values($data) */){
				$dataObj = new \Touchbase\Data\Store();
				$this->set($group, $dataObj->fromArray($data));
			} else {
				$this->set($group, $data);
			}
		}
		
		return $this;
	}
}