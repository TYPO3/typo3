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

use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * The legendary "t3lib_div" class - Miscellaneous functions for general purpose.
 * Most of the functions do not relate specifically to TYPO3
 * However a section of functions requires certain TYPO3 features available
 * See comments in the source.
 * You are encouraged to use this library in your own scripts!
 *
 * USE:
 * The class is intended to be used without creating an instance of it.
 * So: Don't instantiate - call functions with "\TYPO3\CMS\Core\Utility\GeneralUtility::" prefixed the function name.
 * So use \TYPO3\CMS\Core\Utility\GeneralUtility::[method-name] to refer to the functions, eg. '\TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds()'
 */
class GeneralUtility
{
    // Severity constants used by \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog()
    const SYSLOG_SEVERITY_INFO = 0;
    const SYSLOG_SEVERITY_NOTICE = 1;
    const SYSLOG_SEVERITY_WARNING = 2;
    const SYSLOG_SEVERITY_ERROR = 3;
    const SYSLOG_SEVERITY_FATAL = 4;

    const ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL = '.*';
    const ENV_TRUSTED_HOSTS_PATTERN_SERVER_NAME = 'SERVER_NAME';

    /**
     * State of host header value security check
     * in order to avoid unnecessary multiple checks during one request
     *
     * @var bool
     */
    protected static $allowHostHeaderValue = false;

    /**
     * Singleton instances returned by makeInstance, using the class names as
     * array keys
     *
     * @var array<\TYPO3\CMS\Core\SingletonInterface>
     */
    protected static $singletonInstances = [];

    /**
     * Instances returned by makeInstance, using the class names as array keys
     *
     * @var array<array><object>
     */
    protected static $nonSingletonInstances = [];

    /**
     * Cache for makeInstance with given class name and final class names to reduce number of self::getClassName() calls
     *
     * @var array Given class name => final class name
     */
    protected static $finalClassNameCache = [];

    /**
     * The application context
     *
     * @var \TYPO3\CMS\Core\Core\ApplicationContext
     */
    protected static $applicationContext = null;

    /**
     * IDNA string cache
     *
     * @var array<string>
     */
    protected static $idnaStringCache = [];

    /**
     * IDNA converter
     *
     * @var \idna_convert
     */
    protected static $idnaConverter = null;

    /**
     * A list of supported CGI server APIs
     * NOTICE: This is a duplicate of the SAME array in SystemEnvironmentBuilder
     * @var array
     */
    protected static $supportedCgiServerApis = [
        'fpm-fcgi',
        'cgi',
        'isapi',
        'cgi-fcgi',
        'srv', // HHVM with fastcgi
    ];

    /**
     * @var array
     */
    protected static $indpEnvCache = [];

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
     * Returns the 'GLOBAL' value of incoming data from POST or GET, with priority to POST (that is equalent to 'GP' order)
     * To enhance security in your scripts, please consider using GeneralUtility::_GET or GeneralUtility::_POST if you already
     * know by which method your data is arriving to the scripts!
     *
     * @param string $var GET/POST var to return
     * @return mixed POST var named $var and if not set, the GET var of the same name.
     */
    public static function _GP($var)
    {
        if (empty($var)) {
            return;
        }
        if (isset($_POST[$var])) {
            $value = $_POST[$var];
        } elseif (isset($_GET[$var])) {
            $value = $_GET[$var];
        } else {
            $value = null;
        }
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
     * ALWAYS use this API function to acquire the GET variables!
     * This function was previously used to normalize between magic quotes logic, which was removed from PHP 5.5
     *
     * @param string $var Optional pointer to value in GET array (basically name of GET var)
     * @return mixed If $var is set it returns the value of $_GET[$var]. If $var is NULL (default), returns $_GET itself. In any case *slashes are stipped from the output!*
     * @see _POST(), _GP(), _GETset()
     */
    public static function _GET($var = null)
    {
        $value = $var === null ? $_GET : (empty($var) ? null : $_GET[$var]);
        // This is there for backwards-compatibility, in order to avoid NULL
        if (isset($value) && !is_array($value)) {
            $value = (string)$value;
        }
        return $value;
    }

    /**
     * Returns the global $_POST array (or value from) normalized to contain un-escaped values.
     * ALWAYS use this API function to acquire the $_POST variables!
     *
     * @param string $var Optional pointer to value in POST array (basically name of POST var)
     * @return mixed If $var is set it returns the value of $_POST[$var]. If $var is NULL (default), returns $_POST itself. In any case *slashes are stipped from the output!*
     * @see _GET(), _GP()
     */
    public static function _POST($var = null)
    {
        $value = $var === null ? $_POST : (empty($var) ? null : $_POST[$var]);
        // This is there for backwards-compatibility, in order to avoid NULL
        if (isset($value) && !is_array($value)) {
            $value = (string)$value;
        }
        return $value;
    }

    /**
     * Writes input value to $_GET.
     *
     * @param mixed $inputGet
     * @param string $key
     * @return void
     */
    public static function _GETset($inputGet, $key = '')
    {
        if ($key != '') {
            if (strpos($key, '|') !== false) {
                $pieces = explode('|', $key);
                $newGet = [];
                $pointer = &$newGet;
                foreach ($pieces as $piece) {
                    $pointer = &$pointer[$piece];
                }
                $pointer = $inputGet;
                $mergedGet = $_GET;
                ArrayUtility::mergeRecursiveWithOverrule($mergedGet, $newGet);
                $_GET = $mergedGet;
                $GLOBALS['HTTP_GET_VARS'] = $mergedGet;
            } else {
                $_GET[$key] = $inputGet;
                $GLOBALS['HTTP_GET_VARS'][$key] = $inputGet;
            }
        } elseif (is_array($inputGet)) {
            $_GET = $inputGet;
            $GLOBALS['HTTP_GET_VARS'] = $inputGet;
        }
    }

    /**
     * Wrapper for the RemoveXSS function.
     * Removes potential XSS code from an input string.
     *
     * Using an external class by Travis Puderbaugh <kallahar@quickwired.com>
     *
     * @param string $string Input string
     * @return string Input string with potential XSS code removed
     */
    public static function removeXSS($string)
    {
        return \RemoveXSS::process($string);
    }

    /*************************
     *
     * IMAGE FUNCTIONS
     *
     *************************/
    /**
     * Compressing a GIF file if not already LZW compressed.
     * This function is a workaround for the fact that ImageMagick and/or GD does not compress GIF-files to their minimun size (that is RLE or no compression used)
     *
     * The function takes a file-reference, $theFile, and saves it again through GD or ImageMagick in order to compress the file
     * GIF:
     * If $type is not set, the compression is done with ImageMagick (provided that $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'] is pointing to the path of a lzw-enabled version of 'convert') else with GD (should be RLE-enabled!)
     * If $type is set to either 'IM' or 'GD' the compression is done with ImageMagick and GD respectively
     * PNG:
     * No changes.
     *
     * $theFile is expected to be a valid GIF-file!
     * The function returns a code for the operation.
     *
     * @param string $theFile Filepath
     * @param string $type See description of function
     * @return string Returns "GD" if GD was used, otherwise "IM" if ImageMagick was used. If nothing done at all, it returns empty string.
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. Use \TYPO3\CMS\Core\Imaging\GraphicalFunctions::gifCompress() instead.
     */
    public static function gif_compress($theFile, $type)
    {
        static::logDeprecatedFunction();
        $returnCode = GraphicalFunctions::gifCompress($theFile, $type);
        return $returnCode;
    }

    /**
     * Converts a png file to gif.
     * This converts a png file to gif IF the FLAG $GLOBALS['TYPO3_CONF_VARS']['FE']['png_to_gif'] is set TRUE.
     *
     * @param string $theFile The filename with path
     * @return string New filename
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. Use \TYPO3\CMS\Core\Imaging\GraphicalFunctions::pngToGifByImagemagick() instead.
     */
    public static function png_to_gif_by_imagemagick($theFile)
    {
        static::logDeprecatedFunction();
        $newFile = GraphicalFunctions::pngToGifByImagemagick($theFile);
        return $newFile;
    }

    /**
     * Returns filename of the png/gif version of the input file (which can be png or gif).
     * If input file type does not match the wanted output type a conversion is made and temp-filename returned.
     *
     * @param string $theFile Filepath of image file
     * @param bool $output_png If set, then input file is converted to PNG, otherwise to GIF
     * @return string If the new image file exists, its filepath is returned
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. Use \TYPO3\CMS\Core\Imaging\GraphicalFunctions::readPngGif() instead.
     */
    public static function read_png_gif($theFile, $output_png = false)
    {
        static::logDeprecatedFunction();
        $newFile = GraphicalFunctions::readPngGif($theFile, $output_png);
        return $newFile;
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
     */
    public static function fixed_lgd_cs($string, $chars, $appendString = '...')
    {
        if (is_object($GLOBALS['LANG'])) {
            return $GLOBALS['LANG']->csConvObj->crop($GLOBALS['LANG']->charSet, $string, $chars, $appendString);
        } elseif (is_object($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE']->csConvObj)) {
            $charSet = $GLOBALS['TSFE']->renderCharset != '' ? $GLOBALS['TSFE']->renderCharset : $GLOBALS['TSFE']->defaultCharSet;
            return $GLOBALS['TSFE']->csConvObj->crop($charSet, $string, $chars, $appendString);
        } else {
            // This case should not happen
            $csConvObj = self::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
            return $csConvObj->crop('utf-8', $string, $chars, $appendString);
        }
    }

    /**
     * Match IP number with list of numbers with wildcard
     * Dispatcher method for switching into specialised IPv4 and IPv6 methods.
     *
     * @param string $baseIP Is the current remote IP address for instance, typ. REMOTE_ADDR
     * @param string $list Is a comma-list of IP-addresses to match with. *-wildcard allowed instead of number, plus leaving out parts in the IP number is accepted as wildcard (eg. 192.168.*.* equals 192.168). If list is "*" no check is done and the function returns TRUE immediately. An empty list always returns FALSE.
     * @return bool TRUE if an IP-mask from $list matches $baseIP
     */
    public static function cmpIP($baseIP, $list)
    {
        $list = trim($list);
        if ($list === '') {
            return false;
        } elseif ($list === '*') {
            return true;
        }
        if (strpos($baseIP, ':') !== false && self::validIPv6($baseIP)) {
            return self::cmpIPv6($baseIP, $list);
        } else {
            return self::cmpIPv4($baseIP, $list);
        }
    }

    /**
     * Match IPv4 number with list of numbers with wildcard
     *
     * @param string $baseIP Is the current remote IP address for instance, typ. REMOTE_ADDR
     * @param string $list Is a comma-list of IP-addresses to match with. *-wildcard allowed instead of number, plus leaving out parts in the IP number is accepted as wildcard (eg. 192.168.*.* equals 192.168), could also contain IPv6 addresses
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
                    list($test, $mask) = $testList;
                } else {
                    $mask = false;
                }
                if ((int)$mask) {
                    // "192.168.3.0/24"
                    $lnet = ip2long($test);
                    $lip = ip2long($baseIP);
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
     * @param string $list Is a comma-list of IPv6 prefixes, could also contain IPv4 addresses
     * @return bool TRUE If an baseIP matches any prefix
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
                list($test, $mask) = $testList;
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
                    $testBin = self::IPv6Hex2Bin($test);
                    $baseIPBin = self::IPv6Hex2Bin($baseIP);
                    $success = true;
                    // Modulo is 0 if this is a 8-bit-boundary
                    $maskIntModulo = $maskInt % 8;
                    $numFullCharactersUntilBoundary = (int)($maskInt / 8);
                    if (substr($testBin, 0, $numFullCharactersUntilBoundary) !== substr($baseIPBin, 0, $numFullCharactersUntilBoundary)) {
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
     * Transform a regular IPv6 address from hex-representation into binary
     *
     * @param string $hex IPv6 address in hex-presentation
     * @return string Binary representation (16 characters, 128 characters)
     * @see IPv6Bin2Hex()
     */
    public static function IPv6Hex2Bin($hex)
    {
        return inet_pton($hex);
    }

    /**
     * Transform an IPv6 address from binary to hex-representation
     *
     * @param string $bin IPv6 address in hex-presentation
     * @return string Binary representation (16 characters, 128 characters)
     * @see IPv6Hex2Bin()
     */
    public static function IPv6Bin2Hex($bin)
    {
        return inet_ntop($bin);
    }

    /**
     * Normalize an IPv6 address to full length
     *
     * @param string $address Given IPv6 address
     * @return string Normalized address
     * @see compressIPv6()
     */
    public static function normalizeIPv6($address)
    {
        $normalizedAddress = '';
        $stageOneAddress = '';
        // According to RFC lowercase-representation is recommended
        $address = strtolower($address);
        // Normalized representation has 39 characters (0000:0000:0000:0000:0000:0000:0000:0000)
        if (strlen($address) == 39) {
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
            if ($left == 1 && strlen($chunksLeft[0]) == 0) {
                $left = 0;
            }
            $hiddenBlocks = 8 - ($left + $right);
            $hiddenPart = '';
            $h = 0;
            while ($h < $hiddenBlocks) {
                $hiddenPart .= '0000:';
                $h++;
            }
            if ($left == 0) {
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
     * Compress an IPv6 address to the shortest notation
     *
     * @param string $address Given IPv6 address
     * @return string Compressed address
     * @see normalizeIPv6()
     */
    public static function compressIPv6($address)
    {
        return inet_ntop(inet_pton($address));
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
            $baseHostName = gethostbyaddr($baseHost);
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
                        } else {
                            $yes = false;
                        }
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
        return strpos(',' . $list . ',', ',' . $item . ',') !== false;
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
     */
    public static function rmFromList($element, $list)
    {
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
     * Returns TRUE if the current TYPO3 version (or compatibility version) is compatible to the input version
     * Notice that this function compares branches, not versions (4.0.1 would be > 4.0.0 although they use the same compat_version)
     *
     * @param string $verNumberStr Minimum branch number required (format x.y / e.g. "4.0" NOT "4.0.0"!)
     * @return bool Returns TRUE if this setup is compatible with the provided version number
     * @todo Still needs a function to convert versions to branches
     */
    public static function compat_version($verNumberStr)
    {
        return VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >= VersionNumberUtility::convertVersionNumberToInteger($verNumberStr);
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
     */
    public static function shortMD5($input, $len = 10)
    {
        return substr(md5($input), 0, $len);
    }

    /**
     * Returns a proper HMAC on a given input string and secret TYPO3 encryption key.
     *
     * @param string $input Input string to create HMAC from
     * @param string $additionalSecret additionalSecret to prevent hmac beeing used in a different context
     * @return string resulting (hexadecimal) HMAC currently with a length of 40 (HMAC-SHA-1)
     */
    public static function hmac($input, $additionalSecret = '')
    {
        $hashAlgorithm = 'sha1';
        $hashBlocksize = 64;
        $hmac = '';
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
                $key = str_pad(pack('H*', call_user_func($hashAlgorithm, $secret)), $hashBlocksize, chr(0));
            } else {
                // Keys shorter than block size are zero-padded
                $key = str_pad($secret, $hashBlocksize, chr(0));
            }
            $hmac = call_user_func($hashAlgorithm, ($key ^ $opad) . pack('H*', call_user_func($hashAlgorithm, (($key ^ $ipad) . $input))));
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
     */
    public static function uniqueList($in_list, $secondParameter = null)
    {
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
     * @param string $fileNameWithPath File name with path to be analysed (must exist if open_basedir is set)
     * @return array Contains keys [path], [file], [filebody], [fileext], [realFileext]
     */
    public static function split_fileref($fileNameWithPath)
    {
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
     * Modifies a HTML Hex color by adding/subtracting $R,$G and $B integers
     *
     * @param string $color A hexadecimal color code, #xxxxxx
     * @param int $R Offset value 0-255
     * @param int $G Offset value 0-255
     * @param int $B Offset value 0-255
     * @return string A hexadecimal color code, #xxxxxx, modified according to input vars
     * @see modifyHTMLColorAll()
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public static function modifyHTMLColor($color, $R, $G, $B)
    {
        self::logDeprecatedFunction();
        // This takes a hex-color (# included!) and adds $R, $G and $B to the HTML-color (format: #xxxxxx) and returns the new color
        $nR = MathUtility::forceIntegerInRange(hexdec(substr($color, 1, 2)) + $R, 0, 255);
        $nG = MathUtility::forceIntegerInRange(hexdec(substr($color, 3, 2)) + $G, 0, 255);
        $nB = MathUtility::forceIntegerInRange(hexdec(substr($color, 5, 2)) + $B, 0, 255);
        return '#' . substr(('0' . dechex($nR)), -2) . substr(('0' . dechex($nG)), -2) . substr(('0' . dechex($nB)), -2);
    }

    /**
     * Modifies a HTML Hex color by adding/subtracting $all integer from all R/G/B channels
     *
     * @param string $color A hexadecimal color code, #xxxxxx
     * @param int $all Offset value 0-255 for all three channels.
     * @return string A hexadecimal color code, #xxxxxx, modified according to input vars
     * @see modifyHTMLColor()
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public static function modifyHTMLColorAll($color, $all)
    {
        self::logDeprecatedFunction();
        return self::modifyHTMLColor($color, $all, $all, $all);
    }

    /**
     * Returns TRUE if the first part of $str matches the string $partStr
     *
     * @param string $str Full string to check
     * @param string $partStr Reference string which must be found as the "first part" of the full string
     * @return bool TRUE if $partStr was found to be equal to the first part of $str
     */
    public static function isFirstPartOfStr($str, $partStr)
    {
        return $partStr != '' && strpos((string)$str, (string)$partStr, 0) === 0;
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
        // @todo find out which locale is used for current BE user to cover the BE case as well
        $oldLocale = setlocale(LC_NUMERIC, 0);
        $newLocale = isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE']->config['config']['locale_all'] : '';
        if ($newLocale) {
            setlocale(LC_NUMERIC, $newLocale);
        }
        $localeInfo = localeconv();
        if ($newLocale) {
            setlocale(LC_NUMERIC, $oldLocale);
        }
        $sizeInBytes = max($sizeInBytes, 0);
        $multiplier = floor(($sizeInBytes ? log($sizeInBytes) : 0) / log($base));
        $sizeInUnits = $sizeInBytes / pow($base, $multiplier);
        if ($sizeInUnits > ($base * .9)) {
            $multiplier++;
        }
        $multiplier = min($multiplier, count($labelArr) - 1);
        $sizeInUnits = $sizeInBytes / pow($base, $multiplier);
        return number_format($sizeInUnits, (($multiplier > 0) && ($sizeInUnits < 20)) ? 2 : 0, $localeInfo['decimal_point'], '') . $labelArr[$multiplier];
    }

    /**
     * Returns microtime input to milliseconds
     *
     * @param string $microtime Microtime
     * @return int Microtime input string converted to an integer (milliseconds)
     */
    public static function convertMicrotime($microtime)
    {
        $parts = explode(' ', $microtime);
        return round(($parts[0] + $parts[1]) * 1000);
    }

    /**
     * This splits a string by the chars in $operators (typical /+-*) and returns an array with them in
     *
     * @param string $string Input string, eg "123 + 456 / 789 - 4
     * @param string $operators Operators to split by, typically "/+-*
     * @return array Array with operators and operands separated.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::calc(), \TYPO3\CMS\Frontend\Imaging\GifBuilder::calcOffset()
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
     * Re-converts HTML entities if they have been converted by htmlspecialchars()
     * Note: Use htmlspecialchars($str, ENT_COMPAT, 'UTF-8', FALSE) to avoid double encoding.
     *       This makes the call to this method obsolete.
     *
     * @param string $str String which contains eg. "&amp;amp;" which should stay "&amp;". Or "&amp;#1234;" to "&#1234;". Or "&amp;#x1b;" to "&#x1b;
     * @return string Converted result.
     *
     */
    public static function deHSCentities($str)
    {
        return preg_replace('/&amp;([#[:alnum:]]*;)/', '&\\1', $str);
    }

    /**
     * This function is used to escape any ' -characters when transferring text to JavaScript!
     *
     * @param string $string String to escape
     * @param bool $extended If set, also backslashes are escaped.
     * @param string $char The character to escape, default is ' (single-quote)
     * @return string Processed input string
     */
    public static function slashJS($string, $extended = false, $char = '\'')
    {
        if ($extended) {
            $string = str_replace('\\', '\\\\', $string);
        }
        return str_replace($char, '\\' . $char, $string);
    }

    /**
     * Version of rawurlencode() where all spaces (%20) are re-converted to space-characters.
     * Useful when passing text to JavaScript where you simply url-encode it to get around problems with syntax-errors, linebreaks etc.
     *
     * @param string $str String to raw-url-encode with spaces preserved
     * @return string Rawurlencoded result of input string, but with all %20 (space chars) converted to real spaces.
     */
    public static function rawUrlEncodeJS($str)
    {
        return str_replace('%20', ' ', rawurlencode($str));
    }

    /**
     * rawurlencode which preserves "/" chars
     * Useful when file paths should keep the "/" chars, but have all other special chars encoded.
     *
     * @param string $str Input string
     * @return string Output string
     */
    public static function rawUrlEncodeFP($str)
    {
        return str_replace('%2F', '/', rawurlencode($str));
    }

    /**
     * Checking syntax of input email address
     *
     * http://tools.ietf.org/html/rfc3696
     * International characters are allowed in email. So the whole address needs
     * to be converted to punicode before passing it to filter_var(). We convert
     * the user- and domain part separately to increase the chance of hitting an
     * entry in self::$idnaStringCache.
     *
     * Also the @ sign may appear multiple times in an address. If not used as
     * a boundary marker between the user- and domain part, it must be escaped
     * with a backslash: \@. This mean we can not just explode on the @ sign and
     * expect to get just two parts. So we pop off the domain and then glue the
     * rest together again.
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
        $atPosition = strrpos($email, '@');
        if (!$atPosition || $atPosition + 1 === strlen($email)) {
            // Return if no @ found or it is placed at the very beginning or end of the email
            return false;
        }
        $domain = substr($email, $atPosition + 1);
        $user = substr($email, 0, $atPosition);
        if (!preg_match('/^[a-z0-9.\\-]*$/i', $domain)) {
            $domain = self::idnaEncode($domain);
        }
        return filter_var($user . '@' . $domain, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Checks if current e-mail sending method does not accept recipient/sender name
     * in a call to PHP mail() function. Windows version of mail() and mini_sendmail
     * program are known not to process such input correctly and they cause SMTP
     * errors. This function will return TRUE if current mail sending method has
     * problem with recipient name in recipient/sender argument for mail().
     *
     * @todo 4.3 should have additional configuration variable, which is combined
     *   by || with the rest in this function.
     *
     * @return bool TRUE if mail() does not accept recipient name
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public static function isBrokenEmailEnvironment()
    {
        self::logDeprecatedFunction();
        return TYPO3_OS == 'WIN' || false !== strpos(ini_get('sendmail_path'), 'mini_sendmail');
    }

    /**
     * Changes from/to arguments for mail() function to work in any environment.
     *
     * @param string $address Address to adjust
     * @return string Adjusted address
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public static function normalizeMailAddress($address)
    {
        self::logDeprecatedFunction();
        if (self::isBrokenEmailEnvironment() && false !== ($pos1 = strrpos($address, '<'))) {
            $pos2 = strpos($address, '>', $pos1);
            $address = substr($address, $pos1 + 1, ($pos2 ? $pos2 : strlen($address)) - $pos1 - 1);
        }
        return $address;
    }

    /**
     * Formats a string for output between <textarea>-tags
     * All content outputted in a textarea form should be passed through this function
     * Not only is the content htmlspecialchar'ed on output but there is also a single newline added in the top. The newline is necessary because browsers will ignore the first newline after <textarea> if that is the first character. Therefore better set it!
     *
     * @param string $content Input string to be formatted.
     * @return string Formatted for <textarea>-tags
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public static function formatForTextarea($content)
    {
        self::logDeprecatedFunction();
        return LF . htmlspecialchars($content);
    }

    /**
     * Converts string to uppercase
     * The function converts all Latin characters (a-z, but no accents, etc) to
     * uppercase. It is safe for all supported character sets (incl. utf-8).
     * Unlike strtoupper() it does not honour the locale.
     *
     * @param string $str Input string
     * @return string Uppercase String
     */
    public static function strtoupper($str)
    {
        return strtr((string)$str, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    /**
     * Converts string to lowercase
     * The function converts all Latin characters (A-Z, but no accents, etc) to
     * lowercase. It is safe for all supported character sets (incl. utf-8).
     * Unlike strtolower() it does not honour the locale.
     *
     * @param string $str Input string
     * @return string Lowercase String
     */
    public static function strtolower($str)
    {
        return strtr((string)$str, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    }

    /**
     * Returns a string of highly randomized bytes (over the full 8-bit range).
     *
     * Note: Returned values are not guaranteed to be crypto-safe,
     * most likely they are not, depending on the used retrieval method.
     *
     * @param int $bytesToReturn Number of characters (bytes) to return
     * @return string Random Bytes
     * @see http://bugs.php.net/bug.php?id=52523
     * @see http://www.php-security.org/2010/05/09/mops-submission-04-generating-unpredictable-session-ids-and-hashes/index.html
     */
    public static function generateRandomBytes($bytesToReturn)
    {
        // Cache 4k of the generated bytestream.
        static $bytes = '';
        $bytesToGenerate = max(4096, $bytesToReturn);
        // if we have not enough random bytes cached, we generate new ones
        if (!isset($bytes[$bytesToReturn - 1])) {
            if (TYPO3_OS === 'WIN') {
                // Openssl seems to be deadly slow on Windows, so try to use mcrypt
                $bytes .= self::generateRandomBytesMcrypt($bytesToGenerate);
            } else {
                // Try to use native PHP functions first, precedence has openssl
                $bytes .= self::generateRandomBytesOpenSsl($bytesToGenerate);
                if (!isset($bytes[$bytesToReturn - 1])) {
                    $bytes .= self::generateRandomBytesMcrypt($bytesToGenerate);
                }
                // If openssl and mcrypt failed, try /dev/urandom
                if (!isset($bytes[$bytesToReturn - 1])) {
                    $bytes .= self::generateRandomBytesUrandom($bytesToGenerate);
                }
            }
            // Fall back if other random byte generation failed until now
            if (!isset($bytes[$bytesToReturn - 1])) {
                $bytes .= self::generateRandomBytesFallback($bytesToReturn);
            }
        }
        // get first $bytesToReturn and remove it from the byte cache
        $output = substr($bytes, 0, $bytesToReturn);
        $bytes = substr($bytes, $bytesToReturn);
        return $output;
    }

    /**
     * Generate random bytes using openssl if available
     *
     * @param string $bytesToGenerate
     * @return string
     */
    protected static function generateRandomBytesOpenSsl($bytesToGenerate)
    {
        if (!function_exists('openssl_random_pseudo_bytes')) {
            return '';
        }
        $isStrong = null;
        return (string)openssl_random_pseudo_bytes($bytesToGenerate, $isStrong);
    }

    /**
     * Generate random bytes using mcrypt if available
     *
     * @param $bytesToGenerate
     * @return string
     */
    protected static function generateRandomBytesMcrypt($bytesToGenerate)
    {
        if (!function_exists('mcrypt_create_iv')) {
            return '';
        }
        return (string)(@mcrypt_create_iv($bytesToGenerate, MCRYPT_DEV_URANDOM));
    }

    /**
     * Read random bytes from /dev/urandom if it is accessible
     *
     * @param $bytesToGenerate
     * @return string
     */
    protected static function generateRandomBytesUrandom($bytesToGenerate)
    {
        $bytes = '';
        $fh = @fopen('/dev/urandom', 'rb');
        if ($fh) {
            // PHP only performs buffered reads, so in reality it will always read
            // at least 4096 bytes. Thus, it costs nothing extra to read and store
            // that much so as to speed any additional invocations.
            $bytes = fread($fh, $bytesToGenerate);
            fclose($fh);
        }
        return $bytes;
    }

    /**
     * Generate pseudo random bytes as last resort
     *
     * @param $bytesToReturn
     * @return string
     */
    protected static function generateRandomBytesFallback($bytesToReturn)
    {
        $bytes = '';
        // We initialize with somewhat random.
        $randomState = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . base_convert(memory_get_usage() % pow(10, 6), 10, 2) . microtime() . StringUtility::getUniqueId() . getmypid();
        while (!isset($bytes[$bytesToReturn - 1])) {
            $randomState = sha1(microtime() . mt_rand() . $randomState);
            $bytes .= sha1(mt_rand() . $randomState, true);
        }
        return $bytes;
    }

    /**
     * Returns an ASCII string (punicode) representation of $value
     *
     * @param string $value
     * @return string An ASCII encoded (punicode) string
     */
    public static function idnaEncode($value)
    {
        if (isset(self::$idnaStringCache[$value])) {
            return self::$idnaStringCache[$value];
        } else {
            if (!self::$idnaConverter) {
                self::$idnaConverter = new \idna_convert(['idn_version' => 2008]);
            }
            self::$idnaStringCache[$value] = self::$idnaConverter->encode($value);
            return self::$idnaStringCache[$value];
        }
    }

    /**
     * Returns a hex representation of a random byte string.
     *
     * @param int $count Number of hex characters to return
     * @return string Random Bytes
     */
    public static function getRandomHexString($count)
    {
        return substr(bin2hex(self::generateRandomBytes((int)(($count + 1) / 2))), 0, $count);
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
        $upperCamelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', self::strtolower($string))));
        return $upperCamelCase;
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
        $upperCamelCase = str_replace(' ', '', ucwords(str_replace('_', ' ', self::strtolower($string))));
        $lowerCamelCase = self::lcfirst($upperCamelCase);
        return $lowerCamelCase;
    }

    /**
     * Returns a given CamelCasedString as an lowercase string with underscores.
     * Example: Converts BlogExample to blog_example, and minimalValue to minimal_value
     *
     * @param string $string String to be converted to lowercase underscore
     * @return string lowercase_and_underscored_string
     */
    public static function camelCaseToLowerCaseUnderscored($string)
    {
        return self::strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $string));
    }

    /**
     * Converts the first char of a string to lowercase if it is a latin character (A-Z).
     * Example: Converts "Hello World" to "hello World"
     *
     * @param string $string The string to be used to lowercase the first character
     * @return string The string with the first character as lowercase
     */
    public static function lcfirst($string)
    {
        return self::strtolower($string[0]) . substr($string, 1);
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
     * http://tools.ietf.org/html/rfc3986#appendix-A
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
        if (!self::isFirstPartOfStr($url, $parsedUrl['scheme'] . '://')) {
            $url = str_replace($parsedUrl['scheme'] . ':', $parsedUrl['scheme'] . '://', $url);
        }
        $recomposedUrl = HttpUtility::buildUrl($parsedUrl);
        if ($recomposedUrl !== $url) {
            // The parse_url() had to modify characters, so the URL is invalid
            return false;
        }
        if (isset($parsedUrl['host']) && !preg_match('/^[a-z0-9.\\-]*$/i', $parsedUrl['host'])) {
            $parsedUrl['host'] = self::idnaEncode($parsedUrl['host']);
        }
        return filter_var(HttpUtility::buildUrl($parsedUrl), FILTER_VALIDATE_URL) !== false;
    }

    /*************************
     *
     * ARRAY FUNCTIONS
     *
     *************************/
    /**
     * Check if an string item exists in an array.
     * Please note that the order of function parameters is reverse compared to the PHP function in_array()!!!
     *
     * Comparison to PHP in_array():
     * -> $array = array(0, 1, 2, 3);
     * -> variant_a := \TYPO3\CMS\Core\Utility\ArrayUtility::inArray($array, $needle)
     * -> variant_b := in_array($needle, $array)
     * -> variant_c := in_array($needle, $array, TRUE)
     * +---------+-----------+-----------+-----------+
     * | $needle | variant_a | variant_b | variant_c |
     * +---------+-----------+-----------+-----------+
     * | '1a'	| FALSE	 | TRUE	  | FALSE	 |
     * | ''	  | FALSE	 | TRUE	  | FALSE	 |
     * | '0'	 | TRUE	  | TRUE	  | FALSE	 |
     * | 0	   | TRUE	  | TRUE	  | TRUE	  |
     * +---------+-----------+-----------+-----------+
     *
     * @param array $in_array One-dimensional array of items
     * @param string $item Item to check for
     * @return bool TRUE if $item is in the one-dimensional array $in_array
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8  - use ArrayUtility::inArray() instead
     */
    public static function inArray(array $in_array, $item)
    {
        static::logDeprecatedFunction();
        return ArrayUtility::inArray($in_array, $item);
    }

    /**
     * Explodes a $string delimited by $delim and casts each item in the array to (int).
     * Corresponds to \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(), but with conversion to integers for all values.
     *
     * @param string $delimiter Delimiter string to explode with
     * @param string $string The string to explode
     * @param bool $removeEmptyValues If set, all empty values (='') will NOT be set in output
     * @param int $limit If positive, the result will contain a maximum of limit elements,
     * @return array Exploded values, all converted to integers
     */
    public static function intExplode($delimiter, $string, $removeEmptyValues = false, $limit = 0)
    {
        $result = explode($delimiter, $string);
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
     * @return array Exploded values
     */
    public static function revExplode($delimiter, $string, $count = 0)
    {
        // 2 is the (currently, as of 2014-02) most-used value for $count in the core, therefore we check it first
        if ($count === 2) {
            $position = strrpos($string, strrev($delimiter));
            if ($position !== false) {
                return [substr($string, 0, $position), substr($string, $position + strlen($delimiter))];
            } else {
                return [$string];
            }
        } elseif ($count <= 1) {
            return [$string];
        } else {
            $explodedValues = explode($delimiter, strrev($string), $count);
            $explodedValues = array_map('strrev', $explodedValues);
            return array_reverse($explodedValues);
        }
    }

    /**
     * Explodes a string and trims all values for whitespace in the end.
     * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
     *
     * @param string $delim Delimiter string to explode with
     * @param string $string The string to explode
     * @param bool $removeEmptyValues If set, all empty values will be removed in output
     * @param int $limit If limit is set and positive, the returned array will contain a maximum of limit elements with
     *                   the last element containing the rest of string. If the limit parameter is negative, all components
     *                   except the last -limit are returned.
     * @return array Exploded values
     */
    public static function trimExplode($delim, $string, $removeEmptyValues = false, $limit = 0)
    {
        $result = explode($delim, $string);
        if ($removeEmptyValues) {
            $temp = [];
            foreach ($result as $value) {
                if (trim($value) !== '') {
                    $temp[] = $value;
                }
            }
            $result = $temp;
        }
        if ($limit > 0 && count($result) > $limit) {
            $lastElements = array_splice($result, $limit - 1);
            $result[] = implode($delim, $lastElements);
        } elseif ($limit < 0) {
            $result = array_slice($result, 0, $limit);
        }
        $result = array_map('trim', $result);
        return $result;
    }

    /**
     * Removes the value $cmpValue from the $array if found there. Returns the modified array
     *
     * @param array $array Array containing the values
     * @param string $cmpValue Value to search for and if found remove array entry where found.
     * @return array Output array with entries removed if search string is found
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8  - use ArrayUtility::removeArrayEntryByValue() instead
     */
    public static function removeArrayEntryByValue(array $array, $cmpValue)
    {
        static::logDeprecatedFunction();
        return ArrayUtility::removeArrayEntryByValue($array, $cmpValue);
    }

    /**
     * Filters an array to reduce its elements to match the condition.
     * The values in $keepItems can be optionally evaluated by a custom callback function.
     *
     * Example (arguments used to call this function):
     * $array = array(
     * array('aa' => array('first', 'second'),
     * array('bb' => array('third', 'fourth'),
     * array('cc' => array('fifth', 'sixth'),
     * );
     * $keepItems = array('third');
     * $getValueFunc = function($value) { return $value[0]; }
     *
     * Returns:
     * array(
     * array('bb' => array('third', 'fourth'),
     * )
     *
     * @param array $array The initial array to be filtered/reduced
     * @param mixed $keepItems The items which are allowed/kept in the array - accepts array or csv string
     * @param string $getValueFunc (optional) Callback function used to get the value to keep
     * @return array The filtered/reduced array with the kept items
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8  - use ArrayUtility::keepItemsInArray() instead
     */
    public static function keepItemsInArray(array $array, $keepItems, $getValueFunc = null)
    {
        static::logDeprecatedFunction();
        return ArrayUtility::keepItemsInArray($array, $keepItems, $getValueFunc);
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
                if (!$skipBlank || (string)$AVal !== '') {
                    $str .= '&' . ($rawurlencodeParamName ? rawurlencode($thisKeyName) : $thisKeyName) . '=' . rawurlencode($AVal);
                }
            }
        }
        return $str;
    }

    /**
     * Explodes a string with GETvars (eg. "&id=1&type=2&ext[mykey]=3") into an array
     *
     * @param string $string GETvars string
     * @param bool $multidim If set, the string will be parsed into a multidimensional array if square brackets are used in variable names (using PHP function parse_str())
     * @return array Array of values. All values AND keys are rawurldecoded() as they properly should be. But this means that any implosion of the array again must rawurlencode it!
     * @see implodeArrayForUrl()
     */
    public static function explodeUrl2Array($string, $multidim = false)
    {
        $output = [];
        if ($multidim) {
            parse_str($string, $output);
        } else {
            $p = explode('&', $string);
            foreach ($p as $v) {
                if ($v !== '') {
                    list($pK, $pV) = explode('=', $v, 2);
                    $output[rawurldecode($pK)] = rawurldecode($pV);
                }
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
     */
    public static function compileSelectedGetVarsFromArray($varList, array $getArray, $GPvarAlt = true)
    {
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
     * AddSlash array
     * This function traverses a multidimensional array and adds slashes to the values.
     * NOTE that the input array is and argument by reference.!!
     * Twin-function to stripSlashesOnArray
     *
     * @param array $theArray Multidimensional input array, (REFERENCE!)
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     * @return array
     */
    public static function addSlashesOnArray(array &$theArray)
    {
        self::logDeprecatedFunction();
        foreach ($theArray as &$value) {
            if (is_array($value)) {
                self::addSlashesOnArray($value);
            } else {
                $value = addslashes($value);
            }
        }
        unset($value);
        reset($theArray);
    }

    /**
     * StripSlash array
     * This function traverses a multidimensional array and strips slashes to the values.
     * NOTE that the input array is and argument by reference.!!
     * Twin-function to addSlashesOnArray
     *
     * @param array $theArray Multidimensional input array, (REFERENCE!)
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     * @return array
     */
    public static function stripSlashesOnArray(array &$theArray)
    {
        foreach ($theArray as &$value) {
            if (is_array($value)) {
                self::stripSlashesOnArray($value);
            } else {
                $value = stripslashes($value);
            }
        }
        unset($value);
        reset($theArray);
    }

    /**
     * Either slashes ($cmd=add) or strips ($cmd=strip) array $arr depending on $cmd
     *
     * @param array $arr Multidimensional input array
     * @param string $cmd "add" or "strip", depending on usage you wish.
     * @return array
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public static function slashArray(array $arr, $cmd)
    {
        self::logDeprecatedFunction();
        if ($cmd == 'strip') {
            self::stripSlashesOnArray($arr);
        }
        if ($cmd == 'add') {
            self::addSlashesOnArray($arr);
        }
        return $arr;
    }

    /**
     * Rename Array keys with a given mapping table
     *
     * @param array	$array Array by reference which should be remapped
     * @param array	$mappingTable Array with remap information, array/$oldKey => $newKey)
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8  - use ArrayUtility::remapArrayKeys() instead
     */
    public static function remapArrayKeys(&$array, $mappingTable)
    {
        static::logDeprecatedFunction();
        ArrayUtility::remapArrayKeys($array, $mappingTable);
    }

    /**
     * An array_merge function where the keys are NOT renumbered as they happen to be with the real php-array_merge function. It is "binary safe" in the sense that integer keys are overridden as well.
     *
     * @param array $arr1 First array
     * @param array $arr2 Second array
     * @return array Merged result.
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8  - native php '+' operator instead
     */
    public static function array_merge(array $arr1, array $arr2)
    {
        static::logDeprecatedFunction();
        return $arr2 + $arr1;
    }

    /**
     * Filters keys off from first array that also exist in second array. Comparison is done by keys.
     * This method is a recursive version of php array_diff_assoc()
     *
     * @param array $array1 Source array
     * @param array $array2 Reduce source array by this array
     * @return array Source array reduced by keys also present in second array
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8  - use ArrayUtility::arrayDiffAssocRecursive() instead
     */
    public static function arrayDiffAssocRecursive(array $array1, array $array2)
    {
        static::logDeprecatedFunction();
        return ArrayUtility::arrayDiffAssocRecursive($array1, $array2);
    }

    /**
     * Takes a row and returns a CSV string of the values with $delim (default is ,) and $quote (default is ") as separator chars.
     *
     * @param array $row Input array of values
     * @param string $delim Delimited, default is comma
     * @param string $quote Quote-character to wrap around the values.
     * @return string A single line of CSV
     */
    public static function csvValues(array $row, $delim = ',', $quote = '"')
    {
        $out = [];
        foreach ($row as $value) {
            $out[] = str_replace($quote, $quote . $quote, $value);
        }
        $str = $quote . implode(($quote . $delim . $quote), $out) . $quote;
        return $str;
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

    /**
     * Sorts an array by key recursive - uses natural sort order (aAbB-zZ)
     *
     * @param array $array array to be sorted recursively, passed by reference
     * @return bool TRUE if param is an array
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8  - use ArrayUtility::naturalKeySortRecursive() instead
     */
    public static function naturalKeySortRecursive(&$array)
    {
        static::logDeprecatedFunction();
        return ArrayUtility::naturalKeySortRecursive($array);
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
     * @return array Array with the attribute values.
     */
    public static function get_tag_attributes($tag)
    {
        $components = self::split_tag_attributes($tag);
        // Attribute name is stored here
        $name = '';
        $valuemode = false;
        $attributes = [];
        foreach ($components as $key => $val) {
            // Only if $name is set (if there is an attribute, that waits for a value), that valuemode is enabled. This ensures that the attribute is assigned it's value
            if ($val != '=') {
                if ($valuemode) {
                    if ($name) {
                        $attributes[$name] = $val;
                        $name = '';
                    }
                } else {
                    if ($key = strtolower(preg_replace('/[^[:alnum:]_\\:\\-]/', '', $val))) {
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
     * @return array Array with the attribute values.
     */
    public static function split_tag_attributes($tag)
    {
        $tag_tmp = trim(preg_replace('/^<[^[:space:]]*/', '', trim($tag)));
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
                $tag_tmp = trim(substr($tag_tmp, strlen($reg[0]), 1) . $reg[1]);
            }
        }
        reset($value);
        return $value;
    }

    /**
     * Implodes attributes in the array $arr for an attribute list in eg. and HTML tag (with quotes)
     *
     * @param array $arr Array with attribute key/value pairs, eg. "bgcolor"=>"red", "border"=>0
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
                    $newArr[strtolower($p)] = htmlspecialchars($v);
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
     * @param bool $linebreak Wrap script element in line breaks? Default is TRUE.
     * @return string The wrapped JS code, ready to put into a XHTML page
     */
    public static function wrapJS($string, $linebreak = true)
    {
        if (trim($string)) {
            // <script wrapped in nl?
            $cr = $linebreak ? LF : '';
            // remove nl from the beginning
            $string = preg_replace('/^\\n+/', '', $string);
            // re-ident to one tab using the first line as reference
            $match = [];
            if (preg_match('/^(\\t+)/', $string, $match)) {
                $string = str_replace($match[1], TAB, $string);
            }
            $string = $cr . '<script type="text/javascript">
/*<![CDATA[*/
' . $string . '
/*]]>*/
</script>' . $cr;
        }
        return trim($string);
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
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        $parser = xml_parser_create();
        $vals = [];
        $index = [];
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        foreach ($parserOptions as $option => $value) {
            xml_parser_set_option($parser, $option, $value);
        }
        xml_parse_into_struct($parser, $string, $vals, $index);
        libxml_disable_entity_loader($previousValueOfEntityLoader);
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
            if ($type == 'open' || $type == 'complete') {
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
            if ($type == 'complete' || $type == 'close') {
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
            if ($type == 'cdata') {
                $tagi['values'][] = $val['value'];
            }
        }
        return $tagi['ch'];
    }

    /**
     * Turns PHP array into XML. See array2xml()
     *
     * @param array $array The input PHP array with any kind of data; text, binary, integers. Not objects though.
     * @param string $docTag Alternative document tag. Default is "phparray".
     * @param array $options Options for the compilation. See array2xml() for description.
     * @param string $charset Forced charset to prologue
     * @return string An XML string made from the input content in the array.
     * @see xml2array(),array2xml()
     */
    public static function array2xml_cs(array $array, $docTag = 'phparray', array $options = [], $charset = '')
    {
        // Set default charset unless explicitly specified
        $charset = $charset ?: 'utf-8';
        // Return XML:
        return '<?xml version="1.0" encoding="' . htmlspecialchars($charset) . '" standalone="yes" ?>' . LF . self::array2xml($array, '', 0, $docTag, 0, $options);
    }

    /**
     * Deprecated to call directly (unless you are aware of using XML prologues)! Use "array2xml_cs" instead (which adds an XML-prologue)
     *
     * Converts a PHP array into an XML string.
     * The XML output is optimized for readability since associative keys are used as tag names.
     * This also means that only alphanumeric characters are allowed in the tag names AND only keys NOT starting with numbers (so watch your usage of keys!). However there are options you can set to avoid this problem.
     * Numeric keys are stored with the default tag name "numIndex" but can be overridden to other formats)
     * The function handles input values from the PHP array in a binary-safe way; All characters below 32 (except 9,10,13) will trigger the content to be converted to a base64-string
     * The PHP variable type of the data IS preserved as long as the types are strings, arrays, integers and booleans. Strings are the default type unless the "type" attribute is set.
     * The output XML has been tested with the PHP XML-parser and parses OK under all tested circumstances with 4.x versions. However, with PHP5 there seems to be the need to add an XML prologue a la <?xml version="1.0" encoding="[charset]" standalone="yes" ?> - otherwise UTF-8 is assumed! Unfortunately, many times the output from this function is used without adding that prologue meaning that non-ASCII characters will break the parsing!! This suchs of course! Effectively it means that the prologue should always be prepended setting the right characterset, alternatively the system should always run as utf-8!
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
        $binaryChars = chr(0) . chr(1) . chr(2) . chr(3) . chr(4) . chr(5) . chr(6) . chr(7) . chr(8) . chr(11) . chr(12) . chr(14) . chr(15) . chr(16) . chr(17) . chr(18) . chr(19) . chr(20) . chr(21) . chr(22) . chr(23) . chr(24) . chr(25) . chr(26) . chr(27) . chr(28) . chr(29) . chr(30) . chr(31);
        // Set indenting mode:
        $indentChar = $spaceInd ? ' ' : TAB;
        $indentN = $spaceInd > 0 ? $spaceInd : 1;
        $nl = $spaceInd >= 0 ? LF : '';
        // Init output variable:
        $output = '';
        // Traverse the input array
        foreach ($array as $k => $v) {
            $attr = '';
            $tagName = $k;
            // Construct the tag name.
            // Use tag based on grand-parent + parent tag name
            if (isset($options['grandParentTagMap'][$stackData['grandParentTagName'] . '/' . $stackData['parentTagName']])) {
                $attr .= ' index="' . htmlspecialchars($tagName) . '"';
                $tagName = (string)$options['grandParentTagMap'][$stackData['grandParentTagName'] . '/' . $stackData['parentTagName']];
            } elseif (isset($options['parentTagMap'][$stackData['parentTagName'] . ':_IS_NUM']) && MathUtility::canBeInterpretedAsInteger($tagName)) {
                // Use tag based on parent tag name + if current tag is numeric
                $attr .= ' index="' . htmlspecialchars($tagName) . '"';
                $tagName = (string)$options['parentTagMap'][$stackData['parentTagName'] . ':_IS_NUM'];
            } elseif (isset($options['parentTagMap'][$stackData['parentTagName'] . ':' . $tagName])) {
                // Use tag based on parent tag name + current tag
                $attr .= ' index="' . htmlspecialchars($tagName) . '"';
                $tagName = (string)$options['parentTagMap'][$stackData['parentTagName'] . ':' . $tagName];
            } elseif (isset($options['parentTagMap'][$stackData['parentTagName']])) {
                // Use tag based on parent tag name:
                $attr .= ' index="' . htmlspecialchars($tagName) . '"';
                $tagName = (string)$options['parentTagMap'][$stackData['parentTagName']];
            } elseif (MathUtility::canBeInterpretedAsInteger($tagName)) {
                // If integer...;
                if ($options['useNindex']) {
                    // If numeric key, prefix "n"
                    $tagName = 'n' . $tagName;
                } else {
                    // Use special tag for num. keys:
                    $attr .= ' index="' . $tagName . '"';
                    $tagName = $options['useIndexTagForNum'] ?: 'numIndex';
                }
            } elseif ($options['useIndexTagForAssoc']) {
                // Use tag for all associative keys:
                $attr .= ' index="' . htmlspecialchars($tagName) . '"';
                $tagName = $options['useIndexTagForAssoc'];
            }
            // The tag name is cleaned up so only alphanumeric chars (plus - and _) are in there and not longer than 100 chars either.
            $tagName = substr(preg_replace('/[^[:alnum:]_-]/', '', $tagName), 0, 100);
            // If the value is an array then we will call this function recursively:
            if (is_array($v)) {
                // Sub elements:
                if ($options['alt_options'][$stackData['path'] . '/' . $tagName]) {
                    $subOptions = $options['alt_options'][$stackData['path'] . '/' . $tagName];
                    $clearStackPath = $subOptions['clearStackPath'];
                } else {
                    $subOptions = $options;
                    $clearStackPath = false;
                }
                if (empty($v)) {
                    $content = '';
                } else {
                    $content = $nl . self::array2xml($v, $NSprefix, ($level + 1), '', $spaceInd, $subOptions, [
                            'parentTagName' => $tagName,
                            'grandParentTagName' => $stackData['parentTagName'],
                            'path' => ($clearStackPath ? '' : $stackData['path'] . '/' . $tagName)
                        ]) . ($spaceInd >= 0 ? str_pad('', ($level + 1) * $indentN, $indentChar) : '');
                }
                // Do not set "type = array". Makes prettier XML but means that empty arrays are not restored with xml2array
                if ((int)$options['disableTypeAttrib'] != 2) {
                    $attr .= ' type="array"';
                }
            } else {
                // Just a value:
                // Look for binary chars:
                $vLen = strlen($v);
                // Go for base64 encoding if the initial segment NOT matching any binary char has the same length as the whole string!
                if ($vLen && strcspn($v, $binaryChars) != $vLen) {
                    // If the value contained binary chars then we base64-encode it an set an attribute to notify this situation:
                    $content = $nl . chunk_split(base64_encode($v));
                    $attr .= ' base64="1"';
                } else {
                    // Otherwise, just htmlspecialchar the stuff:
                    $content = htmlspecialchars($v);
                    $dType = gettype($v);
                    if ($dType == 'string') {
                        if ($options['useCDATA'] && $content != $v) {
                            $content = '<![CDATA[' . $v . ']]>';
                        }
                    } elseif (!$options['disableTypeAttrib']) {
                        $attr .= ' type="' . $dType . '"';
                    }
                }
            }
            if ((string)$tagName !== '') {
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
     * @see array2xml(),xml2arrayProcess()
     */
    public static function xml2array($string, $NSprefix = '', $reportDocTag = false)
    {
        static $firstLevelCache = [];
        $identifier = md5($string . $NSprefix . ($reportDocTag ? '1' : '0'));
        // Look up in first level cache
        if (!empty($firstLevelCache[$identifier])) {
            $array = $firstLevelCache[$identifier];
        } else {
            // Look up in second level cache
            // @todo: Is this cache really required? It basically substitutes a little cpu work with a db query?
            $array = PageRepository::getHash($identifier, 0);
            if (!is_array($array)) {
                $array = self::xml2arrayProcess(trim($string), $NSprefix, $reportDocTag);
                PageRepository::storeHash($identifier, $array, 'ident_xml2array');
            }
            // Store content in first level cache
            $firstLevelCache[$identifier] = $array;
        }
        return $array;
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
    protected static function xml2arrayProcess($string, $NSprefix = '', $reportDocTag = false)
    {
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        // Create parser:
        $parser = xml_parser_create();
        $vals = [];
        $index = [];
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        // Default output charset is UTF-8, only ASCII, ISO-8859-1 and UTF-8 are supported!!!
        $match = [];
        preg_match('/^[[:space:]]*<\\?xml[^>]*encoding[[:space:]]*=[[:space:]]*"([^"]*)"/', substr($string, 0, 200), $match);
        $theCharset = $match[1] ?: 'utf-8';
        // us-ascii / utf-8 / iso-8859-1
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $theCharset);
        // Parse content:
        xml_parse_into_struct($parser, $string, $vals, $index);
        libxml_disable_entity_loader($previousValueOfEntityLoader);
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
            $tagName = $NSprefix && substr($tagName, 0, strlen($NSprefix)) == $NSprefix ? substr($tagName, strlen($NSprefix)) : $tagName;
            // Test for numeric tag, encoded on the form "nXXX":
            $testNtag = substr($tagName, 1);
            // Closing tag.
            $tagName = $tagName[0] === 'n' && MathUtility::canBeInterpretedAsInteger($testNtag) ? (int)$testNtag : $tagName;
            // Test for alternative index value:
            if ((string)$val['attributes']['index'] !== '') {
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
                    if ($val['attributes']['base64']) {
                        $current[$tagName] = base64_decode($val['value']);
                    } else {
                        // Had to cast it as a string - otherwise it would be evaluate FALSE if tested with isset()!!
                        $current[$tagName] = (string)$val['value'];
                        // Cast type:
                        switch ((string)$val['attributes']['type']) {
                            case 'integer':
                                $current[$tagName] = (int)$current[$tagName];
                                break;
                            case 'double':
                                $current[$tagName] = (double) $current[$tagName];
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
     * @param array $vals An array of XML parts, see xml2tree
     * @return string Re-compiled XML data.
     */
    public static function xmlRecompileFromStructValArray(array $vals)
    {
        $XMLcontent = '';
        foreach ($vals as $val) {
            $type = $val['type'];
            // Open tag:
            if ($type == 'open' || $type == 'complete') {
                $XMLcontent .= '<' . $val['tag'];
                if (isset($val['attributes'])) {
                    foreach ($val['attributes'] as $k => $v) {
                        $XMLcontent .= ' ' . $k . '="' . htmlspecialchars($v) . '"';
                    }
                }
                if ($type == 'complete') {
                    if (isset($val['value'])) {
                        $XMLcontent .= '>' . htmlspecialchars($val['value']) . '</' . $val['tag'] . '>';
                    } else {
                        $XMLcontent .= '/>';
                    }
                } else {
                    $XMLcontent .= '>';
                }
                if ($type == 'open' && isset($val['value'])) {
                    $XMLcontent .= htmlspecialchars($val['value']);
                }
            }
            // Finish tag:
            if ($type == 'close') {
                $XMLcontent .= '</' . $val['tag'] . '>';
            }
            // Cdata
            if ($type == 'cdata') {
                $XMLcontent .= htmlspecialchars($val['value']);
            }
        }
        return $XMLcontent;
    }

    /**
     * Extracts the attributes (typically encoding and version) of an XML prologue (header).
     *
     * @param string $xmlData XML data
     * @return array Attributes of the xml prologue (header)
     */
    public static function xmlGetHeaderAttribs($xmlData)
    {
        $match = [];
        if (preg_match('/^\\s*<\\?xml([^>]*)\\?\\>/', $xmlData, $match)) {
            return self::get_tag_attributes($match[1]);
        }
    }

    /**
     * Minifies JavaScript
     *
     * @param string $script Script to minify
     * @param string $error Error message (if any)
     * @return string Minified script or source string if error happened
     */
    public static function minifyJavaScript($script, &$error = '')
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['minifyJavaScript'])) {
            $fakeThis = false;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['minifyJavaScript'] as $hookMethod) {
                try {
                    $parameters = ['script' => $script];
                    $script = static::callUserFunction($hookMethod, $parameters, $fakeThis);
                } catch (\Exception $e) {
                    $errorMessage = 'Error minifying java script: ' . $e->getMessage();
                    $error .= $errorMessage;
                    static::devLog($errorMessage, \TYPO3\CMS\Core\Utility\GeneralUtility::class, 2, [
                        'JavaScript' => $script,
                        'Stack trace' => $e->getTrace(),
                        'hook' => $hookMethod
                    ]);
                }
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
     * If you are having trouble with proxys when reading URLs you can configure your way out of that with settings like $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] etc.
     *
     * @param string $url File/URL to read
     * @param int $includeHeader Whether the HTTP header should be fetched or not. 0=disable, 1=fetch header+content, 2=fetch header only
     * @param array $requestHeaders HTTP headers to be used in the request
     * @param array $report Error code/message and, if $includeHeader is 1, response meta data (HTTP status and content type)
     * @return mixed The content from the resource given as input. FALSE if an error has occurred.
     */
    public static function getUrl($url, $includeHeader = 0, $requestHeaders = false, &$report = null)
    {
        $content = false;
        if (isset($report)) {
            $report['error'] = 0;
            $report['message'] = '';
        }
        // Use cURL for: http, https, ftp, ftps, sftp and scp
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] == '1' && preg_match('/^(?:http|ftp)s?|s(?:ftp|cp):/', $url)) {
            if (isset($report)) {
                $report['lib'] = 'cURL';
            }
            // External URL without error checking.
            if (!function_exists('curl_init') || !($ch = curl_init())) {
                if (isset($report)) {
                    $report['error'] = -1;
                    $report['message'] = 'Couldn\'t initialize cURL.';
                }
                return false;
            }

            $followLocationSucceeded = @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

            $curlIncludeHeaders = !$followLocationSucceeded || $includeHeader;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, $curlIncludeHeaders ? 1 : 0);
            curl_setopt($ch, CURLOPT_NOBODY, $includeHeader == 2 ? 1 : 0);
            curl_setopt($ch, CURLOPT_HTTPGET, $includeHeader == 2 ? 'HEAD' : 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(0, (int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlTimeout']));

            if (is_array($requestHeaders)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
            }
            // (Proxy support implemented by Arco <arco@appeltaart.mine.nu>)
            if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']) {
                curl_setopt($ch, CURLOPT_PROXY, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, (bool)$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_host']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (bool)$GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer']);
                if ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_verify_peer']) {
                    if ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_cafile']) {
                        curl_setopt($ch, CURLOPT_CAINFO, $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_cafile']);
                    }
                    if ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_capath']) {
                        curl_setopt($ch, CURLOPT_CAPATH, $GLOBALS['TYPO3_CONF_VARS']['HTTP']['ssl_capath']);
                    }
                }
                if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyNTLM']) {
                    curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
                }
                if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']) {
                    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyTunnel']);
                }
                if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass']);
                }
            }
            $content = curl_exec($ch);
            $curlInfo = curl_getinfo($ch);

            // Remove additional proxy header block, when proxy is used for https request and CURL_HEADER is enabled.
            // Most HTTPS proxies add a second header before the actual server headers in their response, as a
            // response to the CONNECT message sent by the client to the proxy. cURL does not strip this since 2005,
            // so there are two headers arriving here, of which the first is not of interest to us—therefore, we can
            // safely strip it.
            // Detecting two linebreaks followed by a "HTTP/" (as done here) is the only reliable way to detect the
            // proxy headers, as the relevant RFCs do not specify the exact status code (it might be any of 2xx) or
            // the status message. Therefore, we check if there is a second HTTP headers block and then strip the
            // first one.
            if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer']
                && $curlIncludeHeaders
                && preg_match('/^https:/', $url)
                && strpos($content, "\r\n\r\nHTTP/") !== false
            ) {
                $content = self::stripHttpHeaders($content);
            }

            if (!$followLocationSucceeded) {
                // Check if we need to do redirects
                if ($curlInfo['http_code'] >= 300 && $curlInfo['http_code'] < 400) {
                    $locationUrl = $curlInfo['redirect_url'];
                    if (!$locationUrl) {
                        // Some curllib versions do not return redirect_url. Examine headers.
                        $locationUrl = self::getRedirectUrlFromHttpHeaders($content);
                    }
                    if ($locationUrl) {
                        $content = self::getUrl($locationUrl, $includeHeader, $requestHeaders, $report);
                        $followLocationSucceeded = true;
                    } else {
                        // Failure: we got a redirection status code but not the URL to redirect to.
                        $content = false;
                    }
                }
                if ($content && !$includeHeader) {
                    $content = self::stripHttpHeaders($content);
                }
            }

            if (isset($report)) {
                if (!$followLocationSucceeded && $curlInfo['http_code'] >= 300 && $curlInfo['http_code'] < 400) {
                    $report['http_code'] = $curlInfo['http_code'];
                    $report['content_type'] = $curlInfo['content_type'];
                    $report['error'] = CURLE_GOT_NOTHING;
                    $report['message'] = 'Expected "Location" header but got nothing.';
                } elseif ($content === false) {
                    $report['error'] = curl_errno($ch);
                    $report['message'] = curl_error($ch);
                } elseif ($includeHeader) {
                    // Set only for $includeHeader to work exactly like PHP variant
                    $report['http_code'] = $curlInfo['http_code'];
                    $report['content_type'] = $curlInfo['content_type'];
                }
            }
            curl_close($ch);
        } elseif ($includeHeader) {
            if (isset($report)) {
                $report['lib'] = 'socket';
            }
            $parsedURL = parse_url($url);
            if (!preg_match('/^https?/', $parsedURL['scheme'])) {
                if (isset($report)) {
                    $report['error'] = -1;
                    $report['message'] = 'Reading headers is not allowed for this protocol.';
                }
                return false;
            }
            $port = (int)$parsedURL['port'];
            if ($port < 1) {
                if ($parsedURL['scheme'] == 'http') {
                    $port = $port > 0 ? $port : 80;
                    $scheme = '';
                } else {
                    $port = $port > 0 ? $port : 443;
                    $scheme = 'ssl://';
                }
            }
            $errno = 0;
            $fp = @fsockopen(($scheme . $parsedURL['host']), $port, $errno, $errstr, 2.0);
            if (!$fp || $errno > 0) {
                if (isset($report)) {
                    $report['error'] = $errno ?: -1;
                    $report['message'] = $errno ? ($errstr ?: 'Socket error.') : 'Socket initialization error.';
                }
                return false;
            }
            $method = $includeHeader == 2 ? 'HEAD' : 'GET';
            $msg = $method . ' ' . (isset($parsedURL['path']) ? $parsedURL['path'] : '/')
                   . ($parsedURL['query'] ? '?' . $parsedURL['query'] : '') . ' HTTP/1.0' . CRLF
                   . 'Host: ' . $parsedURL['host'] . CRLF
                   . 'Connection: close' . CRLF;
            if (is_array($requestHeaders)) {
                $msg .= implode(CRLF, $requestHeaders) . CRLF;
            }
            $msg .= CRLF;
            fwrite($fp, $msg);
            while (!feof($fp)) {
                $line = fgets($fp, 2048);
                if (isset($report)) {
                    if (preg_match('|^HTTP/\\d\\.\\d +(\\d+)|', $line, $status)) {
                        $report['http_code'] = $status[1];
                    } elseif (preg_match('/^Content-Type: *(.*)/i', $line, $type)) {
                        $report['content_type'] = $type[1];
                    }
                }
                $content .= $line;
                if (trim($line) === '') {
                    // Stop at the first empty line (= end of header)
                    break;
                }
            }
            if ($includeHeader != 2) {
                $content .= stream_get_contents($fp);
            }
            fclose($fp);
        } elseif (is_array($requestHeaders)) {
            if (isset($report)) {
                $report['lib'] = 'file/context';
            }
            $parsedURL = parse_url($url);
            if (!preg_match('/^https?/', $parsedURL['scheme'])) {
                if (isset($report)) {
                    $report['error'] = -1;
                    $report['message'] = 'Sending request headers is not allowed for this protocol.';
                }
                return false;
            }
            $ctx = stream_context_get_default([
                'http' => [
                    'header' => implode(CRLF, $requestHeaders)
                ]
            ]);
            $content = @file_get_contents($url, false, $ctx);
            if ($content === false && isset($report)) {
                $report['error'] = -1;
                $report['message'] = 'Couldn\'t get URL: ' . (isset($http_response_header) ? implode(LF, $http_response_header) : $url);
            }
        } else {
            if (isset($report)) {
                $report['lib'] = 'file';
            }
            $content = @file_get_contents($url);
            if ($content === false && isset($report)) {
                $report['error'] = -1;
                $report['message'] = 'Couldn\'t get URL: ' . (isset($http_response_header) ? implode(LF, $http_response_header) : $url);
            }
        }
        return $content;
    }

    /**
     * Parses HTTP headers and returns the content of the "Location" header
     * or the empty string if no such header found.
     *
     * @param string $content
     * @return string
     */
    protected static function getRedirectUrlFromHttpHeaders($content)
    {
        $result = '';
        $headers = explode("\r\n", $content);
        foreach ($headers as $header) {
            if ($header == '') {
                break;
            }
            if (preg_match('/^\s*Location\s*:/i', $header)) {
                list(, $result) = self::trimExplode(':', $header, false, 2);
                if ($result) {
                    $result = self::locationHeaderUrl($result);
                }
                break;
            }
        }
        return $result;
    }

    /**
     * Strips HTTP headers from the content.
     *
     * @param string $content
     * @return string
     */
    protected static function stripHttpHeaders($content)
    {
        $headersEndPos = strpos($content, "\r\n\r\n");
        if ($headersEndPos) {
            $content = substr($content, $headersEndPos + 4);
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
        if (TYPO3_OS === 'WIN') {
            return true;
        }
        $result = false;
        // Make path absolute
        if (!static::isAbsPath($path)) {
            $path = static::getFileAbsFileName($path, false);
        }
        if (static::isAllowedAbsPath($path)) {
            if (@is_file($path)) {
                $targetPermissions = isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'])
                    ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask']
                    : '0644';
            } elseif (@is_dir($path)) {
                $targetPermissions = isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'])
                    ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask']
                    : '0755';
            }
            if (!empty($targetPermissions)) {
                // make sure it's always 4 digits
                $targetPermissions = str_pad($targetPermissions, 4, 0, STR_PAD_LEFT);
                $targetPermissions = octdec($targetPermissions);
                // "@" is there because file is not necessarily OWNED by the user
                $result = @chmod($path, $targetPermissions);
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
                            if (@is_file(($path . '/' . $file))) {
                                $recursionResult = static::fixPermissions($path . '/' . $file);
                            } elseif (@is_dir(($path . '/' . $file))) {
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
     * @param string $filepath Absolute file path to write to inside "typo3temp/". First part of this string must match PATH_site."typo3temp/"
     * @param string $content Content string to write
     * @return string Returns NULL on success, otherwise an error string telling about the problem.
     */
    public static function writeFileToTypo3tempDir($filepath, $content)
    {
        if (!defined('PATH_site')) {
            return 'PATH_site constant was NOT defined!';
        }

        // Parse filepath into directory and basename:
        $fI = pathinfo($filepath);
        $fI['dirname'] .= '/';
        // Check parts:
        if (!static::validPathStr($filepath) || !$fI['basename'] || strlen($fI['basename']) >= 60) {
            return 'Input filepath "' . $filepath . '" was generally invalid!';
        }
        // Setting main temporary directory name (standard)
        $dirName = PATH_site . 'typo3temp/';
        if (!@is_dir($dirName)) {
            return 'PATH_site + "typo3temp/" was not a directory!';
        }
        if (!static::isFirstPartOfStr($fI['dirname'], $dirName)) {
            return '"' . $fI['dirname'] . '" was not within directory PATH_site + "typo3temp/"';
        }
        // Checking if the "subdir" is found:
        $subdir = substr($fI['dirname'], strlen($dirName));
        if ($subdir) {
            if (preg_match('/^[[:alnum:]_]+\\/$/', $subdir) || preg_match('/^[[:alnum:]_]+\\/[[:alnum:]_]+\\/$/', $subdir)) {
                $dirName .= $subdir;
                if (!@is_dir($dirName)) {
                    static::mkdir_deep(PATH_site . 'typo3temp/', $subdir);
                }
            } else {
                return 'Subdir, "' . $subdir . '", was NOT on the form "[[:alnum:]_]/" or  "[[:alnum:]_]/[[:alnum:]_]/"';
            }
        }
        // Checking dir-name again (sub-dir might have been created):
        if (@is_dir($dirName)) {
            if ($filepath == $dirName . $fI['basename']) {
                static::writeFile($filepath, $content);
                if (!@is_file($filepath)) {
                    return 'The file was not written to the disk. Please, check that you have write permissions to the typo3temp/ directory.';
                }
            } else {
                return 'Calculated filelocation didn\'t match input "' . $filepath . '".';
            }
        } else {
            return '"' . $dirName . '" is not a directory!';
        }
        return null;
    }

    /**
     * Wrapper function for mkdir.
     * Sets folder permissions according to $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask']
     * and group ownership according to $GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup']
     *
     * @param string $newFolder Absolute path to folder, see PHP mkdir() function. Removes trailing slash internally.
     * @return bool TRUE if @mkdir went well!
     */
    public static function mkdir($newFolder)
    {
        $result = @mkdir($newFolder, octdec($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask']));
        if ($result) {
            static::fixPermissions($newFolder);
        }
        return $result;
    }

    /**
     * Creates a directory - including parent directories if necessary and
     * sets permissions on newly created directories.
     *
     * @param string $directory Target directory to create. Must a have trailing slash
     * @param string $deepDirectory Directory to create. This second parameter
     * @return void
     * @throws \InvalidArgumentException If $directory or $deepDirectory are not strings
     * @throws \RuntimeException If directory could not be created
     */
    public static function mkdir_deep($directory, $deepDirectory = '')
    {
        if (!is_string($directory)) {
            throw new \InvalidArgumentException('The specified directory is of type "' . gettype($directory) . '" but a string is expected.', 1303662955);
        }
        if (!is_string($deepDirectory)) {
            throw new \InvalidArgumentException('The specified directory is of type "' . gettype($deepDirectory) . '" but a string is expected.', 1303662956);
        }
        // Ensure there is only one slash
        $fullPath = rtrim($directory, '/') . '/' . ltrim($deepDirectory, '/');
        if ($fullPath !== '' && !is_dir($fullPath)) {
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
        $permissionMask = octdec($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask']);
        if (!@is_dir($currentPath)) {
            do {
                $firstCreatedPath = $currentPath;
                $separatorPosition = strrpos($currentPath, DIRECTORY_SEPARATOR);
                $currentPath = substr($currentPath, 0, $separatorPosition);
            } while (!is_dir($currentPath) && $separatorPosition !== false);
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
     * @return bool TRUE if @rmdir went well!
     */
    public static function rmdir($path, $removeNonEmpty = false)
    {
        $OK = false;
        // Remove trailing slash
        $path = preg_replace('|/$|', '', $path);
        if (file_exists($path)) {
            $OK = true;
            if (!is_link($path) && is_dir($path)) {
                if ($removeNonEmpty == true && ($handle = @opendir($path))) {
                    while ($OK && false !== ($file = readdir($handle))) {
                        if ($file == '.' || $file == '..') {
                            continue;
                        }
                        $OK = static::rmdir($path . '/' . $file, $removeNonEmpty);
                    }
                    closedir($handle);
                }
                if ($OK) {
                    $OK = @rmdir($path);
                }
            } elseif (is_link($path) && is_dir($path) && TYPO3_OS === 'WIN') {
                $OK = @rmdir($path);
            } else {
                // If $path is a file, simply remove it
                $OK = @unlink($path);
            }
            clearstatcache();
        } elseif (is_link($path)) {
            $OK = @unlink($path);
            clearstatcache();
        }
        return $OK;
    }

    /**
     * Flushes a directory by first moving to a temporary resource, and then
     * triggering the remove process. This way directories can be flushed faster
     * to prevent race conditions on concurrent processes accessing the same directory.
     *
     * @param string $directory The directory to be renamed and flushed
     * @param bool $keepOriginalDirectory Whether to only empty the directory and not remove it
     * @param bool $flushOpcodeCache Also flush the opcode cache right after renaming the directory.
     * @return bool Whether the action was successful
     */
    public static function flushDirectory($directory, $keepOriginalDirectory = false, $flushOpcodeCache = false)
    {
        $result = false;

        if (is_dir($directory)) {
            $temporaryDirectory = rtrim($directory, '/') . '.' . StringUtility::getUniqueId('remove') . '/';
            if (rename($directory, $temporaryDirectory)) {
                if ($flushOpcodeCache) {
                    self::makeInstance(OpcodeCacheService::class)->clearAllActive($directory);
                }
                if ($keepOriginalDirectory) {
                    static::mkdir($directory);
                }
                clearstatcache();
                $result = static::rmdir($temporaryDirectory, true);
            }
        }

        return $result;
    }

    /**
     * Returns an array with the names of folders in a specific path
     * Will return 'error' (string) if there were an error with reading directory content.
     *
     * @param string $path Path to list directories from
     * @return array Returns an array with the directory entries as values. If no path, the return value is nothing.
     */
    public static function get_dirs($path)
    {
        if ($path) {
            if (is_dir($path)) {
                $dir = scandir($path);
                $dirs = [];
                foreach ($dir as $entry) {
                    if (is_dir($path . '/' . $entry) && $entry != '..' && $entry != '.') {
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
     * @return array|string Array of the files found, or an error message in case the path could not be opened.
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
        $extensionList = ',' . $extensionList . ',';
        $files = [];
        foreach ($rawFileList as $entry) {
            $completePathToEntry = $pathPrefix . $entry;
            if (!@is_file($completePathToEntry)) {
                continue;
            }

            if (
                ($extensionList === ',,' || stripos($extensionList, ',' . pathinfo($entry, PATHINFO_EXTENSION) . ',') !== false)
                && ($excludePattern === '' || !preg_match(('/^' . $excludePattern . '$/'), $entry))
            ) {
                if ($order !== 'mtime') {
                    $files[] = $entry;
                } else {
                    // Store the value in the key so we can do a fast asort later.
                    $files[$entry] = filemtime($completePathToEntry);
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
     * @param array $fileArr Empty input array (will have files added to it)
     * @param string $path The path to read recursively from (absolute) (include trailing slash!)
     * @param string $extList Comma list of file extensions: Only files with extensions in this list (if applicable) will be selected.
     * @param bool $regDirs If set, directories are also included in output.
     * @param int $recursivityLevels The number of levels to dig down...
     * @param string $excludePattern regex pattern of files/directories to exclude
     * @return array An array with the found files/directories.
     */
    public static function getAllFilesAndFoldersInPath(array $fileArr, $path, $extList = '', $regDirs = false, $recursivityLevels = 99, $excludePattern = '')
    {
        if ($regDirs) {
            $fileArr[md5($path)] = $path;
        }
        $fileArr = array_merge($fileArr, self::getFilesInDir($path, $extList, 1, 1, $excludePattern));
        $dirs = self::get_dirs($path);
        if ($recursivityLevels > 0 && is_array($dirs)) {
            foreach ($dirs as $subdirs) {
                if ((string)$subdirs !== '' && ($excludePattern === '' || !preg_match(('/^' . $excludePattern . '$/'), $subdirs))) {
                    $fileArr = self::getAllFilesAndFoldersInPath($fileArr, $path . $subdirs . '/', $extList, $regDirs, $recursivityLevels - 1, $excludePattern);
                }
            }
        }
        return $fileArr;
    }

    /**
     * Removes the absolute part of all files/folders in fileArr
     *
     * @param array $fileArr The file array to remove the prefix from
     * @param string $prefixToRemove The prefix path to remove (if found as first part of string!)
     * @return array The input $fileArr processed.
     */
    public static function removePrefixPathFromList(array $fileArr, $prefixToRemove)
    {
        foreach ($fileArr as $k => &$absFileRef) {
            if (self::isFirstPartOfStr($absFileRef, $prefixToRemove)) {
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
        if (strpos($pathStr, '..') === false) {
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
     * @throws \InvalidArgumentException
     */
    public static function locationHeaderUrl($path)
    {
        $uI = parse_url($path);
        // relative to HOST
        if ($path[0] === '/') {
            $path = self::getIndpEnv('TYPO3_REQUEST_HOST') . $path;
        } elseif (!$uI['scheme']) {
            // No scheme either
            $path = self::getIndpEnv('TYPO3_REQUEST_DIR') . $path;
        }
        // Can be removed once minimum PHP requirement is at least 5.5.22 or 5.6.6
        if (strpbrk($path, "\r\n") !== false) {
            throw new \InvalidArgumentException('HTTP header injection attempt in "' . $path . '"', 1448194036);
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
        // Check for PHP restrictions of the maximum size of one of the $_FILES
        $phpUploadLimit = self::getBytesFromSizeMeasurement(ini_get('upload_max_filesize'));
        // Check for PHP restrictions of the maximum $_POST size
        $phpPostLimit = self::getBytesFromSizeMeasurement(ini_get('post_max_size'));
        // If the total amount of post data is smaller (!) than the upload_max_filesize directive,
        // then this is the real limit in PHP
        $phpUploadLimit = $phpPostLimit > 0 && $phpPostLimit < $phpUploadLimit ? $phpPostLimit : $phpUploadLimit;
        return floor(($phpUploadLimit)) / 1024;
    }

    /**
     * Gets the bytes value from a measurement string like "100k".
     *
     * @param string $measurement The measurement (e.g. "100k")
     * @return int The bytes value (e.g. 102400)
     */
    public static function getBytesFromSizeMeasurement($measurement)
    {
        $bytes = floatval($measurement);
        if (stripos($measurement, 'G')) {
            $bytes *= 1024 * 1024 * 1024;
        } elseif (stripos($measurement, 'M')) {
            $bytes *= 1024 * 1024;
        } elseif (stripos($measurement, 'K')) {
            $bytes *= 1024;
        }
        return $bytes;
    }

    /**
     * Retrieves the maximum path length that is valid in the current environment.
     *
     * @return int The maximum available path length
     */
    public static function getMaximumPathLength()
    {
        return PHP_MAXPATHLEN;
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
     * TYPO3_CONF_VARS[TYPO3_MODE][versionNumberInFilename]
     * = TRUE (BE) / "embed" (FE) : modify filename
     * = FALSE (BE) / "querystring" (FE) : add timestamp as parameter
     *
     * @param string $file Relative path to file including all potential query parameters (not htmlspecialchared yet)
     * @return string Relative path with version filename including the timestamp
     */
    public static function createVersionNumberedFilename($file)
    {
        $lookupFile = explode('?', $file);
        $path = self::resolveBackPath(self::dirname(PATH_thisScript) . '/' . $lookupFile[0]);

        $doNothing = false;
        if (TYPO3_MODE == 'FE') {
            $mode = strtolower($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['versionNumberInFilename']);
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
            $mode = $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['versionNumberInFilename'];
        }
        if (!file_exists($path) || $doNothing) {
            // File not found, return filename unaltered
            $fullName = $file;
        } else {
            if (!$mode) {
                // If use of .htaccess rule is not configured,
                // we use the default query-string method
                if ($lookupFile[1]) {
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
                $fullName .= $lookupFile[1] ? '?' . $lookupFile[1] : '';
            }
        }
        return $fullName;
    }

    /*************************
     *
     * SYSTEM INFORMATION
     *
     *************************/
    /**
     * Returns the HOST+DIR-PATH of the current script (The URL, but without 'http://' and without script-filename)
     *
     * @return string
     * @deprecated since TYPO3 CMS 7, will be removed in CMS 8, use GeneralUtility::getIndpEnv* instead
     */
    public static function getThisUrl()
    {
        self::logDeprecatedFunction();
        // Url of this script
        $p = parse_url(self::getIndpEnv('TYPO3_REQUEST_SCRIPT'));
        $dir = self::dirname($p['path']) . '/';
        // Strip file
        $url = str_replace('//', '/', $p['host'] . ($p['port'] ? ':' . $p['port'] : '') . $dir);
        return $url;
    }

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
     * Takes a full URL, $url, possibly with a querystring and overlays the $getParams arrays values onto the quirystring, packs it all together and returns the URL again.
     * So basically it adds the parameters in $getParams to an existing URL, $url
     *
     * @param string $url URL string
     * @param array $getParams Array of key/value pairs for get parameters to add/overrule with. Can be multidimensional.
     * @return string Output URL with added getParams.
     */
    public static function linkThisUrl($url, array $getParams = [])
    {
        $parts = parse_url($url);
        $getP = [];
        if ($parts['query']) {
            parse_str($parts['query'], $getP);
        }
        ArrayUtility::mergeRecursiveWithOverrule($getP, $getParams);
        $uP = explode('?', $url);
        $params = self::implodeArrayForUrl('', $getP);
        $outurl = $uP[0] . ($params ? '?' . substr($params, 1) : '');
        return $outurl;
    }

    /**
     * Abstraction method which returns System Environment Variables regardless of server OS, CGI/MODULE version etc. Basically this is SERVER variables for most of them.
     * This should be used instead of getEnv() and $_SERVER/ENV_VARS to get reliable values for all situations.
     *
     * @param string $getEnvName Name of the "environment variable"/"server variable" you wish to use. Valid values are SCRIPT_NAME, SCRIPT_FILENAME, REQUEST_URI, PATH_INFO, REMOTE_ADDR, REMOTE_HOST, HTTP_REFERER, HTTP_HOST, HTTP_USER_AGENT, HTTP_ACCEPT_LANGUAGE, QUERY_STRING, TYPO3_DOCUMENT_ROOT, TYPO3_HOST_ONLY, TYPO3_HOST_ONLY, TYPO3_REQUEST_HOST, TYPO3_REQUEST_URL, TYPO3_REQUEST_SCRIPT, TYPO3_REQUEST_DIR, TYPO3_SITE_URL, _ARRAY
     * @return string Value based on the input key, independent of server/os environment.
     * @throws \UnexpectedValueException
     */
    public static function getIndpEnv($getEnvName)
    {
        if (isset(self::$indpEnvCache[$getEnvName])) {
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
        SCRIPT_FILENAME	=	Absolute filename of script		(Differs between windows/unix). On windows 'C:\\blabla\\blabl\\' will be converted to 'C:/blabla/blabl/'Special extras:
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
                $retVal = self::isRunningOnCgiServerApi()
                    && ($_SERVER['ORIG_PATH_INFO'] ?: $_SERVER['PATH_INFO'])
                        ? ($_SERVER['ORIG_PATH_INFO'] ?: $_SERVER['PATH_INFO'])
                        : ($_SERVER['ORIG_SCRIPT_NAME'] ?: $_SERVER['SCRIPT_NAME']);
                // Add a prefix if TYPO3 is behind a proxy: ext-domain.com => int-server.com/prefix
                if (self::cmpIP($_SERVER['REMOTE_ADDR'], $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'])) {
                    if (self::getIndpEnv('TYPO3_SSL') && $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefixSSL']) {
                        $retVal = $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefixSSL'] . $retVal;
                    } elseif ($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefix']) {
                        $retVal = $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyPrefix'] . $retVal;
                    }
                }
                break;
            case 'SCRIPT_FILENAME':
                $retVal = PATH_thisScript;
                break;
            case 'REQUEST_URI':
                // Typical application of REQUEST_URI is return urls, forms submitting to itself etc. Example: returnUrl='.rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'))
                if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['requestURIvar']) {
                    // This is for URL rewriters that store the original URI in a server variable (eg ISAPI_Rewriter for IIS: HTTP_X_REWRITE_URL)
                    list($v, $n) = explode('|', $GLOBALS['TYPO3_CONF_VARS']['SYS']['requestURIvar']);
                    $retVal = $GLOBALS[$v][$n];
                } elseif (!$_SERVER['REQUEST_URI']) {
                    // This is for ISS/CGI which does not have the REQUEST_URI available.
                    $retVal = '/' . ltrim(self::getIndpEnv('SCRIPT_NAME'), '/') . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '');
                } else {
                    $retVal = '/' . ltrim($_SERVER['REQUEST_URI'], '/');
                }
                // Add a prefix if TYPO3 is behind a proxy: ext-domain.com => int-server.com/prefix
                if (self::cmpIP($_SERVER['REMOTE_ADDR'], $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'])) {
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
                if (!self::isRunningOnCgiServerApi()) {
                    $retVal = $_SERVER['PATH_INFO'];
                }
                break;
            case 'TYPO3_REV_PROXY':
                $retVal = self::cmpIP($_SERVER['REMOTE_ADDR'], $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']);
                break;
            case 'REMOTE_ADDR':
                $retVal = $_SERVER['REMOTE_ADDR'];
                if (self::cmpIP($_SERVER['REMOTE_ADDR'], $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'])) {
                    $ip = self::trimExplode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
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
                    if (self::validIP($ip)) {
                        $retVal = $ip;
                    }
                }
                break;
            case 'HTTP_HOST':
                // if it is not set we're most likely on the cli
                $retVal = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
                if (isset($_SERVER['REMOTE_ADDR']) && static::cmpIP($_SERVER['REMOTE_ADDR'], $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'])) {
                    $host = self::trimExplode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
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
                if (!static::isAllowedHostHeaderValue($retVal)) {
                    throw new \UnexpectedValueException(
                        'The current host header value does not match the configured trusted hosts pattern! Check the pattern defined in $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'trustedHostsPattern\'] and adapt it, if you want to allow the current host header \'' . $retVal . '\' for your installation.',
                        1396795884
                    );
                }
                break;
            case 'HTTP_REFERER':

            case 'HTTP_USER_AGENT':

            case 'HTTP_ACCEPT_ENCODING':

            case 'HTTP_ACCEPT_LANGUAGE':

            case 'REMOTE_HOST':

            case 'QUERY_STRING':
                $retVal = '';
                if (isset($_SERVER[$getEnvName])) {
                    $retVal = $_SERVER[$getEnvName];
                }
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
                    $DR = substr($SFN, 0, -(strlen($commonEnd) + 1));
                }
                $retVal = $DR;
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
                $url = self::getIndpEnv('TYPO3_REQUEST_DIR');
                // This can only be set by external entry scripts
                if (defined('TYPO3_PATH_WEB')) {
                    $retVal = $url;
                } elseif (defined('PATH_thisScript') && defined('PATH_site')) {
                    $lPath = PathUtility::stripPathSitePrefix(dirname(PATH_thisScript)) . '/';
                    $siteUrl = substr($url, 0, -strlen($lPath));
                    if (substr($siteUrl, -1) != '/') {
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
                $proxySSL = trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxySSL']);
                if ($proxySSL == '*') {
                    $proxySSL = $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'];
                }
                if (self::cmpIP($_SERVER['REMOTE_ADDR'], $proxySSL)) {
                    $retVal = true;
                } else {
                    $retVal = $_SERVER['SSL_SESSION_ID'] || strtolower($_SERVER['HTTPS']) === 'on' || (string)$_SERVER['HTTPS'] === '1';
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
                    'HTTP_ACCEPT_LANGUAGE'
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
     * If the pattern is not defined (which only can happen early in the bootstrap), deny any value.
     * The result is saved, so the check needs to be executed only once.
     *
     * @param string $hostHeaderValue HTTP_HOST header value as sent during the request (may include port)
     * @return bool
     */
    public static function isAllowedHostHeaderValue($hostHeaderValue)
    {
        if (static::$allowHostHeaderValue === true) {
            return true;
        }

        if (static::isInternalRequestType()) {
            return static::$allowHostHeaderValue = true;
        }

        // Deny the value if trusted host patterns is empty, which means we are early in the bootstrap
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'])) {
            return false;
        }

        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] === self::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL) {
            static::$allowHostHeaderValue = true;
        } else {
            static::$allowHostHeaderValue = static::hostHeaderValueMatchesTrustedHostsPattern($hostHeaderValue);
        }

        return static::$allowHostHeaderValue;
    }

    /**
     * Checks if the provided host header value matches the trusted hosts pattern without any preprocessing.
     *
     * @param string $hostHeaderValue
     * @return bool
     * @internal
     */
    public static function hostHeaderValueMatchesTrustedHostsPattern($hostHeaderValue)
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] === self::ENV_TRUSTED_HOSTS_PATTERN_SERVER_NAME) {
            // Allow values that equal the server name
            // Note that this is only secure if name base virtual host are configured correctly in the webserver
            $defaultPort = self::getIndpEnv('TYPO3_SSL') ? '443' : '80';
            $parsedHostValue = parse_url('http://' . $hostHeaderValue);
            if (isset($parsedHostValue['port'])) {
                $hostMatch = (strtolower($parsedHostValue['host']) === strtolower($_SERVER['SERVER_NAME']) && (string)$parsedHostValue['port'] === $_SERVER['SERVER_PORT']);
            } else {
                $hostMatch = (strtolower($hostHeaderValue) === strtolower($_SERVER['SERVER_NAME']) && $defaultPort === $_SERVER['SERVER_PORT']);
            }
        } else {
            // In case name based virtual hosts are not possible, we allow setting a trusted host pattern
            // See https://typo3.org/teams/security/security-bulletins/typo3-core/typo3-core-sa-2014-001/ for further details
            $hostMatch = (bool)preg_match('/^' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] . '$/i', $hostHeaderValue);
        }

        return $hostMatch;
    }

    /**
     * Allows internal requests to the install tool and from the command line.
     * We accept this risk to have the install tool always available.
     * Also CLI needs to be allowed as unfortunately AbstractUserAuthentication::getAuthInfoArray()
     * accesses HTTP_HOST without reason on CLI
     * Additionally, allows requests when no REQUESTTYPE is set, which can happen quite early in the
     * Bootstrap. See Application.php in EXT:backend/Classes/Http/.
     *
     * @return bool
     */
    protected static function isInternalRequestType()
    {
        return !defined('TYPO3_REQUESTTYPE') || (defined('TYPO3_REQUESTTYPE') && TYPO3_REQUESTTYPE & (TYPO3_REQUESTTYPE_INSTALL | TYPO3_REQUESTTYPE_CLI));
    }

    /**
     * Gets the unixtime as milliseconds.
     *
     * @return int The unixtime as milliseconds
     */
    public static function milliseconds()
    {
        return round(microtime(true) * 1000);
    }

    /**
     * Client Browser Information
     *
     * @param string $useragent Alternative User Agent string (if empty, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT') is used)
     * @return array Parsed information about the HTTP_USER_AGENT in categories BROWSER, VERSION, SYSTEM
     */
    public static function clientInfo($useragent = '')
    {
        if (!$useragent) {
            $useragent = self::getIndpEnv('HTTP_USER_AGENT');
        }
        $bInfo = [];
        // Which browser?
        if (strpos($useragent, 'Konqueror') !== false) {
            $bInfo['BROWSER'] = 'konqu';
        } elseif (strpos($useragent, 'Opera') !== false) {
            $bInfo['BROWSER'] = 'opera';
        } elseif (strpos($useragent, 'MSIE') !== false) {
            $bInfo['BROWSER'] = 'msie';
        } elseif (strpos($useragent, 'Mozilla') !== false) {
            $bInfo['BROWSER'] = 'net';
        } elseif (strpos($useragent, 'Flash') !== false) {
            $bInfo['BROWSER'] = 'flash';
        }
        if (isset($bInfo['BROWSER'])) {
            // Browser version
            switch ($bInfo['BROWSER']) {
                case 'net':
                    $bInfo['VERSION'] = floatval(substr($useragent, 8));
                    if (strpos($useragent, 'Netscape6/') !== false) {
                        $bInfo['VERSION'] = floatval(substr(strstr($useragent, 'Netscape6/'), 10));
                    }
                    // Will we ever know if this was a typo or intention...?! :-(
                    if (strpos($useragent, 'Netscape/6') !== false) {
                        $bInfo['VERSION'] = floatval(substr(strstr($useragent, 'Netscape/6'), 10));
                    }
                    if (strpos($useragent, 'Netscape/7') !== false) {
                        $bInfo['VERSION'] = floatval(substr(strstr($useragent, 'Netscape/7'), 9));
                    }
                    break;
                case 'msie':
                    $tmp = strstr($useragent, 'MSIE');
                    $bInfo['VERSION'] = floatval(preg_replace('/^[^0-9]*/', '', substr($tmp, 4)));
                    break;
                case 'opera':
                    $tmp = strstr($useragent, 'Opera');
                    $bInfo['VERSION'] = floatval(preg_replace('/^[^0-9]*/', '', substr($tmp, 5)));
                    break;
                case 'konqu':
                    $tmp = strstr($useragent, 'Konqueror/');
                    $bInfo['VERSION'] = floatval(substr($tmp, 10));
                    break;
            }
            // Client system
            if (strpos($useragent, 'Win') !== false) {
                $bInfo['SYSTEM'] = 'win';
            } elseif (strpos($useragent, 'Mac') !== false) {
                $bInfo['SYSTEM'] = 'mac';
            } elseif (strpos($useragent, 'Linux') !== false || strpos($useragent, 'X11') !== false || strpos($useragent, 'SGI') !== false || strpos($useragent, ' SunOS ') !== false || strpos($useragent, ' HP-UX ') !== false) {
                $bInfo['SYSTEM'] = 'unix';
            }
        }
        return $bInfo;
    }

    /**
     * Get the fully-qualified domain name of the host.
     *
     * @param bool $requestHost Use request host (when not in CLI mode).
     * @return string The fully-qualified host name.
     */
    public static function getHostname($requestHost = true)
    {
        $host = '';
        // If not called from the command-line, resolve on getIndpEnv()
        // Note that TYPO3_REQUESTTYPE is not used here as it may not yet be defined
        if ($requestHost && (!defined('TYPO3_cliMode') || !TYPO3_cliMode)) {
            $host = self::getIndpEnv('HTTP_HOST');
        }
        if (!$host) {
            // will fail for PHP 4.1 and 4.2
            $host = @php_uname('n');
            // 'n' is ignored in broken installations
            if (strpos($host, ' ')) {
                $host = '';
            }
        }
        // We have not found a FQDN yet
        if ($host && strpos($host, '.') === false) {
            $ip = gethostbyname($host);
            // We got an IP address
            if ($ip != $host) {
                $fqdn = gethostbyaddr($ip);
                if ($ip != $fqdn) {
                    $host = $fqdn;
                }
            }
        }
        if (!$host) {
            $host = 'localhost.localdomain';
        }
        return $host;
    }

    /*************************
     *
     * TYPO3 SPECIFIC FUNCTIONS
     *
     *************************/
    /**
     * Returns the absolute filename of a relative reference, resolves the "EXT:" prefix
     * (way of referring to files inside extensions) and checks that the file is inside
     * the PATH_site of the TYPO3 installation and implies a check with
     * \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr().
     *
     * @param string $filename The input filename/filepath to evaluate
     * @param bool $onlyRelative If $onlyRelative is set (which it is by default), then only return values relative to the current PATH_site is accepted.
     * @param bool $relToTYPO3_mainDir If $relToTYPO3_mainDir is set, then relative paths are relative to PATH_typo3 constant - otherwise (default) they are relative to PATH_site
     * @return string Returns the absolute filename of $filename if valid, otherwise blank string.
     */
    public static function getFileAbsFileName($filename, $onlyRelative = true, $relToTYPO3_mainDir = false)
    {
        if ((string)$filename === '') {
            return '';
        }
        $relPathPrefix = PATH_site;
        if ($relToTYPO3_mainDir) {
            $relPathPrefix = PATH_typo3;
        }

        // Extension
        if (strpos($filename, 'EXT:') === 0) {
            list($extKey, $local) = explode('/', substr($filename, 4), 2);
            $filename = '';
            if ((string)$extKey !== '' && ExtensionManagementUtility::isLoaded($extKey) && (string)$local !== '') {
                $filename = ExtensionManagementUtility::extPath($extKey) . $local;
            }
        } elseif (!static::isAbsPath($filename)) {
            // relative. Prepended with $relPathPrefix
            $filename = $relPathPrefix . $filename;
        } elseif ($onlyRelative && !static::isFirstPartOfStr($filename, $relPathPrefix)) {
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
     * @see http://php.net/manual/en/security.filesystem.nullbytes.php
     */
    public static function validPathStr($theFile)
    {
        return strpos($theFile, '//') === false && strpos($theFile, '\\') === false
            && preg_match('#(?:^\\.\\.|/\\.\\./|[[:cntrl:]])#u', $theFile) === 0;
    }

    /**
     * Checks if the $path is absolute or relative (detecting either '/' or 'x:/' as first part of string) and returns TRUE if so.
     *
     * @param string $path File path to evaluate
     * @return bool
     */
    public static function isAbsPath($path)
    {
        return $path[0] === '/' || TYPO3_OS === 'WIN' && (strpos($path, ':/') === 1 || strpos($path, ':\\') === 1);
    }

    /**
     * Returns TRUE if the path is absolute, without backpath '..' and within the PATH_site OR within the lockRootPath
     *
     * @param string $path File path to evaluate
     * @return bool
     */
    public static function isAllowedAbsPath($path)
    {
        $lockRootPath = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'];
        return static::isAbsPath($path) && static::validPathStr($path)
            && (static::isFirstPartOfStr($path, PATH_site)
                || $lockRootPath && static::isFirstPartOfStr($path, $lockRootPath));
    }

    /**
     * Verifies the input filename against the 'fileDenyPattern'. Returns TRUE if OK.
     *
     * Filenames are not allowed to contain control characters. Therefore we
     * always filter on [[:cntrl:]].
     *
     * @param string $filename File path to evaluate
     * @return bool
     */
    public static function verifyFilenameAgainstDenyPattern($filename)
    {
        $pattern = '/[[:cntrl:]]/';
        if ((string)$filename !== '' && (string)$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] !== '') {
            $pattern = '/(?:[[:cntrl:]]|' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] . ')/i';
        }
        return !preg_match($pattern, $filename);
    }

    /**
     * Low level utility function to copy directories and content recursive
     *
     * @param string $source Path to source directory, relative to document root or absolute
     * @param string $destination Path to destination directory, relative to document root or absolute
     */
    public static function copyDirectory($source, $destination)
    {
        if (strpos($source, PATH_site) === false) {
            $source = PATH_site . $source;
        }
        if (strpos($destination, PATH_site) === false) {
            $destination = PATH_site . $destination;
        }
        if (static::isAllowedAbsPath($source) && static::isAllowedAbsPath($destination)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $target = $destination . '/' . $iterator->getSubPathName();
                if ($item->isDir()) {
                    static::mkdir($target);
                } else {
                    static::upload_copy_move($item, $target);
                }
            }
        }
    }

    /**
     * Checks if a given string is a valid frame URL to be loaded in the
     * backend.
     *
     * @param string $url potential URL to check
     * @return string either $url if $url is considered to be harmless, or an
     */
    public static function sanitizeLocalUrl($url = '')
    {
        $sanitizedUrl = '';
        $decodedUrl = rawurldecode($url);
        if (!empty($url) && self::removeXSS($decodedUrl) === $decodedUrl) {
            $parsedUrl = parse_url($decodedUrl);
            $testAbsoluteUrl = self::resolveBackPath($decodedUrl);
            $testRelativeUrl = self::resolveBackPath(self::dirname(self::getIndpEnv('SCRIPT_NAME')) . '/' . $decodedUrl);
            // Pass if URL is on the current host:
            if (self::isValidUrl($decodedUrl)) {
                if (self::isOnCurrentHost($decodedUrl) && strpos($decodedUrl, self::getIndpEnv('TYPO3_SITE_URL')) === 0) {
                    $sanitizedUrl = $url;
                }
            } elseif (self::isAbsPath($decodedUrl) && self::isAllowedAbsPath($decodedUrl)) {
                $sanitizedUrl = $url;
            } elseif (strpos($testAbsoluteUrl, self::getIndpEnv('TYPO3_SITE_PATH')) === 0 && $decodedUrl[0] === '/') {
                $sanitizedUrl = $url;
            } elseif (empty($parsedUrl['scheme']) && strpos($testRelativeUrl, self::getIndpEnv('TYPO3_SITE_PATH')) === 0 && $decodedUrl[0] !== '/') {
                $sanitizedUrl = $url;
            }
        }
        if (!empty($url) && empty($sanitizedUrl)) {
            self::sysLog('The URL "' . $url . '" is not considered to be local and was denied.', 'core', self::SYSLOG_SEVERITY_NOTICE);
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
        if (is_uploaded_file($source)) {
            $uploaded = true;
            // Return the value of move_uploaded_file, and if FALSE the temporary $source is still around so the user can use unlink to delete it:
            $uploadedResult = move_uploaded_file($source, $destination);
        } else {
            $uploaded = false;
            @copy($source, $destination);
        }
        // Change the permissions of the file
        self::fixPermissions($destination);
        // If here the file is copied and the temporary $source is still around, so when returning FALSE the user can try unlink to delete the $source
        return $uploaded ? $uploadedResult : false;
    }

    /**
     * Will move an uploaded file (normally in "/tmp/xxxxx") to a temporary filename in PATH_site."typo3temp/" from where TYPO3 can use it.
     * Use this function to move uploaded files to where you can work on them.
     * REMEMBER to use \TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile() afterwards - otherwise temp-files will build up! They are NOT automatically deleted in PATH_site."typo3temp/"!
     *
     * @param string $uploadedFileName The temporary uploaded filename, eg. $_FILES['[upload field name here]']['tmp_name']
     * @return string If a new file was successfully created, return its filename, otherwise blank string.
     * @see unlink_tempfile(), upload_copy_move()
     */
    public static function upload_to_tempfile($uploadedFileName)
    {
        if (is_uploaded_file($uploadedFileName)) {
            $tempFile = self::tempnam('upload_temp_');
            move_uploaded_file($uploadedFileName, $tempFile);
            return @is_file($tempFile) ? $tempFile : '';
        }
    }

    /**
     * Deletes (unlink) a temporary filename in 'PATH_site."typo3temp/"' given as input.
     * The function will check that the file exists, is in PATH_site."typo3temp/" and does not contain back-spaces ("../") so it should be pretty safe.
     * Use this after upload_to_tempfile() or tempnam() from this class!
     *
     * @param string $uploadedTempFileName Filepath for a file in PATH_site."typo3temp/". Must be absolute.
     * @return bool Returns TRUE if the file was unlink()'ed
     * @see upload_to_tempfile(), tempnam()
     */
    public static function unlink_tempfile($uploadedTempFileName)
    {
        if ($uploadedTempFileName) {
            $uploadedTempFileName = self::fixWindowsFilePath($uploadedTempFileName);
            if (
                self::validPathStr($uploadedTempFileName)
                && self::isFirstPartOfStr($uploadedTempFileName, PATH_site . 'typo3temp/')
                && @is_file($uploadedTempFileName)
            ) {
                if (unlink($uploadedTempFileName)) {
                    return true;
                }
            }
        }
    }

    /**
     * Create temporary filename (Create file with unique file name)
     * This function should be used for getting temporary file names - will make your applications safe for open_basedir = on
     * REMEMBER to delete the temporary files after use! This is done by \TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile()
     *
     * @param string $filePrefix Prefix for temporary file
     * @param string $fileSuffix Suffix for temporary file, for example a special file extension
     * @return string result from PHP function tempnam() with PATH_site . 'typo3temp/' set for temp path.
     * @see unlink_tempfile(), upload_to_tempfile()
     */
    public static function tempnam($filePrefix, $fileSuffix = '')
    {
        $temporaryPath = PATH_site . 'typo3temp/';
        if ($fileSuffix === '') {
            $tempFileName = $temporaryPath . basename(tempnam($temporaryPath, $filePrefix));
        } else {
            do {
                $tempFileName = $temporaryPath . $filePrefix . mt_rand(1, PHP_INT_MAX) . $fileSuffix;
            } while (file_exists($tempFileName));
            touch($tempFileName);
            clearstatcache(null, $tempFileName);
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
     */
    public static function stdAuthCode($uid_or_record, $fields = '', $codeLength = 8)
    {
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
     */
    public static function hideIfNotTranslated($l18n_cfg_fieldValue)
    {
        return $GLOBALS['TYPO3_CONF_VARS']['FE']['hidePagesIfNotTranslatedByDefault'] xor ($l18n_cfg_fieldValue & 2);
    }

    /**
     * Returns true if the "l18n_cfg" field value is not set to hide
     * pages in the default language
     *
     * @param int $localizationConfiguration
     * @return bool
     */
    public static function hideIfDefaultLanguage($localizationConfiguration)
    {
        return (bool)($localizationConfiguration & 1);
    }

    /**
     * Includes a locallang file and returns the $LOCAL_LANG array found inside.
     *
     * @param string $fileRef Input is a file-reference (see \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName). That file is expected to be a 'locallang.xlf' file conataining a valid XML TYPO3 language structure.
     * @param string $langKey Language key
     * @param string $charset Character set (option); if not set, determined by the language key
     * @param int $errorMode Error mode (when file could not be found): 0 - syslog entry, 1 - do nothing, 2 - throw an exception
     * @return array Value of $LOCAL_LANG found in the included file. If that array is found it will returned.
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public static function readLLfile($fileRef, $langKey, $charset = '', $errorMode = 0)
    {
        self::logDeprecatedFunction();
        /** @var $languageFactory \TYPO3\CMS\Core\Localization\LocalizationFactory */
        $languageFactory = self::makeInstance(\TYPO3\CMS\Core\Localization\LocalizationFactory::class);
        return $languageFactory->getParsedData($fileRef, $langKey, $charset, $errorMode);
    }

    /**
     * Returns auto-filename for locallang localizations
     *
     * @param string $fileRef Absolute file reference to locallang file
     * @param string $language Language key
     * @param bool $sameLocation If TRUE, then locallang localization file name will be returned with same directory as $fileRef
     * @return string Returns the filename reference for the language unless error occurred in which case it will be NULL
     */
    public static function llXmlAutoFileName($fileRef, $language, $sameLocation = false)
    {
        // If $fileRef is already prefixed with "[language key]" then we should return it as is
        $fileName = basename($fileRef);
        if (self::isFirstPartOfStr($fileName, $language . '.')) {
            return $fileRef;
        }

        if ($sameLocation) {
            return str_replace($fileName, $language . '.' . $fileName, $fileRef);
        }

        // Analyse file reference:
        // Is system:
        if (self::isFirstPartOfStr($fileRef, PATH_typo3 . 'sysext/')) {
            $validatedPrefix = PATH_typo3 . 'sysext/';
        } elseif (self::isFirstPartOfStr($fileRef, PATH_typo3 . 'ext/')) {
            // Is global:
            $validatedPrefix = PATH_typo3 . 'ext/';
        } elseif (self::isFirstPartOfStr($fileRef, PATH_typo3conf . 'ext/')) {
            // Is local:
            $validatedPrefix = PATH_typo3conf . 'ext/';
        } else {
            $validatedPrefix = '';
        }
        if ($validatedPrefix) {
            // Divide file reference into extension key, directory (if any) and base name:
            list($file_extKey, $file_extPath) = explode('/', substr($fileRef, strlen($validatedPrefix)), 2);
            $temp = self::revExplode('/', $file_extPath, 2);
            if (count($temp) === 1) {
                array_unshift($temp, '');
            }
            // Add empty first-entry if not there.
            list($file_extPath, $file_fileName) = $temp;
            // The filename is prefixed with "[language key]." because it prevents the llxmltranslate tool from detecting it.
            $location = 'typo3conf/l10n/' . $language . '/' . $file_extKey . '/' . ($file_extPath ? $file_extPath . '/' : '');
            return $location . $language . '.' . $file_fileName;
        }
        return null;
    }

    /**
     * Looks for a sheet-definition in the input data structure array. If found it will return the data structure for the sheet given as $sheet (if found).
     * If the sheet definition is in an external file that file is parsed and the data structure inside of that is returned.
     *
     * @param array $dataStructArray Input data structure, possibly with a sheet-definition and references to external data source files.
     * @param string $sheet The sheet to return, preferably.
     * @return array An array with two num. keys: key0: The data structure is returned in this key (array) UNLESS an error occurred in which case an error string is returned (string). key1: The used sheet key value!
     */
    public static function resolveSheetDefInDS($dataStructArray, $sheet = 'sDEF')
    {
        if (!is_array($dataStructArray)) {
            return 'Data structure must be an array';
        }
        if (is_array($dataStructArray['sheets'])) {
            $singleSheet = false;
            if (!isset($dataStructArray['sheets'][$sheet])) {
                $sheet = 'sDEF';
            }
            $dataStruct = $dataStructArray['sheets'][$sheet];
            // If not an array, but still set, then regard it as a relative reference to a file:
            if ($dataStruct && !is_array($dataStruct)) {
                $file = self::getFileAbsFileName($dataStruct);
                if ($file && @is_file($file)) {
                    $dataStruct = self::xml2array(self::getUrl($file));
                }
            }
        } else {
            $singleSheet = true;
            $dataStruct = $dataStructArray;
            if (isset($dataStruct['meta'])) {
                unset($dataStruct['meta']);
            }
            // Meta data should not appear there.
            // Default sheet
            $sheet = 'sDEF';
        }
        return [$dataStruct, $sheet, $singleSheet];
    }

    /**
     * Resolves ALL sheet definitions in dataStructArray
     * If no sheet is found, then the default "sDEF" will be created with the dataStructure inside.
     *
     * @param array $dataStructArray Input data structure, possibly with a sheet-definition and references to external data source files.
     * @return array Output data structure with all sheets resolved as arrays.
     */
    public static function resolveAllSheetsInDS(array $dataStructArray)
    {
        if (is_array($dataStructArray['sheets'])) {
            $out = ['sheets' => []];
            foreach ($dataStructArray['sheets'] as $sheetId => $sDat) {
                list($ds, $aS) = self::resolveSheetDefInDS($dataStructArray, $sheetId);
                if ($sheetId == $aS) {
                    $out['sheets'][$aS] = $ds;
                }
            }
        } else {
            list($ds) = self::resolveSheetDefInDS($dataStructArray);
            $out = ['sheets' => ['sDEF' => $ds]];
        }
        return $out;
    }

    /**
     * Calls a user-defined function/method in class
     * Such a function/method should look like this: "function proc(&$params, &$ref) {...}"
     *
     * @param string $funcName Function/Method reference or Closure, '[file-reference":"]["&"]class/function["->"method-name]'. You can prefix this reference with "[file-reference]:" and \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName() will then be used to resolve the filename and subsequently include it by "require_once()" which means you don't have to worry about including the class file either! Example: "EXT:realurl/class.tx_realurl.php:&tx_realurl->encodeSpURL". Finally; you can prefix the class name with "&" if you want to reuse a former instance of the same object call ("singleton").
     * @param mixed $params Parameters to be pass along (typically an array) (REFERENCE!)
     * @param mixed $ref Reference to be passed along (typically "$this" - being a reference to the calling object) (REFERENCE!)
     * @param string $checkPrefix Not used anymore since 6.0
     * @param int $errorMode Error mode (when class/function could not be found): 0 - call debug(), 1 - do nothing, 2 - raise an exception (allows to call a user function that may return FALSE)
     * @return mixed Content from method/function call or FALSE if the class/method/function was not found
     * @see getUserObj()
     */
    public static function callUserFunction($funcName, &$params, &$ref, $checkPrefix = '', $errorMode = 0)
    {
        $content = false;
        // Check if we're using a closure and invoke it directly.
        if (is_object($funcName) && is_a($funcName, 'Closure')) {
            return call_user_func_array($funcName, [&$params, &$ref]);
        }
        $funcName = trim($funcName);
        // Check persistent object and if found, call directly and exit.
        if (isset($GLOBALS['T3_VAR']['callUserFunction'][$funcName]) && is_array($GLOBALS['T3_VAR']['callUserFunction'][$funcName])) {
            return call_user_func_array([
                &$GLOBALS['T3_VAR']['callUserFunction'][$funcName]['obj'],
                $GLOBALS['T3_VAR']['callUserFunction'][$funcName]['method']
            ], [&$params, &$ref]);
        }
        // Check file-reference prefix; if found, require_once() the file (should be library of code)
        if (strpos($funcName, ':') !== false) {
            list($file, $funcRef) = self::revExplode(':', $funcName, 2);
            $requireFile = self::getFileAbsFileName($file);
            if ($requireFile) {
                self::requireOnce($requireFile);
            }
        } else {
            $funcRef = $funcName;
        }
        // Check for persistent object token, "&"
        if ($funcRef[0] === '&') {
            $funcRef = substr($funcRef, 1);
            $storePersistentObject = true;
        } else {
            $storePersistentObject = false;
        }
        // Call function or method:
        $parts = explode('->', $funcRef);
        if (count($parts) === 2) {
            // Class
            // Check if class/method exists:
            if (class_exists($parts[0])) {
                // Get/Create object of class:
                if ($storePersistentObject) {
                    // Get reference to current instance of class:
                    if (!is_object($GLOBALS['T3_VAR']['callUserFunction_classPool'][$parts[0]])) {
                        $GLOBALS['T3_VAR']['callUserFunction_classPool'][$parts[0]] = self::makeInstance($parts[0]);
                    }
                    $classObj = $GLOBALS['T3_VAR']['callUserFunction_classPool'][$parts[0]];
                } else {
                    // Create new object:
                    $classObj = self::makeInstance($parts[0]);
                }
                if (method_exists($classObj, $parts[1])) {
                    // If persistent object should be created, set reference:
                    if ($storePersistentObject) {
                        $GLOBALS['T3_VAR']['callUserFunction'][$funcName] = [
                            'method' => $parts[1],
                            'obj' => &$classObj
                        ];
                    }
                    // Call method:
                    $content = call_user_func_array([&$classObj, $parts[1]], [&$params, &$ref]);
                } else {
                    $errorMsg = 'No method name \'' . $parts[1] . '\' in class ' . $parts[0];
                    if ($errorMode == 2) {
                        throw new \InvalidArgumentException($errorMsg, 1294585865);
                    } elseif (!$errorMode) {
                        debug($errorMsg, \TYPO3\CMS\Core\Utility\GeneralUtility::class . '::callUserFunction');
                    }
                }
            } else {
                $errorMsg = 'No class named ' . $parts[0];
                if ($errorMode == 2) {
                    throw new \InvalidArgumentException($errorMsg, 1294585866);
                } elseif (!$errorMode) {
                    debug($errorMsg, \TYPO3\CMS\Core\Utility\GeneralUtility::class . '::callUserFunction');
                }
            }
        } else {
            // Function
            if (function_exists($funcRef)) {
                $content = call_user_func_array($funcRef, [&$params, &$ref]);
            } else {
                $errorMsg = 'No function named: ' . $funcRef;
                if ($errorMode == 2) {
                    throw new \InvalidArgumentException($errorMsg, 1294585867);
                } elseif (!$errorMode) {
                    debug($errorMsg, \TYPO3\CMS\Core\Utility\GeneralUtility::class . '::callUserFunction');
                }
            }
        }
        return $content;
    }

    /**
     * Creates and returns reference to a user defined object.
     * This function can return an object reference if you like.
     * Just prefix the function call with "&": "$objRef = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj('EXT:myext/class.tx_myext_myclass.php:&tx_myext_myclass');".
     * This will work ONLY if you prefix the class name with "&" as well. See description of function arguments.
     * Please note that the reference functionality is deprecated as of TYPO3 CMS 7, and will be removed with TYPO3 CMS 8, let the class use the SingletonInterface of TYPO3 instead.
     *
     * @todo Deprecate the whole method in several steps:
     *      1. Deprecated singleton pattern, (will be removed in TYPO3 CMS 8)
     *      2. Deprecate file prefix/ require file,
     *      3. Deprecate usage without valid class name.
     *      4. The last step should be to deprecate the method itself.
     *
     * @param string $classRef Class reference, '[file-reference":"]["&"]class-name'. You can prefix the class name with "[file-reference]:" and \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName() will then be used to resolve the filename and subsequently include it by "require_once()" which means you don't have to worry about including the class file either! Example: "EXT:realurl/class.tx_realurl.php:&tx_realurl". Finally; for the class name you can prefix it with "&" and you will reuse the previous instance of the object identified by the full reference string (meaning; if you ask for the same $classRef later in another place in the code you will get a reference to the first created one!).
     * @return object The instance of the class asked for. Instance is created with \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance
     * @see callUserFunction()
     */
    public static function getUserObj($classRef)
    {
        // Check persistent object and if found, call directly and exit.
        if (is_object($GLOBALS['T3_VAR']['getUserObj'][$classRef])) {
            return $GLOBALS['T3_VAR']['getUserObj'][$classRef];
        } else {
            // Check file-reference prefix; if found, require_once() the file (should be library of code)
            if (strpos($classRef, ':') !== false) {
                list($file, $class) = self::revExplode(':', $classRef, 2);
                $requireFile = self::getFileAbsFileName($file);
                if ($requireFile) {
                    self::requireOnce($requireFile);
                }
            } else {
                $class = $classRef;
            }
            // Check for persistent object token, "&"
            if ($class[0] === '&') {
                self::deprecationLog(
                    'The persistent functionality of getUserObj(), prepending the class name with & is deprecated since'
                    . ' TYPO3 CMS 7 and will be removed in TYPO3 CMS 8. To allow this functionality, implement '
                    . ' the \\TYPO3\\CMS\\Core\\SingletonInterface in the class "' . $classRef . '" instead.'
                );
                $class = substr($class, 1);
                $storePersistentObject = true;
            } else {
                $storePersistentObject = false;
            }
            // Check if class exists:
            if (class_exists($class)) {
                $classObj = self::makeInstance($class);
                // If persistent object should be created, set reference:
                if ($storePersistentObject) {
                    $GLOBALS['T3_VAR']['getUserObj'][$classRef] = $classObj;
                }
                return $classObj;
            }
        }
    }

    /**
     * Creates an instance of a class taking into account the class-extensions
     * API of TYPO3. USE THIS method instead of the PHP "new" keyword.
     * Eg. "$obj = new myclass;" should be "$obj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("myclass")" instead!
     *
     * You can also pass arguments for a constructor:
     * \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\myClass::class, $arg1, $arg2, ..., $argN)
     *
     * You may want to use \TYPO3\CMS\Extbase\Object\ObjectManager::get() if you
     * want TYPO3 to take care about injecting dependencies of the class to be
     * created. Therefore create an instance of ObjectManager via
     * GeneralUtility::makeInstance() first and call its get() method to get
     * the instance of a specific class.
     *
     * @param string $className name of the class to instantiate, must not be empty and not start with a backslash
     *
     * @return object the created instance
     *
     * @throws \InvalidArgumentException if $className is empty or starts with a backslash
     */
    public static function makeInstance($className)
    {
        if (!is_string($className) || empty($className)) {
            throw new \InvalidArgumentException('$className must be a non empty string.', 1288965219);
        }
        // Never instantiate with a beginning backslash, otherwise things like singletons won't work.
        if ($className[0] === '\\') {
            throw new \InvalidArgumentException(
                '$className "' . $className . '" must not start with a backslash.', 1420281366
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
        // Create new instance and call constructor with parameters
        $instance = static::instantiateClass($finalClassName, func_get_args());
        // Register new singleton instance
        if ($instance instanceof SingletonInterface) {
            self::$singletonInstances[$finalClassName] = $instance;
        }
        return $instance;
    }

    /**
     * Speed optimized alternative to ReflectionClass::newInstanceArgs()
     *
     * @param string $className Name of the class to instantiate
     * @param array $arguments Arguments passed to self::makeInstance() thus the first one with index 0 holds the requested class name
     * @return mixed
     */
    protected static function instantiateClass($className, $arguments)
    {
        switch (count($arguments)) {
            case 1:
                $instance = new $className();
                break;
            case 2:
                $instance = new $className($arguments[1]);
                break;
            case 3:
                $instance = new $className($arguments[1], $arguments[2]);
                break;
            case 4:
                $instance = new $className($arguments[1], $arguments[2], $arguments[3]);
                break;
            case 5:
                $instance = new $className($arguments[1], $arguments[2], $arguments[3], $arguments[4]);
                break;
            case 6:
                $instance = new $className($arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]);
                break;
            case 7:
                $instance = new $className($arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6]);
                break;
            case 8:
                $instance = new $className($arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6], $arguments[7]);
                break;
            case 9:
                $instance = new $className($arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6], $arguments[7], $arguments[8]);
                break;
            default:
                // The default case for classes with constructors that have more than 8 arguments.
                // This will fail when one of the arguments shall be passed by reference.
                // In case we really need to support this edge case, we can implement the solution from here: https://review.typo3.org/26344
                $class = new \ReflectionClass($className);
                array_shift($arguments);
                $instance = $class->newInstanceArgs($arguments);
        }
        return $instance;
    }

    /**
     * Returns the class name for a new instance, taking into account
     * registered implementations for this class
     *
     * @param string $className Base class name to evaluate
     * @return string Final class name to instantiate with "new [classname]
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
        // If we are early in the bootstrap, the configuration might not yet be present
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'])) {
            return false;
        }

        return isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$className])
                && is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$className])
                && !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$className]['className']);
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
     * @return void
     * @internal
     */
    public static function setSingletonInstance($className, SingletonInterface $instance)
    {
        self::checkInstanceClassName($className, $instance);
        self::$singletonInstances[$className] = $instance;
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
     * @return void
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
     * @param array $newSingletonInstances $className => $object
     * @return void
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
     * @return array $className => $object
     */
    public static function getSingletonInstances()
    {
        return static::$singletonInstances;
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
     * @return void
     */
    public static function addInstance($className, $instance)
    {
        self::checkInstanceClassName($className, $instance);
        if ($instance instanceof SingletonInterface) {
            throw new \InvalidArgumentException('$instance must not be an instance of TYPO3\\CMS\\Core\\SingletonInterface. ' . 'For setting singletons, please use setSingletonInstance.', 1288969325);
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
     * @return void
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
     * @return void
     */
    public static function purgeInstances()
    {
        self::$singletonInstances = [];
        self::$nonSingletonInstances = [];
    }

    /**
     * Flush internal runtime caches
     *
     * Used in unit tests only.
     *
     * @return void
     * @internal
     */
    public static function flushInternalRuntimeCaches()
    {
        self::$indpEnvCache = [];
        self::$idnaStringCache = [];
    }

    /**
     * Find the best service and check if it works.
     * Returns object of the service class.
     *
     * @param string $serviceType Type of service (service key).
     * @param string $serviceSubType Sub type like file extensions or similar. Defined by the service.
     * @param mixed $excludeServiceKeys List of service keys which should be excluded in the search for a service. Array or comma list.
     * @return object|string[] The service object or an array with error infos.
     */
    public static function makeInstanceService($serviceType, $serviceSubType = '', $excludeServiceKeys = [])
    {
        $error = false;
        if (!is_array($excludeServiceKeys)) {
            $excludeServiceKeys = self::trimExplode(',', $excludeServiceKeys, true);
        }
        $requestInfo = [
            'requestedServiceType' => $serviceType,
            'requestedServiceSubType' => $serviceSubType,
            'requestedExcludeServiceKeys' => $excludeServiceKeys
        ];
        while ($info = ExtensionManagementUtility::findService($serviceType, $serviceSubType, $excludeServiceKeys)) {
            // provide information about requested service to service object
            $info = array_merge($info, $requestInfo);
            // Check persistent object and if found, call directly and exit.
            if (is_object($GLOBALS['T3_VAR']['makeInstanceService'][$info['className']])) {
                // update request info in persistent object
                $GLOBALS['T3_VAR']['makeInstanceService'][$info['className']]->info = $info;
                // reset service and return object
                $GLOBALS['T3_VAR']['makeInstanceService'][$info['className']]->reset();
                return $GLOBALS['T3_VAR']['makeInstanceService'][$info['className']];
            } else {
                $obj = self::makeInstance($info['className']);
                if (is_object($obj)) {
                    if (!@is_callable([$obj, 'init'])) {
                        // use silent logging??? I don't think so.
                        die('Broken service:' . DebugUtility::viewArray($info));
                    }
                    $obj->info = $info;
                    // service available?
                    if ($obj->init()) {
                        // create persistent object
                        $GLOBALS['T3_VAR']['makeInstanceService'][$info['className']] = $obj;
                        return $obj;
                    }
                    $error = $obj->getLastErrorArray();
                    unset($obj);
                }
            }
            // deactivate the service
            ExtensionManagementUtility::deactivateService($info['serviceType'], $info['serviceKey']);
        }
        return $error;
    }

    /**
     * Require a class for TYPO3
     * Useful to require classes from inside other classes (not global scope). A limited set of global variables are available (see function)
     *
     * @param string $requireFile: Path of the file to be included
     * @return void
     */
    public static function requireOnce($requireFile)
    {
        // Needed for require_once
        global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
        require_once $requireFile;
    }

    /**
     * Requires a class for TYPO3
     * Useful to require classes from inside other classes (not global scope).
     * A limited set of global variables are available (see function)
     *
     * @param string $requireFile: Path of the file to be included
     * @return void
     */
    public static function requireFile($requireFile)
    {
        // Needed for require
        global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
        require $requireFile;
    }

    /**
     * Implementation of quoted-printable encode.
     * See RFC 1521, section 5.1 Quoted-Printable Content-Transfer-Encoding
     *
     * @param string $string Content to encode
     * @param int $maxlen Length of the lines, default is 76
     * @return string The QP encoded string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. Use mailer API instead
     */
    public static function quoted_printable($string, $maxlen = 76)
    {
        static::logDeprecatedFunction();
        // Make sure the string contains only Unix line breaks
        // Replace Windows breaks (\r\n)
        $string = str_replace(CRLF, LF, $string);
        // Replace Mac breaks (\r)
        $string = str_replace(CR, LF, $string);
        // Default line break for Unix systems.
        $linebreak = LF;
        if (TYPO3_OS == 'WIN') {
            // Line break for Windows. This is needed because PHP on Windows systems send mails via SMTP instead of using sendmail, and thus the line break needs to be \r\n.
            $linebreak = CRLF;
        }
        $newString = '';
        // Split lines
        $theLines = explode(LF, $string);
        foreach ($theLines as $val) {
            $newVal = '';
            $theValLen = strlen($val);
            $len = 0;
            // Walk through each character of this line
            for ($index = 0; $index < $theValLen; $index++) {
                $char = substr($val, $index, 1);
                $ordVal = ord($char);
                if ($len > $maxlen - 4 || $len > $maxlen - 14 && $ordVal == 32) {
                    // Add a line break
                    $newVal .= '=' . $linebreak;
                    // Reset the length counter
                    $len = 0;
                }
                if ($ordVal >= 33 && $ordVal <= 60 || $ordVal >= 62 && $ordVal <= 126 || $ordVal == 9 || $ordVal == 32) {
                    // This character is ok, add it to the message
                    $newVal .= $char;
                    $len++;
                } else {
                    // Special character, needs to be encoded
                    $newVal .= sprintf('=%02X', $ordVal);
                    $len += 3;
                }
            }
            // Replaces a possible SPACE-character at the end of a line
            $newVal = preg_replace('/' . chr(32) . '$/', '=20', $newVal);
            // Replaces a possible TAB-character at the end of a line
            $newVal = preg_replace('/' . TAB . '$/', '=09', $newVal);
            $newString .= $newVal . $linebreak;
        }
        // Remove last newline
        return preg_replace('/' . $linebreak . '$/', '', $newString);
    }

    /**
     * Encode header lines
     * Email headers must be ASCII, therefore they will be encoded to quoted_printable (default) or base64.
     *
     * @param string $line Content to encode
     * @param string $enc Encoding type: "base64" or "quoted-printable". Default value is "quoted-printable".
     * @param string $charset Charset used for encoding
     * @return string The encoded string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. Use mailer API instead
     */
    public static function encodeHeader($line, $enc = 'quoted-printable', $charset = 'utf-8')
    {
        static::logDeprecatedFunction();
        // Avoid problems if "###" is found in $line (would conflict with the placeholder which is used below)
        if (strpos($line, '###') !== false) {
            return $line;
        }
        // Check if any non-ASCII characters are found - otherwise encoding is not needed
        if (!preg_match(('/[^' . chr(32) . '-' . chr(127) . ']/'), $line)) {
            return $line;
        }
        // Wrap email addresses in a special marker
        $line = preg_replace('/([^ ]+@[^ ]+)/', '###$1###', $line);
        $matches = preg_split('/(.?###.+###.?|\\(|\\))/', $line, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($matches as $part) {
            $oldPart = $part;
            $partWasQuoted = $part[0] == '"';
            $part = trim($part, '"');
            switch ((string)$enc) {
                case 'base64':
                    $part = '=?' . $charset . '?B?' . base64_encode($part) . '?=';
                    break;
                case 'quoted-printable':

                default:
                    $qpValue = self::quoted_printable($part, 1000);
                    if ($part != $qpValue) {
                        // Encoded words in the header should not contain non-encoded:
                        // * spaces. "_" is a shortcut for "=20". See RFC 2047 for details.
                        // * question mark. See RFC 1342 (http://tools.ietf.org/html/rfc1342)
                        $search = [' ', '?'];
                        $replace = ['_', '=3F'];
                        $qpValue = str_replace($search, $replace, $qpValue);
                        $part = '=?' . $charset . '?Q?' . $qpValue . '?=';
                    }
            }
            if ($partWasQuoted) {
                $part = '"' . $part . '"';
            }
            $line = str_replace($oldPart, $part, $line);
        }
        // Remove the wrappers
        $line = preg_replace('/###(.+?)###/', '$1', $line);
        return $line;
    }

    /**
     * Takes a clear-text message body for a plain text email, finds all 'http://' links and if they are longer than 76 chars they are converted to a shorter URL with a hash parameter. The real parameter is stored in the database and the hash-parameter/URL will be redirected to the real parameter when the link is clicked.
     * This function is about preserving long links in messages.
     *
     * @param string $message Message content
     * @param string $urlmode URL mode; "76" or "all
     * @param string $index_script_url URL of index script (see makeRedirectUrl())
     * @return string Processed message content
     * @see makeRedirectUrl()
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. Use mailer API instead
     */
    public static function substUrlsInPlainText($message, $urlmode = '76', $index_script_url = '')
    {
        static::logDeprecatedFunction();
        switch ((string)$urlmode) {
            case '':
                $lengthLimit = false;
                break;
            case 'all':
                $lengthLimit = 0;
                break;
            case '76':

            default:
                $lengthLimit = (int)$urlmode;
        }
        if ($lengthLimit === false) {
            // No processing
            $messageSubstituted = $message;
        } else {
            $messageSubstituted = preg_replace_callback(
                '/(http|https):\\/\\/.+(?=[\\]\\.\\?]*([\\! \'"()<>]+|$))/iU',
                function (array $matches) use ($lengthLimit, $index_script_url) {
                    return GeneralUtility::makeRedirectUrl($matches[0], $lengthLimit, $index_script_url);
                },
                $message
            );
        }
        return $messageSubstituted;
    }

    /**
     * Create a shortened "redirect" URL with specified length from an incoming URL
     *
     * @param string $inUrl Input URL
     * @param int $l URL string length limit
     * @param string $index_script_url URL of "index script" - the prefix of the "?RDCT=..." parameter. If not supplied it will default to \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR').'index.php'
     * @return string Processed URL
     */
    public static function makeRedirectUrl($inUrl, $l = 0, $index_script_url = '')
    {
        if (strlen($inUrl) > $l) {
            $md5 = substr(md5($inUrl), 0, 20);
            $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cache_md5params', 'md5hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($md5, 'cache_md5params'));
            if (!$count) {
                $insertFields = [
                    'md5hash' => $md5,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'type' => 2,
                    'params' => $inUrl
                ];
                $GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_md5params', $insertFields);
            }
            $inUrl = ($index_script_url ? $index_script_url : self::getIndpEnv('TYPO3_REQUEST_DIR') . 'index.php') . '?RDCT=' . $md5;
        }
        return $inUrl;
    }

    /**
     * Function to compensate for DPI resolution.
     *
     * @param float $fontSize font size for freetype function call
     *
     * @return float compensated font size based on 96 dpi
     */
    public static function freetypeDpiComp($fontSize)
    {
        // FreeType 2 always has 96 dpi.
        $dpi = 96.0;
        return $fontSize / $dpi * 72;
    }

    /**
     * Initialize the system log.
     *
     * @return void
     * @see sysLog()
     */
    public static function initSysLog()
    {
        // For CLI logging name is <fqdn-hostname>:<TYPO3-path>
        // Note that TYPO3_REQUESTTYPE is not used here as it may not yet be defined
        if (defined('TYPO3_cliMode') && TYPO3_cliMode) {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLogHost'] = self::getHostname(($requestHost = false)) . ':' . PATH_site;
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLogHost'] = self::getIndpEnv('TYPO3_SITE_URL');
        }
        // Init custom logging
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLog'])) {
            $params = ['initLog' => true];
            $fakeThis = false;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLog'] as $hookMethod) {
                self::callUserFunction($hookMethod, $params, $fakeThis);
            }
        }
        // Init TYPO3 logging
        foreach (explode(';', $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLog'], 2) as $log) {
            list($type, $destination) = explode(',', $log, 3);
            if ($type == 'syslog') {
                if (TYPO3_OS == 'WIN') {
                    $facility = LOG_USER;
                } else {
                    $facility = constant('LOG_' . strtoupper($destination));
                }
                openlog($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLogHost'], LOG_ODELAY, $facility);
            }
        }
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLogLevel'] = MathUtility::forceIntegerInRange($GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLogLevel'], 0, 4);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLogInit'] = true;
    }

    /**
     * Logs message to the system log.
     * This should be implemented around the source code, including the Core and both frontend and backend, logging serious errors.
     * If you want to implement the sysLog in your applications, simply add lines like:
     * \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('[write message in English here]', 'extension_key', 'severity');
     *
     * @param string $msg Message (in English).
     * @param string $extKey Extension key (from which extension you are calling the log) or "Core
     * @param int $severity \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_* constant
     * @return void
     */
    public static function sysLog($msg, $extKey, $severity = 0)
    {
        $severity = MathUtility::forceIntegerInRange($severity, 0, 4);
        // Is message worth logging?
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLogLevel'] > $severity) {
            return;
        }
        // Initialize logging
        if (!$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLogInit']) {
            self::initSysLog();
        }
        // Do custom logging
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLog']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLog'])) {
            $params = ['msg' => $msg, 'extKey' => $extKey, 'backTrace' => debug_backtrace(), 'severity' => $severity];
            $fakeThis = false;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLog'] as $hookMethod) {
                self::callUserFunction($hookMethod, $params, $fakeThis);
            }
        }
        // TYPO3 logging enabled?
        if (!$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLog']) {
            return;
        }
        $dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
        $timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
        // Use all configured logging options
        foreach (explode(';', $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLog'], 2) as $log) {
            list($type, $destination, $level) = explode(',', $log, 4);
            // Is message worth logging for this log type?
            if ((int)$level > $severity) {
                continue;
            }
            $msgLine = ' - ' . $extKey . ': ' . $msg;
            // Write message to a file
            if ($type == 'file') {
                $file = fopen($destination, 'a');
                if ($file) {
                    fwrite($file, date(($dateFormat . ' ' . $timeFormat)) . $msgLine . LF);
                    fclose($file);
                    self::fixPermissions($destination);
                }
            } elseif ($type == 'mail') {
                list($to, $from) = explode('/', $destination);
                if (!self::validEmail($from)) {
                    $from = MailUtility::getSystemFrom();
                }
                /** @var $mail \TYPO3\CMS\Core\Mail\MailMessage */
                $mail = self::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
                $mail->setTo($to)->setFrom($from)->setSubject('Warning - error in TYPO3 installation')->setBody('Host: ' . $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLogHost'] . LF . 'Extension: ' . $extKey . LF . 'Severity: ' . $severity . LF . LF . $msg);
                $mail->send();
            } elseif ($type == 'error_log') {
                error_log($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLogHost'] . $msgLine, 0);
            } elseif ($type == 'syslog') {
                $priority = [LOG_INFO, LOG_NOTICE, LOG_WARNING, LOG_ERR, LOG_CRIT];
                syslog($priority[(int)$severity], $msgLine);
            }
        }
    }

    /**
     * Logs message to the development log.
     * This should be implemented around the source code, both frontend and backend, logging everything from the flow through an application, messages, results from comparisons to fatal errors.
     * The result is meant to make sense to developers during development or debugging of a site.
     * The idea is that this function is only a wrapper for external extensions which can set a hook which will be allowed to handle the logging of the information to any format they might wish and with any kind of filter they would like.
     * If you want to implement the devLog in your applications, simply add lines like:
     * if (TYPO3_DLOG)	\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[write message in english here]', 'extension key');
     *
     * @param string $msg Message (in english).
     * @param string $extKey Extension key (from which extension you are calling the log)
     * @param int $severity Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
     * @param mixed $dataVar Additional data you want to pass to the logger.
     * @return void
     */
    public static function devLog($msg, $extKey, $severity = 0, $dataVar = false)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog'])) {
            $params = ['msg' => $msg, 'extKey' => $extKey, 'severity' => $severity, 'dataVar' => $dataVar];
            $fakeThis = false;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog'] as $hookMethod) {
                self::callUserFunction($hookMethod, $params, $fakeThis);
            }
        }
    }

    /**
     * Writes a message to the deprecation log.
     *
     * @param string $msg Message (in English).
     * @return void
     */
    public static function deprecationLog($msg)
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog']) {
            return;
        }
        // Legacy values (no strict comparison, $log can be boolean, string or int)
        $log = $GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'];
        if ($log === true || $log == '1') {
            $log = ['file'];
        } else {
            $log = self::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'], true);
        }
        $date = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] . ': ');
        if (in_array('file', $log) !== false) {
            // Write a longer message to the deprecation log
            $destination = static::getDeprecationLogFileName();
            $file = @fopen($destination, 'a');
            if ($file) {
                @fwrite($file, ($date . $msg . LF));
                @fclose($file);
                self::fixPermissions($destination);
            }
        }
        if (in_array('devlog', $log) !== false) {
            // Copy message also to the developer log
            self::devLog($msg, 'Core', self::SYSLOG_SEVERITY_WARNING);
        }
        // Do not use console in login screen
        if (in_array('console', $log) !== false && isset($GLOBALS['BE_USER']->user['uid'])) {
            DebugUtility::debug($msg, $date, 'Deprecation Log');
        }
    }

    /**
     * Gets the absolute path to the deprecation log file.
     *
     * @return string Absolute path to the deprecation log file
     */
    public static function getDeprecationLogFileName()
    {
        return PATH_typo3conf . 'deprecation_' . self::shortMD5((PATH_site . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) . '.log';
    }

    /**
     * Logs a call to a deprecated function.
     * The log message will be taken from the annotation.
     *
     * @return void
     */
    public static function logDeprecatedFunction()
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog']) {
            return;
        }
        $trail = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if ($trail[1]['type']) {
            $function = new \ReflectionMethod($trail[1]['class'], $trail[1]['function']);
        } else {
            $function = new \ReflectionFunction($trail[1]['function']);
        }
        $msg = '';
        if (preg_match('/@deprecated\\s+(.*)/', $function->getDocComment(), $match)) {
            $msg = $match[1];
        }
            // Write a longer message to the deprecation log: <function> <annotion> - <trace> (<source>)
        $logMsg = $trail[1]['class'] . $trail[1]['type'] . $trail[1]['function'];
        $logMsg .= '() - ' . $msg . ' - ' . DebugUtility::debugTrail();
        $logMsg .= ' (' . PathUtility::stripPathSitePrefix($function->getFileName()) . '#' . $function->getStartLine() . ')';
        self::deprecationLog($logMsg);
    }

    /**
     * Converts a one dimensional array to a one line string which can be used for logging or debugging output
     * Example: "loginType: FE; refInfo: Array; HTTP_HOST: www.example.org; REMOTE_ADDR: 192.168.1.5; REMOTE_HOST:; security_level:; showHiddenRecords: 0;"
     *
     * @param array $arr Data array which should be outputted
     * @param mixed $valueList List of keys which should be listed in the output string. Pass a comma list or an array. An empty list outputs the whole array.
     * @param int $valueLength Long string values are shortened to this length. Default: 20
     * @return string Output string with key names and their value as string
     */
    public static function arrayToLogString(array $arr, $valueList = [], $valueLength = 20)
    {
        $str = '';
        if (!is_array($valueList)) {
            $valueList = self::trimExplode(',', $valueList, true);
        }
        $valListCnt = count($valueList);
        foreach ($arr as $key => $value) {
            if (!$valListCnt || in_array($key, $valueList)) {
                $str .= (string)$key . trim(': ' . self::fixed_lgd_cs(str_replace(LF, '|', (string)$value), $valueLength)) . '; ';
            }
        }
        return $str;
    }

    /**
     * Compile the command for running ImageMagick/GraphicsMagick.
     *
     * @param string $command Command to be run: identify, convert or combine/composite
     * @param string $parameters The parameters string
     * @param string $path Override the default path (e.g. used by the install tool)
     * @return string Compiled command that deals with IM6 & GraphicsMagick
     */
    public static function imageMagickCommand($command, $parameters, $path = '')
    {
        return CommandUtility::imageMagickCommand($command, $parameters, $path);
    }

    /**
     * Explode a string (normally a list of filenames) with whitespaces by considering quotes in that string.
     *
     * This is mostly needed by the imageMagickCommand function above.
     *
     * @param string $parameters The whole parameters string
     * @param bool $unQuote If set, the elements of the resulting array are unquoted.
     * @return array Exploded parameters
     */
    public static function unQuoteFilenames($parameters, $unQuote = false)
    {
        $paramsArr = explode(' ', trim($parameters));
        // Whenever a quote character (") is found, $quoteActive is set to the element number inside of $params. A value of -1 means that there are not open quotes at the current position.
        $quoteActive = -1;
        foreach ($paramsArr as $k => $v) {
            if ($quoteActive > -1) {
                $paramsArr[$quoteActive] .= ' ' . $v;
                unset($paramsArr[$k]);
                if (substr($v, -1) === $paramsArr[$quoteActive][0]) {
                    $quoteActive = -1;
                }
            } elseif (!trim($v)) {
                // Remove empty elements
                unset($paramsArr[$k]);
            } elseif (preg_match('/^(["\'])/', $v) && substr($v, -1) !== $v[0]) {
                $quoteActive = $k;
            }
        }
        if ($unQuote) {
            foreach ($paramsArr as $key => &$val) {
                $val = preg_replace('/(?:^"|"$)/', '', $val);
                $val = preg_replace('/(?:^\'|\'$)/', '', $val);
            }
            unset($val);
        }
        // Return reindexed array
        return array_values($paramsArr);
    }

    /**
     * Quotes a string for usage as JS parameter.
     *
     * @param string $value the string to encode, may be empty
     * @return string the encoded value already quoted (with single quotes),
     */
    public static function quoteJSvalue($value)
    {
        return strtr(
            json_encode((string)$value, JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG),
            [
                '"' => '\'',
                '\\\\' => '\\u005C',
                ' ' => '\\u0020',
                '!' => '\\u0021',
                '\\t' => '\\u0009',
                '\\n' => '\\u000A',
                '\\r' => '\\u000D'
            ]
        );
    }

    /**
     * Ends and cleans all output buffers
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in CMS 8, use ob_* functions directly or self::flushOutputBuffers
     */
    public static function cleanOutputBuffers()
    {
        self::logDeprecatedFunction();
        while (ob_end_clean()) {
        }
        header('Content-Encoding: None', true);
    }

    /**
     * Ends and flushes all output buffers
     *
     * @return void
     */
    public static function flushOutputBuffers()
    {
        $obContent = '';
        while ($content = ob_get_clean()) {
            $obContent .= $content;
        }
        // If previously a "Content-Encoding: whatever" has been set, we have to unset it
        if (!headers_sent()) {
            $headersList = headers_list();
            foreach ($headersList as $header) {
                // Split it up at the :
                list($key, $value) = self::trimExplode(':', $header, true);
                // Check if we have a Content-Encoding other than 'None'
                if (strtolower($key) === 'content-encoding' && strtolower($value) !== 'none') {
                    header('Content-Encoding: None');
                    break;
                }
            }
        }
        echo $obContent;
    }

    /**
     * Set the ApplicationContext
     *
     * This function is used by the Bootstrap to hand over the application context. It must not be used anywhere else,
     * because the context shall never be changed on runtime!
     *
     * @param \TYPO3\CMS\Core\Core\ApplicationContext $applicationContext
     * @throws \RuntimeException if applicationContext is overriden
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function presetApplicationContext(ApplicationContext $applicationContext)
    {
        if (is_null(static::$applicationContext)) {
            static::$applicationContext = $applicationContext;
        } else {
            throw new \RuntimeException('Trying to override applicationContext which has already been defined!', 1376084316);
        }
    }

    /**
     * Get the ApplicationContext
     *
     * @return \TYPO3\CMS\Core\Core\ApplicationContext
     */
    public static function getApplicationContext()
    {
        return static::$applicationContext;
    }

    /**
     * Check if the current request is running on a CGI server API
     * @return bool
     */
    public static function isRunningOnCgiServerApi()
    {
        return in_array(PHP_SAPI, self::$supportedCgiServerApis, true);
    }
}
