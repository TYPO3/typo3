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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Generation of elements of the type "folder"
 */
class FolderElement extends AbstractFormElement
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
     * Default field controls for this element.
     *
     * @var array
     */
    protected $defaultFieldControl = [
        'elementBrowser' => [
            'renderType' => 'elementBrowser',
        ],
    ];

    /**
     * Default field wizards for this element
     *
     * @var array
     */
    protected $defaultFieldWizard = [
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
            'after' => [ 'recordsOverview' ],
        ],
        'otherLanguageContent' => [
            'renderType' => 'otherLanguageContent',
            'after' => [ 'localizationStateSelector' ],
        ],
        'defaultLanguageDifferences' => [
            'renderType' => 'defaultLanguageDifferences',
            'after' => [ 'otherLanguageContent' ],
        ],
    ];

    /**
     * This will render a selector box into which folder relations can be
     * inserted.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @throws \RuntimeException
     */
    public function render()
    {
        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();
        // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
        $resultArray['labelHasBeenHandled'] = true;

        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $elementName = $parameterArray['itemFormElName'];

        $selectedItems = $parameterArray['itemFormElValue'];
        $maxItems = $config['maxitems'];

        $size = (int)($config['size'] ?? 5);
        $autoSizeMax = (int)($config['autoSizeMax'] ?? 0);
        if ($autoSizeMax > 0) {
            $size = MathUtility::forceIntegerInRange($size, 1);
            $size = MathUtility::forceIntegerInRange(count($selectedItems) + 1, $size, $autoSizeMax);
        }
        $fieldId = StringUtility::getUniqueId('tceforms-multiselect-');

        $listOfSelectedValues = [];
        $selectorOptionsHtml = [];
        foreach ($selectedItems as $selectedItem) {
            $folder = $selectedItem['folder'];
            $listOfSelectedValues[] = $folder;
            $selectorOptionsHtml[] =
                '<option value="' . htmlspecialchars($folder) . '" title="' . htmlspecialchars($folder) . '">'
                    . htmlspecialchars($folder)
                . '</option>';
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if (isset($config['readOnly']) && $config['readOnly']) {
            // Return early if element is read only
            $html = [];
            $html[] = $this->renderLabel($fieldId);
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-element">';
            $html[] =           '<select';
            $html[] =               ' size="' . $size . '"';
            $html[] =               ' disabled="disabled"';
            $html[] =               ' id="' . $fieldId . '"';
            $html[] =               ' class="form-select"';
            $html[] =               ($maxItems !== 1 && $size !== 1) ? ' multiple="multiple"' : '';
            $html[] =           '>';
            $html[] =               implode(LF, $selectorOptionsHtml);
            $html[] =           '</select>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        $itemCanBeSelectedMoreThanOnce = !empty($config['multiple']);

        $showMoveIcons = true;
        if (isset($config['hideMoveIcons']) && $config['hideMoveIcons']) {
            $showMoveIcons = false;
        }
        $showDeleteControl = true;
        if (isset($config['hideDeleteIcon']) && $config['hideDeleteIcon']) {
            $showDeleteControl = false;
        }

        $selectorAttributes = [
            'id' => $fieldId,
            'data-formengine-input-name' => htmlspecialchars($elementName),
            'data-maxitems' => (string)$maxItems,
            'size' => (string)$size,
        ];
        $selectorAttributes['class'] = 'form-select';
        if ($maxItems !== 1 && $size !== 1) {
            $selectorAttributes['multiple'] = 'multiple';
        }

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $html = [];
        $html[] = $this->renderLabel($fieldId);
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =       '<div class="form-wizards-element">';
        $html[] =           '<input type="hidden" data-formengine-input-name="' . htmlspecialchars($elementName) . '" value="' . $itemCanBeSelectedMoreThanOnce . '" />';
        $html[] =           '<select ' . GeneralUtility::implodeAttributes($selectorAttributes, true) . '>';
        $html[] =               implode(LF, $selectorOptionsHtml);
        $html[] =           '</select>';
        $html[] =       '</div>';
        if (($maxItems > 1 && $size > 1 && $showMoveIcons) || $showDeleteControl) {
            $html[] =       '<div class="form-wizards-items-aside form-wizards-items-aside--move">';
            $html[] =           '<div class="btn-group-vertical">';
            if ($maxItems > 1 && $size >= 5 && $showMoveIcons) {
                $html[] =           '<a href="#"';
                $html[] =               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-top"';
                $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
                $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_top')) . '"';
                $html[] =           '>';
                $html[] =               $this->iconFactory->getIcon('actions-move-to-top', Icon::SIZE_SMALL)->render();
                $html[] =           '</a>';
            }
            if ($maxItems > 1 && $size > 1 && $showMoveIcons) {
                $html[] =           '<a href="#"';
                $html[] =               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-up"';
                $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
                $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_up')) . '"';
                $html[] =           '>';
                $html[] =               $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render();
                $html[] =           '</a>';
                $html[] =           '<a href="#"';
                $html[] =               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-down"';
                $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
                $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_down')) . '"';
                $html[] =           '>';
                $html[] =               $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render();
                $html[] =           '</a>';
            }
            if ($maxItems > 1 && $size >= 5 && $showMoveIcons) {
                $html[] =           '<a href="#"';
                $html[] =               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-bottom"';
                $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
                $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_bottom')) . '"';
                $html[] =           '>';
                $html[] =               $this->iconFactory->getIcon('actions-move-to-bottom', Icon::SIZE_SMALL)->render();
                $html[] =           '</a>';
            }
            if ($showDeleteControl) {
                $html[] =           '<a href="#"';
                $html[] =               ' class="btn btn-default t3js-btn-option t3js-btn-removeoption t3js-revert-unique"';
                $html[] =               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
                $html[] =               ' data-uid="' . htmlspecialchars((string)$row['uid']) . '"';
                $html[] =               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.remove_selected')) . '"';
                $html[] =           '>';
                $html[] =               $this->iconFactory->getIcon('actions-selection-delete', Icon::SIZE_SMALL)->render();
                $html[] =           '</a>';
            }
        }
        $html[] =           '</div>';
        $html[] =       '</div>';
        if ($fieldControlHtml !== '') {
            $html[] =       '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $html[] =           '<div class="btn-group-vertical">';
            $html[] =               $fieldControlHtml;
            $html[] =           '</div>';
            $html[] =       '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-items-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =   '</div>';

        $hiddenElementAttrs = array_merge(
            [
                'type' => 'hidden',
                'name' => $elementName,
                'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
                'value' => implode(',', $listOfSelectedValues),
            ],
            $this->getOnFieldChangeAttrs('change', $parameterArray['fieldChangeFunc'] ?? [])
        );
        $html[] =   '<input ' . GeneralUtility::implodeAttributes($hiddenElementAttrs, true) . '>';
        $html[] = '</div>';

        $resultArray['html'] =
            '<typo3-formengine-element-folder recordFieldId="' . htmlspecialchars($fieldId) . '">
                ' . implode(LF, $html) . '
            </typo3-formengine-element-folder>';

        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine/element/folder-element.js');

        return $resultArray;
    }
}
