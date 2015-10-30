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

defined('TOUCHBASE') or die("Access Denied.");

abstract class Enum extends \Touchbase\Core\Object implements \IteratorAggregate, \JsonSerializable
{
	/**
	 *	@var mixed
	 */
	protected $enum;
	
	/**
	 *	@var array
	 */
	protected static $constantsCache = [];
	
	/* Public Methods */
	
	/**
	 *	__construct
	 *	@param integer $enum
	 *	@param BOOL $strict
	 */
	public function __construct($enum = null, $strict = false){
		
		if(!in_array($enum, $this->getConstantsList())) {
			throw new \InvalidArgumentException(sprintf("%s does not contain the enum value `%d`", get_class($this), $enum));
		}
		
		if(is_null($enum) && !defined('static::__default')){
			throw new \InvalidArgumentException(sprintf("No argument was passed, %s does not contain a default value `static::__default`", get_class($this)));
		}
		
		$this->enum = isset($enum)?$enum:static::__default;
	}
	
	/**
	 *	__toString
	 *	@return string
	 */
	public function __toString(){
		return (string)$this->enum;
	}
	
	/**
	 *	Is
	 *	Helper method to compare a value against a string self
	 *	@return BOOL || $return
	 */
	public function is($compare, $return = true){
		return $compare == (string)$this?$return:false;
	}

	/**
	 *	__callStatic function.
	 *	@param string $name
	 *	@param mixed $arguments
	 *	@access public
	 *	@static
	 *	@return 
	 */
	public static function __callStatic($name, $arguments){
		return new static(constant(get_called_class() . '::' . strtoupper($name)));
	}

	/**
	 *	Get Const List
	 *	\SplEnum getter to return all constants
	 *	@access public
	 *	@return array
	 */
	public function getConstList(){
		return $this->getConstantsList();
	}
	public function getConstantsList(){

		$calledClass = get_called_class();
		if(!array_key_exists($calledClass, self::$constantsCache)){
			self::$constantsCache[$calledClass] = (new \ReflectionClass($calledClass))->getConstants();
		}

		return self::$constantsCache[$calledClass];
	}
	
	/* IteratorAggregate */
	
	/**
	 *	Get Iterator
	 *	@return  \ArrayIterator
	 */
	public function getIterator(){
		return new \ArrayIterator($this->getConstantsList());
	}
	
	/* JSON */
	
	/**
	 *	JSON Serialize
	 *	@return integer
	 */
	public function jsonSerialize(){
		return (int)$this->enum;
	}
}