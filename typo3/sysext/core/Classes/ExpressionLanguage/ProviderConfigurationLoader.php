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

namespace TYPO3\CMS\Core\ExpressionLanguage;

use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Class ProviderConfigurationLoader
 * This class resolves the expression language provider configuration and store in a cache.
 */
class ProviderConfigurationLoader
{
    protected PackageManager $packageManager;

    protected PhpFrontend $cache;

    protected string $cacheIdentifier;

    public function __construct(PackageManager $packageManager, PhpFrontend $coreCache, string $cacheIdentifier)
    {
        $this->packageManager = $packageManager;
        $this->cache = $coreCache;
        $this->cacheIdentifier = $cacheIdentifier;
    }

    /**
     * @return array
     */
    public function getExpressionLanguageProviders(): array
    {
        $providers = $this->cache->require($this->cacheIdentifier);
        if ($providers !== false) {
            return $providers;
        }

        return $this->createCache();
    }

    private function createCache(): array
    {
        $packages = $this->packageManager->getActivePackages();
        $providers = [];
        foreach ($packages as $package) {
            $packageConfiguration = $package->getPackagePath() . 'Configuration/ExpressionLanguage.php';
            if (file_exists($packageConfiguration)) {
                $providersInPackage = require $packageConfiguration;
                if (is_array($providersInPackage)) {
                    $providers[] = $providersInPackage;
                }
            }
        }
        $providers = count($providers) > 0 ? array_merge_recursive(...$providers) : $providers;
        $this->cache->set($this->cacheIdentifier, 'return ' . var_export($providers, true) . ';');
        return $providers;
    }

    /**
     * @internal
     */
    public function warmupCaches(CacheWarmupEvent $event): void
    {
        if ($event->hasGroup('system')) {
            $this->createCache();
        }
    }
}
