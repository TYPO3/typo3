<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Tobias Liebig <mail_typo3@etobi.de>
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
 * @author	Tobias Liebig <mail_typo3@etobi.de>
 */

$GLOBALS['LANG']->includeLLFile('EXT:t3editor/locallang.xml');

class tx_t3editor implements t3lib_Singleton {

	const MODE_TYPOSCRIPT = 'typoscript';
	const MODE_JAVASCRIPT = 'javascript';
	const MODE_CSS = 'css';
	const MODE_XML = 'xml';

	protected $mode = '';
	
	/**
	 * counts the editors on the current page
	 *
	 * @var		int
	 */
	protected $editorCounter = 0;

	/**
	 * flag to enable the t3editor
	 *
	 * @var		bool
	 */
	protected $_isEnabled = true;

	/**
	 * sets the type of code to edit (::MODE_TYPOSCRIPT, ::MODE_JAVASCRIPT)
	 *
	 * @param	$mode	string expects one of the predefined constants
	 * @return	tx_t3editor
	 */
	public function setMode($mode) {
		$this->mode = $mode;
		return $this;
	}

	/**
	 * @return	boolean		true if the t3editor is enabled
	 */
	public function isEnabled() {
		return $this->_isEnabled;
	}

	/**
	 * Creates a new instance of the class
	 *
	 * @return	void
	 */
	public function __construct() {
		$this->checkEditorIsDisabled();

			// disable pmktextarea to avoid conflicts (thanks Peter Klein for this suggestion)
		$GLOBALS["BE_USER"]->uc['disablePMKTextarea'] = 1;
	}

	/**
	 * check if the t3editor should be disabled (by a POST value)
	 */
	protected function checkEditorIsDisabled() {
		$editorIsDisabled = t3lib_div::_POST('t3editor_disableEditor');

		if (!empty($editorIsDisabled)) {
			$editorIsDisabled = ($editorIsDisabled == 'true');
		} else {
			$editorIsDisabled = $GLOBALS['BE_USER']->uc['disableT3Editor'];
		}

		if ($GLOBALS['BE_USER']->uc['disableT3Editor'] != $editorIsDisabled) {
			$GLOBALS['BE_USER']->uc['disableT3Editor'] = $editorIsDisabled;
			$GLOBALS['BE_USER']->writeUC();
		}
	}

	/**
	 * Retrieves JavaScript code (header part) for editor
	 *
	 * @param 	template	$doc
	 * @return	string		JavaScript code
	 */
	public function getJavascriptCode($doc) {
		$content = '';

		if ($this->isEnabled()) {

			$path_t3e = t3lib_extmgm::extRelPath('t3editor');

				// include needed javascript-frameworks
			/** @var $pageRenderer t3lib_PageRenderer */
			$pageRenderer = $doc->getPageRenderer();
			$pageRenderer->loadPrototype();
			$pageRenderer->loadScriptaculous();

				// include editor-css
			$content .= '<link href="' .
				t3lib_div::createVersionNumberedFilename($GLOBALS['BACK_PATH'] .
				t3lib_extmgm::extRelPath('t3editor') .
				'res/css/t3editor.css') .
				'" type="text/css" rel="stylesheet" />';

				// include editor-js-lib
			$doc->loadJavascriptLib($path_t3e . 'res/jslib/codemirror/codemirror.js');
			$doc->loadJavascriptLib($path_t3e . 'res/jslib/t3editor.js');

			if ($this->mode == self::MODE_TYPOSCRIPT) {
				$doc->loadJavascriptLib($path_t3e . 'res/jslib/ts_codecompletion/tsref.js');
				$doc->loadJavascriptLib($path_t3e . 'res/jslib/ts_codecompletion/completionresult.js');
				$doc->loadJavascriptLib($path_t3e . 'res/jslib/ts_codecompletion/tsparser.js');
				$doc->loadJavascriptLib($path_t3e . 'res/jslib/ts_codecompletion/tscodecompletion.js');
			}

			$content .= t3lib_div::wrapJS(
				'T3editor = T3editor || {};' .
				'T3editor.lang = ' . json_encode($this->getJavaScriptLabels()) .';' . LF.
				'T3editor.PATH_t3e = "' . $GLOBALS['BACK_PATH'] . t3lib_extmgm::extRelPath('t3editor') . '"; ' . LF.
				'T3editor.URL_typo3 = "' . htmlspecialchars(t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir) . '"; ' .LF.
				'T3editor.template = '. $this->getPreparedTemplate() .';' .LF.
				'T3editor.parserfile = ' . $this->getParserfileByMode($this->mode) . ';' .LF. 
				'T3editor.stylesheet = ' . $this->getStylesheetByMode($this->mode) . ';'
			);
		}

		return $content;
	}

	/**
	 * get the template code, prepared for javascript (no line breaks, quoted in slinge quotes)
	 *
	 * @return	string	the template code, prepared to use in javascript
	 */
	protected function getPreparedTemplate() {
		$T3Editor_template = t3lib_div::getURL(
			t3lib_div::getFileAbsFileName(
				'EXT:t3editor/res/templates/t3editor.html'
			)
		);
		$T3Editor_template = addslashes($T3Editor_template);
		$T3Editor_template = str_replace(LF, "' + '", $T3Editor_template);

		return '\'' . $T3Editor_template . '\'';
	}

	/**
	 * determine the correct parser js file for given mode
	 *
	 * @param	string	$mode
	 * @return	string	parser file name
	 */
	protected function getParserfileByMode($mode) {
		switch ($mode) {
			case tx_t3editor::MODE_TYPOSCRIPT:
				$parserfile = '["tokenizetyposcript.js", "parsetyposcript.js"]';
			break;

			case tx_t3editor::MODE_JAVASCRIPT:
				$parserfile = '["tokenizejavascript.js", "parsejavascript.js"]';
			break;

			case tx_t3editor::MODE_CSS:
				$parserfile = '"parsecss.js"';
			break;

			case tx_t3editor::MODE_XML:
				$parserfile = '"parsexml.js"';
			break;
		}
		return $parserfile;
	}

	/**
	 * determine the correct css file for given mode
	 *
	 * @param	string	$mode
	 * @return	string	css file name
	 */
	protected function getStylesheetByMode($mode) {
		switch ($mode) {
			case tx_t3editor::MODE_TYPOSCRIPT:
				$stylesheet = '"res/css/typoscriptcolors.css"';
			break;

			case tx_t3editor::MODE_JAVASCRIPT:
				$stylesheet = '"res/css/jscolors.css"';
			break;

			case tx_t3editor::MODE_CSS:
				$stylesheet = '"res/css/csscolors.css"';
			break;

			case tx_t3editor::MODE_XML:
				$stylesheet = '"res/css/xmlcolors.css"';
			break;
		}
		return '[T3editor.PATH_t3e + "res/css/t3editor_inner.css", T3editor.PATH_t3e + ' . $stylesheet . ']';
	}

	/**
	 * Gets the labels to be used in JavaScript in the Ext JS interface.
	 * TODO this method is copied from EXT:Recycler, maybe this should be refactored into a helper class
	 *
	 * @return	array		The labels to be used in JavaScript
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
	 * @param	string		$selectionPrefix: Prefix to select the correct labels (default: 'js.')
	 * @param	string		$stripFromSelectionName: Sub-prefix to be removed from label names in the result (default: '')
	 * @return	array		Lables to be used in JavaScript of the current locallang file
	 * @todo	Check, whether this method can be moved in a generic way to $GLOBALS['LANG']
	 */
	protected function getJavaScriptLabelsFromLocallang($selectionPrefix = 'js.', $stripFromSelectionName = '') {
		$extraction = array();
		$labels = array_merge(
			(array)$GLOBALS['LOCAL_LANG']['default'],
			(array)$GLOBALS['LOCAL_LANG'][$GLOBALS['LANG']->lang]
		);
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
	 * @param	string		$name	Name attribute of HTML tag
	 * @param	string		$class	Class attribute of HTML tag
	 * @param	string		$content	Content of the editor
	 * @param	string		$additionalParams	Any additional editor parameters
	 * @param	string		$alt	Alt attribute
	 * @return	string		Generated HTML code for editor
	 */
	public function getCodeEditor($name, $class='', $content='', $additionalParams='', $alt='', array $hiddenfields = array()) {
		$code = '';

		if ($this->isEnabled()) {
			$this->editorCounter++;

			$class .= ' t3editor';
			if (!empty($alt)) {
				$alt = ' alt="' . $alt . '"';
			}

			$code .= '<div>' .
				'<textarea id="t3editor_' . $this->editorCounter . '" ' .
				'name="' . $name . '" ' .
				'class="' . $class . '" ' .
				$additionalParams . ' ' .
				$alt . '>' .
				$content .
				'</textarea></div>';

			$checked = $GLOBALS['BE_USER']->uc['disableT3Editor'] ? 'checked="checked"' : '';

			$code .= '<br /><br />' .
				'<input type="checkbox" ' .
				'class="checkbox t3editor_disableEditor" ' .
				'onclick="T3editor.toggleEditor(this);" ' .
				'name="t3editor_disableEditor" ' .
				'value="true" ' .
				'id="t3editor_disableEditor_' . $this->editorCounter . '_checkbox" ' .
				$checked.' />&nbsp;' .
				'<label for="t3editor_disableEditor_' . $this->editorCounter . '_checkbox">' .
				$GLOBALS['LANG']->getLL('deactivate') .
				'</label>' .
				'<br /><br />';

			if (count($hiddenfields)) {
				foreach ($hiddenfields as $name => $value) {
					$code.= '<input type="hidden" ' .
						'name="' . $name . '" ' .
						'value="' . $value .
						'" />';
				}
			}

		} else {
			// fallback
			if (!empty($class)) {
				$class = 'class="' . $class . '" ';
			}

			$code .= '<textarea name="' . $name . '" ' .
				$class . $additionalParams.'>' .
				$content . '</textarea>';
		}

		return $code;
	}



	/**
	 * Save the content from t3editor retrieved via Ajax
	 *
	 * new Ajax.Request('/dev/t3e/dummy/typo3/ajax.php', {
	 * 	parameters: {
	 * 		ajaxID: 'tx_t3editor::saveCode',
	 *		t3editor_savetype: 'tx_tstemplateinfo'
	 *	}
	 * });
	 *
	 * @param array	params	Parameters (not used yet)
	 * @param TYPO3AJAX ajaxObj	AjaxObject to handle response
	 */
	public function ajaxSaveCode($params, $ajaxObj) {
		// cancel if its not an Ajax request
		if((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
			$ajaxObj->setContentFormat('json');
			$codeType = t3lib_div::_GP('t3editor_savetype');
			$savingsuccess = false;

			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/t3editor/classes/class.tx_t3editor.php']['ajaxSaveCode'])) {
				$_params = array(
					'pObj' => &$this,
					'type' => $codeType
				);
				foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/t3editor/classes/class.tx_t3editor.php']['ajaxSaveCode'] as $key => $_funcRef)	{
					$savingsuccess = t3lib_div::callUserFunction($_funcRef,$_params,$this) || $savingsuccess;
				}
			}

			$ajaxObj->setContent(array('result' => $savingsuccess));
		}
	}

	/**
	 * Gets plugins that are defined at $TYPO3_CONF_VARS['EXTCONF']['t3editor']['plugins']
	 * (called by typo3/ajax.php)
	 *
	 * @param	array		$params: additional parameters (not used here)
	 * @param	TYPO3AJAX	&$ajaxObj: the TYPO3AJAX object of this request
	 * @return	void
	 * @author	Oliver Hader <oliver@typo3.org>
	 */
	public function getPlugins($params, TYPO3AJAX &$ajaxObj) {
		$result = array();
		$plugins =& $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3editor']['plugins'];

		if (is_array($plugins)) {
			$result = array_values($plugins);
		}

 		$ajaxObj->setContent($result);
		$ajaxObj->setContentFormat('jsonbody');
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3editor/classes/class.tx_t3editor.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3editor/classes/class.tx_t3editor.php']);
}

?>