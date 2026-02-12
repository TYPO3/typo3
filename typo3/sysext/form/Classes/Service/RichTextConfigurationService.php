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

namespace TYPO3\CMS\Form\Service;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Service to resolve RTE configuration for the form editor.
 *
 * This service prepares CKEditor 5 configuration for use in the TYPO3 Form Editor backend.
 * It loads configuration from global TYPO3 RTE presets, processes external plugins,
 * and transforms the configuration to be compatible with the form editor context.
 *
 * Similar to RichTextElement, but adapted for the form editor's specific requirements.
 *
 * @internal
 */
class RichTextConfigurationService
{
    public function __construct(
        private readonly Richtext $richtext,
        private readonly RteHtmlParser $rteHtmlParser,
        private readonly SystemResourcePublisherInterface $resourcePublisher,
        private readonly SystemResourceFactory $systemResourceFactory,
        private readonly UriBuilder $uriBuilder,
    ) {}

    /**
     * Resolves and prepares CKEditor configuration for the form editor.
     *
     * This method loads the specified RTE preset from TYPO3's global configuration,
     * processes it, and returns a configuration array ready for use with CKEditor 5.
     *
     * @param string $presetName Name of the RTE preset (e.g., 'form-label', 'form-content')
     * @return array The processed CKEditor configuration, or empty array if rte_ckeditor is not loaded
     */
    public function resolveCkEditorConfiguration(string $presetName = 'form-label'): array
    {
        // Check if rte_ckeditor extension is loaded
        if (!ExtensionManagementUtility::isLoaded('rte_ckeditor')) {
            return [];
        }

        $richtextConfiguration = $this->loadRichtextConfiguration($presetName);
        return $this->prepareConfigurationForEditor($richtextConfiguration);
    }

    /**
     * Resolves the processing configuration (proc.) for HTML transformations.
     *
     * This method loads the RTE preset and returns the processing configuration
     * that can be used with RteHtmlParser for HTML transformations.
     *
     * Note: Unlike resolveCkEditorConfiguration(), this method does NOT check for rte_ckeditor
     * because RteHtmlParser is part of the Core and works independently of the editor.
     *
     * @param string $presetName Name of the RTE preset (e.g., 'form-label', 'form-content')
     * @return array The processing configuration array
     */
    public function resolveProcessingConfiguration(string $presetName = 'form-label'): array
    {

        $richtextConfiguration = $this->loadRichtextConfiguration($presetName);
        return $richtextConfiguration['proc.'] ?? [];
    }

    /**
     * Transforms HTML content from RTE format for database persistence.
     *
     * @param string $htmlContent The HTML content from the RTE editor
     * @param string $presetName Name of the RTE preset to use for transformation rules
     * @return string The transformed HTML ready for database storage
     */
    public function transformTextForPersistence(string $htmlContent, string $presetName = 'form-label'): string
    {
        $processingConfiguration = $this->resolveProcessingConfiguration($presetName);
        return $this->rteHtmlParser->transformTextForPersistence($htmlContent, $processingConfiguration);
    }

    /**
     * Transforms HTML content from database format for RTE display.
     *
     * @param string $htmlContent The HTML content from the database
     * @param string $presetName Name of the RTE preset to use for transformation rules
     * @return string The transformed HTML ready for the RTE editor
     */
    public function transformTextForRichTextEditor(string $htmlContent, string $presetName = 'form-label'): string
    {
        $processingConfiguration = $this->resolveProcessingConfiguration($presetName);

        return $this->rteHtmlParser->transformTextForRichTextEditor($htmlContent, $processingConfiguration);
    }

    /**
     * Loads the full RTE configuration from the preset.
     *
     * @param string $presetName Name of the RTE preset
     * @return array The full richtext configuration
     */
    private function loadRichtextConfiguration(string $presetName): array
    {
        // Load RTE configuration from TYPO3's global preset system
        // We use dummy values since we're in the form editor context without a specific record
        return $this->richtext->getConfiguration(
            'tx_form_dummy',
            'dummy_field',
            0,
            '',
            ['richtextConfiguration' => $presetName]
        );
    }

    /**
     * Prepares the loaded RTE configuration for the CKEditor.
     *
     * @param array $richtextConfiguration The raw richtext configuration from preset
     * @return array The prepared configuration for CKEditor
     */
    private function prepareConfigurationForEditor(array $richtextConfiguration): array
    {
        $configuration = [
            'customConfig' => '',
            'label' => '',
        ];

        if (is_array($richtextConfiguration['editor']['config'] ?? null)) {
            $configuration = array_replace_recursive($configuration, $richtextConfiguration['editor']['config']);
        }

        $this->processExternalPlugins($richtextConfiguration, $configuration);

        $this->configureLanguage($configuration);

        $configuration = $this->replaceLanguageFileReferences($configuration);
        $configuration = $this->replaceAbsolutePathsToRelativeResourcesPath($configuration);

        if (!isset($configuration['debug'])) {
            $configuration['debug'] = ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] ?? false)
                && Environment::getContext()->isDevelopment();
        }

        return $configuration;
    }

    /**
     * Processes external plugins configuration.
     *
     * External plugins may require additional configuration like route URLs for the link browser.
     * This method handles the transformation of route names to actual URLs.
     *
     * Similar to RichTextElement::getExtraPlugins() and resolveCkEditorConfiguration().
     *
     * @param array $richtextConfiguration The full richtext configuration
     * @param array $configuration The configuration array to modify (passed by reference)
     */
    private function processExternalPlugins(array $richtextConfiguration, array &$configuration): void
    {
        $externalPlugins = $richtextConfiguration['editor']['externalPlugins'] ?? [];

        if ($externalPlugins === []) {
            return;
        }

        foreach ($externalPlugins as $pluginName => $pluginConfig) {
            $configName = $pluginConfig['configName'] ?? $pluginName;

            if (isset($pluginConfig['route'])) {
                $pluginConfig['routeUrl'] = $this->buildPluginRouteUrl($pluginConfig['route']);
            }

            unset($pluginConfig['route'], $pluginConfig['configName'], $pluginConfig['resource']);

            if ($pluginConfig !== []) {
                if (!isset($configuration[$configName])) {
                    $configuration[$configName] = $pluginConfig;
                } elseif (is_array($configuration[$configName])) {
                    $configuration[$configName] = array_replace_recursive(
                        $pluginConfig,
                        $configuration[$configName]
                    );
                }
            }
        }
    }

    /**
     * Builds the route URL for an external plugin.
     *
     * @param string $route The route identifier (e.g., 'rteckeditor_wizard_browse_links')
     * @return string The complete URL for the route
     */
    private function buildPluginRouteUrl(string $route): string
    {
        // Build URL parameters for the route
        // Using dummy values for form editor context as we don't have a specific record
        $urlParameters = [
            'P' => [
                'table' => 'tx_form',
                'uid' => 0,
                'fieldName' => 'form_field',
                'recordType' => '',
                'pid' => 0,
                'richtextConfigurationName' => '',
            ],
        ];

        return (string)$this->uriBuilder->buildUriFromRoute($route, $urlParameters);
    }

    /**
     * Configures language settings for the editor.
     *
     * Sets both UI language (based on backend user preference) and content language.
     * For the form editor context, content language is always set to 'en'.
     *
     * @param array $configuration The configuration array to modify (passed by reference)
     */
    private function configureLanguage(array &$configuration): void
    {
        // Set the UI language of the editor
        if (empty($configuration['language']) ||
            (is_array($configuration['language']) && empty($configuration['language']['ui']))
        ) {
            $userLang = (string)($this->getBackendUser()->user['lang'] ?? 'en');
            $configuration['language']['ui'] = $userLang === 'default' ? 'en' : $userLang;
        } elseif (!is_array($configuration['language'])) {
            // Convert string language config to array format
            $configuration['language'] = [
                'ui' => $configuration['language'],
            ];
        }

        // Set content language to 'en' for form editor (no specific content language context)
        $configuration['language']['content'] = 'en';
    }

    /**
     * Replaces LLL: language references with translated values.
     *
     * Recursively processes the configuration array and translates all language labels.
     *
     * @param array $configuration The configuration to process
     * @return array The configuration with translated labels
     */
    private function replaceLanguageFileReferences(array $configuration): array
    {
        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $configuration[$key] = $this->replaceLanguageFileReferences($value);
            } elseif (is_string($value) && str_starts_with($value, 'LLL:')) {
                $configuration[$key] = $this->getLanguageService()->sL($value);
            }
        }
        return $configuration;
    }

    /**
     * Replaces absolute EXT: paths with relative web paths.
     *
     * Recursively processes the configuration array and converts all EXT: paths
     * to publicly accessible web paths.
     *
     * @param array $configuration The configuration to process
     * @return array The configuration with resolved paths
     */
    private function replaceAbsolutePathsToRelativeResourcesPath(array $configuration): array
    {
        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $configuration[$key] = $this->replaceAbsolutePathsToRelativeResourcesPath($value);
            } elseif (
                is_string($value)
                && $value !== ''
                && PathUtility::isExtensionPath(strtoupper($value), true)
            ) {
                $configuration[$key] = $this->resolveUrlPath($value);
            }
        }
        return $configuration;
    }

    /**
     * Resolves a system resource to an absolute web URL.
     *
     * @param string $value The resource path (e.g., 'EXT:my_ext/Resources/Public/Css/file.css')
     * @return string The public web URL to the resource
     */
    private function resolveUrlPath(string $value): string
    {
        $resource = $this->systemResourceFactory->createPublicResource($value);
        return (string)$this->resourcePublisher->generateUri($resource, null);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
