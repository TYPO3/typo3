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

namespace TYPO3\CMS\Core\Localization;

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Wrapper for dealing with ICU-based (php-intl) date formatting
 * see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
 */
class DateFormatter
{
    /**
     * Formats any given input ($date) into a localized, formatted result
     *
     * @param mixed $date could be a DateTime object, a string or a number (Unix Timestamp)
     * @param string|int $format the pattern, as defined by the ICU - see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
     * @param string|Locale $locale the locale to be used, e.g. "nl-NL"
     * @return string the formatted output, such as "Tuesday at 12:40:20"
     */
    public function format(mixed $date, string|int $format, string|Locale $locale): string
    {
        $locale = (string)$locale;
        if (is_int($format) || MathUtility::canBeInterpretedAsInteger($format)) {
            $dateFormatter = new \IntlDateFormatter($locale, (int)$format, (int)$format);
        } else {
            $dateFormatter = match (strtoupper($format)) {
                'FULL' => new \IntlDateFormatter($locale, \IntlDateFormatter::FULL, \IntlDateFormatter::FULL),
                'FULLDATE' => new \IntlDateFormatter($locale, \IntlDateFormatter::FULL, \IntlDateFormatter::NONE),
                'FULLTIME' => new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::FULL),
                'LONG' => new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, \IntlDateFormatter::LONG),
                'LONGDATE' => new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, \IntlDateFormatter::NONE),
                'LONGTIME' => new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::LONG),
                'MEDIUM' => new \IntlDateFormatter($locale, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM),
                'MEDIUMDATE' => new \IntlDateFormatter($locale, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE),
                'MEDIUMTIME' => new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::MEDIUM),
                'SHORT' => new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT),
                'SHORTDATE' => new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE),
                'SHORTTIME' => new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT),
                // Use a custom pattern
                default => new \IntlDateFormatter($locale, \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, null, null, $format),
            };
        }
        return $dateFormatter->format($date) ?: '';
    }
}
