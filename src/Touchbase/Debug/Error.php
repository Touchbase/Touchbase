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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

defined('TOUCHBASE') or die("Access Denied.");

class Error extends \Touchbase\Core\Object {

	/**
	 *	Error Codes to Text 
	 *z
	 *	@var Array $phpErrors - Text Mapping
	 */
    private $phpErrors = array(
    	0				  => 'Unknown Error',
        E_ERROR           => 'Fatal Error',
        E_WARNING         => 'Warning',
        E_PARSE           => 'Parse Error',
        E_NOTICE          => 'Notice',
        E_CORE_ERROR      => 'Fatal Error',
        E_CORE_WARNING    => 'Warning',
        E_COMPILE_ERROR   => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR      => 'Fatal User Error',
        E_USER_WARNING    => 'User Warning',
        E_USER_NOTICE     => 'User Notice',
        E_STRICT          => 'Notice Strict',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        //E_DEPRECATED 		=> 'Deprecated',
        //E_USER_DEPRECATED 	=> 'User Deprecated'
    );
    
    /**
     *	@var Boolean $trace_show_args - Turn Arguments Off or On in the debug trace.
     */
    static $trace_show_args = false;
    static $instance;
        
  
    public function __construct(){
    	ini_set("display_errors", 1);
		ini_set("docref_root", "http://php.net/");
    	
	    set_error_handler(array($this, 'registerError'), error_reporting());
		register_shutdown_function(array($this, 'registerShutdownError'));
    }
    
	public function registerError($errorType = false, $errorMessage = false, $errorFile = false, $errorLine = 0, $errorContext = array()){
		// Send To Error Handler -> If Error Type Has Been Found.
		if(!empty($errorType) & error_reporting()){
			$this->handler($errorType, $errorMessage, $errorFile, $errorLine, $errorContext);
		}
	}
	
	public function registerShutdownError(){
		//If we have an error that has no errorType. Its Probably Fatal
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
    
	public function trigger($message = NULL, $code = E_USER_NOTICE){
		//Check User Error
		if(!in_array($code, array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE))){
			$code = E_USER_NOTICE;
		}
		
		//Lets add the real line and file number to the message
		$backTrace = debug_backtrace();
		$caller = current($backTrace);
		
		//Remake Message
		$message = $message.' | '.serialize(array('file'=>$caller['file'],'line'=>$caller['line']));
		
		//Trigger Error
		trigger_error($message, $code);
	}
	
	public function get($lastError = true){
		if($lastError){
			return end($this->errorStack);
		} 
		
		return $this->errorStack;
	}
	
	public function handler($errorType, $errorMessage, $errorFile = null, $errorLine = 0, $errorContext = array()){
		//if(in_array($errorType, array(E_CORE_WARNING,E_CORE_ERROR)) && !$this->debugStrict){return;}
		
		//Cheeky
		if(strpos($errorMessage, "Debug::") !== false) return;
		
		//USER ERRORS COME IN WITH WRONG INFOMTAION
		if(strpos($errorMessage,"|") !== false){
			//Strip extra information
			$realMessage = explode("|",$errorMessage);
			
			//We Should Only Get Two Parts
			if(count($realMessage) == 2){
				$errorMessage = trim($realMessage[0]);
				
				$serialisedInfo = unserialize(trim($realMessage[1]));
				$errorFile = $serialisedInfo['file'];
				$errorLine = $serialisedInfo['line'];
			}
		}
				
		if(!empty($errorMessage)){

		}
		
		
		//USER ERRORS
		if(in_array($errorType, array(E_USER_WARNING, E_USER_NOTICE))){
			$this->errorStack[] = $errorMessage;
		}
				
		return true;
	}

}