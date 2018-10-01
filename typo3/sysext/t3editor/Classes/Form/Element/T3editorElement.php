<?php
declare(strict_types = 1);
namespace TYPO3\CMS\T3editor\Form\Element;

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

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
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
     * Relative path to EXT:t3editor
     *
     * @var string
     */
    protected $extPath = '';

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
        $this->extPath = PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath('t3editor'));
        $codeMirrorPath = $this->extPath . 'Resources/Public/JavaScript/Contrib/cm';

        $this->resultArray = $this->initializeResultArray();
        $this->resultArray['stylesheetFiles'][] = $codeMirrorPath . '/lib/codemirror.css';
        $this->resultArray['stylesheetFiles'][] = $this->extPath . '/Resources/Public/Css/t3editor.css';
        $this->resultArray['requireJsModules'][] = [
            'TYPO3/CMS/T3editor/T3editor' => 'function(T3editor) {T3editor.findAndInitializeEditors()}'
        ];

        // Compile and register t3editor configuration
        GeneralUtility::makeInstance(T3editor::class)->registerConfiguration();

        $registeredAddons = AddonRegistry::getInstance()->getForMode($this->getMode()->getFormatCode());
        foreach ($registeredAddons as $addon) {
            foreach ($addon->getCssFiles() as $cssFile) {
                $this->resultArray['stylesheetFiles'][] = $cssFile;
            }
        }

        $parameterArray = $this->data['parameterArray'];

        $rows = MathUtility::forceIntegerInRange($parameterArray['fieldConf']['config']['rows'] ?: 10, 1, 40);

        $attributes = [];
        $attributes['rows'] = $rows;
        $attributes['wrap'] = 'off';
        $attributes['style'] = 'width:100%;';
        $attributes['onchange'] = GeneralUtility::quoteJSvalue($parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged']);

        $attributeString = '';
        foreach ($attributes as $param => $value) {
            $attributeString .= $param . '="' . htmlspecialchars((string)$value) . '" ';
        }

        $editorHtml = $this->getHTMLCodeForEditor(
            $parameterArray['itemFormElName'],
            'text-monospace enable-tab',
            $parameterArray['itemFormElValue'],
            $attributeString,
            $this->data['tableName'] . ' > ' . $this->data['fieldName'],
            [
                'target' => 0,
                'effectivePid' => $this->data['effectivePid']
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
        $html[] =               '<div class="t3editor-wrapper">';
        $html[] =                   $editorHtml;
        $html[] =               '</div>';
        $html[] =           '</div>';
        if (!empty($fieldControlHtml)) {
            $html[] =           '<div class="form-wizards-items-aside">';
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
     * @param string $additionalParams Any additional editor parameters
     * @param string $alt Alt attribute
     * @param array $hiddenfields
     *
     * @return string Generated HTML code for editor
     * @throws \TYPO3\CMS\T3editor\Exception\InvalidModeException
     */
    protected function getHTMLCodeForEditor(
        string $name,
        string $class = '',
        string $content = '',
        string $additionalParams = '',
        string $alt = '',
        array $hiddenfields = []
    ): string {
        $code = [];
        $attributes = [];
        $mode = $this->getMode();
        $registeredAddons = AddonRegistry::getInstance()->getForMode($mode->getFormatCode());

        $attributes['class'] = $class . ' t3editor';
        $attributes['alt'] = $alt;
        $attributes['id'] = 't3editor_' . md5($name);
        $attributes['name'] = $name;

        $settings = AddonRegistry::getInstance()->compileSettings($registeredAddons);
        $addons = [];
        foreach ($registeredAddons as $addon) {
            $addons[] = $addon->getIdentifier();
        }

        $attributes['data-codemirror-config'] = json_encode([
            'mode' => $mode->getIdentifier(),
            'addons' => json_encode($addons),
            'options' => json_encode($settings)
        ]);

        $attributesString = '';
        foreach ($attributes as $attribute => $value) {
            $attributesString .= $attribute . '="' . htmlspecialchars((string)$value) . '" ';
        }
        $attributesString .= $additionalParams;

        $code[] = '<textarea ' . $attributesString . '>' . htmlspecialchars($content) . '</textarea>';

        if (!empty($hiddenfields)) {
            foreach ($hiddenfields as $attributeName => $value) {
                $code[] = '<input type="hidden" name="' . htmlspecialchars($attributeName) . '" value="' . htmlspecialchars((string)$value) . '" />';
            }
        }
        return implode(LF, $code);
    }

    /**
     * @return Mode
     * @throws InvalidModeException
     */
    protected function getMode(): Mode
    {
        $config = $this->data['parameterArray']['fieldConf']['config'];

        if (!isset($config['format'])) {
            return ModeRegistry::getInstance()->getDefaultMode();
        }

        $identifier = $config['format'];
        if (strpos($config['format'], '/') !== false) {
            $parts = explode('/', $config['format']);
            $identifier = end($parts);
        }

        return ModeRegistry::getInstance()->getByFormatCode($identifier);
    }
}
