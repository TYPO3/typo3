<?php
namespace TYPO3\CMS\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * usage inside of scbase class
 *
 * ....
 *
 * $this->MOD_MENU = array(
 * 'function' => array(
 * 'xxx ...
 * ),
 * 'tx_dam_select_storedSettings' => '',
 *
 * ....
 *
 * function main() {
 * reStore settings
 * $store = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\ModuleSettings');
 * $store->init('tx_dam_select');
 * $store->setStoreList('tx_dam_select');
 * $store->processStoreControl();
 *
 * show control panel
 * $this->content.= $this->doc->section('Settings',$store->getStoreControl(),0,1);
 *
 * Format of saved settings
 *
 * $GLOBALS['SOBE']->MOD_SETTINGS[$this->prefix.'_storedSettings'] = serialize(
 * array (
 * 'any id' => array (
 * 'title' => 'title for saved settings',
 * 'desc' => 'description text, not mandatory',
 * 'data' => array(),	// data from MOD_SETTINGS
 * 'user' => NULL, // can be used for extra data used by the application to identify this entry
 * 'tstamp' => 12345, // $GLOBALS['EXEC_TIME']
 * ),
 * 'another id' => ...
 *
 * ) );
 *
 * @author René Fritz <r.fritz@colorcube.de>
 */
class ModuleSettings {

	/**
	 * If type is set 'ses' then the module data will be stored into the session and will be lost with logout.
	 * Type 'perm' will store the data permanently.
	 *
	 * @todo Define visibility
	 */
	public $type = 'perm';

	/**
	 * prefix of MOD_SETTING array keys that should be stored
	 *
	 * @todo Define visibility
	 */
	public $prefix = '';

	/**
	 * Names of keys of the MOD_SETTING array which should be stored
	 *
	 * @todo Define visibility
	 */
	public $storeList = array();

	/**
	 * The stored settings array
	 *
	 * @todo Define visibility
	 */
	public $storedSettings = array();

	/**
	 * Message from the last storage command
	 *
	 * @todo Define visibility
	 */
	public $msg = '';

	/**
	 * Name of the form. Needed for JS
	 *
	 * @todo Define visibility
	 */
	public $formName = 'storeControl';

	// Write messages into the devlog?
	/**
	 * @todo Define visibility
	 */
	public $writeDevLog = 0;

	/********************************
	 *
	 * Init / setup
	 *
	 ********************************/
	/**
	 * Initializes the object
	 *
	 * @param string $prefix Prefix of MOD_SETTING array keys that should be stored
	 * @param array $storeList Additional names of keys of the MOD_SETTING array which should be stored (array or comma list)
	 * @return void
	 * @todo Define visibility
	 */
	public function init($prefix = '', $storeList = '') {
		$this->prefix = $prefix;
		$this->setStoreList($storeList);
		$this->type = 'perm';
		// Enable dev logging if set
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
	 * @param string $type Default is 'ses'
	 * @return void
	 * @todo Define visibility
	 */
	public function setSessionType($type = 'ses') {
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
	 * @param mixed $storeList Array or string (,) - set additional names of keys of the MOD_SETTING array which should be stored
	 * @return void
	 * @todo Define visibility
	 */
	public function setStoreList($storeList) {
		$this->storeList = is_array($storeList) ? $storeList : \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $storeList, 1);
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Store list:' . implode(',', $this->storeList), 'TYPO3\\CMS\\Backend\\ModuleSettings', 0);
		}
	}

	/**
	 * Add MOD_SETTINGS keys to the current list
	 *
	 * @param mixed Array or string (,) - add names of keys of the MOD_SETTING array which should be stored
	 * @return void
	 * @todo Define visibility
	 */
	public function addToStoreList($storeList) {
		$storeList = is_array($storeList) ? $storeList : \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $storeList, 1);
		$this->storeList = array_merge($this->storeList, $storeList);
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Store list:' . implode(',', $this->storeList), 'TYPO3\\CMS\\Backend\\ModuleSettings', 0);
		}
	}

	/**
	 * Add names of keys of the MOD_SETTING array by a prefix
	 *
	 * @param string $prefix Prefix of MOD_SETTING array keys that should be stored
	 * @return void
	 * @todo Define visibility
	 */
	public function addToStoreListFromPrefix($prefix = '') {
		$prefix = $prefix ? $prefix : $this->prefix;
		$prefix = preg_quote($prefix, '/');
		foreach ($GLOBALS['SOBE']->MOD_SETTINGS as $key => $value) {
			if (preg_match('/^' . $prefix . '/', $key)) {
				$this->storeList[$key] = $key;
			}
		}
		unset($this->storeList[$this->prefix . '_storedSettings']);
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Store list:' . implode(',', $this->storeList), 'TYPO3\\CMS\\Backend\\ModuleSettings', 0);
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
	 * @return void
	 * @todo Define visibility
	 */
	public function initStorage() {
		$storedSettings = unserialize($GLOBALS['SOBE']->MOD_SETTINGS[$this->prefix . '_storedSettings']);
		$this->storedSettings = $this->cleanupStorageArray($storedSettings);
	}

	/**
	 * Remove corrupted data entries from the stored settings array
	 *
	 * @param array $storedSettings The stored settings
	 * @return array Cleaned up stored settings
	 * @todo Define visibility
	 */
	public function cleanupStorageArray($storedSettings) {
		$storedSettings = is_array($storedSettings) ? $storedSettings : array();
		// Clean up the array
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
	 * @param array $data Should work with data from _GP('storeControl'). This is ['title']: Title for the entry. ['desc']: A description text. Currently not used by this class
	 * @return array Entry for the stored settings array
	 * @todo Define visibility
	 */
	public function compileEntry($data) {
		$storageData = array();
		foreach ($this->storeList as $MS_key) {
			$storageData[$MS_key] = $GLOBALS['SOBE']->MOD_SETTINGS[$MS_key];
		}
		$storageArr = array(
			'title' => $data['title'],
			'desc' => (string) $data['desc'],
			'data' => $storageData,
			'user' => NULL,
			'tstamp' => $GLOBALS['EXEC_TIME']
		);
		$storageArr = $this->processEntry($storageArr);
		return $storageArr;
	}

	/**
	 * Copies the stored data from entry $index to $writeArray which can be used to set MOD_SETTINGS
	 *
	 * @param mixed $storeIndex The entry key
	 * @param array $writeArray Preset data array. Will be overwritten by copied values.
	 * @return array Data array
	 * @todo Define visibility
	 */
	public function getStoredData($storeIndex, $writeArray = array()) {
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
	 * @param string $mconfName Name of the module to store the settings for. Default: $GLOBALS['SOBE']->MCONF['name'] (current module)
	 * @return string Storage message. Also set in $this->msg
	 * @todo Define visibility
	 */
	public function processStoreControl($mconfName = '') {
		$this->initStorage();
		$storeControl = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('storeControl');
		$storeIndex = $storeControl['STORE'];
		$msg = '';
		$saveSettings = FALSE;
		$writeArray = array();
		if (is_array($storeControl)) {
			if ($this->writeDevLog) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Store command: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::arrayToLogString($storeControl), 'TYPO3\\CMS\\Backend\\ModuleSettings', 0);
			}
			// Processing LOAD
			if ($storeControl['LOAD'] and $storeIndex) {
				$writeArray = $this->getStoredData($storeIndex, $writeArray);
				$saveSettings = TRUE;
				$msg = '\'' . $this->storedSettings[$storeIndex]['title'] . '\' preset loaded!';
			} elseif ($storeControl['SAVE']) {
				if (trim($storeControl['title'])) {
					// Get the data to store
					$newEntry = $this->compileEntry($storeControl);
					// Create an index for the storage array
					if (!$storeIndex) {
						$storeIndex = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5($newEntry['title']);
					}
					// Add data to the storage array
					$this->storedSettings[$storeIndex] = $newEntry;
					$saveSettings = TRUE;
					$msg = '\'' . $newEntry['title'] . '\' preset saved!';
				} else {
					$msg = 'Please enter a name for the preset!';
				}
			} elseif ($storeControl['REMOVE'] and $storeIndex) {
				// Removing entry
				$msg = '\'' . $this->storedSettings[$storeIndex]['title'] . '\' preset entry removed!';
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
	 * @param array $writeArray Array of settings which should be overwrite current MOD_SETTINGS
	 * @param string $mconfName Name of the module to store the settings for. Default: $GLOBALS['SOBE']->MCONF['name'] (current module)
	 * @return void
	 * @todo Define visibility
	 */
	public function writeStoredSetting($writeArray = array(), $mconfName = '') {
		// Making sure, index 0 is not set
		unset($this->storedSettings[0]);
		!($this->storedSettings = $this->cleanupStorageArray($this->storedSettings));
		$writeArray[$this->prefix . '_storedSettings'] = serialize($this->storedSettings);
		$GLOBALS['SOBE']->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($GLOBALS['SOBE']->MOD_MENU, $writeArray, $mconfName ? $mconfName : $GLOBALS['SOBE']->MCONF['name'], $this->type);
		if ($this->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Settings stored:' . $this->msg, 'TYPO3\\CMS\\Backend\\ModuleSettings', 0);
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
	 * @param string $showElements List of elemetns which should be shown: load,remove,save
	 * @param boolean $useOwnForm If set the box is wrapped with own form tag
	 * @return string HTML code
	 * @todo Define visibility
	 */
	public function getStoreControl($showElements = 'load,remove,save', $useOwnForm = TRUE) {
		$showElements = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $showElements, 1);
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
		if ($storedEntries and count($showElements)) {
			// Selector box
			$onChange = 'document.forms[\'' . $this->formName . '\'][\'storeControl[title]\'].value= this.options[this.selectedIndex].value!=0 ? this.options[this.selectedIndex].text : \'\';';
			$code = '
					<select name="storeControl[STORE]" onChange="' . htmlspecialchars($onChange) . '">
					' . implode('
						', $opt) . '
					</select>';
			// Load button
			if (in_array('load', $showElements)) {
				$code .= '
					<input type="submit" name="storeControl[LOAD]" value="Load" /> ';
			}
			// Remove button
			if (in_array('remove', $showElements)) {
				$code .= '
					<input type="submit" name="storeControl[REMOVE]" value="Remove" /> ';
			}
			$codeTD[] = '<td width="1%">Preset:</td>';
			$codeTD[] = '<td nowrap="nowrap">' . $code . '&nbsp;&nbsp;</td>';
		}
		// SAVE
		if (in_array('save', $showElements)) {
			$onClick = !$storedEntries ? '' : 'if (document.forms[\'' . $this->formName . '\'][\'storeControl[STORE]\'].options[document.forms[\'' . $this->formName . '\'][\'storeControl[STORE]\'].selectedIndex].value<0) return confirm(\'Are you sure you want to overwrite the existing entry?\');';
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
		// TODO need to add parameters
		if ($useOwnForm and trim($code)) {
			$code = '
		<form action="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME') . '" method="post" name="' . $this->formName . '" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '">' . $code . '</form>';
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
	 * @param array $storageData Entry for the stored settings array
	 * @return array Entry for the stored settings array
	 * @todo Define visibility
	 */
	public function processEntry($storageArr) {
		return $storageArr;
	}

}


?>
