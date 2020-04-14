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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ProviderConfigurationLoader
 * This class resolves the expression language provider configuration and store in a cache.
 */
class ProviderConfigurationLoader
{
    /**
     * @var string
     */
    protected $cacheIdentifier = 'expressionLanguageProviders';

    /**
     * @return array
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    public function getExpressionLanguageProviders(): array
    {
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('core');

        /** @noinspection PhpUndefinedMethodInspection the method require() will be added to the interface in TYPO3 v10.0 */
        $providers = $cache->require($this->cacheIdentifier);
        if ($providers !== false) {
            return $providers;
        }

        $packages = $packageManager->getActivePackages();
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
        $cache->set($this->cacheIdentifier, 'return ' . var_export($providers, true) . ';');
        return $providers ?? [];
    }
}
