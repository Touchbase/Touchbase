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
 *  @date 23/12/2013
 */
 
namespace Touchbase\Utils;

defined('TOUCHBASE') or die("Access Denied.");

class Transliterator extends \Touchbase\Core\BaseObject
{
	/**
	 *	@var BOOL
	 */
	protected static $useIconv = false;
	
	/* Public Methods */
		
	/**
	 *	TO ASCII
	 *	Convert the given utf8 string to a safe ASCII source
	 *	@param string $source
	 *	@return string
	 */
	public static function toASCII($source) {
		if(function_exists('iconv') && self::$useIconv){
			return iconv("utf-8", "us-ascii//IGNORE//TRANSLIT", $source);
		}
		
		return self::useStrTr($source);
	}

	/**
	 *	Use Str Tr
	 *	Transliteration using strtr() and a lookup table
	 *	@param string $source
	 *	@return string
	 */
	protected static function useStrTr($source) {
		$table = array(
			'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'Ae', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
			'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
			'Õ'=>'O', 'Ö'=>'Oe', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'Ue', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'ss',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'ae', 'å'=>'a', 'æ'=>'ae', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
			'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
			'ô'=>'o', 'õ'=>'o', 'ö'=>'oe', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'ue', 'ý'=>'y', 'ý'=>'y',
			'þ'=>'b', 'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
			'Ā'=>'A', 'ā'=>'a', 'Ē'=>'E', 'ē'=>'e', 'Ī'=>'I', 'ī'=>'i', 'Ō'=>'O', 'ō'=>'o', 'Ū'=>'U', 'ū'=>'u',
			'œ'=>'oe', 'ß'=>'ss', 'ĳ'=>'ij', 
			'ą'=>'a','ę'=>'e', 'ė'=>'e', 'į'=>'i','ų'=>'u','ū'=>'u', 'Ą'=>'A','Ę'=>'E', 'Ė'=>'E', 'Į'=>'I','Ų'=>'U','Ū'=>'u'
		);

		return strtr($source, $table);
	}
}