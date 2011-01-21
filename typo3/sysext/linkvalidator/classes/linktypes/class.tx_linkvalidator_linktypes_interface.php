<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 - 2011 Michael Miousse (michael.miousse@infoglobe.ca)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This class provides interface implementation.
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @package TYPO3
 * @subpackage linkvalidator
 */
interface tx_linkvalidator_linkTypes_Interface {

	/**
	 * Checks a given URL + /path/filename.ext for validity
	 *
	 * @param   string	  $url: url to check
	 * @param	 array	   $softRefEntry: the softref entry which builds the context of that url
	 * @param   object	  $reference:  parent instance of tx_linkvalidator_processing
	 * @return  string	  validation error message or succes code
	 */
	public function checkLink($url, $softRefEntry, $reference);

	/**
	 * Base type fetching method, based on the type that softRefParserObj returns.
	 *
	 * @param   array	 $value: reference properties
	 * @param   string	 $type: current type
	 * @param   string	 $key: validator hook name
	 * @return  string	 fetched type
	 */
	public function fetchType($value, $type, $key);

	/**
	 * Get the value of the private property errorParams.
	 *
	 * @return  array      all parameters needed for the rendering of the error message
	 */
	public function getErrorParams();

	/**
	 * Base url parsing
	 *
	 * @param	array		$row: broken link record
	 * @return	string		parsed broken url
	 */
	public function getBrokenUrl($row);

}

?>