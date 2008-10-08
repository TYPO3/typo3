<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2008 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Front end RTE based on htmlArea
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * $Id$  *
 */

require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlarea_base.php');
require_once(PATH_t3lib.'class.t3lib_parsehtml_proc.php');
require_once(PATH_t3lib.'class.t3lib_befunc.php');

class tx_rtehtmlarea_pi2 extends tx_rtehtmlarea_base {

		// External:
	var $RTEWrapStyle = '';				// Alternative style for RTE wrapper <div> tag.
	var $RTEdivStyle = '';				// Alternative style for RTE <div> tag.
	var $extHttpPath;				// full Path to this extension for http (so no Server path). It ends with "/"
	public $httpTypo3Path;

		// For the editor
	var $elementId;
	var $elementParts;
	var $tscPID;
	var $typeVal;
	var $thePid;
	var $RTEsetup = array();
	var $thisConfig = array();
	var $confValues;
	public $language;
	public $OutputCharset;
	var $specConf;
	var $LOCAL_LANG;

	/**
	 * Draws the RTE as an iframe
	 *
	 * @param	object		Reference to parent object, which is an instance of the TCEforms.
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	array		The current row from which field is being rendered
	 * @param	array		Array of standard content for rendering form fields from TCEforms. See TCEforms for details on this. Includes for instance the value and the form field name, java script actions and more.
	 * @param	array		"special" configuration - what is found at position 4 in the types configuration of a field from record, parsed into an array.
	 * @param	array		Configuration for RTEs; A mix between TSconfig and otherwise. Contains configuration for display, which buttons are enabled, additional transformation information etc.
	 * @param	string		Record "type" field value.
	 * @param	string		Relative path for images/links in RTE; this is used when the RTE edits content from static files where the path of such media has to be transformed forth and back!
	 * @param	integer		PID value of record (true parent page id)
	 * @return	string		HTML code for RTE!
	 */
	function drawRTE($parentObject,$table,$field,$row,$PA,$specConf,$thisConfig,$RTEtypeVal,$RTErelPath,$thePidValue) {
		global $TSFE, $TYPO3_CONF_VARS, $TYPO3_DB;
		
		$this->TCEform =& $parentObject;
		$this->client = $this->clientInfo();
		$this->typoVersion = t3lib_div::int_from_ver(TYPO3_version);
		
		/* =======================================
		 * INIT THE EDITOR-SETTINGS
		 * =======================================
		 */
		 
			// first get the http-path to typo3:
		$this->httpTypo3Path = substr( substr( t3lib_div::getIndpEnv('TYPO3_SITE_URL'), strlen( t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') ) ), 0, -1 );
		if (strlen($this->httpTypo3Path) == 1) {
			$this->httpTypo3Path = '/';
		} else {
			$this->httpTypo3Path .= '/';
		}
			// Get the path to this extension:
		$this->extHttpPath = $this->httpTypo3Path.t3lib_extMgm::siteRelPath($this->ID);
			// Get the site URL
		$this->siteURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
			// Get the host URL
		$this->hostURL = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST');
		
			// Element ID + pid
		$this->elementId = $PA['itemFormElName'];
		$this->elementParts[0] = $table;
		$this->elementParts[1] = $row['uid'];
		$this->tscPID = $thePidValue;
		$this->thePid = $thePidValue;
		
			// Record "type" field value:
		$this->typeVal = $RTEtypeVal; // TCA "type" value for record
		
			// RTE configuration
		$pageTSConfig = $TSFE->getPagesTSconfig();
		if (is_array($pageTSConfig) && is_array($pageTSConfig['RTE.'])) {
			$this->RTEsetup = $pageTSConfig['RTE.'];
		}
		
		if (is_array($thisConfig) && !empty($thisConfig)) {
			$this->thisConfig = $thisConfig;
		} else if (is_array($this->RTEsetup['default.']) && is_array($this->RTEsetup['default.']['FE.'])) {
			$this->thisConfig = $this->RTEsetup['default.']['FE.'];
		}
		
			// Special configuration (line) and default extras:
		$this->specConf = $specConf;
		
		if ($this->thisConfig['forceHTTPS']) {
			$this->httpTypo3Path = preg_replace('/^(http|https)/', 'https', $this->httpTypo3Path);
			$this->extHttpPath = preg_replace('/^(http|https)/', 'https', $this->extHttpPath);
			$this->siteURL = preg_replace('/^(http|https)/', 'https', $this->siteURL);
			$this->hostURL = preg_replace('/^(http|https)/', 'https', $this->hostURL);
		}
		
		/* =======================================
		 * LANGUAGES & CHARACTER SETS
		 * =======================================
		 */
			// Language
		$TSFE->initLLvars();
		$this->language = $TSFE->lang;
		$this->LOCAL_LANG = t3lib_div::readLLfile('EXT:' . $this->ID . '/locallang.xml', $this->language);
		if ($this->language == 'default' || !$this->language)	{
			$this->language = 'en';
		}
		
		$this->contentISOLanguage = $TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['defaultDictionary'];
		$this->contentLanguageUid = ($row['sys_language_uid'] > 0) ? $row['sys_language_uid'] : 0;
		if (t3lib_extMgm::isLoaded('static_info_tables')) {
			if ($this->contentLanguageUid) {
				$tableA = 'sys_language';
				$tableB = 'static_languages';
				$languagesUidsList = $this->contentLanguageUid;
				$selectFields = $tableA . '.uid,' . $tableB . '.lg_iso_2,' . $tableB . '.lg_country_iso_2,' . $tableB . '.lg_typo3';
				$tableAB = $tableA . ' LEFT JOIN ' . $tableB . ' ON ' . $tableA . '.static_lang_isocode=' . $tableB . '.uid';
				$whereClause = $tableA . '.uid IN (' . $languagesUidsList . ') ';
				$whereClause .= t3lib_BEfunc::BEenableFields($tableA);
				$whereClause .= t3lib_BEfunc::deleteClause($tableA);
				$res = $TYPO3_DB->exec_SELECTquery($selectFields, $tableAB, $whereClause);
				while($languageRow = $TYPO3_DB->sql_fetch_assoc($res)) {
					$this->contentISOLanguage = strtolower(trim($languageRow['lg_iso_2']).(trim($languageRow['lg_country_iso_2'])?'_'.trim($languageRow['lg_country_iso_2']):''));
					$this->contentTypo3Language = strtolower(trim($languageRow['lg_typo3']));
				}
			} else {
				$this->contentISOLanguage = trim($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['defaultDictionary']) ? trim($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['defaultDictionary']) : 'en';
				$selectFields = 'lg_iso_2, lg_typo3';
				$tableAB = 'static_languages';
				$whereClause = 'lg_iso_2 = ' . $TYPO3_DB->fullQuoteStr(strtoupper($this->contentISOLanguage), $tableAB);
				$res = $TYPO3_DB->exec_SELECTquery($selectFields, $tableAB, $whereClause);
				while($languageRow = $TYPO3_DB->sql_fetch_assoc($res)) {
					$this->contentTypo3Language = strtolower(trim($languageRow['lg_typo3']));
				}
			}
		}
		
		$this->contentISOLanguage = $this->contentISOLanguage?$this->contentISOLanguage:$this->language;
		$this->contentTypo3Language = $this->contentTypo3Language?$this->contentTypo3Language:$TSFE->lang;
		if ($this->contentTypo3Language == 'default') {
			$this->contentTypo3Language = 'en';
		}
		
			// Character set
		$this->charset = $TSFE->renderCharset;
		$this->OutputCharset  = $TSFE->metaCharset ? $TSFE->metaCharset : $TSFE->renderCharset;
		
			// Set the charset of the content
		$this->contentCharset = $TSFE->csConvObj->charSetArray[$this->contentTypo3Language];
		$this->contentCharset = $this->contentCharset ? $this->contentCharset : 'iso-8859-1';
		$this->contentCharset = trim($TSFE->config['config']['metaCharset']) ? trim($TSFE->config['config']['metaCharset']) : $this->contentCharset;

		/* =======================================
		 * TOOLBAR CONFIGURATION
		 * =======================================
		 */
		$this->initializeToolbarConfiguration();

		/* =======================================
		 * SET STYLES
		 * =======================================
		 */
		 
		$RTEWidth = 460+($this->TCEform->docLarge ? 150 : 0);
		$RTEHeight = 380;
		$RTEHeightOverride = intval($this->thisConfig['RTEHeightOverride']);
		$RTEHeight = ($RTEHeightOverride > 0) ? $RTEHeightOverride : $RTEHeight;
		$editorWrapWidth = $RTEWidth . 'px';
		$editorWrapHeight = $RTEHeight . 'px';
		$this->RTEWrapStyle = $this->RTEWrapStyle ? $this->RTEWrapStyle : ($this->RTEdivStyle ? $this->RTEdivStyle : ('height:' . ($RTEHeight+2) . 'px; width:'. ($RTEWidth+2) . 'px;'));		
		$this->RTEdivStyle = $this->RTEdivStyle ? $this->RTEdivStyle : 'position:relative; left:0px; top:0px; height:' . $RTEHeight . 'px; width:'.$RTEWidth.'px; border: 1px solid black;';
		$this->toolbar_level_size = $RTEWidth;

		/* =======================================
		 * LOAD JS, CSS and more
		 * =======================================
		 */
			// Preloading the pageStyle
		$GLOBALS['TSFE']->additionalHeaderData['rtehtmlarea-contentCSS'] = $this->getPageStyle();
			// Loading RTE skin style sheets
		$GLOBALS['TSFE']->additionalHeaderData['rtehtmlarea-skin'] = $this->getSkin();
			// Loading JavaScript files and code
		$this->TCEform->additionalJS_initial = $this->loadJSfiles($this->TCEform->RTEcounter);
		$this->TCEform->additionalJS_pre['rtehtmlarea-loadJScode'] = $this->loadJScode($this->TCEform->RTEcounter);

		/* =======================================
		 * DRAW THE EDITOR
		 * =======================================
		 */
			// Transform value:
		$value = $this->transformContent('rte',$PA['itemFormElValue'],$table,$field,$row,$specConf,$thisConfig,$RTErelPath,$thePidValue);
		
			// Further content transformation by registered plugins
		foreach ($this->registeredPlugins as $pluginId => $plugin) {
			if ($this->isPluginEnabled($pluginId) && method_exists($plugin, "transformContent")) {
				$value = $plugin->transformContent($value);
			}
		}
		
			// Register RTE windows:
		$this->TCEform->RTEwindows[] = $PA['itemFormElName'];
		$textAreaId = htmlspecialchars($PA['itemFormElName']);
		
			// Register RTE in JS:
		$this->TCEform->additionalJS_post[] = $this->registerRTEinJS($this->TCEform->RTEcounter, '', '', '',$textAreaId);
		
			// Set the save option for the RTE:
		$this->TCEform->additionalJS_submit[] = $this->setSaveRTE($this->TCEform->RTEcounter, $this->TCEform->formName, $textAreaId);
		
			// draw the textarea
		$item = $this->triggerField($PA['itemFormElName']).'
			<div id="pleasewait' . $textAreaId . '" class="pleasewait" style="display: block;" >' . $TSFE->csConvObj->conv($TSFE->getLLL('Please wait',$this->LOCAL_LANG), $this->charset, $TSFE->renderCharset) . '</div>
			<div id="editorWrap' . $textAreaId . '" class="editorWrap" style="visibility: hidden; '. htmlspecialchars($this->RTEWrapStyle). '">
			<textarea id="RTEarea' . $textAreaId . '" name="'.htmlspecialchars($PA['itemFormElName']).'" style="'.htmlspecialchars($this->RTEdivStyle).'">'.t3lib_div::formatForTextarea($value).'</textarea>
			</div>' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableDebugMode'] ? '<div id="HTMLAreaLog"></div>' : '') . '
			';
		return $item;
	}
	
	/**
	 * Return the JS-Code for copy the HTML-Code from the editor in the hidden input field.
	 * This is for submit function from the form.
	 *
	 * @param	integer		$RTEcounter: The index number of the RTE editing area.
	 * @param	string		$form: the name of the form
	 * @param	string		$textarea: the name of the textarea
	 *
	 * @return	string		the JS-Code
	 */
	function setSaveRTE($RTEcounter, $form, $textarea) {
		return '
		rteFound = false;
		for (editornumber = 1; editornumber < RTEarea.length; editornumber++) {
			if (RTEarea[editornumber].textAreaId == \'' . $textarea . '\') {
				if (!RTEarea[editornumber].deleted) {
			fields = document.getElementsByName(\'' . $textarea . '\');
			field = fields.item(0);
			if(field && field.tagName.toLowerCase() == \'textarea\') field.value = RTEarea[editornumber][\'editor\'].getHTML();
		}
				rteFound = true;
				break;
			}
		}
		if (!rteFound) {
			OK = 0;
		}
		';
	}
	

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi2/class.tx_rtehtmlarea_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi2/class.tx_rtehtmlarea_pi2.php']);
}

?>
