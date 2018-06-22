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
 * Class with helper functions for string handling
 */
class StringUtility
{
    /**
     * Returns TRUE if $haystack begins with $needle.
     * The input string is not trimmed before and search is done case sensitive.
     *
     * @param string $haystack Full string to check
     * @param string $needle Reference string which must be found as the "first part" of the full string
     * @throws \InvalidArgumentException
     * @return bool TRUE if $needle was found to be equal to the first part of $haystack
     */
    public static function beginsWith($haystack, $needle)
    {
        // Sanitize $haystack and $needle
        if (is_object($haystack) || $haystack === null || (string)$haystack != $haystack) {
            throw new \InvalidArgumentException(
                '$haystack can not be interpreted as string',
                1347135546
            );
        }
        if (is_object($needle) || (string)$needle != $needle || strlen($needle) < 1) {
            throw new \InvalidArgumentException(
                '$needle can not be interpreted as string or has zero length',
                1347135547
            );
        }
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        return $needle !== '' && strpos($haystack, $needle) === 0;
    }

    /**
     * Returns TRUE if $haystack ends with $needle.
     * The input string is not trimmed before and search is done case sensitive.
     *
     * @param string $haystack Full string to check
     * @param string $needle Reference string which must be found as the "last part" of the full string
     * @throws \InvalidArgumentException
     * @return bool TRUE if $needle was found to be equal to the last part of $haystack
     */
    public static function endsWith($haystack, $needle)
    {
        // Sanitize $haystack and $needle
        if (is_object($haystack) || $haystack === null || (string)$haystack != $haystack) {
            throw new \InvalidArgumentException(
                '$haystack can not be interpreted as string',
                1347135544
            );
        }
        if (is_object($needle) || (string)$needle != $needle || strlen($needle) < 1) {
            throw new \InvalidArgumentException(
                '$needle can not be interpreted as string or has no length',
                1347135545
            );
        }
        $haystackLength = strlen($haystack);
        $needleLength = strlen($needle);
        if (!$haystackLength || $needleLength > $haystackLength) {
            return false;
        }
        $position = strrpos((string)$haystack, (string)$needle);
        return $position !== false && $position === $haystackLength - $needleLength;
    }

    /**
     * This function generates a unique id by using the more entropy parameter.
     * Furthermore the dots are removed so the id can be used inside HTML attributes e.g. id.
     *
     * @param string $prefix
     * @return string
     */
    public static function getUniqueId($prefix = '')
    {
        $uniqueId = uniqid($prefix, true);
        return str_replace('.', '', $uniqueId);
    }

    /**
     * Escape a CSS selector to be used for DOM queries
     *
     * This method takes care to escape any CSS selector meta character.
     * The result may be used to query the DOM like $('#' + escapedSelector)
     *
     * @param string $selector
     * @return string
     */
    public static function escapeCssSelector(string $selector): string
    {
        return preg_replace('/([#:.\\[\\],=@])/', '\\\\$1', $selector);
    }

    /**
     * Removes the Byte Order Mark (BOM) from the input string. This method supports UTF-8 encoded strings only!
     *
     * @param string $input
     * @return string
     */
    public static function removeByteOrderMark(string $input): string
    {
        if (strpos($input, "\xef\xbb\xbf") === 0) {
            $input = substr($input, 3);
        }

        return $input;
    }
}
