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
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationBeforeWriteEvent;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationChangedEvent;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\Loader\Exception\YamlPlaceholderException;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\Loader\YamlPlaceholderGuard;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Writes Site objects into site configuration files.
 *
 * @internal
 */
class SiteWriter
{
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
     * @todo remove, move usages to SiteSettingsFactory
     */
    protected string $settingsFileName = 'settings.yaml';

    /**
     * Identifier to store all configuration data in the core cache.
     *
     * @internal
     */
    protected string $cacheIdentifier = 'sites-configuration';

    public function __construct(
        protected string $configPath,
        protected EventDispatcherInterface $eventDispatcher,
        protected PhpFrontend $cache,
        private YamlFileLoader $yamlFileLoader,
    ) {}

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
            // load without any processing to have the unprocessed base to modify
            $newConfiguration = $this->yamlFileLoader->load(GeneralUtility::fixWindowsFilePath($fileName), 0);
            // load the processed configuration to diff changed values
            $processed = $this->yamlFileLoader->load(GeneralUtility::fixWindowsFilePath($fileName));
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
        $this->cache->remove($this->cacheIdentifier);
        $this->eventDispatcher->dispatch(new SiteConfigurationChangedEvent($siteIdentifier));
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
        $this->eventDispatcher->dispatch(new SiteConfigurationChangedEvent($newIdentifier));
    }

    /**
     * Removes the config.yaml file of a site configuration.
     * Also clears the cache.
     *
     * @throws SiteNotFoundException|SiteConfigurationWriteException
     */
    public function delete(string $siteIdentifier): void
    {
        $fileName = $this->configPath . '/' . $siteIdentifier . '/' . $this->configFileName;
        if (!file_exists($fileName)) {
            throw new SiteNotFoundException('Site configuration file ' . $this->configFileName . ' within the site ' . $siteIdentifier . ' not found.', 1522866184);
        }
        if (!unlink($fileName)) {
            throw new SiteConfigurationWriteException('Unable to delete folder sites/' . $siteIdentifier, 1596462020);
        }
        $this->cache->remove($this->cacheIdentifier);
        $this->eventDispatcher->dispatch(new SiteConfigurationChangedEvent($siteIdentifier));
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
}
