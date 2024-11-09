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

use TYPO3\CMS\Core\Domain\DateTimeFormat;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
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

    public function __construct(
        private readonly IconFactory $iconFactory,
    ) {}

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
        $config = $parameterArray['fieldConf']['config'];

        $format = $config['format'] ?? 'datetime';
        if (!in_array($format, ['datetime', 'date', 'time', 'timesec', 'datetimesec'], true)) {
            throw new \UnexpectedValueException(
                'Format "' . $format . '" for field "' . $fieldName . '" in table "' . $table . '" is '
                . 'not valid. Must be either empty or set to one of: "date", "datetime", "time", "timesec", "datetimesec".',
                1647947686
            );
        }

        $datetime = $parameterArray['itemFormElValue'];
        if ($datetime !== null && !$datetime instanceof \DateTimeInterface) {
            throw new \UnexpectedValueException(
                'The formEngine itemFormElValue parameter for field "' . $fieldName . '" in table "' . $table . '" is '
                . 'not valid. It must be an instance of `\\DateTimeInterface` but is `' . gettype($datetime) . '`. '
                . 'Make sure to have it processed by `FormDataProvider/DatabaseRowDateTimeFields`.',
                1731132127
            );
        }

        $width = $this->formMaxWidth(MathUtility::forceIntegerInRange(
            $config['size'] ?? ($format === 'datetimesec' ? 14 : ($format === 'date' || $format === 'datetime' ? 13 : 10)),
            $this->minimumInputWidth,
            $this->maxInputWidth
        ));
        $fieldId = StringUtility::getUniqueId('formengine-input-');
        $itemName = (string)$parameterArray['itemFormElName'];
        $renderedLabel = $this->renderLabel($fieldId);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if ($config['readOnly'] ?? false) {
            if ($datetime === null) {
                $itemValue = '';
            } elseif ($format === 'time') {
                $itemValue = (string)((int)$datetime->format('H') * 3600 + (int)$datetime->format('i') * 60);
            } elseif ($format === 'timesec') {
                $itemValue = (string)((int)$datetime->format('H') * 3600 + (int)$datetime->format('i') * 60 + (int)$datetime->format('s'));
            } else {
                $itemValue = (string)$datetime->getTimestamp();
            }
            // Format the unix-timestamp to the defined format (date/year etc)
            $formattedDate = $this->formatValue($format, $itemValue);
            $html = [];
            $html[] = $renderedLabel;
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-item-element">';
            $html[] =           '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
            $html[] =               '<input class="form-control" id="' . htmlspecialchars($fieldId) . '" name="' . htmlspecialchars($itemName) . '" value="' . htmlspecialchars($formattedDate) . '" type="text" disabled>';
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        $languageService = $this->getLanguageService();

        // Always add the format to the eval list.
        $evalList = [$format];
        $isNullable = $config['nullable'] ?? false;
        if ($isNullable) {
            $evalList[] = 'null';
        }

        $attributes = [
            'value' => '',
            'id' => $fieldId,
            'class' => implode(' ', [
                'form-control',
                'form-control-clearable',
            ]),
            'data-input-type' => 'datetimepicker',
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

        if ($format === 'datetime' || $format === 'date' || $format === 'datetimesec') {
            if (isset($config['range']['lower'])) {
                $lower = (int)$config['range']['lower'];
                $attributes['data-date-min-date'] = date(DateTimeFormat::ISO8601_LOCALTIME, $lower);
            }
            if (isset($config['range']['upper'])) {
                $upper = (int)$config['range']['upper'];
                $attributes['data-date-max-date'] = date(DateTimeFormat::ISO8601_LOCALTIME, $upper);
            }
        }

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $buttonAriaLabelEscaped = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.datepicker.label'));

        $dateISO8601 = $datetime?->format(DateTimeFormat::ISO8601_LOCALTIME) ?? '';

        $expansionHtml = [];
        $expansionHtml[] = '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $expansionHtml[] =  '<div class="form-wizards-wrap">';
        $expansionHtml[] =      '<div class="form-wizards-item-element">';
        $expansionHtml[] =          '<div class="input-group">';
        $expansionHtml[] =              '<input type="text" ' . GeneralUtility::implodeAttributes($attributes, true) . ' />';
        $expansionHtml[] =              '<input type="hidden" name="' . $itemName . '" value="' . htmlspecialchars($dateISO8601) . '" />';
        $expansionHtml[] =              '<button class="btn btn-default" aria-label="' . $buttonAriaLabelEscaped . '" type="button" data-global-event="click" data-action-focus="#' . $attributes['id'] . '">';
        $expansionHtml[] =                  $this->iconFactory->getIcon('actions-edit-pick-date', IconSize::SMALL)->render();
        $expansionHtml[] =              '</button>';
        $expansionHtml[] =          '</div>';
        $expansionHtml[] =      '</div>';
        if (!empty($fieldControlHtml)) {
            $expansionHtml[] =      '<div class="form-wizards-item-aside form-wizards-item-aside--field-control">';
            $expansionHtml[] =          '<div class="btn-group">';
            $expansionHtml[] =              $fieldControlHtml;
            $expansionHtml[] =          '</div>';
            $expansionHtml[] =      '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $expansionHtml[] = '<div class="form-wizards-item-bottom">';
            $expansionHtml[] = $fieldWizardHtml;
            $expansionHtml[] = '</div>';
        }
        $expansionHtml[] =  '</div>';
        $expansionHtml[] = '</div>';
        $expansionHtml = implode(LF, $expansionHtml);

        $nullControlNameEscaped = htmlspecialchars('control[active][' . $table . '][' . $this->data['databaseRow']['uid'] . '][' . $fieldName . ']');

        $fullElement = $expansionHtml;
        if ($this->hasNullCheckboxWithPlaceholder()) {
            $checked = $datetime !== null ? ' checked="checked"' : '';
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
