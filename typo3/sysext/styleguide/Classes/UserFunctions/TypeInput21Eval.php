<?php
namespace TYPO3\CMS\Styleguide\UserFunctions;

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
 * A "eval" user function used in input_21
 */
class TypeInput21Eval {

	/**
	 * Adds text "JSfoo" at end on mouse out
	 *
	 * @return string
	 */
	function returnFieldJS() {
		return '
			return value + "JSfoo";
		';
	}

	/**
	 * Adds text "PHPfoo" at end on saving
	 *
	 * @param $value
	 * @param $is_in
	 * @param $set
	 * @return string
	 */
	function evaluateFieldValue($value, $is_in, &$set) {
		return $value . 'PHPfoo';
	}
}
