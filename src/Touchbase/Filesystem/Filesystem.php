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

class Filesystem extends \Touchbase\Core\Object {

	public $fileChmod = 02775;
	
	public $folderChmod = 02775;

	public function makeDir($folder, $recursive = true){
		if(!file_exists($folder)){
			return mkdir($folder, $this->folderChmod, $recursive);
		}
		
		return false;
	}

	public function removeDir($folder, $contentsOnly = false) {
		// remove a file encountered by a recursive call.
		if(is_file($folder) || is_link($folder)) {
			unlink($folder);
		} else {
			$dir = dir($folder);
			while($file = $dir->read()) {
				if(($file == '.' || $file == '..')) continue;
				else {
					$this->removeDir($folder . '/' . $file);
				}
			}
			$dir->close();
			
			if(!$contentsOnly){
				rmdir($folder);
			}
		}
	}
	
	public function dirModifiedTime($folder, $extensionList = null) {		
		$modTime = 0;
		
		$items = scandir(BASE_PATH.$folder);
		foreach($items as $item) {
			if($item[0] != '.') {
				// Recurse into folders
				if(is_dir("$folder/$item")) {
					$modTime = max($modTime, $this->dirModifiedTime("$folder/$item", $extensionList, true));
					
				// Check files
				} else {
					if($extensionList) $extension = strtolower(substr($item,strrpos($item,'.')+1));
					if(!$extensionList || in_array($extension, $extensionList)) {
						$modTime = max($modTime, filemtime("$folder/$item"));
					}
				}
			}
		}

		return $modTime;
	}
	
	public function dirSize($folder){
		if(is_dir($folder)){
			$io = popen('/usr/bin/du -sk '.$folder, 'r' );
			$size = fgets($io, 4096);
			$size = substr($size, 0, strpos($size, ' '));
			pclose($io);
			
			return $size;
		}
		return false;		
	}
	
	public function formatSize($size) {
		$sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
		if($size > 0) return round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i];
		return '-';
	}
	
	/**
	 *	Helper Methods
	 */
	 
	/**
	 *	Print Working Directory 
	 */
	public function pwd($fullPath = false){
		return !$fullPath?basename(dirname($this->path)):$this->path;
	}
	 
	/**
	 *	Path Exists
	 *	@param (String) - $path
	 *	@return (BOOL)
	 */
	public function exists($path = null){
		$path = isset($path)?$path:$this->path;
		return file_exists($path) && (is_dir($path) || is_file($path));
	}
	
	/**
	 *	Path Writable
	 *	@return (BOOL)
	 */
	public function writable(){
		return is_writable($this->path);
	}
	
	/**
	 *	Path Readable
	 *	@return (BOOL)
	 */
	public function readable(){
		return is_readable($this->path);
	}
	
	/**
	 *	Build Folder Path
	 *	@return (string) - /example/file/path/
	 */
	public function buildFolderPath(){
		$folders = func_get_args();
		return str_replace(($DS = DIRECTORY_SEPARATOR).$DS, $DS, implode($DS, $folders)).$DS;
	}
}
