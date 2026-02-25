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

namespace TYPO3\CMS\Form\Utility;

/**
 * Central definition of date validation patterns.
 *
 * @internal
 */
final class DateRangeValidatorPatterns
{
    public const RFC3339_FULL_DATE = '\d{4}-(0[1-9]|1[012])-(0[1-9]|[12]\d|3[01])';

    public const RFC3339_FULL_DATE_PCRE = '/^' . self::RFC3339_FULL_DATE . '$/';

    /**
     * Check whether a string is a relative date expression.
     *
     * A relative date is defined as any non-empty string that does NOT match
     * an RFC 3339 full-date (YYYY-MM-DD). Actual validity is determined by
     * PHP's DateTime parser (strtotime).
     */
    public static function isRelativeDateExpression(string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }
        // If it matches an absolute date, it's not a relative expression
        if (preg_match(self::RFC3339_FULL_DATE_PCRE, $trimmed)) {
            return false;
        }
        return true;
    }

    /**
     * Try to parse a relative date expression using PHP's DateTime parser.
     * Returns the resulting DateTime or null if the expression is invalid.
     */
    public static function parseRelativeDateExpression(string $value): ?\DateTime
    {
        if (!self::isRelativeDateExpression($value)) {
            return null;
        }
        try {
            return new \DateTime(trim($value));
        } catch (\Exception) {
            return null;
        }
    }
}
