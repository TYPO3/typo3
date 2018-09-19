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

use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend as PhpFrontendCache;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;

/**
 * This class resolves middleware stacks from defined configuration in all active packages.
 *
 * @internal
 */
class MiddlewareStackResolver
{
    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var DependencyOrderingService
     */
    protected $dependencyOrderingService;

    /**
     * @var PhpFrontendCache
     */
    protected $cache;

    public function __construct(
        PackageManager $packageManager,
        DependencyOrderingService $dependencyOrderingService,
        PhpFrontendCache $cache
    ) {
        $this->packageManager = $packageManager;
        $this->dependencyOrderingService = $dependencyOrderingService;
        $this->cache = $cache;
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
     * Loop over all packages and check for a Configuration/RequestMiddlewares.php file
     *
     * @return array
     */
    protected function loadConfiguration(): array
    {
        $packages = $this->packageManager->getActivePackages();
        $allMiddlewares = [[]];
        foreach ($packages as $package) {
            $packageConfiguration = $package->getPackagePath() . 'Configuration/RequestMiddlewares.php';
            if (file_exists($packageConfiguration)) {
                $middlewaresInPackage = require $packageConfiguration;
                if (is_array($middlewaresInPackage)) {
                    $allMiddlewares[] = $middlewaresInPackage;
                }
            }
        }
        return array_replace_recursive(...$allMiddlewares);
    }

    /**
     * Order each stack and sanitize to a plain array
     *
     * @param array
     * @return array
     */
    protected function sanitizeMiddlewares(array $allMiddlewares): array
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
        return 'middlewares_' . $stackName . '_' . sha1(TYPO3_version . Environment::getProjectPath());
    }
}
