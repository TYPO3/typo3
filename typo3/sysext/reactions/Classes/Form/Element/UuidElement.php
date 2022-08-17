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

use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Creates a readonly input element with a UUID.
 *
 * This is rendered for config type=user, renderType=uuid
 *
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class UuidElement extends AbstractFormElement
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

    public function render()
    {
        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        $itemValue = $parameterArray['itemFormElValue'] ?: (string)Uuid::v4();
        $fieldId = StringUtility::getUniqueId('formengine-input-');

        $attributes = [
            'id' => $fieldId,
            'name' => htmlspecialchars($parameterArray['itemFormElName']),
            'size' => 40,
            'class' => 'form-control',
            'data-formengine-input-name' => htmlspecialchars($parameterArray['itemFormElName']),
        ];

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =     $fieldInformationHtml;
        $html[] =     '<div class="form-control-wrap" style="max-width: ' . $this->formMaxWidth($this->defaultInputWidth) . 'px">';
        $html[] =         '<div class="form-wizards-wrap">';
        $html[] =             '<div class="form-wizards-element">';
        $html[] =                 '<input type="text" readonly="readonly" disabled="disabled" value="' . htmlspecialchars($itemValue, ENT_QUOTES) . '" ';
        $html[] =                     GeneralUtility::implodeAttributes($attributes, true);
        $html[] =                 '/>';
        $html[] =             '</div>';
        $html[] =         '</div>';
        $html[] =     '</div>';
        $html[] = '</div>';
        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }
}
