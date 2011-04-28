<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2011 Kasper Skårhøj (kasper@typo3.com)
*  (c) 2004-2011 Philipp Borgmann <philipp.borgmann@gmx.de>
*  (c) 2004-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 */

class tx_rtehtmlarea_base extends t3lib_rteapi {
		// Configuration of supported browsers
	var $conf_supported_browser = array (
			'msie' => array (
				array (
					'version' => 6.0,
					'system' => array(
						'allowed' => array(
							'winNT',
							'win98',
							'win95',
						),
					),
				),
			),
			'gecko' => array (
				array (
					'version' => 1.8,
				),
			),
			'webkit' => array (
				array (
					'version' => 523,
					'system' => array(
						'disallowed' => array(
							'iOS',
							'android',
						),
					),
				),
			),
			'opera' => array (
				array (
					'version' => 9.62,
					'system' => array(
						'disallowed' => array(
							'iOS',
							'android',
						),
					),
				),
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
		'showhelp'		=> 'ShowHelp',
		'space'			=> 'space',
		'bar'			=> 'separator',
		'linebreak'		=> 'linebreak',
		);

	var $pluginButton = array();
	var $pluginLabel = array();
		// Alternative style for RTE <div> tag.
	public $RTEdivStyle;
		// Relative path to this extension. It ends with "/"
	public $extHttpPath;
	public $backPath = '';
		// TYPO3 site url
	public $siteURL;
		// TYPO3 host url
	public $hostURL;
		// Typo3 version
	public $typoVersion;
		// Identifies the RTE as being the one from the "rtehtmlarea" extension if any external code needs to know
	var $ID = 'rtehtmlarea';
		// If set, the content goes into a regular TEXT area field - for developing testing of transformations.
	var $debugMode = FALSE;
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
	var $toolbarOrderArray = array();
	protected $pluginEnabledArray = array();		// Array of plugin id's enabled in the current RTE editing area
	protected $pluginEnabledCumulativeArray = array();	// Cumulative array of plugin id's enabled so far in any of the RTE editing areas of the form
	protected $cumulativeScripts = array();
	public $registeredPlugins = array();			// Array of registered plugins indexed by their plugin Id's
	protected $fullScreen = false;

	/**
	 * Returns TRUE if the RTE is available. Here you check if the browser requirements are met.
	 * If there are reasons why the RTE cannot be displayed you simply enter them as text in ->errorLog
	 *
	 * @return	boolean		TRUE if this RTE object offers an RTE in the current browser environment
	 */
	function isAvailable()	{

		$this->client = $this->clientInfo();
		$this->errorLog = array();
		if (!$this->debugMode)	{	// If debug-mode, let any browser through
			$rteIsAvailable = FALSE;
			$rteConfBrowser = $this->conf_supported_browser;
			if (is_array($rteConfBrowser)) {
				foreach ($rteConfBrowser as $browser => $browserConf) {
					if ($browser == $this->client['browser']) {
							// Config for Browser found, check it:
						if (is_array($browserConf)) {
							foreach ($browserConf as $browserConfSub) {
								if ($browserConfSub['version'] <= $this->client['version'] || empty($browserConfSub['version'])) {
										// Version is supported
									if (is_array($browserConfSub['system'])) {
											// Check against allowed systems
										if (is_array($browserConfSub['system']['allowed'])) {
											foreach ($browserConfSub['system']['allowed'] as $system) {
												if (in_array($system, $this->client['all_systems'])) {
													$rteIsAvailable = TRUE;
													break;
												}
											}
										} else {
												// All allowed
											$rteIsAvailable = TRUE;
										}
											// Check against disallowed systems
										if (is_array($browserConfSub['system']['disallowed'])) {
											foreach ($browserConfSub['system']['disallowed'] as $system) {
												if (in_array($system, $this->client['all_systems'])) {
													$rteIsAvailable = FALSE;
													break;
												}
											}
										}
									} else {
											// No system config: system is supported
										$rteIsAvailable = TRUE;
										break;
									}// End of System
								}// End of Version
							}// End of foreach-BrowserSubpart
						} else {
							// no config for this browser found, so all versions or system with this browsers are allow
							$rteIsAvailable = TRUE;
							break;
						}
					} // End of Browser Check
				} // foreach: Browser Check
			} else {
				// no Browser config for this RTE-Editor, so all Clients are allow
			}
			if (!$rteIsAvailable) {
				$this->errorLog[] = 'RTE: Browser not supported.';
			}
			if (t3lib_div::int_from_ver(TYPO3_version) < 4000000) {
				$rteIsAvailable = FALSE;
				$this->errorLog[] = 'rte: This version of htmlArea RTE cannot run under this version of TYPO3.';
			}
		}
		return $rteIsAvailable;
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
		global $LANG, $TYPO3_DB, $TYPO3_CONF_VARS;

		$this->TCEform = $parentObject;
		$inline = $this->TCEform->inline;
		$LANG->includeLLFile('EXT:' . $this->ID . '/locallang.xml');
		$this->client = $this->clientInfo();
		$this->typoVersion = t3lib_div::int_from_ver(TYPO3_version);
		$this->userUid = 'BE_' . $GLOBALS['BE_USER']->user['uid'];

			// Draw form element:
		if ($this->debugMode)	{	// Draws regular text area (debug mode)
			$item = parent::drawRTE($this->TCEform, $table, $field, $row, $PA, $specConf, $thisConfig, $RTEtypeVal, $RTErelPath, $thePidValue);
		} else {	// Draw real RTE

			/* =======================================
			 * INIT THE EDITOR-SETTINGS
			 * =======================================
			 */
			 	// Set backPath
			$this->backPath = $this->TCEform->backPath;
				// Get the path to this extension:
			$this->extHttpPath = $this->backPath . t3lib_extMgm::extRelPath($this->ID);
				// Get the site URL
			$this->siteURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
				// Get the host URL
			$this->hostURL = $this->siteURL . TYPO3_mainDir;
				// Element ID + pid
			$this->elementId = $PA['itemFormElName']; // Form element name
			$this->elementParts = explode('][',preg_replace('/\]$/','',preg_replace('/^(TSFE_EDIT\[data\]\[|data\[)/','',$this->elementId)));
				// Find the page PIDs:
			list($this->tscPID,$this->thePid) = t3lib_BEfunc::getTSCpid(trim($this->elementParts[0]),trim($this->elementParts[1]),$thePidValue);

				// Record "types" field value:
			$this->typeVal = $RTEtypeVal; // TCA "types" value for record

				// Find "thisConfig" for record/editor:
			unset($this->RTEsetup);
			$this->RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', t3lib_BEfunc::getPagesTSconfig($this->tscPID));
			$this->thisConfig = $thisConfig;

				// Special configuration and default extras:
			$this->specConf = $specConf;

			if ($this->thisConfig['forceHTTPS']) {
				$this->extHttpPath = preg_replace('/^(http|https)/', 'https', $this->extHttpPath);
				$this->siteURL = preg_replace('/^(http|https)/', 'https', $this->siteURL);
				$this->hostURL = preg_replace('/^(http|https)/', 'https', $this->hostURL);
			}
				// Register RTE windows
			$this->TCEform->RTEwindows[] = $PA['itemFormElName'];
			$textAreaId = preg_replace('/[^a-zA-Z0-9_:.-]/', '_', $PA['itemFormElName']);
			$textAreaId = htmlspecialchars(preg_replace('/^[^a-zA-Z]/', 'x', $textAreaId));

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
				// Check if wizard_rte called this for fullscreen edtition
			if (basename(PATH_thisScript) == 'wizard_rte.php') {
				$this->fullScreen = TRUE;
				$RTEWidth = '100%';
				$RTEHeight = '100%';
				$RTEPaddingRight = '0';
				$editorWrapWidth = '100%';
			} else {
				$RTEWidth = isset($GLOBALS['BE_USER']->userTS['options.']['RTESmallWidth']) ? $GLOBALS['BE_USER']->userTS['options.']['RTESmallWidth'] : '530';
				$RTEHeight = isset($GLOBALS['BE_USER']->userTS['options.']['RTESmallHeight']) ? $GLOBALS['BE_USER']->userTS['options.']['RTESmallHeight'] : '380';
				$RTEWidth  = $RTEWidth + ($this->TCEform->docLarge ? (isset($GLOBALS['BE_USER']->userTS['options.']['RTELargeWidthIncrement']) ? $GLOBALS['BE_USER']->userTS['options.']['RTELargeWidthIncrement'] : '150') : 0);
				$RTEWidth -= ($inline->getStructureDepth() > 0 ? ($inline->getStructureDepth()+1)*$inline->getLevelMargin() : 0);
				$RTEWidthOverride = (is_object($GLOBALS['BE_USER']) && isset($GLOBALS['BE_USER']->uc['rteWidth']) && trim($GLOBALS['BE_USER']->uc['rteWidth'])) ? trim($GLOBALS['BE_USER']->uc['rteWidth']) : trim($this->thisConfig['RTEWidthOverride']);
				if ($RTEWidthOverride) {
					if (strstr($RTEWidthOverride, '%')) {
						if ($this->client['browser'] != 'msie') {
							$RTEWidth = (intval($RTEWidthOverride) > 0) ? $RTEWidthOverride : '100%';
						}
					} else {
						$RTEWidth = (intval($RTEWidthOverride) > 0) ? intval($RTEWidthOverride) : $RTEWidth;
					}
				}
				$RTEWidth = strstr($RTEWidth, '%') ? $RTEWidth :  $RTEWidth . 'px';
				$RTEHeight = $RTEHeight + ($this->TCEform->docLarge ?  (isset($GLOBALS['BE_USER']->userTS['options.']['RTELargeHeightIncrement']) ? $GLOBALS['BE_USER']->userTS['options.']['RTELargeHeightIncrement'] : 0) : 0);
				$RTEHeightOverride = (is_object($GLOBALS['BE_USER']) && isset($GLOBALS['BE_USER']->uc['rteHeight']) && intval($GLOBALS['BE_USER']->uc['rteHeight'])) ? intval($GLOBALS['BE_USER']->uc['rteHeight']) : intval($this->thisConfig['RTEHeightOverride']);
				$RTEHeight = ($RTEHeightOverride > 0) ? $RTEHeightOverride : $RTEHeight;
				$RTEPaddingRight = '2px';
				$editorWrapWidth = '99%';
			}
			$editorWrapHeight = '100%';
			$this->RTEdivStyle = 'position:relative; left:0px; top:0px; height:' . $RTEHeight . 'px; width:' . $RTEWidth . '; border: 1px solid black; padding: 2px ' . $RTEPaddingRight . ' 2px 2px;';
			/* =======================================
			 * LOAD CSS AND JAVASCRIPT
			 * =======================================
			 */
				// Preloading the pageStyle and including RTE skin stylesheets
			$this->addPageStyle();
			$this->addSkin();
				// Re-initialize the scripts array so that only the cumulative set of plugins of the last RTE on the page is used
			$this->cumulativeScripts[$this->TCEform->RTEcounter] = array();
			$this->includeScriptFiles($this->TCEform->RTEcounter);
			$this->buildJSMainLangFile($this->TCEform->RTEcounter);
				// Register RTE in JS:
			$this->TCEform->additionalJS_post[] = $this->registerRTEinJS($this->TCEform->RTEcounter, $table, $row['uid'], $field, $textAreaId);
				// Set the save option for the RTE:
			$this->TCEform->additionalJS_submit[] = $this->setSaveRTE($this->TCEform->RTEcounter, $this->TCEform->formName, $textAreaId, $PA['itemFormElName']);
			$this->TCEform->additionalJS_delete[] = $this->setDeleteRTE($this->TCEform->RTEcounter, $this->TCEform->formName, $textAreaId);
				// Loading JavaScript files and code
			if ($this->TCEform->RTEcounter == 1) {
				$this->TCEform->additionalJS_pre['rtehtmlarea-loadJScode'] = $this->loadJScode($this->TCEform->RTEcounter);
			}
			$this->TCEform->additionalCode_pre['rtehtmlarea-loadJSfiles'] = $this->loadJSfiles($this->TCEform->RTEcounter);
			$pageRenderer = $GLOBALS['SOBE']->doc->getPageRenderer();
			$pageRenderer->enableExtJSQuickTips();
			if (!$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->ID]['enableCompressedScripts']) {
				$pageRenderer->enableExtJsDebug();
			}
			$pageRenderer->addJsFile('../t3lib/js/extjs/notifications.js');
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
				// Draw the textarea
			$visibility = 'hidden';
			$item = $this->triggerField($PA['itemFormElName']).'
				<div id="pleasewait' . $textAreaId . '" class="pleasewait" style="display: block;" >' . $LANG->getLL('Please wait') . '</div>
				<div id="editorWrap' . $textAreaId . '" class="editorWrap" style="visibility: hidden; width:' . $editorWrapWidth . '; height:' . $editorWrapHeight . ';">
				<textarea id="RTEarea' . $textAreaId . '" name="'.htmlspecialchars($PA['itemFormElName']).'" rows="0" cols="0" style="'.t3lib_div::deHSCentities(htmlspecialchars($this->RTEdivStyle)).'">'.t3lib_div::formatForTextarea($value).'</textarea>
				</div>' . LF;
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
		$this->editorCSS = $this->getFullFileName($skinFilename);
		$skinDir = dirname($this->editorCSS);
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
					$key = $this->registeredPlugins[$pluginId]->getExtensionKey();
					$this->addStyleSheet(
						'rtehtmlarea-plugin-' . $pluginId . '-skin',
						($this->is_FE() ? t3lib_extMgm::siteRelPath($key) : $this->backPath . t3lib_extMgm::extRelPath($key)) . $pathToSkin
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
			$pageRenderer = $GLOBALS['SOBE']->doc->getPageRenderer();
			$pageRenderer->addCssFile($href, $relation, 'screen', $title);
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
		if ($this->client['browser'] == 'msie' || $this->client['browser'] == 'opera') {
			$this->thisConfig['keepButtonGroupTogether'] = 0;
		}

		$this->defaultToolbarOrder = 'bar, blockstylelabel, blockstyle, space, textstylelabel, textstyle, linebreak,
			bar, formattext, bold,  strong, italic, emphasis, big, small, insertedtext, deletedtext, citation, code, definition, keyboard, monospaced, quotation, sample, variable, bidioverride, strikethrough, subscript, superscript, underline, span,
			bar, fontstyle, space, fontsize, bar, formatblock, insertparagraphbefore, insertparagraphafter, blockquote, line,
			bar, left, center, right, justifyfull,
			bar, orderedlist, unorderedlist, definitionlist, definitionitem, outdent, indent,  bar, lefttoright, righttoleft, language, showlanguagemarks,
			bar, textcolor, bgcolor, textindicator,
			bar, editelement, emoticon, insertcharacter, link, unlink, image, table,' . (($this->thisConfig['hideTableOperationsInToolbar'] && is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['toggleborders.']) && $this->thisConfig['buttons.']['toggleborders.']['keepInToolbar']) ? ' toggleborders,': '') . ' user, acronym, bar, findreplace, spellcheck,
			bar, chMode, inserttag, removeformat, bar, copy, cut, paste, pastetoggle, pastebehaviour, bar, undo, redo, bar, showhelp, about, linebreak,
			' . ($this->thisConfig['hideTableOperationsInToolbar'] ? '': 'bar, toggleborders,') . ' bar, tableproperties, tablerestyle, bar, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit, bar,
			columnproperties, columninsertbefore, columninsertafter, columndelete, columnsplit, bar,
			cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge';

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
		if(is_object($GLOBALS['BE_USER'])) {
			$RTEkeyList = isset($GLOBALS['BE_USER']->userTS['options.']['RTEkeyList']) ? $GLOBALS['BE_USER']->userTS['options.']['RTEkeyList'] : '*';
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
						$showPlugin = TRUE;
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
	 * Return the HTML code for loading the Javascript files
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	void
 	 */
	protected function includeScriptFiles($RTEcounter) {
		$this->writeTemporaryFile('EXT:' . $this->ID . '/htmlarea/htmlarea.js', 'htmlarea', 'js', '', TRUE);
		if ($this->client['browser'] == 'msie') {
			$this->writeTemporaryFile('EXT:' . $this->ID . '/htmlarea/htmlarea-ie.js', 'htmlarea-ie', 'js', '', TRUE);
		} else {
			$this->writeTemporaryFile('EXT:' . $this->ID . '/htmlarea/htmlarea-gecko.js', 'htmlarea-gecko', 'js', '', TRUE);
		}
		foreach ($this->pluginEnabledCumulativeArray[$RTEcounter] as $pluginId) {
			$extensionKey = is_object($this->registeredPlugins[$pluginId]) ? $this->registeredPlugins[$pluginId]->getExtensionKey() : $this->ID;
			$this->writeTemporaryFile('EXT:' . $extensionKey . '/htmlarea/plugins/' . $pluginId . '/' . strtolower(preg_replace('/([a-z])([A-Z])([a-z])/', "$1".'-'."$2"."$3", $pluginId)) . '.js', $pluginId, 'js', '', TRUE);
		}
	}
	/**
	 * Return the HTML code for loading the Javascript files
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		the html code for loading the Javascript Files
 	 */
	protected function loadJSfiles($RTEcounter) {
		$loadJavascriptCode = '<script type="text/javascript" src="' . $this->doConcatenate($RTEcounter) . '"></script>' . LF;
		$loadJavascriptCode .= t3lib_div::wrapJS('
			if (typeof(RTEarea) == "undefined") {
				RTEarea = new Object();
				RTEarea[0] = new Object();
				RTEarea[0].version = "' . $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->ID]['version'] . '";
				RTEarea[0].editorUrl = "' . $this->extHttpPath . 'htmlarea/";
				RTEarea[0].editorCSS = "' . t3lib_div::createVersionNumberedFilename($this->editorCSS) . '";
				RTEarea[0].editorSkin = "' . dirname($this->editorCSS) . '/";
				RTEarea[0].editedContentCSS = "' . t3lib_div::createVersionNumberedFilename($this->editedContentCSS)  . '";
				RTEarea[0].hostUrl = "' . $this->hostURL . '";
				RTEarea.init = function() {
					if (typeof(HTMLArea) == "undefined" || !Ext.isReady) {
						window.setTimeout("RTEarea.init();", 40);
					} else {
						Ext.QuickTips.init();
						HTMLArea.init();
					}
				};
				RTEarea.initEditor = function(editorNumber) {
					if (typeof(HTMLArea) == "undefined") {
						RTEarea.initEditor.defer(40, null, [editorNumber]);
					} else {
						HTMLArea.initEditor(editorNumber);
					}
				};
			}'
		);
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
		return TAB . 'RTEarea.init();';
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
		$configureRTEInJavascriptString = '
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
			RTEarea[editornumber].RTEWidthOverride = "' . ((is_object($GLOBALS['BE_USER']) && isset($GLOBALS['BE_USER']->uc['rteWidth']) && trim($GLOBALS['BE_USER']->uc['rteWidth'])) ? trim($GLOBALS['BE_USER']->uc['rteWidth']) : trim($this->thisConfig['RTEWidthOverride'])) . '";
			RTEarea[editornumber].RTEHeightOverride = "' . ((is_object($GLOBALS['BE_USER']) && isset($GLOBALS['BE_USER']->uc['rteHeight']) && intval($GLOBALS['BE_USER']->uc['rteHeight'])) ? intval($GLOBALS['BE_USER']->uc['rteHeight']) : intval($this->thisConfig['RTEHeightOverride'])) . '";
			RTEarea[editornumber].resizable = ' . ((is_object($GLOBALS['BE_USER']) && isset($GLOBALS['BE_USER']->uc['rteResize']) && $GLOBALS['BE_USER']->uc['rteResize']) ? 'true' : (trim($this->thisConfig['rteResize']) ? 'true' : 'false')) . ';
			RTEarea[editornumber].maxHeight = "' . ((is_object($GLOBALS['BE_USER']) && isset($GLOBALS['BE_USER']->uc['rteMaxHeight']) && intval($GLOBALS['BE_USER']->uc['rteMaxHeight'])) ? trim($GLOBALS['BE_USER']->uc['rteMaxHeight']) : (intval($this->thisConfig['rteMaxHeight']) ? intval($this->thisConfig['rteMaxHeight']) : '2000')) . '";
			RTEarea[editornumber].fullScreen = ' . ($this->fullScreen ? 'true' : 'false') . ';
			RTEarea[editornumber].showStatusBar = ' . (trim($this->thisConfig['showStatusBar'])?'true':'false') . ';
			RTEarea[editornumber].enableWordClean = ' . (trim($this->thisConfig['enableWordClean'])?'true':'false') . ';
			RTEarea[editornumber].htmlRemoveComments = ' . (trim($this->thisConfig['removeComments'])?'true':'false') . ';
			RTEarea[editornumber].disableEnterParagraphs = ' . (trim($this->thisConfig['disableEnterParagraphs'])?'true':'false') . ';
			RTEarea[editornumber].disableObjectResizing = ' . (trim($this->thisConfig['disableObjectResizing'])?'true':'false') . ';
			RTEarea[editornumber].removeTrailingBR = ' . (trim($this->thisConfig['removeTrailingBR'])?'true':'false') . ';
			RTEarea[editornumber].useCSS = ' . (trim($this->thisConfig['useCSS'])?'true':'false') . ';
			RTEarea[editornumber].keepButtonGroupTogether = ' . (trim($this->thisConfig['keepButtonGroupTogether'])?'true':'false') . ';
			RTEarea[editornumber].disablePCexamples = ' . (trim($this->thisConfig['disablePCexamples'])?'true':'false') . ';
			RTEarea[editornumber].showTagFreeClasses = ' . (trim($this->thisConfig['showTagFreeClasses'])?'true':'false') . ';
			RTEarea[editornumber].useHTTPS = ' . ((trim(stristr($this->siteURL, 'https')) || $this->thisConfig['forceHTTPS'])?'true':'false') . ';
			RTEarea[editornumber].tceformsNested = ' . (is_object($this->TCEform) && method_exists($this->TCEform, 'getDynNestedStack') ? $this->TCEform->getDynNestedStack(TRUE) : '[]') . ';
			RTEarea[editornumber].dialogueWindows = new Object();';
		if (isset($this->thisConfig['dialogueWindows.']['defaultPositionFromTop'])) {
			$configureRTEInJavascriptString .= '
			RTEarea[editornumber].dialogueWindows.positionFromTop = ' . intval($this->thisConfig['dialogueWindows.']['defaultPositionFromTop']) . ';';
		}
		if (isset($this->thisConfig['dialogueWindows.']['defaultPositionFromLeft'])) {
			$configureRTEInJavascriptString .= '
			RTEarea[editornumber].dialogueWindows.positionFromLeft = ' . intval($this->thisConfig['dialogueWindows.']['defaultPositionFromLeft']) . ';';
		}
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
				if (is_array($conf)) {
					$configureRTEInJavascriptString .= '
			RTEarea[editornumber].buttons.'.$button.' = ' . $this->buildNestedJSArray($conf) . ';';
				}
			}
		}

			// Setting the list of tags to be removed if specified in the RTE config
		if (trim($this->thisConfig['removeTags']))  {
			$configureRTEInJavascriptString .= '
			RTEarea[editornumber].htmlRemoveTags = /^(' . implode('|', t3lib_div::trimExplode(',', $this->thisConfig['removeTags'], 1)) . ')$/i;';
		}

			// Setting the list of tags to be removed with their contents if specified in the RTE config
		if (trim($this->thisConfig['removeTagsAndContents']))  {
			$configureRTEInJavascriptString .= '
			RTEarea[editornumber].htmlRemoveTagsAndContents = /^(' . implode('|', t3lib_div::trimExplode(',', $this->thisConfig['removeTagsAndContents'], 1)) . ')$/i;';
		}
			// Process default style configuration
		$configureRTEInJavascriptString .= '
			RTEarea[editornumber].defaultPageStyle = "' . $this->writeTemporaryFile('', 'defaultPageStyle', 'css', $this->buildStyleSheet()) . '";';
			// Setting the pageStyle
		$filename = trim($this->thisConfig['contentCSS']) ? trim($this->thisConfig['contentCSS']) : 'EXT:' . $this->ID . '/res/contentcss/default.css';
		$configureRTEInJavascriptString .= '
			RTEarea[editornumber].pageStyle = "' . t3lib_div::createVersionNumberedFilename($this->getFullFileName($filename)) .'";';
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
			configureEditorInstance["' . $textAreaId . '"]();';
		return $configureRTEInJavascriptString;
	}

	/**
	 * Return TRUE, if the plugin can be loaded
	 *
	 * @param	string		$pluginId: The identification string of the plugin
	 *
	 * @return	boolean		TRUE if the plugin can be loaded
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
				$addElementCode .= strToLower($elListName) . ' {' . $elValue . '}' . LF;
			}

			$stylesheet = $this->thisConfig['mainStyleOverride'] ? $this->thisConfig['mainStyleOverride'] : LF .
				'body.htmlarea-content-body { font-family: ' . $mainStyle_font .
					'; font-size: '.($this->thisConfig['mainStyle_size'] ? $this->thisConfig['mainStyle_size'] : '12px') .
					'; color: '.($this->thisConfig['mainStyle_color']?$this->thisConfig['mainStyle_color'] : 'black') .
					'; background-color: '.($this->thisConfig['mainStyle_bgcolor'] ? $this->thisConfig['mainStyle_bgcolor'] : 'white') .
					';'.$this->thisConfig['mainStyleOverride_add.']['BODY'].'}' . LF .
				'td { ' . $this->thisConfig['mainStyleOverride_add.']['TD'].'}' . LF .
				'div { ' . $this->thisConfig['mainStyleOverride_add.']['DIV'].'}' . LF .
				'pre { ' . $this->thisConfig['mainStyleOverride_add.']['PRE'].'}' . LF .
				'ol { ' . $this->thisConfig['mainStyleOverride_add.']['OL'].'}' . LF .
				'ul { ' . $this->thisConfig['mainStyleOverride_add.']['UL'].'}' . LF .
				'blockquote { ' . $this->thisConfig['mainStyleOverride_add.']['BLOCKQUOTE'].'}' . LF .
				$addElementCode;

			if (is_array($this->thisConfig['inlineStyle.']))        {
				$stylesheet .= LF . implode(LF, $this->thisConfig['inlineStyle.']) . LF;
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
			RTEarea[editornumber].classesTag = new Object();';
		foreach ($classesTagArray as $classesTagName) {
			$HTMLAreaJSClasses = ($this->thisConfig[$classesTagName])?('"' . $this->cleanList($this->thisConfig[$classesTagName]) . '";'):'null;';
			$configureRTEInJavascriptString .= '
			RTEarea[editornumber].classesTag.'. $classesTagConvert[$classesTagName] .' = '. $HTMLAreaJSClasses;
		}
			// Include JS arrays of configured classes
		$configureRTEInJavascriptString .= '
			RTEarea[editornumber].classesUrl = "' . $this->writeTemporaryFile('', 'classes_' . $this->language, 'js', $this->buildJSClassesArray(), TRUE) . '";';
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
			$JSClassesArray .= 'HTMLArea.classes' . ucfirst($key) . ' = ' . $this->buildNestedJSArray($subArray) . ';' . LF;
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
		$JSLanguageArray = 'HTMLArea.I18N = new Object();' . LF;
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
		$JSLanguageArray .= 'HTMLArea.I18N = ' . json_encode($labelsArray) . ';' . LF;
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
	public function writeTemporaryFile($sourceFileName='', $label, $fileExtension='js', $contents='', $concatenate = FALSE) {
		global $TYPO3_CONF_VARS;

		if ($sourceFileName) {
			$output = '';
			$source = t3lib_div::getFileAbsFileName($sourceFileName);
			$output = file_get_contents($source);
		} else {
			$output = $contents;
		}
		$compress = $TYPO3_CONF_VARS['EXTCONF'][$this->ID]['enableCompressedScripts'] && ($fileExtension == 'js') && ($output != '');
		$relativeFilename = 'typo3temp/' . $this->ID . '/' . str_replace('-','_',$label) . '_' . t3lib_div::shortMD5(($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['version'] . ($sourceFileName ? $sourceFileName : $output)), 20) . ($compress ? '_compressed' : '') . '.' . $fileExtension;
		$destination = PATH_site . $relativeFilename;
		if(!file_exists($destination)) {
			$compressedJavaScript = '';
			if ($compress && $fileExtension == 'js') {
				$compressedJavaScript = t3lib_div::minifyJavaScript($output);
			}
			$failure = t3lib_div::writeFileToTypo3tempDir($destination, $compressedJavaScript ? $compressedJavaScript : $output);
			if ($failure)  {
				throw new RuntimeException($failure, 1294585668);
			}
		}
		if ($concatenate && $fileExtension == 'js') {
			$this->cumulativeScripts[$this->TCEform->RTEcounter][] = $destination;
		}
		if ($this->is_FE()) {
			$filename =  ($GLOBALS['TSFE']->absRefPrefix ? $GLOBALS['TSFE']->absRefPrefix : '') . t3lib_div::createVersionNumberedFilename($relativeFilename);
		} else {
			$filename = t3lib_div::createVersionNumberedFilename(($this->isFrontendEditActive() ? '' : ($this->backPath . '../')) . $relativeFilename);
		}
		return t3lib_div::resolveBackPath($filename);
	}
	/**
	 * Concatenates all accumulated scripts in a file in typo3temp/rtehtmlarea directory and returns the file name
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		The name of the file writtten to typo3temp/rtehtmlarea
	 */
	protected function doConcatenate($RTEcounter) {
		$fileExtension = 'js';
		$compress = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->ID]['enableCompressedScripts'];
		$token = implode('|', $this->cumulativeScripts[$RTEcounter]);
		$relativeFilename = 'typo3temp/' . $this->ID . '/' . 'htmlarea_cumulative' . '_' . t3lib_div::shortMD5(($TYPO3_CONF_VARS['EXTCONF'][$this->ID]['version'] . $token ), 20) . ($compress ? '_compressed' : '') . '.' . $fileExtension;
		$destination = PATH_site . $relativeFilename;
		if (!file_exists($destination)) {
			foreach ($this->cumulativeScripts[$RTEcounter] as $fileName) {
				$contents = file_get_contents($fileName);
				if (!file_exists($destination)) {
					$failure = t3lib_div::writeFileToTypo3tempDir($destination, $contents);
					if ($failure)  {
						throw new RuntimeException($failure, 1294585669);
					}
				} else {
					$success = file_put_contents($destination, $contents, FILE_APPEND);
					if (!$success)  {
						throw new RuntimeException('Could not append script: ' . $fileName, 1294585670);
					}
				}
			}
		}
		if ($this->is_FE()) {
			$filename = ($GLOBALS['TSFE']->absRefPrefix ? $GLOBALS['TSFE']->absRefPrefix : '') . t3lib_div::createVersionNumberedFilename($relativeFilename);
		} else {
			if ($compress) {
				$compressor = t3lib_div::makeInstance('t3lib_Compressor');
				$filename = $compressor->compressJsFile(($this->isFrontendEditActive() ? '' : $this->backPath) . '../' . $relativeFilename);
				if ($this->isFrontendEditActive()) {
					$filename = preg_replace('/^..\//', '', $filename);
				}
			} else {
				$filename = t3lib_div::createVersionNumberedFilename(($this->isFrontendEditActive() ? '' : ($this->backPath . '../')) . $relativeFilename);
			}
		}
		return t3lib_div::resolveBackPath($filename);
	}

	/**
	 * Return a file name containing the main JS language array for HTMLArea
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		filename
	 */
	function buildJSMainLangFile($RTEcounter) {
		$contents = $this->buildJSMainLangArray() . LF;
		foreach ($this->pluginEnabledCumulativeArray[$RTEcounter] as $pluginId) {
			$contents .= $this->buildJSLangArray($pluginId) . LF;
		}
		return $this->writeTemporaryFile('', $this->language.'_'.$this->OutputCharset, 'js', $contents, TRUE);
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
		$linebreak = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->ID]['enableCompressedScripts'] ? '' : LF;
		$JSLanguageArray = 'HTMLArea.I18N["' . $plugin . '"] = new Object();' . $linebreak;
		if (is_array($LOCAL_LANG)) {
			if (!empty($LOCAL_LANG[$this->language])) {
				$LOCAL_LANG[$this->language] = t3lib_div::array_merge_recursive_overrule($LOCAL_LANG['default'],$LOCAL_LANG[$this->language]);
			} else {
				$LOCAL_LANG[$this->language] = $LOCAL_LANG['default'];
			}
			$JSLanguageArray .= 'HTMLArea.I18N["' . $plugin . '"] = ' . json_encode($LOCAL_LANG[$this->language]) . ';'. LF;
		}
		return $JSLanguageArray;
	}

	/**
	 * Return the JS code of the toolbar configuration for the HTMLArea editor
	 *
	 * @return string	the JS code as nested JS arrays
	 */
	protected function getJSToolbarArray() {
			// The toolbar array
		$toolbar = array();
			// The current row;  a "linebreak" ends the current row
		$row = array();
			// The current group; each group is between "bar"s; a "linebreak" ends the current group
		$group = array();
			// Process each toolbar item in the toolbar order list
		foreach ($this->toolbarOrderArray as $item) {
			switch ($item) {
				case 'linebreak':
						// Add row to toolbar if not empty
					if (!empty($group)) {
						$row[] = $group;
						$group = array();
					}
					if (!empty($row)) {
						$toolbar[] = $row;
						$row = array();
					}
					break;
				case 'bar':
						// Add group to row if not empty
					if (!empty($group)) {
						$row[] = $group;
						$group = array();
					}
					break;
				case 'space':
					if (end($group) != $this->convertToolbarForHTMLArea($item)) {
						$group[] = $this->convertToolbarForHTMLArea($item);
					}
					break;
				default:
					if (in_array($item, $this->toolbar)) {
							// Add the item to the group
						$convertedItem = $this->convertToolbarForHTMLArea($item);
						if ($convertedItem) {
							$group[] = $convertedItem;
						}
					}
					break;
			}
		}
			// Add the last group and last line, if not empty
		if (!empty($group)) {
			$row[] = $group;
		}
		if (!empty($row)) {
			$toolbar[] = $row;
		}
		return json_encode($toolbar);
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
				$newFilename = (($this->is_FE() || $this->isFrontendEditActive()) ? t3lib_extMgm::siteRelPath($extKey) : $this->backPath . t3lib_extMgm::extRelPath($extKey)) . $local;
			}
		} elseif (substr($filename,0,1) != '/') {
			$newFilename = (($this->is_FE() || $this->isFrontendEditActive()) ? '' : ($this->backPath . '../')) . $filename;
		} else {
			$newFilename = (($this->is_FE() || $this->isFrontendEditActive()) ? '' : ($this->backPath . '../')) . substr($filename, 1);
		}
		return  t3lib_div::resolveBackPath($newFilename);
	}

	/**
	 * Return the Javascript code for copying the HTML code from the editor into the hidden input field.
	 * This is for submit function of the form.
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 * @param	string		$formName: the name of the form
	 * @param	string		$textareaId: the id of the textarea
	 * @param	string		$textareaName: the name of the textarea
	 *
	 * @return	string		Javascript code
	 */
	function setSaveRTE($RTEcounter, $formName, $textareaId, $textareaName) {
		return 'if (RTEarea["' . $textareaId . '"]) { document.' . $formName . '["' . $textareaName . '"].value = RTEarea["' . $textareaId . '"].editor.getHTML(); } else { OK = 0; };';
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
	 * Return TRUE if we are in the FE, but not in the FE editing feature of BE.
	 *
	 * @return boolean
	 */

	function is_FE() {
		return is_object($GLOBALS['TSFE']) && !$this->isFrontendEditActive() && TYPO3_MODE=='FE';
	}

	/**
	 * Checks whether frontend editing is active.
	 *
	 * @return		boolean
	 */
	public function isFrontendEditActive() {
		return is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->beUserLogin && ($GLOBALS['BE_USER']->frontendEdit instanceof t3lib_frontendedit);
	}
	/**
	 * Client Browser Information
	 *
	 * @param	string		$userAgent: The useragent string, t3lib_div::getIndpEnv('HTTP_USER_AGENT')
	 * @return	array		Contains keys "useragent", "browser", "version", "system"
	 *					where "browser" is limited to the engines
	 *					and where "version" is a floating number
	 */
	function clientInfo ($userAgent='') {
		if (!$userAgent) {
			$userAgent = t3lib_div::getIndpEnv('HTTP_USER_AGENT');
		}
		$browserInfo = t3lib_utility_Client::getBrowserInfo($userAgent);
			// Known engines: order is not irrelevant!
		$knownEngines = array('opera', 'msie', 'gecko', 'webkit');
		if (is_array($browserInfo['all'])) {
			foreach ($knownEngines as $engine) {
				if ($browserInfo['all'][$engine]) {
					$browserInfo['browser'] = $engine;
					$browserInfo['version'] = t3lib_utility_Client::getVersion($browserInfo['all'][$engine]);
					break;
				}
			}
		}
		return $browserInfo;
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
if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/class.tx_rtehtmlarea_base.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/class.tx_rtehtmlarea_base.php']);
}
?>