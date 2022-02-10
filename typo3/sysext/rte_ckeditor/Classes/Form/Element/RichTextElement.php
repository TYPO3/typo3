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

namespace TYPO3\CMS\RteCKEditor\Form\Element;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\RteCKEditor\Form\Element\Event\AfterGetExternalPluginsEvent;
use TYPO3\CMS\RteCKEditor\Form\Element\Event\AfterPrepareConfigurationForEditorEvent;
use TYPO3\CMS\RteCKEditor\Form\Element\Event\BeforeGetExternalPluginsEvent;
use TYPO3\CMS\RteCKEditor\Form\Element\Event\BeforePrepareConfigurationForEditorEvent;

/**
 * Render rich text editor in FormEngine
 * @internal This is a specific Backend FormEngine implementation and is not considered part of the Public TYPO3 API.
 */
class RichTextElement extends AbstractFormElement
{
    /**
     * Default field information enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

    /**
     * Default field wizards enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldWizard = [
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
        ],
        'otherLanguageContent' => [
            'renderType' => 'otherLanguageContent',
            'after' => [
                'localizationStateSelector',
            ],
        ],
        'defaultLanguageDifferences' => [
            'renderType' => 'defaultLanguageDifferences',
            'after' => [
                'otherLanguageContent',
            ],
        ],
    ];

    /**
     * This property contains configuration related to the RTE
     * But only the .editor configuration part
     *
     * @var array
     */
    protected $rteConfiguration = [];

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Container objects give $nodeFactory down to other containers.
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     * @param EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(NodeFactory $nodeFactory, array $data, EventDispatcherInterface $eventDispatcher = null)
    {
        parent::__construct($nodeFactory, $data);
        $this->eventDispatcher = $eventDispatcher ?? GeneralUtility::makeInstance(EventDispatcherInterface::class);
    }

    /**
     * Renders the ckeditor element
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];

        $fieldId = $this->sanitizeFieldId($parameterArray['itemFormElName']);
        $itemFormElementName = $this->data['parameterArray']['itemFormElName'];

        $value = $this->data['parameterArray']['itemFormElValue'] ?? '';

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $attributes = [
            'style' => 'display:none',
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'id' => $fieldId,
            'name' => htmlspecialchars($itemFormElementName),
        ];

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-element">';
        $html[] =               '<textarea ' . GeneralUtility::implodeAttributes($attributes, true) . '>';
        $html[] =                   htmlspecialchars($value);
        $html[] =               '</textarea>';
        $html[] =           '</div>';
        if (!empty($fieldControlHtml)) {
            $html[] =           '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $html[] =               '<div class="btn-group">';
            $html[] =                   $fieldControlHtml;
            $html[] =               '</div>';
            $html[] =           '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-items-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);

        $this->rteConfiguration = $config['richtextConfiguration']['editor'] ?? [];
        $resultArray['requireJsModules'][] = $this->loadCkEditorRequireJsModule($fieldId);

        return $resultArray;
    }

    /**
     * Determine the contents language iso code
     *
     * @return string
     */
    protected function getLanguageIsoCodeOfContent(): string
    {
        $currentLanguageUid = ($this->data['databaseRow']['sys_language_uid'] ?? 0);
        if (is_array($currentLanguageUid)) {
            $currentLanguageUid = $currentLanguageUid[0];
        }
        $contentLanguageUid = (int)max($currentLanguageUid, 0);
        if ($contentLanguageUid) {
            // the language rows might not be fully inialized, so we fallback to en_US in this case
            $contentLanguage = $this->data['systemLanguageRows'][$currentLanguageUid]['iso'] ?? 'en_US';
        } else {
            $contentLanguage = $this->rteConfiguration['config']['defaultContentLanguage'] ?? 'en_US';
        }
        $languageCodeParts = explode('_', $contentLanguage);
        $contentLanguage = strtolower($languageCodeParts[0]) . (!empty($languageCodeParts[1]) ? '_' . strtoupper($languageCodeParts[1]) : '');
        // Find the configured language in the list of localization locales
        $locales = GeneralUtility::makeInstance(Locales::class);
        // If not found, default to 'en'
        if (!in_array($contentLanguage, $locales->getLocales(), true)) {
            $contentLanguage = 'en';
        }
        return $contentLanguage;
    }

    /**
     * Gets the JavaScript code for CKEditor module
     * Compiles the configuration, and then adds plugins
     *
     * @param string $fieldId
     * @return JavaScriptModuleInstruction
     */
    protected function loadCkEditorRequireJsModule(string $fieldId): JavaScriptModuleInstruction
    {
        $configuration = $this->prepareConfigurationForEditor();

        $externalPlugins = [];
        foreach ($this->getExtraPlugins() as $extraPluginName => $extraPluginConfig) {
            $configName = $extraPluginConfig['configName'] ?? $extraPluginName;
            if (!empty($extraPluginConfig['config']) && is_array($extraPluginConfig['config'])) {
                if (empty($configuration[$configName])) {
                    $configuration[$configName] = $extraPluginConfig['config'];
                } elseif (is_array($configuration[$configName])) {
                    $configuration[$configName] = array_replace_recursive($extraPluginConfig['config'], $configuration[$configName]);
                }
            }
            $configuration['extraPlugins'] = ($configuration['extraPlugins'] ?? '') . ',' . $extraPluginName;
            if (isset($this->data['parameterArray']['fieldConf']['config']['placeholder'])) {
                $configuration['editorplaceholder'] = (string)$this->data['parameterArray']['fieldConf']['config']['placeholder'];
            }

            $externalPlugins[] = [
                'name' => $extraPluginName,
                'resource' => $extraPluginConfig['resource'],
            ];
        }

        // Make a hash of the configuration and append it to CKEDITOR.timestamp
        // This will mitigate browser caching issue when plugins are updated
        $configurationHash = md5((string)json_encode($configuration));
        return JavaScriptModuleInstruction::create('@typo3/rte-ckeditor/form-engine-initializer.js', 'FormEngineInitializer')
            ->invoke('initializeCKEditor', [
                'fieldId' => $fieldId,
                'configuration' => $configuration,
                'configurationHash' => $configurationHash,
                'externalPlugins' => $externalPlugins,
            ]);
    }

    /**
     * Get configuration of external/additional plugins
     *
     * @return array
     */
    protected function getExtraPlugins(): array
    {
        $externalPlugins = $this->rteConfiguration['externalPlugins'] ?? [];
        $externalPlugins = $this->eventDispatcher
            ->dispatch(new BeforeGetExternalPluginsEvent($externalPlugins, $this->data))
            ->getConfiguration();

        $urlParameters = [
            'P' => [
                'table'      => $this->data['tableName'],
                'uid'        => $this->data['databaseRow']['uid'],
                'fieldName'  => $this->data['fieldName'],
                'recordType' => $this->data['recordTypeValue'],
                'pid'        => $this->data['effectivePid'],
                'richtextConfigurationName' => $this->data['parameterArray']['fieldConf']['config']['richtextConfigurationName'],
            ],
        ];

        $pluginConfiguration = [];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        foreach ($externalPlugins as $pluginName => $configuration) {
            $pluginConfiguration[$pluginName] = [
                'configName' => $configuration['configName'] ?? $pluginName,
                'resource' => $this->resolveUrlPath($configuration['resource']),
            ];
            unset($configuration['configName']);
            unset($configuration['resource']);

            if ($configuration['route'] ?? null) {
                $configuration['routeUrl'] = (string)$uriBuilder->buildUriFromRoute($configuration['route'], $urlParameters);
            }

            $pluginConfiguration[$pluginName]['config'] = $configuration;
        }

        $pluginConfiguration = $this->eventDispatcher
            ->dispatch(new AfterGetExternalPluginsEvent($pluginConfiguration, $this->data))
            ->getConfiguration();
        return $pluginConfiguration;
    }

    /**
     * Add configuration to replace LLL: references with the translated value
     * @param array $configuration
     *
     * @return array
     */
    protected function replaceLanguageFileReferences(array $configuration): array
    {
        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $configuration[$key] = $this->replaceLanguageFileReferences($value);
            } elseif (is_string($value) && stripos($value, 'LLL:') === 0) {
                $configuration[$key] = $this->getLanguageService()->sL($value);
            }
        }
        return $configuration;
    }

    /**
     * Add configuration to replace absolute EXT: paths with relative ones
     * @param array $configuration
     *
     * @return array
     */
    protected function replaceAbsolutePathsToRelativeResourcesPath(array $configuration): array
    {
        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $configuration[$key] = $this->replaceAbsolutePathsToRelativeResourcesPath($value);
            } elseif (is_string($value) && PathUtility::isExtensionPath(strtoupper($value))) {
                $configuration[$key] = $this->resolveUrlPath($value);
            }
        }
        return $configuration;
    }

    /**
     * Resolves an EXT: syntax file to an absolute web URL
     *
     * @param string $value
     * @return string
     */
    protected function resolveUrlPath(string $value): string
    {
        return PathUtility::getPublicResourceWebPath($value);
    }

    /**
     * Compiles the configuration set from the outside
     * to have it easily injected into the CKEditor.
     *
     * @return array the configuration
     */
    protected function prepareConfigurationForEditor(): array
    {
        // Ensure custom config is empty so nothing additional is loaded
        // Of course this can be overridden by the editor configuration below
        $configuration = [
            'customConfig' => '',
        ];

        if (is_array($this->rteConfiguration['config'] ?? null)) {
            $configuration = array_replace_recursive($configuration, $this->rteConfiguration['config']);
        }

        $configuration = $this->eventDispatcher
            ->dispatch(new BeforePrepareConfigurationForEditorEvent($configuration, $this->data))
            ->getConfiguration();

        // Set the UI language of the editor if not hard-coded by the existing configuration
        if (empty($configuration['language'])) {
            $userLang = (string)($this->getBackendUser()->user['lang'] ?: 'en');
            $configuration['language'] = $userLang === 'default' ? 'en' : $userLang;
        }
        $configuration['contentsLanguage'] = $this->getLanguageIsoCodeOfContent();

        // Replace all label references
        $configuration = $this->replaceLanguageFileReferences($configuration);
        // Replace all paths
        $configuration = $this->replaceAbsolutePathsToRelativeResourcesPath($configuration);

        // there are some places where we define an array, but it needs to be a list in order to work
        if (is_array($configuration['extraPlugins'] ?? null)) {
            $configuration['extraPlugins'] = implode(',', $configuration['extraPlugins']);
        }
        if (is_array($configuration['removePlugins'] ?? null)) {
            $configuration['removePlugins'] = implode(',', $configuration['removePlugins']);
        }
        if (is_array($configuration['removeButtons'] ?? null)) {
            $configuration['removeButtons'] = implode(',', $configuration['removeButtons']);
        }

        $configuration = $this->eventDispatcher
            ->dispatch(new AfterPrepareConfigurationForEditorEvent($configuration, $this->data))
            ->getConfiguration();

        return $configuration;
    }

    /**
     * @param string $itemFormElementName
     * @return string
     */
    protected function sanitizeFieldId(string $itemFormElementName): string
    {
        $fieldId = (string)preg_replace('/[^a-zA-Z0-9_:.-]/', '_', $itemFormElementName);
        return htmlspecialchars((string)preg_replace('/^[^a-zA-Z]/', 'x', $fieldId));
    }
}
