<?php
namespace TYPO3\CMS\Backend\Module;

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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Parent class for 'Extension Objects' in backend modules.
 *
 * Used for 'submodules' to other modules. Also called 'Function menu modules'
 * in \TYPO3\CMS\Core\Utility\ExtensionManagementUtility. And now its even called
 * 'Extension Objects'. Or 'Module functions'. Wish we had just one name. Or a
 * name at all...(?) Thank God its not so advanced when it works...
 *
 * In other words this class is used for backend modules which is not true
 * backend modules appearing in the menu but rather adds themselves as a new
 * entry in the function menu which typically exists for a backend
 * module (like Web>Functions, Web>Info or Tools etc...)
 * The magic that binds this together is stored in the global variable
 * $TBE_MODULES_EXT where extensions wanting to connect a module based on
 * this class to an existing backend module store configuration which consists
 * of the classname, script-path and a label (title/name).
 *
 * For more information about this, please see the large example comment for the
 * class \TYPO3\CMS\Backend\Module\BaseScriptClass. This will show the principle of a
 * 'level-1' connection. The more advanced example - having two layers as it is done
 * by the 'func_wizards' extension with the 'web_info' module - can be seen in the
 * comment above.
 *
 * EXAMPLE: One level.
 * This can be seen in the extension 'cms' where the info module have a
 * function added. In 'ext_tables.php' this is done by this function call:
 *
 * \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
 * 'web_info',
 * 'tx_cms_webinfo_page',
 * \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'web_info/class.tx_cms_webinfo.php',
 * 'LLL:EXT:cms/locallang_tca.xlf:mod_tx_cms_webinfo_page'
 * );
 *
 * EXAMPLE: Two levels.
 * This is the advanced example. You can see it with the extension 'func_wizards'
 * which is the first layer but then providing another layer for extensions to connect by.
 * The key used in TBE_MODULES_EXT is normally 'function' (for the 'function menu')
 * but the 'func_wizards' extension uses an alternative key for its configuration: 'wiz'.
 * In the 'ext_tables.php' file of an extension ('wizard_crpages') which uses the
 * framework provided by 'func_wizards' this looks like this:
 *
 * \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
 * 'web_func',
 * 'tx_wizardcrpages_webfunc_2',
 * \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'class.tx_wizardcrpages_webfunc_2.php',
 * 'LLL:EXT:wizard_crpages/locallang.php:wiz_crMany',
 * 'wiz'
 * );
 *
 * But for this two-level thing to work it also requires that the parent
 * module (the real backend module) supports it.
 * This is the case for the modules web_func and web_info since they have two
 * times inclusion sections in their index.php scripts. For example (from web_func):
 *
 * Make instance:
 * $SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("SC_mod_web_func_index");
 * $SOBE->init();
 *
 * Include files?
 * foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
 * $SOBE->checkExtObj();	// Checking for first level external objects
 *
 * Repeat Include files! - if any files has been added by second-level extensions
 * foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
 * $SOBE->checkSubExtObj(); // Checking second level external objects
 *
 * $SOBE->main();
 * $SOBE->printContent();
 *
 * Notice that the first part is as usual: Include classes and call
 * $SOBE->checkExtObj() to initialize any level-1 sub-modules.
 * But then again ->include_once is traversed IF the initialization of
 * the level-1 modules might have added more files!!
 * And after that $SOBE->checkSubExtObj() is called to initialize the second level.
 *
 * In this way even a third level could be supported - but most likely that is
 * a too layered model to be practical.
 *
 * Anyways, the final interesting thing is to see what the framework
 * "func_wizard" actually does:
 *
 * class WebFunctionWizardsBaseController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {
 * var $localLangFile = "locallang.php";
 * var $function_key = "wiz";
 * function init(&$pObj, $conf) {
 * OK, handles ordinary init. This includes setting up the
 * menu array with ->modMenu
 * parent::init($pObj,$conf);
 * Making sure that any further external classes are added to the
 * include_once array. Notice that inclusion happens twice
 * in the main script because of this!!!
 * $this->handleExternalFunctionValue();
 * }
 * }
 *
 * Notice that the handleExternalFunctionValue of this class
 * is called and that the ->function_key internal var is set!
 *
 * The two level-2 sub-module "wizard_crpages" and "wizard_sortpages"
 * are totally normal "submodules".
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see \TYPO3\CMS\Backend\Module\BaseScriptClass
 * @see tx_funcwizards_webfunc::init()
 * @see tx_funcwizards_webfunc
 * @see tx_wizardsortpages_webfunc_2
 */
abstract class AbstractFunctionModule {

	/**
	 * Contains a reference to the parent (calling) object (which is probably an instance of
	 * an extension class to \TYPO3\CMS\Backend\Module\BaseScriptClass
	 *
	 * @var \TYPO3\CMS\Backend\Module\BaseScriptClass
	 * @see init()
	 * @todo Define visibility
	 */
	public $pObj;

	/**
	 * Set to the directory name of this class file.
	 *
	 * @see init()
	 * @todo Define visibility
	 */
	public $thisPath = '';

	/**
	 * Can be hardcoded to the name of a locallang.php file (from the same directory as the class file) to use/load
	 *
	 * @see incLocalLang()
	 * @todo Define visibility
	 */
	public $localLangFile = 'locallang.php';

	/**
	 * Contains module configuration parts from TBE_MODULES_EXT if found
	 *
	 * @see handleExternalFunctionValue()
	 * @todo Define visibility
	 */
	public $extClassConf;

	/**
	 * If this value is set it points to a key in the TBE_MODULES_EXT array (not on the top level..) where another classname/filepath/title can be defined for sub-subfunctions.
	 * This is a little hard to explain, so see it in action; it used in the extension 'func_wizards' in order to provide yet a layer of interfacing with the backend module.
	 * The extension 'func_wizards' has this description: 'Adds the 'Wizards' item to the function menu in Web>Func. This is just a framework for wizard extensions.' - so as you can see it is designed to allow further connectivity - 'level 2'
	 *
	 * @see handleExternalFunctionValue(), tx_funcwizards_webfunc
	 * @todo Define visibility
	 */
	public $function_key = '';

	/**
	 * Initialize the object
	 *
	 * @param object $pObj A reference to the parent (calling) object (which is probably an instance of an
	 * extension class to \TYPO3\CMS\Backend\Module\BaseScriptClass
	 *
	 * @param array $conf The configuration set for this module - from global array TBE_MODULES_EXT
	 * @return void
	 * @see \TYPO3\CMS\Backend\Module\BaseScriptClass::checkExtObj()
	 * @todo Define visibility
	 */
	public function init(&$pObj, $conf) {
		$this->pObj = $pObj;
		// Path of this script:
		$this->thisPath = dirname($conf['path']);
		if (!@is_dir($this->thisPath)) {
			throw new \RuntimeException('TYPO3 Fatal Error: Extension "' . $this->thisPath . ' was not a directory as expected...', 1270853912);
		}
		// Local lang:
		$this->incLocalLang();
		// Setting MOD_MENU items as we need them for logging:
		$this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
	}

	/**
	 * If $this->function_key is set (which means there are two levels of object connectivity) then $this->extClassConf is loaded with the TBE_MODULES_EXT configuration for that sub-sub-module
	 *
	 * @return void
	 * @see $function_key, tx_funcwizards_webfunc::init()
	 * @todo Define visibility
	 */
	public function handleExternalFunctionValue() {
		// Must clean first to make sure the correct key is set...
		$this->pObj->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->pObj->MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $this->pObj->MCONF['name']);
		if ($this->function_key) {
			$this->extClassConf = $this->pObj->getExternalItemConfig($this->pObj->MCONF['name'], $this->function_key, $this->pObj->MOD_SETTINGS[$this->function_key]);
			if (is_array($this->extClassConf) && $this->extClassConf['path']) {
				$this->pObj->include_once[] = $this->extClassConf['path'];
			}
		}
	}

	/**
	 * Including any locallang file configured and merging its content over the current global LOCAL_LANG array (which is EXPECTED to exist!!!)
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function incLocalLang() {
		if ($this->localLangFile && (@is_file(($this->thisPath . '/' . $this->localLangFile)) || @is_file(($this->thisPath . '/' . substr($this->localLangFile, 0, -4) . '.xml')) || @is_file(($this->thisPath . '/' . substr($this->localLangFile, 0, -4) . '.xlf')))) {
			$LOCAL_LANG = $GLOBALS['LANG']->includeLLFile($this->thisPath . '/' . $this->localLangFile, FALSE);
			if (is_array($LOCAL_LANG)) {
				$GLOBALS['LOCAL_LANG'] = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule((array) $GLOBALS['LOCAL_LANG'], $LOCAL_LANG);
			}
		}
	}

	/**
	 * Same as \TYPO3\CMS\Backend\Module\BaseScriptClass::checkExtObj()
	 *
	 * @return void
	 * @see \TYPO3\CMS\Backend\Module\BaseScriptClass::checkExtObj()
	 * @todo Define visibility
	 */
	public function checkExtObj() {
		if (is_array($this->extClassConf) && $this->extClassConf['name']) {
			$this->extObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->extClassConf['name']);
			$this->extObj->init($this->pObj, $this->extClassConf);
			// Re-write:
			$this->pObj->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->pObj->MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $this->pObj->MCONF['name']);
		}
	}

	/**
	 * Calls the main function inside ANOTHER sub-submodule which might exist.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function extObjContent() {
		if (is_object($this->extObj)) {
			return $this->extObj->main();
		}
	}

	/**
	 * Dummy function - but is used to set up additional menu items for this submodule.
	 * For an example see the extension 'cms' where the 'web_info' submodule is defined in cms/web_info/class.tx_cms_webinfo.php, tx_cms_webinfo_page::modMenu()
	 *
	 * @return array A MOD_MENU array which will be merged together with the one from the parent object
	 * @see init(), tx_cms_webinfo_page::modMenu()
	 * @todo Define visibility
	 */
	public function modMenu() {
		return array();
	}

}


?>