<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * @author Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 *
 * $Id$  *
 */

require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlarea_base.php');

class tx_rtehtmlarea_pi2 extends tx_rtehtmlarea_base {

		// External:
	var $RTEdivStyle;				// Alternative style for RTE <div> tag.
	var $extHttpPath;				// full Path to this extension for http (so no Server path). It ends with "/"

		// For the editor
	var $elementId;
	var $elementParts;
	var $tscPID;
	var $typeVal;
	var $thePid;
	var $RTEsetup;
	var $thisConfig;
	var $confValues;
	var $language;
	var $spellCheckerLanguage;
	var $spellCheckerCharset;
	var $spellCheckerMode;
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
	function drawRTE(&$pObj,$table,$field,$row,$PA,$specConf,$thisConfig,$RTEtypeVal,$RTErelPath,$thePidValue) {
			//call $this->transformContent
			//call $this->triggerField
                $this->TCEform = $pObj;
		$this->client = $this->clientInfo();
		$this->typoVersion = t3lib_div::int_from_ver($GLOBALS['TYPO_VERSION']);

		/* =======================================
		 * INIT THE EDITOR-SETTINGS
		 * =======================================
		 */

			// first get the http-path to typo3:
		$this->httpTypo3Path = substr( substr( t3lib_div::getIndpEnv('TYPO3_SITE_URL'), strlen( t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') ) ), 0, -1 );
		if (strlen($this->httpTypo3Path) == 1) {
			$this->httpTypo3Path = "/";
		} else {
			$this->httpTypo3Path .= "/";
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

		unset($this->RTEsetup);
		$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
		$this->RTEsetup = $pageTSConfig['RTE.'];
		$this->thisConfig = $this->RTEsetup['default.'];
		$this->thisConfig = $this->thisConfig['FE.'];

			// Special configuration (line) and default extras:
		$this->specConf = $specConf;
		
			// Language
		$GLOBALS['TSFE']->initLLvars();
		$this->language = $GLOBALS['TSFE']->lang;
		if($this->typoVersion >= 3008000 ) {
			$this->LOCAL_LANG = t3lib_div::readLLfile('EXT:' . $this->ID . '/locallang.xml', $this->language);
		} else {
			$this->LOCAL_LANG = $this->readLLfile('EXT:' . $this->ID . '/locallang.xml', $this->language);
		}
		if ($this->language=='default' || !$this->language)	{
			$this->language='en';
		}
			// Character set
		$this->charset = $GLOBALS['TSFE']->labelsCharset;
		if($this->typoVersion >= 3007000 ) {
			$this->OutputCharset  = $GLOBALS['TSFE']->metaCharset ? $GLOBALS['TSFE']->metaCharset : $GLOBALS['TSFE']->renderCharset;
		} else {
			$renderCharset = $GLOBALS['TSFE']->csConvObj->parse_charset($GLOBALS['TSFE']->config['config']['renderCharset'] ? $GLOBALS['TSFE']->config['config']['renderCharset'] : ($GLOBALS['TSFE']->TYPO3_CONF_VARS['BE']['forceCharset'] ? $GLOBALS['TSFE']->TYPO3_CONF_VARS['BE']['forceCharset'] : $GLOBALS['TSFE']->defaultCharSet));    // REndering charset of HTML page.
			$metaCharset = $GLOBALS['TSFE']->csConvObj->parse_charset($GLOBALS['TSFE']->config['config']['metaCharset'] ? $GLOBALS['TSFE']->config['config']['metaCharset'] : $renderCharset);
			$this->OutputCharset  = $metaCharset ? $metaCharset : $renderCharset;
		}

		/* =======================================
		 * TOOLBAR CONFIGURATION
		 * =======================================
		 */
			// htmlArea plugins list
		$this->pluginEnableArray = array_intersect(t3lib_div::trimExplode(',', $this->pluginList , 1), t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->ID]['HTMLAreaPluginList'], 1));
		$hidePlugins = array('TYPO3Browsers', 'UserElements', 'Acronym');
		if ($this->client['BROWSER'] == 'opera') {
			$hidePlugins[] = 'ContextMenu';
			$this->thisConfig['hideTableOperationsInToolbar'] = 0;
		}
		if(!t3lib_extMgm::isLoaded('sr_static_info') || in_array($this->language, t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->ID]['noSpellCheckLanguages']))) $hidePlugins[] = 'SpellChecker';
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

		if( $this->isPluginEnable('SpellChecker') ) {
				// Set the language of the content for the SpellChecker
			$this->spellCheckerLanguage = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['defaultDictionary'];
			if($row['sys_language_uid']) {
				$tableA = 'sys_language';
				$tableB = 'static_languages';
				$languagesUidsList = $row['sys_language_uid'];
				$selectFields = $tableA . '.uid,' . $tableB . '.lg_iso_2,' . $tableB . '.lg_country_iso_2,' . $tableB . '.lg_typo3';
				$table = $tableA . ' LEFT JOIN ' . $tableB . ' ON ' . $tableA . '.static_lang_isocode=' . $tableB . '.uid';
				$whereClause = $tableA . '.uid IN (' . $languagesUidsList . ') ';
				$whereClause .= $GLOBALS['TSFE']->cObj->enableFields($tableA);
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($selectFields, $table, $whereClause);
				while ( $languageRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) ) {
					$this->spellCheckerLanguage = strtolower(trim($languageRow['lg_iso_2']).(trim($languageRow['lg_country_iso_2'])?'_'.trim($languageRow['lg_country_iso_2']):''));
					$this->spellCheckerTypo3Language = strtolower(trim($languageRow['lg_typo3']));
				}
			}
			$this->spellCheckerLanguage = $this->spellCheckerLanguage?$this->spellCheckerLanguage:$this->language;
			$this->spellCheckerTypo3Language = $this->spellCheckerTypo3Language?$this->spellCheckerTypo3Language:$GLOBALS['TSFE']->lang;
			if ($this->spellCheckerTypo3Language=='default') {
				$this->spellCheckerTypo3Language='en';
			}

				// Set the charset of the content for the SpellChecker
			$this->spellCheckerCharset = $GLOBALS['TSFE']->csConvObj->charSetArray[$this->spellCheckerTypo3Language];
			$this->spellCheckerCharset = $this->spellCheckerCharset ? $this->spellCheckerCharset : 'iso-8859-1';
			$this->spellCheckerCharset = trim($GLOBALS['TSFE']->config['config']['metaCharset']) ? trim($GLOBALS['TSFE']->config['config']['metaCharset']) : $this->spellCheckerCharset;

				// Set the SpellChecker mode
			$this->spellCheckerMode = isset($this->thisConfig['HTMLAreaPspellMode']) ? trim($this->thisConfig['HTMLAreaPspellMode']) : 'normal';
			if( !in_array($this->spellCheckerMode, $this->spellCheckerModes)) {
				$this->spellCheckerMode = 'normal';
			}
		}

		if( $this->isPluginEnable('QuickTag') && trim($this->thisConfig['hideTags'])) {
			$this->quickTagHideTags = implode(',', t3lib_div::trimExplode(',', $this->thisConfig['hideTags'], 1));
		}

		/* =======================================
		 * SET STYLES
		 * =======================================
		 */

		$RTEWidth = 460+($pObj->docLarge ? 150 : 0);
		$RTEHeight = 380;
		$editorWrapWidth = $RTEWidth . 'px';
		$editorWrapHeight = $RTEHeight . 'px';
		$this->RTEdivStyle = $this->RTEdivStyle ? $this->RTEdivStyle : 'position:relative; left:0px; top:0px; height:' . $RTEHeight . 'px; width:'.$RTEWidth.'px; border: 1px solid black;';
		$this->toolbar_level_size = $RTEWidth;

		/* =======================================
		 * LOAD JS, CSS and more
		 * =======================================
		 */
			// Preloading the pageStyle
		$filename = trim($this->thisConfig['contentCSS']) ? trim($this->thisConfig['contentCSS']) : 'EXT:' . $this->ID . '/htmlarea/plugins/DynamicCSS/dynamiccss.css';
		$additionalCode_loadCSS = '
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
				$skinFilename = $this->httpTypo3Path . t3lib_extMgm::siteRelPath($extKey).$local;
				$skinDir = $this->siteURL . t3lib_extMgm::siteRelPath($extKey) . dirname($local);
			}
		} elseif (substr($skinFilename,0,1) != '/') {
			$skinDir = $this->siteURL.dirname($skinFilename);
			$skinFilename = $this->siteURL.$skinFilename;
		} else {
			$skinDir = substr($this->siteURL,0,-1) . dirname($skinFilename);
		}

		$this->editorCSS = $skinFilename;
		$this->editedContentCSS = $skinDir . '/htmlarea-edited-content.css';
		$additionalCode_loadCSS .= '
		<link rel="alternate stylesheet" type="text/css" href="' . $this->editedContentCSS . '" />';
		$additionalCode_loadCSS .= '
		<link rel="stylesheet" type="text/css" href="' . $this->editorCSS . '" />';

			// Loading CSS, JavaScript files and code
		$GLOBALS['TSFE']->additionalHeaderData['htmlArea'] = $additionalCode_loadCSS . $this->loadJSfiles($pObj->RTEcounter) . '<script type="text/javascript">' . $this->loadJScode($pObj->RTEcounter) . '</script>'; 

		/* =======================================
		 * DRAW THE EDITOR
		 * =======================================
		 */
			// Transform value:
		$value = $this->transformContent('rte',$PA['itemFormElValue'],$table,$field,$row,$specConf,$thisConfig,$RTErelPath,$thePidValue);
		if ($this->client['BROWSER'] == 'gecko') {
				// change <strong> to <b>
			$value = preg_replace("/<(\/?)strong>/i", "<$1b>", $value);
				// change <em> to <i>
			$value = preg_replace("/<(\/?)em>/i", "<$1i>", $value);
		}

			// Register RTE windows:
		$pObj->RTEwindows[] = $PA['itemFormElName'];
			
			// Register RTE in JS:
		$pObj->additionalJS_post[] = $this->registerRTEinJS($pObj->RTEcounter);

			// Set the save option for the RTE:
		$pObj->additionalJS_submit[] = $this->setSaveRTE($pObj->RTEcounter, $pObj->formName, htmlspecialchars($PA['itemFormElName']));

			// draw the textarea
		$visibility = 'hidden';
		$item = $this->triggerField($PA['itemFormElName']).'
			<div id="pleasewait' . $pObj->RTEcounter . '" class="pleasewait">' . $GLOBALS['TSFE']->csConvObj->conv($GLOBALS['TSFE']->getLLL('Please wait',$this->LOCAL_LANG), $this->charset, $GLOBALS['TSFE']->renderCharset) . '</div>
			<div id="editorWrap' . $pObj->RTEcounter . '" class="editorWrap" style="visibility:' . $visibility . '; width:' . $editorWrapWidth . '; height:' . $editorWrapHeight . ';">
			<textarea id="RTEarea'.$pObj->RTEcounter.'" name="'.htmlspecialchars($PA['itemFormElName']).'" style="'.htmlspecialchars($this->RTEdivStyle).'">'.t3lib_div::formatForTextarea($value).'</textarea>
			</div>' . ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->ID]['enableDebugMode'] ? '<div id="HTMLAreaLog"></div>' : '') . '
			';
		return $item;
	}
	
	/**
	 * Return the JS-Code for copy the HTML-Code from the editor in the hidden input field.
	 * This is for submit function from the form.
	 *
	 * @return string		the JS-Code
	 */
	function setSaveRTE($number, $form, $textarea) {
		return '
		editornumber = '.$number.';
		if (RTEarea[editornumber]) {
			fields = document.getElementsByName(\'' . $textarea . '\');
			field = fields.item(0);
			if(field && field.tagName.toLowerCase() == \'textarea\') field.value = RTEarea[editornumber][\'editor\'].getHTML();
		}
		else {
			OK=0;
		}
		';
	}
	
	/**
	 * Split Label function for front-end applications.
	 *
	 * @param	string		Key string. Accepts the "LLL:" prefix.
	 * @return	string		Label value, if any.
	 */
	function sL($input)	{
		global $TSFE;
		if (strcmp(substr($input,0,4),'LLL:'))	{
			$t = explode('|',$input);
			return $t[$TSFE->langSplitIndex] ? $t[$TSFE->langSplitIndex] : $t[0];
		} else {
			if (!isset($TSFE->LL_labels_cache[$TSFE->lang][$input])) {	// If cached label
				$restStr = trim(substr($input,4));
				$extPrfx='';
				if (!strcmp(substr($restStr,0,4),'EXT:'))	{
					$restStr = trim(substr($restStr,4));
					$extPrfx='EXT:';
				}
				$parts = explode(':',$restStr);
				$parts[0]=$extPrfx.$parts[0];
				if (!isset($TSFE->LL_files_cache[$parts[0]]))	{	// Getting data if not cached
					$TSFE->LL_files_cache[$parts[0]] = $this->readLLfile($parts[0],$TSFE->lang);
				}
				$TSFE->LL_labels_cache[$TSFE->lang][$input] = $TSFE->csConv($TSFE->getLLL($parts[1],$TSFE->LL_files_cache[$parts[0]]));
			}
			return $TSFE->LL_labels_cache[$TSFE->lang][$input];
		}
	}
	
	function readLLfile($fileRef,$langKey)  {
		$file = t3lib_div::getFileAbsFileName($fileRef);
		if ($file)      {
			$baseFile = ereg_replace('\.(php|xml)$', '', $file);

			if (@is_file($baseFile.'.xml')) {
				$LOCAL_LANG = $this->readLLXMLfile($baseFile.'.xml', $langKey);
			} elseif (@is_file($baseFile.'.php'))   {
				include($baseFile.'.php');
			} else die('Filereference, "'.$file.'", not found!');
		}
		return is_array($LOCAL_LANG)?$LOCAL_LANG:array();
	}
	
	function readLLXMLfile($fileRef,$langKey)       {
		$csConvObj = $GLOBALS['TSFE']->csConvObj;

		if (@is_file($fileRef) && $langKey && is_object($csConvObj))    {
			
				// Set charset:
			$origCharset = $csConvObj->parse_charset($csConvObj->charSetArray[$langKey] ? $csConvObj->charSetArray[$langKey] : 'iso-8859-1');

				// Cache file name:
			$hashSource = substr($fileRef,strlen(PATH_site)).'|'.date('d-m-Y H:i:s',filemtime($fileRef));
			$cacheFileName = PATH_site.'typo3temp/llxml/'.
					substr(basename($fileRef),10,15).
					'_'.t3lib_div::shortMD5($hashSource).'.'.$langKey.'.'.$origCharset.'.cache';

				// Check if cache file exists...
			if (!@is_file($cacheFileName))  {       // ... if it doesn't, create content and write it:

					// Read XML, parse it.
				$xmlString = t3lib_div::getUrl($fileRef);
				$xmlContent = t3lib_div::xml2array($xmlString);

					// Set default LOCAL_LANG array content:
				$LOCAL_LANG = array();
				$LOCAL_LANG['default'] = $xmlContent['data']['default'];

					// Specific language, convert from utf-8 to backend language charset:
					// NOTICE: Converting from utf-8 back to "native" language may be a temporary solution until we can totally discard "locallang.php" files altogether (and use utf-8 for everything). But doing this conversion is the quickest way to migrate now and the source is in utf-8 anyway which is the main point.
				if ($langKey!='default')        {
					$LOCAL_LANG[$langKey] = $xmlContent['data'][$langKey];

						// Checking if charset should be converted.
					if (is_array($LOCAL_LANG[$langKey]) && $origCharset!='utf-8')   {
						foreach($LOCAL_LANG[$langKey] as $labelKey => $labelValue)      {
							$LOCAL_LANG[$langKey][$labelKey] = $csConvObj->utf8_decode($labelValue,$origCharset);
						}
					}
				}

					// Cache the content now:
				$serContent = array('origFile'=>$hashSource, 'LOCAL_LANG'=>$LOCAL_LANG);
				$res = t3lib_div::writeFileToTypo3tempDir($cacheFileName, serialize($serContent));
				if ($res)       die('ERROR: '.$res);
			} else {
					// Get content from cache:
				$serContent = unserialize(t3lib_div::getUrl($cacheFileName));
				$LOCAL_LANG = $serContent['LOCAL_LANG'];
			}

				// Checking for EXTERNAL file for non-default language:
			if ($langKey!='default' && is_string($LOCAL_LANG[$langKey]) && strlen($LOCAL_LANG[$langKey]))   {

					// Look for localized file:
				$localized_file = t3lib_div::getFileAbsFileName($LOCAL_LANG[$langKey]);
				if ($localized_file && @is_file($localized_file))       {

						// Cache file name:
					$hashSource = substr($localized_file,strlen(PATH_site)).'|'.date('d-m-Y H:i:s',filemtime($localized_file));
					$cacheFileName = PATH_site.'typo3temp/llxml/ext_'.
						substr(basename($localized_file),10,15).
						'_'.t3lib_div::shortMD5($hashSource).'.'.$langKey.'.'.$origCharset.'.cache';

						// Check if cache file exists...
					if (!@is_file($cacheFileName))  {       // ... if it doesn't, create content and write it:

							// Read and parse XML content:
						$local_xmlString = t3lib_div::getUrl($localized_file);
						$local_xmlContent = t3lib_div::xml2array($local_xmlString);
						$LOCAL_LANG[$langKey] = is_array($local_xmlContent['data'][$langKey]) ? $local_xmlContent['data'][$langKey] : array();

							// Checking if charset should be converted.
						if (is_array($LOCAL_LANG[$langKey]) && $origCharset!='utf-8')   {
							foreach($LOCAL_LANG[$langKey] as $labelKey => $labelValue)      {
								$LOCAL_LANG[$langKey][$labelKey] = $csConvObj->utf8_decode($labelValue,$origCharset);
							}
						}

							// Cache the content now:
						$serContent = array('extlang'=>$langKey, 'origFile'=>$LOCAL_LANG[$langKey], 'EXT_DATA'=>$LOCAL_LANG[$langKey]);
						$res = t3lib_div::writeFileToTypo3tempDir($cacheFileName, serialize($serContent));
						if ($res)       die('ERROR: '.$res);
					} else {
							// Get content from cache:
						$serContent = unserialize(t3lib_div::getUrl($cacheFileName));
						$LOCAL_LANG[$langKey] = $serContent['EXT_DATA'];
					}
				} else {
					$LOCAL_LANG[$langKey] = array();
				}
			}

			return $LOCAL_LANG;
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi2/class.tx_rtehtmlarea_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi2/class.tx_rtehtmlarea_pi2.php']);
}

?>