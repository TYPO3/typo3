<?php
namespace TYPO3\CMS\Core\Utility;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
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
