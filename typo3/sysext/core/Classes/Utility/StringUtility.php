<?php
namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Susanne Moog <typo3@susanne-moog.de>
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
 * Class with helper functions for string handling
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 */
class StringUtility {

	/**
	 * Returns TRUE if $haystack ends with $needle.
	 * The input string is not trimmed before and search
	 * is done case sensitive.
	 *
	 * @param string $haystack Full string to check
	 * @param string $needle Reference string which must be found as the "last part" of the full string
	 * @return boolean TRUE if $needle was found to be equal to the last part of $str
	 */
	static public function isLastPartOfString($haystack, $needle) {
			// Sanitize $haystack and $needle
		if (is_object($haystack) || (string)$haystack != $haystack || strlen($haystack) < 1) {
			throw new \InvalidArgumentException(
				'$haystack can not be interpreted as string or has no length',
				1347135544
			);
		}
		if (is_object($needle) || (string)$needle != $needle || strlen($needle) < 1) {
			throw new \InvalidArgumentException(
				'$needle can not be interpreted as string or has no length',
				1347135545
			);
		}
		$stringLength = strlen($haystack);
		$needleLength = strlen($needle);
		return strrpos((string) $haystack, (string) $needle, 0) === $stringLength - $needleLength;
	}

}


?>