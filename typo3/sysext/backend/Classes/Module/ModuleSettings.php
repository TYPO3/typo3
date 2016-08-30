<?php
namespace TYPO3\CMS\Backend;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Manage storing and restoring of $this->getModule()->MOD_SETTINGS settings.
 * Provides a presets box for BE modules.
 *
 * usage inside of BaseScriptClass class
 *
 * ....
 *
 * $this->MOD_MENU = array(
 * 'function' => array('xxx'),
 * 'tx_someext_storedSettings' => '',
 *
 * ....
 *
 * function main() {
 * // reStore settings
 * $store = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\ModuleSettings::class);
 * $store->init('tx_someext');
 * $store->setStoreList('tx_someext');
 * $store->processStoreControl();
 *
 * // show control panel
 * $this->content .= '<h2>Settings</h2>' . '<div>' . $store->getStoreControl() . '</div>';
 *
 * Format of saved settings
 *
 * $this->getModule()->MOD_SETTINGS[$this->prefix . '_storedSettings'] = serialize(
 * array (
 *   'any id' => array(
 *     'title' => 'title for saved settings',
 *     'desc' => 'description text, not mandatory',
 *     'data' => array(),	// data from MOD_SETTINGS
 *     'user' => NULL, // can be used for extra data used by the application to identify this entry
 *     'tstamp' => 12345, // $GLOBALS['EXEC_TIME']
 *   ),
 *   'another id' => ...
 * )
 *
 * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8.
 */
class ModuleSettings
{
    /**
     * If type is set 'ses' then the module data will be stored into the session and will be lost with logout.
     * Type 'perm' will store the data permanently.
     *
     * @var string
     */
    public $type = 'perm';

    /**
     * prefix of MOD_SETTING array keys that should be stored
     *
     * @var string
     */
    public $prefix = '';

    /**
     * Names of keys of the MOD_SETTING array which should be stored
     *
     * @var array
     */
    public $storeList = [];

    /**
     * The stored settings array
     *
     * @var array
     */
    public $storedSettings = [];

    /**
     * Message from the last storage command
     *
     * @var string
     */
    public $msg = '';

    /**
     * Name of the form. Needed for JS
     *
     * @var string
     */
    public $formName = 'storeControl';

    /**
     * Write messages into the devlog?
     *
     * @var bool
     */
    public $writeDevLog = false;

    /********************************
     *
     * Init / setup
     *
     ********************************/

    /**
     * Default constructor
     */
    public function __construct()
    {
        GeneralUtility::deprecationLog('Class ModuleSettings is deprecated since TYPO3 CMS 7 and will be removed with TYPO3 CMS 8');
    }

    /**
     * Initializes the object
     *
     * @param string $prefix Prefix of MOD_SETTING array keys that should be stored
     * @param array|string $storeList Additional names of keys of the MOD_SETTING array which should be stored (array or comma list)
     * @return void
     */
    public function init($prefix = '', $storeList = '')
    {
        $this->prefix = $prefix;
        $this->setStoreList($storeList);
        $this->type = 'perm';
        // Enable dev logging if set
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_modSettings.php']['writeDevLog'])) {
            $this->writeDevLog = true;
        }
        if (TYPO3_DLOG) {
            $this->writeDevLog = true;
        }
    }

    /**
     * Set session type to 'ses' which will store the settings data not permanently.
     *
     * @param string $type Default is 'ses'
     * @return void
     */
    public function setSessionType($type = 'ses')
    {
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
     * @param array|string $storeList Add names of keys of the MOD_SETTING array which should be stored
     * @return void
     */
    public function setStoreList($storeList)
    {
        $this->storeList = [];
        $this->addToStoreList($storeList);
    }

    /**
     * Add MOD_SETTINGS keys to the current list
     *
     * @param array|string $storeList Add names of keys of the MOD_SETTING array which should be stored
     * @return void
     */
    public function addToStoreList($storeList)
    {
        $storeList = is_array($storeList) ? $storeList : GeneralUtility::trimExplode(',', $storeList, true);
        $this->storeList = array_merge($this->storeList, $storeList);
        if ($this->writeDevLog) {
            GeneralUtility::devLog('Store list:' . implode(',', $this->storeList), __CLASS__, 0);
        }
    }

    /**
     * Add names of keys of the MOD_SETTING array by a prefix
     *
     * @param string $prefix Prefix of MOD_SETTING array keys that should be stored
     * @return void
     */
    public function addToStoreListFromPrefix($prefix = '')
    {
        $prefix = $prefix ?: $this->prefix;
        $prefix = preg_quote($prefix, '/');
        foreach ($this->getModule()->MOD_SETTINGS as $key => $value) {
            if (preg_match('/^' . $prefix . '/', $key)) {
                $this->storeList[$key] = $key;
            }
        }
        unset($this->storeList[$this->prefix . '_storedSettings']);
        if ($this->writeDevLog) {
            GeneralUtility::devLog('Store list:' . implode(',', $this->storeList), __CLASS__, 0);
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
     */
    public function initStorage()
    {
        $storedSettings = unserialize($this->getModule()->MOD_SETTINGS[$this->prefix . '_storedSettings']);
        $this->storedSettings = $this->cleanupStorageArray($storedSettings);
    }

    /**
     * Remove corrupted data entries from the stored settings array
     *
     * @param array $storedSettings The stored settings
     * @return array Cleaned up stored settings
     */
    public function cleanupStorageArray($storedSettings)
    {
        $storedSettings = is_array($storedSettings) ? $storedSettings : [];
        // Clean up the array
        foreach ($storedSettings as $id => $sdArr) {
            if (!is_array($sdArr)) {
                unset($storedSettings[$id]);
            } elseif (!is_array($sdArr['data'])) {
                unset($storedSettings[$id]);
            } elseif (!trim($sdArr['title'])) {
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
     */
    public function compileEntry($data)
    {
        $storageData = [];
        foreach ($this->storeList as $MS_key) {
            $storageData[$MS_key] = $this->getModule()->MOD_SETTINGS[$MS_key];
        }
        $storageArr = [
            'title' => $data['title'],
            'desc' => (string)$data['desc'],
            'data' => $storageData,
            'user' => null,
            'tstamp' => $GLOBALS['EXEC_TIME']
        ];
        $storageArr = $this->processEntry($storageArr);
        return $storageArr;
    }

    /**
     * Copies the stored data from entry $index to $writeArray which can be used to set MOD_SETTINGS
     *
     * @param mixed $storeIndex The entry key
     * @param array $writeArray Preset data array. Will be overwritten by copied values.
     * @return array Data array
     */
    public function getStoredData($storeIndex, $writeArray = [])
    {
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
     * @param string $mconfName Name of the module to store the settings for. Default: $this->getModule()->MCONF['name'] (current module)
     * @return string Storage message. Also set in $this->msg
     */
    public function processStoreControl($mconfName = '')
    {
        $this->initStorage();
        $storeControl = GeneralUtility::_GP('storeControl');
        $storeIndex = $storeControl['STORE'];
        $msg = '';
        $saveSettings = false;
        $writeArray = [];
        if (is_array($storeControl)) {
            if ($this->writeDevLog) {
                GeneralUtility::devLog('Store command: ' . GeneralUtility::arrayToLogString($storeControl), __CLASS__, 0);
            }
            // Processing LOAD
            if ($storeControl['LOAD'] && $storeIndex) {
                $writeArray = $this->getStoredData($storeIndex, $writeArray);
                $saveSettings = true;
                $msg = '\'' . $this->storedSettings[$storeIndex]['title'] . '\' preset loaded!';
            } elseif ($storeControl['SAVE']) {
                if (trim($storeControl['title'])) {
                    // Get the data to store
                    $newEntry = $this->compileEntry($storeControl);
                    // Create an index for the storage array
                    if (!$storeIndex) {
                        $storeIndex = GeneralUtility::shortMD5($newEntry['title']);
                    }
                    // Add data to the storage array
                    $this->storedSettings[$storeIndex] = $newEntry;
                    $saveSettings = true;
                    $msg = '\'' . $newEntry['title'] . '\' preset saved!';
                } else {
                    $msg = 'Please enter a name for the preset!';
                }
            } elseif ($storeControl['REMOVE'] and $storeIndex) {
                // Removing entry
                $msg = '\'' . $this->storedSettings[$storeIndex]['title'] . '\' preset entry removed!';
                unset($this->storedSettings[$storeIndex]);
                $saveSettings = true;
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
     * @param string $mconfName Name of the module to store the settings for. Default: $this->getModule()->MCONF['name'] (current module)
     * @return void
     */
    public function writeStoredSetting($writeArray = [], $mconfName = '')
    {
        // Making sure, index 0 is not set
        unset($this->storedSettings[0]);
        $this->storedSettings = $this->cleanupStorageArray($this->storedSettings);
        $writeArray[$this->prefix . '_storedSettings'] = serialize($this->storedSettings);
        $this->getModule()->MOD_SETTINGS = Utility\BackendUtility::getModuleData(
            $this->getModule()->MOD_MENU,
            $writeArray,
            $mconfName ?: $this->getModule()->MCONF['name'],
            $this->type
        );
        if ($this->writeDevLog) {
            GeneralUtility::devLog('Settings stored:' . $this->msg, __CLASS__, 0);
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
     * @param bool $useOwnForm If set the box is wrapped with own form tag
     * @return string HTML code
     */
    public function getStoreControl($showElements = 'load,remove,save', $useOwnForm = true)
    {
        $showElements = GeneralUtility::trimExplode(',', $showElements, true);
        $this->initStorage();
        // Preset selector
        $opt = [];
        $opt[] = '<option value="0">   </option>';
        foreach ($this->storedSettings as $id => $v) {
            $opt[] = '<option value="' . $id . '">' . htmlspecialchars($v['title']) . '</option>';
        }
        $storedEntries = count($opt) > 1;
        $codeTD = [];
        $code = '';
        // LOAD, REMOVE, but also show selector so you can overwrite an entry with SAVE
        if ($storedEntries && !empty($showElements)) {
            // Selector box
            $onChange = 'document.forms[' . GeneralUtility::quoteJSvalue($this->formName) . '][\'storeControl[title]\'].value= this.options[this.selectedIndex].value!=0 ? this.options[this.selectedIndex].text : \'\';';
            $code = '
					<select name="storeControl[STORE]" onChange="' . htmlspecialchars($onChange) . '">
					' . implode('
						', $opt) . '
					</select>';
            // Load button
            if (in_array('load', $showElements)) {
                $code .= '
					<input class="btn btn-default" type="submit" name="storeControl[LOAD]" value="Load" /> ';
            }
            // Remove button
            if (in_array('remove', $showElements)) {
                $code .= '
					<input class="btn btn-default" type="submit" name="storeControl[REMOVE]" value="Remove" /> ';
            }
            $codeTD[] = '<td width="1%">Preset:</td>';
            $codeTD[] = '<td nowrap="nowrap">' . $code . '&nbsp;&nbsp;</td>';
        }
        // SAVE
        if (in_array('save', $showElements)) {
            $onClick = !$storedEntries ? '' : 'if (document.forms[' . GeneralUtility::quoteJSvalue($this->formName) . '][\'storeControl[STORE]\'].options[document.forms[' . GeneralUtility::quoteJSvalue($this->formName) . '][\'storeControl[STORE]\'].selectedIndex].value<0) return confirm(\'Are you sure you want to overwrite the existing entry?\');';
            $code = '<input name="storeControl[title]" value="" type="text" max="80" width="25"> ';
            $code .= '<input class="btn btn-default" type="submit" name="storeControl[SAVE]" value="Save" onClick="' . htmlspecialchars($onClick) . '" />';
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
        if ($useOwnForm && trim($code)) {
            $code = '
		<form action="' . GeneralUtility::getIndpEnv('SCRIPT_NAME') . '" method="post" name="' . $this->formName . '" enctype="multipart/form-data">' . $code . '</form>';
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
     * @param array $storageArr Entry for the stored settings array
     * @return array Entry for the stored settings array
     */
    public function processEntry($storageArr)
    {
        return $storageArr;
    }

    /**
     * @return BaseScriptClass
     */
    protected function getModule()
    {
        return $GLOBALS['SOBE'];
    }
}
