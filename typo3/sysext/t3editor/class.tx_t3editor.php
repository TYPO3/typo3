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
			$code.= '<link href="' . 
				$GLOBALS['BACK_PATH'] .  
				t3lib_extmgm::extRelPath('t3editor') . 
				$this->filepathEditorcss . 
				'" type="text/css" rel="stylesheet" />';

				// include editor-js-lib
			$doc->loadJavascriptLib($path_t3e . 'jslib/codemirror/codemirror.js');
			$doc->loadJavascriptLib($path_t3e . 'jslib/t3editor.js');

			// set correct path to the editor
			$code.= '<script type="text/javascript">' .
				'PATH_t3e = "' .
				$GLOBALS['BACK_PATH'] . t3lib_extmgm::extRelPath('t3editor') . '"; ' .
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
	public function getCodeEditor($name, $class='', $content='', $additionalParams='', $alt='', array $hiddenfields = array()) {
		$code = '';

		if ($this->isEnabled) {
			$this->editorCounter++;

			$class .= ' t3editor';
			$alt = htmlspecialchars($alt);
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

			$code .= '<br/><br/>' .
				'<input type="checkbox" ' .
				'class="checkbox" ' .
				'onclick="t3editor_toggleEditor(this);" ' .
				'name="t3editor_disableEditor" ' .
				'value="true" ' .
				'id="t3editor_disableEditor_' . $this->editorCounter.'_checkbox" ' .
				$checked.' />&nbsp;' .
				'<label for="t3editor_disableEditor_' . $this->editorCounter . '_checkbox">' .
				'deactivate t3editor' .
				'</label>' .
				'<br/><br/>';
			
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
	public function postOutputProcessingHook($parameters, $pObj) {
		tx_t3editor::makeGlobalEditorInstance();
		if (!$GLOBALS['T3_VAR']['t3editorObj']->isEnabled) {
			return;
		}

		// Template Constants
		if ($parameters['e']['constants']) {
			$attributes = 'rows="' . $parameters['numberOfRows'] . '" ' . 
				'wrap="off" ' . 
				$pObj->pObj->doc->formWidthText(48, 'width:98%;height:60%', 'off');

			$title = 'Template: ' . htmlspecialchars($parameters['tplRow']['title']) . ': Constants';

			$outCode = $GLOBALS['T3_VAR']['t3editorObj']->getCodeEditor(
						'data[constants]',
						'fixed-font enable-tab',
						'$1',
						$attributes,
						$title,
						array(
							'pageId' => intval($pObj->pObj->id),
							't3editor_savetype' => 'tx_tstemplateinfo',
						)
					);
			$parameters['theOutput'] = preg_replace(
				'/\<textarea name="data\[constants\]".*\>([^\<]*)\<\/textarea\>/mi',
				$outCode,
				$parameters['theOutput']
				);
		}

		// Template Setup
		if ($parameters['e']['config']) {
			$attributes = 'rows="' . $parameters['numberOfRows'] . '" ' .
				'wrap="off" ' . 
				$pObj->pObj->doc->formWidthText(48, 'width:98%;height:60%', 'off');

			$title = 'Template: ' . htmlspecialchars($parameters['tplRow']['title']) . ': Setup';

			$outCode = $GLOBALS['T3_VAR']['t3editorObj']->getCodeEditor(
						'data[config]',
						'fixed-font enable-tab',
						'$1',
						$attributes,
						$title,
						array(
							'pageId' => intval($pObj->pObj->id),
							't3editor_savetype' => 'tx_tstemplateinfo',
						)
					);
			$parameters['theOutput'] = preg_replace(
				'/\<textarea name="data\[config\]".*\>([^\<]*)\<\/textarea\>/mi',
				$outCode,
				$parameters['theOutput']
				);
		}
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
	public function saveCode($params, $ajaxObj) {
		// cancel if its not an Ajax request
		if((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
			$ajaxObj->setContentFormat('json');
			$codeType = t3lib_div::_GP('t3editor_savetype');
			$savingsuccess = false;
			
			switch ($codeType) {
				case 'tx_tstemplateinfo':
					$savingsuccess = $this->saveCodeTsTemplateInfo();
					break;
					
				// TODO: fileadmin, extmng, TCEform, ...
				
				default:
					$ajaxObj->setError('Unknown content type: ' . $codeType);
			}
			$ajaxObj->setContent(array('result' => $savingsuccess));
		}
	}
	
	/**
	 * Process saving request like in class.tstemplateinfo.php (TCE processing)
	 *
	 * @return boolean true if successful
	 */
	public function saveCodeTsTemplateInfo() {
		$savingsuccess = false;
		
		$pageId = t3lib_div::_GP('pageId');
		
		if (!is_numeric($pageId) || $pageId < 1) {
			return false;
		}
		
		// if given use the requested template_uid
		// if not, use the first template-record on the page (in this case there should only be one record!)
		$set = t3lib_div::_GP('SET');
		$template_uid = $set['templatesOnPage'] ? $set['templatesOnPage'] : 0;
		
		$tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');	// Defined global here!
		$tmpl->tt_track = 0;	// Do not log time-performance information
		$tmpl->init();
		
		// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
		$tplRow = $tmpl->ext_getFirstTemplate($pageId, $template_uid);	
		$existTemplate =  (is_array($tplRow) ? true : false);
	
		if ($existTemplate)	{
			$saveId = ($tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid']);

			// Update template ?
			$POST = t3lib_div::_POST();
			
			if ($POST['submit']) {
				require_once(PATH_t3lib . 'class.t3lib_tcemain.php');

				// Set the data to be saved
				$recData = array();

				if (is_array($POST['data'])) {
					foreach ($POST['data'] as $field => $val) {
						switch ($field) {
							case 'constants':
							case 'config':
							case 'title':
							case 'sitetitle':
							case 'description':
								$recData['sys_template'][$saveId][$field] = $val;
								break;
						}
					}
				}
				if (count($recData)) {
					// Create new tce-object
					$tce = t3lib_div::makeInstance('t3lib_TCEmain');
					$tce->stripslashes_values = 0;

					// Initialize
					$tce->start($recData, array());

					// Saved the stuff
					$tce->process_datamap();

					// Clear the cache (note: currently only admin-users can clear the 
					// cache in tce_main.php)
					$tce->clear_cacheCmd('all');
					
					$savingsuccess = true;
				}
			}
		}
		return $savingsuccess;	
	}
}


	// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3editor/class.tx_t3editor.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3editor/class.tx_t3editor.php']);
}

?>
