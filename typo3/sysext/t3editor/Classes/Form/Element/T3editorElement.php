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

namespace TYPO3\CMS\T3editor\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\T3editor\Exception\InvalidModeException;
use TYPO3\CMS\T3editor\Mode;
use TYPO3\CMS\T3editor\Registry\AddonRegistry;
use TYPO3\CMS\T3editor\Registry\ModeRegistry;
use TYPO3\CMS\T3editor\T3editor;

/**
 * t3editor FormEngine widget
 * @internal
 */
class T3editorElement extends AbstractFormElement
{
    /**
     * @var array
     */
    protected $resultArray;

    /**
     * @var string
     */
    protected $mode = '';

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
     * Render t3editor element
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @throws \TYPO3\CMS\T3editor\Exception\InvalidModeException
     * @throws \InvalidArgumentException
     * @throws \BadFunctionCallException
     */
    public function render(): array
    {
        $this->resultArray = $this->initializeResultArray();
        $this->resultArray['stylesheetFiles'][] = 'EXT:t3editor/Resources/Public/JavaScript/Contrib/codemirror/lib/codemirror.css';
        $this->resultArray['stylesheetFiles'][] = 'EXT:t3editor/Resources/Public/Css/t3editor.css';
        $this->resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/T3editor/Element/CodeMirrorElement');

        // Compile and register t3editor configuration
        GeneralUtility::makeInstance(T3editor::class)->registerConfiguration();

        $addonRegistry = GeneralUtility::makeInstance(AddonRegistry::class);
        $registeredAddons = $addonRegistry->getForMode($this->getMode()->getFormatCode());
        foreach ($registeredAddons as $addon) {
            foreach ($addon->getCssFiles() as $cssFile) {
                $this->resultArray['stylesheetFiles'][] = $cssFile;
            }
        }

        $parameterArray = $this->data['parameterArray'];

        $attributes = [
            'wrap' => 'off',
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($parameterArray['fieldConf']['config']),
        ];
        if (isset($parameterArray['fieldConf']['config']['rows']) && MathUtility::canBeInterpretedAsInteger($parameterArray['fieldConf']['config']['rows'])) {
            $attributes['rows'] = $parameterArray['fieldConf']['config']['rows'];
        }

        $settings = [];
        if ($parameterArray['fieldConf']['config']['readOnly'] ?? false) {
            $settings['readOnly'] = true;
        }

        $editorHtml = $this->getHTMLCodeForEditor(
            $parameterArray['itemFormElName'],
            'text-monospace enable-tab',
            $parameterArray['itemFormElValue'],
            $attributes,
            $settings,
            $this->data['tableName'] . ' > ' . $this->data['fieldName'],
            [
                'target' => 0,
                'effectivePid' => $this->data['effectivePid'] ?? 0,
            ]
        );

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $this->resultArray = $this->mergeChildReturnIntoExistingResult($this->resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $this->resultArray = $this->mergeChildReturnIntoExistingResult($this->resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $this->resultArray = $this->mergeChildReturnIntoExistingResult($this->resultArray, $fieldWizardResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-element">';
        $html[] =               $editorHtml;
        $html[] =           '</div>';
        if (!empty($fieldControlHtml)) {
            $html[] =           '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $html[] =               '<div class="btn-group">';
            $html[] =                   $fieldControlHtml;
            $html[] =               '</div>';
            $html[] =           '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-items-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $this->resultArray['html'] = implode(LF, $html);

        return $this->resultArray;
    }

    /**
     * Generates HTML with code editor
     *
     * @param string $name Name attribute of HTML tag
     * @param string $class Class attribute of HTML tag
     * @param string $content Content of the editor
     * @param array $attributes Any additional editor parameters
     * @param array $settings
     * @param string $label Codemirror panel label
     * @param array $hiddenfields
     *
     * @return string Generated HTML code for editor
     * @throws \TYPO3\CMS\T3editor\Exception\InvalidModeException
     */
    protected function getHTMLCodeForEditor(
        string $name,
        string $class = '',
        string $content = '',
        array $attributes = [],
        array $settings = [],
        string $label = '',
        array $hiddenfields = []
    ): string {
        $code = [];
        $mode = $this->getMode();
        $addonRegistry = GeneralUtility::makeInstance(AddonRegistry::class);
        $registeredAddons = $addonRegistry->getForMode($mode->getFormatCode());

        $attributes['class'] = $class;
        $attributes['id'] = 't3editor_' . md5($name);
        $attributes['name'] = $name;

        $settings = array_merge($addonRegistry->compileSettings($registeredAddons), $settings);

        $addons = [];
        foreach ($registeredAddons as $addon) {
            $addons[] = $addon->getIdentifier();
        }
        $codeMirrorConfig = [
            'mode' => $mode->getIdentifier(),
            'label' => $label,
            'addons' => GeneralUtility::jsonEncodeForHtmlAttribute($addons, false),
            'options' => GeneralUtility::jsonEncodeForHtmlAttribute($settings, false),
        ];

        $code[] = '<typo3-t3editor-codemirror ' . GeneralUtility::implodeAttributes($codeMirrorConfig, true) . '>';
        $code[] = '<textarea ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . htmlspecialchars($content) . '</textarea>';

        if (!empty($hiddenfields)) {
            foreach ($hiddenfields as $attributeName => $value) {
                $code[] = '<input type="hidden" name="' . htmlspecialchars((string)$attributeName) . '" value="' . htmlspecialchars((string)$value) . '" />';
            }
        }
        $code[] = '</typo3-t3editor-codemirror>';

        return implode(LF, $code);
    }

    /**
     * @return Mode
     * @throws InvalidModeException
     */
    protected function getMode(): Mode
    {
        $config = $this->data['parameterArray']['fieldConf']['config'];

        $registry = GeneralUtility::makeInstance(ModeRegistry::class);
        if (!isset($config['format'])) {
            return $registry->getDefaultMode();
        }

        $identifier = $config['format'];
        if (str_contains($config['format'], '/')) {
            $parts = explode('/', $config['format']);
            $identifier = end($parts);
        }

        return $registry->getByFormatCode($identifier);
    }
}
