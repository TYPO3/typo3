<?php
declare(strict_types = 1);
namespace TYPO3\CMS\T3editor;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\T3editor\Registry\AddonRegistry;
use TYPO3\CMS\T3editor\Registry\ModeRegistry;

/**
 * Provides necessary code to setup a t3editor instance in FormEngine
 * @internal
 */
class T3editor implements SingletonInterface
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * Registers the configuration and bootstraps the modes / addons.
     *
     * @throws \InvalidArgumentException
     */
    public function registerConfiguration()
    {
        $configuration = $this->buildConfiguration();

        if (isset($configuration['modes'])) {
            $modeRegistry = ModeRegistry::getInstance();
            foreach ($configuration['modes'] as $formatCode => $mode) {
                $modeInstance = GeneralUtility::makeInstance(Mode::class, $mode['module'])->setFormatCode($formatCode);

                if (!empty($mode['extensions']) && is_array($mode['extensions'])) {
                    $modeInstance->bindToFileExtensions($mode['extensions']);
                }

                if (isset($mode['default']) && $mode['default'] === true) {
                    $modeInstance->setAsDefault();
                }

                $modeRegistry->register($modeInstance);
            }
        }

        $addonRegistry = GeneralUtility::makeInstance(AddonRegistry::class);
        if (isset($configuration['addons'])) {
            foreach ($configuration['addons'] as $addon) {
                $addonInstance = GeneralUtility::makeInstance(Addon::class, $addon['module']);

                if (!empty($addon['cssFiles']) && is_array($addon['cssFiles'])) {
                    $addonInstance->setCssFiles($addon['cssFiles']);
                }

                if (!empty($addon['options']) && is_array($addon['options'])) {
                    $addonInstance->setOptions($addon['options']);
                }

                if (!empty($addon['modes']) && is_array($addon['modes'])) {
                    $addonInstance->setModes($addon['modes']);
                }

                $addonRegistry->register($addonInstance);
            }
        }
    }

    /**
     * Compiles the configuration for t3editor. Configuration is stored in caching framework.
     *
     * @return array
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
     * @throws \InvalidArgumentException
     */
    protected function buildConfiguration(): array
    {
        if ($this->configuration !== null) {
            return $this->configuration;
        }

        $this->configuration = [
            'modes' => [],
            'addons' => [],
        ];

        $cache = $this->getCache();
        $cacheIdentifier = $this->generateCacheIdentifier('T3editorConfiguration');
        $configurationFromCache = $cache->get($cacheIdentifier);
        if ($configurationFromCache !== false) {
            $this->configuration = $configurationFromCache;
        } else {
            $packageManager = GeneralUtility::makeInstance(PackageManager::class);
            $packages = $packageManager->getActivePackages();

            foreach ($packages as $package) {
                $configurationPath = $package->getPackagePath() . 'Configuration/Backend/T3editor';
                $modesFileNameForPackage = $configurationPath . '/Modes.php';
                if (is_file($modesFileNameForPackage)) {
                    $definedModes = require_once $modesFileNameForPackage;
                    if (is_array($definedModes)) {
                        $this->configuration['modes'] = array_merge($this->configuration['modes'], $definedModes);
                    }
                }

                $addonsFileNameForPackage = $configurationPath . '/Addons.php';
                if (is_file($addonsFileNameForPackage)) {
                    $definedAddons = require_once $addonsFileNameForPackage;
                    if (is_array($definedAddons)) {
                        $this->configuration['addons'] = array_merge($this->configuration['addons'], $definedAddons);
                    }
                }
            }
            $cache->set($cacheIdentifier, $this->configuration);
        }

        return $this->configuration;
    }

    /**
     * @param string $key
     * @return string
     */
    protected function generateCacheIdentifier(string $key): string
    {
        return $key . '_' . sha1(TYPO3_version . Environment::getProjectPath() . $key);
    }

    /**
     * @return FrontendInterface
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     * @throws \InvalidArgumentException
     */
    protected function getCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('assets');
    }
}
