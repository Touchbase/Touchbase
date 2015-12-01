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

class Validation extends \Touchbase\Core\Object implements \Countable
{
	/**
	 *	@var string
	 */
	protected $_ruleset;
	
	/**
	 *	@var array
	 */
	protected $rules = [];
	
	/**
	 *	@var array
	 */
	public $errors = [];

	/* Public Methods */
	
	/**
	 *	__construct
	 *	@param string $ruleset
	 *	@param array $rules
	 */
	public function __construct($ruleset = null, array $rules = []) {
		$this->_ruleset = $ruleset;
		foreach($rules as $rule){
			$this->addRule($rule);
		}
	}
	
	/**
	 *	Add Rule
	 *	@param Closure | \Touchbase\Utils\Validation $rule
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function addRule($rule, $errorMessage = null) {
		if ($rule instanceof \Closure) {
			$this->rules[] = [$rule, $errorMessage];
		} else if ($rule instanceof $this) {
			$this->rules[] = [$rule, $errorMessage];
		} else {
			trigger_error(sprintf("Argument %d passed to %s must be an instance of %s, %s given", 1, __METHOD__, "Closure", is_object($rule)?get_class($rule):gettype($rule)), E_USER_WARNING);
		}
		
		return $this;
	}
	
	/**
	 *	Validate
	 *	@param array $input - The post array
	 *	@return BOOL
	 */
	public function validate($input) {
		
		foreach ($this->rules as $rule) {
			
			list($rule, $errorMessage) = $rule;
			
			if($rule instanceof $this){
				if(!$rule->validate($input)){
					$this->errors = array_merge($rule->errors, $this->errors);
				}
				
				continue;
			}
			
			$overrideMessage = null;
			if (!$rule(@$input[$this->_ruleset], $overrideMessage)) {
				if(!isset($this->errors[$this->_ruleset])){
					$this->errors[$this->_ruleset] = $overrideMessage ?: $errorMessage;
				}
			}
		}

		return empty($this->errors);
	}

	/* Validation Helpers */
	
	/**
	 *	Type
	 *	@param string $type
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function type($type, $errorMessage = null){
		
		switch($type){
			case "email":
				$this->addRule(function($value) {
					return empty($value) || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
				}, $errorMessage ?: "Email address is invalid");
			break;
			case "url":
				$this->addRule(function($value) {
					return empty($value) || filter_var($value, FILTER_VALIDATE_URL) !== false;
				}, $errorMessage ?: "Url is invalid");
			break;
			case "number":
				$this->addRule(function($value) {
					return empty($value) || filter_var($value, FILTER_VALIDATE_INT) !== false || filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
				}, $errorMessage ?: "Number is invalid");
			break;
			case "file":			
				//Check File Errored
				$this->addRule(function($value, &$message) {
					$error = $value['error'];
					$errorValidation = function($error) use (&$message){
						switch($error){
							case UPLOAD_ERR_OK:
								return true;
							case UPLOAD_ERR_INI_SIZE:
							case UPLOAD_ERR_FORM_SIZE:
								$message = "A file that was uploaded was greater than the allowed size";
								return false;
							case UPLOAD_ERR_PARTIAL:
								$message = "A file failed to upload fully";
								return false;
							case UPLOAD_ERR_NO_FILE:
								$message = "No file was selected for upload";
								return false;
							case UPLOAD_ERR_NO_TMP_DIR:
							case UPLOAD_ERR_CANT_WRITE:
							case UPLOAD_ERR_EXTENSION:
								$message = "An error occurred whilst trying to upload a file";
								return false;
						}
					};
					
					if(is_array($error)){
						foreach($error as $err){
							if(!empty($value) && !$errorValidation($err)){
								return false;
							}
						}
						
						return true;
					}
					
					return empty($value) || $errorValidation($error);
				});
				
				//Check File Validity
				$this->addRule(function($value) {
					$tmpName = $value['tmp_name'];
					if(is_array($tmpName)){
						foreach($tmpName as $tmp){
							if(!is_uploaded_file($tmp)){
								return false;
							}
						}
						return true;
					}
					
					return is_uploaded_file($tmpName);
				}, $errorMessage ?: "A file uploaded was not processed correctly");
				
			break;
		}

		return $this;
	}
	
	/**
	 *	Required
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function required($errorMessage = null) {
		$this->addRule(function($value) {
			return !empty($value);
		}, $errorMessage ?: "Required value was not submitted");

		return $this;
	}
	
	/**
	 *	Readonly
	 *	@param string $initalValue
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function readonly($initialValue, $errorMessage = null) {
		$this->addRule(function($value) use ($initialValue) {
			return $value === $initialValue;
		}, $errorMessage ?: "Can not assign to a readonly value");

		return $this;
	}

	/**
	 *	Disabled
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function disabled($errorMessage = null) {
		$this->addRule(function($value) {
			return is_null($value);
		}, $errorMessage ?: "Disabled element can not be submitted");

		return $this;
	}
	
	/**
	 *	Min Length
	 *	@param integer $minLength
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function minLength($minLength, $errorMessage = null) {
		$this->addRule(function($value) use ($minLength) {
			return empty($value) || strlen($value) > $minLength;
		}, $errorMessage ?: "Value entered was too short");

		return $this;
	}

	/**
	 *	Max Length
	 *	@param integer $maxLength
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function maxLength($maxLength, $errorMessage = null) {
		$this->addRule(function($value) use ($maxLength) {
			return strlen($value) <= $maxLength;
		}, $errorMessage ?: "Value entered was too long");

		return $this;
	}

	/**
	 *	Min
	 *	@param integer $min
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function min($min, $errorMessage = null) {
		$this->addRule(function($value) use ($min) {
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

	/**
	 *	Max
	 *	@param integer $max
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function max($max, $errorMessage = null) {
		$this->addRule(function($value) use ($max) {
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

	/**
	 *	Pattern
	 *	@param string $type - A regex pattern
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function pattern($pattern, $errorMessage = null) {
		$this->addRule(function($value) use ($pattern) {
			return preg_match($pattern, $value);
		}, $errorMessage ?: "Value did not meet the validation requirements");

		return $this;
	}

	/**
	 *	Equals
	 *	@param string $equals
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function equals($equals, $errorMessage = null) {
		$this->addRule(function($value) use ($equals) {
			return $value === $equals;
		}, $errorMessage ?: "Value does not match");

		return $this;
	}
	
	/**
	 *	In
	 *	@param array $in
	 *	@param string $errorMessage
	 *	@return \Touchbase\Utils\Validation
	 */
	public function in(array $in, $errorMessage = null) {
		$this->addRule(function($value) use ($in) {
			return in_array($value, $in);
		}, $errorMessage ?: "Value was not expected");

		return $this;
	}

	/* Getters / Setters */

	/**
	 *	Ruleset
	 *	@return string
	 */
	public function ruleset() {
		return $this->_ruleset;
	}

	/* Countable */
	
	/**
	 *	Count
	 *	@return integer
	 */
	public function count() {
		return count($this->rules);
	}
}