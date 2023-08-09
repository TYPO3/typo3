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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationBeforeWriteEvent;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationLoadedEvent;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\Loader\Exception\YamlPlaceholderException;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\Loader\YamlPlaceholderGuard;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
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

    protected string $configPath;

    /**
     * Config yaml file name.
     *
     * @internal
     */
    protected string $configFileName = 'config.yaml';

    /**
     * YAML file name with all settings.
     *
     * @internal
     */
    protected string $settingsFileName = 'settings.yaml';

    /**
     * YAML file name with all settings related to Content-Security-Policies.
     *
     * @internal
     */
    protected string $contentSecurityFileName = 'csp.yaml';

    /**
     * Identifier to store all configuration data in cache_core cache.
     *
     * @internal
     */
    protected string $cacheIdentifier = 'sites-configuration';

    /**
     * Cache stores all configuration as Site objects, as long as they haven't been changed.
     * This drastically improves performance as SiteFinder utilizes SiteConfiguration heavily
     *
     * @var array|null
     */
    protected $firstLevelCache;
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * @param PhpFrontend|null $coreCache
     */
    public function __construct(string $configPath, EventDispatcherInterface $eventDispatcher, PhpFrontend $coreCache = null)
    {
        $this->configPath = $configPath;
        // The following fallback to GeneralUtility;:getContainer() is only used in acceptance tests
        // @todo: Fix testing-framework/typo3/sysext/core/Classes/Configuration/SiteConfiguration.php
        // to inject the cache instance
        $this->cache = $coreCache ?? GeneralUtility::getContainer()->get('cache.core');
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Return all site objects which have been found in the filesystem.
     *
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
                    'locale' => 'en_US.UTF-8',
                    'navigationTitle' => 'English',
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
     * @return Site[]
     */
    public function resolveAllExistingSites(bool $useCache = true): array
    {
        $sites = [];
        $siteConfiguration = $this->getAllSiteConfigurationFromFiles($useCache);
        foreach ($siteConfiguration as $identifier => $configuration) {
            // cast $identifier to string, as the identifier can potentially only consist of (int) digit numbers
            $identifier = (string)$identifier;
            $siteSettings = $this->getSiteSettings($identifier, $configuration);
            $configuration['contentSecurityPolicies'] = $this->getContentSecurityPolicies($identifier);

            $rootPageId = (int)($configuration['rootPageId'] ?? 0);
            if ($rootPageId > 0) {
                $sites[$identifier] = new Site($identifier, $rootPageId, $configuration, $siteSettings);
            }
        }
        $this->firstLevelCache = $sites;
        return $sites;
    }

    /**
     * Resolve all site objects which have been found in the filesystem containing settings only from the `config.yaml`
     * file ignoring values from the `settings.yaml` and `csp.yaml` file.
     *
     * @return Site[]
     * @internal Not part of public API. Used as intermediate solution until settings are handled by a dedicated GUI.
     */
    public function resolveAllExistingSitesRaw(): array
    {
        $sites = [];
        $siteConfiguration = $this->getAllSiteConfigurationFromFiles(false);
        foreach ($siteConfiguration as $identifier => $configuration) {
            // cast $identifier to string, as the identifier can potentially only consist of (int) digit numbers
            $identifier = (string)$identifier;
            $siteSettings = new SiteSettings($configuration['settings'] ?? []);

            $rootPageId = (int)($configuration['rootPageId'] ?? 0);
            if ($rootPageId > 0) {
                $sites[$identifier] = new Site($identifier, $rootPageId, $configuration, $siteSettings);
            }
        }
        return $sites;
    }

    /**
     * Returns an array of paths in which a site configuration is found.
     *
     * @internal
     */
    public function getAllSiteConfigurationPaths(): array
    {
        $finder = new Finder();
        $paths = [];
        try {
            $finder->files()->depth(0)->name($this->configFileName)->in($this->configPath . '/*');
        } catch (\InvalidArgumentException $e) {
            $finder = [];
        }

        foreach ($finder as $fileInfo) {
            $path = $fileInfo->getPath();
            $paths[basename($path)] = $path;
        }
        return $paths;
    }

    /**
     * Read the site configuration from config files.
     *
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
            $event = $this->eventDispatcher->dispatch(new SiteConfigurationLoadedEvent($identifier, $configuration));
            $siteConfiguration[$identifier] = $event->getConfiguration();
        }
        $this->cache->set($this->cacheIdentifier, 'return ' . var_export($siteConfiguration, true) . ';');

        return $siteConfiguration;
    }

    /**
     * Load plain configuration without additional settings.
     *
     * This method should only be used in case the original configuration as it exists in the file should be loaded,
     * for example for writing / editing configuration.
     *
     * All read related actions should be performed on the site entity.
     *
     * @param string $siteIdentifier
     */
    public function load(string $siteIdentifier): array
    {
        $fileName = $this->configPath . '/' . $siteIdentifier . '/' . $this->configFileName;
        $loader = GeneralUtility::makeInstance(YamlFileLoader::class);
        return $loader->load(GeneralUtility::fixWindowsFilePath($fileName), YamlFileLoader::PROCESS_IMPORTS);
    }

    /**
     * Fetch the settings for a specific site and return the parsed Site Settings object.
     *
     * @todo This method resolves placeholders during the loading, which is okay as this is only used in context where
     *       the replacement is needed. However, this may change in the future, for example if loading is needed for
     *       implementing a GUI for the settings - which should either get a dedicated method or a flag to control if
     *       placeholder should be resolved during yaml file loading or not. The SiteConfiguration save action currently
     *       avoid calling this method.
     */
    protected function getSiteSettings(string $siteIdentifier, array $siteConfiguration): SiteSettings
    {
        $fileName = $this->configPath . '/' . $siteIdentifier . '/' . $this->settingsFileName;
        if (file_exists($fileName)) {
            $loader = GeneralUtility::makeInstance(YamlFileLoader::class);
            $settings = $loader->load(GeneralUtility::fixWindowsFilePath($fileName));
        } else {
            $settings = $siteConfiguration['settings'] ?? [];
        }
        return new SiteSettings($settings);
    }

    protected function getContentSecurityPolicies(string $siteIdentifier): array
    {
        $fileName = $this->configPath . '/' . $siteIdentifier . '/' . $this->contentSecurityFileName;
        if (file_exists($fileName)) {
            $loader = GeneralUtility::makeInstance(YamlFileLoader::class);
            return $loader->load(GeneralUtility::fixWindowsFilePath($fileName), YamlFileLoader::PROCESS_IMPORTS);
        }
        return [];
    }

    public function writeSettings(string $siteIdentifier, array $settings): void
    {
        $fileName = $this->configPath . '/' . $siteIdentifier . '/' . $this->settingsFileName;
        $yamlFileContents = Yaml::dump($settings, 99, 2);
        if (!GeneralUtility::writeFile($fileName, $yamlFileContents)) {
            throw new SiteConfigurationWriteException('Unable to write site settings in sites/' . $siteIdentifier . '/' . $this->configFileName, 1590487411);
        }
    }

    /**
     * Add or update a site configuration
     *
     * @param bool $protectPlaceholders whether to disallow introducing new placeholders
     * @todo enforce $protectPlaceholders with TYPO3 v13.0
     * @throws SiteConfigurationWriteException
     */
    public function write(string $siteIdentifier, array $configuration, bool $protectPlaceholders = false): void
    {
        $folder = $this->configPath . '/' . $siteIdentifier;
        $fileName = $folder . '/' . $this->configFileName;
        $newConfiguration = $configuration;
        if (!file_exists($folder)) {
            GeneralUtility::mkdir_deep($folder);
            if ($protectPlaceholders && $newConfiguration !== []) {
                $newConfiguration = $this->protectPlaceholders([], $newConfiguration);
            }
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
            if ($protectPlaceholders && $newModified !== []) {
                $newModified = $this->protectPlaceholders($newConfiguration, $newModified);
            }
            // change _only_ the modified keys, leave the original non-changed areas alone
            ArrayUtility::mergeRecursiveWithOverrule($newConfiguration, $newModified);
        }
        $event = $this->eventDispatcher->dispatch(new SiteConfigurationBeforeWriteEvent($siteIdentifier, $newConfiguration));
        $newConfiguration = $this->sortConfiguration($event->getConfiguration());
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
     * Detects placeholders that have been introduced and handles* them.
     * (*) currently throws an exception, but could be purged or escaped as well
     *
     * @param array<string, mixed> $existingConfiguration
     * @param array<string, mixed> $modifiedConfiguration
     * @return array<string, mixed> sanitized configuration (currently not used, exception thrown before)
     * @throws SiteConfigurationWriteException
     */
    protected function protectPlaceholders(array $existingConfiguration, array $modifiedConfiguration): array
    {
        try {
            return GeneralUtility::makeInstance(YamlPlaceholderGuard::class, $existingConfiguration)
                ->process($modifiedConfiguration);
        } catch (YamlPlaceholderException $exception) {
            throw new SiteConfigurationWriteException($exception->getMessage(), 1670361271, $exception);
        }
    }

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
