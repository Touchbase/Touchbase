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
 *  @category Filesystem
 *  @date 23/12/2013
 */
 
namespace Touchbase\Filesystem;

defined('TOUCHBASE') or die("Access Denied.");

class File extends Filesystem {

	public $path = null;
	public $name = null;
	public $folder = null;
	public $info = null;
	
	public $handle = null;
	protected $lock = null;
	
	public function __construct($path, $create = null, $mode = null){
		if(!empty($path)){
			if(is_array($path)){
				$path = call_user_func_array("static::buildFolderPath", $path);
			}
			
			$this->folder = Folder::create(dirname($path), $create, $mode);
			
			if(!is_dir($path)){
				$this->name = basename($path);
			}

			$this->path = $this->folder->path.$this->name;
				
			if($create && !$this->exists()){
				$this->create();
			}
			
			$this->info();
		}
	}
	
	public function __destruct(){
		$this->close();
	}
	
	//FILE MANIPULATION
	public function open($mode = 'r', $force = false){
		if(!$force && is_resource($this->handle)){
			return true;
		}
		clearstatcache();
		if($this->exists() === false){
			if($this->create() === false){
				return false;
			}
		}

		$this->handle = fopen($this->path, $mode);
		if(is_resource($this->handle)){
			return true;
		}
		return false;
	}

	public function touch(){
		if($this->folder->exists() && $this->folder->writeable() && !$this->exists()){
			$old = umask(0);
			if(touch($this->path)){
				umask($old);
				return true;
			}
		}
		return false;
	}
	
	public function read($bytes = false, $mode = 'rb', $force = false){
		if($this->exists()){
			if($bytes === false && $this->lock === null){
				return file_get_contents($this->path);
			}
			if($this->open($mode, $force) === false){
				return false;
			}
			if($this->lock !== null && flock($this->handle, LOCK_SH) === false){
				return false;
			}
			if(is_int($bytes)){
				return fread($this->handle, $bytes);
			}
		
			$data = '';
			while(!feof($this->handle)){
				$data .= fgets($this->handle, 4096);
			}
		
			if($this->lock !== null){
				flock($this->handle, LOCK_UN);
			}
			if($bytes === false){
				$this->close();
			}
			return trim($data);
		}
		
		return false;
	}
	
	public function excerpt($line, $context = 2) {
		$lines = array();
		
		if($this->exists()){
			if ($line < 0 || $context < 0) {
				return false;
			}
				
			//Based on a 0 index;
			$line--;
			
			//Line Number Offset.
			$offset = ($line - $context < 0)?abs($line - $context - 1):1;
			
			if(class_exists("SplFileObject")){
				$data = new SplFileObject($this->path);	
							
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
				$data = @explode("\n", $this->read());
		
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
		return $lines;
	}

	
	public function write($data, $mode = 'w', $force = false){
		$success = false;
		if($this->open($mode, $force) === true){
			if($this->lock !== null){
				if(flock($this->handle, LOCK_EX) === false){
					return false;
				}
			}

			if(fwrite($this->handle, $data) !== false){
				$success = true;
			}
			if($this->lock !== null){
				flock($this->handle, LOCK_UN);
			}
		}
		return $success;
	}
	
	public function append($data, $force = false) {
		return $this->write($data, 'a', $force);
	}
	
	public function copy($dest, $overwrite = true){
		if(!$this->exists() || is_file($dest) && !$overwrite){
			return false;
		}
		return copy($this->path, $dest);
	}
	
	public function delete(){
		clearstatcache();
		if(is_resource($this->handle)){
			fclose($this->handle);
			$this->handle = null;
		}
		if($this->exists()){
			return unlink($this->path);
		}
		return false;
	}
	
	public function close(){
		if(!is_resource($this->handle)){
			return true;
		}
		return fclose($this->handle);
	}
	
	public function offset($offset = false, $seek = SEEK_SET){
		if($offset === false){
			if(is_resource($this->handle)){
				return ftell($this->handle);
			}
		} elseif($this->open() === true){
			return fseek($this->handle, $offset, $seek) === 0;
		}
		return false;
	}
	
	//FILE HELPERS
	public function info(){
		if ($this->info == null) {
			$this->info = pathinfo($this->path);
		}
		if (!isset($this->info['filename'])) {
			$this->info['filename'] = $this->name();
		}
		if (!isset($this->info['filesize'])) {
			$this->info['filesize'] = $this->size();
		}
		if (!isset($this->info['mime'])) {
			$this->info['mime'] = $this->mime();
		}
		return $this->info;
	}

	public function executable(){
		return is_executable($this->path);
	}

	public function owner(){
		if($this->exists()){
			return fileowner($this->path);
		}
		return false;
	}

	public function group(){
		if($this->exists()){
			return filegroup($this->path);
		}
		return false;
	}
	
	public function size(){
		if($this->exists()){
			return filesize($this->path);
		}
		return false;
	}

	public function lastAccessed(){
		if($this->exists()){
			return fileatime($this->path);
		}
		return false;
	}

	public function lastChanged() {
		if($this->exists()){
			return filemtime($this->path);
		}
		return false;
	}
	
	public function perms(){
		if($this->exists()){
			return substr(sprintf('%o', fileperms($this->path)), -4);
		}
		return false;
	}
	
	public function ext(){
		return $this->info['extension'];
	}
	
	public function mime(){
		if($this->exists()){
			if(function_exists('finfo_open')){
				$finfo = finfo_open(FILEINFO_MIME);
				list($type, $charset) = explode(';', finfo_file($finfo, $this->path));
			} elseif (function_exists('mime_content_type')) {
				//A Depriciated function. But better than nothing!
				$type = @mime_content_type($this->path);
			} else {
				//All Else Fails.				
				$regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*(".$this->info['extension']."\s)/i"; 
				$lines = file(dirname(__FILE__)."/mime.types"); 
				foreach($lines as $line) { 
					if(substr($line, 0, 1) == '#') continue; // skip comments 
					$line = rtrim($line) . " "; 
					if(!preg_match($regex, $line, $matches)) continue; // no match to the extension 
					$type = $matches[1]; 
				} 
			}
		}
		return isset($type)?$type:false;
	}
	
	public function md5($maxsize = 5) {
		if($maxsize === true){
			return md5_file($this->path);
		}

		$size = $this->size();
		if ($size && $size < ($maxsize * 1024) * 1024) {
			return md5_file($this->path);
		}

		return false;
	}
}
