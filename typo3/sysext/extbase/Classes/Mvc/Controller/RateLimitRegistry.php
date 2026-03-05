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

namespace TYPO3\CMS\Extbase\Mvc\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use TYPO3\CMS\Core\RateLimiter\RateLimiterFactoryInterface;
use TYPO3\CMS\Extbase\Attribute\RateLimit;

/**
 * Registry for rate limit configurations of extbase controller actions,
 * populated at compile time via {@see \TYPO3\CMS\Extbase\DependencyInjection\RateLimitPass}.
 *
 * @internal
 */
final class RateLimitRegistry
{
    /** @var array<string, array<string, RateLimit>> */
    private array $rateLimits = [];

    public function __construct(
        private readonly RateLimiterFactoryInterface $rateLimiterFactory,
    ) {}

    public function add(string $controllerClass, string $actionMethod, int $limit, string $interval, string $policy, string $message): void
    {
        $this->rateLimits[$controllerClass][$actionMethod] = new RateLimit($limit, $interval, $policy, $message);
    }

    public function getRateLimit(string $controllerClass, string $actionMethod): ?RateLimit
    {
        return $this->rateLimits[$controllerClass][$actionMethod] ?? null;
    }

    public function createLimiter(string $controllerClass, string $actionMethod, ServerRequestInterface $request): ?LimiterInterface
    {
        $rateLimit = $this->getRateLimit($controllerClass, $actionMethod);
        if ($rateLimit === null) {
            return null;
        }

        $identifier = strtolower(str_replace('\\', '-', $controllerClass) . '-' . $actionMethod);
        return $this->rateLimiterFactory->createRequestBasedLimiter($request, $rateLimit->getConfiguration($identifier));
    }
}
