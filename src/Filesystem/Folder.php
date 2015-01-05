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

class Folder extends Filesystem {

	const FOLDERS = 0;
	const FILES = 1;

	public $path = null;
	public $sort = false;
	
	/**
	 *	Ignore Files
	 */
	public $ignore = [
		'.svn',
		'.DS_Store'
	];

	protected $_messages = array();
	protected $_errors = array();
	protected $_directories;
	protected $_files;

	public function __construct($path, $create = null, $mode = null) {
		
		if($mode){
			$this->folderChmod = $mode;
		}
		
		//Does the developer want use to construct the path?
		if(is_array($path)){
			$path = call_user_func_array([$this, "buildFolderPath"], $path);
		}
		
		//TODO: Allow empty paths to create tempory folders?
		if(!$this->exists($path) && $create === true){
			$this->makeDir($path);
		}
		
		//if (!Folder::isAbsolute($path)) {
		//	$path = realpath($path);
		//}
			
		if(!empty($path)){
			$this->cd($path);
		}
	}

	/**
	 *	Change Directory
	 *	@param String - $path
	 *	@return (SELF)
	 */
	public function cd($path){
		$path = realpath($path)."/";
		
		if($this->exists($path)){		
			$this->path = $path;
		}	

		return $this;
	}
		
	/**
	 *	Find Files
	 *	@param (String) $regexpPattern - .*\.(jpg|jpeg|png|gif) -> Search For Images
	 *	@param (BOOL) $fullPath
	 *	@param (BOOL) $sort
	 *	@param (BOOL) $recursive
	 *	@return Array
	 */
	public function findRecursive($regexpPattern = '.*', $fullPath = true, $sort = false){
		return $this->find($regexpPattern, $fullPath, $sort, true);
	}
	public function find($regexpPattern = '.*', $fullPath = true, $sort = false, $recursive = false){
		$regexpPattern = is_null($regexpPattern)?'.*':$regexpPattern;
		
		list($dirs, $files) = $this->read($sort, $fullPath);
	
		if(!$recursive){
			return array_values(preg_grep('|^' . $regexpPattern . '$|si', $files));
		} else {
			$found = array();
			
			foreach($files as $file){
				if(preg_match('|^' . $regexpPattern . '$|si', $file)){
					$found[] = $file;
				}
			}
			
			$startDir = $this->path;	
			foreach($dirs as $dir){
				$this->cd((!$fullPath?$startDir:'').$dir.DIRECTORY_SEPARATOR);
				$found = array_merge($found, $this->findRecursive($regexpPattern, $fullPath, $sort));
			}
			$this->cd($startDir);
			
			return $found;
		}
	}
		
	/**
	 *	Read Directory
	 *	@param (BOOL) $sort
	 *	@param (BOOL) $fullPath
	 *	@param (Array) $ignore
	 *	@return ([directories, files])
	 */
	public function read($sort = true, $fullPath = false, $ignore = array()){
		$dirs = $files = array();
				
		if($this->path){
			if(is_array($ignore)){
				$ignore = array_merge($ignore, $this->ignore);
				$ignore = array_flip($ignore);
			}
			$skipHidden = isset($ignore['.']) || $ignore === true;
	
			try {
				//PHP CLASS: DirectoryIterator
				$iterator = new \DirectoryIterator($this->path);
				
				foreach($iterator as $item){
					if($item->isDot()){
						continue;
					}
					
					$name = $item->getFileName();
					if(($skipHidden && $name[0] === '.') || isset($ignore[$name])){
						continue;
					}
					
					if($fullPath){
						$name = $item->getPathName();
					}	
					
					if($item->isDir()){
						$dirs[] = $name;
					} else {
						$files[] = $name;
					}
				}
				
				if($sort || $this->sort){
					sort($dirs);
					sort($files);
				}
				
			} catch(Exception $e){}
		}
		
		return array($dirs, $files);
	}
	
	/**
	 *	Tree
	 *	@param (Array) $ignore
	 *	@return Array
	 */
	public function tree($ignore = array()){
		$dirs = $files = array();
		
		if(is_array($exceptions)){
			$exceptions = array_flip($exceptions);
		}
		
		if(is_array($ignore)){
			$ignore = array_merge($ignore, $this->ignore);
			$ignore = array_flip($ignore);
		}
		$skipHidden = isset($ignore['.']) || $ignore === true;

		try {
			$directory = new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::KEY_AS_PATHNAME | \RecursiveDirectoryIterator::CURRENT_AS_SELF);
			$iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
			
			foreach($iterator as $itemPath => $fsIterator){
				if($skipHidden){
					$subPathName = $fsIterator->getSubPathname();
					if($subPathName[0] === '.' || strpos($subPathName, DIRECTORY_SEPARATOR.'.') !== false){
						continue;
					}
				}
				
				$item = $fsIterator->current();
				if((!empty($ignore) && isset($ignore[$item->getFilename()])) || $item->isDot()){
					continue;
				}

				if($item->isDir()){
					$dirs[] = $itemPath;
				} else {
					$files[] = $itemPath;
				}				
			}
		} catch (Exception $e){}

		return array($dirs, $files);
	}
	
		
/*
	public function inPath($path = '', $reverse = false) {
		$dir = Folder::slashTerm($path);
		$current = Folder::slashTerm($this->path);

		if (!$reverse) {
			$return = preg_match('/^(.*)' . preg_quote($dir, '/') . '(.*)/', $current);
		} else {
			$return = preg_match('/^(.*)' . preg_quote($current, '/') . '(.*)/', $dir);
		}
		return (bool)$return;
	}
*/

	public function chmod($mode = false, $recursive = true, $exceptions = array()) {
		//Revert to default if empty!
		$mode = ($mode)?$mode:$this->folderChmod;

		if($recursive === false && $this->exists()){
			if(@chmod($this->path, intval($mode, 8))){
				$this->_messages[] = sprintf('%s changed to %s', $this->path, $mode);
				return true;
			}

			$this->_errors[] = sprintf('%s NOT changed to %s', $this->path, $mode);
			return false;
		}

		if($this->exists()){
			$paths = $this->tree();

			foreach($paths as $type){
				foreach($type as $key => $fullpath){
					$check = explode(DS, $fullpath);
					$count = count($check);

					if(in_array($check[$count - 1], $exceptions)){
						continue;
					}

					if(@chmod($fullpath, intval($mode, 8))){
						$this->_messages[] = sprintf('%s changed to %s', $fullpath, $mode);
					} else {
						$this->_errors[] = sprintf('%s NOT changed to %s', $fullpath, $mode);
					}
				}
			}

			if(empty($this->_errors)){
				return true;
			}
		}
		return false;
	}
	
	public function copy($options = array()){
		if(!$this->path){
			return false;
		}
		$to = null;
		if(is_string($options)){
			$to = $options;
			$options = array();
		}
		$options = array_merge(
			array('to' => $to,
				  'from' => $this->path,
				  'mode' => $this->folderChmod,
				  'skip' => array())
		, $options);

		$fromDir = $options['from'];
		$toDir = $options['to'];
		$mode = $options['mode'];

		if(!$this->cd($fromDir)){
			$this->_errors[] = sprintf('%s not found', $fromDir);
			return false;
		}

		if(!is_dir($toDir)){
			$this->makeDir($toDir);
		}

		if(!is_writable($toDir)){
			$this->_errors[] = sprintf('%s not writable', $toDir);
			return false;
		}

		$exceptions = array_merge(
			array('.', '..', '.svn')
		, $options['skip']);
		
		if ($handle = @opendir($fromDir)) {
			while (false !== ($item = readdir($handle))) {
				if (!in_array($item, $exceptions)) {
					$from = Folder::addPathElement($fromDir, $item);
					$to = Folder::addPathElement($toDir, $item);
					if (is_file($from)) {
						if (copy($from, $to)) {
							chmod($to, intval($mode, 8));
							touch($to, filemtime($from));
							$this->_messages[] = sprintf('%s copied to %s', $from, $to);
						} else {
							$this->_errors[] = sprintf('%s NOT copied to %s', $from, $to);
						}
					}

					if (is_dir($from) && !file_exists($to)) {
						$old = umask(0);
						if (mkdir($to, $mode)) {
							umask($old);
							$old = umask(0);
							chmod($to, $mode);
							umask($old);
							$this->_messages[] = sprintf('%s created', $to);
							$options = array_merge($options, array('to' => $to, 
																   'from' => $from));
							$this->copy($options);
						} else {
							$this->_errors[] = sprintf('%s not created', $to);
						}
					}
				}
			}
			closedir($handle);
		} else {
			return false;
		}

		if (!empty($this->_errors)) {
			return false;
		}
		return true;
	}

	public function move($options){
		$to = null;
		if(is_string($options)){
			$to = $options;
			$options = (array)$options;
		}
		
		$options = array_merge(
			array('to' => $to, 
				  'from' => $this->path,
				  'mode' => $this->folderChmod,
				  'skip' => array())
		, $options);

		//We Copy The Directory
		if($this->copy($options)){
			//Then Delete It!
			if($this->removeDir($options['from'])){
				return (bool)$this->cd($options['to']);
			}
		}
		
		return false;
	}
	
	/**
	 *	Helper Methods
	 */

	public function messages() {
		return $this->_messages;
	}

	public function errors() {
		return $this->_errors;
	}
}