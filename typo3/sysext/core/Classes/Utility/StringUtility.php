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

namespace TYPO3\CMS\Core\Utility;

/**
 * Class with helper functions for string handling
 */
class StringUtility
{
    /**
     * Casts applicable types (string, bool, finite numeric) to string.
     *
     * Any other type will be replaced by the `$default` value.
     */
    public static function cast(mixed $value, ?string $default = null): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_bool($value) || (is_numeric($value) && is_finite($value))) {
            return (string)$value;
        }

        return $default;
    }

    /**
     * Keeps only string types (filters out non-strings).
     *
     * Any other non-string type will be replaced by the `$default` value.
     */
    public static function filter(mixed $value, ?string $default = null): ?string
    {
        return is_string($value) ? $value : $default;
    }

    /**
     * This function generates a unique id by using the more entropy parameter.
     * Furthermore, the dots are removed so the id can be used inside HTML attributes e.g. id.
     */
    public static function getUniqueId(string $prefix = ''): string
    {
        $uniqueId = uniqid($prefix, true);
        return str_replace('.', '', $uniqueId);
    }

    /**
     * Escape a CSS selector to be used for DOM queries
     *
     * This method takes care to escape any CSS selector meta character.
     * The result may be used to query the DOM like $('#' + escapedSelector)
     */
    public static function escapeCssSelector(string $selector): string
    {
        return preg_replace('/([#:.\\[\\],=@])/', '\\\\$1', $selector);
    }

    /**
     * Removes the Byte Order Mark (BOM) from the input string.
     *
     * This method supports UTF-8 encoded strings only!
     */
    public static function removeByteOrderMark(string $input): string
    {
        if (str_starts_with($input, "\xef\xbb\xbf")) {
            $input = substr($input, 3);
        }

        return $input;
    }

    /**
     * Matching two strings against each other, supporting a "*" wildcard (match many) or a "?" wildcard (match one= or (if wrapped in "/") PCRE regular expressions
     *
     * @param string $haystack The string in which to find $needle.
     * @param string $needle The string to find in $haystack
     * @return bool Returns TRUE if $needle matches or is found in (according to wildcards) $haystack. E.g. if $haystack is "Netscape 6.5" and $needle is "Net*" or "Net*ape" then it returns TRUE.
     */
    public static function searchStringWildcard(string $haystack, string $needle): bool
    {
        $result = false;
        if ($haystack === $needle) {
            $result = true;
        } elseif ($needle) {
            if (preg_match('/^\\/.+\\/$/', $needle)) {
                // Regular expression, only "//" is allowed as delimiter
                $regex = $needle;
            } else {
                $needle = str_replace(['*', '?'], ['%%%MANY%%%', '%%%ONE%%%'], $needle);
                $regex = '/^' . preg_quote($needle, '/') . '$/';
                // Replace the marker with .* to match anything (wildcard)
                $regex = str_replace(['%%%MANY%%%', '%%%ONE%%%'], ['.*', '.'], $regex);
            }
            $result = (bool)preg_match($regex, $haystack);
        }
        return $result;
    }

    /**
     * Takes a comma-separated list and removes all duplicates.
     * If a value in the list is trim(empty), the value is ignored.
     *
     * @param string $list A comma-separated list of values.
     * @return string Returns the list without any duplicates of values, space around values are trimmed.
     */
    public static function uniqueList(string $list): string
    {
        return implode(',', array_unique(GeneralUtility::trimExplode(',', $list, true)));
    }

    /**
     * Works the same as str_pad() except that it correctly handles strings with multibyte characters
     * and takes an additional optional argument $encoding.
     */
    public static function multibyteStringPad(string $string, int $length, string $pad_string = ' ', int $pad_type = STR_PAD_RIGHT, string $encoding = 'UTF-8'): string
    {
        $len = mb_strlen($string, $encoding);
        $pad_string_len = mb_strlen($pad_string, $encoding);
        if ($len >= $length || $pad_string_len === 0) {
            return $string;
        }

        switch ($pad_type) {
            case STR_PAD_RIGHT:
                $string .= str_repeat($pad_string, (int)(($length - $len) / $pad_string_len));
                $string .= mb_substr($pad_string, 0, ($length - $len) % $pad_string_len);
                return $string;

            case STR_PAD_LEFT:
                $leftPad = str_repeat($pad_string, (int)(($length - $len) / $pad_string_len));
                $leftPad .= mb_substr($pad_string, 0, ($length - $len) % $pad_string_len);
                return $leftPad . $string;

            case STR_PAD_BOTH:
                $leftPadCount = (int)(($length - $len) / 2);
                $len += $leftPadCount;
                $padded = ((int)($leftPadCount / $pad_string_len)) * $pad_string_len;
                $leftPad = str_repeat($pad_string, (int)($leftPadCount / $pad_string_len));
                $leftPad .= mb_substr($pad_string, 0, $leftPadCount - $padded);
                $string = $leftPad . $string . str_repeat($pad_string, (int)(($length - $len) / $pad_string_len));
                $string .= mb_substr($pad_string, 0, ($length - $len) % $pad_string_len);
                return $string;
        }
        return $string;
    }

    /**
     * Returns base64 encoded value with a URL and filename safe alphabet
     * according to https://tools.ietf.org/html/rfc4648#section-5
     *
     * The difference to classic base64 is, that the result
     * alphabet is adjusted like shown below, padding (`=`)
     * is stripped completely:
     *  + position #62: `+` -> `-` (minus)
     *  + position #63: `/` -> `_` (underscore)
     *
     * @param string $value raw value
     * @return string base64url encoded string
     */
    public static function base64urlEncode(string $value): string
    {
        return strtr(base64_encode($value), ['+' => '-', '/' => '_', '=' => '']);
    }

    /**
     * Returns base64 decoded value with a URL and filename safe alphabet
     * according to https://tools.ietf.org/html/rfc4648#section-5
     *
     * The difference to classic base64 is, that the result
     * alphabet is adjusted like shown below, padding (`=`)
     * is stripped completely:
     *  + position #62: `-` (minus)      -> `+`
     *  + position #63: `_` (underscore) -> `/`
     *
     * @param string $value base64url decoded string
     * @return string raw value
     */
    public static function base64urlDecode(string $value): string
    {
        return base64_decode(strtr($value, ['-' => '+', '_' => '/']));
    }

    /**
     * Explodes a string while respecting escape characters
     *
     * e.g.: delimiter: '.'; escapeCharacter: '\'; subject: 'new\.site.child'
     * result: [new.site, child]
     * @param string $delimiter
     * @param string $subject
     * @param string $escapeCharacter
     */
    public static function explodeEscaped(string $delimiter, string $subject, string $escapeCharacter = '\\'): array
    {
        if ($delimiter !== '') {
            $placeholder = '\\0\\0\\0_esc';
            $subjectEscaped = str_replace($escapeCharacter . $delimiter, $placeholder, $subject);
            $escapeParts = explode($delimiter, $subjectEscaped);
            foreach ($escapeParts as &$part) {
                $part = str_replace($placeholder, $delimiter, $part);
            }
            return $escapeParts;
        }
        return [$subject];
    }
}
