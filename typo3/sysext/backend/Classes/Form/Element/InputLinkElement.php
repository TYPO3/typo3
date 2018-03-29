<?php
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Link input element.
 *
 * Shows current link and the link popup.
 */
class InputLinkElement extends AbstractFormElement
{
    /**
     * Default field controls render the link icon
     *
     * @var array
     */
    protected $defaultFieldControl = [
        'linkPopup' => [
            'renderType' => 'linkPopup',
            'options' => []
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
                'localizationStateSelector'
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
     * This will render a single-line input form field, possibly with various control/validation features
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $languageService = $this->getLanguageService();

        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();
        $config = $parameterArray['fieldConf']['config'];

        $itemValue = $parameterArray['itemFormElValue'];
        $evalList = GeneralUtility::trimExplode(',', $config['eval'], true);
        $size = MathUtility::forceIntegerInRange($config['size'] ?? $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);
        $width = (int)$this->formMaxWidth($size);
        $nullControlNameEscaped = htmlspecialchars('control[active][' . $table . '][' . $row['uid'] . '][' . $fieldName . ']');

        if ($config['readOnly']) {
            // Early return for read only fields
            $html = [];
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-element">';
            $html[] =           '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
            $html[] =               '<input class="form-control" value="' . htmlspecialchars($itemValue) . '" type="text" disabled>';
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        // @todo: The whole eval handling is a mess and needs refactoring
        foreach ($evalList as $func) {
            // @todo: This is ugly: The code should find out on it's own whether a eval definition is a
            // @todo: keyword like "date", or a class reference. The global registration could be dropped then
            // Pair hook to the one in \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_input_Eval()
            if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func])) {
                if (class_exists($func)) {
                    $evalObj = GeneralUtility::makeInstance($func);
                    if (method_exists($evalObj, 'deevaluateFieldValue')) {
                        $_params = [
                            'value' => $itemValue
                        ];
                        $itemValue = $evalObj->deevaluateFieldValue($_params);
                    }
                    if (method_exists($evalObj, 'returnFieldJS')) {
                        $resultArray['additionalJavaScriptPost'][] = 'TBE_EDITOR.customEvalFunctions[' . GeneralUtility::quoteJSvalue($func) . ']'
                            . ' = function(value) {' . $evalObj->returnFieldJS() . '};';
                    }
                }
            }
        }

        $attributes = [
            'value' => '',
            'id' => StringUtility::getUniqueId('formengine-input-'),
            'class' => implode(' ', [
                'form-control',
                't3js-clearable',
                't3js-form-field-inputlink-input',
                'hidden',
                'hasDefaultValue',
            ]),
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'data-formengine-input-params' => json_encode([
                'field' => $parameterArray['itemFormElName'],
                'evalList' => implode(',', $evalList)
            ]),
            'data-formengine-input-name' => $parameterArray['itemFormElName'],
        ];

        $maxLength = $config['max'] ?? 0;
        if ((int)$maxLength > 0) {
            $attributes['maxlength'] = (int)$maxLength;
        }
        if (!empty($config['placeholder'])) {
            $attributes['placeholder'] = trim($config['placeholder']);
        }
        if (isset($config['autocomplete'])) {
            $attributes['autocomplete'] = empty($config['autocomplete']) ? 'new-' . $fieldName : 'on';
        }

        $valuePickerHtml = [];
        if (isset($config['valuePicker']['items']) && is_array($config['valuePicker']['items'])) {
            $mode = $config['valuePicker']['mode'] ?? '';
            $itemName = $parameterArray['itemFormElName'];
            $fieldChangeFunc = $parameterArray['fieldChangeFunc'];
            if ($mode === 'append') {
                $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0]'
                    . '.value=\'\'+this.options[this.selectedIndex].value+document.editform[' . GeneralUtility::quoteJSvalue($itemName) . '].value';
            } elseif ($mode === 'prepend') {
                $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0]'
                    . '.value+=\'\'+this.options[this.selectedIndex].value';
            } else {
                $assignValue = 'document.querySelectorAll(' . GeneralUtility::quoteJSvalue('[data-formengine-input-name="' . $itemName . '"]') . ')[0]'
                    . '.value=this.options[this.selectedIndex].value';
            }
            $valuePickerHtml[] = '<select';
            $valuePickerHtml[] =  ' class="form-control tceforms-select tceforms-wizardselect"';
            $valuePickerHtml[] =  ' onchange="' . htmlspecialchars($assignValue . ';this.blur();this.selectedIndex=0;' . implode('', $fieldChangeFunc)) . '"';
            $valuePickerHtml[] = '>';
            $valuePickerHtml[] = '<option></option>';
            foreach ($config['valuePicker']['items'] as $item) {
                $valuePickerHtml[] = '<option value="' . htmlspecialchars($item[1]) . '">' . htmlspecialchars($languageService->sL($item[0])) . '</option>';
            }
            $valuePickerHtml[] = '</select>';
        }

        $legacyWizards = $this->renderWizards();
        $legacyFieldControlHtml = implode(LF, $legacyWizards['fieldControl']);
        $legacyFieldWizardHtml = implode(LF, $legacyWizards['fieldWizard']);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $legacyFieldWizardHtml . $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $legacyFieldControlHtml . $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $linkExplanation = $this->getLinkExplanation($itemValue ?: '');
        $explanation = htmlspecialchars($linkExplanation['text']);
        $toggleButtonTitle = $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:buttons.toggleLinkExplanation');

        $expansionHtml = [];
        $expansionHtml[] = '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $expansionHtml[] =  '<div class="form-wizards-wrap">';
        $expansionHtml[] =      '<div class="form-wizards-element">';
        $expansionHtml[] =          '<div class="input-group t3js-form-field-inputlink">';
        $expansionHtml[] =              '<span class="input-group-addon">' . $linkExplanation['icon'] . '</span>';
        $expansionHtml[] =              '<input class="form-control form-field-inputlink-explanation t3js-form-field-inputlink-explanation" data-toggle="tooltip" data-title="' . $explanation . '" value="' . $explanation . '" readonly>';
        $expansionHtml[] =              '<input type="text"' . GeneralUtility::implodeAttributes($attributes, true) . ' />';
        $expansionHtml[] =              '<span class="input-group-btn">';
        $expansionHtml[] =                  '<button class="btn btn-default t3js-form-field-inputlink-explanation-toggle" type="button" title="' . htmlspecialchars($toggleButtonTitle) . '">';
        $expansionHtml[] =                      $this->iconFactory->getIcon('actions-version-workspaces-preview-link', Icon::SIZE_SMALL)->render();
        $expansionHtml[] =                  '</button>';
        $expansionHtml[] =              '</span>';
        $expansionHtml[] =              '<input type="hidden" name="' . $parameterArray['itemFormElName'] . '" value="' . htmlspecialchars($itemValue) . '" />';
        $expansionHtml[] =          '</div>';
        $expansionHtml[] =      '</div>';
        $expansionHtml[] =      '<div class="form-wizards-items-aside">';
        $expansionHtml[] =          '<div class="btn-group">';
        $expansionHtml[] =              implode(LF, $valuePickerHtml);
        $expansionHtml[] =              $fieldControlHtml;
        $expansionHtml[] =          '</div>';
        $expansionHtml[] =      '</div>';
        $expansionHtml[] =      '<div class="form-wizards-items-bottom">';
        $expansionHtml[] =          $linkExplanation['additionalAttributes'];
        $expansionHtml[] =          $fieldWizardHtml;
        $expansionHtml[] =      '</div>';
        $expansionHtml[] =  '</div>';
        $expansionHtml[] = '</div>';
        $expansionHtml = implode(LF, $expansionHtml);

        $fullElement = $expansionHtml;
        if ($this->hasNullCheckboxButNoPlaceholder()) {
            $checked = $itemValue !== null ? ' checked="checked"' : '';
            $fullElement = [];
            $fullElement[] = '<div class="t3-form-field-disable"></div>';
            $fullElement[] = '<div class="checkbox t3-form-field-eval-null-checkbox">';
            $fullElement[] =     '<label for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         '<input type="hidden" name="' . $nullControlNameEscaped . '" value="0" />';
            $fullElement[] =         '<input type="checkbox" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . ' />';
            $fullElement[] =         $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.nullCheckbox');
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = $expansionHtml;
            $fullElement = implode(LF, $fullElement);
        } elseif ($this->hasNullCheckboxWithPlaceholder()) {
            $checked = $itemValue !== null ? ' checked="checked"' : '';
            $placeholder = $shortenedPlaceholder = $config['placeholder'] ?? '';
            $disabled = '';
            $fallbackValue = 0;
            if (strlen($placeholder) > 0) {
                $shortenedPlaceholder = GeneralUtility::fixed_lgd_cs($placeholder, 20);
                if ($placeholder !== $shortenedPlaceholder) {
                    $overrideLabel = sprintf(
                        $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override'),
                        '<span title="' . htmlspecialchars($placeholder) . '">' . htmlspecialchars($shortenedPlaceholder) . '</span>'
                    );
                } else {
                    $overrideLabel = sprintf(
                        $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override'),
                        htmlspecialchars($placeholder)
                    );
                }
            } else {
                $overrideLabel = $languageService->sL(
                    'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override_not_available'
                );
            }
            $fullElement = [];
            $fullElement[] = '<div class="checkbox t3js-form-field-eval-null-placeholder-checkbox">';
            $fullElement[] =     '<label for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         '<input type="hidden" name="' . $nullControlNameEscaped . '" value="' . $fallbackValue . '" />';
            $fullElement[] =         '<input type="checkbox" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . $disabled . ' />';
            $fullElement[] =         $overrideLabel;
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-placeholder">';
            $fullElement[] =    '<div class="form-control-wrap" style="max-width:' . $width . 'px">';
            $fullElement[] =        '<input type="text" class="form-control" disabled="disabled" value="' . $shortenedPlaceholder . '" />';
            $fullElement[] =    '</div>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-formfield">';
            $fullElement[] =    $expansionHtml;
            $fullElement[] = '</div>';
            $fullElement = implode(LF, $fullElement);
        }

        $resultArray['html'] = '<div class="formengine-field-item t3js-formengine-field-item">' . $fieldInformationHtml . $fullElement . '</div>';
        return $resultArray;
    }

    /**
     * @param string $itemValue
     * @return array
     */
    protected function getLinkExplanation(string $itemValue): array
    {
        if (empty($itemValue)) {
            return [];
        }
        $data = ['text' => '', 'icon' => ''];
        $typolinkService = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        $linkParts = $typolinkService->decode($itemValue);
        $linkService = GeneralUtility::makeInstance(LinkService::class);

        try {
            $linkData = $linkService->resolve($linkParts['url']);
        } catch (FileDoesNotExistException $e) {
            return $data;
        } catch (FolderDoesNotExistException $e) {
            return $data;
        } catch (UnknownLinkHandlerException $e) {
            return $data;
        } catch (InvalidPathException $e) {
            return $data;
        }

        // Resolving the TypoLink parts (class, title, params)
        $additionalAttributes = [];
        foreach ($linkParts as $key => $value) {
            if ($key === 'url') {
                continue;
            }
            if ($value) {
                switch ($key) {
                    case 'class':
                        $label = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_browse_links.xlf:class');
                        break;
                    case 'title':
                        $label = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_browse_links.xlf:title');
                        break;
                    case 'additionalParams':
                        $label = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_browse_links.xlf:params');
                        break;
                    default:
                        $label = $key;
                }

                $additionalAttributes[] = '<span><strong>' . htmlspecialchars($label) . ': </strong> ' . htmlspecialchars($value) . '</span>';
            }
        }

        // Resolve the actual link
        switch ($linkData['type']) {
            case LinkService::TYPE_PAGE:
                $pageRecord = BackendUtility::readPageAccess($linkData['pageuid'], '1=1');
                // Is this a real page
                if ($pageRecord['uid']) {
                    $data = [
                        'text' => $pageRecord['_thePathFull'] . '[' . $pageRecord['uid'] . ']',
                        'icon' => $this->iconFactory->getIconForRecord('pages', $pageRecord, Icon::SIZE_SMALL)->render()
                    ];
                }
                break;
            case LinkService::TYPE_EMAIL:
                $data = [
                    'text' => $linkData['email'],
                    'icon' => $this->iconFactory->getIcon('content-elements-mailform', Icon::SIZE_SMALL)->render()
                ];
                break;
            case LinkService::TYPE_URL:
                $data = [
                    'text' => $this->getDomainByUrl($linkData['url']),
                    'icon' => $this->iconFactory->getIcon('apps-pagetree-page-shortcut-external', Icon::SIZE_SMALL)->render()

                ];
                break;
            case LinkService::TYPE_FILE:
                /** @var File $file */
                $file = $linkData['file'];
                if ($file) {
                    $data = [
                        'text' => $file->getPublicUrl(),
                        'icon' => $this->iconFactory->getIconForFileExtension($file->getExtension(), Icon::SIZE_SMALL)->render()
                    ];
                }
                break;
            case LinkService::TYPE_FOLDER:
                /** @var Folder $folder */
                $folder = $linkData['folder'];
                if ($folder) {
                    $data = [
                        'text' => $folder->getPublicUrl(),
                        'icon' => $this->iconFactory->getIcon('apps-filetree-folder-default', Icon::SIZE_SMALL)->render()
                    ];
                }
                break;
            case LinkService::TYPE_RECORD:
                $table = $this->data['pageTsConfig']['TCEMAIN.']['linkHandler.'][$linkData['identifier'] . '.']['configuration.']['table'];
                $record = BackendUtility::getRecord($table, $linkData['uid']);
                if ($record) {
                    $recordTitle = BackendUtility::getRecordTitle($table, $record);
                    $tableTitle = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
                    $data = [
                        'text' => sprintf('%s [%s:%d]', $recordTitle, $tableTitle, $linkData['uid']),
                        'icon' => $this->iconFactory->getIconForRecord($table, $record, Icon::SIZE_SMALL)->render(),
                    ];
                } else {
                    $icon = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['default'];
                    if (empty($icon)) {
                        $icon = 'tcarecords-' . $table . '-default';
                    }
                    $data = [
                        'text' => sprintf('%s', $linkData['uid']),
                        'icon' => $this->iconFactory->getIcon('tcarecords-' . $table . '-default', Icon::SIZE_SMALL, 'overlay-missing')->render(),
                    ];
                }
                break;
            default:
                // Please note that this hook is preliminary and might change, as this element could become its own
                // TCA type in the future
                if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['linkHandler'][$linkData['type']])) {
                    $linkBuilder = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['linkHandler'][$linkData['type']]);
                    $data = $linkBuilder->getFormData($linkData, $linkParts, $this->data, $this);
                } else {
                    $data = [
                        'text' => 'not implemented type ' . $linkData['type'],
                        'icon' => ''
                    ];
                }
        }

        $data['additionalAttributes'] = '<div class="help-block">' . implode(' - ', $additionalAttributes) . '</div>';
        return $data;
    }

    /**
     * @param string $uriString
     *
     * @return string
     */
    protected function getDomainByUrl(string $uriString): string
    {
        $data = parse_url($uriString);
        return $data['host'] ?? '';
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
