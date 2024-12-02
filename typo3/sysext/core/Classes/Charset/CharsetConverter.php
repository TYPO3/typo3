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

namespace TYPO3\CMS\Core\Charset;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Class for conversion between charsets
 */
#[Autoconfigure(public: true)]
class CharsetConverter
{
    /**
     * Fallback character for chars with no equivalent.
     */
    protected const FALLBACK_CHAR = '?';

    public function __construct(
        private readonly CharsetProvider $charsetProvider
    ) {}

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
            $chr = $str[$a];
            $ord = ord($chr);
            // This means multibyte! (first byte!)
            if ($ord > 127) {
                // Since the first byte must have the 7th bit set we check that. Otherwise, we might be in the middle of a byte sequence.
                if ($ord & 64) {
                    // Add first byte
                    $buf = $chr;
                    // For each byte in multibyte string...
                    for ($b = 0; $b < 8; $b++) {
                        // Shift it left and ...
                        $ord <<= 1;
                        // ... and with 8th bit - if that is set, then there are still bytes in sequence.
                        if ($ord & 128) {
                            $a++;
                            // ... and add the next char.
                            $buf .= $str[$a];
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
     * Converts a UTF-8 Multibyte character to a UNICODE number
     * Unit-tested by Kasper
     *
     * @param string $str UTF-8 multibyte character string
     * @param bool $hex If set, then a hex. number is returned.
     * @return ($hex is false ? int : string) UNICODE integer
     */
    public function utf8CharToUnumber(string $str, bool $hex = false): int|string
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
                $ord <<= 1;
                // ... and with 8th bit - if that is set, then there are still bytes in sequence.
                if ($ord & 128) {
                    $binBuf .= substr('00000000' . decbin(ord($str[$b + 1])), -6);
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
     * Maps all characters of a UTF-8 string.
     *
     * @param string $str UTF-8 string
     */
    public function utf8_char_mapping(string $str): string
    {
        $out = '';
        for ($i = 0; isset($str[$i]); $i++) {
            $c = ord($str[$i]);
            $mbc = '';
            // single-byte (0xxxxxx)
            if (!($c & 128)) {
                $mbc = $str[$i];
            } elseif (($c & 192) === 192) {
                $bc = 0;
                // multibyte starting byte (11xxxxxx)
                for (; $c & 128; $c <<= 1) {
                    $bc++;
                }
                // calculate number of bytes
                $mbc = substr($str, $i, $bc);
                $i += $bc - 1;
            }
            if ($this->charsetProvider->hasMultibyteChar('utf-8', $mbc)) {
                $out .= $this->charsetProvider->getByMultibyteChar('utf-8', $mbc);
            } else {
                $out .= $mbc;
            }
        }
        return $out;
    }
}
