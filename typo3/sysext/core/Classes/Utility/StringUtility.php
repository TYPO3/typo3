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
 * A copy is found in the text file GPL.txt and important notices to the license
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
 * @author Markus Klein <klein.t3@mfc-linz.at>
 */
class StringUtility {

	/**
	 * Returns TRUE if $haystack ends with $needle.
	 * The input string is not trimmed before and search
	 * is done case sensitive.
	 *
	 * @param string $haystack Full string to check
	 * @param string $needle Reference string which must be found as the "last part" of the full string
	 * @throws \InvalidArgumentException
	 * @return boolean TRUE if $needle was found to be equal to the last part of $str
	 * @deprecated since 6.3 - will be removed two versions later, use beginsWith() instead
	 */
	static public function isLastPartOfString($haystack, $needle) {
		GeneralUtility::logDeprecatedFunction();
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

	/**
	 * Returns TRUE if the first part of $str matches the string $partStr
	 *
	 * @param string $haystack Full string to check
	 * @param string $needle Reference string which must be found as the "first part" of the full string
	 * @throws \InvalidArgumentException
	 * @return boolean TRUE if $needle was found to be equal to the first part of $haystack
	 */
	static public function beginsWith($haystack, $needle) {
		// Sanitize $haystack and $needle
		if (is_object($haystack) || $haystack === NULL || (string)$haystack != $haystack) {
			throw new \InvalidArgumentException(
				'$haystack can not be interpreted as string',
				1347135546
			);
		}
		if (is_object($needle) || (string)$needle != $needle || strlen($needle) < 1) {
			throw new \InvalidArgumentException(
				'$needle can not be interpreted as string or has zero length',
				1347135547
			);
		}
		$haystack = (string)$haystack;
		$needle = (string)$needle;
		return $needle !== '' && strpos($haystack, $needle) === 0;
	}

	/**
	 * Returns TRUE if $haystack ends with $needle.
	 * The input string is not trimmed before and search is done case sensitive.
	 *
	 * @param string $haystack Full string to check
	 * @param string $needle Reference string which must be found as the "last part" of the full string
	 * @throws \InvalidArgumentException
	 * @return boolean TRUE if $needle was found to be equal to the last part of $haystack
	 */
	static public function endsWith($haystack, $needle) {
		// Sanitize $haystack and $needle
		if (is_object($haystack) || $haystack === NULL || (string)$haystack != $haystack) {
			throw new \InvalidArgumentException(
				'$haystack can not be interpreted as string',
				1347135544
			);
		}
		if (is_object($needle) || (string)$needle != $needle || strlen($needle) < 1) {
			throw new \InvalidArgumentException(
				'$needle can not be interpreted as string or has no length',
				1347135545
			);
		}
		$haystackLength = strlen($haystack);
		$needleLength = strlen($needle);
		if (!$haystackLength || $needleLength > $haystackLength) {
			return FALSE;
		}
		$position = strrpos((string)$haystack, (string)$needle);
		return $position !== FALSE && $position === $haystackLength - $needleLength;
	}

}
