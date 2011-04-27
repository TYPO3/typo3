<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2005-2010 Karsten Dambekalns <karsten@typo3.org>
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
 * Module: Extension manager
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Karsten Dambekalns <karsten@typo3.org>
 * @author	Steffen Kamper <info@sk-typo3.de>
 */

$GLOBALS['LANG']->includeLLFile(t3lib_extMgm::extPath('em') . 'language/locallang.xml');

// from tx_ter by Robert Lemke
define('TX_TER_RESULT_EXTENSIONSUCCESSFULLYUPLOADED', '10504');

define('EM_INSTALL_VERSION_MIN', 1);
define('EM_INSTALL_VERSION_MAX', 2);
define('EM_INSTALL_VERSION_STRICT', 3);

unset($MCONF);
require('conf.php');

$BE_USER->modAccess($MCONF, 1);


/**
 * Module: Extension manager
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Karsten Dambekalns <karsten@typo3.org>
 * @package TYPO3
 * @subpackage core
 */
class SC_mod_tools_em_index extends t3lib_SCbase {

	// Internal, static:
	var $versionDiffFactor = 1; // This means that version difference testing for import is detected for sub-versions only, not dev-versions. Default: 1000
	var $systemInstall = 0; // If "1" then installs in the sysext directory is allowed. Default: 0
	var $requiredExt = ''; // List of required extension (from TYPO3_CONF_VARS)
	var $maxUploadSize = 31457280; // Max size in bytes of extension upload to repository
	var $kbMax = 500; // Max size in kilobytes for files to be edited.
	var $doPrintContent = true; // If set (default), the function printContent() will echo the content which was collected in $this->content. You can set this to FALSE in order to echo content from elsewhere, fx. when using outbut buffering
	var $listingLimit = 500; // List that many extension maximally at one time (fixing memory problems)
	var $listingLimitAuthor = 250; // List that many extension maximally at one time (fixing memory problems)
	var $script = ''; //URL to this script


	var $categories = array(); // Extension Categories (static var); see init()

	var $states = array(); // Extension States; see init()

	var $detailCols = array(
		0 => 2,
		1 => 5,
		2 => 6,
		3 => 6,
		4 => 4,
		5 => 1
	);

	var $fe_user = array(
		'username' => '',
		'password' => '',
	);

	var $privacyNotice; // Set in init()
	var $securityHint; // Set in init()
	var $editTextExtensions = 'html,htm,txt,css,tmpl,inc,php,sql,conf,cnf,pl,pm,sh,xml,ChangeLog';
	var $nameSpaceExceptions = 'beuser_tracking,design_components,impexp,static_file_edit,cms,freesite,quickhelp,classic_welcome,indexed_search,sys_action,sys_workflows,sys_todos,sys_messages,direct_mail,sys_stat,tt_address,tt_board,tt_calender,tt_guest,tt_links,tt_news,tt_poll,tt_rating,tt_products,setup,taskcenter,tsconfig_help,context_help,sys_note,tstemplate,lowlevel,install,belog,beuser,phpmyadmin,aboutmodules,imagelist,setup,taskcenter,sys_notepad,viewpage,adodb';


	// Default variables for backend modules
	var $MCONF = array(); // Module configuration
	var $MOD_MENU = array(); // Module menu items
	var $MOD_SETTINGS = array(); // Module session settings
	/**
	 * Document Template Object
	 *
	 * @var noDoc
	 */
	var $doc;
	var $content; // Accumulated content

	var $inst_keys = array(); // Storage of installed extensions
	var $gzcompress = 0; // Is set true, if system support compression.

	/**
	 * Instance of EM API
	 *
	 * @var tx_em_API
	 */
	protected $api;

	/**
	 * Instance of TER connection handler
	 *
	 * @var tx_em_Connection_Ter
	 */
	public $terConnection;


	/**
	 * XML handling class for the TYPO3 Extension Manager
	 *
	 * @var tx_em_Tools_XmlHandler
	 */
	public $xmlHandler;


	/**
	 * Class for printing extension lists
	 *
	 * @var tx_em_Extensions_List
	 */
	public $extensionList;

	/**
	 * Class for extension details
	 *
	 * @var tx_em_Extensions_Details
	 */
	public $extensionDetails;

	/**
	 * Class for new ExtJs Extension Manager
	 *
	 * @var tx_em_ExtensionManager
	 */
	public $extensionmanager;

	/**
	 * Class for translation handling
	 *
	 * @var tx_em_Translations
	 */
	public $translations;

	/**
	 * Class for install extensions
	 *
	 * @var tx_em_Install
	 */
	public $install;

	/**
	 * Settings object
	 *
	 * @var tx_em_Settings
	 */
	public $settings;


	var $JScode; // JavaScript code to be forwared to $this->doc->JScode

	// GPvars:
	var $CMD = array(); // CMD array
	var $listRemote; // If set, connects to remote repository
	var $lookUpStr; // Search string when listing local extensions


	protected $noDocHeader = 0;

	/*********************************
	 *
	 * Standard module initialization
	 *
	 *********************************/

	/**
	 * Standard init function of a module.
	 *
	 * @return	void
	 */
	function init() {
		/**
		 * Extension Categories (static var)
		 * Content must be redundant with the same internal variable as in class.tx_extrep.php!
		 */
		$this->categories = array(
			'be' => $GLOBALS['LANG']->getLL('category_BE'),
			'module' => $GLOBALS['LANG']->getLL('category_BE_modules'),
			'fe' => $GLOBALS['LANG']->getLL('category_FE'),
			'plugin' => $GLOBALS['LANG']->getLL('category_FE_plugins'),
			'misc' => $GLOBALS['LANG']->getLL('category_miscellanous'),
			'services' => $GLOBALS['LANG']->getLL('category_services'),
			'templates' => $GLOBALS['LANG']->getLL('category_templates'),
			'example' => $GLOBALS['LANG']->getLL('category_examples'),
			'doc' => $GLOBALS['LANG']->getLL('category_documentation')
		);

		/**
		 * Extension States
		 * Content must be redundant with the same internal variable as in class.tx_extrep.php!
		 */
		$this->states = tx_em_Tools::getStates();

		$this->script = 'mod.php?M=tools_em';
		$this->privacyNotice = $GLOBALS['LANG']->getLL('privacy_notice');
		$securityMessage = $GLOBALS['LANG']->getLL('security_warning_extensions') .
				'<br /><br />' . sprintf($GLOBALS['LANG']->getLL('security_descr'),
			'<a href="http://typo3.org/teams/security/" target="_blank">', '</a>'
		);
		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$securityMessage,
			$GLOBALS['LANG']->getLL('security_header'),
			t3lib_FlashMessage::INFO
		);
		$this->securityHint = $flashMessage->render();

		$this->excludeForPackaging = $GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging'];

		// Setting module configuration:
		$this->MCONF = $GLOBALS['MCONF'];

		// Setting GPvars:
		$this->CMD = is_array(t3lib_div::_GP('CMD')) ? t3lib_div::_GP('CMD') : array();
		$this->lookUpStr = trim(t3lib_div::_GP('lookUp'));
		$this->listRemote = t3lib_div::_GP('ter_connect');
		$this->listRemote_search = trim(t3lib_div::_GP('ter_search'));
		$this->noDocHeader = intval(t3lib_div::_GP('nodoc') > 0);

		$this->settings = t3lib_div::makeInstance('tx_em_Settings');
		$this->install = t3lib_div::makeInstance('tx_em_Install', $this);

		if (t3lib_div::_GP('silentMode') || $this->noDocHeader) {
			$this->CMD['silentMode'] = 1;
			$this->noDocHeader = 1;
		}

		if ($this->CMD['silentMode']) {
			$this->install->setSilentMode(TRUE);
		}

		// Configure menu
		$this->menuConfig();

		// Setting internal static:

		$this->requiredExt = t3lib_div::trimExplode(',', t3lib_extMgm::getRequiredExtensionList(), TRUE);

		// Initialize Document Template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/em_index.html');

		// Initialize helper objects
		$this->api = t3lib_div::makeInstance('tx_em_API');
		$this->terConnection = t3lib_div::makeInstance('tx_em_Connection_Ter', $this);
		$this->terConnection->wsdlURL = $GLOBALS['TYPO3_CONF_VARS']['EXT']['em_wsdlURL'];


		$this->xmlHandler = t3lib_div::makeInstance('tx_em_Tools_XmlHandler');
		$this->xmlHandler->emObj = $this;
		$this->xmlHandler->useObsolete = $this->MOD_SETTINGS['display_obsolete'];


		// Initialize newListing
		if (isset($this->MOD_MENU['function']['extensionmanager'])) {
			$this->extensionmanager = t3lib_div::makeInstance('tx_em_ExtensionManager', $this);
		} else {
			$this->extensionmanager = &$this;
		}


		// Output classes
		$this->extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		$this->extensionDetails = t3lib_div::makeInstance('tx_em_Extensions_Details', $this);
		$this->translations = t3lib_div::makeInstance('tx_em_Translations', $this);


		// the id is needed for getting same styles TODO: general table styles
		$this->doc->bodyTagId = 'typo3-mod-tools-em-index-php';

		// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL)	{	//
				window.location.href = URL;
			}
		');

		// Reload left frame menu
		if ($this->CMD['refreshMenu']) {
			$this->doc->JScode .= $this->doc->wrapScriptTags('
				if(top.refreshMenu) {
					top.refreshMenu();
				} else {
					top.TYPO3ModuleMenu.refreshMenu();
				}
			');
		}


		// Descriptions:
		$this->descrTable = '_MOD_' . $this->MCONF['name'];
		if ($GLOBALS['BE_USER']->uc['edit_showFieldHelp']) {
			$GLOBALS['LANG']->loadSingleTableDescription($this->descrTable);
		}

		// Setting username/password etc. for upload-user:
		$this->fe_user['username'] = $this->MOD_SETTINGS['fe_u'];
		$this->fe_user['password'] = $this->MOD_SETTINGS['fe_p'];
		parent::init();
		$this->handleExternalFunctionValue('singleDetails');
	}

	/**
	 * This function is a copy of the same function in t3lib_SCbase with one modification:
	 * In contrast to t3lib_SCbase::handleExternalFunctionValue() this function merges the $this->extClassConf array
	 * instead of overwriting it. That was necessary for including the Kickstarter as a submodule into the 'singleDetails'
	 * selectorbox as well as in the main 'function' selectorbox.
	 *
	 * @param	string		Mod-setting array key
	 * @param	string		Mod setting value, overriding the one in the key
	 * @return	void
	 * @see t3lib_SCbase::handleExternalFunctionValue()
	 */
	function handleExternalFunctionValue($MM_key = 'function', $MS_value = NULL) {
		$MS_value = is_null($MS_value) ? $this->MOD_SETTINGS[$MM_key] : $MS_value;
		$externalItems = $this->getExternalItemConfig($this->MCONF['name'], $MM_key, $MS_value);
		if (is_array($externalItems)) {
			$this->extClassConf = array_merge($externalItems, is_array($this->extClassConf) ? $this->extClassConf : array());
		}
		if (is_array($this->extClassConf) && $this->extClassConf['path']) {
			$this->include_once[] = $this->extClassConf['path'];
		}
	}

	/**
	 * Configuration of which mod-menu items can be used
	 *
	 * @return	void
	 */
	function menuConfig() {
			// MENU-ITEMS:
		$this->MOD_MENU = $this->settings->MOD_MENU;
		$globalSettings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['em']);

		if (!is_array($globalSettings)) {
				// no settings saved yet, set default values
			$globalSettings['showOldModules'] = 1;
			$globalSettings['inlineToWindow'] = 1;
			$globalSettings['displayMyExtensions'] = 0;
		}

		if ($globalSettings['showOldModules'] == 0) {
			unset(
				$this->MOD_MENU['function']['loaded_list'],
				$this->MOD_MENU['function']['installed_list'],
				$this->MOD_MENU['function']['import'],
				$this->MOD_MENU['function']['translations'],
				$this->MOD_MENU['function']['settings']
			);
		}
		$this->MOD_MENU['singleDetails'] = $this->mergeExternalItems($this->MCONF['name'], 'singleDetails', $this->MOD_MENU['singleDetails']);
		$this->MOD_MENU['extensionInfo'] = $this->mergeExternalItems($this->MCONF['name'], 'singleDetails', array());


			// page/be_user TSconfig settings and blinding of menu-items
		if (!$GLOBALS['BE_USER']->getTSConfigVal('mod.' . $this->MCONF['name'] . '.allowTVlisting')) {
			unset($this->MOD_MENU['display_details'][3]);
			unset($this->MOD_MENU['display_details'][4]);
			unset($this->MOD_MENU['display_details'][5]);
		}

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);


		if ($this->MOD_SETTINGS['function'] == 2) {
			// If listing from online repository, certain items are removed though:
			unset($this->MOD_MENU['listOrder']['type']);
			unset($this->MOD_MENU['display_details'][2]);
			unset($this->MOD_MENU['display_details'][3]);
			unset($this->MOD_MENU['display_details'][4]);
			unset($this->MOD_MENU['display_details'][5]);
			$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
		}

		$this->settings->saveSettings($this->MOD_SETTINGS);
		parent::menuConfig();

		$this->settings->saveSettings($this->MOD_SETTINGS);
	}

	/**
	 * Main function for Extension Manager module.
	 *
	 * @return	void
	 */
	function main() {

		$menu = '';

		if (empty($this->MOD_SETTINGS['mirrorListURL'])) {
			$this->MOD_SETTINGS['mirrorListURL'] = $GLOBALS['TYPO3_CONF_VARS']['EXT']['em_mirrorListURL'];
		}

		// Starting page:
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('header'));

		// Command given which is executed regardless of main menu setting:
		if ($this->CMD['showExt']) { // Show details for a single extension
			$this->showExtDetails($this->CMD['showExt']);
		} elseif ($this->CMD['requestInstallExtensions']) { // Show details for a single extension
			$this->requestInstallExtensions($this->CMD['requestInstallExtensions']);
		} elseif ($this->CMD['importExt'] || $this->CMD['uploadExt']) { // Imports an extension from online rep.
			$err = $this->importExtFromRep($this->CMD['importExt'], $this->CMD['extVersion'], $this->CMD['loc'], $this->CMD['uploadExt']);
			if ($err) {
				$this->content .= $this->doc->section('', tx_em_Tools::rfw($err));
			}
			if (!$err && $this->CMD['importExt']) {
				$this->translations->installTranslationsForExtension($this->CMD['importExt'], $this->getMirrorURL());
			}
		} elseif ($this->CMD['importExtInfo']) { // Gets detailed information of an extension from online rep.
			$this->importExtInfo($this->CMD['importExtInfo'], $this->CMD['extVersion']);
		} elseif ($this->CMD['downloadExtFile']) {
			tx_em_Tools::sendFile($this->CMD['downloadExtFile']);
		} else { // No command - we show what the menu setting tells us:
			if (t3lib_div::inList('loaded_list,installed_list,import', $this->MOD_SETTINGS['function'])) {
				$menu .= '&nbsp;' . $GLOBALS['LANG']->getLL('group_by') . '&nbsp;' . t3lib_BEfunc::getFuncMenu(0, 'SET[listOrder]', $this->MOD_SETTINGS['listOrder'], $this->MOD_MENU['listOrder']) .
					'&nbsp;&nbsp;' . $GLOBALS['LANG']->getLL('show') . '&nbsp;' . t3lib_BEfunc::getFuncMenu(0, 'SET[display_details]', $this->MOD_SETTINGS['display_details'], $this->MOD_MENU['display_details']) . '<br />';
			}
			if (t3lib_div::inList('loaded_list,installed_list,updates', $this->MOD_SETTINGS['function'])) {
				$menu .= '<label for="checkDisplayShy">' . $GLOBALS['LANG']->getLL('display_shy') . '</label>&nbsp;&nbsp;' . t3lib_BEfunc::getFuncCheck(0, 'SET[display_shy]', $this->MOD_SETTINGS['display_shy'], '', '', 'id="checkDisplayShy"');
			}
			if (t3lib_div::inList('import', $this->MOD_SETTINGS['function']) && strlen($this->fe_user['username'])) {
				$menu .= '<label for="checkDisplayOwn">' . $GLOBALS['LANG']->getLL('only_my_ext') . '</label>&nbsp;&nbsp;' . t3lib_BEfunc::getFuncCheck(0, 'SET[display_own]', $this->MOD_SETTINGS['display_own'], '', '', 'id="checkDisplayOwn"');
			}
			if (t3lib_div::inList('loaded_list,installed_list,import', $this->MOD_SETTINGS['function'])) {
				$menu .= '&nbsp;&nbsp;<label for="checkDisplayObsolete">' . $GLOBALS['LANG']->getLL('show_obsolete') . '</label>&nbsp;&nbsp;' . t3lib_BEfunc::getFuncCheck(0, 'SET[display_obsolete]', $this->MOD_SETTINGS['display_obsolete'], '', '', 'id="checkDisplayObsolete"');
			}

			$this->content .= $menu ? $this->doc->section('', '<form action="' . $this->script . '" method="post" name="pageform"><span class="nobr">' . $menu . '</span></form>') : '';


			$view = $this->MOD_SETTINGS['function'];
			if (t3lib_div::_GP('view')) {
					// temporary overwrite the view with GP var. Used from ExtJS without changing the submodule
				$view = t3lib_div::_GP('view');
			}

			switch ($view) {
				case 'loaded_list':
					// Lists loaded (installed) extensions
					$headline = $GLOBALS['LANG']->getLL('loaded_exts');
					$headline = t3lib_BEfunc::wrapInHelp('_MOD_tools_em', 'loaded', $headline);
					$content = $this->extensionList->extensionList_loaded();

					$this->content .= $this->doc->section($headline, $content, FALSE, TRUE, FALSE, TRUE);
					break;
				case 'installed_list':
					// Lists the installed (available) extensions
					$headline = sprintf($GLOBALS['LANG']->getLL('available_extensions'), $this->MOD_MENU['listOrder'][$this->MOD_SETTINGS['listOrder']]);
					$headline = t3lib_BEfunc::wrapInHelp('_MOD_tools_em', 'avail', $headline);
					$content = $this->extensionList->extensionList_installed();

					$this->content .= $this->doc->section($headline, $content, FALSE, TRUE, FALSE, TRUE);
					break;
				case 'import':
					// Lists the extensions available from online rep.
					$this->extensionList_import();
					break;
				case 'settings':
					// Shows the settings screen
					$headline = $GLOBALS['LANG']->getLL('repository_settings');
					$headline = t3lib_BEfunc::wrapInHelp('_MOD_tools_em', 'settings', $headline);
					$content = $this->alterSettings();

					$this->content .= $this->doc->section($headline, $content, FALSE, TRUE, FALSE, TRUE);
					break;
				case 'translations':
					// Allows to set the translation preferences and check the status
					$this->translations->translationHandling();
					break;
				case 'updates':
					// Shows a list of extensions with updates in TER
					$this->checkForUpdates();
					break;
				case 'extensionmanager':
					$this->content .= $this->extensionmanager->render();
					break;
				default:
					$this->extObjContent();
					break;
			}
		}

			// closing any form?
		$formTags = substr_count($this->content, '<form') + substr_count($this->content, '</form');
		if ($formTags % 2 > 0) {
			$this->content .= '</form>';
		}

		if (!$this->noDocHeader) {
			// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers = array(
				'CSH' => $docHeaderButtons['csh'],
				'FUNC_MENU' => $this->getFuncMenu(),
				'CONTENT' => $this->content
			);

			// Build the <body> for the module
			$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		}
			// Renders the module page
		$this->content = $this->doc->render(
			'Extension Manager',
			$this->content
	    );

	}

	/**
	 * Print module content. Called as last thing in the global scope.
	 *
	 * @return	void
	 */
	function printContent() {
		if ($this->doPrintContent) {
			echo $this->content;
		}
	}

	/**
	 * Create the function menu
	 *
	 * @return	string	HTML of the function menu
	 */
	public function getFuncMenu() {
		$funcMenu = '';
		if (!$this->CMD['showExt'] && !$this->CMD['requestInstallExtensions'] && !$this->CMD['importExt'] && !$this->CMD['uploadExt'] && !$this->CMD['importExtInfo']) {
			$funcMenu = t3lib_BEfunc::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		} elseif ($this->CMD['showExt'] && (!$this->CMD['standAlone'] && !t3lib_div::_GP('standAlone'))) {
			$funcMenu = t3lib_BEfunc::getFuncMenu(0, 'SET[singleDetails]', $this->MOD_SETTINGS['singleDetails'], $this->MOD_MENU['singleDetails'], '', '&CMD[showExt]=' . $this->CMD['showExt']);
		}
		return $funcMenu;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	public function getButtons() {

		$buttons = array(
			'csh' => '',
			'back' => '',
			'shortcut' => ''
		);

		// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('CMD', 'function', $this->MCONF['name']);
		}
		// Back
		if (($this->CMD['showExt'] && (!$this->CMD['standAlone'] && !t3lib_div::_GP('standAlone'))) || ($this->CMD['importExt'] || $this->CMD['uploadExt'] && (!$this->CMD['standAlone'])) || $this->CMD['importExtInfo']) {
			$buttons['back'] = '<a href="' . t3lib_div::linkThisScript(array(
				'CMD' => ''
			)) . '" class="typo3-goBack" title="' . $GLOBALS['LANG']->getLL('go_back') . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-view-go-back') .
					'</a>';
		}

		return $buttons;
	}


	/*********************************
	 *
	 * Function Menu Applications
	 *
	 *********************************/


	/**
	 * Listing remote extensions from online repository
	 *
	 * @return	void
	 */
	function extensionList_import() {
		$content = '';

		// Listing from online repository:
		if ($this->listRemote) {
			list($inst_list,) = $this->extensionList->getInstalledExtensions();
			$this->inst_keys = array_flip(array_keys($inst_list));

			$this->detailCols[1] += 6;

			// see if we have an extensionlist at all
			$this->extensionCount = $this->xmlHandler->countExtensions();
			if (!$this->extensionCount) {
				$content .= $this->fetchMetaData('extensions');
			}

			if ($this->MOD_SETTINGS['listOrder'] == 'author_company') {
				$this->listingLimit = $this->listingLimitAuthor;
			}

			$this->pointer = intval(t3lib_div::_GP('pointer'));
			$offset = $this->listingLimit * $this->pointer;

			if ($this->MOD_SETTINGS['display_own'] && strlen($this->fe_user['username'])) {
				$this->xmlHandler->searchExtensionsXML($this->listRemote_search, $this->fe_user['username'], $this->MOD_SETTINGS['listOrder'], TRUE);
			} else {
				$this->xmlHandler->searchExtensionsXML($this->listRemote_search, '', $this->MOD_SETTINGS['listOrder'], TRUE, FALSE, $offset, $this->listingLimit);
			}
			if (count($this->xmlHandler->extensionsXML)) {
				list($list, $cat) = $this->extensionList->prepareImportExtList(TRUE);

				// Available extensions
				if (is_array($cat[$this->MOD_SETTINGS['listOrder']])) {
					$lines = array();
					$lines[] = $this->extensionList->extensionListRowHeader(' class="t3-row-header"', array('<td><img src="clear.gif" width="18" height="1" alt="" /></td>'), 1);

					foreach ($cat[$this->MOD_SETTINGS['listOrder']] as $catName => $extEkeys) {
						if (count($extEkeys)) {
							$lines[] = '<tr><td colspan="' . (3 + $this->detailCols[$this->MOD_SETTINGS['display_details']]) . '"><br /></td></tr>';
							$lines[] = '<tr><td colspan="' . (3 + $this->detailCols[$this->MOD_SETTINGS['display_details']]) . '">' . t3lib_iconWorks::getSpriteIcon('apps-filetree-folder-default') . '<strong>' . htmlspecialchars($this->listOrderTitle($this->MOD_SETTINGS['listOrder'], $catName)) . '</strong></td></tr>';
							natcasesort($extEkeys);
							foreach ($extEkeys as $extKey => $value) {
								$version = array_keys($list[$extKey]['versions']);
								$version = end($version);
								$ext = $list[$extKey]['versions'][$version];
								$ext['downloadcounter_all'] = $list[$extKey]['downloadcounter'];
								$ext['_ICON'] = $list[$extKey]['_ICON'];
								$loadUnloadLink = '';
								if ($inst_list[$extKey]['type'] != 'S' && (!isset($inst_list[$extKey]) || tx_em_Tools::versionDifference($version, $inst_list[$extKey]['EM_CONF']['version'], $this->versionDiffFactor))) {
									if (isset($inst_list[$extKey])) {
										// update
										if ($inst_list[$extKey]['EM_CONF']['state'] != 'excludeFromUpdates') {
											$loc = ($inst_list[$extKey]['type'] == 'G' ? 'G' : 'L');
											$aUrl = t3lib_div::linkThisScript(array(
												'CMD[importExt]' => $extKey,
												'CMD[extVersion]' => $version,
												'CMD[loc]' => $loc
											));
											$loadUnloadLink .= '<a href="' . htmlspecialchars($aUrl) . '" title="' . sprintf($GLOBALS['LANG']->getLL('do_update'), ($loc == 'G' ? $GLOBALS['LANG']->getLL('global') : $GLOBALS['LANG']->getLL('local'))) . '">' .
													t3lib_iconWorks::getSpriteIcon('actions-system-extension-update') .
													'</a>';
										} else {
											// extension is marked as "excludeFromUpdates"
											$loadUnloadLink .= t3lib_iconWorks::getSpriteIcon('status-dialog-warning', $GLOBALS['LANG']->getLL('excluded_from_updates'));
										}
									} else {
										// import
										$aUrl = t3lib_div::linkThisScript(array(
											'CMD[importExt]' => $extKey,
											'CMD[extVersion]' => $version,
											'CMD[loc]' => 'L'
										));
										$loadUnloadLink .= '<a href="' . htmlspecialchars($aUrl) . '" title="' . $GLOBALS['LANG']->getLL('import_to_local_dir') . '">' . t3lib_iconWorks::getSpriteIcon('actions-system-extension-import') . '</a>';
									}
								} else {
									$loadUnloadLink = '&nbsp;';
								}

								if (isset($inst_list[$extKey])) {
									$theRowClass = t3lib_extMgm::isLoaded($extKey) ? 'em-listbg1' : 'em-listbg2';
								} else {
									$theRowClass = 'em-listbg3';
								}

								$lines[] = $this->extensionList->extensionListRow(
									$extKey, $ext, array(
									'<td class="bgColor">' . $loadUnloadLink . '</td>'
								), $theRowClass, $inst_list, 1, t3lib_div::linkThisScript(array(
									'CMD[importExtInfo]' => rawurlencode($extKey)
								)));
								unset($list[$extKey]);
							}
						}
					}
					unset($list);

					// headline and CSH
					$headline = $GLOBALS['LANG']->getLL('extensions_repository_group_by') . ' ' .
							$this->MOD_MENU['listOrder'][$this->MOD_SETTINGS['listOrder']];
					$headline = t3lib_BEfunc::wrapInHelp('_MOD_tools_em', 'import_ter', $headline);

					$onsubmit = "window.location.href='" . $this->script . "&ter_connect=1&ter_search='+escape(this.elements['lookUp'].value);return false;";
					$content .= '<form action="' . $this->script . '" method="post" onsubmit="' . htmlspecialchars($onsubmit) .
							'"><label for="lookUp">' . $GLOBALS['LANG']->getLL('list_or_look_up_extensions') . '</label><br />
							<input type="text" id="lookUp" name="lookUp" value="' . htmlspecialchars($this->listRemote_search) .
							'" /> <input type="submit" value="' . $GLOBALS['LANG']->getLL('look_up_button') . '" /></form><br /><br />';

					$content .= $this->browseLinks();

					$content .= '

					<!-- TER Extensions list -->
					<table border="0" cellpadding="2" cellspacing="1">' . implode(LF, $lines) . '</table>';
					$content .= '<br />' . $this->browseLinks();
					$content .= '<br /><br />' . $this->securityHint;
					$content .= '<br /><br /><strong>' . $GLOBALS['LANG']->getLL('privacy_notice_header') .
							'</strong><br /> ' . $this->privacyNotice;

					$this->content .= $this->doc->section($headline, $content, FALSE, TRUE, FALSE, TRUE);

					// Plugins which are NOT uploaded to repository but present on this server.
					$content = '';
					$lines = array();
					if (count($this->inst_keys)) {
						foreach ($this->inst_keys as $extKey => $value) {
							$this->xmlHandler->searchExtensionsXMLExact($extKey, '', '', TRUE, TRUE);
							if ((strlen($this->listRemote_search) && !stristr($extKey, $this->listRemote_search)) || isset($this->xmlHandler->extensionsXML[$extKey])) {
								continue;
							}

							$loadUnloadLink = t3lib_extMgm::isLoaded($extKey) ?
									'<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
										'CMD[showExt]' => $extKey,
										'CMD[remove]' => 1,
										'CMD[clrCmd]' => 1,
										'SET[singleDetails]' => 'info'
									))) . '">' . tx_em_Tools::removeButton() . '</a>' :
									'<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
										'CMD[showExt]' => $extKey,
										'CMD[load]' => 1,
										'CMD[clrCmd]' => 1,
										'SET[singleDetails]' => 'info'
									))) . '">' . tx_em_Tools::installButton() . '</a>';
							if (in_array($extKey, $this->requiredExt)) {
								$loadUnloadLink = '<strong>' . tx_em_Tools::rfw($GLOBALS['LANG']->getLL('extension_required_short')) . '</strong>';
							}
							$lines[] = $this->extensionList->extensionListRow($extKey, $inst_list[$extKey], array('<td class="bgColor">' . $loadUnloadLink . '</td>'), t3lib_extMgm::isLoaded($extKey) ? 'em-listbg1' : 'em-listbg2');
						}
					}
					if (count($lines)) {
						$content .= $GLOBALS['LANG']->getLL('list_of_local_extensions') .
								'<br />' . $GLOBALS['LANG']->getLL('might_be_user_defined') . '<br /><br />';
						$content .= '<table border="0" cellpadding="2" cellspacing="1">' .
								$this->extensionList->extensionListRowHeader(' class="t3-row-header"', array('<td><img src="clear.gif" width="18" height="1" alt="" /></td>')) .
								implode('', $lines) . '</table>';
						$this->content .= $this->doc->spacer(20);
						$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('only_on_this_server'), $content, 0, 1);
					}
				}
			} else {
				// headline and CSH
				$headline = $GLOBALS['LANG']->getLL('extensions_repository_group_by') . ' ' .
						$this->MOD_MENU['listOrder'][$this->MOD_SETTINGS['listOrder']];
				$headline = t3lib_BEfunc::wrapInHelp('_MOD_tools_em', 'import_ter', $headline);

				$onsubmit = "window.location.href='" . $this->script . "&ter_connect=1&ter_search='+escape(this.elements['lookUp'].value);return false;";
				$content .= '<form action="' . $this->script . '" method="post" onsubmit="' . htmlspecialchars($onsubmit) .
						'"><label for="lookUp">' .
						$GLOBALS['LANG']->getLL('list_or_look_up_extensions') . '</label><br />
					<input type="text" id="lookUp" name="lookUp" value="' . htmlspecialchars($this->listRemote_search) .
						'" /> <input type="submit" value="' . $GLOBALS['LANG']->getLL('look_up_button') . '" /></form><br /><br />';

				$content .= '<p><strong>' . $GLOBALS['LANG']->getLL('no_matching_extensions') . '</strong></p>';

				$content .= '<br /><br /><strong>' . $GLOBALS['LANG']->getLL('privacy_notice_header') .
						'</strong><br /> ' . $this->privacyNotice;
				$this->content .= $this->doc->section($headline, $content, FALSE, TRUE, 0, TRUE);
			}
		} else {
			// section headline and CSH
			$headline = $GLOBALS['LANG']->getLL('in_repository');
			$headline = t3lib_BEfunc::wrapInHelp('_MOD_tools_em', 'import', $headline);

			$onsubmit = "window.location.href='" . $this->script . "&ter_connect=1&ter_search='+escape(this.elements['lookUp'].value);return false;";
			$content .= '<form action="' . $this->script . '" method="post" onsubmit="' . htmlspecialchars($onsubmit) .
					'"><label for="lookUp">' .
					$GLOBALS['LANG']->getLL('list_or_look_up_extensions') . '</label><br />
				<input type="text" id="lookUp" name="lookUp" value="" /> <input type="submit" value="' .
					$GLOBALS['LANG']->getLL('look_up_button') . '" /><br /><br />';

			if ($this->CMD['fetchMetaData']) { // fetches mirror/extension data from online rep.
				$content .= $this->fetchMetaData($this->CMD['fetchMetaData']);
			} else {
				$onCLick = 'window.location.href="' . t3lib_div::linkThisScript(array(
					'CMD[fetchMetaData]' => 'extensions'
				)) . '";return false;';
				$content .= $GLOBALS['LANG']->getLL('connect_to_ter') . '<br />
					<input type="submit" value="' . $GLOBALS['LANG']->getLL('retrieve_update') .
						'" onclick="' . htmlspecialchars($onCLick) . '" />';
				if (is_file(PATH_site . 'typo3temp/extensions.xml.gz')) {
					$dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
					$timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
					$content .= ' ' . sprintf($GLOBALS['LANG']->getLL('ext_list_last_updated') . ' ',
						date(
							$dateFormat . ', ' . $timeFormat,
							filemtime(PATH_site . 'typo3temp/extensions.xml.gz')
						),
						tx_em_Database::getExtensionCountFromRepository()
					);
				}
			}
			$content .= '</form><br /><br />' . $this->securityHint;
			$content .= '<br /><br /><strong>' . $GLOBALS['LANG']->getLL('privacy_notice_header') .
					'</strong><br />' . $this->privacyNotice;

			$this->content .= $this->doc->section($headline, $content, FALSE, TRUE, FALSE, TRUE);
		}

		// Upload:
		if ($this->importAtAll()) {
			$content = '<form action="' . $this->script . '" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '" method="post">
			<label for="upload_ext_file">' . $GLOBALS['LANG']->getLL('upload_t3x') . '</label><br />
				<input type="file" size="60" id="upload_ext_file" name="upload_ext_file" /><br />' .
					$GLOBALS['LANG']->getLL('upload_to_location') . '<br />
				<select name="CMD[loc]">';
			if (tx_em_Tools::importAsType('L')) {
				$content .= '<option value="L">' . $GLOBALS['LANG']->getLL('local_folder') . '</option>';
			}
			if (tx_em_Tools::importAsType('G')) {
				$content .= '<option value="G">' . $GLOBALS['LANG']->getLL('global_folder') . '</option>';
			}
			if (tx_em_Tools::importAsType('S')) {
				$content .= '<option value="S">' . $GLOBALS['LANG']->getLL('system_folder') . '</option>';
			}
			$content .= '</select><br />
	<input type="checkbox" value="1" name="CMD[uploadOverwrite]" id="checkUploadOverwrite" /> <label for="checkUploadOverwrite">' .
					$GLOBALS['LANG']->getLL('overwrite_ext') . '</label><br />
	<input type="submit" name="CMD[uploadExt]" value="' . $GLOBALS['LANG']->getLL('upload_ext_file') . '" /></form><br />
			';
		} else {
			$content = tx_em_Tools::noImportMsg();
		}

		$this->content .= $this->doc->spacer(20);
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('upload_ext_directly'), $content, 0, 1);
	}

	/**
	 * Generates a link to the next page of extensions
	 *
	 * @return	void
	 */
	function browseLinks() {
		$content = '';
		if ($this->pointer) {
			$content .= '<a href="' . t3lib_div::linkThisScript(array('pointer' => $this->pointer - 1)) .
					'" class="typo3-prevPage"><img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],
				'gfx/pilleft_n.gif', 'width="14" height="14"') .
					' alt="' . $GLOBALS['LANG']->getLL('previous_page') . '" /> ' .
					$GLOBALS['LANG']->getLL('previous_page') . '</a>';
		}
		if ($content) {
			$content .= '&nbsp;&nbsp;&nbsp;';
		}
		if (intval($this->xmlHandler->matchingCount / $this->listingLimit) > $this->pointer) {
			$content .= '<a href="' . t3lib_div::linkThisScript(array('pointer' => $this->pointer + 1)) .
					'" class="typo3-nextPage"><img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],
				'gfx/pilright_n.gif', 'width="14" height="14"') .
					' alt="' . $GLOBALS['LANG']->getLL('next_page') . '" /> ' .
					$GLOBALS['LANG']->getLL('next_page') . '</a>';
		}
		$upper = (($this->pointer + 1) * $this->listingLimit);
		if ($upper > $this->xmlHandler->matchingCount) {
			$upper = $this->xmlHandler->matchingCount;
		}
		if ($content) {
			$content .= '<br /><br />' .
					sprintf($GLOBALS['LANG']->getLL('showing_extensions_from_to'),
							'<strong>' . ($this->pointer * $this->listingLimit + 1) . '</strong>',
							'<strong>' . $upper . '</strong>'
					);
		}
		if ($content) {
			$content .= '<br /><br />';
		}
		return $content;
	}

	/**
	 * Allows changing of settings
	 *
	 * @return	void
	 */
	function alterSettings() {
		$content = '';
		// Prepare the HTML output:
		$content .= '
			<form action="' . $this->script . '" method="post" name="altersettings">
			<fieldset><legend>' . $GLOBALS['LANG']->getLL('user_settings') . '</legend>
			<table border="0" cellpadding="2" cellspacing="2">
				<tr class="bgColor4">
					<td><label for="set_fe_u">' . $GLOBALS['LANG']->getLL('enter_repository_username') . '</label></td>
					<td><input type="text" id="set_fe_u" name="SET[fe_u]" value="' . htmlspecialchars($this->MOD_SETTINGS['fe_u']) . '" /></td>
				</tr>
				<tr class="bgColor4">
					<td><label for="set_fe_p">' . $GLOBALS['LANG']->getLL('enter_repository_password') . '</label></td>
					<td><input type="password" id="set_fe_p" name="SET[fe_p]" value="' . htmlspecialchars($this->MOD_SETTINGS['fe_p']) . '" /></td>
				</tr>
			</table>
			<strong>' . $GLOBALS['LANG']->getLL('notice') . '</strong> ' .
				$GLOBALS['LANG']->getLL('repository_password_info') . '
			</fieldset>
			<br />
			<br />
			<fieldset><legend>' . $GLOBALS['LANG']->getLL('mirror_selection') . '</legend>
			<table border="0" cellpadding="2" cellspacing="2">
				<tr class="bgColor4">
					<td><label for="set_mirror_list_url">' . $GLOBALS['LANG']->getLL('mirror_list_url') . '</label></td>
					<td><input type="text" size="50" id="set_mirror_list_url" name="SET[mirrorListURL]" value="' . htmlspecialchars($this->MOD_SETTINGS['mirrorListURL']) . '" /></td>
				</tr>
			</table>
			</fieldset>
			<br />
			<p>' . $GLOBALS['LANG']->getLL('mirror_select') . '<br /><br /></p>
			<fieldset><legend>' . $GLOBALS['LANG']->getLL('mirror_list') . '</legend>';
		if (!empty($this->MOD_SETTINGS['mirrorListURL'])) {
			if ($this->CMD['fetchMetaData']) { // fetches mirror/extension data from online rep.
				$content .= $this->fetchMetaData($this->CMD['fetchMetaData']);
			} else {
				$content .= '<a href="' . t3lib_div::linkThisScript(array(
					'CMD[fetchMetaData]' => 'mirrors'
				)) . '">' . $GLOBALS['LANG']->getLL('mirror_list_reload') . '</a>';
			}
		}
		$content .= '<br />
			<table cellspacing="4" style="text-align:left; vertical-alignment:top;">
			<tr>
				<td>' . $GLOBALS['LANG']->getLL('mirror_use') . '</td>
				<td>' . $GLOBALS['LANG']->getLL('mirror_name') . '</td>
				<td>' . $GLOBALS['LANG']->getLL('mirror_url') . '</td>
				<td>' . $GLOBALS['LANG']->getLL('mirror_country') . '</td>
				<td>' . $GLOBALS['LANG']->getLL('mirror_sponsored_by') . '</td>
			</tr>
		';

		if (!strlen($this->MOD_SETTINGS['extMirrors'])) {
			$this->fetchMetaData('mirrors');
		}
		$extMirrors = unserialize($this->MOD_SETTINGS['extMirrors']);
		$extMirrors[''] = array('title' => $GLOBALS['LANG']->getLL('mirror_use_random'));
		ksort($extMirrors);
		if (is_array($extMirrors)) {
			foreach ($extMirrors as $k => $v) {
				if (isset($v['sponsor'])) {
					$sponsor = '<a href="' . htmlspecialchars($v['sponsor']['link']) . '" target="_blank"><img src="' . $v['sponsor']['logo'] . '" title="' . htmlspecialchars($v['sponsor']['name']) . '" alt="' . htmlspecialchars($v['sponsor']['name']) . '" /></a>';
				}
				$selected = ($this->MOD_SETTINGS['selectedMirror'] == $k) ? 'checked="checked"' : '';
				$content .= '<tr class="bgColor4">
			<td><input type="radio" name="SET[selectedMirror]" id="selectedMirror' . $k . '" value="' . $k . '" ' . $selected . '/></td><td><label for="selectedMirror' . $k . '">' . htmlspecialchars($v['title']) . '</label></td><td>' . htmlspecialchars($v['host'] . $v['path']) . '</td><td>' . $v['country'] . '</td><td>' . $sponsor . '</td></tr>';
			}
		}
		$content .= '
			</table>
			</fieldset>
			<fieldset>
			<br />
			<table border="0" cellpadding="2" cellspacing="2">
				<tr class="bgColor4">
					<td><label for="set_rep_url">' . $GLOBALS['LANG']->getLL('enter_repository_url') . '</label></td>
					<td><input type="text" size="50" id="set_rep_url" name="SET[rep_url]" value="' . htmlspecialchars($this->MOD_SETTINGS['rep_url']) . '" /></td>
				</tr>
			</table>

			' . $GLOBALS['LANG']->getLL('repository_url_hint') . '<br />
			</fieldset>
			<br />
			<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_tsfe.xml:update') . '" />
			</form>
		';

		return $content;
	}

	/**
	 * Allows to set the translation preferences and check the status
	 *
	 * @return	void
	 */


	/*********************************
	 *
	 * Command Applications (triggered by GET var)
	 *
	 *********************************/

	/**
	 * Returns detailed info about an extension in the online repository
	 *
	 * @param	string		Extension repository uid + optional "private key": [uid]-[key].
	 * @param	[type]		$version: ...
	 * @return	void
	 */
	function importExtInfo($extKey, $version = '') {

		$content = '<form action="' . $this->script . '" method="post" name="pageform">';
		$addUrl = '';
		if ($this->noDocHeader) {
		   $content .= '<input type="hidden" name="nodoc" value="1" />';
		   $addUrl = '&nodoc=1';
	   }
		// Fetch remote data:
		$this->xmlHandler->searchExtensionsXMLExact($extKey, '', '', true, true);
		list($fetchData,) = $this->extensionList->prepareImportExtList(true);

		$versions = array_keys($fetchData[$extKey]['versions']);
		natsort($versions);
		$version = ($version == '') ? end($versions) : $version;

		$opt = array();
		foreach ($versions as $ver) {
			$opt[] = '<option value="' . $ver . '"' . (($version == $ver) ? ' selected="selected"' : '') . '>' . $ver . '</option>';
		}

		// "Select version" box:
		$onClick = 'window.location.href="' . $this->script . $addUrl . '&CMD[importExtInfo]=' . $extKey . '&CMD[extVersion]="+document.pageform.extVersion.options[document.pageform.extVersion.selectedIndex].value; return false;';
		$select = '<select name="extVersion">' . implode('', $opt) .
				'</select> <input type="submit" value="' . $GLOBALS['LANG']->getLL('ext_load_details_button') .
				'" onclick="' . htmlspecialchars($onClick) . '" />';

		if ($this->importAtAll()) {
			// Check for write-protected extension
			list($inst_list,) = $this->extensionList->getInstalledExtensions();
			if ($inst_list[$extKey]['EM_CONF']['state'] != 'excludeFromUpdates') {
				$onClick = '
						window.location.href="' . $this->script . $addUrl . '&CMD[importExt]=' . $extKey . '"
							+"&CMD[extVersion]="+document.pageform.extVersion.options[document.pageform.extVersion.selectedIndex].value
							+"&CMD[loc]="+document.pageform.loc.options[document.pageform.loc.selectedIndex].value;
							return false;';
				$select .= ' ' . $GLOBALS['LANG']->getLL('ext_or') . '<br /><br />
					<input type="submit" value="' . $GLOBALS['LANG']->getLL('ext_import_update_button') .
						'" onclick="' . htmlspecialchars($onClick) . '" /> ' . $GLOBALS['LANG']->getLL('ext_import_update_to') . '
					<select name="loc">' .
						(tx_em_Tools::importAsType('G', $fetchData['emconf_lockType']) ?
								'<option value="G">' . $GLOBALS['LANG']->getLL('ext_import_global') . ' ' . tx_em_Tools::typePath('G') . $extKey . '/' .
										(@is_dir(tx_em_Tools::typePath('G') . $extKey) ?
												' ' . $GLOBALS['LANG']->getLL('ext_import_overwrite') :
												' ' . $GLOBALS['LANG']->getLL('ext_import_folder_empty')
										) . '</option>' : ''
						) .
						(tx_em_Tools::importAsType('L', $fetchData['emconf_lockType']) ?
								'<option value="L">' . $GLOBALS['LANG']->getLL('ext_import_local') . ' ' . tx_em_Tools::typePath('L') . $extKey . '/' .
										(@is_dir(tx_em_Tools::typePath('L') . $extKey) ?
												' ' . $GLOBALS['LANG']->getLL('ext_import_overwrite') :
												' ' . $GLOBALS['LANG']->getLL('ext_import_folder_empty')
										) . '</option>' : ''
						) .
						(tx_em_Tools::importAsType('S', $fetchData['emconf_lockType']) ?
								'<option value="S">' . $GLOBALS['LANG']->getLL('ext_import_system') . ' ' . tx_em_Tools::typePath('S') . $extKey . '/' .
										(@is_dir(tx_em_Tools::typePath('S') . $extKey) ?
												' ' . $GLOBALS['LANG']->getLL('ext_import_overwrite') :
												' ' . $GLOBALS['LANG']->getLL('ext_import_folder_empty')
										) . '</option>' : ''
						) .
						'</select>
					</form>';
			} else {
				$select .= '<br /><br />' . $GLOBALS['LANG']->getLL('ext_import_excluded_from_updates');
			}
		} else {
			$select .= '<br /><br />' . tx_em_Tools::noImportMsg();
		}
		$content .= $select;
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('ext_import_select_command'), $content, 0, 1);

		// Details:
		$eInfo = $fetchData[$extKey]['versions'][$version];
		$content = '<strong>' . $fetchData[$extKey]['_ICON'] . ' &nbsp;' . $eInfo['EM_CONF']['title'] . ' (' . $extKey . ', ' . $version . ')</strong><br /><br />';
		$content .= $this->extensionDetails->extInformationarray($extKey, $eInfo, 1);
		$this->content .= $this->doc->spacer(10);
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('ext_import_remote_ext_details'), $content, 0, 1);
	}

	/**
	 * Fetches metadata and stores it to the corresponding place. This includes the mirror list,
	 * extension XML files.
	 *
	 * @param	string		Type of data to fetch: (mirrors)
	 * @param	boolean		If true the method doesn't produce any output
	 * @return	void
	 */
	function fetchMetaData($metaType) {
		$content = '';
		switch ($metaType) {
			case 'mirrors':
				$mfile = t3lib_div::tempnam('mirrors');
				$mirrorsFile = t3lib_div::getURL($this->MOD_SETTINGS['mirrorListURL'], 0, array(TYPO3_user_agent));
				if ($mirrorsFile===false) {
					t3lib_div::unlink_tempfile($mfile);
					$content = '<p>' .
							sprintf($GLOBALS['LANG']->getLL('ext_import_list_not_updated'),
								$this->MOD_SETTINGS['mirrorListURL']
							) . ' ' .
							$GLOBALS['LANG']->getLL('translation_problems') . '</p>';
				} else {
					t3lib_div::writeFile($mfile, $mirrorsFile);
					$mirrors = implode('', gzfile($mfile));
					t3lib_div::unlink_tempfile($mfile);

					$mirrors = $this->xmlHandler->parseMirrorsXML($mirrors);
					if (is_array($mirrors) && count($mirrors)) {
						t3lib_BEfunc::getModuleData($this->MOD_MENU, array('extMirrors' => serialize($mirrors)), $this->MCONF['name'], '', 'extMirrors');
						$this->MOD_SETTINGS['extMirrors'] = serialize($mirrors);
						$content = '<p>' .
								sprintf($GLOBALS['LANG']->getLL('ext_import_list_updated'),
									count($mirrors)
								) . '</p>';
					}
					else {
						$content = '<p>' . $mirrors . '<br />' . $GLOBALS['LANG']->getLL('ext_import_list_empty') . '</p>';
					}
				}
				break;
			case 'extensions':
				$this->fetchMetaData('mirrors'); // if we fetch the extensions anyway, we can as well keep this up-to-date

				$mirror = $this->getMirrorURL();
				$extfile = $mirror . 'extensions.xml.gz';
				$extmd5 = t3lib_div::getURL($mirror . 'extensions.md5', 0, array(TYPO3_user_agent));
				if (is_file(PATH_site . 'typo3temp/extensions.xml.gz')) {
					$localmd5 = md5_file(PATH_site . 'typo3temp/extensions.xml.gz');
				}

				// count cached extensions. If cache is empty re-fill it
				$cacheCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('extkey', 'cache_extensions');

				if ($extmd5 === false) {
					$content .= '<p>' .
							sprintf($GLOBALS['LANG']->getLL('ext_import_md5_not_updated'),
									$mirror . 'extensions.md5'
							) .
							$GLOBALS['LANG']->getLL('translation_problems') . '</p>';
				} elseif ($extmd5 == $localmd5 && $cacheCount) {
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('ext_import_list_unchanged'),
						$GLOBALS['LANG']->getLL('ext_import_list_unchanged_header'),
						t3lib_FlashMessage::INFO
					);
					$content .= $flashMessage->render();
				} else {
					$extXML = t3lib_div::getURL($extfile, 0, array(TYPO3_user_agent));
					if ($extXML === false) {
						$content .= '<p>' .
								sprintf($GLOBALS['LANG']->getLL('ext_import_list_unchanged'),
									$extfile
								) . ' ' .
								$GLOBALS['LANG']->getLL('translation_problems') . '</p>';
					} else {
						t3lib_div::writeFile(PATH_site . 'typo3temp/extensions.xml.gz', $extXML);
						$content .= $this->xmlHandler->parseExtensionsXML(PATH_site . 'typo3temp/extensions.xml.gz');
					}
				}
				break;
		}

		return $content;
	}

	/**
	 * Returns the base URL for the slected or a random mirror.
	 *
	 * @return	string		The URL for the selected or a random mirror
	 */
	function getMirrorURL() {
		if (strlen($this->MOD_SETTINGS['rep_url'])) {
			return $this->MOD_SETTINGS['rep_url'];
		}

		$mirrors = unserialize($this->MOD_SETTINGS['extMirrors']);
		if (!is_array($mirrors)) {
			$this->fetchMetaData('mirrors');
			$mirrors = unserialize($this->MOD_SETTINGS['extMirrors']);
			if (!is_array($mirrors)) {
				return false;
			}
		}
		if ($this->MOD_SETTINGS['selectedMirror'] == '') {
			$rand = array_rand($mirrors);
			$url = 'http://' . $mirrors[$rand]['host'] . $mirrors[$rand]['path'];
		}
		else {
			$url = 'http://' . $mirrors[$this->MOD_SETTINGS['selectedMirror']]['host'] . $mirrors[$this->MOD_SETTINGS['selectedMirror']]['path'];
		}

		return $url;
	}


	/**
	 * Installs (activates) an extension
	 *
	 * For $mode use the three constants EM_INSTALL_VERSION_MIN, EM_INSTALL_VERSION_MAX, EM_INSTALL_VERSION_STRICT
	 *
	 * If an extension is loaded or imported already and the version requirement is matched, it will not be
	 * fetched from the repository. This means, if you use EM_INSTALL_VERSION_MIN, you will not always get the latest
	 * version of an extension!
	 *
	 * @param	string		$extKey	The extension key to install
	 * @param	string		$version	A version number that should be installed
	 * @param	int		$mode	If a version is requested, this determines if it is the min, max or strict version requested
	 * @return	[type]		...
	 * @todo Make the method able to handle needed interaction somehow (unmatched dependencies)
	 */
	function installExtension($extKey, $version = NULL, $mode = EM_INSTALL_VERSION_MIN) {
		list($inst_list,) = $this->extensionList->getInstalledExtensions();

		// check if it is already installed and loaded with sufficient version
		if (isset($inst_list[$extKey])) {
			$currentVersion = $inst_list[$extKey]['EM_CONF']['version'];

			if (t3lib_extMgm::isLoaded($extKey)) {
				if ($version===NULL) {
					return array(true, $GLOBALS['LANG']->getLL('ext_import_ext_already_installed_loaded'));
				} else {
					switch ($mode) {
						case EM_INSTALL_VERSION_STRICT:
							if ($currentVersion == $version) {
								return array(true, $GLOBALS['LANG']->getLL('ext_import_ext_already_installed_loaded'));
							}
							break;
						case EM_INSTALL_VERSION_MIN:
							if (version_compare($currentVersion, $version, '>=')) {
								return array(true, $GLOBALS['LANG']->getLL('ext_import_ext_already_installed_loaded'));
							}
							break;
						case EM_INSTALL_VERSION_MAX:
							if (version_compare($currentVersion, $version, '<=')) {
								return array(true, $GLOBALS['LANG']->getLL('ext_import_ext_already_installed_loaded'));
							}
							break;
					}
				}
			} else {
				if (!t3lib_extMgm::isLocalconfWritable()) {
					return array(false, $GLOBALS['LANG']->getLL('ext_import_p_localconf'));
				}
				$newExtList = -1;
				switch ($mode) {
					case EM_INSTALL_VERSION_STRICT:
						if ($currentVersion == $version) {
							$newExtList = $this->extensionList->addExtToList($extKey, $inst_list);
						}
						break;
					case EM_INSTALL_VERSION_MIN:
						if (version_compare($currentVersion, $version, '>=')) {
							$newExtList = $this->extensionList->addExtToList($extKey, $inst_list);
						}
						break;
					case EM_INSTALL_VERSION_MAX:
						if (version_compare($currentVersion, $version, '<=')) {
							$newExtList = $this->extensionList->addExtToList($extKey, $inst_list);
						}
						break;
				}
				if ($newExtList != -1) {
					$this->install->writeNewExtensionList($newExtList);
					tx_em_Tools::refreshGlobalExtList();
					$this->install->forceDBupdates($extKey, $inst_list[$extKey]);
					return array(true, $GLOBALS['LANG']->getLL('ext_import_ext_loaded'));
				}
			}
		}

		// at this point we know we need to import (a matching version of) the extension from TER2

		// see if we have an extension list at all
		if (!$this->xmlHandler->countExtensions()) {
			$this->fetchMetaData('extensions');
		}
		$this->xmlHandler->searchExtensionsXMLExact($extKey, '', '', true);

		// check if extension can be fetched
		if (isset($this->xmlHandler->extensionsXML[$extKey])) {
			$versions = array_keys($this->xmlHandler->extensionsXML[$extKey]['versions']);
			$latestVersion = end($versions);
			switch ($mode) {
				case EM_INSTALL_VERSION_STRICT:
					if (!isset($this->xmlHandler->extensionsXML[$extKey]['versions'][$version])) {
						return array(false, $GLOBALS['LANG']->getLL('ext_import_ext_n_a'));
					}
					break;
				case EM_INSTALL_VERSION_MIN:
					if (version_compare($latestVersion, $version, '>=')) {
						$version = $latestVersion;
					} else {
						return array(false, $GLOBALS['LANG']->getLL('ext_import_ext_n_a'));
					}
					break;
				case EM_INSTALL_VERSION_MAX:
					while (($v = array_pop($versions)) && version_compare($v, $version, '>=')) {
						// Loop until a version is found
					}

					if ($v !== NULL && version_compare($v, $version, '<=')) {
						$version = $v;
					} else {
						return array(false, $GLOBALS['LANG']->getLL('ext_import_ext_n_a'));
					}
					break;
			}
			$this->importExtFromRep($extKey, $version, 'L');
			$newExtList = $this->extensionList->addExtToList($extKey, $inst_list);
			if ($newExtList != -1) {
				$this->install->writeNewExtensionList($newExtList);
				tx_em_Tools::refreshGlobalExtList();
				$this->install->forceDBupdates($extKey, $inst_list[$extKey]);
				$this->translations->installTranslationsForExtension($extKey, $this->getMirrorURL());
				return array(true, $GLOBALS['LANG']->getLL('ext_import_ext_imported'));
			} else {
				return array(false, $GLOBALS['LANG']->getLL('ext_import_ext_not_loaded'));
			}
		} else {
			return array(false, $GLOBALS['LANG']->getLL('ext_import_ext_n_a_rep'));
		}
	}


	/**
	 * Imports an extensions from the online repository
	 * NOTICE: in version 4.0 this changed from "importExtFromRep_old($extRepUid,$loc,$uploadFlag=0,$directInput='',$recentTranslations=0,$incManual=0,$dontDelete=0)"
	 *
	 * @param	string		Extension key
	 * @param	string		Version
	 * @param	string		Install scope: "L" or "G" or "S"
	 * @param	boolean		If true, extension is uploaded as file
	 * @param	boolean		If true, extension directory+files will not be deleted before writing the new ones. That way custom files stored in the extension folder will be kept.
	 * @param	array		Direct input array (like from kickstarter)
	 * @return	string		Return false on success, returns error message if error.
	 */
	function importExtFromRep($extKey, $version, $loc, $uploadFlag = 0, $dontDelete = 0, $directInput = '') {

		$uploadSucceed = false;
		$uploadedTempFile = '';
		if (is_array($directInput)) {
			$fetchData = array($directInput, '');
			$loc = ($loc==='G' || $loc==='S') ? $loc : 'L';
		} elseif ($uploadFlag) {
			if (($uploadedTempFile = $this->CMD['alreadyUploaded']) || $_FILES['upload_ext_file']['tmp_name']) {

				// Read uploaded file:
				if (!$uploadedTempFile) {
					if (!is_uploaded_file($_FILES['upload_ext_file']['tmp_name'])) {
						t3lib_div::sysLog('Possible file upload attack: ' . $_FILES['upload_ext_file']['tmp_name'], 'Extension Manager', 3);

						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$GLOBALS['LANG']->getLL('ext_import_file_not_uploaded'),
							'',
							t3lib_FlashMessage::ERROR
						);
						return $flashMessage->render();
					}

					$uploadedTempFile = t3lib_div::upload_to_tempfile($_FILES['upload_ext_file']['tmp_name']);
				}
				$fileContent = t3lib_div::getUrl($uploadedTempFile);

				if (!$fileContent) {
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('ext_import_file_empty'),
						'',
						t3lib_FlashMessage::ERROR
					);
					return $flashMessage->render();
				}

				// Decode file data:
				$fetchData = $this->terConnection->decodeExchangeData($fileContent);

				if (is_array($fetchData)) {
					$extKey = $fetchData[0]['extKey'];
					if ($extKey) {
						if (!$this->CMD['uploadOverwrite']) {
							$loc = ($loc==='G' || $loc==='S') ? $loc : 'L';
							$comingExtPath = tx_em_Tools::typePath($loc) . $extKey . '/';
							if (@is_dir($comingExtPath)) {
								$flashMessage = t3lib_div::makeInstance(
									't3lib_FlashMessage',
										sprintf($GLOBALS['LANG']->getLL('ext_import_ext_present_no_overwrite'), $comingExtPath) .
												'<br />' . $GLOBALS['LANG']->getLL('ext_import_ext_present_nothing_done'),
									'',
									t3lib_FlashMessage::ERROR
								);
								return $flashMessage->render();
							} // ... else go on, install...
						} // ... else go on, install...
					} else {
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$GLOBALS['LANG']->getLL('ext_import_no_key'),
							'',
							t3lib_FlashMessage::ERROR
						);
						return $flashMessage->render();
					}
				} else {
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						sprintf($GLOBALS['LANG']->getLL('ext_import_wrong_file_format'), $fetchData),
						'',
						t3lib_FlashMessage::ERROR
					);
					return $flashMessage->render();
				}
			} else {
				$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('ext_import_no_file'),
					'',
					t3lib_FlashMessage::ERROR
				);
				return $flashMessage->render();
			}
		} else {
			$this->xmlHandler->searchExtensionsXMLExact($extKey, '', '', true, true);

			// Fetch extension from TER:
			if (!strlen($version)) {
				$versions = array_keys($this->xmlHandler->extensionsXML[$extKey]['versions']);
				$version = end($versions);
			}
			$fetchData = $this->terConnection->fetchExtension($extKey, $version, $this->xmlHandler->extensionsXML[$extKey]['versions'][$version]['t3xfilemd5'], $this->getMirrorURL());
		}

		// At this point the extension data should be present; so we want to write it to disc:
		$content = $this->install->installExtension($fetchData, $loc, $version, $uploadedTempFile, $dontDelete);

		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('ext_import_results'), $content, 0, 1);

		if ($uploadSucceed && $uploadedTempFile) {
			t3lib_div::unlink_tempfile($uploadedTempFile);
		}

		return false;
	}

	/**
	 * Display extensions details.
	 *
	 * @param	string		Extension key
	 * @return	void		Writes content to $this->content
	 */
	function showExtDetails($extKey) {
		list($list,) = $this->extensionList->getInstalledExtensions();
		$absPath = tx_em_Tools::getExtPath($extKey, $list[$extKey]['type']);

		// Check updateModule:
		if (isset($list[$extKey]) && @is_file($absPath . 'class.ext_update.php')) {
			require_once($absPath . 'class.ext_update.php');
			$updateObj = new ext_update;
			if (!$updateObj->access()) {
				unset($this->MOD_MENU['singleDetails']['updateModule']);
			}
		} else {
			unset($this->MOD_MENU['singleDetails']['updateModule']);
		}

		if ($this->CMD['doDelete']) {
			$this->MOD_MENU['singleDetails'] = array();
		}

		// Function menu here:
		if (!$this->CMD['standAlone'] && !t3lib_div::_GP('standAlone')) {
			$content = $GLOBALS['LANG']->getLL('ext_details_ext') . '&nbsp;<strong>' .
					$this->extensionTitleIconHeader($extKey, $list[$extKey]) . '</strong> (' . htmlspecialchars($extKey) . ')';
			$this->content .= $this->doc->section('', $content);
		}

		// Show extension details:
		if ($list[$extKey]) {

			// Checking if a command for install/uninstall is executed:
			if (($this->CMD['remove'] || $this->CMD['load']) && !in_array($extKey, $this->requiredExt)) {

				// Install / Uninstall extension here:
				if (t3lib_extMgm::isLocalconfWritable()) {
					// Check dependencies:
					$depStatus = $this->install->checkDependencies($extKey, $list[$extKey]['EM_CONF'], $list);

					if (!$this->CMD['remove'] && !$depStatus['returnCode']) {
						$this->content .= $depStatus['html'];
						$newExtList = -1;
					} elseif ($this->CMD['remove']) {
						$newExtList = $this->extensionList->removeExtFromList($extKey, $list);
					} else {
						$newExtList = $this->extensionList->addExtToList($extKey, $list);
					}

					// Successful installation:
					if ($newExtList != -1) {
						$updates = '';
						if ($this->CMD['load']) {
							if ($_SERVER['REQUEST_METHOD'] == 'POST') {
								$script = t3lib_div::linkThisScript(array(
									'CMD[showExt]' => $extKey,
									'CMD[load]' => 1,
									'CMD[clrCmd]' => $this->CMD['clrCmd'],
									'SET[singleDetails]' => 'info'
								));
							} else {
								$script = '';
							}
							$standaloneUpdates = '';
							if ($this->CMD['standAlone']) {
								$standaloneUpdates .= '<input type="hidden" name="standAlone" value="1" />';
							}
							if ($this->CMD['silendMode']) {
								$standaloneUpdates .= '<input type="hidden" name="silendMode" value="1" />';
							}
							$depsolver = t3lib_div::_POST('depsolver');
							if (is_array($depsolver['ignore'])) {
								foreach ($depsolver['ignore'] as $depK => $depV) {
									$dependencyUpdates .= '<input type="hidden" name="depsolver[ignore][' . $depK . ']" value="1" />';
								}
							}
							$updatesForm = $this->install->updatesForm(
								$extKey,
								$list[$extKey],
								1,
								$script,
								$dependencyUpdates . $standaloneUpdates . '<input type="hidden" name="_do_install" value="1" /><input type="hidden" name="_clrCmd" value="' . $this->CMD['clrCmd'] . '" />',
								TRUE
							);
							if ($updatesForm) {
								$updates = $GLOBALS['LANG']->getLL('ext_details_new_tables_fields') . '<br />' .
										$GLOBALS['LANG']->getLL('ext_details_new_tables_fields_select') . $updatesForm;
								$labelDBUpdate = $GLOBALS['LANG']->csConvObj->conv_case(
									$GLOBALS['LANG']->charSet,
									$GLOBALS['LANG']->getLL('ext_details_db_needs_update'),
									'toUpper'
								);
								$this->content .= $this->doc->section(
									sprintf($GLOBALS['LANG']->getLL('ext_details_installing') . ' ',
										$this->extensionTitleIconHeader($extKey, $list[$extKey])
									) . ' ' .
											$labelDBUpdate,
									$updates, 1, 1, 1, 1
								);
							}
						} elseif ($this->CMD['remove']) {
							$updates .= $this->install->checkClearCache($list[$extKey]);
							if ($updates) {
								$updates = '
								<form action="' . $this->script . '" method="post">' . $updates . '
								<br /><input type="submit" name="write" value="' .
										$GLOBALS['LANG']->getLL('ext_details_remove_ext') . '" />
								<input type="hidden" name="_do_install" value="1" />
								<input type="hidden" name="_clrCmd" value="' . $this->CMD['clrCmd'] . '" />
								<input type="hidden" name="CMD[showExt]" value="' . $this->CMD['showExt'] . '" />
								<input type="hidden" name="CMD[remove]" value="' . $this->CMD['remove'] . '" />
								<input type="hidden" name="standAlone" value="' . $this->CMD['standAlone'] . '" />
								<input type="hidden" name="silentMode" value="' . $this->CMD['silentMode'] . '" />
								' . ($this->noDocHeader ? '<input type="hidden" name="nodoc" value="1" />' : '') . '
								</form>';
								$labelDBUpdate = $GLOBALS['LANG']->csConvObj->conv_case(
									$GLOBALS['LANG']->charSet,
									$GLOBALS['LANG']->getLL('ext_details_db_needs_update'),
									'toUpper'
								);
								$this->content .= $this->doc->section(
									sprintf($GLOBALS['LANG']->getLL('ext_details_removing') . ' ',
										$this->extensionTitleIconHeader($extKey, $list[$extKey])
									) . ' ' .
											$labelDBUpdate,
									$updates, 1, 1, 1, 1
								);
							}
						}
						if (!$updates || t3lib_div::_GP('_do_install') || ($this->noDocHeader && $this->CMD['remove'])) {
							$this->install->writeNewExtensionList($newExtList);
							$action = $this->CMD['load'] ? 'installed' : 'removed';
							$GLOBALS['BE_USER']->writelog(5, 1, 0, 0, 'Extension list has been changed, extension %s has been %s', array($extKey, $action));

							if (!t3lib_div::_GP('silentMode') && !$this->CMD['standAlone']) {
								$messageLabel = 'ext_details_ext_' . $action . '_with_key';
								$flashMessage = t3lib_div::makeInstance(
									't3lib_FlashMessage',
									sprintf($GLOBALS['LANG']->getLL($messageLabel), $extKey),
									'',
									t3lib_FlashMessage::OK,
									TRUE
								);
								t3lib_FlashMessageQueue::addMessage($flashMessage);
							}
							if ($this->CMD['clrCmd'] || t3lib_div::_GP('_clrCmd')) {
								if ($this->CMD['load'] && @is_file($absPath . 'ext_conf_template.txt')) {
									$vA = array('CMD' => array('showExt' => $extKey));
								} else {
									$vA = array('CMD' => '');
								}
							} else {
								$vA = array('CMD' => array('showExt' => $extKey));
							}

							if ($this->CMD['standAlone'] || t3lib_div::_GP('standAlone')) {
								$this->content .= sprintf($GLOBALS['LANG']->getLL('ext_details_ext_installed_removed'),
									($this->CMD['load'] ?
											$GLOBALS['LANG']->getLL('ext_details_installed') :
											$GLOBALS['LANG']->getLL('ext_details_removed')
									)
								) .
										'<br /><br />' . $this->getSubmitAndOpenerCloseLink();
							} else {
								// Determine if new modules were installed:
								$techInfo = $this->install->makeDetailedExtensionAnalysis($extKey, $list[$extKey]);
								if (($this->CMD['load'] || $this->CMD['remove']) && is_array($techInfo['flags']) && in_array('Module', $techInfo['flags'], true)) {
									$vA['CMD']['refreshMenu'] = 1;
								}
								t3lib_utility_Http::redirect(t3lib_div::linkThisScript($vA));
								exit;
							}
						}
					}
				} else {
					$writeAccessError = $GLOBALS['LANG']->csConvObj->conv_case(
						$GLOBALS['LANG']->charSet,
						$GLOBALS['LANG']->getLL('ext_details_write_access_error'),
						'toUpper'
					);
					$this->content .= $this->doc->section(
						sprintf($GLOBALS['LANG']->getLL('ext_details_installing') . ' ',
							$this->extensionTitleIconHeader($extKey, $list[$extKey])
						) . ' ' .
								$writeAccessError,
						$GLOBALS['LANG']->getLL('ext_details_write_error_localconf'),
						1, 1, 2, 1
					);
				}

			} elseif ($this->CMD['downloadFile'] && !in_array($extKey, $this->requiredExt)) {

				// Link for downloading extension has been clicked - deliver content stream:
				$dlFile = $this->CMD['downloadFile'];
				if (t3lib_div::isAllowedAbsPath($dlFile) && t3lib_div::isFirstPartOfStr($dlFile, PATH_site) && t3lib_div::isFirstPartOfStr($dlFile, $absPath) && @is_file($dlFile)) {
					$mimeType = 'application/octet-stream';
					Header('Content-Type: ' . $mimeType);
					Header('Content-Disposition: attachment; filename=' . basename($dlFile));
					echo t3lib_div::getUrl($dlFile);
					exit;
				} else {
					throw new RuntimeException(
						'TYPO3 Fatal Error: ' . $GLOBALS['LANG']->getLL('ext_details_error_downloading'),
						1270853980
					);
				}

			} elseif ($this->CMD['editFile'] && !in_array($extKey, $this->requiredExt)) {

				// Editing extension file:
				$editFile = rawurldecode($this->CMD['editFile']);
				if (t3lib_div::isAllowedAbsPath($editFile) && t3lib_div::isFirstPartOfStr($editFile, $absPath)) {

					$fI = t3lib_div::split_fileref($editFile);
					if (@is_file($editFile) && t3lib_div::inList($this->editTextExtensions, ($fI['fileext'] ? $fI['fileext'] : $fI['filebody']))) {
						if (filesize($editFile) < ($this->kbMax * 1024)) {
							$outCode = '<form action="' . $this->script . '" method="post" name="editfileform">';
							$info = '';
							$submittedContent = t3lib_div::_POST('edit');
							$saveFlag = 0;

							if (isset($submittedContent['file']) && !$GLOBALS['TYPO3_CONF_VARS']['EXT']['noEdit']) { // Check referer here?
								$oldFileContent = t3lib_div::getUrl($editFile);
								if ($oldFileContent != $submittedContent['file']) {
									$oldMD5 = md5(str_replace(CR, '', $oldFileContent));
									$info .= sprintf(
										$GLOBALS['LANG']->getLL('ext_details_md5_previous'),
											'<strong>' . $oldMD5 . '</strong>'
									) . '<br />';
									t3lib_div::writeFile($editFile, $submittedContent['file']);
									$saveFlag = 1;
								} else {
									$info .= $GLOBALS['LANG']->getLL('ext_details_no_changes') . '<br />';
								}
							}

							$fileContent = t3lib_div::getUrl($editFile);

							$outCode .= sprintf(
								$GLOBALS['LANG']->getLL('ext_details_file'),
									'<strong>' . substr($editFile, strlen($absPath)) . '</strong> (' .
											t3lib_div::formatSize(filesize($editFile)) . ')<br />'
							);
							$fileMD5 = md5(str_replace(CR, '', $fileContent));
							$info .= sprintf(
								$GLOBALS['LANG']->getLL('ext_details_md5_current'),
									'<strong>' . $fileMD5 . '</strong>'
							) . '<br />';
							if ($saveFlag) {
								$saveMD5 = md5(str_replace(CR, '', $submittedContent['file']));
								$info .= sprintf(
									$GLOBALS['LANG']->getLL('ext_details_md5_submitted'),
										'<strong>' . $saveMD5 . '</strong>'
								) . '<br />';
								if ($fileMD5 != $saveMD5) {
									$info .= tx_em_Tools::rfw(
										'<br /><strong>' . $GLOBALS['LANG']->getLL('ext_details_saving_failed_changes_lost') . '</strong>'
									) . '<br />';
								}
								else {
									$info .= tx_em_Tools::rfw(
										'<br /><strong>' . $GLOBALS['LANG']->getLL('ext_details_file_saved') . '</strong>'
									) . '<br />';
								}
							}

							$outCode .= '<textarea name="edit[file]" rows="35" wrap="off"' . $this->doc->formWidthText(48, 'width:98%;height:70%', 'off') . ' class="fixed-font enable-tab">' . t3lib_div::formatForTextarea($fileContent) . '</textarea>';
							$outCode .= '<input type="hidden" name="edit[filename]" value="' . $editFile . '" />';
							$outCode .= '<input type="hidden" name="CMD[editFile]" value="' . htmlspecialchars($editFile) . '" />';
							$outCode .= '<input type="hidden" name="CMD[showExt]" value="' . $extKey . '" />';
							$outCode .= $info;

							if (!$GLOBALS['TYPO3_CONF_VARS']['EXT']['noEdit']) {
								$outCode .= '<br /><input type="submit" name="save_file" value="' .
										$GLOBALS['LANG']->getLL('ext_details_file_save_button') . '" />';
							}
							else {
								$outCode .= tx_em_Tools::rfw(
									'<br />' . $GLOBALS['LANG']->getLL('ext_details_saving_disabled') . ' '
								);
							}

							$onClick = 'window.location.href="' . t3lib_div::linkThisScript(array(
								'CMD[showExt]' => $extKey
							)) . '";return false;';
							$outCode .= '<input type="submit" name="cancel" value="' .
									$GLOBALS['LANG']->getLL('ext_details_cancel_button') . '" onclick="' .
									htmlspecialchars($onClick) . '" /></form>';

							$theOutput .= $this->doc->spacer(15);
							$theOutput .= $this->doc->section($GLOBALS['LANG']->getLL('ext_details_edit_file'), '', 0, 1);
							$theOutput .= $this->doc->sectionEnd() . $outCode;
							$this->content .= $theOutput;
						} else {
							$theOutput .= $this->doc->spacer(15);
							$theOutput .= $this->doc->section(
								sprintf(
									$GLOBALS['LANG']->getLL('ext_details_filesize_exceeded_kb'),
									$this->kbMax
								),
								sprintf(
									$GLOBALS['LANG']->getLL('ext_details_file_too_large'),
									$this->kbMax
								)
							);
						}
					}
				} else {
					die (sprintf($GLOBALS['LANG']->getLL('ext_details_fatal_edit_error'),
						htmlspecialchars($editFile)
					)
					);
				}
			} else {

				// MAIN:
				switch ((string) $this->MOD_SETTINGS['singleDetails']) {
					case 'info':
						// Loaded / Not loaded:
						if (!in_array($extKey, $this->requiredExt)) {
							if ($GLOBALS['TYPO3_LOADED_EXT'][$extKey]) {
								$content = '<strong>' . $GLOBALS['LANG']->getLL('ext_details_loaded_and_running') . '</strong><br />' .
										'<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
									'CMD[showExt]' => $extKey,
									'CMD[remove]' => 1
								))) .
										'">' . $GLOBALS['LANG']->getLL('ext_details_remove_button') . ' ' . tx_em_Tools::removeButton() . '</a>';
							} else {
								$content = $GLOBALS['LANG']->getLL('ext_details_not_loaded') . '<br />' .
										'<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
									'CMD[showExt]' => $extKey,
									'CMD[load]' => 1
								))) .
										'">' . $GLOBALS['LANG']->getLL('ext_details_install_button') . ' ' . tx_em_Tools::installButton() . '</a>';
							}
						} else {
							$content = $GLOBALS['LANG']->getLL('ext_details_always_loaded');
						}
						$this->content .= $this->doc->spacer(10);
						$this->content .= $this->doc->section(
							$GLOBALS['LANG']->getLL('ext_details_current_status'), $content, 0, 1
						);

						if (t3lib_extMgm::isLoaded($extKey)) {
							$updates = $this->install->updatesForm($extKey, $list[$extKey]);
							if ($updates) {
								$this->content .= $this->doc->spacer(10);
								$this->content .= $this->doc->section(
									$GLOBALS['LANG']->getLL('ext_details_update_needed'),
										$updates . '<br /><br />' . $GLOBALS['LANG']->getLL('ext_details_notice_static_data'),
									0, 1
								);
							}
						}

						// Config:
						if (@is_file($absPath . 'ext_conf_template.txt')) {
							$this->content .= $this->doc->spacer(10);
							$this->content .= $this->doc->section(
								$GLOBALS['LANG']->getLL('ext_details_configuration'),
									$GLOBALS['LANG']->getLL('ext_details_notice_clear_cache') . '<br /><br />',
								0, 1
							);

							$this->content .= $this->install->tsStyleConfigForm($extKey, $list[$extKey]);
						}

						// Show details:
						$headline = $GLOBALS['LANG']->getLL('ext_details_details');
						$headline = t3lib_BEfunc::wrapInHelp('_MOD_tools_em', 'info', $headline);
						$content = $this->extensionDetails->extInformationarray($extKey, $list[$extKey]);


						$this->content .= $this->doc->spacer(10);
						$this->content .= $this->doc->section($headline, $content, FALSE, TRUE, FALSE, TRUE);
						break;
					case 'upload':
						$em = t3lib_div::_POST('em');
						if ($em['action'] == 'doUpload') {
							$em['extKey'] = $extKey;
							$em['extInfo'] = $list[$extKey];
							$content = $this->extensionDetails->uploadExtensionToTER($em);
							$content .= $this->doc->spacer(10);
							// Must reload this, because EM_CONF information has been updated!
							list($list,) = $this->extensionList->getInstalledExtensions();
						} else {
							// headline and CSH
							$headline = $GLOBALS['LANG']->getLL('ext_details_upload_to_ter');
							$headline = t3lib_BEfunc::wrapInHelp('_MOD_tools_em', 'upload', $headline);

							// Upload:
							if (substr($extKey, 0, 5) != 'user_') {
								$content = $this->getRepositoryUploadForm($extKey, $list[$extKey]);
								$eC = 0;
							} else {
								$content = $GLOBALS['LANG']->getLL('ext_details_no_unique_ext');
								$eC = 2;
							}
							if (!$this->fe_user['username']) {
								$flashMessage = t3lib_div::makeInstance(
									't3lib_FlashMessage',
									sprintf($GLOBALS['LANG']->getLL('ext_details_no_username'),
											'<a href="' . t3lib_div::linkThisScript(array(
												'SET[function]' => 3
											)) . '">', '</a>'
									),
									'',
									t3lib_FlashMessage::INFO
								);
								$content .= '<br />' . $flashMessage->render();

							}
						}
						$this->content .= $this->doc->section($headline, $content, 0, 1, $eC, TRUE);
						break;
					case 'backup':
						if ($this->CMD['doDelete']) {
							$content = $this->install->extDelete($extKey, $list[$extKey], $this->CMD);
							$this->content .= $this->doc->section(
								$GLOBALS['LANG']->getLL('ext_details_delete'),
								$GLOBALS['LANG']->getLL('ext_details_delete'),
								$content, 0, 1
							);
						} else {
							// headline and CSH
							$headline = $GLOBALS['LANG']->getLL('ext_details_backup');
							$headline = t3lib_BEfunc::wrapInHelp('_MOD_tools_em', 'backup_delete', $headline);

							$content = $this->extBackup($extKey, $list[$extKey]);
							$this->content .= $this->doc->section($headline, $content, 0, 1, 0, 1);

							$content = $this->install->extDelete($extKey, $list[$extKey], $this->CMD);
							$this->content .= $this->doc->section(
								$GLOBALS['LANG']->getLL('ext_details_delete'),
								$content, 0, 1
							);

							$content = $this->extUpdateEMCONF($extKey, $list[$extKey]);
							$this->content .= $this->doc->section(
								$GLOBALS['LANG']->getLL('ext_details_update_em_conf'),
								$content, 0, 1
							);
						}
						break;
					case 'dump':
						$this->extDumpTables($extKey, $list[$extKey]);
						break;
					case 'edit':
						// headline and CSH
						$headline = $GLOBALS['LANG']->getLL('ext_details_ext_files');
						$headline = t3lib_BEfunc::wrapInHelp('_MOD_tools_em', 'editfiles', $headline);

						$content = $this->getFileListOfExtension($extKey, $list[$extKey]);

						$this->content .= $this->doc->section($headline, $content, FALSE, TRUE, FALSE, TRUE);
						break;
					case 'updateModule':
						$this->content .= $this->doc->section(
							$GLOBALS['LANG']->getLL('ext_details_update'),
							is_object($updateObj) ?
									$updateObj->main() :
									$GLOBALS['LANG']->getLL('ext_details_no_update_object'),
							0, 1
						);
						break;
					default:
						$this->extObjContent();
						break;
				}
			}
		}
	}

	/**
	 * Outputs a screen from where you can install multiple extensions in one go
	 * This can be called from external modules with "...index.php?CMD[requestInstallExtensions]=
	 *
	 * @param	string		Comma list of extension keys to install. Renders a screen with checkboxes for all extensions not already imported or installed
	 * @return	void
	 */
	function requestInstallExtensions($extList) {

		// Return URL:
		$returnUrl = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));
		$installOrImportExtension = t3lib_div::_POST('installOrImportExtension');

		// Extension List:
		$extArray = explode(',', $extList);
		$outputRow = array();
		$outputRow[] = '
			<tr class="t3-row-header tableheader">
				<td>' . $GLOBALS['LANG']->getLL('reqInstExt_install_import') . '</td>
				<td>' . $GLOBALS['LANG']->getLL('reqInstExt_ext_key') . '</td>
			</tr>
		';

		foreach ($extArray as $extKey) {

			// Check for the request:
			if ($installOrImportExtension[$extKey]) {
				$this->installExtension($extKey);
			}

			// Display:
			if (!t3lib_extMgm::isLoaded($extKey)) {
				$outputRow[] = '
				<tr class="bgColor4">
					<td><input type="checkbox" name="' . htmlspecialchars('installOrImportExtension[' . $extKey . ']') . '" value="1" checked="checked" id="check_' . $extKey . '" /></td>
					<td><label for="check_' . $extKey . '">' . htmlspecialchars($extKey) . '</label></td>
				</tr>
				';
			}
		}

		if (count($outputRow) > 1 || !$returnUrl) {
			$content = '
				<!-- ending page form ... -->
			<form action="' . htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')) . '" method="post">
				<table border="0" cellpadding="1" cellspacing="1">' . implode('', $outputRow) . '</table>
			<input type="submit" name="_" value="' . $GLOBALS['LANG']->getLL('reqInstExt_import_install_selected') . '" />
			</form>';

			if ($returnUrl) {
				$content .= '
				<br />
				<br />
				<a href="' . htmlspecialchars($returnUrl) . '">' . $GLOBALS['LANG']->getLL('reqInstExt_return') . '</a>
				';
			}

			$this->content .= $this->doc->section(
				$GLOBALS['LANG']->getLL('reqInstExt_imp_inst_ext'), $content, 0, 1
			);
		} else {
			t3lib_utility_Http::redirect($returnUrl);
		}
	}


	/***********************************
	 *
	 * Application Sub-functions (HTML parts)
	 *
	 **********************************/


	/**
	 * Creates view for dumping static tables and table/fields structures...
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	void
	 */
	function extDumpTables($extKey, $extInfo) {

		// Get dbInfo which holds the structure known from the tables.sql file
		$techInfo = $this->install->makeDetailedExtensionAnalysis($extKey, $extInfo);
		$absPath = tx_em_Tools::getExtPath($extKey, $extInfo['type']);

		// Static tables:
		if (is_array($techInfo['static'])) {
			if ($this->CMD['writeSTATICdump']) { // Writing static dump:
				$writeFile = $absPath . 'ext_tables_static+adt.sql';
				if (@is_file($writeFile)) {
					$dump_static = tx_em_Database::dumpStaticTables(implode(',', $techInfo['static']));
					t3lib_div::writeFile($writeFile, $dump_static);
					$this->content .= $this->doc->section(
						$GLOBALS['LANG']->getLL('extDumpTables_tables_fields'),
						sprintf($GLOBALS['LANG']->getLL('extDumpTables_bytes_written_to'),
							t3lib_div::formatSize(strlen($dump_static)),
							substr($writeFile, strlen(PATH_site))
						),
						0, 1
					);
				}
			} else { // Showing info about what tables to dump - and giving the link to execute it.
				$msg = $GLOBALS['LANG']->getLL('extDumpTables_dumping_content') . '<br />';
				$msg .= '<br />' . implode('<br />', $techInfo['static']) . '<br />';

				// ... then feed that to this function which will make new CREATE statements of the same fields but based on the current database content.
				$this->content .= $this->doc->section(
					$GLOBALS['LANG']->getLL('extDumpTables_static_tables'),
						$msg . '<hr /><strong><a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
							'CMD[showExt]' => $extKey,
							'CMD[writeSTATICdump]' => 1
						))) . '">' . $GLOBALS['LANG']->getLL('extDumpTables_write_static') . '</a></strong>',
					0, 1
				);
				$this->content .= $this->doc->spacer(20);
			}
		}

		// Table and field definitions:
		if (is_array($techInfo['dump_tf'])) {
			$dump_tf_array = tx_em_Database::getTableAndFieldStructure($techInfo['dump_tf']);
			$dump_tf = tx_em_Database::dumpTableAndFieldStructure($dump_tf_array);
			if ($this->CMD['writeTFdump']) {
				$writeFile = $absPath . 'ext_tables.sql';
				if (@is_file($writeFile)) {
					t3lib_div::writeFile($writeFile, $dump_tf);
					$this->content .= $this->doc->section(
						$GLOBALS['LANG']->getLL('extDumpTables_tables_fields'),
						sprintf($GLOBALS['LANG']->getLL('extDumpTables_bytes_written_to'),
							t3lib_div::formatSize(strlen($dump_tf)),
							substr($writeFile, strlen(PATH_site))
						),
						0, 1
					);
				}
			} else {
				$msg = $GLOBALS['LANG']->getLL('extDumpTables_dumping_db_structure') . '<br />';
				if (is_array($techInfo['tables'])) {
					$msg .= '<br /><strong>' . $GLOBALS['LANG']->getLL('extDumpTables_tables') . '</strong><br />' .
							implode('<br />', $techInfo['tables']) . '<br />';
				}
				if (is_array($techInfo['fields'])) {
					$msg .= '<br /><strong>' . $GLOBALS['LANG']->getLL('extDumpTables_solo_fields') . '</strong><br />' .
							implode('<br />', $techInfo['fields']) . '<br />';
				}

				// ... then feed that to this function which will make new CREATE statements of the same fields but based on the current database content.
				$this->content .= $this->doc->section(
					$GLOBALS['LANG']->getLL('extDumpTables_tables_fields'),
						$msg . '<hr /><strong><a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
							'CMD[showExt]' => $extKey,
							'CMD[writeTFdump]' => 1
						))) .
								'">' . $GLOBALS['LANG']->getLL('extDumpTables_write_dump') . '</a></strong><hr />
						<pre>' . htmlspecialchars($dump_tf) . '</pre>',
					0, 1
				);


				$details = '							' . $GLOBALS['LANG']->getLL('extDumpTables_based_on') . '<br />
				<ul>
				<li>' . $GLOBALS['LANG']->getLL('extDumpTables_based_on_one') . '</li>
				<li>' . $GLOBALS['LANG']->getLL('extDumpTables_based_on_two') . '</li>
				</ul>
				' . $GLOBALS['LANG']->getLL('extDumpTables_bottomline') . '<br />';
				$this->content .= $this->doc->section('', $details);
			}
		}
	}

	/**
	 * Returns file-listing of an extension
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML table.
	 */
	function getFileListOfExtension($extKey, $conf) {
		$content = '';
		$extPath = tx_em_Tools::getExtPath($extKey, $conf['type']);

		if ($extPath) {
			// Read files:
			$fileArr = array();
			$fileArr = t3lib_div::getAllFilesAndFoldersInPath($fileArr, $extPath, '', 0, 99, $this->excludeForPackaging);

			// Start table:
			$lines = array();
			$totalSize = 0;

			// Header:
			$lines[] = '
				<tr class="t3-row-header">
					<td>' . $GLOBALS['LANG']->getLL('extFileList_file') . '</td>
					<td>' . $GLOBALS['LANG']->getLL('extFileList_size') . '</td>
					<td>' . $GLOBALS['LANG']->getLL('extFileList_edit') . '</td>
				</tr>';

			foreach ($fileArr as $file) {
				$fI = t3lib_div::split_fileref($file);
				$lines[] = '
				<tr class="bgColor4">
					<td><a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
					'CMD[showExt]' => $extKey,
					'CMD[downloadFile]' => rawurlencode($file)
				))) . '" title="' . $GLOBALS['LANG']->getLL('extFileList_download') . '">' .
						substr($file, strlen($extPath)) . '</a></td>
					<td>' . t3lib_div::formatSize(filesize($file)) . '</td>
					<td>' . (!in_array($extKey, $this->requiredExt) &&
						t3lib_div::inList($this->editTextExtensions,
							($fI['fileext'] ? $fI['fileext'] : $fI['filebody'])) ?
						'<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
							'CMD[showExt]' => $extKey,
							'CMD[editFile]' => rawurlencode($file)
						))) . '">' .
								$GLOBALS['LANG']->getLL('extFileList_edit_file') . '</a>' : ''
				) . '</td>
				</tr>';
				$totalSize += filesize($file);
			}

			$lines[] = '
				<tr class="bgColor6">
					<td><strong>' . $GLOBALS['LANG']->getLL('extFileList_total') . '</strong></td>
					<td><strong>' . t3lib_div::formatSize($totalSize) . '</strong></td>
					<td>&nbsp;</td>
				</tr>';

			$content = '
			Path: ' . $extPath . '<br /><br />
			<table border="0" cellpadding="1" cellspacing="2">' . implode('', $lines) . '</table>';
		}

		return $content;
	}


	/**
	 * Update extension EM_CONF...
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML content.
	 */
	function extUpdateEMCONF($extKey, $extInfo) {
		$absPath = tx_em_Tools::getExtPath($extKey, $extInfo['type']);
		$content = '';

		if ($this->CMD['doUpdateEMCONF']) {
			return $this->extensionDetails->updateLocalEM_CONF($extKey, $extInfo);
		} else {
			$sure = $GLOBALS['LANG']->getLL('extUpdateEMCONF_sure');
			$updateEMConf = $GLOBALS['LANG']->getLL('extUpdateEMCONF_file');
			$onClick = "if (confirm('$sure')) {window.location.href='" . t3lib_div::linkThisScript(array(
				'CMD[showExt]' => $extKey,
				'CMD[doUpdateEMCONF]' => 1
			)) . "';}";
			$content .= $GLOBALS['LANG']->getLL('extUpdateEMCONF_info_changes') . '<br />'
				. $GLOBALS['LANG']->getLL('extUpdateEMCONF_info_reset') . '<br /><br />';
			$content .= '<a class="t3-link" href="#" onclick="' . htmlspecialchars($onClick) .
					' return false;"><strong>' . $updateEMConf . '</strong> ' .
					sprintf($GLOBALS['LANG']->getLL('extDelete_from_location'),
						$this->typeLabels[$extInfo['type']],
						substr($absPath, strlen(PATH_site))
					) . '</a>';
			return $content;
		}
	}

	/**
	 * Download extension as file / make backup
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML content
	 */
	function extBackup($extKey, $extInfo) {
		$uArr = $this->extensionDetails->makeUploadarray($extKey, $extInfo);
		if (is_array($uArr)) {
			$backUpData = $this->terConnection->makeUploadDataFromarray($uArr);
			$filename = 'T3X_' . $extKey . '-' . str_replace('.', '_', $extInfo['EM_CONF']['version']) . '-z-' . date('YmdHi') . '.t3x';
			if (intval($this->CMD['doBackup']) == 1) {
				t3lib_div::cleanOutputBuffers();
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename=' . $filename);
				echo $backUpData;
				exit;
			} elseif ($this->CMD['dumpTables']) {
				$filename = 'T3X_' . $extKey;
				$cTables = count(explode(',', $this->CMD['dumpTables']));
				if ($cTables > 1) {
					$filename .= '-' . $cTables . 'tables';
				} else {
					$filename .= '-' . $this->CMD['dumpTables'];
				}
				$filename .= '+adt.sql';

				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename=' . $filename);
				echo tx_em_Database::dumpStaticTables($this->CMD['dumpTables']);
				exit;
			} else {
				$techInfo = $this->install->makeDetailedExtensionAnalysis($extKey, $extInfo);
				$lines = array();
				$lines[] = '<tr class="t3-row-header"><td colspan="2">' .
						$GLOBALS['LANG']->getLL('extBackup_select') . '</td></tr>';
				$lines[] = '<tr class="bgColor4"><td><strong>' .
						$GLOBALS['LANG']->getLL('extBackup_files') . '</strong></td><td>' .
						'<a class="t3-link" href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
					'CMD[doBackup]' => 1,
					'CMD[showExt]' => $extKey
				))) .
						'">' . sprintf($GLOBALS['LANG']->getLL('extBackup_download'),
					$extKey
				) . '</a><br />
					(' . $filename . ', <br />' .
						t3lib_div::formatSize(strlen($backUpData)) . ', <br />' .
						$GLOBALS['LANG']->getLL('extBackup_md5') . ' ' . md5($backUpData) . ')
					<br /></td></tr>';

				if (is_array($techInfo['tables'])) {
					$lines[] = '<tr class="bgColor4"><td><strong>' . $GLOBALS['LANG']->getLL('extBackup_data_tables') .
							'</strong></td><td>' . tx_em_Database::dumpDataTablesLine($techInfo['tables'], $extKey) . '</td></tr>';
				}
				if (is_array($techInfo['static'])) {
					$lines[] = '<tr class="bgColor4"><td><strong>' . $GLOBALS['LANG']->getLL('extBackup_static_tables') .
							'</strong></td><td>' . tx_em_Database::dumpDataTablesLine($techInfo['static'], $extKey) . '</td></tr>';
				}

				$content = '<table border="0" cellpadding="2" cellspacing="2">' . implode('', $lines) . '</table>';
				return $content;
			}
		} else {
			throw new RuntimeException(
				'TYPO3 Fatal Error: ' . $GLOBALS['LANG']->getLL('extBackup_unexpected_error'),
				1270853981
			);
		}
	}



	/**
	 * Prints the upload form for extensions
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML content.
	 */
	function getRepositoryUploadForm($extKey, $extInfo) {
		$content = '<form action="' . $this->script . '" method="post" name="repuploadform">
			<input type="hidden" name="CMD[showExt]" value="' . $extKey . '" />
			<input type="hidden" name="em[action]" value="doUpload" />
			<table border="0" cellpadding="2" cellspacing="1">
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('repositoryUploadForm_username') . '</td>
					<td><input' . $this->doc->formWidth(20) . ' type="text" name="em[user][fe_u]" value="' . $this->fe_user['username'] . '" /></td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('repositoryUploadForm_password') . '</td>
					<td><input' . $this->doc->formWidth(20) . ' type="password" name="em[user][fe_p]" value="' . $this->fe_user['password'] . '" /></td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('repositoryUploadForm_changelog') . '</td>
					<td><textarea' . $this->doc->formWidth(30, 1) . ' rows="5" name="em[upload][comment]"></textarea></td>
				</tr>
				<tr class="bgColor4">
					<td>' . $GLOBALS['LANG']->getLL('repositoryUploadForm_command') . '</td>
					<td nowrap="nowrap">
						<input type="radio" name="em[upload][mode]" id="new_dev" value="new_dev" checked="checked" />
							<label for="new_dev">' . sprintf($GLOBALS['LANG']->getLL('repositoryUploadForm_new_bugfix'),
				'x.x.<strong>' . tx_em_Tools::rfw('x+1') . '</strong>'
		) . '</label><br />
						<input type="radio" name="em[upload][mode]" id="new_sub" value="new_sub" />
							<label for="new_sub">' . sprintf($GLOBALS['LANG']->getLL('repositoryUploadForm_new_sub_version'),
				'x.<strong>' . tx_em_Tools::rfw('x+1') . '</strong>.0'
		) . '</label><br />
						<input type="radio" name="em[upload][mode]" id="new_main" value="new_main" />
							<label for="new_main">' . sprintf($GLOBALS['LANG']->getLL('repositoryUploadForm_new_main_version'),
				'<strong>' . tx_em_Tools::rfw('x+1') . '</strong>.0.0'
		) . '</label><br />
					</td>
				</tr>
				<tr class="bgColor4">
					<td>&nbsp;</td>
					<td><input type="submit" name="submit" value="' . $GLOBALS['LANG']->getLL('repositoryUploadForm_upload') . '" />
					</td>
				</tr>
			</table>
			</form>';

		return $content;
	}


	/************************************
	 *
	 * Output helper functions
	 *
	 ************************************/


	/**
	 * Returns a header for an extensions including icon if any
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @param	string		align-attribute value (for <img> tag)
	 * @return	string		HTML; Extension title and image.
	 */
	function extensionTitleIconHeader($extKey, $extInfo, $align = 'top') {
		$imgInfo = @getImageSize(tx_em_Tools::getExtPath($extKey, $extInfo['type']) . '/ext_icon.gif');
		$out = '';
		if (is_array($imgInfo)) {
			$out .= '<img src="' . $GLOBALS['BACK_PATH'] . tx_em_Tools::typeRelPath($extInfo['type']) . $extKey . '/ext_icon.gif" ' . $imgInfo[3] . ' align="' . $align . '" alt="" />';
		}
		$out .= $extInfo['EM_CONF']['title'] ? htmlspecialchars(t3lib_div::fixed_lgd_cs($extInfo['EM_CONF']['title'], 40)) : '<em>' . htmlspecialchars($extKey) . '</em>';
		return $out;
	}








	/************************************
	 *
	 * Various helper functions
	 *
	 ************************************/

	/**
	 * Returns subtitles for the extension listings
	 *
	 * @param	string		List order type
	 * @param	string		Key value
	 * @return	string		output.
	 */
	function listOrderTitle($listOrder, $key) {
		switch ($listOrder) {
			case 'cat':
				return isset($this->categories[$key]) ? $this->categories[$key] : '[' . $key . ']';
				break;
			case 'author_company':
				return $key;
				break;
			case 'state':
				return $this->states[$key];
				break;
			case 'type':
				return $this->typeDescr[$key];
				break;
		}
	}


	/**
	 * Returns true if global OR local installation of extensions is allowed/possible.
	 *
	 * @return	boolean		Returns true if global OR local installation of extensions is allowed/possible.
	 */
	function importAtAll() {
		return ($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowGlobalInstall'] || $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowLocalInstall']);
	}


	/**
	 * Searches for ->lookUpStr in extension and returns true if found (or if no search string is set)
	 *
	 * @param	string		Extension key
	 * @param	array		Extension content
	 * @return	boolean		If true, display extension in list
	 */
	function searchExtension($extKey, $row) {
		if ($this->lookUpStr) {
			return (
					stristr($extKey, $this->lookUpStr) ||
							stristr($row['EM_CONF']['title'], $this->lookUpStr) ||
							stristr($row['EM_CONF']['description'], $this->lookUpStr) ||
							stristr($row['EM_CONF']['author'], $this->lookUpStr) ||
							stristr($row['EM_CONF']['author_company'], $this->lookUpStr)
			);
		} else {
			return true;
		}
	}


	/**
	 *  Checks if there are newer versions of installed extensions in the TER
	 *  integrated from the extension "ter_update_check" for TYPO3 4.2 by Christian Welzel
	 *
	 * @return	nothing
	 */
	function checkForUpdates() {
		$content = '';

		$count = intval(tx_em_Database::getExtensionCountFromRepository());
		if ($count > 0) {
			$content = $this->extensionList->showExtensionsToUpdate()
					. t3lib_BEfunc::getFuncCheck(0, 'SET[display_installed]', $this->MOD_SETTINGS['display_installed'], '', '', 'id="checkDisplayInstalled"')
					. '&nbsp;<label for="checkDisplayInstalled">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:display_nle') . '</label><br />'
					. t3lib_BEfunc::getFuncCheck(0, 'SET[display_files]', $this->MOD_SETTINGS['display_files'], '', '', 'id="checkDisplayFiles"')
					. '&nbsp;<label for="checkDisplayFiles">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:display_files') . '</label>';
			$this->content .= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:header_upd_ext'), $content, 0, 1);

			$dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
			$timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
			$content = sprintf($GLOBALS['LANG']->getLL('note_last_update_new'),
				date(
					$dateFormat . ', ' . $timeFormat,
					filemtime(PATH_site . 'typo3temp/extensions.xml.gz')
				)
			) . '<br />';
		}

		$content .= sprintf($GLOBALS['LANG']->getLL('note_last_update2_new'),
				'<a href="' . t3lib_div::linkThisScript(array(
					'SET[function]' => 2
				)) . '">', '</a>');
		$this->content .= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:header_vers_ret'), $content, 0, 1);
	}


	function showRepositoryUpdateForm() {
		$content = '<div class="em-repupdate"><strong>Repository:</strong>';

		// print registered repositories
		/* @var $settings em_settings */
		$settings = t3lib_div::makeInstance('tx_em_Settings');
		$registeredRepos = $settings->getRegisteredRepositories();
		$content .= '<select>';
		foreach ($registeredRepos as $repository) {
			$content .= '<option>' . $repository->getTitle() . '</option>';
		}
		$content .= '</select>';

		$selectedRepo = $settings->getSelectedRepository();
		/* @var $repoUtility em_repository_utility */
		$repoUtility = t3lib_div::makeInstance('tx_em_Repository_Utility');
		$repoUtility->setRepository($selectedRepo);

		$onCLick = 'window.location.href="' . t3lib_div::linkThisScript(array(
			'CMD[fetchMetaData]' => 'extensions'
		)) . '";return false;';
		$content .= '
			<input type="button" value="' . $GLOBALS['LANG']->getLL('retrieve_update') .
				'" onclick="' . htmlspecialchars($onCLick) . '" />';
		if (is_file($repoUtility->getLocalExtListFile())) {
			$dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
			$timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];

			$count = tx_em_Database::getExtensionCountFromRepository($repoUtility->getRepositoryUID());
			$content .= '<span style="margin-left:10px;padding-right: 50px;" class="typo3-message message-notice">' .
					sprintf($GLOBALS['LANG']->getLL('ext_list_last_updated'),
						date(
							$dateFormat . ', ' . $timeFormat,
							filemtime($repoUtility->getLocalExtListFile())
						), $count) . '</span>';
		} else {
			$content .= '<span style="margin-left:10px;padding-right: 50px;" class="typo3-message message-error">There are no extensions available, please update!</span>';
		}
		$content .= '<br>&nbsp;<br>';

		if ($this->CMD['fetchMetaData'] && $this->CMD['fetchMetaData'] == 'extensions') { // fetches mirror/extension data from online rep.
			$content .= $repoUtility->updateExtList(TRUE)->render();
		}

		$content .= '</div>';
		return $content;
	}


	// Function wrappers for compatibility

	/**
	 * Reports back if installation in a certain scope is possible.
	 *
	 * @param	string		Scope: G, L, S
	 * @param	string		Extension lock-type (eg. "L" or "G")
	 * @return	boolean		True if installation is allowed.
	 */
	public static function importAsType($type, $lockType = '') {
		return tx_em_Tools::importAsType($type, $lockType);
	}

	/**
	 * Returns the list of available (installed) extensions
	 *
	 * @return	array		Array with two arrays, list array (all extensions with info) and category index
	 * @wrapper for compatibility
	 */
	public function getInstalledExtensions() {
		return $this->extensionList->getInstalledExtensions();
	}


	/**
	 * @return string
	 */
	protected function getSubmitAndOpenerCloseLink() {
		if (!$this->CMD['standAlone'] && !$this->noDocHeader && ($this->CMD['standAlone'] || t3lib_div::_GP('standAlone'))) {
			$link = '<a href="javascript:opener.top.list.iframe.document.forms[0].submit();window.close();">' .
				$GLOBALS['LANG']->getLL('ext_import_close_check') . '</a>';
			return $link;
		} else {
			return '<a id="closewindow" href="javascript:if (parent.TYPO3.EM) {parent.TYPO3.EM.Tools.closeImportWindow();} else {window.close();}">' . $GLOBALS['LANG']->getLL('ext_import_close') . '</a>';
		}
	}


	/* Compatibility wrappers */


/**
	 * Returns the absolute path where the extension $extKey is installed (based on 'type' (SGL))
	 *
	 * @param	string		Extension key
	 * @param	string		Install scope type: L, G, S
	 * @return	string		Returns the absolute path to the install scope given by input $type variable. It is checked if the path is a directory. Slash is appended.
	 */
	public function getExtPath($extKey, $type, $returnWithoutExtKey = FALSE) {
		return tx_em_Tools::getExtPath($extKey, $type, $returnWithoutExtKey);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['em/index.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['em/index.php']);
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_em_index');
$SOBE->init();
foreach ($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}
$SOBE->checkExtObj();

$SOBE->main();
$SOBE->printContent();

?>