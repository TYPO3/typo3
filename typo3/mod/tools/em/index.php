<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  196: class em_install_class extends t3lib_install
 *  203:     function em_install_class()
 *
 *
 *  220: class SC_mod_tools_em_index
 *
 *              SECTION: Standard module initialization
 *  370:     function init()
 *  434:     function menuConfig()
 *  509:     function main()
 *  581:     function printContent()
 *
 *              SECTION: Function Menu Applications
 *  608:     function extensionList_loaded()
 *  643:     function extensionList_installed()
 *  715:     function extensionList_import()
 *  868:     function kickstarter()
 *  885:     function alterSettings()
 *
 *              SECTION: Command Applications (triggered by GET var)
 *  931:     function importExtInfo($extRepUid)
 * 1032:     function importExtFromRep($extRepUid,$loc,$uploadFlag=0,$directInput='',$recentTranslations=0,$incManual=0)
 * 1192:     function showExtDetails($extKey)
 *
 *              SECTION: Application Sub-functions (HTML parts)
 * 1477:     function updatesForm($extKey,$extInfo,$notSilent=0,$script='',$addFields='')
 * 1508:     function extDumpTables($extKey,$extInfo)
 * 1575:     function getFileListOfExtension($extKey,$conf)
 * 1626:     function extDelete($extKey,$extInfo)
 * 1658:     function extUpdateEMCONF($extKey,$extInfo)
 * 1678:     function extMakeNewFromFramework($extKey,$extInfo)
 * 1699:     function extBackup($extKey,$extInfo)
 * 1767:     function extBackup_dumpDataTablesLine($tablesArray,$extKey)
 * 1795:     function extInformationArray($extKey,$extInfo,$remote=0)
 * 1892:     function extInformationArray_dbReq($techInfo,$tableHeader=0)
 * 1905:     function extInformationArray_dbInst($dbInst,$current)
 * 1924:     function getRepositoryUploadForm($extKey,$extInfo)
 *
 *              SECTION: Extension list rendering
 * 2022:     function extensionListRowHeader($trAttrib,$cells,$import=0)
 * 2087:     function extensionListRow($extKey,$extInfo,$cells,$bgColorClass='',$inst_list=array(),$import=0,$altLinkUrl='')
 *
 *              SECTION: Output helper functions
 * 2212:     function wrapEmail($str,$email)
 * 2225:     function helpCol($key)
 * 2239:     function labelInfo($str)
 * 2251:     function extensionTitleIconHeader($extKey,$extInfo,$align='top')
 * 2266:     function removeButton()
 * 2275:     function installButton()
 * 2284:     function noImportMsg()
 *
 *              SECTION: Read information about all available extensions
 * 2309:     function getInstalledExtensions()
 * 2336:     function getInstExtList($path,&$list,&$cat,$type)
 * 2370:     function getImportExtList($listArr)
 * 2422:     function setCat(&$cat,$listArrayPart,$extKey)
 *
 *              SECTION: Extension analyzing (detailed information)
 * 2484:     function makeDetailedExtensionAnalysis($extKey,$extInfo,$validity=0)
 * 2666:     function getClassIndexLocallangFiles($absPath,$table_class_prefix,$extKey)
 * 2737:     function modConfFileAnalysis($confFilePath)
 * 2765:     function serverExtensionMD5Array($extKey,$conf)
 * 2790:     function findMD5ArrayDiff($current,$past)
 *
 *              SECTION: File system operations
 * 2822:     function createDirsInPath($dirs,$extDirPath)
 * 2847:     function removeExtDirectory($removePath,$removeContentOnly=0)
 * 2908:     function clearAndMakeExtensionDir($importedData,$type)
 * 2961:     function removeCacheFiles()
 * 2981:     function extractDirsFromFileList($files)
 * 3007:     function getExtPath($extKey,$type)
 *
 *              SECTION: Writing to "conf.php" and "localconf.php" files
 * 3039:     function writeTYPO3_MOD_PATH($confFilePath,$type,$mP)
 * 3076:     function writeNewExtensionList($newExtList)
 * 3099:     function writeTsStyleConfig($extKey,$arr)
 * 3121:     function updateLocalEM_CONF($extKey,$extInfo)
 *
 *              SECTION: Compiling upload information, emconf-file etc.
 * 3159:     function construct_ext_emconf_file($extKey,$EM_CONF)
 * 3204:     function makeUploadArray($extKey,$conf)
 * 3271:     function getSerializedLocalLang($file,$content)
 *
 *              SECTION: Managing dependencies, conflicts, priorities, load order of extension keys
 * 3305:     function addExtToList($extKey,$instExtInfo)
 * 3367:     function removeExtFromList($extKey,$instExtInfo)
 * 3404:     function removeRequiredExtFromListArr($listArr)
 * 3419:     function managesPriorities($listArr,$instExtInfo)
 *
 *              SECTION: System Update functions (based on extension requirements)
 * 3471:     function checkClearCache($extKey,$extInfo)
 * 3499:     function checkUploadFolder($extKey,$extInfo)
 * 3587:     function checkDBupdates($extKey,$extInfo,$infoOnly=0)
 * 3686:     function tsStyleConfigForm($extKey,$extInfo,$output=0,$script='',$addFields='')
 *
 *              SECTION: Dumping database (MySQL compliant)
 * 3780:     function dumpTableAndFieldStructure($arr)
 * 3805:     function dumpStaticTables($tableList)
 * 3834:     function dumpHeader()
 * 3851:     function dumpTableHeader($table,$fieldKeyInfo,$dropTableIfExists=0)
 * 3890:     function dumpTableContent($table,$fieldStructure)
 * 3925:     function getTableAndFieldStructure($parts)
 *
 *              SECTION: TER Communication functions
 * 3973:     function fetchServerData($repositoryUrl)
 * 4002:     function decodeServerData($externalData,$stat=array())
 * 4028:     function decodeExchangeData($str)
 * 4050:     function makeUploadDataFromArray($uploadArray,$local_gzcompress=-1)
 * 4075:     function repTransferParams()
 * 4089:     function makeReturnUrl()
 * 4099:     function T3instID()
 * 4110:     function processRepositoryReturnData($TER_CMD)
 *
 *              SECTION: Various helper functions
 * 4154:     function listOrderTitle($listOrder,$key)
 * 4185:     function makeVersion($v,$mode)
 * 4197:     function renderVersion($v,$raise='')
 * 4234:     function ulFolder($extKey)
 * 4243:     function importAtAll()
 * 4254:     function importAsType($type,$lockType='')
 * 4274:     function deleteAsType($type)
 * 4292:     function getDocManual($extension_key,$loc='')
 * 4308:     function versionDifference($v1,$v2,$div=1)
 * 4320:     function first_in_array($str,$array,$caseInsensitive=FALSE)
 * 4337:     function includeEMCONF($path,$_EXTKEY)
 *
 * TOTAL FUNCTIONS: 89
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$BE_USER->modAccess($MCONF,1);

	// Include classes needed:
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_install.php');
require_once(PATH_t3lib.'class.t3lib_tsstyleconfig.php');

	// Include kickstarter wrapped class if extension "extrep_wizard" turns out to be loaded!
if (t3lib_extMgm::isLoaded('extrep_wizard'))	{
	require('./class.kickstarter.php');
}







/**
 * Module: Extension manager
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_mod_tools_em_index {

		// Internal, static:
	var $versionDiffFactor = 1000;		// This means that version difference testing for import is detected for sub-versions only, not dev-versions. Default: 1000
	var $systemInstall = 0;				// If "1" then installs in the sysext directory is allowed. Default: 0
	var $repositoryUrl = '';			// Default is "http://ter.typo3.com/?id=t3_extrep" configured in config_default.php
	var $requiredExt = '';				// List of required extension (from TYPO3_CONF_VARS)
	var $maxUploadSize = 6024000;		// Max size of extension upload to repository
	var $kbMax = 100;					// Max size in kilobytes for files to be edited.

	/**
	 * Internal variable loaded with extension categories (for display/listing). Should reflect $categories above
	 * Dynamic var.
	 */
	var $defaultCategories = Array(
		'cat' => Array (
			'be' => array(),
			'module' => array(),
			'fe' => array(),
			'plugin' => array(),
			'misc' => array(),
			'services' => array(),
			'templates' => array(),
			'example' => array(),
			'doc' => array()
		)
	);

	/**
	 * Extension Categories (static var)
	 * Content must be redundant with the same internal variable as in class.tx_extrep.php!
	 */
	var $categories = Array(
		'be' => 'Backend',
		'module' => 'Backend Modules',
		'fe' => 'Frontend',
		'plugin' => 'Frontend Plugins',
		'misc' => 'Miscellaneous',
		'services' => 'Services',
		'templates' => 'Templates',
		'example' => 'Examples',
		'doc' => 'Documentation'
	);

	/**
	 * Extension States
	 * Content must be redundant with the same internal variable as in class.tx_extrep.php!
	 */
	var $states = Array (
		'alpha' => 'Alpha',
		'beta' => 'Beta',
		'stable' => 'Stable',
		'experimental' => 'Experimental',
		'test' => 'Test',
		'obsolete' => 'Obsolete',
	);

	/**
	 * "TYPE" information; labels, paths, description etc.
	 */
	var $typeLabels = Array (
		'S' => 'System',
		'G' => 'Global',
		'L' => 'Local',
	);
	var $typeDescr = Array (
		'S' => 'System extension (typo3/sysext/) - Always distributed with source code (Static).',
		'G' => 'Global extensions (typo3/ext/) - Available for shared source on server (Dynamic).',
		'L' => 'Local extensions (typo3conf/ext/) - Local for this TYPO3 installation only (Dynamic).',
	);
	var $typePaths = Array();			// Also static, set in init()
	var $typeBackPaths = Array();		// Also static, set in init()

	var $typeRelPaths = Array (
		'S' => 'sysext/',
		'G' => 'ext/',
		'L' => '../typo3conf/ext/',
	);

	/**
	 * Remote access types (labels)
	 */
	var $remoteAccess = Array (
		'all' => '',
		'owner' => 'Owner',
		'selected' => 'Selected',
		'member' => 'Member',
	);

	var $detailCols = Array (
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
			'uploadPass' => '',
		);

	var $privacyNotice = 'When ever you interact with the online repository, server information is sent and stored in the repository for statistics. No personal information is sent, only identification of this TYPO3 install. If you want know exactly what is sent, look in typo3/tools/em/index.php, function repTransferParams()';
	var $editTextExtensions = 'html,htm,txt,css,tmpl,inc,php,sql,conf,cnf,pl,pm,sh';
	var $nameSpaceExceptions = 'beuser_tracking,design_components,impexp,static_file_edit,cms,freesite,quickhelp,classic_welcome,indexed_search,sys_action,sys_workflows,sys_todos,sys_messages,plugin_mgm,direct_mail,sys_stat,tt_address,tt_board,tt_calender,tt_guest,tt_links,tt_news,tt_poll,tt_rating,tt_products,setup,taskcenter,tsconfig_help,context_help,sys_note,tstemplate,lowlevel,install,belog,beuser,phpmyadmin,aboutmodules,imagelist,setup,taskcenter,sys_notepad,viewpage';





		// Default variables for backend modules
	var $MCONF = array();				// Module configuration
	var $MOD_MENU = array();			// Module menu items
	var $MOD_SETTINGS = array();		// Module session settings
	var $doc;							// Document Template Object
	var $content;						// Accumulated content

	var $inst_keys = array();			// Storage of installed extensions
	var $gzcompress = 0;				// Is set true, if system support compression.

		// GPvars:
	var $CMD = array();					// CMD array
	var $listRemote;					// If set, connects to remote repository














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
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TYPO3_CONF_VARS;

			// Setting paths of install scopes:
		$this->typePaths = Array (
			'S' => TYPO3_mainDir.'sysext/',
			'G' => TYPO3_mainDir.'ext/',
			'L' => 'typo3conf/ext/'
		);
		$this->typeBackPaths = Array (
			'S' => '../../../',
			'G' => '../../../',
			'L' => '../../../../'.TYPO3_mainDir
		);

			// Setting module configuration:
		$this->MCONF = $GLOBALS['MCONF'];

			// Setting GPvars:
		$this->CMD = t3lib_div::_GP('CMD');
		$this->listRemote = t3lib_div::_GP('ter_connect');
		$this->listRemote_search = t3lib_div::_GP('ter_search');


			// Configure menu
		$this->menuConfig();

			// Setting internal static:
		$this->gzcompress = function_exists('gzcompress');
		if ($TYPO3_CONF_VARS['EXT']['em_devVerUpdate'])		$this->versionDiffFactor = 1;
		if ($TYPO3_CONF_VARS['EXT']['em_systemInstall'])	$this->systemInstall = 1;
		$this->repositoryUrl = $TYPO3_CONF_VARS['EXT']['em_TERurls'][0];
		$this->requiredExt = t3lib_div::trimExplode(',',$TYPO3_CONF_VARS['EXT']['requiredExt'],1);

			// Initialize Document Template object:
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType = 'xhtml_trans';

				// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL)	{	//
				document.location = URL;
			}
		');
		$this->doc->form = '<form action="" method="post" name="pageform">';

			// Descriptions:
		$this->descrTable = '_MOD_'.$this->MCONF['name'];
		if ($BE_USER->uc['edit_showFieldHelp'])	{
			$LANG->loadSingleTableDescription($this->descrTable);
		}

			// Setting username/password etc. for upload-user:
		$this->fe_user['username'] = $this->MOD_SETTINGS['fe_u'];
		$this->fe_user['password'] = $this->MOD_SETTINGS['fe_p'];
		$this->fe_user['uploadPass'] = $this->MOD_SETTINGS['fe_up'];
	}

	/**
	 * Configuration of which mod-menu items can be used
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $BE_USER;

			// MENU-ITEMS:
		$this->MOD_MENU = array(
			'function' => array(
				0 => 'Loaded extensions',
				1 => 'Available extensions to install',
				2 => 'Import extensions from online repository',
				4 => 'Make new extension',
				3 => 'Settings',
			),
			'listOrder' => array(
				'cat' => 'Category',
				'author_company' => 'Author',
				'state' => 'State',
				'private' => 'Private',
				'type' => 'Type',
				'dep' => 'Dependencies',
			),
			'display_details' => array(
				1 => 'Details',
				0 => 'Description',
				2 => 'More details',

				3 => 'Technical (takes time!)',
				4 => 'Validating (takes time!)',
				5 => 'Changed? (takes time!)',
			),
			'display_shy' => '',
			'own_member_only' => '',
			'singleDetails' => array(
				'info' => 'Information',
				'edit' => 'Edit files',
				'backup' => 'Backup/Delete',
				'dump' => 'Dump DB',
				'upload' => 'Upload',
				'updateModule' => 'UPDATE!',
			),
			'fe_u' => '',
			'fe_p' => '',
			'fe_up' => '',
		);

			// page/be_user TSconfig settings and blinding of menu-items
		if (!$BE_USER->getTSConfigVal('mod.'.$this->MCONF['name'].'.allowTVlisting'))	{
			unset($this->MOD_MENU['display_details'][3]);
			unset($this->MOD_MENU['display_details'][4]);
			unset($this->MOD_MENU['display_details'][5]);
		}

			// Remove kickstarter if extension is not loaded:
		if (!t3lib_extMgm::isLoaded('extrep_wizard'))	{
			unset($this->MOD_MENU['function'][4]);
		}

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);

		if ($this->MOD_SETTINGS['function']==2)	{
				// If listing from online repository, certain items are removed though:
			unset($this->MOD_MENU['listOrder']['type']);
			unset($this->MOD_MENU['listOrder']['private']);
			unset($this->MOD_MENU['display_details'][3]);
			unset($this->MOD_MENU['display_details'][4]);
			unset($this->MOD_MENU['display_details'][5]);
			$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
		}
	}

	/**
	 * Main function for Extension Manager module.
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG;

			// Starting page:
		$this->content.=$this->doc->startPage('Extension Manager');
		$this->content.=$this->doc->header('Extension Manager');
		$this->content.=$this->doc->spacer(5);


			// Commands given which is executed regardless of main menu setting:
		if ($this->CMD['showExt'])	{	// Show details for a single extension
			$this->showExtDetails($this->CMD['showExt']);
		} elseif ($this->CMD['importExt'] || $this->CMD['uploadExt'])	{	// Imports an extension from online rep.
			$err = $this->importExtFromRep($this->CMD['importExt'],$this->CMD['loc'],$this->CMD['uploadExt'],'',$this->CMD['transl'],$this->CMD['inc_manual']);
			if ($err)	{
				$this->content.=$this->doc->section('',$GLOBALS['TBE_TEMPLATE']->rfw($err));
			}
		} elseif ($this->CMD['importExtInfo'])	{	// Gets detailed information of an extension from online rep.
			$this->importExtInfo($this->CMD['importExtInfo']);
		} else {	// No command - we show what the menu setting tells us:

			$menu = $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.menu').' '.
				t3lib_BEfunc::getFuncMenu(0,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function']);

			if (t3lib_div::inList('0,1,2',$this->MOD_SETTINGS['function']))	{
				$menu.='&nbsp;Order by:&nbsp;'.t3lib_BEfunc::getFuncMenu(0,'SET[listOrder]',$this->MOD_SETTINGS['listOrder'],$this->MOD_MENU['listOrder']).
					'&nbsp;&nbsp;Show:&nbsp;'.t3lib_BEfunc::getFuncMenu(0,'SET[display_details]',$this->MOD_SETTINGS['display_details'],$this->MOD_MENU['display_details']).
					'<br />Display shy extensions:&nbsp;&nbsp;'.t3lib_BEfunc::getFuncCheck(0,'SET[display_shy]',$this->MOD_SETTINGS['display_shy']);
			}

			if ($this->MOD_SETTINGS['function']==2)	{
					$menu.='&nbsp;&nbsp;&nbsp;Get own/member/selected extensions only:&nbsp;&nbsp;'.
								t3lib_BEfunc::getFuncCheck(0,'SET[own_member_only]',$this->MOD_SETTINGS['own_member_only']);
			}

			$this->content.=$this->doc->section('','<span class="nobr">'.$menu.'</span>');
			$this->content.=$this->doc->spacer(10);

			switch($this->MOD_SETTINGS['function'])	{
				case 0:
						// Lists loaded (installed) extensions
					$this->extensionList_loaded();
				break;
				case 1:
						// Lists the installed (available) extensions
					$this->extensionList_installed();
				break;
				case 2:
						// Lists the extensions available from online rep.
					$this->extensionList_import();
				break;
				case 3:
						// Lists the extensions available from online rep.
					$this->alterSettings();
				break;
				case 4:
					$this->kickstarter();
				break;
			}
		}

			// Shortcuts:
		if ($BE_USER->mayMakeShortcut())	{
			$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('CMD','function',$this->MCONF['name']));
		}
	}

	/**
	 * Print module content. Called as last thing in the global scope.
	 *
	 * @return	void
	 */
	function printContent()	{
		global $SOBE;

		$this->content.= $this->doc->endPage();
		echo $this->content;
	}










	/*********************************
	 *
	 * Function Menu Applications
	 *
	 *********************************/

	/**
	 * Listing of loaded (installed) extensions
	 *
	 * @return	void
	 */
	function extensionList_loaded()	{
		global $TYPO3_LOADED_EXT;

		list($list) = $this->getInstalledExtensions();

			// Loaded extensions
		$content = '';
		$lines = array();
		$lines[] = $this->extensionListRowHeader(' class="bgColor5"',array('<td><img src="clear.gif" width="1" height="1" alt="" /></td>'));

		foreach($TYPO3_LOADED_EXT as $extKey => $eConf)	{
			if (strcmp($extKey, '_CACHEFILE'))	{
				if ($this->MOD_SETTINGS['display_shy'] || !$list[$extKey]['EM_CONF']['shy'])	{
					if (in_array($extKey, $this->requiredExt))	{
						$loadUnloadLink = '<strong>'.$GLOBALS['TBE_TEMPLATE']->rfw('Rq').'</strong>';
					} else {
						$loadUnloadLink = '<a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[remove]=1').'">'.$this->removeButton().'</a>';
					}

					$lines[] = $this->extensionListRow($extKey,$list[$extKey],array('<td class="bgColor">'.$loadUnloadLink.'</td>'));
				}
			}
		}

		$content.= '"Loaded extensions" are currently running on the system. This list shows you which extensions are loaded and in which order.<br />"Shy" extensions are also loaded but "hidden" in this list because they are system related and generally you should just leave them alone unless you know what you are doing.<br /><br />';
		$content.= '<table border="0" cellpadding="2" cellspacing="1">'.implode('',$lines).'</table>';

		$this->content.=$this->doc->section('Loaded Extensions',$content,0,1);
	}

	/**
	 * Listing of available (installed) extensions
	 *
	 * @return	void
	 */
	function extensionList_installed()	{
		global $TYPO3_LOADED_EXT;

		list($list,$cat)=$this->getInstalledExtensions();

			// Available extensions
		if (is_array($cat[$this->MOD_SETTINGS['listOrder']]))	{
			$content='';
			$lines=array();
			$lines[]=$this->extensionListRowHeader(' class="bgColor5"',array('<td><img src="clear.gif" width="18" height="1" alt="" /></td>'));

			$allKeys=array();
			foreach($cat[$this->MOD_SETTINGS['listOrder']] as $catName => $extEkeys)	{
				$allKeys[]='';
				$allKeys[]='TYPE: '.$catName;

				$lines[]='<tr><td colspan="'.(3+$this->detailCols[$this->MOD_SETTINGS['display_details']]).'"><br /></td></tr>';
				$lines[]='<tr><td colspan="'.(3+$this->detailCols[$this->MOD_SETTINGS['display_details']]).'"><img src="'.$GLOBALS['BACK_PATH'].'gfx/i/sysf.gif" width="18" height="16" align="top" alt="" /><strong>'.$this->listOrderTitle($this->MOD_SETTINGS['listOrder'],$catName).'</strong></td></tr>';

				asort($extEkeys);
				reset($extEkeys);
				while(list($extKey)=each($extEkeys))	{
					$allKeys[]=$extKey;
					if ($this->MOD_SETTINGS['display_shy'] || !$list[$extKey]['EM_CONF']['shy'])	{
						$loadUnloadLink = t3lib_extMgm::isLoaded($extKey)?
							'<a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[remove]=1&CMD[clrCmd]=1&SET[singleDetails]=info').'">'.$this->removeButton().'</a>':
							'<a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[load]=1&CMD[clrCmd]=1&SET[singleDetails]=info').'">'.$this->installButton().'</a>';
						if (in_array($extKey,$this->requiredExt))	$loadUnloadLink='<strong>'.$GLOBALS['TBE_TEMPLATE']->rfw('Rq').'</strong>';

						if ($list[$extKey]['EM_CONF']['private'])	{
							$theBgColor = '#F6CA96';
						} else {
							$theBgColor = t3lib_extMgm::isLoaded($extKey)?$this->doc->bgColor4:t3lib_div::modifyHTMLcolor($this->doc->bgColor4,20,20,20);
						}
						$lines[]=$this->extensionListRow($extKey,$list[$extKey],array('<td class="bgColor">'.$loadUnloadLink.'</td>'),
										$theBgColor);
					}
				}
			}

			$content.='


<!--
EXTENSION KEYS:


'.trim(implode(chr(10),$allKeys)).'

-->




';

#debug($this->MOD_SETTINGS['listOrder']);
			$content.= 'Available extensions are extensions which are present in the extension folders. You can install any of the available extensions in this list. When you install an extension it will be loaded by TYPO3 from that moment.<br />
						In this list the extensions with dark background are installed (loaded) - the others just available (not loaded), ready to be installed on your request.<br />
						So if you want to use an extension in TYPO3, you should simply click the "plus" button '.$this->installButton().' . <br />
						Installed extensions can also be removed again - just click the remove button '.$this->removeButton().' .<br /><br />';
			$content.= '<table border="0" cellpadding="2" cellspacing="1">'.implode('',$lines).'</table>';

			$this->content.=$this->doc->section('Available Extensions - Order by: '.$this->MOD_MENU['listOrder'][$this->MOD_SETTINGS['listOrder']],$content,0,1);
		}
	}

	/**
	 * Listing remote extensions from online repository
	 *
	 * @return	void
	 */
	function extensionList_import()	{
		global $TYPO3_LOADED_EXT;

			// Listing from online repository:
		if ($this->listRemote)	{
			list($inst_list,$inst_cat) = $this->getInstalledExtensions();
			$this->inst_keys = array_flip(array_keys($inst_list));

			$this->detailCols[1]+=6;

				// Getting data from repository:
			$repositoryUrl=$this->repositoryUrl.
				$this->repTransferParams().
				'&tx_extrep[cmd]=currentListing'.
				($this->MOD_SETTINGS['own_member_only']?'&tx_extrep[listmode]=1':'').
				($this->listRemote_search ? '&tx_extrep[search]='.rawurlencode($this->listRemote_search) : '');

			$fetchData = $this->fetchServerData($repositoryUrl);

			if (is_array($fetchData))	{
				$listArr = $fetchData[0];
				list($list,$cat) = $this->getImportExtList($listArr);

					// Available extensions
				if (is_array($cat[$this->MOD_SETTINGS['listOrder']]))	{
					$content='';
					$lines=array();
					$lines[]=$this->extensionListRowHeader(' class="bgColor5"',array('<td><img src="clear.gif" width="18" height="1" alt="" /></td>'),1);

					foreach($cat[$this->MOD_SETTINGS['listOrder']] as $catName => $extEkeys)	{
						if (count($extEkeys))	{
							$lines[]='<tr><td colspan="'.(3+$this->detailCols[$this->MOD_SETTINGS['display_details']]).'"><br /></td></tr>';
							$lines[]='<tr><td colspan="'.(3+$this->detailCols[$this->MOD_SETTINGS['display_details']]).'"><img src="'.$GLOBALS['BACK_PATH'].'gfx/i/sysf.gif" width="18" height="16" align="top" alt="" /><strong>'.$this->listOrderTitle($this->MOD_SETTINGS['listOrder'],$catName).'</strong></td></tr>';

							asort($extEkeys);
							reset($extEkeys);
							while(list($extKey)=each($extEkeys))	{
								if ($this->MOD_SETTINGS['display_shy'] || !$list[$extKey]['EM_CONF']['shy'])	{
									$loadUnloadLink='';
									if ($inst_list[$extKey]['type']!='S' && (!isset($inst_list[$extKey]) || $this->versionDifference($list[$extKey]['EM_CONF']['version'],$inst_list[$extKey]['EM_CONF']['version'],$this->versionDiffFactor)))	{
										if (isset($inst_list[$extKey]))	{
												// update
											$loc= ($inst_list[$extKey]['type']=='G'?'G':'L');
											$aUrl = 'index.php?CMD[importExt]='.$list[$extKey]['extRepUid'].'&CMD[loc]='.$loc.($this->getDocManual($extKey,$loc)?'&CMD[inc_manual]=1':'');
											$loadUnloadLink.= '<a href="'.htmlspecialchars($aUrl).'"><img src="'.$GLOBALS['BACK_PATH'].'gfx/import_update.gif" width="12" height="12" title="Update the extension in \''.($loc=='G'?'global':'local').'\' from online repository to server" alt="" /></a>';
										} else {
												// import
											$aUrl = 'index.php?CMD[importExt]='.$list[$extKey]['extRepUid'].'&CMD[loc]=L'.($this->getDocManual($extKey)?'&CMD[inc_manual]=1':'');
											$loadUnloadLink.= '<a href="'.htmlspecialchars($aUrl).'"><img src="'.$GLOBALS['BACK_PATH'].'gfx/import.gif" width="12" height="12" title="Import this extension to \'local\' dir typo3conf/ext/ from online repository." alt="" /></a>';
										}
									} else {
										$loadUnloadLink = '&nbsp;';
									}

									if ($list[$extKey]['_MEMBERS_ONLY'])	{
										$theBgColor = '#F6CA96';
									} elseif (isset($inst_list[$extKey]))	{
										$theBgColor = t3lib_extMgm::isLoaded($extKey)?$this->doc->bgColor4:t3lib_div::modifyHTMLcolor($this->doc->bgColor4,20,20,20);
									} else {
										$theBgColor = t3lib_div::modifyHTMLcolor($this->doc->bgColor2,30,30,30);
									}
									$lines[]=$this->extensionListRow($extKey,$list[$extKey],array('<td class="bgColor">'.$loadUnloadLink.'</td>'),
													$theBgColor,$inst_list,1,'index.php?CMD[importExtInfo]='.$list[$extKey]['extRepUid']);
								}
							}
						}
					}

					$content.= 'Extensions in this list are online for immediate download from the TYPO3 Extension Repository.<br />
								Extensions with dark background are those already on your server - the others must be imported from the repository to your server before you can use them.<br />
								So if you want to use an extension from the repository, you should simply click the "import" button.<br /><br />';

					$content.= '<table border="0" cellpadding="2" cellspacing="1">'.implode('',$lines).'</table>';

					$content.= '<br />Data fetched: ['.implode('][',$fetchData[1]).']';
					$content.= '<br /><br /><strong>PRIVACY NOTICE:</strong><br /> '.$this->privacyNotice;

					$this->content.=$this->doc->section('Extensions in TYPO3 Extension Repository (online) - Order by: '.$this->MOD_MENU['listOrder'][$this->MOD_SETTINGS['listOrder']],$content,0,1);

					if (!$this->MOD_SETTINGS['own_member_only'] && !$this->listRemote_search)	{
							// Plugins which are NOT uploaded to repository but present on this server.
						$content='';
						$lines=array();
						if (count($this->inst_keys))	{
							$lines[]=$this->extensionListRowHeader(' class="bgColor5"',array('<td><img src="clear.gif" width="18" height="1" alt="" /></td>'));

							reset($this->inst_keys);
							while(list($extKey)=each($this->inst_keys))	{
								if ($this->MOD_SETTINGS['display_shy'] || !$inst_list[$extKey]['EM_CONF']['shy'])	{
									$loadUnloadLink = t3lib_extMgm::isLoaded($extKey)?
										'<a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[remove]=1&CMD[clrCmd]=1&SET[singleDetails]=info').'">'.$this->removeButton().'</a>':
										'<a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[load]=1&CMD[clrCmd]=1&SET[singleDetails]=info').'">'.$this->installButton().'</a>';
									if (in_array($extKey,$this->requiredExt))	$loadUnloadLink='<strong>'.$GLOBALS['TBE_TEMPLATE']->rfw('Rq').'</strong>';
									$lines[]=$this->extensionListRow($extKey,$inst_list[$extKey],array('<td class="bgColor">'.$loadUnloadLink.'</td>'),
													t3lib_extMgm::isLoaded($extKey)?$this->doc->bgColor4:t3lib_div::modifyHTMLcolor($this->doc->bgColor4,20,20,20));
								}
							}
						}

						$content.= 'This is the list of extensions which are either user-defined (should be prepended user_ then) or which are private (and does not show up in the public list above).<br /><br />';
						$content.= '<table border="0" cellpadding="2" cellspacing="1">'.implode('',$lines).'</table>';
						$this->content.=$this->doc->spacer(20);
						$this->content.=$this->doc->section('Extensions found only on this server',$content,0,1);
					}
				}
			}
		} else {
			$content = 'Click here to connect to "'.$this->repositoryUrl.'" and retrieve the list of publicly available plugins from the TYPO3 Extension Repository.<br />';

			if ($this->fe_user['username'])	{
				$content.= '<br /><img src="'.$GLOBALS['BACK_PATH'].'gfx/icon_note.gif" width="18" height="16" align="top" alt="" />Repository username "'.$this->fe_user['username'].'" will be sent as authentication.<br />';
			} else {
				$content.= '<br /><img src="'.$GLOBALS['BACK_PATH'].'gfx/icon_warning2.gif" width="18" height="16" align="top" alt="" />You have not configured a repository username/password yet. Please <a href="index.php?SET[function]=3">go to "Settings"</a> and do that.<br />';
			}

			$onCLick = "document.location='index.php?ter_connect=1&ter_search='+escape(this.form['_lookUp'].value);return false;";
			$content.= '<br />
			Look up: <input type="text" name="_lookUp" value="" />
			<input type="submit" value="Connect to online repository" onclick="'.htmlspecialchars($onCLick).'" />';

			$this->content.=$this->doc->section('Extensions in TYPO3 Extension Repository',$content,0,1);
		}

			// Private lookup:
/*
		$onClick = 'document.location=\'index.php?CMD[importExtInfo]=\'+document.pageform.uid_private_key.value+\'&CMD[download_password]=\'+document.pageform.download_password.value; return false;';
		$content= 'Privat lookup key: <input type="text" name="uid_private_key" /> Password, if any: <input type="text" name="download_password" /><input type="submit" value="Lookup" onclick="'.htmlspecialchars($onClick).'" />';
		$this->content.=$this->doc->spacer(20);
		$this->content.=$this->doc->section('Private extension lookup:',$content,0,1);
*/

			// Upload:
		if ($this->importAtAll())	{
			$content= '</form><form action="index.php" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" method="post">
			Upload extension file (.t3x):<br />
				<input type="file" size="60" name="upload_ext_file" /><br />
				... in location:<br />
				<select name="CMD[loc]">';
				if ($this->importAsType('L'))	$content.='<option value="L">Local (../typo3conf/ext/)</option>';
				if ($this->importAsType('G'))	$content.='<option value="G">Global (typo3/ext/)</option>';
				if ($this->importAsType('S'))	$content.='<option value="S">System (typo3/sysext/)</option>';
			$content.='</select><br />
	<input type="checkbox" value="1" name="CMD[uploadOverwrite]" /> Overwrite any existing extension!<br />
	<input type="submit" name="CMD[uploadExt]" value="Upload extension file" /><br />
			';
			if (!$this->gzcompress)	{
				$content.='<br />'.$GLOBALS['TBE_TEMPLATE']->rfw("NOTE: No decompression available! Don't upload a compressed extension - it will not succeed.");
			}
		} else $content=$this->noImportMsg();

		$this->content.=$this->doc->spacer(20);
		$this->content.=$this->doc->section('Upload extension file directly (.t3x):',$content,0,1);
	}

	/**
	 * Making of new extensions with the kickstarter
	 *
	 * @return	void
	 */
	function kickstarter()	{
		$kickstarter = t3lib_div::makeInstance('em_kickstarter');
		$kickstarter->getPIdata();
		$kickstarter->color = array($this->doc->bgColor5,$this->doc->bgColor4,$this->doc->bgColor);
		$kickstarter->siteBackPath = $this->doc->backPath.'../';
		$kickstarter->pObj = &$this;
		$kickstarter->EMmode = 1;

		$content = $kickstarter->mgm_wizard();
		$this->content.='</form>'.$this->doc->section('Kickstarter wizard',$content,0,1).'<form>';
	}

	/**
	 * Allows changing of settings
	 *
	 * @return	void
	 */
	function alterSettings()	{
		$content = '
		<table border="0" cellpadding="2" cellspacing="2">
			<tr class="bgColor4">
				<td>Enter repository username:</td>
				<td><input type="text" name="SET[fe_u]" value="'.htmlspecialchars($this->MOD_SETTINGS['fe_u']).'" /></td>
			</tr>
			<tr class="bgColor4">
				<td>Enter repository password:</td>
				<td><input type="password" name="SET[fe_p]" value="'.htmlspecialchars($this->MOD_SETTINGS['fe_p']).'" /></td>
			</tr>
			<tr class="bgColor4">
				<td>Enter default upload password:</td>
				<td><input type="password" name="SET[fe_up]" value="'.htmlspecialchars($this->MOD_SETTINGS['fe_up']).'" /></td>
			</tr>
		</table>

		<strong>Notice:</strong> This is <em>not</em> your password to the TYPO3 backend! This user information is what is needed to log in at typo3.org with your account there!<br />
		<br />
		<input type="submit" value="Update" />
		';

		$this->content.=$this->doc->section('Repository settings',$content,0,1);
	}










	/*********************************
	 *
	 * Command Applications (triggered by GET var)
	 *
	 *********************************/

	/**
	 * Returns detailed info about an extension in the online repository
	 *
	 * @param	string		Extension repository uid + optional "private key": [uid]-[key].
	 * @return	void
	 */
	function importExtInfo($extRepUid)	{

			// "Go back" link
		$content = '<a href="index.php" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/goback.gif','width="14" height="14"').' alt="" /> Go back</a>';
		$this->content.= $this->doc->section('',$content);
		$content = '';

			// Create connection URL:
		$uidParts = t3lib_div::trimExplode('-',$extRepUid);
		if (count($uidParts)==2)	{
			$extRepUid = $uidParts[0];
			$addParams = '&tx_extrep[pKey]='.rawurlencode(trim($uidParts[1]))
						.'&tx_extrep[pPass]='.rawurlencode(trim($this->CMD['download_password']));
			$addImportParams = '&CMD[download_password]='.rawurlencode(trim($this->CMD['download_password']));
		} else $addParams = '';

		$repositoryUrl = $this->repositoryUrl.
			$this->repTransferParams().
			$addParams.
			'&tx_extrep[cmd]=extensionInfo'.
			'&tx_extrep[uid]='.$extRepUid;

			// Fetch remote data:
		list($fetchData) = $this->fetchServerData($repositoryUrl);
		if (is_array($fetchData['_other_versions']))	{
			$opt = array();
			$opt[] = '<option value=""></option>';
			$selectWasSet=0;

			foreach($fetchData['_other_versions'] as $dat)	{
				$setSel = ($dat['uid']==$extRepUid?' selected="selected"':'');
				if ($setSel)	$selectWasSet=1;
				$opt[]='<option value="'.$dat['uid'].'"'.$setSel.'>'.$dat['version'].'</option>';
			}
			if (!$selectWasSet && $fetchData['emconf_private'])	{
				$opt[]='<option value="'.$fetchData['uid'].'-'.$fetchData['private_key'].'" selected="selected">'.$fetchData['version'].' (Private)</option>';
			}

				// "Select version" box:
			$onClick = 'document.location=\'index.php?CMD[importExtInfo]=\'+document.pageform.repUid.options[document.pageform.repUid.selectedIndex].value; return false;';
			$select='<select name="repUid">'.implode('',$opt).'</select> <input type="submit" value="Load details" onclick="'.htmlspecialchars($onClick).'" /> or<br /><br />';
			if ($this->importAtAll())	{
				$onClick = '
					document.location=\'index.php?CMD[importExt]=\'
						+document.pageform.repUid.options[document.pageform.repUid.selectedIndex].value
						+\'&CMD[loc]=\'+document.pageform.loc.options[document.pageform.loc.selectedIndex].value
						+\'&CMD[transl]=\'+(document.pageform.transl.checked?1:0)
						+\'&CMD[inc_manual]=\'+(document.pageform.inc_manual.checked?1:0)
						+\''.$addImportParams.'\'; return false;';
				$select.='
				<input type="submit" value="Import/Update" onclick="'.htmlspecialchars($onClick).'"> to:
				<select name="loc">'.
					($this->importAsType('G',$fetchData['emconf_lockType'])?'<option value="G">Global: '.$this->typePaths['G'].$fetchData['extension_key'].'/'.(@is_dir(PATH_site.$this->typePaths['G'].$fetchData['extension_key'])?' (OVERWRITE)':' (empty)').'</option>':'').
					($this->importAsType('L',$fetchData['emconf_lockType'])?'<option value="L">Local: '.$this->typePaths['L'].$fetchData['extension_key'].'/'.(@is_dir(PATH_site.$this->typePaths['L'].$fetchData['extension_key'])?' (OVERWRITE)':' (empty)').'</option>':'').
					($this->importAsType('S',$fetchData['emconf_lockType'])?'<option value="S">System: '.$this->typePaths['S'].$fetchData['extension_key'].'/'.(@is_dir(PATH_site.$this->typePaths['S'].$fetchData['extension_key'])?' (OVERWRITE)':' (empty)').'</option>':'').
					#'<option value="fileadmin">'.htmlspecialchars('TEST: fileadmin/_temp_/[extension key name + date]').'</option>'.
				'</select>
				<br /><input type="checkbox" name="transl" value="1" />Include most recent translations
				<br /><input type="checkbox" name="inc_manual" value="1"'.($this->getDocManual($fetchData['extension_key'],@is_dir(PATH_site.$this->typePaths['G'].$fetchData['extension_key'])?'G':'L')?' checked="checked"':'').' />Include "doc/manual.sxw", if any
				';
			} else $select.= $this->noImportMsg();
			$content.= $select;
			$this->content.= $this->doc->section('Select command',$content,0,1);
		}

			// Details:
		$extKey = $fetchData['extension_key'];
		list($xList) = $this->getImportExtList(array($fetchData));
		$eInfo = $xList[$extKey];
		$eInfo['_TECH_INFO'] = unserialize($fetchData['techinfo']);
		$tempFiles = unserialize($fetchData['files']);

		if (is_array($tempFiles))	{
			reset($tempFiles);
			while(list($fk)=each($tempFiles))	{
				if (!strstr($fk,'/'))	$eInfo['files'][]=$fk;
			}
		}

		$content='<strong>'.$fetchData['_ICON'].' &nbsp;'.$eInfo['EM_CONF']['title'].'</strong><br /><br />';
		$content.=$this->extInformationArray($extKey,$eInfo,1);
		$this->content.=$this->doc->spacer(10);
		$this->content.=$this->doc->section('Remote Extension Details:',$content,0,1);

		if (is_array($fetchData['_MESSAGES']))	{
			$content = implode('<hr />',$fetchData['_MESSAGES']);
			$this->content.=$this->doc->section('Messages from repository server:',$content,0,1,1);
		}
	}

	/**
	 * Imports an extensions from the online repository
	 *
	 * @param	string		Extension repository uid + optional "private key": [uid]-[key].
	 * @param	string		Install scope: "L" or "G"
	 * @param	boolean		If true, extension is uploaded as file
	 * @param	string		"Direct input" of the extension stream. Debugging purpuses, it seems.
	 * @param	boolean		If true, recent translations are included.
	 * @param	boolean		If true, manual is included.
	 * @return	string		Return false on success, returns error message if error.
	 */
	function importExtFromRep($extRepUid,$loc,$uploadFlag=0,$directInput='',$recentTranslations=0,$incManual=0)	{

		if (is_array($directInput))	{
			$fetchData = array($directInput,'');
			$loc = !strcmp($loc,'G')?'G':'L';
		} elseif ($uploadFlag)	{
			if ($GLOBALS['HTTP_POST_FILES']['upload_ext_file']['tmp_name'])	{

					// Read uploaded file:
				$uploadedTempFile = t3lib_div::upload_to_tempfile($GLOBALS['HTTP_POST_FILES']['upload_ext_file']['tmp_name']);
				$fileContent = t3lib_div::getUrl($uploadedTempFile);
				t3lib_div::unlink_tempfile($uploadedTempFile);

					// Decode file data:
				$fetchData = array($this->decodeExchangeData($fileContent),'');

				if (is_array($fetchData))	{
					$extKey = $fetchData[0]['extKey'];
					if ($extKey)	{
						if (!$this->CMD['uploadOverwrite'])	{
							$loc = !strcmp($loc,'G')?'G':'L';
							$comingExtPath = PATH_site.$this->typePaths[$loc].$extKey.'/';
							if (@is_dir($comingExtPath))	{
#								debug('!');
								return 'Extension was already present in "'.$comingExtPath.'" - and the overwrite flag was not set! So nothing done...';
							}	// ... else go on, install...
						}	// ... else go on, install...
					} else return 'No extension key in file. Strange...';
				} else return 'Wrong file format. No data recognized.';
			} else return 'No file uploaded! Probably the file was too large for PHPs internal limit for uploadable files.';
		} else {

				// Create link:
			$content = '<a href="index.php" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/goback.gif','width="14" height="14"').' alt="" /> Go back</a>';
			$this->content.= $this->doc->section('',$content);
			$content = '';

				// Building request URL:
			$uidParts = t3lib_div::trimExplode('-',$extRepUid);
			if (count($uidParts)==2)	{
				$extRepUid=$uidParts[0];
				$addParams='&tx_extrep[pKey]='.rawurlencode(trim($uidParts[1]))
							.'&tx_extrep[pPass]='.rawurlencode(trim($this->CMD['download_password']));
			} else $addParams='';

				// If most recent translation should be delivered, send this:
			if ($recentTranslations)	{
				$addParams.='&tx_extrep[transl]=1';
			}

				// If manual should be included, send this:
			if ($incManual)	{
				$addParams.='&tx_extrep[inc_manual]=1';
			}

			$repositoryUrl=$this->repositoryUrl.
				$this->repTransferParams().
				$addParams.
				'&tx_extrep[cmd]=importExtension'.
				'&tx_extrep[uid]='.$extRepUid;

				// Fetch extension from TER:
			$fetchData = $this->fetchServerData($repositoryUrl);
		}

			// At this point the extension data should be present; so we want to write it to disc:
		if ($this->importAsType($loc))	{
			if (is_array($fetchData))	{	// There was some data successfully transferred
				if ($fetchData[0]['extKey'] && is_array($fetchData[0]['FILES']))	{
					$extKey = $fetchData[0]['extKey'];
					$EM_CONF = $fetchData[0]['EM_CONF'];
					if (!$EM_CONF['lockType'] || !strcmp($EM_CONF['lockType'],$loc))	{
						$res = $this->clearAndMakeExtensionDir($fetchData[0],$loc);
						if (is_array($res))	{
							$extDirPath = trim($res[0]);
							if ($extDirPath && @is_dir($extDirPath) && substr($extDirPath,-1)=='/')	{

								$emConfFile = $this->construct_ext_emconf_file($extKey,$EM_CONF);
								$dirs = $this->extractDirsFromFileList(array_keys($fetchData[0]['FILES']));

								$res = $this->createDirsInPath($dirs,$extDirPath);
								if (!$res)	{
									$writeFiles = $fetchData[0]['FILES'];
									$writeFiles['ext_emconf.php']['content'] = $emConfFile;
									$writeFiles['ext_emconf.php']['content_md5'] = md5($emConfFile);

										// Write files:
									foreach($writeFiles as $theFile => $fileData)	{
										t3lib_div::writeFile($extDirPath.$theFile,$fileData['content']);
										if (!@is_file($extDirPath.$theFile))	{
											$content.='Error: File "'.$extDirPath.$theFile.'" could not be created!!!<br />';
										} elseif (md5(t3lib_div::getUrl($extDirPath.$theFile)) != $fileData['content_md5']) {
											$content.='Error: File "'.$extDirPath.$theFile.'" MD5 was different from the original files MD5 - so the file is corrupted!<br />';
										} elseif (TYPO3_OS!='WIN') {
											#chmod ($extDirPath.$theFile, 0755);	# SHOULD NOT do that here since writing the file should already have set adequate permissions!
										}
									}

										// No content, no errors. Create success output here:
									if (!$content)	{
										$content='SUCCESS: '.$extDirPath.'<br />';

											// Fix TYPO3_MOD_PATH for backend modules in extension:
										$modules = t3lib_div::trimExplode(',',$EM_CONF['module'],1);
										if (count($modules))	{
											foreach($modules as $mD)	{
												$confFileName = $extDirPath.$mD.'/conf.php';
												if (@is_file($confFileName))	{
													$content.= $this->writeTYPO3_MOD_PATH($confFileName,$loc,$extKey.'/'.$mD.'/').'<br />';
												} else $content.='Error: Couldn\'t find "'.$confFileName.'"<br />';
											}
										}
		// NOTICE: I used two hours trying to find out why a script, ext_emconf.php, written twice and in between included by PHP did not update correct the second time. Probably something with PHP-A cache and mtime-stamps.
		// But this order of the code works.... (using the empty Array with type, EMCONF and files hereunder).

											// Writing to ext_emconf.php:
										$sEMD5A = $this->serverExtensionMD5Array($extKey,array(
											'type' => $loc,
											'EM_CONF' => array(),
											'files' => array()
										));
										$EM_CONF['_md5_values_when_last_written'] = serialize($sEMD5A);
										$emConfFile = $this->construct_ext_emconf_file($extKey,$EM_CONF);
										t3lib_div::writeFile($extDirPath.'ext_emconf.php',$emConfFile);

										$content.='ext_emconf.php: '.$extDirPath.'ext_emconf.php<br />';
										$content.='Type: '.$loc.'<br />';

											// Remove cache files:
										if (t3lib_extMgm::isLoaded($extKey))	{
											if ($this->removeCacheFiles())	{
												$content.='Cache-files are removed and will be re-written upon next hit<br />';
											}

											list($new_list)=$this->getInstalledExtensions();
											$content.=$this->updatesForm($extKey,$new_list[$extKey],1,'index.php?CMD[showExt]='.$extKey.'&SET[singleDetails]=info');
										}

											// Show any messages:
										if (is_array($fetchData[0]['_MESSAGES']))	{
											$content.='<hr /><strong>Messages from repository:</strong><br /><br />'.implode('<br />',$fetchData[0]['_MESSAGES']);
										}

											// Install / Uninstall:
										$content.='<h3>Install / Uninstall Extension:</h3>';
										$content.=
											$new_list[$extKey] ?
											'<a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[remove]=1&CMD[clrCmd]=1&SET[singleDetails]=info').'">'.$this->removeButton().' Uninstall extension</a>' :
											'<a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[load]=1&CMD[clrCmd]=1&SET[singleDetails]=info').'">'.$this->installButton().' Install extension</a>';

									}
								} else $content = $res;
							} else $content = 'Error: The extension path "'.$extDirPath.'" was different than expected...';
						} else $content = $res;
					} else $content = 'Error: The extension can only be installed in the path '.$this->typePaths[$EM_CONF['lockType']].' (lockType='.$EM_CONF['lockType'].')';
				} else $content = 'Error: No extension key!!! Why? - nobody knows... (Or no files in the file-array...)';
			}  else $content = 'Error: The datatransfer did not succeed...';
		}  else $content = 'Error: Installation is not allowed in this path ('.$this->typePaths[$loc].')';

		$this->content.=$this->doc->section('Extension copied to server',$content,0,1);
	}

	/**
	 * Display extensions details.
	 *
	 * @param	string		Extension key
	 * @return	void		Writes content to $this->content
	 */
	function showExtDetails($extKey)	{
		global $TYPO3_LOADED_EXT;

		list($list,$cat)=$this->getInstalledExtensions();
		$absPath = $this->getExtPath($extKey,$list[$extKey]['type']);

			// Check updateModule:
		if (@is_file($absPath.'class.ext_update.php'))	{
			require_once($absPath.'class.ext_update.php');
			$updateObj = new ext_update;
			if (!$updateObj->access())	{
				unset($this->MOD_MENU['singleDetails']['updateModule']);
			}
		} else {
			unset($this->MOD_MENU['singleDetails']['updateModule']);
		}

			// Function menu here:
		$content = '
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td nowrap="nowrap">Extension:&nbsp;<strong>'.$this->extensionTitleIconHeader($extKey,$list[$extKey]).'</strong> ('.$extKey.')</td>
					<td align="right" nowrap="nowrap">'.
						t3lib_BEfunc::getFuncMenu(0,'SET[singleDetails]',$this->MOD_SETTINGS['singleDetails'],$this->MOD_MENU['singleDetails'],'','&CMD[showExt]='.$extKey).' &nbsp; &nbsp; '.
						'<a href="index.php" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/goback.gif','width="14" height="14"').' class="absmiddle" alt="" /> Go back</a></td>
				</tr>
			</table>';
		$this->content.=$this->doc->section('',$content);

			// Show extension details:
		if ($list[$extKey])	{

				// Checking if a command for install/uninstall is executed:
			if (($this->CMD['remove'] || $this->CMD['load']) && !in_array($extKey,$this->requiredExt))	{

					// Install / Uninstall extension here:
				if (t3lib_extMgm::isLocalconfWritable())	{
					if ($this->CMD['remove'])	{
						$newExtList = $this->removeExtFromList($extKey,$list);
					} else {
						$newExtList = $this->addExtToList($extKey,$list);
					}

						// Success-installation:
					if ($newExtList!=-1)	{
						$updates = '';
						if ($this->CMD['load'])	{
							$updates = $this->updatesForm($extKey,$list[$extKey],1,'','<input type="hidden" name="_do_install" value="1" /><input type="hidden" name="_clrCmd" value="'.$this->CMD['clrCmd'].'" />');
							if ($updates)	{
								$updates = 'Before the extension can be installed the database needs to be updated with new tables or fields. Please select which operations to perform:'.$updates;
								$this->content.=$this->doc->section('Installing '.$this->extensionTitleIconHeader($extKey,$list[$extKey]).strtoupper(': Database needs to be updated'),$updates,1,1,1,1);
							}
#							$updates.=$this->checkDBupdates($extKey,$list[$extKey]);
#							$updates.= $this->checkClearCache($extKey,$list[$extKey]);
#							$updates.= $this->checkUploadFolder($extKey,$list[$extKey]);
/*							if ($updates)	{
								$updates='
								Before the extension can be installed the database needs to be updated with new tables or fields. Please select which operations to perform:
								</form><form action="'.t3lib_div::linkThisScript().'" method="post">'.$updates.'
								<br /><input type="submit" name="write" value="Update database and install extension" />
								<input type="hidden" name="_do_install" value="1" />
								';
								$this->content.=$this->doc->section('Installing '.$this->extensionTitleIconHeader($extKey,$list[$extKey]).strtoupper(': Database needs to be updated'),$updates,1,1,1);
							}
	*/					} elseif ($this->CMD['remove']) {
							$updates.= $this->checkClearCache($extKey,$list[$extKey]);
							if ($updates)	{
								$updates = '
								</form><form action="'.t3lib_div::linkThisScript().'" method="post">'.$updates.'
								<br /><input type="submit" name="write" value="Remove extension" />
								<input type="hidden" name="_do_install" value="1" />
								<input type="hidden" name="_clrCmd" value="'.$this->CMD['clrCmd'].'" />
								';
								$this->content.=$this->doc->section('Installing '.$this->extensionTitleIconHeader($extKey,$list[$extKey]).strtoupper(': Database needs to be updated'),$updates,1,1,1,1);
							}
						}
						if (!$updates || t3lib_div::_GP('_do_install')) {
							$this->writeNewExtensionList($newExtList);


							/*
							$content = $newExtList;
							$this->content.=$this->doc->section('Active status',"
							<strong>Extension list is written to localconf.php!</strong><br />
							It may be necessary to reload TYPO3 depending on the change.<br />

							<em>(".$content.")</em>",0,1);
							*/
							if ($this->CMD['clrCmd'] || t3lib_div::_GP('_clrCmd'))	{
								$vA = array('CMD'=>'');
							} else {
								$vA = array('CMD'=>Array('showExt'=>$extKey));
							}
							header('Location: '.t3lib_div::linkThisScript($vA));
						}
					}
				} else {
					$this->content.=$this->doc->section('Installing '.$this->extensionTitleIconHeader($extKey,$list[$extKey]).strtoupper(': Write access error'),'typo3conf/localconf.php seems not to be writable, so the extension cannot be installed automatically!',1,1,2,1);
				}

			} elseif ($this->CMD['downloadFile'] && !in_array($extKey,$this->requiredExt))	{

					// Link for downloading extension has been clicked - deliver content stream:
				$dlFile = $this->CMD['downloadFile'];
				if (t3lib_div::isFirstPartOfStr($dlFile,PATH_site) && t3lib_div::isFirstPartOfStr($dlFile,$absPath) && @is_file($dlFile))	{
					$mimeType = 'application/octet-stream';
					Header('Content-Type: '.$mimeType);
					Header('Content-Disposition: attachment; filename='.basename($dlFile));
					echo t3lib_div::getUrl($dlFile);
					exit;
				} else die('error....');

			} elseif ($this->CMD['editFile'] && !in_array($extKey,$this->requiredExt))	{

					// Editing extension file:
				$editFile = $this->CMD['editFile'];
				if (t3lib_div::isFirstPartOfStr($editFile,PATH_site) && t3lib_div::isFirstPartOfStr($editFile,$absPath))	{	// Paranoia...

					$fI = t3lib_div::split_fileref($editFile);
					if (@is_file($editFile) && t3lib_div::inList($this->editTextExtensions,$fI['fileext']))	{
						if (filesize($editFile)<($this->kbMax*1024))	{
							$outCode = '';
							$info = '';
							$submittedContent = t3lib_div::_POST('edit');
							$saveFlag = 0;

							if(isset($submittedContent['file']))	{		// Check referer here?
								$info.= $GLOBALS['TBE_TEMPLATE']->rfw('<br /><strong>File saved.</strong>').'<br />';
								$oldFileContent = t3lib_div::getUrl($editFile);
								$info.= 'MD5: <b>'.md5(str_replace(chr(13),'',$oldFileContent)).'</b> (Previous File)<br />';
								if (!$GLOBALS['TYPO3_CONF_VARS']['EXT']['noEdit'])	{
									t3lib_div::writeFile($editFile,$submittedContent['file']);
									$saveFlag = 1;
								} else die('Saving disabled!!!');
							}

							$fileContent = t3lib_div::getUrl($editFile);
							$numberOfRows = 35;

							$outCode.= 'File: <b>'.substr($editFile,strlen($absPath)).'</b> ('.t3lib_div::formatSize(filesize($editFile)).')<br />';
							$info.= 'MD5: <b>'.md5(str_replace(chr(13),'',$fileContent)).'</b> (File)<br />';
							if($saveFlag)	$info.= 'MD5: <b>'.md5(str_replace(chr(13),'',$submittedContent['file'])).'</b> (Saved)<br />';
							$outCode.= '<textarea name="edit[file]" rows="'.$numberOfRows.'" wrap="off"'.$this->doc->formWidthText(48,'width:98%;height:70%','off').'>'.t3lib_div::formatForTextarea($fileContent).'</textarea>';
							$outCode.= '<input type="hidden" name="edit[filename]" value="'.$editFile.'" />';
							$outCode.= '<input type="hidden" name="CMD[editFile]" value="'.htmlspecialchars($editFile).'" />';
							$outCode.= '<input type="hidden" name="CMD[showExt]" value="'.$extKey.'" />';
							$outCode.= $info;

							if (!$GLOBALS['TYPO3_CONF_VARS']['EXT']['noEdit'])	{
								$outCode.='<br /><input type="submit" name="save_file" value="Save file" />';
							} else $outCode.=$GLOBALS['TBE_TEMPLATE']->rfw('<br />[SAVING IS DISABLED - can be enabled by the TYPO3_CONF_VARS[EXT][noEdit]-flag] ');

							$onClick = 'document.location=\'index.php?CMD[showExt]='.$extKey.'\';return false;';
							$outCode.='<input type="submit" name="cancel" value="Cancel" onclick="'.htmlspecialchars($onClick).'" />';

							$theOutput.=$this->doc->spacer(15);
							$theOutput.=$this->doc->section('Edit file:','',0,1);
							$theOutput.=$this->doc->sectionEnd().$outCode;
							$this->content.=$theOutput;
						} else {
							$theOutput.=$this->doc->spacer(15);
							$theOutput.=$this->doc->section('Filesize exceeded '.$this->kbMax.' Kbytes','Files larger than '.$this->kbMax.' KBytes are not allowed to be edited.');
						}
					}
				} else die('Fatal Edit error: File "'.$editFile.'" was not inside the correct path of the TYPO3 Extension!');
			} else {

					// MAIN:
				switch((string)$this->MOD_SETTINGS['singleDetails'])	{
					case 'info':
							// Loaded / Not loaded:
						if (!in_array($extKey,$this->requiredExt))	{
							if ($TYPO3_LOADED_EXT[$extKey])	{
								$content = '<strong>The extension is installed (loaded and running)!</strong><br />'.
											'<a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[remove]=1').'">Click here to remove the extension: '.$this->removeButton().'</a>';
							} else {
								$content = 'The extension is <strong>not</strong> installed yet.<br />'.
											'<a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[load]=1').'">Click here to install the extension: '.$this->installButton().'</a>';
							}
						} else {
							$content = 'This extension is entered in the TYPO3_CONF_VARS[SYS][requiredExt] list and is therefore always loaded.';
						}
						$this->content.=$this->doc->spacer(10);
						$this->content.=$this->doc->section('Active status:',$content,0,1);

						if (t3lib_extMgm::isLoaded($extKey))	{
							$updates=$this->updatesForm($extKey,$list[$extKey]);
							if ($updates)	{
								$this->content.=$this->doc->spacer(10);
								$this->content.=$this->doc->section('Update needed:',$updates.'<br /><br />Notice: "Static data" may not <em>need</em> to be updated. You will only have to import static data each time you upgrade the extension.',0,1);
							}
						}

								// Config:
						if (@is_file($absPath.'ext_conf_template.txt'))	{
							$this->content.=$this->doc->spacer(10);
							$this->content.=$this->doc->section('Configuration:','(<em>Notice: You may need to clear the cache after configuration of the extension. This is required if the extension adds TypoScript depending on these settings.</em>)<br /><br />',0,1);
							$this->tsStyleConfigForm($extKey,$list[$extKey]);
						}

							// Show details:
						$content = $this->extInformationArray($extKey,$list[$extKey]);
						$this->content.=$this->doc->spacer(10);
						$this->content.=$this->doc->section('Details:',$content,0,1);
					break;
					case 'upload':
						$TER_CMD = t3lib_div::_GP('TER_CMD');
						if (is_array($TER_CMD))	{
							$msg = $this->processRepositoryReturnData($TER_CMD);
							if ($msg)	{
								$this->content.=$this->doc->section('Local update of EM_CONF',$msg,0,1,1);
								$this->content.=$this->doc->spacer(10);
							}
								// Must reload this, because EM_CONF information has been updated!
							list($list,$cat)=$this->getInstalledExtensions();
						} else {
								// Upload:
							if (substr($extKey,0,5)!='user_')	{
								$content = $this->getRepositoryUploadForm($extKey,$list[$extKey]);
								$eC=0;
							} else {
								$content='The extensions has an extension key prefixed "user_" which indicates that it is a user-defined extension with no official unique identification. Therefore it cannot be uploaded.<br />
								You are encouraged to register a unique extension key for all your TYPO3 extensions - even if the project is current not official.';
								$eC=2;
							}
							$this->content.=$this->doc->section('Upload extension to repository',$content,0,1,$eC);
						}
					break;
					case 'download':
					break;
					case 'backup':
						$content = $this->extBackup($extKey,$list[$extKey]);
						$this->content.=$this->doc->section('Backup',$content,0,1);

						$content = $this->extDelete($extKey,$list[$extKey]);
						$this->content.=$this->doc->section('Delete',$content,0,1);

						$content = $this->extUpdateEMCONF($extKey,$list[$extKey]);
						$this->content.=$this->doc->section('Update EM_CONF',$content,0,1);

						$content = $this->extMakeNewFromFramework($extKey,$list[$extKey]);
						if ($content)	$this->content.=$this->doc->section('Make new extension',$content,0,1);
					break;
					case 'dump':
						$this->extDumpTables($extKey,$list[$extKey]);
					break;
					case 'edit':
							// Files:
						$content = $this->getFileListOfExtension($extKey,$list[$extKey]);
						$this->content.=$this->doc->section('Extension files',$content,0,1);
					break;
					case 'updateModule':
						$this->content.=$this->doc->section('Update:',$updateObj->main(),0,1);
					break;
				}
			}
		}
	}










	/***********************************
	 *
	 * Application Sub-functions (HTML parts)
	 *
	 **********************************/

	/**
	 * Creates a form for an extension which contains all options for configuration, updates of database, clearing of cache etc.
	 * This form is shown when
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @param	boolean		If set, the form will ONLY show if fields/tables should be updated (suppressing forms like general configuration and cache clearing).
	 * @param	string		Alternative action=""-script
	 * @param	string		HTML: Additional form fields
	 * @return	string		HTML
	 */
	function updatesForm($extKey,$extInfo,$notSilent=0,$script='',$addFields='')	{
		$script = $script ? $script : t3lib_div::linkThisScript();
		$updates.= $this->checkDBupdates($extKey,$extInfo);
		$uCache = $this->checkClearCache($extKey,$extInfo);
		if ($notSilent)	$updates.= $uCache;
		$updates.= $this->checkUploadFolder($extKey,$extInfo);

		$absPath = $this->getExtPath($extKey,$extInfo['type']);
		if ($notSilent && @is_file($absPath.'ext_conf_template.txt'))	{
			$cForm = $this->tsStyleConfigForm($extKey,$extInfo,1,$script,$updates.$addFields.'<br />');
		}

		if ($updates || $cForm)	{
			if ($cForm)	{
				$updates = '</form>'.$cForm.'<form>';
			} else {
				$updates = '</form><form action="'.htmlspecialchars($script).'" method="post">'.$updates.$addFields.'
					<br /><input type="submit" name="write" value="Make updates" />
				';
			}
		}
		return $updates;
	}

	/**
	 * Creates view for dumping static tables and table/fields structures...
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	void
	 */
	function extDumpTables($extKey,$extInfo)	{

			// Get dbInfo which holds the structure known from the tables.sql file
		$techInfo = $this->makeDetailedExtensionAnalysis($extKey,$extInfo);
		$absPath = $this->getExtPath($extKey,$extInfo['type']);

			// Static tables:
		if (is_array($techInfo['static']))	{
			if ($this->CMD['writeSTATICdump'])	{	// Writing static dump:
				$writeFile = $absPath.'ext_tables_static+adt.sql';
				if (@is_file($writeFile))	{
					$dump_static = $this->dumpStaticTables(implode(',',$techInfo['static']));
					t3lib_div::writeFile($writeFile,$dump_static);
					$this->content.=$this->doc->section('Table and field structure required',t3lib_div::formatSize(strlen($dump_static)).'bytes written to '.substr($writeFile,strlen(PATH_site)),0,1);
				}
			} else {	// Showing info about what tables to dump - and giving the link to execute it.
				$msg = 'Dumping table content for static tables:<br />';
				$msg.= '<br />'.implode('<br />',$techInfo['static']).'<br />';

					// ... then feed that to this function which will make new CREATE statements of the same fields but based on the current database content.
				$this->content.=$this->doc->section('Static tables',$msg.'<hr /><strong><a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[writeSTATICdump]=1').'">Write current static table contents to ext_tables_static+adt.sql now!</a></strong>',0,1);
				$this->content.=$this->doc->spacer(20);
			}
		}

			// Table and field definitions:
		if (is_array($techInfo['dump_tf']))	{
			$dump_tf_array = $this->getTableAndFieldStructure($techInfo['dump_tf']);
			$dump_tf = $this->dumpTableAndFieldStructure($dump_tf_array);
			if ($this->CMD['writeTFdump'])	{
				$writeFile = $absPath.'ext_tables.sql';
				if (@is_file($writeFile))	{
					t3lib_div::writeFile($writeFile,$dump_tf);
					$this->content.=$this->doc->section('Table and field structure required',t3lib_div::formatSize(strlen($dump_tf)).'bytes written to '.substr($writeFile,strlen(PATH_site)),0,1);
				}
			} else {
				$msg = 'Dumping current database structure for:<br />';
				if (is_array($techInfo['tables']))	{
					$msg.= '<br /><strong>Tables:</strong><br />'.implode('<br />',$techInfo['tables']).'<br />';
				}
				if (is_array($techInfo['fields']))	{
					$msg.= '<br /><strong>Solo-fields:</strong><br />'.implode('<br />',$techInfo['fields']).'<br />';
				}

					// ... then feed that to this function which will make new CREATE statements of the same fields but based on the current database content.
				$this->content.=$this->doc->section('Table and field structure required',$msg.'<hr /><strong><a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[writeTFdump]=1').'">Write this dump to ext_tables.sql now!</a></strong><hr />
				<pre>'.htmlspecialchars($dump_tf).'</pre>',0,1);


				$details = '							This dump is based on two factors:<br />
				<ul>
				<li>1) All tablenames in ext_tables.sql which are <em>not</em> found in the "modify_tables" list in ext_emconf.php are dumped with the current database structure.</li>
				<li>2) For any tablenames which <em>are</em> listed in "modify_tables" all fields and keys found for the table in ext_tables.sql will be re-dumped with the fresh equalents from the database.</li>
				</ul>
				Bottomline is: Whole tables are dumped from database with no regard to which fields and keys are defined in ext_tables.sql. But for tables which are only modified, any NEW fields added to the database must in some form or the other exist in the ext_tables.sql file as well.<br />';
				$this->content.=$this->doc->section('',$details);
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
	function getFileListOfExtension($extKey,$conf)	{
		$extPath = $this->getExtPath($extKey,$conf['type']);

		if ($extPath)	{
				// Read files:
			$fileArr = array();
			$fileArr = t3lib_div::getAllFilesAndFoldersInPath($fileArr,$extPath);

				// Start table:
			$lines = array();
			$totalSize = 0;

				// Header:
			$lines[] = '
				<tr class="bgColor5">
					<td>File:</td>
					<td>Size:</td>
					<td>Edit:</td>
				</tr>';

			foreach($fileArr as $file)	{
				$fI = t3lib_div::split_fileref($file);
				$lines[] = '
				<tr class="bgColor4">
					<td><a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[downloadFile]='.rawurlencode($file)).'" title="Download...">'.substr($file,strlen($extPath)).'</a></td>
					<td>'.t3lib_div::formatSize(filesize($file)).'</td>
					<td>'.(!in_array($extKey,$this->requiredExt)&&t3lib_div::inList($this->editTextExtensions,$fI['fileext'])?'<a href="'.htmlspecialchars('index.php?CMD[showExt]='.$extKey.'&CMD[editFile]='.rawurlencode($file)).'">Edit file</a>':'').'</td>
				</tr>';
				$totalSize+=filesize($file);
			}

			$lines[] = '
				<tr class="bgColor6">
					<td><strong>Total:</strong></td>
					<td><strong>'.t3lib_div::formatSize($totalSize).'</strong></td>
					<td>&nbsp;</td>
				</tr>';

			return '
			Path: '.$extPath.'<br /><br />
			<table border="0" cellpadding="1" cellspacing="2">'.implode('',$lines).'</table>';
		}
	}

	/**
	 * Delete extension from the file system
	 *
	 * @param	string		Extension key
	 * @param	array		Extension info array
	 * @return	string		Returns message string about the status of the operation
	 */
	function extDelete($extKey,$extInfo)	{
		$absPath = $this->getExtPath($extKey,$extInfo['type']);
		if (t3lib_extMgm::isLoaded($extKey))	{
			return 'This extension is currently installed (loaded and active) and so cannot be deleted!';
		} elseif (!$this->deleteAsType($extInfo['type'])) {
			return 'You cannot delete (and install/update) extensions in the '.$this->typeLabels[$extInfo['type']].' scope.';
		} elseif (t3lib_div::inList('G,L',$extInfo['type'])) {
			if ($this->CMD['doDelete'] && !strcmp($absPath,$this->CMD['absPath'])) {
				$res = $this->removeExtDirectory($absPath);
				if ($res) {
					return 'ERROR: Could not remove extension directory "'.$absPath.'". Had the following errors:<br /><br />'.
								nl2br($res);
				} else {
					rmdir($absPath);
					return 'Removed extension in path "'.$absPath.'"!';
				}
			} else {
				$onClick = "if (confirm('Are you sure you want to delete this extension from the server?')) {document.location='index.php?CMD[showExt]=".$extKey.'&CMD[doDelete]=1&CMD[absPath]='.rawurlencode($absPath)."';}";
				$content.= '<a href="#" onclick="'.htmlspecialchars($onClick).' return false;"><strong>DELETE EXTENSION FROM SERVER</strong> (in the "'.$this->typeLabels[$extInfo['type']].'" location "'.substr($absPath,strlen(PATH_site)).'")!</a>';
				$content.= '<br /><br />(Maybe you should make a backup first, see above.)';
				return $content;
			}
		} else return 'Extension is not a global or local extension and cannot be removed.';
	}

	/**
	 * Update extension EM_CONF...
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML content.
	 */
	function extUpdateEMCONF($extKey,$extInfo)	{
		$absPath = $this->getExtPath($extKey,$extInfo['type']);
		if ($this->CMD['doUpdateEMCONF']) {
			return $this->updateLocalEM_CONF($extKey,$extInfo);
		} else {
			$onClick = "if (confirm('Are you sure you want to update EM_CONF?')) {document.location='index.php?CMD[showExt]=".$extKey."&CMD[doUpdateEMCONF]=1';}";
			$content.= '<a href="#" onclick="'.htmlspecialchars($onClick).' return false;"><strong>Update extension EM_CONF file</strong> (in the "'.$this->typeLabels[$extInfo['type']].'" location "'.substr($absPath,strlen(PATH_site)).'")!</a>';
			$content.= '<br /><br />If files are changed, added or removed to an extension this is normally detected and displayed so you know that this extension has been locally altered and may need to be uploaded or at least not overridden.<br />
						Updating this file will first of all reset this registration.';
			return $content;
		}
	}

	/**
	 * Reload in Kickstarter Wizard
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML content
	 */
	function extMakeNewFromFramework($extKey,$extInfo)	{
		$absPath = $this->getExtPath($extKey,$extInfo['type']);
		if (isset($this->MOD_MENU['function'][4]) && @is_file($absPath.'doc/wizard_form.dat'))	{
			$content = "The file '".substr($absPath."doc/wizard_form.dat",strlen(PATH_site))."' contains the data which this extension was originally made from with the 'Kickstarter' wizard.<br />Pressing this button will allow you to create another extension based on the that framework.<br /><br />";
			$content.= '</form>
				<form action="index.php?SET[function]=4" method="post">
					<input type="submit" value="Start new" />
					<input type="hidden" name="tx_extrep[wizArray_ser]" value="'.base64_encode(t3lib_div::getUrl($absPath.'doc/wizard_form.dat')).'" />
				</form>
			<form>';
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
	function extBackup($extKey,$extInfo)	{
		$uArr = $this->makeUploadArray($extKey,$extInfo);
		if (is_array($uArr))	{
			$local_gzcompress = $this->gzcompress && !$this->CMD['dontCompress'];
			$backUpData = $this->makeUploadDataFromArray($uArr,intval($local_gzcompress));
			$filename = 'T3X_'.$extKey.'-'.str_replace('.','_',$extInfo['EM_CONF']['version']).($local_gzcompress?'-z':'').'-'.date('YmdHi').'.t3x';
			if (intval($this->CMD['doBackup'])==1)	{

				$mimeType = 'application/octet-stream';
				Header('Content-Type: '.$mimeType);
				Header('Content-Disposition: attachment; filename='.$filename);

					// New headers suggested by Xin:
					// For now they are commented out because a) I have seen no official support yet, b) when clicking the back-link in MSIE after download you see ugly binary stuff and c) I couldn't see a BIG difference, in particular not in Moz/Opera.
/*				header('Content-Type: application/force-download');
				header('Content-Length: '.strlen($backUpData));

				header('Content-Disposition: attachment; filename='.$filename);
				header('Content-Description: File Transfer');
				header('Content-Transfer-Encoding: binary');
*/

				// ANYWAYS! The download is NOT always working - in some cases extensions will never get the same MD5 sum as the one shown at the download link - and they should in order to work! We do NOT know why yet.

				echo $backUpData;
				exit;
			} elseif ($this->CMD['dumpTables'])	{
				$filename='T3X_'.$extKey;
				$cTables = count(explode(',',$this->CMD['dumpTables']));
				if ($cTables>1)	{
					$filename.='-'.$cTables.'tables';
				} else {
					$filename.='-'.$this->CMD['dumpTables'];
				}
				$filename.='+adt.sql';

				$mimeType = 'application/octet-stream';
				Header('Content-Type: '.$mimeType);
				Header('Content-Disposition: attachment; filename='.$filename);
				echo $this->dumpStaticTables($this->CMD['dumpTables']);
				exit;
			} else {
				$techInfo = $this->makeDetailedExtensionAnalysis($extKey,$extInfo);
//								if ($techInfo['tables']||$techInfo['static']||$techInfo['fields'])	{
#debug($techInfo);
				$lines=array();
				$lines[]='<tr class="bgColor5"><td colspan="2"><strong>Make selection:</strong></td></tr>';
				$lines[]='<tr class="bgColor4"><td><strong>Extension files:</strong></td><td>'.
					'<a href="'.htmlspecialchars('index.php?CMD[doBackup]=1&CMD[showExt]='.$extKey).'">Download extension "'.$extKey.'" as a file</a><br />('.$filename.', '.t3lib_div::formatSize(strlen($backUpData)).', MD5: '.md5($backUpData).')<br />'.
					($this->gzcompress ? '<br /><a href="'.htmlspecialchars('index.php?CMD[doBackup]=1&CMD[dontCompress]=1&CMD[showExt]='.$extKey).'">(Click here to download extension without compression.)</a>':'').
					'</td></tr>';

				if (is_array($techInfo['tables']))	{	$lines[]='<tr class="bgColor4"><td><strong>Data tables:</strong></td><td>'.$this->extBackup_dumpDataTablesLine($techInfo['tables'],$extKey).'</td></tr>';	}
				if (is_array($techInfo['static']))	{	$lines[]='<tr class="bgColor4"><td><strong>Static tables:</strong></td><td>'.$this->extBackup_dumpDataTablesLine($techInfo['static'],$extKey).'</td></tr>';	}

				$content = '<table border="0" cellpadding="2" cellspacing="2">'.implode('',$lines).'</table>';
				return $content;
			}
		} else die('Error...');
	}

	/**
	 * Link to dump of database tables
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML
	 */
	function extBackup_dumpDataTablesLine($tablesArray,$extKey)	{
		$tables = array();
		$tablesNA = array();

		foreach($tablesArray as $tN)	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', $tN, '');
			if (!$GLOBALS['TYPO3_DB']->sql_error())	{
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$tables[$tN]='<tr><td>&nbsp;</td><td><a href="'.htmlspecialchars('index.php?CMD[dumpTables]='.rawurlencode($tN).'&CMD[showExt]='.$extKey).'" title="Dump table \''.$tN.'\'">'.$tN.'</a></td><td>&nbsp;&nbsp;&nbsp;</td><td>'.$row[0].' records</td></tr>';
			} else {
				$tablesNA[$tN]='<tr><td>&nbsp;</td><td>'.$tN.'</td><td>&nbsp;</td><td>Did not exist.</td></tr>';
			}
		}
		$label = '<table border="0" cellpadding="0" cellspacing="0">'.implode('',array_merge($tables,$tablesNA)).'</table>';// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
		if (count($tables))	{
			$label = '<a href="'.htmlspecialchars('index.php?CMD[dumpTables]='.rawurlencode(implode(',',array_keys($tables))).'&CMD[showExt]='.$extKey).'" title="Dump all existing tables.">Download all data from:</a><br /><br />'.$label;
		} else $label = 'Nothing to dump...<br /><br />'.$label;
		return $label;
	}

	/**
	 * Prints a table with extension information in it.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @param	boolean		If set, the information array shows information for a remote extension in TER, not a local one.
	 * @return	string		HTML content.
	 */
	function extInformationArray($extKey,$extInfo,$remote=0)	{
		$lines=array();
		$lines[]='<tr class="bgColor5"><td colspan="2"><strong>General information:</strong></td>'.$this->helpCol('').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Title:</td><td>'.$extInfo['EM_CONF']['_icon'].$extInfo['EM_CONF']['title'].'</td>'.$this->helpCol('title').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Description:</td><td>'.nl2br(htmlspecialchars($extInfo['EM_CONF']['description'])).'</td>'.$this->helpCol('description').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Author:</td><td>'.$this->wrapEmail($extInfo['EM_CONF']['author'].($extInfo['EM_CONF']['author_email'] ? ' <'.$extInfo['EM_CONF']['author_email'].'>' : ''),$extInfo['EM_CONF']['author_email']).
			($extInfo['EM_CONF']['author_company']?', '.$extInfo['EM_CONF']['author_company']:'').
			'</td>'.$this->helpCol('description').'</tr>';

		$lines[]='<tr class="bgColor4"><td>Version:</td><td>'.$extInfo['EM_CONF']['version'].'</td>'.$this->helpCol('version').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Category:</td><td>'.$this->categories[$extInfo['EM_CONF']['category']].'</td>'.$this->helpCol('category').'</tr>';
		$lines[]='<tr class="bgColor4"><td>State:</td><td>'.$this->states[$extInfo['EM_CONF']['state']].'</td>'.$this->helpCol('state').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Shy?</td><td>'.($extInfo['EM_CONF']['shy']?'Yes':'').'</td>'.$this->helpCol('shy').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Internal?</td><td>'.($extInfo['EM_CONF']['internal']?'Yes':'').'</td>'.$this->helpCol('internal').'</tr>';

		$lines[]='<tr class="bgColor4"><td>Dependencies:</td><td>'.$extInfo['EM_CONF']['dependencies'].'</td>'.$this->helpCol('dependencies').'</tr>';
		if (!$remote)	{
			$lines[]='<tr class="bgColor4"><td>Conflicts:</td><td>'.$extInfo['EM_CONF']['conflicts'].'</td>'.$this->helpCol('conflicts').'</tr>';
			$lines[]='<tr class="bgColor4"><td>Priority:</td><td>'.$extInfo['EM_CONF']['priority'].'</td>'.$this->helpCol('priority').'</tr>';
			$lines[]='<tr class="bgColor4"><td>Clear cache?</td><td>'.($extInfo['EM_CONF']['clearCacheOnLoad']?'Yes':'').'</td>'.$this->helpCol('clearCacheOnLoad').'</tr>';
			$lines[]='<tr class="bgColor4"><td>Includes modules:</td><td>'.$extInfo['EM_CONF']['module'].'</td>'.$this->helpCol('module').'</tr>';
		}
		$lines[]='<tr class="bgColor4"><td>Lock Type?</td><td>'.($extInfo['EM_CONF']['lockType']?$extInfo['EM_CONF']['lockType']:'').'</td>'.$this->helpCol('lockType').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Modifies tables:</td><td>'.$extInfo['EM_CONF']['modify_tables'].'</td>'.$this->helpCol('modify_tables').'</tr>';

		$lines[]='<tr class="bgColor4"><td>Private?</td><td>'.($extInfo['EM_CONF']['private']?'Yes':'').'</td>'.$this->helpCol('private').'</tr>';
		if (!$remote)	$lines[]='<tr class="bgColor4"><td>Download password:</td><td>'.$extInfo['EM_CONF']['download_password'].'</td>'.$this->helpCol('download_password').'</tr>';

			// Installation status:
		$lines[]='<tr><td>&nbsp;</td><td></td>'.$this->helpCol('').'</tr>';
		$lines[]='<tr class="bgColor5"><td colspan="2"><strong>Installation status:</strong></td>'.$this->helpCol('').'</tr>';
		if (!$remote)	{
			$lines[]='<tr class="bgColor4"><td>Type of install:</td><td>'.$this->typeLabels[$extInfo['type']].' - <em>'.$this->typeDescr[$extInfo['type']].'</em></td>'.$this->helpCol('type').'</tr>';
			$lines[]='<tr class="bgColor4"><td>Double installs?</td><td>'.$this->extInformationArray_dbInst($extInfo['doubleInstall'],$extInfo['type']).'</td>'.$this->helpCol('doubleInstall').'</tr>';
		}
		if (is_array($extInfo['files']))	{
			sort($extInfo['files']);
			$lines[]='<tr class="bgColor4"><td>Root files:</td><td>'.implode('<br />',$extInfo['files']).'</td>'.$this->helpCol('rootfiles').'</tr>';
		}

		if (!$remote)	{
			$techInfo = $this->makeDetailedExtensionAnalysis($extKey,$extInfo,1);
		} else $techInfo = $extInfo['_TECH_INFO'];
#debug($techInfo);

		if ($techInfo['tables']||$techInfo['static']||$techInfo['fields'])	{
			if (!$remote && t3lib_extMgm::isLoaded($extKey))	{
				$tableStatus = $GLOBALS['TBE_TEMPLATE']->rfw(($techInfo['tables_error']?'<strong>Table error!</strong><br />Probably one or more required fields/tables are missing in the database!':'').
					($techInfo['static_error']?'<strong>Static table error!</strong><br />The static tables are missing or empty!':''));
			} else {
				$tableStatus = $techInfo['tables_error']||$techInfo['static_error'] ? 'The database will need to be updated when this extension is installed.' : 'All required tables are already in the database!';
			}
		}

		$lines[]='<tr class="bgColor4"><td>Database requirements:</td><td>'.$this->extInformationArray_dbReq($techInfo,1).'</td>'.$this->helpCol('dbReq').'</tr>';
		if (!$remote)	$lines[]='<tr class="bgColor4"><td>Database status:</td><td>'.$tableStatus.'</td>'.$this->helpCol('dbStatus').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Flags:</td><td>'.(is_array($techInfo['flags'])?implode('<br />',$techInfo['flags']):'').'</td>'.$this->helpCol('flags').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Config template?</td><td>'.($techInfo['conf']?'Yes':'').'</td>'.$this->helpCol('conf').'</tr>';
		$lines[]='<tr class="bgColor4"><td>TypoScript files:</td><td>'.(is_array($techInfo['TSfiles'])?implode('<br />',$techInfo['TSfiles']):'').'</td>'.$this->helpCol('TSfiles').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Language files:</td><td>'.(is_array($techInfo['locallang'])?implode('<br />',$techInfo['locallang']):'').'</td>'.$this->helpCol('locallang').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Upload folder:</td><td>'.($techInfo['uploadfolder']?$techInfo['uploadfolder']:'').'</td>'.$this->helpCol('uploadfolder').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Create directories:</td><td>'.(is_array($techInfo['createDirs'])?implode('<br />',$techInfo['createDirs']):'').'</td>'.$this->helpCol('createDirs').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Module names:</td><td>'.(is_array($techInfo['moduleNames'])?implode('<br />',$techInfo['moduleNames']):'').'</td>'.$this->helpCol('moduleNames').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Class names:</td><td>'.(is_array($techInfo['classes'])?implode('<br />',$techInfo['classes']):'').'</td>'.$this->helpCol('classNames').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Errors:</td><td>'.(is_array($techInfo['errors'])?$GLOBALS['TBE_TEMPLATE']->rfw(implode('<hr />',$techInfo['errors'])):'').'</td>'.$this->helpCol('errors').'</tr>';
		$lines[]='<tr class="bgColor4"><td>Naming errors:</td><td>'.(is_array($techInfo['NSerrors'])?
				(!t3lib_div::inList($this->nameSpaceExceptions,$extKey)?t3lib_div::view_array($techInfo['NSerrors']):$GLOBALS['TBE_TEMPLATE']->dfw('[exception]'))
				:'').'</td>'.$this->helpCol('NSerrors').'</tr>';


		if (!$remote)	{
			$currentMd5Array = $this->serverExtensionMD5Array($extKey,$extInfo);
			$affectedFiles='';

			$msgLines=array();
#			$msgLines[] = 'Files: '.count($currentMd5Array);
			if (strcmp($extInfo['EM_CONF']['_md5_values_when_last_written'],serialize($currentMd5Array)))	{
				$msgLines[] = $GLOBALS['TBE_TEMPLATE']->rfw('<br /><strong>A difference between the originally installed version and the current was detected!</strong>');
				$affectedFiles = $this->findMD5ArrayDiff($currentMd5Array,unserialize($extInfo['EM_CONF']['_md5_values_when_last_written']));
				if (count($affectedFiles))	$msgLines[] = '<br /><strong>Modified files:</strong><br />'.$GLOBALS['TBE_TEMPLATE']->rfw(implode('<br />',$affectedFiles));
			}
			$lines[]='<tr class="bgColor4"><td>Files changed?</td><td>'.implode('<br />',$msgLines).'</td>'.$this->helpCol('filesChanged').'</tr>';
		}

		return '<table border="0" cellpadding="1" cellspacing="2">
					'.implode('
					',$lines).'
				</table>';
	}

	/**
	 * Returns HTML with information about database requirements
	 *
	 * @param	array		Technical information array
	 * @param	boolean		Table header displayed
	 * @return	string		HTML content.
	 */
	function extInformationArray_dbReq($techInfo,$tableHeader=0)	{
		return nl2br(trim((is_array($techInfo['tables'])?($tableHeader?"\n\n<strong>Tables:</strong>\n":'').implode(chr(10),$techInfo['tables']):'').
				(is_array($techInfo['static'])?"\n\n<strong>Static tables:</strong>\n".implode(chr(10),$techInfo['static']):'').
				(is_array($techInfo['fields'])?"\n\n<strong>Additional fields:</strong>\n".implode('<hr />',$techInfo['fields']):'')));
	}

	/**
	 * Double install warning.
	 *
	 * @param	string		Double-install string, eg. "LG" etc.
	 * @param	string		Current scope, eg. "L" or "G" or "S"
	 * @return	string		Message
	 */
	function extInformationArray_dbInst($dbInst,$current)	{
		if (strlen($dbInst)>1)	{
			$others = array();
			for($a=0;$a<strlen($dbInst);$a++)	{
				if (substr($dbInst,$a,1)!=$current)	{
					$others[]='"'.$this->typeLabels[substr($dbInst,$a,1)].'"';
				}
			}
			return $GLOBALS['TBE_TEMPLATE']->rfw('A '.implode(' and ',$others).' extension with this key is also available on the server, but cannot be loaded because the "'.$this->typeLabels[$current].'" version takes precedence.');
		} else return '';
	}

	/**
	 * Prints the upload form for extensions
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML content.
	 */
	function getRepositoryUploadForm($extKey,$extInfo)	{
		$uArr = $this->makeUploadArray($extKey,$extInfo);
		if (is_array($uArr))	{
			$backUpData = $this->makeUploadDataFromArray($uArr);

#debug($this->decodeExchangeData($backUpData));
			$content.='Extension "'.$this->extensionTitleIconHeader($extKey,$extInfo).'" is ready to be uploaded.<br />
			The size of the upload is <strong>'.t3lib_div::formatSize(strlen($backUpData)).'</strong><br />
			';

			$b64data = base64_encode($backUpData);
			$content='</form><form action="'.$this->repositoryUrl.'" method="post" enctype="application/x-www-form-urlencoded">
			<input type="hidden" name="tx_extrep[upload][returnUrl]" value="'.htmlspecialchars($this->makeReturnUrl()).'" />
			<input type="hidden" name="tx_extrep[upload][data]" value="'.$b64data.'" />
			<input type="hidden" name="tx_extrep[upload][typo3ver]" value="'.$GLOBALS['TYPO_VERSION'].'" />
			<input type="hidden" name="tx_extrep[upload][os]" value="'.TYPO3_OS.'" />
			<input type="hidden" name="tx_extrep[upload][sapi]" value="'.php_sapi_name().'" />
			<input type="hidden" name="tx_extrep[upload][phpver]" value="'.phpversion().'" />
			<input type="hidden" name="tx_extrep[upload][gzcompressed]" value="'.$this->gzcompress.'" />
			<input type="hidden" name="tx_extrep[upload][data_md5]" value="'.md5($b64data).'" />
			<table border="0" cellpadding="2" cellspacing="1">
				<tr class="bgColor4">
					<td>Repository Username:</td>
					<td><input'.$this->doc->formWidth(20).' type="text" name="tx_extrep[user][fe_u]" value="'.$this->fe_user['username'].'" /></td>
				</tr>
				<tr class="bgColor4">
					<td>Repository Password:</td>
					<td><input'.$this->doc->formWidth(20).' type="password" name="tx_extrep[user][fe_p]" value="'.$this->fe_user['password'].'" /></td>
				</tr>
				<tr class="bgColor4">
					<td>Upload password for this extension:</td>
					<td><input'.$this->doc->formWidth(30).' type="password" name="tx_extrep[upload][upload_p]" value="'.$this->fe_user['uploadPass'].'" /></td>
				</tr>
				<tr class="bgColor4">
					<td>Changelog for upload:</td>
					<td><textarea'.$this->doc->formWidth(30,1).' rows="5" name="tx_extrep[upload][comment]"></textarea></td>
				</tr>
				<tr class="bgColor4">
					<td>Upload command:</td>
					<td nowrap="nowrap">
						<input type="radio" name="tx_extrep[upload][mode]" value="new_dev" checked="checked" /> New development version (latest x.x.<strong>'.$GLOBALS['TBE_TEMPLATE']->rfw('x+1').'</strong>)<br />
						<input type="radio" name="tx_extrep[upload][mode]" value="latest" /> Override <em>this</em> development version ('.$extInfo['EM_CONF']['version'].')<br />
						<input type="radio" name="tx_extrep[upload][mode]" value="new_sub" /> New sub version (latest x.<strong>'.$GLOBALS['TBE_TEMPLATE']->rfw('x+1').'</strong>.0)<br />
						<input type="radio" name="tx_extrep[upload][mode]" value="new_main" /> New main version (latest <strong>'.$GLOBALS['TBE_TEMPLATE']->rfw('x+1').'</strong>.0.0)<br />
					</td>
				</tr>
<!-- Removing "private keys" since they are probably not used much. Better option for people is to distribute "private" extensions as files by emails.
				<tr class="bgColor4">
					<td>Private?</td>
					<td>
						<input type="checkbox" name="tx_extrep[upload][private]" value="1"'.($extInfo['EM_CONF']['private'] ? ' checked="checked"' : '').' />Yes, dont show <em>this upload</em> in the public list.<br />
					("Private" uploads requires you to manually enter a special key (which will be shown to you after the upload has been completed) to be able to import and view details for the upload. This is nice when you are working on something internally which you do not want others to look at.)<br />
					<br /><strong>Additional import password:</strong><br />
					<input'.$this->doc->formWidth(20).' type="text" name="tx_extrep[upload][download_password]" value="'.htmlspecialchars(trim($extInfo['EM_CONF']['download_password'])).'" /> (Textfield!) <br />
					(Anybody who knows the "special key" assigned to the private upload will be able to import it. Specifying an import password allows you to give away the download key for private uploads and also require a password given in addition. The password can be changed later on.)<br />
					</td>
				</tr>
-->
				<tr class="bgColor4">
					<td>&nbsp;</td>
					<td><input type="submit" name="submit" value="Upload extension" /><br />
					'.t3lib_div::formatSize(strlen($b64data)).($this->gzcompress?", compressed":"").', base64<br />
					<br />
					Clicking "Save as file" will allow you to save the extension as a file. This provides you with a backup copy of your extension which can be imported later if needed. "Save as file" ignores the information entered in this form!
					</td>
				</tr>
			</table>
			';

			return $content;
		} else {
			return $uArr;
		}
	}










	/***********************************
	 *
	 * Extension list rendering
	 *
	 **********************************/

	/**
	 * Prints the header row for the various listings
	 *
	 * @param	string		Attributes for the <tr> tag
	 * @param	array		Preset cells in the beginning of the row. Typically a blank cell with a clear-gif
	 * @param	boolean		If set, the list is coming from remote server.
	 * @return	string		HTML <tr> table row
	 */
	function extensionListRowHeader($trAttrib,$cells,$import=0)	{
		$cells[] = '<td></td>';
		$cells[] = '<td>Title:</td>';

		if (!$this->MOD_SETTINGS['display_details'])	{
			$cells[] = '<td>Description:</td>';
			$cells[] = '<td>Author:</td>';
		} elseif ($this->MOD_SETTINGS['display_details']==2)	{
			$cells[] = '<td>Priority:</td>';
			$cells[] = '<td>Mod.Tables:</td>';
			$cells[] = '<td>Modules:</td>';
			$cells[] = '<td>Cl.Cache?</td>';
			$cells[] = '<td>Internal?</td>';
			$cells[] = '<td>Shy?</td>';
		} elseif ($this->MOD_SETTINGS['display_details']==3)	{
			$cells[] = '<td>Tables/Fields:</td>';
			$cells[] = '<td>TS-files:</td>';
			$cells[] = '<td>Affects:</td>';
			$cells[] = '<td>Modules:</td>';
			$cells[] = '<td>Config?</td>';
			$cells[] = '<td>Errors:</td>';
		} elseif ($this->MOD_SETTINGS['display_details']==4)	{
			$cells[] = '<td>locallang:</td>';
			$cells[] = '<td>Classes:</td>';
			$cells[] = '<td>Errors:</td>';
			$cells[] = '<td>NameSpace Errors:</td>';
		} elseif ($this->MOD_SETTINGS['display_details']==5)	{
			$cells[] = '<td>Changed files:</td>';
		} else {
			$cells[] = '<td>Extension key:</td>';
			$cells[] = '<td>Version:</td>';
			if (!$import) {
				$cells[] = '<td>Doc:</td>';
				$cells[] = '<td>Type:</td>';
			} else {
				$cells[] = '<td class="bgColor6"'.$this->labelInfo('Current version of the extension on this server. If colored red there is a newer version in repository! Then you should upgrade.').'>Cur. Ver:</td>';
				$cells[] = '<td class="bgColor6"'.$this->labelInfo('Current type of installation of the extension on this server.').'>Cur. Type:</td>';
				$cells[] = '<td'.$this->labelInfo('If blank, everyone has access to this extension. "Owner" means that you see it ONLY because you are the owner. "Member" means you see it ONLY because you are among the project members.').'>Access:</td>';
				$cells[] = '<td'.$this->labelInfo('TYPO3 version of last uploading server.').'>T3 ver:</td>';
				$cells[] = '<td'.$this->labelInfo('PHP version of last uploading server.').'>PHP:</td>';
				$cells[] = '<td'.$this->labelInfo('Size of extension, uncompressed / compressed').'>Size:</td>';
				$cells[] = '<td'.$this->labelInfo('Number of downloads, all versions/this version').'>DL:</td>';
			}
			$cells[] = '<td>State:</td>';
			$cells[] = '<td>Dependencies:</td>';
		}
		return '
			<tr'.$trAttrib.'>
				'.implode('
				',$cells).'
			</tr>';
	}

	/**
	 * Prints a row with data for the various extension listings
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @param	array		Preset table cells, eg. install/uninstall icons.
	 * @param	string		<tr> tag class
	 * @param	array		Array with installed extension keys (as keys)
	 * @param	boolean		If set, the list is coming from remote server.
	 * @param	string		Alternative link URL
	 * @return	string		HTML <tr> content
	 */
	function extensionListRow($extKey,$extInfo,$cells,$bgColorClass='',$inst_list=array(),$import=0,$altLinkUrl='')	{

			// Initialize:
		$style = t3lib_extMgm::isLoaded($extKey) ? '' : ' style="color:#666666;"';

			// Icon:
		$imgInfo = @getImageSize($this->getExtPath($extKey,$extInfo['type']).'/ext_icon.gif');
		if (is_array($imgInfo))	{
			$cells[] = '<td><img src="'.$GLOBALS['BACK_PATH'].$this->typeRelPaths[$extInfo['type']].$extKey.'/ext_icon.gif'.'" '.$imgInfo[3].' alt="" /></td>';
		} elseif ($extInfo['_ICON']) {
			$cells[] = '<td>'.$extInfo['_ICON'].'</td>';
		} else {
			$cells[] = '<td><img src="clear.gif" width="1" height="1" alt="" /></td>';
		}

			// Extension title:
		$cells[] = '<td nowrap="nowrap"><a href="'.htmlspecialchars($altLinkUrl?$altLinkUrl:'index.php?CMD[showExt]='.$extKey.'&SET[singleDetails]=info').'" title="'.$extKey.'"'.$style.'>'.t3lib_div::fixed_lgd($extInfo['EM_CONF']['title']?$extInfo['EM_CONF']['title']:'<em>'.$extKey.'</em>',40).'</a></td>';

			// Unset extension key in installed keys array (for tracking)
		if (isset($inst_list[$extKey]))	{
			unset($this->inst_keys[$extKey]);
		}

			// Based on which display mode you will see more or less details:
		if (!$this->MOD_SETTINGS['display_details'])	{
			$cells[] = '<td>'.htmlspecialchars(t3lib_div::fixed_lgd($extInfo['EM_CONF']['description'],400)).'<br /><img src="clear.gif" width="300" height="1" alt="" /></td>';
			$cells[] = '<td nowrap="nowrap">'.htmlspecialchars($extInfo['EM_CONF']['author'].($extInfo['EM_CONF']['author_company'] ? '<br />'.$extInfo['EM_CONF']['author_company'] : '')).'</td>';
		} elseif ($this->MOD_SETTINGS['display_details']==2)	{
			$cells[] = '<td nowrap="nowrap">'.$extInfo['EM_CONF']['priority'].'</td>';
			$cells[] = '<td nowrap="nowrap">'.implode('<br />',t3lib_div::trimExplode(',',$extInfo['EM_CONF']['modify_tables'],1)).'</td>';
			$cells[] = '<td nowrap="nowrap">'.$extInfo['EM_CONF']['module'].'</td>';
			$cells[] = '<td nowrap="nowrap">'.($extInfo['EM_CONF']['clearCacheOnLoad'] ? 'Yes' : '').'</td>';
			$cells[] = '<td nowrap="nowrap">'.($extInfo['EM_CONF']['internal'] ? 'Yes' : '').'</td>';
			$cells[] = '<td nowrap="nowrap">'.($extInfo['EM_CONF']['shy'] ? 'Yes' : '').'</td>';
		} elseif ($this->MOD_SETTINGS['display_details']==3)	{
			$techInfo = $this->makeDetailedExtensionAnalysis($extKey,$extInfo);

			$cells[] = '<td>'.$this->extInformationArray_dbReq($techInfo).
				'</td>';
			$cells[] = '<td nowrap="nowrap">'.(is_array($techInfo['TSfiles']) ? implode('<br />',$techInfo['TSfiles']) : '').'</td>';
			$cells[] = '<td nowrap="nowrap">'.(is_array($techInfo['flags']) ? implode('<br />',$techInfo['flags']) : '').'</td>';
			$cells[] = '<td nowrap="nowrap">'.(is_array($techInfo['moduleNames']) ? implode('<br />',$techInfo['moduleNames']) : '').'</td>';
			$cells[] = '<td nowrap="nowrap">'.($techInfo['conf'] ? 'Yes' : '').'</td>';
			$cells[] = '<td>'.
				$GLOBALS['TBE_TEMPLATE']->rfw((t3lib_extMgm::isLoaded($extKey)&&$techInfo['tables_error']?'<strong>Table error!</strong><br />Probably one or more required fields/tables are missing in the database!':'').
				(t3lib_extMgm::isLoaded($extKey)&&$techInfo['static_error']?'<strong>Static table error!</strong><br />The static tables are missing or empty!':'')).
				'</td>';
		} elseif ($this->MOD_SETTINGS['display_details']==4)	{
			$techInfo=$this->makeDetailedExtensionAnalysis($extKey,$extInfo,1);

			$cells[] = '<td>'.(is_array($techInfo['locallang']) ? implode('<br />',$techInfo['locallang']) : '').'</td>';
			$cells[] = '<td>'.(is_array($techInfo['classes']) ? implode('<br />',$techInfo['classes']) : '').'</td>';
			$cells[] = '<td>'.(is_array($techInfo['errors']) ? $GLOBALS['TBE_TEMPLATE']->rfw(implode('<hr />',$techInfo['errors'])) : '').'</td>';
			$cells[] = '<td>'.(is_array($techInfo['NSerrors']) ? (!t3lib_div::inList($this->nameSpaceExceptions,$extKey) ? t3lib_div::view_array($techInfo['NSerrors']) : $GLOBALS['TBE_TEMPLATE']->dfw('[exception]')) :'').'</td>';
		} elseif ($this->MOD_SETTINGS['display_details']==5)	{
			$currentMd5Array = $this->serverExtensionMD5Array($extKey,$extInfo);
			$affectedFiles = '';
			$msgLines = array();
			$msgLines[] = 'Files: '.count($currentMd5Array);
			if (strcmp($extInfo['EM_CONF']['_md5_values_when_last_written'],serialize($currentMd5Array)))	{
				$msgLines[] = $GLOBALS['TBE_TEMPLATE']->rfw('<br /><strong>A difference between the originally installed version and the current was detected!</strong>');
				$affectedFiles = $this->findMD5ArrayDiff($currentMd5Array,unserialize($extInfo['EM_CONF']['_md5_values_when_last_written']));
				if (count($affectedFiles))	$msgLines[] = '<br /><strong>Modified files:</strong><br />'.$GLOBALS['TBE_TEMPLATE']->rfw(implode('<br />',$affectedFiles));
			}
			$cells[] = '<td>'.implode('<br />',$msgLines).'</td>';
		} else {
					// Default view:
			$verDiff = $inst_list[$extKey] && $this->versionDifference($extInfo['EM_CONF']['version'],$inst_list[$extKey]['EM_CONF']['version'],$this->versionDiffFactor);

			$cells[] = '<td nowrap="nowrap"><em>'.$extKey.'</em></td>';
			$cells[] = '<td nowrap="nowrap">'.($verDiff ? '<strong>'.$GLOBALS['TBE_TEMPLATE']->rfw(htmlspecialchars($extInfo['EM_CONF']['version'])).'</strong>' : $extInfo['EM_CONF']['version']).'</td>';
			if (!$import) {		// Listing extenson on LOCAL server:
				$fileP = PATH_site.$this->typePaths[$extInfo['type']].$extKey.'/doc/manual.sxw';

				$cells[] = '<td nowrap="nowrap">'.
						($this->typePaths[$extInfo['type']] && @is_file($fileP)?'<img src="oodoc.gif" width="13" height="16" title="Local Open Office Manual" alt="" />':'').
						'</td>';
				$cells[] = '<td nowrap="nowrap">'.$this->typeLabels[$extInfo['type']].(strlen($extInfo['doubleInstall'])>1?'<strong> '.$GLOBALS['TBE_TEMPLATE']->rfw($extInfo['doubleInstall']).'</strong>':'').'</td>';
			} else {	// Listing extensions from REMOTE repository:
				$inst_curVer = $inst_list[$extKey]['EM_CONF']['version'];
				if (isset($inst_list[$extKey]))	{
					if ($verDiff)	$inst_curVer = '<strong>'.$GLOBALS['TBE_TEMPLATE']->rfw($inst_curVer).'</strong>';
				}
				$cells[] = '<td nowrap="nowrap">'.$inst_curVer.'</td>';
				$cells[] = '<td nowrap="nowrap">'.$this->typeLabels[$inst_list[$extKey]['type']].(strlen($inst_list[$extKey]['doubleInstall'])>1?'<strong> '.$GLOBALS['TBE_TEMPLATE']->rfw($inst_list[$extKey]['doubleInstall']).'</strong>':'').'</td>';
				$cells[] = '<td nowrap="nowrap"><strong>'.$GLOBALS['TBE_TEMPLATE']->rfw($this->remoteAccess[$extInfo['_ACCESS']]).'</strong></td>';
				$cells[] = '<td nowrap="nowrap">'.$extInfo['EM_CONF']['_typo3_ver'].'</td>';
				$cells[] = '<td nowrap="nowrap">'.$extInfo['EM_CONF']['_php_ver'].'</td>';
				$cells[] = '<td nowrap="nowrap">'.$extInfo['EM_CONF']['_size'].'</td>';
				$cells[] = '<td nowrap="nowrap">'.($extInfo['_STAT_IMPORT']['extension_allversions']?$extInfo['_STAT_IMPORT']['extension_allversions']:'&nbsp;&nbsp;').'/'.($extInfo['_STAT_IMPORT']['extension_thisversion']?$extInfo['_STAT_IMPORT']['extension_thisversion']:'&nbsp;').'</td>';
			}
			$cells[] = '<td nowrap="nowrap">'.$this->states[$extInfo['EM_CONF']['state']].'</td>';
			$cells[] = '<td nowrap="nowrap">'.$extInfo['EM_CONF']['dependencies'].'</td>';
		}

		$bgColor = ' class="'.($bgColorClass?$bgColorClass:'bgColor4').'"';
		return '
			<tr'.$bgColor.$style.'>
				'.implode('
				',$cells).'
			</tr>';
	}










	/************************************
	 *
	 * Output helper functions
	 *
	 ************************************/

	/**
	 * Wrapping input string in a link tag with link to email address
	 *
	 * @param	string		Input string, being wrapped in <a> tags
	 * @param	string		Email address for use in link.
	 * @return	string		Output
	 */
	function wrapEmail($str,$email)	{
		if ($email)	{
			$str = '<a href="mailto:'.htmlspecialchars($email).'">'.htmlspecialchars($str).'</a>';
		}
		return $str;
	}

	/**
	 * Returns help text if applicable.
	 *
	 * @param	string		Help text key
	 * @return	string		HTML table cell
	 */
	function helpCol($key)	{
		global $BE_USER;
		if ($BE_USER->uc['edit_showFieldHelp'])	{
			$hT = trim(t3lib_BEfunc::helpText($this->descrTable,'emconf_'.$key,$this->doc->backPath));
			return '<td>'.($hT?$hT:t3lib_BEfunc::helpTextIcon($this->descrTable,'emconf_'.$key,$this->doc->backPath)).'</td>';
		}
	}

	/**
	 * Returns title and style attribute for mouseover help text.
	 *
	 * @param	string		Help text.
	 * @return	string		title="" attribute prepended with a single space
	 */
	function labelInfo($str)	{
		return ' title="'.htmlspecialchars($str).'" style="cursor:help;"';
	}

	/**
	 * Returns a header for an extensions including icon if any
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @param	string		align-attribute value (for <img> tag)
	 * @return	string		HTML; Extension title and image.
	 */
	function extensionTitleIconHeader($extKey,$extInfo,$align='top')	{
		$imgInfo = @getImageSize($this->getExtPath($extKey,$extInfo['type']).'/ext_icon.gif');
		$out = '';
		if (is_array($imgInfo))	{
			$out.= '<img src="'.$GLOBALS['BACK_PATH'].$this->typeRelPaths[$extInfo['type']].$extKey.'/ext_icon.gif" '.$imgInfo[3].' align="'.$align.'" alt="" />';
		}
		$out.= $extInfo['EM_CONF']['title'] ? htmlspecialchars(t3lib_div::fixed_lgd($extInfo['EM_CONF']['title'],40)) : '<em>'.$extKey.'</em>';
		return $out;
	}

	/**
	 * Returns image tag for "uninstall"
	 *
	 * @return	string		<img> tag
	 */
	function removeButton()	{
		return '<img src="uninstall.gif" width="16" height="16" title="Remove extension" align="top" alt="" />';
	}

	/**
	 * Returns image for "install"
	 *
	 * @return	string		<img> tag
	 */
	function installButton()	{
		return '<img src="install.gif" width="16" height="16" title="Install extension..." align="top" alt="" />';
	}

	/**
	 * Warning (<img> + text string) message about the impossibility to import extensions (both local and global locations are disabled...)
	 *
	 * @return	string		<img> + text string.
	 */
	function noImportMsg()	{
		return '<img src="'.$this->doc->backPath.'gfx/icon_warning2.gif" width="18" height="16" align="top" alt="" /><strong>Import to both local and global path is disabled in TYPO3_CONF_VARS!</strong>';
	}










	/********************************
	 *
	 * Read information about all available extensions
	 *
	 *******************************/

	/**
	 * Returns the list of available (installed) extensions
	 *
	 * @return	array		Array with two arrays, list array (all extensions with info) and category index
	 * @see getInstExtList()
	 */
	function getInstalledExtensions()	{
		$list = array();
		$cat = $this->defaultCategories;

		$path = PATH_site.TYPO3_mainDir.'sysext/';
		$this->getInstExtList($path,$list,$cat,'S');

		$path = PATH_site.TYPO3_mainDir.'ext/';
		$this->getInstExtList($path,$list,$cat,'G');

		$path = PATH_site.'typo3conf/ext/';
		$this->getInstExtList($path,$list,$cat,'L');

		return array($list,$cat);
	}

	/**
	 * Gathers all extensions in $path
	 *
	 * @param	string		Absolute path to local, global or system extensions
	 * @param	array		Array with information for each extension key found. Notice: passed by reference
	 * @param	array		Categories index: Contains extension titles grouped by various criteria.
	 * @param	string		Path-type: L, G or S
	 * @return	void		"Returns" content by reference
	 * @access private
	 * @see getInstalledExtensions()
	 */
	function getInstExtList($path,&$list,&$cat,$type)	{

		if (@is_dir($path))	{
			$extList = t3lib_div::get_dirs($path);
			if (is_array($extList))	{
				foreach($extList as $extKey)	{
					if (@is_file($path.$extKey.'/ext_emconf.php'))	{
						$emConf = $this->includeEMCONF($path.$extKey.'/ext_emconf.php', $extKey);
						if (is_array($emConf))	{
#							unset($emConf['_md5_values_when_last_written']);		// Trying to save space - hope this doesn't break anything. Shaves of maybe 100K!
#							unset($emConf['description']);		// Trying to save space - hope this doesn't break anything
							if (is_array($list[$extKey]))	{
								$list[$extKey]=array('doubleInstall'=>$list[$extKey]['doubleInstall']);
							}
							$list[$extKey]['doubleInstall'].= $type;
							$list[$extKey]['type'] = $type;
							$list[$extKey]['EM_CONF'] = $emConf;
#							$list[$extKey]['files'] = array_keys(array_flip(t3lib_div::getFilesInDir($path.$extKey)));	// Shaves off a little by using num-indexes
							$list[$extKey]['files'] = t3lib_div::getFilesInDir($path.$extKey);

							$this->setCat($cat,$list[$extKey], $extKey);
						}
					}
				}
			}
		}
	}

	/**
	 * Maps remote extensions information into $cat/$list arrays for listing
	 *
	 * @param	array		List of extensions from remote repository
	 * @return	array		List array and category index as key 0 / 1 in an array.
	 */
	function getImportExtList($listArr)	{
		$list = array();
		$cat = $this->defaultCategories;

		if (is_array($listArr))	{

			foreach($listArr as $dat)	{
				$extKey = $dat['extension_key'];
				$list[$extKey]['type'] = '_';
				$list[$extKey]['extRepUid'] = $dat['uid'];
				$list[$extKey]['_STAT_IMPORT'] = $dat['_STAT_IMPORT'];
				$list[$extKey]['_ACCESS'] = $dat['_ACCESS'];
				$list[$extKey]['_ICON'] = $dat['_ICON'];
				$list[$extKey]['_MEMBERS_ONLY'] = $dat['_MEMBERS_ONLY'];
				$list[$extKey]['EM_CONF'] = array(
					'title' => $dat['emconf_title'],
					'description' => $dat['emconf_description'],
					'category' => $dat['emconf_category'],
					'shy' => $dat['emconf_shy'],
					'dependencies' => $dat['emconf_dependencies'],
					'state' => $dat['emconf_state'],
					'private' => $dat['emconf_private'],
					'uploadfolder' => $dat['emconf_uploadfolder'],
					'createDirs' => $dat['emconf_createDirs'],
					'modify_tables' => $dat['emconf_modify_tables'],
					'module' => $dat['emconf_module'],
					'lockType' => $dat['emconf_lockType'],
					'clearCacheOnLoad' => $dat['emconf_clearCacheOnLoad'],
					'priority' => $dat['emconf_priority'],
					'version' => $dat['version'],
					'internal' => $dat['emconf_internal'],
					'author' => $dat['emconf_author'],
					'author_company' => $dat['emconf_author_company'],

					'_typo3_ver' => $dat['upload_typo3_version'],
					'_php_ver' => $dat['upload_php_version'],
					'_size' => t3lib_div::formatSize($dat['datasize']).'/'.t3lib_div::formatSize($dat['datasize_gz']),
				);
				$this->setCat($cat, $list[$extKey], $extKey);
			}
		}
		return array($list,$cat);
	}

	/**
	 * Set category array entries for extension
	 *
	 * @param	array		Category index array
	 * @param	array		Part of list array for extension.
	 * @param	string		Extension key
	 * @return	array		Modified category index array
	 */
	function setCat(&$cat,$listArrayPart,$extKey)	{

			// Getting extension title:
		$extTitle = $listArrayPart['EM_CONF']['title'];

			// Category index:
		$index = $listArrayPart['EM_CONF']['category'];
		$cat['cat'][$index][$extKey] = $extTitle;

			// Author index:
		$index = $listArrayPart['EM_CONF']['author'].($listArrayPart['EM_CONF']['author_company']?', '.$listArrayPart['EM_CONF']['author_company']:'');
		$cat['author_company'][$index][$extKey] = $extTitle;

			// State index:
		$index = $listArrayPart['EM_CONF']['state'];
		$cat['state'][$index][$extKey] = $extTitle;

			// Private index:
		$index = $listArrayPart['EM_CONF']['private'] ? 1 : 0;
		$cat['private'][$index][$extKey] = $extTitle;

			// Type index:
		$index = $listArrayPart['type'];
		$cat['type'][$index][$extKey] = $extTitle;

			// Dependencies:
		if ($list[$extKey]['EM_CONF']['dependencies'])	{
			$depItems = t3lib_div::trimExplode(',', $list[$extKey]['EM_CONF']['dependencies'], 1);
			foreach($depItems as $depKey)	{
				$cat['dep'][$depKey][$extKey] = $extTitle;
			}
		}

			// Return categories:
		return $cat;
	}










	/*******************************
	 *
	 * Extension analyzing (detailed information)
	 *
	 ******************************/

	/**
	 * Perform a detailed, technical analysis of the available extension on server!
	 * Includes all kinds of verifications
	 * Takes some time to process, therfore use with care, in particular in listings.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information
	 * @param	boolean		If set, checks for validity of classes etc.
	 * @return	array		Information in an array.
	 */
	function makeDetailedExtensionAnalysis($extKey,$extInfo,$validity=0)	{

			// Get absolute path of the extension
		$absPath = $this->getExtPath($extKey,$extInfo['type']);

		$infoArray = array();

		$table_class_prefix = substr($extKey,0,5)=='user_' ? 'user_' : 'tx_'.str_replace('_','',$extKey).'_';
		$module_prefix = substr($extKey,0,5)=='user_' ? 'u' : 'tx'.str_replace('_','',$extKey);

			// Database status:
		$dbInfo = $this->checkDBupdates($extKey,$extInfo,1);

			// Database structure required:
		if (is_array($dbInfo['structure']['tables_fields']))	{
			$modify_tables = t3lib_div::trimExplode(',',$extInfo['EM_CONF']['modify_tables'],1);
			$infoArray['dump_tf'] = array();

			foreach($dbInfo['structure']['tables_fields'] as $tN => $d)	{
				if (in_array($tN,$modify_tables))	{
					$infoArray['fields'][] = $tN.': <i>'.
							(is_array($d['fields']) ? implode(', ',array_keys($d['fields'])) : '').
							(is_array($d['keys']) ? ' + '.count($d['keys']).' keys' : '').
						'</i>';
					if (is_array($d['fields']))	{
						reset($d['fields']);
						while(list($fN) = each($d['fields']))	{
							$infoArray['dump_tf'][] = $tN.'.'.$fN;
							if (!t3lib_div::isFirstPartOfStr($fN,$table_class_prefix))	{
								$infoArray['NSerrors']['fields'][$fN] = $fN;
							} else {
								$infoArray['NSok']['fields'][$fN] = $fN;
							}
						}
					}
					if (is_array($d['keys']))	{
						reset($d['keys']);
						while(list($fN)=each($d['keys']))	{
							$infoArray['dump_tf'][] = $tN.'.KEY:'.$fN;
						}
					}
				} else {
					$infoArray['dump_tf'][] = $tN;
					$infoArray['tables'][] = $tN;
					if (!t3lib_div::isFirstPartOfStr($tN,$table_class_prefix))	{
						$infoArray['NSerrors']['tables'][$tN] = $tN;
					} else $infoArray['NSok']['tables'][$tN] = $tN;
				}
			}
			if (count($dbInfo['structure']['diff']['diff']) || count($dbInfo['structure']['diff']['extra']))	{
				$msg = array();
				if (count($dbInfo['structure']['diff']['diff']))	$msg[] = 'missing';
				if (count($dbInfo['structure']['diff']['extra']))	$msg[] = 'of wrong type';
				$infoArray['tables_error'] = 1;
				if (t3lib_extMgm::isLoaded($extKey))	$infoArray['errors'][] = 'Some tables or fields are '.implode(' and ',$msg).'!';
			}
		}

			// Static tables?
		if (is_array($dbInfo['static']))	{
			$infoArray['static'] = array_keys($dbInfo['static']);

			foreach($dbInfo['static'] as $tN => $d)	{
				if (!$d['exists'])	{
					$infoArray['static_error'] = 1;
					if (t3lib_extMgm::isLoaded($extKey))	$infoArray['errors'][] = 'Static table(s) missing!';
					if (!t3lib_div::isFirstPartOfStr($tN,$table_class_prefix))	{
						$infoArray['NSerrors']['tables'][$tN] = $tN;
					} else $infoArray['NSok']['tables'][$tN] = $tN;
				}
			}
		}

			// Backend Module-check:
		$knownModuleList = t3lib_div::trimExplode(',',$extInfo['EM_CONF']['module'],1);
		foreach($knownModuleList as $mod)	{
			if (@is_dir($absPath.$mod))	{
				if (@is_file($absPath.$mod.'/conf.php'))	{
					$confFileInfo = $this->modConfFileAnalysis($absPath.$mod.'/conf.php');
					if (is_array($confFileInfo['TYPO3_MOD_PATH']))	{
						$shouldBePath = $this->typeRelPaths[$extInfo['type']].$extKey.'/'.$mod.'/';
						if (strcmp($confFileInfo['TYPO3_MOD_PATH'][1][1],$shouldBePath))	{
							$infoArray['errors'][] = 'Configured TYPO3_MOD_PATH "'.$confFileInfo['TYPO3_MOD_PATH'][1][1].'" different from "'.$shouldBePath.'"';
						}
					} else $infoArray['errors'][] = 'No definition of TYPO3_MOD_PATH constant found inside!';
					if (is_array($confFileInfo['MCONF_name']))	{
						$mName = $confFileInfo['MCONF_name'][1][1];
						$mNameParts = explode('_',$mName);
						$infoArray['moduleNames'][] = $mName;
						if (!t3lib_div::isFirstPartOfStr($mNameParts[0],$module_prefix) &&
							(!$mNameParts[1] || !t3lib_div::isFirstPartOfStr($mNameParts[1],$module_prefix)))	{
							$infoArray['NSerrors']['modname'][] = $mName;
						} else $infoArray['NSok']['modname'][] = $mName;
					} else $infoArray['errors'][] = 'No definition of MCONF[name] variable found inside!';
				} else  $infoArray['errors'][] = 'Backend module conf file "'.$mod.'/conf.php" should exist but does not!';
			} else $infoArray['errors'][] = 'Backend module folder "'.$mod.'/" should exist but does not!';
		}
		$dirs = t3lib_div::get_dirs($absPath);
		if (is_array($dirs))	{
			reset($dirs);
			while(list(,$mod) = each($dirs))	{
				if (!in_array($mod,$knownModuleList) && @is_file($absPath.$mod.'/conf.php'))	{
					$confFileInfo = $this->modConfFileAnalysis($absPath.$mod.'/conf.php');
					if (is_array($confFileInfo))	{
						$infoArray['errors'][] = 'It seems like there is a backend module in "'.$mod.'/conf.php" which is not configured in ext_emconf.php';
					}
				}
			}
		}

			// ext_tables.php:
		if (@is_file($absPath.'ext_tables.php'))	{
			$content = t3lib_div::getUrl($absPath.'ext_tables.php');
			if (eregi('t3lib_extMgm::addModule',$content))	$infoArray['flags'][] = 'Module';
			if (eregi('t3lib_extMgm::insertModuleFunction',$content))	$infoArray['flags'][] = 'Module+';
			if (stristr($content,'t3lib_div::loadTCA'))	$infoArray['flags'][] = 'loadTCA';
			if (stristr($content,'$TCA['))	$infoArray['flags'][] = 'TCA';
			if (eregi('t3lib_extMgm::addPlugin',$content))	$infoArray['flags'][] = 'Plugin';
		}

			// ext_localconf.php:
		if (@is_file($absPath.'ext_localconf.php'))	{
			$content = t3lib_div::getUrl($absPath.'ext_localconf.php');
			if (eregi('t3lib_extMgm::addPItoST43',$content))	$infoArray['flags'][]='Plugin/ST43';
			if (eregi('t3lib_extMgm::addPageTSConfig',$content))	$infoArray['flags'][]='Page-TSconfig';
			if (eregi('t3lib_extMgm::addUserTSConfig',$content))	$infoArray['flags'][]='User-TSconfig';
			if (eregi('t3lib_extMgm::addTypoScriptSetup',$content))	$infoArray['flags'][]='TS/Setup';
			if (eregi('t3lib_extMgm::addTypoScriptConstants',$content))	$infoArray['flags'][]='TS/Constants';
		}

		if (@is_file($absPath.'ext_typoscript_constants.txt'))	{
			$infoArray['TSfiles'][] = 'Constants';
		}
		if (@is_file($absPath.'ext_typoscript_setup.txt'))	{
			$infoArray['TSfiles'][] = 'Setup';
		}
		if (@is_file($absPath.'ext_conf_template.txt'))	{
			$infoArray['conf'] = 1;
		}

			// Classes:
		if ($validity)	{
			$filesInside = $this->getClassIndexLocallangFiles($absPath,$table_class_prefix,$extKey);
			if (is_array($filesInside['errors']))	$infoArray['errors'] = array_merge($infoArray['errors'],$filesInside['errors']);
			if (is_array($filesInside['NSerrors']))	$infoArray['NSerrors'] = array_merge($infoArray['NSerrors'],$filesInside['NSerrors']);
			if (is_array($filesInside['NSok']))	$infoArray['NSok'] = array_merge($infoArray['NSok'],$filesInside['NSok']);
			$infoArray['locallang'] = $filesInside['locallang'];
			$infoArray['classes'] = $filesInside['classes'];
		}

			// Upload folders
		if ($extInfo['EM_CONF']['uploadfolder'])	{
	 		$infoArray['uploadfolder'] = $this->ulFolder($extKey);
			if (!@is_dir(PATH_site.$infoArray['uploadfolder']))	{
				$infoArray['errors'][] = 'Error: Upload folder "'.$infoArray['uploadfolder'].'" did not exist!';
				$infoArray['uploadfolder'] = '';
			}
		}

			// Create directories:
		if ($extInfo['EM_CONF']['createDirs'])	{
	 		$infoArray['createDirs'] = array_unique(t3lib_div::trimExplode(',',$extInfo['EM_CONF']['createDirs'],1));
			foreach($infoArray['createDirs'] as $crDir)	{
				if (!@is_dir(PATH_site.$crDir))	{
					$infoArray['errors'][]='Error: Upload folder "'.$crDir.'" did not exist!';
				}
			}
		}

			// Return result array:
		return $infoArray;
	}

	/**
	 * Analyses the php-scripts of an available extension on server
	 *
	 * @param	string		Absolute path to extension
	 * @param	string		Prefix for tables/classes.
	 * @param	string		Extension key
	 * @return	array		Information array.
	 * @see makeDetailedExtensionAnalysis()
	 */
	function getClassIndexLocallangFiles($absPath,$table_class_prefix,$extKey)	{
		$filesInside = t3lib_div::removePrefixPathFromList(t3lib_div::getAllFilesAndFoldersInPath(array(),$absPath,'php,inc'),$absPath);
		$out = array();

		foreach($filesInside as $fileName)	{
			if (substr($fileName,0,4)!='ext_')	{
				$baseName = basename($fileName);
				if (substr($baseName,0,9)=='locallang' && substr($baseName,-4)=='.php')	{
					$out['locallang'][] = $fileName;
				} elseif ($baseName!='conf.php')	{
					if (filesize($absPath.$fileName)<500*1024)	{
						$fContent = t3lib_div::getUrl($absPath.$fileName);
						unset($reg);
						if (ereg("\n[[:space:]]*class[[:space:]]*([[:alnum:]_]+)([[:alnum:][:space:]_]*){",$fContent,$reg))	{

								// Find classes:
							$classesInFile=array();
							$lines = explode(chr(10),$fContent);
							foreach($lines as $k => $l)	{
								$line = trim($l);
								unset($reg);
								if (ereg('^class[[:space:]]*([[:alnum:]_]+)([[:alnum:][:space:]_]*){',$line,$reg))	{
									$out['classes'][] = $reg[1];
									$out['files'][$fileName]['classes'][] = $reg[1];
									if (substr($reg[1],0,3)!='ux_' && !t3lib_div::isFirstPartOfStr($reg[1],$table_class_prefix) && strcmp(substr($table_class_prefix,0,-1),$reg[1]))	{
										$out['NSerrors']['classname'][] = $reg[1];
									} else $out['NSok']['classname'][] = $reg[1];
								}
							}
								// If class file prefixed 'class.'....
							if (substr($baseName,0,6)=='class.')	{
								$fI = pathinfo($baseName);
								$testName=substr($baseName,6,-(1+strlen($fI['extension'])));
								if (substr($testName,0,3)!='ux_' && !t3lib_div::isFirstPartOfStr($testName,$table_class_prefix) && strcmp(substr($table_class_prefix,0,-1),$testName))	{
									$out['NSerrors']['classfilename'][] = $baseName;
								} else {
									$out['NSok']['classfilename'][] = $baseName;
									if (is_array($out['files'][$fileName]['classes']) && $this->first_in_array($testName,$out['files'][$fileName]['classes'],1))	{
										$out['msg'][] = 'Class filename "'.$fileName.'" did contain the class "'.$testName.'" just as it should.';
									} else $out['errors'][] = 'Class filename "'.$fileName.'" did NOT contain the class "'.$testName.'"!';
								}
							}
								//
							$XclassParts = split('if \(defined\([\'"]TYPO3_MODE[\'"]\) && \$TYPO3_CONF_VARS\[TYPO3_MODE\]\[[\'"]XCLASS[\'"]\]',$fContent,2);
							if (count($XclassParts)==2)	{
								unset($reg);
								ereg('^\[[\'"]([[:alnum:]_\/\.]*)[\'"]\]',$XclassParts[1],$reg);
								if ($reg[1]) {
									$cmpF = 'ext/'.$extKey.'/'.$fileName;
									if (!strcmp($reg[1],$cmpF))	{
										if (ereg('_once\(\$TYPO3_CONF_VARS\[TYPO3_MODE\]\[[\'"]XCLASS[\'"]\]\[[\'"]'.$cmpF.'[\'"]\]\);', $XclassParts[1]))	{
											 $out['msg'][] = 'XCLASS OK in '.$fileName;
										} else $out['errors'][] = 'Couldn\'t find the include_once statement for XCLASS!';
									} else $out['errors'][] = 'The XCLASS filename-key "'.$reg[1].'" was different from "'.$cmpF.'" which it should have been!';
								} else $out['errors'][] = 'No XCLASS filename-key found in file "'.$fileName.'". Maybe a regex coding error here...';
							} elseif (!$this->first_in_array('ux_',$out['files'][$fileName]['classes'])) $out['errors'][] = 'No XCLASS inclusion code found in file "'.$fileName.'"';
						}
					}
				}
			}
		}
		return $out;
	}

	/**
	 * Reads $confFilePath (a module $conf-file) and returns information on the existence of TYPO3_MOD_PATH definition and MCONF_name
	 *
	 * @param	string		Absolute path to a "conf.php" file of a module which we are analysing.
	 * @return	array		Information found.
	 * @see writeTYPO3_MOD_PATH()
	 */
	function modConfFileAnalysis($confFilePath)	{
		$lines = explode(chr(10),t3lib_div::getUrl($confFilePath));
		$confFileInfo = array();
		$confFileInfo['lines'] = $lines;

		foreach($lines as $k => $l)	{
			$line = trim($l);

			unset($reg);
			if (ereg('^define[[:space:]]*\([[:space:]]*["\']TYPO3_MOD_PATH["\'][[:space:]]*,[[:space:]]*["\']([[:alnum:]_\/\.]+)["\'][[:space:]]*\)[[:space:]]*;',$line,$reg))	{
				$confFileInfo['TYPO3_MOD_PATH'] = array($k,$reg);
			}

			unset($reg);
			if (ereg('^\$MCONF\[["\']?name["\']?\][[:space:]]*=[[:space:]]*["\']([[:alnum:]_]+)["\'];',$line,$reg))	{
				$confFileInfo['MCONF_name'] = array($k,$reg);
			}
		}
		return $confFileInfo;
	}

	/**
	 * Creates a MD5-hash array over the current files in the extension
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	array		MD5-keys
	 */
	function serverExtensionMD5Array($extKey,$conf)	{

			// Creates upload-array - including filelist.
		$mUA = $this->makeUploadArray($extKey,$conf);

		$md5Array = array();
		if (is_array($mUA['FILES']))	{

				// Traverse files.
			foreach($mUA['FILES'] as $fN => $d)	{
				if ($fN!='ext_emconf.php')	{
					$md5Array[$fN] = substr($d['content_md5'],0,4);
				}
			}
		} else debug($mUA);
		return $md5Array;
	}

	/**
	 * Compares two arrays with MD5-hash values for analysis of which files has changed.
	 *
	 * @param	array		Current values
	 * @param	array		Past values
	 * @return	array		Affected files
	 */
	function findMD5ArrayDiff($current,$past)	{
		if (!is_array($current))	$current = array();
		if (!is_array($past))		$past = array();
		$filesInCommon = array_intersect($current,$past);
		$diff1 =  array_keys(array_diff($past,$filesInCommon));
		$diff2 =  array_keys(array_diff($current,$filesInCommon));
		$affectedFiles = array_unique(array_merge($diff1,$diff2));
		return $affectedFiles;
	}










	/***********************************
	 *
	 * File system operations
	 *
	 **********************************/

	/**
	 * Creates directories in $extDirPath
	 *
	 * @param	array		Array of directories to create relative to extDirPath, eg. "blabla", "blabla/blabla" etc...
	 * @param	string		Absolute path to directory.
	 * @return	mixed		Returns false on success or an error string
	 */
	function createDirsInPath($dirs,$extDirPath)	{
		if (is_array($dirs))	{
			foreach($dirs as $dir)	{
				$allDirs = t3lib_div::trimExplode('/',$dir,1);
				$root = '';
				foreach($allDirs as $dirParts)	{
					$root.=$dirParts.'/';
					if (!is_dir($extDirPath.$root))	{
						t3lib_div::mkdir($extDirPath.$root);
						if (!@is_dir($extDirPath.$root))	{
							return 'Error: The directory "'.$extDirPath.$root.'" could not be created...';
						}
					}
				}
			}
		}
	}

	/**
	 * Removes the extension directory (including content)
	 *
	 * @param	string		Extension directory to remove (with trailing slash)
	 * @param	boolean		If set, will leave the extension directory
	 * @return	boolean		False on success, otherwise error string.
	 */
	function removeExtDirectory($removePath,$removeContentOnly=0)	{
		$errors = array();
		if (@is_dir($removePath) && substr($removePath,-1)=='/' && (
			t3lib_div::isFirstPartOfStr($removePath,PATH_site.$this->typePaths['G']) ||
			t3lib_div::isFirstPartOfStr($removePath,PATH_site.$this->typePaths['L']) ||
			(t3lib_div::isFirstPartOfStr($removePath,PATH_site.$this->typePaths['S']) && $this->systemInstall) ||
			t3lib_div::isFirstPartOfStr($removePath,PATH_site.'fileadmin/_temp_/'))		// Playing-around directory...
			) {

				// All files in extension directory:
			$fileArr = t3lib_div::getAllFilesAndFoldersInPath(array(),$removePath);
			if (is_array($fileArr))	{

					// Remove files in dirs:
				foreach($fileArr as $removeFile)	{
					if (!@is_dir($removeFile))	{
						if (@is_file($removeFile) && t3lib_div::isFirstPartOfStr($removeFile,$removePath) && strcmp($removeFile,$removePath))	{	// ... we are very paranoid, so we check what cannot go wrong: that the file is in fact within the prefix path!
							@unlink($removeFile);
							clearstatcache();
							if (@is_file($removeFile))	{
								$errors[] = 'Error: "'.$removeFile.'" could not be deleted!';
							}
						} else $errors[] = 'Error: "'.$removeFile.'" was either not a file, or it was equal to the removed directory or simply outside the removed directory "'.$removePath.'"!';
					}
				}

					// Remove directories:
				$remDirs = $this->extractDirsFromFileList(t3lib_div::removePrefixPathFromList($fileArr,$removePath));
				$remDirs = array_reverse($remDirs);	// Must delete outer directories first...
				foreach($remDirs as $removeRelDir)	{
					$removeDir = $removePath.$removeRelDir;
					if (@is_dir($removeDir))	{
						rmdir($removeDir);
						clearstatcache();
						if (@is_dir($removeDir))	{
							$errors[] = 'Error: "'.$removeDir.'" could not be removed (are there files left?)';
						}
					} else $errors[] = 'Error: "'.$removeDir.'" was not a directory!';
				}

					// If extension dir should also be removed:
				if (!$removeContentOnly)	{
					rmdir($removePath);
					clearstatcache();
					if (@is_dir($removePath))	{
						$errors[] = 'Error: Extension directory "'.$removePath.'" could not be removed (are there files or folders left?)';
					}
				}
			} else $errors[] = 'Error: '.$fileArr;
		} else $errors[] = 'Error: Unallowed path to remove: '.$removePath;

			// Return errors if any:
		return implode(chr(10),$errors);
	}

	/**
	 * Removes the current extension of $type and creates the base folder for the new one (which is going to be imported)
	 *
	 * @param	array		Data for imported extension
	 * @param	string		Extension installation scope (L,G,S)
	 * @return	mixed		Returns array on success (with extension directory), otherwise an error string.
	 */
	function clearAndMakeExtensionDir($importedData,$type)	{
		if (!$importedData['extKey'])	return 'FATAL ERROR: Extension key was not set for some VERY strange reason. Nothing done...';

			// Setting install path (L, G, S or fileadmin/_temp_/)
		$path = '';
		switch((string)$type)	{
			case 'G':
			case 'L':
				$path = PATH_site.$this->typePaths[$type];
				$suffix = '';

					// Creates the typo3conf/ext/ directory if it does NOT already exist:
				if ((string)$type=='L' && !@is_dir($path))	{
					t3lib_div::mkdir($path);
				}
			break;
			default:
				if ($this->systemInstall && (string)$type=='S')	{
					$path = PATH_site.$this->typePaths[$type];
					$suffix = '';
				} else {
					$path = PATH_site.'fileadmin/_temp_/';
					$suffix = '_'.date('dmy-His');
				}
			break;
		}

			// If the install path is OK...
		if ($path && @is_dir($path))	{

				// Set extension directory:
			$extDirPath = $path.$importedData['extKey'].$suffix.'/';

				// Install dir was found, remove it then:
			if (@is_dir($extDirPath))	{
				$res = $this->removeExtDirectory($extDirPath);
				if ($res) {
					return 'ERROR: Could not remove extension directory "'.$extDirPath.'". Reasons:<br /><br />'.nl2br($res);
				}
			}

				// We go create...
			t3lib_div::mkdir($extDirPath);
			if (!is_dir($extDirPath))	return 'ERROR: Could not create extension directory "'.$extDirPath.'"';
			return array($extDirPath);
		} else return 'ERROR: The extension install path "'.$path.'" was not a directory.';
	}

	/**
	 * Unlink (delete) cache files
	 *
	 * @return	integer		Number of deleted files.
	 */
	function removeCacheFiles()	{
		$cacheFiles = t3lib_extMgm::currentCacheFiles();
		$out = 0;
		if (is_array($cacheFiles))	{
			reset($cacheFiles);
			while(list(,$cfile) = each($cacheFiles))	{
				@unlink($cfile);
				clearstatcache();
				$out++;
			}
		}
		return $out;
	}

	/**
	 * Extracts the directories in the $files array
	 *
	 * @param	array		Array of files / directories
	 * @return	array		Array of directories from the input array.
	 */
	function extractDirsFromFileList($files)	{
		$dirs = array();

		if (is_array($files))	{
				// Traverse files / directories array:
			foreach($files as $file)	{
				if (substr($file,-1)=='/')	{
					$dirs[$file] = $file;
				} else {
					$pI = pathinfo($file);
					if (strcmp($pI['dirname'],'') && strcmp($pI['dirname'],'.'))	{
						$dirs[$pI['dirname'].'/'] = $pI['dirname'].'/';
					}
				}
			}
		}
		return $dirs;
	}

	/**
	 * Returns the absolute path where the extension $extKey is installed (based on 'type' (SGL))
	 *
	 * @param	string		Extension key
	 * @param	string		Install scope type: L, G, S
	 * @return	string		Returns the absolute path to the install scope given by input $type variable. It is checked if the path is a directory. Slash is appended.
	 */
	function getExtPath($extKey,$type)	{
		$typeP = $this->typePaths[$type];
		if ($typeP)	{
			$path = PATH_site.$typeP.$extKey.'/';
			return @is_dir($path) ? $path : '';
		}
	}










	/*******************************
	 *
	 * Writing to "conf.php" and "localconf.php" files
	 *
	 ******************************/

	/**
	 * Write new TYPO3_MOD_PATH to "conf.php" file.
	 *
	 * @param	string		Absolute path to a "conf.php" file of the backend module which we want to write back to.
	 * @param	string		Install scope type: L, G, S
	 * @param	string		Relative path for the module folder in extenson
	 * @return	string		Returns message about the status.
	 * @see modConfFileAnalysis()
	 */
	function writeTYPO3_MOD_PATH($confFilePath,$type,$mP)	{
		$lines = explode(chr(10),t3lib_div::getUrl($confFilePath));
		$confFileInfo = array();
		$confFileInfo['lines'] = $lines;

		$flag_M = 0;
		$flag_B = 0;

		foreach($lines as $k => $l)	{
			$line = trim($l);

			unset($reg);
			if (ereg('^define[[:space:]]*\([[:space:]]*["\']TYPO3_MOD_PATH["\'][[:space:]]*,[[:space:]]*["\']([[:alnum:]_\/\.]+)["\'][[:space:]]*\)[[:space:]]*;',$line,$reg))	{
				$lines[$k] = str_replace($reg[0], 'define(\'TYPO3_MOD_PATH\', \''.$this->typeRelPaths[$type].$mP.'\');', $lines[$k]);
				$flag_M = $k+1;
			}

			unset($reg);
			if (ereg('^\$BACK_PATH[[:space:]]*=[[:space:]]*["\']([[:alnum:]_\/\.]+)["\'][[:space:]]*;',$line,$reg))	{
				$lines[$k] = str_replace($reg[0], '$BACK_PATH=\''.$this->typeBackPaths[$type].'\';', $lines[$k]);
				$flag_B = $k+1;
			}
		}

		if ($flag_B && $flag_M)	{
			t3lib_div::writeFile($confFilePath,implode(chr(10),$lines));
			return 'TYPO3_MOD_PATH and $BACK_PATH was updated in "'.substr($confFilePath,strlen(PATH_site)).'"';
		} else return 'Error: Either TYPO3_MOD_PATH or $BACK_PATH was not found in the "'.$confFilePath.'" file. You must manually configure that!';
	}

	/**
	 * Writes the extension list to "localconf.php" file
	 * Removes the temp_CACHED* files before return.
	 *
	 * @param	string		List of extensions
	 * @return	void
	 */
	function writeNewExtensionList($newExtList)	{

			// Instance of install tool
		$instObj = new t3lib_install;
		$instObj->allowUpdateLocalConf =1;
		$instObj->updateIdentity = 'TYPO3 Extension Manager';

			// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extList\']', $newExtList);
		$instObj->writeToLocalconf_control($lines);

		$this->removeCacheFiles();
	}

	/**
	 * Writes the TSstyleconf values to "localconf.php"
	 * Removes the temp_CACHED* files before return.
	 *
	 * @param	string		Extension key
	 * @param	array		Configuration array to write back
	 * @return	void
	 */
	function writeTsStyleConfig($extKey,$arr)	{

			// Instance of install tool
		$instObj = new t3lib_install;
		$instObj->allowUpdateLocalConf =1;
		$instObj->updateIdentity = 'TYPO3 Extension Manager';

			// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extConf\'][\''.$extKey.'\']', serialize($arr));	// This will be saved only if there are no linebreaks in it !
		$instObj->writeToLocalconf_control($lines);

		$this->removeCacheFiles();
	}

	/**
	 * Forces update of local EM_CONF. This will renew the information of changed files.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		Status message
	 */
	function updateLocalEM_CONF($extKey,$extInfo)	{
		$EM_CONF = $extInfo['EM_CONF'];
		$EM_CONF['_md5_values_when_last_written'] = serialize($this->serverExtensionMD5Array($extKey,$extInfo));
		$emConfFileContent = $this->construct_ext_emconf_file($extKey,$EM_CONF);

		if ($emConfFileContent)	{
			$absPath = $this->getExtPath($extKey,$extInfo['type']);
			$emConfFileName = $absPath.'ext_emconf.php';

			if (@is_file($emConfFileName))	{
				t3lib_div::writeFile($emConfFileName,$emConfFileContent);
				return '"'.substr($emConfFileName,strlen($absPath)).'" was updated with a cleaned up EM_CONF array.';
			} else die('Error: No file "'.$emConfFileName.'" found.');
		}
	}










	/*******************************************
	 *
	 * Compiling upload information, emconf-file etc.
	 *
	 *******************************************/

	/**
	 * Compiles the ext_emconf.php file
	 *
	 * @param	string		Extension key
	 * @param	array		EM_CONF array
	 * @return	string		PHP file content, ready to write to ext_emconf.php file
	 */
	function construct_ext_emconf_file($extKey,$EM_CONF)	{

		$fMsg = array(
			'version' => '	// Don\'t modify this! Managed automatically during upload to repository.'
		);

			// clean version number:
		$vDat = $this->renderVersion($EM_CONF['version']);
		$EM_CONF['version']=$vDat['version'];

		$lines=array();
		$lines[]='<?php';
		$lines[]='';
		$lines[]='########################################################################';
		$lines[]='# Extension Manager/Repository config file for ext: "'.$extKey.'"';
		$lines[]='# ';
		$lines[]='# Auto generated '.date('d-m-Y H:i');
		$lines[]='# ';
		$lines[]='# Manual updates:';
		$lines[]='# Only the data in the array - anything else is removed by next write';
		$lines[]='########################################################################';
		$lines[]='';
		$lines[]='$EM_CONF[$_EXTKEY] = Array (';

		foreach($EM_CONF as $k => $v)	{
			$lines[] = chr(9)."'".$k."' => ".(
				t3lib_div::testInt($v)?
				intval($v):
				"'".t3lib_div::slashJS(trim($v),1)."'"
			).','.$fMsg[$k];
		}
		$lines[]=');';
		$lines[]='';
		$lines[]='?>';

		return implode(chr(10),$lines);
	}

	/**
	 * Make upload array out of extension
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	mixed		Returns array with extension upload array on success, otherwise an error string.
	 */
	function makeUploadArray($extKey,$conf)	{
		$extPath = $this->getExtPath($extKey,$conf['type']);

		if ($extPath)	{

				// Get files for extension:
			$fileArr = array();
			$fileArr = t3lib_div::getAllFilesAndFoldersInPath($fileArr,$extPath);

				// Calculate the total size of those files:
			$totalSize = 0;
			foreach($fileArr as $file)	{
				$totalSize+=filesize($file);
			}

				// If the total size is less than the upper limit, proceed:
			if ($totalSize < $this->maxUploadSize)	{

					// Initialize output array:
				$uploadArray = array();
				$uploadArray['extKey'] = $extKey;
				$uploadArray['EM_CONF'] = $conf['EM_CONF'];
				$uploadArray['misc']['codelines'] = 0;
				$uploadArray['misc']['codebytes'] = 0;

				$uploadArray['techInfo'] = $this->makeDetailedExtensionAnalysis($extKey,$conf,1);

					// Read all files:
				foreach($fileArr as $file)	{
					$relFileName = substr($file,strlen($extPath));
					$fI = pathinfo($relFileName);
					if ($relFileName!='ext_emconf.php')	{		// This file should be dynamically written...
						$uploadArray['FILES'][$relFileName] = array(
							'name' => $relFileName,
							'size' => filesize($file),
							'mtime' => filemtime($file),
							'is_executable' => (TYPO3_OS=='WIN' ? 0 : is_executable($file)),
							'content' => t3lib_div::getUrl($file)
						);
						if (t3lib_div::inList('php,inc',strtolower($fI['extension'])))	{
							$uploadArray['FILES'][$relFileName]['codelines']=count(explode(chr(10),$uploadArray['FILES'][$relFileName]['content']));
							$uploadArray['misc']['codelines']+=$uploadArray['FILES'][$relFileName]['codelines'];
							$uploadArray['misc']['codebytes']+=$uploadArray['FILES'][$relFileName]['size'];

								// locallang*.php files:
							if (substr($fI['basename'],0,9)=='locallang' && strstr($uploadArray['FILES'][$relFileName]['content'],'$LOCAL_LANG'))	{
								$uploadArray['FILES'][$relFileName]['LOCAL_LANG']=$this->getSerializedLocalLang($file,$uploadArray['FILES'][$relFileName]['content']);
							}
						}
						$uploadArray['FILES'][$relFileName]['content_md5'] = md5($uploadArray['FILES'][$relFileName]['content']);
					}
				}

					// Return upload-array:
				return $uploadArray;
			} else return 'Error: Total size of uncompressed upload ('.$totalSize.') exceeds '.t3lib_div::formatSize($this->maxUploadSize);
		}
	}

	/**
	 * Include a locallang file and return the $LOCAL_LANG array serialized.
	 *
	 * @param	string		Absolute path to locallang file to include.
	 * @param	string		Old content of a locallang file (keeping the header content)
	 * @return	array		Array with header/content as key 0/1
	 * @see makeUploadArray()
	 */
	function getSerializedLocalLang($file,$content)	{
		$returnParts = explode('$LOCAL_LANG',$content,2);

		include($file);
		if (is_array($LOCAL_LANG))	{
			$returnParts[1] = serialize($LOCAL_LANG);
			return $returnParts;
		}
	}










	/********************************
	 *
	 * Managing dependencies, conflicts, priorities, load order of extension keys
	 *
	 *******************************/

	/**
	 * Adds extension to extension list and returns new list. If -1 is returned, an error happend.
	 * Checks dependencies etc.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array - information about installed extensions
	 * @return	string		New list of installed extensions or -1 if error
	 * @see showExtDetails()
	 */
	function addExtToList($extKey,$instExtInfo)	{
		global $TYPO3_LOADED_EXT;

			// ext_emconf.php information:
		$conf = $instExtInfo[$extKey]['EM_CONF'];

			// Check dependencies on other extensions:
		if ($conf['dependencies'])	{
			$dep = t3lib_div::trimExplode(',',$conf['dependencies'],1);

			foreach($dep as $depK)	{
				if (!t3lib_extMgm::isLoaded($depK))	{
					if (!isset($instExtInfo[$depK]))	{
						$msg = 'Extension "'.$depK.'" was not available in the system. Please import it from the TYPO3 Extension Repository.';
					} else {
						$msg = 'Extension "'.$depK.'" ('.$instExtInfo[$depK]['EM_CONF']['title'].') was not installed. Please installed it first.';
					}
					$this->content.= $this->doc->section('Dependency Error',$msg,0,1,2);
					return -1;
				}
			}
		}

			// Check conflicts with other extensions:
		if ($conf['conflicts'])	{
			$conflict = t3lib_div::trimExplode(',',$conf['conflicts'],1);

			foreach($conflict as $conflictK)	{
				if (t3lib_extMgm::isLoaded($conflictK))	{
					$msg = 'The extention "'.$extKey.'" and "'.$conflictK.'" ('.$instExtInfo[$conflictK]['EM_CONF']['title'].') will conflict with each other. Please remove "'.$conflictK.'" if you want to install "'.$extKey.'".';
					$this->content.= $this->doc->section('Conflict Error',$msg,0,1,2);
					return -1;
				}
			}
		}

			// Get list of installed extensions and add this one.
		$listArr = array_keys($TYPO3_LOADED_EXT);
		if ($conf['priority']=='top')	{
			array_unshift($listArr,$extKey);
		} else {
			$listArr[]=$extKey;
		}

			// Manage other circumstances:
		$listArr = $this->managesPriorities($listArr,$instExtInfo);
		$listArr = $this->removeRequiredExtFromListArr($listArr);

			// Implode unique list of extensions to load and return:
		$list = implode(',',array_unique($listArr));
		return $list;
	}

	/**
	 * Remove extension key from the list of currently installed extensions and return list. If -1 is returned, an error happend.
	 * Checks dependencies etc.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array - information about installed extensions
	 * @return	string		New list of installed extensions or -1 if error
	 * @see showExtDetails()
	 */
	function removeExtFromList($extKey,$instExtInfo)	{
		global $TYPO3_LOADED_EXT;

			// Initialize:
		$depList = array();
		$listArr = array_keys($TYPO3_LOADED_EXT);

			// Traverse all installed extensions to check if any of them have this extension as dependency since if that is the case it will not work out!
		foreach($listArr as $k => $ext)	{
			if ($instExtInfo[$ext]['EM_CONF']['dependencies'])	{
				$dep = t3lib_div::trimExplode(',',$instExtInfo[$ext]['EM_CONF']['dependencies'],1);
				if (in_array($extKey,$dep))	{
					$depList[] = $ext;
				}
			}
			if (!strcmp($ext,$extKey))	unset($listArr[$k]);
		}

			// Returns either error or the new list
		if (count($depList))	{
			$msg = 'The extension(s) "'.implode(', ',$depList).'" depends on the extension you are trying to remove. The operation was not completed.';
			$this->content.=$this->doc->section('Dependency Error',$msg,0,1,2);
			return -1;
		} else {
			$listArr = $this->removeRequiredExtFromListArr($listArr);
			$list = implode(',',array_unique($listArr));
			return $list;
		}
	}

	/**
	 * This removes any required extensions from the $listArr - they should NOT be added to the common extension list, because they are found already in "requiredExt" list
	 *
	 * @param	array		Array of extension keys as values
	 * @return	array		Modified array
	 * @see removeExtFromList(), addExtToList()
	 */
	function removeRequiredExtFromListArr($listArr)	{
		foreach($listArr as $k => $ext)	{
			if (in_array($ext,$this->requiredExt) || !strcmp($ext,'_CACHEFILE'))	unset($listArr[$k]);
		}
		return $listArr;
	}

	/**
	 * Traverse the array of installed extensions keys and arranges extensions in the priority order they should be in
	 *
	 * @param	array		Array of extension keys as values
	 * @param	array		Extension information array
	 * @return	array		Modified array of extention keys as values
	 * @see addExtToList()
	 */
	function managesPriorities($listArr,$instExtInfo)	{

			// Initialize:
		$levels = array(
			'top' => array(),
			'middle' => array(),
			'bottom' => array(),
		);

			// Traverse list of extensions:
		foreach($listArr as $k => $ext)	{
			$prio = trim($instExtInfo[$ext]['EM_CONF']['priority']);
			switch((string)$prio)	{
				case 'top':
				case 'bottom':
					$levels[$prio][] = $ext;
				break;
				default:
					$levels['middle'][] = $ext;
				break;
			}
		}
		return array_merge(
			$levels['top'],
			$levels['middle'],
			$levels['bottom']
		);
	}










	/*******************************
	 *
	 * System Update functions (based on extension requirements)
	 *
	 ******************************/

	/**
	 * Check if clear-cache should be performed, otherwise show form (for installation of extension)
	 * Shown only if the extension has the clearCacheOnLoad flag set.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML output (if form is shown)
	 */
	function checkClearCache($extKey,$extInfo)	{
		if ($extInfo['EM_CONF']['clearCacheOnLoad'])	{
			if (t3lib_div::_POST('_clear_all_cache'))	{		// Action: Clearing the cache
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values = 0;
				$tce->start(Array(),Array());
				$tce->clear_cacheCmd('all');
			} else {	// Show checkbox for clearing cache:
				$content.= '
					<br />
					<h3>Clear cache</h3>
					<p>This extension requests the cache to be cleared when it is installed/removed.<br />
						Clear all cache: <input type="checkbox" name="_clear_all_cache" checked="checked" value="1" /><br />
						</p>
				';
			}
		}
		return $content;
	}

	/**
	 * Check if upload folder / "createDir" directories should be created.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML content.
	 */
	function checkUploadFolder($extKey,$extInfo)	{

			// Checking for upload folder:
		$uploadFolder = PATH_site.$this->ulFolder($extKey);
		if ($extInfo['EM_CONF']['uploadfolder'] && !@is_dir($uploadFolder))	{
			if (t3lib_div::_POST('_uploadfolder'))	{	// CREATE dir:
				t3lib_div::mkdir($uploadFolder);
				$indexContent = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD>
	<TITLE></TITLE>
<META http-equiv=Refresh Content="0; Url=../../">
</HEAD>
</HTML>';
				t3lib_div::writeFile($uploadFolder.'index.html',$indexContent);
			} else {	// Show checkbox / HTML for creation:
				$content.='
					<br /><h3>Create upload folder</h3>
					<p>The extension requires the upload folder "'.$this->ulFolder($extKey).'" to exist.<br />
				Create directory "'.$this->ulFolder($extKey).'": <input type="checkbox" name="_uploadfolder" checked="checked" value="1" /><br />
				</p>
				';
			}
		}

			// Additional directories that should be created:
		if ($extInfo['EM_CONF']['createDirs'])	{
	 		$createDirs = array_unique(t3lib_div::trimExplode(',',$extInfo['EM_CONF']['createDirs'],1));

			foreach($createDirs as $crDir)	{
				if (!@is_dir(PATH_site.$crDir))	{
					if (t3lib_div::_POST('_createDir_'.md5($crDir)))	{	// CREATE dir:

							// Initialize:
						$crDirStart = '';
						$dirs_in_path = explode('/',ereg_replace('/$','',$crDir));

							// Traverse each part of the dir path and create it one-by-one:
						foreach($dirs_in_path as $dirP)	{
							if (strcmp($dirP,''))	{
								$crDirStart.= $dirP.'/';
								if (!@is_dir(PATH_site.$crDirStart))	{
									t3lib_div::mkdir(PATH_site.$crDirStart);
									$finalDir = PATH_site.$crDirStart;
								}
							} else {
								die('ERROR: The path "'.PATH_site.$crDir.'" could not be created.');
							}
						}
						if ($finalDir)	{
							$indexContent = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD>
	<TITLE></TITLE>
<META http-equiv=Refresh Content="0; Url=/">
</HEAD>
</HTML>';
							t3lib_div::writeFile($finalDir.'index.html',$indexContent);
						}
					} else {	// Show checkbox / HTML for creation:
						$content.='
							<br />
							<h3>Create folder</h3>
							<p>The extension requires the folder "'.$crDir.'" to exist.<br />
						Create directory "'.$crDir.'": <input type="checkbox" name="_createDir_'.md5($crDir).'" checked="checked" value="1" /><br />
						</p>
						';
					}
				}
			}
		}

		return $content;
	}

	/**
	 * Validates the database according to extension requirements
	 * Prints form for changes if any. If none, returns blank. If an update is ordered, empty is returned as well.
	 * DBAL compliant (based on Install Tool code)
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @param	boolean		If true, returns array with info.
	 * @return	mixed		If $infoOnly, returns array with information. Otherwise performs update.
	 */
	function checkDBupdates($extKey,$extInfo,$infoOnly=0)	{

			// Initializing Install Tool object:
		$instObj = new t3lib_install;
		$instObj->INSTALL = t3lib_div::_GP('TYPO3_INSTALL');
		$dbStatus = array();

			// Updating tables and fields?
		if (in_array('ext_tables.sql',$extInfo['files']))	{
			$fileContent = t3lib_div::getUrl($this->getExtPath($extKey,$extInfo['type']).'ext_tables.sql');

			$FDfile = $instObj->getFieldDefinitions_sqlContent($fileContent);
			if (count($FDfile))	{
				$FDdb = $instObj->getFieldDefinitions_database(TYPO3_db);
				$diff = $instObj->getDatabaseExtra($FDfile, $FDdb);
				$update_statements = $instObj->getUpdateSuggestions($diff);

				$dbStatus['structure']['tables_fields'] = $FDfile;
				$dbStatus['structure']['diff'] = $diff;

					// Updating database...
				if (!$infoOnly && is_array($instObj->INSTALL['database_update']))	{
					$instObj->performUpdateQueries($update_statements['add'],$instObj->INSTALL['database_update']);
					$instObj->performUpdateQueries($update_statements['change'],$instObj->INSTALL['database_update']);
					$instObj->performUpdateQueries($update_statements['create_table'],$instObj->INSTALL['database_update']);
				} else {
					$content.=$instObj->generateUpdateDatabaseForm_checkboxes($update_statements['add'],'Add fields');
					$content.=$instObj->generateUpdateDatabaseForm_checkboxes($update_statements['change'],'Changing fields',1,0,$update_statements['change_currentValue']);
					$content.=$instObj->generateUpdateDatabaseForm_checkboxes($update_statements['create_table'],'Add tables');
				}
			}
		}

			// Importing static tables?
		if (in_array('ext_tables_static+adt.sql',$extInfo['files']))	{
			$fileContent = t3lib_div::getUrl($this->getExtPath($extKey,$extInfo['type']).'ext_tables_static+adt.sql');

			$statements = $instObj->getStatementArray($fileContent,1);
			list($statements_table, $insertCount) = $instObj->getCreateTables($statements,1);

				// Execute import of static table content:
			if (!$infoOnly && is_array($instObj->INSTALL['database_import']))	{

					// Traverse the tables
				foreach($instObj->INSTALL['database_import'] as $table => $md5str)	{
					if ($md5str == md5($statements_table[$table]))	{
						$res = $GLOBALS['TYPO3_DB']->admin_query('DROP TABLE IF EXISTS '.$table);
						$res = $GLOBALS['TYPO3_DB']->admin_query($statements_table[$table]);

						if ($insertCount[$table])	{
							$statements_insert = $instObj->getTableInsertStatements($statements, $table);

							foreach($statements_insert as $k => $v)	{
								$res = $GLOBALS['TYPO3_DB']->admin_query($v);
							}
						}
					}
				}
			} else {
				$whichTables = $instObj->getListOfTables();
				if (count($statements_table))	{
					$out = '';
					foreach($statements_table as $table => $definition)	{
						$exist = isset($whichTables[$table]);

						$dbStatus['static'][$table]['exists'] = $exist;
						$dbStatus['static'][$table]['count'] = $insertCount[$table];

						$out.= '<tr>
							<td><input type="checkbox" name="TYPO3_INSTALL[database_import]['.$table.']" checked="checked" value="'.md5($definition).'" /></td>
							<td><strong>'.$table.'</strong></td>
							<td><img src="clear.gif" width="10" height="1" alt="" /></td>
							<td nowrap="nowrap">'.($insertCount[$table]?'Rows: '.$insertCount[$table]:'').'</td>
							<td><img src="clear.gif" width="10" height="1" alt="" /></td>
							<td nowrap="nowrap">'.($exist?'<img src="'.$GLOBALS['BACK_PATH'].'t3lib/gfx/icon_warning.gif" width="18" height="16" align="top" alt="" />Table exists!':'').'</td>
							</tr>';
					}
					$content.= '
						<br />
						<h3>Import static data</h3>
						<table border="0" cellpadding="0" cellspacing="0">'.$out.'</table>';
				}
			}
		}

			// Return array of information if $infoOnly, otherwise content.
		return $infoOnly ? $dbStatus : $content;
	}

	/**
	 * Produces the config form for an extension (if any template file, ext_conf_template.txt is found)
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @param	boolean		If true, the form HTML content is returned, otherwise the content is set in $this->content.
	 * @param	string		Submit-to URL (supposedly)
	 * @param	string		Additional form fields to include.
	 * @return	string		Depending on $output. Can return the whole form.
	 */
	function tsStyleConfigForm($extKey,$extInfo,$output=0,$script='',$addFields='')	{
		global $TYPO3_CONF_VARS;

			// Initialize:
		$absPath = $this->getExtPath($extKey,$extInfo['type']);
		$relPath = $this->typeRelPaths[$extInfo['type']].$extKey.'/';

			// Look for template file for form:
		if (@is_file($absPath.'ext_conf_template.txt'))	{

				// Load tsStyleConfig class and parse configuration template:
			$tsStyleConfig = t3lib_div::makeInstance('t3lib_tsStyleConfig');
			$theConstants = $tsStyleConfig->ext_initTSstyleConfig(
				t3lib_div::getUrl($absPath.'ext_conf_template.txt'),
				$relPath,
				$absPath,
				$GLOBALS['BACK_PATH']
			);

				// Load the list of resources.
			$tsStyleConfig->ext_loadResources($absPath.'res/');

				// Load current value:
			$arr = unserialize($TYPO3_CONF_VARS['EXT']['extConf'][$extKey]);
			$arr = is_array($arr) ? $arr : array();

				// Call processing function for constants config and data before write and form rendering:
			if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/mod/tools/em/index.php']['tsStyleConfigForm']))	{
				$_params = array('fields' => &$theConstants, 'data' => &$arr, 'extKey' => $extKey);
				foreach($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/mod/tools/em/index.php']['tsStyleConfigForm'] as $_funcRef)	{
					t3lib_div::callUserFunction($_funcRef,$_params,$this);
				}
				unset($_params);
			}

				// If saving operation is done:
			if (t3lib_div::_POST('submit'))	{
				$tsStyleConfig->ext_procesInput(t3lib_div::_POST(),array(),$theConstants,array());
				$arr = $tsStyleConfig->ext_mergeIncomingWithExisting($arr);
				$this->writeTsStyleConfig($extKey,$arr);
			}

				// Setting value array
			$tsStyleConfig->ext_setValueArray($theConstants,$arr);

				// Getting session data:
			$MOD_MENU = array();
			$MOD_MENU['constant_editor_cat'] = $tsStyleConfig->ext_getCategoriesForModMenu();
			$MOD_SETTINGS = t3lib_BEfunc::getModuleData($MOD_MENU, t3lib_div::_GP('SET'), 'xMod_test');

				// Resetting the menu (stop)
			if (count($MOD_MENU)>1)	{
				$menu = 'Category: '.t3lib_BEfunc::getFuncMenu(0,'SET[constant_editor_cat]',$MOD_SETTINGS['constant_editor_cat'],$MOD_MENU['constant_editor_cat'],'','&CMD[showExt]='.$extKey);
				$this->content.=$this->doc->section('','<span class="nobr">'.$menu.'</span>');
				$this->content.=$this->doc->spacer(10);
			}

				// Category and constant editor config:
			$form = '
				<table border="0" cellpadding="0" cellspacing="0" width="600">
					<tr>
						<td>'.$tsStyleConfig->ext_getForm($MOD_SETTINGS['constant_editor_cat'],$theConstants,$script,$addFields).'</td>
					</tr>
				</table>';
			if ($output)	{
				return $form;
			} else {
				$this->content.=$this->doc->section('','</form>'.$form.'<form>');
			}
		}
	}










	/*******************************
	 *
	 * Dumping database (MySQL compliant)
	 *
	 ******************************/

	/**
	 * Makes a dump of the tables/fields definitions for an extension
	 *
	 * @param	array		Array with table => field/key definition arrays in
	 * @return	string		SQL for the table definitions
	 * @see dumpStaticTables()
	 */
	function dumpTableAndFieldStructure($arr)	{
		$tables = array();

		if (count($arr))	{

				// Get file header comment:
			$tables[] = $this->dumpHeader();

				// Traverse tables, write each table/field definition:
			foreach($arr as $table => $fieldKeyInfo)	{
				$tables[] = $this->dumpTableHeader($table,$fieldKeyInfo);
			}
		}

			// Return result:
		return implode(chr(10).chr(10).chr(10),$tables);
	}

	/**
	 * Dump content for static tables
	 *
	 * @param	string		Comma list of tables from which to dump content
	 * @return	string		Returns the content
	 * @see dumpTableAndFieldStructure()
	 */
	function dumpStaticTables($tableList)	{
		$instObj = new t3lib_install;
		$dbFields = $instObj->getFieldDefinitions_database(TYPO3_db);

		$out = '';
		$parts = t3lib_div::trimExplode(',',$tableList,1);

			// Traverse the table list and dump each:
		foreach($parts as $table)	{
			if (is_array($dbFields[$table]['fields']))	{
				$dHeader = $this->dumpHeader();
				$header = $this->dumpTableHeader($table,$dbFields[$table],1);
				$insertStatements = $this->dumpTableContent($table,$dbFields[$table]['fields']);

				$out.= $dHeader.chr(10).chr(10).chr(10).
						$header.chr(10).chr(10).chr(10).
						$insertStatements.chr(10).chr(10).chr(10);
			} else {
				die('Fatal error: Table for dump not found in database...');
			}
		}
		return $out;
	}

	/**
	 * Header comments of the SQL dump file
	 *
	 * @return	string		Table header
	 */
	function dumpHeader()	{
		return trim('
# TYPO3 Extension Manager dump 1.1
#
# Host: '.TYPO3_db_host.'    Database: '.TYPO3_db.'
#--------------------------------------------------------
');
	}

	/**
	 * Dump CREATE TABLE definition
	 *
	 * @param	string		Table name
	 * @param	array		Field and key information (as provided from Install Tool class!)
	 * @param	boolean		If true, add "DROP TABLE IF EXISTS"
	 * @return	string		Table definition SQL
	 */
	function dumpTableHeader($table,$fieldKeyInfo,$dropTableIfExists=0)	{
		$lines = array();

			// Create field definitions
		if (is_array($fieldKeyInfo['fields']))	{
			foreach($fieldKeyInfo['fields'] as $fieldN => $data)	{
				$lines[]='  '.$fieldN.' '.$data;
			}
		}

			// Create index key definitions
		if (is_array($fieldKeyInfo['keys']))	{
			foreach($fieldKeyInfo['keys'] as $fieldN => $data)	{
				$lines[]='  '.$data;
			}
		}

			// Compile final output:
		if (count($lines))	{
			return trim('
#
# Table structure for table "'.$table.'"
#
'.($dropTableIfExists ? 'DROP TABLE IF EXISTS '.$table.';
' : '').'CREATE TABLE '.$table.' (
'.implode(','.chr(10),$lines).'
);'
			);
		}
	}

	/**
	 * Dump table content
	 * Is DBAL compliant, but the dump format is written as MySQL standard. If the INSERT statements should be imported in a DBMS using other quoting than MySQL they must first be translated. t3lib_sqlengine can parse these queries correctly and translate them somehow.
	 *
	 * @param	string		Table name
	 * @param	array		Field structure
	 * @return	string		SQL Content of dump (INSERT statements)
	 */
	function dumpTableContent($table,$fieldStructure)	{

			// Substitution of certain characters (borrowed from phpMySQL):
		$search = array('\\', '\'', "\x00", "\x0a", "\x0d", "\x1a");
		$replace = array('\\\\', '\\\'', '\0', '\n', '\r', '\Z');

		$lines = array();

			// Select all rows from the table:
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, '');

			// Traverse the selected rows and dump each row as a line in the file:
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$values = array();
			reset($fieldStructure);
			while(list($field) = each($fieldStructure))	{
				$values[] = isset($row[$field]) ? "'".str_replace($search, $replace, $row[$field])."'" : 'NULL';
			}
			$lines[] = 'INSERT INTO '.$table.' VALUES ('.implode(', ',$values).');';
		}

			// Free DB result:
        $GLOBALS['TYPO3_DB']->sql_free_result($result);

			// Implode lines and return:
		return implode(chr(10),$lines);
	}

	/**
	 * Gets the table and field structure from database.
	 * Which fields and which tables are determined from the ext_tables.sql file
	 *
	 * @param	string		Array with table.field values
	 * @return	array		Array of tables and fields splitted.
	 */
	function getTableAndFieldStructure($parts)	{
			// Instance of install tool
		$instObj = new t3lib_install;
		$dbFields = $instObj->getFieldDefinitions_database(TYPO3_db);


		$outTables = array();
		foreach($parts as $table)	{
			$tP = explode('.',$table);
			if ($tP[0] && isset($dbFields[$tP[0]]))	{
				if ($tP[1])	{
					$kfP = explode('KEY:',$tP[1],2);
					if (count($kfP)==2 && !$kfP[0])	{	// key:
						if (isset($dbFields[$tP[0]]['keys'][$kfP[1]]))	$outTables[$tP[0]]['keys'][$kfP[1]] = $dbFields[$tP[0]]['keys'][$kfP[1]];
					} else {
						if (isset($dbFields[$tP[0]]['fields'][$tP[1]]))	$outTables[$tP[0]]['fields'][$tP[1]] = $dbFields[$tP[0]]['fields'][$tP[1]];
					}
				} else {
					$outTables[$tP[0]] = $dbFields[$tP[0]];
				}
			}
		}

		return $outTables;
	}










	/*******************************
	 *
	 * TER Communication functions
	 *
	 ******************************/

	/**
	 * Fetches data from the $repositoryUrl, un-compresses it, unserializes array and returns an array with the content if success.
	 *
	 * @param	string		Request URL
	 * @return	array		Array with information and statistics.
	 * @see importExtFromRep(), extensionList_import(), importExtInfo()
	 */
	function fetchServerData($repositoryUrl)	{

			// Request data from remote:
		$ps1 = t3lib_div::milliseconds();
		$externalData = t3lib_div::getUrl($repositoryUrl);
		$ps2 = t3lib_div::milliseconds()+1;
#echo $externalData; exit;
#debug(array($externalData));exit;
			// Compile statistics array:
		$stat = Array(
			($ps2-$ps1),
			strlen($externalData),
			'Time: '.($ps2-$ps1).'ms',
			'Size: '.t3liB_div::formatSize(strlen($externalData)),
			'Transfer: '.t3liB_div::formatSize(strlen($externalData) / (($ps2-$ps1)/1000)).'/sec'
		);

			// Decode result and return:
		return $this->decodeServerData($externalData,$stat);
	}

	/**
	 * Decode server data
	 * This is information like the extension list, extension information etc., return data after uploads (new em_conf)
	 *
	 * @param	string		Data stream from remove server
	 * @param	array		Statistics array for request of external data
	 * @return	mixed		On success, returns an array with data array and stats array as key 0 and 1. Otherwise returns error string
	 * @see fetchServerData(), processRepositoryReturnData()
	 */
	function decodeServerData($externalData,$stat=array())	{
		$parts = explode(':',$externalData,4);
		$dat = base64_decode($parts[2]);
		if ($parts[0]==md5($dat))	{
			if ($parts[1]=='gzcompress')	{
				if ($this->gzcompress)	{
					$dat = gzuncompress($dat);
				} else return 'Decoding Error: No decompressor available for compressed content. gzcompress()/gzuncompress() functions are not available!';
			}
			$listArr = unserialize($dat);

			if (is_array($listArr))	{
				return array($listArr,$stat);
			} else {
				return 'Error: Unserialized information was not an array - strange!';
			}
		} else return 'Error: MD5 hashes did not match!';
	}

	/**
	 * Decodes extension upload array.
	 * This kind of data is when an extension is uploaded to TER
	 *
	 * @param	string		Data stream
	 * @return	mixed		Array with result on success, otherwise an error string.
	 */
	function decodeExchangeData($str)	{
		$parts = explode(':',$str,3);
		if ($parts[1]=='gzcompress')	{
			if ($this->gzcompress)	{
				$parts[2] = gzuncompress($parts[2]);
			} else return 'Decoding Error: No decompressor available for compressed content. gzcompress()/gzuncompress() functions are not available!';
		}
		if (md5($parts[2]) == $parts[0])	{
			$output = unserialize($parts[2]);
			if (is_array($output))	{
				return $output;
			} else return 'Error: Content could not be unserialized to an array. Strange (since MD5 hashes match!)';
		} else return 'Error: MD5 mismatch. Maybe the extension file was downloaded and saved as a text file by the browser and thereby corrupted!? (Always select "All" filetype when saving extensions)';
	}

	/**
	 * Encodes extension upload array
	 *
	 * @param	array		Array containing extension
	 * @param	integer		Overriding system setting for compression. 1=compression, 0=no compression.
	 * @return	string		Content stream
	 */
	function makeUploadDataFromArray($uploadArray,$local_gzcompress=-1)	{
		if (is_array($uploadArray))	{
			$serialized = serialize($uploadArray);
			$md5 = md5($serialized);

			$local_gzcompress = ($local_gzcompress>-1)?$local_gzcompress:$this->gzcompress;

			$content = $md5.':';
			if ($local_gzcompress)	{
				$content.= 'gzcompress:';
				$content.= gzcompress($serialized);
			} else {
				$content.= ':';
				$content.= $serialized;
			}
		}
		return $content;
	}

	/**
	 * Compiles the additional GET-parameters sent to the repository during requests for information.
	 *
	 * @return	string		GET parameter for URL
	 * @see importExtFromRep(), extensionList_import(), importExtInfo()
	 */
	function repTransferParams()	{
		return '&tx_extrep[T3instID]='.rawurlencode($this->T3instID()).
			'&tx_extrep[TYPO3_ver]='.rawurlencode($GLOBALS['TYPO_VERSION']).
			'&tx_extrep[PHP_ver]='.rawurlencode(phpversion()).
			'&tx_extrep[returnUrl]='.rawurlencode($this->makeReturnUrl()).
			'&tx_extrep[gzcompress]='.$this->gzcompress.
			'&tx_extrep[user][fe_u]='.$this->fe_user['username'].
			'&tx_extrep[user][fe_p]='.$this->fe_user['password'];
	}

	/**
	 * Returns the return Url of the current script (for repository exchange)
	 *
	 * @return	string		Value of t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
	 * @see repTransferParams()
	 */
	function makeReturnUrl()	{
		return t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
	}

	/**
	 * Returns the unique TYPO3 Install Identification (sent to repository for statistics)
	 *
	 * @return	string		Value of $GLOBALS['TYPO3_CONF_VARS']['SYS']['T3instID'];
	 * @see repTransferParams()
	 */
	function T3instID()	{
		return $GLOBALS['TYPO3_CONF_VARS']['SYS']['T3instID'];
	}

	/**
	 * Processes return-data from online repository.
	 * Currently only the returned emconf array is written to extension.
	 *
	 * @param	array		Command array returned from TER
	 * @return	string		Message
	 */
	function processRepositoryReturnData($TER_CMD)	{
		switch((string)$TER_CMD['cmd'])	{
			case 'EM_CONF':
				list($list)=$this->getInstalledExtensions();
				$extKey = $TER_CMD['extKey'];

				$data = $this->decodeServerData($TER_CMD['returnValue']);
				$EM_CONF = $data[0];
				$EM_CONF['_md5_values_when_last_written'] = serialize($this->serverExtensionMD5Array($extKey,$list[$extKey]));
				$emConfFileContent = $this->construct_ext_emconf_file($extKey,$EM_CONF);
				if (is_array($list[$extKey]) && $emConfFileContent)	{
					$absPath = $this->getExtPath($extKey,$list[$extKey]['type']);
					$emConfFileName = $absPath.'ext_emconf.php';
					if (@is_file($emConfFileName))	{
						t3lib_div::writeFile($emConfFileName,$emConfFileContent);
						return '"'.substr($emConfFileName,strlen($absPath)).'" was updated with a cleaned up EM_CONF array.';
					} else die('Error: No file "'.$emConfFileName.'" found.');
				} else  die('Error: No EM_CONF content prepared...');
			break;
		}
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
	function listOrderTitle($listOrder,$key)	{
		switch($listOrder)	{
			case 'cat':
				return isset($this->categories[$key])?$this->categories[$key]:'<em>['.$key.']</em>';
			break;
			case 'author_company':
				return $key;
			break;
			case 'dep':
				return $key;
			break;
			case 'state':
				return $this->states[$key];
			break;
			case 'private':
				return $key?'Private (Password required to download from repository)':'Public (Everyone can download this from Extention repository)';
			break;
			case 'type':
				return $this->typeDescr[$key];
			break;
		}
	}

	/**
	 * Returns version information
	 *
	 * @param	string		Version code, x.x.x
	 * @param	string		part: "", "int", "main", "sub", "dev"
	 * @return	string
	 * @see renderVersion()
	 */
	function makeVersion($v,$mode)	{
		$vDat = $this->renderVersion($v);
		return $vDat['version_'.$mode];
	}

	/**
	 * Parses the version number x.x.x and returns an array with the various parts.
	 *
	 * @param	string		Version code, x.x.x
	 * @param	string		Increase version part: "main", "sub", "dev"
	 * @return	string
	 */
	function renderVersion($v,$raise='')	{
		$parts = t3lib_div::intExplode('.',$v.'..');
		$parts[0] = t3lib_div::intInRange($parts[0],0,999);
		$parts[1] = t3lib_div::intInRange($parts[1],0,999);
		$parts[2] = t3lib_div::intInRange($parts[2],0,999);

		switch((string)$raise)	{
			case 'main':
				$parts[0]++;
				$parts[1]=0;
				$parts[2]=0;
			break;
			case 'sub':
				$parts[1]++;
				$parts[2]=0;
			break;
			case 'dev':
				$parts[2]++;
			break;
		}

		$res = array();
		$res['version'] = $parts[0].'.'.$parts[1].'.'.$parts[2];
		$res['version_int'] = intval(str_pad($parts[0],3,'0',STR_PAD_LEFT).str_pad($parts[1],3,'0',STR_PAD_LEFT).str_pad($parts[2],3,'0',STR_PAD_LEFT));
		$res['version_main'] = $parts[0];
		$res['version_sub'] = $parts[1];
		$res['version_dev'] = $parts[2];

		return $res;
	}

	/**
	 * Returns upload folder for extension
	 *
	 * @param	string		Extension key
	 * @return	string		Upload folder for extension
	 */
	function ulFolder($extKey)	{
		return 'uploads/tx_'.str_replace('_','',$extKey).'/';
	}

	/**
	 * Returns true if global OR local installation of extensions is allowed/possible.
	 *
	 * @return	boolean		Returns true if global OR local installation of extensions is allowed/possible.
	 */
	function importAtAll()	{
		return ($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowGlobalInstall'] || $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowLocalInstall']);
	}

	/**
	 * Reports back if installation in a certain scope is possible.
	 *
	 * @param	string		Scope: G, L, S
	 * @param	string		Extension lock-type (eg. "L" or "G")
	 * @return	boolean		True if installation is allowed.
	 */
	function importAsType($type,$lockType='')	{
		switch($type)	{
			case 'G':
				return $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowGlobalInstall'] && (!$lockType || !strcmp($lockType,$type));
			break;
			case 'L':
				return $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowLocalInstall'] && (!$lockType || !strcmp($lockType,$type));
			break;
			case 'S':
				return $this->systemInstall;
			break;
		}
	}

	/**
	 * Returns true if extensions in scope, $type, can be deleted (or installed for that sake)
	 *
	 * @param	string		Scope: "G" or "L"
	 * @return	boolean		True if possible.
	 */
	function deleteAsType($type)	{
		switch($type)	{
			case 'G':
				return $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowGlobalInstall'];
			break;
			case 'L':
				return $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowLocalInstall'];
			break;
		}
	}

	/**
	 * Returns true if the doc/manual.sxw should be returned
	 *
	 * @param	string		Extension key
	 * @param	string		Extension install type (L, G, S)
	 * @return	boolean		Returns true if either the TYPO3_CONF_VARS flag for always including manuals are set OR if the manual is ALREADY found for the extension in question.
	 */
	function getDocManual($extension_key,$loc='')	{
		$res = FALSE;
		if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['em_alwaysGetOOManual'])	$res = TRUE;
		if ($loc && $this->typePaths[$loc] && @is_file(PATH_site.$this->typePaths[$loc].$extension_key.'/doc/manual.sxw'))	$res = TRUE;

		return $res;
	}

	/**
	 * Evaluates differences in version numbers with three parts, x.x.x. Returns true if $v1 is greater than $v2
	 *
	 * @param	string		Version number 1
	 * @param	string		Version number 2
	 * @param	integer		Tolerance factor. For instance, set to 1000 to ignore difference in dev-version (third part)
	 * @return	boolean		True if version 1 is greater than version 2
	 */
	function versionDifference($v1,$v2,$div=1)	{
		return floor($this->makeVersion($v1,'int')/$div) > floor($this->makeVersion($v2,'int')/$div);
	}

	/**
	 * Returns true if the $str is found as the first part of a string in $array
	 *
	 * @param	string		String to test with.
	 * @param	array		Input array
	 * @param	boolean		If set, the test is case insensitive
	 * @return	boolean		True if found.
	 */
	function first_in_array($str,$array,$caseInsensitive=FALSE)	{
		if ($caseInsensitive)	$str = strtolower($str);
		if (is_array($array))	{
			foreach($array as $cl)	{
				if ($caseInsensitive)	$cl = strtolower($cl);
				if (t3lib_div::isFirstPartOfStr($cl,$str))	return 1;
			}
		}
	}

	/**
	 * Returns the $EM_CONF array from an extensions ext_emconf.php file
	 *
	 * @param	string		Absolute path to EMCONF file.
	 * @param	string		Extension key.
	 * @return	array		EMconf array values.
	 */
	function includeEMCONF($path,$_EXTKEY)	{
		include($path);

		return $EM_CONF[$_EXTKEY];
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/tools/em/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/tools/em/index.php']);
}









// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_em_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
