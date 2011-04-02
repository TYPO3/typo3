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
 * Contains the parent class for 'ScriptClasses' in backend modules.
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  133: class t3lib_SCbase
 *  249:	 function init()
 *  269:	 function menuConfig()
 *  292:	 function mergeExternalItems($modName,$menuKey,$menuArr)
 *  317:	 function handleExternalFunctionValue($MM_key='function', $MS_value=NULL)
 *  335:	 function getExternalItemConfig($modName,$menuKey,$value='')
 *  349:	 function checkExtObj()
 *  363:	 function checkSubExtObj()
 *  375:	 function extObjHeader()
 *  384:	 function extObjContent()
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * EXAMPLE PROTOTYPE
 *
 * As for examples there are lots of them if you search for classes which extends 't3lib_SCbase'.
 * However you can see a prototype example of how a module might use this class in an index.php file typically hosting a backend module.
 * NOTICE: This example only outlines the basic structure of how this class is used. You should consult the documentation and other real-world examples for some actual things to do when building modules.
 *
 *		   // TYPICAL 'HEADER' OF A BACKEND MODULE:
 *		 unset($MCONF);
 *		 require ('conf.php');
 *		 require ($BACK_PATH.'init.php');
 *		 require ($BACK_PATH.'template.php');
 *		 $GLOBALS['LANG']->includeLLFile('EXT:prototype/locallang.php');
 *		 $GLOBALS['BE_USER']->modAccess($MCONF,1);
 *
 *			 // SC_mod_prototype EXTENDS THE CLASS t3lib_SCbase with a main() and printContent() function:
 *		 class SC_mod_prototype extends t3lib_SCbase {
 *				 // MAIN FUNCTION - HERE YOU CREATE THE MODULE CONTENT IN $this->content
 *			 function main()	{
 *					 // TYPICALLY THE INTERNAL VAR, $this->doc is instantiated like this:
 *				 $this->doc = t3lib_div::makeInstance('mediumDoc');
 *					 // TYPICALLY THE INTERNAL VAR, $this->backPath is set like this:
 *				 $this->backPath = $this->doc->backPath = $GLOBALS['BACK_PATH'];
 *					 // ... AND OF COURSE A LOT OF OTHER THINGS GOES ON - LIKE PUTTING CONTENT INTO $this->content
 *				 $this->content='';
 *			 }
 *				 // PRINT CONTENT - DONE AS THE LAST THING
 *			 function printContent()	{
 *				 echo $this->content;
 *			 }
 *		 }
 *
 *			 // CHECKING IF THERE ARE AN EXTENSION CLASS CONFIGURED FOR THIS CLASS:
 *		 if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/prototype/index.php'])) {
 *			 include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/prototype/index.php']);
 *		 }
 *
 *		   // MAKE INSTANCE OF THE SCRIPT CLASS AND CALL init()
 *		 $SOBE = t3lib_div::makeInstance('SC_mod_prototype');
 *		 $SOBE->init();
 *
 *		   // AFTER INIT THE INTERNAL ARRAY ->include_once MAY HOLD FILENAMES TO INCLUDE
 *		 foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
 *
 *		   // THEN WE WILL CHECK IF THERE IS A 'SUBMODULE' REGISTERED TO BE INITIALIZED AS WELL:
 *		 $SOBE->checkExtObj();
 *
 *		   // THEN WE CALL THE main() METHOD AND THIS SHOULD SPARK THE CREATION OF THE MODULE OUTPUT.
 *		 $SOBE->main();
 *		   // FINALLY THE printContent() FUNCTION WILL OUTPUT THE ACCUMULATED CONTENT
 *		 $SOBE->printContent();
 */

/**
 * Parent class for 'ScriptClasses' in backend modules.
 * See example comment above.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see t3lib_extobjbase
 */
class t3lib_SCbase {

	/**
	 * Loaded with the global array $MCONF which holds some module configuration from the conf.php file of backend modules.
	 * @see init()
	 */
	var $MCONF = array();

	/**
	 * The integer value of the GET/POST var, 'id'. Used for submodules to the 'Web' module (page id)
	 * @see init()
	 */
	var $id;

	/**
	 * The value of GET/POST var, 'CMD'
	 * @see init()
	 */
	var $CMD;

	/**
	 * A WHERE clause for selection records from the pages table based on read-permissions of the current backend user.
	 * @see init()
	 */
	var $perms_clause;


	/**
	 * The module menu items array. Each key represents a key for which values can range between the items in the array of that key.
	 * @see init()
	 */
	var $MOD_MENU = array(
		'function' => array()
	);

	/**
	 * Current settings for the keys of the MOD_MENU array
	 * @see $MOD_MENU
	 */
	var $MOD_SETTINGS = array();

	/**
	 * Module TSconfig based on PAGE TSconfig / USER TSconfig
	 * @see menuConfig()
	 */
	var $modTSconfig;

	/**
	 * If type is 'ses' then the data is stored as session-lasting data. This means that it'll be wiped out the next time the user logs in.
	 * Can be set from extension classes of this class before the init() function is called.
	 *
	 * @see menuConfig(), t3lib_BEfunc::getModuleData()
	 */
	var $modMenu_type = '';

	/**
	 * dontValidateList can be used to list variables that should not be checked if their value is found in the MOD_MENU array. Used for dynamically generated menus.
	 * Can be set from extension classes of this class before the init() function is called.
	 *
	 * @see menuConfig(), t3lib_BEfunc::getModuleData()
	 */
	var $modMenu_dontValidateList = '';

	/**
	 * List of default values from $MOD_MENU to set in the output array (only if the values from MOD_MENU are not arrays)
	 * Can be set from extension classes of this class before the init() function is called.
	 *
	 * @see menuConfig(), t3lib_BEfunc::getModuleData()
	 */
	var $modMenu_setDefaultList = '';

	/**
	 * Contains module configuration parts from TBE_MODULES_EXT if found
	 *
	 * @see handleExternalFunctionValue()
	 */
	var $extClassConf;

	/**
	 * Contains absolute paths to class files to include from the global scope. This is done in the module index.php files after calling the init() function
	 *
	 * @see handleExternalFunctionValue()
	 */
	var $include_once = array();

	/**
	 * Generally used for accumulating the output content of backend modules
	 */
	var $content = '';

	/**
	 * Generally used to hold an instance of the 'template' class from typo3/template.php
	 *
	 * @var template
	 */
	var $doc;

	/**
	 * May contain an instance of a 'Function menu module' which connects to this backend module.
	 *
	 * @see checkExtObj()
	 */
	var $extObj;


	/**
	 * Initializes the backend module by setting internal variables, initializing the menu.
	 *
	 * @return	void
	 * @see menuConfig()
	 */
	function init() {
			// name might be set from outside
		if (!$this->MCONF['name']) {
			$this->MCONF = $GLOBALS['MCONF'];
		}
		$this->id = intval(t3lib_div::_GP('id'));
		$this->CMD = t3lib_div::_GP('CMD');
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$this->menuConfig();
		$this->handleExternalFunctionValue();
	}

	/**
	 * Initializes the internal MOD_MENU array setting and unsetting items based on various conditions. It also merges in external menu items from the global array TBE_MODULES_EXT (see mergeExternalItems())
	 * Then MOD_SETTINGS array is cleaned up (see t3lib_BEfunc::getModuleData()) so it contains only valid values. It's also updated with any SET[] values submitted.
	 * Also loads the modTSconfig internal variable.
	 *
	 * @return	void
	 * @see init(), $MOD_MENU, $MOD_SETTINGS, t3lib_BEfunc::getModuleData(), mergeExternalItems()
	 */
	function menuConfig() {
			// page/be_user TSconfig settings and blinding of menu-items
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id, 'mod.' . $this->MCONF['name']);
		$this->MOD_MENU['function'] = $this->mergeExternalItems($this->MCONF['name'], 'function', $this->MOD_MENU['function']);
		$this->MOD_MENU['function'] = t3lib_BEfunc::unsetMenuItems($this->modTSconfig['properties'], $this->MOD_MENU['function'], 'menu.function');

		#debug($this->MOD_MENU['function'],$this->MCONF['name']);
		#debug($this->modTSconfig['properties']);

		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData(
			$this->MOD_MENU,
			t3lib_div::_GP('SET'),
			$this->MCONF['name'],
			$this->modMenu_type,
			$this->modMenu_dontValidateList,
			$this->modMenu_setDefaultList
		);
	}

	/**
	 * Merges menu items from global array $TBE_MODULES_EXT
	 *
	 * @param	string		Module name for which to find value
	 * @param	string		Menu key, eg. 'function' for the function menu.
	 * @param	array		The part of a MOD_MENU array to work on.
	 * @return	array		Modified array part.
	 * @access private
	 * @see t3lib_extMgm::insertModuleFunction(), menuConfig()
	 */
	function mergeExternalItems($modName, $menuKey, $menuArr) {
		$mergeArray = $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey];
		if (is_array($mergeArray)) {
			foreach ($mergeArray as $k => $v) {
				if ((string) $v['ws'] === '' ||
					($GLOBALS['BE_USER']->workspace === 0 && t3lib_div::inList($v['ws'], 'online')) ||
					($GLOBALS['BE_USER']->workspace === -1 && t3lib_div::inList($v['ws'], 'offline')) ||
					($GLOBALS['BE_USER']->workspace > 0 && t3lib_div::inList($v['ws'], 'custom'))) {
					$menuArr[$k] = $GLOBALS['LANG']->sL($v['title']);
				}
			}
		}
		return $menuArr;
	}

	/**
	 * Loads $this->extClassConf with the configuration for the CURRENT function of the menu.
	 * If for this array the key 'path' is set then that is expected to be an absolute path to a file which should be included - so it is set in the internal array $this->include_once
	 *
	 * @param	string		The key to MOD_MENU for which to fetch configuration. 'function' is default since it is first and foremost used to get information per "extension object" (I think that is what its called)
	 * @param	string		The value-key to fetch from the config array. If NULL (default) MOD_SETTINGS[$MM_key] will be used. This is usefull if you want to force another function than the one defined in MOD_SETTINGS[function]. Call this in init() function of your Script Class: handleExternalFunctionValue('function', $forcedSubModKey)
	 * @return	void
	 * @see getExternalItemConfig(), $include_once, init()
	 */
	function handleExternalFunctionValue($MM_key = 'function', $MS_value = NULL) {
		$MS_value = is_null($MS_value) ? $this->MOD_SETTINGS[$MM_key] : $MS_value;
		$this->extClassConf = $this->getExternalItemConfig($this->MCONF['name'], $MM_key, $MS_value);
		if (is_array($this->extClassConf) && $this->extClassConf['path']) {
			$this->include_once[] = $this->extClassConf['path'];
		}
	}

	/**
	 * Returns configuration values from the global variable $TBE_MODULES_EXT for the module given.
	 * For example if the module is named "web_info" and the "function" key ($menuKey) of MOD_SETTINGS is "stat" ($value) then you will have the values of $TBE_MODULES_EXT['webinfo']['MOD_MENU']['function']['stat'] returned.
	 *
	 * @param	string		Module name
	 * @param	string		Menu key, eg. "function" for the function menu. See $this->MOD_MENU
	 * @param	string		Optionally the value-key to fetch from the array that would otherwise have been returned if this value was not set. Look source...
	 * @return	mixed		The value from the TBE_MODULES_EXT array.
	 * @see handleExternalFunctionValue()
	 */
	function getExternalItemConfig($modName, $menuKey, $value = '') {
		return strcmp($value, '') ? $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey][$value] : $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey];
	}

	/**
	 * Creates an instance of the class found in $this->extClassConf['name'] in $this->extObj if any (this should hold three keys, "name", "path" and "title" if a "Function menu module" tries to connect...)
	 * This value in extClassConf might be set by an extension (in a ext_tables/ext_localconf file) which thus "connects" to a module.
	 * The array $this->extClassConf is set in handleExternalFunctionValue() based on the value of MOD_SETTINGS[function]
	 * (Should be) called from global scope right after inclusion of files from the ->include_once array.
	 * If an instance is created it is initiated with $this passed as value and $this->extClassConf as second argument. Further the $this->MOD_SETTING is cleaned up again after calling the init function.
	 *
	 * @return	void
	 * @see handleExternalFunctionValue(), t3lib_extMgm::insertModuleFunction(), $extObj
	 */
	function checkExtObj() {
		if (is_array($this->extClassConf) && $this->extClassConf['name']) {
			$this->extObj = t3lib_div::makeInstance($this->extClassConf['name']);
			$this->extObj->init($this, $this->extClassConf);
				// Re-write:
			$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData(
				$this->MOD_MENU,
				t3lib_div::_GP('SET'),
				$this->MCONF['name'],
				$this->modMenu_type,
				$this->modMenu_dontValidateList,
				$this->modMenu_setDefaultList
			);
		}
	}

	/**
	 * Calls the checkExtObj function in sub module if present.
	 *
	 * @return	void
	 */
	function checkSubExtObj() {
		if (is_object($this->extObj)) {
			$this->extObj->checkExtObj();
		}
	}

	/**
	 * Calls the 'header' function inside the "Function menu module" if present.
	 * A header function might be needed to add JavaScript or other stuff in the head. This can't be done in the main function because the head is already written.
	 * example call in the header function:
	 * $this->pObj->doc->JScode = $this->pObj->doc->wrapScriptTags(' ...
	 *
	 * @return	void
	 */
	function extObjHeader() {
		if (is_callable(array($this->extObj, 'head'))) {
			$this->extObj->head();
		}
	}

	/**
	 * Calls the 'main' function inside the "Function menu module" if present
	 *
	 * @return	void
	 */
	function extObjContent() {
		$this->extObj->pObj = $this;

		if (is_callable(array($this->extObj, 'main'))) {
			$this->content .= $this->extObj->main();
		}
	}
}

?>
