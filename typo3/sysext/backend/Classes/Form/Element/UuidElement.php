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

use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Render a readonly input field, which is filled with a UUID
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

    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
        $resultArray['labelHasBeenHandled'] = true;
        $parameterArray = $this->data['parameterArray'];
        $itemValue = htmlspecialchars((string)$parameterArray['itemFormElValue'], ENT_QUOTES);
        $config = $parameterArray['fieldConf']['config'];
        $itemName = $parameterArray['itemFormElName'];
        $fieldId = StringUtility::getUniqueId('formengine-uuid-');

        if (!isset($config['required'])) {
            $config['required'] = true;
        }

        if ($config['required'] && !Uuid::isValid($itemValue)) {
            // Note: This can only happen in case the TcaUuid data provider is not executed or a custom
            // data provider has changed the value afterwards. Since this can only happen in user code,
            // we throw an exception to inform the administrator about this misconfiguration.
            throw new \RuntimeException(
                'Field "' . $this->data['fieldName'] . '" in table "' . $this->data['tableName'] . '" of type "uuid" defines the field to be required but does not contain a valid uuid. Make sure to properly generate a valid uuid value.',
                1678895476
            );
        }

        $width = $this->formMaxWidth(
            MathUtility::forceIntegerInRange($config['size'] ?? $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth)
        );

        $attributes = [
            'id' => $fieldId,
            'name' => $itemName,
            'type' => 'text',
            'readonly' => 'readonly',
            'class' => 'form-control disabled',
            'data-formengine-input-name' => $itemName,
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
        ];

        $uuidElement = '
            <input value="' . $itemValue . '"
                ' . GeneralUtility::implodeAttributes($attributes, true) . '
            />';

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if (($config['enableCopyToClipboard'] ?? true) !== false) {
            $uuidElement = '
                <div class="input-group">
                    ' . $uuidElement . '
                    <typo3-copy-to-clipboard
                        class="btn btn-default"
                        title="' . htmlspecialchars(sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_copytoclipboard.xlf:copyToClipboard.title'), 'UUID')) . '"
                        text="' . $itemValue . '"
                    >
                        ' . $this->iconFactory->getIcon('actions-clipboard', Icon::SIZE_SMALL) . '
                    </typo3-copy-to-clipboard>
                </div>';

            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/copy-to-clipboard.js');
        }

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $html = [];
        $html[] = $this->renderLabel($fieldId);
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =     $fieldInformationHtml;
        $html[] =     '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $html[] =         '<div class="form-wizards-wrap">';
        $html[] =             '<div class="form-wizards-element">';
        $html[] =                 $uuidElement;
        $html[] =             '</div>';

        if (!empty($fieldControlHtml)) {
            $html[] =      '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $html[] =          '<div class="btn-group">';
            $html[] =              $fieldControlHtml;
            $html[] =          '</div>';
            $html[] =      '</div>';
        }

        $html[] =         '</div>';
        $html[] =     '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);

        return $resultArray;
    }
}
