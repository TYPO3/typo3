<?php
namespace TYPO3\CMS\T3Editor;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Tobias Liebig <mail_typo3@etobi.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Provides a javascript-driven code editor with syntax highlighting for TS, HTML, CSS and more
 *
 * @author Tobias Liebig <mail_typo3@etobi.de>
 */
class T3Editor implements \TYPO3\CMS\Core\SingletonInterface {

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
	 * @var integer
	 */
	protected $editorCounter = 0;

	/**
	 * Flag to enable the t3editor
	 *
	 * @var boolean
	 */
	protected $_isEnabled = TRUE;

	/**
	 * sets the type of code to edit (::MODE_TYPOSCRIPT, ::MODE_JAVASCRIPT)
	 *
	 * @param $mode	string Expects one of the predefined constants
	 * @return \TYPO3\CMS\T3Editor\T3Editor
	 */
	public function setMode($mode) {
		$this->mode = $mode;
		return $this;
	}

	/**
	 * Set the AJAX save type
	 *
	 * @param string $ajaxSaveType
	 * @return \TYPO3\CMS\T3Editor\T3Editor
	 */
	public function setAjaxSaveType($ajaxSaveType) {
		$this->ajaxSaveType = $ajaxSaveType;
		return $this;
	}

	/**
	 * Set mode by file
	 *
	 * @param string $file
	 * @return string
	 */
	public function setModeByFile($file) {
		$fileInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($file);
		return $this->setModeByType($fileInfo['fileext']);
	}

	/**
	 * Set mode by type
	 *
	 * @param string $type
	 * @return void
	 */
	public function setModeByType($type) {
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
	public function getMode() {
		return $this->mode;
	}

	/**
	 * @return boolean TRUE if the t3editor is enabled
	 */
	public function isEnabled() {
		return $this->_isEnabled;
	}

	/**
	 * Creates a new instance of the class
	 */
	public function __construct() {
		// Disable pmktextarea to avoid conflicts (thanks Peter Klein for this suggestion)
		$GLOBALS['BE_USER']->uc['disablePMKTextarea'] = 1;
	}

	/**
	 * Retrieves JavaScript code (header part) for editor
	 *
	 * @param \TYPO3\CMS\Backend\Template\DocumentTemplate $doc
	 * @return string JavaScript code
	 */
	public function getJavascriptCode($doc) {
		$content = '';
		if ($this->isEnabled()) {
			$path_t3e = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3editor');
			$path_codemirror = 'contrib/codemirror/js/';
			// Include needed javascript-frameworks
			$pageRenderer = $doc->getPageRenderer();
			/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
			$pageRenderer->loadPrototype();
			$pageRenderer->loadScriptaculous();
			// Include editor-css
			$content .= '<link href="' . \TYPO3\CMS\Core\Utility\GeneralUtility::createVersionNumberedFilename(($GLOBALS['BACK_PATH'] . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3editor') . 'res/css/t3editor.css')) . '" type="text/css" rel="stylesheet" />';
			// Include editor-js-lib
			$doc->loadJavascriptLib($path_codemirror . 'codemirror.js');
			$doc->loadJavascriptLib($path_t3e . 'res/jslib/t3editor.js');

			$content .= \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS(
				'T3editor = T3editor || {};' .
				'T3editor.lang = ' . json_encode($this->getJavaScriptLabels()) . ';' . LF .
				'T3editor.PATH_t3e = "' . $GLOBALS['BACK_PATH'] . $path_t3e . '"; ' . LF .
				'T3editor.PATH_codemirror = "' . $GLOBALS['BACK_PATH'] . $path_codemirror . '"; ' . LF .
				'T3editor.URL_typo3 = "' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir) . '"; ' . LF .
				'T3editor.template = ' . $this->getPreparedTemplate() . ';' . LF .
				'T3editor.ajaxSavetype = "' . $this->ajaxSaveType . '";' . LF
			);
			$content .= $this->getModeSpecificJavascriptCode();
		}
		return $content;
	}

	/**
	 * Get mode specific JavaScript code
	 *
	 * @return string
	 */
	public function getModeSpecificJavascriptCode() {
		if (empty($this->mode)) {
			return '';
		}
		$path_t3e = $GLOBALS['BACK_PATH'] . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3editor');
		$content = '';
		if ($this->mode === self::MODE_TYPOSCRIPT) {
			$content .= '<script type="text/javascript" src="' . $path_t3e . 'res/jslib/ts_codecompletion/tsref.js' . '"></script>';
			$content .= '<script type="text/javascript" src="' . $path_t3e . 'res/jslib/ts_codecompletion/completionresult.js' . '"></script>';
			$content .= '<script type="text/javascript" src="' . $path_t3e . 'res/jslib/ts_codecompletion/tsparser.js' . '"></script>';
			$content .= '<script type="text/javascript" src="' . $path_t3e . 'res/jslib/ts_codecompletion/tscodecompletion.js' . '"></script>';
		}
		$content .= \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS('T3editor.parserfile = ' . $this->getParserfileByMode($this->mode) . ';' . LF . 'T3editor.stylesheet = ' . $this->getStylesheetByMode($this->mode) . ';');
		return $content;
	}

	/**
	 * Get the template code, prepared for javascript (no line breaks, quoted in single quotes)
	 *
	 * @return string The template code, prepared to use in javascript
	 */
	protected function getPreparedTemplate() {
		$T3Editor_template = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:t3editor/res/templates/t3editor.html'));
		$T3Editor_template = addslashes($T3Editor_template);
		$T3Editor_template = str_replace(array(CR, LF), array('', '\' + \''), $T3Editor_template);
		return '\'' . $T3Editor_template . '\'';
	}

	/**
	 * Determine the correct parser js file for given mode
	 *
	 * @param string $mode
	 * @return string Parser file name
	 */
	protected function getParserfileByMode($mode) {
		switch ($mode) {
		case self::MODE_TYPOSCRIPT:
			$relPath = ($GLOBALS['BACK_PATH'] ? $GLOBALS['BACK_PATH'] : '../../../') . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3editor') . 'res/jslib/parse_typoscript/';
			$parserfile = '["' . $relPath . 'tokenizetyposcript.js", "' . $relPath . 'parsetyposcript.js"]';
			break;
		case self::MODE_JAVASCRIPT:
			$parserfile = '["tokenizejavascript.js", "parsejavascript.js"]';
			break;
		case self::MODE_CSS:
			$parserfile = '"parsecss.js"';
			break;
		case self::MODE_XML:
			$parserfile = '"parsexml.js"';
			break;
		case self::MODE_SPARQL:
			$parserfile = '"parsesparql.js"';
			break;
		case self::MODE_HTML:
			$parserfile = '["tokenizejavascript.js", "parsejavascript.js", "parsecss.js", "parsexml.js", "parsehtmlmixed.js"]';
			break;
		case self::MODE_PHP:

		case self::MODE_MIXED:
			$parserfile = '[' . '"tokenizejavascript.js", ' . '"parsejavascript.js", ' . '"parsecss.js", ' . '"parsexml.js", ' . '"../contrib/php/js/tokenizephp.js", ' . '"../contrib/php/js/parsephp.js", ' . '"../contrib/php/js/parsephphtmlmixed.js"' . ']';
			break;
		}
		return $parserfile;
	}

	/**
	 * Determine the correct css file for given mode
	 *
	 * @param string $mode
	 * @return string css file name
	 */
	protected function getStylesheetByMode($mode) {
		switch ($mode) {
		case self::MODE_TYPOSCRIPT:
			$stylesheet = 'T3editor.PATH_t3e + "res/css/typoscriptcolors.css"';
			break;
		case self::MODE_JAVASCRIPT:
			$stylesheet = 'T3editor.PATH_codemirror + "../css/jscolors.css"';
			break;
		case self::MODE_CSS:
			$stylesheet = 'T3editor.PATH_codemirror + "../css/csscolors.css"';
			break;
		case self::MODE_XML:
			$stylesheet = 'T3editor.PATH_codemirror + "../css/xmlcolors.css"';
			break;
		case self::MODE_HTML:
			$stylesheet = 'T3editor.PATH_codemirror + "../css/xmlcolors.css", ' . 'T3editor.PATH_codemirror + "../css/jscolors.css", ' . 'T3editor.PATH_codemirror + "../css/csscolors.css"';
			break;
		case self::MODE_SPARQL:
			$stylesheet = 'T3editor.PATH_codemirror + "../css/sparqlcolors.css"';
			break;
		case self::MODE_PHP:
			$stylesheet = 'T3editor.PATH_codemirror + "../contrib/php/css/phpcolors.css"';
			break;
		case self::MODE_MIXED:
			$stylesheet = 'T3editor.PATH_codemirror + "../css/xmlcolors.css", ' . 'T3editor.PATH_codemirror + "../css/jscolors.css", ' . 'T3editor.PATH_codemirror + "../css/csscolors.css", ' . 'T3editor.PATH_codemirror + "../contrib/php/css/phpcolors.css"';
			break;
		}
		if ($stylesheet != '') {
			$stylesheet = '' . $stylesheet . ', ';
		}
		return '[' . $stylesheet . 'T3editor.PATH_t3e + "res/css/t3editor_inner.css"]';
	}

	/**
	 * Gets the labels to be used in JavaScript in the Ext JS interface.
	 * TODO this method is copied from EXT:Recycler, maybe this should be refactored into a helper class
	 *
	 * @return array The labels to be used in JavaScript
	 */
	protected function getJavaScriptLabels() {
		$coreLabels = array();
		$extensionLabels = $this->getJavaScriptLabelsFromLocallang('js.', 'label_');
		return array_merge($coreLabels, $extensionLabels);
	}

	/**
	 * Gets labels to be used in JavaScript fetched from the current locallang file.
	 * TODO this method is copied from EXT:Recycler, maybe this should be refactored into a helper class
	 *
	 * @param string $selectionPrefix Prefix to select the correct labels (default: 'js.')
	 * @param string $stripFromSelectionName Sub-prefix to be removed from label names in the result (default: '')
	 * @return array Lables to be used in JavaScript of the current locallang file
	 * @todo Check, whether this method can be moved in a generic way to $GLOBALS['LANG']
	 */
	protected function getJavaScriptLabelsFromLocallang($selectionPrefix = 'js.', $stripFromSelectionName = '') {
		$extraction = array();
		$labels = array_merge((array) $GLOBALS['LOCAL_LANG']['default'], (array) $GLOBALS['LOCAL_LANG'][$GLOBALS['LANG']->lang]);
		// Regular expression to strip the selection prefix and possibly something from the label name:
		$labelPattern = '#^' . preg_quote($selectionPrefix, '#') . '(' . preg_quote($stripFromSelectionName, '#') . ')?#';
		// Iterate throuh all locallang lables:
		foreach ($labels as $label => $value) {
			if (strpos($label, $selectionPrefix) === 0) {
				$key = preg_replace($labelPattern, '', $label);
				$extraction[$key] = $value;
			}
		}
		return $extraction;
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
	public function getCodeEditor($name, $class = '', $content = '', $additionalParams = '', $alt = '', array $hiddenfields = array()) {
		$code = '';
		if ($this->isEnabled()) {
			$this->editorCounter++;
			$class .= ' t3editor';
			$alt = htmlspecialchars($alt);
			if (!empty($alt)) {
				$alt = ' alt="' . $alt . '"';
			}
			$code .= '<div>' . '<textarea id="t3editor_' . $this->editorCounter . '" ' . 'name="' . $name . '" ' . 'class="' . $class . '" ' . $additionalParams . ' ' . $alt . '>' . htmlspecialchars($content) . '</textarea></div>';
			$checked = $GLOBALS['BE_USER']->uc['disableT3Editor'] ? 'checked="checked"' : '';
			$code .= '<br /><br />' . '<input type="checkbox" ' . 'class="checkbox t3editor_disableEditor" ' . 'onclick="T3editor.toggleEditor(this);" ' . 'name="t3editor_disableEditor" ' . 'value="true" ' . 'id="t3editor_disableEditor_' . $this->editorCounter . '_checkbox" ' . $checked . ' />&nbsp;' . '<label for="t3editor_disableEditor_' . $this->editorCounter . '_checkbox">' . $GLOBALS['LANG']->getLL('deactivate') . '</label>' . '<br /><br />';
			if (count($hiddenfields)) {
				foreach ($hiddenfields as $name => $value) {
					$code .= '<input type="hidden" ' . 'name="' . $name . '" ' . 'value="' . $value . '" />';
				}
			}
		} else {
			// Fallback
			if (!empty($class)) {
				$class = 'class="' . $class . '" ';
			}
			$code .= '<textarea name="' . $name . '" ' . $class . $additionalParams . '>' . $content . '</textarea>';
		}
		return $code;
	}

	/**
	 * Save the content from t3editor retrieved via Ajax
	 *
	 * new Ajax.Request('/dev/t3e/dummy/typo3/ajax.php', {
	 * parameters: {
	 * ajaxID: 'T3Editor::saveCode',
	 * t3editor_savetype: 'tx_tstemplateinfo'
	 * }
	 * });
	 *
	 * @param array $params Parameters (not used yet)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler ajaxObj AjaxObject to handle response
	 */
	public function ajaxSaveCode($params, $ajaxObj) {
		// cancel if its not an Ajax request
		if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
			$ajaxObj->setContentFormat('json');
			$codeType = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('t3editor_savetype');
			$savingsuccess = FALSE;
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/t3editor/classes/class.tx_t3editor.php']['ajaxSaveCode'])) {
				$_params = array(
					'pObj' => &$this,
					'type' => $codeType,
					'ajaxObj' => &$ajaxObj
				);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/t3editor/classes/class.tx_t3editor.php']['ajaxSaveCode'] as $key => $_funcRef) {
					$savingsuccess = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this) || $savingsuccess;
				}
			}
			$ajaxObj->setContent(array('result' => $savingsuccess));
		}
	}

	/**
	 * Gets plugins that are defined at $TYPO3_CONF_VARS['EXTCONF']['t3editor']['plugins']
	 * (called by typo3/ajax.php)
	 *
	 * @param array $params additional parameters (not used here)
	 * @param TYPO3AJAX	&$ajaxObj: the TYPO3AJAX object of this request
	 * @return void
	 * @author Oliver Hader <oliver@typo3.org>
	 */
	public function getPlugins($params, \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj) {
		$result = array();
		$plugins = &$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3editor']['plugins'];
		if (is_array($plugins)) {
			$result = array_values($plugins);
		}
		$ajaxObj->setContent($result);
		$ajaxObj->setContentFormat('jsonbody');
	}

}


?>