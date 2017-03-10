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

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * None element is a "disabled" input element with formatted values if needed.
 */
class NoneElement extends AbstractFormElement
{
    /**
     * This will render a non-editable display of the content of the field.
     *
     * @return string The HTML code for the TCEform field
     */
    public function render()
    {
        $resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $itemValue = $parameterArray['itemFormElValue'];

        if (isset($config['format']) && $config['format']) {
            $formatOptions = $config['format.'] ?? [];
            $itemValue = $this->formatValue($config['format'], $itemValue, $formatOptions);
        }
        if (!$config['pass_content']) {
            $itemValue = htmlspecialchars($itemValue);
        }

        $cols = $config['cols'] ?: ($config['size'] ?: $this->defaultInputWidth);
        $size = MathUtility::forceIntegerInRange($cols ?: $this->defaultInputWidth, 5, $this->maxInputWidth);
        $width = $this->formMaxWidth($size);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
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
}
