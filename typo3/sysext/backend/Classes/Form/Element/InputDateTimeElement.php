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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Generation of TCEform elements of the type "input type=text"
 */
class InputDateTimeElement extends AbstractFormElement
{
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
     * @throws \RuntimeException with invalid configuration
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
        $defaultInputWidth = 10;
        $evalList = GeneralUtility::trimExplode(',', $config['eval'], true);
        $nullControlNameEscaped = htmlspecialchars('control[active][' . $table . '][' . $row['uid'] . '][' . $fieldName . ']');

        if (in_array('date', $evalList, true)) {
            $format = 'date';
            $defaultInputWidth = 13;
        } elseif (in_array('datetime', $evalList, true)) {
            $format = 'datetime';
            $defaultInputWidth = 13;
        } elseif (in_array('time', $evalList, true)) {
            $format = 'time';
        } elseif (in_array('timesec', $evalList, true)) {
            $format = 'timesec';
        } else {
            throw new \RuntimeException(
                'Field "' . $fieldName . '" in table "' . $table . '" with renderType "inputDataTime" needs'
                . '"eval" set to either "date", "datetime", "time" or "timesec"',
                1483823746
            );
        }

        $size = MathUtility::forceIntegerInRange($config['size'] ?? $defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);
        $width = (int)$this->formMaxWidth($size);

        if (isset($config['readOnly']) && $config['readOnly']) {
            // Early return for read only fields
            $itemValue = $this->formatValue($format, $itemValue);
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

        $attributes = [
            'value' => '',
            'id' => StringUtility::getUniqueId('formengine-input-'),
            'class' => implode(' ', [
                't3js-datetimepicker',
                'form-control',
                't3js-clearable',
                'hasDefaultValue',
            ]),
            'data-date-type' => $format,
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

        if ($format === 'datetime' || $format === 'date') {
            // This only handles integer timestamps; if the field is a SQL native date(time), it was already converted
            // to an ISO-8601 date by the DatabaseRowDateTimeFields class. (those dates are stored as server local time)
            if (MathUtility::canBeInterpretedAsInteger($itemValue) && $itemValue != 0) {
                // We store UTC timestamps in the database.
                // Convert the timestamp to a proper ISO-8601 date so we get rid of timezone issues on the client.
                // Details: As the JS side is not capable of handling dates in the server's timezone
                // (moment.js can only handle UTC or browser's local timezone), we need to offset the value
                // to eliminate the timezone. JS will receive all dates as if they were UTC, which we undo on save in DataHandler
                $adjustedValue = $itemValue + date('Z', (int)$itemValue);
                // output date as a ISO-8601 date
                $itemValue = gmdate('c', $adjustedValue);
            }
            if (isset($config['range']['lower'])) {
                $attributes['data-date-min-date'] = (int)$config['range']['lower'] * 1000;
            }
            if (isset($config['range']['upper'])) {
                $attributes['data-date-max-date'] = (int)$config['range']['upper'] * 1000;
            }
        }
        if (($format === 'time' || $format === 'timesec') && MathUtility::canBeInterpretedAsInteger($itemValue) && $itemValue != 0) {
            // time(sec) is stored as elapsed seconds in DB, hence we interpret it as UTC time on 1970-01-01
            // and pass on the ISO format to JS.
            $itemValue = gmdate('c', (int)$itemValue);
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

        $expansionHtml = [];
        $expansionHtml[] = '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $expansionHtml[] =  '<div class="form-wizards-wrap">';
        $expansionHtml[] =      '<div class="form-wizards-element">';
        $expansionHtml[] =          '<div class="input-group">';
        $expansionHtml[] =              '<input type="text"' . GeneralUtility::implodeAttributes($attributes, true) . ' />';
        $expansionHtml[] =              '<input type="hidden" name="' . $parameterArray['itemFormElName'] . '" value="' . htmlspecialchars($itemValue) . '" />';
        $expansionHtml[] =              '<span class="input-group-btn">';
        $expansionHtml[] =                  '<label class="btn btn-default" for="' . $attributes['id'] . '">';
        $expansionHtml[] =                      $this->iconFactory->getIcon('actions-edit-pick-date', Icon::SIZE_SMALL)->render();
        $expansionHtml[] =                  '</label>';
        $expansionHtml[] =              '</span>';
        $expansionHtml[] =          '</div>';
        $expansionHtml[] =      '</div>';
        $expansionHtml[] =      '<div class="form-wizards-items-aside">';
        $expansionHtml[] =          '<div class="btn-group">';
        $expansionHtml[] =              $fieldControlHtml;
        $expansionHtml[] =          '</div>';
        $expansionHtml[] =      '</div>';
        $expansionHtml[] =      '<div class="form-wizards-items-bottom">';
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
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
