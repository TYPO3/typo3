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
        // Use fallback locale if 'C' is provided.
        if ($locale === 'C') {
            $locale = 'en-US';
        }
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

    /**
     * Locale-formatted strftime using IntlDateFormatter (PHP 8.1 compatible)
     * This provides a cross-platform alternative to strftime() for when it will be removed from PHP.
     * Note that output can be slightly different between libc sprintf and this function as it is using ICU.
     *
     * Original author BohwaZ <https://bohwaz.net/>
     * Adapted from https://github.com/alphp/strftime
     * MIT licensed
     */
    public function strftime(string $format, int|string|\DateTimeInterface|null $timestamp, string|Locale|null $locale = null, $useUtcTimeZone = false): string
    {
        if (!($timestamp instanceof \DateTimeInterface)) {
            $timestamp = is_int($timestamp) ? '@' . $timestamp : (string)$timestamp;
            try {
                $timestamp = new \DateTime($timestamp);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.', 1679091446, $e);
            }
            $timestamp->setTimezone(new \DateTimeZone($useUtcTimeZone ? 'UTC' : date_default_timezone_get()));
        }

        if (empty($locale)) {
            // get current locale
            $locale = (string)setlocale(LC_TIME, '0');
        } else {
            $locale = (string)$locale;
        }
        // Use fallback locale if 'C' is provided.
        if ($locale === 'C') {
            $locale = 'en-US';
        }
        // remove trailing part not supported by ext-intl locale
        $locale = preg_replace('/[^\w-].*$/', '', $locale);

        $intl_formats = [
            '%a' => 'EEE',	// An abbreviated textual representation of the day	Sun through Sat
            '%A' => 'EEEE',	// A full textual representation of the day	Sunday through Saturday
            '%b' => 'MMM',	// Abbreviated month name, based on the locale	Jan through Dec
            '%B' => 'MMMM',	// Full month name, based on the locale	January through December
            '%h' => 'MMM',	// Abbreviated month name, based on the locale (an alias of %b)	Jan through Dec
        ];

        $intl_formatter = function (\DateTimeInterface $timestamp, string $format) use ($intl_formats, $locale): string {
            $tz = $timestamp->getTimezone();
            $date_type = \IntlDateFormatter::FULL;
            $time_type = \IntlDateFormatter::FULL;
            $pattern = '';

            switch ($format) {
                // %c = Preferred date and time stamp based on locale
                // Example: Tue Feb 5 00:45:10 2009 for February 5, 2009 at 12:45:10 AM
                case '%c':
                    $date_type = \IntlDateFormatter::LONG;
                    $time_type = \IntlDateFormatter::SHORT;
                    break;

                    // %x = Preferred date representation based on locale, without the time
                    // Example: 02/05/09 for February 5, 2009
                case '%x':
                    $date_type = \IntlDateFormatter::SHORT;
                    $time_type = \IntlDateFormatter::NONE;
                    break;

                    // Localized time format
                case '%X':
                    $date_type = \IntlDateFormatter::NONE;
                    $time_type = \IntlDateFormatter::MEDIUM;
                    break;

                default:
                    $pattern = $intl_formats[$format];
            }

            // In October 1582, the Gregorian calendar replaced the Julian in much of Europe, and
            //  the 4th October was followed by the 15th October.
            // ICU (including IntlDateFormattter) interprets and formats dates based on this cutover.
            // Posix (including strftime) and timelib (including DateTimeImmutable) instead use
            //  a "proleptic Gregorian calendar" - they pretend the Gregorian calendar has existed forever.
            // This leads to the same instants in time, as expressed in Unix time, having different representations
            //  in formatted strings.
            // To adjust for this, a custom calendar can be supplied with a cutover date arbitrarily far in the past.
            $calendar = \IntlGregorianCalendar::createInstance();
            $calendar->setGregorianChange(PHP_INT_MIN);

            return (new \IntlDateFormatter($locale, $date_type, $time_type, $tz, $calendar, $pattern))->format($timestamp) ?: '';
        };

        // Same order as https://www.php.net/manual/en/function.strftime.php
        $translation_table = [
            // Day
            '%a' => $intl_formatter,
            '%A' => $intl_formatter,
            '%d' => 'd',
            '%e' => function (\DateTimeInterface $timestamp, string $_): string {
                return sprintf('% 2u', $timestamp->format('j'));
            },
            '%j' => function (\DateTimeInterface $timestamp, string $_): string {
                // Day number in year, 001 to 366
                return sprintf('%03d', (int)($timestamp->format('z')) + 1);
            },
            '%u' => 'N',
            '%w' => 'w',

            // Week
            '%U' => function (\DateTimeInterface $timestamp, string $_): string {
                // Number of weeks between date and first Sunday of year
                $day = new \DateTime(sprintf('%d-01 Sunday', $timestamp->format('Y')));
                return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
            },
            '%V' => 'W',
            '%W' => function (\DateTimeInterface $timestamp, string $_): string {
                // Number of weeks between date and first Monday of year
                $day = new \DateTime(sprintf('%d-01 Monday', $timestamp->format('Y')));
                return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
            },

            // Month
            '%b' => $intl_formatter,
            '%B' => $intl_formatter,
            '%h' => $intl_formatter,
            '%m' => 'm',

            // Year
            '%C' => function (\DateTimeInterface $timestamp, string $_): string {
                // Century (-1): 19 for 20th century
                return (string)floor($timestamp->format('Y') / 100);
            },
            '%g' => function (\DateTimeInterface $timestamp, string $_): string {
                return substr($timestamp->format('o'), -2);
            },
            '%G' => 'o',
            '%y' => 'y',
            '%Y' => 'Y',

            // Time
            '%H' => 'H',
            '%k' => function (\DateTimeInterface $timestamp, string $_): string {
                return sprintf('% 2u', $timestamp->format('G'));
            },
            '%I' => 'h',
            '%l' => function (\DateTimeInterface $timestamp, string $_): string {
                return sprintf('% 2u', $timestamp->format('g'));
            },
            '%M' => 'i',
            '%p' => 'A', // AM PM (this is reversed on purpose!)
            '%P' => 'a', // am pm
            '%r' => 'h:i:s A', // %I:%M:%S %p
            '%R' => 'H:i', // %H:%M
            '%S' => 's',
            '%T' => 'H:i:s', // %H:%M:%S
            '%X' => $intl_formatter, // Preferred time representation based on locale, without the date

            // Timezone
            '%z' => 'O',
            '%Z' => 'T',

            // Time and Date Stamps
            '%c' => $intl_formatter,
            '%D' => 'm/d/Y',
            '%F' => 'Y-m-d',
            '%s' => 'U',
            '%x' => $intl_formatter,
        ];

        $out = preg_replace_callback('/(?<!%)%([_#-]?)([a-zA-Z])/', function ($match) use ($translation_table, $timestamp) {
            $prefix = $match[1];
            $char = $match[2];
            $pattern = '%' . $char;
            if ($pattern == '%n') {
                return "\n";
            }
            if ($pattern == '%t') {
                return "\t";
            }

            if (!isset($translation_table[$pattern])) {
                throw new \InvalidArgumentException(sprintf('Format "%s" is unknown in time format', $pattern), 1679091475);
            }

            $replace = $translation_table[$pattern];

            if (is_string($replace)) {
                $result = $timestamp->format($replace);
            } else {
                $result = $replace($timestamp, $pattern);
            }

            return match ($prefix) {
                '_' => preg_replace('/\G0(?=.)/', ' ', $result),
                '#', '-' => preg_replace('/^0+(?=.)/', '', $result),
                default => $result,
            };
        }, $format);
        return str_replace('%%', '%', $out);
    }
}
