<?php
namespace TYPO3\CMS\Core\Charset;

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

use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Log\LogManager;
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
 * - strlen: returns the length in BYTES, if you need the length in CHARACTERS use utf8_strlen
 * - trim/ltrim/rtrim: the second parameter 'charlist' won't work for characters not contained in 7-bit ASCII
 * - strpos/strrpos: they return the BYTE position, if you need the CHARACTER position use utf8_strpos/utf8_strrpos
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
    public $noCharByteVal = 63;

    /**
     * This is the array where parsed conversion tables are stored (cached)
     *
     * @var array
     */
    public $parsedCharsets = [];

    /**
     * An array where case folding data will be stored (cached)
     *
     * @var array
     */
    public $caseFolding = [];

    /**
     * An array where charset-to-ASCII mappings are stored (cached)
     *
     * @var array
     */
    public $toASCII = [];

    /**
     * This tells the converter which charsets has two bytes per char:
     *
     * @var array
     */
    public $twoByteSets = [
        'ucs-2' => 1
    ];

    /**
     * This tells the converter which charsets has four bytes per char:
     *
     * @var array
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9
     */
    public $fourByteSets = [
        'ucs-4' => 1, // 4-byte Unicode
        'utf-32' => 1
    ];

    /**
     * This tells the converter which charsets use a scheme like the Extended Unix Code:
     *
     * @var array
     */
    public $eucBasedSets = [
        'gb2312' => 1, // Chinese, simplified.
        'big5' => 1, // Chinese, traditional.
        'euc-kr' => 1, // Korean
        'shift_jis' => 1
    ];

    /**
     * @link http://developer.apple.com/documentation/macos8/TextIntlSvcs/TextEncodingConversionManager/TEC1.5/TEC.b0.html
     * @link http://czyborra.com/charsets/iso8859.html
     *
     * @var array
     */
    public $synonyms = [
        'us' => 'ascii',
        'us-ascii' => 'ascii',
        'cp819' => 'iso-8859-1',
        'ibm819' => 'iso-8859-1',
        'iso-ir-100' => 'iso-8859-1',
        'iso-ir-101' => 'iso-8859-2',
        'iso-ir-109' => 'iso-8859-3',
        'iso-ir-110' => 'iso-8859-4',
        'iso-ir-144' => 'iso-8859-5',
        'iso-ir-127' => 'iso-8859-6',
        'iso-ir-126' => 'iso-8859-7',
        'iso-ir-138' => 'iso-8859-8',
        'iso-ir-148' => 'iso-8859-9',
        'iso-ir-157' => 'iso-8859-10',
        'iso-ir-179' => 'iso-8859-13',
        'iso-ir-199' => 'iso-8859-14',
        'iso-ir-203' => 'iso-8859-15',
        'csisolatin1' => 'iso-8859-1',
        'csisolatin2' => 'iso-8859-2',
        'csisolatin3' => 'iso-8859-3',
        'csisolatin5' => 'iso-8859-9',
        'csisolatin8' => 'iso-8859-14',
        'csisolatin9' => 'iso-8859-15',
        'csisolatingreek' => 'iso-8859-7',
        'iso-celtic' => 'iso-8859-14',
        'latin1' => 'iso-8859-1',
        'latin2' => 'iso-8859-2',
        'latin3' => 'iso-8859-3',
        'latin5' => 'iso-8859-9',
        'latin6' => 'iso-8859-10',
        'latin8' => 'iso-8859-14',
        'latin9' => 'iso-8859-15',
        'l1' => 'iso-8859-1',
        'l2' => 'iso-8859-2',
        'l3' => 'iso-8859-3',
        'l5' => 'iso-8859-9',
        'l6' => 'iso-8859-10',
        'l8' => 'iso-8859-14',
        'l9' => 'iso-8859-15',
        'cyrillic' => 'iso-8859-5',
        'arabic' => 'iso-8859-6',
        'tis-620' => 'iso-8859-11',
        'win874' => 'windows-874',
        'win1250' => 'windows-1250',
        'win1251' => 'windows-1251',
        'win1252' => 'windows-1252',
        'win1253' => 'windows-1253',
        'win1254' => 'windows-1254',
        'win1255' => 'windows-1255',
        'win1256' => 'windows-1256',
        'win1257' => 'windows-1257',
        'win1258' => 'windows-1258',
        'cp1250' => 'windows-1250',
        'cp1251' => 'windows-1251',
        'cp1252' => 'windows-1252',
        'ms-ee' => 'windows-1250',
        'ms-ansi' => 'windows-1252',
        'ms-greek' => 'windows-1253',
        'ms-turk' => 'windows-1254',
        'winbaltrim' => 'windows-1257',
        'koi-8ru' => 'koi-8r',
        'koi8r' => 'koi-8r',
        'cp878' => 'koi-8r',
        'mac' => 'macroman',
        'macintosh' => 'macroman',
        'euc-cn' => 'gb2312',
        'x-euc-cn' => 'gb2312',
        'euccn' => 'gb2312',
        'cp936' => 'gb2312',
        'big-5' => 'big5',
        'cp950' => 'big5',
        'eucjp' => 'euc-jp',
        'sjis' => 'shift_jis',
        'shift-jis' => 'shift_jis',
        'cp932' => 'shift_jis',
        'cp949' => 'euc-kr',
        'utf7' => 'utf-7',
        'utf8' => 'utf-8',
        'utf16' => 'utf-16',
        'utf32' => 'utf-32',
        'ucs2' => 'ucs-2',
        'ucs4' => 'ucs-4'
    ];

    /**
     * TYPO3 specific: Array with the system charsets used for each system language in TYPO3:
     * Empty values means "utf-8"
     *
     * @var array
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use Locales
     */
    public $charSetArray = [
        'af' => '',
        'ar' => 'iso-8859-6',
        'ba' => 'iso-8859-2',
        'bg' => 'windows-1251',
        'br' => '',
        'ca' => 'iso-8859-15',
        'ch' => 'gb2312',
        'cs' => 'windows-1250',
        'cz' => 'windows-1250',
        'da' => '',
        'de' => '',
        'dk' => '',
        'el' => 'iso-8859-7',
        'eo' => 'utf-8',
        'es' => '',
        'et' => 'iso-8859-4',
        'eu' => '',
        'fa' => 'utf-8',
        'fi' => '',
        'fo' => 'utf-8',
        'fr' => '',
        'fr_CA' => '',
        'ga' => '',
        'ge' => 'utf-8',
        'gl' => '',
        'gr' => 'iso-8859-7',
        'he' => 'utf-8',
        'hi' => 'utf-8',
        'hk' => 'big5',
        'hr' => 'windows-1250',
        'hu' => 'iso-8859-2',
        'is' => 'utf-8',
        'it' => '',
        'ja' => 'shift_jis',
        'jp' => 'shift_jis',
        'ka' => 'utf-8',
        'kl' => 'utf-8',
        'km' => 'utf-8',
        'ko' => 'euc-kr',
        'kr' => 'euc-kr',
        'lt' => 'windows-1257',
        'lv' => 'utf-8',
        'ms' => '',
        'my' => '',
        'nl' => '',
        'no' => '',
        'pl' => 'iso-8859-2',
        'pt' => '',
        'pt_BR' => '',
        'qc' => '',
        'ro' => 'iso-8859-2',
        'ru' => 'windows-1251',
        'se' => '',
        'si' => 'windows-1250',
        'sk' => 'windows-1250',
        'sl' => 'windows-1250',
        'sq' => 'utf-8',
        'sr' => 'utf-8',
        'sv' => '',
        'th' => 'iso-8859-11',
        'tr' => 'iso-8859-9',
        'ua' => 'windows-1251',
        'uk' => 'windows-1251',
        'vi' => 'utf-8',
        'vn' => 'utf-8',
        'zh' => 'big5'
    ];

    /**
     * Normalize - changes input character set to lowercase letters.
     *
     * @param string $charset Input charset
     * @return string Normalized charset
     */
    public function parse_charset($charset)
    {
        $charset = trim(strtolower($charset));
        if (isset($this->synonyms[$charset])) {
            $charset = $this->synonyms[$charset];
        }
        return $charset;
    }

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
     * @param bool $useEntityForNoChar If set, then characters that are not available in the destination character set will be encoded as numeric entities
     * @return string Converted string
     * @see convArray()
     */
    public function conv($inputString, $fromCharset, $toCharset, $useEntityForNoChar = false)
    {
        if ($fromCharset === $toCharset) {
            return $inputString;
        }
        // PHP-libs don't support fallback to SGML entities, but UTF-8 handles everything
        if ($toCharset === 'utf-8' || !$useEntityForNoChar) {
            // Returns FALSE for unsupported charsets
            $convertedString = mb_convert_encoding($inputString, $toCharset, $fromCharset);
            if (false !== $convertedString) {
                return $convertedString;
            }
        }
        if ($fromCharset !== 'utf-8') {
            $inputString = $this->utf8_encode($inputString, $fromCharset);
        }
        if ($toCharset !== 'utf-8') {
            $inputString = $this->utf8_decode($inputString, $toCharset, $useEntityForNoChar);
        }
        return $inputString;
    }

    /**
     * Convert all elements in ARRAY with type string from one charset to another charset.
     * NOTICE: Array is passed by reference!
     *
     * @param array $array Input array, possibly multidimensional
     * @param string $fromCharset From charset (the current charset of the string)
     * @param string $toCharset To charset (the output charset wanted)
     * @param bool $useEntityForNoChar If set, then characters that are not available in the destination character set will be encoded as numeric entities
     * @see conv()
     */
    public function convArray(&$array, $fromCharset, $toCharset, $useEntityForNoChar = false)
    {
        foreach ($array as $key => $value) {
            if (is_array($array[$key])) {
                $this->convArray($array[$key], $fromCharset, $toCharset, $useEntityForNoChar);
            } elseif (is_string($array[$key])) {
                $array[$key] = $this->conv($array[$key], $fromCharset, $toCharset, $useEntityForNoChar);
            }
        }
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
                                $outStr .= chr(($mByte >> 8 & 255)) . chr(($mByte & 255));
                            } else {
                                $outStr .= chr($mByte);
                            }
                        } elseif ($useEntityForNoChar) {
                            // Create num entity:
                            $outStr .= '&#' . $this->utf8CharToUnumber($buf, 1) . ';';
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
     * Converts all chars > 127 to numeric entities.
     *
     * @param string $str Input string
     * @return string Output string
     */
    public function utf8_to_entities($str)
    {
        $strLen = strlen($str);
        $outStr = '';
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
                    $outStr .= '&#' . $this->utf8CharToUnumber($buf, 1) . ';';
                } else {
                    $outStr .= chr($this->noCharByteVal);
                }
            } else {
                $outStr .= $chr;
            }
        }
        return $outStr;
    }

    /**
     * Converts numeric entities (UNICODE, eg. decimal (&#1234;) or hexadecimal (&#x1b;)) to UTF-8 multibyte chars.
     * All string-HTML entities (like &amp; or &pound;) will be converted as well
     * @param string $str Input string, UTF-8
     * @return string Output string
     */
    public function entities_to_utf8($str)
    {
        $trans_tbl = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT));
        $token = md5(microtime());
        $parts = explode($token, preg_replace('/(&([#[:alnum:]]*);)/', $token . '${2}' . $token, $str));
        foreach ($parts as $k => $v) {
            // Only take every second element
            if ($k % 2 === 0) {
                continue;
            }
            $position = 0;
            // Dec or hex entities
            if (substr($v, $position, 1) === '#') {
                $position++;
                if (substr($v, $position, 1) === 'x') {
                    $v = hexdec(substr($v, ++$position));
                } else {
                    $v = substr($v, $position);
                }
                $parts[$k] = $this->UnumberToChar($v);
            } elseif (isset($trans_tbl['&' . $v . ';'])) {
                // Other entities:
                $v = $trans_tbl['&' . $v . ';'];
                $parts[$k] = $v;
            } else {
                // No conversion:
                $parts[$k] = '&' . $v . ';';
            }
        }
        return implode('', $parts);
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
        $str = $this->entities_to_utf8($str);

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
                    $binBuf .= substr('00000000' . decbin(ord(substr($str, ($b + 1), 1))), -6);
                } else {
                    break;
                }
            }
            $binBuf = substr(('00000000' . decbin(ord($str[0]))), -(6 - $b)) . $binBuf;
            $int = bindec($binBuf);
        } else {
            $int = $ord;
        }
        return $hex ? 'x' . dechex($int) : $int;
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
     * @return int Returns '1' if already loaded. Returns FALSE if charset conversion table was not found. Returns '2' if the charset conversion table was found and parsed.
     * @access private
     */
    public function initCharset($charset)
    {
        // Only process if the charset is not yet loaded:
        if (!is_array($this->parsedCharsets[$charset])) {
            // Conversion table filename:
            $charsetConvTableFile = ExtensionManagementUtility::extPath('core') . 'Resources/Private/Charsets/csconvtbl/' . $charset . '.tbl';
            // If the conversion table is found:
            if ($charset && GeneralUtility::validPathStr($charsetConvTableFile) && @is_file($charsetConvTableFile)) {
                // Cache file for charsets:
                // Caching brought parsing time for gb2312 down from 2400 ms to 150 ms. For other charsets we are talking 11 ms down to zero.
                $cacheFile = GeneralUtility::getFileAbsFileName('typo3temp/var/charset/charset_' . $charset . '.tbl');
                if ($cacheFile && @is_file($cacheFile)) {
                    $this->parsedCharsets[$charset] = unserialize(file_get_contents($cacheFile));
                } else {
                    // Parse conversion table into lines:
                    $lines = GeneralUtility::trimExplode(LF, file_get_contents($charsetConvTableFile), true);
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
                                $detectedType = preg_match('/[[:space:]]*0x([[:alnum:]]*)[[:space:]]+0x([[:alnum:]]*)[[:space:]]+/', $value) ? 'whitespaced' : 'ms-token';
                            }
                            $hexbyte = '';
                            $utf8 = '';
                            if ($detectedType === 'ms-token') {
                                list($hexbyte, $utf8) = preg_split('/[=:]/', $value, 3);
                            } elseif ($detectedType === 'whitespaced') {
                                $regA = [];
                                preg_match('/[[:space:]]*0x([[:alnum:]]*)[[:space:]]+0x([[:alnum:]]*)[[:space:]]+/', $value, $regA);
                                $hexbyte = $regA[1];
                                $utf8 = 'U+' . $regA[2];
                            }
                            $decval = hexdec(trim($hexbyte));
                            if ($decval > 127) {
                                $utf8decval = hexdec(substr(trim($utf8), 2));
                                $this->parsedCharsets[$charset]['local'][$decval] = $this->UnumberToChar($utf8decval);
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
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(__CLASS__)
                ->warning('Unknown charset "' . $charset . '" used for settings like config.metaCharset.');
            return false;
        }
        return 1;
    }

    /**
     * This function initializes all UTF-8 character data tables.
     *
     * PLEASE SEE: http://www.unicode.org/Public/UNIDATA/
     *
     * @param string $mode Mode ("case", "ascii", ...)
     * @return int Returns FALSE on error, a TRUE value on success: 1 table already loaded, 2, cached version, 3 table parsed (and cached).
     * @access private
     */
    public function initUnicodeData($mode = null)
    {
        // Cache files
        $cacheFileCase = GeneralUtility::getFileAbsFileName('typo3temp/var/charset/cscase_utf-8.tbl');
        $cacheFileASCII = GeneralUtility::getFileAbsFileName('typo3temp/var/charset/csascii_utf-8.tbl');
        // Only process if the tables are not yet loaded
        switch ($mode) {
            case 'case':
                if (is_array($this->caseFolding['utf-8'])) {
                    return 1;
                }
                // Use cached version if possible
                if ($cacheFileCase && @is_file($cacheFileCase)) {
                    $this->caseFolding['utf-8'] = unserialize(file_get_contents($cacheFileCase));
                    return 2;
                }
                break;
            case 'ascii':
                if (is_array($this->toASCII['utf-8'])) {
                    return 1;
                }
                // Use cached version if possible
                if ($cacheFileASCII && @is_file($cacheFileASCII)) {
                    $this->toASCII['utf-8'] = unserialize(file_get_contents($cacheFileASCII));
                    return 2;
                }
                break;
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
        // key = utf8 char (single codepoint), value = utf8 string (codepoint sequence)
        // Note: we use the UTF-8 characters here and not the Unicode numbers to avoid conversion roundtrip in utf8_strtolower/-upper)
        $this->caseFolding['utf-8'] = [];
        $utf8CaseFolding = &$this->caseFolding['utf-8'];
        // a shorthand
        $utf8CaseFolding['toUpper'] = [];
        $utf8CaseFolding['toLower'] = [];
        $utf8CaseFolding['toTitle'] = [];
        // Array of temp. decompositions
        $decomposition = [];
        // Array of chars that are marks (eg. composing accents)
        $mark = [];
        // Array of chars that are numbers (eg. digits)
        $number = [];
        // Array of chars to be omitted (eg. Russian hard sign)
        $omit = [];
        while (!feof($fh)) {
            $line = fgets($fh, 4096);
            // Has a lot of info
            list($char, $name, $cat, , , $decomp, , , $num, , , , $upper, $lower, $title, ) = explode(';', rtrim($line));
            $ord = hexdec($char);
            if ($ord > 65535) {
                // Only process the BMP
                break;
            }
            $utf8_char = $this->UnumberToChar($ord);
            if ($upper) {
                $utf8CaseFolding['toUpper'][$utf8_char] = $this->UnumberToChar(hexdec($upper));
            }
            if ($lower) {
                $utf8CaseFolding['toLower'][$utf8_char] = $this->UnumberToChar(hexdec($lower));
            }
            // Store "title" only when different from "upper" (only a few)
            if ($title && $title !== $upper) {
                $utf8CaseFolding['toTitle'][$utf8_char] = $this->UnumberToChar(hexdec($title));
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
        // Process additional Unicode data for casing (allow folded characters to expand into a sequence)
        $specialCasingFile = ExtensionManagementUtility::extPath('core') . 'Resources/Private/Charsets/unidata/SpecialCasing.txt';
        if (GeneralUtility::validPathStr($specialCasingFile) && @is_file($specialCasingFile)) {
            $fh = fopen($specialCasingFile, 'rb');
            if ($fh) {
                while (!feof($fh)) {
                    $line = fgets($fh, 4096);
                    if ($line[0] !== '#' && trim($line) !== '') {
                        list($char, $lower, $title, $upper, $cond) = GeneralUtility::trimExplode(';', $line);
                        if ($cond === '' || $cond[0] === '#') {
                            $utf8_char = $this->UnumberToChar(hexdec($char));
                            if ($char !== $lower) {
                                $arr = explode(' ', $lower);
                                for ($i = 0; isset($arr[$i]); $i++) {
                                    $arr[$i] = $this->UnumberToChar(hexdec($arr[$i]));
                                }
                                $utf8CaseFolding['toLower'][$utf8_char] = implode('', $arr);
                            }
                            if ($char !== $title && $title !== $upper) {
                                $arr = explode(' ', $title);
                                for ($i = 0; isset($arr[$i]); $i++) {
                                    $arr[$i] = $this->UnumberToChar(hexdec($arr[$i]));
                                }
                                $utf8CaseFolding['toTitle'][$utf8_char] = implode('', $arr);
                            }
                            if ($char !== $upper) {
                                $arr = explode(' ', $upper);
                                for ($i = 0; isset($arr[$i]); $i++) {
                                    $arr[$i] = $this->UnumberToChar(hexdec($arr[$i]));
                                }
                                $utf8CaseFolding['toUpper'][$utf8_char] = implode('', $arr);
                            }
                        }
                    }
                }
                fclose($fh);
            }
        }
        // Process custom decompositions
        $customTranslitFile = ExtensionManagementUtility::extPath('core') . 'Resources/Private/Charsets/unidata/Translit.txt';
        if (GeneralUtility::validPathStr($customTranslitFile) && @is_file($customTranslitFile)) {
            $fh = fopen($customTranslitFile, 'rb');
            if ($fh) {
                while (!feof($fh)) {
                    $line = fgets($fh, 4096);
                    if ($line[0] !== '#' && trim($line) !== '') {
                        list($char, $translit) = GeneralUtility::trimExplode(';', $line);
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
        $ascii = &$this->toASCII['utf-8'];
        foreach ($decomposition as $from => $to) {
            $code_decomp = [];
            while ($code_value = array_shift($to)) {
                $ord = hexdec($code_value);
                if ($ord > 127) {
                    continue 2;
                }
                // Skip decompositions containing non-ASCII chars
                $code_decomp[] = chr($ord);
            }
            $ascii[$this->UnumberToChar(hexdec($from))] = implode('', $code_decomp);
        }
        // Add numeric decompositions
        foreach ($number as $from => $to) {
            $utf8_char = $this->UnumberToChar(hexdec($from));
            if (!isset($ascii[$utf8_char])) {
                $ascii[$utf8_char] = $to;
            }
        }
        if ($cacheFileCase) {
            GeneralUtility::writeFileToTypo3tempDir($cacheFileCase, serialize($utf8CaseFolding));
        }
        if ($cacheFileASCII) {
            GeneralUtility::writeFileToTypo3tempDir($cacheFileASCII, serialize($ascii));
        }
        return 3;
    }

    /**
     * This function initializes the folding table for a charset other than UTF-8.
     * This function is automatically called by the case folding functions.
     *
     * @param string $charset Charset for which to initialize case folding.
     * @return int Returns FALSE on error, a TRUE value on success: 1 table already loaded, 2, cached version, 3 table parsed (and cached).
     * @access private
     */
    public function initCaseFolding($charset)
    {
        // Only process if the case table is not yet loaded:
        if (is_array($this->caseFolding[$charset])) {
            return 1;
        }
        // Use cached version if possible
        $cacheFile = GeneralUtility::getFileAbsFileName('typo3temp/var/charset/cscase_' . $charset . '.tbl');
        if ($cacheFile && @is_file($cacheFile)) {
            $this->caseFolding[$charset] = unserialize(file_get_contents($cacheFile));
            return 2;
        }
        // init UTF-8 conversion for this charset
        if (!$this->initCharset($charset)) {
            return false;
        }
        // UTF-8 case folding is used as the base conversion table
        if (!$this->initUnicodeData('case')) {
            return false;
        }
        $nochar = chr($this->noCharByteVal);
        foreach ($this->parsedCharsets[$charset]['local'] as $ci => $utf8) {
            // Reconvert to charset (don't use chr() of numeric value, might be muli-byte)
            $c = $this->utf8_decode($utf8, $charset);
            $cc = $this->utf8_decode($this->caseFolding['utf-8']['toUpper'][$utf8], $charset);
            if ($cc !== '' && $cc !== $nochar) {
                $this->caseFolding[$charset]['toUpper'][$c] = $cc;
            }
            $cc = $this->utf8_decode($this->caseFolding['utf-8']['toLower'][$utf8], $charset);
            if ($cc !== '' && $cc !== $nochar) {
                $this->caseFolding[$charset]['toLower'][$c] = $cc;
            }
            $cc = $this->utf8_decode($this->caseFolding['utf-8']['toTitle'][$utf8], $charset);
            if ($cc !== '' && $cc !== $nochar) {
                $this->caseFolding[$charset]['toTitle'][$c] = $cc;
            }
        }
        // Add the ASCII case table
        $start = ord('a');
        $end = ord('z');
        for ($i = $start; $i <= $end; $i++) {
            $this->caseFolding[$charset]['toUpper'][chr($i)] = chr($i - 32);
        }
        $start = ord('A');
        $end = ord('Z');
        for ($i = $start; $i <= $end; $i++) {
            $this->caseFolding[$charset]['toLower'][chr($i)] = chr($i + 32);
        }
        if ($cacheFile) {
            GeneralUtility::writeFileToTypo3tempDir($cacheFile, serialize($this->caseFolding[$charset]));
        }
        return 3;
    }

    /**
     * This function initializes the to-ASCII conversion table for a charset other than UTF-8.
     * This function is automatically called by the ASCII transliteration functions.
     *
     * @param string $charset Charset for which to initialize conversion.
     * @return int Returns FALSE on error, a TRUE value on success: 1 table already loaded, 2, cached version, 3 table parsed (and cached).
     * @access private
     */
    public function initToASCII($charset)
    {
        // Only process if the case table is not yet loaded:
        if (is_array($this->toASCII[$charset])) {
            return 1;
        }
        // Use cached version if possible
        $cacheFile = GeneralUtility::getFileAbsFileName('typo3temp/var/charset/csascii_' . $charset . '.tbl');
        if ($cacheFile && @is_file($cacheFile)) {
            $this->toASCII[$charset] = unserialize(file_get_contents($cacheFile));
            return 2;
        }
        // Init UTF-8 conversion for this charset
        if (!$this->initCharset($charset)) {
            return false;
        }
        // UTF-8/ASCII transliteration is used as the base conversion table
        if (!$this->initUnicodeData('ascii')) {
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
     * Returns a part of a string.
     * Unit-tested by Kasper (single byte charsets only)
     *
     * @param string $charset The character set
     * @param string $string Character string
     * @param int $start Start position (character position)
     * @param int $len Length (in characters)
     * @return string The substring
     * @see substr(), mb_substr()
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_substr() directly
     */
    public function substr($charset, $string, $start, $len = null)
    {
        GeneralUtility::logDeprecatedFunction();
        return mb_substr($string, $start, $len, $charset);
    }

    /**
     * Counts the number of characters.
     * Unit-tested by Kasper (single byte charsets only)
     *
     * @param string $charset The character set
     * @param string $string Character string
     * @return int The number of characters
     * @see strlen()
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_strlen() directly
     */
    public function strlen($charset, $string)
    {
        GeneralUtility::logDeprecatedFunction();
        return mb_strlen($string, $charset);
    }

    /**
     * Truncates a string and pre-/appends a string.
     * Unit tested by Kasper
     *
     * @param string $charset The character set
     * @param string $string Character string
     * @param int $len Length (in characters)
     * @param string $crop Crop signifier
     * @return string The shortened string
     * @see substr(), mb_strimwidth()
     */
    public function crop($charset, $string, $len, $crop = '')
    {
        if ((int)$len === 0 || mb_strlen($string, $charset) <= abs($len)) {
            return $string;
        }
        if ($len > 0) {
            $string = mb_substr($string, 0, $len, $charset) . $crop;
        } else {
            $string = $crop . mb_substr($string, $len, mb_strlen($string, $charset), $charset);
        }
        return $string;
    }

    /**
     * Cuts a string short at a given byte length.
     *
     * @param string $charset The character set
     * @param string $string Character string
     * @param int $len The byte length
     * @return string The shortened string
     * @see mb_strcut()
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_strcut() directly
     */
    public function strtrunc($charset, $string, $len)
    {
        GeneralUtility::logDeprecatedFunction();
        if ($len <= 0) {
            return '';
        }
        return mb_strcut($string, 0, $len, $charset);
    }

    /**
     * Translates all characters of a string into their respective case values.
     * Unlike strtolower() and strtoupper() this method is locale independent.
     * Note that the string length may change!
     * eg. lower case German "ß" (sharp S) becomes upper case "SS"
     * Unit-tested by Kasper
     * Real case folding is language dependent, this method ignores this fact.
     *
     * @param string $charset Character set of string
     * @param string $string Input string to convert case for
     * @param string $case Case keyword: "toLower" means lowercase conversion, anything else is uppercase (use "toUpper" )
     * @return string The converted string
     * @see strtolower(), strtoupper()
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_strtolower() or mb_strtoupper() directly
     */
    public function conv_case($charset, $string, $case)
    {
        GeneralUtility::logDeprecatedFunction();
        return $case === 'toLower'
            ? mb_strtolower($string, $charset)
            : mb_strtoupper($string, $charset);
    }

    /**
     * Equivalent of lcfirst/ucfirst but using character set.
     *
     * @param string $charset
     * @param string $string
     * @param string $case can be 'toLower' or 'toUpper'
     * @return string
     */
    public function convCaseFirst($charset, $string, $case)
    {
        $firstChar = mb_substr($string, 0, 1, $charset);
        $firstChar = $case === 'toLower'
            ? mb_strtolower($firstChar, $charset)
            : mb_strtoupper($firstChar, $charset);
        $remainder = mb_substr($string, 1, null, $charset);
        return $firstChar . $remainder;
    }

    /**
     * Capitalize the given string
     *
     * @param string $charset
     * @param string $string
     * @return string
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_convert_case() directly
     */
    public function convCapitalize($charset, $string)
    {
        GeneralUtility::logDeprecatedFunction();
        return mb_convert_case($string, MB_CASE_TITLE, $charset);
    }

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
            $string = $this->utf8_char_mapping($string, 'ascii');
        } elseif (isset($this->eucBasedSets[$charset])) {
            $string = $this->euc_char_mapping($string, $charset, 'ascii');
        } else {
            // Treat everything else as single-byte encoding
            $string = $this->sb_char_mapping($string, $charset, 'ascii');
        }
        return $string;
    }

    /**
     * Converts the language codes that we get from the client (usually HTTP_ACCEPT_LANGUAGE)
     * into a TYPO3-readable language code
     *
     * @param string $languageCodesList List of language codes. something like 'de,en-us;q=0.9,de-de;q=0.7,es-cl;q=0.6,en;q=0.4,es;q=0.3,zh;q=0.1'
     * @return string A preferred language that TYPO3 supports, or "default" if none found
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use Locales::getPreferredClientLanguage() for usage
     */
    public function getPreferredClientLanguage($languageCodesList)
    {
        GeneralUtility::logDeprecatedFunction();
        /** @var Locales $locales */
        $locales = GeneralUtility::makeInstance(Locales::class);
        return $locales->getPreferredClientLanguage($languageCodesList);
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
     * @param string $mode Mode: 'case' (case folding) or 'ascii' (ASCII transliteration)
     * @param string $opt 'case': conversion 'toLower' or 'toUpper'
     * @return string The converted string
     */
    public function sb_char_mapping($str, $charset, $mode, $opt = '')
    {
        switch ($mode) {
            case 'case':
                if (!$this->initCaseFolding($charset)) {
                    return $str;
                }
                // Do nothing
                $map = &$this->caseFolding[$charset][$opt];
                break;
            case 'ascii':
                if (!$this->initToASCII($charset)) {
                    return $str;
                }
                // Do nothing
                $map = &$this->toASCII[$charset];
                break;
            default:
                return $str;
        }
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
     * Returns a part of a UTF-8 string.
     * Unit-tested by Kasper and works 100% like substr() / mb_substr() for full range of $start/$len
     *
     * @param string $str UTF-8 string
     * @param int $start Start position (character position)
     * @param int $len Length (in characters)
     * @return string The substring
     * @see substr()
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_substr() directly
     */
    public function utf8_substr($str, $start, $len = null)
    {
        GeneralUtility::logDeprecatedFunction();
        if ((string)$len === '0') {
            return '';
        }
        $byte_start = $this->utf8_char2byte_pos($str, $start);
        if ($byte_start === false) {
            if ($start > 0) {
                // $start outside string length
                return false;
            }
        }
        $str = substr($str, $byte_start);
        if ($len != null) {
            $byte_end = $this->utf8_char2byte_pos($str, $len);
            // $len outside actual string length
            if ($byte_end === false) {
                return $len < 0 ? '' : $str;
            }
            // When length is less than zero and exceeds, then we return blank string.
            return substr($str, 0, $byte_end);
        }
        return $str;
    }

    /**
     * Counts the number of characters of a string in UTF-8.
     * Unit-tested by Kasper and works 100% like strlen() / mb_strlen()
     *
     * @param string $str UTF-8 multibyte character string
     * @return int The number of characters
     * @see strlen()
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_strlen() directly
     */
    public function utf8_strlen($str)
    {
        GeneralUtility::logDeprecatedFunction();
        $n = 0;
        for ($i = 0; isset($str[$i]); $i++) {
            $c = ord($str[$i]);
            // Single-byte (0xxxxxx)
            if (!($c & 128)) {
                $n++;
            } elseif (($c & 192) === 192) {
                // Multi-byte starting byte (11xxxxxx)
                $n++;
            }
        }
        return $n;
    }

    /**
     * Truncates a string in UTF-8 short at a given byte length.
     *
     * @param string $str UTF-8 multibyte character string
     * @param int $len The byte length
     * @return string The shortened string
     * @see mb_strcut()
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_strcut() directly
     */
    public function utf8_strtrunc($str, $len)
    {
        GeneralUtility::logDeprecatedFunction();
        $i = $len - 1;
        // Part of a multibyte sequence
        if (ord($str[$i]) & 128) {
            for (; $i > 0 && !(ord($str[$i]) & 64); $i--) {
            }
            if ($i <= 0) {
                return '';
            }
            $bc = 0;
            // Sanity check
            for ($mbs = ord($str[$i]); $mbs & 128; $mbs = $mbs << 1) {
                // Calculate number of bytes
                $bc++;
            }
            if ($bc + $i > $len) {
                return substr($str, 0, $i);
            }
        }
        return substr($str, 0, $len);
    }

    /**
     * Find position of first occurrence of a string, both arguments are in UTF-8.
     *
     * @param string $haystack UTF-8 string to search in
     * @param string $needle UTF-8 string to search for
     * @param int $offset Position to start the search
     * @return int The character position
     * @see strpos()
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_strpos() directly
     */
    public function utf8_strpos($haystack, $needle, $offset = 0)
    {
        GeneralUtility::logDeprecatedFunction();
        return mb_strpos($haystack, $needle, $offset, 'utf-8');
    }

    /**
     * Find position of last occurrence of a char in a string, both arguments are in UTF-8.
     *
     * @param string $haystack UTF-8 string to search in
     * @param string $needle UTF-8 character to search for (single character)
     * @return int The character position
     * @see strrpos()
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_strrpos() directly
     */
    public function utf8_strrpos($haystack, $needle)
    {
        GeneralUtility::logDeprecatedFunction();
        return mb_strrpos($haystack, $needle, 'utf-8');
    }

    /**
     * Translates a character position into an 'absolute' byte position.
     * Unit tested by Kasper.
     *
     * @param string $str UTF-8 string
     * @param int $pos Character position (negative values start from the end)
     * @return int Byte position
     */
    public function utf8_char2byte_pos($str, $pos)
    {
        // Number of characters found
        $n = 0;
        // Number of characters wanted
        $p = abs($pos);
        if ($pos >= 0) {
            $i = 0;
            $d = 1;
        } else {
            $i = strlen($str) - 1;
            $d = -1;
        }
        for (; isset($str[$i]) && $n < $p; $i += $d) {
            $c = (int)ord($str[$i]);
            // single-byte (0xxxxxx)
            if (!($c & 128)) {
                $n++;
            } elseif (($c & 192) === 192) {
                // Multi-byte starting byte (11xxxxxx)
                $n++;
            }
        }
        if (!isset($str[$i])) {
            // Offset beyond string length
            return false;
        }
        if ($pos >= 0) {
            // Skip trailing multi-byte data bytes
            while (ord($str[$i]) & 128 && !(ord($str[$i]) & 64)) {
                $i++;
            }
        } else {
            // Correct offset
            $i++;
        }
        return $i;
    }

    /**
     * Translates an 'absolute' byte position into a character position.
     * Unit tested by Kasper.
     *
     * @param string $str UTF-8 string
     * @param int $pos Byte position
     * @return int Character position
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, former internal function only
     */
    public function utf8_byte2char_pos($str, $pos)
    {
        GeneralUtility::logDeprecatedFunction();
        // Number of characters
        $n = 0;
        $i = $pos;
        for (; $i > 0; $i--) {
            $c = (int)ord($str[$i]);
            // single-byte (0xxxxxx)
            if (!($c & 128)) {
                $n++;
            } elseif (($c & 192) === 192) {
                // Multi-byte starting byte (11xxxxxx)
                $n++;
            }
        }
        if (!isset($str[$i])) {
            // Offset beyond string length
            return false;
        }
        return $n;
    }

    /**
     * Maps all characters of an UTF-8 string.
     *
     * @param string $str UTF-8 string
     * @param string $mode Mode: 'case' (case folding) or 'ascii' (ASCII transliteration)
     * @param string $opt 'case': conversion 'toLower' or 'toUpper'
     * @return string The converted string
     */
    public function utf8_char_mapping($str, $mode, $opt = '')
    {
        if (!$this->initUnicodeData($mode)) {
            // Do nothing
            return $str;
        }
        $out = '';
        switch ($mode) {
            case 'case':
                $map = &$this->caseFolding['utf-8'][$opt];
                break;
            case 'ascii':
                $map = &$this->toASCII['utf-8'];
                break;
            default:
                return $str;
        }
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
     * Cuts a string in the EUC charset family short at a given byte length.
     *
     * @param string $str EUC multibyte character string
     * @param int $len The byte length
     * @param string $charset The charset
     * @return string The shortened string
     * @see mb_strcut()
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_strcut() directly
     */
    public function euc_strtrunc($str, $len, $charset)
    {
        GeneralUtility::logDeprecatedFunction();
        $shiftJis = $charset === 'shift_jis';
        $i = 0;
        for (; isset($str[$i]) && $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($shiftJis) {
                if ($c >= 128 && $c < 160 || $c >= 224) {
                    $i++;
                }
            } else {
                if ($c >= 128) {
                    $i++;
                }
            }
        }
        if (!isset($str[$i])) {
            return $str;
        }
        // string shorter than supplied length
        if ($i > $len) {
            // We ended on a first byte
            return substr($str, 0, $len - 1);
        }
        return substr($str, 0, $len);
    }

    /**
     * Returns a part of a string in the EUC charset family.
     *
     * @param string $str EUC multibyte character string
     * @param int $start Start position (character position)
     * @param string $charset The charset
     * @param int $len Length (in characters)
     * @return string the substring
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_substr() directly
     */
    public function euc_substr($str, $start, $charset, $len = null)
    {
        GeneralUtility::logDeprecatedFunction();
        $byte_start = $this->euc_char2byte_pos($str, $start, $charset);
        if ($byte_start === false) {
            // $start outside string length
            return false;
        }
        $str = substr($str, $byte_start);
        if ($len != null) {
            $byte_end = $this->euc_char2byte_pos($str, $len, $charset);
            // $len outside actual string length
            if ($byte_end === false) {
                return $str;
            }
            return substr($str, 0, $byte_end);
        }
        return $str;
    }

    /**
     * Counts the number of characters of a string in the EUC charset family.
     *
     * @param string $str EUC multibyte character string
     * @param string $charset The charset
     * @return int The number of characters
     * @see strlen()
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, use mb_strlen() directly
     */
    public function euc_strlen($str, $charset)
    {
        GeneralUtility::logDeprecatedFunction();
        $sjis = $charset === 'shift_jis';
        $n = 0;
        for ($i = 0; isset($str[$i]); $i++) {
            $c = ord($str[$i]);
            if ($sjis) {
                if ($c >= 128 && $c < 160 || $c >= 224) {
                    $i++;
                }
            } else {
                if ($c >= 128) {
                    $i++;
                }
            }
            $n++;
        }
        return $n;
    }

    /**
     * Translates a character position into an 'absolute' byte position.
     *
     * @param string $str EUC multibyte character string
     * @param int $pos Character position (negative values start from the end)
     * @param string $charset The charset
     * @return int Byte position
     * @deprecated since TYPO3 v8, will be removed with TYPO3 v9, former internal function only
     */
    public function euc_char2byte_pos($str, $pos, $charset)
    {
        GeneralUtility::logDeprecatedFunction();
        $sjis = $charset === 'shift_jis';
        // Number of characters seen
        $n = 0;
        // Number of characters wanted
        $p = abs($pos);
        if ($pos >= 0) {
            $i = 0;
            $d = 1;
        } else {
            $i = strlen($str) - 1;
            $d = -1;
        }
        for (; isset($str[$i]) && $n < $p; $i += $d) {
            $c = ord($str[$i]);
            if ($sjis) {
                if ($c >= 128 && $c < 160 || $c >= 224) {
                    $i += $d;
                }
            } else {
                if ($c >= 128) {
                    $i += $d;
                }
            }
            $n++;
        }
        if (!isset($str[$i])) {
            return false;
        }
        // offset beyond string length
        if ($pos < 0) {
            $i++;
        }
        // correct offset
        return $i;
    }

    /**
     * Maps all characters of a string in the EUC charset family.
     *
     * @param string $str EUC multibyte character string
     * @param string $charset The charset
     * @param string $mode Mode: 'case' (case folding) or 'ascii' (ASCII transliteration)
     * @param string $opt 'case': conversion 'toLower' or 'toUpper'
     * @return string The converted string
     */
    public function euc_char_mapping($str, $charset, $mode, $opt = '')
    {
        switch ($mode) {
            case 'case':
                if (!$this->initCaseFolding($charset)) {
                    return $str;
                }
                // do nothing
                $map = &$this->caseFolding[$charset][$opt];
                break;
            case 'ascii':
                if (!$this->initToASCII($charset)) {
                    return $str;
                }
                // do nothing
                $map = &$this->toASCII[$charset];
                break;
            default:
                return $str;
        }
        $sjis = $charset === 'shift_jis';
        $out = '';
        for ($i = 0; isset($str[$i]); $i++) {
            $mbc = $str[$i];
            $c = ord($mbc);
            if ($sjis) {
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
