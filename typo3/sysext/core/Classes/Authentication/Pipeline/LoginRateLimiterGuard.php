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

namespace TYPO3\CMS\Core\Authentication\Pipeline;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\RateLimiter\RateLimiterFactoryInterface;
use TYPO3\CMS\Core\RateLimiter\RequestRateLimitedException;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Applies the login rate limit for active login attempts.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
final class LoginRateLimiterGuard
{
    public function __construct(
        private readonly RateLimiterFactoryInterface $rateLimiterFactory,
        private readonly LoggerInterface $logger,
    ) {}

    public function ensureLoginRateLimit(AbstractUserAuthentication $user, ServerRequestInterface $request): ?LimiterInterface
    {
        if (!$user->isActiveLogin($request)) {
            return null;
        }
        $loginRateLimiter = $this->rateLimiterFactory->createLoginRateLimiter($request, $user->loginType);
        $limit = $loginRateLimiter->consume();
        if (!$limit->isAccepted()) {
            $this->logger->debug('Login request has been rate limited for IP address {ipAddress}', ['ipAddress' => $request->getAttribute('normalizedParams')->getRemoteAddress()]);
            $dateformat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
            $lockedUntil = $limit->getRetryAfter()->getTimestamp() > 0
                ? ' until ' . date($dateformat, $limit->getRetryAfter()->getTimestamp()) : '';
            $message = 'The login is locked' . $lockedUntil . ' due to too many failed login attempts from your IP address.';
            if ($user->loginType === 'FE') {
                throw new RequestRateLimitedException(HttpUtility::HTTP_STATUS_403, $message, 'Login Request Rate Limited', 1616175847);
            }
            throw new RequestRateLimitedException(HttpUtility::HTTP_STATUS_403, $message, 'Login Request Rate Limited', 1616175867);
        }
        return $loginRateLimiter;
    }
}
