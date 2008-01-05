<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Kasper Skaarhoj (kasper@typo3.com)
*  (c) 2004 Philipp Borgmann <philipp.borgmann@gmx.de>
*  (c) 2004-2008 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * A RTE using the htmlArea editor
 *
 * @author	Philipp Borgmann <philipp.borgmann@gmx.de>
 * @author	Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 *
 * $Id$  *
 */

require_once(PATH_t3lib.'class.t3lib_rteapi.php');
require_once(PATH_t3lib.'class.t3lib_cs.php');

class tx_rtehtmlarea_base extends t3lib_rteapi {

		// Configuration of supported browsers
	var $conf_supported_browser = array (
			'msie' => array (
				1 => array (
					'version' => 5.5,
					'system' => 'win'
				)
			),
			'gecko' => array (
				1 => array (
					'version' => 1.3
				)
			),
			'safari' => array (
				1 => array (
					'version' => 523
				)
			),
			'opera' => array (
				1 => array (
					'version' => 9
				)
			)
		);

		// Always hide these toolbar buttons (TYPO3 button name)
	var $conf_toolbar_hide = array (
		'showhelp',		// Has no content yet
		);
	
		// Hide toolbar buttons not implemented in client browsers
	var $hideButtonsFromClient = array (
		'safari'	=>	array('paste'),
		'opera'		=>	array('copy', 'cut', 'paste'),
 		);
	
		// Always show these toolbar buttons (TYPO3 button name)
	var $conf_toolbar_show = array (
		'undo',
		'redo',
		//'showhelp',
		'about',
		);
	
		// The order of the toolbar: the name is the TYPO3-button name
	var $defaultToolbarOrder;

		// The default hotkeys: the name is the TYPO3-button name
	var $defaultHotKeyList = 'selectall, cleanword, undo, redo';

		// Conversion array: TYPO3 button names to htmlArea button names
	var $convertToolbarForHtmlAreaArray = array (
			// 'TYPO3 name' => 'htmlArea name'
		'fontstyle'		=> 'FontName',
		'fontsize'		=> 'FontSize',
		'textcolor'		=> 'ForeColor',
		'bgcolor'		=> 'HiliteColor',
		'orderedlist'		=> 'InsertOrderedList',
		'unorderedlist'		=> 'InsertUnorderedList',
		'emoticon'		=> 'InsertSmiley',
		'line'			=> 'InsertHorizontalRule',
		'link'			=> 'CreateLink',
		'table'			=> 'InsertTable',
		'image'			=> 'InsertImage',
		'cut'			=> 'Cut',
		'copy'			=> 'Copy',
		'paste'			=> 'Paste',
		'chMode'		=> 'HtmlMode',
		'user'			=> 'UserElements',
		
			// htmlArea extra buttons
		'lefttoright'		=> 'LeftToRight',
		'righttoleft'		=> 'RightToLeft',
		'showhelp'		=> 'ShowHelp',
		'findreplace'		=> 'FindReplace',
		'spellcheck'		=> 'SpellCheck',
		'removeformat'		=> 'RemoveFormat',
		'inserttag'		=> 'InsertTag',
		'acronym'		=> 'Acronym',
		'splitblock'		=> 'SplitBlock',
		'toggleborders'		=> 'TO-toggle-borders',
		'tableproperties'	=> 'TO-table-prop',
		'rowproperties'		=> 'TO-row-prop',
		'rowinsertabove'	=> 'TO-row-insert-above',
		'rowinsertunder'	=> 'TO-row-insert-under',
		'rowdelete'		=> 'TO-row-delete',
		'rowsplit'		=> 'TO-row-split',
		'columninsertbefore'	=> 'TO-col-insert-before',
		'columninsertafter'	=> 'TO-col-insert-after',
		'columndelete'		=> 'TO-col-delete',
		'columnsplit'		=> 'TO-col-split',
		'cellproperties'	=> 'TO-cell-prop',
		'cellinsertbefore'	=> 'TO-cell-insert-before',
		'cellinsertafter'	=> 'TO-cell-insert-after',
		'celldelete'		=> 'TO-cell-delete',
		'cellsplit'		=> 'TO-cell-split',
		'cellmerge'		=> 'TO-cell-merge',

			// Toolbar formating
		'space'			=> 'space',
		'bar'			=> 'separator',
		'linebreak'		=> 'linebreak',

			// Always show
		'undo'			=> 'Undo',
		'redo'			=> 'Redo',
		'textindicator'		=> 'TextIndicator',
		'about'			=> 'About',
		);
	
	var $defaultFontFaces = array(
		'Arial'			=> 'Arial,sans-serif',
		'Arial Black'		=> 'Arial Black,sans-serif',
		'Verdana'		=> 'Verdana,Arial,sans-serif',
		'Times New Roman'	=> 'Times New Roman,Times,serif',
		'Garamond'		=> 'Garamond',
		'Lucida Handwriting'	=> 'Lucida Handwriting',
		'Courier'		=> 'Courier',
		'Webdings'		=> 'Webdings',
		'Wingdings'		=> 'Wingdings',
		);
				
	var $defaultFontSizes = array(
		'1'	=>	'1 (8 pt)',
		'2'	=>	'2 (10 pt)',
		'3'	=>	'3 (12 pt)',
		'4'	=>	'4 (14 pt)',
		'5'	=>	'5 (18 pt)',
		'6'	=>	'6 (24 pt)',
		'7'	=>	'7 (36 pt)',
		);
	
	var $defaultFontSizes_safari = array(
		'1'	=>	'x-small (10px)',
		'2'	=>	'small (13px)',
		'3'	=>	'medium (16px)',
		'4'	=>	'large (18px)',
		'5'	=>	'x-large (24px)',
		'6'	=>	'xx-large (32px)',
		'7'	=>	'xxx-large (48px)',
		);
	
	var $pluginList = 'TableOperations, ContextMenu, SpellChecker, SelectColor, TYPO3Browsers, InsertSmiley, FindReplace, RemoveFormat, CharacterMap, QuickTag, UserElements, Acronym, TYPO3HtmlParser';
	
	var $pluginButton = array(
		'SpellChecker'		=> 'spellcheck',
		'InsertSmiley'		=> 'emoticon',
		'FindReplace'		=> 'findreplace',
		'RemoveFormat'		=> 'removeformat',
		'QuickTag'		=> 'inserttag',
		'TableOperations'	=> 'table, toggleborders, tableproperties, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit,
						columninsertbefore, columninsertafter, columndelete, columnsplit,
						cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge',
		'UserElements'		=> 'user',
		'Acronym'		=> 'acronym',
		'SelectColor'		=> 'textcolor,bgcolor',
		);

	var $pluginLabel = array();

	var $spellCheckerModes = array( 'ultra', 'fast', 'normal', 'bad-spellers');

		// External:
	var $RTEdivStyle;			// Alternative style for RTE <div> tag.
	var $extHttpPath;			// full Path to this extension for http (so no Server path). It ends with "/"
	var $siteURL;				// TYPO3 site url
	var $hostURL;				// TYPO3 host url
	var $typoVersion;			// Typo3 version

		// Internal, static:
	var $ID = 'rtehtmlarea';		// Identifies the RTE as being the one from the "rte" extension if any external code needs to know...
	var $debugMode = FALSE;			// If set, the content goes into a regular TEXT area field - for developing testing of transformations. (Also any browser will load the field!)

		// For the editor
	var $client;
	
       /**
        * Reference to parent object, which is an instance of the TCEforms
        *
        * @var t3lib_TCEforms
        */
	var $TCEform;
	var $elementId;
	var $elementParts;
	var $tscPID;
	var $typeVal;
	var $thePid;
	var $RTEsetup;
	var $thisConfig;
	var $confValues;
	var $language;
	var $BECharset;
	var $OutputCharset;
	var $editorCSS;
	var $spellCheckerLanguage;
	var $spellCheckerCharset;
	var $spellCheckerMode;
	var $quickTagHideTags;
	var $specConf;
	var $toolbar = array();					// Save the buttons for the toolbar
	var $toolbar_level_size;				// The size for each level in the toolbar:
	var $toolbarOrderArray = array();
	protected $pluginEnabledArray = array();		// Array of plugin id's enabled in the current RTE editing area
	protected $pluginEnabledCumulativeArray = array();	// Cumulative array of plugin id's enabled so far in any of the RTE editing areas of the form
	protected $registeredPlugins = array();			// Array of registered plugins indexd by their plugin Id's
	
	/**
	 * Returns true if the RTE is available. Here you check if the browser requirements are met.
	 * If there are reasons why the RTE cannot be displayed you simply enter them as text in ->errorLog
	 *
	 * @return	boolean		TRUE if this RTE object offers an RTE in the current browser environment
	 */
	
	function isAvailable()	{
		global $TYPO3_CONF_VARS;
		
		$this->client = $this->clientInfo();
		$this->errorLog = array();
		if (!$this->debugMode)	{	// If debug-mode, let any browser through
			$rteIsAvailable = 0;
			$rteConfBrowser = $this->conf_supported_browser;
			if (!$TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['enableInOpera9']) unset($rteConfBrowser['opera']);
			if (is_array($rteConfBrowser)) {
				foreach ($rteConfBrowser as $browser => $browserConf) {
					if ($browser == $this->client['BROWSER']) {
							// Config for Browser found, check it:
						if (is_array($browserConf)) {
							foreach ($browserConf as $browserConfNr => $browserConfSub) {
								if ($browserConfSub['version'] <= $this->client['VERSION'] || empty($browserConfSub['version'])) {
									// Version is correct
									if ($browserConfSub['system'] == $this->client['SYSTEM'] || empty($browserConfSub['system'])) {
											// System is correctly
										$rteIsAvailable = 1;
									}// End of System
								}// End of Version
							}// End of foreach-BrowserSubpart
						} else {
							// no config for this browser found, so all versions or system with this browsers are allow
							$rteIsAvailable = 1;
						}
					} // End of Browser Check
				} // foreach: Browser Check
			} else {
				// no Browser config for this RTE-Editor, so all Clients are allow			   
			}
			if (!$rteIsAvailable) {
				$this->errorLog[] = 'rte: Browser not supported. Only msie Version 5 or higher and Mozilla based client 1. and higher.';
			}
			if (t3lib_div::int_from_ver(TYPO3_version) < 4000000) {
				$rteIsAvailable = 0;
				$this->errorLog[] = 'rte: This version of htmlArea RTE cannot run under this version of TYPO3.';
			}
		}
		if ($rteIsAvailable)	return true;
	}

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
	
	function drawRTE($parentObject, $table, $field, $row, $PA, $specConf, $thisConfig, $RTEtypeVal, $RTErelPath, $thePidValue) {
		global $BE_USER, $LANG, $TYPO3_DB, $TYPO3_CONF_VARS;
		
		$this->TCEform =& $parentObject;
		$inline =& $this->TCEform->inline;
		$LANG->includeLLFile('EXT:' . $this->ID . '/locallang.xml');
		$this->client = $this->clientInfo();
		$this->typoVersion = t3lib_div::int_from_ver(TYPO3_version);
		$this->userUid = 'BE_' . $BE_USER->user['uid'];
		
			// Draw form element:
		if ($this->debugMode)	{	// Draws regular text area (debug mode)
			$item = parent::drawRTE($this->TCEform, $table, $field, $row, $PA, $specConf, $thisConfig, $RTEtypeVal, $RTErelPath, $thePidValue);
		} else {	// Draw real RTE
		
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
			$this->extHttpPath = $this->httpTypo3Path . t3lib_extMgm::siteRelPath($this->ID);
				// Get the site URL
			$this->siteURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
				// Get the host URL
			$this->hostURL = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST');

				// Element ID + pid
			$this->elementId = $PA['itemFormElName']; // Form element name
			$this->elementParts = explode('][',ereg_replace('\]$','',ereg_replace('^(TSFE_EDIT\[data\]\[|data\[)','',$this->elementId)));

				// Find the page PIDs:
			list($this->tscPID,$this->thePid) = t3lib_BEfunc::getTSCpid(trim($this->elementParts[0]),trim($this->elementParts[1]),$thePidValue);

				// Record "types" field value:
			$this->typeVal = $RTEtypeVal; // TCA "types" value for record

				// Find "thisConfig" for record/editor:
			unset($this->RTEsetup);
			$this->RTEsetup = $BE_USER->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($this->tscPID));
			$this->thisConfig = $thisConfig;

				// Special configuration and default extras:
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

				// Languages: interface and content
			$this->language = $LANG->lang;
			if ($this->language=='default' || !$this->language)	{
				$this->language='en';
			}
			$this->contentTypo3Language = $this->language;
			
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

				// Character sets: interface and content
			$this->charset = $LANG->csConvObj->charSetArray[$this->language];
			$this->charset = $this->charset ? $this->charset : 'iso-8859-1';
			$this->BECharset = trim($TYPO3_CONF_VARS['BE']['forceCharset']) ? trim($TYPO3_CONF_VARS['BE']['forceCharset']) : $this->charset;
			$this->OutputCharset = $this->BECharset;
			
			$this->contentCharset = $LANG->csConvObj->charSetArray[$this->contentTypo3Language];
			$this->contentCharset = $this->contentCharset ? $this->contentCharset : 'iso-8859-1';
			$this->origContentCharSet = $this->contentCharset;
			$this->contentCharset = (trim($TYPO3_CONF_VARS['BE']['forceCharset']) ? trim($TYPO3_CONF_VARS['BE']['forceCharset']) : $this->contentCharset);

			/* =======================================
			 * TOOLBAR CONFIGURATION
			 * =======================================
			 */
			
				// htmlArea plugins list
			$this->pluginEnabledArray = t3lib_div::trimExplode(',', $this->pluginList, 1);
			$this->enableRegisteredPlugins();
			$hidePlugins = array();
			if (!t3lib_extMgm::isLoaded('static_info_tables') || in_array($this->language, t3lib_div::trimExplode(',', $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['noSpellCheckLanguages']))) $hidePlugins[] = 'SpellChecker';
			if ($this->client['BROWSER'] == 'msie') {
				//if ($this->client['VERSION'] < 7) {
					$hidePlugins[] = 'Acronym';
				//}
				$this->thisConfig['keepButtonGroupTogether'] = 0;
			}
			if ($this->client['BROWSER'] == 'opera') {
				$hidePlugins[] = 'ContextMenu';
				$this->thisConfig['hideTableOperationsInToolbar'] = 0;
				$this->thisConfig['keepButtonGroupTogether'] = 0;
			}
			if ($this->client['BROWSER'] == 'gecko' && $this->client['VERSION'] == '1.3')  {
				$this->thisConfig['keepButtonGroupTogether'] = 0;
			}
			$this->pluginEnabledArray = array_diff($this->pluginEnabledArray, $hidePlugins);

				// Toolbar
			$this->setToolbar();

				// Check if some plugins need to be disabled
			$this->setPlugins();
			
				// Merge the list of enabled plugins with the lists from the previous RTE editing areas on the same form
			$this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter] = $this->pluginEnabledArray;
			if ($this->TCEform->RTEcounter > 1 && isset($this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter-1]) && is_array($this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter-1])) {
				$this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter] = array_unique(array_values(array_merge($this->pluginEnabledArray,$this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter-1])));
			}
			
			/* =======================================
			 * PLUGIN-SPECIFIC CONFIGURATION
			 * =======================================
			 */
			
			if ($this->isPluginEnabled('SpellChecker')) {
					// Set the language of the content for the SpellChecker
				$this->spellCheckerLanguage = $this->contentISOLanguage;
				$this->spellCheckerTypo3Language = $this->contentTypo3Language;
				
					// Set the charset of the content for the SpellChecker
				$this->spellCheckerCharset = $this->contentCharset;
				$this->spellCheckerCharset = trim($TYPO3_CONF_VARS['BE']['forceCharset']) ? trim($TYPO3_CONF_VARS['BE']['forceCharset']) : $this->spellCheckerCharset;
				
					// Set the SpellChecker mode
				$this->spellCheckerMode = isset($BE_USER->userTS['options.']['HTMLAreaPspellMode']) ? trim($BE_USER->userTS['options.']['HTMLAreaPspellMode']) : 'normal';
				if( !in_array($this->spellCheckerMode, $this->spellCheckerModes)) {
					$this->spellCheckerMode = 'normal';
				}
				
					// Set the use of personal dictionary
				$this->spellCheckerPersonalDicts = $this->thisConfig['enablePersonalDicts'] ? ((isset($BE_USER->userTS['options.']['enablePersonalDicts']) && $BE_USER->userTS['options.']['enablePersonalDicts']) ? true : false) : false;
				if (ini_get('safe_mode')) {
					$this->spellCheckerPersonalDicts = false;
				}
			}

			/* =======================================
			 * SET STYLES
			 * =======================================
			 */

			$RTEWidth = isset($BE_USER->userTS['options.']['RTESmallWidth']) ? $BE_USER->userTS['options.']['RTESmallWidth'] : '530';
			$RTEHeight = isset($BE_USER->userTS['options.']['RTESmallHeight']) ? $BE_USER->userTS['options.']['RTESmallHeight'] : '380';
			$RTEWidth  = $RTEWidth + ($this->TCEform->docLarge ? (isset($BE_USER->userTS['options.']['RTELargeWidthIncrement']) ? $BE_USER->userTS['options.']['RTELargeWidthIncrement'] : '150') : 0);
			$RTEWidth -= ($inline->getStructureDepth() > 0 ? ($inline->getStructureDepth()+1)*$inline->getLevelMargin() : 0);
			$RTEHeight = $RTEHeight + ($this->TCEform->docLarge ?  (isset($BE_USER->userTS['options.']['RTELargeHeightIncrement']) ? $BE_USER->userTS['options.']['RTELargeHeightIncrement'] : 0) : 0);
			$editorWrapWidth = $RTEWidth . 'px';
			$editorWrapHeight = $RTEHeight . 'px';
			$this->RTEdivStyle = 'position:relative; left:0px; top:0px; height:' . $RTEHeight . 'px; width:'.$RTEWidth.'px; border: 1px solid black; padding: 2px 0px 2px 2px;';
			$this->toolbar_level_size = $RTEWidth;

			/* =======================================
			 * LOAD CSS AND JAVASCRIPT
			 * =======================================
			 */

				// Preloading the pageStyle
			$filename = trim($this->thisConfig['contentCSS']) ? trim($this->thisConfig['contentCSS']) : 'EXT:' . $this->ID . '/res/contentcss/default.css';
			$this->TCEform->additionalCode_pre['loadCSS'] = '
		<link rel="alternate stylesheet" type="text/css" href="' . $this->getFullFileName($filename) . '" title="HTMLArea RTE Content CSS" />';

				// Loading the editor skin
			$skinFilename = trim($this->thisConfig['skin']) ? trim($this->thisConfig['skin']) : 'EXT:' . $this->ID . '/htmlarea/skins/default/htmlarea.css';
			if($this->client['BROWSER'] == 'gecko' && $this->client['VERSION'] == '1.3' && substr($skinFilename,0,4) == 'EXT:')  {
				$skinFilename = 'EXT:' . $this->ID . '/htmlarea/skins/default/htmlarea.css';
			}
			if (substr($skinFilename,0,4) == 'EXT:')      {       // extension
				list($extKey,$local) = explode('/',substr($skinFilename,4),2);
				$skinFilename='';
				if (strcmp($extKey,'') &&  t3lib_extMgm::isLoaded($extKey) && strcmp($local,'')) {
					$skinFilename = $this->httpTypo3Path . t3lib_extMgm::siteRelPath($extKey) . $local;
					$skinDir = $this->siteURL . t3lib_extMgm::siteRelPath($extKey) . dirname($local);
				}
			} elseif (substr($skinFilename,0,1) != '/') {
				$skinDir = $this->siteURL.dirname($skinFilename);
				$skinFilename = $this->siteURL . $skinFilename;
			} else {
				$skinDir = substr($this->siteURL,0,-1) . dirname($skinFilename);
			}
			$this->editorCSS = $skinFilename;
			$this->editedContentCSS = $skinDir . '/htmlarea-edited-content.css';
			$this->TCEform->additionalCode_pre['loadCSS'] .= '
		<link rel="alternate stylesheet" type="text/css" href="' . $this->editedContentCSS . '" />';
			
				// Main skin
			$this->TCEform->additionalCode_pre['loadCSS'] .= '
		<link rel="stylesheet" type="text/css" href="' . $this->editorCSS . '" />';
			
				// Additional icons from registered plugins
			foreach ($this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter] as $pluginId) {
				if (is_object($this->registeredPlugins[$pluginId])) {
					$pathToSkin = $this->registeredPlugins[$pluginId]->getPathToSkin();
					if ($pathToSkin) {
						$this->TCEform->additionalCode_pre['loadCSS'] .= '
		<link rel="stylesheet" type="text/css" href="' . $this->httpTypo3Path . t3lib_extMgm::siteRelPath($this->registeredPlugins[$pluginId]->getExtensionKey()) . $pathToSkin . '" />';
					}
				}
			}
			
				// Loading JavaScript files and code
			$this->TCEform->additionalCode_pre['loadJSfiles'] = $this->loadJSfiles($this->TCEform->RTEcounter);
			$this->TCEform->additionalJS_pre['loadJScode'] = $this->loadJScode($this->TCEform->RTEcounter);

			/* =======================================
			 * DRAW THE EDITOR
			 * =======================================
			 */

				// Transform value:
			$value = $this->transformContent('rte',$PA['itemFormElValue'],$table,$field,$row,$specConf,$thisConfig,$RTErelPath,$thePidValue);
			
			foreach ($this->registeredPlugins as $pluginId => $plugin) {
				if ($this->isPluginEnabled($pluginId) && method_exists($plugin, "transformContent")) {
					$value = $plugin->transformContent($value);
				}
			}
			//if ($this->client['BROWSER'] == 'msie') {
			if ($this->client['BROWSER'] == 'msie' && $this->client['VERSION'] < 7) {
					// change <abbr> to <acronym>
				$value = preg_replace('/<(\/?)abbr/i', "<$1acronym", $value);
			}

				// Register RTE windows
			$this->TCEform->RTEwindows[] = $PA['itemFormElName'];

				// Check if wizard_rte called this for fullscreen edtition; if so, change the size of the RTE to fullscreen using JS
			if (basename(PATH_thisScript) == 'wizard_rte.php') {
				$height = 'window.innerHeight';
				$width = 'window.innerWidth';
				if ($this->client['BROWSER'] == 'msie') {
					$height = 'document.body.offsetHeight';
					$width = 'document.body.offsetWidth';
				}
				$editorWrapWidth = '100%';
				$editorWrapHeight = '100%';
				$this->RTEdivStyle = 'position:relative; left:0px; top:0px; height:100%; width:100%; border: 1px solid black; padding: 2px 0px 2px 2px;';
				$this->TCEform->additionalJS_post[] = $this->setRTEsizeByJS('RTEarea'.$this->TCEform->RTEcounter, $height, $width);
			}

				// Register RTE in JS:
			$this->TCEform->additionalJS_post[] = $this->registerRTEinJS($this->TCEform->RTEcounter, $table, $row['uid'], $field);

				// Set the save option for the RTE:
			$this->TCEform->additionalJS_submit[] = $this->setSaveRTE($this->TCEform->RTEcounter, $this->TCEform->formName, htmlspecialchars($PA['itemFormElName']));

				// Draw the textarea
			$visibility = 'hidden';
			$item = $this->triggerField($PA['itemFormElName']).'
				<div id="pleasewait' . $this->TCEform->RTEcounter . '" class="pleasewait" style="display: none;" >' . $LANG->getLL('Please wait') . '</div>
				<div id="editorWrap' . $this->TCEform->RTEcounter . '" class="editorWrap" style="width:' . $editorWrapWidth . '; height:' . $editorWrapHeight . ';">
				<textarea id="RTEarea'.$this->TCEform->RTEcounter.'" name="'.htmlspecialchars($PA['itemFormElName']).'" style="'.t3lib_div::deHSCentities(htmlspecialchars($this->RTEdivStyle)).'">'.t3lib_div::formatForTextarea($value).'</textarea>
				</div>' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableDebugMode'] ? '<div id="HTMLAreaLog"></div>' : '') . '
				';
		}

			// Return form item:
		return $item;
	}
	
	/**
	 * Add registered plugins to the array of enabled plugins
	 *
	 */
	function enableRegisteredPlugins() {
		global $TYPO3_CONF_VARS;
					// Traverse registered plugins
		if (is_array($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['plugins'])) {
			foreach($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['plugins'] as $pluginId => $pluginObjectConfiguration) {
				$plugin = &t3lib_div::getUserObj($pluginObjectConfiguration['objectReference']);
				if (is_object($plugin)) {
					if ($plugin->main($this)) {
						$this->registeredPlugins[$pluginId] = $plugin;
							// Override buttons from previously registered plugins
						$pluginButtons = t3lib_div::trimExplode(',', $plugin->getPluginButtons(), 1);
						foreach ($this->pluginButton as $previousPluginId => $buttonList) {
							$this->pluginButton[$previousPluginId] = implode(',',array_diff(t3lib_div::trimExplode(',', $this->pluginButton[$previousPluginId], 1), $pluginButtons));
						}
						$this->pluginButton[$pluginId] = $plugin->getPluginButtons();
						$pluginLabels = t3lib_div::trimExplode(',', $plugin->getPluginLabels(), 1);
						foreach ($this->pluginLabel as $previousPluginId => $labelList) {
							$this->pluginLabel[$previousPluginId] = implode(',',array_diff(t3lib_div::trimExplode(',', $this->pluginLabel[$previousPluginId], 1), $pluginLabels));
						}
						$this->pluginLabel[$pluginId] = $plugin->getPluginLabels();
						$this->pluginEnabledArray[] = $pluginId;
					}
				}
			}
		}
			// Process overrides
		$hidePlugins = array();
		foreach ($this->registeredPlugins as $pluginId => $plugin) {
			if (!$this->pluginButton[$pluginId]) {
				$hidePlugins[] = $pluginId;
			}
		}
		$this->pluginEnabledArray = array_diff($this->pluginEnabledArray, $hidePlugins);
	}
	
	/**
	 * Set the toolbar config (only in this PHP-Object, not in JS):
	 *
	 */

	function setToolbar() {
		global $BE_USER;
		
		$this->defaultToolbarOrder = 'bar, blockstylelabel, blockstyle, space, textstylelabel, textstyle, linebreak,
			bar, inlineelement, bold,  strong, italic, emphasis, big, small, insertedtext, deletedtext, citation, code, definition, keyboard, monospaced, quotation, sample, variable, bidioverride, strikethrough, subscript, superscript, underline, span,
			bar, fontstyle, space, fontsize, bar, formatblock, insertparagraphbefore, insertparagraphafter, blockquote,
			bar, left, center, right, justifyfull,
			bar, orderedlist, unorderedlist, outdent, indent,  bar, lefttoright, righttoleft,
			bar, textcolor, bgcolor, textindicator,
			bar, emoticon, insertcharacter, line, link, image, table,' . (($this->thisConfig['hideTableOperationsInToolbar'] && is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['toggleborders.']) && $this->thisConfig['buttons.']['toggleborders.']['keepInToolbar']) ? ' toggleborders,': '') . ' user, acronym, bar, findreplace, spellcheck,
			bar, chMode, inserttag, removeformat, bar, copy, cut, paste, bar, undo, redo, bar, showhelp, about, linebreak, 
			' . ($this->thisConfig['hideTableOperationsInToolbar'] ? '': 'bar, toggleborders,') . ' bar, tableproperties, bar, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit, bar,
			columninsertbefore, columninsertafter, columndelete, columnsplit, bar,
			cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge';
		
			// Special toolbar for Mozilla Wamcom on Mac OS 9
		if($this->client['BROWSER'] == 'gecko' && $this->client['VERSION'] == '1.3')  {
			$this->defaultToolbarOrder = $this->TCEform->docLarge ? 'bar, blockstylelabel, blockstyle, space, textstylelabel, textstyle, linebreak,
				bar, fontstyle, space, fontsize, space, formatblock, insertparagraphbefore, insertparagraphafter, blockquote, bar, bold, italic, underline, strikethrough,
				subscript, superscript, lefttoright, righttoleft, bar, left, center, right, justifyfull, linebreak,
				bar, orderedlist, unorderedlist, outdent, indent, bar, textcolor, bgcolor, textindicator, bar, emoticon,
				insertcharacter, line, link, image, table, user, acronym, bar, findreplace, spellcheck, bar, chMode, inserttag,
				removeformat, bar, copy, cut, paste, bar, undo, redo, bar, showhelp, about, linebreak,
				bar, toggleborders, bar, tableproperties, bar, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit, bar,
				columninsertbefore, columninsertafter, columndelete, columnsplit, bar,
				cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge'
				: 'bar, blockstylelabel, blockstyle, space, textstylelabel, textstyle, linebreak,
				bar, fontstyle, space, fontsize, space, formatblock, insertparagraphbefore, insertparagraphafter, blockquote, bar, bold, italic, underline, strikethrough,
				subscript, superscript, linebreak, bar, lefttoright, righttoleft, bar, left, center, right, justifyfull,
				orderedlist, unorderedlist, outdent, indent, bar, textcolor, bgcolor, textindicator, bar, emoticon,
				insertcharacter, line, link, image, table, user, acronym, linebreak, bar, findreplace, spellcheck, bar, chMode, inserttag,
				removeformat, bar, copy, cut, paste, bar, undo, redo, bar, showhelp, about, linebreak,
				bar, toggleborders, bar, tableproperties, bar, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit, bar,
				columninsertbefore, columninsertafter, columndelete, columnsplit, bar,
				cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge';
		}
		
			// Additional buttons from registered plugins
		foreach($this->registeredPlugins as $pluginId => $plugin) {
			if ($this->isPluginEnabled($pluginId)) {
				$this->defaultToolbarOrder = $plugin->addButtonsToToolbar();
			}
		}
		$toolbarOrder = $this->thisConfig['toolbarOrder'] ? $this->thisConfig['toolbarOrder'] : $this->defaultToolbarOrder;

			// Getting rid of undefined buttons
		$this->toolbarOrderArray = array_intersect(t3lib_div::trimExplode(',', $toolbarOrder, 1), t3lib_div::trimExplode(',', $this->defaultToolbarOrder, 1));
		$toolbarOrder = array_unique(array_values($this->toolbarOrderArray));

			// Fetching specConf for field from backend
		$pList = is_array($this->specConf['richtext']['parameters']) ? implode(',',$this->specConf['richtext']['parameters']) : '';
		if ($pList != '*') {	// If not all
			$show = is_array($this->specConf['richtext']['parameters']) ? $this->specConf['richtext']['parameters'] : array();
			if ($this->thisConfig['showButtons'])	{
				if (!t3lib_div::inList($this->thisConfig['showButtons'],'*')) {
					$show = array_unique(array_merge($show,t3lib_div::trimExplode(',',$this->thisConfig['showButtons'],1)));
				} else {
					$show = array_unique(array_merge($show, $toolbarOrder));
				}
			}
			if (is_array($this->thisConfig['showButtons.'])) {
				foreach ($this->thisConfig['showButtons.'] as $buttonId => $value) {
					if ($value) $show[] = $buttonId;
				}
				$show = array_unique($show);
			}
		} else {
			$show = $toolbarOrder;
		}

			// Resticting to RTEkeyList for backend user
		if(is_object($BE_USER)) {
			$RTEkeyList = isset($BE_USER->userTS['options.']['RTEkeyList']) ? $BE_USER->userTS['options.']['RTEkeyList'] : '*';
			if ($RTEkeyList != '*')	{ 	// If not all
				$show = array_intersect($show, t3lib_div::trimExplode(',',$RTEkeyList,1));
			}
		}
		
			// Hiding buttons of disabled plugins
		$hideButtons = array('space', 'bar', 'linebreak');
		foreach ($this->pluginButton as $pluginId => $buttonList) {
			if (!$this->isPluginEnabled($pluginId)) {
				$buttonArray = t3lib_div::trimExplode(',',$buttonList,1);
				foreach ($buttonArray as $button) {
					$hideButtons[] = $button;
				}
			}
		}
		
			// Hiding labels of disabled plugins
		foreach ($this->pluginLabel as $pluginId => $label) {
			if (!$this->isPluginEnabled($pluginId)) {
				$hideButtons[] = $label;
			}
		}
		
			// Hiding buttons not implemented in some clients
		foreach ($this->hideButtonsFromClient as $client => $buttonArray) {
			if ($this->client['BROWSER'] == $client) {
				foreach($buttonArray as $buttonId) {
					$hideButtons[] = $buttonId;
				}
			}
		}
		
			// Hiding the buttons
		$show = array_diff($show, $this->conf_toolbar_hide, $hideButtons, t3lib_div::trimExplode(',',$this->thisConfig['hideButtons'],1));

			// Adding the always show buttons
		$show = array_unique(array_merge($show, $this->conf_toolbar_show));
		$toolbarOrder = array_unique(array_merge($toolbarOrder, $this->conf_toolbar_show));
		foreach ($this->conf_toolbar_show as $buttonId) {
			if (!in_array($buttonId, $this->toolbarOrderArray)) $this->toolbarOrderArray[] = $buttonId;
		}
		
			// Getting rid of the buttons for which we have no position
		$show = array_intersect($show, $toolbarOrder);
		$this->toolbar = $show;
	}

	/**
	 * Disable some plugins
	 *
	 */

	function setPlugins() {
		global $TYPO3_CONF_VARS;
		
		$hideButtons = array();
		
			// Disabling the plugins if their buttons are not in the toolbar
		$hidePlugins = array();
		foreach ($this->pluginButton as $pluginId => $buttonList) {
			$showPlugin = false;
			$buttonArray = t3lib_div::trimExplode(',', $buttonList, 1);
			foreach ($buttonArray as $button) {
				if (in_array($button, $this->toolbar)) {
					$showPlugin = true;
				}
			}
			if (!$showPlugin) {
				$hidePlugins[] = $pluginId;
			}
		}
		if($this->thisConfig['disableContextMenu'] || $this->thisConfig['disableRightClick']) $hidePlugins[] = 'ContextMenu';
		if($this->thisConfig['disableSelectColor']) $hidePlugins[] = 'SelectColor';
		if($this->thisConfig['disableTYPO3Browsers']) $hidePlugins[] = 'TYPO3Browsers';
		if(!$this->thisConfig['enableWordClean'] || !is_array($this->thisConfig['enableWordClean.'])) $hidePlugins[] = 'TYPO3HtmlParser';
		if(!t3lib_extMgm::isLoaded('static_info_tables') || in_array($this->language, t3lib_div::trimExplode(',', $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['noSpellCheckLanguages']))) $hidePlugins[] = 'SpellChecker';
		$this->pluginEnabledArray = array_diff($this->pluginEnabledArray, $hidePlugins);
		
			// Hiding labels of disabled plugins
		foreach ($this->pluginLabel as $pluginId => $label) {
			if (!$this->isPluginEnabled($pluginId)) {
				$hideButtons[] = $label;
			}
		}
		$this->toolbar = array_diff($this->toolbar, $hideButtons);
		
			// Completing the toolbar converion array for htmlArea
		foreach ($this->registeredPlugins as $pluginId => $plugin) {
			if ($this->isPluginEnabled($pluginId)) {
				$this->convertToolbarForHtmlAreaArray = array_unique(array_merge($this->convertToolbarForHtmlAreaArray, $plugin->getConvertToolbarForHtmlAreaArray()));
			}
		}
			// Renaming buttons of replacement plugins
		if( $this->isPluginEnabled('SelectColor') ) {
			$this->convertToolbarForHtmlAreaArray['textcolor'] = 'CO-forecolor';
			$this->convertToolbarForHtmlAreaArray['bgcolor'] = 'CO-hilitecolor';
		}
	}

	/**
	 * Convert the TYPO3 names of buttons into the names for htmlArea RTE
	 * 
	 * @param	string	buttonname (typo3-name)
	 * @return	string	buttonname (htmlarea-name)
	 */

	 function convertToolbarForHTMLArea($button) {
 		return $this->convertToolbarForHtmlAreaArray[$button];
	 }

	/**
	 * Return the JS-function for setting the RTE size.
	 *
	 * @param	string		DivID-Name
	 * @param	int			the height for the RTE
	 * @param	int			the width for the RTE
	 * @return string		Loader function in JS
	 */
	function setRTEsizeByJS($divId, $height, $width) {
		return '
			setRTEsizeByJS(\''.$divId.'\','.$height.', '.$width.');
		';
	}

	/**
	 * Return the HTML code for loading the Javascript files
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		the html code for loading the Javascript Files
 	 */
	function loadJSfiles($RTEcounter) {
		global $TYPO3_CONF_VARS;
		
		$loadJavascriptCode = '
		<script type="text/javascript">
		/*<![CDATA[*/
			var i=1;
			while (document.getElementById("pleasewait" + i)) {
				document.getElementById("pleasewait" + i).style.display = "block";
				document.getElementById("editorWrap" + i).style.visibility = "hidden";
				i++;
			};
			var RTEarea = new Array();
			RTEarea[0] = new Object();
			RTEarea[0]["version"] = "' . $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['version'] . '";
			RTEarea[0]["popupwin"] = "' . $this->writeTemporaryFile('EXT:' . $this->ID . '/htmlarea/popupwin.js', "popupwin") . '";'
			. (($this->client['BROWSER'] == 'msie') ? ('
			RTEarea[0]["htmlarea-ie"] = "' . $this->writeTemporaryFile('EXT:' . $this->ID . '/htmlarea/htmlarea-ie.js', "htmlarea-ie") . '";')
			: ('
			RTEarea[0]["htmlarea-gecko"] = "' . $this->writeTemporaryFile('EXT:' . $this->ID . '/htmlarea/htmlarea-gecko.js', "htmlarea-gecko") . '";')) . '
			var _editor_url = "' . $this->extHttpPath . 'htmlarea";
			var _editor_lang = "' . $this->language . '";
			var _editor_CSS = "' . $this->editorCSS . '";
			var _editor_skin = "' . dirname($this->editorCSS) . '";
			var _editor_edited_content_CSS = "' .  $this->editedContentCSS  . '";
			var _typo3_host_url = "' . $this->hostURL . '";
			var _editor_debug_mode = ' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableDebugMode'] ? 'true' : 'false') . ';
			var _editor_compressed_scripts = ' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] ? 'true' : 'false') . ';'
			. (($this->client['BROWSER'] == 'gecko') ? ('
			var _editor_mozAllowClipboard_url = "' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['mozAllowClipboardURL'] ? $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['mozAllowClipboardURL'] : '') . '";')
			: '') . '
			var _spellChecker_lang = "' . $this->spellCheckerLanguage . '";
			var _spellChecker_charset = "' . $this->spellCheckerCharset . '";
			var _spellChecker_mode = "' . $this->spellCheckerMode . '";
		/*]]>*/
		</script>';
		$loadJavascriptCode .= '
		<script type="text/javascript" src="' . $this->buildJSMainLangFile($RTEcounter) . '"></script>
		<script type="text/javascript" src="' . $this->writeTemporaryFile('EXT:' . $this->ID . '/htmlarea/htmlarea.js', "htmlarea") . '"></script>
		';
		return $loadJavascriptCode;
	}
	
	/**
	 * Return the inline Javascript code for initializing the RTE
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return 	string		the inline Javascript code for initializing the RTE
	 */
	 
	function loadJScode($RTEcounter) {
		global $TYPO3_CONF_VARS;
		
		$loadPluginCode = '';
		foreach ($this->pluginEnabledCumulativeArray[$RTEcounter] as $pluginId) {
			$extensionKey = is_object($this->registeredPlugins[$pluginId]) ? $this->registeredPlugins[$pluginId]->getExtensionKey() : $this->ID;
			$loadPluginCode .= '
			HTMLArea.loadPlugin("' . $pluginId . '", true, "' . $this->writeTemporaryFile('EXT:' . $extensionKey . '/htmlarea/plugins/' . $pluginId . '/' . strtolower(preg_replace('/([a-z])([A-Z])([a-z])/', "$1".'-'."$2"."$3", $pluginId)) . '.js', $pluginId) . '");';
		}
		return (!$this->is_FE() ? '' : '
		' . '/*<![CDATA[*/') . ($this->is_FE() ? '' : '
			RTEarea[0]["RTEtsConfigParams"] = "&RTEtsConfigParams=' . rawurlencode($this->RTEtsConfigParams()) . '";
			RTEarea[0]["pathAcronymModule"] = "../../mod2/acronym.php";
			RTEarea[0]["pathLinkModule"] = "../../mod3/browse_links.php";
			RTEarea[0]["pathImageModule"] = "../../mod4/select_image.php";
			RTEarea[0]["pathUserModule"] = "../../mod5/user.php";
			RTEarea[0]["pathParseHtmlModule"] = "' . $this->extHttpPath . 'mod6/parse_html.php";')
			. $loadPluginCode .  '
			HTMLArea.init();' . (!$this->is_FE() ? '' : '
		/*]]>*/
		');
	}
	
	/**
	 * Return the Javascript code for configuring the RTE
	 * 
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 * @param	string		$table: The table that includes this RTE (optional, necessary for IRRE).
	 * @param	string		$uid: The uid of that table that includes this RTE (optional, necessary for IRRE).
	 * @param	string		$field: The field of that record that includes this RTE (optional).
	 *
	 * @return	string		the Javascript code for configuring the RTE
	 */
	function registerRTEinJS($RTEcounter, $table='', $uid='', $field='') {
		global $TYPO3_CONF_VARS;
		
		$configureRTEInJavascriptString = (!$this->is_FE() ? '' : '
			' . '/*<![CDATA[*/') . '
			RTEarea['.$RTEcounter.'] = new Object();
			RTEarea['.$RTEcounter.']["RTEtsConfigParams"] = "&RTEtsConfigParams=' . rawurlencode($this->RTEtsConfigParams()) . '";
			RTEarea['.$RTEcounter.']["number"] = '.$RTEcounter.';
			RTEarea['.$RTEcounter.']["id"] = "RTEarea'.$RTEcounter.'";
			RTEarea['.$RTEcounter.']["enableWordClean"] = ' . (trim($this->thisConfig['enableWordClean'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["htmlRemoveComments"] = ' . (trim($this->thisConfig['removeComments'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["disableEnterParagraphs"] = ' . (trim($this->thisConfig['disableEnterParagraphs'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["removeTrailingBR"] = ' . (trim($this->thisConfig['removeTrailingBR'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["useCSS"] = ' . (trim($this->thisConfig['useCSS'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["keepButtonGroupTogether"] = ' . (trim($this->thisConfig['keepButtonGroupTogether'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["disablePCexamples"] = ' . (trim($this->thisConfig['disablePCexamples'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["statusBar"] = ' . (trim($this->thisConfig['showStatusBar'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["showTagFreeClasses"] = ' . (trim($this->thisConfig['showTagFreeClasses'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["useHTTPS"] = ' . ((trim(stristr($this->siteURL, 'https')) || $this->thisConfig['forceHTTPS'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["enableMozillaExtension"] = ' . (($this->client['BROWSER'] == 'gecko' && $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableMozillaExtension'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["tceformsNested"] = ' . (is_object($this->TCEform) && method_exists($this->TCEform, 'getDynNestedStack') ? $this->TCEform->getDynNestedStack(true) : '[]') . ';';

			// The following properties apply only to the backend
		if (!$this->is_FE()) {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["sys_language_content"] = "' . $this->contentLanguageUid . '";
			RTEarea['.$RTEcounter.']["typo3ContentLanguage"] = "' . $this->contentTypo3Language . '";
			RTEarea['.$RTEcounter.']["typo3ContentCharset"] = "' . $this->contentCharset . '";
			RTEarea['.$RTEcounter.']["enablePersonalDicts"] = ' . ($this->spellCheckerPersonalDicts ? 'true' : 'false') . ';
			RTEarea['.$RTEcounter.']["userUid"] = "' . $this->userUid . '";';
		}
		
			// Setting the plugin flags
		$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["plugin"] = new Object();';
		foreach ($this->pluginEnabledArray as $pluginId) {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["plugin"]["'.$pluginId.'"] = true;';
		}
		
			// Setting the buttons configuration
		$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["buttons"] = new Object();';
		if (is_array($this->thisConfig['buttons.'])) {
			foreach ($this->thisConfig['buttons.'] as $buttonIndex => $conf) {
				$button = substr($buttonIndex, 0, -1);
				if (in_array($button,$this->toolbar)) {
					$indexButton = 0;
					$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["buttons"]["'.$button.'"] = {';
					if (is_array($conf)) {
						foreach ($conf as $propertyName => $conf1) {
							$property = $propertyName;
							if ($indexButton) {
								$configureRTEInJavascriptString .= ', ';
							}
							if (is_array($conf1)) {
								$property = substr($property, 0, -1);
								$indexProperty = 0;
								$configureRTEInJavascriptString .= '"'.$property.'" : {';
								foreach ($conf1 as $property1Name => $conf2) {
									$property1 = $property1Name;
									if ($indexProperty) {
										$configureRTEInJavascriptString .= ', ';
									}
									if (is_array($conf2)) {
										$property1 = substr($property1, 0, -1);
										$indexProperty1 = 0;
										$configureRTEInJavascriptString .= '"'.$property1.'" : {';
										foreach ($conf2 as $property2Name => $conf3) {
											$property2 = $property2Name;
											if ($indexProperty1) {
												$configureRTEInJavascriptString .= ', ';
											}
											if (is_array($conf3)) {
												$property2 = substr($property2, 0, -1);
												$indexProperty2 = 0;
												$configureRTEInJavascriptString .= '"'.$property2.'" : {';
												foreach($conf3 as $property3Name => $conf4) {
													$property3 = $property3Name;
													if ($indexProperty2) {
														$configureRTEInJavascriptString .= ', ';
													}
													if (!is_array($conf4)) {
														$configureRTEInJavascriptString .= '"'.$property3.'" : '.($conf4?'"'.$conf4.'"':'false');
													}
													$indexProperty2++;
												}
												$configureRTEInJavascriptString .= '}';
											} else {
												$configureRTEInJavascriptString .= '"'.$property2.'" : '.($conf3?'"'.$conf3.'"':'false');												
											}
											$indexProperty1++;
										}
										$configureRTEInJavascriptString .= '}';
									} else {
										$configureRTEInJavascriptString .= '"'.$property1.'" : '.($conf2?'"'.$conf2.'"':'false');
									}
									$indexProperty++;
								}
								$configureRTEInJavascriptString .= '}';
							} else {
								$configureRTEInJavascriptString .= '"'.$property.'" : '.($conf1?'"'.$conf1.'"':'false');
							}
							$indexButton++;
						}
					}
					$configureRTEInJavascriptString .= '};';
				}
			}
		}
		
			// Deprecated inserttag button configuration
		if (in_array('inserttag', $this->toolbar) && trim($this->thisConfig['hideTags'])) {
			if (!is_array($this->thisConfig['buttons.']['inserttag.'])) {
				$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["buttons"]["inserttag"] = new Object();
			RTEarea['.$RTEcounter.']["buttons"]["inserttag"]["denyTags"] = "'.implode(',', t3lib_div::trimExplode(',', $this->thisConfig['hideTags'], 1)).'";';
			} elseif (!$this->thisConfig['buttons.']['inserttag.']['denyTags']) {
				$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["buttons"]["inserttag"]["denyTags"] = "'.implode(',', t3lib_div::trimExplode(',', $this->thisConfig['hideTags'], 1)).'";';
			}
		}
		
			// Setting the list of tags to be removed if specified in the RTE config
		if (trim($this->thisConfig['removeTags']))  {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["htmlRemoveTags"] = /^(' . implode('|', t3lib_div::trimExplode(',', $this->thisConfig['removeTags'], 1)) . ')$/i;';
		}
		
			// Setting the list of tags to be removed with their contents if specified in the RTE config
		if (trim($this->thisConfig['removeTagsAndContents']))  {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["htmlRemoveTagsAndContents"] = /^(' . implode('|', t3lib_div::trimExplode(',', $this->thisConfig['removeTagsAndContents'], 1)) . ')$/i;';
		}
		
			// Process default style configuration
		$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["defaultPageStyle"] = "' . $this->hostURL . $this->writeTemporaryFile('', 'defaultPageStyle', 'css', $this->buildStyleSheet()) . '";';
			
			// Setting the pageStyle
		$filename = trim($this->thisConfig['contentCSS']) ? trim($this->thisConfig['contentCSS']) : 'EXT:' . $this->ID . '/res/contentcss/default.css';
		$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["pageStyle"] = "' . $this->getFullFileName($filename) .'";';
		
			// Process colors configuration
		if ( $this->isPluginEnabled('SelectColor') ) {
			$configureRTEInJavascriptString .= $this->buildJSColorsConfig($RTEcounter);
		}
		
			// Process classes configuration
		$classesConfigurationRequired = false;
		foreach ($this->registeredPlugins as $pluginId => $plugin) {
			if ($this->isPluginEnabled($pluginId)) {
				$classesConfigurationRequired = $classesConfigurationRequired || $plugin->requiresClassesConfiguration();
			}
		}
		if ($classesConfigurationRequired) {
			$configureRTEInJavascriptString .= $this->buildJSClassesConfig($RTEcounter);
		}
		
			// Process font faces configuration
		if (in_array('fontstyle',$this->toolbar)) {
			$configureRTEInJavascriptString .= $this->buildJSFontFacesConfig($RTEcounter);
		}
		
			// Process font sizes configuration
		if (in_array('fontsize',$this->toolbar)) {
			$configureRTEInJavascriptString .= $this->buildJSFontSizesConfig($RTEcounter);
		}
		
		if ($this->isPluginEnabled('TableOperations')) {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["hideTableOperationsInToolbar"] = ' . (trim($this->thisConfig['hideTableOperationsInToolbar']) ? 'true' : 'false') . ';
			RTEarea['.$RTEcounter.']["disableLayoutFieldsetInTableOperations"] = ' . (trim($this->thisConfig['disableLayoutFieldsetInTableOperations'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["disableAlignmentFieldsetInTableOperations"] = ' . (trim($this->thisConfig['disableAlignmentFieldsetInTableOperations'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["disableSpacingFieldsetInTableOperations"] = ' . (trim($this->thisConfig['disableSpacingFieldsetInTableOperations'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["disableBordersFieldsetInTableOperations"] = ' . (trim($this->thisConfig['disableBordersFieldsetInTableOperations'])?'true':'false') . ';
			RTEarea['.$RTEcounter.']["disableColorFieldsetInTableOperations"] = ' . (trim($this->thisConfig['disableColorFieldsetInTableOperations'])?'true':'false') . ';';
				// // Deprecated toggleborders button configuration
			if (in_array('toggleborders',$this->toolbar) && $this->thisConfig['keepToggleBordersInToolbar']) {
				if (!is_array($this->thisConfig['buttons.']['toggleborders.'])) {
					$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["buttons"]["toggleborders"] = new Object();
			RTEarea['.$RTEcounter.']["buttons"]["toggleborders"]["keepInToolbar"] = true;';
				} elseif (!$this->thisConfig['buttons.']['toggleborders.']['keepInToolbar']) {
					$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["buttons"]["toggleborders"]["keepInToolbar"] = true;';
				}
			}
		}
		
		if ($this->isPluginEnabled('Acronym')) {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["acronymUrl"] = "' . $this->writeTemporaryFile('', 'acronym_'.$this->contentLanguageUid, 'js', $this->buildJSAcronymArray()) . '";';
		}
		
		if ($this->isPluginEnabled('TYPO3Browsers')) {
			$configureRTEInJavascriptString .= $this->buildJSClassesAnchorConfig($RTEcounter);
		}
		
			// Add Javascript configuration for registered plugins
		foreach ($this->registeredPlugins as $pluginId => $plugin) {
			if ($this->isPluginEnabled($pluginId)) {
				$configureRTEInJavascriptString .= $plugin->buildJavascriptConfiguration($RTEcounter);
			}
		}
		
		$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["toolbar"] = '.$this->getJSToolbarArray().';
			HTMLArea.initEditor('.$RTEcounter.');' . (!$this->is_FE() ? '' : '
			/*]]>*/');
		return $configureRTEInJavascriptString;
	}

	/**
	 * Return true, if the plugin can be loaded
	 *
	 * @param	string		$pluginId: The identification string of the plugin
	 *
	 * @return	boolean		true if the plugin can be loaded
	 */
	
	function isPluginEnabled($pluginId) { 
		return in_array($pluginId, $this->pluginEnabledArray);
	}
	
	
	/**
	 * Return Javascript configuration of font sizes
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		Javascript font sizes configuration
	 */
	function buildJSFontSizesConfig($RTEcounter) {
		global $LANG, $TSFE;
		$configureRTEInJavascriptString = '';
		
			// Builing JS array of default font sizes
		$HTMLAreaFontSizes = array();
		if ($this->is_FE()) {
			$HTMLAreaFontSizes[0] = $TSFE->csConvObj->conv($TSFE->getLLL('No size',$this->LOCAL_LANG), $TSFE->labelsCharset, $TSFE->renderCharset);
		} else {
			$HTMLAreaFontSizes[0] = $LANG->getLL('No size');
		}
		
		foreach ($this->defaultFontSizes as $FontSizeItem => $FontSizeLabel) {
			if ($this->client['BROWSER'] == 'safari') {
				$HTMLAreaFontSizes[$FontSizeItem] = $this->defaultFontSizes_safari[$FontSizeItem];
			} else {
				$HTMLAreaFontSizes[$FontSizeItem] = $FontSizeLabel;
			}
		}
		if ($this->thisConfig['hideFontSizes'] ) {
			$hideFontSizes =  t3lib_div::trimExplode(',', $this->cleanList($this->thisConfig['hideFontSizes']), 1);
			foreach ($hideFontSizes as $item)  {
				if ($HTMLAreaFontSizes[strtolower($item)]) {
					unset($HTMLAreaFontSizes[strtolower($item)]);
				}
			}
		}
		
		$HTMLAreaJSFontSize = '{';
		if ($this->cleanList($this->thisConfig['hideFontSizes']) != '*') {
			$HTMLAreaFontSizeIndex = 0;
			foreach ($HTMLAreaFontSizes as $FontSizeItem => $FontSizeLabel) {
				if($HTMLAreaFontSizeIndex) { 
					$HTMLAreaJSFontSize .= ',';
				}
				$HTMLAreaJSFontSize .= '
				"' . $FontSizeLabel . '" : "' . ($FontSizeItem?$FontSizeItem:'') . '"';
				$HTMLAreaFontSizeIndex++;
			}
		}
		$HTMLAreaJSFontSize .= '};';
		$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["fontsize"] = '. $HTMLAreaJSFontSize;
			
		return $configureRTEInJavascriptString;
	}
	
	/**
	 * Return Javascript configuration of font faces
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		Javascript configuration of font faces
 	 */
	function buildJSFontfacesConfig($RTEcounter) {
		global $TSFE, $LANG;
		
		if ($this->is_FE()) {
			$RTEProperties = $this->RTEsetup;
		} else {
			$RTEProperties = $this->RTEsetup['properties'];
		}
		
		$configureRTEInJavascriptString = '';
		
			// Builing JS array of default font faces
		$HTMLAreaFontname = array();
		$HTMLAreaFontname['nofont'] = '
				"' . $fontName . '" : "' . $this->cleanList($fontValue) . '"';
		$defaultFontFacesList = 'nofont,';
		if ($this->is_FE()) {
			$HTMLAreaFontname['nofont'] = '
				"' . $TSFE->csConvObj->conv($TSFE->getLLL('No font',$this->LOCAL_LANG), $TSFE->labelsCharset, $TSFE->renderCharset) . '" : ""';
		} else {
			$HTMLAreaFontname['nofont'] = '
				"' . $LANG->getLL('No font') . '" : ""';
		}
		
		$hideFontFaces = $this->cleanList($this->thisConfig['hideFontFaces']);
		if ($hideFontFaces != '*') {
			$index = 0;
			foreach ($this->defaultFontFaces as $fontName => $fontValue) {
				if (!t3lib_div::inList($hideFontFaces, $index+1)) {
					$HTMLAreaFontname[$fontName] = '
				"' . $fontName . '" : "' . $this->cleanList($fontValue) . '"';
					$defaultFontFacesList .= $fontName . ',';
				}
				$index++;
			}
		}
		
			// Adding configured font faces
		if (is_array($RTEProperties['fonts.'])) {
			foreach ($RTEProperties['fonts.'] as $fontName => $conf) {
				$fontName = substr($fontName,0,-1);
				$fontLabel = $this->getPageConfigLabel($conf['name'],0);
				$HTMLAreaFontname[$fontName] = '
				"' . $fontLabel . '" : "' . $this->cleanList($conf['value']) . '"';
			}
		}
		
			// Setting the list of font faces
		$HTMLAreaJSFontface = '{';
		$HTMLAreaFontface = t3lib_div::trimExplode(',' , $this->cleanList($defaultFontFacesList . ',' . $this->thisConfig['fontFace']));
		$HTMLAreaFontfaceIndex = 0;
		foreach ($HTMLAreaFontface as $fontName) {
			if($HTMLAreaFontfaceIndex) { 
				$HTMLAreaJSFontface .= ',';
			}
			$HTMLAreaJSFontface .= $HTMLAreaFontname[$fontName];
			$HTMLAreaFontfaceIndex++;
		}
		$HTMLAreaJSFontface .= '};';
		
		$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["fontname"] = '. $HTMLAreaJSFontface;
		
		return $configureRTEInJavascriptString;
	}
	
	/**
	 * Return Javascript configuration of colors
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		Javascript configuration of colors
	 */
	function buildJSColorsConfig($RTEcounter) {
		global $TSFE, $LANG;
		
		if ($this->is_FE()) {
			$RTEProperties = $this->RTEsetup;
		} else {
			$RTEProperties = $this->RTEsetup['properties'];
		}
		
		$configureRTEInJavascriptString = '';
		
		if(trim($this->thisConfig['disableColorPicker'])) {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["disableColorPicker"] = true;';
		} else {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["disableColorPicker"] = false;';
		}
		
			// Building JS array of configured colors
		if (is_array($RTEProperties['colors.']) )  {
			$HTMLAreaColorname = array();
			foreach ($RTEProperties['colors.'] as $colorName => $conf) {
				$colorName=substr($colorName,0,-1);
				$colorLabel = $this->getPageConfigLabel($conf['name']);
				$HTMLAreaColorname[$colorName] = '
				[' . $colorLabel . ' , "' . $conf['value'] . '"]';
			}
		}
		
			// Setting the list of colors if specified in the RTE config
		if ($this->thisConfig['colors'] ) {
			$HTMLAreaJSColors = '[';
			$HTMLAreaColors = t3lib_div::trimExplode(',' , $this->cleanList($this->thisConfig['colors']));
			$HTMLAreaColorsIndex = 0;
			foreach ($HTMLAreaColors as $colorName) {
				if($HTMLAreaColorsIndex && $HTMLAreaColorname[$colorName]) { 
					$HTMLAreaJSColors .= ',';
				}
				$HTMLAreaJSColors .= $HTMLAreaColorname[$colorName];
				$HTMLAreaColorsIndex++;
			}
			$HTMLAreaJSColors .= '];';
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["colors"] = '. $HTMLAreaJSColors;
		}
		
		return $configureRTEInJavascriptString;
	}
	
	/**
	 * Build the default content style sheet
	 *
	 * @return string		Style sheet
	 */
	function buildStyleSheet() {
		
		if (!trim($this->thisConfig['ignoreMainStyleOverride'])) {
			$mainStyle_font = $this->thisConfig['mainStyle_font'] ? $this->thisConfig['mainStyle_font']: 'Verdana,sans-serif';
			
			$mainElements = array();
			$mainElements['P'] = $this->thisConfig['mainStyleOverride_add.']['P'];
			$elList = explode(',','H1,H2,H3,H4,H5,H6,PRE');
			foreach ($elList as $elListName) {
				if ($this->thisConfig['mainStyleOverride_add.'][$elListName]) {
					$mainElements[$elListName] = $this->thisConfig['mainStyleOverride_add.'][$elListName];
				}
			}
			
			$addElementCode = '';
			foreach ($mainElements as $elListName => $elValue) {
				$addElementCode .= strToLower($elListName) . ' {' . $elValue . '}' . chr(10);
			}
			
			$stylesheet = $this->thisConfig['mainStyleOverride'] ? $this->thisConfig['mainStyleOverride'] : chr(10) .
				'body.htmlarea-content-body { font-family: ' . $mainStyle_font .
					'; font-size: '.($this->thisConfig['mainStyle_size'] ? $this->thisConfig['mainStyle_size'] : '12px') .
					'; color: '.($this->thisConfig['mainStyle_color']?$this->thisConfig['mainStyle_color'] : 'black') .
					'; background-color: '.($this->thisConfig['mainStyle_bgcolor'] ? $this->thisConfig['mainStyle_bgcolor'] : 'white') .
					';'.$this->thisConfig['mainStyleOverride_add.']['BODY'].'}' . chr(10) .
				'td { ' . $this->thisConfig['mainStyleOverride_add.']['TD'].'}' . chr(10) .
				'div { ' . $this->thisConfig['mainStyleOverride_add.']['DIV'].'}' . chr(10) .
				'pre { ' . $this->thisConfig['mainStyleOverride_add.']['PRE'].'}' . chr(10) .
				'ol { ' . $this->thisConfig['mainStyleOverride_add.']['OL'].'}' . chr(10) .
				'ul { ' . $this->thisConfig['mainStyleOverride_add.']['UL'].'}' . chr(10) .
				'blockquote { ' . $this->thisConfig['mainStyleOverride_add.']['BLOCKQUOTE'].'}' . chr(10) .
				$addElementCode;
	
			if (is_array($this->thisConfig['inlineStyle.']))        {
				$stylesheet .= chr(10) . implode(chr(10), $this->thisConfig['inlineStyle.']) . chr(10);
			}
		} else {
			$stylesheet = '/* mainStyleOverride and inlineStyle properties ignored. */';
		}
		return $stylesheet;
	}
	
	/**
	 * Return Javascript configuration of classes
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		Javascript configuration of classes
	 */
	function buildJSClassesConfig($RTEcounter) {
			// Build JS array of lists of classes
		$classesTagList = 'classesCharacter, classesParagraph, classesImage, classesTable, classesLinks, classesTD';
		$classesTagConvert = array( 'classesCharacter' => 'span', 'classesParagraph' => 'p', 'classesImage' => 'img', 'classesTable' => 'table', 'classesLinks' => 'a', 'classesTD' => 'td');
		$classesTagArray = t3lib_div::trimExplode(',' , $classesTagList);
		$configureRTEInJavascriptString = '
			RTEarea['.$RTEcounter.']["classesTag"] = new Object();';
		foreach ($classesTagArray as $classesTagName) {
			$HTMLAreaJSClasses = ($this->thisConfig[$classesTagName])?('"' . $this->cleanList($this->thisConfig[$classesTagName]) . '";'):'null;';
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["classesTag"]["'. $classesTagConvert[$classesTagName] .'"] = '. $HTMLAreaJSClasses;
		}
		
			// Include JS arrays of configured classes
		$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["classesUrl"] = "' . $this->hostURL . $this->writeTemporaryFile('', 'classes_'.$LANG->lang, 'js', $this->buildJSClassesArray()) . '";';
		
		return $configureRTEInJavascriptString;
	}
	
	/**
	 * Return JS arrays of classes labels and noShow flags
	 *
	 * @return string		JS classes arrays
	 */
	function buildJSClassesArray() {
		global $TSFE, $LANG, $TYPO3_CONF_VARS;
		
		if ($this->is_FE()) {
			$RTEProperties = $this->RTEsetup;
		} else {
			$RTEProperties = $this->RTEsetup['properties'];
		}
		
		$linebreak = $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] ? '' : chr(10);
		$index = 0;
		$JSClassesLabelsArray = 'HTMLArea.classesLabels = { ' . $linebreak;
		$JSClassesValuesArray = 'HTMLArea.classesValues = { ' . $linebreak;
		$JSClassesNoShowArray = 'HTMLArea.classesNoShow = { ' . $linebreak;
		$JSClassesXORArray = 'HTMLArea.classesXOR = { ' . $linebreak;
		
			// Scanning the list of classes if specified in the RTE config
		if (is_array($RTEProperties['classes.']))  {
			$stylesheet = '';
			foreach ($RTEProperties['classes.'] as $className => $conf) {
				$className = substr($className,0,-1);
				$classLabel = $this->getPageConfigLabel($conf['name']);
				$JSClassesLabelsArray .= (($index)?',':'') . '"' . $className . '": ' . $classLabel . $linebreak;
				$JSClassesValuesArray .= (($index)?',':'') . '"' . $className . '":"' . str_replace('"', '\"', str_replace('\\\'', '\'', $conf['value'])) . '"' . $linebreak;
				$JSClassesNoShowArray .= (($index)?',':'') . '"' . $className . '":' . ($conf['noShow']?'true':'false') . $linebreak;
				if (is_array($RTEProperties['mutuallyExclusiveClasses.']))  {
					foreach ($RTEProperties['mutuallyExclusiveClasses.'] as $listName => $conf) {
						if (t3lib_div::inList($conf, $className)) {
							$JSClassesXORArray .= (($index)?',':'') . '"' . $className . '": /^(' . implode('|', t3lib_div::trimExplode(',', t3lib_div::rmFromList($className, $conf), 1)) . ')$/i' . $linebreak;
							break;
						}
					}
				}
				$index++;
			}
		}
		$JSClassesLabelsArray .= '};' . $linebreak;
		$JSClassesValuesArray .= '};' . $linebreak;
		$JSClassesNoShowArray .= '};' . $linebreak;
		$JSClassesXORArray .= '};' . $linebreak;
		
		return $JSClassesLabelsArray . $JSClassesValuesArray . $JSClassesNoShowArray . $JSClassesXORArray;
	}
	
	/**
	 * Return a Javascript localization array for htmlArea RTE
	 *
	 * @return	string		Javascript localization array
	 */
	function buildJSMainLangArray() {
		global $TSFE, $LANG, $TYPO3_CONF_VARS;
		
		$linebreak = $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] ? '' : chr(10);
		$JSLanguageArray .= 'var HTMLArea_langArray = new Object();' . $linebreak;
		$JSLanguageArray .= 'HTMLArea_langArray = { ' . $linebreak;
		if($this->is_FE()) {
			$JSLanguageArray = $TSFE->csConvObj->conv($JSLanguageArray, 'iso-8859-1', $this->OutputCharset);
		} else {
			$JSLanguageArray = $LANG->csConvObj->conv($JSLanguageArray, 'iso-8859-1', $this->OutputCharset);
		}

		$subArrays = array( 'tooltips', 'msg' , 'dialogs');
		$subArraysIndex = 0;
		foreach($subArrays as $labels) {
			$JSLanguageArray .= (($subArraysIndex++)?',':'') . $labels . ': {' . $linebreak;
			if($this->is_FE()) {
				$LOCAL_LANG = $TSFE->readLLfile(t3lib_extMgm::extPath($this->ID).'htmlarea/locallang_' . $labels . '.xml', $this->language);
				$TSFE->csConvObj->convArray($LOCAL_LANG['default'], 'iso-8859-1', $this->OutputCharset);
				if(!empty($LOCAL_LANG[$this->language])) $TSFE->csConvObj->convArray($LOCAL_LANG[$this->language], $this->charset, $this->OutputCharset);
			} else {
				$LOCAL_LANG = $LANG->readLLfile(t3lib_extMgm::extPath($this->ID).'htmlarea/locallang_' . $labels . '.xml');
				$LANG->csConvObj->convArray($LOCAL_LANG['default'], 'iso-8859-1', $this->OutputCharset);
				if(!empty($LOCAL_LANG[$this->language])) $LANG->csConvObj->convArray($LOCAL_LANG[$this->language], $this->charset, $this->OutputCharset);
			}
			if(!empty($LOCAL_LANG[$this->language])) {
				$LOCAL_LANG[$this->language] = t3lib_div::array_merge_recursive_overrule($LOCAL_LANG['default'], $LOCAL_LANG[$this->language]);
			} else {
				$LOCAL_LANG[$this->language] = $LOCAL_LANG['default'];
			}
			$index = 0;
			foreach ( $LOCAL_LANG[$this->language] as $labelKey => $labelValue ) {
				$JSLanguageArray .=  (($index++)?',':'') . '"' . $labelKey . '":"' . str_replace('"', '\"', $labelValue) . '"' . $linebreak;
			}
			if($this->is_FE()) {
				$JSLanguageArray .= $TSFE->csConvObj->conv(' }' . chr(10), 'iso-8859-1', $this->OutputCharset);
			} else {
				$JSLanguageArray .= $LANG->csConvObj->conv(' }' . chr(10), 'iso-8859-1', $this->OutputCharset);
			}
		}

		if($this->is_FE()) {
			$JSLanguageArray .= $TSFE->csConvObj->conv(' }' . chr(10), 'iso-8859-1', $this->OutputCharset);
		} else {
			$JSLanguageArray .= $LANG->csConvObj->conv(' }' . chr(10), 'iso-8859-1', $this->OutputCharset);
		}
		return $JSLanguageArray;
	}

	/**
	 * Return an acronym array for the Acronym plugin
	 *
	 * @return	string		acronym Javascript array
	 */

	function buildJSAcronymArray() {
		global $TYPO3_CONF_VARS, $TYPO3_DB;
		
		$linebreak = $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] ? '' : chr(10);
		$acronymIndex = 0;
		$abbraviationIndex = 0;
		$JSAcronymArray .= 'acronyms = { ' . $linebreak;
		$JSAbbreviationArray .= 'abbreviations = { ' . $linebreak;
		$table = 'tx_rtehtmlarea_acronym';
		if($this->contentLanguageUid > -1) {
			$whereClause = '(sys_language_uid='.$this->contentLanguageUid . ' OR sys_language_uid=-1)';
		} else {
			$whereClause = '1 = 1';
		}
		$whereClause .= t3lib_BEfunc::BEenableFields($table);
		$whereClause .= t3lib_BEfunc::deleteClause($table);
		$res = $TYPO3_DB->exec_SELECTquery('type,term,acronym', $table, $whereClause);
		while($acronymRow = $TYPO3_DB->sql_fetch_assoc($res))    {
			if($acronymRow['type'] == 1) $JSAcronymArray .= (($acronymIndex++)?',':'') . '"' . $acronymRow['acronym'] . '":"' . $acronymRow['term'] . '"' . $linebreak;
			if($acronymRow['type'] == 2) $JSAbbreviationArray .= (($AbbreviationIndex++)?',':'') . '"' . $acronymRow['acronym'] . '":"' . $acronymRow['term'] . '"' . $linebreak;
		}
		$JSAcronymArray .= '};' . $linebreak;
		$JSAbbreviationArray .= '};' . $linebreak;

		return $JSAcronymArray . $JSAbbreviationArray;
	}
	
	/**
	 * Return Javascript configuration of special anchor classes
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		Javascript configuration of special anchor classes
	 */
	function buildJSClassesAnchorConfig($RTEcounter) {
		
		if ($this->is_FE()) {
			$RTEProperties = $this->RTEsetup;
		} else {
			$RTEProperties = $this->RTEsetup['properties'];
		}
		
		$configureRTEInJavascriptString = '';
		if (is_array($RTEProperties['classesAnchor.'])) {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.']["classesAnchorUrl"] = "' . $this->writeTemporaryFile('', 'classesAnchor_'.$this->contentLanguageUid, 'js', $this->buildJSClassesAnchorArray()) . '";';
		}
		return $configureRTEInJavascriptString;
	}
	
	/**
	 * Return a JS array for special anchor classes
	 *
	 * @return 	string		classesAnchor array definition
	 */
	function buildJSClassesAnchorArray() {
		global $LANG, $TYPO3_CONF_VARS;
		
		$linebreak = $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] ? '' : chr(10);
		$JSClassesAnchorArray .= 'editor.classesAnchorSetup = [ ' . $linebreak;
		$classesAnchorIndex = 0;
		foreach ($this->RTEsetup['properties']['classesAnchor.'] as $label => $conf) {
			if (is_array($conf) && $conf['class']) {
				$JSClassesAnchorArray .= (($classesAnchorIndex++)?',':'') . ' { ' . $linebreak;
				$index = 0;
				$JSClassesAnchorArray .= (($index++)?',':'') . 'name : "' . $conf['class'] . '"' . $linebreak;
				if ($conf['type']) {
					$JSClassesAnchorArray .= (($index++)?',':'') . 'type : "' . $conf['type'] . '"' . $linebreak;
				}
				if (trim(str_replace('\'', '', str_replace('"', '', $conf['image'])))) {
					$JSClassesAnchorArray .= (($index++)?',':'') . 'image : "' . $this->getFullFileName(trim(str_replace('\'', '', str_replace('"', '', $conf['image'])))) . '"' . $linebreak;
				}
				if (trim($conf['altText'])) {
					$string = $this->getLLContent(trim($conf['altText']));
					$JSClassesAnchorArray .= (($index++)?',':'') . 'altText : ' . str_replace('"', '\"', str_replace('\\\'', '\'', $string)) . $linebreak;
				}
				if (trim($conf['titleText'])) {
					$string = $this->getLLContent(trim($conf['titleText']));
					$JSClassesAnchorArray .= (($index++)?',':'') . 'titleText : ' . str_replace('"', '\"', str_replace('\\\'', '\'', $string)) . $linebreak;
				}
				$JSClassesAnchorArray .= '}' . $linebreak;
			}
		}	
		$JSClassesAnchorArray .= '];' . $linebreak;
		return $JSClassesAnchorArray;
	 }
	
	/**
	 * Writes contents in a file in typo3temp/rtehtmlarea directory and returns the file name
	 *
	 * @param	string		$sourceFileName: The name of the file from which the contents should be extracted
	 * @param	string		$label: A label to insert at the beginning of the name of the file
	 * @param	string		$fileExtension: The file extension of the file, defaulting to 'js'
	 * @param	string		$contents: The contents to write into the file if no $sourceFileName is provided
	 *
	 * @return	string		The name of the file writtten to typo3temp/rtehtmlarea
	 */
	private function writeTemporaryFile($sourceFileName='', $label, $fileExtension='js', $contents='') {
		global $TYPO3_CONF_VARS;
		
		if ($sourceFileName) {
			$output = '';
			$source = t3lib_div::getFileAbsFileName($sourceFileName);
			$inputHandle = @fopen($source, "rb");
			while (!feof($inputHandle)) {
				$output .= @fread($inputHandle, 8192);
			}
			fclose($inputHandle);
		} else {
			$output = $contents;
		}
		$compress = $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] && ($fileExtension == 'js') && ($output != '');
		$relativeFilename = 'typo3temp/' . $this->ID . '/' . str_replace('-','_',$label) . '_' . t3lib_div::shortMD5(($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['version'] . ($sourceFileName?$sourceFileName:$output)), 20) . ($compress ? '_compressed' : '') . '.' . $fileExtension;
		$destination = PATH_site . $relativeFilename;
		if(!file_exists($destination)) {
			$compressedJavaScript = '';
			if ($compress) {
				$compressedJavaScript = t3lib_div::minifyJavaScript($output);
			}
			$failure = t3lib_div::writeFileToTypo3tempDir($destination, $compressedJavaScript?$compressedJavaScript:$output);
			if ($failure)  {
				die($failure);
			}
		}
		return ($this->thisConfig['forceHTTPS']?$this->siteURL:$this->httpTypo3Path) . $relativeFilename;
	}
	
	/**
	 * Return a file name containing the main JS language array for HTMLArea
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		filename
	 */
	 
	function buildJSMainLangFile($RTEcounter) { 
		$contents = $this->buildJSMainLangArray() . chr(10);
		foreach ($this->pluginEnabledCumulativeArray[$RTEcounter] as $pluginId) {
			$contents .= $this->buildJSLangArray($pluginId) . chr(10);
		}
		return $this->writeTemporaryFile('', $this->language.'_'.$this->OutputCharset, 'js', $contents);
	}

	/**
	 * Return a Javascript localization array for the plugin
	 *
	 * @param	string		$plugin: identification string of the plugin
	 *
	 * @return	string		Javascript localization array
	 */
	 
	function buildJSLangArray($plugin) {
		global $TSFE, $LANG, $TYPO3_CONF_VARS;
		
		$extensionKey = is_object($this->registeredPlugins[$plugin]) ? $this->registeredPlugins[$plugin]->getExtensionKey() : $this->ID;
		
		$linebreak = $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] ? '' : chr(10);
		if($this->is_FE()) {
			$LOCAL_LANG = $TSFE->readLLfile(t3lib_extMgm::extPath($extensionKey).'htmlarea/plugins/' . $plugin . '/locallang.xml', $this->language);
			if(!empty($LOCAL_LANG['default'])) $TSFE->csConvObj->convArray($LOCAL_LANG['default'], 'iso-8859-1', $this->OutputCharset);
			if(!empty($LOCAL_LANG[$this->language])) $TSFE->csConvObj->convArray($LOCAL_LANG[$this->language], $this->charset, $this->OutputCharset);
		} else {
			$LOCAL_LANG = $LANG->readLLfile(t3lib_extMgm::extPath($extensionKey).'htmlarea/plugins/' . $plugin . '/locallang.xml');
			if(!empty($LOCAL_LANG['default'])) $LANG->csConvObj->convArray($LOCAL_LANG['default'], 'iso-8859-1', $this->OutputCharset);
			if(!empty($LOCAL_LANG[$this->language])) $LANG->csConvObj->convArray($LOCAL_LANG[$this->language], $this->charset, $this->OutputCharset);
		}
		
		if(!empty($LOCAL_LANG[$this->language])) {
			$LOCAL_LANG[$this->language] = t3lib_div::array_merge_recursive_overrule($LOCAL_LANG['default'],$LOCAL_LANG[$this->language]);
		} else {
			$LOCAL_LANG[$this->language] = $LOCAL_LANG['default'];
		}
		
		$JSLanguageArray .= 'var ' . $plugin . '_langArray = new Object();' . $linebreak;
		$JSLanguageArray .= $plugin . '_langArray = {' . $linebreak;
		if($this->is_FE()) {
			$JSLanguageArray = $TSFE->csConvObj->conv($JSLanguageArray, 'iso-8859-1', $this->OutputCharset);
		} else {
			$JSLanguageArray = $LANG->csConvObj->conv($JSLanguageArray, 'iso-8859-1', $this->OutputCharset);
		}
		
		$index = 0;
		foreach ( $LOCAL_LANG[$this->language] as $labelKey => $labelValue ) {
			$JSLanguageArray .=  (($index++)?',':'') . '"' . $labelKey . '":"' . str_replace('"', '\"', $labelValue) . '"' . $linebreak;
		}
		
		if($this->is_FE()) {
			$JSLanguageArray .= $TSFE->csConvObj->conv(' }' . chr(10), 'iso-8859-1', $this->OutputCharset);
		} else {
			$JSLanguageArray .= $LANG->csConvObj->conv(' }' . chr(10), 'iso-8859-1', $this->OutputCharset);
		}

		return $JSLanguageArray;
	}

	/**
	 * Return the JS-Code for the Toolbar-Config-Array for HTML-Area
	 *
	 * @return string		the JS-Code as an JS-Array
	 */

	function getJSToolbarArray() {
		$toolbar = '';			// The JS-Code for the toolbar
		$group = '';			// The TS-Code for the group in the moment, each group are between "bar"s
		$group_has_button = false;	// True if the group has any enabled buttons
		$group_needs_starting_bar = false;
		$previous_is_space = false;

			// process each button in the order list
		foreach ($this->toolbarOrderArray as $button) {
			// check if a new group starts
			if (($button == 'bar' || $button == 'linebreak') && $group_has_button) {
					// New line
				if ($button == 'linebreak') {
					$convertButton = '"' . $this->convertToolbarForHTMLArea('linebreak') . '"';
					$group = ($group!='') ? ($group . ', ' . $convertButton) : $convertButton;
				}
					// New group
				$toolbar .= $toolbar ? (', ' . $group) : ('[[' . $group);
				$group = '';
				$previous_is_space = false;
				$group_has_button = false;
				$group_needs_starting_bar = true;
			} elseif ($toolbar && $button == 'linebreak' && !$group_has_button) {
					// Insert linebreak if no group is opened
				$group = '';
				$previous_is_space = false;
				$group_needs_starting_bar = false;
				$toolbar .= ', "' . $this->convertToolbarForHTMLArea($button) . '"';
			} elseif ($button == 'bar' && !$group_has_button) {
				$group_needs_starting_bar = true;
			} elseif ($button == 'space' && $group_has_button && !$previous_is_space) {
				$convertButton = $this->convertToolbarForHTMLArea($button);
				$convertButton = '"' . $convertButton . '"';
				$group .= $group ? (', ' . $convertButton) : ($group_needs_starting_bar ? ('"' . $this->convertToolbarForHTMLArea('bar') . '", ' . $convertButton) : $convertButton);
				$group_needs_starting_bar = false;
				$previous_is_space = true;
			} elseif (in_array($button, $this->toolbar)) {
					// Add the button to the group
				$convertButton = $this->convertToolbarForHTMLArea($button);
				if ($convertButton) {
					$convertButton = '"' . $convertButton . '"';
					$group .= $group ? (', ' . $convertButton) : ($group_needs_starting_bar ? ('"' . $this->convertToolbarForHTMLArea('bar') . '", ' . $convertButton) : $convertButton);
					$group_has_button = true;
					$group_needs_starting_bar = false;
					$previous_is_space = false;
				}
			}
			// else ignore
		}
			// add the last group
		if($group_has_button) $toolbar .= $toolbar ? (', ' . $group) : ('[[' . $group);
		$toolbar = $toolbar . ']]';
		return $toolbar;
	}
	
	function getLLContent($string) {
		global $LANG;
		
		$BE_lang = $LANG->lang;
		$BE_origCharset = $LANG->origCharSet;
		$BE_charSet = $LANG->charSet;
		$LANG->lang = $this->contentTypo3Language;
		$LANG->origCharSet = $this->origContentCharSet;
		$LANG->charSet = $this->contentCharset;
		$LLString = $LANG->JScharCode($LANG->sL($string));
		$LANG->lang = $BE_lang;
		$LANG->origCharSet = $BE_origCharset;
		$LANG->charSet = $BE_charSet;
		return $LLString;
	}
	
	function getPageConfigLabel($string,$JScharCode=1) {
		global $LANG, $TSFE;
		
		if ($this->is_FE()) {
			$label = $TSFE->csConvObj->conv($TSFE->sL(trim($string)), $TSFE->renderCharset, $TSFE->metaCharset);
			$label = str_replace('"', '\"', str_replace('\\\'', '\'', $label));
			$label = $JScharCode ? $this->feJScharCode($label) : $label;
		} else {
			if (strcmp(substr($string,0,4),'LLL:')) {
				$label = $string;
			} else {
				$label = $LANG->sL(trim($string));
			}
			$label = str_replace('"', '\"', str_replace('\\\'', '\'', $label));
			$label = $JScharCode ? $LANG->JScharCode($label): $label;
		}
		return $label;
	}
	
	function feJScharCode($str) {
		global $TSFE;
			// Convert string to UTF-8:
		if ($this->OutputCharset != 'utf-8') $str = $TSFE->csConvObj->utf8_encode($str,$this->OutputCharset);
			// Convert the UTF-8 string into a array of char numbers:
		$nArr = $TSFE->csConvObj->utf8_to_numberarray($str);
		return 'String.fromCharCode('.implode(',',$nArr).')';
	}
	
	function getFullFileName($filename) {
		if (substr($filename,0,4)=='EXT:')      {       // extension
			list($extKey,$local) = explode('/',substr($filename,4),2);
			$newFilename = '';
			if (strcmp($extKey,'') &&  t3lib_extMgm::isLoaded($extKey) && strcmp($local,'')) {
				$newFilename = $this->siteURL . t3lib_extMgm::siteRelPath($extKey) . $local;
			}
		} elseif (substr($filename,0,1) != '/') {
			$newFilename = $this->siteURL . $filename;
		} else {
			$newFilename = $this->siteURL . substr($filename,1);
		}
		return $newFilename;
	}

	/**
	 * Return the Javascript code for copying the HTML code from the editor into the hidden input field.
	 * This is for submit function of the form.
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 * @param	string		$formName: the name of the form
	 * @param	string		$textareaId: the id of the textarea
	 *
	 * @return	string		Javascript code
	 */
	function setSaveRTE($RTEcounter, $formName, $textareaId) {
		return '
		editornumber = '.$RTEcounter.';
		if (RTEarea[editornumber]) {
			document.'.$formName.'["'.$textareaId.'"].value = RTEarea[editornumber]["editor"].getHTML();
		}
		else {
			OK=0;
		}
		';
	}
	
	/**
	 * Return true if we are in the FE, but not in the FE editing feature of BE.
	 *
	 * @return boolean
	 */
	 
	function is_FE() {
		global $TSFE;
		return is_object($TSFE) && !strstr($this->elementId,'TSFE_EDIT');
	}
	
	/**
	 * Client Browser Information
	 *
	 * Usage: 4
	 *
	 * @param	string		Alternative User Agent string (if empty, t3lib_div::getIndpEnv('HTTP_USER_AGENT') is used)
	 * @return	array		Parsed information about the HTTP_USER_AGENT in categories BROWSER, VERSION, SYSTEM and FORMSTYLE
	 */

	function clientInfo($useragent='')	{
		global $TYPO3_CONF_VARS;
		
		if (!$useragent) $useragent=t3lib_div::getIndpEnv('HTTP_USER_AGENT');
		
		$bInfo=array();
			// Which browser?
		if (strstr($useragent,'Konqueror'))	{
			$bInfo['BROWSER']= 'konqu';
		} elseif (strstr($useragent,'Opera'))	{
			$bInfo['BROWSER']= 'opera';
		} elseif (strstr($useragent,'MSIE'))	{
			$bInfo['BROWSER']= 'msie';
		} elseif (strstr($useragent,'Gecko/'))	{
			$bInfo['BROWSER']='gecko';
		} elseif (strstr($useragent,'Safari/')) {
			$bInfo['BROWSER']='safari';
		} elseif (strstr($useragent,'Mozilla/4')) {
			$bInfo['BROWSER']='net';
		}

		if ($bInfo['BROWSER'])	{
				// Browser version
			switch($bInfo['BROWSER'])	{
				case 'net':
					$bInfo['VERSION']= doubleval(substr($useragent,8));
					if (strstr($useragent,'Netscape6/')) {$bInfo['VERSION']=doubleval(substr(strstr($useragent,'Netscape6/'),10));}
					if (strstr($useragent,'Netscape/7')) {$bInfo['VERSION']=doubleval(substr(strstr($useragent,'Netscape/7'),9));}
				break;
				case 'gecko':
					$tmp = strstr($useragent,'rv:');
					$bInfo['VERSION'] = doubleval(ereg_replace('^[^0-9]*','',substr($tmp,3)));
				break;
				case 'msie':
					$tmp = strstr($useragent,'MSIE');
					$bInfo['VERSION'] = doubleval(ereg_replace('^[^0-9]*','',substr($tmp,4)));
				break;
				case 'safari':
					$tmp = strstr($useragent,'Safari/');
					$bInfo['VERSION'] = doubleval(ereg_replace('^[^0-9]*','',substr($tmp,3)));
				break;
				case 'opera':
					$tmp = strstr($useragent,'Opera');
					$bInfo['VERSION'] = doubleval(ereg_replace('^[^0-9]*','',substr($tmp,5)));
				break;
				case 'konqu':
					$tmp = strstr($useragent,'Konqueror/');
					$bInfo['VERSION'] = doubleval(substr($tmp,10));
				break;
			}

				// Client system
			if (strstr($useragent,'Win'))	{
				$bInfo['SYSTEM'] = 'win';
			} elseif (strstr($useragent,'Mac'))	{
				$bInfo['SYSTEM'] = 'mac';
			} elseif (strstr($useragent,'Linux') || strstr($useragent,'X11') || strstr($useragent,'SGI') || strstr($useragent,' SunOS ') || strstr($useragent,' HP-UX '))	{
				$bInfo['SYSTEM'] = 'unix';
			}
		}

			// Is true if the browser supports css to format forms, especially the width
		$bInfo['FORMSTYLE']=($bInfo['BROWSER']=='msie' || ($bInfo['BROWSER']=='net'&&$bInfo['VERSION']>=5) || $bInfo['BROWSER']=='opera' || $bInfo['BROWSER']=='konqu');
		return $bInfo;
	}

	/***************************
	 *
	 * OTHER FUNCTIONS:	(from Classic RTE)
	 *
	 ***************************/
	/**
	 * @return	[type]		...
	 * @desc 
	 */

	function RTEtsConfigParams()	{
		if($this->is_FE()) {
			return '';
		} else {
			$p = t3lib_BEfunc::getSpecConfParametersFromArray($this->specConf['rte_transform']['parameters']);
			return $this->elementParts[0].':'.$this->elementParts[1].':'.$this->elementParts[2].':'.$this->thePid.':'.$this->typeVal.':'.$this->tscPID.':'.$p['imgpath'];
		}
	}

	function cleanList($str)        {
		if (strstr($str,'*'))   {
			$str = '*';
		} else {
			$str = implode(',',array_unique(t3lib_div::trimExplode(',',$str,1)));
		}
		return $str;
	}
	
	function filterStyleEl($elValue,$matchList)     {
		$matchParts = t3lib_div::trimExplode(',',$matchList,1);
		$styleParts = explode(';',$elValue);
		$nStyle=array();
		foreach ($styleParts as $k => $p) {
			$pp = t3lib_div::trimExplode(':',$p);
			if ($pp[0]&&$pp[1])     {
				foreach ($matchParts as $el) {
					$star=substr($el,-1)=='*';
					if (!strcmp($pp[0],$el) || ($star && t3lib_div::isFirstPartOfStr($pp[0],substr($el,0,-1)) ))    {
						$nStyle[]=$pp[0].':'.$pp[1];
					} else  unset($styleParts[$k]);
				}
			} else {
				unset($styleParts[$k]);
			}
		}
		return implode('; ',$nStyle);
	}
	
		// Hook on lorem_ipsum extension to insert text into the RTE in wysiwyg mode
	function loremIpsumInsert($params) {
		return "
				if (typeof(lorem_ipsum) == 'function' && " . $params['element'] . ".tagName.toLowerCase() == 'textarea' ) lorem_ipsum(" . $params['element'] . ", lipsum_temp_strings[lipsum_temp_pointer]);
				";
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/class.tx_rtehtmlarea_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/class.tx_rtehtmlarea_base.php']);
}

?>
