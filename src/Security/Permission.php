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
 *  @category Security
 *  @date 07/01/2015
 */
 
namespace Touchbase\Security;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Data\StaticStore;
use Touchbase\Security\PermissionInterface;
use Touchbase\Core\Config\Store as ConfigStore;

class Permission
{
	const PERMISSION_KEY = 'touchbase.key.permission';
	
	/** 
	 *	Shared
	 *	@return \Touchbase\Security\Permission
	 */
	public static function shared(){
		$instance = StaticStore::shared()->get(self::PERMISSION_KEY, false);
		if(!$instance || is_null($instance)){
			$config = StaticStore::shared()->get(ConfigStore::CONFIG_KEY, false);
			
			//Validation 
			if(!$config instanceof ConfigStore){
				throw new \RuntimeException("No configuration settings found.");
			}
			
			
			$instanceName = $config->get("permission")->get("provider", "\Touchbase\Security\Permission\Provider\AccessAllAreasProvider");
			$instance = new $instanceName;
			
			//Validation 
			if(!$instance instanceof PermissionInterface){
				throw new \InvalidArgumentException("Permission provider must be an instance of \Touchbase\Security\PermissionInterface");
			}
			
			//Configure
			$instance->configure($config);
			
			//Save
			StaticStore::shared()->set(self::PERMISSION_KEY, $instance);
		}
		
		return $instance;
	}
}