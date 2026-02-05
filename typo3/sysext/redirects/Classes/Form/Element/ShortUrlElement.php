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

namespace TYPO3\CMS\Redirects\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Custom form element that combines source_host and source_path fields
 * into a single short URL input field.
 */
class ShortUrlElement extends AbstractFormElement
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

    public function __construct(
        private readonly IconFactory $iconFactory,
    ) {}

    /**
     * Renders a combined field displaying source_host and source_path together.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $isReadOnly = $parameterArray['fieldConf']['config']['readOnly'] ?? false;

        // Render label and field information for the short_url field
        $fieldId = 'formengine-' . md5($this->data['fieldName']);
        $renderedLabel = $this->renderLabel($fieldId);
        $fieldInformationResult = $this->renderFieldInformation();
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if ($isReadOnly) {
            $html = $this->renderReadOnlyView();
            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/copy-to-clipboard.js');
        } else {
            $html = $this->renderEditableView($resultArray);
        }

        $resultArray['html'] = $renderedLabel . '
            <div class="formengine-field-item t3js-formengine-field-item">
                ' . $fieldInformationResult['html'] . '
                ' . $html . '
            </div>';

        return $resultArray;
    }

    /**
     * Renders the read-only view showing the complete short URL with copy button.
     */
    private function renderReadOnlyView(): string
    {
        $row = $this->data['databaseRow'];
        $sourceHost = $row['source_host'] ?? '';
        $sourcePath = $row['source_path'] ?? '';

        $scheme = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https' : 'http';
        $completeUrl = $scheme . '://' . $sourceHost . $sourcePath;

        $copyTitle = $this->getLanguageService()->sL('redirects.module_redirect:short_url.copy');

        $html = [];
        $html[] = '<div class="form-control-wrap">';
        $html[] = '    <div class="form-wizards-wrap">';
        $html[] = '        <div class="form-wizards-item-element">';
        $html[] = '            <div class="input-group">';
        $html[] = '                <span class="input-group-text">' . htmlspecialchars($completeUrl) . '</span>';
        $html[] = '                <typo3-copy-to-clipboard text="' . htmlspecialchars($completeUrl) . '" class="btn btn-default" title="' . htmlspecialchars($copyTitle) . '">';
        $html[] = '                    ' . $this->iconFactory->getIcon('actions-clipboard', IconSize::SMALL)->render();
        $html[] = '                </typo3-copy-to-clipboard>';
        $html[] = '            </div>';
        $html[] = '        </div>';
        $html[] = '    </div>';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    /**
     * Renders the editable view with both source_host and source_path fields.
     */
    private function renderEditableView(array &$resultArray): string
    {
        $row = $this->data['databaseRow'];
        $processedTca = $this->data['processedTca'];
        $itemName = $this->data['parameterArray']['itemFormElName'];
        $shortUrlFieldName = $this->data['fieldName'];

        $sourceHostConfig = $processedTca['columns']['source_host']['config'] ?? [];
        $sourcePathConfig = $processedTca['columns']['source_path']['config'] ?? [];
        $sourceHostName = str_replace('[' . $shortUrlFieldName . ']', '[source_host]', $itemName);
        $sourcePathName = str_replace('[' . $shortUrlFieldName . ']', '[source_path]', $itemName);
        $sourceHostValue = $row['source_host'] ?? '';
        $sourcePathValue = $row['source_path'] ?? '';

        $sourceHostHtml = $this->renderSourceHostField($sourceHostName, $sourceHostValue, $sourceHostConfig);
        $sourcePathHtml = $this->renderSourcePathField($sourcePathName, $sourcePathValue, $sourcePathConfig);

        $fieldControlResult = $this->renderFieldControl();
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/element/combobox-element.js');

        $html = [];
        $html[] = '<div class="form-control-wrap">';
        $html[] = '    <div class="form-wizards-wrap">';
        $html[] = '        <div class="form-wizards-item-element">';
        $html[] = '            <div class="input-group short-url-input-group">';
        $html[] = '                ' . $sourceHostHtml;
        $html[] = '                ' . $sourcePathHtml;
        $html[] = '                ' . $fieldControlResult['html'];
        $html[] = '            </div>';
        $html[] = '        </div>';
        $html[] = '    </div>';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    /**
     * Renders the source_host field as a combobox with value picker items.
     */
    private function renderSourceHostField(string $name, string $value, array $config): string
    {
        $attributes = [
            'type' => 'text',
            'name' => $name,
            'value' => $value,
            'class' => 'form-control',
            'data-formengine-input-name' => $name,
        ];

        // Add required validation from config
        if (!empty($config['required'])) {
            $attributes['required'] = 'required';
            $attributes['data-formengine-validation-rules'] = '[{"type":"required"}]';
        }

        // Add eval rules from config
        if (!empty($config['eval'])) {
            $attributes['data-formengine-input-params'] = json_encode([
                'field' => $name,
                'evalList' => $config['eval'],
            ]);
        }

        // Build combobox with value picker items
        $html = '<typo3-backend-combobox>';
        $html .= '<input ' . GeneralUtility::implodeAttributes($attributes, true) . ' />';

        // Add value picker items if available
        if (isset($config['valuePicker']['items'])) {
            foreach ($config['valuePicker']['items'] as $item) {
                $itemValue = $item['value'] ?? '';
                $itemLabel = $item['label'] ?? $itemValue;
                $html .= '<typo3-backend-combobox-choice value="' . htmlspecialchars($itemValue) . '">';
                $html .= htmlspecialchars($this->getLanguageService()->sL($itemLabel));
                $html .= '</typo3-backend-combobox-choice>';
            }
        }

        $html .= '</typo3-backend-combobox>';

        return $html;
    }

    /**
     * Renders the source_path field as an input with validation.
     */
    private function renderSourcePathField(string $name, string $value, array $config): string
    {
        $attributes = [
            'type' => 'text',
            'name' => $name,
            'value' => $value,
            'class' => 'form-control form-control-clearable t3js-clearable',
            'data-formengine-input-name' => $name,
        ];

        if (!empty($config['size'])) {
            $attributes['size'] = (string)$config['size'];
        }

        if (!empty($config['max'])) {
            $attributes['maxlength'] = (string)$config['max'];
        }

        if (!empty($config['required'])) {
            $attributes['required'] = 'required';
        }

        if (!empty($config['placeholder'])) {
            $placeholder = $this->getLanguageService()->sL($config['placeholder']);
            if ($placeholder !== '') {
                $attributes['placeholder'] = $placeholder;
            }
        }

        if (!empty($config['eval'])) {
            $attributes['data-formengine-input-params'] = json_encode([
                'field' => $name,
                'evalList' => $config['eval'],
            ]);
        }

        $validationRules = [];
        if (!empty($config['required'])) {
            $validationRules[] = ['type' => 'required'];
        }
        if (!empty($validationRules)) {
            $attributes['data-formengine-validation-rules'] = json_encode($validationRules);
        }

        return '<input ' . GeneralUtility::implodeAttributes($attributes, true) . ' />';
    }
}
