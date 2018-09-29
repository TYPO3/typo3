<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Http;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides normalized server parameters in HTTP request context.
 * It normalizes reverse proxy scenarios and various other web server specific differences
 * of the native PSR-7 request object parameters (->getServerParams() / $GLOBALS['_SERVER']).
 *
 * An instance of this class is available as PSR-7 ServerRequestInterface attribute:
 * $normalizedParams = $request->getAttribute('normalizedParams')
 *
 * This class substitutes the old GeneralUtility::getIndpEnv() method.
 */
class NormalizedParams
{
    /**
     * Sanitized HTTP_HOST value
     *
     * host[:port]
     *
     * - www.domain.com
     * - www.domain.com:443
     * - 192.168.1.42:80
     *
     * @var string
     */
    protected $httpHost = '';

    /**
     * @var bool True if request has been done via HTTPS
     */
    protected $isHttps = false;

    /**
     * Sanitized HTTP_HOST with protocol
     *
     * scheme://host[:port]
     *
     * - https://www.domain.com
     *
     * @var string
     */
    protected $requestHost = '';

    /**
     * Host / domain part of HTTP_HOST, no port, no protocol
     *
     * - www.domain.com
     * - 192.168.1.42
     *
     * @var string
     */
    protected $requestHostOnly = '';

    /**
     * Port of HTTP_HOST if given
     *
     * @var int
     */
    protected $requestPort = 0;

    /**
     * Entry script path of URI, without domain and without query parameters, with leading /
     *
     * [path_script]
     *
     * - /typo3/index.php
     *
     * @var string
     */
    protected $scriptName = '';

    /**
     * REQUEST URI without domain and scheme, with trailing slash
     *
     * [path][?[query]]
     *
     * - /index.php
     * - /typo3/index.php/arg1/arg2/?arg1,arg2&p1=parameter1&p2[key]=value
     *
     * @var string
     */
    protected $requestUri = '';

    /**
     * REQUEST URI with scheme, host, port, path and query
     *
     * scheme://host[:[port]][path][?[query]]
     *
     * - http://www.domain.com/typo3/index.php?route=foo/bar&id=42
     *
     * @var string
     */
    protected $requestUrl = '';

    /**
     * REQUEST URI with scheme, host, port and path, but *without* query part
     *
     * scheme://host[:[port]][path_script]
     *
     * - http://www.domain.com/typo3/index.php
     *
     * @var string
     */
    protected $requestScript = '';

    /**
     * Full Uri with path, but without script name and query parts
     *
     * scheme://host[:[port]][path_dir]
     *
     * - http://www.domain.com/typo3/
     *
     * @var string
     */
    protected $requestDir = '';

    /**
     * True if request via a reverse proxy is detected
     *
     * @var bool
     */
    protected $isBehindReverseProxy = false;

    /**
     * IPv4 or IPv6 address of remote client with resolved proxy setup
     *
     * @var string
     */
    protected $remoteAddress = '';

    /**
     * Absolute server path to entry script on server filesystem
     *
     * - /var/www/typo3/index.php
     *
     * @var string
     */
    protected $scriptFilename = '';

    /**
     * Absolute server path to web document root without trailing slash
     *
     * - /var/www/typo3
     *
     * @var string
     */
    protected $documentRoot = '';

    /**
     * Website frontend URL.
     * Note this is note "safe" if called from Backend since sys_domain and
     * other factors are not taken into account.
     *
     * scheme://host[:[port]]/[path_dir]
     *
     * - https://www.domain.com/
     * - https://www.domain.com/some/sub/dir/
     *
     * @var string
     */
    protected $siteUrl = '';

    /**
     * Path part to frontend, no domain, no protocol
     *
     * - /
     * - /some/sub/dir/
     *
     * @var string
     */
    protected $sitePath = '';

    /**
     * Path to script, without sub path if TYPO3 is running in sub directory, without trailing slash
     *
     * - typo/index.php?id=42
     * - index.php?id=42
     *
     * @var string
     */
    protected $siteScript = '';

    /**
     * Entry script path of URI, without domain and without query parameters, with leading /
     * This is often not set at all.
     * Will be deprecated later, use $scriptName instead as more reliable solution.
     *
     * [path_script]
     *
     * - /typo3/index.php
     *
     * @var string
     */
    protected $pathInfo = '';

    /**
     * HTTP_REFERER
     * Will be deprecated later, use $request->getServerParams()['HTTP_REFERER'] instead
     *
     * scheme://host[:[port]][path]
     *
     * - https://www.domain.com/typo3/index.php?id=42
     *
     * @var string
     */
    protected $httpReferer = '';

    /**
     * HTTP_USER_AGENT
     * Will be deprecated later, use $request->getServerParams()['HTTP_USER_AGENT'] instead
     *
     * - Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36
     *
     * @var string
     */
    protected $httpUserAgent = '';

    /**
     * HTTP_ACCEPT_ENCODING
     * Will be deprecated later, use $request->getServerParams()['HTTP_ACCEPT_ENCODING'] instead
     *
     * - gzip, deflate
     *
     * @var string
     */
    protected $httpAcceptEncoding = '';

    /**
     * HTTP_ACCEPT_LANGUAGE
     * Will be deprecated later, use $request->getServerParams()['HTTP_ACCEPT_LANGUAGE'] instead
     *
     * - de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7
     *
     * @var string
     */
    protected $httpAcceptLanguage = '';

    /**
     * REMOTE_HOST Resolved host name of REMOTE_ADDR if configured in web server
     * Will be deprecated later, use $request->getServerParams()['REMOTE_HOST'] instead
     *
     * - www.clientDomain.com
     *
     * @var string
     */
    protected $remoteHost = '';

    /**
     * QUERY_STRING
     * Will be deprecated later, use $request->getServerParams()['QUERY_STRING'] instead
     *
     * [query]
     *
     * - id=42&foo=bar
     *
     * @var string
     */
    protected $queryString = '';

    /**
     * Constructor calculates all values by incoming variables.
     *
     * This object is immutable.
     *
     * All determine*() "detail worker methods" in this class retrieve their dependencies
     * to other properties as method arguments, they are static, stateless and have no
     * dependency to $this. This ensures the chain of inter-property dependencies
     * is visible by only looking at the construct() method.
     *
     * @param array $serverParams, usually coming from $_SERVER or $request->getServerParams()
     * @param array $configuration $GLOBALS['TYPO3_CONF_VARS']['SYS']
     * @param string $pathThisScript Absolute server entry script path, usually found within Environment::getCurrentScript()
     * @param string $pathSite Absolute server path to document root, Environment::getPublicPath()
     */
    public function __construct(array $serverParams, array $configuration, string $pathThisScript, string $pathSite)
    {
        $isBehindReverseProxy = $this->isBehindReverseProxy = self::determineIsBehindReverseProxy($serverParams, $configuration);
        $httpHost = $this->httpHost = self::determineHttpHost($serverParams, $configuration, $isBehindReverseProxy);
        $isHttps = $this->isHttps = self::determineHttps($serverParams, $configuration);
        $requestHost = $this->requestHost = ($isHttps ? 'https://' : 'http://') . $httpHost;
        $requestHostOnly = $this->requestHostOnly = self::determineRequestHostOnly($httpHost);
        $this->requestPort = self::determineRequestPort($httpHost, $requestHostOnly);
        $scriptName = $this->scriptName = self::determineScriptName($serverParams, $configuration, $isHttps, $isBehindReverseProxy);
        $requestUri = $this->requestUri = self::determineRequestUri($serverParams, $configuration, $isHttps, $scriptName, $isBehindReverseProxy);
        $requestUrl = $this->requestUrl = $requestHost . $requestUri;
        $this->requestScript = $requestHost . $scriptName;
        $requestDir = $this->requestDir = $requestHost . GeneralUtility::dirname($scriptName) . '/';
        $this->remoteAddress = self::determineRemoteAddress($serverParams, $configuration, $isBehindReverseProxy);
        $scriptFilename = $this->scriptFilename = $pathThisScript;
        $this->documentRoot = self::determineDocumentRoot($scriptName, $scriptFilename);
        $siteUrl = $this->siteUrl = self::determineSiteUrl($requestDir, $pathThisScript, $pathSite . '/');
        $this->sitePath = self::determineSitePath($requestHost, $siteUrl);
        $this->siteScript = self::determineSiteScript($requestUrl, $siteUrl);

        // @deprecated Below variables can be fully deprecated as soon as core does not use them anymore
        $this->pathInfo = $serverParams['PATH_INFO'] ?? '';
        $this->httpReferer = $serverParams['HTTP_REFERER'] ?? '';
        $this->httpUserAgent = $serverParams['HTTP_USER_AGENT'] ?? '';
        $this->httpAcceptEncoding = $serverParams['HTTP_ACCEPT_ENCODING'] ?? '';
        $this->httpAcceptLanguage = $serverParams['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $this->remoteHost = $serverParams['REMOTE_HOST'] ?? '';
        $this->queryString = $serverParams['QUERY_STRING'] ?? '';
    }

    /**
     * @return string Sanitized HTTP_HOST value host[:port]
     */
    public function getHttpHost(): string
    {
        return $this->httpHost;
    }

    /**
     * @return bool True if client request has been done using HTTPS
     */
    public function isHttps(): bool
    {
        return $this->isHttps;
    }

    /**
     * @return string Sanitized HTTP_HOST with protocol scheme://host[:port], eg. https://www.domain.com/
     */
    public function getRequestHost(): string
    {
        return $this->requestHost;
    }

    /**
     * @return string Host / domain /IP only, eg. www.domain.com
     */
    public function getRequestHostOnly(): string
    {
        return $this->requestHostOnly;
    }

    /**
     * @return int Requested port if given, eg. 8080 - often not explicitly given, then 0
     */
    public function getRequestPort(): int
    {
        return $this->requestPort;
    }

    /**
     * @return string Script path part of URI, eg. 'typo3/index.php'
     */
    public function getScriptName(): string
    {
        return $this->scriptName;
    }

    /**
     * @return string Request Uri without domain and protocol, eg. /index.php?id=42
     */
    public function getRequestUri(): string
    {
        return $this->requestUri;
    }

    /**
     * @return string Full REQUEST_URI, eg. http://www.domain.com/typo3/index.php?route=foo/bar&id=42
     */
    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    /**
     * @return string REQUEST URI without query part, eg. http://www.domain.com/typo3/index.php
     */
    public function getRequestScript(): string
    {
        return $this->requestScript;
    }

    /**
     * @return string REQUEST URI without script file name and query parts, eg. http://www.domain.com/typo3/
     */
    public function getRequestDir(): string
    {
        return $this->requestDir;
    }

    /**
     * @return bool True if request comes from a configured reverse proxy
     */
    public function isBehindReverseProxy(): bool
    {
        return $this->isBehindReverseProxy;
    }

    /**
     * @return string Client IP
     */
    public function getRemoteAddress(): string
    {
        return $this->remoteAddress;
    }

    /**
     * @return string Absolute entry script path on server, eg. /var/www/typo3/index.php
     */
    public function getScriptFilename(): string
    {
        return $this->scriptFilename;
    }

    /**
     * @return string Absolute path to web document root, eg. /var/www/typo3
     */
    public function getDocumentRoot(): string
    {
        return $this->documentRoot;
    }

    /**
     * @return string Website frontend url, eg. https://www.domain.com/some/sub/dir/
     */
    public function getSiteUrl(): string
    {
        return $this->siteUrl;
    }

    /**
     * @return string Path part to frontend, eg. /some/sub/dir/
     */
    public function getSitePath(): string
    {
        return $this->sitePath;
    }

    /**
     * @return string Path part to entry script with parameters, without sub dir, eg 'typo3/index.php?id=42'
     */
    public function getSiteScript(): string
    {
        return $this->siteScript;
    }

    /**
     * Will be deprecated later, use getScriptName() as reliable solution instead
     *
     * @return string Script path part of URI, eg. 'typo3/index.php'
     */
    public function getPathInfo(): string
    {
        return $this->pathInfo;
    }

    /**
     * Will be deprecated later, use $request->getServerParams()['HTTP_REFERER'] instead
     *
     * @return string HTTP_REFERER, eg. 'https://www.domain.com/typo3/index.php?id=42'
     */
    public function getHttpReferer(): string
    {
        return $this->httpReferer;
    }

    /**
     * Will be deprecated later, use $request->getServerParams()['HTTP_USER_AGENT'] instead
     *
     * @return string HTTP_USER_AGENT identifier
     */
    public function getHttpUserAgent(): string
    {
        return $this->httpUserAgent;
    }

    /**
     * Will be deprecated later, use $request->getServerParams()['HTTP_ACCEPT_ENCODING'] instead
     *
     * @return string HTTP_ACCEPT_ENCODING, eg. 'gzip, deflate'
     */
    public function getHttpAcceptEncoding(): string
    {
        return $this->httpAcceptEncoding;
    }

    /**
     * Will be deprecated later, use $request->getServerParams()['HTTP_ACCEPT_LANGUAGE'] instead
     *
     * @return string HTTP_ACCEPT_LANGUAGE, eg. 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7'
     */
    public function getHttpAcceptLanguage(): string
    {
        return $this->httpAcceptLanguage;
    }

    /**
     * Will be deprecated later, use $request->getServerParams()['REMOTE_HOST'] instead
     *
     * @return string REMOTE_HOST if configured in web server, eg. 'www.clientDomain.com'
     */
    public function getRemoteHost(): string
    {
        return $this->remoteHost;
    }

    /**
     * Will be deprecated later, use $request->getServerParams()['QUERY_STRING'] instead
     *
     * @return string QUERY_STRING, eg 'id=42&foo=bar'
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * Sanitize HTTP_HOST, take proxy configuration into account and
     * verify allowed hosts with configured trusted hosts pattern.
     *
     * @param array $serverParams Basically the $_SERVER, but from $request object
     * @param array $configuration $TYPO3_CONF_VARS['SYS'] array
     * @param bool $isBehindReverseProxy True if reverse proxy setup is detected
     * @return string Sanitized HTTP_HOST
     */
    protected static function determineHttpHost(array $serverParams, array $configuration, bool $isBehindReverseProxy): string
    {
        $httpHost = $serverParams['HTTP_HOST'] ?? '';
        if ($isBehindReverseProxy) {
            // If the request comes from a configured proxy which has set HTTP_X_FORWARDED_HOST, then
            // evaluate reverseProxyHeaderMultiValue and
            $xForwardedHostArray = GeneralUtility::trimExplode(',', $serverParams['HTTP_X_FORWARDED_HOST'] ?? '', true);
            $xForwardedHost = '';
            // Choose which host in list to use
            if (!empty($xForwardedHostArray)) {
                $configuredReverseProxyHeaderMultiValue = trim($configuration['reverseProxyHeaderMultiValue'] ?? '');
                // Default if reverseProxyHeaderMultiValue is not set or set to 'none', instead of 'first' / 'last' is to
                // ignore $serverParams['HTTP_X_FORWARDED_HOST']
                // @todo: Maybe this default is stupid: Both SYS/reverseProxyIP hand SYS/reverseProxyHeaderMultiValue have to
                // @todo: be configured for a working setup. It would be easier to only configure SYS/reverseProxyIP and fall
                // @todo: back to "first" if SYS/reverseProxyHeaderMultiValue is not set.
                if ($configuredReverseProxyHeaderMultiValue === 'last') {
                    $xForwardedHost = array_pop($xForwardedHostArray);
                } elseif ($configuredReverseProxyHeaderMultiValue === 'first') {
                    $xForwardedHost = array_shift($xForwardedHostArray);
                }
            }
            if ($xForwardedHost) {
                $httpHost = $xForwardedHost;
            }
        }
        if (!GeneralUtility::isAllowedHostHeaderValue($httpHost)) {
            throw new \UnexpectedValueException(
                'The current host header value does not match the configured trusted hosts pattern!'
                . ' Check the pattern defined in $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'trustedHostsPattern\']'
                . ' and adapt it, if you want to allow the current host header \'' . $httpHost . '\' for your installation.',
                1396795886
            );
        }
        return $httpHost;
    }

    /**
     * Determine if the client called via HTTPS. Takes proxy ssl terminator
     * configurations into account.
     *
     * @param array $serverParams Basically the $_SERVER, but from $request object
     * @param array $configuration $TYPO3_CONF_VARS['SYS'] array
     * @return bool True if request has been done via HTTPS
     */
    protected static function determineHttps(array $serverParams, array $configuration): bool
    {
        $isHttps = false;
        $configuredProxySSL = trim($configuration['reverseProxySSL'] ?? '');
        if ($configuredProxySSL === '*') {
            $configuredProxySSL = trim($configuration['reverseProxyIP'] ?? '');
        }
        $httpsParam = (string)($serverParams['HTTPS'] ?? '');
        if (GeneralUtility::cmpIP(trim($serverParams['REMOTE_ADDR'] ?? ''), $configuredProxySSL)
            || ($serverParams['SSL_SESSION_ID'] ?? '')
            // https://secure.php.net/manual/en/reserved.variables.server.php
            // "Set to a non-empty value if the script was queried through the HTTPS protocol."
            || ($httpsParam !== '' && $httpsParam !== 'off' && $httpsParam !== '0')
        ) {
            $isHttps = true;
        }
        return $isHttps;
    }

    /**
     * Determine script name and path
     *
     * @param array $serverParams Basically the $_SERVER, but from $request object
     * @param array $configuration TYPO3_CONF_VARS['SYS'] array
     * @param bool $isHttps True if used protocol is HTTPS
     * @param bool $isBehindReverseProxy True if reverse proxy setup is detected
     * @return string Sanitized script name
     */
    protected static function determineScriptName(array $serverParams, array $configuration, bool $isHttps, bool $isBehindReverseProxy): string
    {
        $scriptName = $serverParams['ORIG_PATH_INFO'] ??
            $serverParams['PATH_INFO'] ??
            $serverParams['ORIG_SCRIPT_NAME'] ??
            $serverParams['SCRIPT_NAME'] ??
            '';
        if ($isBehindReverseProxy) {
            // Add a prefix if TYPO3 is behind a proxy: ext-domain.com => int-server.com/prefix
            if ($isHttps && !empty($configuration['reverseProxyPrefixSSL'])) {
                $scriptName = $configuration['reverseProxyPrefixSSL'] . $scriptName;
            } elseif (!empty($configuration['reverseProxyPrefix'])) {
                $scriptName = $configuration['reverseProxyPrefix'] . $scriptName;
            }
        }
        return $scriptName;
    }

    /**
     * Determine REQUEST_URI, taking proxy configuration and various web server
     * specifics into account.
     *
     * @param array $serverParams Basically the $_SERVER, but from $request object
     * @param array $configuration $TYPO3_CONF_VARS['SYS'] array
     * @param bool $isHttps True if used protocol is HTTPS
     * @param string $scriptName Script name
     * @param bool $isBehindReverseProxy True if reverse proxy setup is detected
     * @return string Sanitized REQUEST_URI
     */
    protected static function determineRequestUri(array $serverParams, array $configuration, bool $isHttps, string $scriptName, bool $isBehindReverseProxy): string
    {
        $proxyPrefixApplied = false;
        if (!empty($configuration['requestURIvar'])) {
            // This is for URL rewriter that store the original URI in a server
            // variable (e.g. ISAPI Rewriter for IIS: HTTP_X_REWRITE_URL), a config then looks like:
            // requestURIvar = '_SERVER|HTTP_X_REWRITE_URL' which will access $GLOBALS['_SERVER']['HTTP_X_REWRITE_URL']
            list($firstLevel, $secondLevel) = GeneralUtility::trimExplode('|', $configuration['requestURIvar'], true);
            $requestUri = $GLOBALS[$firstLevel][$secondLevel];
        } elseif (empty($serverParams['REQUEST_URI'])) {
            // This is for ISS/CGI which does not have the REQUEST_URI available.
            $queryString = !empty($serverParams['QUERY_STRING']) ? '?' . $serverParams['QUERY_STRING'] : '';
            // script name already had the proxy prefix handling, we must not add it a second time
            $proxyPrefixApplied = true;
            $requestUri = '/' . ltrim($scriptName, '/') . $queryString;
        } else {
            $requestUri = '/' . ltrim($serverParams['REQUEST_URI'], '/');
        }
        if (!$proxyPrefixApplied && $isBehindReverseProxy) {
            // Add a prefix if TYPO3 is behind a proxy: ext-domain.com => int-server.com/prefix
            if ($isHttps && !empty($configuration['reverseProxyPrefixSSL'])) {
                $requestUri = $configuration['reverseProxyPrefixSSL'] . $requestUri;
            } elseif (!empty($configuration['reverseProxyPrefix'])) {
                $requestUri = $configuration['reverseProxyPrefix'] . $requestUri;
            }
        }
        return $requestUri;
    }

    /**
     * Determine clients REMOTE_ADDR, even if there is a reverse proxy in between.
     *
     * @param array $serverParams Basically the $_SERVER, but from $request object
     * @param array $configuration $TYPO3_CONF_VARS[SYS] array
     * @param bool $isBehindReverseProxy True if reverse proxy setup is detected
     * @return string Resolved REMOTE_ADDR
     */
    protected static function determineRemoteAddress(array $serverParams, array $configuration, bool $isBehindReverseProxy): string
    {
        $remoteAddress = trim($serverParams['REMOTE_ADDR'] ?? '');
        if ($isBehindReverseProxy) {
            $ip = GeneralUtility::trimExplode(',', $serverParams['HTTP_X_FORWARDED_FOR'] ?? '', true);
            // Choose which IP in list to use
            $configuredReverseProxyHeaderMultiValue = trim($configuration['reverseProxyHeaderMultiValue'] ?? '');
            if (!empty($ip) && $configuredReverseProxyHeaderMultiValue === 'last') {
                $ip = array_pop($ip);
            } elseif (!empty($ip) && $configuredReverseProxyHeaderMultiValue === 'first') {
                $ip = array_shift($ip);
            } else {
                $ip = '';
            }
            if (GeneralUtility::validIP($ip)) {
                $remoteAddress = $ip;
            }
        }
        return $remoteAddress;
    }

    /**
     * Check if a configured reverse proxy setup is detected.
     *
     * @param array $serverParams Basically the $_SERVER, but from $request object
     * @param array $configuration $TYPO3_CONF_VARS[SYS] array
     * @return bool True if TYPO3 is behind a reverse proxy
     */
    protected static function determineIsBehindReverseProxy($serverParams, $configuration): bool
    {
        return GeneralUtility::cmpIP(trim($serverParams['REMOTE_ADDR'] ?? ''), trim($configuration['reverseProxyIP'] ?? ''));
    }

    /**
     * HTTP_HOST without port
     *
     * @param string $httpHost host[:[port]]
     * @return string Resolved host
     */
    protected static function determineRequestHostOnly(string $httpHost): string
    {
        $httpHostBracketPosition = strpos($httpHost, ']');
        $httpHostParts = explode(':', $httpHost);
        return $httpHostBracketPosition !== false ? substr($httpHost, 0, $httpHostBracketPosition + 1) : array_shift($httpHostParts);
    }

    /**
     * Requested port if given
     *
     * @param string $httpHost host[:[port]]
     * @param string $httpHostOnly host
     * @return int Resolved port if given, else 0
     */
    protected static function determineRequestPort(string $httpHost, string $httpHostOnly): int
    {
        return strlen($httpHost) > strlen($httpHostOnly) ? (int)substr($httpHost, strlen($httpHostOnly) + 1) : 0;
    }

    /**
     * Calculate absolute path to web document root
     *
     * @param string $scriptName Entry script path of URI, without domain and without query parameters, with leading /
     * @param string $scriptFilename Absolute path to entry script on server filesystem
     * @return string Path to document root with trailing slash
     */
    protected static function determineDocumentRoot(string $scriptName, string $scriptFilename): string
    {
        // Get the web root (it is not the root of the TYPO3 installation)
        // Some CGI-versions (LA13CGI) and mod-rewrite rules on MODULE versions will deliver a 'wrong'
        // DOCUMENT_ROOT (according to our description). Further various aliases/mod_rewrite rules can
        // disturb this as well. Therefore the DOCUMENT_ROOT is always calculated as the SCRIPT_FILENAME
        // minus the end part shared with SCRIPT_NAME.
        $webDocRoot = '';
        $scriptNameArray = explode('/', strrev($scriptName));
        $scriptFilenameArray = explode('/', strrev($scriptFilename));
        $path = [];
        foreach ($scriptNameArray as $segmentNumber => $segment) {
            if ((string)$scriptFilenameArray[$segmentNumber] === (string)$segment) {
                $path[] = $segment;
            } else {
                break;
            }
        }
        $commonEnd = strrev(implode('/', $path));
        if ((string)$commonEnd !== '') {
            $webDocRoot = substr($scriptFilename, 0, -(strlen($commonEnd) + 1));
        }
        return $webDocRoot;
    }

    /**
     * Determine frontend url
     *
     * @param string $requestDir Full Uri with path, but without script name and query parts
     * @param string $pathThisScript Absolute path to entry script on server filesystem
     * @param string $pathSite Absolute server path to document root
     * @return string Calculated Frontend Url
     */
    protected static function determineSiteUrl(string $requestDir, string $pathThisScript, string $pathSite): string
    {
        if (defined('TYPO3_PATH_WEB')) {
            // This can only be set by external entry scripts
            $siteUrl = $requestDir;
        } else {
            $pathThisScriptDir = substr(dirname($pathThisScript), strlen($pathSite)) . '/';
            $siteUrl = substr($requestDir, 0, -strlen($pathThisScriptDir));
            $siteUrl = rtrim($siteUrl, '/') . '/';
        }
        return $siteUrl;
    }

    /**
     * Determine site path
     *
     * @param string $requestHost scheme://host[:port]
     * @param string $siteUrl Full Frontend Url
     * @return string
     */
    protected static function determineSitePath(string $requestHost, string $siteUrl): string
    {
        return (string)substr($siteUrl, strlen($requestHost));
    }

    /**
     * Determine site script
     *
     * @param string $requestUrl
     * @param string $siteUrl
     * @return string
     */
    protected static function determineSiteScript(string $requestUrl, string $siteUrl): string
    {
        return substr($requestUrl, strlen($siteUrl));
    }
}
