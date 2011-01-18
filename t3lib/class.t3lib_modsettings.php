<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Manage storing and restoring of $GLOBALS['SOBE']->MOD_SETTINGS settings.
 * Provides a presets box for BE modules.
 *
 * inspired by t3lib_fullsearch
 *
 * $Id$
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  125: class t3lib_modSettings
 *
 *			  SECTION: Init / setup
 *  181:	 function init($prefix='', $storeList='')
 *  197:	 function setSessionType($type='ses')
 *
 *			  SECTION: Store list - which values should be stored
 *  218:	 function setStoreList($storeList)
 *  231:	 function addToStoreList($storeList)
 *  245:	 function addToStoreListFromPrefix($prefix='')
 *
 *			  SECTION: Process storage array
 *  279:	 function initStorage()
 *  294:	 function cleanupStorageArray($storedSettings)
 *  316:	 function compileEntry($data)
 *  343:	 function getStoredData($storeIndex, $writeArray=array())
 *  360:	 function processStoreControl($mconfName='')
 *  442:	 function writeStoredSetting($writeArray=array(), $mconfName='')
 *
 *			  SECTION: GUI
 *  474:	 function getStoreControl($showElements='load,remove,save', $useOwnForm=TRUE)
 *
 *			  SECTION: Misc
 *  576:	 function processEntry($storageArr)
 *
 * TOTAL FUNCTIONS: 13
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * usage inside of scbase class
 *
 * ....
 *
 * $this->MOD_MENU = array(
 *	 'function' => array(
 *		 'xxx ...
 *	 ),
 *	 'tx_dam_select_storedSettings' => '',
 *
 * ....
 *
 * function main()	{
 *	 // reStore settings
 * $store = t3lib_div::makeInstance('t3lib_modSettings');
 * $store->init('tx_dam_select');
 * $store->setStoreList('tx_dam_select');
 * $store->processStoreControl();
 *
 *	 // show control panel
 * $this->content.= $this->doc->section('Settings',$store->getStoreControl(),0,1);
 *
 *
 *
 * Format of saved settings
 *
 *	$SOBE->MOD_SETTINGS[$this->prefix.'_storedSettings'] = serialize(
 *		array (
 *			'any id' => array (
 *					'title' => 'title for saved settings',
 *					'desc' => 'descritpion text, not mandatory',
 *					'data' => array(),	// data from MOD_SETTINGS
 *					'user' => NULL, // can be used for extra data used by the application to identify this entry
 *					'tstamp' => 12345, // $GLOBALS['EXEC_TIME']
 *				),
 *			'another id' => ...
 *
 *			) );
 *
 */

/**
 * Manage storing and restoring of $GLOBALS['SOBE']->MOD_SETTINGS settings.
 * Provides a presets box for BE modules.
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_modSettings {

	/**
	 * If type is set 'ses' then the module data will be stored into the session and will be lost with logout.
	 * Type 'perm' will store the data permanently.
	 */
	var $type = 'perm';

	/**
	 * prefix of MOD_SETTING array keys that should be stored
	 */
	var $prefix = '';

	/**
	 * Names of keys of the MOD_SETTING array which should be stored
	 */
	var $storeList = array();

	/**
	 * The stored settings array
	 */
	var $storedSettings = array();

	/**
	 * Message from the last storage command
	 */
	var $msg = '';


	/**
	 * Name of the form. Needed for JS
	 */
	var $formName = 'storeControl';


	var $writeDevLog = 0; // write messages into the devlog?


	/********************************
	 *
	 * Init / setup
	 *
	 ********************************/


	/**
	 * Initializes the object
	 *
	 * @param	string		Prefix of MOD_SETTING array keys that should be stored
	 * @param	array		additional names of keys of the MOD_SETTING array which should be stored (array or comma list)
	 * @return	void
	 */
	function init($prefix = '', $storeList = '') {
		$this->prefix = $prefix;
		$this->setStoreList($storeList);
		$this->type = 'perm';

			// enable dev logging if set
		if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_modSettings.php']['writeDevLog']) {
			$this->writeDevLog = TRUE;
		}
		if (TYPO3_DLOG) {
			$this->writeDevLog = TRUE;
		}
	}

	/**
	 * Set session type to 'ses' which will store the settings data not permanently.
	 *
	 * @param	string		Default is 'ses'
	 * @return	void
	 */
	function setSessionType($type = 'ses') {
		$this->type = $type;
	}


	/********************************
	 *
	 * Store list - which values should be stored
	 *
	 ********************************/


	/**
	 * Set MOD_SETTINGS keys which should be stored
	 *
	 * @param	mixed		array or string (,) - set additional names of keys of the MOD_SETTING array which should be stored
	 * @return	void
	 */
	function setStoreList($storeList) {
		$this->storeList = is_array($storeList) ? $storeList : t3lib_div::trimExplode(',', $storeList, 1);

		if ($this->writeDevLog) {
			t3lib_div::devLog('Store list:' . implode(',', $this->storeList), 't3lib_modSettings', 0);
		}
	}


	/**
	 * Add MOD_SETTINGS keys to the current list
	 *
	 * @param	mixed		array or string (,) - add names of keys of the MOD_SETTING array which should be stored
	 * @return	void
	 */
	function addToStoreList($storeList) {
		$storeList = is_array($storeList) ? $storeList : t3lib_div::trimExplode(',', $storeList, 1);
		$this->storeList = array_merge($this->storeList, $storeList);

		if ($this->writeDevLog) {
			t3lib_div::devLog('Store list:' . implode(',', $this->storeList), 't3lib_modSettings', 0);
		}
	}


	/**
	 * Add names of keys of the MOD_SETTING array by a prefix
	 *
	 * @param	string		prefix of MOD_SETTING array keys that should be stored
	 * @return	void
	 */
	function addToStoreListFromPrefix($prefix = '') {
		global $SOBE;

		$prefix = $prefix ? $prefix : $this->prefix;

		foreach ($SOBE->MOD_SETTINGS as $key => $value) {
			if (preg_match('/^' . $prefix . '/', $key)) {
				$this->storeList[$key] = $key;
			}
		}

		unset($this->storeList[$this->prefix . '_storedSettings']);

		if ($this->writeDevLog) {
			t3lib_div::devLog('Store list:' . implode(',', $this->storeList), 't3lib_modSettings', 0);
		}
	}


	/********************************
	 *
	 * Process storage array
	 *
	 ********************************/


	/**
	 * Get the stored settings from MOD_SETTINGS and set them in $this->storedSettings
	 *
	 * @return	void
	 */
	function initStorage() {
		global $SOBE;

		$storedSettings = unserialize($SOBE->MOD_SETTINGS[$this->prefix . '_storedSettings']);
		$this->storedSettings = $this->cleanupStorageArray($storedSettings);
	}


	/**
	 * Remove corrupted data entries from the stored settings array
	 *
	 * @param	array		$storedSettings
	 * @return	array		$storedSettings
	 */
	function cleanupStorageArray($storedSettings) {

		$storedSettings = is_array($storedSettings) ? $storedSettings : array();

			// clean up the array
		foreach ($storedSettings as $id => $sdArr) {
			if (!is_array($sdArr)) {
				unset($storedSettings[$id]);
			}
			if (!is_array($sdArr['data'])) {
				unset($storedSettings[$id]);
			}
			if (!trim($sdArr['title'])) {
				$storedSettings[$id]['title'] = '[no title]';
			}
		}

		return $storedSettings;
	}


	/**
	 * Creates an entry for the stored settings array
	 * Collects data from MOD_SETTINGS selected by the storeList
	 *
	 * @param	array		Should work with data from _GP('storeControl'). This is ['title']: Title for the entry. ['desc']: A description text. Currently not used by this class
	 * @return	array		$storageArr: entry for the stored settings array
	 */
	function compileEntry($data) {
		global $SOBE;

		$storageData = array();
		foreach ($this->storeList as $MS_key) {
			$storageData[$MS_key] = $SOBE->MOD_SETTINGS[$MS_key];
		}
		$storageArr = array(
			'title' => $data['title'],
			'desc' => (string) $data['desc'],
			'data' => $storageData,
			'user' => NULL,
			'tstamp' => $GLOBALS['EXEC_TIME'],
		);
		$storageArr = $this->processEntry($storageArr);

		return $storageArr;
	}


	/**
	 * Copies the stored data from entry $index to $writeArray which can be used to set MOD_SETTINGS
	 *
	 * @param	mixed		The entry key
	 * @param	array		Preset data array. Will be overwritten by copied values.
	 * @return	array		Data array
	 */
	function getStoredData($storeIndex, $writeArray = array()) {
		if ($this->storedSettings[$storeIndex]) {
			foreach ($this->storeList as $k) {
				$writeArray[$k] = $this->storedSettings[$storeIndex]['data'][$k];
			}
		}
		return $writeArray;
	}


	/**
	 * Processing of the storage command LOAD, SAVE, REMOVE
	 *
	 * @param	string		Name of the module to store the settings for. Default: $GLOBALS['SOBE']->MCONF['name'] (current module)
	 * @return	string		Storage message. Also set in $this->msg
	 */
	function processStoreControl($mconfName = '') {

		$this->initStorage();

		$storeControl = t3lib_div::_GP('storeControl');
		$storeIndex = $storeControl['STORE'];

		$msg = '';
		$saveSettings = FALSE;
		$writeArray = array();

		if (is_array($storeControl)) {
			if ($this->writeDevLog) {
				t3lib_div::devLog('Store command: ' . t3lib_div::arrayToLogString($storeControl), 't3lib_modSettings', 0);
			}

				//
				// processing LOAD
				//

			if ($storeControl['LOAD'] AND $storeIndex) {
				$writeArray = $this->getStoredData($storeIndex, $writeArray);
				$saveSettings = TRUE;
				$msg = "'" . $this->storedSettings[$storeIndex]['title'] . "' preset loaded!";

					//
					// processing SAVE
					//

			} elseif ($storeControl['SAVE']) {
				if (trim($storeControl['title'])) {

						// get the data to store
					$newEntry = $this->compileEntry($storeControl);

						// create an index for the storage array
					if (!$storeIndex) {
						$storeIndex = t3lib_div::shortMD5($newEntry['title']);
					}

						// add data to the storage array
					$this->storedSettings[$storeIndex] = $newEntry;

					$saveSettings = TRUE;
					$msg = "'" . $newEntry['title'] . "' preset saved!";

				} else {
					$msg = 'Please enter a name for the preset!';
				}

					//
					// processing REMOVE
					//

			} elseif ($storeControl['REMOVE'] AND $storeIndex) {
					// Removing entry
				$msg = "'" . $this->storedSettings[$storeIndex]['title'] . "' preset entry removed!";
				unset($this->storedSettings[$storeIndex]);

				$saveSettings = TRUE;
			}


			$this->msg = $msg;

			if ($saveSettings) {
				$this->writeStoredSetting($writeArray, $mconfName);
			}

		}
		return $this->msg;
	}


	/**
	 * Write the current storage array and update MOD_SETTINGS
	 *
	 * @param	array		Array of settings which should be overwrite current MOD_SETTINGS
	 * @param	string		Name of the module to store the settings for. Default: $GLOBALS['SOBE']->MCONF['name'] (current module)
	 * @return	void
	 */
	function writeStoredSetting($writeArray = array(), $mconfName = '') {
		global $SOBE;

			// for debugging: just removes all module data from user settings
			// $GLOBALS['BE_USER']->pushModuleData($SOBE->MCONF['name'],array());

		unset($this->storedSettings[0]); // making sure, index 0 is not set!
		$this->storedSettings = $this->cleanupStorageArray($this->storedSettings);
		$writeArray[$this->prefix . '_storedSettings'] = serialize($this->storedSettings);

		$SOBE->MOD_SETTINGS = t3lib_BEfunc::getModuleData($SOBE->MOD_MENU, $writeArray, ($mconfName ? $mconfName : $SOBE->MCONF['name']), $this->type);

		if ($this->writeDevLog) {
			t3lib_div::devLog('Settings stored:' . $this->msg, 't3lib_modSettings', 0);
		}
	}


	/********************************
	 *
	 * GUI
	 *
	 ********************************/


	/**
	 * Returns the storage control box
	 *
	 * @param	string		List of elemetns which should be shown: load,remove,save
	 * @param	boolean		If set the box is wrapped with own form tag
	 * @return	string		HTML code
	 */
	function getStoreControl($showElements = 'load,remove,save', $useOwnForm = TRUE) {
		global $TYPO3_CONF_VARS;

		$showElements = t3lib_div::trimExplode(',', $showElements, 1);

		$this->initStorage();

			// Preset selector
		$opt = array();
		$opt[] = '<option value="0">   </option>';
		foreach ($this->storedSettings as $id => $v) {
			$opt[] = '<option value="' . $id . '">' . htmlspecialchars($v['title']) . '</option>';
		}
		$storedEntries = count($opt) > 1;


		$codeTD = array();


			// LOAD, REMOVE, but also show selector so you can overwrite an entry with SAVE
		if ($storedEntries AND (count($showElements))) {

				// selector box
			$onChange = 'document.forms[\'' . $this->formName . '\'][\'storeControl[title]\'].value= this.options[this.selectedIndex].value!=0 ? this.options[this.selectedIndex].text : \'\';';
			$code = '
					<select name="storeControl[STORE]" onChange="' . htmlspecialchars($onChange) . '">
					' . implode('
						', $opt) . '
					</select>';

				// load button
			if (in_array('load', $showElements)) {
				$code .= '
					<input type="submit" name="storeControl[LOAD]" value="Load" /> ';
			}

				// remove button
			if (in_array('remove', $showElements)) {
				$code .= '
					<input type="submit" name="storeControl[REMOVE]" value="Remove" /> ';
			}
			$codeTD[] = '<td width="1%">Preset:</td>';
			$codeTD[] = '<td nowrap="nowrap">' . $code . '&nbsp;&nbsp;</td>';
		}


			// SAVE
		if (in_array('save', $showElements)) {
			$onClick = (!$storedEntries) ? '' : 'if (document.forms[\'' . $this->formName . '\'][\'storeControl[STORE]\'].options[document.forms[\'' . $this->formName . '\'][\'storeControl[STORE]\'].selectedIndex].value<0) return confirm(\'Are you sure you want to overwrite the existing entry?\');';
			$code = '<input name="storeControl[title]" value="" type="text" max="80" width="25"> ';
			$code .= '<input type="submit" name="storeControl[SAVE]" value="Save" onClick="' . htmlspecialchars($onClick) . '" />';
			$codeTD[] = '<td nowrap="nowrap">' . $code . '</td>';
		}


		$codeTD = implode('
			', $codeTD);

		if (trim($code)) {
			$code = '
			<!--
				Store control
			-->
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr class="bgColor4">
				' . $codeTD . '
				</tr>
			</table>
			';
		}

		if ($this->msg) {
			$code .= '
			<div><strong>' . htmlspecialchars($this->msg) . '</strong></div>';
		}
		#TODO need to add parameters
		if ($useOwnForm AND trim($code)) {
			$code = '
		<form action="' . t3lib_div::getIndpEnv('SCRIPT_NAME') . '" method="post" name="' . $this->formName . '" enctype="' . $TYPO3_CONF_VARS['SYS']['form_enctype'] . '">' . $code . '</form>';
		}

		return $code;
	}


	/********************************
	 *
	 * Misc
	 *
	 ********************************/


	/**
	 * Processing entry for the stored settings array
	 * Can be overwritten by extended class
	 *
	 * @param	array		$storageData: entry for the stored settings array
	 * @return	array		$storageData: entry for the stored settings array
	 */
	function processEntry($storageArr) {
		return $storageArr;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_modSettings.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_modSettings.php']);
}

?>