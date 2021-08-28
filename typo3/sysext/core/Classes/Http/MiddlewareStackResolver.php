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

use ArrayObject;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend as PhpFrontendCache;
use TYPO3\CMS\Core\Service\DependencyOrderingService;

/**
 * This class resolves middleware stacks from defined configuration in all active packages.
 *
 * @internal
 */
class MiddlewareStackResolver
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var DependencyOrderingService
     */
    protected $dependencyOrderingService;

    /**
     * @var PhpFrontendCache
     */
    protected $cache;

    private string $baseCacheIdentifier;

    public function __construct(
        ContainerInterface $container,
        DependencyOrderingService $dependencyOrderingService,
        PhpFrontendCache $cache,
        string $baseCacheIdentifier
    ) {
        $this->container = $container;
        $this->dependencyOrderingService = $dependencyOrderingService;
        $this->cache = $cache;
        $this->baseCacheIdentifier = '_' . $baseCacheIdentifier;
    }

    /**
     * Returns the middleware stack registered in all packages within Configuration/RequestMiddlewares.php
     * which are sorted by given dependency requirements
     *
     * @param string $stackName
     * @return array
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function resolve(string $stackName): array
    {
        // Check if the registered middlewares from all active packages have already been cached
        $cacheIdentifier = $this->getCacheIdentifier($stackName);
        if ($this->cache->has($cacheIdentifier)) {
            return $this->cache->require($cacheIdentifier);
        }

        $allMiddlewares = $this->loadConfiguration();
        $middlewares = $this->sanitizeMiddlewares($allMiddlewares);

        // Ensure that we create a cache for $stackName, even if the stack is empty
        if (!isset($middlewares[$stackName])) {
            $middlewares[$stackName] = [];
        }

        foreach ($middlewares as $stack => $middlewaresOfStack) {
            $this->cache->set($this->getCacheIdentifier($stack), 'return ' . var_export($middlewaresOfStack, true) . ';');
        }

        return $middlewares[$stackName];
    }

    /**
     * Lazy load configuration from the container
     *
     * @return ArrayObject
     */
    protected function loadConfiguration(): ArrayObject
    {
        return $this->container->get('middlewares');
    }

    /**
     * Order each stack and sanitize to a plain array
     *
     * @param ArrayObject $allMiddlewares
     * @return array
     */
    protected function sanitizeMiddlewares(ArrayObject $allMiddlewares): array
    {
        $middlewares = [];

        foreach ($allMiddlewares as $stack => $middlewaresOfStack) {
            $middlewaresOfStack = $this->dependencyOrderingService->orderByDependencies($middlewaresOfStack);

            $sanitizedMiddlewares = [];
            foreach ($middlewaresOfStack as $name => $middleware) {
                if (isset($middleware['disabled']) && $middleware['disabled'] === true) {
                    // Skip this middleware if disabled by configuration
                    continue;
                }
                $sanitizedMiddlewares[$name] = $middleware['target'];
            }

            // Order reverse, MiddlewareDispatcher executes the last middleware in the array first (last in, first out).
            $middlewares[$stack] = array_reverse($sanitizedMiddlewares);
        }

        return $middlewares;
    }

    /**
     * @param string $stackName
     * @return string
     */
    protected function getCacheIdentifier(string $stackName): string
    {
        return 'middlewares_' . $stackName . $this->baseCacheIdentifier;
    }

    public function warmupCaches(CacheWarmupEvent $event): void
    {
        if ($event->hasGroup('system')) {
            $allMiddlewares = $this->loadConfiguration();
            $middlewares = $this->sanitizeMiddlewares($allMiddlewares);

            foreach ($middlewares as $stack => $middlewaresOfStack) {
                $this->cache->set($this->getCacheIdentifier($stack), 'return ' . var_export($middlewaresOfStack, true) . ';');
            }
        }
    }
}
