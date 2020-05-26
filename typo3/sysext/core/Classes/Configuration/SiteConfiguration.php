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

namespace TYPO3\CMS\Core\Configuration;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Responsibility: Handles the format of the configuration (currently yaml), and the location of the file system folder
 *
 * Reads all available site configuration options, and puts them into Site objects.
 *
 * @internal
 */
class SiteConfiguration implements SingletonInterface
{
    protected PhpFrontend $cache;

    /**
     * @var string
     */
    protected $configPath;

    /**
     * Config yaml file name.
     *
     * @internal
     * @var string
     */
    protected $configFileName = 'config.yaml';

    /**
     * Identifier to store all configuration data in cache_core cache.
     *
     * @internal
     * @var string
     */
    protected $cacheIdentifier = 'sites-configuration';

    /**
     * Cache stores all configuration as Site objects, as long as they haven't been changed.
     * This drastically improves performance as SiteFinder utilizes SiteConfiguration heavily
     *
     * @var array|null
     */
    protected $firstLevelCache;

    /**
     * @param string $configPath
     * @param PhpFrontend $coreCache
     */
    public function __construct(string $configPath, PhpFrontend $coreCache = null)
    {
        $this->configPath = $configPath;
        // The following fallback to GeneralUtility;:getContainer() is only used in acceptance tests
        // @todo: Fix testing-framework/typo3/sysext/core/Classes/Configuration/SiteConfiguration.php
        // to inject the cache instance
        $this->cache = $coreCache ?? GeneralUtility::getContainer()->get('cache.core');
    }

    /**
     * Return all site objects which have been found in the filesystem.
     *
     * @param bool $useCache
     * @return Site[]
     */
    public function getAllExistingSites(bool $useCache = true): array
    {
        if ($useCache && $this->firstLevelCache !== null) {
            return $this->firstLevelCache;
        }
        return $this->resolveAllExistingSites($useCache);
    }

    /**
     * Creates a site configuration with one language "English" which is the de-facto default language for TYPO3 in general.
     *
     * @param string $identifier
     * @param int $rootPageId
     * @param string $base
     * @throws SiteConfigurationWriteException
     */
    public function createNewBasicSite(string $identifier, int $rootPageId, string $base): void
    {
        // Create a default site configuration called "main" as best practice
        $this->write($identifier, [
            'rootPageId' => $rootPageId,
            'base' => $base,
            'languages' => [
                0 => [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => 0,
                    'base' => '/',
                    'typo3Language' => 'default',
                    'locale' => 'en_US.UTF-8',
                    'iso-639-1' => 'en',
                    'navigationTitle' => 'English',
                    'hreflang' => 'en-us',
                    'direction' => 'ltr',
                    'flag' => 'us',
                ],
            ],
            'errorHandling' => [],
            'routes' => [],
        ]);
    }

    /**
     * Resolve all site objects which have been found in the filesystem.
     *
     * @param bool $useCache
     * @return Site[]
     */
    public function resolveAllExistingSites(bool $useCache = true): array
    {
        $sites = [];
        $siteConfiguration = $this->getAllSiteConfigurationFromFiles($useCache);
        foreach ($siteConfiguration as $identifier => $configuration) {
            $rootPageId = (int)($configuration['rootPageId'] ?? 0);
            if ($rootPageId > 0) {
                $sites[$identifier] = GeneralUtility::makeInstance(Site::class, $identifier, $rootPageId, $configuration);
            }
        }
        $this->firstLevelCache = $sites;
        return $sites;
    }

    /**
     * Read the site configuration from config files.
     *
     * @param bool $useCache
     * @return array
     * @throws InvalidDataException
     */
    protected function getAllSiteConfigurationFromFiles(bool $useCache = true): array
    {
        // Check if the data is already cached
        $siteConfiguration = $useCache ? $this->cache->require($this->cacheIdentifier) : false;
        if ($siteConfiguration !== false) {
            return $siteConfiguration;
        }
        $finder = new Finder();
        try {
            $finder->files()->depth(0)->name($this->configFileName)->in($this->configPath . '/*');
        } catch (\InvalidArgumentException $e) {
            // Directory $this->configPath does not exist yet
            $finder = [];
        }
        $loader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $siteConfiguration = [];
        foreach ($finder as $fileInfo) {
            $configuration = $loader->load(GeneralUtility::fixWindowsFilePath((string)$fileInfo));
            $identifier = basename($fileInfo->getPath());
            $siteConfiguration[$identifier] = $configuration;
        }
        $this->cache->set($this->cacheIdentifier, 'return ' . var_export($siteConfiguration, true) . ';');

        return $siteConfiguration;
    }

    /**
     * Load plain configuration
     * This method should only be used in case the original configuration as it exists in the file should be loaded,
     * for example for writing / editing configuration.
     *
     * All read related actions should be performed on the site entity.
     *
     * @param string $siteIdentifier
     * @return array
     */
    public function load(string $siteIdentifier): array
    {
        $fileName = $this->configPath . '/' . $siteIdentifier . '/' . $this->configFileName;
        $loader = GeneralUtility::makeInstance(YamlFileLoader::class);
        return $loader->load(GeneralUtility::fixWindowsFilePath($fileName), YamlFileLoader::PROCESS_IMPORTS);
    }

    /**
     * Add or update a site configuration
     *
     * @param string $siteIdentifier
     * @param array $configuration
     * @throws SiteConfigurationWriteException
     */
    public function write(string $siteIdentifier, array $configuration): void
    {
        $folder = $this->configPath . '/' . $siteIdentifier;
        $fileName = $folder . '/' . $this->configFileName;
        $newConfiguration = $configuration;
        if (!file_exists($folder)) {
            GeneralUtility::mkdir_deep($folder);
        } elseif (file_exists($fileName)) {
            $loader = GeneralUtility::makeInstance(YamlFileLoader::class);
            // load without any processing to have the unprocessed base to modify
            $newConfiguration = $loader->load(GeneralUtility::fixWindowsFilePath($fileName), 0);
            // load the processed configuration to diff changed values
            $processed = $loader->load(GeneralUtility::fixWindowsFilePath($fileName));
            // find properties that were modified via GUI
            $newModified = array_replace_recursive(
                self::findRemoved($processed, $configuration),
                self::findModified($processed, $configuration)
            );
            // change _only_ the modified keys, leave the original non-changed areas alone
            ArrayUtility::mergeRecursiveWithOverrule($newConfiguration, $newModified);
        }
        $newConfiguration = $this->sortConfiguration($newConfiguration);
        $yamlFileContents = Yaml::dump($newConfiguration, 99, 2);
        if (!GeneralUtility::writeFile($fileName, $yamlFileContents)) {
            throw new SiteConfigurationWriteException('Unable to write site configuration in sites/' . $siteIdentifier . '/' . $this->configFileName, 1590487011);
        }
        $this->firstLevelCache = null;
        $this->cache->remove($this->cacheIdentifier);
    }

    /**
     * Renames a site identifier (and moves the folder)
     *
     * @param string $currentIdentifier
     * @param string $newIdentifier
     * @throws SiteConfigurationWriteException
     */
    public function rename(string $currentIdentifier, string $newIdentifier): void
    {
        if (!rename($this->configPath . '/' . $currentIdentifier, $this->configPath . '/' . $newIdentifier)) {
            throw new SiteConfigurationWriteException('Unable to rename folder sites/' . $currentIdentifier, 1522491300);
        }
        $this->cache->remove($this->cacheIdentifier);
        $this->firstLevelCache = null;
    }

    /**
     * Removes the config.yaml file of a site configuration.
     * Also clears the cache.
     *
     * @param string $siteIdentifier
     * @throws SiteNotFoundException|SiteConfigurationWriteException
     */
    public function delete(string $siteIdentifier): void
    {
        $sites = $this->getAllExistingSites();
        if (!isset($sites[$siteIdentifier])) {
            throw new SiteNotFoundException('Site configuration named ' . $siteIdentifier . ' not found.', 1522866183);
        }
        $fileName = $this->configPath . '/' . $siteIdentifier . '/' . $this->configFileName;
        if (!file_exists($fileName)) {
            throw new SiteNotFoundException('Site configuration file ' . $this->configFileName . ' within the site ' . $siteIdentifier . ' not found.', 1522866184);
        }
        if (!unlink($fileName)) {
            throw new SiteConfigurationWriteException('Unable to delete folder sites/' . $siteIdentifier, 1596462020);
        }
        $this->cache->remove($this->cacheIdentifier);
        $this->firstLevelCache = null;
    }

    /**
     * @param array $newConfiguration
     * @return array
     */
    protected function sortConfiguration(array $newConfiguration): array
    {
        ksort($newConfiguration);
        if (isset($newConfiguration['imports'])) {
            $imports = $newConfiguration['imports'];
            unset($newConfiguration['imports']);
            $newConfiguration['imports'] = $imports;
        }
        return $newConfiguration;
    }

    protected static function findModified(array $currentConfiguration, array $newConfiguration): array
    {
        $differences = [];
        foreach ($newConfiguration as $key => $value) {
            if (!isset($currentConfiguration[$key]) || $currentConfiguration[$key] !== $newConfiguration[$key]) {
                if (!isset($newConfiguration[$key]) && isset($currentConfiguration[$key])) {
                    $differences[$key] = '__UNSET';
                } elseif (isset($currentConfiguration[$key])
                    && is_array($newConfiguration[$key])
                    && is_array($currentConfiguration[$key])
                ) {
                    $differences[$key] = self::findModified($currentConfiguration[$key], $newConfiguration[$key]);
                } else {
                    $differences[$key] = $value;
                }
            }
        }
        return $differences;
    }

    protected static function findRemoved(array $currentConfiguration, array $newConfiguration): array
    {
        $removed = [];
        foreach ($currentConfiguration as $key => $value) {
            if (!isset($newConfiguration[$key])) {
                $removed[$key] = '__UNSET';
            } elseif (isset($currentConfiguration[$key]) && is_array($currentConfiguration[$key]) && is_array($newConfiguration[$key])) {
                $removedInRecursion = self::findRemoved($currentConfiguration[$key], $newConfiguration[$key]);
                if (!empty($removedInRecursion)) {
                    $removed[$key] = $removedInRecursion;
                }
            }
        }

        return $removed;
    }

    public function warmupCaches(CacheWarmupEvent $event): void
    {
        if ($event->hasGroup('system')) {
            $this->getAllSiteConfigurationFromFiles(false);
        }
    }
}
