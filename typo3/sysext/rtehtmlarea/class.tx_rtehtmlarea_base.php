<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Kasper Skaarhoj (kasper@typo3.com)
*  (c) 2004 Philipp Borgmann <philipp.borgmann@gmx.de>
*  (c) 2004-2007 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
					'version' => 312
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
	
		// Hide these toolbar buttons not implemented in Safari
	var $conf_toolbar_safari_hide = array (
		'strikethrough',
		'line',
		'orderedlist',
		'unorderedlist',
		);
	
		// Hide these toolbar buttons not implemented in Opera
	var $conf_toolbar_opera_hide = array (
		'copy',
		'cut',
		'paste',
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
	var $defaultHotKeyList = 'selectall, bold, italic, underline, strikethrough, left, center, right, justifyfull, formatblock, paste, cleanword, undo, redo';

		// Conversion array: TYPO3 button names to htmlArea button names
	var $conf_toolbar_convert = array (
			// 'TYPO3 name' => 'htmlArea name'
		'fontstyle'		=> 'FontName',
		'fontsize'		=> 'FontSize',
		'textcolor'		=> 'ForeColor',
		'bgcolor'		=> 'HiliteColor',
		'bold'			=> 'Bold',
		'italic'		=> 'Italic',
		'underline'		=> 'Underline',
		'left'			=> 'JustifyLeft',
		'center'		=> 'JustifyCenter',
		'right'			=> 'JustifyRight',
		'orderedlist'		=> 'InsertOrderedList',
		'unorderedlist'		=> 'InsertUnorderedList',
		'outdent'		=> 'Outdent',
		'indent'		=> 'Indent',
		'emoticon'		=> 'InsertSmiley',
		'line'			=> 'InsertHorizontalRule',
		'link'			=> 'CreateLink',
		'table'			=> 'InsertTable',
		'image'			=> 'InsertImage',
		'cut'			=> 'Cut',
		'copy'			=> 'Copy',
		'paste'			=> 'Paste',
		'formatblock'		=> 'FormatBlock',
		'chMode'		=> 'HtmlMode',
		'user'			=> 'UserElements',
		
			// htmlArea extra buttons
		'lefttoright'		=> 'LeftToRight',
		'righttoleft'		=> 'RightToLeft',
		'justifyfull'		=> 'JustifyFull',
		'strikethrough'		=> 'StrikeThrough',
		'superscript'		=> 'Superscript',
		'subscript'		=> 'Subscript',
		'showhelp'		=> 'ShowHelp',
		'insertcharacter'	=> 'InsertCharacter',
		'findreplace'		=> 'FindReplace',
		'spellcheck'		=> 'SpellCheck',
		'removeformat'		=> 'RemoveFormat',
		'inserttag'		=> 'InsertTag',
		'acronym'		=> 'Acronym',
		'splitblock'		=> 'SplitBlock',
		'blockstylelabel'	=> 'I[style]',	
		'blockstyle'		=> 'DynamicCSS-class',
		'textstylelabel'	=> 'I[text_style]',
		'textstyle'		=> 'InlineCSS-class',
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
	
	var $defaultParagraphs = array(
		'p'		=> 'Normal',
		'h1'		=> 'Heading 1',
		'h2'		=> 'Heading 2',
		'h3'		=> 'Heading 3',
		'h4'		=> 'Heading 4',
		'h5'		=> 'Heading 5',
		'h6'		=> 'Heading 6',
		'pre'		=> 'Preformatted',
		'address'	=> 'Address',
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
		'1'	=>	'xx-small',
		'2'	=>	'x-small',
		'3'	=>	'small',
		'4'	=>	'medium',
		'5'	=>	'large',
		'6'	=>	'x-large',
		'7'	=>	'xx-large',
		);
	
	var $pluginList = 'TableOperations, ContextMenu, SpellChecker, SelectColor, TYPO3Browsers, InsertSmiley, FindReplace, RemoveFormat, CharacterMap, QuickTag, InlineCSS, DynamicCSS, UserElements, Acronym, TYPO3HtmlParser';
	
	var $pluginButton = array(
		'InlineCSS'		=> 'textstyle',
		'DynamicCSS'		=> 'blockstyle',
		'SpellChecker'		=> 'spellcheck',
		'InsertSmiley'		=> 'emoticon',
		'FindReplace'		=> 'findreplace',
		'RemoveFormat'		=> 'removeformat',
		'QuickTag'		=> 'inserttag',
		'CharacterMap'		=> 'insertcharacter',
		'TableOperations'	=> 'table, toggleborders, tableproperties, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit,
						columninsertbefore, columninsertafter, columndelete, columnsplit,
						cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge',
		'UserElements'		=> 'user',
		'Acronym'		=> 'acronym',
		);

	var $pluginLabel = array(
		'InlineCSS' 	=> 'textstylelabel',
		'DynamicCSS' 	=> 'blockstylelabel',
		);

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
	var $toolBar = array();			// Save the buttons for the toolbar
	var $toolbar_level_size;		// The size for each level in the toolbar:
	var $toolbarOrderArray = array();
	var $pluginEnableList;
	var $pluginEnableArray = array();

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
				reset($rteConfBrowser);
				while(list ($browser, $browserConf) = each($rteConfBrowser)){
					if ($browser == $this->client['BROWSER']) {
							// Config for Browser found, check it:
						if (is_array($browserConf)) {
							reset($browserConf);
							while(list ($browserConfNr, $browserConfSub) = each($browserConf)){
								if ($browserConfSub['version'] <= $this->client['VERSION'] || empty($browserConfSub['version'])) {
									// Version is correct
									if ($browserConfSub['system'] == $this->client['SYSTEM'] || empty($browserConfSub['system'])) {
											// System is correctly
										$rteIsAvailable = 1;
									}// End of System
								}// End of Version
							}// End of while-BrowserSubpart
						} else {
							// no config for this browser found, so all versions or system with this browsers are allow
							$rteIsAvailable = 1;
						}
					} // End of Browser Check
				} // while: Browser Check
			} else {
				// no Browser config for this RTE-Editor, so all Clients are allow			   
			}
			if (!$rteIsAvailable) {
				$this->errorLog[] = 'rte: Browser not supported. Only msie Version 5 or higher and Mozilla based client 1. and higher.';
			}
			if (t3lib_div::int_from_ver(TYPO3_version) < 3007000) {
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

	function drawRTE(&$pObj,$table,$field,$row,$PA,$specConf,$thisConfig,$RTEtypeVal,$RTErelPath,$thePidValue)	{
		global $BE_USER,$LANG, $TYPO3_DB, $TYPO3_CONF_VARS;

		$this->TCEform = $pObj;
		$inline =& $this->TCEform->inline;

		$LANG->includeLLFile('EXT:' . $this->ID . '/locallang.xml');
		$this->client = $this->clientInfo();
		$this->typoVersion = t3lib_div::int_from_ver(TYPO3_version);
		$this->userUid = 'BE_' . $BE_USER->user['uid'];
		
			// Draw form element:
		if ($this->debugMode)	{	// Draws regular text area (debug mode)
			$item = parent::drawRTE($pObj,$table,$field,$row,$PA,$specConf,$thisConfig,$RTEtypeVal,$RTErelPath,$thePidValue);
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
			$this->pluginEnableArray = array_intersect(t3lib_div::trimExplode(',', $this->pluginList , 1), t3lib_div::trimExplode(',', $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['HTMLAreaPluginList'], 1));
			$hidePlugins = array();
			if(!t3lib_extMgm::isLoaded('static_info_tables') || in_array($this->language, t3lib_div::trimExplode(',', $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['noSpellCheckLanguages']))) $hidePlugins[] = 'SpellChecker';
			if ($this->client['BROWSER'] == 'msie') $hidePlugins[] = 'Acronym';
			if ($this->client['BROWSER'] == 'opera') {
				$hidePlugins[] = 'ContextMenu';
				$this->thisConfig['hideTableOperationsInToolbar'] = 0;
				$this->thisConfig['disableEnterParagraphs'] = 1;
			}
			$this->pluginEnableArray = array_diff($this->pluginEnableArray, $hidePlugins);
			$this->pluginEnableArrayMultiple = $this->pluginEnableArray;

				// Toolbar
			$this->setToolBar();

				// Check if some plugins need to be disabled
			$this->setPlugins();

			/* =======================================
			 * PLUGIN-SPECIFIC CONFIGURATION
			 * =======================================
			 */
			
			if ($this->isPluginEnable('SpellChecker')) {
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
				$this->spellCheckerPersonalDicts = $this->thisConfig['enablePersonalDicts'] ? (isset($BE_USER->userTS['options.']['enablePersonalDicts']) ? true : false) : false;
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
			$RTEWidth  = $RTEWidth + ($pObj->docLarge ? (isset($BE_USER->userTS['options.']['RTELargeWidthIncrement']) ? $BE_USER->userTS['options.']['RTELargeWidthIncrement'] : '150') : 0);
			$RTEWidth -= ($inline->getStructureDepth() > 0 ? ($inline->getStructureDepth()+1)*$inline->getLevelMargin() : 0);
			$RTEHeight = $RTEHeight + ($pObj->docLarge ?  (isset($BE_USER->userTS['options.']['RTELargeHeightIncrement']) ? $BE_USER->userTS['options.']['RTELargeHeightIncrement'] : 0) : 0);
			$editorWrapWidth = $RTEWidth . 'px';
			$editorWrapHeight = $RTEHeight . 'px';
			$this->RTEdivStyle = 'position:relative; left:0px; top:0px; height:' . $RTEHeight . 'px; width:'.$RTEWidth.'px; border: 1px solid black; padding: 2px 0px 2px 2px;';
			$this->toolbar_level_size = $RTEWidth;

			/* =======================================
			 * LOAD CSS AND JAVASCRIPT
			 * =======================================
			 */

				// Preloading the pageStyle
			$filename = trim($this->thisConfig['contentCSS']) ? trim($this->thisConfig['contentCSS']) : 'EXT:' . $this->ID . '/htmlarea/plugins/DynamicCSS/dynamiccss.css';
			$pObj->additionalCode_pre['loadCSS'] = '
		<link rel="alternate stylesheet" type="text/css" href="' . $this->getFullFileName($filename) . '" />';

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
			$pObj->additionalCode_pre['loadCSS'] .= '
		<link rel="alternate stylesheet" type="text/css" href="' . $this->editedContentCSS . '" />';

			$pObj->additionalCode_pre['loadCSS'] .= '
		<link rel="stylesheet" type="text/css" href="' . $this->editorCSS . '" />';

				// Loading JavaScript files and code
			$pObj->additionalCode_pre['loadJSfiles'] = $this->loadJSfiles($pObj->RTEcounter);
			$pObj->additionalJS_pre['loadJScode'] = $this->loadJScode($pObj->RTEcounter);

			/* =======================================
			 * DRAW THE EDITOR
			 * =======================================
			 */

				// Transform value:
			$value = $this->transformContent('rte',$PA['itemFormElValue'],$table,$field,$row,$specConf,$thisConfig,$RTErelPath,$thePidValue);
			
				// Change some tags
			if ($this->client['BROWSER'] == 'gecko') {
					// change <strong> to <b>
				$value = preg_replace('/<(\/?)strong/i', "<$1b", $value);
					// change <em> to <i>
				$value = preg_replace('/<(\/?)em([^b>]*>)/i', "<$1i$2", $value);
			}
			if ($this->client['BROWSER'] == 'msie') {
					// change <abbr> to <acronym>
				$value = preg_replace('/<(\/?)abbr/i', "<$1acronym", $value);
			}

				// Register RTE windows
			$pObj->RTEwindows[] = $PA['itemFormElName'];

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
				$pObj->additionalJS_post[] = $this->setRTEsizeByJS('RTEarea'.$pObj->RTEcounter, $height, $width);
			}

				// Register RTE in JS:
			$pObj->additionalJS_post[] = $this->registerRTEinJS($pObj->RTEcounter, $table, $row['uid'], $field);

				// Set the save option for the RTE:
			$pObj->additionalJS_submit[] = $this->setSaveRTE($pObj->RTEcounter, $pObj->formName, htmlspecialchars($PA['itemFormElName']));

				// Draw the textarea
			$visibility = 'hidden';
			$item = $this->triggerField($PA['itemFormElName']).'
				<div id="pleasewait' . $pObj->RTEcounter . '" class="pleasewait" style="display: none;" >' . $LANG->getLL('Please wait') . '</div>
				<div id="editorWrap' . $pObj->RTEcounter . '" class="editorWrap" style="width:' . $editorWrapWidth . '; height:' . $editorWrapHeight . ';">
				<textarea id="RTEarea'.$pObj->RTEcounter.'" name="'.htmlspecialchars($PA['itemFormElName']).'" style="'.t3lib_div::deHSCentities(htmlspecialchars($this->RTEdivStyle)).'">'.t3lib_div::formatForTextarea($value).'</textarea>
				</div>' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableDebugMode'] ? '<div id="HTMLAreaLog"></div>' : '') . '
				';
		}

			// Return form item:
		return $item;
	}

	/**
	 * Set the toolbar config (only in this PHP-Object, not in JS):
	 *
	 */

	function setToolBar() {
		global $BE_USER;
		
		$this->defaultToolbarOrder = 'bar, blockstylelabel, blockstyle, space, textstylelabel, textstyle, linebreak,
			bar, fontstyle, space, fontsize, space, formatblock,
			bar, bold, italic, underline, strikethrough, subscript, superscript,
			bar, lefttoright, righttoleft, bar, left, center, right, justifyfull,
			bar, orderedlist, unorderedlist, outdent, indent, bar, textcolor, bgcolor, textindicator,
			bar, emoticon, insertcharacter, line, link, image, table,' . (($this->thisConfig['hideTableOperationsInToolbar'] && is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['toggleborders.']) && $this->thisConfig['buttons.']['toggleborders.']['keepInToolbar']) ? ' toggleborders,': '') . ' user, acronym, bar, findreplace, spellcheck,
			bar, chMode, inserttag, removeformat, bar, copy, cut, paste, bar, undo, redo, bar, showhelp, about, linebreak, 
			' . ($this->thisConfig['hideTableOperationsInToolbar'] ? '': 'bar, toggleborders,') . ' bar, tableproperties, bar, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit, bar,
			columninsertbefore, columninsertafter, columndelete, columnsplit, bar,
			cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge';
		
			// Special toolbar for Mozilla Wamcom on Mac OS 9
		if($this->client['BROWSER'] == 'gecko' && $this->client['VERSION'] == '1.3')  {
			$this->defaultToolbarOrder = $this->TCEform->docLarge ? 'bar, blockstylelabel, blockstyle, space, textstylelabel, textstyle, linebreak,
				bar, fontstyle, space, fontsize, space, formatblock, bar, bold, italic, underline, strikethrough,
				subscript, superscript, lefttoright, righttoleft, bar, left, center, right, justifyfull, linebreak,
				bar, orderedlist, unorderedlist, outdent, indent, bar, textcolor, bgcolor, textindicator, bar, emoticon,
				insertcharacter, line, link, image, table, user, acronym, bar, findreplace, spellcheck, bar, chMode, inserttag,
				removeformat, bar, copy, cut, paste, bar, undo, redo, bar, showhelp, about, linebreak,
				bar, toggleborders, bar, tableproperties, bar, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit, bar,
				columninsertbefore, columninsertafter, columndelete, columnsplit, bar,
				cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge'
				: 'bar, blockstylelabel, blockstyle, space, textstylelabel, textstyle, linebreak,
				bar, fontstyle, space, fontsize, space, formatblock, bar, bold, italic, underline, strikethrough,
				subscript, superscript, linebreak, bar, lefttoright, righttoleft, bar, left, center, right, justifyfull,
				orderedlist, unorderedlist, outdent, indent, bar, textcolor, bgcolor, textindicator, bar, emoticon,
				insertcharacter, line, link, image, table, user, acronym, linebreak, bar, findreplace, spellcheck, bar, chMode, inserttag,
				removeformat, bar, copy, cut, paste, bar, undo, redo, bar, showhelp, about, linebreak,
				bar, toggleborders, bar, tableproperties, bar, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit, bar,
				columninsertbefore, columninsertafter, columndelete, columnsplit, bar,
				cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge';
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
				reset($this->thisConfig['showButtons.']);
				while(list($button,$value) = each($this->thisConfig['showButtons.'])) {
					if ($value) $show[] = $button;
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
		reset($this->pluginButton);
		while(list($plugin, $buttonList) = each($this->pluginButton) ) {
			if(!$this->isPluginEnable($plugin)) {
				$buttonArray = t3lib_div::trimExplode(',',$buttonList,1);
				foreach($buttonArray as $button) {
					$hideButtons[] = $button;
				}
			}
		}

			// Hiding labels of disabled plugins
		reset($this->pluginLabel);
		while(list($plugin, $label) = each($this->pluginLabel) ) {
			if(!$this->isPluginEnable($plugin)) $hideButtons[] = $label;
		}

			// Hiding buttons not implemented in Safari
		if ($this->client['BROWSER'] == 'safari') {
			reset($this->conf_toolbar_safari_hide);
			while(list(, $button) = each($this->conf_toolbar_safari_hide) ) {
				$hideButtons[] = $button;
			}
		}
		
			// Hiding buttons not implemented in Opera
		if ($this->client['BROWSER'] == 'opera') {
			reset($this->conf_toolbar_opera_hide);
			while(list(, $button) = each($this->conf_toolbar_opera_hide) ) {
				$hideButtons[] = $button;
			}
		}

			// Hiding the buttons
		$show = array_diff($show, $this->conf_toolbar_hide, $hideButtons, t3lib_div::trimExplode(',',$this->thisConfig['hideButtons'],1));

			// Adding the always show buttons
		$show = array_unique(array_merge($show, $this->conf_toolbar_show));
		$toolbarOrder = array_unique(array_merge($toolbarOrder, $this->conf_toolbar_show));
		reset($this->conf_toolbar_show);
		while(list(,$button) = each($this->conf_toolbar_show)) {
			if(!in_array($button, $this->toolbarOrderArray)) $this->toolbarOrderArray[] = $button;
		}

			// Getting rid of the buttons for which we have no position
		$show = array_intersect($show, $toolbarOrder);
		$this->toolBar = $show;
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
		reset($this->pluginButton);
		while(list($plugin, $buttonList) = each($this->pluginButton) ) {
			$buttonArray = t3lib_div::trimExplode(',',$buttonList,1);
			if(!in_array($buttonArray[0],$this->toolBar)) {
				$hidePlugins[] = $plugin;
				foreach($buttonArray as $button) {
					$hideButtons[] = $button;
				}
			}
		}
		
		if($this->thisConfig['disableContextMenu'] || $this->thisConfig['disableRightClick']) $hidePlugins[] = 'ContextMenu';
		if($this->thisConfig['disableSelectColor']) $hidePlugins[] = 'SelectColor';
		if($this->thisConfig['disableTYPO3Browsers']) $hidePlugins[] = 'TYPO3Browsers';
		if(!$this->thisConfig['enableWordClean'] || !is_array($this->thisConfig['enableWordClean.'])) $hidePlugins[] = 'TYPO3HtmlParser';
		if(!t3lib_extMgm::isLoaded('static_info_tables') || in_array($this->language, t3lib_div::trimExplode(',', $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['noSpellCheckLanguages']))) $hidePlugins[] = 'SpellChecker';
		
		$this->pluginEnableArray = array_diff($this->pluginEnableArray, $hidePlugins);
		
			// Hiding labels of disabled plugins
		reset($this->pluginLabel);
		while(list($plugin, $label) = each($this->pluginLabel) ) {
			if(!$this->isPluginEnable($plugin)) $hideButtons[] = $label;
		}
		$this->toolBar = array_diff($this->toolBar, $hideButtons);

			// Renaming buttons of replacement plugins
		if( $this->isPluginEnable('SelectColor') ) {
			$this->conf_toolbar_convert['textcolor'] = 'CO-forecolor';
			$this->conf_toolbar_convert['bgcolor'] = 'CO-hilitecolor';
		}
	}

	/**
	 * Convert the TYPO3 names of buttons into the names for htmlArea RTE
	 * 
	 * @param	string	buttonname (typo3-name)
	 * @return	string	buttonname (htmlarea-name)
	 */

	 function convertToolBarForHTMLArea($button) {
 		return $this->conf_toolbar_convert[$button];
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
	 * Return the HTML-Code for loading the Javascript-Files
	 *
	 * @return string		the html-code for loading the Javascript-Files
	 */
	function loadJSfiles($number) {
		global $TYPO3_CONF_VARS;
		
		return '
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
			RTEarea[0]["popupwin"] = "' . $this->writeJSFileToTypo3tempDir('EXT:' . $this->ID . '/htmlarea/popupwin' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts']?'-compressed':'') .'.js', "popupwin", $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'])  . '";
			RTEarea[0]["htmlarea-gecko"] = "' . $this->writeJSFileToTypo3tempDir('EXT:' . $this->ID . '/htmlarea/htmlarea-gecko' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts']?'-compressed':'') .'.js', "htmlarea-gecko", $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'])  . '";
			RTEarea[0]["htmlarea-ie"] = "' . $this->writeJSFileToTypo3tempDir('EXT:' . $this->ID . '/htmlarea/htmlarea-ie' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts']?'-compressed':'') .'.js', "htmlarea-ie", $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'])  . '";
			var _editor_url = "' . $this->extHttpPath . 'htmlarea";
			var _editor_lang = "' . $this->language . '";
			var _editor_CSS = "' . $this->editorCSS . '";
			var _editor_skin = "' . dirname($this->editorCSS) . '";
			var _editor_edited_content_CSS = "' .  $this->editedContentCSS  . '";
			var _typo3_host_url = "' . $this->hostURL . '";
			var _editor_debug_mode = ' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableDebugMode'] ? 'true' : 'false') . ';
			var _editor_compressed_scripts = ' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] ? 'true' : 'false') . ';
			var _editor_mozAllowClipboard_url = "' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['mozAllowClipboardURL'] ? $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['mozAllowClipboardURL'] : '') . '";
			var _spellChecker_lang = "' . $this->spellCheckerLanguage . '";
			var _spellChecker_charset = "' . $this->spellCheckerCharset . '";
			var _spellChecker_mode = "' . $this->spellCheckerMode . '";
		/*]]>*/
		</script>
		<script type="text/javascript" src="' . $this->buildJSMainLangFile($number) . '"></script>
		<script type="text/javascript" src="' . $this->writeJSFileToTypo3tempDir('EXT:' . $this->ID . '/htmlarea/htmlarea' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts']?'-compressed':'') .'.js', "htmlarea", $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts']) . '"></script>
		';
	}
	
	/**
	 * Return the JS-Code to initialize the Editor
	 *
	 * @return string	the html-code for loading the Javascript-Files
	 */
	 
	function loadJScode($number) {
		global $TSFE, $TYPO3_CONF_VARS;
		
		$loadPluginCode = '';
		$pluginArray = t3lib_div::trimExplode(',', $this->pluginList , 1);
		while( list(,$plugin) = each($pluginArray) ) {
			if ($this->isPluginEnable($plugin) || (intval($number) > 1 && in_array($plugin, $this->pluginEnableArrayMultiple))) {
				$loadPluginCode .= '
			HTMLArea.loadPlugin("' . $plugin . '", true, "' . $this->writeJSFileToTypo3tempDir('EXT:' . $this->ID . '/htmlarea/plugins/' . $plugin . '/' . strtolower(preg_replace('/([a-z])([A-Z])([a-z])/', "$1".'-'."$2"."$3", $plugin)) . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts']?'-compressed':'') .'.js', $plugin, $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts']) . '");';
			}
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
	 * Return the JS-Code for Register the RTE in JS
	 * 
	 * @param	integer		$number: The index number of the RTE.
	 * @param	string		$table: The table that includes this RTE (optional, necessary for IRRE).
	 * @param	string		$uid: The uid of that table that includes this RTE (optional, necessary for IRRE).
	 * @param	string		$field: The field of that record that includes this RTE (optional).
	 *
	 * @return string		the JS-Code for Register the RTE in JS
	 */
	
	function registerRTEinJS($number, $table='', $uid='', $field='') {
		global $TSFE, $TYPO3_CONF_VARS;

		$registerRTEinJSString = (!$this->is_FE() ? '' : '
			' . '/*<![CDATA[*/') . '
			RTEarea['.$number.'] = new Object();
			RTEarea['.$number.']["RTEtsConfigParams"] = "&RTEtsConfigParams=' . rawurlencode($this->RTEtsConfigParams()) . '";
			RTEarea['.$number.']["number"] = '.$number.';
			RTEarea['.$number.']["id"] = "RTEarea'.$number.'";
			RTEarea['.$number.']["enableWordClean"] = ' . (trim($this->thisConfig['enableWordClean'])?'true':'false') . ';
			RTEarea['.$number.']["htmlRemoveComments"] = ' . (trim($this->thisConfig['removeComments'])?'true':'false') . ';
			RTEarea['.$number.']["disableEnterParagraphs"] = ' . (trim($this->thisConfig['disableEnterParagraphs'])?'true':'false') . ';
			RTEarea['.$number.']["removeTrailingBR"] = ' . (trim($this->thisConfig['removeTrailingBR'])?'true':'false') . ';
			RTEarea['.$number.']["useCSS"] = ' . (trim($this->thisConfig['useCSS'])?'true':'false') . ';
			RTEarea['.$number.']["keepButtonGroupTogether"] = ' . (trim($this->thisConfig['keepButtonGroupTogether'])?'true':'false') . ';
			RTEarea['.$number.']["disablePCexamples"] = ' . (trim($this->thisConfig['disablePCexamples'])?'true':'false') . ';
			RTEarea['.$number.']["statusBar"] = ' . (trim($this->thisConfig['showStatusBar'])?'true':'false') . ';
			RTEarea['.$number.']["showTagFreeClasses"] = ' . (trim($this->thisConfig['showTagFreeClasses'])?'true':'false') . ';
			RTEarea['.$number.']["useHTTPS"] = ' . ((trim(stristr($this->siteURL, 'https')) || $this->thisConfig['forceHTTPS'])?'true':'false') . ';
			RTEarea['.$number.']["enableMozillaExtension"] = ' . (($this->client['BROWSER'] == 'gecko' && $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableMozillaExtension'])?'true':'false') . ';
			RTEarea['.$number.']["tceformsNested"] = ' . (is_object($this->TCEform) && method_exists($this->TCEform, 'getDynNestedStack') ? $this->TCEform->getDynNestedStack(true) : '[]') . ';';

			// The following properties apply only to the backend
		if (!$this->is_FE()) {
			$registerRTEinJSString .= '
			RTEarea['.$number.']["sys_language_content"] = "' . $this->contentLanguageUid . '";
			RTEarea['.$number.']["typo3ContentLanguage"] = "' . $this->contentTypo3Language . '";
			RTEarea['.$number.']["typo3ContentCharset"] = "' . $this->contentCharset . '";
			RTEarea['.$number.']["enablePersonalDicts"] = ' . ($this->spellCheckerPersonalDicts ? 'true' : 'false') . ';
			RTEarea['.$number.']["userUid"] = "' . $this->userUid . '";';
		}
			// Setting the plugin flags
		$registerRTEinJSString .= '
			RTEarea['.$number.']["plugin"] = new Object();';

		$pluginArray = t3lib_div::trimExplode(',', $this->pluginList , 1);
		reset($pluginArray);
		while( list(,$plugin) = each($pluginArray) ) {
			if ($this->isPluginEnable($plugin)) {
				$registerRTEinJSString .= '
			RTEarea['.$number.']["plugin"]["'.$plugin.'"] = true;';
			}
		}
		
			// Setting the buttons configuration
		$registerRTEinJSString .= '
			RTEarea['.$number.']["buttons"] = new Object();';
		if (is_array($this->thisConfig['buttons.'])) {
			reset($this->thisConfig['buttons.']);
			while( list($buttonIndex,$conf) = each($this->thisConfig['buttons.']) ) {
				$button = substr($buttonIndex, 0, -1);
				if (in_array($button,$this->toolBar)) {
					$indexButton = 0;
					$registerRTEinJSString .= '
			RTEarea['.$number.']["buttons"]["'.$button.'"] = {';
					if (is_array($conf)) {
						reset($conf);
						while (list($propertyName,$conf1) = each($conf)) {
							$property = $propertyName;
							if ($indexButton) {
								$registerRTEinJSString .= ', ';
							}
							if (is_array($conf1)) {
								$property = substr($property, 0, -1);
								$indexProperty = 0;
								$registerRTEinJSString .= '"'.$property.'" : {';
								reset($conf1);
								while (list($property1Name,$conf2) = each($conf1)) {
									$property1 = $property1Name;
									if ($indexProperty) {
										$registerRTEinJSString .= ', ';
									}
									if (is_array($conf2)) {
										$property1 = substr($property1, 0, -1);
										$indexProperty1 = 0;
										$registerRTEinJSString .= '"'.$property1.'" : {';
										reset($conf2);
										while (list($property2Name,$conf3) = each($conf2)) {
											$property2 = $property2Name;
											if ($indexProperty1) {
												$registerRTEinJSString .= ', ';
											}
											if (is_array($conf3)) {
												$property2 = substr($property2, 0, -1);
												$indexProperty2 = 0;
												$registerRTEinJSString .= '"'.$property2.'" : {';
												reset($conf3);
												while (list($property3Name,$conf4) = each($conf3)) {
													$property3 = $property3Name;
													if ($indexProperty2) {
														$registerRTEinJSString .= ', ';
													}
													if (!is_array($conf4)) {
														$registerRTEinJSString .= '"'.$property3.'" : '.($conf4?'"'.$conf4.'"':'false');
													}
													$indexProperty2++;
												}
												$registerRTEinJSString .= '}';
											} else {
												$registerRTEinJSString .= '"'.$property2.'" : '.($conf3?'"'.$conf3.'"':'false');												
											}
											$indexProperty1++;
										}
										$registerRTEinJSString .= '}';
									} else {
										$registerRTEinJSString .= '"'.$property1.'" : '.($conf2?'"'.$conf2.'"':'false');
									}
									$indexProperty++;
								}
								$registerRTEinJSString .= '}';
							} else {
								$registerRTEinJSString .= '"'.$property.'" : '.($conf1?'"'.$conf1.'"':'false');
							}
							$indexButton++;
						}
					}
					$registerRTEinJSString .= '};';
				}
			}
		}
		
			// Deprecated inserttag button configuration
		if (in_array('inserttag', $this->toolBar) && trim($this->thisConfig['hideTags'])) {
			if (!is_array($this->thisConfig['buttons.']['inserttag.'])) {
				$registerRTEinJSString .= '
			RTEarea['.$number.']["buttons"]["inserttag"] = new Object();
			RTEarea['.$number.']["buttons"]["inserttag"]["denyTags"] = "'.implode(',', t3lib_div::trimExplode(',', $this->thisConfig['hideTags'], 1)).'";';
			} elseif (!$this->thisConfig['buttons.']['inserttag.']['denyTags']) {
				$registerRTEinJSString .= '
			RTEarea['.$number.']["buttons"]["inserttag"]["denyTags"] = "'.implode(',', t3lib_div::trimExplode(',', $this->thisConfig['hideTags'], 1)).'";';
			}
		}
		
			// Setting the list of tags to be removed if specified in the RTE config
		if (trim($this->thisConfig['removeTags']))  {
			$registerRTEinJSString .= '
			RTEarea['.$number.']["htmlRemoveTags"] = /^(' . implode('|', t3lib_div::trimExplode(',', $this->thisConfig['removeTags'], 1)) . ')$/i;';
		}
		
			// Setting the list of tags to be removed with their contents if specified in the RTE config
		if (trim($this->thisConfig['removeTagsAndContents']))  {
			$registerRTEinJSString .= '
			RTEarea['.$number.']["htmlRemoveTagsAndContents"] = /^(' . implode('|', t3lib_div::trimExplode(',', $this->thisConfig['removeTagsAndContents'], 1)) . ')$/i;';
		}
		
			// Process default style configuration
		$registerRTEinJSString .= '
			RTEarea['.$number.']["defaultPageStyle"] = "' . $this->hostURL . $this->buildJSFile('css', $this->buildStyleSheet(), 'css') . '";';
			
			// Setting the pageStyle
		$filename = trim($this->thisConfig['contentCSS']) ? trim($this->thisConfig['contentCSS']) : 'EXT:' . $this->ID . '/htmlarea/plugins/DynamicCSS/dynamiccss.css';
		$registerRTEinJSString .= '
			RTEarea['.$number.']["pageStyle"] = "' . $this->getFullFileName($filename) .'";';
		
			// Process colors configuration
		if ( $this->isPluginEnable('SelectColor') ) {
			$registerRTEinJSString .= $this->buildJSColorsConfig($number);
		}
		
			// Process classes configuration
		if ($this->isPluginEnable('InlineCSS') || $this->isPluginEnable('DynamicCSS')) {
			$registerRTEinJSString .= $this->buildJSClassesConfig($number);
		}
		
			// Process font faces configuration
		$registerRTEinJSString .= $this->buildJSFontFacesConfig($number);
		
			// Process paragraphs configuration
		$registerRTEinJSString .= $this->buildJSParagraphsConfig($number);
		
			// Process font sizes configuration
		$registerRTEinJSString .= $this->buildJSFontSizesConfig($number);
		
		if ($this->isPluginEnable('TableOperations')) {
			$registerRTEinJSString .= '
			RTEarea['.$number.']["hideTableOperationsInToolbar"] = ' . (trim($this->thisConfig['hideTableOperationsInToolbar']) ? 'true' : 'false') . ';
			RTEarea['.$number.']["disableLayoutFieldsetInTableOperations"] = ' . (trim($this->thisConfig['disableLayoutFieldsetInTableOperations'])?'true':'false') . ';
			RTEarea['.$number.']["disableAlignmentFieldsetInTableOperations"] = ' . (trim($this->thisConfig['disableAlignmentFieldsetInTableOperations'])?'true':'false') . ';
			RTEarea['.$number.']["disableSpacingFieldsetInTableOperations"] = ' . (trim($this->thisConfig['disableSpacingFieldsetInTableOperations'])?'true':'false') . ';
			RTEarea['.$number.']["disableBordersFieldsetInTableOperations"] = ' . (trim($this->thisConfig['disableBordersFieldsetInTableOperations'])?'true':'false') . ';
			RTEarea['.$number.']["disableColorFieldsetInTableOperations"] = ' . (trim($this->thisConfig['disableColorFieldsetInTableOperations'])?'true':'false') . ';';
				// // Deprecated toggleborders button configuration
			if (in_array('toggleborders',$this->toolBar) && $this->thisConfig['keepToggleBordersInToolbar']) {
				if (!is_array($this->thisConfig['buttons.']['toggleborders.'])) {
					$registerRTEinJSString .= '
			RTEarea['.$number.']["buttons"]["toggleborders"] = new Object();
			RTEarea['.$number.']["buttons"]["toggleborders"]["keepInToolbar"] = true;';
				} elseif (!$this->thisConfig['buttons.']['toggleborders.']['keepInToolbar']) {
					$registerRTEinJSString .= '
			RTEarea['.$number.']["buttons"]["toggleborders"]["keepInToolbar"] = true;';
				}
			}
		}
		
		if ($this->isPluginEnable('Acronym')) {
			$registerRTEinJSString .= '
			RTEarea['.$number.']["acronymUrl"] = "' . $this->buildJSFile('acronym_'.$this->contentLanguageUid, $this->buildJSAcronymArray()) . '";';
		}
		
		if ($this->isPluginEnable('TYPO3Browsers')) {
			$registerRTEinJSString .= $this->buildJSClassesAnchorConfig($number);
		}
		
		$registerRTEinJSString .= '
			RTEarea['.$number.']["toolbar"] = '.$this->getJSToolbarArray().';
			HTMLArea.initEditor('.$number.');' . (!$this->is_FE() ? '' : '
			/*]]>*/');
		return $registerRTEinJSString;
	}

	/**
	 * Return ture, if the plugin can loaded
	 *
	 * @return boolean		1 if the plugin can be loaded
	 */

	function isPluginEnable($plugin) { 
		return in_array($plugin, $this->pluginEnableArray);
	}
	
	
	/**
	 * Return JS configuration of font sizes
	 *
	 * @return string		JS font sizes configuration
	 */
	function buildJSFontSizesConfig($number) {
		global $LANG, $TSFE;
		$registerRTEinJSString = '';
		
			// Builing JS array of default font sizes
		$HTMLAreaFontSizes = array();
		if ($this->is_FE()) {
			$HTMLAreaFontSizes[0] = $TSFE->csConvObj->conv($TSFE->getLLL('No size',$this->LOCAL_LANG), $TSFE->labelsCharset, $TSFE->renderCharset);
		} else {
			$HTMLAreaFontSizes[0] = $LANG->getLL('No size');
		}

		reset($this->defaultFontSizes);
		while( list($FontSizeItem,$FontSizeLabel) = each($this->defaultFontSizes)) {
			if ($this->client['BROWSER'] == 'safari') {
				$HTMLAreaFontSizes[$this->defaultFontSizes_safari[$FontSizeItem]] = $FontSizeLabel;
			} else {
				$HTMLAreaFontSizes[$FontSizeItem] = $FontSizeLabel;
			}
		}
		if ($this->thisConfig['hideFontSizes'] ) {
			$hideFontSizes =  t3lib_div::trimExplode(',', $this->cleanList($this->thisConfig['hideFontSizes']), 1);
			foreach($hideFontSizes as $item)  {
				if ($HTMLAreaFontSizes[strtolower($item)]) {
					if ($this->client['BROWSER'] == 'safari') {
						unset($HTMLAreaFontSizes[$this->defaultFontSizes_safari[strtolower($item)]]);
					} else {
						unset($HTMLAreaFontSizes[strtolower($item)]);
					}
				} else {
					
				}
			}
		}
		
		$HTMLAreaJSFontSize = '{';
		if ($this->cleanList($this->thisConfig['hideFontSizes']) != '*') {
			reset($HTMLAreaFontSizes);
			$HTMLAreaParagraphIndex = 0;
			while( list($FontSizeItem,$FontSizeLabel) = each($HTMLAreaFontSizes)) {
				if($HTMLAreaFontSizeIndex) { 
					$HTMLAreaJSFontSize .= ',';
				}
				$HTMLAreaJSFontSize .= '
				"' . $FontSizeLabel . '" : "' . ($FontSizeItem?$FontSizeItem:'') . '"';
				$HTMLAreaFontSizeIndex++;
			}
		}
		$HTMLAreaJSFontSize .= '};';
		$registerRTEinJSString .= '
			RTEarea['.$number.']["fontsize"] = '. $HTMLAreaJSFontSize;
			
		return $registerRTEinJSString;
	}
	/**
	 * Return JS configuration of paragraphs
	 *
	 * @return string		JS paragraphs configuration
	 */
	function buildJSParagraphsConfig($number) {
		global $TSFE, $LANG;
		$registerRTEinJSString = '';
		
			// Paragraphs
		$HTMLAreaParagraphs = $this->defaultParagraphs;
		if ($this->thisConfig['hidePStyleItems']) {
			$hidePStyleItems =  t3lib_div::trimExplode(',', $this->cleanList($this->thisConfig['hidePStyleItems']), 1);
			foreach($hidePStyleItems as $item)  unset($HTMLAreaParagraphs[strtolower($item)]);
		}
		$HTMLAreaJSParagraph = '{';
		if ($this->cleanList($this->thisConfig['hidePStyleItems']) != '*') {
			reset($HTMLAreaParagraphs);
			$HTMLAreaParagraphIndex = 0;
			while( list($PStyleItem,$PStyleLabel) = each($HTMLAreaParagraphs)) {
				if($HTMLAreaParagraphIndex) { 
					$HTMLAreaJSParagraph .= ',';
				}
				if ($this->is_FE()) {
					$HTMLAreaJSParagraph .= '
				"' . $TSFE->csConvObj->conv($TSFE->getLLL($PStyleLabel,$this->LOCAL_LANG), $TSFE->labelsCharset, $TSFE->renderCharset) . '" : "' . $PStyleItem . '"';

				} else {
					$HTMLAreaJSParagraph .= '
				"' . $LANG->getLL($PStyleLabel) . '" : "' . $PStyleItem . '"';
				}
				$HTMLAreaParagraphIndex++;
			}
		}
		$HTMLAreaJSParagraph .= '};';
		$registerRTEinJSString .= '
			RTEarea['.$number.']["paragraphs"] = '. $HTMLAreaJSParagraph;
			
		return $registerRTEinJSString;
	}
	
	/**
	 * Return JS configuration of font faces
	 *
	 * @return string		JS font faces configuration
	 */
	function buildJSFontfacesConfig($number) {
		global $TSFE, $LANG;
		
		if ($this->is_FE()) {
			$RTEProperties = $this->RTEsetup;
		} else {
			$RTEProperties = $this->RTEsetup['properties'];
		}
		
		$registerRTEinJSString = '';
		
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
			reset($this->defaultFontFaces);
			while (list($fontName,$fontValue) = each($this->defaultFontFaces)) {
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
			reset($RTEProperties['fonts.']);
			while(list($fontName,$conf)=each($RTEProperties['fonts.'])) {
				$fontName=substr($fontName,0,-1);
				if ($this->is_FE()) {
					$string = $TSFE->sL($conf['name']);
				} else {
					$string = $LANG->sL($conf['name']);
				}
				$HTMLAreaFontname[$fontName] = '
				"' . str_replace('"', '\"', str_replace('\\\'', '\'', $string)) . '" : "' . $this->cleanList($conf['value']) . '"';
			}
		}
		
			// Setting the list of font faces
		$HTMLAreaJSFontface = '{';
		$HTMLAreaFontface = t3lib_div::trimExplode(',' , $this->cleanList($defaultFontFacesList . ',' . $this->thisConfig['fontFace']));
		reset($HTMLAreaFontface);
		$HTMLAreaFontfaceIndex = 0;
		while( list(,$fontName) = each($HTMLAreaFontface)) {
			if($HTMLAreaFontfaceIndex) { 
				$HTMLAreaJSFontface .= ',';
			}
			$HTMLAreaJSFontface .= $HTMLAreaFontname[$fontName];
			$HTMLAreaFontfaceIndex++;
		}
		$HTMLAreaJSFontface .= '};';
		
		$registerRTEinJSString .= '
			RTEarea['.$number.']["fontname"] = '. $HTMLAreaJSFontface;
		
		return $registerRTEinJSString;
	}
	
	/**
	 * Return JS configuration of colors
	 *
	 * @return string		JS colors configuration
	 */
	function buildJSColorsConfig($number) {
		global $TSFE, $LANG;
		
		if ($this->is_FE()) {
			$RTEProperties = $this->RTEsetup;
		} else {
			$RTEProperties = $this->RTEsetup['properties'];
		}
		
		$registerRTEinJSString = '';
		
		if(trim($this->thisConfig['disableColorPicker'])) {
			$registerRTEinJSString .= '
			RTEarea['.$number.']["disableColorPicker"] = true;';
		} else {
			$registerRTEinJSString .= '
			RTEarea['.$number.']["disableColorPicker"] = false;';
		}
		
			// Building JS array of configured colors
		if (is_array($RTEProperties['colors.']) )  {
			$HTMLAreaColorname = array();
			reset($RTEProperties['colors.']);
			while(list($colorName,$conf)=each($RTEProperties['colors.']))      {
				$colorName=substr($colorName,0,-1);
				if ($this->is_FE()) {
					$string = $TSFE->csConvObj->conv($TSFE->sL(trim($conf['name'])), $TSFE->renderCharset, $TSFE->metaCharset);
					$string = str_replace('"', '\"', str_replace('\\\'', '\'', $string));
					$string = $this->feJScharCode($string);
				} else {
					$string = $this->getLLContent(trim($conf['name']));
				}
				$HTMLAreaColorname[$colorName] = '
				[' . $string . ' , "' . $conf['value'] . '"]';
			}
		}
		
			// Setting the list of colors if specified in the RTE config
		if ($this->thisConfig['colors'] ) {
			$HTMLAreaJSColors = '[';
			$HTMLAreaColors = t3lib_div::trimExplode(',' , $this->cleanList($this->thisConfig['colors']));
			reset($HTMLAreaColors);
			$HTMLAreaColorsIndex = 0;
			while( list(,$colorName) = each($HTMLAreaColors)) {
				if($HTMLAreaColorsIndex && $HTMLAreaColorname[$colorName]) { 
					$HTMLAreaJSColors .= ',';
				}
				$HTMLAreaJSColors .= $HTMLAreaColorname[$colorName];
				$HTMLAreaColorsIndex++;
			}
			$HTMLAreaJSColors .= '];';
			$registerRTEinJSString .= '
			RTEarea['.$number.']["colors"] = '. $HTMLAreaJSColors;
		}
		
		return $registerRTEinJSString;
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
			reset($elList);
			while(list(,$elListName)=each($elList)) {
				if ($this->thisConfig['mainStyleOverride_add.'][$elListName]) {
					$mainElements[$elListName] = $this->thisConfig['mainStyleOverride_add.'][$elListName];
				}
			}
			
			$addElementCode = '';
			reset($mainElements);
			while(list($elListName,$elValue)=each($mainElements))   {
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
	 * Return JS configuration of classes
	 *
	 * @return string		JS classes configuration
	 */
	function buildJSClassesConfig($number) {
		
			// Build JS array of lists of classes
		$classesTagList = 'classesCharacter, classesParagraph, classesImage, classesTable, classesLinks, classesTD';
		$classesTagConvert = array( 'classesCharacter' => 'span', 'classesParagraph' => 'p', 'classesImage' => 'img', 'classesTable' => 'table', 'classesLinks' => 'a', 'classesTD' => 'td');
		$classesTagArray = t3lib_div::trimExplode(',' , $classesTagList);
		$registerRTEinJSString = '
			RTEarea['.$number.']["classesTag"] = new Object();';
		while( list(,$classesTagName) = each($classesTagArray)) {
			$HTMLAreaJSClasses = ($this->thisConfig[$classesTagName])?('"' . $this->cleanList($this->thisConfig[$classesTagName]) . '";'):'null;';
			$registerRTEinJSString .= '
			RTEarea['.$number.']["classesTag"]["'. $classesTagConvert[$classesTagName] .'"] = '. $HTMLAreaJSClasses;
		}
		
			// Include JS arrays of configured classes
		$registerRTEinJSString .= '
			RTEarea['.$number.']["classesUrl"] = "' . $this->hostURL . $this->buildJSFile('classes_'.$this->contentLanguageUid, $this->buildJSClassesArray()) . '";';
		
		return $registerRTEinJSString;
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
		
			// Scanning the list of classes if specified in the RTE config
		if (is_array($RTEProperties['classes.']))  {
			$stylesheet = '';
			reset($RTEProperties['classes.']);
			while(list($className,$conf)=each($RTEProperties['classes.'])) {
				$className = substr($className,0,-1);
				if ($this->is_FE()) {
					$string = $TSFE->csConvObj->conv($TSFE->sL(trim($conf['name'])), $TSFE->renderCharset, $TSFE->metaCharset);
					$string = str_replace('"', '\"', str_replace('\\\'', '\'', $string));
					$string = $this->feJScharCode($string);
				} else {
					$string = $this->getLLContent(trim($conf['name']));
				}
				$JSClassesLabelsArray .= (($index)?',':'') . '"' . $className . '": ' . $string . $linebreak;
				$JSClassesValuesArray .= (($index)?',':'') . '"' . $className . '":"' . str_replace('"', '\"', str_replace('\\\'', '\'', $conf['value'])) . '"' . $linebreak;
				$JSClassesNoShowArray .= (($index)?',':'') . '"' . $className . '":' . ($conf['noShow']?'true':'false') . $linebreak;
				$index++;
			}
		}
		$JSClassesLabelsArray .= '};' . $linebreak;
		$JSClassesValuesArray .= '};' . $linebreak;
		$JSClassesNoShowArray .= '};' . $linebreak;
		
		return $JSClassesLabelsArray . $JSClassesValuesArray . $JSClassesNoShowArray;
	}
	
	/**
	 * Return a JS language array for htmlArea RTE
	 *
	 * @return string		JS language array
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
	 * @return string		acronym array
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
	 * Return JS configuration of special anchor classes
	 *
	 * @return string		JS special anchor classes configuration
	 */
	function buildJSClassesAnchorConfig($number) {
		global $TSFE;
		
		if ($this->is_FE()) {
			$RTEProperties = $this->RTEsetup;
		} else {
			$RTEProperties = $this->RTEsetup['properties'];
		}
		
		$registerRTEinJSString = '';
		if (is_array($RTEProperties['classesAnchor.'])) {
			$registerRTEinJSString .= '
			RTEarea['.$number.']["classesAnchorUrl"] = "' . $this->buildJSFile('classesAnchor_'.$this->contentLanguageUid, $this->buildJSClassesAnchorArray()) . '";';
		}
		return $registerRTEinJSString;
	}
	
	/**
	 * Return a JS array for special anchor classes
	 *
	 * @return string		classesAnchor array definition
	 */
	function buildJSClassesAnchorArray() {
		global $LANG, $TYPO3_CONF_VARS;
		
		$linebreak = $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] ? '' : chr(10);
		$JSClassesAnchorArray .= 'editor.classesAnchorSetup = [ ' . $linebreak;
		$classesAnchorIndex = 0;
		reset($this->RTEsetup['properties']['classesAnchor.']);
		while(list($label,$conf)=each($this->RTEsetup['properties']['classesAnchor.'])) {
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
	 * Return a file name built with the label and containing the specified contents
	 *
	 * @return string		filename
	 */
	 
	function buildJSFile($label,$contents,$ext='js') {
		$relFilename = 'typo3temp/' . $this->ID . '_' . $label . '_' . md5($contents) . '.' . $ext;
		$outputFilename = PATH_site . $relFilename;
		if(!file_exists($outputFilename)) {
			$outputHandle = fopen($outputFilename,'wb');
			fwrite($outputHandle, $contents);
			fclose($outputHandle);
			t3lib_div::fixPermissions($outputFilename);
		}
		return $this->httpTypo3Path . $relFilename;
	}
	
	/**
	 * Return a file name built with the label and containing a cached copy of the specified file
	 *
	 * @return string		filename
	 */
	 
	function writeJSFileToTypo3tempDir($JSFile,$label,$compressed=FALSE,$ext='js') {
		global $TYPO3_CONF_VARS;
		
		$source = t3lib_div::getFileAbsFileName($JSFile);
		$relFilename = 'typo3temp/' . $this->ID . '_' . $label . '_' . md5($JSFile . $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['version']) . ($compressed ? '-compressed' : '') . '.' . $ext;
		$destination = PATH_site . $relFilename;
		if(!file_exists($destination)) {
			@copy($source,$destination);
			t3lib_div::fixPermissions($destination);
		}
		return ($this->thisConfig['forceHTTPS']?$this->siteURL:$this->httpTypo3Path) . $relFilename;
	}
	
	/**
	 * Return a file name containing the main JS language array for HTMLArea
	 *
	 * @return string		filename
	 */
	 
	function buildJSMainLangFile($number) { 
		$contents = $this->buildJSMainLangArray() . chr(10);
		$pluginArray = t3lib_div::trimExplode(',', $this->pluginList , 1);
		while( list(,$plugin) = each($pluginArray) ) {
			if ($this->isPluginEnable($plugin)  || (intval($number) > 1 && in_array($plugin, $this->pluginEnableArrayMultiple))) {
				$contents .= $this->buildJSLangArray($plugin) . chr(10);
			}
		}
		return $this->buildJSFile($this->language.'_'.$this->OutputCharset,$contents);
	}

	/**
	 * Return a JS language array for the plugin
	 *
	 * @return string		JS language array
	 */
	 
	function buildJSLangArray($plugin) {
		global $TSFE, $LANG, $TYPO3_CONF_VARS;
		
		$linebreak = $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] ? '' : chr(10);
		if($this->is_FE()) {
			$LOCAL_LANG = $TSFE->readLLfile(t3lib_extMgm::extPath($this->ID).'htmlarea/plugins/' . $plugin . '/locallang.xml', $this->language);
			if(!empty($LOCAL_LANG['default'])) $TSFE->csConvObj->convArray($LOCAL_LANG['default'], 'iso-8859-1', $this->OutputCharset);
			if(!empty($LOCAL_LANG[$this->language])) $TSFE->csConvObj->convArray($LOCAL_LANG[$this->language], $this->charset, $this->OutputCharset);
		} else {
			$LOCAL_LANG = $LANG->readLLfile(t3lib_extMgm::extPath($this->ID).'htmlarea/plugins/' . $plugin . '/locallang.xml');
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
		reset($this->toolbarOrderArray);
		while (list(, $button) = each($this->toolbarOrderArray) ) {
			// check if a new group starts
			if (($button == 'bar' || $button == 'linebreak') && $group_has_button) {
					// New line
				if ($button == 'linebreak') {
					$convertButton = '"' . $this->convertToolBarForHTMLArea('linebreak') . '"';
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
				$toolbar .= ', "' . $this->convertToolBarForHTMLArea($button) . '"';
			} elseif ($button == 'bar' && !$group_has_button) {
				$group_needs_starting_bar = true;
			} elseif ($button == 'space' && $group_has_button && !$previous_is_space) {
				$convertButton = $this->convertToolBarForHTMLArea($button);
				$convertButton = '"' . $convertButton . '"';
				$group .= $group ? (', ' . $convertButton) : ($group_needs_starting_bar ? ('"' . $this->convertToolBarForHTMLArea('bar') . '", ' . $convertButton) : $convertButton);
				$group_needs_starting_bar = false;
				$previous_is_space = true;
			} elseif (in_array($button, $this->toolBar)) {
					// Add the button to the group
				$convertButton = $this->convertToolBarForHTMLArea($button);
				if ($convertButton) {
					$convertButton = '"' . $convertButton . '"';
					$group .= $group ? (', ' . $convertButton) : ($group_needs_starting_bar ? ('"' . $this->convertToolBarForHTMLArea('bar') . '", ' . $convertButton) : $convertButton);
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
	 * Return the JS-Code to copy the HTML-Code from the editor in the hidden input field.
	 * This is for submit function from the form.
	 *
	 * @return string		the JS-Code
	 */

	function setSaveRTE($number, $form, $textarea) {
		return '
		editornumber = '.$number.';
		if (RTEarea[editornumber]) {
			document.'.$form.'["'.$textarea.'"].value = RTEarea[editornumber]["editor"].getHTML();
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
		} elseif (strstr($useragent,'Safari/') &&  $TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['safari_test'] == 1) {
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
		global $TSFE;
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
		while(list($k,$p)=each($styleParts))    {
			$pp = t3lib_div::trimExplode(':',$p);
			if ($pp[0]&&$pp[1])     {
				reset($matchParts);
				while(list(,$el)=each($matchParts))     {
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
