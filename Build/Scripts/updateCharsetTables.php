#!/usr/bin/env php
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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (PHP_SAPI !== 'cli') {
    die('Script must be called from command line.' . chr(10));
}

require __DIR__ . '/../../vendor/autoload.php';

final readonly class CharsetUpdater
{
    /**
     * @see http://www.unicode.org/Public/UNIDATA/
     */
    private const CHARSET_DIRECTORY = '../Sources/Charsets/';

    public function generate(): void
    {
        $absolutePath = realpath(__DIR__ . '/' . static::CHARSET_DIRECTORY);
        $handle = opendir($absolutePath);
        if ($handle === false) {
            throw new \RuntimeException('Cannot open dir ' . $absolutePath, 1733156704);
        }

        $fh = fopen($absolutePath . '/unicode-data.txt', 'rb');
        if (!$fh) {
            throw new \RuntimeException('Cannot open file ' . $absolutePath . '/unicode-data.txt', 1733157167);
        }

        $toASCII = [];
        $decomposition = [];
        $mark = [];
        $number = [];
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

        $fh = fopen($absolutePath . '/translit.txt', 'rb');
        if (!$fh) {
            throw new \RuntimeException('Cannot open file ' . $absolutePath . '/translit.txt', 1733157351);
        }

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

        // Decompose and remove marks
        foreach ($decomposition as $from => $to) {
            $codeDecomposition = [];
            while ($code_value = array_shift($to)) {
                // Do recursive decomposition
                if (isset($decomposition['U+' . $code_value])) {
                    foreach (array_reverse($decomposition['U+' . $code_value]) as $cv) {
                        array_unshift($to, $cv);
                    }
                } elseif (!isset($mark['U+' . $code_value])) {
                    // remove mark
                    $codeDecomposition[] = $code_value;
                }
            }
            if (!empty($codeDecomposition) || isset($omit[$from])) {
                $decomposition[$from] = $codeDecomposition;
            } else {
                unset($decomposition[$from]);
            }
        }
        // Create ascii only mapping
        $toASCII['utf-8'] = [];
        foreach ($decomposition as $from => $to) {
            $codeDecomposition = [];
            while ($code_value = array_shift($to)) {
                $ord = (int)hexdec($code_value);
                if ($ord > 127) {
                    continue 2;
                }
                // Skip decompositions containing non-ASCII chars
                $codeDecomposition[] = chr($ord);
            }
            $toASCII['utf-8'][$this->unicodeNumberToChar((int)hexdec(substr($from, 2)))] = implode('', $codeDecomposition);
        }
        // Add numeric decompositions
        foreach ($number as $from => $to) {
            $utf8_char = $this->unicodeNumberToChar((int)hexdec(substr($from, 2)));
            if (!isset($toASCII['utf-8'][$utf8_char])) {
                $toASCII['utf-8'][$utf8_char] = $to;
            }
        }

        $charsetProviderFileLocation = __DIR__ . '/../../typo3/sysext/core/Classes/Charset/CharsetProvider.php';
        $this->updatePhpFile($charsetProviderFileLocation, $toASCII);
    }

    /**
     * Converts a UNICODE number to a UTF-8 multibyte character
     * Algorithm based on script found at From: http://czyborra.com/utf/
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
     */
    private function unicodeNumberToChar(int $unicodeInteger): string
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
            $str .= '?';
        }
        return $str;
    }

    private function updatePhpFile(string $fileLocation, array $contents): void
    {
        ksort($contents, SORT_NATURAL);
        $fileContents = file_get_contents($fileLocation);
        $newFormattedData = ArrayUtility::arrayExport($contents);
        $newFormattedData = str_replace("\n", "\n    ", $newFormattedData);
        $newFileContents = preg_replace('/private const RAW_DATA = (?:(?!];).)++];/us', 'private const RAW_DATA = ' . addcslashes($newFormattedData, '\\') . ';', $fileContents);
        file_put_contents($fileLocation, $newFileContents);
    }
}

(new CharsetUpdater())->generate();
