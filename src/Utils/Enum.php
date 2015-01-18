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
 *  @date 15/01/2015
 */

namespace Touchbase\Utils;

abstract class Enum extends \Touchbase\Core\Object implements \IteratorAggregate
{
	protected $enum;
	
	protected static $constantsCache = [];

	public function __construct($enum = null, $strict = false){
		
		if(!in_array($enum, $this->getConstantsList())) {
			throw IllegalArgumentException();
		}
		
		$this->enum = $enum?:static::__defualt;
	}
	
	public function __toString(){
		return (string)$this->enum;
	}
	

	/**
	 *	__callStatic function.
	 * 
	 *	@access public
	 *	@static
	 *	@param string $name
	 *	@param mixed $arguments
	 *	@return 
	 */
	public static function __callStatic($name, $arguments){
		return new static(constant(get_called_class() . '::' . strtoupper($name)));
	}

	/**
	 *	Get Const List
	 *	\SplEnum getter to return all constants
	 *
	 *	@access public
	 *	@return array
	 */
	public function getConstList(){
		return $this->getConstantList();
	}
	public function getConstantsList(){

		$calledClass = get_called_class();
		if(!array_key_exists($calledClass, self::$constantsCache)){
			self::$constantsCache[$calledClass] = (new \ReflectionClass($calledClass))->getConstants();
		}

		return self::$constantsCache[$calledClass];
	}
	
	/* IteratorAggregate */
	public function getIterator(){
		return new \ArrayIterator($this->getConstantsList());
	}
	
	/* NoOp */
	
	final private function __clone(){}
	final public function __sleep(){}
	final public function __wakeup(){}
}