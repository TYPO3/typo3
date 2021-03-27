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

namespace TYPO3\CMS\Core\RateLimiter;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory as SymfonyRateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\RateLimiter\Storage\CachingFrameworkStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This is not part of the official TYPO3 Core API due to a limitation of the experimental Symfony Rate Limiter API.
 */
class RateLimiterFactory
{
    public function createLoginRateLimiter(AbstractUserAuthentication $userAuthentication, ServerRequestInterface $request): LimiterInterface
    {
        $loginType = $userAuthentication->loginType;
        $normalizedParams = $request->getAttribute('normalizedParams') ?? NormalizedParams::createFromRequest($request);
        $remoteIp = $normalizedParams->getRemoteAddress();
        $limiterId = sha1('typo3-login-' . $loginType);
        $limit = (int)($GLOBALS['TYPO3_CONF_VARS'][$loginType]['loginRateLimit'] ?? 5);
        $interval = $GLOBALS['TYPO3_CONF_VARS'][$loginType]['loginRateLimitInterval'] ?? '15 minutes';

        // If not enabled, return a null limiter
        $enabled = !$this->isIpExcluded($loginType, $remoteIp) && $limit > 0;

        $config = [
            'id' => $limiterId,
            'policy' => ($enabled ? 'sliding_window' : 'no_limit'),
            'limit' => $limit,
            'interval' => $interval,
        ];
        $storage = ($enabled ? GeneralUtility::makeInstance(CachingFrameworkStorage::class) : new InMemoryStorage());
        $limiterFactory = new SymfonyRateLimiterFactory(
            $config,
            $storage
        );
        return $limiterFactory->create($remoteIp);
    }

    protected function isIpExcluded(string $loginType, string $remoteAddress): bool
    {
        $ipMask = trim($GLOBALS['TYPO3_CONF_VARS'][$loginType]['loginRateLimitIpExcludeList'] ?? '');
        return GeneralUtility::cmpIP($remoteAddress, $ipMask);
    }
}
