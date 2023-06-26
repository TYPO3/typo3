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

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\T3editor\Registry\AddonRegistry;
use TYPO3\CMS\T3editor\Registry\ModeRegistry;
use TYPO3\CMS\T3editor\T3editor;

/**
 * Handles type=json elements.
 *
 * Renders either a code editor or a standard textarea.
 */
class JsonElement extends AbstractFormElement
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

    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
        $resultArray['labelHasBeenHandled'] = true;

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $readOnly = (bool)($config['readOnly'] ?? false);
        $placeholder = trim((string)($config['placeholder'] ?? ''));
        $enableCodeEditor = ($config['enableCodeEditor'] ?? true) && ExtensionManagementUtility::isLoaded('t3editor');

        $itemValue = '';
        if (!empty($parameterArray['itemFormElValue'])) {
            try {
                $itemValue = (string)json_encode($parameterArray['itemFormElValue'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
            }
        }

        $width = null;
        if ($config['cols'] ?? false) {
            $width = $this->formMaxWidth(MathUtility::forceIntegerInRange($config['cols'], $this->minimumInputWidth, $this->maxInputWidth));
        }

        $rows = MathUtility::forceIntegerInRange(($config['rows'] ?? 5) ?: 5, 1, 20);
        $originalRows = $rows;
        if (($itemFormElementValueLength = strlen($itemValue)) > 80) {
            $calculatedRows = MathUtility::forceIntegerInRange(
                (int)round($itemFormElementValueLength / 40),
                count(explode(LF, $itemValue)),
                20
            );
            if ($originalRows < $calculatedRows) {
                $rows = $calculatedRows;
            }
        }
        $fieldId = StringUtility::getUniqueId('formengine-json-');

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        // Early return readonly display in case t3editor is not available
        if ($readOnly && !$enableCodeEditor) {
            $html = [];
            $html[] = $this->renderLabel($fieldId);
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-element">';
            $html[] =           '<div class="form-control-wrap"' . ($width ? ' style="max-width: ' . $width . 'px">' : '>');
            $html[] =               '<textarea class="form-control font-monospace" id="' . htmlspecialchars($fieldId) . '" rows="' . $rows . '" disabled>';
            $html[] =                   htmlspecialchars($itemValue);
            $html[] =               '</textarea>';
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        $itemName = (string)$parameterArray['itemFormElName'];
        $attributes = [
            'id' => $fieldId,
            'name' => $itemName,
            'wrap' => 'off',
            'rows' => (string)$rows,
            'class' => 'form-control font-monospace',
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
        ];

        if ($readOnly) {
            $attributes['disabled'] = '';
        }

        if ($placeholder !== '') {
            $attributes['placeholder'] = $placeholder;
        }

        // Use CodeMirror if available
        if ($enableCodeEditor) {
            // Compile and register t3editor configuration
            GeneralUtility::makeInstance(T3editor::class)->registerConfiguration();

            $modeRegistry = GeneralUtility::makeInstance(ModeRegistry::class);
            $mode = $modeRegistry->isRegistered('json')
                ? $modeRegistry->getByFormatCode('json')
                : $modeRegistry->getDefaultMode();

            $addons = $keymaps = [];
            foreach (GeneralUtility::makeInstance(AddonRegistry::class)->getAddons() as $addon) {
                foreach ($addon->getCssFiles() as $cssFile) {
                    $resultArray['stylesheetFiles'][] = $cssFile;
                }
                if (($module = $addon->getModule())) {
                    $addons[] = $module;
                }
                if (($keymap = $addon->getKeymap())) {
                    $keymaps[] = $keymap;
                }
            }

            $codeMirrorConfig = [
                'mode' => GeneralUtility::jsonEncodeForHtmlAttribute($mode->getModule(), false),
            ];

            if ($readOnly) {
                $codeMirrorConfig['readonly'] = '';
            }
            if ($placeholder !== '') {
                $codeMirrorConfig['placeholder'] = $placeholder;
            }
            if ($addons !== []) {
                $codeMirrorConfig['addons'] = GeneralUtility::jsonEncodeForHtmlAttribute($addons, false);
            }
            if ($keymaps !== []) {
                $codeMirrorConfig['keymaps'] = GeneralUtility::jsonEncodeForHtmlAttribute($keymaps, false);
            }

            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/t3editor/element/code-mirror-element.js');
            $editorHtml = '
                <typo3-t3editor-codemirror ' . GeneralUtility::implodeAttributes($codeMirrorConfig, true, true) . '>
                    <textarea ' . GeneralUtility::implodeAttributes($attributes, true, true) . '>' . htmlspecialchars($itemValue) . '</textarea>
                    <input type="hidden" name="target" value="0" />
                    <input type="hidden" name="effectivePid" value="' . htmlspecialchars((string)($this->data['effectivePid'] ?? '0')) . '" />
                </typo3-t3editor-codemirror>';
        } else {
            $attributes['class'] = implode(' ', array_merge(explode(' ', $attributes['class']), ['formengine-textarea', 't3js-enable-tab']));
            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine/element/json-element.js');
            $editorHtml = '
                <typo3-formengine-element-json recordFieldId="' . htmlspecialchars($fieldId) . '">
                    <textarea ' . GeneralUtility::implodeAttributes($attributes, true, true) . '>' . htmlspecialchars($itemValue) . '</textarea>
                </typo3-formengine-element-json>';
        }

        $additionalHtml = [];
        if (!$readOnly) {
            $fieldControlResult = $this->renderFieldControl();
            $fieldControlHtml = $fieldControlResult['html'];
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

            if (!empty($fieldControlHtml)) {
                $additionalHtml[] = '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
                $additionalHtml[] =     '<div class="btn-group">';
                $additionalHtml[] =         $fieldControlHtml;
                $additionalHtml[] =     '</div>';
                $additionalHtml[] = '</div>';
            }

            $fieldWizardResult = $this->renderFieldWizard();
            $fieldWizardHtml = $fieldWizardResult['html'];
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

            if (!empty($fieldWizardHtml)) {
                $additionalHtml[] = '<div class="form-wizards-items-bottom">';
                $additionalHtml[] =     $fieldWizardHtml;
                $additionalHtml[] = '</div>';
            }
        }

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap"' . ($width ? ' style="max-width: ' . $width . 'px">' : '>');
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-element">';
        $html[] =               $editorHtml;
        $html[] =           '</div>';
        $html[] =           implode(LF, $additionalHtml);
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['html'] = $this->wrapWithFieldsetAndLegend(implode(LF, $html));

        return $resultArray;
    }
}
