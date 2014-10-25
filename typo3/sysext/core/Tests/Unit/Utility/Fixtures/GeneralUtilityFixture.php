<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GeneralUtilityFixture
 */
class GeneralUtilityFixture extends GeneralUtility {

	/**
	 * @var int
	 */
	public static $isAllowedHostHeaderValueCallCount = 0;

	/**
	 * Tracks number of calls done to this method
	 *
	 * @param string $hostHeaderValue Host name without port
	 * @return bool
	 */
	static public function isAllowedHostHeaderValue($hostHeaderValue) {
		self::$isAllowedHostHeaderValueCallCount++;
		return parent::isAllowedHostHeaderValue($hostHeaderValue);
	}

	/**
	 * @param bool $allowHostHeaderValue
	 */
	static public function setAllowHostHeaderValue($allowHostHeaderValue) {
		static::$allowHostHeaderValue = $allowHostHeaderValue;
	}

	/**
	 * For testing we must not generally allow HTTP Host headers
	 *
	 * @return bool
	 */
	static protected function isInternalRequestType() {
		return FALSE;
	}


}