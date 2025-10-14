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

namespace TYPO3\CMS\Form\Mvc\Configuration;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;

/**
 * Extend the ExtbaseConfigurationManager to read YAML configurations.
 *
 * Scope: frontend / backend
 * @internal
 */
#[AsAlias(ConfigurationManagerInterface::class, public: true)]
readonly class ConfigurationManager implements ExtFormConfigurationManagerInterface
{
    public function __construct(
        private YamlSource $yamlSource,
        #[Autowire(service: 'cache.assets')]
        private FrontendInterface $cache,
        private TypoScriptService $typoScriptService,
    ) {}

    /**
     * Load and parse YAML files which are configured within the TypoScript
     * path plugin.tx_extensionkey.settings.yamlConfigurations
     *
     * The following steps will be done:
     *
     * * Convert each singe YAML file into an array
     * * merge this arrays together
     * * resolve all declared inheritances
     * * remove all keys if their values are NULL
     * * return all configuration paths within TYPO3.CMS
     * * sort by array keys, if all keys within the current nesting level are numerical keys
     * * resolve possible TypoScript settings in FE mode
     */
    public function getYamlConfiguration(array $typoScriptSettings, bool $isFrontend, ?ServerRequestInterface $request = null): array
    {
        $yamlSettingsFilePaths = isset($typoScriptSettings['yamlConfigurations'])
            ? ArrayUtility::sortArrayWithIntegerKeys($typoScriptSettings['yamlConfigurations'])
            : [];
        $cacheKey = strtolower('YamlSettings_form' . md5(json_encode($yamlSettingsFilePaths)));
        if ($this->cache->has($cacheKey)) {
            $yamlSettings = $this->cache->get($cacheKey);
        } else {
            $yamlSettings = InheritancesResolverService::create($this->yamlSource->load($yamlSettingsFilePaths))->getResolvedConfiguration();
            $yamlSettings = ArrayUtility::removeNullValuesRecursive($yamlSettings);
            $yamlSettings = ArrayUtility::sortArrayWithIntegerKeysRecursive($yamlSettings);
            $this->cache->set($cacheKey, $yamlSettings);
        }
        if (is_array($typoScriptSettings['yamlSettingsOverrides'] ?? null) && !empty($typoScriptSettings['yamlSettingsOverrides'])) {
            $yamlSettingsOverrides = $typoScriptSettings['yamlSettingsOverrides'];
            if ($isFrontend) {
                if ($request === null) {
                    throw new \RuntimeException('Frontend rendering an ext:form requires the request being hand over', 1760451538);
                }
                $yamlSettingsOverrides = $this->typoScriptService->resolvePossibleTypoScriptConfiguration($yamlSettingsOverrides, $request);
            }
            ArrayUtility::mergeRecursiveWithOverrule($yamlSettings, $yamlSettingsOverrides);
        }
        return $yamlSettings;
    }
}
