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
 *  @category Config
 *  @date 12/01/2014
 */
 
namespace Touchbase\Core\Config\Provider;

defined('TOUCHBASE') or die("Access Denied.");

class IniConfigProvider extends \Touchbase\Core\BaseObject
{
	/**
	 *	@var array
	 */
	protected $_iniFileData = array();
	
	/* Public Methods */
	
	/**
	 *	Get Configuration
	 *	@access public
	 *	@return void
	 */
	public function getConfiguration(){
		return \Touchbase\Data\Store::create()->fromArray($this->_iniFileData);
	}
	
	/**
	 *	Parse Ini File
	 *	@access public
	 *	@param \Touchbase\Filesystem\File $file
	 *	@return VOID
	 */
	public function parseIniFile(\Touchbase\Filesystem\File $file){
		if(!$file->exists()){
			throw new \Exception("Config file '$file->path' could not be found");
		}
		
		$this->_iniFileData = parse_ini_file($file->path, true);
		
		if(!$this->_iniFileData){
			throw new \Exception("The ini file '$file->path' is corrupt or invalid");
		}
		
		return $this;
	}
}