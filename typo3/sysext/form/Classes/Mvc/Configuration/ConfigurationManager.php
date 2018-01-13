<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Mvc\Configuration;

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
use TYPO3\CMS\Core\Configuration\Loader\FalYamlFileLoader;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader\Configuration;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager as ExtbaseConfigurationManager;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\ExtensionNameRequiredException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoConfigurationFoundException;

/**
 * Extend the ExtbaseConfigurationManager to read YAML configurations.
 *
 * Scope: frontend / backend
 * @internal
 */
class ConfigurationManager extends ExtbaseConfigurationManager implements ConfigurationManagerInterface
{

    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected $cache;

    /**
     * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
     * @param string $extensionName if specified, the configuration for the given extension will be returned.
     * @param string $pluginName if specified, the configuration for the given plugin will be returned.
     * @return array The configuration
     * @internal
     */
    public function getConfiguration($configurationType, $extensionName = null, $pluginName = null)
    {
        switch ($configurationType) {
            case self::CONFIGURATION_TYPE_YAML_SETTINGS:
                return $this->getConfigurationFromYamlFile($extensionName);
            default:
                return parent::getConfiguration($configurationType, $extensionName, $pluginName);
        }
    }

    /**
     * Load and parse a YAML configuration which is configured within
     * plugin.tx_form.settings.configurationFile
     *
     * The following steps will be done:
     *
     * * load a YAML file into an array
     * * resolve all declared inheritances
     * * convert all boolean strings ('true' / 'false') into boolean values
     * * remove all keys if their values are NULL
     * * return all configuration paths within TYPO3.CMS
     * * sort by array keys, if all keys within the current nesting level are numerical keys
     * * resolve possible TypoScript settings in FE mode
     *
     * @param string $extensionName
     * @return array
     * @throws ExtensionNameRequiredException
     * @throws NoConfigurationFoundException
     */
    protected function getConfigurationFromYamlFile(string $extensionName): array
    {
        if (empty($extensionName)) {
            throw new ExtensionNameRequiredException(
                'Please specify an extension key to load a YAML configuration',
                1471473377
            );
        }
        $ucFirstExtensioName = ucfirst($extensionName);

        $typoscriptSettings = $this->getTypoScriptSettings($extensionName);

        $cacheKeySuffix = $extensionName;
        if (isset($typoscriptSettings['configurationFile'])) {
            $cacheKeySuffix .= md5($typoscriptSettings['configurationFile']);
        } elseif (isset($typoscriptSettings['yamlConfigurations'])) {
            $cacheKeySuffix .= md5(json_encode($typoscriptSettings['yamlConfigurations']));
        }

        $yamlSettings = $this->getYamlSettingsFromCache($cacheKeySuffix);
        if (!empty($yamlSettings)) {
            return $this->overrideConfigurationByTypoScript($yamlSettings, $extensionName);
        }

        $configuration = $this->objectManager->get(Configuration::class)
            ->setMergeLists(false);
        if (isset($typoscriptSettings['configurationFile'])) {
            $yamlSettings = $this->objectManager->get(FalYamlFileLoader::class, $configuration)
                ->load($typoscriptSettings['configurationFile']);
        } elseif (isset($typoscriptSettings['yamlConfigurations'])) {
            trigger_error('EXT:form configuration registration via "<module|plugin>.tx_form.settings.yamlConfigurations" has been deprecated in v9 and will be removed in v10. Use "<module|plugin>.tx_form.settings.configurationFile" instead.', E_USER_DEPRECATED);
            $yamlContent = $this->generateYamlFromLegacyYamlConfigurations(
                $typoscriptSettings['yamlConfigurations']
            );

            $yamlSettings = $this->objectManager->get(YamlFileLoader::class, $configuration)
                ->loadFromContent($yamlContent);
        } else {
            throw new NoConfigurationFoundException(
                'No YAML configurations could be found for extension ' . $extensionName,
                1471473378
            );
        }

        $yamlSettings = ArrayUtility::convertBooleanStringsToBooleanRecursive($yamlSettings);
        $yamlSettings = ArrayUtility::removeNullValuesRecursive($yamlSettings);
        $yamlSettings = InheritancesResolverService::create($yamlSettings)
            ->getResolvedConfiguration();

        $yamlSettings = is_array($yamlSettings['TYPO3']['CMS'][$ucFirstExtensioName])
            ? $yamlSettings['TYPO3']['CMS'][$ucFirstExtensioName]
            : [];
        $yamlSettings = ArrayUtility::sortArrayWithIntegerKeysRecursive($yamlSettings);

        $this->setYamlSettingsIntoCache($cacheKeySuffix, $yamlSettings);

        return $this->overrideConfigurationByTypoScript($yamlSettings, $extensionName);
    }

    /**
     * @param array $yamlSettings
     * @param string $extensionName
     * @return array
     */
    protected function overrideConfigurationByTypoScript(array $yamlSettings, string $extensionName): array
    {
        $typoScript = parent::getConfiguration(self::CONFIGURATION_TYPE_SETTINGS, $extensionName);
        if (is_array($typoScript['yamlSettingsOverrides']) && !empty($typoScript['yamlSettingsOverrides'])) {
            ArrayUtility::mergeRecursiveWithOverrule(
                $yamlSettings,
                $typoScript['yamlSettingsOverrides']
            );

            if ($this->environmentService->isEnvironmentInFrontendMode()) {
                $yamlSettings = $this->objectManager->get(TypoScriptService::class)
                    ->resolvePossibleTypoScriptConfiguration($yamlSettings);
            }
        }
        return $yamlSettings;
    }

    /**
     * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected function getCacheFrontend(): FrontendInterface
    {
        if ($this->cache === null) {
            $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('assets');
        }
        return $this->cache;
    }

    /**
     * @param string $cacheKeySuffix
     * @return string
     */
    protected function getConfigurationCacheKey(string $cacheKeySuffix): string
    {
        return strtolower(self::CONFIGURATION_TYPE_YAML_SETTINGS . '_' . $cacheKeySuffix);
    }

    /**
     * @param string $cacheKeySuffix
     * @return mixed
     */
    protected function getYamlSettingsFromCache(string $cacheKeySuffix)
    {
        return $this->getCacheFrontend()->get(
            $this->getConfigurationCacheKey($cacheKeySuffix)
        );
    }

    /**
     * @param string $cacheKeySuffix
     * @param array $yamlSettings
     */
    protected function setYamlSettingsIntoCache(
        string $cacheKeySuffix,
        array $yamlSettings
    ) {
        $this->getCacheFrontend()->set(
            $this->getConfigurationCacheKey($cacheKeySuffix),
            $yamlSettings
        );
    }

    /**
     * @param string $extensionName
     * @return null|[]
     */
    protected function getTypoScriptSettings(string $extensionName)
    {
        return parent::getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            $extensionName
        );
    }

    /**
     * Compatibility layer for the deprecated TypoScript option
     * "plugin.tx_form.settings.yamlConfigurations"
     *
     * @param array $yamlConfigurations
     * @return string
     * @internal
     */
    protected function generateYamlFromLegacyYamlConfigurations(array $yamlConfigurations): string
    {
        $yamlConfigurations = ArrayUtility::sortArrayWithIntegerKeys($yamlConfigurations);
        $yamlContent = 'imports:' . LF;

        $baseExtFormConfigurations = [
            'EXT:form/Configuration/Yaml/BaseSetup.yaml',
            'EXT:form/Configuration/Yaml/FormEditorSetup.yaml',
            'EXT:form/Configuration/Yaml/FormEngineSetup.yaml',
            'EXT:form/Configuration/Yaml/FormSetup.yaml',
        ];

        $imports = '';
        $baseExtFormConfigurationsExists = false;
        foreach ($yamlConfigurations as $yamlConfiguration) {
            if (in_array($yamlConfiguration, $baseExtFormConfigurations)) {
                $baseExtFormConfigurationsExists = true;
            } else {
                $imports .= '  - { resource: "' . $yamlConfiguration . '" }' . LF;
            }
        }

        // We assume that if one of the files defined within $baseExtFormConfigurations exists
        // within plugin.tx_form.settings.yamlConfigurations, someone wants to load
        // the base EXT:form setup files (old or new) and afterwards extend it with his own configuration.
        // In this case, we define the new EXT:form/Configuration/Yaml/FormSetup.yaml file as the
        // first file to import from.
        if ($baseExtFormConfigurationsExists) {
            $yamlContent .= '  - { resource: "EXT:form/Configuration/Yaml/FormSetup.yaml" }' . LF . $imports;
        } else {
            $yamlContent .= $imports;
        }

        return $yamlContent;
    }
}
