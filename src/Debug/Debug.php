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

class Debug {
	
	/**
	 *	Enable Settings
	 *
	 *	@var Boolean $debugEnabled - Turn Off All Debugging if FALSE
	 *	@var Boolean $debugToConsole - Route debug information to Javascript Console if TRUE
	 */
	static $debugEnabled = true;
	static $debugToConsole = false;
	
	/**
	 *	Presentational Settings
	 *
	 *	@vat Array $defaultTemplate - This is what the ouput is wrapped in. Be sure to include {HTML}
	 *	@var Array $defaultColors - Light Text Color, Dark Text Color
	 *	@var Array $defaultIterationColors - Light Background Color (x2), Dark Background Color (x2)
	 *	@var Array $defualtStyle - Stylesheet Params (common - Both Light and Dark, light, dark)
	 */
	static $defaultTemplate = '<pre style="margin:0">{HTML}</pre>';
	static $defaultColors = array("dark"=>"#ccc", "light"=>"#666");
	static $defaultIterationColors = array("dark"=>array("#424242","#3B3B3B"), "light"=>array("#CCCCCC","#EEEEEE"));
	static $defaultStyle = array("common"=>array("display"=>"block",
							  				     "font-family"=>"monospace",
							  				     "padding"=>"5px",
							  				     "overflow"=>"auto"));
	
	/**
	 *	Current State
	 *
	 *	@var Int $iterationCounter - Holds the iteration number to mod(2) and create alternate lines
	 *	@var String $currentTheme - Current Theme (light, dark)
	 *	@var String $currentTextColor - Current Text Highlight Color
	 *	@var String $currentBackColor - Current Background Color
	 *	@var String $currentStyle - Current Text Style (Bold, Italic, Underlined)
	 *	@var Bool $suppressOutput - Don't Output. Used in function 'say' to only output if a condition is true
	 *	@var Bool $prioritiseError - Sends output to error stack
	 */
	static $iterationCounter = -1;
	static $currentTheme = "light";
	private $currentTextColor;
	private $currentBackColor;
	private $currentStyle = array();
	private $suppressOutput = false;
	private $prioritiseError = false;
	
	/**
	 *	Current Output Buffer
	 *
	 *	Used by the new system to keep track of current html to insert css and js in the correct place.
	 *	@var Object $HTML - HtmlBuffer Object for including Javascript and Css correctly ie. $this->HTML->assets->includeJs();
	 */
	private $HTML;
	
	
	
	/**
	 *	Init Constructor 
	 */ 
	public function init(){
		$class = new ReflectionClass("Debug");
		return $class->newInstance();
	} 
	 
	
	/**
	 *	Public Functions
	 */
	public function write(){
		$arguments = func_get_args();
		
		//This Function Requires Inistigating If you want to use ->stop()
		if(!isset($this) || isset($this) && get_class($this) != __CLASS__){
			return call_user_func_array(array(Debug::init(), "write"), $arguments);
		}
		
		Debug::$iterationCounter++;
		foreach($arguments as $item){
			Debug::output(Debug::formatItem($item));
		}
		
		return $this;
	}
	
	/**
	 *	DEBUG BACKTRACE
	 *
	 *	@param Array $options - Array of options about the trace. Defaults are provided.
	 */
	public static function trace($options = array()) {
		$options = array_merge_recursive(array(
			'depth'		=> 999, 
			'format'	=> 'js',
			'args'		=> false,
			'start'		=> 0,
			'scope'		=> null,
			'exclude'	=> array('call_user_func_array', 'trigger_error', 'user_error')),
		$options);

		$backtrace = debug_backtrace();
		$count = count($backtrace);
		$back = array();

		$_trace = array(
			'line'     => '??',
			'file'     => '[internal]',
			'class'    => null,
			'function' => '[main]'
		);

		for($i = $options['start']; $i < $count && $i < ($options['depth']+$options['start']); $i++){
			$trace = array_merge(array('file' => '[internal]', 'line' => '??'), $backtrace[$i]);
			$signature = $reference = '[main]';

			if(isset($backtrace[$i + 1])){
				$next = array_merge($_trace, $backtrace[$i + 1]);
				$signature = $reference = $next['function'];

				if(!empty($next['class'])){
					$signature = $next['class'] . '::' . $next['function'];
					$reference = $signature . '(';
					if($options['args'] && isset($next['args'])){
						$args = array();
						foreach($next['args'] as $arg){
							$args[] = htmlentities(print_r($arg, true));
						}
						$reference .= join(', ', $args);
					}
					$reference .= ')';
				}
			}
			if(in_array($signature, $options['exclude'])){
				continue;
			}
			if($options['format'] == 'points' && $trace['file'] != '[internal]'){
				$back[] = array('file' => $trace['file'], 'line' => $trace['line']);
			} elseif($options['format'] == 'array'){
				$back[] = $trace;
			} else {
				$trace['path'] = $trace['file'];
				$trace['reference'] = $reference;
				unset($trace['object'], $trace['args']);
				$back[] = $trace['reference']." - ".$trace['path'].", line ".$trace['line'];			
			}
		}

		if($options['format'] == 'array' || $options['format'] == 'points'){
			return $back;
		}
		return '<pre style="margin:0">'.implode("\n", $back).'</pre>';
	}
	
	/**
	 *	DEBUG EXCERPT
	 *
	 *	@param String $filePath - File Location
	 *	@param Int $line - Line Number
	 *	@param Int $contenxt - Number of lines above and below.
	 */
	
	public static function excerpt($filePath, $line, $context = 2) {
		$lines = array();
		$errorLine  = $line;
		
		if(is_readable($filePath)){
			if ($line < 0 || $context < 0) {
				return false;
			}
				
			//Based on a 0 index;
			$line--;
			
			//Line Number Offset.
			$offset = ($line - $context < 0)?abs($line - $context - 1):1;
			
			if(class_exists("SplFileObject")){
				$data = new SplFileObject($filePath);	
							
				//We Can't Seek Into The Minus!
				if($line - $context > 0){	
					$data->seek($line - $context);
				}
				
				for($i = $line - $context; $i <= $line + $context; $i++){
					if(!$data->valid()){
						//No More Information
						break;
					}
					
					//Format Current Line + Add To Array
					$lines[$i+$offset] = str_replace(array("\r\n", "\n"), "", $data->current());
					
					//Read Next Line
					$data->next();
				}
			} else {
				//Old Fasion Way. Load The Whole File Into An Array.
				$data = @explode("\n", file_get_contents($filePath));
		
				//Sanity Check
				if(empty($data) || !isset($data[$line])){
					return false;
				}
				
				for($i = $line - $context; $i <= $line + $context; $i++){
					if(!isset($data[$i+$offset-1])){
						//No More Information
						break;
					}
					
					//Format Current Line + Add To Array
					$lines[$i+$offset] = str_replace(array("\r\n", "\n"), "", $data[$i+$offset-1]);
				}
			}
		}
			
		//Return Array Of Line Items		
		return Debug::formatCode($lines, $errorLine);
	}
	
	
	/**
	 *	Exit Processing
	 *
	 *	@param Int $status - Exit Status (0 - 254)
	 */
	public function stop($status = 0){
		exit($status);
	}
	
	/**
	 *	Make Error Priority
	 */
	public function isError(){
		//This Function Requires Inistigating 
		if(!isset($this) || isset($this) && get_class($this) != __CLASS__){
			return Debug::init()->isError();
		}
	
		$this->prioritiseError = true;
		return $this;
	}		
	
	/**
	 *	Conditional Show
	 *
	 *	@param Bool $condition - An statement resulting in true or false.
	 */
	public function say($condition){
		//This Function Requires Inistigating 
		if(!isset($this) || isset($this) && get_class($this) != __CLASS__){
			return Debug::init()->say($condition);
		}
	
		$this->suppressOutput = !$condition;
		return $this;
	}		

	/**
	 *	Set Color
	 *
	 *	@param String $fontColor - Valid Color Hex or Name
	 *	@param String $backColor - Valid Color Hex or Name
	 */
	public function setColor($fontColor, $backColor = false){
		//This Function Requires Inistigating 
		if(!isset($this) || isset($this) && get_class($this) != __CLASS__){
			return Debug::init()->setColor($fontColor, $backColor);
		}

		$this->setFontColor($fontColor);
		$this->setBackColor($backColor);
		return $this;
	}	
	
	/**
	 *	Set Font Color
	 *
	 *	@param String $color - Valid Color Hex or Name
	 */
	public function setFontColor($color){
		//This Function Requires Inistigating 
		if(!isset($this) || isset($this) && get_class($this) != __CLASS__){
			return Debug::init()->setFontColor($color);
		}
	
		if($this->validateColor($color)){
			$this->currentTextColor = $color;
		}
		return $this;
	}
	
	/**
	 *	Set Background Color
	 *
	 *	@param String $color - Valid Color Hex or Name
	 */
	public function setBackColor($color){
		//This Function Requires Inistigating 
		if(!isset($this) || isset($this) && get_class($this) != __CLASS__){
			return Debug::init()->setBackColor($color);
		}
	
		if($this->validateColor($color)){
			$this->currentBackColor = $color;
		}
		return $this;
	}
	
	/**
	 *	Set Text Style
	 *
	 *	Either:
	 *		@param String $style - (bold, italic, oblique, normal, overline, line-through, underline ) 
	 *		@param Array $style - Any Key=>Pair Css value; 
	 */
	public function setStyle($style){
		//This Function Requires Inistigating 
		if(!isset($this) || isset($this) && get_class($this) != __CLASS__){
			return Debug::init()->setStyle($style);
		}
		if(!is_array($style)){
			if($fontStyle = $this->validateStyle($style)){
				$this->currentStyle[$fontStyle[0]] = $fontStyle[1];
			}
		} else {
			$this->currentStyle = array_merge($this->currentStyle, $style);
		}
		return $this;
	}
	
		
	
	/**
	 *	Private Inhouse Functions
	 */


	/**
	 *	Output
	 *
	 *	@param String $output - Used for static calls
	 */
	private function output($output = false){
		global ${HTML};
		 
		//Suppress Message Or Not?
		$suppress = false;
		$error = false;
		if(isset($this) && get_class($this) == __CLASS__){
			$suppress = $this->suppressOutput;
			$error = $this->prioritiseError;
		}
		
		if(Debug::$debugEnabled && !$suppress){
			$output = ($output)?$output:$this->currentOutput;
			if(Debug::$debugToConsole){
				if(!empty(${HTML})){
					${HTML}->assets->includeJs("<script>\r\n//<![CDATA[\r\nif(!console){var console={log:function(){}}}"
												   ."console.log(\"".addcslashes(print_r($output, true), "\\\'\"&\n\r<>")."\");"
												   ."\r\n//]]>\r\n</script>");
				}
			} else {
				//Try and send debug information to the top of the page.
				if(!empty(${HTML})){
					if($error){
						${HTML}->setError($output);
					} else {
						${HTML}->setDebug($output);
					}
				} else {
					//Just print there and then
					print $output;
				}
			}
		}
	}
		
	/**
	 *	Build HTML
	 *
	 *	@param String $ouput- The String for output
	 *	@return String - The whole html with style
	 */
	private function buildHtml($output){
		$returnString = Debug::$defaultTemplate;
			
		//Return Styled Html
		if(Debug::$debugToConsole){
			return strip_tags($output);
		}
		return HtmlBuilder::make("span")->css(Debug::formatStyle())->html(str_replace("{HTML}", $output, $returnString))->output();
	}
	
	/**
	 *	Format Item 
	 *
	 *	@param Mix $item - The output type
	 *	@return String - Html
	 */
	private function formatItem($item){
		if(is_array($item) || is_object($item)){
			//utf8_encode for dodgy chars. htmlentities would return an empty string.
			$item = htmlentities(utf8_encode(print_r($item,true)));
		} else {
			//Reformat Types
			if(is_float($item)){
				$item = "(float) ".$item;
			} else if(is_int($item)){
				$item = "(int) ".$item;
			} else if(is_null($item)){
				$item = "null";
			} else if(is_bool($item)){
				$item = ($item)?"true":"false";
				$item = "(bool) ".$item;
			} else if(is_resource($item)){
				$item = strtolower(gettype($item));
			} 
			
			$item = (string)$item;
		}
		
		return Debug::buildHtml($item);
	}
	
	/**
	 *	Format Code 
	 *
	 *	@param Array $lines - The lines of code to display
	 *	@param Int $errorLine - The line of the error
	 *	@param
	 *	@return String - Formatted Code
	 */
	private function formatCode($lines, $errorLine){
		$return = "";
		
		//Find Minimum Tab Count At Start Of Line
		$tabRemoveCount = null;
		foreach($lines as $line => $loc){
			if(!empty($loc)){
				$currentTabCount = strspn($loc, "\t");
				if($currentTabCount < $tabRemoveCount || is_null($tabRemoveCount)){
					$tabRemoveCount = $currentTabCount;
				}
			}
		}
		
		//Format Lines
		foreach($lines as $line => $loc){
			$loc = substr($loc, strlen(str_repeat("\t",$tabRemoveCount)), strlen($loc));
			
			if($line == $errorLine){
				$return .= HtmlBuilder::make_r("strong", $line."| ".$loc)."\n";
			} else {
				$return .= $line."| ".HtmlBuilder::make_r("em", $loc)."\n";
			}
		}
		
		//RETURN PREFORAMATTED.
		return '<pre style="margin:0">'.$return.'</pre>';
	}
	
	/**
	 *	Format Style 
	 *
	 *	@return Array - Array of styles that you can pass to HtmlBuilder
	 */
	private function formatStyle(){		
		//Set Style
		$style = Debug::$defaultStyle['common'];
		
		//If there is a theme merge it with default
		if(isset(Debug::$defaultStyle[Debug::$currentTheme])){
			$style = array_merge($style, Debug::$defaultStyle[Debug::$currentTheme]);
		}
		
		//Default Color 
		$fontColor = Debug::$defaultColors[Debug::$currentTheme];
		$backColor = Debug::$defaultIterationColors[Debug::$currentTheme][Debug::$iterationCounter % 2];
		
		if(isset($this) || isset($this) && get_class($this) != __CLASS__){
			//Sort out Color + Background
			if(isset($this->currentTextColor)){
				$fontColor = $this->currentTextColor;
			}
			$backColor = isset($this->currentBackColor)?$this->currentBackColor:$backColor;
			
			if(isset($this->currentBackColor)){
				$backColor = $this->currentBackColor;
			}
						
			//Sort Out Style
			if(!empty($this->currentStyle)){
				$style = array_merge($style, $this->currentStyle);
			}
		}
		
		//Merge Color Rules
		$style = array_merge($style, array("color" => $fontColor));
		$style = array_merge($style, array("background-color" => $backColor));

		return $style;
	}

	/**
	 *	Return true if color validates
	 *
	 *	@param String $color - Color name or Hex Value
	 *	@return Boolean
	 */	
	private function validateColor($color){
		
		if(substr($color, 0, 1) == "#" && preg_match('/^#([0-9A-F]{3}|[0-9A-F]{6})$/i', $color)){	
			return true;
		} else if(in_array(strtolower($color), array("aqua", "black", "blue", "fuchsia", "gray", 
													 "grey", "green", "lime", "maroon", "navy", 
													 "olive", "purple", "red", "silver", "teal", 
													 "white", "yellow"))){
			return true;
		}
		
		return false;
	}
	
	/**
	 *	Validates Font Styling
	 *
	 *	@return String - css font style
	 */
	private function validateStyle($style){
		$style = strtolower($style);

		if(in_array($style, array("normal","italic","oblique"))){
			return array("font-style", $style);	
		} else if(in_array($style, array("overline", "line-through", "underline"))){
			return array("text-decoration", $style);	
		} else if($style == "bold"){
			return array("font-weight", $style);	
		}
		
		return false;
	}
}

if(!defined("DEBUG_ENABLED")) define("DEBUG_ENABLED", true, true); //Defualt True
if(!defined("DEBUG_ROUTE_TO_CONSOLE")) define("DEBUG_ROUTE_TO_CONSOLE", !DEBUG_ENABLED, true);//Defualt -> If Debuging Disabled Route To Console = TRUE

/* Helper Function To Remove The Need To Write 'new' Everytime */
function debug($condition = null){
	static $iterationCounter;
    return new Debug(DEBUG_ENABLED, $iterationCounter++, $condition, DEBUG_ROUTE_TO_CONSOLE);
}
function console(){
	//Console Ignores Debug = False as it displays silently.
    return new Debug(true, 0, null, true);
}
?>