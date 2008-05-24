<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Tobias Liebig <mail_typo3@etobi.de>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   47: class tx_t3editor
 *   85:     public function __construct()
 *  117:     public function getJavascriptCode()
 *  162:     public function getCodeEditor($name, $class='', $content='', $additionalParams='', $alt='')
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * Provides a javascript-driven code editor with syntax highlighting for TS, HTML, CSS and more
 *
 * @author	Tobias Liebig <mail_typo3@etobi.de>
 */
class tx_t3editor {

	/**
	 * path to the main javascript-file
	 *
	 * @var string
	 */
	protected $filepathEditorlib;

	/**
	 * path to the main stylesheet
	 *
	 * @TODO: make it configurable
	 *
	 * @var string
	 */
	protected $filepathEditorcss;

	/**
	 * counts the editors on the current page
	 *
	 * @var int
	 */
	protected $editorCounter;

	/**
	 * flag to enable the t3editor
	 *
	 * @var bool
	 */
	public $isEnabled;


	/**
	 * Creates a new instance of the class
	 *
	 * @return	void
	 */
	public function __construct() {

		$this->filepathEditorlib = 'jslib/t3editor.js';
		$this->filepathEditorcss = 'css/t3editor.css';
		$this->editorCounter     = 0;
		$this->isEnabled         = true; //TODO add a method to switch to false and turn off the editor completly

			// check BE user settings
			//TODO give $state a more descriptive name / state of/for what?
		$state = t3lib_div::_GP('t3editor_disableEditor') == 'true' ? true : $GLOBALS['BE_USER']->uc['disableT3Editor'];
		$this->setBEUCdisableT3Editor($state);

			// disable pmktextarea to avoid conflicts (thanks Peter Klein for this suggestion)
		$GLOBALS["BE_USER"]->uc['disablePMKTextarea'] = 1;
	}

	/**
	 * Sets editor enabled/disabled state
	 *
	 * @param	boolean		$state	<code>true</code> if editor is disabled
	 * @return	void
	 */
	public function setBEUCdisableT3Editor($state) { //TODO better descriptive name for $state
		if ($GLOBALS['BE_USER']->uc['disableT3Editor'] != $state) {
			$GLOBALS['BE_USER']->uc['disableT3Editor'] = $state;

			$GLOBALS['BE_USER']->writeUC();
		}
	}

	/**
	 * Retrieves JavaScript code for editor
	 *
	 * @param 	template	$doc
	 * @return	string		JavaScript code
	 */
	public function getJavascriptCode($doc) {
		$code = ''; // TODO find a more descriptive name (low prio)

		if ($this->isEnabled) {

			$path_t3e = t3lib_extmgm::extRelPath('t3editor');

				// include needed javascript-frameworks
			$doc->loadJavascriptLib('contrib/prototype/prototype.js');
			$doc->loadJavascriptLib('contrib/scriptaculous/scriptaculous.js');

				// include editor-css
			$code.= '<link href="'.$GLOBALS['BACK_PATH'].t3lib_extmgm::extRelPath('t3editor').$this->filepathEditorcss.'" type="text/css" rel="stylesheet" />';
				// include editor-js-lib
			$doc->loadJavascriptLib($path_t3e.'jslib/codemirror/codemirror.js');
			$doc->loadJavascriptLib($path_t3e.'jslib/t3editor.js');

			// set correct path to the editor
			$code.= '<script type="text/javascript">'.
				'PATH_t3e = "'.$GLOBALS['BACK_PATH']. t3lib_extmgm::extRelPath('t3editor').'"; '.
				'</script>';

		}

		return $code;
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
	public function getCodeEditor($name, $class='', $content='', $additionalParams='', $alt='') {
		$code = '';

		if ($this->isEnabled) {
			$this->editorCounter++;

			$class .= ' t3editor';
			if(!empty($alt)) {
				$alt = ' alt="'.$alt.'"';
			}

			$code.= '<div>
				<textarea id="t3editor_'.$this->editorCounter.'" name="'.$name.'" class="'.$class.'" '.$additionalParams.' '.$alt.'>'
				.$content
				.'</textarea></div>';

			$checked = $GLOBALS['BE_USER']->uc['disableT3Editor'] ? 'checked="checked"' : '';
			$code.= '<br/><br/>
				<input type="checkbox" onclick="t3editor_toggleEditor(this);" name="t3editor_disableEditor" value="true" id="t3editor_disableEditor_'.$this->editorCounter.'_checkbox" '.$checked.' />&nbsp;
				<label for="t3editor_disableEditor_'.$this->editorCounter.'_checkbox">deactivate t3editor</label>
				<input type="hidden" name="submitAjax" id="submitAjax" value="0" />
				<br/><br/>';

		} else {
				// fallback
			if (!empty($class)) {
				$class = 'class="'.$class.'" ';
			}

			$code .= '<textarea name="'.$name.'" '.$class.$additionalParams.'>'.$content.'</textarea>';
		}

		return $code;
	}


	public function makeGlobalEditorInstance() {
		if (!is_object($GLOBALS['T3_VAR']['t3editorObj'])) {
			$GLOBALS['T3_VAR']['t3editorObj'] = t3lib_div::getUserObj('EXT:t3editor/class.tx_t3editor.php:&tx_t3editor');
		}
	}

	/**
	 * Hook-function: inject t3editor JavaScript code before the page is compiled
	 * called in typo3/template.php:startPage
	 *
	 * @param array $parameters
	 * @param template $pObj
	 */
	public function preStartPageHook($parameters, $pObj) {
			// enable editor in Template-Modul
		if (preg_match('/sysext\/tstemplate\/ts\/index\.php/', $_SERVER['SCRIPT_NAME'])) {

			tx_t3editor::makeGlobalEditorInstance();

			// insert javascript code in document header
			$pObj->JScode .= $GLOBALS['T3_VAR']['t3editorObj']->getJavascriptCode($pObj);
		}
	}


	/**
	 * Hook-function:
	 * called in typo3/sysext/tstemplate_info/class.tx_tstemplateinfo.php
	 *
	 * @param array $parameters
	 * @param tx_tstemplateinfo $pObj
	 */
	public function postTCEProcessingHook($parameters, $pObj) {
		if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
			tx_t3editor::makeGlobalEditorInstance();
			$GLOBALS['T3_VAR']['t3editorObj']->setBEUCdisableT3Editor(false);

			header('X-JSON: ('.t3lib_div::array2json(array('result' => $pObj->tce_processed)).')');
			// Stop further processing here!
			exit();
		}
	}

	/**
	 * Hook-function:
	 * called in typo3/sysext/tstemplate_info/class.tx_tstemplateinfo.php
	 *
	 * @param array $parameters
	 * @param tx_tstemplateinfo $pObj
	 */
	public function postOutputProcessingHook($parameters, $pObj) {
		tx_t3editor::makeGlobalEditorInstance();
		if (!$GLOBALS['T3_VAR']['t3editorObj']->isEnabled) {
			return;
		}

		// Template Constants
		if ($parameters['e']['constants']) {
			$outCode = $GLOBALS['T3_VAR']['t3editorObj']->getCodeEditor(
						'data[constants]',	// name
						'fixed-font enable-tab',	// class
						t3lib_div::formatForTextarea($parameters['tplRow']['constants']),	// content
						'rows="'.$parameters['numberOfRows'].'" wrap="off" '.$pObj->pObj->doc->formWidthText(48, 'width:98%;height:60%', 'off'),
						'Template: '.htmlspecialchars($parameters['tplRow']['title']).': Constants' // title
					);
			$parameters['theOutput'] = preg_replace(
				'/\<textarea name="data\[constants\]".*\>([^\<]*)\<\/textarea\>/mi',
				$outCode,
				$parameters['theOutput']
				);
		}

		// Template Setup
		if ($parameters['e']['config']) {
			$outCode = $GLOBALS['T3_VAR']['t3editorObj']->getCodeEditor(
						'data[config]',	// name
						'fixed-font enable-tab',	// class
						t3lib_div::formatForTextarea($parameters['tplRow']['config']),	// content
						'rows="'.$parameters['numberOfRows'].'" wrap="off" '.$pObj->pObj->doc->formWidthText(48, 'width:98%;height:60%', 'off'),
						'Template: '.htmlspecialchars($parameters['tplRow']['title']).': Setup' // title
					);
			$parameters['theOutput'] = preg_replace(
				'/\<textarea name="data\[config\]".*\>([^\<]*)\<\/textarea\>/mi',
				$outCode,
				$parameters['theOutput']
				);
		}
	}


}


	// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3editor/class.tx_t3editor.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3editor/class.tx_t3editor.php']);
}

?>