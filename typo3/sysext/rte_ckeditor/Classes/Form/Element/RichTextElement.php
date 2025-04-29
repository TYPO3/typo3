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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\Environment;
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

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UriBuilder $uriBuilder,
        private readonly Locales $locales,
    ) {}

    /**
     * Renders the ckeditor element
     *
     * @throws \InvalidArgumentException
     */
    public function render(): array
    {
        $languageService = $this->getLanguageService();

        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];

        $fieldId = $this->sanitizeFieldId($parameterArray['itemFormElName']);
        $itemFormElementName = $this->data['parameterArray']['itemFormElName'];

        $value = $this->data['parameterArray']['itemFormElValue'] ?? null;

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $this->rteConfiguration = $config['richtextConfiguration']['editor'] ?? [];
        $ckeditorConfiguration = $this->resolveCkEditorConfiguration();

        $ckeditorAttributes = GeneralUtility::implodeAttributes([
            'id' => $fieldId . 'ckeditor5',
            'options' => GeneralUtility::jsonEncodeForHtmlAttribute($ckeditorConfiguration, false),
        ], true);

        $textareaAttributes = GeneralUtility::implodeAttributes([
            'slot' => 'textarea',
            'id' => $fieldId,
            'name' => $itemFormElementName,
            'rows' => '18',
            'class' => 'form-control',
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
        ], true);

        $html = [];
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-item-element">';
        $html[] =               '<typo3-rte-ckeditor-ckeditor5 ' . $ckeditorAttributes . '>';
        $html[] =                 '<textarea ' . $textareaAttributes . '>';
        $html[] =                   htmlspecialchars((string)$value);
        $html[] =                 '</textarea>';
        $html[] =               '</typo3-rte-ckeditor-ckeditor5>';
        $html[] =           '</div>';
        if (!empty($fieldControlHtml)) {
            $html[] =           '<div class="form-wizards-item-aside form-wizards-item-aside--field-control">';
            $html[] =               '<div class="btn-group">';
            $html[] =                   $fieldControlHtml;
            $html[] =               '</div>';
            $html[] =           '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-item-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =       '</div>';
        $html[] =   '</div>';

        $nullControlNameEscaped = htmlspecialchars('control[active][' . $this->data['tableName'] . '][' . $this->data['databaseRow']['uid'] . '][' . $this->data['fieldName'] . ']');

        $fullElement = $html;
        // @todo - The logic for hasNullCheckboxButNoPlaceholder() / hasNullCheckboxWithPlaceholder() wants to be streamlined here;
        // Ideally, a placeholder should only be an instructive placeholder and not conflict with usage of a "default fallback".
        // Instead of "[x] Set value (Default: …)" it might better be to use "[x] Set value (Fallback: …)", because what is shown as "default" here is not really the final value
        // of the saved element, but what is inerhited as fallback values from a possible rendering chain. Looking at you, sys_file_reference IRRE.
        if ($this->hasNullCheckboxButNoPlaceholder()) {
            $checked = $value !== null ? ' checked="checked"' : '';
            $fullElement = [];
            $fullElement[] = '<div class="t3-form-field-disable"></div>';
            $fullElement[] = '<div class="form-check t3-form-field-eval-null-checkbox">';
            $fullElement[] =     '<input type="hidden" name="' . $nullControlNameEscaped . '" value="0" />';
            $fullElement[] =     '<input type="checkbox" class="form-check-input" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . ' />';
            $fullElement[] =     '<label class="form-check-label" for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.nullCheckbox');
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = implode(LF, $html);
        } elseif ($this->hasNullCheckboxWithPlaceholder()) {
            $checked = $value !== null ? ' checked="checked"' : '';
            // Note that we draw the raw placeholder from $config instead of $ckeditorConfiguration so it
            // contains the full HTML markup. $ckeditorConfiguration['placeholder'] has strip_tags() applied.
            // The full HTML is only emitted with htmlspecialchars(), and later parsed by CKEditor.
            // The HTML-stripped placeholder is used for the label of the nullable checkbox.

            $placeholder = trim((string)($ckeditorConfiguration['placeholder'] ?? ''));
            $defaultValue = '';
            $rawPlaceholder = trim((string)($config['placeholder'] ?? ''));
            if ($rawPlaceholder !== '') {
                $defaultValue = $rawPlaceholder;
            }
            if ($placeholder !== '') {
                $shortenedPlaceholder = GeneralUtility::fixed_lgd_cs($placeholder, 20);
                if ($placeholder !== $shortenedPlaceholder) {
                    $overrideLabel = sprintf(
                        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override'),
                        '<span title="' . htmlspecialchars($placeholder) . '">' . htmlspecialchars($shortenedPlaceholder) . '</span>'
                    );
                } else {
                    $overrideLabel = sprintf(
                        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override'),
                        htmlspecialchars($placeholder)
                    );
                }
            } else {
                $overrideLabel = $languageService->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override_not_available'
                );
            }

            $placeholderCkeditorAttributes = GeneralUtility::implodeAttributes([
                'id' => $fieldId . '-placeholder-ckeditor5',
                'options' => GeneralUtility::jsonEncodeForHtmlAttribute([
                    ...$ckeditorConfiguration,
                    'readOnly' => true,
                ], false),
            ], true);

            $placeholderTextareaAttributes = GeneralUtility::implodeAttributes([
                'slot' => 'textarea',
                'id' => $fieldId . '-placeholder',
                'rows' => '18',
                'class' => 'form-control',
            ], true);

            $fullElement = [];
            $fullElement[] = '<div class="form-check t3js-form-field-eval-null-placeholder-checkbox">';
            $fullElement[] =     '<input type="hidden" name="' . $nullControlNameEscaped . '" value="0" />';
            $fullElement[] =     '<input type="checkbox" class="form-check-input" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . ' />';
            $fullElement[] =     '<label class="form-check-label" for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         $overrideLabel;
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-placeholder">';
            $fullElement[] =    '<div class="form-control-wrap">';
            $fullElement[] =        '<typo3-rte-ckeditor-ckeditor5 ' . $placeholderCkeditorAttributes . '>';
            $fullElement[] =            '<textarea ' . $placeholderTextareaAttributes . '>';
            $fullElement[] =                htmlspecialchars($defaultValue);
            $fullElement[] =            '</textarea>';
            $fullElement[] =        '</typo3-rte-ckeditor-ckeditor5>';
            $fullElement[] =    '</div>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-formfield">';
            $fullElement[] =    implode(LF, $html);
            $fullElement[] = '</div>';
        }

        $fullElement = '<div class="formengine-field-item t3js-formengine-field-item">' . implode(LF, $fullElement) . '</div>';

        $resultArray['html'] = $this->wrapWithFieldsetAndLegend($fullElement);
        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/rte-ckeditor/ckeditor5.js');

        $uiLanguage = $ckeditorConfiguration['language']['ui'];
        if ($this->translationExists($uiLanguage)) {
            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/ckeditor5/translations/' . $uiLanguage . '.js');
        }

        $contentLanguage = $ckeditorConfiguration['language']['content'];
        if ($this->translationExists($contentLanguage)) {
            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/ckeditor5/translations/' . $contentLanguage . '.js');
        }

        $resultArray['stylesheetFiles'][] = 'EXT:rte_ckeditor/Resources/Public/Css/editor.css';

        return $resultArray;
    }

    /**
     * Determine the contents language iso code
     */
    protected function getLanguageIsoCodeOfContent(): string
    {
        $currentLanguageUid = ($this->data['databaseRow']['sys_language_uid'] ?? 0);
        if (is_array($currentLanguageUid)) {
            $currentLanguageUid = $currentLanguageUid[0];
        }
        $contentLanguageUid = (int)max($currentLanguageUid, 0);
        if ($contentLanguageUid) {
            // the language rows might not be fully initialized, so we fall back to en-US in this case
            $contentLanguage = $this->data['systemLanguageRows'][$currentLanguageUid]['iso'] ?? 'en-US';
        } else {
            $contentLanguage = $this->rteConfiguration['config']['defaultContentLanguage'] ?? 'en-US';
        }
        $languageCodeParts = explode('_', $contentLanguage);
        $contentLanguage = strtolower($languageCodeParts[0]) . (!empty($languageCodeParts[1]) ? '_' . strtoupper($languageCodeParts[1]) : '');
        // Find the configured language in the list of localization locales, if not found, default to 'en'.
        if ($contentLanguage === 'default' || !$this->locales->isValidLanguageKey($contentLanguage)) {
            $contentLanguage = 'en';
        }
        return $contentLanguage;
    }

    protected function resolveCkEditorConfiguration(): array
    {
        $configuration = $this->prepareConfigurationForEditor();

        foreach ($this->getExtraPlugins() as $extraPluginName => $extraPluginConfig) {
            $configName = $extraPluginConfig['configName'] ?? $extraPluginName;
            if (!empty($extraPluginConfig['config']) && is_array($extraPluginConfig['config'])) {
                if (empty($configuration[$configName])) {
                    $configuration[$configName] = $extraPluginConfig['config'];
                } elseif (is_array($configuration[$configName])) {
                    $configuration[$configName] = array_replace_recursive($extraPluginConfig['config'], $configuration[$configName]);
                }
            }
        }
        if (isset($this->data['parameterArray']['fieldConf']['config']['placeholder'])) {
            // Note that HTML tags are stripped here, because CKEditor does not parse placeholder text.
            // Without it, the HTML code would be displayed as-is.
            $configuration['placeholder'] = strip_tags((string)$this->data['parameterArray']['fieldConf']['config']['placeholder']);
        }
        return $configuration;
    }

    /**
     * Get configuration of external/additional plugins
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
        foreach ($externalPlugins as $pluginName => $configuration) {
            $pluginConfiguration[$pluginName] = [
                'configName' => $configuration['configName'] ?? $pluginName,
            ];
            unset($configuration['configName']);
            // CKEditor4 style config, unused in CKEditor5 and not forwarded to the resutling plugin config
            unset($configuration['resource']);

            if ($configuration['route'] ?? null) {
                $configuration['routeUrl'] = (string)$this->uriBuilder->buildUriFromRoute($configuration['route'], $urlParameters);
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
     */
    protected function replaceLanguageFileReferences(array $configuration): array
    {
        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $configuration[$key] = $this->replaceLanguageFileReferences($value);
            } elseif (is_string($value)) {
                $configuration[$key] = $this->getLanguageService()->sL($value);
            }
        }
        return $configuration;
    }

    /**
     * Add configuration to replace absolute EXT: paths with relative ones
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
     */
    protected function resolveUrlPath(string $value): string
    {
        if (str_contains($value, '?')) {
            return PathUtility::getPublicResourceWebPath($value);
        }
        $value = GeneralUtility::getFileAbsFileName($value);
        $value = GeneralUtility::createVersionNumberedFilename($value);
        return PathUtility::getAbsoluteWebPath($value);

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
            'label' => $this->data['parameterArray']['fieldConf']['label'] ?? '',
        ];

        if ($this->data['parameterArray']['fieldConf']['config']['readOnly'] ?? false) {
            $configuration['readOnly'] = true;
        }

        if (is_array($this->rteConfiguration['config'] ?? null)) {
            $configuration = array_replace_recursive($configuration, $this->rteConfiguration['config']);
        }

        $configuration = $this->eventDispatcher
            ->dispatch(new BeforePrepareConfigurationForEditorEvent($configuration, $this->data))
            ->getConfiguration();

        // Set the UI language of the editor if not hard-coded by the existing configuration
        if (empty($configuration['language']) ||
            (is_array($configuration['language']) && empty($configuration['language']['ui']))
        ) {
            $userLang = (string)($this->getBackendUser()->user['lang'] ?: 'en');
            $configuration['language']['ui'] = $userLang === 'default' ? 'en' : $userLang;
        } elseif (!is_array($configuration['language'])) {
            $configuration['language'] = [
                'ui' => $configuration['language'],
            ];
        }
        $configuration['language']['content'] = $this->getLanguageIsoCodeOfContent();

        // Replace all label references
        $configuration = $this->replaceLanguageFileReferences($configuration);
        // Replace all paths
        $configuration = $this->replaceAbsolutePathsToRelativeResourcesPath($configuration);

        // unless explicitly set, the debug mode is enabled in development context
        if (!isset($configuration['debug'])) {
            $configuration['debug'] = ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] ?? false) && Environment::getContext()->isDevelopment();
        }

        $configuration = $this->eventDispatcher
            ->dispatch(new AfterPrepareConfigurationForEditorEvent($configuration, $this->data))
            ->getConfiguration();

        return $configuration;
    }

    protected function sanitizeFieldId(string $itemFormElementName): string
    {
        $fieldId = (string)preg_replace('/[^a-zA-Z0-9_:-]/', '_', $itemFormElementName);
        return htmlspecialchars((string)preg_replace('/^[^a-zA-Z]/', 'x', $fieldId));
    }

    protected function translationExists(string $language): bool
    {
        $fileName = GeneralUtility::getFileAbsFileName('EXT:rte_ckeditor/Resources/Public/Contrib/translations/' . $language . '.js');
        return file_exists($fileName);
    }
}
