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

namespace TYPO3\CMS\Install\Http\Security;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Security\ReferrerEnforcer as CoreReferrerEnforcer;
use TYPO3\CMS\Core\Http\Uri;

/**
 * Treats a referrer as same-origin only if it addresses the install tool entry point
 * (site path plus `__typo3_install` query parameter) - and not merely the same host.
 *
 * Note this requires the client to submit the query string as part of the `Referer` header.
 * Deployments that reduce the referrer to its origin only (`Referrer-Policy: origin` or
 * `strict-origin`) therefore cannot use the install tool while the feature toggle
 * `security.backend.enforceReferrer` is enabled.
 *
 * @internal
 */
readonly class ReferrerEnforcer extends CoreReferrerEnforcer
{
    protected function resolveReferrerType(ServerRequestInterface $request): int
    {
        $normalizedParams = $request->getAttribute('normalizedParams');

        $requestHost = rtrim($this->resolveRequestHost($request), '/') . '/';
        $referrer = $request->getServerParams()['HTTP_REFERER'] ?? '';
        if ($referrer === '') {
            return self::TYPE_REFERRER_EMPTY;
        }

        try {
            $referrerUri = new Uri($referrer);
        } catch (\InvalidArgumentException) {
            return 0;
        }

        $queryParams = [];
        parse_str($referrerUri->getQuery(), $queryParams);
        if (str_starts_with($referrer, $requestHost)) {
            if ($referrerUri->getPath() === $normalizedParams->getSitePath()
                && isset($queryParams['__typo3_install'])
            ) {
                // same-origin implies same-site
                return self::TYPE_REFERRER_SAME_ORIGIN | self::TYPE_REFERRER_SAME_SITE;
            }

            return self::TYPE_REFERRER_SAME_SITE;
        }
        return 0;
    }
}
