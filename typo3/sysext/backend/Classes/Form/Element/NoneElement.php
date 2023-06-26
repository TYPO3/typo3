<?php

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

use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * None element is a "disabled" input element with formatted values if needed.
 */
class NoneElement extends AbstractFormElement
{
    /**
     * @var int
     */
    protected $minimumInputWidth = 5;

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
     * This will render a non-editable display of the content of the field.
     *
     * @return array The HTML code for the TCEform field
     */
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
        $resultArray['labelHasBeenHandled'] = true;

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $itemValue = $parameterArray['itemFormElValue'];

        if ($config['format'] ?? false) {
            $formatOptions = $config['format.'] ?? [];
            $itemValue = $this->formatValue($config['format'], $itemValue, $formatOptions);
        }

        $size = $config['size'] ?? $this->defaultInputWidth;
        $size = MathUtility::forceIntegerInRange($size, $this->minimumInputWidth, $this->maxInputWidth);
        $width = $this->formMaxWidth($size);
        $fieldId = StringUtility::getUniqueId('formengine-textarea-');

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $html = [];
        $html[] = $this->renderLabel($fieldId);
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
}
