<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Susanne Moog <typo3@susanne-moog.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class with helper functions for version number handling
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 * @package TYPO3
 * @subpackage t3lib
 */
final class t3lib_utility_VersionNumber {

	/**
	 * Returns an integer from a three part version number, eg '4.12.3' -> 4012003
	 *
	 * @param $versionNumber string Version number on format x.x.x
	 * @return integer Integer version of version number (where each part can count to 999)
	 */
	public static function convertVersionNumberToInteger($versionNumber) {
		$versionParts = explode('.', $versionNumber);
		return intval((int) $versionParts[0] . str_pad((int) $versionParts[1], 3, '0', STR_PAD_LEFT) . str_pad((int) $versionParts[2], 3, '0', STR_PAD_LEFT));
	}

	/**
	 * Returns the three part version number (string) from an integer, eg 4012003 -> '4.12.3'
	 *
	 * @param $versionInteger integer Integer representation of version number
	 * @return string Version number as format x.x.x
	 * @throws \InvalidArgumentException if $versionInteger is not an integer
	 */
	public static function convertIntegerToVersionNumber($versionInteger) {
		if (!is_int($versionInteger)) {
			throw new \InvalidArgumentException(
				't3lib_utility_VersionNumber::convertIntegerToVersionNumber() supports an integer argument only!',
				1334072223
			);
		}

		$versionString = str_pad($versionInteger, 9, '0', STR_PAD_LEFT);
		$parts = array(
			substr($versionString, 0, 3),
			substr($versionString, 3, 3),
			substr($versionString, 6, 3)
		);
		return intval($parts[0]) . '.' . intval($parts[1]) . '.' . intval($parts[2]);
	}

	/**
	 * Splits a version range into an array.
	 *
	 * If a single version number is given, it is considered a minimum value.
	 * If a dash is found, the numbers left and right are considered as minimum and maximum. Empty values are allowed.
	 *
	 * @param	string		$ver A string with a version range.
	 * @return	array
	 */
	public static function splitVersionRange($ver) {
		$versionRange = array();
		if (strstr($ver, '-')) {
			$versionRange = explode('-', $ver, 2);
		} else {
			$versionRange[0] = $ver;
			$versionRange[1] = '';
		}

		if (!$versionRange[0]) {
			$versionRange[0] = '0.0.0';
		}
		if (!$versionRange[1]) {
			$versionRange[1] = '0.0.0';
		}

		return $versionRange;
	}
}

?>