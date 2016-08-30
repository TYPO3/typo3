<?php
namespace TYPO3\CMS\Core\Encoder;

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
 * Adopted from OWASP Enterprise Security API (ESAPI) reference implementation for the JavaScript Codec.
 * Original Author: Mike Boberski
 *
 * This class provides encoding for user input that is intended to be used in a JavaScript context.
 * It encodes all characters except alphanumericals and the immune characters to a hex representation.
 * @copyright 2009-2010 The OWASP Foundation
 * @link http://www.owasp.org/index.php/ESAPI
 */
class JavaScriptEncoder implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * A map where the keys are ordinal values of non-alphanumeric single-byte
     * characters and the values are hexadecimal equivalents as strings.
     *
     * @var array
     */
    protected $hexMatrix = [];

    /**
     * Characters that are immune (not dangerous) in the JavaScript context
     *
     * @var array
     */
    protected $immuneCharacters = [',', '.', '_'];

    /**
     * TYPO3 charset encoding object
     *
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    protected $charsetConversion = null;

    /**
     * Populates the $hex map of non-alphanumeric single-byte characters.
     *
     * Alphanumerical character are set to NULL in the matrix.
     */
    public function __construct()
    {
        $this->charsetConversion = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
        for ($i = 0; $i < 256; $i++) {
            if ($i >= ord('0') && $i <= ord('9') || $i >= ord('A') && $i <= ord('Z') || $i >= ord('a') && $i <= ord('z')) {
                $this->hexMatrix[$i] = null;
            } else {
                $this->hexMatrix[$i] = dechex($i);
            }
        }
    }

    /**
     * Encodes a string for JavaScript.
     *
     * @param string $input The string to encode, may be empty.
     * @return string The encoded string.
     */
    public function encode($input)
    {
        $stringLength = $this->charsetConversion->strlen('utf-8', $input);
        $encodedString = '';
        for ($i = 0; $i < $stringLength; $i++) {
            $c = $this->charsetConversion->substr('utf-8', $input, $i, 1);
            $encodedString .= $this->encodeCharacter($c);
        }
        return $encodedString;
    }

    /**
     * Returns backslash encoded numeric format. Does not use backslash
     * character escapes such as, \" or \' as these may cause parsing problems.
     * For example, if a javascript attribute, such as onmouseover, contains
     * a \" that will close the entire attribute and allow an attacker to inject
     * another script attribute.
     *
     * @param string $character utf-8 character that needs to be encoded
     * @return string encoded character
     */
    protected function encodeCharacter($character)
    {
        if ($this->isImmuneCharacter($character)) {
            return $character;
        }
        $ordinalValue = $this->charsetConversion->utf8CharToUnumber($character);
        // Check for alphanumeric characters
        $hex = $this->getHexForNonAlphanumeric($ordinalValue);
        if ($hex === null) {
            return $character;
        }
        // Encode up to 256 with \\xHH
        if ($ordinalValue < 256) {
            $pad = substr('00', strlen($hex));
            return '\\x' . $pad . strtoupper($hex);
        }
        // Otherwise encode with \\uHHHH
        $pad = substr('0000', strlen($hex));
        return '\\u' . $pad . strtoupper($hex);
    }

    /**
     * Checks if the given character is one of the immune characters
     *
     * @param string $character utf-8 character to search for, must not be empty
     * @return bool TRUE if character is immune, FALSE otherwise
     */
    protected function isImmuneCharacter($character)
    {
        return in_array($character, $this->immuneCharacters, true);
    }

    /**
     * Returns the ordinal value as a hex string of any character that is not a
     * single-byte alphanumeric. The character should be supplied as a string in
     * the utf-8 character encoding.
     * If the character is an alphanumeric character with ordinal value below 255,
     * then this method will return NULL.
     *
     * @param int $ordinalValue Ordinal value of the character
     * @return string hexadecimal ordinal value of non-alphanumeric characters or NULL otherwise.
     */
    protected function getHexForNonAlphanumeric($ordinalValue)
    {
        if ($ordinalValue <= 255) {
            return $this->hexMatrix[$ordinalValue];
        }
        return dechex($ordinalValue);
    }
}
