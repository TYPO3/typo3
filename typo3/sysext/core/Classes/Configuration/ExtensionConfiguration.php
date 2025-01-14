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

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * API to get() instance specific extension configuration options.
 *
 * Extension authors are encouraged to use this API - it is currently a simple
 * wrapper to access TYPO3_CONF_VARS['EXTENSIONS'] but could later become something
 * different in case core decides to store extension configuration elsewhere.
 *
 * Extension authors must not access TYPO3_CONF_VARS['EXTENSIONS'] on their own.
 *
 * Extension configurations are often 'feature flags' currently defined by
 * ext_conf_template.txt files. The core (more specifically the install tool)
 * takes care default values and overridden values are properly prepared upon
 * loading or updating an extension.
 *
 * Note only ->get() is official API and other public methods are low level
 * core internal API that is usually only used by extension manager and install tool.
 */
#[AsAlias('extension-configuration', public: true)]
class ExtensionConfiguration
{
    /**
     * Get a single configuration value, a sub array or the whole configuration.
     *
     * Examples:
     * // Simple and typical usage: Get a single config value, or an array if the key is a "TypoScript"
     * // a-like sub-path in ext_conf_template.txt "foo.bar = defaultValue"
     * ->get('myExtension', 'aConfigKey');
     *
     * // Get all current configuration values, always an array
     * ->get('myExtension');
     *
     * // Get a nested config value if the path is a "TypoScript" a-like sub-path
     * // in ext_conf_template.txt "topLevelKey.subLevelKey = defaultValue"
     * ->get('myExtension', 'topLevelKey/subLevelKey')
     *
     * Notes:
     * - If a configuration or configuration path of an extension is not found, the
     *   code tries to synchronize configuration with ext_conf_template.txt first, only
     *   if still not found, it will throw exceptions.
     * - Return values are NOT type safe: A boolean false could be returned as string 0.
     *   Cast accordingly.
     * - This API throws exceptions if the path does not exist or the extension
     *   configuration is not available. The install tool takes care any new
     *   ext_conf_template.txt values are available TYPO3_CONF_VARS['EXTENSIONS'],
     *   a thrown exception indicates a programming error on developer side
     *   and should not be caught.
     * - It is not checked if the extension in question is loaded at all,
     *   it's just checked the extension configuration path exists.
     * - Extensions should typically not get configuration of a different extension.
     *
     * @param string $extension Extension name
     * @param string $path Configuration path - e.g. "featureCategory/coolThingIsEnabled"
     * @return mixed The value. Can be a sub array or a single value.
     * @throws ExtensionConfigurationExtensionNotConfiguredException If the extension configuration does not exist
     * @throws ExtensionConfigurationPathDoesNotExistException If a requested path in the extension configuration does not exist
     */
    public function get(string $extension, string $path = ''): mixed
    {
        $hasBeenSynchronized = false;
        if (!$this->hasConfiguration($extension)) {
            // This if() should not be hit at "casual" runtime, but only in early setup phases
            $this->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions(true);
            $hasBeenSynchronized = true;
            if (!$this->hasConfiguration($extension)) {
                // If there is still no such entry, even after sync -> throw
                throw new ExtensionConfigurationExtensionNotConfiguredException(
                    'No extension configuration for extension ' . $extension . ' found. Either this extension'
                    . ' has no extension configuration or the configuration is not up to date. Execute the'
                    . ' install tool to update configuration.',
                    1509654728
                );
            }
        }
        if (empty($path)) {
            return $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension];
        }
        if (!ArrayUtility::isValidPath($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'], $extension . '/' . $path)) {
            // This if() should not be hit at "casual" runtime, but only in early setup phases
            if (!$hasBeenSynchronized) {
                $this->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions(true);
            }
            // If there is still no such entry, even after sync -> throw
            if (!ArrayUtility::isValidPath($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'], $extension . '/' . $path)) {
                throw new ExtensionConfigurationPathDoesNotExistException(
                    'Path ' . $path . ' does not exist in extension configuration',
                    1509977699
                );
            }
        }
        return ArrayUtility::getValueByPath($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'], $extension . '/' . $path);
    }

    /**
     * Store a new or overwrite an existing configuration value.
     *
     * This is typically used by core internal low level tasks like the install
     * tool but may become handy if an extension needs to update extension configuration
     * on the fly for whatever reason.
     *
     * Examples:
     * // Set a full extension configuration ($value could be a nested array, too)
     * ->set('myExtension', ['aFeature' => 'true', 'aCustomClass' => 'css-foo'])
     *
     * // Unset a whole extension configuration
     * ->set('myExtension')
     *
     * Notes:
     * - Do NOT call this at arbitrary places during runtime (eg. NOT in ext_localconf.php or
     *   similar). ->set() is not supposed to be called each request since it writes LocalConfiguration
     *   each time. This API is however OK to be called from extension manager hooks.
     * - Values are not type safe, if the install tool wrote them,
     *   boolean true could become string 1 on ->get()
     * - It is not possible to store 'null' as value, giving $value=null
     *   or no value at all will unset the path
     * - Setting a value and calling ->get() afterwards will still return the new value.
     * - Warning on system/additional.php: If this file overwrites settings, it spoils the
     *   ->set() call and values may not end up as expected.
     *
     * @param string $extension Extension name
     * @param mixed|null $value The value. If null, unset the path
     * @internal
     */
    public function set(string $extension, mixed $value = null): void
    {
        if (empty($extension)) {
            throw new \RuntimeException('extension name must not be empty', 1509715852);
        }
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        if ($value === null) {
            // Remove whole extension config
            $configurationManager->removeLocalConfigurationKeysByPath(['EXTENSIONS/' . $extension]);
            if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension])) {
                unset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension]);
            }
        } else {
            // Set full extension config
            $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS/' . $extension, $value);
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension] = $value;
        }
    }

    /**
     * Set new configuration of all extensions and reload TYPO3_CONF_VARS.
     * This is a "do all" variant of set() for all extensions that prevents
     * writing and loading system/settings.php many times.
     *
     * @param array $configuration Configuration of all extensions
     * @internal
     */
    public function setAll(array $configuration, bool $skipWriteIfLocalConfigurationDoesNotExist = false): void
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        if ($skipWriteIfLocalConfigurationDoesNotExist === false || @file_exists($configurationManager->getSystemConfigurationFileLocation())) {
            $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS', $configuration);
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] = $configuration;
    }

    /**
     * If there are new config settings in ext_conf_template of an extension,
     * they are found here and synchronized to LocalConfiguration['EXTENSIONS'].
     *
     * Used when entering the install tool, during installation and if calling ->get()
     * with an extension or path that is not yet found in LocalConfiguration
     *
     * @internal
     */
    public function synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions(bool $skipWriteIfLocalConfigurationDoesNotExist = false): void
    {
        $activePackages = GeneralUtility::makeInstance(PackageManager::class)->getActivePackages();
        $fullConfiguration = [];
        $currentLocalConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] ?? [];
        foreach ($activePackages as $package) {
            if (!@is_file($package->getPackagePath() . 'ext_conf_template.txt')) {
                continue;
            }
            $extensionKey = $package->getPackageKey();
            $currentExtensionConfig = $currentLocalConfiguration[$extensionKey] ?? [];
            $extConfTemplateConfiguration = $this->getExtConfTablesWithoutCommentsAsNestedArrayWithoutDots($extensionKey);
            ArrayUtility::mergeRecursiveWithOverrule($extConfTemplateConfiguration, $currentExtensionConfig);
            if (!empty($extConfTemplateConfiguration)) {
                $fullConfiguration[$extensionKey] = $extConfTemplateConfiguration;
            }
        }
        // Write new config if changed. Loose array comparison to not write if only array key order is different
        if ($fullConfiguration != $currentLocalConfiguration) {
            $this->setAll($fullConfiguration, $skipWriteIfLocalConfigurationDoesNotExist);
        }
    }

    /**
     * Read values from ext_conf_template, verify if they are in LocalConfiguration.php
     * already and if not, add them.
     *
     * Used public by extension manager when updating extension
     *
     * @internal
     */
    public function synchronizeExtConfTemplateWithLocalConfiguration(string $extensionKey): void
    {
        $package = GeneralUtility::makeInstance(PackageManager::class)->getPackage($extensionKey);
        if (!@is_file($package->getPackagePath() . 'ext_conf_template.txt')) {
            return;
        }
        $currentLocalConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extensionKey] ?? [];
        $extConfTemplateConfiguration = $this->getExtConfTablesWithoutCommentsAsNestedArrayWithoutDots($extensionKey);
        ArrayUtility::mergeRecursiveWithOverrule($extConfTemplateConfiguration, $currentLocalConfiguration);
        // Write new config if changed. Loose array comparison to not write if only array key order is different
        if ($extConfTemplateConfiguration != $currentLocalConfiguration) {
            $this->set($extensionKey, $extConfTemplateConfiguration);
        }
    }

    /**
     * Helper method of ext_conf_template.txt parsing.
     *
     * Poor man version of getDefaultConfigurationFromExtConfTemplateAsValuedArray() which ignores
     * comments and returns ext_conf_template as array where nested keys have no dots.
     */
    protected function getExtConfTablesWithoutCommentsAsNestedArrayWithoutDots(string $extensionKey): array
    {
        $rawConfigurationString = $this->getDefaultConfigurationRawString($extensionKey);
        $typoScriptStringFactory = GeneralUtility::makeInstance(TypoScriptStringFactory::class);
        $typoScriptTree = $typoScriptStringFactory->parseFromString($rawConfigurationString, new AstBuilder(new NoopEventDispatcher()));
        return GeneralUtility::removeDotsFromTS($typoScriptTree->toArray());
    }

    /**
     * Helper method of ext_conf_template.txt parsing.
     *
     * Return content of an extensions' ext_conf_template.txt file if
     * the file exists, empty string if file does not exist.
     */
    protected function getDefaultConfigurationRawString(string $extensionKey): string
    {
        $rawString = '';
        $extConfTemplateFileLocation = GeneralUtility::getFileAbsFileName(
            'EXT:' . $extensionKey . '/ext_conf_template.txt'
        );
        if (file_exists($extConfTemplateFileLocation)) {
            $rawString = (string)file_get_contents($extConfTemplateFileLocation);
        }
        return $rawString;
    }

    protected function hasConfiguration(string $extension): bool
    {
        return isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension]) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension]);
    }
}
