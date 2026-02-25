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

    /**
     * Covers the subset of PHP's strtotime() syntax explicitly supported by the
     * DateRange validator. Only explicit expressions are accepted to prevent
     * ambiguous strings from being silently misinterpreted.
     */
    public const RELATIVE_DATE = '(today|now|yesterday|tomorrow|[+-]?\s*\d+\s+(year|month|week|day|hour|minute|second)s?(\s+ago)?(\s*[+-]?\s*\d+\s+(year|month|week|day|hour|minute|second)s?(\s+ago))*)';

    public const RFC3339_FULL_DATE_PCRE = '/^' . self::RFC3339_FULL_DATE . '$/';

    public const RELATIVE_DATE_PCRE = '/^' . self::RELATIVE_DATE . '$/i';
}
