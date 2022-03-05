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

use TYPO3\CMS\Backend\Controller\FormSlugAjaxController;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * General type=input element for TCA Type=Slug with some additional value.
 */
class InputSlugElement extends AbstractFormElement
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
     * This will render a single-line input form field, possibly with various control/validation features
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();

        $languageId = 0;
        if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField']) && !empty($GLOBALS['TCA'][$table]['ctrl']['languageField'])) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            $languageId = (int)((is_array($row[$languageField] ?? null) ? ($row[$languageField][0] ?? 0) : $row[$languageField]) ?? 0);
        }

        $itemValue = $parameterArray['itemFormElValue'];
        $config = $parameterArray['fieldConf']['config'];
        $evalList = GeneralUtility::trimExplode(',', $config['eval'] ?? '', true);
        $size = MathUtility::forceIntegerInRange($config['size'] ?? $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);
        $width = $this->formMaxWidth($size);
        $baseUrl = $this->data['customData'][$this->data['fieldName']]['slugPrefix'] ?? '';

        // Convert UTF-8 characters back (that is important, see Slug class when sanitizing)
        $itemValue = rawurldecode($itemValue);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        // readOnly is not supported as columns config but might be set by SingleFieldContainer in case
        // "l10n_display" is set to "defaultAsReadonly". To prevent misbehaviour for fields, which falsely
        // set this, we also check for "defaultAsReadonly" being set and whether the record is an overlay.
        if (($config['readOnly'] ?? false)
            && ($this->data['processedTca']['ctrl']['transOrigPointerField'] ?? false)
            && ($row[$this->data['processedTca']['ctrl']['transOrigPointerField']][0] ?? $row[$this->data['processedTca']['ctrl']['transOrigPointerField']] ?? false)
            && GeneralUtility::inList($parameterArray['fieldConf']['l10n_display'] ?? '', 'defaultAsReadonly')
        ) {
            $disabledFieldAttributes = [
                'class' => 'form-control',
                'data-formengine-input-name' => $parameterArray['itemFormElName'],
                'type' => 'text',
                'value' => $itemValue,
                'title' => $itemValue,
            ];

            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =     $fieldInformationHtml;
            $html[] =     '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
            $html[] =         '<div class="form-wizards-wrap">';
            $html[] =             '<div class="form-wizards-element">';
            $html[] =                 '<div class="input-group">';
            $html[] =                     ($baseUrl ? '<span class="input-group-addon">' . htmlspecialchars($baseUrl) . '</span>' : '');
            $html[] =                     '<input ' . GeneralUtility::implodeAttributes($disabledFieldAttributes, true) . ' disabled>';
            $html[] =                 '</div>';
            $html[] =             '</div>';
            $html[] =         '</div>';
            $html[] =     '</div>';
            $html[] = '</div>';

            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);
        $toggleButtonTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.toggleSlugExplanation');
        $recreateButtonTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.recreateSlugExplanation');

        $successMessage = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:slugCreation.success.' . ($table === 'pages' ? 'page' : 'record'));
        $errorMessage = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:slugCreation.error');

        $thisSlugId = 't3js-form-field-slug-id' . StringUtility::getUniqueId();
        $mainFieldHtml = [];
        $mainFieldHtml[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $mainFieldHtml[] =  $fieldInformationHtml;
        $mainFieldHtml[] =  '<div class="form-control-wrap" style="max-width: ' . $width . 'px" id="' . htmlspecialchars($thisSlugId) . '">';
        $mainFieldHtml[] =      '<div class="form-wizards-wrap">';
        $mainFieldHtml[] =          '<div class="form-wizards-element">';
        $mainFieldHtml[] =              '<div class="input-group">';
        $mainFieldHtml[] =                  ($baseUrl ? '<span class="input-group-addon">' . htmlspecialchars($baseUrl) . '</span>' : '');
        // We deal with 3 fields here: a readonly field for current / default values, an input
        // field to manipulate the value, and the final hidden field used to send the value
        $mainFieldHtml[] =                  '<input';
        $mainFieldHtml[] =                      ' class="form-control t3js-form-field-slug-readonly"';
        $mainFieldHtml[] =                      ' data-bs-toggle="tooltip"';
        $mainFieldHtml[] =                      ' title="' . htmlspecialchars($itemValue) . '"';
        $mainFieldHtml[] =                      ' value="' . htmlspecialchars($itemValue) . '"';
        $mainFieldHtml[] =                      ' readonly';
        $mainFieldHtml[] =                  ' />';
        $mainFieldHtml[] =                  '<input type="text"';
        $mainFieldHtml[] =                      ' id="' . htmlspecialchars(StringUtility::getUniqueId('formengine-input-')) . '"';
        $mainFieldHtml[] =                      ' class="form-control t3js-form-field-slug-input hidden"';
        $mainFieldHtml[] =                      ' placeholder="' . htmlspecialchars($row['slug'] ?? '/') . '"';
        $mainFieldHtml[] =                      ' data-formengine-validation-rules="' . htmlspecialchars($this->getValidationDataAsJsonString($config)) . '"';
        $mainFieldHtml[] =                      ' data-formengine-input-params="' . htmlspecialchars((string)json_encode(['field' => $parameterArray['itemFormElName'], 'evalList' => implode(',', $evalList)])) . '"';
        $mainFieldHtml[] =                      ' data-formengine-input-name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
        $mainFieldHtml[] =                  ' />';
        $mainFieldHtml[] =                  '<span class="input-group-btn">';
        $mainFieldHtml[] =                      '<button class="btn btn-default t3js-form-field-slug-toggle" type="button" title="' . htmlspecialchars($toggleButtonTitle) . '">';
        $mainFieldHtml[] =                          $this->iconFactory->getIcon('actions-version-workspaces-preview-link', Icon::SIZE_SMALL)->render();
        $mainFieldHtml[] =                      '</button>';
        $mainFieldHtml[] =                      '<button class="btn btn-default t3js-form-field-slug-recreate" type="button" title="' . htmlspecialchars($recreateButtonTitle) . '">';
        $mainFieldHtml[] =                          $this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render();
        $mainFieldHtml[] =                      '</button>';
        $mainFieldHtml[] =                  '</span>';
        $mainFieldHtml[] =                  '<input type="hidden"';
        $mainFieldHtml[] =                      ' class="t3js-form-field-slug-hidden"';
        $mainFieldHtml[] =                      ' name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
        $mainFieldHtml[] =                      ' value="' . htmlspecialchars($itemValue) . '"';
        $mainFieldHtml[] =                  ' />';
        $mainFieldHtml[] =              '</div>';
        $mainFieldHtml[] =          '</div>';
        if (!empty($fieldControlHtml)) {
            $mainFieldHtml[] =      '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $mainFieldHtml[] =          '<div class="btn-group">';
            $mainFieldHtml[] =              $fieldControlHtml;
            $mainFieldHtml[] =          '</div>';
            $mainFieldHtml[] =      '</div>';
        }
        $mainFieldHtml[] =          '<div class="form-wizards-items-bottom">';
        $mainFieldHtml[] =              '<span class="t3js-form-proposal-accepted hidden d-inline-block bg-success mt-2 p-1 ps-2 pe-2 lh-base">' . sprintf(htmlspecialchars($successMessage), '<samp class="text-nowrap">' . htmlspecialchars($baseUrl) . '<span class="fw-bold">/abc/</span></samp>') . '</span>';
        $mainFieldHtml[] =              '<span class="t3js-form-proposal-different hidden d-inline-block bg-warning mt-2 p-1 ps-2 pe-2 lh-base">' . sprintf(htmlspecialchars($errorMessage), '<samp class="text-nowrap">' . htmlspecialchars($baseUrl) . '<span class="fw-bold">/abc/</span></samp>') . '</span>';
        $mainFieldHtml[] =              $fieldWizardHtml;
        $mainFieldHtml[] =          '</div>';
        $mainFieldHtml[] =      '</div>';
        $mainFieldHtml[] =  '</div>';
        $mainFieldHtml[] = '</div>';

        $resultArray['html'] = implode(LF, $mainFieldHtml);

        [$commonElementPrefix] = GeneralUtility::revExplode('[', $parameterArray['itemFormElName'], 2);
        $validInputNamesToListenTo = [];
        $includeUidInValues = false;
        foreach ($config['generatorOptions']['fields'] ?? [] as $fieldNameParts) {
            if (is_string($fieldNameParts)) {
                $fieldNameParts = GeneralUtility::trimExplode(',', $fieldNameParts);
            }
            foreach ($fieldNameParts as $listenerFieldName) {
                if ($listenerFieldName === 'uid') {
                    $includeUidInValues = true;
                    continue;
                }
                $validInputNamesToListenTo[$listenerFieldName] = $commonElementPrefix . '[' . htmlspecialchars($listenerFieldName) . ']';
            }
        }
        $parentPageId = $this->data['parentPageRow']['uid'] ?? 0;
        $signature = GeneralUtility::hmac(
            implode(
                '',
                [
                    $table,
                    $this->data['effectivePid'],
                    $row['uid'],
                    $languageId,
                    $this->data['fieldName'],
                    $this->data['command'],
                    $parentPageId,
                ]
            ),
            FormSlugAjaxController::class
        );
        $optionsForModule = [
            'pageId' => $this->data['effectivePid'],
            'recordId' => $row['uid'],
            'tableName' => $table,
            'fieldName' => $this->data['fieldName'],
            'config' => $config,
            'listenerFieldNames' => $validInputNamesToListenTo,
            'language' => $languageId,
            'originalValue' => $itemValue,
            'signature' => $signature,
            'command' => $this->data['command'],
            'parentPageId' => $parentPageId,
            'includeUidInValues' => $includeUidInValues,
        ];
        $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
            'TYPO3/CMS/Backend/FormEngine/Element/SlugElement'
        )->instance('#' . $thisSlugId, $optionsForModule);
        return $resultArray;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
