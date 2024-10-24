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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationChangedEvent;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationLoadedEvent;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\Entity\SiteTSconfig;
use TYPO3\CMS\Core\Site\Entity\SiteTypoScript;
use TYPO3\CMS\Core\Site\Set\SetError;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Site\SiteSettingsFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Responsibility: Handles the format of the configuration (currently yaml), and the location of the file system folder
 *
 * Reads all available site configuration options, and puts them into Site objects.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
class SiteConfiguration
{
    /**
     * Config yaml file name.
     */
    private const CONFIG_FILE_NAME = 'config.yaml';

    /**
     * File naming containing TypoScript Setup.
     */
    private const TYPOSCRIPT_SETUP_FILE_NAME = 'setup.typoscript';

    /**
     * File naming containing TypoScript Constants.
     */
    private const TYPOSCRIPT_CONSTANTS_FILE_NAME = 'constants.typoscript';

    /**
     * File naming containing page TSconfig definitions
     */
    private const PAGE_TSCONFIG_FILE_NAME = 'page.tsconfig';

    /**
     * YAML file name with all settings related to Content-Security-Policies.
     */
    private const CONTENT_SECURITY_FILE_NAME = 'csp.yaml';

    /**
     * Identifier to store all configuration data in the core cache.
     */
    private const CACHE_IDENTIFIER = 'sites-configuration';

    public function __construct(
        #[Autowire('%env(TYPO3:configPath)%/sites')]
        protected string $configPath,
        protected SiteSettingsFactory $siteSettingsFactory,
        protected SetRegistry $setRegistry,
        protected EventDispatcherInterface $eventDispatcher,
        #[Autowire(service: 'cache.core')]
        protected PhpFrontend $cache,
        private readonly YamlFileLoader $yamlFileLoader,
        #[Autowire(service: 'cache.runtime')]
        protected readonly FrontendInterface $runtimeCache,
    ) {}

    /**
     * Return all site objects which have been found in the filesystem.
     *
     * @return Site[]
     */
    public function getAllExistingSites(bool $useCache = true): array
    {
        if ($useCache && $this->runtimeCache->has(self::CACHE_IDENTIFIER)) {
            return $this->runtimeCache->get(self::CACHE_IDENTIFIER);
        }
        return $this->resolveAllExistingSites($useCache);
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
            $siteSettings = $this->siteSettingsFactory->getSettings($identifier, $configuration);
            $siteTypoScript = $this->getSiteTypoScript($identifier);
            $siteTSconfig = $this->getSiteTSconfig($identifier);
            $configuration['contentSecurityPolicies'] = $this->getContentSecurityPolicies($identifier);

            $rootPageId = (int)($configuration['rootPageId'] ?? 0);
            if ($rootPageId > 0) {
                $site = new Site($identifier, $rootPageId, $configuration, $siteSettings, $siteTypoScript, $siteTSconfig);
                $this->determineInvalidSets($site);
                $sites[$identifier] = $site;

            }
        }
        $this->runtimeCache->set(self::CACHE_IDENTIFIER, $sites);
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
            $inlineSettings = $configuration['settings'] ?? [];
            $siteSettings = SiteSettings::createFromSettingsTree($inlineSettings);
            $siteTypoScript = $this->getSiteTypoScript($identifier);

            $rootPageId = (int)($configuration['rootPageId'] ?? 0);
            if ($rootPageId > 0) {
                $site = new Site($identifier, $rootPageId, $configuration, $siteSettings, $siteTypoScript);
                $this->determineInvalidSets($site);
                $sites[$identifier] = $site;
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
            $finder->files()->depth(0)->name(self::CONFIG_FILE_NAME)->in($this->configPath . '/*');
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
        $siteConfiguration = $useCache ? $this->cache->require(self::CACHE_IDENTIFIER) : false;
        if ($siteConfiguration !== false) {
            return $siteConfiguration;
        }
        $finder = new Finder();
        try {
            $finder->files()->depth(0)->name(self::CONFIG_FILE_NAME)->in($this->configPath . '/*');
        } catch (\InvalidArgumentException $e) {
            // Directory $this->configPath does not exist yet
            $finder = [];
        }
        $siteConfiguration = [];
        foreach ($finder as $fileInfo) {
            $configuration = $this->yamlFileLoader->load(GeneralUtility::fixWindowsFilePath((string)$fileInfo));
            $identifier = basename($fileInfo->getPath());
            $event = $this->eventDispatcher->dispatch(new SiteConfigurationLoadedEvent($identifier, $configuration));
            $siteConfiguration[$identifier] = $event->getConfiguration();
        }
        $this->cache->set(self::CACHE_IDENTIFIER, 'return ' . var_export($siteConfiguration, true) . ';');

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
        $fileName = $this->configPath . '/' . $siteIdentifier . '/' . self::CONFIG_FILE_NAME;
        return $this->yamlFileLoader->load(GeneralUtility::fixWindowsFilePath($fileName), YamlFileLoader::PROCESS_IMPORTS);
    }

    protected function getSiteTypoScript(string $siteIdentifier): ?SiteTypoScript
    {
        $data = [
            'setup' => self::TYPOSCRIPT_SETUP_FILE_NAME,
            'constants' => self::TYPOSCRIPT_CONSTANTS_FILE_NAME,
        ];
        $definitions = [];
        foreach ($data as $type => $fileName) {
            $path = $this->configPath . '/' . $siteIdentifier . '/' . $fileName;
            if (file_exists($path)) {
                $contents = @file_get_contents(GeneralUtility::fixWindowsFilePath($path));
                if ($contents !== false) {
                    $definitions[$type] = $contents;
                }
            }
        }
        if ($definitions === []) {
            return null;
        }
        return new SiteTypoScript(...$definitions);
    }

    protected function getSiteTSconfig(string $siteIdentifier): ?SiteTSconfig
    {
        $pageTSconfig = null;
        $path = $this->configPath . '/' . $siteIdentifier . '/' . self::PAGE_TSCONFIG_FILE_NAME;
        if (file_exists($path)) {
            $contents = @file_get_contents(GeneralUtility::fixWindowsFilePath($path));
            if ($contents !== false) {
                $pageTSconfig = $contents;
            }
        }
        if ($pageTSconfig === null) {
            return null;
        }

        return new SiteTSconfig(
            pageTSconfig: $pageTSconfig
        );
    }

    protected function getContentSecurityPolicies(string $siteIdentifier): array
    {
        $fileName = $this->configPath . '/' . $siteIdentifier . '/' . self::CONTENT_SECURITY_FILE_NAME;
        if (file_exists($fileName)) {
            return $this->yamlFileLoader->load(GeneralUtility::fixWindowsFilePath($fileName));
        }
        return [];
    }

    protected function determineInvalidSets(Site $site): void
    {
        $site->invalidSets = array_filter(
            $this->setRegistry->getInvalidSets(),
            static fn($setName) => in_array($setName, $site->getSets(), true),
            ARRAY_FILTER_USE_KEY
        );
        foreach ($site->getSets() as $set) {
            if (!$this->setRegistry->hasSet($set) && !isset($site->invalidSets[$set])) {
                $site->invalidSets[$set] = [
                    'name' => $set,
                    'error' => SetError::notFound,
                    'context' => 'site:' . $site->getIdentifier(),
                ];
            }
        }
    }

    #[AsEventListener(event: SiteConfigurationChangedEvent::class)]
    public function siteConfigurationChanged(): void
    {
        $this->cache->remove(self::CACHE_IDENTIFIER);
        $this->runtimeCache->remove(self::CACHE_IDENTIFIER);
    }

    #[AsEventListener('typo3-core/site-configuration')]
    public function warmupCaches(CacheWarmupEvent $event): void
    {
        if ($event->hasGroup('system')) {
            $this->getAllSiteConfigurationFromFiles(false);
        }
    }
}
