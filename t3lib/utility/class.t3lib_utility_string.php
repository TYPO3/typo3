<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 Susanne Moog <typo3@susanne-moog.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class with helper functions for array handling
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 * @package TYPO3
 * @subpackage t3lib
 */
final class t3lib_utility_String {

	/**
	 * Returns TRUE if the last part of $str matches the string $partStr
	 *
	 * @param string $string Full string to check
	 * @param string $partString Reference string which must be found as the "first part" of the full string
	 * @return boolean TRUE if $partStr was found to be equal to the first part of $str
	 */
	public static function isLastPartOfStr($string, $partString) {
		$stringLength = strlen($string);
		$partStringLength = strlen($partString);
		return $partString != '' && strrpos((string)$string, (string)$partString, 0) === ($stringLength - $partStringLength);
	}

}
?>