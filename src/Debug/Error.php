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
 *  @category Debug
 *  @date 23/12/2013
 */

namespace Touchbase\Debug;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Control\Router;

class Error extends \Touchbase\Core\Object
{
	/**
	 *	Error Codes to Text
	 *	@var array
	 */
	private $errorText = [
		0 => 'Unknown error',
		E_ERROR => 'Fatal error',
		E_WARNING => 'Warning',
		E_PARSE => 'Parse error',
		E_NOTICE => 'Notice',
		E_CORE_ERROR => 'Fatal error',
		E_CORE_WARNING => 'Warning',
		E_COMPILE_ERROR => 'Compile error',
		E_COMPILE_WARNING => 'Compile warning',
		E_USER_ERROR => 'Fatal error',
		E_USER_WARNING => 'Warning',
		E_USER_NOTICE => 'Notice',
		E_STRICT => 'Notice strict',
		E_RECOVERABLE_ERROR => 'Recoverable error',
		E_DEPRECATED => 'Deprecated',
		E_USER_DEPRECATED => 'Deprecated'
	];
	
	/**
	 *	User Errors
	 *	Values that can be used in `trigger_error`
	 *	@var array
	 */
	private static $userErrors = [
		E_USER_ERROR, 
		E_USER_WARNING, 
		E_USER_NOTICE, 
		E_USER_DEPRECATED
	];
	
	/**
	 *	Terminal Errors
	 *	Values that wil crash the system
	 *	@var array
	 */
	private static $terminalErrors = [
		E_ERROR, 
		E_PARSE, 
		E_CORE_ERROR, 
		E_COMPILE_ERROR,
		E_USER_ERROR
	];
	
	/**
	 *	@var int
	 */
	private static $callerBacktraceBump = 0;
	
	/* Public Methods */

	public function __construct(){
		ini_set("display_errors", 0);
		ini_set("docref_root", "http://php.net/");
		
		set_error_handler([$this, 'registerError'], error_reporting());
		register_shutdown_function([$this, 'registerShutdownError']);
	}
	
	/**
	 *	Trigger
	 *	@param string $message
	 *	@param int $code
	 *	@return VOID
	 */
	public static function trigger($message = null, $code = E_USER_NOTICE){
		//You have to pass a user error value to `trigger_error`, lets make sure of it.
		if(!in_array($code, self::$userErrors)){
			$code = E_USER_NOTICE;
		}
		
		self::$callerBacktraceBump++;
		trigger_error($message, $code);
	}

	/**
	 *	Register Error
	 *	Set in `set_error_handler`
	 *	@param int $errorType
	 *	@param string $errorMessage
	 *	@param string $errorFile
	 *	@param int $errorLine
	 *	@param array $errorContext
	 *	@return VOID
	 */
	public function registerError($errorType = 0, $errorMessage = null, $errorFile = null, $errorLine = 0, $errorContext = array()){
		if(!empty($errorType) & error_reporting()){
			$this->handler($errorType, $errorMessage, $errorFile, $errorLine, $errorContext);
		}
	}
	
	/**
	 *	Register Shutdown Error
	 *	Set in `register_shutdown_function`
	 *	@return VOID
	 */
	public function registerShutdownError(){

		if(function_exists('error_get_last')){
			$lastError = error_get_last();
			
			if($lastError['type'] & error_reporting()){
				$errorType = $lastError['type'];
				$errorMessage = $lastError['message'];
				$errorFile = $lastError['file'];
				$errorLine = $lastError['line'];

				//Register The Error
				$this->registerError($errorType, $errorMessage, $errorFile, $errorLine, $errorContext = false);
			}
		}
	}
	
	/* Private Methods */

	/**
	 *	Handler
	 *	This method handles the displaying of the error message
	 *	@param int $errorType
	 *	@param string $errorMessage
	 *	@param string $errorFile
	 *	@param int $errorLine
	 *	@param array $errorContext
	 *	@return VOID
	 */
	private function handler($errorType, $errorMessage, $errorFile = null, $errorLine = 0, $errorContext = array()){	
		if(in_array($errorType, array(E_CORE_WARNING,E_CORE_ERROR)) && !$this->debugStrict) return;

		//Lets add the real line and file number to errors that have been thrown via `trigger_error`
		if(in_array($errorType, self::$userErrors)){
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4 + (self::$callerBacktraceBump?self::$callerBacktraceBump--:0));
			$caller = end($backtrace);
			$errorFile = $caller['file'];
			$errorLine = $caller['line'];
		}

		error_log(sprintf($cliError = "PHP %s: %s in %s on line %d", $this->errorText[$errorType], $errorMessage, $errorFile, $errorLine));
		print sprintf(Router::isCLI()?$cliError:"<pre><strong>%s:</strong> %s in <strong>%s</strong> on line <strong>%d</strong></pre>", $this->errorText[$errorType], $errorMessage, $errorFile, $errorLine);
		
		//Kill the application if required.
		if(in_array($errorType, self::$terminalErrors)) exit;
	}
}