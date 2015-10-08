<?php
namespace TYPO3\CMS\Compatibility6\Utility;

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
 * Contains some functions that were been previously found
 * inside TypoScriptFrontendController
 * but are shared between FormContentObject and TypoScriptFrontendController
 *
 */
class FormUtility
{
    /**
     * En/decodes strings with lightweight encryption and a hash containing the server encryptionKey (salt)
     * Can be used for authentication of information sent from server generated pages back to the server to establish that the server generated the page. (Like hidden fields with recipient mail addresses)
     * Encryption is mainly to avoid spam-bots to pick up information.
     *
     * @param string $string Input string to en/decode
     * @param bool $decode If set, string is decoded, not encoded.
     * @return string encoded/decoded version of $string
     */
    public static function codeString($string, $decode = false)
    {
        if ($decode) {
            list($md5Hash, $str) = explode(':', $string, 2);
            $newHash = substr(md5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . ':' . $str), 0, 10);
            if ($md5Hash === $newHash) {
                $str = base64_decode($str);
                $str = self::roundTripCryptString($str);
                return $str;
            } else {
                return false;
            }
        } else {
            $str = $string;
            $str = self::roundTripCryptString($str);
            $str = base64_encode($str);
            $newHash = substr(md5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . ':' . $str), 0, 10);
            return $newHash . ':' . $str;
        }
    }

    /**
     * Encrypts a strings by XOR'ing all characters with a key derived from the
     * TYPO3 encryption key.
     *
     * Using XOR means that the string can be decrypted by simply calling the
     * function again - just like rot-13 works (but in this case for ANY byte
     * value).
     *
     * @param string $string String to crypt, may be empty
     * @return string binary crypt string, will have the same length as $string
     */
    protected static function roundTripCryptString($string)
    {
        $out = '';
        $cleartextLength = strlen($string);
        $key = sha1($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
        $keyLength = strlen($key);
        for ($a = 0; $a < $cleartextLength; $a++) {
            $xorVal = ord($key[$a % $keyLength]);
            $out .= chr(ord($string[$a]) ^ $xorVal);
        }
        return $out;
    }
}
