<?php
declare(encoding = 'utf-8');

/***************************************************************
*  Copyright notice
*
*  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
 * Remove Cross Site Scripting filter
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_system_filter_removexss implements tx_form_system_filter_interface {

	/**
	 * Constructor
	 *
	 * @param array $arguments Filter configuration
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($arguments = array()) {
	}

	/**
	 * Return filtered value
	 * Removes potential XSS code from the input string.
	 *
	 * Using an external class by Travis Puderbaugh <kallahar@quickwired.com>
	 *
	 * @param  string $value Unfiltered value
	 * @return string The filtered value
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function filter($value) {
		$value = stripslashes($value);
		$value = html_entity_decode($value, ENT_QUOTES);

		$filteredValue = t3lib_div::removeXSS($value);

		return $filteredValue;
	}
}
?>