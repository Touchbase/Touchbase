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
 *  @category Core
 *  @date 23/12/2013
 */
 
namespace Touchbase\Core;

defined('TOUCHBASE') or die("Access Denied.");

abstract class Object
{
	
	/**
	 *	Create
	 *	Reflection helper to init a class
	 *	@params (variable)
	 *	@return (class)
	 */
	public static function create(){
		$args = func_get_args();

		// Class to create should be the calling class if not Object,
		// otherwise the first parameter
		$class = get_called_class();
		if($class == 'Object') $class = array_shift($args);
		
		$newClass = new \ReflectionClass($class);
		return $newClass->newInstanceArgs($args);
	}
	
	/**
	 *	To String
	 *	Magic method to get the class name
	 *	@return (string) - class name
	 */
	public function __toString(){
		return get_class($this);
	}
	
	/**
	 *	Get Parent Class
	 *	@return (string) - parent class name
	 */
	public function getParentClass(){
		return get_parent_class($this);
	}
	
	/**
	 *	Get Subclasses
	 *	@return (Array) - Returns a list of inherited classes
	 */
	public function getSubclasses(){
		$classes = array();
		foreach(get_declared_classes() as $className){
			if(is_subclass_of($className, /* $this->toString() */ __CLASS__)){
				$classes[] = $className;
			}
	 	}

	 	return $classes;
	}

	/**
	 *	Get Caller Method
	 *	@return (string) - Name of the function calling this method
	 */
	public function getCallerMethod(){
		$traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		if (isset($traces[2])){
			return $traces[2]['function'];
		}

		return null; 
	}
	
	/**
	 *	Is A
	 *	@return BOOL
	 */
	public function is_a($class){
		return $this instanceof $class;
	}
	
	/**
	 *	Has Method
	 *	@return BOOL
	 */
	public function hasMethod($method){
		return method_exists($this, $method);
	}
	
	/**
	 *	Has Property
	 *	@return BOOL
	 */
	public function hasProperty($property){
		return property_exists($this, $property) || $this->hasMethod($property);
	}
	
	/**
	 *	__isset
	 *	Magic method to
	 *	@return BOOL
	 */
	public function __isset($property){
		return $this->hasProperty($property);
	}

	/**
	 *	__set
	 *	Magic method to run $this->property = "newValue" through a setter if exists
	 *	@return VOID
	 */
	public function __set($property, $value){
		if($this->hasMethod($method = "set$property")) {
			$this->$property = $this->$method($value);
		} else {
			$this->$property = $value;
		}
	}
	
	/**
	 *	__get
	 *	Magic method to get a value from a getter if exists
	 *	@return mixed
	 */
	public function __get($property){
		if($this->hasMethod($method = "$property")) {
			return $this->$method();
		} else {
			return $this->$property;
		}
	}
	
	/**
	 *	__call
	 *	Magic method to get or set a property
	 *	@return mixed
	 */
	public function __call($method, array $arguments){
		if($this->hasProperty($property = "$method")) {
			return $this->$property;
		} else if(substr($method, 0, 3) === "set" && !empty($arguments)){
			$property = substr($method, 4);
			return $this->$property = $arguments[0];
		}
	}
	
	/**
	 *	Halt
	 *	@return VOID
	 */
	protected function halt($status = 0){
		exit($status);
	}

}