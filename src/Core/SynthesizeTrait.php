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

trait SynthesizeTrait
{	
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
		return property_exists($this, $property) || ($this->hasMethod($property) && property_exists($this, "_$property"));
	}
	
	/**
	 *	__isset
	 *	Magic method to determine if a property has been set
	 *	@return BOOL
	 */
	public function __isset($property){
		if(($parent = get_parent_class()) && method_exists($parent, "__isset")){
			return parent::__isset($property);
		}
		
		return $this->hasProperty($property);
	}

	/**
	 *	__set
	 *	Magic method to run $this->property = "newValue" through a setter if exists
	 *	@discussion If a variable of the same name prefixed with an underscore(_) exists on the class. We assume that the developer wants
	 *	to take care of setting the property themselves. In this instance a getter must also be supplied to access the property externally.
	 *	If no setter exists for an underscore(_) prefixed property we deem that the property is readonly and therfore trigger an error.
	 *	If no properties exist on the class with the prefixed name, we use the return value of the setProperty method to set the variable.
	 *	@param string $property
	 *	@param mixed $value
	 *	@return VOID
	 */
	public function __set($property, $value){
		if($this->hasMethod($method = "set$property")) {
			if(($value = $this->$method($value)) && !$this->hasProperty("_$property")){
				$this->$property = $value;
			}
		} else if(($parent = get_parent_class()) && method_exists($parent, "__set")){
			parent::__set($property, $value);
		} else if(!$this->hasProperty("_$property") && !$this->hasProperty("$property")){
			$this->$property = $value;
		} else if(property_exists($this, $property)){
			$scope = (new \ReflectionProperty($this, $property))->isProtected()?"protected":"private";
			trigger_error(sprintf("Cannot access %s property %s::$%s", $scope, get_class($this), $property), E_USER_ERROR);
		} else {
			trigger_error(sprintf("Assignment to readonly property %s::$%s", get_class($this), $property), E_USER_ERROR);
		}
	}
	
	/**
	 *	__get
	 *	Magic method to get a value from a getter if exists
	 *	@param string $property
	 *	@return mixed
	 */
	public function __get($property){
		if($this->hasMethod($method = "$property") && $this->hasProperty("_$property")) {
			return $this->$method();
		} else if(property_exists($this, $property)){
			$scope = (new \ReflectionProperty($this, $property))->isProtected()?"protected":"private";
			trigger_error(sprintf("Cannot access %s property %s::$%s", $scope, get_class($this), $property), E_USER_ERROR);
		} else if(($parent = get_parent_class()) && method_exists($parent, "__get")){
			return parent::__get($property);
		} else if(isset($this->$property)){
			return $this->$property;
		}
		
		trigger_error(sprintf("Undefined property: %s::$%s", get_class($this), $property), E_USER_NOTICE);
	}
	
	/**
	 *	__call
	 *	Magic method to get or set a property
	 *	@return mixed
	 */
	public function __call($method, array $arguments){
		if($this->hasProperty($property = "$method")){
			$propertyReflection = new \ReflectionProperty($this, $property);
			if(!$propertyReflection->isPublic()){
				$scope = $propertyReflection->isProtected()?"protected":"private";
				trigger_error(sprintf("Cannot access %s property %s::$%s", $scope, get_class($this), $property), E_USER_ERROR);
			}
			
			return $this->$property;
		} else if(substr($method, 0, 3) === "set" && !empty($arguments)){
			$property = lcfirst(substr($method, 3));
			$this->$property = $arguments[0];
			return $this;
		} else if(($parent = get_parent_class()) && method_exists($parent, "__call")){
			return parent::__call($method, $arguments);
		}
		
		trigger_error(sprintf("Call to undefined function %s()", $method), E_USER_NOTICE);
	}
}