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
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Service\FormDefinitionMigrationService;

/**
 * Extend the ExtbaseConfigurationManager to read YAML configurations.
 *
 * YAML files are discovered automatically from every active extension's
 * Configuration/Form/<SetName>/ directory (see {@see \TYPO3\CMS\Form\DependencyInjection\FormYamlCollectorConfigurator}).
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
        private FormDefinitionMigrationService $migrationService,
        private FormYamlCollector $formYamlCollector,
    ) {}

    /**
     * Load and parse YAML files for the current rendering context.
     *
     * Files are resolved via auto-discovery: {@see FormYamlCollector} scans every
     * active extension's Configuration/Form/<SetName>/ directory and returns
     * all paths sorted by priority.
     *
     * Legacy TypoScript-based paths from {@code yamlConfigurations} are still
     * honoured during the deprecation period (TYPO3 v14.2–v15.0) and merged
     * after the auto-discovered paths.
     *
     * The following post-processing steps are applied to the merged configuration:
     *
     * * Resolve all declared inheritances
     * * Remove all keys whose values are NULL
     * * Sort by array keys if all keys within a nesting level are numerical
     * * Resolve possible TypoScript settings in FE mode
     */
    public function getYamlConfiguration(array $typoScriptSettings, bool $isFrontend, ?ServerRequestInterface $request = null): array
    {
        $yamlSettingsFilePaths = $this->formYamlCollector->getPaths();
        if (isset($typoScriptSettings['yamlConfigurations']) && $typoScriptSettings['yamlConfigurations'] !== []) {
            trigger_error(
                'TypoScript-based registration of form YAML files via plugin.tx_form.settings.yamlConfigurations'
                . ' or module.tx_form.settings.yamlConfigurations has been deprecated in TYPO3 v14.2 and will'
                . ' be removed in TYPO3 v15.0. Use the auto-discovery directory convention'
                . ' EXT:my_extension/Configuration/Form/<SetName>/config.yaml instead.',
                E_USER_DEPRECATED
            );
            $legacyPaths = ArrayUtility::sortArrayWithIntegerKeys($typoScriptSettings['yamlConfigurations']);
            $legacyPaths = array_filter($legacyPaths, static fn(string $path): bool => !in_array($path, $yamlSettingsFilePaths, true));
            $yamlSettingsFilePaths = array_merge($yamlSettingsFilePaths, array_values($legacyPaths));
        }
        $cacheKey = strtolower('YamlSettings_form' . md5(json_encode($yamlSettingsFilePaths)));
        if ($this->cache->has($cacheKey)) {
            $yamlSettings = $this->cache->get($cacheKey);
        } else {
            $yamlSettings = InheritancesResolverService::create($this->yamlSource->load($yamlSettingsFilePaths))->getResolvedConfiguration();
            $yamlSettings = ArrayUtility::removeNullValuesRecursive($yamlSettings);
            $yamlSettings = ArrayUtility::sortArrayWithIntegerKeysRecursive($yamlSettings);
            $yamlSettings = $this->migrationService->migrate($yamlSettings);
            $this->cache->set($cacheKey, $yamlSettings);
        }

        $this->applySiteSettingsOverrides($yamlSettings, $request);

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

    /**
     * Read form template/translation site set settings from the current site
     * and merge them into the YAML configuration.
     * Non-empty values are added at key 20 so they overlay the base paths (key 10)
     * while still allowing higher-priority overrides from form sets or yamlSettingsOverrides.
     */
    private function applySiteSettingsOverrides(array &$yamlSettings, ?ServerRequestInterface $request): void
    {
        $site = $request?->getAttribute('site');
        if (!$site instanceof Site) {
            return;
        }

        $siteSettings = $site->getSettings();
        $renderingOptionsOverrides = [];

        $templateRootPath = (string)$siteSettings->get('form.templates.templateRootPath', '');
        if ($templateRootPath !== '') {
            $renderingOptionsOverrides['templateRootPaths'][20] = $templateRootPath;
        }

        $partialRootPath = (string)$siteSettings->get('form.templates.partialRootPath', '');
        if ($partialRootPath !== '') {
            $renderingOptionsOverrides['partialRootPaths'][20] = $partialRootPath;
        }

        $layoutRootPath = (string)$siteSettings->get('form.templates.layoutRootPath', '');
        if ($layoutRootPath !== '') {
            $renderingOptionsOverrides['layoutRootPaths'][20] = $layoutRootPath;
        }

        $translationFile = (string)$siteSettings->get('form.translation.translationFile', '');
        if ($translationFile !== '') {
            $renderingOptionsOverrides['translation']['translationFiles'][20] = $translationFile;
        }

        if ($renderingOptionsOverrides !== []) {
            $overrides = [
                'prototypes' => [
                    'standard' => [
                        'formElementsDefinition' => [
                            'Form' => [
                                'renderingOptions' => $renderingOptionsOverrides,
                            ],
                        ],
                    ],
                ],
            ];
            ArrayUtility::mergeRecursiveWithOverrule($yamlSettings, $overrides);
        }
    }
}
