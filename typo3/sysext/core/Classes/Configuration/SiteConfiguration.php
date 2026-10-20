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
use Psr\Log\LoggerInterface;
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
use TYPO3\CMS\Core\Configuration\Loader\Exception\YamlFileLoadingException;
use TYPO3\CMS\Core\Configuration\Loader\Exception\YamlParseException;
use TYPO3\CMS\Core\Configuration\Loader\Exception\YamlPlaceholderException;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Exception\InvalidSiteConfigurationException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\Entity\SiteTSconfig;
use TYPO3\CMS\Core\Site\Entity\SiteTypoScript;
use TYPO3\CMS\Core\Site\Set\SetError;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Site\SiteSettingsFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Responsibility: Handles the format of the configuration (currently yaml), and the location of the file system folder
 *
 * Reads all available site configuration options, and puts them into Site objects.
 *
 * Site configurations that cannot be loaded or turned into a Site object are skipped
 * and collected as invalid sites instead, so a single broken site does not render the
 * whole installation inaccessible. Only errors of the configuration itself are collected,
 * as invalid sites found while loading are persisted in the core cache. Any other
 * exception, for example of an event listener, is not caught.
 *
 * @phpstan-type InvalidSite array{identifier: string, rootPageId: int|null, type: class-string<\Throwable>, code: int, message: string, field: string|null}
 * @internal
 */
#[Autoconfigure(public: true)]
readonly class SiteConfiguration
{
    /**
     * Config yaml file name.
     */
    private const string CONFIG_FILE_NAME = 'config.yaml';

    /**
     * File naming containing TypoScript Setup.
     */
    private const string TYPOSCRIPT_SETUP_FILE_NAME = 'setup.typoscript';

    /**
     * File naming containing TypoScript Constants.
     */
    private const string TYPOSCRIPT_CONSTANTS_FILE_NAME = 'constants.typoscript';

    /**
     * File naming containing page TSconfig definitions
     */
    private const string PAGE_TSCONFIG_FILE_NAME = 'page.tsconfig';

    /**
     * YAML file name with all settings related to Content-Security-Policies.
     */
    private const string CONTENT_SECURITY_FILE_NAME = 'csp.yaml';

    /**
     * Identifier to store all configuration data in the core cache.
     */
    private const string CACHE_IDENTIFIER = 'sites-configuration';

    /**
     * Identifier to store invalid sites in the runtime cache.
     */
    private const string INVALID_SITES_CACHE_IDENTIFIER = 'sites-configuration-invalid';

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
        private readonly LoggerInterface $logger,
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
        return $this->resolveSites($useCache)['sites'];
    }

    /**
     * Read fresh site configurations for validation without reading or writing the site caches.
     *
     * @return array{sites: array<string, Site>, invalidSites: array<string, InvalidSite>}
     */
    public function getSitesForValidation(): array
    {
        return $this->resolveSites(false, false);
    }

    /**
     * Return all sites which could not be resolved, indexed by site identifier.
     *
     * Invalid sites of the most recent resolution in this request are returned.
     * Call getAllExistingSites(false) beforehand, or pass $useCache = false, to
     * evaluate the current state of the configuration files.
     *
     * @return array<string, InvalidSite>
     */
    public function getInvalidSites(bool $useCache = true): array
    {
        if ($useCache && $this->runtimeCache->has(self::INVALID_SITES_CACHE_IDENTIFIER)) {
            return $this->runtimeCache->get(self::INVALID_SITES_CACHE_IDENTIFIER);
        }
        return $this->resolveSites($useCache)['invalidSites'];
    }

    /**
     * @return InvalidSite|null
     */
    public function getInvalidSiteByRootPageId(int $rootPageId): ?array
    {
        foreach ($this->getInvalidSites() as $invalidSite) {
            if ($invalidSite['rootPageId'] === $rootPageId) {
                return $invalidSite;
            }
        }
        return null;
    }

    /**
     * @return array{sites: array<string, Site>, invalidSites: array<string, InvalidSite>}
     */
    private function resolveSites(bool $useCache, bool $writeCache = true): array
    {
        $sites = [];
        $resolvedSiteData = $writeCache
            ? $this->getAllResolvedSiteData($useCache)
            : $this->loadAllResolvedSiteData($useCache, false);
        $invalidSites = $resolvedSiteData['invalidSites'];
        foreach ($resolvedSiteData['sites'] as $identifier => $data) {
            // cast $identifier to string, as the identifier can potentially only consist of (int) digit numbers
            $identifier = (string)$identifier;
            // The Site object is always created at runtime, as the constructor evaluates
            // base variant expressions, which must not be cached persistently.
            try {
                $site = new Site(
                    $identifier,
                    $data['rootPageId'],
                    $data['configuration'],
                    $data['settings'],
                    $data['typoscript'],
                    $data['tsConfig'],
                );
            } catch (\Throwable $e) {
                $invalidSites[$identifier] = $this->createInvalidSite($identifier, $data['rootPageId'], $e);
                continue;
            }
            $site->invalidSets = $data['invalidSets'];
            $sites[$identifier] = $site;
        }
        if ($writeCache) {
            $this->runtimeCache->set(self::CACHE_IDENTIFIER, $sites);
            $this->runtimeCache->set(self::INVALID_SITES_CACHE_IDENTIFIER, $invalidSites);
        }
        return ['sites' => $sites, 'invalidSites' => $invalidSites];
    }

    /**
     * @return InvalidSite
     */
    private function createInvalidSite(string $identifier, ?int $rootPageId, \Throwable $exception): array
    {
        $this->logger->warning('Site configuration "{identifier}" is invalid and has been skipped: {message}', [
            'identifier' => $identifier,
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);
        $cause = $exception;
        $field = null;
        if ($exception instanceof InvalidSiteConfigurationException) {
            $cause = $exception->getPrevious() ?? $exception;
            $field = $exception->configurationPath;
        }
        return [
            'identifier' => $identifier,
            'rootPageId' => $rootPageId,
            'type' => $cause::class,
            'code' => (int)$cause->getCode(),
            'message' => $cause->getMessage(),
            'field' => $field,
        ];
    }

    /**
     * Assemble all data needed to create Site objects: the site configuration enriched
     * with route enhancers and settings from site sets, settings objects, TypoScript
     * and TSconfig read from the file system. This data is cached in the core cache,
     * to avoid set resolution, settings composition and file system access per request.
     * Sites failing to load or resolve are cached as invalid sites.
     *
     * @return array{sites: array, invalidSites: array<string, InvalidSite>}
     */
    protected function getAllResolvedSiteData(bool $useCache): array
    {
        return $this->loadAllResolvedSiteData($useCache, true);
    }

    /**
     * @return array{sites: array, invalidSites: array<string, InvalidSite>}
     */
    private function loadAllResolvedSiteData(bool $useCache, bool $writeCache): array
    {
        if ($useCache) {
            $cacheEntry = $this->cache->require(self::CACHE_IDENTIFIER);
            if (is_array($cacheEntry['resolvedData'] ?? null)) {
                return ['sites' => $cacheEntry['resolvedData'], 'invalidSites' => $cacheEntry['invalidSites'] ?? []];
            }
        }
        $resolvedData = [];
        ['siteConfiguration' => $siteConfiguration, 'invalidSites' => $invalidSites] = $this->loadAllSiteConfigurationFromFiles($useCache, $writeCache);
        foreach ($siteConfiguration as $identifier => $configuration) {
            // cast $identifier to string, as the identifier can potentially only consist of (int) digit numbers
            $identifier = (string)$identifier;
            $rootPageId = (int)($configuration['rootPageId'] ?? 0);
            try {
                if ($rootPageId <= 0) {
                    throw new InvalidSiteConfigurationException(
                        'Site "' . $identifier . '" has no valid root page id configured.',
                        1792506654,
                        'rootPageId'
                    );
                }
                $siteSettings = $useCache
                    ? $this->siteSettingsFactory->getSettings($identifier, $configuration)
                    : $this->siteSettingsFactory->createSettings($configuration['dependencies'] ?? [], $identifier, $configuration['settings'] ?? []);
                $configuration['contentSecurityPolicies'] = $this->getContentSecurityPolicies($identifier);
                $configuration['routeEnhancers'] = ArrayUtility::replaceAndAppendScalarValuesRecursive(
                    $this->getRouteEnhancersFromSets($configuration['dependencies'] ?? []),
                    $configuration['routeEnhancers'] ?? []
                );
                $resolvedData[$identifier] = [
                    'rootPageId' => $rootPageId,
                    'configuration' => $configuration,
                    'settings' => $siteSettings,
                    'typoscript' => $this->getSiteTypoScript($identifier),
                    'tsConfig' => $this->getSiteTSconfig($identifier),
                    'invalidSets' => $this->determineInvalidSets($identifier, $configuration['dependencies'] ?? []),
                ];
            } catch (YamlParseException|YamlFileLoadingException|YamlPlaceholderException|InvalidSiteConfigurationException|\UnexpectedValueException $e) {
                $invalidSites[$identifier] = $this->createInvalidSite($identifier, $rootPageId > 0 ? $rootPageId : null, $e);
            }
        }
        if ($writeCache) {
            $this->writeCache($siteConfiguration, $resolvedData, $invalidSites);
        }
        return ['sites' => $resolvedData, 'invalidSites' => $invalidSites];
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
                try {
                    $site = new Site($identifier, $rootPageId, $configuration, $siteSettings, $siteTypoScript);
                } catch (\Throwable) {
                    // Invalid sites are reported by getInvalidSites()
                    continue;
                }
                $site->invalidSets = $this->determineInvalidSets($identifier, $configuration['dependencies'] ?? []);
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
        return $this->loadAllSiteConfigurationFromFiles($useCache)['siteConfiguration'];
    }

    /**
     * Read the site configuration from config files, collecting files which cannot be loaded as invalid sites.
     *
     * @return array{siteConfiguration: array, invalidSites: array<string, InvalidSite>}
     */
    private function loadAllSiteConfigurationFromFiles(bool $useCache, bool $writeCache = true): array
    {
        // Check if the data is already cached
        if ($useCache) {
            $cacheEntry = $this->cache->require(self::CACHE_IDENTIFIER);
            if (is_array($cacheEntry['siteConfiguration'] ?? null)) {
                return ['siteConfiguration' => $cacheEntry['siteConfiguration'], 'invalidSites' => $cacheEntry['invalidSites'] ?? []];
            }
        }
        $finder = new Finder();
        try {
            $finder->files()->depth(0)->name(self::CONFIG_FILE_NAME)->in($this->configPath . '/*');
        } catch (\InvalidArgumentException $e) {
            // Directory $this->configPath does not exist yet
            $finder = [];
        }
        $siteConfiguration = [];
        $invalidSites = [];
        foreach ($finder as $fileInfo) {
            $identifier = basename($fileInfo->getPath());
            try {
                $configuration = $this->yamlFileLoader->load(
                    GeneralUtility::fixWindowsFilePath((string)$fileInfo),
                    YamlFileLoader::PROCESS_PLACEHOLDERS | YamlFileLoader::PROCESS_IMPORTS | YamlFileLoader::RETHROW_IMPORT_EXCEPTIONS
                );
            } catch (YamlParseException|YamlFileLoadingException|YamlPlaceholderException|\UnexpectedValueException $e) {
                $invalidSites[$identifier] = $this->createInvalidSite($identifier, null, $e);
                continue;
            }
            $event = $this->eventDispatcher->dispatch(new SiteConfigurationLoadedEvent($identifier, $configuration));
            $siteConfiguration[$identifier] = $event->getConfiguration();
        }
        if ($writeCache) {
            $this->writeCache($siteConfiguration, null, $invalidSites);
        }

        return ['siteConfiguration' => $siteConfiguration, 'invalidSites' => $invalidSites];
    }

    /**
     * Persist the raw site configuration and - if available - the resolved site data
     * in one core cache entry, to keep both in sync at all times. Invalid sites
     * belong to the latest stage that has been written.
     *
     * @param array<string, InvalidSite> $invalidSites
     */
    protected function writeCache(array $siteConfiguration, ?array $resolvedData, array $invalidSites = []): void
    {
        $this->cache->set(
            self::CACHE_IDENTIFIER,
            'return ['
            . '\'siteConfiguration\' => ' . var_export($siteConfiguration, true) . ', '
            . '\'resolvedData\' => ' . var_export($resolvedData, true) . ', '
            . '\'invalidSites\' => ' . var_export($invalidSites, true)
            . '];'
        );
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
            return $this->yamlFileLoader->load(
                GeneralUtility::fixWindowsFilePath($fileName),
                YamlFileLoader::PROCESS_PLACEHOLDERS | YamlFileLoader::PROCESS_IMPORTS | YamlFileLoader::RETHROW_IMPORT_EXCEPTIONS
            );
        }
        return [];
    }

    /**
     * Get route enhancers from site sets.
     */
    protected function getRouteEnhancersFromSets(array $dependencies): array
    {
        $routeEnhancers = [];
        $sets = $this->setRegistry->getSets(...$dependencies);
        foreach ($sets as $set) {
            $routeEnhancers = ArrayUtility::replaceAndAppendScalarValuesRecursive(
                $routeEnhancers,
                $set->routeEnhancers
            );
        }
        return $routeEnhancers;
    }

    protected function determineInvalidSets(string $siteIdentifier, array $sets): array
    {
        $invalidSets = array_filter(
            $this->setRegistry->getInvalidSets(),
            static fn($setName) => in_array($setName, $sets, true),
            ARRAY_FILTER_USE_KEY
        );
        foreach ($sets as $set) {
            if (!$this->setRegistry->hasSet($set) && !isset($invalidSets[$set])) {
                $invalidSets[$set] = [
                    'name' => $set,
                    'error' => SetError::notFound,
                    'context' => 'site:' . $siteIdentifier,
                ];
            }
        }
        return $invalidSets;
    }

    #[AsEventListener(event: SiteConfigurationChangedEvent::class)]
    public function siteConfigurationChanged(): void
    {
        $this->cache->remove(self::CACHE_IDENTIFIER);
        $this->runtimeCache->remove(self::CACHE_IDENTIFIER);
        $this->runtimeCache->remove(self::INVALID_SITES_CACHE_IDENTIFIER);
    }

    #[AsEventListener('typo3-core/site-configuration')]
    public function warmupCaches(CacheWarmupEvent $event): void
    {
        if ($event->hasGroup('system')) {
            $this->getAllSiteConfigurationFromFiles(false);
        }
    }
}
