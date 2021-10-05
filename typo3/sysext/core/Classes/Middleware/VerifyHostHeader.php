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

namespace TYPO3\CMS\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Checks if the provided host header value matches the trusted hosts pattern.
 *
 * @internal
 */
class VerifyHostHeader implements MiddlewareInterface
{
    public const ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL = '.*';
    public const ENV_TRUSTED_HOSTS_PATTERN_SERVER_NAME = 'SERVER_NAME';

    protected string $trustedHostsPattern;

    public function __construct(string $trustedHostsPattern)
    {
        $this->trustedHostsPattern = $trustedHostsPattern;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $serverParams = $request->getServerParams();
        $httpHost = $serverParams['HTTP_HOST'] ?? '';
        if (!$this->isAllowedHostHeaderValue($httpHost, $serverParams)) {
            throw new \UnexpectedValueException(
                'The current host header value does not match the configured trusted hosts pattern!'
                . ' Check the pattern defined in $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'trustedHostsPattern\']'
                . ' and adapt it, if you want to allow the current host header \'' . $httpHost . '\' for your installation.',
                1396795884
            );
        }

        return $handler->handle($request);
    }

    /**
     * Checks if the provided host header value matches the trusted hosts pattern.
     *
     * @param string $hostHeaderValue HTTP_HOST header value as sent during the request (may include port)
     * @return bool
     */
    public function isAllowedHostHeaderValue(string $hostHeaderValue, array $serverParams): bool
    {
        // Deny the value if trusted host patterns is empty, which means configuration is invalid.
        if ($this->trustedHostsPattern === '') {
            return false;
        }

        if ($this->trustedHostsPattern === self::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL) {
            return true;
        }

        return $this->hostHeaderValueMatchesTrustedHostsPattern($hostHeaderValue, $serverParams);
    }

    /**
     * Checks if the provided host header value matches the trusted hosts pattern without any preprocessing.
     */
    protected function hostHeaderValueMatchesTrustedHostsPattern(string $hostHeaderValue, array $serverParams): bool
    {
        if ($this->trustedHostsPattern === self::ENV_TRUSTED_HOSTS_PATTERN_SERVER_NAME) {
            $host = strtolower($hostHeaderValue);
            // Default port to be verified if HTTP_HOST does not contain explicit port information.
            // Deriving from raw/local webserver HTTPS information (not taking possible proxy configurations into account)
            // as we compare against the raw/local server information (SERVER_PORT).
            $port = self::webserverUsesHttps($serverParams) ? '443' : '80';

            $parsedHostValue = parse_url('http://' . $host);
            if (isset($parsedHostValue['port'])) {
                $host = $parsedHostValue['host'];
                $port = (string)$parsedHostValue['port'];
            }

            // Allow values that equal the server name
            // Note that this is only secure if name base virtual host are configured correctly in the webserver
            $hostMatch = $host === strtolower($serverParams['SERVER_NAME']) && $port === $serverParams['SERVER_PORT'];
        } else {
            // In case name based virtual hosts are not possible, we allow setting a trusted host pattern
            // See https://typo3.org/teams/security/security-bulletins/typo3-core/typo3-core-sa-2014-001/ for further details
            $hostMatch = (bool)preg_match('/^' . $this->trustedHostsPattern . '$/i', $hostHeaderValue);
        }

        return $hostMatch;
    }

    /**
     * Determine if the webserver uses HTTPS.
     *
     * HEADS UP: This does not check if the client performed a
     * HTTPS request, as possible proxies are not taken into
     * account. It provides raw information about the current
     * webservers configuration only.
     */
    protected function webserverUsesHttps(array $serverParams): bool
    {
        if (!empty($serverParams['SSL_SESSION_ID'])) {
            return true;
        }

        // https://secure.php.net/manual/en/reserved.variables.server.php
        // "Set to a non-empty value if the script was queried through the HTTPS protocol."
        return !empty($serverParams['HTTPS']) && strtolower($serverParams['HTTPS']) !== 'off';
    }
}
