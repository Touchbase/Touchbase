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
 *  @category Database
 *  @date 23/12/2013
 */
 
namespace Touchbase\Database;

defined('TOUCHBASE') or die("Access Denied.");

interface DatabaseInterface
{	
	/**
	 * @param string $mode Either 'r' (reading) or 'w' (reading and writing)
	 */
	public function connect($mode = 'w');
	
	/**
	 * Disconnect from the connection
	 *
	 * @return mixed
	 */
	public function disconnect();
	
	/**
	 * Run a standard query
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function query($query);
	
	/**
	 * Get a single field
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function getField($query);
	
	/**
	 * Get a single row
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function getRow($query);
	
	/**
	 * Get multiple rows
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function getRows($query);
	
	/**
	 * Get a keyed array based on the first field of the result
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function getKeyedRows($query);
	
	/**
	 * Number of rows for a query
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function numRows($query);
	
	/**
	 * Get column names
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function getColumns($query);
	
	/**
	 * Escape column name
	 *
	 * @param $column
	 *
	 * @return mixed
	 */
	public function escapeColumnName($column);
	
	/**
	 * Escape string value for insert
	 *
	 * @param $string
	 *
	 * @return mixed
	 */
	public function escapeString($string);
	
	/**
	 * Last Inserted ID
	 *
	 * @return null|mixed
	 */
	public function insertId();
	
	/**
	 * Last Error Number
	 *
	 * @return mixed
	 */
	public function errorNo();
	
	/**
	 * Last Error Message
	 *
	 * @return mixed
	 */
	public function errorMsg();
	
	/**
	 * Number of rows affected by the last query
	 *
	 * @return int
	 */
	public function affectedRows();
}
