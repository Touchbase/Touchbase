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
	use SynthesizeTrait;
	
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
	 *	Halt
	 *	@return VOID
	 */
	protected function halt($status = 0){
		exit($status);
	}

}