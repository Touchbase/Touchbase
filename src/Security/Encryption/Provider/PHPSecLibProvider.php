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
 *  @date 14/03/2014
 */
 
namespace Touchbase\Security\Encryption\Provider;

defined('TOUCHBASE') or die("Access Denied.");

use Touchbase\Security\EncryptionInterface;
use Touchbase\Core\Config\Store as ConfigStore;

class PHPSecLibProvider implements EncryptionInterface 
{
	protected $_encryption;
	
	public function configure(ConfigStore $config){
		$encryptionLibrary = $config->get("encryption")->get("library", "Crypt_AES");
		
		if(class_exists($encryptionLibrary)){
			$this->_encryption = new $encryptionLibrary;
			$this->_encryption->setKey(
				$config->get("encryption")->get("key", "#o%jR=S0Y6+ic7R$~6Y;0a7b65u\$_%")
			);
		} else {
			throw new \RuntimeException("Encryption library does not exist");
		}
	}

	/**
	 *	Encrypt
	 *	@param $string - input string
	 *	@return STRING
	 */
	public function encrypt($string){
		return $this->_encryption->encrypt($string);
	}
	
	/**
	 *	Decrypt
	 *	@param $string - input string
	 *	@return STRING
	 */
	public function decrypt($string){
		return $this->_encryption->decrypt($string);
	}
}