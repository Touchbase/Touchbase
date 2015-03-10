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
 *  @date 04/03/2015
 */

namespace Touchbase\Utils;

defined('TOUCHBASE') or die("Access Denied.");

class Validation extends \Touchbase\Core\Object implements \Serializable, \Countable
{
	/**
	 * Public Properties
	 */
	protected $ruleset;
	protected $rules;
	protected $errors = [];

	/* Public Methods */
	public function __construct($ruleset, $rules = []) {
		$this->ruleset = $ruleset;
	}

	public function addRule($rule, $errorMessage = null) {
		if ($rule instanceof \Closure) {
			$this->rules[] = [$rule, $errorMessage];
		} else if ($rule instanceof $this) {
			$this->rules[] = [$rule, $errorMessage];
		} else {
			trigger_error(sprintf("Argument %d passed to %s must be an instance of %s, %s given", 1, __METHOD__, "Closure", is_object($rule)?get_class($rule):gettype($rule)), E_USER_WARNING);
		}
	}

	public function validate($input) {

		foreach ($this->rules as $rule) {
			
			list($rule, $errorMessage) = $rule;
			
			if($rule instanceof $this){
				if(!$rule->validate($input)){
					$this->errors = array_merge($rule->errors, $this->errors);
				}
				
				continue;
			}
			
			if (!$rule(@$input[$this->ruleset])) {
				$this->errors[$this->ruleset] = $errorMessage;
			}
		}

		return empty($this->errors);
	}

	/* Validation Helpers */
	
	public function type($type, $errorMessage = null){
		switch($type){
			case "email":
				$this->addRule(function($value) {
					return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
				}, $errorMessage ?: "Email address is invalid");
			break;
			case "url":
				$this->addRule(function($value) {
					return filter_var($value, FILTER_VALIDATE_URL) !== false;
				}, $errorMessage ?: "Url is invalid");
			break;
		}

		return $this;
	}

	public function required($errorMessage = null) {
		$this->addRule(function($value) {
			return !empty($value);
		}, $errorMessage ?: "Required value was not submitted");

		return $this;
	}

	public function readonly($initialValue, $errorMessage = null) {
		$this->addRule(function($value) use ($initialValue) {
			return $value === $initialValue;
		}, $errorMessage ?: "Can not assign to a readonly value");

		return $this;
	}

	public function disabled($errorMessage = null) {
		$this->addRule(function($value) {
			return is_null($value);
		}, $errorMessage ?: "Disabled element can not be submitted");

		return $this;
	}

	public function maxLength($maxLength, $errorMessage = null) {
		$this->addRule(function($value) {
			return strlen($value) <= $maxLength;
		}, $errorMessage ?: "Value entered was too long");

		return $this;
	}

	public function min($min, $errorMessage = null) {
		$this->addRule(function($value) {
			switch ($this->type) {
				case "date":
				case "datetime-local":
				case "time":
					return new \DateTime($value) >= \DateTime($min);
				case "month":
					return (new \DateTime($value))->format("m") >= (new \DateTime($min))->format("m");
				case "number":
				case "range":
				case "week":
				default:
					return $value >= $min;
			}
		}, $errorMessage ?: "Value entered was below the minimum allowed");

		return $this;
	}

	public function max($max, $errorMessage = null) {
		$this->addRule(function($value) {
			switch ($this->type) {
				case "date":
				case "datetime-local":
				case "time":
					return new \DateTime($value) <= \DateTime($max);
				case "month":
					return (new \DateTime($value))->format("m") <= (new \DateTime($max))->format("m");
				case "number":
				case "range":
				case "week":
				default:
					return $value <= $max;
			}
		}, $errorMessage ?: "Value entered was above the maximum allowed ");

		return $this;
	}

	public function pattern($pattern, $errorMessage = null) {
		$this->addRule(function($value) {
			return preg_match($pattern, $value);
		}, $errorMessage ?: "Value did not meet the validation requirements");

		return $this;
	}

	public function equals($equals, $errorMessage = null) {
		$this->addRule(function($value) use ($equals) {
			return $value == $equals;
		}, $errorMessage ?: "Value does not match");

		return $this;
	}

	/* Getters / Setters */

	public function rulesets() {
		if (!$this->_rulesets) {

		}

		return $this->_rulesets;
	}

	/* Serializable */

	public function serialize() {
		return serialize(get_object_vars($this));
	}

	public function unserialize($data) {
		if(is_array($data)){
			foreach($data as $key => $value){
				$this->$key = $value;
			}
		}
	}

	/* Countable */

	public function count() {
		return count($this->rules);
	}
}