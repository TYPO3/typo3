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
 * Notes on UTF-8
 *
 * Functions working on UTF-8 strings:
 *
 * - strchr/strstr
 * - strrchr
 * - substr_count
 * - implode/explode/join
 *
 * Functions nearly working on UTF-8 strings:
 *
 * - trim/ltrim/rtrim: the second parameter 'charlist' won't work for characters not contained in 7-bit ASCII
 * - htmlentities: charset support for UTF-8 only since PHP 4.3.0
 * - preg_*: Support compiled into PHP by default nowadays, but could be unavailable, need to use modifier
 *
 * Functions NOT working on UTF-8 strings:
 *
 * - str*cmp
 * - stristr
 * - stripos
 * - substr
 * - strrev
 * - split/spliti
 * - ...
 */

/**
 * Class for conversion between charsets
 */
class CharsetConverter implements SingletonInterface
{
    /**
     * ASCII Value for chars with no equivalent.
     *
     * @var int
     */
    protected $noCharByteVal = 63;

    /**
     * This is the array where parsed conversion tables are stored (cached)
     *
     * @var array
     */
    protected $parsedCharsets = [];

    /**
     * An array where charset-to-ASCII mappings are stored (cached)
     *
     * @var array
     */
    protected $toASCII = [];

    /**
     * This tells the converter which charsets has two bytes per char:
     *
     * @var array
     */
    protected $twoByteSets = [
        'ucs-2' => 1,
    ];

    /**
     * This tells the converter which charsets use a scheme like the Extended Unix Code:
     *
     * @var array
     */
    protected $eucBasedSets = [
        'gb2312' => 1, // Chinese, simplified.
        'big5' => 1, // Chinese, traditional.
        'euc-kr' => 1, // Korean
        'shift_jis' => 1,
    ];

    /********************************************
     *
     * Charset Conversion functions
     *
     ********************************************/
    /**
     * Convert from one charset to another charset.
     *
     * @param string $inputString Input string
     * @param string $fromCharset From charset (the current charset of the string)
     * @param string $toCharset To charset (the output charset wanted)
     * @return string Converted string
     */
    public function conv($inputString, $fromCharset, $toCharset)
    {
        if ($fromCharset === $toCharset) {
            return $inputString;
        }
        // PHP-libs don't support fallback to SGML entities, but UTF-8 handles everything
        if ($toCharset === 'utf-8') {
            // Returns FALSE for unsupported charsets
            $convertedString = mb_convert_encoding($inputString, $toCharset, $fromCharset);
            if ($convertedString !== false) {
                return $convertedString;
            }
        }
        if ($fromCharset !== 'utf-8') {
            $inputString = $this->utf8_encode($inputString, $fromCharset);
        }
        if ($toCharset !== 'utf-8') {
            $inputString = $this->utf8_decode($inputString, $toCharset, true);
        }
        return $inputString;
    }

    /**
     * Converts $str from $charset to UTF-8
     *
     * @param string $str String in local charset to convert to UTF-8
     * @param string $charset Charset, lowercase. Must be found in csconvtbl/ folder.
     * @return string Output string, converted to UTF-8
     */
    public function utf8_encode($str, $charset)
    {
        if ($charset === 'utf-8') {
            return $str;
        }
        // Charset is case-insensitive
        // Parse conv. table if not already
        if ($this->initCharset($charset)) {
            $strLen = strlen($str);
            $outStr = '';
            // Traverse each char in string
            for ($a = 0; $a < $strLen; $a++) {
                $chr = substr($str, $a, 1);
                $ord = ord($chr);
                // If the charset has two bytes per char
                if (isset($this->twoByteSets[$charset])) {
                    // TYPO3 cannot convert from ucs-2 as the according conversion table is not present
                    $ord2 = ord($str[$a + 1]);
                    // Assume big endian
                    $ord = $ord << 8 | $ord2;
                    // If the local char-number was found in parsed conv. table then we use that, otherwise 127 (no char?)
                    if (isset($this->parsedCharsets[$charset]['local'][$ord])) {
                        $outStr .= $this->parsedCharsets[$charset]['local'][$ord];
                    } else {
                        $outStr .= chr($this->noCharByteVal);
                    }
                    // No char exists
                    $a++;
                } elseif ($ord > 127) {
                    // If char has value over 127 it's a multibyte char in UTF-8
                    // EUC uses two-bytes above 127; we get both and advance pointer and make $ord a 16bit int.
                    if (isset($this->eucBasedSets[$charset])) {
                        // Shift-JIS: chars between 160 and 223 are single byte
                        if ($charset !== 'shift_jis' || ($ord < 160 || $ord > 223)) {
                            $a++;
                            $ord2 = ord(substr($str, $a, 1));
                            $ord = $ord * 256 + $ord2;
                        }
                    }
                    if (isset($this->parsedCharsets[$charset]['local'][$ord])) {
                        // If the local char-number was found in parsed conv. table then we use that, otherwise 127 (no char?)
                        $outStr .= $this->parsedCharsets[$charset]['local'][$ord];
                    } else {
                        $outStr .= chr($this->noCharByteVal);
                    }
                } else {
                    $outStr .= $chr;
                }
            }
            return $outStr;
        }
        return '';
    }

    /**
     * Converts $str from UTF-8 to $charset
     *
     * @param string $str String in UTF-8 to convert to local charset
     * @param string $charset Charset, lowercase. Must be found in csconvtbl/ folder.
     * @param bool $useEntityForNoChar If set, then characters that are not available in the destination character set will be encoded as numeric entities
     * @return string Output string, converted to local charset
     */
    public function utf8_decode($str, $charset, $useEntityForNoChar = false)
    {
        if ($charset === 'utf-8') {
            return $str;
        }
        // Charset is case-insensitive.
        // Parse conv. table if not already
        if ($this->initCharset($charset)) {
            $strLen = strlen($str);
            $outStr = '';
            // Traverse each char in UTF-8 string
            for ($a = 0, $i = 0; $a < $strLen; $a++, $i++) {
                $chr = substr($str, $a, 1);
                $ord = ord($chr);
                // This means multibyte! (first byte!)
                if ($ord > 127) {
                    // Since the first byte must have the 7th bit set we check that. Otherwise we might be in the middle of a byte sequence.
                    if ($ord & 64) {
                        // Add first byte
                        $buf = $chr;
                        // For each byte in multibyte string
                        for ($b = 0; $b < 8; $b++) {
                            // Shift it left and
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
                        // If the UTF-8 char-sequence is found then...
                        if (isset($this->parsedCharsets[$charset]['utf8'][$buf])) {
                            // The local number
                            $mByte = $this->parsedCharsets[$charset]['utf8'][$buf];
                            // If the local number is greater than 255 we will need to split the byte (16bit word assumed) in two chars.
                            if ($mByte > 255) {
                                $outStr .= chr($mByte >> 8 & 255) . chr($mByte & 255);
                            } else {
                                $outStr .= chr($mByte);
                            }
                        } elseif ($useEntityForNoChar) {
                            // Create num entity:
                            $outStr .= '&#' . $this->utf8CharToUnumber($buf, true) . ';';
                        } else {
                            $outStr .= chr($this->noCharByteVal);
                        }
                    } else {
                        $outStr .= chr($this->noCharByteVal);
                    }
                } else {
                    $outStr .= $chr;
                }
            }
            return $outStr;
        }
        return '';
    }

    /**
     * Converts all chars in the input UTF-8 string into integer numbers returned in an array.
     * All HTML entities (like &amp; or &pound; or &#123; or &#x3f5d;) will be detected as characters.
     * Also, instead of integer numbers the real UTF-8 char is returned.
     *
     * @param string $str Input string, UTF-8
     * @return array Output array with the char numbers
     */
    public function utf8_to_numberarray($str)
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
                    $outArr[] = chr($this->noCharByteVal);
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
     * bytes | bits | representation
     *     1 |    7 | 0vvvvvvv
     *     2 |   11 | 110vvvvv 10vvvvvv
     *     3 |   16 | 1110vvvv 10vvvvvv 10vvvvvv
     *     4 |   21 | 11110vvv 10vvvvvv 10vvvvvv 10vvvvvv
     *     5 |   26 | 111110vv 10vvvvvv 10vvvvvv 10vvvvvv 10vvvvvv
     *     6 |   31 | 1111110v 10vvvvvv 10vvvvvv 10vvvvvv 10vvvvvv 10vvvvvv
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
            $str .= chr($this->noCharByteVal);
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
    public function utf8CharToUnumber($str, $hex = false)
    {
        // First char
        $ord = ord($str[0]);
        // This verifies that it IS a multi byte string
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

    /********************************************
     *
     * Init functions
     *
     ********************************************/
    /**
     * This will initialize a charset for use if it's defined in the 'typo3/sysext/core/Resources/Private/Charsets/csconvtbl/' folder
     * This function is automatically called by the conversion functions
     *
     * PLEASE SEE: http://www.unicode.org/Public/MAPPINGS/
     *
     * @param string $charset The charset to be initialized. Use lowercase charset always (the charset must match exactly with a filename in csconvtbl/ folder ([charset].tbl)
     * @return int Returns '1' if already loaded, '2' if the charset conversion table was found and parsed.
     * @throws UnknownCharsetException if no charset table was found
     */
    protected function initCharset($charset)
    {
        // Only process if the charset is not yet loaded:
        if (empty($this->parsedCharsets[$charset])) {
            // Conversion table filename:
            $charsetConvTableFile = ExtensionManagementUtility::extPath('core') . 'Resources/Private/Charsets/csconvtbl/' . $charset . '.tbl';
            // If the conversion table is found:
            if ($charset && GeneralUtility::validPathStr($charsetConvTableFile) && @is_file($charsetConvTableFile)) {
                // Cache file for charsets:
                // Caching brought parsing time for gb2312 down from 2400 ms to 150 ms. For other charsets we are talking 11 ms down to zero.
                $cacheFile = Environment::getVarPath() . '/charset/charset_' . $charset . '.tbl';
                if ($cacheFile && @is_file($cacheFile)) {
                    $this->parsedCharsets[$charset] = unserialize((string)file_get_contents($cacheFile), ['allowed_classes' => false]);
                } else {
                    // Parse conversion table into lines:
                    $lines = GeneralUtility::trimExplode(LF, (string)file_get_contents($charsetConvTableFile), true);
                    // Initialize the internal variable holding the conv. table:
                    $this->parsedCharsets[$charset] = ['local' => [], 'utf8' => []];
                    // traverse the lines:
                    $detectedType = '';
                    foreach ($lines as $value) {
                        // Comment line or blanks are ignored.
                        if (trim($value) && $value[0] !== '#') {
                            // Detect type if not done yet: (Done on first real line)
                            // The "whitespaced" type is on the syntax 	"0x0A	0x000A	#LINE FEED" 	while 	"ms-token" is like 		"B9 = U+00B9 : SUPERSCRIPT ONE"
                            if (!$detectedType) {
                                $detectedType = preg_match('/[[:space:]]*0x([[:xdigit:]]*)[[:space:]]+0x([[:xdigit:]]*)[[:space:]]+/', $value) ? 'whitespaced' : 'ms-token';
                            }
                            $hexbyte = '';
                            $utf8 = '';
                            if ($detectedType === 'ms-token') {
                                [$hexbyte, $utf8] = preg_split('/[=:]/', $value, 3);
                            } elseif ($detectedType === 'whitespaced') {
                                $regA = [];
                                preg_match('/[[:space:]]*0x([[:xdigit:]]*)[[:space:]]+0x([[:xdigit:]]*)[[:space:]]+/', $value, $regA);
                                if (empty($regA)) {
                                    // No match => skip this item
                                    continue;
                                }
                                $hexbyte = $regA[1];
                                $utf8 = 'U+' . $regA[2];
                            }
                            $decval = hexdec(trim($hexbyte));
                            if ($decval > 127) {
                                $utf8decval = hexdec(substr(trim($utf8), 2));
                                $this->parsedCharsets[$charset]['local'][$decval] = $this->UnumberToChar((int)$utf8decval);
                                $this->parsedCharsets[$charset]['utf8'][$this->parsedCharsets[$charset]['local'][$decval]] = $decval;
                            }
                        }
                    }
                    if ($cacheFile) {
                        GeneralUtility::writeFileToTypo3tempDir($cacheFile, serialize($this->parsedCharsets[$charset]));
                    }
                }
                return 2;
            }
            throw new UnknownCharsetException(sprintf('Unknown charset "%s"', $charset), 1508916031);
        }
        return 1;
    }

    /**
     * This function initializes all UTF-8 character data tables.
     *
     * PLEASE SEE: http://www.unicode.org/Public/UNIDATA/
     *
     * @return int Returns FALSE on error, a TRUE value on success: 1 table already loaded, 2, cached version, 3 table parsed (and cached).
     */
    protected function initUnicodeData()
    {
        // Cache file
        $cacheFileASCII = Environment::getVarPath() . '/charset/csascii_utf-8.tbl';
        // Only process if the tables are not yet loaded
        if (isset($this->toASCII['utf-8']) && is_array($this->toASCII['utf-8'])) {
            return 1;
        }
        // Use cached version if possible
        if ($cacheFileASCII && @is_file($cacheFileASCII)) {
            $this->toASCII['utf-8'] = unserialize((string)file_get_contents($cacheFileASCII), ['allowed_classes' => false]);
            return 2;
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
        if ($cacheFileASCII) {
            GeneralUtility::writeFileToTypo3tempDir($cacheFileASCII, serialize($this->toASCII['utf-8']));
        }
        return 3;
    }

    /**
     * This function initializes the to-ASCII conversion table for a charset other than UTF-8.
     * This function is automatically called by the ASCII transliteration functions.
     *
     * @param string $charset Charset for which to initialize conversion.
     * @return int Returns FALSE on error, a TRUE value on success: 1 table already loaded, 2, cached version, 3 table parsed (and cached).
     */
    protected function initToASCII($charset)
    {
        // Only process if the case table is not yet loaded:
        if (isset($this->toASCII[$charset]) && is_array($this->toASCII[$charset])) {
            return 1;
        }
        // Use cached version if possible
        $cacheFile = Environment::getVarPath() . '/charset/csascii_' . $charset . '.tbl';
        if ($cacheFile && @is_file($cacheFile)) {
            $this->toASCII[$charset] = unserialize((string)file_get_contents($cacheFile), ['allowed_classes' => false]);
            return 2;
        }
        // Init UTF-8 conversion for this charset
        if (!$this->initCharset($charset)) {
            return false;
        }
        // UTF-8/ASCII transliteration is used as the base conversion table
        if (!$this->initUnicodeData()) {
            return false;
        }
        foreach ($this->parsedCharsets[$charset]['local'] as $ci => $utf8) {
            // Reconvert to charset (don't use chr() of numeric value, might be muli-byte)
            $c = $this->utf8_decode($utf8, $charset);
            if (isset($this->toASCII['utf-8'][$utf8])) {
                $this->toASCII[$charset][$c] = $this->toASCII['utf-8'][$utf8];
            }
        }
        if ($cacheFile) {
            GeneralUtility::writeFileToTypo3tempDir($cacheFile, serialize($this->toASCII[$charset]));
        }
        return 3;
    }

    /********************************************
     *
     * String operation functions
     *
     ********************************************/

    /**
     * Converts special chars (like æøåÆØÅ, umlauts etc) to ascii equivalents (usually double-bytes, like æ => ae etc.)
     *
     * @param string $charset Character set of string
     * @param string $string Input string to convert
     * @return string The converted string
     */
    public function specCharsToASCII($charset, $string)
    {
        if ($charset === 'utf-8') {
            $string = $this->utf8_char_mapping($string);
        } elseif (isset($this->eucBasedSets[$charset])) {
            $string = $this->euc_char_mapping($string, $charset);
        } else {
            // Treat everything else as single-byte encoding
            $string = $this->sb_char_mapping($string, $charset);
        }
        return $string;
    }

    /********************************************
     *
     * Internal string operation functions
     *
     ********************************************/
    /**
     * Maps all characters of a string in a single byte charset.
     *
     * @param string $str The string
     * @param string $charset The charset
     * @return string The converted string
     */
    public function sb_char_mapping($str, $charset)
    {
        if (!$this->initToASCII($charset)) {
            return $str;
        }
        // Do nothing
        $map = &$this->toASCII[$charset];
        $out = '';
        for ($i = 0; isset($str[$i]); $i++) {
            $c = $str[$i];
            if (isset($map[$c])) {
                $out .= $map[$c];
            } else {
                $out .= $c;
            }
        }
        return $out;
    }

    /********************************************
     *
     * Internal UTF-8 string operation functions
     *
     ********************************************/

    /**
     * Maps all characters of a UTF-8 string.
     *
     * @param string $str UTF-8 string
     * @return string The converted string
     */
    public function utf8_char_mapping($str)
    {
        if (!$this->initUnicodeData()) {
            // Do nothing
            return $str;
        }
        $out = '';
        $map = &$this->toASCII['utf-8'];
        for ($i = 0; isset($str[$i]); $i++) {
            $c = ord($str[$i]);
            $mbc = '';
            // single-byte (0xxxxxx)
            if (!($c & 128)) {
                $mbc = $str[$i];
            } elseif (($c & 192) === 192) {
                $bc = 0;
                // multi-byte starting byte (11xxxxxx)
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

    /********************************************
     *
     * Internal EUC string operation functions
     *
     * Extended Unix Code:
     *  ASCII compatible 7bit single bytes chars
     *  8bit two byte chars
     *
     * Shift-JIS is treated as a special case.
     *
     ********************************************/

    /**
     * Maps all characters of a string in the EUC charset family.
     *
     * @param string $str EUC multibyte character string
     * @param string $charset The charset
     * @return string The converted string
     */
    public function euc_char_mapping($str, $charset)
    {
        if (!$this->initToASCII($charset)) {
            return $str;
        }
        // do nothing
        $map = &$this->toASCII[$charset];
        $out = '';
        for ($i = 0; isset($str[$i]); $i++) {
            $mbc = $str[$i];
            $c = ord($mbc);
            if ($charset === 'shift_jis') {
                // A double-byte char
                if ($c >= 128 && $c < 160 || $c >= 224) {
                    $mbc = substr($str, $i, 2);
                    $i++;
                }
            } else {
                // A double-byte char
                if ($c >= 128) {
                    $mbc = substr($str, $i, 2);
                    $i++;
                }
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
