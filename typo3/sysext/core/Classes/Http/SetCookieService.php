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

namespace TYPO3\CMS\Core\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class that is used to apply a SetCookie to a response,
 * based on the current (authenticated or stateful) session,
 * in order to use the same session across multiple HTTP requests.
 */
class SetCookieService
{
    use CookieHeaderTrait;

    protected readonly LoggerInterface $logger;

    public static function create(string $name, string $type): self
    {
        $lifetime = (int)($GLOBALS['TYPO3_CONF_VARS'][$type]['lifetime'] ?? 0);
        return new self($name, $type, $lifetime);
    }

    private function __construct(
        protected readonly string $name,
        protected readonly string $loginType,
        /**
         * Lifetime for the session-cookie (on the client)
         *
         * If >0: permanent cookie with given lifetime
         * If 0: session-cookie
         * Session-cookie means the browser will remove it when the browser is closed.
         */
        protected readonly int $lifetime
    ) {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Sets the session cookie for the current disposal.
     */
    public function setSessionCookie(UserSession $userSession, NormalizedParams $normalizedParams): ?Cookie
    {
        $setCookie = null;
        $isRefreshTimeBasedCookie = $this->isRefreshTimeBasedCookie($userSession);
        if ($this->isSetSessionCookie($userSession) || $isRefreshTimeBasedCookie) {
            // Get the domain to be used for the cookie (if any):
            $cookieDomain = $this->getCookieDomain($normalizedParams);
            // If no cookie domain is set, use the base path:
            $cookiePath = $cookieDomain ? '/' : $normalizedParams->getSitePath();
            // If the cookie lifetime is set, use it:
            $cookieExpire = $isRefreshTimeBasedCookie ? $GLOBALS['EXEC_TIME'] + $this->lifetime : 0;
            // Valid options are "strict", "lax" or "none", whereas "none" only works in HTTPS requests (default & fallback is "strict")
            $cookieSameSite = $this->sanitizeSameSiteCookieValue(
                strtolower($GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['cookieSameSite'] ?? Cookie::SAMESITE_STRICT)
            );
            // Use the secure option when the current request is served by a secure connection:
            // SameSite "none" needs the secure option (only allowed on HTTPS)
            $isSecure = $cookieSameSite === Cookie::SAMESITE_NONE || $normalizedParams->isHttps();
            $sessionId = $userSession->getIdentifier();
            $cookieValue = $userSession->getJwt();
            $setCookie = new Cookie(
                $this->name,
                $cookieValue,
                $cookieExpire,
                $cookiePath,
                $cookieDomain,
                $isSecure,
                true,
                false,
                $cookieSameSite
            );
            $message = $isRefreshTimeBasedCookie ? 'Updated Cookie: {session}, {domain}' : 'Set Cookie: {session}, {domain}';
            $this->logger->debug($message, [
                'session' => sha1($sessionId),
                'domain' => $cookieDomain,
            ]);
        }
        return $setCookie;
    }

    /**
     * Gets the domain to be used on setting cookies.
     * The information is taken from the value in $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'].
     *
     * @return string The domain to be used on setting cookies
     */
    protected function getCookieDomain(NormalizedParams $normalizedParams): string
    {
        $result = '';
        $cookieDomain = $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'] ?? '';
        // If a specific cookie domain is defined for a given application type, use that domain
        if (!empty($GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['cookieDomain'])) {
            $cookieDomain = $GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['cookieDomain'];
        }
        if ($cookieDomain) {
            if ($cookieDomain[0] === '/') {
                $match = [];
                $matchCnt = @preg_match($cookieDomain, $normalizedParams->getRequestHostOnly(), $match);
                if ($matchCnt === false) {
                    $this->logger->critical(
                        'The regular expression for the cookie domain ({domain}) contains errors. The session is not shared across sub-domains.',
                        ['domain' => $cookieDomain]
                    );
                } elseif ($matchCnt) {
                    $result = $match[0];
                }
            } else {
                $result = $cookieDomain;
            }
        }
        return $result;
    }

    /**
     * Determine whether a session cookie needs to be set (lifetime=0)
     */
    public function isSetSessionCookie(UserSession $userSession, bool $forceSetCookie = false): bool
    {
        if ($this->loginType === 'FE') {
            return ($userSession->isNew() || $forceSetCookie)
                && ($this->lifetime === 0 || !$userSession->isPermanent());
        }
        return $userSession->isNew() && $this->lifetime === 0;
    }

    /**
     * Determine whether a non-session cookie needs to be set (lifetime>0)
     *
     * @internal
     */
    public function isRefreshTimeBasedCookie(UserSession $userSession): bool
    {
        if ($this->loginType === 'FE') {
            return $this->lifetime > 0 && $userSession->isPermanent();
        }
        return $this->lifetime > 0;
    }

    /**
     * Returns whether this request is going to set a cookie
     * or a cookie was already found in the system
     *
     * @return bool Returns TRUE if a cookie is set
     */
    public function isCookieSet(?ServerRequestInterface $request, ?UserSession $userSession): bool
    {
        $isRefreshTimeBasedCookie = $userSession && $this->isRefreshTimeBasedCookie($userSession);
        if ($isRefreshTimeBasedCookie || $this->isSetSessionCookie($userSession)) {
            return true;
        }
        if ($request && isset($request->getCookieParams()[$this->name])) {
            return true;
        }
        return false;
    }

    /**
     * Empty / unset the cookie
     */
    public function removeCookie(NormalizedParams $normalizedParams): Cookie
    {
        $cookieDomain = $this->getCookieDomain($normalizedParams);
        // If no cookie domain is set, use the base path
        $cookiePath = $cookieDomain ? '/' : $normalizedParams->getSitePath();
        return new Cookie(
            $this->name,
            '',
            -1,
            $cookiePath,
            $cookieDomain
        );
    }
}
