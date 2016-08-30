<?php
namespace TYPO3\CMS\Core\Utility;

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
                $version .= str_pad((int)$versionParts[$i], 3, '0', STR_PAD_LEFT);
            } else {
                $version .= '000';
            }
        }
        return (int)$version;
    }

    /**
     * Returns the three part version number (string) from an integer, eg 4012003 -> '4.12.3'
     *
     * @param int $versionInteger Integer representation of version number
     * @return string Version number as format x.x.x
     * @throws \InvalidArgumentException if $versionInteger is not an integer
     */
    public static function convertIntegerToVersionNumber($versionInteger)
    {
        if (!is_int($versionInteger)) {
            throw new \InvalidArgumentException(\TYPO3\CMS\Core\Utility\VersionNumberUtility::class . '::convertIntegerToVersionNumber() supports an integer argument only!', 1334072223);
        }
        $versionString = str_pad($versionInteger, 9, '0', STR_PAD_LEFT);
        $parts = [
            substr($versionString, 0, 3),
            substr($versionString, 3, 3),
            substr($versionString, 6, 3)
        ];
        return (int)$parts[0] . '.' . (int)$parts[1] . '.' . (int)$parts[2];
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
    public static function splitVersionRange($version)
    {
        $versionRange = [];
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
    public static function convertVersionsStringToVersionNumbers($versionsString)
    {
        $versions = GeneralUtility::trimExplode('-', $versionsString);
        $versionsCount = count($versions);
        for ($i = 0; $i < $versionsCount; $i++) {
            $cleanedVersion = GeneralUtility::trimExplode('.', $versions[$i]);
            $cleanedVersionCount = count($cleanedVersion);
            for ($j = 0; $j < $cleanedVersionCount; $j++) {
                $cleanedVersion[$j] = MathUtility::forceIntegerInRange($cleanedVersion[$j], 0, 999);
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

    /**
     * Method to raise a version number
     *
     * @param string $raise one of "main", "sub", "dev" - the version part to raise by one
     * @param string $version (like 4.1.20)
     * @return string
     * @throws \TYPO3\CMS\Core\Exception
     */
    public static function raiseVersionNumber($raise, $version)
    {
        if (!in_array($raise, ['main', 'sub', 'dev'])) {
            throw new \TYPO3\CMS\Core\Exception('RaiseVersionNumber expects one of "main", "sub" or "dev".', 1342639555);
        }
        $parts = GeneralUtility::intExplode('.', $version . '..');
        $parts[0] = MathUtility::forceIntegerInRange($parts[0], 0, 999);
        $parts[1] = MathUtility::forceIntegerInRange($parts[1], 0, 999);
        $parts[2] = MathUtility::forceIntegerInRange($parts[2], 0, 999);
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
