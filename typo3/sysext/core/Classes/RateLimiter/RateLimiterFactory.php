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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory as SymfonyRateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\RateLimiter\Storage\CachingFrameworkStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[Autoconfigure(public: true, shared: false)]
readonly class RateLimiterFactory implements RateLimiterFactoryInterface
{
    public function __construct(
        protected CachingFrameworkStorage $storage,
        protected array $config = [],
    ) {}

    public function create(?string $key = null): LimiterInterface
    {
        if ($this->config === []) {
            throw new \LogicException(
                'Cannot call create() on a RateLimiterFactory without configuration. '
                . 'Use a pre-configured named service or call createLimiter() with an explicit config array.',
                1740000001
            );
        }

        $config = $this->applyConfigOverrides($this->config);
        $factory = new SymfonyRateLimiterFactory($config, $this->storage);
        return $factory->create($key);
    }

    public function createLimiter(array $config, ?string $key = null): LimiterInterface
    {
        $config = $this->applyConfigOverrides($config);
        $factory = new SymfonyRateLimiterFactory($config, $this->storage);
        return $factory->create($key);
    }

    public function createRequestBasedLimiter(ServerRequestInterface $request, array $configuration): LimiterInterface
    {
        $normalizedParams = $request->getAttribute('normalizedParams') ?? NormalizedParams::createFromRequest($request);
        $remoteIp = $normalizedParams->getRemoteAddress();
        return $this->createLimiter($configuration, $remoteIp);
    }

    public function createLoginRateLimiter(ServerRequestInterface $request, string $loginType): LimiterInterface
    {
        $normalizedParams = $request->getAttribute('normalizedParams') ?? NormalizedParams::createFromRequest($request);
        $remoteIp = $normalizedParams->getRemoteAddress();
        $limiterId = 'login-' . strtolower($loginType);
        $limit = (int)($GLOBALS['TYPO3_CONF_VARS'][$loginType]['loginRateLimit'] ?? 5);
        $interval = $GLOBALS['TYPO3_CONF_VARS'][$loginType]['loginRateLimitInterval'] ?? '15 minutes';

        $enabled = !$this->isIpExcluded($loginType, $remoteIp) && $limit > 0;

        if (!$enabled) {
            $config = [
                'id' => $limiterId,
                'policy' => 'no_limit',
                'limit' => $limit,
                'interval' => $interval,
            ];
            $factory = new SymfonyRateLimiterFactory($config, new InMemoryStorage());
            return $factory->create($remoteIp);
        }

        return $this->createLimiter(
            [
                'id' => $limiterId,
                'policy' => 'sliding_window',
                'limit' => $limit,
                'interval' => $interval,
            ],
            $remoteIp
        );
    }

    protected function applyConfigOverrides(array $config): array
    {
        $overrides = $GLOBALS['TYPO3_CONF_VARS']['SYS']['rateLimiter'][$config['id']] ?? [];
        if ($overrides !== []) {
            $config = array_replace($config, $overrides);
        }
        return $config;
    }

    protected function isIpExcluded(string $loginType, string $remoteAddress): bool
    {
        $ipMask = trim($GLOBALS['TYPO3_CONF_VARS'][$loginType]['loginRateLimitIpExcludeList'] ?? '');
        return GeneralUtility::cmpIP($remoteAddress, $ipMask);
    }
}
