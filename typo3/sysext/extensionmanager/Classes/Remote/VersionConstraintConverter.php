<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extensionmanager\Remote;

use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * The TER REST API and FAIR documents send a version range as
 * ">=13.4.29 <=14.3.99", and "*" for any version, while Dependency reads the
 * ext_emconf.php notation "13.4.29-14.3.99".
 *
 * That notation only knows inclusive bounds. An exclusive bound is moved to
 * the next version number the integer representation of a version knows, so
 * "<15.0.0" becomes "14.999.999".
 *
 * @internal This class is a specific implementation and is not part of the Public TYPO3 API.
 */
final class VersionConstraintConverter
{
    private const BOUND_PATTERN = '/(>=|<=|>|<|=)\s*(\d+(?:\.\d+){0,2})/';

    /**
     * @throws UnsupportedVersionConstraintException
     */
    public static function toEmConfNotation(string $constraint): string
    {
        $constraint = trim($constraint);
        if ($constraint === '' || $constraint === '*') {
            return '';
        }
        if (preg_match('/^\d[\d.]*(-\d[\d.]*)?$/', $constraint)) {
            return $constraint;
        }
        if (!preg_match_all(self::BOUND_PATTERN, $constraint, $matches, PREG_SET_ORDER)
            || trim((string)preg_replace(self::BOUND_PATTERN, '', $constraint), ' ,') !== ''
        ) {
            throw self::unsupported($constraint);
        }
        $lowest = null;
        $highest = null;
        foreach ($matches as [, $operator, $versionNumber]) {
            $version = VersionNumberUtility::convertVersionNumberToInteger($versionNumber);
            [$lower, $upper] = match ($operator) {
                '>=' => [$version, null],
                '>' => [$version + 1, null],
                '<=' => [null, $version],
                '<' => [null, $version - 1],
                '=' => [$version, $version],
            };
            if (($lower !== null && $lowest !== null) || ($upper !== null && $highest !== null)) {
                throw self::unsupported($constraint);
            }
            $lowest ??= $lower;
            $highest ??= $upper;
        }
        return rtrim(self::toVersionNumber($lowest) . '-' . self::toVersionNumber($highest), '-');
    }

    private static function toVersionNumber(?int $version): string
    {
        if ($version === null) {
            return '';
        }
        return intdiv($version, 1000000) . '.' . intdiv($version % 1000000, 1000) . '.' . $version % 1000;
    }

    private static function unsupported(string $constraint): UnsupportedVersionConstraintException
    {
        return new UnsupportedVersionConstraintException(
            'The version constraint "' . $constraint . '" cannot be expressed as a range of the ext_emconf.php notation.',
            1794248547
        );
    }
}
