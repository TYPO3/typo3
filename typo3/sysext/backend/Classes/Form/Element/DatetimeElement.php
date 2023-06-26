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

use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Generation of form elements with TCA type "datetime"
 */
class DatetimeElement extends AbstractFormElement
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
     * This will render a single-line datetime form field, possibly with various control/validation features
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

        $format = $config['format'] ?? 'datetime';
        if (!in_array($format, ['datetime', 'date', 'time', 'timesec'], true)) {
            throw new \UnexpectedValueException(
                'Format "' . $format . '" for field "' . $fieldName . '" in table "' . $table . '" is '
                . 'not valid. Must be either empty or set to one of: "date", "datetime", "time", "timesec".',
                1647947686
            );
        }

        $itemValue = $parameterArray['itemFormElValue'];
        $width = $this->formMaxWidth(MathUtility::forceIntegerInRange(
            $config['size'] ?? ($format === 'date' || $format === 'datetime' ? 13 : 10),
            $this->minimumInputWidth,
            $this->maxInputWidth
        ));
        $fieldId = StringUtility::getUniqueId('formengine-input-');
        $renderedLabel = $this->renderLabel($fieldId);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if ($config['readOnly'] ?? false) {
            // Ensure dbType values (see DatabaseRowDateTimeFields) are converted to a UNIX timestamp before rendering read-only
            if (!empty($itemValue) && !MathUtility::canBeInterpretedAsInteger($itemValue)) {
                $itemValue = (new \DateTime((string)$itemValue))->getTimestamp();
            }
            // Format the unix-timestamp to the defined format (date/year etc)
            $itemValue = $this->formatValue($format, $itemValue);
            $html = [];
            $html[] = $renderedLabel;
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-element">';
            $html[] =           '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
            $html[] =               '<input class="form-control" id="' . htmlspecialchars($fieldId) . '" value="' . htmlspecialchars($itemValue) . '" type="text" disabled>';
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        $languageService = $this->getLanguageService();
        $itemName = (string)$parameterArray['itemFormElName'];

        // Always add the format to the eval list.
        $evalList = [$format];
        if ($config['nullable'] ?? false) {
            $evalList[] = 'null';
        }

        // Always add "integer" eval in case non or an invalid dbType is specified (Used for JS processing)
        if (!in_array($config['dbType'] ?? '', QueryHelper::getDateTimeTypes(), true)) {
            $evalList = array_merge($evalList, ['integer']);
        }

        $attributes = [
            'value' => '',
            'id' => $fieldId,
            'class' => implode(' ', [
                't3js-datetimepicker',
                'form-control',
                'form-control-clearable',
                't3js-clearable',
                'hasDefaultValue',
            ]),
            'data-date-type' => $format,
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

        if ($format === 'datetime' || $format === 'date') {
            // This only handles integer timestamps; if the field is a SQL native date(time), it was already converted
            // to an ISO-8601 date by the DatabaseRowDateTimeFields class. (those dates are stored as server local time)
            if (MathUtility::canBeInterpretedAsInteger($itemValue) && (int)$itemValue !== 0) {
                // We store UTC timestamps in the database.
                // Convert the timestamp to a proper ISO-8601 date so we get rid of timezone issues on the client.
                // Details: As the JS side is not capable of handling dates in the server's timezone
                // (moment.js can only handle UTC or browser's local timezone), we need to offset the value
                // to eliminate the timezone. JS will receive all dates as if they were UTC, which we undo on save in DataHandler
                $adjustedValue = (int)$itemValue + (int)date('Z', (int)$itemValue);
                // output date as an ISO-8601 date
                $itemValue = gmdate('c', $adjustedValue);
            }
            if (isset($config['range']['lower'])) {
                $attributes['data-date-min-date'] = (string)((int)$config['range']['lower'] * 1000);
            }
            if (isset($config['range']['upper'])) {
                $attributes['data-date-max-date'] = (string)((int)$config['range']['upper'] * 1000);
            }
        }
        if (($format === 'time' || $format === 'timesec') && MathUtility::canBeInterpretedAsInteger($itemValue) && (int)$itemValue !== 0) {
            // time(sec) is stored as elapsed seconds in DB, hence we interpret it as UTC time on 1970-01-01
            // and pass on the ISO format to JS.
            $itemValue = gmdate('c', (int)$itemValue);
        }

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $expansionHtml = [];
        $expansionHtml[] = '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $expansionHtml[] =  '<div class="form-wizards-wrap">';
        $expansionHtml[] =      '<div class="form-wizards-element">';
        $expansionHtml[] =          '<div class="input-group">';
        $expansionHtml[] =              '<input type="text" ' . GeneralUtility::implodeAttributes($attributes, true) . ' />';
        $expansionHtml[] =              '<input type="hidden" name="' . $itemName . '" value="' . htmlspecialchars((string)$itemValue) . '" />';
        $expansionHtml[] =              '<button class="btn btn-default" type="button" data-global-event="click" data-action-focus="#' . $attributes['id'] . '">';
        $expansionHtml[] =                  $this->iconFactory->getIcon('actions-edit-pick-date', Icon::SIZE_SMALL)->render();
        $expansionHtml[] =              '</button>';
        $expansionHtml[] =          '</div>';
        $expansionHtml[] =      '</div>';
        if (!empty($fieldControlHtml)) {
            $expansionHtml[] =      '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $expansionHtml[] =          '<div class="btn-group">';
            $expansionHtml[] =              $fieldControlHtml;
            $expansionHtml[] =          '</div>';
            $expansionHtml[] =      '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $expansionHtml[] = '<div class="form-wizards-items-bottom">';
            $expansionHtml[] = $fieldWizardHtml;
            $expansionHtml[] = '</div>';
        }
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
            <typo3-formengine-element-datetime class="formengine-field-item t3js-formengine-field-item" recordFieldId="' . htmlspecialchars($fieldId) . '">
                ' . $fieldInformationHtml . '
                ' . $fullElement . '
            </typo3-formengine-element-datetime>';

        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine/element/datetime-element.js');

        return $resultArray;
    }
}
