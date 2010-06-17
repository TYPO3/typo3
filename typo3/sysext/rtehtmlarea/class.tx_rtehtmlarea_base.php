<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2009 Kasper Skaarhoj (kasper@typo3.com)
*  (c) 2004-2009 Philipp Borgmann <philipp.borgmann@gmx.de>
*  (c) 2004-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * $Id$  *
 */

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
					'version' => 9.62
				)
			)
		);

		// Always hide these toolbar buttons (TYPO3 button name)
	var $conf_toolbar_hide = array (
		'showhelp',		// Has no content yet
		);

		// The order of the toolbar: the name is the TYPO3-button name
	var $defaultToolbarOrder;

		// Conversion array: TYPO3 button names to htmlArea button names
	var $convertToolbarForHtmlAreaArray = array (
		'line'			=> 'InsertHorizontalRule',
		'showhelp'		=> 'ShowHelp',
		'textindicator'		=> 'TextIndicator',
		'space'			=> 'space',
		'bar'			=> 'separator',
		'linebreak'		=> 'linebreak',
		);

	var $pluginButton = array();
	var $pluginLabel = array();

		// External:
	var $RTEdivStyle;			// Alternative style for RTE <div> tag.
	public $httpTypo3Path;
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
	public $language;
	public $contentTypo3Language;
	public $contentISOLanguage;
	public $contentCharset;
	public $OutputCharset;
	var $editorCSS;
	var $specConf;
	var $toolbar = array();					// Save the buttons for the toolbar
	var $toolbar_level_size;				// The size for each level in the toolbar:
	var $toolbarOrderArray = array();
	protected $pluginEnabledArray = array();		// Array of plugin id's enabled in the current RTE editing area
	protected $pluginEnabledCumulativeArray = array();	// Cumulative array of plugin id's enabled so far in any of the RTE editing areas of the form
	public $registeredPlugins = array();			// Array of registered plugins indexed by their plugin Id's

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

		$this->TCEform = $parentObject;
		$inline = $this->TCEform->inline;
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
			$this->elementParts = explode('][',preg_replace('/\]$/','',preg_replace('/^(TSFE_EDIT\[data\]\[|data\[)/','',$this->elementId)));

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
			$this->contentISOLanguage = 'en';
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
					$this->contentISOLanguage = trim($this->thisConfig['defaultContentLanguage']) ? trim($this->thisConfig['defaultContentLanguage']) : 'en';
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
			$this->charset = $LANG->charSet;
			$this->OutputCharset = $this->charset;

			$this->contentCharset = $LANG->csConvObj->charSetArray[$this->contentTypo3Language];
			$this->contentCharset = $this->contentCharset ? $this->contentCharset : 'iso-8859-1';
			$this->origContentCharSet = $this->contentCharset;
			$this->contentCharset = (trim($TYPO3_CONF_VARS['BE']['forceCharset']) ? trim($TYPO3_CONF_VARS['BE']['forceCharset']) : $this->contentCharset);

			/* =======================================
			 * TOOLBAR CONFIGURATION
			 * =======================================
			 */
			$this->initializeToolbarConfiguration();

			/* =======================================
			 * SET STYLES
			 * =======================================
			 */

			$RTEWidth = isset($BE_USER->userTS['options.']['RTESmallWidth']) ? $BE_USER->userTS['options.']['RTESmallWidth'] : '530';
			$RTEHeight = isset($BE_USER->userTS['options.']['RTESmallHeight']) ? $BE_USER->userTS['options.']['RTESmallHeight'] : '380';
			$RTEWidth  = $RTEWidth + ($this->TCEform->docLarge ? (isset($BE_USER->userTS['options.']['RTELargeWidthIncrement']) ? $BE_USER->userTS['options.']['RTELargeWidthIncrement'] : '150') : 0);
			$RTEWidth -= ($inline->getStructureDepth() > 0 ? ($inline->getStructureDepth()+1)*$inline->getLevelMargin() : 0);
			if (isset($this->thisConfig['RTEWidthOverride'])) {
				if (strstr($this->thisConfig['RTEWidthOverride'], '%')) {
					if ($this->client['BROWSER'] != 'msie') {
						$RTEWidth = (intval($this->thisConfig['RTEWidthOverride']) > 0) ? $this->thisConfig['RTEWidthOverride'] : '100%';
					}
				} else {
					$RTEWidth = (intval($this->thisConfig['RTEWidthOverride']) > 0) ? intval($this->thisConfig['RTEWidthOverride']) : $RTEWidth;
				}
			}
			$RTEWidth = strstr($RTEWidth, '%') ? $RTEWidth :  $RTEWidth . 'px';
			$RTEHeight = $RTEHeight + ($this->TCEform->docLarge ?  (isset($BE_USER->userTS['options.']['RTELargeHeightIncrement']) ? $BE_USER->userTS['options.']['RTELargeHeightIncrement'] : 0) : 0);
			$RTEHeightOverride = intval($this->thisConfig['RTEHeightOverride']);
			$RTEHeight = ($RTEHeightOverride > 0) ? $RTEHeightOverride : $RTEHeight;
			$editorWrapWidth = '99%';
			$editorWrapHeight = '100%';
			$this->RTEdivStyle = 'position:relative; left:0px; top:0px; height:' . $RTEHeight . 'px; width:'.$RTEWidth.'; border: 1px solid black; padding: 2px 2px 2px 2px;';

			/* =======================================
			 * LOAD CSS AND JAVASCRIPT
			 * =======================================
			 */
				// Preloading the pageStyle and including RTE skin stylesheets
			$this->addPageStyle();
			$this->addSkin();

				// Loading JavaScript files and code
			if ($this->TCEform->RTEcounter == 1) {
				$this->TCEform->additionalJS_pre['rtehtmlarea-loadJScode'] = $this->loadJScode($this->TCEform->RTEcounter);
			}
			$this->TCEform->additionalCode_pre['rtehtmlarea-loadJSfiles'] = $this->loadJSfiles($this->TCEform->RTEcounter);

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
				// Register RTE windows
			$this->TCEform->RTEwindows[] = $PA['itemFormElName'];
			$textAreaId = htmlspecialchars($PA['itemFormElName']);

				// Check if wizard_rte called this for fullscreen edtition; if so, change the size of the RTE to fullscreen using JS
			if (basename(PATH_thisScript) == 'wizard_rte.php') {
				$height = 'window.innerHeight';
				$width = 'window.innerWidth';
				if ($this->client['BROWSER'] == 'msie') {
					$height = 'document.body.offsetHeight';
					$width = 'document.body.offsetWidth';
				}

					// Subtract the docheader height from the calculated window height
				$height .= ' - document.getElementById("typo3-docheader").offsetHeight';

				$editorWrapWidth = '100%';
				$editorWrapHeight = '100%';
				$this->RTEdivStyle = 'position:relative; left:0px; top:0px; height:100%; width:100%; border: 1px solid black; padding: 2px 0px 2px 2px;';
				$this->TCEform->additionalJS_post[] = $this->setRTEsizeByJS('RTEarea' . $textAreaId, $height, $width);
			}

				// Register RTE in JS:
			$this->TCEform->additionalJS_post[] = $this->registerRTEinJS($this->TCEform->RTEcounter, $table, $row['uid'], $field, $textAreaId);

				// Set the save option for the RTE:
			$this->TCEform->additionalJS_submit[] = $this->setSaveRTE($this->TCEform->RTEcounter, $this->TCEform->formName, $textAreaId);
			$this->TCEform->additionalJS_delete[] = $this->setDeleteRTE($this->TCEform->RTEcounter, $this->TCEform->formName, $textAreaId);

				// Draw the textarea
			$visibility = 'hidden';
			$item = $this->triggerField($PA['itemFormElName']).'
				<div id="pleasewait' . $textAreaId . '" class="pleasewait" style="display: block;" >' . $LANG->getLL('Please wait') . '</div>
				<div id="editorWrap' . $textAreaId . '" class="editorWrap" style="visibility: hidden; width:' . $editorWrapWidth . '; height:' . $editorWrapHeight . ';">
				<textarea id="RTEarea' . $textAreaId . '" name="'.htmlspecialchars($PA['itemFormElName']).'" style="'.t3lib_div::deHSCentities(htmlspecialchars($this->RTEdivStyle)).'">'.t3lib_div::formatForTextarea($value).'</textarea>
				</div>' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableDebugMode'] ? '<div id="HTMLAreaLog"></div>' : '') . '
				';
		}

			// Return form item:
		return $item;
	}

	/**
	 * Add link to content style sheet to document header
	 *
	 * @return	void
	 */
	protected function addPageStyle() {
			// Get stylesheet file name from Page TSConfig if any
		$filename = trim($this->thisConfig['contentCSS']) ? trim($this->thisConfig['contentCSS']) : 'EXT:' . $this->ID . '/res/contentcss/default.css';
		$this->addStyleSheet(
			'rtehtmlarea-page-style',
			$this->getFullFileName($filename),
			'htmlArea RTE Content CSS',
			'alternate stylesheet'
			);
	}

	/**
	 * Add links to skin style sheet(s) to document header
	 *
	 * @return	void
	 */
	protected function addSkin() {
			// Get skin file name from Page TSConfig if any
		$skinFilename = trim($this->thisConfig['skin']) ? trim($this->thisConfig['skin']) : 'EXT:' . $this->ID . '/htmlarea/skins/default/htmlarea.css';
		if($this->client['BROWSER'] == 'gecko' && $this->client['VERSION'] == '1.3' && substr($skinFilename,0,4) == 'EXT:')  {
			$skinFilename = 'EXT:' . $this->ID . '/htmlarea/skins/default/htmlarea.css';
		}
			// Skin provided by some extension
		if (substr($skinFilename,0,4) == 'EXT:') {
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
			// Editing area style sheet
		$this->editedContentCSS = $skinDir . '/htmlarea-edited-content.css';
		$this->addStyleSheet(
			'rtehtmlarea-editing-area-skin',
			$this->editedContentCSS
			);
			// Main skin
		$this->addStyleSheet(
			'rtehtmlarea-skin',
			$this->editorCSS
			);
			// Additional icons from registered plugins
		foreach ($this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter] as $pluginId) {
			if (is_object($this->registeredPlugins[$pluginId])) {
				$pathToSkin = $this->registeredPlugins[$pluginId]->getPathToSkin();
				if ($pathToSkin) {
					$this->addStyleSheet(
						'rtehtmlarea-plugin-' . $pluginId . '-skin',
						$this->httpTypo3Path . t3lib_extMgm::siteRelPath($this->registeredPlugins[$pluginId]->getExtensionKey()) . $pathToSkin
						);
				}
			}
		}
	}

	/**
	 * Add style sheet file to document header
	 *
	 * @param	string		$key: some key identifying the style sheet
	 * @param	string		$href: uri to the style sheet file
	 * @param	string		$title: value for the title attribute of the link element
	 * @return	string		$relation: value for the rel attribute of the link element
	 * @return	void
	 */
	protected function addStyleSheet($key, $href, $title='', $relation='stylesheet') {
			// If it was not known that an RTE-enabled would be created when the page was first created, the css would not be added to head
		if (is_object($this->TCEform->inline) && $this->TCEform->inline->isAjaxCall) {
			$this->TCEform->additionalCode_pre[$key] = '<link rel="' . $relation . '" type="text/css" href="' . $href . '" title="' . $title. '" />';
		} else {
			$this->TCEform->addStyleSheet($key, $href, $title, $relation);
		}

	}

	/**
	 * Initialize toolbar configuration and enable registered plugins
	 *
	 * @return	void
	 */
	protected function initializeToolbarConfiguration() {

			// Enable registred plugins
		$this->enableRegisteredPlugins();

			// Configure toolbar
		$this->setToolbar();

			// Check if some plugins need to be disabled
		$this->setPlugins();

			// Merge the list of enabled plugins with the lists from the previous RTE editing areas on the same form
		$this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter] = $this->pluginEnabledArray;
		if ($this->TCEform->RTEcounter > 1 && isset($this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter-1]) && is_array($this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter-1])) {
			$this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter] = array_unique(array_values(array_merge($this->pluginEnabledArray,$this->pluginEnabledCumulativeArray[$this->TCEform->RTEcounter-1])));
		}
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
				$plugin = false;
				if (is_array($pluginObjectConfiguration) && count($pluginObjectConfiguration)) {
					$plugin = t3lib_div::getUserObj($pluginObjectConfiguration['objectReference']);
				}
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
			if ($plugin->addsButtons() && !$this->pluginButton[$pluginId]) {
				$hidePlugins[] = $pluginId;
			}
		}
		$this->pluginEnabledArray = array_unique(array_diff($this->pluginEnabledArray, $hidePlugins));
	}

	/**
	 * Set the toolbar config (only in this PHP-Object, not in JS):
	 *
	 */

	function setToolbar() {
		global $BE_USER;

		if ($this->client['BROWSER'] == 'msie' || $this->client['BROWSER'] == 'opera' || ($this->client['BROWSER'] == 'gecko' && $this->client['VERSION'] == '1.3')) {
			$this->thisConfig['keepButtonGroupTogether'] = 0;
		}

		$this->defaultToolbarOrder = 'bar, blockstylelabel, blockstyle, space, textstylelabel, textstyle, linebreak,
			bar, formattext, bold,  strong, italic, emphasis, big, small, insertedtext, deletedtext, citation, code, definition, keyboard, monospaced, quotation, sample, variable, bidioverride, strikethrough, subscript, superscript, underline, span,
			bar, fontstyle, space, fontsize, bar, formatblock, insertparagraphbefore, insertparagraphafter, blockquote,
			bar, left, center, right, justifyfull,
			bar, orderedlist, unorderedlist, definitionlist, definitionitem, outdent, indent,  bar, lefttoright, righttoleft, language, showlanguagemarks,
			bar, textcolor, bgcolor, textindicator,
			bar, emoticon, insertcharacter, line, link, unlink, image, table,' . (($this->thisConfig['hideTableOperationsInToolbar'] && is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['toggleborders.']) && $this->thisConfig['buttons.']['toggleborders.']['keepInToolbar']) ? ' toggleborders,': '') . ' user, acronym, bar, findreplace, spellcheck,
			bar, chMode, inserttag, removeformat, bar, copy, cut, paste, bar, undo, redo, bar, showhelp, about, linebreak,
			' . ($this->thisConfig['hideTableOperationsInToolbar'] ? '': 'bar, toggleborders,') . ' bar, tableproperties, tablerestyle, bar, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit, bar,
			columnproperties, columninsertbefore, columninsertafter, columndelete, columnsplit, bar,
			cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge';

			// Special toolbar for Mozilla Wamcom on Mac OS 9
		if($this->client['BROWSER'] == 'gecko' && $this->client['VERSION'] == '1.3')  {
			$this->defaultToolbarOrder = $this->TCEform->docLarge ? 'bar, blockstylelabel, blockstyle, space, textstylelabel, textstyle, linebreak,
				bar, fontstyle, space, fontsize, space, formatblock, insertparagraphbefore, insertparagraphafter, blockquote, bar, bold, italic, underline, strikethrough,
				subscript, superscript, lefttoright, righttoleft, language, showlanguagemarks, bar, left, center, right, justifyfull, linebreak,
				bar, orderedlist, unorderedlist, definitionlist, definitionitem, outdent, indent, bar, textcolor, bgcolor, textindicator, bar, emoticon,
				insertcharacter, line, link, unlink, image, table, user, acronym, bar, findreplace, spellcheck, bar, chMode, inserttag,
				removeformat, bar, copy, cut, paste, bar, undo, redo, bar, showhelp, about, linebreak,
				bar, toggleborders, bar, tableproperties, tablerestyle, bar, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit, bar,
				columnproperties, columninsertbefore, columninsertafter, columndelete, columnsplit, bar,
				cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge'
				: 'bar, blockstylelabel, blockstyle, space, textstylelabel, textstyle, linebreak,
				bar, fontstyle, space, fontsize, space, formatblock, insertparagraphbefore, insertparagraphafter, blockquote, bar, bold, italic, underline, strikethrough,
				subscript, superscript, linebreak, bar, lefttoright, righttoleft, language, showlanguagemarks, bar, left, center, right, justifyfull,
				orderedlist, unorderedlist, definitionlist, definitionitem, outdent, indent, bar, textcolor, bgcolor, textindicator, bar, emoticon,
				insertcharacter, line, link, unlink, image, table, user, acronym, linebreak, bar, findreplace, spellcheck, bar, chMode, inserttag,
				removeformat, bar, copy, cut, paste, bar, undo, redo, bar, showhelp, about, linebreak,
				bar, toggleborders, bar, tableproperties, tablerestyle, bar, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit, bar,
				columnproperties, columninsertbefore, columninsertafter, columndelete, columnsplit, bar,
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

			// Hiding buttons
		$show = array_diff($show, $this->conf_toolbar_hide, t3lib_div::trimExplode(',',$this->thisConfig['hideButtons'],1));

			// Apply toolbar constraints from registered plugins
		foreach ($this->registeredPlugins as $pluginId => $plugin) {
			if ($this->isPluginEnabled($pluginId) && method_exists($plugin, "applyToolbarConstraints")) {
				$show = $plugin->applyToolbarConstraints($show);
			}
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

			// Disabling a plugin that adds buttons if none of its buttons is in the toolbar
		$hidePlugins = array();
		foreach ($this->pluginButton as $pluginId => $buttonList) {
			if ($this->registeredPlugins[$pluginId]->addsButtons()) {
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
		}
		$this->pluginEnabledArray = array_diff($this->pluginEnabledArray, $hidePlugins);

			// Hiding labels of disabled plugins
		$hideLabels = array();
		foreach ($this->pluginLabel as $pluginId => $label) {
			if (!$this->isPluginEnabled($pluginId)) {
				$hideLabels[] = $label;
			}
		}
		$this->toolbar = array_diff($this->toolbar, $hideLabels);

			// Adding plugins declared as prerequisites by enabled plugins
		$requiredPlugins = array();
		foreach ($this->registeredPlugins as $pluginId => $plugin) {
			if ($this->isPluginEnabled($pluginId)) {
				$requiredPlugins = array_merge($requiredPlugins, t3lib_div::trimExplode(',', $plugin->getRequiredPlugins(), 1));
			}
		}
		$requiredPlugins = array_unique($requiredPlugins);
		foreach ($requiredPlugins as $pluginId) {
			if (is_object($this->registeredPlugins[$pluginId]) && !$this->isPluginEnabled($pluginId)) {
				$this->pluginEnabledArray[] = $pluginId;
			}
		}
		$this->pluginEnabledArray = array_unique($this->pluginEnabledArray);

			// Completing the toolbar conversion array for htmlArea
		foreach ($this->registeredPlugins as $pluginId => $plugin) {
			if ($this->isPluginEnabled($pluginId)) {
				$this->convertToolbarForHtmlAreaArray = array_unique(array_merge($this->convertToolbarForHtmlAreaArray, $plugin->getConvertToolbarForHtmlAreaArray()));
			}
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

		$loadPluginCode = '
						HTMLArea_plugins = new Array();';
		foreach ($this->pluginEnabledCumulativeArray[$RTEcounter] as $pluginId) {
			$extensionKey = is_object($this->registeredPlugins[$pluginId]) ? $this->registeredPlugins[$pluginId]->getExtensionKey() : $this->ID;
			$loadPluginCode .= '
						HTMLArea_plugins.push({url : "' . $this->writeTemporaryFile('EXT:' . $extensionKey . '/htmlarea/plugins/' . $pluginId . '/' . strtolower(preg_replace('/([a-z])([A-Z])([a-z])/', "$1".'-'."$2"."$3", $pluginId)) . '.js', $pluginId) . '", asynchronous : ' . ($this->registeredPlugins[$pluginId]->requiresSynchronousLoad() ? 'false' : 'true'). ' });';
		}
			// Avoid re-initialization on AJax call when RTEarea object was already initialized
		$loadJavascriptCode = '
		<script type="text/javascript">
		/*<![CDATA[*/
			if (typeof(RTEarea) == "undefined") {
				RTEarea = new Object();
				RTEarea.init = function() {
					if (typeof(HTMLArea) == "undefined") {
						window.setTimeout("RTEarea.init();", 40);
					} else {'
						. $loadPluginCode . '
						HTMLArea.init();
					}
				};
				RTEarea.initEditor = function(editorNumber) {
					if (typeof(HTMLArea) == "undefined") {
						window.setTimeout("RTEarea.initEditor(\'" + editorNumber + "\');", 40);
					} else {
						HTMLArea.initEditor(editorNumber);
					}
				};
				RTEarea[0] = new Object();
				RTEarea[0].version = "' . $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['version'] . '";'
				. (($this->client['BROWSER'] == 'msie') ? ('
				RTEarea[0]["htmlarea-ie"] = "' . $this->writeTemporaryFile('EXT:' . $this->ID . '/htmlarea/htmlarea-ie.js', "htmlarea-ie") . '";')
				: ('
				RTEarea[0]["htmlarea-gecko"] = "' . $this->writeTemporaryFile('EXT:' . $this->ID . '/htmlarea/htmlarea-gecko.js', "htmlarea-gecko") . '";')) . '
				_editor_url = "' . $this->extHttpPath . 'htmlarea";
				_editor_lang = "' . $this->language . '";
				_editor_CSS = "' . $this->editorCSS . '";
				_editor_skin = "' . dirname($this->editorCSS) . '";
				_editor_edited_content_CSS = "' .  $this->editedContentCSS  . '";
				_typo3_host_url = "' . $this->hostURL . '";
				_editor_debug_mode = ' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableDebugMode'] ? 'true' : 'false') . ';
				_editor_compressed_scripts = ' . ($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] ? 'true' : 'false') . ';
			}
		/*]]>*/
		</script>';
		$loadJavascriptCode .= '
		<script type="text/javascript" src="' . $this->buildJSMainLangFile($RTEcounter) . '" charset="utf-8"></script>
		<script type="text/javascript" src="' . $this->writeTemporaryFile('EXT:' . $this->ID . '/htmlarea/htmlarea.js', "htmlarea") . '"></script>
		';
		return $loadJavascriptCode;
	}

	/**
	 * Return the Javascript code for initializing the RTE
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return 	string		the Javascript code for initializing the RTE
	 */
	function loadJScode($RTEcounter) {
		return (!$this->is_FE() ? '' : '
		' . '/*<![CDATA[*/') . '
			RTEarea.init();' . (!$this->is_FE() ? '' : '
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
	function registerRTEinJS($RTEcounter, $table='', $uid='', $field='', $textAreaId = '') {
		global $TYPO3_CONF_VARS;

		$configureRTEInJavascriptString = (!$this->is_FE() ? '' : '
			' . '/*<![CDATA[*/') . '
			if (typeof(configureEditorInstance) == "undefined") {
				configureEditorInstance = new Object();
			}
			configureEditorInstance["' . $textAreaId . '"] = function() {
				if (typeof(RTEarea) == "undefined" || typeof(HTMLArea) == "undefined") {
					window.setTimeout("configureEditorInstance[\'' . $textAreaId . '\']();", 40);
				} else {
			editornumber = "' . $textAreaId . '";
			RTEarea[editornumber] = new Object();
			RTEarea[editornumber].RTEtsConfigParams = "&RTEtsConfigParams=' . rawurlencode($this->RTEtsConfigParams()) . '";
			RTEarea[editornumber].number = editornumber;
			RTEarea[editornumber].deleted = false;
			RTEarea[editornumber].textAreaId = "' . $textAreaId . '";
			RTEarea[editornumber].id = "RTEarea" + editornumber;
			RTEarea[editornumber].enableWordClean = ' . (trim($this->thisConfig['enableWordClean'])?'true':'false') . ';
			RTEarea[editornumber]["htmlRemoveComments"] = ' . (trim($this->thisConfig['removeComments'])?'true':'false') . ';
			RTEarea[editornumber].disableEnterParagraphs = ' . (trim($this->thisConfig['disableEnterParagraphs'])?'true':'false') . ';
			RTEarea[editornumber].disableObjectResizing = ' . (trim($this->thisConfig['disableObjectResizing'])?'true':'false') . ';
			RTEarea[editornumber]["removeTrailingBR"] = ' . (trim($this->thisConfig['removeTrailingBR'])?'true':'false') . ';
			RTEarea[editornumber]["useCSS"] = ' . (trim($this->thisConfig['useCSS'])?'true':'false') . ';
			RTEarea[editornumber]["keepButtonGroupTogether"] = ' . (trim($this->thisConfig['keepButtonGroupTogether'])?'true':'false') . ';
			RTEarea[editornumber]["disablePCexamples"] = ' . (trim($this->thisConfig['disablePCexamples'])?'true':'false') . ';
			RTEarea[editornumber]["showTagFreeClasses"] = ' . (trim($this->thisConfig['showTagFreeClasses'])?'true':'false') . ';
			RTEarea[editornumber]["useHTTPS"] = ' . ((trim(stristr($this->siteURL, 'https')) || $this->thisConfig['forceHTTPS'])?'true':'false') . ';
			RTEarea[editornumber].tceformsNested = ' . (is_object($this->TCEform) && method_exists($this->TCEform, 'getDynNestedStack') ? $this->TCEform->getDynNestedStack(true) : '[]') . ';
			RTEarea[editornumber].dialogueWindows = new Object();
			RTEarea[editornumber].dialogueWindows.defaultPositionFromTop = ' . (isset($this->thisConfig['dialogueWindows.']['defaultPositionFromTop'])? intval($this->thisConfig['dialogueWindows.']['defaultPositionFromTop']) : '100') . ';
			RTEarea[editornumber].dialogueWindows.defaultPositionFromLeft = ' . (isset($this->thisConfig['dialogueWindows.']['defaultPositionFromLeft'])? intval($this->thisConfig['dialogueWindows.']['defaultPositionFromLeft']) : '100') . ';
			RTEarea[editornumber].dialogueWindows.doNotResize = ' . (trim($this->thisConfig['dialogueWindows.']['doNotResize'])?'true':'false') . ';
			RTEarea[editornumber].dialogueWindows.doNotCenter = ' . (trim($this->thisConfig['dialogueWindows.']['doNotCenter'])?'true':'false') . ';';

			// The following properties apply only to the backend
		if (!$this->is_FE()) {
			$configureRTEInJavascriptString .= '
			RTEarea[editornumber].sys_language_content = "' . $this->contentLanguageUid . '";
			RTEarea[editornumber].typo3ContentLanguage = "' . $this->contentTypo3Language . '";
			RTEarea[editornumber].typo3ContentCharset = "' . $this->contentCharset . '";
			RTEarea[editornumber].userUid = "' . $this->userUid . '";';
		}

			// Setting the plugin flags
		$configureRTEInJavascriptString .= '
			RTEarea[editornumber].plugin = new Object();
			RTEarea[editornumber].pathToPluginDirectory = new Object();';
		foreach ($this->pluginEnabledArray as $pluginId) {
			$configureRTEInJavascriptString .= '
			RTEarea[editornumber].plugin.'.$pluginId.' = true;';
			if (is_object($this->registeredPlugins[$pluginId])) {
				$pathToPluginDirectory = $this->registeredPlugins[$pluginId]->getPathToPluginDirectory();
				if ($pathToPluginDirectory) {
					$configureRTEInJavascriptString .= '
			RTEarea[editornumber].pathToPluginDirectory.'.$pluginId.' = "' . $pathToPluginDirectory . '";';
				}
			}
		}

			// Setting the buttons configuration
		$configureRTEInJavascriptString .= '
			RTEarea[editornumber].buttons = new Object();';
		if (is_array($this->thisConfig['buttons.'])) {
			foreach ($this->thisConfig['buttons.'] as $buttonIndex => $conf) {
				$button = substr($buttonIndex, 0, -1);
				if (in_array($button,$this->toolbar)) {
					$configureRTEInJavascriptString .= '
			RTEarea[editornumber].buttons.'.$button.' = ' . $this->buildNestedJSArray($conf) . ';';
				}
			}
		}

			// Setting the list of tags to be removed if specified in the RTE config
		if (trim($this->thisConfig['removeTags']))  {
			$configureRTEInJavascriptString .= '
			RTEarea[editornumber]["htmlRemoveTags"] = /^(' . implode('|', t3lib_div::trimExplode(',', $this->thisConfig['removeTags'], 1)) . ')$/i;';
		}

			// Setting the list of tags to be removed with their contents if specified in the RTE config
		if (trim($this->thisConfig['removeTagsAndContents']))  {
			$configureRTEInJavascriptString .= '
			RTEarea[editornumber]["htmlRemoveTagsAndContents"] = /^(' . implode('|', t3lib_div::trimExplode(',', $this->thisConfig['removeTagsAndContents'], 1)) . ')$/i;';
		}

			// Process default style configuration
		$configureRTEInJavascriptString .= '
			RTEarea[editornumber].defaultPageStyle = "' . ($this->thisConfig['forceHTTPS'] ? '' : $this->hostURL) . $this->writeTemporaryFile('', 'defaultPageStyle', 'css', $this->buildStyleSheet()) . '";';

			// Setting the pageStyle
		$filename = trim($this->thisConfig['contentCSS']) ? trim($this->thisConfig['contentCSS']) : 'EXT:' . $this->ID . '/res/contentcss/default.css';
		$configureRTEInJavascriptString .= '
			RTEarea[editornumber].pageStyle = "' . $this->getFullFileName($filename) .'";';

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

			// Add Javascript configuration for registered plugins
		foreach ($this->registeredPlugins as $pluginId => $plugin) {
			if ($this->isPluginEnabled($pluginId)) {
				$configureRTEInJavascriptString .= $plugin->buildJavascriptConfiguration('editornumber');
			}
		}
			// Avoid premature reference to HTMLArea when being initially loaded by IRRE Ajax call
		$configureRTEInJavascriptString .= '
			RTEarea[editornumber].toolbar = ' . $this->getJSToolbarArray() . ';
			RTEarea[editornumber].convertButtonId = ' . json_encode(array_flip($this->convertToolbarForHtmlAreaArray)) . ';
			RTEarea.initEditor(editornumber);
				}
			};
			configureEditorInstance["' . $textAreaId . '"]();'. (!$this->is_FE() ? '' : '
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
		$classesTagConvert = array( 'classesCharacter' => 'span', 'classesParagraph' => 'div', 'classesImage' => 'img', 'classesTable' => 'table', 'classesLinks' => 'a', 'classesTD' => 'td');
		$classesTagArray = t3lib_div::trimExplode(',' , $classesTagList);
		$configureRTEInJavascriptString = '
			RTEarea[editornumber]["classesTag"] = new Object();';
		foreach ($classesTagArray as $classesTagName) {
			$HTMLAreaJSClasses = ($this->thisConfig[$classesTagName])?('"' . $this->cleanList($this->thisConfig[$classesTagName]) . '";'):'null;';
			$configureRTEInJavascriptString .= '
			RTEarea[editornumber]["classesTag"]["'. $classesTagConvert[$classesTagName] .'"] = '. $HTMLAreaJSClasses;
		}

			// Include JS arrays of configured classes
		$configureRTEInJavascriptString .= '
			RTEarea[editornumber]["classesUrl"] = "' . ($this->thisConfig['forceHTTPS'] ? '' : $this->hostURL) . $this->writeTemporaryFile('', 'classes_'.$LANG->lang, 'js', $this->buildJSClassesArray()) . '";';

		return $configureRTEInJavascriptString;
	}

	/**
	 * Return JS arrays of classes configuration
	 *
	 * @return string	JS classes arrays
	 */
	function buildJSClassesArray() {
		if ($this->is_FE()) {
			$RTEProperties = $this->RTEsetup;
		} else {
			$RTEProperties = $this->RTEsetup['properties'];
		}
		$classesArray = array('labels' => array(), 'values' => array(), 'noShow' => array(), 'alternating' => array(), 'counting' => array(), 'XOR' => array());
		$JSClassesArray = '';
			// Scanning the list of classes if specified in the RTE config
		if (is_array($RTEProperties['classes.'])) {
			foreach ($RTEProperties['classes.'] as $className => $conf) {
				$className = rtrim($className, '.');
				$classesArray['labels'][$className] = $this->getPageConfigLabel($conf['name'], FALSE);
				$classesArray['values'][$className] = str_replace('\\\'', '\'', $conf['value']);
				if (isset($conf['noShow'])) {
					$classesArray['noShow'][$className] = $conf['noShow'];
				}
				if (is_array($conf['alternating.'])) {
					$classesArray['alternating'][$className] = $conf['alternating.'];
				}
				if (is_array($conf['counting.'])) {
					$classesArray['counting'][$className] = $conf['counting.'];
				}
			}
		}
			// Scanning the list of sets of mutually exclusives classes if specified in the RTE config
		if (is_array($RTEProperties['mutuallyExclusiveClasses.'])) {
			foreach ($RTEProperties['mutuallyExclusiveClasses.'] as $listName => $conf) {
				$classSet = t3lib_div::trimExplode(',', $conf, 1);
				$classList = implode(',', $classSet);
				foreach ($classSet as $className) {
					$classesArray['XOR'][$className] = '/^(' . implode('|', t3lib_div::trimExplode(',', t3lib_div::rmFromList($className, $classList), 1)) . ')$/';
				}
			}
		}
		foreach ($classesArray as $key => $subArray) {
			$JSClassesArray .= 'HTMLArea.classes' . ucfirst($key) . ' = ' . $this->buildNestedJSArray($subArray) . ';' . chr(10);
		}
		return $JSClassesArray;
	}

	/**
	 * Translate Page TS Config array in JS nested array definition
	 * Replace 0 values with false
	 * Unquote regular expression values
	 * Replace empty arrays with empty objects
	 *
	 * @param	array		$conf: Page TSConfig configuration array
	 *
	 * @return 	string		nested JS array definition
	 */
	function buildNestedJSArray($conf) {
		$convertedConf = t3lib_div::removeDotsFromTS($conf);
		if ($this->is_FE()) {
			$GLOBALS['TSFE']->csConvObj->convArray($convertedConf, ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : 'iso-8859-1'), 'utf-8');
		} else {
			$GLOBALS['LANG']->csConvObj->convArray($convertedConf, $GLOBALS['LANG']->charSet, 'utf-8');
		}
		return str_replace(array(':"0"', ':"\/^(', ')$\/i"', ':"\/^(', ')$\/"', '[]'), array(':false', ':/^(', ')$/i', ':/^(', ')$/', '{}'), json_encode($convertedConf));
	}

	/**
	 * Return a Javascript localization array for htmlArea RTE
	 *
	 * @return	string		Javascript localization array
	 */
	function buildJSMainLangArray() {
		$JSLanguageArray = 'var HTMLArea_langArray = new Object();' . chr(10);
		$labelsArray = array('tooltips' => array(), 'msg' => array(), 'dialogs' => array());
		foreach ($labelsArray as $labels => $subArray) {
			$LOCAL_LANG = t3lib_div::readLLfile('EXT:' . $this->ID . '/htmlarea/locallang_' . $labels . '.xml', $this->language, 'utf-8');
			if (!empty($LOCAL_LANG[$this->language])) {
				$LOCAL_LANG[$this->language] = t3lib_div::array_merge_recursive_overrule($LOCAL_LANG['default'], $LOCAL_LANG[$this->language]);
			} else {
				$LOCAL_LANG[$this->language] = $LOCAL_LANG['default'];
			}
			$labelsArray[$labels] = $LOCAL_LANG[$this->language];
		}
		$JSLanguageArray .= 'HTMLArea_langArray = ' . json_encode($labelsArray) . ';' . chr(10);
		return $JSLanguageArray;
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
	public function writeTemporaryFile($sourceFileName='', $label, $fileExtension='js', $contents='') {
		global $TYPO3_CONF_VARS;

		if ($sourceFileName) {
			$output = '';
			$source = t3lib_div::getFileAbsFileName($sourceFileName);
			$output = file_get_contents($source);
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
		$LOCAL_LANG = FALSE;
		$extensionKey = is_object($this->registeredPlugins[$plugin]) ? $this->registeredPlugins[$plugin]->getExtensionKey() : $this->ID;
		$LOCAL_LANG = t3lib_div::readLLfile('EXT:' . $extensionKey . '/htmlarea/plugins/' . $plugin . '/locallang.xml', $this->language, 'utf-8', 1);
		$linebreak = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->ID]['enableCompressedScripts'] ? '' : chr(10);
		$JSLanguageArray = 'var ' . $plugin . '_langArray = new Object();' . $linebreak;
		if (is_array($LOCAL_LANG)) {
			if (!empty($LOCAL_LANG[$this->language])) {
				$LOCAL_LANG[$this->language] = t3lib_div::array_merge_recursive_overrule($LOCAL_LANG['default'],$LOCAL_LANG[$this->language]);
			} else {
				$LOCAL_LANG[$this->language] = $LOCAL_LANG['default'];
			}
			$JSLanguageArray .= $plugin . '_langArray = ' . json_encode($LOCAL_LANG[$this->language]) . ';'. chr(10);
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
				$group_needs_starting_bar = ($button == 'bar');
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

	public function getLLContent($string) {
		global $LANG;

		$BE_lang = $LANG->lang;
		$BE_charSet = $LANG->charSet;
		$LANG->lang = $this->contentTypo3Language;
		$LANG->charSet = $this->contentCharset;
		$LLString = $LANG->JScharCode($LANG->sL($string));
		$LANG->lang = $BE_lang;
		$LANG->charSet = $BE_charSet;
		return $LLString;
	}

	public function getPageConfigLabel($string,$JScharCode=1) {
		global $LANG, $TSFE, $TYPO3_CONF_VARS;

		if ($this->is_FE()) {
			if (strcmp(substr($string,0,4),'LLL:') && $TYPO3_CONF_VARS['BE']['forceCharset'])	{
					// A pure string coming from Page TSConfig must be in forceCharset, otherwise we just don't know..
				$label = $TSFE->csConvObj->conv($TSFE->sL(trim($string)), $TYPO3_CONF_VARS['BE']['forceCharset'], $this->OutputCharset);
			} else {
				$label = $TSFE->csConvObj->conv($TSFE->sL(trim($string)), $this->charset, $this->OutputCharset);
			}
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

	public function getFullFileName($filename) {
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
		return 'if (RTEarea["' . $textareaId . '"]) { document.' . $formName . '["' . $textareaId . '"].value = RTEarea["' . $textareaId . '"].editor.getPluginInstance("EditorMode").getHTML(); } else { OK = 0; };';
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
	function setDeleteRTE($RTEcounter, $formName, $textareaId) {
		return 'if (RTEarea["' . $textareaId . '"]) { RTEarea["' . $textareaId . '"].deleted = true;}';
	}

	/**
	 * Return true if we are in the FE, but not in the FE editing feature of BE.
	 *
	 * @return boolean
	 */

	function is_FE() {
		global $TSFE;
		return is_object($TSFE) && is_array($this->LOCAL_LANG) && !strstr($this->elementId,'TSFE_EDIT');
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
					$bInfo['VERSION'] = doubleval(preg_replace('/^[^0-9]*/','',substr($tmp,3)));
				break;
				case 'msie':
					$tmp = strstr($useragent,'MSIE');
					$bInfo['VERSION'] = doubleval(preg_replace('/^[^0-9]*/','',substr($tmp,4)));
				break;
				case 'safari':
					$tmp = strstr($useragent,'Safari/');
					$bInfo['VERSION'] = doubleval(preg_replace('/^[^0-9]*/','',substr($tmp,3)));
				break;
				case 'opera':
					$tmp = strstr($useragent,'Opera');
					$bInfo['VERSION'] = doubleval(preg_replace('/^[^0-9]*/','',substr($tmp,5)));
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

	public function cleanList($str)        {
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