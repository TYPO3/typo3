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

namespace TYPO3\CMS\Reactions\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Creates a dynamic element to add values to table fields.
 *
 * This is rendered for config type=json, renderType=fieldMap
 *
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class FieldMapElement extends AbstractFormElement
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

    protected array $supportedFieldTypes = ['input', 'textarea', 'text', 'email', 'number', 'datetime', 'color'];

    public function __construct(
        private readonly TcaSchemaFactory $schemaFactory
    ) {}

    public function render(): array
    {
        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        $itemValue = $parameterArray['itemFormElValue'];
        $itemName = $parameterArray['itemFormElName'];

        $tableName = (string)($this->data['databaseRow']['table_name'][0] ?? '');

        $fieldsHtml = '';
        if ($this->schemaFactory->has($tableName)) {
            $itemValue = is_array($itemValue) ? $itemValue : [];
            foreach ($this->schemaFactory->get($tableName)->getFields() as $fieldName => $fieldInfo) {
                if (!in_array($fieldInfo->getType(), $this->supportedFieldTypes, true)) {
                    continue;
                }
                $fieldName = htmlspecialchars($fieldName);
                $fieldValue = isset($itemValue[$fieldName]) ? htmlspecialchars((string)$itemValue[$fieldName]) : '';
                $fieldsHtml .= '
                    <div class="form-group">
                        <label class="form-label" for="' . $fieldName . '">
                            ' . $languageService->sL($fieldInfo->getLabel()) /** @todo This is not how a field label should be resolved **/ . '
                        </label>
                        <input type="text" class="form-control" id="' . $fieldName . '" name="' . htmlspecialchars($itemName) . '[' . $fieldName . ']" value="' . $fieldValue . '">
                    </div>';
            }
        }

        if ($fieldsHtml !== '') {
            $fieldsHtml = '<div class="row">' . $fieldsHtml . '</div>';
        } else {
            $fieldsHtml = '
                <div class="alert alert-warning">
                    ' . htmlspecialchars(sprintf($languageService->sL('LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:fieldMapElement.noFields'), $tableName)) . '
                </div>';
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =     $fieldInformationHtml;
        $html[] =     '<div class="form-control-wrap" style="max-width: ' . $this->formMaxWidth($this->defaultInputWidth) . 'px">';
        $html[] =         '<div class="form-wizards-wrap">';
        $html[] =             '<div class="form-wizards-item-element">';
        $html[] =                 $fieldsHtml;
        $html[] =             '</div>';
        $html[] =         '</div>';
        $html[] =     '</div>';
        $html[] = '</div>';
        $resultArray['html'] = $this->wrapWithFieldsetAndLegend(implode(LF, $html));
        return $resultArray;
    }
}
