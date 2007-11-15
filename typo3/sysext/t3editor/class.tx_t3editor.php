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
	private $filepathEditorlib;
	
	/**
	 * path to the main stylesheet
	 *
	 * @TODO: make it configurable
	 *
	 * @var string
	 */
	private $filepathEditorcss;

	/**
	 * counts the editors on the current page
	 *
	 * @var int
	 */
	private $editorCounter;
	
	/**
	 * flag to enable the t3editor
	 *
	 * @var bool
	 */
	public $isEnabled;


	/**
	 * constructor
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
	}

	/**
	 * Enter description here...
	 *
	 * @param	boolean		$state
	 * @return	void
	 */
	public function setBEUCdisableT3Editor($state) { //TODO better descriptive name for $state
		if ($GLOBALS['BE_USER']->uc['disableT3Editor'] != $state) {
			$GLOBALS['BE_USER']->uc['disableT3Editor'] = $state;

			$GLOBALS['BE_USER']->writeUC();
		}
	}

	/**
	 * Enter description here...
	 *
	 * @return	string		DESCRIPTION GOES HERE
	 */
	public function getJavascriptCode()	{
		$code = ''; //TODO find a more descriptive name (low prio)

		if($this->isEnabled) {
				// disable the obsolete tab.js to avoid conflicts
			$GLOBALS['BE_USER']->uc['disableTabInTextarea'] = '1';

			$path_t3e = $GLOBALS['BACK_PATH'].t3lib_extmgm::extRelPath('t3editor');

			$code.= '<script type="text/javascript">'.
				'var PATH_t3e = "'.$GLOBALS['BACK_PATH']. t3lib_extmgm::extRelPath('t3editor').'"; '.
				'</script>';

			$code.= '<script src="'.$path_t3e.'/jslib/Mochi.js" type="text/javascript"></script>'.
				'<script src="'.$path_t3e.'/jslib/util.js" type="text/javascript"></script>'.
				'<script src="'.$path_t3e.'/jslib/select.js" type="text/javascript"></script>'.
				'<script src="'.$path_t3e.'/jslib/stringstream.js" type="text/javascript"></script>'.
				'<script src="'.$path_t3e.'/jslib/parsetyposcript.js" type="text/javascript"></script>'.
				'<script src="'.$path_t3e.'/jslib/tokenizetyposcript.js" type="text/javascript"></script>';

				// include prototype and scriptacolous
				// TODO: should use the new loadJavascriptLib
			$code.= '<script src="'.$GLOBALS['BACK_PATH'].'contrib/prototype/prototype.js" type="text/javascript" id="prototype-script"></script>';
			$code.= '<script src="'.$GLOBALS['BACK_PATH'].'contrib/scriptaculous/scriptaculous.js" type="text/javascript" id="scriptaculous-script"></script>';

				// include editor-css
			$code.= '<link href="'.$GLOBALS['BACK_PATH'].t3lib_extmgm::extRelPath('t3editor').$this->filepathEditorcss.'" type="text/css" rel="stylesheet" />';

				// include editor-js-lib
			$code.= '<script src="'.$GLOBALS['BACK_PATH'].t3lib_extmgm::extRelPath('t3editor').$this->filepathEditorlib.'" type="text/javascript" id="t3editor-script"></script>';
		}

		return $code;
	}

	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$name
	 * @param	unknown_type		$class
	 * @param	unknown_type		$content
	 * @param	unknown_type		$additionalParams
	 * @param	unknown_type		$alt
	 * @return	string		DESCRIPTION GOES HERE
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
				<br/><br/>';

		} else {
				// fallback
			if(!empty($class)) {
				$class = 'class="'.$class.'" ';
			}

			$code.= '<textarea name="'.$name.'" '.$class.$additionalParams.'>'.$content.'</textarea>';
		}

		return $code;
	}

}


	// Include extension?
if(defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['sysext/t3editor/class.tx_t3editor.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['sysext/t3editor/class.tx_t3editor.php']);
}


?>