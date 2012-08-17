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
class t3lib_utility_VersionNumber {

	/**
	 * Returns an integer from a three part version number, eg '4.12.3' -> 4012003
	 *
	 * @param string $versionNumber Version number on format x.x.x
	 * @return integer Integer version of version number (where each part can count to 999)
	 */
	public static function convertVersionNumberToInteger($versionNumber) {
		$versionParts = explode('.', $versionNumber);
		return intval((int) $versionParts[0] . str_pad((int) $versionParts[1], 3, '0', STR_PAD_LEFT) . str_pad((int) $versionParts[2], 3, '0', STR_PAD_LEFT));
	}

	/**
	 * Returns the three part version number (string) from an integer, eg 4012003 -> '4.12.3'
	 *
	 * @param integer $versionInteger Integer representation of version number
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
	 * If no version can be parsed "0.0.0" — "0.0.0" is the result
	 *
	 * @param string $version A string with a version range.
	 * @return array
	 */
	public static function splitVersionRange($version) {
		$versionRange = array();
		if (strstr($version, '-')) {
			$versionRange = explode('-', $version, 2);
		} else {
			$versionRange[0] = $version;
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

	/**
	 * Removes -dev -alpha -beta -RC states from a version number
	 * and replaces them by .0
	 *
	 * @static
	 * @return string
	 */
	public static function getNumericTypo3Version() {
		$t3version = static::getCurrentTypo3Version();
		if (stripos($t3version, '-dev')
			|| stripos($t3version, '-alpha')
			|| stripos($t3version, '-beta')
			|| stripos($t3version, '-RC')) {
				// find the last occurence of "-" and replace that part with a ".0"
			$t3version = substr($t3version, 0, strrpos($t3version, '-')) . '.0';
		}
		return $t3version;
	}

	/**
	 * Wrapper function for TYPO3_version constant to make functions using
	 * the constant unit testable
	 *
	 * @static
	 * @return string
	 */
	protected static function getCurrentTypo3Version() {
		return TYPO3_version;
	}

	/**
	 * This function converts version range strings (like '4.2.0-4.4.99') to an array
	 * (like array('4.2.0', '4.4.99'). It also forces each version part to be between
	 * 0 and 999
	 *
	 * @param string $versionsString
	 * @return array
	 */
	public static function convertVersionsStringToVersionNumbers($versionsString) {
		$versions = t3lib_div::trimExplode('-', $versionsString);
		for ($i = 0; $i < count($versions); $i++) {
			$cleanedVersion = t3lib_div::trimExplode('.', $versions[$i]);
			for ($j = 0; $j < count($cleanedVersion); $j++) {
				$cleanedVersion[$j] = t3lib_utility_Math::forceIntegerInRange($cleanedVersion[$j], 0, 999);
			}
			$cleanedVersionString = implode('.', $cleanedVersion);
			if (static::convertVersionNumberToInteger($cleanedVersionString) === 0) {
				$cleanedVersionString = '';
			}
			$versions[$i] = $cleanedVersionString;
		}
		return $versions;
	}

	/**
	 * Parses the version number x.x.x and returns an array with the various parts.
	 * It also forces each … 0 to 999
	 *
	 * @param string $version Version code, x.x.x
	 * @return array
	 */
	public static function convertVersionStringToArray($version) {
		$parts = t3lib_div::intExplode('.', $version . '..');
		$parts[0] = t3lib_utility_Math::forceIntegerInRange($parts[0], 0, 999);
		$parts[1] = t3lib_utility_Math::forceIntegerInRange($parts[1], 0, 999);
		$parts[2] = t3lib_utility_Math::forceIntegerInRange($parts[2], 0, 999);

		$result = array();
		$result['version'] = $parts[0] . '.' . $parts[1] . '.' . $parts[2];
		$result['version_int'] = intval($parts[0] * 1000000 + $parts[1] * 1000 + $parts[2]);
		$result['version_main'] = $parts[0];
		$result['version_sub'] = $parts[1];
		$result['version_dev'] = $parts[2];

		return $result;
	}

	/**
	 * Method to raise a version number
	 *
	 * @param string $raise one of "main", "sub", "dev" - the version part to raise by one
	 * @param string $version (like 4.1.20)
	 * @return string
	 * @throws t3lib_exception
	 */
	public static function raiseVersionNumber($raise, $version) {
		if (!in_array($raise, array('main', 'sub', 'dev'))) {
			throw new t3lib_exception('RaiseVersionNumber expects one of "main", "sub" or "dev".', 1342639555);
		}
		$parts = t3lib_div::intExplode('.', $version . '..');
		$parts[0] = t3lib_utility_Math::forceIntegerInRange($parts[0], 0, 999);
		$parts[1] = t3lib_utility_Math::forceIntegerInRange($parts[1], 0, 999);
		$parts[2] = t3lib_utility_Math::forceIntegerInRange($parts[2], 0, 999);

		switch ((string)$raise) {
			case 'main':
				$parts[0]++;
				$parts[1] = 0;
				$parts[2] = 0;
				break;
			case 'sub':
				$parts[1]++;
				$parts[2] = 0;
				break;
			case 'dev':
				$parts[2]++;
				break;
		}
		return $parts[0] . '.' . $parts[1] . '.' . $parts[2];
	}

}

?>