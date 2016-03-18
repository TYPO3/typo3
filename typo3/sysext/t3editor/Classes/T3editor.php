<?php
namespace TYPO3\CMS\T3editor;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides a javascript-driven code editor with syntax highlighting for TS, HTML, CSS and more
 */
class T3editor implements \TYPO3\CMS\Core\SingletonInterface
{
    const MODE_TYPOSCRIPT = 'typoscript';
    const MODE_JAVASCRIPT = 'javascript';
    const MODE_CSS = 'css';
    const MODE_XML = 'xml';
    const MODE_HTML = 'html';
    const MODE_PHP = 'php';
    const MODE_SPARQL = 'sparql';
    const MODE_MIXED = 'mixed';

    /**
     * @var string
     */
    protected $mode = '';

    /**
     * @var string
     */
    protected $ajaxSaveType = '';

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
     * Relative directory to codemirror
     *
     * @var string
     */
    protected $codemirrorPath = 'Resources/Public/JavaScript/Contrib/codemirror/js/';

    /**
     * RequireJS modules loaded for code completion
     *
     * @var array
     */
    protected $codeCompletionComponents = array('TsRef', 'CompletionResult', 'TsParser', 'TsCodeCompletion');

    /**
     * sets the type of code to edit (::MODE_TYPOSCRIPT, ::MODE_JAVASCRIPT)
     *
     * @param $mode	string Expects one of the predefined constants
     * @return \TYPO3\CMS\T3editor\T3editor
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Set the AJAX save type
     *
     * @param string $ajaxSaveType
     * @return \TYPO3\CMS\T3editor\T3editor
     */
    public function setAjaxSaveType($ajaxSaveType)
    {
        $this->ajaxSaveType = $ajaxSaveType;
        return $this;
    }

    /**
     * Set mode by file
     *
     * @param string $file
     * @return string
     */
    public function setModeByFile($file)
    {
        $fileInfo = GeneralUtility::split_fileref($file);
        // @TODO: @FIXME: the method setModeByType returns void, so this method will never return a string
        return $this->setModeByType($fileInfo['fileext']);
    }

    /**
     * Set mode by type
     *
     * @param string $type
     * @return void
     */
    public function setModeByType($type)
    {
        switch ($type) {
            case 'html':
            case 'htm':
            case 'tmpl':
                $mode = self::MODE_HTML;
                break;
            case 'js':
                $mode = self::MODE_JAVASCRIPT;
                break;
            case 'xml':
            case 'svg':
                $mode = self::MODE_XML;
                break;
            case 'css':
                $mode = self::MODE_CSS;
                break;
            case 'ts':
                $mode = self::MODE_TYPOSCRIPT;
                break;
            case 'sparql':
                $mode = self::MODE_SPARQL;
                break;
            case 'php':
            case 'phpsh':
            case 'inc':
                $mode = self::MODE_PHP;
                break;
            default:
                $mode = self::MODE_MIXED;
        }
        $this->setMode($mode);
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
     * Creates a new instance of the class
     */
    public function __construct()
    {
        $GLOBALS['LANG']->includeLLFile('EXT:t3editor/Resources/Private/Language/locallang.xlf');
        // Disable pmktextarea to avoid conflicts (thanks Peter Klein for this suggestion)
        $GLOBALS['BE_USER']->uc['disablePMKTextarea'] = 1;

        $this->relExtPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3editor');
        $this->codemirrorPath = $this->relExtPath . $this->codemirrorPath;
    }

    /**
     * Retrieves JavaScript code (header part) for editor
     *
     * @return string
     */
    public function getJavascriptCode()
    {
        /** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
        $pageRenderer = $this->getPageRenderer();
        $pageRenderer->addCssFile($this->relExtPath . 'Resources/Public/Css/t3editor.css');
        // Include editor-js-lib
        $pageRenderer->addJsLibrary('codemirror', $this->codemirrorPath . 'codemirror.js');
        if ($this->mode === self::MODE_TYPOSCRIPT) {
            foreach ($this->codeCompletionComponents as $codeCompletionComponent) {
                $pageRenderer->loadRequireJsModule('TYPO3/CMS/T3editor/Plugins/CodeCompletion/' . $codeCompletionComponent);
            }
        }
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/T3editor/T3editor');
        return '';
    }

    /**
     * Get the template code, prepared for javascript (no line breaks, quoted in single quotes)
     *
     * @return string The template code, prepared to use in javascript
     */
    protected function getPreparedTemplate()
    {
        $T3editor_template = file_get_contents(
            GeneralUtility::getFileAbsFileName('EXT:t3editor/Resources/Private/Templates/t3editor.html')
        );
        return str_replace(array(CR, LF), '', $T3editor_template);
    }

    /**
     * Determine the correct parser js file for given mode
     *
     * @param string $mode
     * @return string Parser file name
     */
    protected function getParserfileByMode($mode)
    {
        switch ($mode) {
            case self::MODE_TYPOSCRIPT:
                $relPath = '../../../parse_typoscript/';
                $parserfile = array($relPath . 'tokenizetyposcript.js', $relPath . 'parsetyposcript.js');
                break;
            case self::MODE_JAVASCRIPT:
                $parserfile = array('tokenizejavascript.js', 'parsejavascript.js');
                break;
            case self::MODE_CSS:
                $parserfile = array('parsecss.js');
                break;
            case self::MODE_XML:
                $parserfile = array('parsexml.js');
                break;
            case self::MODE_SPARQL:
                $parserfile = array('parsesparql.js');
                break;
            case self::MODE_HTML:
                $parserfile = array('tokenizejavascript.js', 'parsejavascript.js', 'parsecss.js', 'parsexml.js', 'parsehtmlmixed.js');
                break;
            case self::MODE_PHP:
            case self::MODE_MIXED:
                $parserfile = array('tokenizejavascript.js', 'parsejavascript.js', 'parsecss.js', 'parsexml.js', '../contrib/php/js/tokenizephp.js', '../contrib/php/js/parsephp.js', '../contrib/php/js/parsephphtmlmixed.js');
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
                $stylesheet = array($this->relExtPath . 'Resources/Public/Css/typoscriptcolors.css');
                break;
            case self::MODE_JAVASCRIPT:
                $stylesheet = array($this->codemirrorPath . '../css/jscolors.css');
                break;
            case self::MODE_CSS:
                $stylesheet = array($this->codemirrorPath . '../css/csscolors.css');
                break;
            case self::MODE_XML:
                $stylesheet = array($this->codemirrorPath . '../css/xmlcolors.css');
                break;
            case self::MODE_HTML:
                $stylesheet = array($this->codemirrorPath . '../css/xmlcolors.css', $this->codemirrorPath . '../css/jscolors.css', $this->codemirrorPath . '../css/csscolors.css');
                break;
            case self::MODE_SPARQL:
                $stylesheet = array($this->codemirrorPath . '../css/sparqlcolors.css');
                break;
            case self::MODE_PHP:
                $stylesheet = array($this->codemirrorPath . '../contrib/php/css/phpcolors.css');
                break;
            case self::MODE_MIXED:
                $stylesheet = array($this->codemirrorPath . '../css/xmlcolors.css', $this->codemirrorPath . '../css/jscolors.css', $this->codemirrorPath . '../css/csscolors.css', $this->codemirrorPath . '../contrib/php/css/phpcolors.css');
                break;
            default:
                $stylesheet = array();
        }
        $stylesheet[] = $this->relExtPath . 'Resources/Public/Css/t3editor_inner.css';
        return json_encode($stylesheet);
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
    public function getCodeEditor($name, $class = '', $content = '', $additionalParams = '', $alt = '', array $hiddenfields = array())
    {
        $code = '';
        $class .= ' t3editor';
        $alt = trim($alt);
        $code .=
            '<div class="t3editor">'
                . '<div class="t3e_wrap">'
                    . $this->getPreparedTemplate()
                . '</div>'
                . '<textarea '
                    . 'id="t3editor_' . (int)$this->editorCounter . '" '
                    . 'name="' . htmlspecialchars($name) . '" '
                    . 'class="' . htmlspecialchars($class) . '" '
                    . $additionalParams . ' '
                    . ($alt !== '' ? ' alt="' . htmlspecialchars($alt) . '"' : '')
                    . ' data-labels="' . htmlspecialchars(json_encode($GLOBALS['LANG']->getLabelsWithPrefix('js.', 'label_'))) . '"'
                    . ' data-instance-number="' . (int)$this->editorCounter . '"'
                    . ' data-editor-path="' . htmlspecialchars($this->relExtPath) . '"'
                    . ' data-codemirror-path="' . htmlspecialchars($this->codemirrorPath) . '"'
                    . ' data-ajaxsavetype="' . htmlspecialchars($this->ajaxSaveType) . '"'
                    . ' data-parserfile="' . htmlspecialchars($this->getParserfileByMode($this->mode)) . '"'
                    . ' data-stylesheet="' . htmlspecialchars($this->getStylesheetByMode($this->mode)) . '"'
                    . '>' . htmlspecialchars($content)
                . '</textarea>'
            . '</div>';
        if (!empty($hiddenfields)) {
            foreach ($hiddenfields as $name => $value) {
                $code .= '<input type="hidden" ' . 'name="' . htmlspecialchars($name) . '" ' . 'value="' . htmlspecialchars($value) . '" />';
            }
        }
        $this->editorCounter++;
        return $code;
    }

    /**
     * Save the content from t3editor retrieved via Ajax
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function ajaxSaveCode(ServerRequestInterface $request, ResponseInterface $response)
    {
        // cancel if its not an Ajax request
        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
            $codeType = isset($request->getParsedBody()['t3editor_savetype']) ? $request->getParsedBody()['t3editor_savetype'] : $request->getQueryParams()['t3editor_savetype'];
            $savingsuccess = false;
            try {
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/t3editor/classes/class.tx_t3editor.php']['ajaxSaveCode'])) {
                    $_params = array(
                        'pObj' => &$this,
                        'type' => $codeType,
                        'request' => $request,
                        'response' => $response
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/t3editor/classes/class.tx_t3editor.php']['ajaxSaveCode'] as $key => $_funcRef) {
                        $savingsuccess = GeneralUtility::callUserFunction($_funcRef, $_params, $this) || $savingsuccess;
                    }
                }
                $responseContent = array('result' => $savingsuccess);
            } catch (\Exception $e) {
                $responseContent = array(
                    'result' => false,
                    'exceptionMessage' => htmlspecialchars($e->getMessage()),
                    'exceptionCode' => $e->getCode()
                );
            }
            /** @var \TYPO3\CMS\Core\Http\Response $response */
            $response = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\Response::class);
            $response->getBody()->write(json_encode($responseContent));
        }

        return $response;
    }

    /**
     * Gets plugins that are defined at $TYPO3_CONF_VARS['EXTCONF']['t3editor']['plugins']
     * Called by AjaxRequestHandler
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getPlugins(ServerRequestInterface $request, ResponseInterface $response)
    {
        $result = array();
        $plugins = &$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3editor']['plugins'];
        if (is_array($plugins)) {
            $result = array_values($plugins);
        }
        $request->getBody()->write(json_encode($result));
        return $request;
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
