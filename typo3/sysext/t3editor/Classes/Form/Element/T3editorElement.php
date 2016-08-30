<?php
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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\T3editor\T3editor;

/**
 * t3editor FormEngine widget
 */
class T3editorElement extends AbstractFormElement
{
    const MODE_CSS = 'css';
    const MODE_HTML = 'html';
    const MODE_JAVASCRIPT = 'javascript';
    const MODE_MIXED = 'mixed';
    const MODE_PHP = 'php';
    const MODE_SPARQL = 'sparql';
    const MODE_TYPOSCRIPT = 'typoscript';
    const MODE_XML = 'xml';

    /**
     * @var array
     */
    protected $allowedModes = [
        self::MODE_CSS,
        self::MODE_HTML,
        self::MODE_JAVASCRIPT,
        self::MODE_MIXED,
        self::MODE_PHP,
        self::MODE_SPARQL,
        self::MODE_TYPOSCRIPT,
        self::MODE_XML,
    ];

    /**
     * @var array
     */
    protected $resultArray;

    /**
     * @var string
     */
    protected $mode = '';

    /**
     * Counts the editors on the current page
     *
     * @var int
     */
    protected $editorCounter = 0;

    /**
     * Relative path to EXT:t3editor
     *
     * @var string
     */
    protected $relExtPath = '';

    /**
     * @var string
     */
    protected $codemirrorPath = 'Resources/Public/JavaScript/Contrib/codemirror/js/';

    /**
     * RequireJS modules loaded for code completion
     *
     * @var array
     */
    protected $codeCompletionComponents = ['TsRef', 'CompletionResult', 'TsParser', 'TsCodeCompletion'];

    /**
     * Render t3editor element
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $this->getLanguageService()->includeLLFile('EXT:t3editor/Resources/Private/Language/locallang.xlf');
        $this->relExtPath = ExtensionManagementUtility::extRelPath('t3editor');
        $this->codemirrorPath = $this->relExtPath . $this->codemirrorPath;

        $this->resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];

        $rows = MathUtility::forceIntegerInRange($parameterArray['fieldConf']['config']['rows'] ?: 10, 1, 40);
        $this->setMode(isset($parameterArray['fieldConf']['config']['format']) ? $parameterArray['fieldConf']['config']['format'] : T3editor::MODE_MIXED);

        $attributes = [];
        $attributes['rows'] = $rows;
        $attributes['wrap'] = 'off';
        $attributes['style'] = 'width:100%;';
        $attributes['onchange'] = GeneralUtility::quoteJSvalue($parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged']);

        $attributeString = '';
        foreach ($attributes as $param => $value) {
            $attributeString .= $param . '="' . htmlspecialchars($value) . '" ';
        }

        $this->resultArray['html'] = $this->getHTMLCodeForEditor(
            $parameterArray['itemFormElName'],
            'text-monospace enable-tab',
            $parameterArray['itemFormElValue'],
            $attributeString,
            $this->data['tableName'] . ' > ' . $this->data['fieldName'],
            ['target' => 0]
        );
        $this->resultArray['additionalJavaScriptPost'][] = 'require(["TYPO3/CMS/T3editor/T3editor"], function(T3editor) {T3editor.findAndInitializeEditors();});';

        $this->initJavascriptCode();
        return $this->resultArray;
    }

    /**
     * Sets the type of code to edit, use one of the predefined constants
     *
     * @param string $mode Expects one of the predefined constants
     * @throws \InvalidArgumentException
     */
    public function setMode($mode)
    {
        if (!in_array($mode, $this->allowedModes, true)) {
            throw new \InvalidArgumentException($mode . 'is not allowed', 1438352574);
        }
        $this->mode = $mode;
    }

    /**
     * Get mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Init the JavaScript code (header part) for editor
     */
    protected function initJavascriptCode()
    {
        $this->resultArray['stylesheetFiles'][] = $this->relExtPath . 'Resources/Public/Css/t3editor.css';
        if ($this->mode === self::MODE_TYPOSCRIPT) {
            foreach ($this->codeCompletionComponents as $codeCompletionComponent) {
                $this->resultArray['requireJsModules'][] = 'TYPO3/CMS/T3editor/Plugins/CodeCompletion/' . $codeCompletionComponent;
            }
        }
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
     * @return string Generated HTML code for editor
     */
    protected function getHTMLCodeForEditor($name, $class = '', $content = '', $additionalParams = '', $alt = '', array $hiddenfields = [])
    {
        $code = [];
        $attributes = [];
        $attributes['class'] = $class . ' t3editor';
        $attributes['alt'] = $alt;
        $attributes['id'] = 't3editor_' . $this->editorCounter;
        $attributes['name'] = $name;
        $attributes['data-labels'] = json_encode($this->getLanguageService()->getLabelsWithPrefix('js.', 'label_'));
        $attributes['data-instance-number'] =  $this->editorCounter;
        $attributes['data-editor-path'] =  $this->relExtPath;
        $attributes['data-codemirror-path'] =  $this->codemirrorPath;
        $attributes['data-ajaxsavetype'] = ''; // no ajax save in FormEngine at the moment
        $attributes['data-parserfile'] = $this->getParserfileByMode($this->mode);
        $attributes['data-stylesheet'] = $this->getStylesheetByMode($this->mode);

        $attributesString = '';
        foreach ($attributes as $attribute => $value) {
            $attributesString .= $attribute . '="' . htmlspecialchars($value) . '" ';
        }
        $attributesString .= $additionalParams;

        $code[] = '<div class="t3editor">';
        $code[] = '	<div class="t3e_wrap">';
        $code[] = str_replace([CR, LF], '', GeneralUtility::getUrl(GeneralUtility::getFileAbsFileName('EXT:t3editor/Resources/Private/Templates/t3editor.html')));
        $code[] = '	</div>';
        $code[] = '	<textarea ' . $attributesString . '>' . htmlspecialchars($content) . '</textarea>';
        $code[] = '</div>';

        if (!empty($hiddenfields)) {
            foreach ($hiddenfields as $name => $value) {
                $code[] = '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />';
            }
        }
        $this->editorCounter++;
        return implode(LF, $code);
    }

    /**
     * Determine the correct parser js file for given mode
     *
     * @param string $mode
     * @return string Parser file name
     */
    protected function getParserfileByMode($mode)
    {
        $parserfile = [];
        switch ($mode) {
            case self::MODE_TYPOSCRIPT:
                $relPath = '../../../parse_typoscript/';
                $parserfile = [$relPath . 'tokenizetyposcript.js', $relPath . 'parsetyposcript.js'];
                break;
            case self::MODE_JAVASCRIPT:
                $parserfile = ['tokenizejavascript.js', 'parsejavascript.js'];
                break;
            case self::MODE_CSS:
                $parserfile = ['parsecss.js'];
                break;
            case self::MODE_XML:
                $parserfile = ['parsexml.js'];
                break;
            case self::MODE_SPARQL:
                $parserfile = ['parsesparql.js'];
                break;
            case self::MODE_HTML:
                $parserfile = ['tokenizejavascript.js', 'parsejavascript.js', 'parsecss.js', 'parsexml.js', 'parsehtmlmixed.js'];
                break;
            case self::MODE_PHP:
            case self::MODE_MIXED:
                $parserfile = ['tokenizejavascript.js', 'parsejavascript.js', 'parsecss.js', 'parsexml.js', '../contrib/php/js/tokenizephp.js', '../contrib/php/js/parsephp.js', '../contrib/php/js/parsephphtmlmixed.js'];
                break;
        }
        return json_encode($parserfile);
    }

    /**
     * Determine the correct css file for given mode
     *
     * @param string $mode
     * @return string css file name
     */
    protected function getStylesheetByMode($mode)
    {
        switch ($mode) {
            case self::MODE_TYPOSCRIPT:
                $stylesheet = [$this->relExtPath . 'Resources/Public/Css/typoscriptcolors.css'];
                break;
            case self::MODE_JAVASCRIPT:
                $stylesheet = [$this->codemirrorPath . '../css/jscolors.css'];
                break;
            case self::MODE_CSS:
                $stylesheet = [$this->codemirrorPath . '../css/csscolors.css'];
                break;
            case self::MODE_XML:
                $stylesheet = [$this->codemirrorPath . '../css/xmlcolors.css'];
                break;
            case self::MODE_HTML:
                $stylesheet = [$this->codemirrorPath . '../css/xmlcolors.css', $this->codemirrorPath . '../css/jscolors.css', $this->codemirrorPath . '../css/csscolors.css'];
                break;
            case self::MODE_SPARQL:
                $stylesheet = [$this->codemirrorPath . '../css/sparqlcolors.css'];
                break;
            case self::MODE_PHP:
                $stylesheet = [$this->codemirrorPath . '../contrib/php/css/phpcolors.css'];
                break;
            case self::MODE_MIXED:
                $stylesheet = [$this->codemirrorPath . '../css/xmlcolors.css', $this->codemirrorPath . '../css/jscolors.css', $this->codemirrorPath . '../css/csscolors.css', $this->codemirrorPath . '../contrib/php/css/phpcolors.css'];
                break;
            default:
                $stylesheet = [];
        }
        $stylesheet[] = $this->relExtPath . 'Resources/Public/Css/t3editor_inner.css';
        return json_encode($stylesheet);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
