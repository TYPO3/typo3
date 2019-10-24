<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Configuration;

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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
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
    protected $cacheIdentifier = 'site-configuration';

    /**
     * Cache stores all configuration as Site objects, as long as they haven't been changed.
     * This drastically improves performance as SiteFinder utilizes SiteConfiguration heavily
     *
     * @var array|null
     */
    protected $firstLevelCache;

    /**
     * @param string $configPath
     */
    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
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
     * Resolve all site objects which have been found in the filesystem.
     *
     * @return Site[]
     */
    public function resolveAllExistingSites(): array
    {
        $sites = [];
        $siteConfiguration = $this->getAllSiteConfigurationFromFiles();
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
     * @return array
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function getAllSiteConfigurationFromFiles(): array
    {
        // Check if the data is already cached
        if ($siteConfiguration = $this->getCache()->get($this->cacheIdentifier)) {
            // Due to the nature of PhpFrontend, the `<?php` and `#` wraps have to be removed
            $siteConfiguration = preg_replace('/^<\?php\s*|\s*#$/', '', $siteConfiguration);
            $siteConfiguration = json_decode($siteConfiguration, true);
        }

        // Nothing in the cache (or no site found)
        if (empty($siteConfiguration)) {
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
                if (isset($configuration['site'])) {
                    trigger_error(
                        'Site configuration with key \'site\' has been deprecated, remove indentation level and site key.',
                        E_USER_DEPRECATED
                    );
                    $configuration = $configuration['site'];
                }
                $siteConfiguration[$identifier] = $configuration;
            }
            $this->getCache()->set($this->cacheIdentifier, json_encode($siteConfiguration));
        }
        return $siteConfiguration ?? [];
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
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
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
        GeneralUtility::writeFile($fileName, $yamlFileContents);
        $this->firstLevelCache = null;
        $this->getCache()->remove($this->cacheIdentifier);
        $this->getCache()->remove('pseudo-sites');
    }

    /**
     * Renames a site identifier (and moves the folder)
     *
     * @param string $currentIdentifier
     * @param string $newIdentifier
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    public function rename(string $currentIdentifier, string $newIdentifier): void
    {
        $result = rename($this->configPath . '/' . $currentIdentifier, $this->configPath . '/' . $newIdentifier);
        if (!$result) {
            throw new \RuntimeException('Unable to rename folder sites/' . $currentIdentifier, 1522491300);
        }
        $this->getCache()->remove($this->cacheIdentifier);
        $this->firstLevelCache = null;
    }

    /**
     * Removes the config.yaml file of a site configuration.
     * Also clears the cache.
     *
     * @param string $siteIdentifier
     * @throws SiteNotFoundException
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
        @unlink($fileName);
        $this->getCache()->remove($this->cacheIdentifier);
        $this->getCache()->remove('pseudo-sites');
        $this->firstLevelCache = null;
    }

    /**
     * Short-hand function for the cache
     *
     * @return FrontendInterface
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function getCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_core');
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
}
