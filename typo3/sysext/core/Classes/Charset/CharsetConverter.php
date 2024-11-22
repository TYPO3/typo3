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

namespace TYPO3\CMS\Core\Charset;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for conversion between charsets
 */
class CharsetConverter implements SingletonInterface
{
    /**
     * Fallback character for chars with no equivalent.
     */
    protected const FALLBACK_CHAR = '?';

    /**
     * An array where charset-to-ASCII mappings are stored (cached)
     */
    protected array $toASCII = [];

    /**
     * Converts all chars in the input UTF-8 string into integer numbers returned in an array.
     * All HTML entities (like &amp; or &pound; or &#123; or &#x3f5d;) will be detected as characters.
     * Also, instead of integer numbers the real UTF-8 char is returned.
     *
     * @param string $str Input string, UTF-8
     * @return array Output array with the char numbers
     */
    public function utf8_to_numberarray(string $str): array
    {
        // Entities must be registered as well
        $str = html_entity_decode($str, ENT_COMPAT, 'utf-8');

        // Do conversion:
        $strLen = strlen($str);
        $outArr = [];
        // Traverse each char in UTF-8 string.
        for ($a = 0; $a < $strLen; $a++) {
            $chr = substr($str, $a, 1);
            $ord = ord($chr);
            // This means multibyte! (first byte!)
            if ($ord > 127) {
                // Since the first byte must have the 7th bit set we check that. Otherwise we might be in the middle of a byte sequence.
                if ($ord & 64) {
                    // Add first byte
                    $buf = $chr;
                    // For each byte in multibyte string...
                    for ($b = 0; $b < 8; $b++) {
                        // Shift it left and ...
                        $ord = $ord << 1;
                        // ... and with 8th bit - if that is set, then there are still bytes in sequence.
                        if ($ord & 128) {
                            $a++;
                            // ... and add the next char.
                            $buf .= substr($str, $a, 1);
                        } else {
                            break;
                        }
                    }
                    $outArr[] = $buf;
                } else {
                    $outArr[] = self::FALLBACK_CHAR;
                }
            } else {
                $outArr[] = chr($ord);
            }
        }
        return $outArr;
    }

    /**
     * Converts a UNICODE number to a UTF-8 multibyte character
     * Algorithm based on script found at From: http://czyborra.com/utf/
     * Unit-tested by Kasper
     *
     * The binary representation of the character's integer value is thus simply spread across the bytes
     * and the number of high bits set in the lead byte announces the number of bytes in the multibyte sequence:
     *
     * ```
     * bytes | bits | representation
     *     1 |    7 | 0vvvvvvv
     *     2 |   11 | 110vvvvv 10vvvvvv
     *     3 |   16 | 1110vvvv 10vvvvvv 10vvvvvv
     *     4 |   21 | 11110vvv 10vvvvvv 10vvvvvv 10vvvvvv
     *     5 |   26 | 111110vv 10vvvvvv 10vvvvvv 10vvvvvv 10vvvvvv
     *     6 |   31 | 1111110v 10vvvvvv 10vvvvvv 10vvvvvv 10vvvvvv 10vvvvvv
     *
     * ```
     *
     * @param int $unicodeInteger UNICODE integer
     * @return string UTF-8 multibyte character string
     * @see utf8CharToUnumber()
     */
    public function UnumberToChar($unicodeInteger)
    {
        $str = '';
        if ($unicodeInteger < 128) {
            $str .= chr($unicodeInteger);
        } elseif ($unicodeInteger < 2048) {
            $str .= chr(192 | $unicodeInteger >> 6);
            $str .= chr(128 | $unicodeInteger & 63);
        } elseif ($unicodeInteger < 65536) {
            $str .= chr(224 | $unicodeInteger >> 12);
            $str .= chr(128 | $unicodeInteger >> 6 & 63);
            $str .= chr(128 | $unicodeInteger & 63);
        } elseif ($unicodeInteger < 2097152) {
            $str .= chr(240 | $unicodeInteger >> 18);
            $str .= chr(128 | $unicodeInteger >> 12 & 63);
            $str .= chr(128 | $unicodeInteger >> 6 & 63);
            $str .= chr(128 | $unicodeInteger & 63);
        } elseif ($unicodeInteger < 67108864) {
            $str .= chr(248 | $unicodeInteger >> 24);
            $str .= chr(128 | $unicodeInteger >> 18 & 63);
            $str .= chr(128 | $unicodeInteger >> 12 & 63);
            $str .= chr(128 | $unicodeInteger >> 6 & 63);
            $str .= chr(128 | $unicodeInteger & 63);
        } elseif ($unicodeInteger < 2147483648) {
            $str .= chr(252 | $unicodeInteger >> 30);
            $str .= chr(128 | $unicodeInteger >> 24 & 63);
            $str .= chr(128 | $unicodeInteger >> 18 & 63);
            $str .= chr(128 | $unicodeInteger >> 12 & 63);
            $str .= chr(128 | $unicodeInteger >> 6 & 63);
            $str .= chr(128 | $unicodeInteger & 63);
        } else {
            // Cannot express a 32-bit character in UTF-8
            $str .= self::FALLBACK_CHAR;
        }
        return $str;
    }

    /**
     * Converts a UTF-8 Multibyte character to a UNICODE number
     * Unit-tested by Kasper
     *
     * @param string $str UTF-8 multibyte character string
     * @param bool $hex If set, then a hex. number is returned.
     * @return int UNICODE integer
     * @see UnumberToChar()
     */
    public function utf8CharToUnumber(string $str, bool $hex = false)
    {
        // First char
        $ord = ord($str[0]);
        // This verifies that it IS a multibyte string
        if (($ord & 192) === 192) {
            $binBuf = '';
            $b = 0;
            // For each byte in multibyte string...
            for (; $b < 8; $b++) {
                // Shift it left and ...
                $ord = $ord << 1;
                // ... and with 8th bit - if that is set, then there are still bytes in sequence.
                if ($ord & 128) {
                    $binBuf .= substr('00000000' . decbin(ord(substr($str, $b + 1, 1))), -6);
                } else {
                    break;
                }
            }
            $binBuf = substr('00000000' . decbin(ord($str[0])), -(6 - $b)) . $binBuf;
            $int = bindec($binBuf);
        } else {
            $int = $ord;
        }
        return $hex ? 'x' . dechex((int)$int) : $int;
    }

    /**
     * This function initializes all UTF-8 character data tables.
     *
     * PLEASE SEE: http://www.unicode.org/Public/UNIDATA/
     *
     * @return bool Returns FALSE on error, TRUE value on success
     */
    protected function initUnicodeData(): bool
    {
        // Only process if the tables are not yet loaded
        if (isset($this->toASCII['utf-8']) && is_array($this->toASCII['utf-8'])) {
            return true;
        }
        // Cache file
        $cacheFileASCII = Environment::getVarPath() . '/charset/csascii_utf-8.tbl';
        // Use cached version if possible
        if (@is_file($cacheFileASCII)) {
            $this->toASCII['utf-8'] = unserialize((string)file_get_contents($cacheFileASCII), ['allowed_classes' => false]);
            return true;
        }
        // Process main Unicode data file
        $unicodeDataFile = ExtensionManagementUtility::extPath('core') . 'Resources/Private/Charsets/unidata/UnicodeData.txt';
        if (!(GeneralUtility::validPathStr($unicodeDataFile) && @is_file($unicodeDataFile))) {
            return false;
        }
        $fh = fopen($unicodeDataFile, 'rb');
        if (!$fh) {
            return false;
        }
        // Array of temp. decompositions
        $decomposition = [];
        // Array of chars that are marks (eg. composing accents)
        $mark = [];
        // Array of chars that are numbers (eg. digits)
        $number = [];
        // Array of chars to be omitted (eg. Russian hard sign)
        $omit = [];
        while (!feof($fh)) {
            $line = (string)fgets($fh, 4096);
            // Has a lot of info
            [$char, $name, $cat, , , $decomp, , , $num] = explode(';', rtrim($line));
            $ord = hexdec($char);
            if ($ord > 65535) {
                // Only process the BMP
                break;
            }
            switch ($cat[0]) {
                case 'M':
                    // mark (accent, umlaut, ...)
                    $mark['U+' . $char] = 1;
                    break;
                case 'N':
                    // numeric value
                    if ($ord > 128 && $num !== '') {
                        $number['U+' . $char] = $num;
                    }
            }
            // Accented Latin letters without "official" decomposition
            $match = [];
            if (preg_match('/^LATIN (SMALL|CAPITAL) LETTER ([A-Z]) WITH/', $name, $match) && !$decomp) {
                $c = ord($match[2]);
                if ($match[1] === 'SMALL') {
                    $c += 32;
                }
                $decomposition['U+' . $char] = [dechex($c)];
                continue;
            }
            $match = [];
            if (preg_match('/(<.*>)? *(.+)/', $decomp, $match)) {
                switch ($match[1]) {
                    case '<circle>':
                        // add parenthesis as circle replacement, eg (1)
                        $match[2] = '0028 ' . $match[2] . ' 0029';
                        break;
                    case '<square>':
                        // add square brackets as square replacement, eg [1]
                        $match[2] = '005B ' . $match[2] . ' 005D';
                        break;
                    case '<compat>':
                        // ignore multi char decompositions that start with a space
                        if (preg_match('/^0020 /', $match[2])) {
                            continue 2;
                        }
                        break;
                    case '<initial>':
                    case '<medial>':
                    case '<final>':
                    case '<isolated>':
                    case '<vertical>':
                        continue 2;
                }
                $decomposition['U+' . $char] = explode(' ', $match[2]);
            }
        }
        fclose($fh);
        // Process custom decompositions
        $customTranslitFile = ExtensionManagementUtility::extPath('core') . 'Resources/Private/Charsets/unidata/Translit.txt';
        if (GeneralUtility::validPathStr($customTranslitFile) && @is_file($customTranslitFile)) {
            $fh = fopen($customTranslitFile, 'rb');
            if ($fh) {
                while (!feof($fh)) {
                    $line = fgets($fh, 4096);
                    if ($line === false) {
                        continue;
                    }
                    if ($line[0] !== '#' && trim($line) !== '') {
                        [$char, $translit] = GeneralUtility::trimExplode(';', $line);
                        if (!$translit) {
                            $omit['U+' . $char] = 1;
                        }
                        $decomposition['U+' . $char] = explode(' ', $translit);
                    }
                }
                fclose($fh);
            }
        }
        // Decompose and remove marks; inspired by unac (Loic Dachary <loic@senga.org>)
        foreach ($decomposition as $from => $to) {
            $code_decomp = [];
            while ($code_value = array_shift($to)) {
                // Do recursive decomposition
                if (isset($decomposition['U+' . $code_value])) {
                    foreach (array_reverse($decomposition['U+' . $code_value]) as $cv) {
                        array_unshift($to, $cv);
                    }
                } elseif (!isset($mark['U+' . $code_value])) {
                    // remove mark
                    $code_decomp[] = $code_value;
                }
            }
            if (!empty($code_decomp) || isset($omit[$from])) {
                $decomposition[$from] = $code_decomp;
            } else {
                unset($decomposition[$from]);
            }
        }
        // Create ascii only mapping
        $this->toASCII['utf-8'] = [];
        foreach ($decomposition as $from => $to) {
            $code_decomp = [];
            while ($code_value = array_shift($to)) {
                $ord = (int)hexdec($code_value);
                if ($ord > 127) {
                    continue 2;
                }
                // Skip decompositions containing non-ASCII chars
                $code_decomp[] = chr($ord);
            }
            $this->toASCII['utf-8'][$this->UnumberToChar((int)hexdec(substr($from, 2)))] = implode('', $code_decomp);
        }
        // Add numeric decompositions
        foreach ($number as $from => $to) {
            $utf8_char = $this->UnumberToChar((int)hexdec(substr($from, 2)));
            if (!isset($this->toASCII['utf-8'][$utf8_char])) {
                $this->toASCII['utf-8'][$utf8_char] = $to;
            }
        }
        GeneralUtility::writeFileToTypo3tempDir($cacheFileASCII, serialize($this->toASCII['utf-8']));
        return true;
    }

    /**
     * Maps all characters of a UTF-8 string.
     *
     * @param string $str UTF-8 string
     */
    public function utf8_char_mapping(string $str): string
    {
        if (!$this->initUnicodeData()) {
            // Do nothing
            return $str;
        }
        $out = '';
        $map = $this->toASCII['utf-8'];
        for ($i = 0; isset($str[$i]); $i++) {
            $c = ord($str[$i]);
            $mbc = '';
            // single-byte (0xxxxxx)
            if (!($c & 128)) {
                $mbc = $str[$i];
            } elseif (($c & 192) === 192) {
                $bc = 0;
                // multibyte starting byte (11xxxxxx)
                for (; $c & 128; $c = $c << 1) {
                    $bc++;
                }
                // calculate number of bytes
                $mbc = substr($str, $i, $bc);
                $i += $bc - 1;
            }
            if (isset($map[$mbc])) {
                $out .= $map[$mbc];
            } else {
                $out .= $mbc;
            }
        }
        return $out;
    }
}
