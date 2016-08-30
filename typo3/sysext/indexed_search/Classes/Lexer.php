<?php
namespace TYPO3\CMS\IndexedSearch;

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
 * Lexer class for indexed_search
 * A lexer splits the text into words
 */
class Lexer
{
    /**
     * Debugging options:
     *
     * @var bool
     */
    public $debug = false;

    /**
     * If set, the debugString is filled with HTML output highlighting search / non-search words (for backend display)
     *
     * @var string
     */
    public $debugString = '';

    /**
     * Charset class object
     *
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    public $csObj;

    /**
     * Configuration of the lexer:
     *
     * @var array
     */
    public $lexerConf = [
        //Characters: . - _ : / '
        'printjoins' => [46, 45, 95, 58, 47, 39],
        'casesensitive' => false,
        // Set, if case sensitive indexing is wanted.
        'removeChars' => [45]
    ];

    /**
     * Constructor: Initializes the charset class
     *
     */
    public function __construct()
    {
        $this->csObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
    }

    /**
     * Splitting string into words.
     * Used for indexing, can also be used to find words in query.
     *
     * @param string String with UTF-8 content to process.
     * @return array Array of words in utf-8
     */
    public function split2Words($wordString)
    {
        // Reset debug string:
        $this->debugString = '';
        // Then convert the string to lowercase:
        if (!$this->lexerConf['casesensitive']) {
            $wordString = $this->csObj->conv_case('utf-8', $wordString, 'toLower');
        }
        // Now, splitting words:
        $len = 0;
        $start = 0;
        $pos = 0;
        $words = [];
        $this->debugString = '';
        while (1) {
            list($start, $len) = $this->get_word($wordString, $pos);
            if ($len) {
                $this->addWords($words, $wordString, $start, $len);
                if ($this->debug) {
                    $this->debugString .= '<span style="color:red">' . htmlspecialchars(substr($wordString, $pos, ($start - $pos))) . '</span>' . htmlspecialchars(substr($wordString, $start, $len));
                }
                $pos = $start + $len;
            } else {
                break;
            }
        }
        return $words;
    }

    /**********************************
     *
     * Helper functions
     *
     ********************************/
    /**
     * Add word to word-array
     * This function should be used to make sure CJK sequences are split up in the right way
     *
     * @param array $words Array of accumulated words
     * @param string $wordString Complete Input string from where to extract word
     * @param int $start Start position of word in input string
     * @param int $len The Length of the word string from start position
     * @return void
     */
    public function addWords(&$words, &$wordString, $start, $len)
    {
        // Get word out of string:
        $theWord = substr($wordString, $start, $len);
        // Get next chars unicode number and find type:
        $bc = 0;
        $cp = $this->utf8_ord($theWord, $bc);
        list($cType) = $this->charType($cp);
        // If string is a CJK sequence we follow this algorithm:
        /*
        DESCRIPTION OF (CJK) ALGORITHMContinuous letters and numbers make up words. Spaces and symbols
        separate letters and numbers into words. This is sufficient for
        all western text.CJK doesn't use spaces or separators to separate words, so the only
        way to really find out what constitutes a word would be to have a
        dictionary and advanced heuristics. Instead, we form pairs from
        consecutive characters, in such a way that searches will find only
        characters that appear more-or-less the right sequence. For example:ABCDE => AB BC CD DEThis works okay since both the index and the search query is split
        in the same manner, and since the set of characters is huge so the
        extra matches are not significant.(Hint taken from ZOPEs chinese user group)[Kasper: As far as I can see this will only work well with or-searches!]
         */
        if ($cType == 'cjk') {
            // Find total string length:
            $strlen = $this->csObj->utf8_strlen($theWord);
            // Traverse string length and add words as pairs of two chars:
            for ($a = 0; $a < $strlen; $a++) {
                if ($strlen == 1 || $a < $strlen - 1) {
                    $words[] = $this->csObj->utf8_substr($theWord, $a, 2);
                }
            }
        } else {
            // Normal "single-byte" chars:
            // Remove chars:
            foreach ($this->lexerConf['removeChars'] as $skipJoin) {
                $theWord = str_replace($this->csObj->UnumberToChar($skipJoin), '', $theWord);
            }
            // Add word:
            $words[] = $theWord;
        }
    }

    /**
     * Get the first word in a given utf-8 string (initial non-letters will be skipped)
     *
     * @param string $str Input string (reference)
     * @param int $pos Starting position in input string
     * @return array 0: start, 1: len or FALSE if no word has been found
     */
    public function get_word(&$str, $pos = 0)
    {
        $len = 0;
        // If return is TRUE, a word was found starting at this position, so returning position and length:
        if ($this->utf8_is_letter($str, $len, $pos)) {
            return [$pos, $len];
        }
        // If the return value was FALSE it means a sequence of non-word chars were found (or blank string) - so we will start another search for the word:
        $pos += $len;
        if ($str[$pos] == '') {
            // Check end of string before looking for word of course.
            return false;
        }
        $this->utf8_is_letter($str, $len, $pos);
        return [$pos, $len];
    }

    /**
     * See if a character is a letter (or a string of letters or non-letters).
     *
     * @param string $str Input string (reference)
     * @param int $len Byte-length of character sequence (reference, return value)
     * @param int $pos Starting position in input string
     * @return bool letter (or word) found
     */
    public function utf8_is_letter(&$str, &$len, $pos = 0)
    {
        $len = 0;
        $bc = 0;
        $cp = 0;
        $printJoinLgd = 0;
        $cType = ($cType_prev = false);
        // Letter type
        $letter = true;
        // looking for a letter?
        if ($str[$pos] == '') {
            // Return FALSE on end-of-string at this stage
            return false;
        }
        while (1) {
            // If characters has been obtained we will know whether the string starts as a sequence of letters or not:
            if ($len) {
                if ($letter) {
                    // We are in a sequence of words
                    if (
                        !$cType
                        || $cType_prev == 'cjk' && ($cType === 'num' || $cType === 'alpha')
                        || $cType == 'cjk' && ($cType_prev === 'num' || $cType_prev === 'alpha')
                    ) {
                        // Check if the non-letter char is NOT a print-join char because then it signifies the end of the word.
                        if (!in_array($cp, $this->lexerConf['printjoins'])) {
                            // If a printjoin start length has been recorded, set that back now so the length is right (filtering out multiple end chars)
                            if ($printJoinLgd) {
                                $len = $printJoinLgd;
                            }
                            return true;
                        } else {
                            // If a printJoin char is found, record the length if it has not been recorded already:
                            if (!$printJoinLgd) {
                                $printJoinLgd = $len;
                            }
                        }
                    } else {
                        // When a true letter is found, reset printJoinLgd counter:
                        $printJoinLgd = 0;
                    }
                } elseif (!$letter && $cType) {
                    // end of non-word reached
                    return false;
                }
            }
            $len += $bc;
            // add byte-length of last found character
            if ($str[$pos] == '') {
                // End of string; return status of string till now
                return $letter;
            }
            // Get next chars unicode number:
            $cp = $this->utf8_ord($str, $bc, $pos);
            $pos += $bc;
            // Determine the type:
            $cType_prev = $cType;
            list($cType) = $this->charType($cp);
            if ($cType) {
                continue;
            }
            // Setting letter to FALSE if the first char was not a letter!
            if (!$len) {
                $letter = false;
            }
        }
        return false;
    }

    /**
     * Determine the type of character
     *
     * @param int $cp Unicode number to evaluate
     * @return array Type of char; index-0: the main type: num, alpha or CJK (Chinese / Japanese / Korean)
     */
    public function charType($cp)
    {
        // Numeric?
        if ($cp >= 48 && $cp <= 57) {
            return ['num'];
        }
        // LOOKING for Alpha chars (Latin, Cyrillic, Greek, Hebrew and Arabic):
        if ($cp >= 65 && $cp <= 90 || $cp >= 97 && $cp <= 122 || $cp >= 192 && $cp <= 255 && $cp != 215 && $cp != 247 || $cp >= 256 && $cp < 640 || ($cp == 902 || $cp >= 904 && $cp < 1024) || ($cp >= 1024 && $cp < 1154 || $cp >= 1162 && $cp < 1328) || ($cp >= 1424 && $cp < 1456 || $cp >= 1488 && $cp < 1523) || ($cp >= 1569 && $cp <= 1624 || $cp >= 1646 && $cp <= 1747) || $cp >= 7680 && $cp < 8192) {
            return ['alpha'];
        }
        // Looking for CJK (Chinese / Japanese / Korean)
        // Ranges are not certain - deducted from the translation tables in typo3/sysext/core/Resources/Private/Charsets/csconvtbl/
        // Verified with http://www.unicode.org/charts/ (16/2) - may still not be complete.
        if ($cp >= 12352 && $cp <= 12543 || $cp >= 12592 && $cp <= 12687 || $cp >= 13312 && $cp <= 19903 || $cp >= 19968 && $cp <= 40879 || $cp >= 44032 && $cp <= 55215 || $cp >= 131072 && $cp <= 195103) {
            return ['cjk'];
        }
    }

    /**
     * Converts a UTF-8 multibyte character to a UNICODE codepoint
     *
     * @param string $str UTF-8 multibyte character string (reference)
     * @param int $len The length of the character (reference, return value)
     * @param int $pos Starting position in input string
     * @param bool $hex If set, then a hex. number is returned
     * @return int UNICODE codepoint
     */
    public function utf8_ord(&$str, &$len, $pos = 0, $hex = false)
    {
        $ord = ord($str[$pos]);
        $len = 1;
        if ($ord > 128) {
            for ($bc = -1, $mbs = $ord; $mbs & 128; $mbs = $mbs << 1) {
                // calculate number of extra bytes
                $bc++;
            }
            $len += $bc;
            $ord = $ord & (1 << 6 - $bc) - 1;
            // mask utf-8 lead-in bytes
            // "bring in" data bytes
            for ($i = $pos + 1; $bc; $bc--, $i++) {
                $ord = $ord << 6 | ord($str[$i]) & 63;
            }
        }
        return $hex ? 'x' . dechex($ord) : $ord;
    }
}
