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

namespace TYPO3\CMS\Core\Utility;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use GuzzleHttp\Exception\RequestException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Middleware\VerifyHostHeader;
use TYPO3\CMS\Core\Package\Exception as PackageException;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * The legendary "t3lib_div" class - Miscellaneous functions for general purpose.
 * Most of the functions do not relate specifically to TYPO3
 * However a section of functions requires certain TYPO3 features available
 * See comments in the source.
 * You are encouraged to use this library in your own scripts!
 *
 * USE:
 * All methods in this class are meant to be called statically.
 * So use \TYPO3\CMS\Core\Utility\GeneralUtility::[method-name] to refer to the functions, eg. '\TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds()'
 */
class GeneralUtility
{
    /* @deprecated since v11, will be removed in v12. */
    const ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL = '.*';
    /* @deprecated since v11, will be removed in v12. */
    const ENV_TRUSTED_HOSTS_PATTERN_SERVER_NAME = 'SERVER_NAME';

    /**
     * @var ContainerInterface|null
     */
    protected static $container;

    /**
     * Singleton instances returned by makeInstance, using the class names as
     * array keys
     *
     * @var array<string, SingletonInterface>
     */
    protected static $singletonInstances = [];

    /**
     * Instances returned by makeInstance, using the class names as array keys
     *
     * @var array<string, array<object>>
     */
    protected static $nonSingletonInstances = [];

    /**
     * Cache for makeInstance with given class name and final class names to reduce number of self::getClassName() calls
     *
     * @var array<string, class-string> Given class name => final class name
     */
    protected static $finalClassNameCache = [];

    /**
     * @var array<string, string|bool|array<string, string|bool|null>|null>
     */
    protected static $indpEnvCache = [];

    final private function __construct()
    {
    }

    /*************************
     *
     * GET/POST Variables
     *
     * Background:
     * Input GET/POST variables in PHP may have their quotes escaped with "\" or not depending on configuration.
     * TYPO3 has always converted quotes to BE escaped if the configuration told that they would not be so.
     * But the clean solution is that quotes are never escaped and that is what the functions below offers.
     * Eventually TYPO3 should provide this in the global space as well.
     * In the transitional phase (or forever..?) we need to encourage EVERY to read and write GET/POST vars through the API functions below.
     * This functionality was previously needed to normalize between magic quotes logic, which was removed from PHP 5.4,
     * so these methods are still in use, but not tackle the slash problem anymore.
     *
     *************************/
    /**
     * Returns the 'GLOBAL' value of incoming data from POST or GET, with priority to POST, which is equivalent to 'GP' order
     * In case you already know by which method your data is arriving consider using GeneralUtility::_GET or GeneralUtility::_POST.
     *
     * @param string $var GET/POST var to return
     * @return mixed POST var named $var, if not set, the GET var of the same name and if also not set, NULL.
     */
    public static function _GP($var)
    {
        if (empty($var)) {
            return;
        }

        $value = $_POST[$var] ?? $_GET[$var] ?? null;

        // This is there for backwards-compatibility, in order to avoid NULL
        if (isset($value) && !is_array($value)) {
            $value = (string)$value;
        }
        return $value;
    }

    /**
     * Returns the global arrays $_GET and $_POST merged with $_POST taking precedence.
     *
     * @param string $parameter Key (variable name) from GET or POST vars
     * @return array Returns the GET vars merged recursively onto the POST vars.
     */
    public static function _GPmerged($parameter)
    {
        $postParameter = isset($_POST[$parameter]) && is_array($_POST[$parameter]) ? $_POST[$parameter] : [];
        $getParameter = isset($_GET[$parameter]) && is_array($_GET[$parameter]) ? $_GET[$parameter] : [];
        $mergedParameters = $getParameter;
        ArrayUtility::mergeRecursiveWithOverrule($mergedParameters, $postParameter);
        return $mergedParameters;
    }

    /**
     * Returns the global $_GET array (or value from) normalized to contain un-escaped values.
     * This function was previously used to normalize between magic quotes logic, which was removed from PHP 5.5
     *
     * @param string $var Optional pointer to value in GET array (basically name of GET var)
     * @return mixed If $var is set it returns the value of $_GET[$var]. If $var is NULL (default), returns $_GET itself.
     * @see _POST()
     * @see _GP()
     */
    public static function _GET($var = null)
    {
        $value = $var === null
            ? $_GET
            : (empty($var) ? null : ($_GET[$var] ?? null));
        // This is there for backwards-compatibility, in order to avoid NULL
        if (isset($value) && !is_array($value)) {
            $value = (string)$value;
        }
        return $value;
    }

    /**
     * Returns the global $_POST array (or value from) normalized to contain un-escaped values.
     *
     * @param string $var Optional pointer to value in POST array (basically name of POST var)
     * @return mixed If $var is set it returns the value of $_POST[$var]. If $var is NULL (default), returns $_POST itself.
     * @see _GET()
     * @see _GP()
     */
    public static function _POST($var = null)
    {
        $value = $var === null ? $_POST : (empty($var) || !isset($_POST[$var]) ? null : $_POST[$var]);
        // This is there for backwards-compatibility, in order to avoid NULL
        if (isset($value) && !is_array($value)) {
            $value = (string)$value;
        }
        return $value;
    }

    /*************************
     *
     * STRING FUNCTIONS
     *
     *************************/
    /**
     * Truncates a string with appended/prepended "..." and takes current character set into consideration.
     *
     * @param string $string String to truncate
     * @param int $chars Must be an integer with an absolute value of at least 4. if negative the string is cropped from the right end.
     * @param string $appendString Appendix to the truncated string
     * @return string Cropped string
     * @todo Add strict types and return types as breaking change in v12.
     */
    public static function fixed_lgd_cs($string, $chars, $appendString = '...')
    {
        $string = (string)$string;
        if ((int)$chars === 0 || mb_strlen($string, 'utf-8') <= abs($chars)) {
            return $string;
        }
        if ($chars > 0) {
            $string = mb_substr($string, 0, $chars, 'utf-8') . $appendString;
        } else {
            $string = $appendString . mb_substr($string, $chars, mb_strlen($string, 'utf-8'), 'utf-8');
        }
        return $string;
    }

    /**
     * Match IP number with list of numbers with wildcard
     * Dispatcher method for switching into specialised IPv4 and IPv6 methods.
     *
     * @param string $baseIP Is the current remote IP address for instance, typ. REMOTE_ADDR
     * @param string $list Is a comma-list of IP-addresses to match with. CIDR-notation should be used. For IPv4 addresses only, the *-wildcard is also allowed instead of number, plus leaving out parts in the IP number is accepted as wildcard (eg. 192.168.*.* equals 192.168). If list is "*" no check is done and the function returns TRUE immediately. An empty list always returns FALSE.
     * @return bool TRUE if an IP-mask from $list matches $baseIP
     */
    public static function cmpIP($baseIP, $list)
    {
        $list = trim($list);
        if ($list === '') {
            return false;
        }
        if ($list === '*') {
            return true;
        }
        if (str_contains($baseIP, ':') && self::validIPv6($baseIP)) {
            return self::cmpIPv6($baseIP, $list);
        }
        return self::cmpIPv4($baseIP, $list);
    }

    /**
     * Match IPv4 number with list of numbers with wildcard
     *
     * @param string $baseIP Is the current remote IP address for instance, typ. REMOTE_ADDR
     * @param string $list Is a comma-list of IP-addresses to match with. CIDR-notation, *-wildcard allowed instead of number, plus leaving out parts in the IP number is accepted as wildcard (eg. 192.168.0.0/16 equals 192.168.*.* equals 192.168), could also contain IPv6 addresses
     * @return bool TRUE if an IP-mask from $list matches $baseIP
     */
    public static function cmpIPv4($baseIP, $list)
    {
        $IPpartsReq = explode('.', $baseIP);
        if (count($IPpartsReq) === 4) {
            $values = self::trimExplode(',', $list, true);
            foreach ($values as $test) {
                $testList = explode('/', $test);
                if (count($testList) === 2) {
                    [$test, $mask] = $testList;
                } else {
                    $mask = false;
                }
                if ((int)$mask) {
                    $mask = (int)$mask;
                    // "192.168.3.0/24"
                    $lnet = (int)ip2long($test);
                    $lip = (int)ip2long($baseIP);
                    $binnet = str_pad(decbin($lnet), 32, '0', STR_PAD_LEFT);
                    $firstpart = substr($binnet, 0, $mask);
                    $binip = str_pad(decbin($lip), 32, '0', STR_PAD_LEFT);
                    $firstip = substr($binip, 0, $mask);
                    $yes = $firstpart === $firstip;
                } else {
                    // "192.168.*.*"
                    $IPparts = explode('.', $test);
                    $yes = 1;
                    foreach ($IPparts as $index => $val) {
                        $val = trim($val);
                        if ($val !== '*' && $IPpartsReq[$index] !== $val) {
                            $yes = 0;
                        }
                    }
                }
                if ($yes) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Match IPv6 address with a list of IPv6 prefixes
     *
     * @param string $baseIP Is the current remote IP address for instance
     * @param string $list Is a comma-list of IPv6 prefixes, could also contain IPv4 addresses. IPv6 addresses
     *   must be specified in CIDR-notation, not with * wildcard, otherwise self::validIPv6() will fail.
     * @return bool TRUE If a baseIP matches any prefix
     */
    public static function cmpIPv6($baseIP, $list)
    {
        // Policy default: Deny connection
        $success = false;
        $baseIP = self::normalizeIPv6($baseIP);
        $values = self::trimExplode(',', $list, true);
        foreach ($values as $test) {
            $testList = explode('/', $test);
            if (count($testList) === 2) {
                [$test, $mask] = $testList;
            } else {
                $mask = false;
            }
            if (self::validIPv6($test)) {
                $test = self::normalizeIPv6($test);
                $maskInt = (int)$mask ?: 128;
                // Special case; /0 is an allowed mask - equals a wildcard
                if ($mask === '0') {
                    $success = true;
                } elseif ($maskInt == 128) {
                    $success = $test === $baseIP;
                } else {
                    $testBin = (string)inet_pton($test);
                    $baseIPBin = (string)inet_pton($baseIP);

                    $success = true;
                    // Modulo is 0 if this is a 8-bit-boundary
                    $maskIntModulo = $maskInt % 8;
                    $numFullCharactersUntilBoundary = (int)($maskInt / 8);
                    $substring = (string)substr($baseIPBin, 0, $numFullCharactersUntilBoundary);
                    if (strpos($testBin, $substring) !== 0) {
                        $success = false;
                    } elseif ($maskIntModulo > 0) {
                        // If not an 8-bit-boundary, check bits of last character
                        $testLastBits = str_pad(decbin(ord(substr($testBin, $numFullCharactersUntilBoundary, 1))), 8, '0', STR_PAD_LEFT);
                        $baseIPLastBits = str_pad(decbin(ord(substr($baseIPBin, $numFullCharactersUntilBoundary, 1))), 8, '0', STR_PAD_LEFT);
                        if (strncmp($testLastBits, $baseIPLastBits, $maskIntModulo) != 0) {
                            $success = false;
                        }
                    }
                }
            }
            if ($success) {
                return true;
            }
        }
        return false;
    }

    /**
     * Normalize an IPv6 address to full length
     *
     * @param string $address Given IPv6 address
     * @return string Normalized address
     */
    public static function normalizeIPv6($address)
    {
        $normalizedAddress = '';
        // According to RFC lowercase-representation is recommended
        $address = strtolower($address);
        // Normalized representation has 39 characters (0000:0000:0000:0000:0000:0000:0000:0000)
        if (strlen($address) === 39) {
            // Already in full expanded form
            return $address;
        }
        // Count 2 if if address has hidden zero blocks
        $chunks = explode('::', $address);
        if (count($chunks) === 2) {
            $chunksLeft = explode(':', $chunks[0]);
            $chunksRight = explode(':', $chunks[1]);
            $left = count($chunksLeft);
            $right = count($chunksRight);
            // Special case: leading zero-only blocks count to 1, should be 0
            if ($left === 1 && strlen($chunksLeft[0]) === 0) {
                $left = 0;
            }
            $hiddenBlocks = 8 - ($left + $right);
            $hiddenPart = '';
            $h = 0;
            while ($h < $hiddenBlocks) {
                $hiddenPart .= '0000:';
                $h++;
            }
            if ($left === 0) {
                $stageOneAddress = $hiddenPart . $chunks[1];
            } else {
                $stageOneAddress = $chunks[0] . ':' . $hiddenPart . $chunks[1];
            }
        } else {
            $stageOneAddress = $address;
        }
        // Normalize the blocks:
        $blocks = explode(':', $stageOneAddress);
        $divCounter = 0;
        foreach ($blocks as $block) {
            $tmpBlock = '';
            $i = 0;
            $hiddenZeros = 4 - strlen($block);
            while ($i < $hiddenZeros) {
                $tmpBlock .= '0';
                $i++;
            }
            $normalizedAddress .= $tmpBlock . $block;
            if ($divCounter < 7) {
                $normalizedAddress .= ':';
                $divCounter++;
            }
        }
        return $normalizedAddress;
    }

    /**
     * Validate a given IP address.
     *
     * Possible format are IPv4 and IPv6.
     *
     * @param string $ip IP address to be tested
     * @return bool TRUE if $ip is either of IPv4 or IPv6 format.
     */
    public static function validIP($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate a given IP address to the IPv4 address format.
     *
     * Example for possible format: 10.0.45.99
     *
     * @param string $ip IP address to be tested
     * @return bool TRUE if $ip is of IPv4 format.
     */
    public static function validIPv4($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate a given IP address to the IPv6 address format.
     *
     * Example for possible format: 43FB::BB3F:A0A0:0 | ::1
     *
     * @param string $ip IP address to be tested
     * @return bool TRUE if $ip is of IPv6 format.
     */
    public static function validIPv6($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Match fully qualified domain name with list of strings with wildcard
     *
     * @param string $baseHost A hostname or an IPv4/IPv6-address (will by reverse-resolved; typically REMOTE_ADDR)
     * @param string $list A comma-list of domain names to match with. *-wildcard allowed but cannot be part of a string, so it must match the full host name (eg. myhost.*.com => correct, myhost.*domain.com => wrong)
     * @return bool TRUE if a domain name mask from $list matches $baseIP
     */
    public static function cmpFQDN($baseHost, $list)
    {
        $baseHost = trim($baseHost);
        if (empty($baseHost)) {
            return false;
        }
        if (self::validIPv4($baseHost) || self::validIPv6($baseHost)) {
            // Resolve hostname
            // Note: this is reverse-lookup and can be randomly set as soon as somebody is able to set
            // the reverse-DNS for his IP (security when for example used with REMOTE_ADDR)
            $baseHostName = (string)gethostbyaddr($baseHost);
            if ($baseHostName === $baseHost) {
                // Unable to resolve hostname
                return false;
            }
        } else {
            $baseHostName = $baseHost;
        }
        $baseHostNameParts = explode('.', $baseHostName);
        $values = self::trimExplode(',', $list, true);
        foreach ($values as $test) {
            $hostNameParts = explode('.', $test);
            // To match hostNameParts can only be shorter (in case of wildcards) or equal
            $hostNamePartsCount = count($hostNameParts);
            $baseHostNamePartsCount = count($baseHostNameParts);
            if ($hostNamePartsCount > $baseHostNamePartsCount) {
                continue;
            }
            $yes = true;
            foreach ($hostNameParts as $index => $val) {
                $val = trim($val);
                if ($val === '*') {
                    // Wildcard valid for one or more hostname-parts
                    $wildcardStart = $index + 1;
                    // Wildcard as last/only part always matches, otherwise perform recursive checks
                    if ($wildcardStart < $hostNamePartsCount) {
                        $wildcardMatched = false;
                        $tempHostName = implode('.', array_slice($hostNameParts, $index + 1));
                        while ($wildcardStart < $baseHostNamePartsCount && !$wildcardMatched) {
                            $tempBaseHostName = implode('.', array_slice($baseHostNameParts, $wildcardStart));
                            $wildcardMatched = self::cmpFQDN($tempBaseHostName, $tempHostName);
                            $wildcardStart++;
                        }
                        if ($wildcardMatched) {
                            // Match found by recursive compare
                            return true;
                        }
                        $yes = false;
                    }
                } elseif ($baseHostNameParts[$index] !== $val) {
                    // In case of no match
                    $yes = false;
                }
            }
            if ($yes) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if a given URL matches the host that currently handles this HTTP request.
     * Scheme, hostname and (optional) port of the given URL are compared.
     *
     * @param string $url URL to compare with the TYPO3 request host
     * @return bool Whether the URL matches the TYPO3 request host
     */
    public static function isOnCurrentHost($url)
    {
        return stripos($url . '/', self::getIndpEnv('TYPO3_REQUEST_HOST') . '/') === 0;
    }

    /**
     * Check for item in list
     * Check if an item exists in a comma-separated list of items.
     *
     * @param string $list Comma-separated list of items (string)
     * @param string $item Item to check for
     * @return bool TRUE if $item is in $list
     */
    public static function inList($list, $item)
    {
        return str_contains(',' . $list . ',', ',' . $item . ',');
    }

    /**
     * Removes an item from a comma-separated list of items.
     *
     * If $element contains a comma, the behaviour of this method is undefined.
     * Empty elements in the list are preserved.
     *
     * @param string $element Element to remove
     * @param string $list Comma-separated list of items (string)
     * @return string New comma-separated list of items
     * @deprecated since v11, will be removed in v12.
     */
    public static function rmFromList($element, $list)
    {
        trigger_error(
            'GeneralUtility::rmFromList() is deprecated and will be removed in v12.',
            E_USER_DEPRECATED
        );

        $items = explode(',', $list);
        foreach ($items as $k => $v) {
            if ($v == $element) {
                unset($items[$k]);
            }
        }
        return implode(',', $items);
    }

    /**
     * Expand a comma-separated list of integers with ranges (eg 1,3-5,7 becomes 1,3,4,5,7).
     * Ranges are limited to 1000 values per range.
     *
     * @param string $list Comma-separated list of integers with ranges (string)
     * @return string New comma-separated list of items
     */
    public static function expandList($list)
    {
        $items = explode(',', $list);
        $list = [];
        foreach ($items as $item) {
            $range = explode('-', $item);
            if (isset($range[1])) {
                $runAwayBrake = 1000;
                for ($n = $range[0]; $n <= $range[1]; $n++) {
                    $list[] = $n;
                    $runAwayBrake--;
                    if ($runAwayBrake <= 0) {
                        break;
                    }
                }
            } else {
                $list[] = $item;
            }
        }
        return implode(',', $list);
    }

    /**
     * Makes a positive integer hash out of the first 7 chars from the md5 hash of the input
     *
     * @param string $str String to md5-hash
     * @return int Returns 28bit integer-hash
     */
    public static function md5int($str)
    {
        return hexdec(substr(md5($str), 0, 7));
    }

    /**
     * Returns the first 10 positions of the MD5-hash		(changed from 6 to 10 recently)
     *
     * @param string $input Input string to be md5-hashed
     * @param int $len The string-length of the output
     * @return string Substring of the resulting md5-hash, being $len chars long (from beginning)
     * @deprecated since v11, will be removed in v12.
     */
    public static function shortMD5($input, $len = 10)
    {
        trigger_error(__METHOD__ . ' will be removed in TYPO3 v12, use md5() instead.', E_USER_DEPRECATED);
        return substr(md5($input), 0, $len);
    }

    /**
     * Returns a proper HMAC on a given input string and secret TYPO3 encryption key.
     *
     * @param string $input Input string to create HMAC from
     * @param string $additionalSecret additionalSecret to prevent hmac being used in a different context
     * @return string resulting (hexadecimal) HMAC currently with a length of 40 (HMAC-SHA-1)
     */
    public static function hmac($input, $additionalSecret = '')
    {
        $hashAlgorithm = 'sha1';
        $hashBlocksize = 64;
        $secret = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . $additionalSecret;
        if (extension_loaded('hash') && function_exists('hash_hmac') && function_exists('hash_algos') && in_array($hashAlgorithm, hash_algos())) {
            $hmac = hash_hmac($hashAlgorithm, $input, $secret);
        } else {
            // Outer padding
            $opad = str_repeat(chr(92), $hashBlocksize);
            // Inner padding
            $ipad = str_repeat(chr(54), $hashBlocksize);
            if (strlen($secret) > $hashBlocksize) {
                // Keys longer than block size are shorten
                $key = str_pad(pack('H*', $hashAlgorithm($secret)), $hashBlocksize, "\0");
            } else {
                // Keys shorter than block size are zero-padded
                $key = str_pad($secret, $hashBlocksize, "\0");
            }
            $hmac = $hashAlgorithm(($key ^ $opad) . pack('H*', $hashAlgorithm(($key ^ $ipad) . $input)));
        }
        return $hmac;
    }

    /**
     * Takes comma-separated lists and arrays and removes all duplicates
     * If a value in the list is trim(empty), the value is ignored.
     *
     * @param string $in_list Accept multiple parameters which can be comma-separated lists of values and arrays.
     * @param mixed $secondParameter Dummy field, which if set will show a warning!
     * @return string Returns the list without any duplicates of values, space around values are trimmed
     * @deprecated since TYPO3 v11, will be removed in TYPO3 v12. Use StringUtility::uniqueList() instead.
     */
    public static function uniqueList($in_list, $secondParameter = null)
    {
        trigger_error(
            'GeneralUtility::uniqueList() is deprecated and will be removed in v12. Use StringUtility::uniqueList() instead.',
            E_USER_DEPRECATED
        );
        if (is_array($in_list)) {
            throw new \InvalidArgumentException('TYPO3 Fatal Error: TYPO3\\CMS\\Core\\Utility\\GeneralUtility::uniqueList() does NOT support array arguments anymore! Only string comma lists!', 1270853885);
        }
        if (isset($secondParameter)) {
            throw new \InvalidArgumentException('TYPO3 Fatal Error: TYPO3\\CMS\\Core\\Utility\\GeneralUtility::uniqueList() does NOT support more than a single argument value anymore. You have specified more than one!', 1270853886);
        }
        return implode(',', array_unique(self::trimExplode(',', $in_list, true)));
    }

    /**
     * Splits a reference to a file in 5 parts
     *
     * @param string $fileNameWithPath File name with path to be analyzed (must exist if open_basedir is set)
     * @return array<string, string> Contains keys [path], [file], [filebody], [fileext], [realFileext]
     */
    public static function split_fileref($fileNameWithPath)
    {
        $info = [];
        $reg = [];
        if (preg_match('/(.*\\/)(.*)$/', $fileNameWithPath, $reg)) {
            $info['path'] = $reg[1];
            $info['file'] = $reg[2];
        } else {
            $info['path'] = '';
            $info['file'] = $fileNameWithPath;
        }
        $reg = '';
        // If open_basedir is set and the fileName was supplied without a path the is_dir check fails
        if (!is_dir($fileNameWithPath) && preg_match('/(.*)\\.([^\\.]*$)/', $info['file'], $reg)) {
            $info['filebody'] = $reg[1];
            $info['fileext'] = strtolower($reg[2]);
            $info['realFileext'] = $reg[2];
        } else {
            $info['filebody'] = $info['file'];
            $info['fileext'] = '';
        }
        reset($info);
        return $info;
    }

    /**
     * Returns the directory part of a path without trailing slash
     * If there is no dir-part, then an empty string is returned.
     * Behaviour:
     *
     * '/dir1/dir2/script.php' => '/dir1/dir2'
     * '/dir1/' => '/dir1'
     * 'dir1/script.php' => 'dir1'
     * 'd/script.php' => 'd'
     * '/script.php' => ''
     * '' => ''
     *
     * @param string $path Directory name / path
     * @return string Processed input value. See function description.
     */
    public static function dirname($path)
    {
        $p = self::revExplode('/', $path, 2);
        return count($p) === 2 ? $p[0] : '';
    }

    /**
     * Returns TRUE if the first part of $str matches the string $partStr
     *
     * @param string $str Full string to check
     * @param string $partStr Reference string which must be found as the "first part" of the full string
     * @return bool TRUE if $partStr was found to be equal to the first part of $str
     * @deprecated will be removed in TYPO3 v12.0. Use native PHP str_starts_with() with proper casting instead.
     */
    public static function isFirstPartOfStr($str, $partStr)
    {
        trigger_error('GeneralUtility::isFirstPartOfStr() will be removed in TYPO3 v12.0. Use PHPs str_starts_with() function instead.', E_USER_DEPRECATED);
        $str = is_array($str) ? '' : (string)$str;
        $partStr = is_array($partStr) ? '' : (string)$partStr;
        return $partStr !== '' && strpos($str, $partStr, 0) === 0;
    }

    /**
     * Formats the input integer $sizeInBytes as bytes/kilobytes/megabytes (-/K/M)
     *
     * @param int $sizeInBytes Number of bytes to format.
     * @param string $labels Binary unit name "iec", decimal unit name "si" or labels for bytes, kilo, mega, giga, and so on separated by vertical bar (|) and possibly encapsulated in "". Eg: " | K| M| G". Defaults to "iec".
     * @param int $base The unit base if not using a unit name. Defaults to 1024.
     * @return string Formatted representation of the byte number, for output.
     */
    public static function formatSize($sizeInBytes, $labels = '', $base = 0)
    {
        $defaultFormats = [
            'iec' => ['base' => 1024, 'labels' => [' ', ' Ki', ' Mi', ' Gi', ' Ti', ' Pi', ' Ei', ' Zi', ' Yi']],
            'si' => ['base' => 1000, 'labels' => [' ', ' k', ' M', ' G', ' T', ' P', ' E', ' Z', ' Y']],
        ];
        // Set labels and base:
        if (empty($labels)) {
            $labels = 'iec';
        }
        if (isset($defaultFormats[$labels])) {
            $base = $defaultFormats[$labels]['base'];
            $labelArr = $defaultFormats[$labels]['labels'];
        } else {
            $base = (int)$base;
            if ($base !== 1000 && $base !== 1024) {
                $base = 1024;
            }
            $labelArr = explode('|', str_replace('"', '', $labels));
        }
        // This is set via Site Handling and in the Locales class via setlocale()
        $localeInfo = localeconv();
        $sizeInBytes = max($sizeInBytes, 0);
        $multiplier = floor(($sizeInBytes ? log($sizeInBytes) : 0) / log($base));
        $sizeInUnits = $sizeInBytes / $base ** $multiplier;
        if ($sizeInUnits > ($base * .9)) {
            $multiplier++;
        }
        $multiplier = min($multiplier, count($labelArr) - 1);
        $sizeInUnits = $sizeInBytes / $base ** $multiplier;
        return number_format($sizeInUnits, (($multiplier > 0) && ($sizeInUnits < 20)) ? 2 : 0, $localeInfo['decimal_point'], '') . $labelArr[$multiplier];
    }

    /**
     * This splits a string by the chars in $operators (typical /+-*) and returns an array with them in
     *
     * @param string $string Input string, eg "123 + 456 / 789 - 4
     * @param string $operators Operators to split by, typically "/+-*
     * @return array<int, array<int, string>> Array with operators and operands separated.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::calc()
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder::calcOffset()
     */
    public static function splitCalc($string, $operators)
    {
        $res = [];
        $sign = '+';
        while ($string) {
            $valueLen = strcspn($string, $operators);
            $value = substr($string, 0, $valueLen);
            $res[] = [$sign, trim($value)];
            $sign = substr($string, $valueLen, 1);
            $string = substr($string, $valueLen + 1);
        }
        reset($res);
        return $res;
    }

    /**
     * Checking syntax of input email address
     *
     * @param string $email Input string to evaluate
     * @return bool Returns TRUE if the $email address (input string) is valid
     */
    public static function validEmail($email)
    {
        // Early return in case input is not a string
        if (!is_string($email)) {
            return false;
        }
        if (trim($email) !== $email) {
            return false;
        }
        if (!str_contains($email, '@')) {
            return false;
        }
        $validators = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['validators'] ?? [RFCValidation::class] as $className) {
            $validator = new $className();
            if ($validator instanceof EmailValidation) {
                $validators[] = $validator;
            }
        }
        return (new EmailValidator())->isValid($email, new MultipleValidationWithAnd($validators, MultipleValidationWithAnd::STOP_ON_ERROR));
    }

    /**
     * Returns a given string with underscores as UpperCamelCase.
     * Example: Converts blog_example to BlogExample
     *
     * @param string $string String to be converted to camel case
     * @return string UpperCamelCasedWord
     */
    public static function underscoredToUpperCamelCase($string)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($string))));
    }

    /**
     * Returns a given string with underscores as lowerCamelCase.
     * Example: Converts minimal_value to minimalValue
     *
     * @param string $string String to be converted to camel case
     * @return string lowerCamelCasedWord
     */
    public static function underscoredToLowerCamelCase($string)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($string)))));
    }

    /**
     * Returns a given CamelCasedString as a lowercase string with underscores.
     * Example: Converts BlogExample to blog_example, and minimalValue to minimal_value
     *
     * @param string $string String to be converted to lowercase underscore
     * @return string lowercase_and_underscored_string
     */
    public static function camelCaseToLowerCaseUnderscored($string)
    {
        $value = preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $string) ?? '';
        return mb_strtolower($value, 'utf-8');
    }

    /**
     * Checks if a given string is a Uniform Resource Locator (URL).
     *
     * On seriously malformed URLs, parse_url may return FALSE and emit an
     * E_WARNING.
     *
     * filter_var() requires a scheme to be present.
     *
     * http://www.faqs.org/rfcs/rfc2396.html
     * Scheme names consist of a sequence of characters beginning with a
     * lower case letter and followed by any combination of lower case letters,
     * digits, plus ("+"), period ("."), or hyphen ("-").  For resiliency,
     * programs interpreting URI should treat upper case letters as equivalent to
     * lower case in scheme names (e.g., allow "HTTP" as well as "http").
     * scheme = alpha *( alpha | digit | "+" | "-" | "." )
     *
     * Convert the domain part to punicode if it does not look like a regular
     * domain name. Only the domain part because RFC3986 specifies the the rest of
     * the url may not contain special characters:
     * https://tools.ietf.org/html/rfc3986#appendix-A
     *
     * @param string $url The URL to be validated
     * @return bool Whether the given URL is valid
     */
    public static function isValidUrl($url)
    {
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['scheme'])) {
            return false;
        }
        // HttpUtility::buildUrl() will always build urls with <scheme>://
        // our original $url might only contain <scheme>: (e.g. mail:)
        // so we convert that to the double-slashed version to ensure
        // our check against the $recomposedUrl is proper
        if (!str_starts_with($url, $parsedUrl['scheme'] . '://')) {
            $url = str_replace($parsedUrl['scheme'] . ':', $parsedUrl['scheme'] . '://', $url);
        }
        $recomposedUrl = HttpUtility::buildUrl($parsedUrl);
        if ($recomposedUrl !== $url) {
            // The parse_url() had to modify characters, so the URL is invalid
            return false;
        }
        if (isset($parsedUrl['host']) && !preg_match('/^[a-z0-9.\\-]*$/i', $parsedUrl['host'])) {
            $host = idn_to_ascii($parsedUrl['host']);
            if ($host === false) {
                return false;
            }
            $parsedUrl['host'] = $host;
        }
        return filter_var(HttpUtility::buildUrl($parsedUrl), FILTER_VALIDATE_URL) !== false;
    }

    /*************************
     *
     * ARRAY FUNCTIONS
     *
     *************************/

    /**
     * Explodes a $string delimited by $delimiter and casts each item in the array to (int).
     * Corresponds to \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(), but with conversion to integers for all values.
     *
     * @param string $delimiter Delimiter string to explode with
     * @param string $string The string to explode
     * @param bool $removeEmptyValues If set, all empty values (='') will NOT be set in output
     * @param int $limit If positive, the result will contain a maximum of limit elements,
     * @return int[] Exploded values, all converted to integers
     */
    public static function intExplode($delimiter, $string, $removeEmptyValues = false, $limit = 0)
    {
        $result = explode($delimiter, $string) ?: [];
        foreach ($result as $key => &$value) {
            if ($removeEmptyValues && ($value === '' || trim($value) === '')) {
                unset($result[$key]);
            } else {
                $value = (int)$value;
            }
        }
        unset($value);
        if ($limit !== 0) {
            if ($limit < 0) {
                $result = array_slice($result, 0, $limit);
            } elseif (count($result) > $limit) {
                $lastElements = array_slice($result, $limit - 1);
                $result = array_slice($result, 0, $limit - 1);
                $result[] = implode($delimiter, $lastElements);
            }
        }
        return $result;
    }

    /**
     * Reverse explode which explodes the string counting from behind.
     *
     * Note: The delimiter has to given in the reverse order as
     *       it is occurring within the string.
     *
     * GeneralUtility::revExplode('[]', '[my][words][here]', 2)
     *   ==> array('[my][words', 'here]')
     *
     * @param string $delimiter Delimiter string to explode with
     * @param string $string The string to explode
     * @param int $count Number of array entries
     * @return string[] Exploded values
     */
    public static function revExplode($delimiter, $string, $count = 0)
    {
        // 2 is the (currently, as of 2014-02) most-used value for $count in the core, therefore we check it first
        if ($count === 2) {
            $position = strrpos($string, strrev($delimiter));
            if ($position !== false) {
                return [substr($string, 0, $position), substr($string, $position + strlen($delimiter))];
            }
            return [$string];
        }
        if ($count <= 1) {
            return [$string];
        }
        $explodedValues = explode($delimiter, strrev($string), $count) ?: [];
        $explodedValues = array_map('strrev', $explodedValues);
        return array_reverse($explodedValues);
    }

    /**
     * Explodes a string and removes whitespace-only values.
     *
     * If $removeEmptyValues is set, then all values that contain only whitespace are removed.
     *
     * Each item will have leading and trailing whitespace removed. However, if the tail items are
     * returned as a single array item, their internal whitespace will not be modified.
     *
     * @param string $delim Delimiter string to explode with
     * @param string $string The string to explode
     * @param bool $removeEmptyValues If set, all empty values will be removed in output
     * @param int $limit If limit is set and positive, the returned array will contain a maximum of limit elements with
     *                   the last element containing the rest of string. If the limit parameter is negative, all components
     *                   except the last -limit are returned.
     * @return string[] Exploded values
     */
    public static function trimExplode($delim, $string, $removeEmptyValues = false, $limit = 0): array
    {
        $result = explode($delim, (string)$string) ?: [];
        if ($removeEmptyValues) {
            // Remove items that are just whitespace, but leave whitespace intact for the rest.
            $result = array_values(array_filter($result, static fn ($item) => trim($item) !== ''));
        }

        if ($limit === 0) {
            // Return everything.
            return array_map('trim', $result);
        }

        if ($limit < 0) {
            // Trim and return just the first $limit elements and ignore the rest.
            return array_map('trim', array_slice($result, 0, $limit));
        }

        // Fold the last length - $limit elements into a single trailing item, then trim and return the result.
        $tail = array_slice($result, $limit - 1);
        $result = array_slice($result, 0, $limit - 1);
        if ($tail) {
            $result[] = implode($delim, $tail);
        }
        return array_map('trim', $result);
    }

    /**
     * Implodes a multidim-array into GET-parameters (eg. &param[key][key2]=value2&param[key][key3]=value3)
     *
     * @param string $name Name prefix for entries. Set to blank if you wish none.
     * @param array $theArray The (multidimensional) array to implode
     * @param string $str (keep blank)
     * @param bool $skipBlank If set, parameters which were blank strings would be removed.
     * @param bool $rawurlencodeParamName If set, the param name itself (for example "param[key][key2]") would be rawurlencoded as well.
     * @return string Imploded result, fx. &param[key][key2]=value2&param[key][key3]=value3
     * @see explodeUrl2Array()
     */
    public static function implodeArrayForUrl($name, array $theArray, $str = '', $skipBlank = false, $rawurlencodeParamName = false)
    {
        foreach ($theArray as $Akey => $AVal) {
            $thisKeyName = $name ? $name . '[' . $Akey . ']' : $Akey;
            if (is_array($AVal)) {
                $str = self::implodeArrayForUrl($thisKeyName, $AVal, $str, $skipBlank, $rawurlencodeParamName);
            } else {
                $stringValue = (string)$AVal;
                if (!$skipBlank || $stringValue !== '') {
                    $parameterName = $rawurlencodeParamName ? rawurlencode($thisKeyName) : $thisKeyName;
                    $parameterValue = rawurlencode($stringValue);
                    $str .= '&' . $parameterName . '=' . $parameterValue;
                }
            }
        }
        return $str;
    }

    /**
     * Explodes a string with GETvars (eg. "&id=1&type=2&ext[mykey]=3") into an array.
     *
     * Note! If you want to use a multi-dimensional string, consider this plain simple PHP code instead:
     *
     * $result = [];
     * parse_str($queryParametersAsString, $result);
     *
     * However, if you do magic with a flat structure (e.g. keeping "ext[mykey]" as flat key in a one-dimensional array)
     * then this method is for you.
     *
     * @param string $string GETvars string
     * @return array<string, string> Array of values. All values AND keys are rawurldecoded() as they properly should be. But this means that any implosion of the array again must rawurlencode it!
     * @see implodeArrayForUrl()
     */
    public static function explodeUrl2Array($string)
    {
        $output = [];
        $p = explode('&', $string);
        foreach ($p as $v) {
            if ($v !== '') {
                [$pK, $pV] = explode('=', $v, 2);
                $output[rawurldecode($pK)] = rawurldecode($pV);
            }
        }
        return $output;
    }

    /**
     * Returns an array with selected keys from incoming data.
     * (Better read source code if you want to find out...)
     *
     * @param string $varList List of variable/key names
     * @param array $getArray Array from where to get values based on the keys in $varList
     * @param bool $GPvarAlt If set, then \TYPO3\CMS\Core\Utility\GeneralUtility::_GP() is used to fetch the value if not found (isset) in the $getArray
     * @return array Output array with selected variables.
     * @deprecated since v11, will be removed in v12.
     */
    public static function compileSelectedGetVarsFromArray($varList, array $getArray, $GPvarAlt = true)
    {
        trigger_error(
            'GeneralUtility::compileSelectedGetVarsFromArray() is deprecated and will be removed in v12.',
            E_USER_DEPRECATED
        );

        $keys = self::trimExplode(',', $varList, true);
        $outArr = [];
        foreach ($keys as $v) {
            if (isset($getArray[$v])) {
                $outArr[$v] = $getArray[$v];
            } elseif ($GPvarAlt) {
                $outArr[$v] = self::_GP($v);
            }
        }
        return $outArr;
    }

    /**
     * Removes dots "." from end of a key identifier of TypoScript styled array.
     * array('key.' => array('property.' => 'value')) --> array('key' => array('property' => 'value'))
     *
     * @param array $ts TypoScript configuration array
     * @return array TypoScript configuration array without dots at the end of all keys
     */
    public static function removeDotsFromTS(array $ts)
    {
        $out = [];
        foreach ($ts as $key => $value) {
            if (is_array($value)) {
                $key = rtrim($key, '.');
                $out[$key] = self::removeDotsFromTS($value);
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    /*************************
     *
     * HTML/XML PROCESSING
     *
     *************************/
    /**
     * Returns an array with all attributes of the input HTML tag as key/value pairs. Attributes are only lowercase a-z
     * $tag is either a whole tag (eg '<TAG OPTION ATTRIB=VALUE>') or the parameter list (ex ' OPTION ATTRIB=VALUE>')
     * If an attribute is empty, then the value for the key is empty. You can check if it existed with isset()
     *
     * @param string $tag HTML-tag string (or attributes only)
     * @param bool $decodeEntities Whether to decode HTML entities
     * @return array<string, string> Array with the attribute values.
     */
    public static function get_tag_attributes($tag, bool $decodeEntities = false)
    {
        $components = self::split_tag_attributes($tag);
        // Attribute name is stored here
        $name = '';
        $valuemode = false;
        $attributes = [];
        foreach ($components as $key => $val) {
            // Only if $name is set (if there is an attribute, that waits for a value), that valuemode is enabled. This ensures that the attribute is assigned it's value
            if ($val !== '=') {
                if ($valuemode) {
                    if ($name) {
                        $attributes[$name] = $decodeEntities ? htmlspecialchars_decode($val) : $val;
                        $name = '';
                    }
                } else {
                    if ($key = strtolower(preg_replace('/[^[:alnum:]_\\:\\-]/', '', $val) ?? '')) {
                        $attributes[$key] = '';
                        $name = $key;
                    }
                }
                $valuemode = false;
            } else {
                $valuemode = true;
            }
        }
        return $attributes;
    }

    /**
     * Returns an array with the 'components' from an attribute list from an HTML tag. The result is normally analyzed by get_tag_attributes
     * Removes tag-name if found
     *
     * @param string $tag HTML-tag string (or attributes only)
     * @return string[] Array with the attribute values.
     */
    public static function split_tag_attributes($tag)
    {
        $tag_tmp = trim(preg_replace('/^<[^[:space:]]*/', '', trim($tag)) ?? '');
        // Removes any > in the end of the string
        $tag_tmp = trim(rtrim($tag_tmp, '>'));
        $value = [];
        // Compared with empty string instead , 030102
        while ($tag_tmp !== '') {
            $firstChar = $tag_tmp[0];
            if ($firstChar === '"' || $firstChar === '\'') {
                $reg = explode($firstChar, $tag_tmp, 3);
                $value[] = $reg[1];
                $tag_tmp = trim($reg[2]);
            } elseif ($firstChar === '=') {
                $value[] = '=';
                // Removes = chars.
                $tag_tmp = trim(substr($tag_tmp, 1));
            } else {
                // There are '' around the value. We look for the next ' ' or '>'
                $reg = preg_split('/[[:space:]=]/', $tag_tmp, 2);
                $value[] = trim($reg[0]);
                $tag_tmp = trim(substr($tag_tmp, strlen($reg[0]), 1) . ($reg[1] ?? ''));
            }
        }
        reset($value);
        return $value;
    }

    /**
     * Implodes attributes in the array $arr for an attribute list in eg. and HTML tag (with quotes)
     *
     * @param array<string, string> $arr Array with attribute key/value pairs, eg. "bgcolor" => "red", "border" => "0"
     * @param bool $xhtmlSafe If set the resulting attribute list will have a) all attributes in lowercase (and duplicates weeded out, first entry taking precedence) and b) all values htmlspecialchar()'ed. It is recommended to use this switch!
     * @param bool $dontOmitBlankAttribs If TRUE, don't check if values are blank. Default is to omit attributes with blank values.
     * @return string Imploded attributes, eg. 'bgcolor="red" border="0"'
     */
    public static function implodeAttributes(array $arr, $xhtmlSafe = false, $dontOmitBlankAttribs = false)
    {
        if ($xhtmlSafe) {
            $newArr = [];
            foreach ($arr as $p => $v) {
                if (!isset($newArr[strtolower($p)])) {
                    $newArr[strtolower($p)] = htmlspecialchars((string)$v);
                }
            }
            $arr = $newArr;
        }
        $list = [];
        foreach ($arr as $p => $v) {
            if ((string)$v !== '' || $dontOmitBlankAttribs) {
                $list[] = $p . '="' . $v . '"';
            }
        }
        return implode(' ', $list);
    }

    /**
     * Wraps JavaScript code XHTML ready with <script>-tags
     * Automatic re-indenting of the JS code is done by using the first line as indent reference.
     * This is nice for indenting JS code with PHP code on the same level.
     *
     * @param string $string JavaScript code
     * @return string The wrapped JS code, ready to put into a XHTML page
     */
    public static function wrapJS($string)
    {
        if (trim($string)) {
            // remove nl from the beginning
            $string = ltrim($string, LF);
            // re-ident to one tab using the first line as reference
            $match = [];
            if (preg_match('/^(\\t+)/', $string, $match)) {
                $string = str_replace($match[1], "\t", $string);
            }
            return '<script>
/*<![CDATA[*/
' . $string . '
/*]]>*/
</script>';
        }
        return '';
    }

    /**
     * Parses XML input into a PHP array with associative keys
     *
     * @param string $string XML data input
     * @param int $depth Number of element levels to resolve the XML into an array. Any further structure will be set as XML.
     * @param array $parserOptions Options that will be passed to PHP's xml_parser_set_option()
     * @return mixed The array with the parsed structure unless the XML parser returns with an error in which case the error message string is returned.
     */
    public static function xml2tree($string, $depth = 999, $parserOptions = [])
    {
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = null;
        if (PHP_MAJOR_VERSION < 8) {
            $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        }
        $parser = xml_parser_create();
        $vals = [];
        $index = [];
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        foreach ($parserOptions as $option => $value) {
            xml_parser_set_option($parser, $option, $value);
        }
        xml_parse_into_struct($parser, $string, $vals, $index);
        if (PHP_MAJOR_VERSION < 8) {
            libxml_disable_entity_loader($previousValueOfEntityLoader);
        }
        if (xml_get_error_code($parser)) {
            return 'Line ' . xml_get_current_line_number($parser) . ': ' . xml_error_string(xml_get_error_code($parser));
        }
        xml_parser_free($parser);
        $stack = [[]];
        $stacktop = 0;
        $startPoint = 0;
        $tagi = [];
        foreach ($vals as $key => $val) {
            $type = $val['type'];
            // open tag:
            if ($type === 'open' || $type === 'complete') {
                $stack[$stacktop++] = $tagi;
                if ($depth == $stacktop) {
                    $startPoint = $key;
                }
                $tagi = ['tag' => $val['tag']];
                if (isset($val['attributes'])) {
                    $tagi['attrs'] = $val['attributes'];
                }
                if (isset($val['value'])) {
                    $tagi['values'][] = $val['value'];
                }
            }
            // finish tag:
            if ($type === 'complete' || $type === 'close') {
                $oldtagi = $tagi;
                $tagi = $stack[--$stacktop];
                $oldtag = $oldtagi['tag'];
                unset($oldtagi['tag']);
                if ($depth == $stacktop + 1) {
                    if ($key - $startPoint > 0) {
                        $partArray = array_slice($vals, $startPoint + 1, $key - $startPoint - 1);
                        $oldtagi['XMLvalue'] = self::xmlRecompileFromStructValArray($partArray);
                    } else {
                        $oldtagi['XMLvalue'] = $oldtagi['values'][0];
                    }
                }
                $tagi['ch'][$oldtag][] = $oldtagi;
                unset($oldtagi);
            }
            // cdata
            if ($type === 'cdata') {
                $tagi['values'][] = $val['value'];
            }
        }
        return $tagi['ch'];
    }

    /**
     * Converts a PHP array into an XML string.
     * The XML output is optimized for readability since associative keys are used as tag names.
     * This also means that only alphanumeric characters are allowed in the tag names AND only keys NOT starting with numbers (so watch your usage of keys!). However there are options you can set to avoid this problem.
     * Numeric keys are stored with the default tag name "numIndex" but can be overridden to other formats)
     * The function handles input values from the PHP array in a binary-safe way; All characters below 32 (except 9,10,13) will trigger the content to be converted to a base64-string
     * The PHP variable type of the data IS preserved as long as the types are strings, arrays, integers and booleans. Strings are the default type unless the "type" attribute is set.
     * The output XML has been tested with the PHP XML-parser and parses OK under all tested circumstances with 4.x versions. However, with PHP5 there seems to be the need to add an XML prologue a la <?xml version="1.0" encoding="[charset]" standalone="yes" ?> - otherwise UTF-8 is assumed! Unfortunately, many times the output from this function is used without adding that prologue meaning that non-ASCII characters will break the parsing!! This sucks of course! Effectively it means that the prologue should always be prepended setting the right characterset, alternatively the system should always run as utf-8!
     * However using MSIE to read the XML output didn't always go well: One reason could be that the character encoding is not observed in the PHP data. The other reason may be if the tag-names are invalid in the eyes of MSIE. Also using the namespace feature will make MSIE break parsing. There might be more reasons...
     *
     * @param array $array The input PHP array with any kind of data; text, binary, integers. Not objects though.
     * @param string $NSprefix tag-prefix, eg. a namespace prefix like "T3:"
     * @param int $level Current recursion level. Don't change, stay at zero!
     * @param string $docTag Alternative document tag. Default is "phparray".
     * @param int $spaceInd If greater than zero, then the number of spaces corresponding to this number is used for indenting, if less than zero - no indentation, if zero - a single TAB is used
     * @param array $options Options for the compilation. Key "useNindex" => 0/1 (boolean: whether to use "n0, n1, n2" for num. indexes); Key "useIndexTagForNum" => "[tag for numerical indexes]"; Key "useIndexTagForAssoc" => "[tag for associative indexes"; Key "parentTagMap" => array('parentTag' => 'thisLevelTag')
     * @param array $stackData Stack data. Don't touch.
     * @return string An XML string made from the input content in the array.
     * @see xml2array()
     */
    public static function array2xml(array $array, $NSprefix = '', $level = 0, $docTag = 'phparray', $spaceInd = 0, array $options = [], array $stackData = [])
    {
        // The list of byte values which will trigger binary-safe storage. If any value has one of these char values in it, it will be encoded in base64
        $binaryChars = "\0" . chr(1) . chr(2) . chr(3) . chr(4) . chr(5) . chr(6) . chr(7) . chr(8) . chr(11) . chr(12) . chr(14) . chr(15) . chr(16) . chr(17) . chr(18) . chr(19) . chr(20) . chr(21) . chr(22) . chr(23) . chr(24) . chr(25) . chr(26) . chr(27) . chr(28) . chr(29) . chr(30) . chr(31);
        // Set indenting mode:
        $indentChar = $spaceInd ? ' ' : "\t";
        $indentN = $spaceInd > 0 ? $spaceInd : 1;
        $nl = $spaceInd >= 0 ? LF : '';
        // Init output variable:
        $output = '';
        // Traverse the input array
        foreach ($array as $k => $v) {
            $attr = '';
            $tagName = (string)$k;
            // Construct the tag name.
            // Use tag based on grand-parent + parent tag name
            if (isset($stackData['grandParentTagName'], $stackData['parentTagName'], $options['grandParentTagMap'][$stackData['grandParentTagName'] . '/' . $stackData['parentTagName']])) {
                $attr .= ' index="' . htmlspecialchars($tagName) . '"';
                $tagName = (string)$options['grandParentTagMap'][$stackData['grandParentTagName'] . '/' . $stackData['parentTagName']];
            } elseif (isset($stackData['parentTagName'], $options['parentTagMap'][$stackData['parentTagName'] . ':_IS_NUM']) && MathUtility::canBeInterpretedAsInteger($tagName)) {
                // Use tag based on parent tag name + if current tag is numeric
                $attr .= ' index="' . htmlspecialchars($tagName) . '"';
                $tagName = (string)$options['parentTagMap'][$stackData['parentTagName'] . ':_IS_NUM'];
            } elseif (isset($stackData['parentTagName'], $options['parentTagMap'][$stackData['parentTagName'] . ':' . $tagName])) {
                // Use tag based on parent tag name + current tag
                $attr .= ' index="' . htmlspecialchars($tagName) . '"';
                $tagName = (string)$options['parentTagMap'][$stackData['parentTagName'] . ':' . $tagName];
            } elseif (isset($stackData['parentTagName'], $options['parentTagMap'][$stackData['parentTagName']])) {
                // Use tag based on parent tag name:
                $attr .= ' index="' . htmlspecialchars($tagName) . '"';
                $tagName = (string)$options['parentTagMap'][$stackData['parentTagName']];
            } elseif (MathUtility::canBeInterpretedAsInteger($tagName)) {
                // If integer...;
                if ($options['useNindex'] ?? false) {
                    // If numeric key, prefix "n"
                    $tagName = 'n' . $tagName;
                } else {
                    // Use special tag for num. keys:
                    $attr .= ' index="' . $tagName . '"';
                    $tagName = ($options['useIndexTagForNum'] ?? false) ?: 'numIndex';
                }
            } elseif (!empty($options['useIndexTagForAssoc'])) {
                // Use tag for all associative keys:
                $attr .= ' index="' . htmlspecialchars($tagName) . '"';
                $tagName = $options['useIndexTagForAssoc'];
            }
            // The tag name is cleaned up so only alphanumeric chars (plus - and _) are in there and not longer than 100 chars either.
            $tagName = substr(preg_replace('/[^[:alnum:]_-]/', '', $tagName), 0, 100);
            // If the value is an array then we will call this function recursively:
            if (is_array($v)) {
                // Sub elements:
                if (isset($options['alt_options']) && ($options['alt_options'][($stackData['path'] ?? '') . '/' . $tagName] ?? false)) {
                    $subOptions = $options['alt_options'][($stackData['path'] ?? '') . '/' . $tagName];
                    $clearStackPath = (bool)($subOptions['clearStackPath'] ?? false);
                } else {
                    $subOptions = $options;
                    $clearStackPath = false;
                }
                if (empty($v)) {
                    $content = '';
                } else {
                    $content = $nl . self::array2xml($v, $NSprefix, $level + 1, '', $spaceInd, $subOptions, [
                            'parentTagName' => $tagName,
                            'grandParentTagName' => $stackData['parentTagName'] ?? '',
                            'path' => $clearStackPath ? '' : ($stackData['path'] ?? '') . '/' . $tagName,
                        ]) . ($spaceInd >= 0 ? str_pad('', ($level + 1) * $indentN, $indentChar) : '');
                }
                // Do not set "type = array". Makes prettier XML but means that empty arrays are not restored with xml2array
                if (!isset($options['disableTypeAttrib']) || (int)$options['disableTypeAttrib'] != 2) {
                    $attr .= ' type="array"';
                }
            } else {
                $stringValue = (string)$v;
                // Just a value:
                // Look for binary chars:
                $vLen = strlen($stringValue);
                // Go for base64 encoding if the initial segment NOT matching any binary char has the same length as the whole string!
                if ($vLen && strcspn($stringValue, $binaryChars) != $vLen) {
                    // If the value contained binary chars then we base64-encode it and set an attribute to notify this situation:
                    $content = $nl . chunk_split(base64_encode($stringValue));
                    $attr .= ' base64="1"';
                } else {
                    // Otherwise, just htmlspecialchar the stuff:
                    $content = htmlspecialchars($stringValue);
                    $dType = gettype($v);
                    if ($dType === 'string') {
                        if (isset($options['useCDATA']) && $options['useCDATA'] && $content != $stringValue) {
                            $content = '<![CDATA[' . $stringValue . ']]>';
                        }
                    } elseif (!($options['disableTypeAttrib'] ?? false)) {
                        $attr .= ' type="' . $dType . '"';
                    }
                }
            }
            if ($tagName !== '') {
                // Add the element to the output string:
                $output .= ($spaceInd >= 0 ? str_pad('', ($level + 1) * $indentN, $indentChar) : '')
                    . '<' . $NSprefix . $tagName . $attr . '>' . $content . '</' . $NSprefix . $tagName . '>' . $nl;
            }
        }
        // If we are at the outer-most level, then we finally wrap it all in the document tags and return that as the value:
        if (!$level) {
            $output = '<' . $docTag . '>' . $nl . $output . '</' . $docTag . '>';
        }
        return $output;
    }

    /**
     * Converts an XML string to a PHP array.
     * This is the reverse function of array2xml()
     * This is a wrapper for xml2arrayProcess that adds a two-level cache
     *
     * @param string $string XML content to convert into an array
     * @param string $NSprefix The tag-prefix resolve, eg. a namespace like "T3:"
     * @param bool $reportDocTag If set, the document tag will be set in the key "_DOCUMENT_TAG" of the output array
     * @return mixed If the parsing had errors, a string with the error message is returned. Otherwise an array with the content.
     * @see array2xml()
     * @see xml2arrayProcess()
     */
    public static function xml2array($string, $NSprefix = '', $reportDocTag = false)
    {
        $runtimeCache = static::makeInstance(CacheManager::class)->getCache('runtime');
        $firstLevelCache = $runtimeCache->get('generalUtilityXml2Array') ?: [];
        $identifier = md5($string . $NSprefix . ($reportDocTag ? '1' : '0'));
        // Look up in first level cache
        if (empty($firstLevelCache[$identifier])) {
            $firstLevelCache[$identifier] = self::xml2arrayProcess($string, $NSprefix, $reportDocTag);
            $runtimeCache->set('generalUtilityXml2Array', $firstLevelCache);
        }
        return $firstLevelCache[$identifier];
    }

    /**
     * Converts an XML string to a PHP array.
     * This is the reverse function of array2xml()
     *
     * @param string $string XML content to convert into an array
     * @param string $NSprefix The tag-prefix resolve, eg. a namespace like "T3:"
     * @param bool $reportDocTag If set, the document tag will be set in the key "_DOCUMENT_TAG" of the output array
     * @return mixed If the parsing had errors, a string with the error message is returned. Otherwise an array with the content.
     * @see array2xml()
     */
    public static function xml2arrayProcess($string, $NSprefix = '', $reportDocTag = false)
    {
        $string = trim((string)$string);
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = null;
        if (PHP_MAJOR_VERSION < 8) {
            $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        }
        // Create parser:
        $parser = xml_parser_create();
        $vals = [];
        $index = [];
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        // Default output charset is UTF-8, only ASCII, ISO-8859-1 and UTF-8 are supported!!!
        $match = [];
        preg_match('/^[[:space:]]*<\\?xml[^>]*encoding[[:space:]]*=[[:space:]]*"([^"]*)"/', substr($string, 0, 200), $match);
        $theCharset = $match[1] ?? 'utf-8';
        // us-ascii / utf-8 / iso-8859-1
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $theCharset);
        // Parse content:
        xml_parse_into_struct($parser, $string, $vals, $index);
        if (PHP_MAJOR_VERSION < 8) {
            libxml_disable_entity_loader($previousValueOfEntityLoader);
        }
        // If error, return error message:
        if (xml_get_error_code($parser)) {
            return 'Line ' . xml_get_current_line_number($parser) . ': ' . xml_error_string(xml_get_error_code($parser));
        }
        xml_parser_free($parser);
        // Init vars:
        $stack = [[]];
        $stacktop = 0;
        $current = [];
        $tagName = '';
        $documentTag = '';
        // Traverse the parsed XML structure:
        foreach ($vals as $key => $val) {
            // First, process the tag-name (which is used in both cases, whether "complete" or "close")
            $tagName = $val['tag'];
            if (!$documentTag) {
                $documentTag = $tagName;
            }
            // Test for name space:
            $tagName = $NSprefix && strpos($tagName, $NSprefix) === 0 ? substr($tagName, strlen($NSprefix)) : $tagName;
            // Test for numeric tag, encoded on the form "nXXX":
            $testNtag = substr($tagName, 1);
            // Closing tag.
            $tagName = $tagName[0] === 'n' && MathUtility::canBeInterpretedAsInteger($testNtag) ? (int)$testNtag : $tagName;
            // Test for alternative index value:
            if ((string)($val['attributes']['index'] ?? '') !== '') {
                $tagName = $val['attributes']['index'];
            }
            // Setting tag-values, manage stack:
            switch ($val['type']) {
                case 'open':
                    // If open tag it means there is an array stored in sub-elements. Therefore increase the stackpointer and reset the accumulation array:
                    // Setting blank place holder
                    $current[$tagName] = [];
                    $stack[$stacktop++] = $current;
                    $current = [];
                    break;
                case 'close':
                    // If the tag is "close" then it is an array which is closing and we decrease the stack pointer.
                    $oldCurrent = $current;
                    $current = $stack[--$stacktop];
                    // Going to the end of array to get placeholder key, key($current), and fill in array next:
                    end($current);
                    $current[key($current)] = $oldCurrent;
                    unset($oldCurrent);
                    break;
                case 'complete':
                    // If "complete", then it's a value. If the attribute "base64" is set, then decode the value, otherwise just set it.
                    if (!empty($val['attributes']['base64'])) {
                        $current[$tagName] = base64_decode($val['value']);
                    } else {
                        // Had to cast it as a string - otherwise it would be evaluate FALSE if tested with isset()!!
                        $current[$tagName] = (string)($val['value'] ?? '');
                        // Cast type:
                        switch ((string)($val['attributes']['type'] ?? '')) {
                            case 'integer':
                                $current[$tagName] = (int)$current[$tagName];
                                break;
                            case 'double':
                                $current[$tagName] = (double)$current[$tagName];
                                break;
                            case 'boolean':
                                $current[$tagName] = (bool)$current[$tagName];
                                break;
                            case 'NULL':
                                $current[$tagName] = null;
                                break;
                            case 'array':
                                // MUST be an empty array since it is processed as a value; Empty arrays would end up here because they would have no tags inside...
                                $current[$tagName] = [];
                                break;
                        }
                    }
                    break;
            }
        }
        if ($reportDocTag) {
            $current[$tagName]['_DOCUMENT_TAG'] = $documentTag;
        }
        // Finally return the content of the document tag.
        return $current[$tagName];
    }

    /**
     * This implodes an array of XML parts (made with xml_parse_into_struct()) into XML again.
     *
     * @param array<int, array<string, mixed>> $vals An array of XML parts, see xml2tree
     * @return string Re-compiled XML data.
     */
    public static function xmlRecompileFromStructValArray(array $vals)
    {
        $XMLcontent = '';
        foreach ($vals as $val) {
            $type = $val['type'];
            // Open tag:
            if ($type === 'open' || $type === 'complete') {
                $XMLcontent .= '<' . $val['tag'];
                if (isset($val['attributes'])) {
                    foreach ($val['attributes'] as $k => $v) {
                        $XMLcontent .= ' ' . $k . '="' . htmlspecialchars($v) . '"';
                    }
                }
                if ($type === 'complete') {
                    if (isset($val['value'])) {
                        $XMLcontent .= '>' . htmlspecialchars($val['value']) . '</' . $val['tag'] . '>';
                    } else {
                        $XMLcontent .= '/>';
                    }
                } else {
                    $XMLcontent .= '>';
                }
                if ($type === 'open' && isset($val['value'])) {
                    $XMLcontent .= htmlspecialchars($val['value']);
                }
            }
            // Finish tag:
            if ($type === 'close') {
                $XMLcontent .= '</' . $val['tag'] . '>';
            }
            // Cdata
            if ($type === 'cdata') {
                $XMLcontent .= htmlspecialchars($val['value']);
            }
        }
        return $XMLcontent;
    }

    /**
     * Minifies JavaScript
     *
     * @param string $script Script to minify
     * @param string $error Error message (if any)
     * @return string Minified script or source string if error happened
     * @deprecated will be removed in TYPO3 v12.0. Use ResourceCompressor->compressJavaScriptSource() instead.
     */
    public static function minifyJavaScript($script, &$error = '')
    {
        trigger_error('Calling GeneralUtility::minifyJavaScript directly will be removed in TYPO3 v12.0. Use ResourceCompressor->compressJavaScriptSource() instead.', E_USER_DEPRECATED);
        $fakeThis = null;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['minifyJavaScript'] ?? [] as $hookMethod) {
            try {
                $parameters = ['script' => $script];
                $script = static::callUserFunction($hookMethod, $parameters, $fakeThis);
            } catch (\Exception $e) {
                $error .= 'Error minifying Javascript: ' . $e->getMessage();
                static::getLogger()->warning('Error minifying Javascript: {file}, hook: {hook}', [
                    'file' => $script,
                    'hook' => $hookMethod,
                    'exception' => $e,
                ]);
            }
        }
        return $script;
    }

    /*************************
     *
     * FILES FUNCTIONS
     *
     *************************/
    /**
     * Reads the file or url $url and returns the content
     * If you are having trouble with proxies when reading URLs you can configure your way out of that with settings within $GLOBALS['TYPO3_CONF_VARS']['HTTP'].
     *
     * @param string $url File/URL to read
     * @return string|false The content from the resource given as input. FALSE if an error has occurred.
     */
    public static function getUrl($url)
    {
        // Looks like it's an external file, use Guzzle by default
        if (preg_match('/^(?:http|ftp)s?|s(?:ftp|cp):/', $url)) {
            $requestFactory = static::makeInstance(RequestFactory::class);
            try {
                $response = $requestFactory->request($url);
            } catch (RequestException $exception) {
                return false;
            }
            $content = $response->getBody()->getContents();
        } else {
            $content = @file_get_contents($url);
        }
        return $content;
    }

    /**
     * Writes $content to the file $file
     *
     * @param string $file Filepath to write to
     * @param string $content Content to write
     * @param bool $changePermissions If TRUE, permissions are forced to be set
     * @return bool TRUE if the file was successfully opened and written to.
     */
    public static function writeFile($file, $content, $changePermissions = false)
    {
        if (!@is_file($file)) {
            $changePermissions = true;
        }
        if ($fd = fopen($file, 'wb')) {
            $res = fwrite($fd, $content);
            fclose($fd);
            if ($res === false) {
                return false;
            }
            // Change the permissions only if the file has just been created
            if ($changePermissions) {
                static::fixPermissions($file);
            }
            return true;
        }
        return false;
    }

    /**
     * Sets the file system mode and group ownership of a file or a folder.
     *
     * @param string $path Path of file or folder, must not be escaped. Path can be absolute or relative
     * @param bool $recursive If set, also fixes permissions of files and folders in the folder (if $path is a folder)
     * @return mixed TRUE on success, FALSE on error, always TRUE on Windows OS
     */
    public static function fixPermissions($path, $recursive = false)
    {
        $targetPermissions = null;
        if (Environment::isWindows()) {
            return true;
        }
        $result = false;
        // Make path absolute
        if (!PathUtility::isAbsolutePath($path)) {
            $path = static::getFileAbsFileName($path);
        }
        if (static::isAllowedAbsPath($path)) {
            if (@is_file($path)) {
                $targetPermissions = (string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] ?? '0644');
            } elseif (@is_dir($path)) {
                $targetPermissions = (string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] ?? '0755');
            }
            if (!empty($targetPermissions)) {
                // make sure it's always 4 digits
                $targetPermissions = str_pad($targetPermissions, 4, '0', STR_PAD_LEFT);
                $targetPermissions = octdec($targetPermissions);
                // "@" is there because file is not necessarily OWNED by the user
                $result = @chmod($path, (int)$targetPermissions);
            }
            // Set createGroup if not empty
            if (
                isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup'])
                && $GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup'] !== ''
            ) {
                // "@" is there because file is not necessarily OWNED by the user
                $changeGroupResult = @chgrp($path, $GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup']);
                $result = $changeGroupResult ? $result : false;
            }
            // Call recursive if recursive flag if set and $path is directory
            if ($recursive && @is_dir($path)) {
                $handle = opendir($path);
                if (is_resource($handle)) {
                    while (($file = readdir($handle)) !== false) {
                        $recursionResult = null;
                        if ($file !== '.' && $file !== '..') {
                            if (@is_file($path . '/' . $file)) {
                                $recursionResult = static::fixPermissions($path . '/' . $file);
                            } elseif (@is_dir($path . '/' . $file)) {
                                $recursionResult = static::fixPermissions($path . '/' . $file, true);
                            }
                            if (isset($recursionResult) && !$recursionResult) {
                                $result = false;
                            }
                        }
                    }
                    closedir($handle);
                }
            }
        }
        return $result;
    }

    /**
     * Writes $content to a filename in the typo3temp/ folder (and possibly one or two subfolders...)
     * Accepts an additional subdirectory in the file path!
     *
     * @param string $filepath Absolute file path to write within the typo3temp/ or Environment::getVarPath() folder - the file path must be prefixed with this path
     * @param string $content Content string to write
     * @return string|null Returns NULL on success, otherwise an error string telling about the problem.
     */
    public static function writeFileToTypo3tempDir($filepath, $content)
    {
        // Parse filepath into directory and basename:
        $fI = pathinfo($filepath);
        $fI['dirname'] .= '/';
        // Check parts:
        if (!static::validPathStr($filepath) || !$fI['basename'] || strlen($fI['basename']) >= 60) {
            return 'Input filepath "' . $filepath . '" was generally invalid!';
        }

        // Setting main temporary directory name (standard)
        $allowedPathPrefixes = [
            Environment::getPublicPath() . '/typo3temp' => 'Environment::getPublicPath() + "/typo3temp/"',
        ];
        // Also allow project-path + /var/
        if (Environment::getVarPath() !== Environment::getPublicPath() . '/typo3temp/var') {
            $relPath = substr(Environment::getVarPath(), strlen(Environment::getProjectPath()) + 1);
            $allowedPathPrefixes[Environment::getVarPath()] = 'ProjectPath + ' . $relPath;
        }

        $errorMessage = null;
        foreach ($allowedPathPrefixes as $pathPrefix => $prefixLabel) {
            $dirName = $pathPrefix . '/';
            // Invalid file path, let's check for the other path, if it exists
            if (!str_starts_with($fI['dirname'], $dirName)) {
                if ($errorMessage === null) {
                    $errorMessage = '"' . $fI['dirname'] . '" was not within directory ' . $prefixLabel;
                }
                continue;
            }
            // This resets previous error messages from the first path
            $errorMessage = null;

            if (!@is_dir($dirName)) {
                $errorMessage = $prefixLabel . ' was not a directory!';
                // continue and see if the next iteration resets the errorMessage above
                continue;
            }
            // Checking if the "subdir" is found
            $subdir = substr($fI['dirname'], strlen($dirName));
            if ($subdir) {
                if (preg_match('#^(?:[[:alnum:]_]+/)+$#', $subdir)) {
                    $dirName .= $subdir;
                    if (!@is_dir($dirName)) {
                        static::mkdir_deep($pathPrefix . '/' . $subdir);
                    }
                } else {
                    $errorMessage = 'Subdir, "' . $subdir . '", was NOT on the form "[[:alnum:]_]/+"';
                    break;
                }
            }
            // Checking dir-name again (sub-dir might have been created)
            if (@is_dir($dirName)) {
                if ($filepath === $dirName . $fI['basename']) {
                    static::writeFile($filepath, $content);
                    if (!@is_file($filepath)) {
                        $errorMessage = 'The file was not written to the disk. Please, check that you have write permissions to the ' . $prefixLabel . ' directory.';
                    }
                    break;
                }
                $errorMessage = 'Calculated file location didn\'t match input "' . $filepath . '".';
                break;
            }
            $errorMessage = '"' . $dirName . '" is not a directory!';
            break;
        }
        return $errorMessage;
    }

    /**
     * Wrapper function for mkdir.
     * Sets folder permissions according to $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask']
     * and group ownership according to $GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup']
     *
     * @param string $newFolder Absolute path to folder, see PHP mkdir() function. Removes trailing slash internally.
     * @return bool TRUE if operation was successful
     */
    public static function mkdir($newFolder)
    {
        $result = @mkdir($newFolder, (int)octdec($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask']));
        if ($result) {
            static::fixPermissions($newFolder);
        }
        return $result;
    }

    /**
     * Creates a directory - including parent directories if necessary and
     * sets permissions on newly created directories.
     *
     * @param string $directory Target directory to create
     * @throws \InvalidArgumentException If $directory is not a string
     * @throws \RuntimeException If directory could not be created
     */
    public static function mkdir_deep($directory)
    {
        if (!is_string($directory)) {
            throw new \InvalidArgumentException('The specified directory is of type "' . gettype($directory) . '" but a string is expected.', 1303662955);
        }
        // Ensure there is only one slash
        $fullPath = rtrim($directory, '/') . '/';
        if ($fullPath !== '/' && !is_dir($fullPath)) {
            $firstCreatedPath = static::createDirectoryPath($fullPath);
            if ($firstCreatedPath !== '') {
                static::fixPermissions($firstCreatedPath, true);
            }
        }
    }

    /**
     * Creates directories for the specified paths if they do not exist. This
     * functions sets proper permission mask but does not set proper user and
     * group.
     *
     * @static
     * @param string $fullDirectoryPath
     * @return string Path to the the first created directory in the hierarchy
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep
     * @throws \RuntimeException If directory could not be created
     */
    protected static function createDirectoryPath($fullDirectoryPath)
    {
        $currentPath = $fullDirectoryPath;
        $firstCreatedPath = '';
        $permissionMask = (int)octdec($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] ?? 0);
        if (!@is_dir($currentPath)) {
            do {
                $firstCreatedPath = $currentPath;
                $separatorPosition = (int)strrpos($currentPath, DIRECTORY_SEPARATOR);
                $currentPath = substr($currentPath, 0, $separatorPosition);
            } while (!is_dir($currentPath) && $separatorPosition > 0);
            $result = @mkdir($fullDirectoryPath, $permissionMask, true);
            // Check existence of directory again to avoid race condition. Directory could have get created by another process between previous is_dir() and mkdir()
            if (!$result && !@is_dir($fullDirectoryPath)) {
                throw new \RuntimeException('Could not create directory "' . $fullDirectoryPath . '"!', 1170251401);
            }
        }
        return $firstCreatedPath;
    }

    /**
     * Wrapper function for rmdir, allowing recursive deletion of folders and files
     *
     * @param string $path Absolute path to folder, see PHP rmdir() function. Removes trailing slash internally.
     * @param bool $removeNonEmpty Allow deletion of non-empty directories
     * @return bool TRUE if operation was successful
     */
    public static function rmdir($path, $removeNonEmpty = false)
    {
        $OK = false;
        // Remove trailing slash
        $path = preg_replace('|/$|', '', $path) ?? '';
        $isWindows = DIRECTORY_SEPARATOR === '\\';
        if (file_exists($path)) {
            $OK = true;
            if (!is_link($path) && is_dir($path)) {
                if ($removeNonEmpty === true && ($handle = @opendir($path))) {
                    $entries = [];

                    while (false !== ($file = readdir($handle))) {
                        if ($file === '.' || $file === '..') {
                            continue;
                        }

                        $entries[] = $path . '/' . $file;
                    }

                    closedir($handle);

                    foreach ($entries as $entry) {
                        if (!static::rmdir($entry, $removeNonEmpty)) {
                            $OK = false;
                        }
                    }
                }
                if ($OK) {
                    $OK = @rmdir($path);
                }
            } elseif (is_link($path) && is_dir($path) && $isWindows) {
                $OK = @rmdir($path);
            } else {
                // If $path is a file, simply remove it
                $OK = @unlink($path);
            }
            clearstatcache();
        } elseif (is_link($path)) {
            $OK = @unlink($path);
            if (!$OK && $isWindows) {
                // Try to delete dead folder links on Windows systems
                $OK = @rmdir($path);
            }
            clearstatcache();
        }
        return $OK;
    }

    /**
     * Returns an array with the names of folders in a specific path
     * Will return 'error' (string) if there were an error with reading directory content.
     * Will return null if provided path is false.
     *
     * @param string $path Path to list directories from
     * @return string[]|string|null Returns an array with the directory entries as values. If no path is provided, the return value will be null.
     */
    public static function get_dirs($path)
    {
        $dirs = null;
        if ($path) {
            if (is_dir($path)) {
                $dir = scandir($path);
                $dirs = [];
                foreach ($dir as $entry) {
                    if (is_dir($path . '/' . $entry) && $entry !== '..' && $entry !== '.') {
                        $dirs[] = $entry;
                    }
                }
            } else {
                $dirs = 'error';
            }
        }
        return $dirs;
    }

    /**
     * Finds all files in a given path and returns them as an array. Each
     * array key is a md5 hash of the full path to the file. This is done because
     * 'some' extensions like the import/export extension depend on this.
     *
     * @param string $path The path to retrieve the files from.
     * @param string $extensionList A comma-separated list of file extensions. Only files of the specified types will be retrieved. When left blank, files of any type will be retrieved.
     * @param bool $prependPath If TRUE, the full path to the file is returned. If FALSE only the file name is returned.
     * @param string $order The sorting order. The default sorting order is alphabetical. Setting $order to 'mtime' will sort the files by modification time.
     * @param string $excludePattern A regular expression pattern of file names to exclude. For example: 'clear.gif' or '(clear.gif|.htaccess)'. The pattern will be wrapped with: '/^' and '$/'.
     * @return array<string, string>|string Array of the files found, or an error message in case the path could not be opened.
     */
    public static function getFilesInDir($path, $extensionList = '', $prependPath = false, $order = '', $excludePattern = '')
    {
        $excludePattern = (string)$excludePattern;
        $path = rtrim($path, '/');
        if (!@is_dir($path)) {
            return [];
        }

        $rawFileList = scandir($path);
        if ($rawFileList === false) {
            return 'error opening path: "' . $path . '"';
        }

        $pathPrefix = $path . '/';
        $allowedFileExtensionArray = self::trimExplode(',', $extensionList);
        $extensionList = ',' . str_replace(' ', '', $extensionList) . ',';
        $files = [];
        foreach ($rawFileList as $entry) {
            $completePathToEntry = $pathPrefix . $entry;
            if (!@is_file($completePathToEntry)) {
                continue;
            }

            foreach ($allowedFileExtensionArray as $allowedFileExtension) {
                if (
                    ($extensionList === ',,' || stripos($extensionList, ',' . substr($entry, strlen($allowedFileExtension) * -1, strlen($allowedFileExtension)) . ',') !== false)
                    && ($excludePattern === '' || !preg_match('/^' . $excludePattern . '$/', $entry))
                ) {
                    if ($order !== 'mtime') {
                        $files[] = $entry;
                    } else {
                        // Store the value in the key so we can do a fast asort later.
                        $files[$entry] = filemtime($completePathToEntry);
                    }
                }
            }
        }

        $valueName = 'value';
        if ($order === 'mtime') {
            asort($files);
            $valueName = 'key';
        }

        $valuePathPrefix = $prependPath ? $pathPrefix : '';
        $foundFiles = [];
        foreach ($files as $key => $value) {
            // Don't change this ever - extensions may depend on the fact that the hash is an md5 of the path! (import/export extension)
            $foundFiles[md5($pathPrefix . ${$valueName})] = $valuePathPrefix . ${$valueName};
        }

        return $foundFiles;
    }

    /**
     * Recursively gather all files and folders of a path.
     *
     * @param string[] $fileArr Empty input array (will have files added to it)
     * @param string $path The path to read recursively from (absolute) (include trailing slash!)
     * @param string $extList Comma list of file extensions: Only files with extensions in this list (if applicable) will be selected.
     * @param bool $regDirs If set, directories are also included in output.
     * @param int $recursivityLevels The number of levels to dig down...
     * @param string $excludePattern regex pattern of files/directories to exclude
     * @return array<string, string> An array with the found files/directories.
     */
    public static function getAllFilesAndFoldersInPath(array $fileArr, $path, $extList = '', $regDirs = false, $recursivityLevels = 99, $excludePattern = '')
    {
        if ($regDirs) {
            $fileArr[md5($path)] = $path;
        }
        $fileArr = array_merge($fileArr, (array)self::getFilesInDir($path, $extList, true, '', $excludePattern));
        $dirs = self::get_dirs($path);
        if ($recursivityLevels > 0 && is_array($dirs)) {
            foreach ($dirs as $subdirs) {
                if ((string)$subdirs !== '' && ($excludePattern === '' || !preg_match('/^' . $excludePattern . '$/', $subdirs))) {
                    $fileArr = self::getAllFilesAndFoldersInPath($fileArr, $path . $subdirs . '/', $extList, $regDirs, $recursivityLevels - 1, $excludePattern);
                }
            }
        }
        return $fileArr;
    }

    /**
     * Removes the absolute part of all files/folders in fileArr
     *
     * @param string[] $fileArr The file array to remove the prefix from
     * @param string $prefixToRemove The prefix path to remove (if found as first part of string!)
     * @return string[]|string The input $fileArr processed, or a string with an error message, when an error occurred.
     */
    public static function removePrefixPathFromList(array $fileArr, string $prefixToRemove)
    {
        foreach ($fileArr as &$absFileRef) {
            if (str_starts_with($absFileRef, $prefixToRemove)) {
                $absFileRef = substr($absFileRef, strlen($prefixToRemove));
            } else {
                return 'ERROR: One or more of the files was NOT prefixed with the prefix-path!';
            }
        }
        unset($absFileRef);
        return $fileArr;
    }

    /**
     * Fixes a path for windows-backslashes and reduces double-slashes to single slashes
     *
     * @param string $theFile File path to process
     * @return string
     */
    public static function fixWindowsFilePath($theFile)
    {
        return str_replace(['\\', '//'], '/', $theFile);
    }

    /**
     * Resolves "../" sections in the input path string.
     * For example "fileadmin/directory/../other_directory/" will be resolved to "fileadmin/other_directory/"
     *
     * @param string $pathStr File path in which "/../" is resolved
     * @return string
     */
    public static function resolveBackPath($pathStr)
    {
        if (!str_contains($pathStr, '..')) {
            return $pathStr;
        }
        $parts = explode('/', $pathStr);
        $output = [];
        $c = 0;
        foreach ($parts as $part) {
            if ($part === '..') {
                if ($c) {
                    array_pop($output);
                    --$c;
                } else {
                    $output[] = $part;
                }
            } else {
                ++$c;
                $output[] = $part;
            }
        }
        return implode('/', $output);
    }

    /**
     * Prefixes a URL used with 'header-location' with 'http://...' depending on whether it has it already.
     * - If already having a scheme, nothing is prepended
     * - If having REQUEST_URI slash '/', then prefixing 'http://[host]' (relative to host)
     * - Otherwise prefixed with TYPO3_REQUEST_DIR (relative to current dir / TYPO3_REQUEST_DIR)
     *
     * @param string $path URL / path to prepend full URL addressing to.
     * @return string
     */
    public static function locationHeaderUrl($path)
    {
        if (strpos($path, '//') === 0) {
            return $path;
        }

        // relative to HOST
        if (strpos($path, '/') === 0) {
            return self::getIndpEnv('TYPO3_REQUEST_HOST') . $path;
        }

        $urlComponents = parse_url($path);
        if (!($urlComponents['scheme'] ?? false)) {
            // No scheme either
            return self::getIndpEnv('TYPO3_REQUEST_DIR') . $path;
        }

        return $path;
    }

    /**
     * Returns the maximum upload size for a file that is allowed. Measured in KB.
     * This might be handy to find out the real upload limit that is possible for this
     * TYPO3 installation.
     *
     * @return int The maximum size of uploads that are allowed (measured in kilobytes)
     */
    public static function getMaxUploadFileSize()
    {
        $uploadMaxFilesize = (string)ini_get('upload_max_filesize');
        $postMaxSize = (string)ini_get('post_max_size');
        // Check for PHP restrictions of the maximum size of one of the $_FILES
        $phpUploadLimit = self::getBytesFromSizeMeasurement($uploadMaxFilesize);
        // Check for PHP restrictions of the maximum $_POST size
        $phpPostLimit = self::getBytesFromSizeMeasurement($postMaxSize);
        // If the total amount of post data is smaller (!) than the upload_max_filesize directive,
        // then this is the real limit in PHP
        $phpUploadLimit = $phpPostLimit > 0 && $phpPostLimit < $phpUploadLimit ? $phpPostLimit : $phpUploadLimit;
        return floor($phpUploadLimit) / 1024;
    }

    /**
     * Gets the bytes value from a measurement string like "100k".
     *
     * @param string $measurement The measurement (e.g. "100k")
     * @return int The bytes value (e.g. 102400)
     */
    public static function getBytesFromSizeMeasurement($measurement)
    {
        $bytes = (float)$measurement;
        if (stripos($measurement, 'G')) {
            $bytes *= 1024 * 1024 * 1024;
        } elseif (stripos($measurement, 'M')) {
            $bytes *= 1024 * 1024;
        } elseif (stripos($measurement, 'K')) {
            $bytes *= 1024;
        }
        return (int)$bytes;
    }

    /**
     * Function for static version numbers on files, based on the filemtime
     *
     * This will make the filename automatically change when a file is
     * changed, and by that re-cached by the browser. If the file does not
     * exist physically the original file passed to the function is
     * returned without the timestamp.
     *
     * Behaviour is influenced by the setting
     * TYPO3_CONF_VARS['BE' and 'FE'][versionNumberInFilename]
     * = TRUE (BE) / "embed" (FE) : modify filename
     * = FALSE (BE) / "querystring" (FE) : add timestamp as parameter
     *
     * @param string $file Relative path to file including all potential query parameters (not htmlspecialchared yet)
     * @return string Relative path with version filename including the timestamp
     */
    public static function createVersionNumberedFilename($file)
    {
        $lookupFile = explode('?', $file);
        $path = self::resolveBackPath(self::dirname(Environment::getCurrentScript()) . '/' . $lookupFile[0]);

        $doNothing = false;

        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
        ) {
            $mode = strtolower($GLOBALS['TYPO3_CONF_VARS']['FE']['versionNumberInFilename']);
            if ($mode === 'embed') {
                $mode = true;
            } else {
                if ($mode === 'querystring') {
                    $mode = false;
                } else {
                    $doNothing = true;
                }
            }
        } else {
            $mode = $GLOBALS['TYPO3_CONF_VARS']['BE']['versionNumberInFilename'];
        }
        if ($doNothing || !file_exists($path)) {
            // File not found, return filename unaltered
            $fullName = $file;
        } else {
            if (!$mode) {
                // If use of .htaccess rule is not configured,
                // we use the default query-string method
                if (!empty($lookupFile[1])) {
                    $separator = '&';
                } else {
                    $separator = '?';
                }
                $fullName = $file . $separator . filemtime($path);
            } else {
                // Change the filename
                $name = explode('.', $lookupFile[0]);
                $extension = array_pop($name);
                array_push($name, filemtime($path), $extension);
                $fullName = implode('.', $name);
                // Append potential query string
                $fullName .= !empty($lookupFile[1]) ? '?' . $lookupFile[1] : '';
            }
        }
        return $fullName;
    }

    /**
     * Writes string to a temporary file named after the md5-hash of the string
     * Quite useful for extensions adding their custom built JavaScript during runtime.
     *
     * @param string $content JavaScript to write to file.
     * @return string filename to include in the <script> tag
     */
    public static function writeJavaScriptContentToTemporaryFile(string $content)
    {
        $script = 'typo3temp/assets/js/' . md5($content) . '.js';
        if (!@is_file(Environment::getPublicPath() . '/' . $script)) {
            self::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $script, $content);
        }
        return $script;
    }

    /**
     * Writes string to a temporary file named after the md5-hash of the string
     * Quite useful for extensions adding their custom built StyleSheet during runtime.
     *
     * @param string $content CSS styles to write to file.
     * @return string filename to include in the <link> tag
     */
    public static function writeStyleSheetContentToTemporaryFile(string $content)
    {
        $script = 'typo3temp/assets/css/' . md5($content) . '.css';
        if (!@is_file(Environment::getPublicPath() . '/' . $script)) {
            self::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $script, $content);
        }
        return $script;
    }

    /*************************
     *
     * SYSTEM INFORMATION
     *
     *************************/

    /**
     * Returns the link-url to the current script.
     * In $getParams you can set associative keys corresponding to the GET-vars you wish to add to the URL. If you set them empty, they will remove existing GET-vars from the current URL.
     * REMEMBER to always use htmlspecialchars() for content in href-properties to get ampersands converted to entities (XHTML requirement and XSS precaution)
     *
     * @param array $getParams Array of GET parameters to include
     * @return string
     */
    public static function linkThisScript(array $getParams = [])
    {
        $parts = self::getIndpEnv('SCRIPT_NAME');
        $params = self::_GET();
        foreach ($getParams as $key => $value) {
            if ($value !== '') {
                $params[$key] = $value;
            } else {
                unset($params[$key]);
            }
        }
        $pString = self::implodeArrayForUrl('', $params);
        return $pString ? $parts . '?' . ltrim($pString, '&') : $parts;
    }

    /**
     * This method is only for testing and should never be used outside tests-
     *
     * @param string $envName
     * @param mixed $value
     * @internal
     */
    public static function setIndpEnv($envName, $value)
    {
        self::$indpEnvCache[$envName] = $value;
    }

    /**
     * Abstraction method which returns System Environment Variables regardless of server OS, CGI/MODULE version etc. Basically this is SERVER variables for most of them.
     * This should be used instead of getEnv() and $_SERVER/ENV_VARS to get reliable values for all situations.
     *
     * @param string $getEnvName Name of the "environment variable"/"server variable" you wish to use. Valid values are SCRIPT_NAME, SCRIPT_FILENAME, REQUEST_URI, PATH_INFO, REMOTE_ADDR, REMOTE_HOST, HTTP_REFERER, HTTP_HOST, HTTP_USER_AGENT, HTTP_ACCEPT_LANGUAGE, QUERY_STRING, TYPO3_DOCUMENT_ROOT, TYPO3_HOST_ONLY, TYPO3_HOST_ONLY, TYPO3_REQUEST_HOST, TYPO3_REQUEST_URL, TYPO3_REQUEST_SCRIPT, TYPO3_REQUEST_DIR, TYPO3_SITE_URL, _ARRAY
     * @return string|bool|array<string, string|bool|null>|null Value based on the input key, independent of server/OS environment.
     * @throws \UnexpectedValueException
     */
    public static function getIndpEnv($getEnvName)
    {
        if (array_key_exists($getEnvName, self::$indpEnvCache)) {
            return self::$indpEnvCache[$getEnvName];
        }

        /*
        Conventions:
        output from parse_url():
        URL:	http://username:password@192.168.1.4:8080/typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/?arg1,arg2,arg3&p1=parameter1&p2[key]=value#link1
        [scheme] => 'http'
        [user] => 'username'
        [pass] => 'password'
        [host] => '192.168.1.4'
        [port] => '8080'
        [path] => '/typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/'
        [query] => 'arg1,arg2,arg3&p1=parameter1&p2[key]=value'
        [fragment] => 'link1'Further definition: [path_script] = '/typo3/32/temp/phpcheck/index.php'
        [path_dir] = '/typo3/32/temp/phpcheck/'
        [path_info] = '/arg1/arg2/arg3/'
        [path] = [path_script/path_dir][path_info]Keys supported:URI______:
        REQUEST_URI		=	[path]?[query]		= /typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/?arg1,arg2,arg3&p1=parameter1&p2[key]=value
        HTTP_HOST		=	[host][:[port]]		= 192.168.1.4:8080
        SCRIPT_NAME		=	[path_script]++		= /typo3/32/temp/phpcheck/index.php		// NOTICE THAT SCRIPT_NAME will return the php-script name ALSO. [path_script] may not do that (eg. '/somedir/' may result in SCRIPT_NAME '/somedir/index.php')!
        PATH_INFO		=	[path_info]			= /arg1/arg2/arg3/
        QUERY_STRING	=	[query]				= arg1,arg2,arg3&p1=parameter1&p2[key]=value
        HTTP_REFERER	=	[scheme]://[host][:[port]][path]	= http://192.168.1.4:8080/typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/?arg1,arg2,arg3&p1=parameter1&p2[key]=value
        (Notice: NO username/password + NO fragment)CLIENT____:
        REMOTE_ADDR		=	(client IP)
        REMOTE_HOST		=	(client host)
        HTTP_USER_AGENT	=	(client user agent)
        HTTP_ACCEPT_LANGUAGE	= (client accept language)SERVER____:
        SCRIPT_FILENAME	=	Absolute filename of script		(Differs between windows/unix). On windows 'C:\\some\\path\\' will be converted to 'C:/some/path/'Special extras:
        TYPO3_HOST_ONLY =		[host] = 192.168.1.4
        TYPO3_PORT =			[port] = 8080 (blank if 80, taken from host value)
        TYPO3_REQUEST_HOST = 		[scheme]://[host][:[port]]
        TYPO3_REQUEST_URL =		[scheme]://[host][:[port]][path]?[query] (scheme will by default be "http" until we can detect something different)
        TYPO3_REQUEST_SCRIPT =  	[scheme]://[host][:[port]][path_script]
        TYPO3_REQUEST_DIR =		[scheme]://[host][:[port]][path_dir]
        TYPO3_SITE_URL = 		[scheme]://[host][:[port]][path_dir] of the TYPO3 website frontend
        TYPO3_SITE_PATH = 		[path_dir] of the TYPO3 website frontend
        TYPO3_SITE_SCRIPT = 		[script / Speaking URL] of the TYPO3 website
        TYPO3_DOCUMENT_ROOT =		Absolute path of root of documents: TYPO3_DOCUMENT_ROOT.SCRIPT_NAME = SCRIPT_FILENAME (typically)
        TYPO3_SSL = 			Returns TRUE if this session uses SSL/TLS (https)
        TYPO3_PROXY = 			Returns TRUE if this session runs over a well known proxyNotice: [fragment] is apparently NEVER available to the script!Testing suggestions:
        - Output all the values.
        - In the script, make a link to the script it self, maybe add some parameters and click the link a few times so HTTP_REFERER is seen
        - ALSO TRY the script from the ROOT of a site (like 'http://www.mytest.com/' and not 'http://www.mytest.com/test/' !!)
         */
        $retVal = '';
        switch ((string)$getEnvName) {
            case 'SCRIPT_NAME':
                $retVal = Environment::isRunningOnCgiServer()
                    && (($_SERVER['ORIG_PATH_INFO'] ?? false) ?: ($_SERVER['PATH_INFO'] ?? false))
                        ? (($_SERVER['ORIG_PATH_INFO'] ?? '') ?: ($_SERVER['PATH_INFO'] ?? ''))
                        : (($_SERVER['ORIG_SCRIPT_NAME'] ?? '') ?: ($_SERVER['SCRIPT_NAME'] ?? ''));
                // Add a prefix if TYPO3 is behind a proxy: ext-domain.com => int-server.com/prefix
                if (self::cmpIP($_SERVER['REMOTE_ADDR'] ?? '', $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'] ?? '')) {
                    if (self::getIndpEnv('TYPO3_SSL') && $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefixSSL']) {
                        $retVal = $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefixSSL'] . $retVal;
                    } elseif ($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefix']) {
                        $retVal = $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefix'] . $retVal;
                    }
                }
                break;
            case 'SCRIPT_FILENAME':
                $retVal = Environment::getCurrentScript();
                break;
            case 'REQUEST_URI':
                // Typical application of REQUEST_URI is return urls, forms submitting to itself etc. Example: returnUrl='.rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'))
                if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['requestURIvar'])) {
                    // This is for URL rewriters that store the original URI in a server variable (eg ISAPI_Rewriter for IIS: HTTP_X_REWRITE_URL)
                    [$v, $n] = explode('|', $GLOBALS['TYPO3_CONF_VARS']['SYS']['requestURIvar']);
                    $retVal = $GLOBALS[$v][$n];
                } elseif (empty($_SERVER['REQUEST_URI'])) {
                    // This is for ISS/CGI which does not have the REQUEST_URI available.
                    $retVal = '/' . ltrim(self::getIndpEnv('SCRIPT_NAME'), '/') . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
                } else {
                    $retVal = '/' . ltrim($_SERVER['REQUEST_URI'], '/');
                }
                // Add a prefix if TYPO3 is behind a proxy: ext-domain.com => int-server.com/prefix
                if (isset($_SERVER['REMOTE_ADDR'], $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'])
                    && self::cmpIP($_SERVER['REMOTE_ADDR'], $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'])
                ) {
                    if (self::getIndpEnv('TYPO3_SSL') && $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefixSSL']) {
                        $retVal = $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefixSSL'] . $retVal;
                    } elseif ($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefix']) {
                        $retVal = $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefix'] . $retVal;
                    }
                }
                break;
            case 'PATH_INFO':
                // $_SERVER['PATH_INFO'] != $_SERVER['SCRIPT_NAME'] is necessary because some servers (Windows/CGI)
                // are seen to set PATH_INFO equal to script_name
                // Further, there must be at least one '/' in the path - else the PATH_INFO value does not make sense.
                // IF 'PATH_INFO' never works for our purpose in TYPO3 with CGI-servers,
                // then 'PHP_SAPI=='cgi'' might be a better check.
                // Right now strcmp($_SERVER['PATH_INFO'], GeneralUtility::getIndpEnv('SCRIPT_NAME')) will always
                // return FALSE for CGI-versions, but that is only as long as SCRIPT_NAME is set equal to PATH_INFO
                // because of PHP_SAPI=='cgi' (see above)
                if (!Environment::isRunningOnCgiServer()) {
                    $retVal = $_SERVER['PATH_INFO'] ?? '';
                }
                break;
            case 'TYPO3_REV_PROXY':
                $retVal = self::cmpIP($_SERVER['REMOTE_ADDR'] ?? '', $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']);
                break;
            case 'REMOTE_ADDR':
                $retVal = $_SERVER['REMOTE_ADDR'] ?? '';
                if (self::cmpIP($retVal, $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'] ?? '')) {
                    $ip = self::trimExplode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
                    // Choose which IP in list to use
                    if (!empty($ip)) {
                        switch ($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyHeaderMultiValue']) {
                            case 'last':
                                $ip = array_pop($ip);
                                break;
                            case 'first':
                                $ip = array_shift($ip);
                                break;
                            case 'none':

                            default:
                                $ip = '';
                        }
                    }
                    if (self::validIP((string)$ip)) {
                        $retVal = $ip;
                    }
                }
                break;
            case 'HTTP_HOST':
                // if it is not set we're most likely on the cli
                $retVal = $_SERVER['HTTP_HOST'] ?? '';
                if (isset($_SERVER['REMOTE_ADDR']) && static::cmpIP($_SERVER['REMOTE_ADDR'], $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'])) {
                    $host = self::trimExplode(',', $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '');
                    // Choose which host in list to use
                    if (!empty($host)) {
                        switch ($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyHeaderMultiValue']) {
                            case 'last':
                                $host = array_pop($host);
                                break;
                            case 'first':
                                $host = array_shift($host);
                                break;
                            case 'none':

                            default:
                                $host = '';
                        }
                    }
                    if ($host) {
                        $retVal = $host;
                    }
                }
                break;
            case 'HTTP_REFERER':

            case 'HTTP_USER_AGENT':

            case 'HTTP_ACCEPT_ENCODING':

            case 'HTTP_ACCEPT_LANGUAGE':

            case 'REMOTE_HOST':

            case 'QUERY_STRING':
                $retVal = $_SERVER[$getEnvName] ?? '';
                break;
            case 'TYPO3_DOCUMENT_ROOT':
                // Get the web root (it is not the root of the TYPO3 installation)
                // The absolute path of the script can be calculated with TYPO3_DOCUMENT_ROOT + SCRIPT_FILENAME
                // Some CGI-versions (LA13CGI) and mod-rewrite rules on MODULE versions will deliver a 'wrong' DOCUMENT_ROOT (according to our description). Further various aliases/mod_rewrite rules can disturb this as well.
                // Therefore the DOCUMENT_ROOT is now always calculated as the SCRIPT_FILENAME minus the end part shared with SCRIPT_NAME.
                $SFN = self::getIndpEnv('SCRIPT_FILENAME');
                $SN_A = explode('/', strrev(self::getIndpEnv('SCRIPT_NAME')));
                $SFN_A = explode('/', strrev($SFN));
                $acc = [];
                foreach ($SN_A as $kk => $vv) {
                    if ((string)$SFN_A[$kk] === (string)$vv) {
                        $acc[] = $vv;
                    } else {
                        break;
                    }
                }
                $commonEnd = strrev(implode('/', $acc));
                if ((string)$commonEnd !== '') {
                    $retVal = substr($SFN, 0, -(strlen($commonEnd) + 1));
                }
                break;
            case 'TYPO3_HOST_ONLY':
                $httpHost = self::getIndpEnv('HTTP_HOST');
                $httpHostBracketPosition = strpos($httpHost, ']');
                $httpHostParts = explode(':', $httpHost);
                $retVal = $httpHostBracketPosition !== false ? substr($httpHost, 0, $httpHostBracketPosition + 1) : array_shift($httpHostParts);
                break;
            case 'TYPO3_PORT':
                $httpHost = self::getIndpEnv('HTTP_HOST');
                $httpHostOnly = self::getIndpEnv('TYPO3_HOST_ONLY');
                $retVal = strlen($httpHost) > strlen($httpHostOnly) ? substr($httpHost, strlen($httpHostOnly) + 1) : '';
                break;
            case 'TYPO3_REQUEST_HOST':
                $retVal = (self::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://') . self::getIndpEnv('HTTP_HOST');
                break;
            case 'TYPO3_REQUEST_URL':
                $retVal = self::getIndpEnv('TYPO3_REQUEST_HOST') . self::getIndpEnv('REQUEST_URI');
                break;
            case 'TYPO3_REQUEST_SCRIPT':
                $retVal = self::getIndpEnv('TYPO3_REQUEST_HOST') . self::getIndpEnv('SCRIPT_NAME');
                break;
            case 'TYPO3_REQUEST_DIR':
                $retVal = self::getIndpEnv('TYPO3_REQUEST_HOST') . self::dirname(self::getIndpEnv('SCRIPT_NAME')) . '/';
                break;
            case 'TYPO3_SITE_URL':
                if (Environment::getCurrentScript()) {
                    $lPath = PathUtility::stripPathSitePrefix(PathUtility::dirnameDuringBootstrap(Environment::getCurrentScript())) . '/';
                    $url = self::getIndpEnv('TYPO3_REQUEST_DIR');
                    $siteUrl = substr($url, 0, -strlen($lPath));
                    if (substr($siteUrl, -1) !== '/') {
                        $siteUrl .= '/';
                    }
                    $retVal = $siteUrl;
                }
                break;
            case 'TYPO3_SITE_PATH':
                $retVal = substr(self::getIndpEnv('TYPO3_SITE_URL'), strlen(self::getIndpEnv('TYPO3_REQUEST_HOST')));
                break;
            case 'TYPO3_SITE_SCRIPT':
                $retVal = substr(self::getIndpEnv('TYPO3_REQUEST_URL'), strlen(self::getIndpEnv('TYPO3_SITE_URL')));
                break;
            case 'TYPO3_SSL':
                $proxySSL = trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxySSL'] ?? '');
                if ($proxySSL === '*') {
                    $proxySSL = $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'];
                }
                if (self::cmpIP($_SERVER['REMOTE_ADDR'] ?? '', $proxySSL)) {
                    $retVal = true;
                } else {
                    $retVal = self::webserverUsesHttps();
                }
                break;
            case '_ARRAY':
                $out = [];
                // Here, list ALL possible keys to this function for debug display.
                $envTestVars = [
                    'HTTP_HOST',
                    'TYPO3_HOST_ONLY',
                    'TYPO3_PORT',
                    'PATH_INFO',
                    'QUERY_STRING',
                    'REQUEST_URI',
                    'HTTP_REFERER',
                    'TYPO3_REQUEST_HOST',
                    'TYPO3_REQUEST_URL',
                    'TYPO3_REQUEST_SCRIPT',
                    'TYPO3_REQUEST_DIR',
                    'TYPO3_SITE_URL',
                    'TYPO3_SITE_SCRIPT',
                    'TYPO3_SSL',
                    'TYPO3_REV_PROXY',
                    'SCRIPT_NAME',
                    'TYPO3_DOCUMENT_ROOT',
                    'SCRIPT_FILENAME',
                    'REMOTE_ADDR',
                    'REMOTE_HOST',
                    'HTTP_USER_AGENT',
                    'HTTP_ACCEPT_LANGUAGE',
                ];
                foreach ($envTestVars as $v) {
                    $out[$v] = self::getIndpEnv($v);
                }
                reset($out);
                $retVal = $out;
                break;
        }
        self::$indpEnvCache[$getEnvName] = $retVal;
        return $retVal;
    }

    /**
     * Checks if the provided host header value matches the trusted hosts pattern.
     *
     * @param string $hostHeaderValue HTTP_HOST header value as sent during the request (may include port)
     * @return bool
     * @deprecated will be removed in TYPO3 v12.0.
     */
    public static function isAllowedHostHeaderValue($hostHeaderValue)
    {
        trigger_error('GeneralUtility::isAllowedHostHeaderValue() will be removed in TYPO3 v12.0. Host header is verified by frontend and backend middlewares.', E_USER_DEPRECATED);

        $verifyHostHeader = new VerifyHostHeader($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] ?? '');
        return $verifyHostHeader->isAllowedHostHeaderValue($hostHeaderValue, $_SERVER);
    }

    /**
     * Determine if the webserver uses HTTPS.
     *
     * HEADS UP: This does not check if the client performed a
     * HTTPS request, as possible proxies are not taken into
     * account. It provides raw information about the current
     * webservers configuration only.
     *
     * @return bool
     */
    protected static function webserverUsesHttps()
    {
        if (!empty($_SERVER['SSL_SESSION_ID'])) {
            return true;
        }

        // https://secure.php.net/manual/en/reserved.variables.server.php
        // "Set to a non-empty value if the script was queried through the HTTPS protocol."
        return !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
    }

    /*************************
     *
     * TYPO3 SPECIFIC FUNCTIONS
     *
     *************************/
    /**
     * Returns the absolute filename of a relative reference, resolves the "EXT:" prefix
     * (way of referring to files inside extensions) and checks that the file is inside
     * the TYPO3's base folder and implies a check with
     * \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr().
     *
     * @param string $filename The input filename/filepath to evaluate
     * @return string Returns the absolute filename of $filename if valid, otherwise blank string.
     */
    public static function getFileAbsFileName($filename)
    {
        if ((string)$filename === '') {
            return '';
        }
        // Extension
        if (PathUtility::isExtensionPath($filename)) {
            try {
                $filename = ExtensionManagementUtility::resolvePackagePath($filename);
            } catch (PackageException $e) {
                $filename = '';
            }
        } elseif (!PathUtility::isAbsolutePath($filename)) {
            // is relative. Prepended with the public web folder
            $filename = Environment::getPublicPath() . '/' . $filename;
        } elseif (!(
            str_starts_with($filename, Environment::getProjectPath())
                  || str_starts_with($filename, Environment::getPublicPath())
        )) {
            // absolute, but set to blank if not allowed
            $filename = '';
        }
        if ((string)$filename !== '' && static::validPathStr($filename)) {
            // checks backpath.
            return $filename;
        }
        return '';
    }

    /**
     * Checks for malicious file paths.
     *
     * Returns TRUE if no '//', '..', '\' or control characters are found in the $theFile.
     * This should make sure that the path is not pointing 'backwards' and further doesn't contain double/back slashes.
     * So it's compatible with the UNIX style path strings valid for TYPO3 internally.
     *
     * @param string $theFile File path to evaluate
     * @return bool TRUE, $theFile is allowed path string, FALSE otherwise
     * @see https://php.net/manual/en/security.filesystem.nullbytes.php
     */
    public static function validPathStr($theFile)
    {
        return !str_contains($theFile, '//') && !str_contains($theFile, '\\')
            && preg_match('#(?:^\\.\\.|/\\.\\./|[[:cntrl:]])#u', $theFile) === 0;
    }

    /**
     * Checks if the $path is absolute or relative (detecting either '/' or 'x:/' as first part of string) and returns TRUE if so.
     *
     * @param string $path File path to evaluate
     * @return bool
     * @deprecated will be removed in TYPO3 v12.0. Use PathUtility::isAbsolutePath() instead.
     */
    public static function isAbsPath($path)
    {
        trigger_error('GeneralUtility::isAbsPath() will be removed in TYPO3 v12.0. Use PathUtility::isAbsolutePath() instead.', E_USER_DEPRECATED);
        if (substr($path, 0, 6) === 'vfs://') {
            return true;
        }
        return
            (isset($path[0]) && $path[0] === '/')
            || (Environment::isWindows() && (strpos($path, ':/') === 1))
            || strpos($path, ':\\') === 1;
    }

    /**
     * Returns TRUE if the path is absolute, without backpath '..' and within TYPO3s project or public folder OR within the lockRootPath
     *
     * @param string $path File path to evaluate
     * @return bool
     */
    public static function isAllowedAbsPath($path)
    {
        if (substr($path, 0, 6) === 'vfs://') {
            return true;
        }
        $lockRootPath = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'] ?? '';
        return PathUtility::isAbsolutePath($path) && static::validPathStr($path)
            && (
                str_starts_with($path, Environment::getProjectPath())
                || str_starts_with($path, Environment::getPublicPath())
                || ($lockRootPath && str_starts_with($path, $lockRootPath))
            );
    }

    /**
     * Low level utility function to copy directories and content recursive
     *
     * @param string $source Path to source directory, relative to document root or absolute
     * @param string $destination Path to destination directory, relative to document root or absolute
     */
    public static function copyDirectory($source, $destination)
    {
        if (!str_contains($source, Environment::getProjectPath() . '/')) {
            $source = Environment::getPublicPath() . '/' . $source;
        }
        if (!str_contains($destination, Environment::getProjectPath() . '/')) {
            $destination = Environment::getPublicPath() . '/' . $destination;
        }
        if (static::isAllowedAbsPath($source) && static::isAllowedAbsPath($destination)) {
            static::mkdir_deep($destination);
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            /** @var \SplFileInfo $item */
            foreach ($iterator as $item) {
                $target = $destination . '/' . static::fixWindowsFilePath($iterator->getSubPathName());
                if ($item->isDir()) {
                    static::mkdir($target);
                } else {
                    static::upload_copy_move(static::fixWindowsFilePath($item->getPathname()), $target);
                }
            }
        }
    }

    /**
     * Checks if a given string is a valid frame URL to be loaded in the
     * backend.
     *
     * If the given url is empty or considered to be harmless, it is returned
     * as is, else the event is logged and an empty string is returned.
     *
     * @param string $url potential URL to check
     * @return string $url or empty string
     */
    public static function sanitizeLocalUrl($url = '')
    {
        $sanitizedUrl = '';
        if (!empty($url)) {
            $decodedUrl = rawurldecode($url);
            $parsedUrl = parse_url($decodedUrl);
            $testAbsoluteUrl = self::resolveBackPath($decodedUrl);
            $testRelativeUrl = self::resolveBackPath(self::dirname(self::getIndpEnv('SCRIPT_NAME')) . '/' . $decodedUrl);
            // Pass if URL is on the current host:
            if (self::isValidUrl($decodedUrl)) {
                if (self::isOnCurrentHost($decodedUrl) && strpos($decodedUrl, self::getIndpEnv('TYPO3_SITE_URL')) === 0) {
                    $sanitizedUrl = $url;
                }
            } elseif (PathUtility::isAbsolutePath($decodedUrl) && self::isAllowedAbsPath($decodedUrl)) {
                $sanitizedUrl = $url;
            } elseif (strpos($testAbsoluteUrl, self::getIndpEnv('TYPO3_SITE_PATH')) === 0 && $decodedUrl[0] === '/' &&
                substr($decodedUrl, 0, 2) !== '//'
            ) {
                $sanitizedUrl = $url;
            } elseif (empty($parsedUrl['scheme']) && strpos($testRelativeUrl, self::getIndpEnv('TYPO3_SITE_PATH')) === 0
                && $decodedUrl[0] !== '/' && strpbrk($decodedUrl, '*:|"<>') === false && !str_contains($decodedUrl, '\\\\')
            ) {
                $sanitizedUrl = $url;
            }
        }
        if (!empty($url) && empty($sanitizedUrl)) {
            static::getLogger()->notice('The URL "{url}" is not considered to be local and was denied.', ['url' => $url]);
        }
        return $sanitizedUrl;
    }

    /**
     * Moves $source file to $destination if uploaded, otherwise try to make a copy
     *
     * @param string $source Source file, absolute path
     * @param string $destination Destination file, absolute path
     * @return bool Returns TRUE if the file was moved.
     * @see upload_to_tempfile()
     */
    public static function upload_copy_move($source, $destination)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Utility\GeneralUtility::class]['moveUploadedFile'] ?? null)) {
            $params = ['source' => $source, 'destination' => $destination, 'method' => 'upload_copy_move'];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Utility\GeneralUtility::class]['moveUploadedFile'] as $hookMethod) {
                $fakeThis = null;
                self::callUserFunction($hookMethod, $params, $fakeThis);
            }
        }

        $result = false;
        if (is_uploaded_file($source)) {
            // Return the value of move_uploaded_file, and if FALSE the temporary $source is still
            // around so the user can use unlink to delete it:
            $result = move_uploaded_file($source, $destination);
        } else {
            @copy($source, $destination);
        }
        // Change the permissions of the file
        self::fixPermissions($destination);
        // If here the file is copied and the temporary $source is still around,
        // so when returning FALSE the user can try unlink to delete the $source
        return $result;
    }

    /**
     * Will move an uploaded file (normally in "/tmp/xxxxx") to a temporary filename in Environment::getProjectPath() . "var/" from where TYPO3 can use it.
     * Use this function to move uploaded files to where you can work on them.
     * REMEMBER to use \TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile() afterwards - otherwise temp-files will build up! They are NOT automatically deleted in the temporary folder!
     *
     * @param string $uploadedFileName The temporary uploaded filename, eg. $_FILES['[upload field name here]']['tmp_name']
     * @return string If a new file was successfully created, return its filename, otherwise blank string.
     * @see unlink_tempfile()
     * @see upload_copy_move()
     */
    public static function upload_to_tempfile($uploadedFileName)
    {
        if (is_uploaded_file($uploadedFileName)) {
            $tempFile = self::tempnam('upload_temp_');
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Utility\GeneralUtility::class]['moveUploadedFile'] ?? null)) {
                $params = ['source' => $uploadedFileName, 'destination' => $tempFile, 'method' => 'upload_to_tempfile'];
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Utility\GeneralUtility::class]['moveUploadedFile'] as $hookMethod) {
                    $fakeThis = null;
                    self::callUserFunction($hookMethod, $params, $fakeThis);
                }
            }

            move_uploaded_file($uploadedFileName, $tempFile);
            return @is_file($tempFile) ? $tempFile : '';
        }

        return '';
    }

    /**
     * Deletes (unlink) a temporary filename in the var/ or typo3temp folder given as input.
     * The function will check that the file exists, is within TYPO3's var/ or typo3temp/ folder and does not contain back-spaces ("../") so it should be pretty safe.
     * Use this after upload_to_tempfile() or tempnam() from this class!
     *
     * @param string $uploadedTempFileName absolute file path - must reside within var/ or typo3temp/ folder.
     * @return bool|null Returns TRUE if the file was unlink()'ed
     * @see upload_to_tempfile()
     * @see tempnam()
     */
    public static function unlink_tempfile($uploadedTempFileName)
    {
        if ($uploadedTempFileName) {
            $uploadedTempFileName = self::fixWindowsFilePath($uploadedTempFileName);
            if (
                self::validPathStr($uploadedTempFileName)
                && (
                    str_starts_with($uploadedTempFileName, Environment::getPublicPath() . '/typo3temp/')
                    || str_starts_with($uploadedTempFileName, Environment::getVarPath() . '/')
                )
                && @is_file($uploadedTempFileName)
            ) {
                if (unlink($uploadedTempFileName)) {
                    return true;
                }
            }
        }

        return null;
    }

    /**
     * Create temporary filename (Create file with unique file name)
     * This function should be used for getting temporary file names - will make your applications safe for open_basedir = on
     * REMEMBER to delete the temporary files after use! This is done by \TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile()
     *
     * @param string $filePrefix Prefix for temporary file
     * @param string $fileSuffix Suffix for temporary file, for example a special file extension
     * @return non-empty-string result from PHP function `tempnam()` with the temp/var folder prefixed.
     * @see unlink_tempfile()
     * @see upload_to_tempfile()
     */
    public static function tempnam($filePrefix, $fileSuffix = '')
    {
        $temporaryPath = Environment::getVarPath() . '/transient/';
        if (!is_dir($temporaryPath)) {
            self::mkdir_deep($temporaryPath);
        }
        if ($fileSuffix === '') {
            $path = (string)tempnam($temporaryPath, $filePrefix);
            $tempFileName = $temporaryPath . PathUtility::basename($path);
        } else {
            do {
                $tempFileName = $temporaryPath . $filePrefix . random_int(1, PHP_INT_MAX) . $fileSuffix;
            } while (file_exists($tempFileName));
            touch($tempFileName);
            clearstatcache(false, $tempFileName);
        }
        return $tempFileName;
    }

    /**
     * Standard authentication code (used in Direct Mail, checkJumpUrl and setfixed links computations)
     *
     * @param mixed $uid_or_record Uid (int) or record (array)
     * @param string $fields List of fields from the record if that is given.
     * @param int $codeLength Length of returned authentication code.
     * @return string MD5 hash of 8 chars.
     * @deprecated since v11, will be removed in v12.
     */
    public static function stdAuthCode($uid_or_record, $fields = '', $codeLength = 8)
    {
        trigger_error(
            'GeneralUtility::stdAuthCode() is deprecated and will be removed in v12.',
            E_USER_DEPRECATED
        );

        if (is_array($uid_or_record)) {
            $recCopy_temp = [];
            if ($fields) {
                $fieldArr = self::trimExplode(',', $fields, true);
                foreach ($fieldArr as $k => $v) {
                    $recCopy_temp[$k] = $uid_or_record[$v];
                }
            } else {
                $recCopy_temp = $uid_or_record;
            }
            $preKey = implode('|', $recCopy_temp);
        } else {
            $preKey = $uid_or_record;
        }
        $authCode = $preKey . '||' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        $authCode = substr(md5($authCode), 0, $codeLength);
        return $authCode;
    }

    /**
     * Responds on input localization setting value whether the page it comes from should be hidden if no translation exists or not.
     *
     * @param int $l18n_cfg_fieldValue Value from "l18n_cfg" field of a page record
     * @return bool TRUE if the page should be hidden
     * @deprecated since TYPO3 v11, will be removed in TYPO3 v12. Use PageTranslationVisibility BitSet instead.
     */
    public static function hideIfNotTranslated($l18n_cfg_fieldValue)
    {
        trigger_error('GeneralUtility::hideIfNotTranslated() will be removed in TYPO3 v12, use the PageTranslationVisibility BitSet API instead.', E_USER_DEPRECATED);
        return $GLOBALS['TYPO3_CONF_VARS']['FE']['hidePagesIfNotTranslatedByDefault'] xor ($l18n_cfg_fieldValue & 2);
    }

    /**
     * Returns true if the "l18n_cfg" field value is not set to hide
     * pages in the default language
     *
     * @param int $localizationConfiguration
     * @return bool
     * @deprecated since TYPO3 v11, will be removed in TYPO3 v12. Use PageTranslationVisibility BitSet instead.
     */
    public static function hideIfDefaultLanguage($localizationConfiguration)
    {
        trigger_error('GeneralUtility::hideIfDefaultLanguage() will be removed in TYPO3 v12, use the PageTranslationVisibility BitSet API instead.', E_USER_DEPRECATED);
        return (bool)($localizationConfiguration & 1);
    }

    /**
     * Calls a user-defined function/method in class
     * Such a function/method should look like this: "function proc(&$params, &$ref) {...}"
     *
     * @param non-empty-string|\Closure $funcName Function/Method reference or Closure.
     * @param mixed $params Parameters to be pass along (typically an array) (REFERENCE!)
     * @param object|null $ref Reference to be passed along (typically "$this" - being a reference to the calling object)
     * @return mixed Content from method/function call
     * @throws \InvalidArgumentException
     */
    public static function callUserFunction($funcName, &$params, ?object $ref = null)
    {
        // Check if we're using a closure and invoke it directly.
        if (is_object($funcName) && is_a($funcName, \Closure::class)) {
            return call_user_func_array($funcName, [&$params, &$ref]);
        }
        $funcName = trim($funcName);
        $parts = explode('->', $funcName);
        // Call function or method
        if (count($parts) === 2) {
            // It's a class/method
            // Check if class/method exists:
            if (class_exists($parts[0])) {
                // Create object
                $classObj = self::makeInstance($parts[0]);
                $methodName = (string)$parts[1];
                $callable = [$classObj, $methodName];
                if (is_callable($callable)) {
                    // Call method:
                    $content = call_user_func_array($callable, [&$params, &$ref]);
                } else {
                    throw new \InvalidArgumentException('No method name \'' . $parts[1] . '\' in class ' . $parts[0], 1294585865);
                }
            } else {
                throw new \InvalidArgumentException('No class named ' . $parts[0], 1294585866);
            }
        } elseif (function_exists($funcName) && is_callable($funcName)) {
            // It's a function
            $content = call_user_func_array($funcName, [&$params, &$ref]);
        } else {
            // Usually this will be annotated by static code analysis tools, but there's no native "not empty string" type
            throw new \InvalidArgumentException('No function named: ' . $funcName, 1294585867);
        }
        return $content;
    }

    /**
     * @param ContainerInterface $container
     * @internal
     */
    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * @return ContainerInterface
     * @internal
     */
    public static function getContainer(): ContainerInterface
    {
        if (self::$container === null) {
            throw new \LogicException('PSR-11 Container is not available', 1549404144);
        }
        return self::$container;
    }

    /**
     * Creates an instance of a class taking into account the class-extensions
     * API of TYPO3. USE THIS method instead of the PHP "new" keyword.
     * Eg. "$obj = new myclass;" should be "$obj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("myclass")" instead!
     *
     * You can also pass arguments for a constructor:
     * \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\myClass::class, $arg1, $arg2, ..., $argN)
     *
     * @template T of object
     * @param string|class-string<T> $className name of the class to instantiate, must not be empty and not start with a backslash
     * @param array<int, mixed> $constructorArguments Arguments for the constructor
     * @return T the created instance
     * @throws \InvalidArgumentException if $className is empty or starts with a backslash
     */
    public static function makeInstance($className, ...$constructorArguments)
    {
        if (!is_string($className) || empty($className)) {
            throw new \InvalidArgumentException('$className must be a non empty string.', 1288965219);
        }
        // Never instantiate with a beginning backslash, otherwise things like singletons won't work.
        if ($className[0] === '\\') {
            throw new \InvalidArgumentException(
                '$className "' . $className . '" must not start with a backslash.',
                1420281366
            );
        }
        if (isset(static::$finalClassNameCache[$className])) {
            $finalClassName = static::$finalClassNameCache[$className];
        } else {
            $finalClassName = self::getClassName($className);
            static::$finalClassNameCache[$className] = $finalClassName;
        }
        // Return singleton instance if it is already registered
        if (isset(self::$singletonInstances[$finalClassName])) {
            return self::$singletonInstances[$finalClassName];
        }
        // Return instance if it has been injected by addInstance()
        if (
            isset(self::$nonSingletonInstances[$finalClassName])
            && !empty(self::$nonSingletonInstances[$finalClassName])
        ) {
            return array_shift(self::$nonSingletonInstances[$finalClassName]);
        }

        // Read service and prototypes from the DI container, this is required to
        // support classes that require dependency injection.
        // We operate on the original class name on purpose, as class overrides
        // are resolved inside the container
        if (self::$container !== null && $constructorArguments === [] && self::$container->has($className)) {
            return self::$container->get($className);
        }

        // Create new instance and call constructor with parameters
        $instance = new $finalClassName(...$constructorArguments);
        // Register new singleton instance, but only if it is not a known PSR-11 container service
        if ($instance instanceof SingletonInterface && !(self::$container !== null && self::$container->has($className))) {
            self::$singletonInstances[$finalClassName] = $instance;
        }
        if ($instance instanceof LoggerAwareInterface) {
            $instance->setLogger(static::makeInstance(LogManager::class)->getLogger($className));
        }
        return $instance;
    }

    /**
     * Creates a class taking implementation settings and class aliases into account.
     *
     * Intended to be used to create objects by the dependency injection
     * container.
     *
     * @template T of object
     * @param string|class-string<T> $className name of the class to instantiate
     * @param array<int, mixed> $constructorArguments Arguments for the constructor
     * @return T the created instance
     * @internal
     */
    public static function makeInstanceForDi(string $className, ...$constructorArguments): object
    {
        $finalClassName = static::$finalClassNameCache[$className] ?? static::$finalClassNameCache[$className] = self::getClassName($className);

        // Return singleton instance if it is already registered (currently required for unit and functional tests)
        if (isset(self::$singletonInstances[$finalClassName])) {
            return self::$singletonInstances[$finalClassName];
        }
        // Create new instance and call constructor with parameters
        return new $finalClassName(...$constructorArguments);
    }

    /**
     * Returns the class name for a new instance, taking into account
     * registered implementations for this class
     *
     * @param string $className Base class name to evaluate
     * @return class-string Final class name to instantiate with "new [classname]
     */
    protected static function getClassName($className)
    {
        if (class_exists($className)) {
            while (static::classHasImplementation($className)) {
                $className = static::getImplementationForClass($className);
            }
        }
        return ClassLoadingInformation::getClassNameForAlias($className);
    }

    /**
     * Returns the configured implementation of the class
     *
     * @param string $className
     * @return string
     */
    protected static function getImplementationForClass($className)
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$className]['className'];
    }

    /**
     * Checks if a class has a configured implementation
     *
     * @param string $className
     * @return bool
     */
    protected static function classHasImplementation($className)
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$className]['className']);
    }

    /**
     * Sets the instance of a singleton class to be returned by makeInstance.
     *
     * If this function is called multiple times for the same $className,
     * makeInstance will return the last set instance.
     *
     * Warning:
     * This is NOT a public API method and must not be used in own extensions!
     * This methods exists mostly for unit tests to inject a mock of a singleton class.
     * If you use this, make sure to always combine this with getSingletonInstances()
     * and resetSingletonInstances() in setUp() and tearDown() of the test class.
     *
     * @see makeInstance
     * @param string $className
     * @param \TYPO3\CMS\Core\SingletonInterface $instance
     * @internal
     */
    public static function setSingletonInstance($className, SingletonInterface $instance)
    {
        self::checkInstanceClassName($className, $instance);
        // Check for XCLASS registration (same is done in makeInstance() in order to store the singleton of the final class name)
        $finalClassName = self::getClassName($className);
        self::$singletonInstances[$finalClassName] = $instance;
    }

    /**
     * Removes the instance of a singleton class to be returned by makeInstance.
     *
     * Warning:
     * This is NOT a public API method and must not be used in own extensions!
     * This methods exists mostly for unit tests to inject a mock of a singleton class.
     * If you use this, make sure to always combine this with getSingletonInstances()
     * and resetSingletonInstances() in setUp() and tearDown() of the test class.
     *
     * @see makeInstance
     * @throws \InvalidArgumentException
     * @param string $className
     * @param \TYPO3\CMS\Core\SingletonInterface $instance
     * @internal
     */
    public static function removeSingletonInstance($className, SingletonInterface $instance)
    {
        self::checkInstanceClassName($className, $instance);
        if (!isset(self::$singletonInstances[$className])) {
            throw new \InvalidArgumentException('No Instance registered for ' . $className . '.', 1394099179);
        }
        if ($instance !== self::$singletonInstances[$className]) {
            throw new \InvalidArgumentException('The instance you are trying to remove has not been registered before.', 1394099256);
        }
        unset(self::$singletonInstances[$className]);
    }

    /**
     * Set a group of singleton instances. Similar to setSingletonInstance(),
     * but multiple instances can be set.
     *
     * Warning:
     * This is NOT a public API method and must not be used in own extensions!
     * This method is usually only used in tests to restore the list of singletons in
     * tearDown(), that was backed up with getSingletonInstances() in setUp() and
     * manipulated in tests with setSingletonInstance()
     *
     * @internal
     * @param array<string, SingletonInterface> $newSingletonInstances
     */
    public static function resetSingletonInstances(array $newSingletonInstances)
    {
        static::$singletonInstances = [];
        foreach ($newSingletonInstances as $className => $instance) {
            static::setSingletonInstance($className, $instance);
        }
    }

    /**
     * Get all currently registered singletons
     *
     * Warning:
     * This is NOT a public API method and must not be used in own extensions!
     * This method is usually only used in tests in setUp() to fetch the list of
     * currently registered singletons, if this list is manipulated with
     * setSingletonInstance() in tests.
     *
     * @internal
     * @return array<string, SingletonInterface>
     */
    public static function getSingletonInstances()
    {
        return static::$singletonInstances;
    }

    /**
     * Get all currently registered non singleton instances
     *
     * Warning:
     * This is NOT a public API method and must not be used in own extensions!
     * This method is only used in UnitTestCase base test tearDown() to verify tests
     * have no left over instances that were previously added using addInstance().
     *
     * @internal
     * @return array<string, array<object>>
     */
    public static function getInstances()
    {
        return static::$nonSingletonInstances;
    }

    /**
     * Sets the instance of a non-singleton class to be returned by makeInstance.
     *
     * If this function is called multiple times for the same $className,
     * makeInstance will return the instances in the order in which they have
     * been added (FIFO).
     *
     * Warning: This is a helper method for unit tests. Do not call this directly in production code!
     *
     * @see makeInstance
     * @throws \InvalidArgumentException if class extends \TYPO3\CMS\Core\SingletonInterface
     * @param string $className
     * @param object $instance
     */
    public static function addInstance($className, $instance)
    {
        self::checkInstanceClassName($className, $instance);
        if ($instance instanceof SingletonInterface) {
            throw new \InvalidArgumentException('$instance must not be an instance of TYPO3\\CMS\\Core\\SingletonInterface. For setting singletons, please use setSingletonInstance.', 1288969325);
        }
        if (!isset(self::$nonSingletonInstances[$className])) {
            self::$nonSingletonInstances[$className] = [];
        }
        self::$nonSingletonInstances[$className][] = $instance;
    }

    /**
     * Checks that $className is non-empty and that $instance is an instance of
     * $className.
     *
     * @throws \InvalidArgumentException if $className is empty or if $instance is no instance of $className
     * @param string $className a class name
     * @param object $instance an object
     */
    protected static function checkInstanceClassName($className, $instance)
    {
        if ($className === '') {
            throw new \InvalidArgumentException('$className must not be empty.', 1288967479);
        }
        if (!$instance instanceof $className) {
            throw new \InvalidArgumentException('$instance must be an instance of ' . $className . ', but actually is an instance of ' . get_class($instance) . '.', 1288967686);
        }
    }

    /**
     * Purge all instances returned by makeInstance.
     *
     * This function is most useful when called from tearDown in a test case
     * to drop any instances that have been created by the tests.
     *
     * Warning: This is a helper method for unit tests. Do not call this directly in production code!
     *
     * @see makeInstance
     */
    public static function purgeInstances()
    {
        self::$container = null;
        self::$singletonInstances = [];
        self::$nonSingletonInstances = [];
    }

    /**
     * Flush internal runtime caches
     *
     * Used in unit tests only.
     *
     * @internal
     */
    public static function flushInternalRuntimeCaches()
    {
        self::$indpEnvCache = [];
    }

    /**
     * Find the best service and check if it works.
     * Returns object of the service class.
     *
     * This method is used for the legacy ExtensionManager:addService() mechanism,
     * not with Dependency-Injected services. In practice, all remaining core uses of
     * this mechanism are authentication services, which all have an info property.
     *
     * @param string $serviceType Type of service (service key).
     * @param string $serviceSubType Sub type like file extensions or similar. Defined by the service.
     * @param array $excludeServiceKeys List of service keys which should be excluded in the search for a service
     * @throws \RuntimeException
     * @return object|string[]|false The service object or an array with error infos, or false if no service was found.
     */
    public static function makeInstanceService($serviceType, $serviceSubType = '', array $excludeServiceKeys = [])
    {
        $error = false;
        $requestInfo = [
            'requestedServiceType' => $serviceType,
            'requestedServiceSubType' => $serviceSubType,
            'requestedExcludeServiceKeys' => $excludeServiceKeys,
        ];
        while ($info = ExtensionManagementUtility::findService($serviceType, $serviceSubType, $excludeServiceKeys)) {
            // provide information about requested service to service object
            $info = array_merge($info, $requestInfo);

            /** @var class-string<AbstractAuthenticationService> $className */
            $className = $info['className'];
            /** @var AbstractAuthenticationService $obj */
            $obj = self::makeInstance($className);
            if (is_object($obj)) {
                if (!is_callable([$obj, 'init'])) {
                    self::getLogger()->error('Requested service {class} has no init() method.', [
                        'class' => $info['className'],
                        'service' => $info,
                    ]);
                    throw new \RuntimeException('Broken service: ' . $info['className'], 1568119209);
                }
                $obj->info = $info;
                // service available?
                if ($obj->init()) {
                    return $obj;
                }
                $error = $obj->getLastErrorArray();
                unset($obj);
            }

            // deactivate the service
            ExtensionManagementUtility::deactivateService($info['serviceType'], $info['serviceKey']);
        }
        return $error;
    }

    /**
     * Quotes a string for usage as JS parameter.
     *
     * @param string $value the string to encode, may be empty
     * @return string the encoded value already quoted (with single quotes),
     */
    public static function quoteJSvalue($value)
    {
        $json = (string)json_encode(
            (string)$value,
            JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG
        );

        return strtr(
            $json,
            [
                '"' => '\'',
                '\\\\' => '\\u005C',
                ' ' => '\\u0020',
                '!' => '\\u0021',
                '\\t' => '\\u0009',
                '\\n' => '\\u000A',
                '\\r' => '\\u000D',
            ]
        );
    }

    /**
     * Serializes data to JSON, to be used in HTML attribute, e.g.
     *
     * `<div data-value="[[JSON]]">...</div>`
     * (`[[JSON]]` represents return value of this function)
     *
     * @param mixed $value
     * @param bool $useHtmlEntities
     * @return string
     */
    public static function jsonEncodeForHtmlAttribute($value, bool $useHtmlEntities = true): string
    {
        $json = (string)json_encode($value, JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG);
        return $useHtmlEntities ? htmlspecialchars($json) : $json;
    }

    /**
     * Serializes data to JSON, to be used in JavaScript instructions, e.g.
     *
     * `<script>const value = JSON.parse('[[JSON]]');</script>`
     * (`[[JSON]]` represents return value of this function)
     *
     * @param mixed $value
     * @return string
     */
    public static function jsonEncodeForJavaScript($value): string
    {
        $json = (string)json_encode($value, JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG);
        return strtr(
            $json,
            [
                // comments below refer to JSON-encoded data
                '\\\\' => '\\\\u005C', // `"\\Vendor\\Package"` -> `"\\u005CVendor\\u005CPackage"`
                '\\t' => '\\u0009', // `"\t"` -> `"\u0009"`
                '\\n' => '\\u000A', // `"\n"` -> `"\u000A"`
                '\\r' => '\\u000D', // `"\r"` -> `"\u000D"`
            ]
        );
    }

    /**
     * @return LoggerInterface
     */
    protected static function getLogger()
    {
        return static::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }
}
