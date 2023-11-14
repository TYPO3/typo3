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

trait CookieScopeTrait
{
    /**
     * Returns the domain and path to be used for setting cookies.
     * The information is taken from the value in $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'] if set,
     * otherwise the normalized request params are used.
     *
     * @return array{domain: string, path: string} The domain and path to be used when setting cookies
     */
    private function getCookieScope(NormalizedParams $normalizedParams): array
    {
        $cookieDomain = $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'] ?? '';
        // If a specific cookie domain is defined for a given application type, use that domain
        if (!empty($GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['cookieDomain'])) {
            $cookieDomain = $GLOBALS['TYPO3_CONF_VARS'][$this->loginType]['cookieDomain'];
        }
        if (!$cookieDomain) {
            return [
                'domain' => $normalizedParams->getRequestHostOnly(),
                // If no cookie domain is set, use the base path
                'path' => $normalizedParams->getSitePath(),
            ];
        }
        if ($cookieDomain[0] === '/') {
            $match = [];
            $matchCount = @preg_match($cookieDomain, $normalizedParams->getRequestHostOnly(), $match);
            if ($matchCount === false) {
                $this->logger->critical(
                    'The regular expression for the cookie domain ({domain}) contains errors. The session is not shared across sub-domains.',
                    ['domain' => $cookieDomain]
                );
            }
            if ($matchCount === false || $matchCount === 0) {
                return [
                    'domain' => $normalizedParams->getRequestHostOnly(),
                    // If no cookie domain could be matched, use the base path
                    'path' => $normalizedParams->getSitePath(),
                ];
            }
            $cookieDomain = $match[0];
        }

        return [
            'domain' => trim($cookieDomain, '.'),
            'path' => '/',
        ];
    }
}
