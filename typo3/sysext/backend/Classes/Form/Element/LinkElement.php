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

namespace TYPO3\CMS\Backend\Form\Element;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Form\Event\ModifyLinkExplanationEvent;
use TYPO3\CMS\Backend\LinkHandler\RecordLinkHandler;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Link element.
 *
 * Shows current link and the link popup.
 */
class LinkElement extends AbstractFormElement
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
     * This will render a single-line link form field, possibly with various control/validation features
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();
        // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
        $resultArray['labelHasBeenHandled'] = true;
        $config = $parameterArray['fieldConf']['config'];

        if (is_array($config['allowedTypes'] ?? false) && $config['allowedTypes'] === []) {
            throw new \RuntimeException(
                'Field "' . $fieldName . '" in table "' . $table . '" of type "link" defines an empty list of allowed link types.',
                1646922484
            );
        }

        $itemValue = $parameterArray['itemFormElValue'];
        $width = $this->formMaxWidth(
            MathUtility::forceIntegerInRange($config['size'] ?? $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth)
        );
        $fieldId = StringUtility::getUniqueId('formengine-input-');
        $renderedLabel = $this->renderLabel($fieldId);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if ($config['readOnly'] ?? false) {
            $html = [];
            $html[] = $renderedLabel;
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-element">';
            $html[] =           '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
            $html[] =               '<input class="form-control" id="' . htmlspecialchars($fieldId) . '" value="' . htmlspecialchars((string)$itemValue) . '" type="text" disabled>';
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        $languageService = $this->getLanguageService();
        $itemName = (string)$parameterArray['itemFormElName'];

        // Always adding "trim".
        $evalList = ['trim'];
        if ($config['nullable'] ?? false) {
            $evalList[] = 'null';
        }

        $attributes = [
            'value' => '',
            'id' => $fieldId,
            'class' => implode(' ', [
                'form-control',
                'form-control-clearable',
                't3js-clearable',
                't3js-form-field-link-input',
                'hidden',
                'hasDefaultValue',
            ]),
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'data-formengine-input-params' => (string)json_encode([
                'field' => $itemName,
                'evalList' => implode(',', $evalList),
            ], JSON_THROW_ON_ERROR),
            'data-formengine-input-name' => $itemName,
        ];

        if (!empty($config['placeholder'])) {
            $attributes['placeholder'] = trim($config['placeholder']);
        }
        if (isset($config['autocomplete'])) {
            $attributes['autocomplete'] = empty($config['autocomplete']) ? 'new-' . $fieldName : 'on';
        }

        $valuePickerHtml = [];
        if (is_array($config['valuePicker']['items'] ?? false)) {
            $valuePickerConfiguration = [
                'mode' => $config['valuePicker']['mode'] ?? 'replace',
                'linked-field' => '[data-formengine-input-name="' . $itemName . '"]',
            ];
            $valuePickerAttributes = array_merge(
                [
                    'class' => 'form-select form-control-adapt',
                ],
                $this->getOnFieldChangeAttrs('change', $parameterArray['fieldChangeFunc'] ?? [])
            );

            $valuePickerHtml[] = '<typo3-formengine-valuepicker ' . GeneralUtility::implodeAttributes($valuePickerConfiguration, true) . '>';
            $valuePickerHtml[] = '<select ' . GeneralUtility::implodeAttributes($valuePickerAttributes, true) . '>';
            $valuePickerHtml[] = '<option></option>';
            foreach ($config['valuePicker']['items'] as $item) {
                $valuePickerHtml[] = '<option value="' . htmlspecialchars($item[1]) . '">' . htmlspecialchars($languageService->sL($item[0])) . '</option>';
            }
            $valuePickerHtml[] = '</select>';
            $valuePickerHtml[] = '</typo3-formengine-valuepicker>';

            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine/field-wizard/value-picker.js');
        }

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        // Manually initialize the "linkPopup" FieldControl configuration, based on the link type specific settings
        $this->initializeLinkPopup($config);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $linkExplanation = $this->getLinkExplanation((string)$itemValue);
        $explanation = htmlspecialchars($linkExplanation['text'] ?? '');
        $toggleButtonTitle = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.toggleLinkExplanation');

        $expansionHtml = [];
        $expansionHtml[] = '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $expansionHtml[] =  '<div class="form-wizards-wrap">';
        $expansionHtml[] =      '<div class="form-wizards-element">';
        $expansionHtml[] =          '<div class="input-group t3js-form-field-link">';
        $expansionHtml[] =              '<span class="t3js-form-field-link-icon input-group-addon">' . ($linkExplanation['icon'] ?? '') . '</span>';
        $expansionHtml[] =              '<input class="form-control t3js-form-field-link-explanation" title="' . $explanation . '" value="' . $explanation . '" readonly>';
        $expansionHtml[] =              '<input type="text" ' . GeneralUtility::implodeAttributes($attributes, true) . ' />';
        $expansionHtml[] =              '<button class="btn btn-default t3js-form-field-link-explanation-toggle" type="button" title="' . htmlspecialchars($toggleButtonTitle) . '">';
        $expansionHtml[] =                  $this->iconFactory->getIcon('actions-version-workspaces-preview-link', Icon::SIZE_SMALL)->render();
        $expansionHtml[] =              '</button>';
        $expansionHtml[] =              '<input type="hidden" name="' . $itemName . '" value="' . htmlspecialchars((string)$itemValue) . '" />';
        $expansionHtml[] =          '</div>';
        $expansionHtml[] =      '</div>';
        if (!empty($valuePickerHtml) || !empty($fieldControlHtml)) {
            $expansionHtml[] =      '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $expansionHtml[] =          '<div class="btn-group">';
            $expansionHtml[] =              implode(LF, $valuePickerHtml);
            $expansionHtml[] =              $fieldControlHtml;
            $expansionHtml[] =          '</div>';
            $expansionHtml[] =      '</div>';
        }
        $expansionHtml[] =      '<div class="form-wizards-items-bottom">';
        $expansionHtml[] =          $linkExplanation['additionalAttributes'] ?? '';
        $expansionHtml[] =          $fieldWizardHtml;
        $expansionHtml[] =      '</div>';
        $expansionHtml[] =  '</div>';
        $expansionHtml[] = '</div>';
        $expansionHtml = implode(LF, $expansionHtml);

        $nullControlNameEscaped = htmlspecialchars('control[active][' . $table . '][' . $this->data['databaseRow']['uid'] . '][' . $fieldName . ']');

        $fullElement = $expansionHtml;
        if ($this->hasNullCheckboxButNoPlaceholder()) {
            $checked = $itemValue !== null ? ' checked="checked"' : '';
            $fullElement = [];
            $fullElement[] = '<div class="t3-form-field-disable"></div>';
            $fullElement[] = '<div class="form-check t3-form-field-eval-null-checkbox">';
            $fullElement[] =     '<input type="hidden" name="' . $nullControlNameEscaped . '" value="0" />';
            $fullElement[] =     '<input type="checkbox" class="form-check-input" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . ' />';
            $fullElement[] =     '<label class="form-check-label" for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.nullCheckbox');
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = $expansionHtml;
            $fullElement = implode(LF, $fullElement);
        } elseif ($this->hasNullCheckboxWithPlaceholder()) {
            $checked = $itemValue !== null ? ' checked="checked"' : '';
            $placeholder = $shortenedPlaceholder = (string)($config['placeholder'] ?? '');
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
            $fullElement = [];
            $fullElement[] = '<div class="form-check t3js-form-field-eval-null-placeholder-checkbox">';
            $fullElement[] =     '<input type="hidden" name="' . $nullControlNameEscaped . '" value="0" />';
            $fullElement[] =     '<input type="checkbox" class="form-check-input" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . ' />';
            $fullElement[] =     '<label class="form-check-label" for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         $overrideLabel;
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-placeholder">';
            $fullElement[] =    '<div class="form-control-wrap" style="max-width:' . $width . 'px">';
            $fullElement[] =        '<input type="text" class="form-control" disabled="disabled" value="' . htmlspecialchars($shortenedPlaceholder) . '" />';
            $fullElement[] =    '</div>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-formfield">';
            $fullElement[] =    $expansionHtml;
            $fullElement[] = '</div>';
            $fullElement = implode(LF, $fullElement);
        }

        $resultArray['html'] = $renderedLabel . '
            <typo3-formengine-element-link class="formengine-field-item t3js-formengine-field-item" recordFieldId="' . htmlspecialchars($fieldId) . '">
                ' . $fieldInformationHtml . '
                ' . $fullElement . '
            </typo3-formengine-element-link>';

        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine/element/link-element.js');

        return $resultArray;
    }

    protected function getLinkExplanation(string $itemValue): array
    {
        if ($itemValue === '') {
            return [];
        }

        $data = ['text' => '', 'icon' => ''];
        $typolinkService = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        $linkParts = $typolinkService->decode($itemValue);
        $linkService = GeneralUtility::makeInstance(LinkService::class);

        try {
            $linkData = $linkService->resolve($linkParts['url']);
        } catch (FileDoesNotExistException|FolderDoesNotExistException|UnknownLinkHandlerException|InvalidPathException $e) {
            return $data;
        }

        // Resolving the TypoLink parts (class, title, params)
        $additionalAttributes = [];
        foreach ($linkParts as $key => $value) {
            if ($key === 'url') {
                continue;
            }
            if ($value) {
                $label = match ((string)$key) {
                    'class' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:class'),
                    'title' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:title'),
                    'additionalParams' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:params'),
                    default => (string)$key
                };
                $additionalAttributes[] = '<span><strong>' . htmlspecialchars($label) . ': </strong> ' . htmlspecialchars($value) . '</span>';
            }
        }

        // Resolve the actual link
        switch ($linkData['type']) {
            case LinkService::TYPE_PAGE:
                $pageRecord = BackendUtility::readPageAccess($linkData['pageuid'] ?? null, '1=1');
                // Is this a real page
                if ($pageRecord['uid'] ?? 0) {
                    $fragmentTitle = '';
                    if (isset($linkData['fragment'])) {
                        if (MathUtility::canBeInterpretedAsInteger($linkData['fragment'])) {
                            $contentElement = BackendUtility::getRecord('tt_content', (int)$linkData['fragment'], '*', 'pid=' . $pageRecord['uid']);
                            if ($contentElement) {
                                $fragmentTitle = BackendUtility::getRecordTitle('tt_content', $contentElement, false, false);
                            }
                        }
                        $fragmentTitle = ' #' . ($fragmentTitle ?: $linkData['fragment']);
                    }
                    $data = [
                        'text' => $pageRecord['_thePathFull'] . '[' . $pageRecord['uid'] . ']' . $fragmentTitle,
                        'icon' => $this->iconFactory->getIconForRecord('pages', $pageRecord, Icon::SIZE_SMALL)->render(),
                    ];
                }
                break;
            case LinkService::TYPE_EMAIL:
                $data = [
                    'text' => $linkData['email'] ?? '',
                    'icon' => $this->iconFactory->getIcon('content-elements-mailform', Icon::SIZE_SMALL)->render(),
                ];
                break;
            case LinkService::TYPE_URL:
                $data = [
                    'text' => $linkData['url'] ?? '',
                    'icon' => $this->iconFactory->getIcon('apps-pagetree-page-shortcut-external', Icon::SIZE_SMALL)->render(),

                ];
                break;
            case LinkService::TYPE_FILE:
                $file = $linkData['file'] ?? null;
                if ($file instanceof File) {
                    $data = [
                        'text' => $file->getPublicUrl(),
                        'icon' => $this->iconFactory->getIconForFileExtension($file->getExtension(), Icon::SIZE_SMALL)->render(),
                    ];
                }
                break;
            case LinkService::TYPE_FOLDER:
                $folder = $linkData['folder'] ?? null;
                if ($folder instanceof Folder) {
                    $data = [
                        'text' => $folder->getPublicUrl(),
                        'icon' => $this->iconFactory->getIcon('apps-filetree-folder-default', Icon::SIZE_SMALL)->render(),
                    ];
                }
                break;
            case LinkService::TYPE_RECORD:
                $table = $this->data['pageTsConfig']['TCEMAIN.']['linkHandler.'][$linkData['identifier'] . '.']['configuration.']['table'] ?? '';
                $record = BackendUtility::getRecord($table, $linkData['uid']);
                if ($record) {
                    $recordTitle = BackendUtility::getRecordTitle($table, $record);
                    $tableTitle = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
                    $data = [
                        'text' => sprintf('%s [%s:%d]', $recordTitle, $tableTitle, $linkData['uid']),
                        'icon' => $this->iconFactory->getIconForRecord($table, $record, Icon::SIZE_SMALL)->render(),
                    ];
                } else {
                    $data = [
                        'text' => sprintf('%s', $linkData['uid']),
                        'icon' => $this->iconFactory->getIcon('tcarecords-' . $table . '-default', Icon::SIZE_SMALL, 'overlay-missing')->render(),
                    ];
                }
                break;
            case LinkService::TYPE_TELEPHONE:
                $telephone = $linkData['telephone'];
                if ($telephone) {
                    $data = [
                        'text' => $telephone,
                        'icon' => $this->iconFactory->getIcon('actions-device-mobile', Icon::SIZE_SMALL)->render(),
                    ];
                }
                break;
            case LinkService::TYPE_UNKNOWN:
                $data = [
                    'text' => $linkData['file'] ?? $linkData['url'] ?? '',
                    'icon' => $this->iconFactory->getIcon('actions-link', Icon::SIZE_SMALL)->render(),
                ];
                break;
            default:
                $data = [
                    'text' => 'not implemented type ' . $linkData['type'],
                    'icon' => '',
                ];
        }

        $data['additionalAttributes'] = '<div class="form-text">' . implode(' - ', $additionalAttributes) . '</div>';

        return GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new ModifyLinkExplanationEvent($data, $linkData, $linkParts, $this->data)
        )->getLinkExplanation();
    }

    /**
     * Initializes the LinkPopup FieldControl by processing the
     * field specific configuration and by creating the necessary
     * options array for the FieldControl.
     */
    protected function initializeLinkPopup(array $fieldConfig): void
    {
        if (!($fieldConfig['appearance']['enableBrowser'] ?? true)) {
            return;
        }

        $options = [];

        if (is_array($fieldConfig['allowedTypes'] ?? null)
            && ($fieldConfig['allowedTypes'][0] ?? '') !== '*'
        ) {
            $options['allowedTypes'] = $this->resolveAllowedTypes($fieldConfig['allowedTypes']);
        }
        if (is_array($fieldConfig['appearance']['allowedOptions'] ?? null)
            && ($fieldConfig['appearance']['allowedOptions'][0] ?? '') !== '*'
        ) {
            $options['allowedOptions'] = $fieldConfig['appearance']['allowedOptions'];
        }
        if (is_array($fieldConfig['appearance']['allowedFileExtensions'] ?? null)
            && ($fieldConfig['appearance']['allowedFileExtensions'][0] ?? '') !== '*'
        ) {
            $options['allowedFileExtensions'] = $fieldConfig['appearance']['allowedFileExtensions'];
        }
        if ($fieldConfig['appearance']['browserTitle'] ?? false) {
            $options['title'] = $fieldConfig['appearance']['browserTitle'];
        }

        // Add the LinkPopup configuration to the field configuration
        $this->data['parameterArray']['fieldConf']['config']['fieldControl']['linkPopup'] = [
            'renderType' => 'linkPopup',
            'options' => $options,
        ];
    }

    /**
     * This method applies further processing to a given allow list
     */
    protected function resolveAllowedTypes(array $allowedTypes): array
    {
        // First, remove duplicate entries
        $allowedTypes = array_unique($allowedTypes);

        // Replace "record" with available record link handlers
        if (in_array('record', $allowedTypes, true)) {
            unset($allowedTypes[(int)array_search('record', $allowedTypes, true)]);
            $allowedTypes = array_merge($allowedTypes, $this->getRecordLinkHandlers());
        }

        // Return the resolves types, while removing duplicate entries
        return array_unique($allowedTypes);
    }

    /**
     * Returns the identifiers of link handlers, using the RecordLinkHandler class
     */
    protected function getRecordLinkHandlers(): array
    {
        return $this->getLinkHandlerIdentifiers(
            array_filter(
                (array)($this->data['pageTsConfig']['TCEMAIN.']['linkHandler.'] ?? []),
                static fn ($handler) => ($handler['handler'] ?? '') === RecordLinkHandler::class
            )
        );
    }

    protected function getLinkHandlerIdentifiers(array $linkHandlers): array
    {
        return array_map(static fn ($handler) => trim($handler, '.'), array_keys($linkHandlers));
    }
}
