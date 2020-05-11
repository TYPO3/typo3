<?php

/*
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

namespace TYPO3\CMS\Core\Utility;

use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Class with helper functions for version number handling
 */
class VersionNumberUtility
{
    /**
     * Returns an integer from a three part version number, eg '4.12.3' -> 4012003
     *
     * @param string $versionNumber Version number on format x.x.x
     * @return int Integer version of version number (where each part can count to 999)
     */
    public static function convertVersionNumberToInteger($versionNumber)
    {
        $versionParts = explode('.', $versionNumber);
        $version = $versionParts[0];
        for ($i = 1; $i < 3; $i++) {
            if (!empty($versionParts[$i])) {
                $version .= str_pad((string)(int)$versionParts[$i], 3, '0', STR_PAD_LEFT);
            } else {
                $version .= '000';
            }
        }
        return (int)$version;
    }

    /**
     * Removes -dev -alpha -beta -RC states (also without '-' prefix) from a version number
     * and replaces them by .0 and normalizes to a three part version number
     *
     * @return string
     */
    public static function getNumericTypo3Version()
    {
        $t3version = static::getCurrentTypo3Version();
        $t3version = preg_replace('/-?(dev|alpha|beta|RC).*$/', '', $t3version);
        $parts = GeneralUtility::intExplode('.', $t3version . '..');
        $t3version = MathUtility::forceIntegerInRange($parts[0], 0, 999) . '.' .
            MathUtility::forceIntegerInRange($parts[1], 0, 999) . '.' .
            MathUtility::forceIntegerInRange($parts[2], 0, 999);
        return $t3version;
    }

    /**
     * Wrapper function for TYPO3_version constant to make functions using
     * the constant unit testable
     *
     * @return string
     */
    public static function getCurrentTypo3Version()
    {
        return (string)GeneralUtility::makeInstance(Typo3Version::class);
    }

    /**
     * This function converts version range strings (like '4.2.0-4.4.99') to an array
     * (like array('4.2.0', '4.4.99'). It also forces each version part to be between
     * 0 and 999
     *
     * @param string $versionsString
     * @return array
     */
    public static function convertVersionsStringToVersionNumbers($versionsString)
    {
        $versions = GeneralUtility::trimExplode('-', $versionsString);
        $versionsCount = count($versions);
        for ($i = 0; $i < $versionsCount; $i++) {
            $cleanedVersion = GeneralUtility::trimExplode('.', $versions[$i]);
            $cleanedVersionCount = count($cleanedVersion);
            for ($j = 0; $j < $cleanedVersionCount; $j++) {
                $cleanedVersion[$j] = MathUtility::forceIntegerInRange((int)$cleanedVersion[$j], 0, 999);
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
    public static function convertVersionStringToArray($version)
    {
        $parts = GeneralUtility::intExplode('.', $version . '..');
        $parts[0] = MathUtility::forceIntegerInRange($parts[0], 0, 999);
        $parts[1] = MathUtility::forceIntegerInRange($parts[1], 0, 999);
        $parts[2] = MathUtility::forceIntegerInRange($parts[2], 0, 999);
        $result = [];
        $result['version'] = $parts[0] . '.' . $parts[1] . '.' . $parts[2];
        $result['version_int'] = (int)($parts[0] * 1000000 + $parts[1] * 1000 + $parts[2]);
        $result['version_main'] = $parts[0];
        $result['version_sub'] = $parts[1];
        $result['version_dev'] = $parts[2];
        return $result;
    }
}
