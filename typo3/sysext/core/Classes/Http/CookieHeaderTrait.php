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

use Symfony\Component\HttpFoundation\Cookie;

trait CookieHeaderTrait
{
    private function hasSameSiteCookieSupport(): bool
    {
        return version_compare(PHP_VERSION, '7.3.0', '>=');
    }

    /**
     * Since PHP < 7.3 is not capable of sending the same-site cookie information, session_start() effectively
     * sends the Set-Cookie header. This method fetches the set-cookie headers, parses it via Symfony's Cookie
     * object, and resends the header.
     *
     * @param string[] $cookieNames
     */
    private function resendCookieHeader(array $cookieNames = []): void
    {
        $cookies = array_filter(headers_list(), function (string $header) {
            return stripos($header, 'Set-Cookie:') === 0;
        });
        $cookies = array_map(function (string $cookieHeader) use ($cookieNames) {
            $payload = ltrim(substr($cookieHeader, 11));
            $cookie = Cookie::fromString($payload);
            $sameSite = $cookie->getSameSite();
            // adjust SameSite flag only for given cookie names (applied to all if not declared)
            if (empty($cookieNames) || in_array($cookie->getName(), $cookieNames, true)) {
                $sameSite = $sameSite ?? Cookie::SAMESITE_STRICT;
            }
            return (string)Cookie::create(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly(),
                $cookie->isRaw(),
                $sameSite
            );
        }, $cookies);
        if (!empty($cookies)) {
            header_remove('Set-Cookie');
            foreach ($cookies as $cookie) {
                header('Set-Cookie: ' . $cookie, false);
            }
        }
    }

    private function sanitizeSameSiteCookieValue(string $cookieSameSite): string
    {
        if (!in_array($cookieSameSite, [Cookie::SAMESITE_STRICT, Cookie::SAMESITE_LAX, Cookie::SAMESITE_NONE], true)) {
            $cookieSameSite = Cookie::SAMESITE_STRICT;
        }
        return $cookieSameSite;
    }
}
