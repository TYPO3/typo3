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
 *  153: class em_install_class extends t3lib_install 
 *  155:     function em_install_class()	
 *
 *
 *  178: class SC_mod_tools_em_index 
 *  276:     function init()	
 *  309:     function jumpToUrl(URL)	
 *  333:     function menuConfig()	
 *  410:     function main()	
 *  481:     function printContent()	
 *  495:     function kickstarter()	
 *  512:     function alterSettings()	
 *  532:     function extensionList_loaded()	
 *  568:     function extensionList_import()	
 *  719:     function extensionList_installed()	
 *  796:     function importExtInfo($extRepUid)	
 *  886:     function getDocManual($extension_key,$loc="")	
 *  906:     function importExtFromRep($extRepUid,$loc,$uploadFlag=0,$directInput="",$recentTranslations=0,$incManual=0)	
 * 1055:     function showExtDetails($extKey)	
 * 1300:     function updatesForm($extKey,$info,$notSilent=0,$script="",$addFields="")	
 * 1331:     function extDumpTables($eKey,$info)	
 * 1396:     function extDelete($eKey,$info)	
 * 1422:     function extUpdateEMCONF($eKey,$info)	
 * 1442:     function extMakeNewFromFramework($eKey,$info)	
 * 1463:     function extBackup($eKey,$info)	
 * 1517:     function extBackup_dumpDataTablesLine($tablesArray,$eKey)	
 * 1546:     function extInformationArray($eKey,$info,$remote=0)	
 * 1640:     function extInformationArray_dbReq($techInfo,$tableHeader=0)	
 * 1653:     function extInformationArray_dbInst($dbInst,$current)	
 * 1672:     function wrapEmail($str,$email)	
 * 1685:     function helpCol($key)	
 * 1700:     function getRepositoryUploadForm($eKey,$info)	
 * 1771:     function extensionListRowHeader($bgColor,$cells,$import=0)	
 * 1833:     function extensionListRow($eKey,$eConf,$info,$cells,$bgColor="",$inst_list=array(),$import=0,$altLinkUrl="")	
 * 1933:     function labelInfo($str)	
 * 1944:     function createDirsInPath($dirs,$extDirPath)	
 * 1971:     function removeExtDirectory($removePath,$removeContentOnly=0)	
 * 2031:     function extractDirsFromFileList($files)	
 * 2056:     function clearAndMakeExtensionDir($importedData,$type)	
 * 2102:     function versionDifference($v1,$v2,$div=1)	
 * 2113:     function fetchServerData($repositoryUrl)	
 * 2136:     function decodeServerData($externalData,$stat=array())	
 * 2155:     function addClearCacheFiles()	
 * 2179:     function extensionTitleIconHeader($eKey,$info,$align="top")	
 * 2198:     function makeDetailedExtensionAnalysis($eKey,$info,$validity=0)	
 * 2371:     function getClassIndexLocallangFiles($absPath,$table_class_prefix,$eKey)	
 * 2442:     function first_in_array($str,$array)	
 * 2457:     function modConfFileAnalysis($confFilePath)	
 * 2485:     function writeTYPO3_MOD_PATH($confFilePath,$type,$mP)	
 * 2523:     function tsStyleConfigForm($eKey,$info,$output=0,$script="",$addFields="")	
 * 2573:     function writeTsStyleConfig($eKey,$arr)	
 * 2594:     function dumpStaticTables($tableList)	
 * 2621:     function dumpTableAndFieldStructure($arr)	
 * 2638:     function dumpHeader()	
 * 2655:     function dumpTableHeader($table,$fieldKeyInfo,$dropTableIfExists=0)	
 * 2689:     function dumpTableContent($table,$fieldStructure)	
 * 2715:     function writeNewExtensionList($newExtList)	
 * 2734:     function removeCacheFiles()	
 * 2755:     function checkClearCache($eKey,$info)	
 * 2777:     function checkUploadFolder($eKey,$info)	
 * 2850:     function checkDBupdates($eKey,$info,$infoOnly=0)	
 * 2950:     function findMD5ArrayDiff($current,$past)	
 * 2966:     function removeCVSentries($arr)	
 * 2981:     function serverExtensionMD5Array($extKey,$conf)	
 * 3003:     function makeUploadArray($extKey,$conf)	
 * 3061:     function getSerializedLocalLang($file,$content)	
 * 3078:     function getTableAndFieldStructure($parts)	
 * 3110:     function construct_ext_emconf_file($extKey,$EM_CONF)	
 * 3152:     function decodeExchangeData($str)	
 * 3171:     function makeUploadDataFromArray($uploadArray,$local_gzcompress=-1)	
 * 3197:     function getFileListOfExtension($extKey,$conf)	
 * 3245:     function getAllFilesAndFoldersInPath($fileArr,$extPath,$extList="",$regDirs=0)	
 * 3268:     function removePrefixPathFromList($fileArr,$extPath)	
 * 3285:     function getExtPath($extKey,$conf)	
 * 3301:     function addExtToList($extKey,$list)	
 * 3352:     function removeExtFromList($extKey,$list)	
 * 3385:     function removeRequiredExtFromListArr($listArr)	
 * 3400:     function managesPriorities($listArr,$list)	
 * 3431:     function getInstalledExtensions()	
 * 3456:     function getInstExtList($path,$list,$cat,$type)	
 * 3491:     function getImportExtList($listArr)	
 * 3543:     function setCat($cat,$list,$eKey)	
 * 3566:     function processRepositoryReturnData($TER_CMD)	
 * 3595:     function updateLocalEM_CONF($extKey,$info)	
 * 3618:     function includeEMCONF($path,$_EXTKEY)	
 * 3631:     function listOrderTitle($listOrder,$key)	
 * 3661:     function makeVersion($v,$mode)	
 * 3673:     function renderVersion($v,$raise="")	
 * 3706:     function T3instID()	
 * 3715:     function makeReturnUrl()	
 * 3724:     function repTransferParams()	
 * 3738:     function ulFolder($eKey)	
 * 3747:     function removeButton()	
 * 3756:     function installButton()	
 * 3765:     function importAtAll()	
 * 3774:     function noImportMsg()	
 * 3785:     function importAsType($type,$lockType="")	
 *
 * TOTAL FUNCTIONS: 93
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
class em_install_class extends t3lib_install {
		# Make sure the normal constructor is not called:
	function em_install_class()	{
	}
}
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
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();
	var $doc;	
	
	var $CMD=array();
	
	var $content;
	var $inst_keys=array();

	var $versionDiffFactor = 1000;		// This means that version difference testing for import is detected for sub-versions only. Not dev-versions. Default: 1000
	var $systemInstall = 0;				// If "1" then installs in the sysext directory is allowed. Default: 0
	var $repositoryUrl = "";			// Default is "http://ter.typo3.com/?id=t3_extrep" configured in config_default.php

	var $maxUploadSize = 4024000;		// Max size of plugin upload to repository
	var $kbMax=100;
	var $gzcompress=0;
	var $categories = Array(
		'fe' => 'Frontend',
		'plugin' => 'Frontend Plugins',
		'be' => 'Backend',
		'module' => 'Backend Modules',
		'example' => 'Examples',
		'misc' => 'Miscellaneous',
		'services' => 'Services',
		'templates' => 'Templates',
		'doc' => 'Documentation'
	);
	var $states = Array (
		'alpha' => 'Alpha',
		'beta' => 'Beta',
		'stable' => 'Stable',
		'experimental' => 'Experimental',
		'test' => 'Test',
	);
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
	var $typePaths = Array();
	var $typeBackPaths = Array();
	
	var $typeRelPaths = Array (
		'S' => 'sysext/',
		'G' => 'ext/',
		'L' => '../typo3conf/ext/',
	);
	var $remoteAccess = Array (
		'all' => '',
		'owner' => 'Owner',
		'selected' => 'Selected',
		'member' => 'Member',
	);
	
	var $defaultCategories = Array(
		'cat' => Array (
			'be' => array(),
			'module' => array(),
			'fe' => array(),
			'plugin' => array(),
			'misc' => array(),
			'services' => array(),
			'templates' => array(),
			'example' => array()
		)
	);
	var $detailCols = Array (
		0 => 2,
		1 => 5,
		2 => 6,
		3 => 6,
		4 => 4,
		5 => 1
	);
	var $noCVS=0;	// Tried to set it to 1, but then the CVS dir was removed and check in didn't work - there was an error. So now we try to accept that CVS dirs come along with the extension... Maybe it's not a problem at all.
	
	var $fe_user=array(
			'username' => '',
			'password' => '',
			'uploadPass' => '',
		);
	
	var $privacyNotice = 'When ever you interact with the online repository, server information is sent and stored in the repository for statistics. No personal information is sent, only identification of this TYPO3 install. If you want know exactly what is sent, look in typo3/tools/em/index.php, function repTransferParams()';
	var $editTextExtensions = 'html,htm,txt,css,tmpl,inc,php,sql,conf,cnf,pl,pm,sh';
	var $nameSpaceExceptions = 'beuser_tracking,design_components,impexp,static_file_edit,cms,freesite,quickhelp,classic_welcome,indexed_search,sys_action,sys_workflows,sys_todos,sys_messages,plugin_mgm,direct_mail,sys_stat,tt_address,tt_board,tt_calender,tt_guest,tt_links,tt_news,tt_poll,tt_rating,tt_products,setup,taskcenter,tsconfig_help,context_help,sys_note,tstemplate,lowlevel,install,belog,beuser,phpmyadmin,aboutmodules,imagelist,setup,taskcenter,sys_notepad,viewpage';
	
	/**
	 * Standard init function of a module.
	 * 
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

#sleep(10);

		$this->typePaths = Array (
			'S' => TYPO3_mainDir.'sysext/',
			'G' => TYPO3_mainDir.'ext/',
			'L' => 'typo3conf/ext/'
		);
		$this->typeBackPaths = Array (
			"S" => "../../../",
			"G" => "../../../",
			"L" => "../../../../".TYPO3_mainDir
		);


		$this->MCONF = $GLOBALS["MCONF"];
		$this->CMD=t3lib_div::GPvar("CMD",1);
		$this->menuConfig();
		$this->gzcompress = function_exists('gzcompress');
		if ($TYPO3_CONF_VARS["EXT"]["em_devVerUpdate"])	$this->versionDiffFactor=1;
		if ($TYPO3_CONF_VARS["EXT"]["em_systemInstall"])	$this->systemInstall=1;
		$this->repositoryUrl = $TYPO3_CONF_VARS["EXT"]["em_TERurls"][0];
		
		$this->requiredExt = t3lib_div::trimExplode(",",$TYPO3_CONF_VARS["EXT"]["requiredExt"],1);

		$this->doc = t3lib_div::makeInstance("noDoc");
		$this->doc->backPath = $BACK_PATH;
				// JavaScript
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL)	{
				document.location = URL;
			}
		</script>
		';
		
		$this->doc->form = '<form action="" method="post" name="pageform">';

			// Descriptions:
		$this->descrTable = "_MOD_".$this->MCONF["name"];
		if ($BE_USER->uc["edit_showFieldHelp"])	{
			$LANG->loadSingleTableDescription($this->descrTable);
		}
		
		$this->fe_user["username"] = $this->MOD_SETTINGS["fe_u"];
		$this->fe_user["password"] = $this->MOD_SETTINGS["fe_p"];
		$this->fe_user["uploadPass"] = $this->MOD_SETTINGS["fe_up"];
	}

	/**
	 * Configuration of which mod-menu items can be used
	 * 
	 * @return	[type]		...
	 */
	function menuConfig()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved. 
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			"function" => array(
				0 => 'Loaded extensions',
				1 => 'Available extensions to install',
				2 => 'Import extensions from online repository',
				4 => 'Make new extension',
				3 => 'Settings',
			),
			"listOrder" => array(
				"cat" => 'Category',
				"author_company" => 'Author',
				"state" => 'State',
				"private" => 'Private',
				"type" => 'Type',
				"dep" => 'Dependencies',
			),
			"display_details" => array(
				1 => 'Details',
				0 => 'Description',
				2 => 'More details',

				3 => 'Technical (takes time!)',
				4 => 'Validating (takes time!)',
				5 => 'Changed? (takes time!)',
			),
			"display_shy" => "",
			"own_member_only" => "",
			"singleDetails" => array(
				"info" => "Information",
				"edit" => "Edit files",
				"backup" => "Backup/Delete",
				"dump" => "Dump DB",
				"upload" => "Upload",
				"updateModule" => "UPDATE!",
#				"download" => "Download",
			),
			"fe_u" => "",
			"fe_p" => "",
			"fe_up" => "",
		);
		
			// page/be_user TSconfig settings and blinding of menu-items
		if (!$BE_USER->getTSConfigVal("mod.".$this->MCONF["name"].".allowTVlisting"))	{
			unset($this->MOD_MENU["display_details"][3]);
			unset($this->MOD_MENU["display_details"][4]);
			unset($this->MOD_MENU["display_details"][5]);
		}
			// Remove kickstarter if extension is not loaded:
		if (!t3lib_extMgm::isLoaded("extrep_wizard"))	{
			unset($this->MOD_MENU["function"][4]);
		}
		
			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::GPvar("SET",1), $this->MCONF["name"]);
		if ($this->MOD_SETTINGS["function"]==2)	{
				// If listing from online repository, certain items are removed though:
			unset($this->MOD_MENU["listOrder"]["type"]);
			unset($this->MOD_MENU["listOrder"]["private"]);
			unset($this->MOD_MENU["display_details"][3]);
			unset($this->MOD_MENU["display_details"][4]);
			unset($this->MOD_MENU["display_details"][5]);
			$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::GPvar("SET",1), $this->MCONF["name"]);
		}
	}

	/**
	 * Main function.
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->content.=$this->doc->startPage('Extension Manager');
		$this->content.=$this->doc->header('Extension Manager');
		$this->content.=$this->doc->spacer(5);

		if ($this->CMD['showExt'])	{
				// Show details for a single extension
			$this->showExtDetails($this->CMD['showExt']);
		} elseif ($this->CMD['importExt'] || $this->CMD['uploadExt'])	{
				// Imports and extension from online rep.
			$err = $this->importExtFromRep($this->CMD['importExt'],$this->CMD['loc'],$this->CMD['uploadExt'],'',$this->CMD['transl'],$this->CMD['inc_manual']);
			if ($err)	{
				$this->content.=$this->doc->section('',$GLOBALS['TBE_TEMPLATE']->rfw($err));
			}
		} elseif ($this->CMD['importExtInfo'])	{
				// Gets detailed information of an extension from online rep.
			$this->importExtInfo($this->CMD['importExtInfo']);
		} else {
			$menu = $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.menu').' '.
				t3lib_BEfunc::getFuncMenu(0,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function']);

			if (t3lib_div::inList('0,1,2',$this->MOD_SETTINGS['function']))	{
				$menu.='&nbsp;Order by:&nbsp;'.t3lib_BEfunc::getFuncMenu(0,'SET[listOrder]',$this->MOD_SETTINGS['listOrder'],$this->MOD_MENU['listOrder']).
					'&nbsp;&nbsp;Show:&nbsp;'.t3lib_BEfunc::getFuncMenu(0,'SET[display_details]',$this->MOD_SETTINGS['display_details'],$this->MOD_MENU['display_details']).
					'<br />Display shy extensions:&nbsp;&nbsp;'.t3lib_BEfunc::getFuncCheck(0,'SET[display_shy]',$this->MOD_SETTINGS['display_shy']);
			}

			if ($this->MOD_SETTINGS['function']==2)	{
					$menu.='&nbsp;&nbsp;&nbsp;Get own/member/selected extensions only:&nbsp;&nbsp;'.t3lib_BEfunc::getFuncCheck(0,'SET[own_member_only]',$this->MOD_SETTINGS['own_member_only']);
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

//debug($GLOBALS['HTTP_GET_VARS']);
			
		if ($BE_USER->mayMakeShortcut())	{
			$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('','function',$this->MCONF['name']));
		}
	}

	/**
	 * Print module content. Called as last thing in the global scope.
	 * 
	 * @return	[type]		...
	 */
	function printContent()	{
		global $SOBE;

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
	

	/**
	 * Making of new extensions.
	 * 
	 * @return	[type]		...
	 */
	function kickstarter()	{
		$kickstarter = t3lib_div::makeInstance('em_kickstarter');
		$kickstarter->getPIdata();
		$kickstarter->color = array($this->doc->bgColor5,$this->doc->bgColor4,$this->doc->bgColor);
		$kickstarter->siteBackPath = $this->doc->backPath.'../';
		$kickstarter->pObj = &$this;
		$kickstarter->EMmode=1;

		$content = $kickstarter->mgm_wizard();
		$this->content.='</form>'.$this->doc->section('Kickstarter wizard',$content,0,1).'<form>';
	}
	
	/**
	 * Allows changing of settings
	 * 
	 * @return	[type]		...
	 */
	function alterSettings()	{
		$content = '
		<table border=0 cellpadding=2 cellspacing=2>
			<tr class="bgColor4"><td>Enter repository username:</td><td><input type="text" name="SET[fe_u]" value="'.htmlspecialchars($this->MOD_SETTINGS['fe_u']).'"></td></tr>
			<tr class="bgColor4"><td>Enter repository password:</td><td><input type="password" name="SET[fe_p]" value="'.htmlspecialchars($this->MOD_SETTINGS['fe_p']).'"></td></tr>
			<tr class="bgColor4"><td>Enter default upload password:</td><td><input type="password" name="SET[fe_up]" value="'.htmlspecialchars($this->MOD_SETTINGS['fe_up']).'"></td></tr>
		</table>
		<strong>Notice:</strong> This is <em>not</em> your password to the TYPO3 backend! This user information is what is needed to log in at typo3.org with your account there!<br />
		<br />
		<input type="submit" value="Update">
		';
		
		$this->content.=$this->doc->section('Repository settings',$content,0,1);
	}

	/**
	 * Listing of loaded (installed) extensions
	 * 
	 * @return	[type]		...
	 */
	function extensionList_loaded()	{
		global $TYPO3_LOADED_EXT;
		list($list,$cat)=$this->getInstalledExtensions();

			// Loaded extensions
		$content='';
		$lines=array();
		$lines[]=$this->extensionListRowHeader(' class="bgColor5"',array('<td><img src=clear.gif width=1 height=1></td>'));

		reset($TYPO3_LOADED_EXT);
		while(list($eKey,$eConf)=each($TYPO3_LOADED_EXT))	{
			if (strcmp($eKey,'_CACHEFILE'))	{
				if ($this->MOD_SETTINGS['display_shy'] || !$list[$eKey]['EM_CONF']['shy'])	{
					if (in_array($eKey,$this->requiredExt))	{
						$loadUnloadLink='<strong>'.$GLOBALS['TBE_TEMPLATE']->rfw('Rq').'</strong>';
					} else {
						$loadUnloadLink='<a href="index.php?CMD[showExt]='.$eKey.'&CMD[remove]=1">'.$this->removeButton().'</a>';
					}
	
					$lines[]=$this->extensionListRow($eKey,$eConf,$list[$eKey],array('<td valign=top class="bgColor">'.$loadUnloadLink.'</td>'));
				}
			}
		}

		$content.= '"Loaded extensions" are currently running on the system. This list shows you which extensions are loaded and in which order.<br />"Shy" extensions are also loaded but "hidden" in this list because they are system related and generally you should just leave them alone unless you know what you are doing.<br /><br />';
		$content.= '<table border=0 cellpadding=2 cellspacing=1>'.implode('',$lines).'</table>';

		$this->content.=$this->doc->section('Loaded Extensions',$content,0,1);
		$this->addClearCacheFiles();
	}

	/**
	 * Listing remote extensions from online repository
	 * 
	 * @return	[type]		...
	 */
	function extensionList_import()	{
		global $TYPO3_LOADED_EXT;

		$listRemote = t3lib_div::GPvar('ter_connect');
		
		if ($listRemote)	{
			list($inst_list,$inst_cat)=$this->getInstalledExtensions();
			$this->inst_keys=array_flip(array_keys($inst_list));
	
			$this->detailCols[1]+=6;
	
			$repositoryUrl=$this->repositoryUrl.
				$this->repTransferParams().
				'&tx_extrep[cmd]=currentListing'.
				($this->MOD_SETTINGS['own_member_only']?'&tx_extrep[listmode]=1':'');
			$fetchData = $this->fetchServerData($repositoryUrl);
	//debug($fetchData);
			if (is_array($fetchData))	{
				$listArr=$fetchData[0];
				list($list,$cat) = $this->getImportExtList($listArr);

					// Available extensions
				if (is_array($cat[$this->MOD_SETTINGS['listOrder']]))	{
					$content='';
					$lines=array();
					$lines[]=$this->extensionListRowHeader(' class="bgColor5"',array('<td><img src=clear.gif width=18 height=1></td>'),1);
					
					reset($cat[$this->MOD_SETTINGS['listOrder']]);
					while(list($catName,$extEkeys)=each($cat[$this->MOD_SETTINGS['listOrder']]))	{
						$lines[]='<tr><td colspan='.(3+$this->detailCols[$this->MOD_SETTINGS['display_details']]).'><br /></td></tr>';
						$lines[]='<tr><td colspan='.(3+$this->detailCols[$this->MOD_SETTINGS['display_details']]).'><img src="'.$GLOBALS['BACK_PATH'].'gfx/i/sysf.gif" width="18" height="16" align="top" alt="" /><strong>'.$this->listOrderTitle($this->MOD_SETTINGS['listOrder'],$catName).'</strong></td></tr>';
		
						asort($extEkeys);
						reset($extEkeys);
						while(list($eKey)=each($extEkeys))	{
							if ($this->MOD_SETTINGS["display_shy"] || !$list[$eKey]["EM_CONF"]["shy"])	{
								$loadUnloadLink="";
								if ($inst_list[$eKey]["type"]!="S" && (!isset($inst_list[$eKey]) || $this->versionDifference($list[$eKey]["EM_CONF"]["version"],$inst_list[$eKey]["EM_CONF"]["version"],$this->versionDiffFactor)))	{
									if (isset($inst_list[$eKey]))	{
											// update
										$loc=($inst_list[$eKey]["type"]=="G"?"G":"L");
										$loadUnloadLink.='<a href="index.php?CMD[importExt]='.$list[$eKey]["extRepUid"].'&CMD[loc]='.$loc.($this->getDocManual($eKey,$loc)?'&CMD[inc_manual]=1':'').'"><img src="'.$GLOBALS["BACK_PATH"].'gfx/import_update.gif" width="12" height="12" title="Update the extension in \''.($loc=="G"?"global":"local").'\' from online repository to server." alt="" /></a>';
									} else {
											// import
										$loadUnloadLink.='<a href="index.php?CMD[importExt]='.$list[$eKey]["extRepUid"].'&CMD[loc]=L'.($this->getDocManual($eKey)?'&CMD[inc_manual]=1':'').'"><img src="'.$GLOBALS["BACK_PATH"].'gfx/import.gif" width="12" height="12" title="Import this extension to \'local\' dir typo3conf/ext/ from online repository." alt="" /></a>';
									}
								} else $loadUnloadLink="&nbsp;";
								
								if ($list[$eKey]["_MEMBERS_ONLY"])	{
									$theBgColor = "#F6CA96";
								} elseif (isset($inst_list[$eKey]))	{
									$theBgColor = t3lib_extMgm::isLoaded($eKey)?$this->doc->bgColor4:t3lib_div::modifyHTMLcolor($this->doc->bgColor4,20,20,20);
								} else {
									$theBgColor = t3lib_div::modifyHTMLcolor($this->doc->bgColor2,30,30,30);
								}
								$lines[]=$this->extensionListRow($eKey,array(),$list[$eKey],array('<td valign=top class="bgColor">'.$loadUnloadLink.'</td>'),
												$theBgColor,$inst_list,1,'index.php?CMD[importExtInfo]='.$list[$eKey]["extRepUid"]);
							}
						}
					}
	
					$content.= 'Extensions in this list are online for immediate download from the TYPO3 Extension Repository.<br />
								Extensions with dark background are those already on your server - the others must be imported from the repository to your server before you can use them.<br />
								So if you want to use an extension from the repository, you should simply click the "import" button.<br /><br />';
	
					$content.= '<table border=0 cellpadding=2 cellspacing=1>'.implode("",$lines).'</table>';
	
					$content.= '<br />Data fetched: ['.implode("][",$fetchData[1]).']';
					$content.= '<br /><br /><strong>PRIVACY NOTICE:</strong><br /> '.$this->privacyNotice;
	
					$this->content.=$this->doc->section("Extensions in TYPO3 Extension Repository (online) - Order by: ".$this->MOD_MENU["listOrder"][$this->MOD_SETTINGS["listOrder"]],$content,0,1);
	
					if (!$this->MOD_SETTINGS["own_member_only"])	{
							// Plugins which are NOT uploaded to repository but present on this server.
						$content="";
						$lines=array();
						if (count($this->inst_keys))	{
							$lines[]=$this->extensionListRowHeader(' class="bgColor5"',array('<td><img src=clear.gif width=18 height=1></td>'));
			
							reset($this->inst_keys);
							while(list($eKey)=each($this->inst_keys))	{
								if ($this->MOD_SETTINGS["display_shy"] || !$inst_list[$eKey]["EM_CONF"]["shy"])	{
									$eConf = $TYPO3_LOADED_EXT[$eKey];
									$loadUnloadLink = t3lib_extMgm::isLoaded($eKey)?
										'<a href="index.php?CMD[showExt]='.$eKey.'&CMD[remove]=1&CMD[clrCmd]=1&SET[singleDetails]=info">'.$this->removeButton().'</a>':
										'<a href="index.php?CMD[showExt]='.$eKey.'&CMD[load]=1&CMD[clrCmd]=1&SET[singleDetails]=info">'.$this->installButton().'</a>';
									if (in_array($eKey,$this->requiredExt))	$loadUnloadLink="<strong>".$GLOBALS["TBE_TEMPLATE"]->rfw("Rq")."</strong>";
									$lines[]=$this->extensionListRow($eKey,$eConf,$inst_list[$eKey],array('<td valign=top class="bgColor">'.$loadUnloadLink.'</td>'),
													t3lib_extMgm::isLoaded($eKey)?$this->doc->bgColor4:t3lib_div::modifyHTMLcolor($this->doc->bgColor4,20,20,20));
								}
							}
						}
						
						$content.= 'This is the list of extensions which are either user-defined (should be prepended user_ then) or which are private (and does not show up in the public list above).<br /><br />';
						$content.= '<table border=0 cellpadding=2 cellspacing=1>'.implode("",$lines).'</table>';
						$this->content.=$this->doc->spacer(20);
						$this->content.=$this->doc->section("Extensions found only on this server",$content,0,1);
					}
				}
			}
		} else {
			$content = 'Click here to connect to "'.$this->repositoryUrl.'" and retrieve the list of publicly available plugins from the TYPO3 Extension Repository.<br />';

			if ($this->fe_user["username"])	{
				$content.= '<br /><img src="'.$GLOBALS["BACK_PATH"].'gfx/icon_note.gif" width="18" height="16" align="top" alt="" />Repository username "'.$this->fe_user["username"].'" will be sent as authentication.<br />';
			} else {
				$content.= '<br /><img src="'.$GLOBALS["BACK_PATH"].'gfx/icon_warning2.gif" width="18" height="16" align="top" alt="" />You have not configured a repository username/password yet. Please <a href="index.php?SET[function]=3">go to "Settings"</a> and do that.<br />';
			}

			$onCLick = "document.location='index.php?ter_connect=1';return false;";
			$content.= '<br /><input type="submit" value="Connect to online repository" onClick="'.$onCLick.'">';
#			$content.= '<br /><br /><strong>PRIVACY NOTICE:</strong><br /> '.$this->privacyNotice;
			$this->content.=$this->doc->section("Extensions in TYPO3 Extension Repository",$content,0,1);
		}

			// Private lookup:
		$content= 'Privat lookup key: <input type="text" name="uid_private_key"> Password, if any: <input type="text" name="download_password"><input type="submit" value="Lookup" onClick="document.location=\'index.php?CMD[importExtInfo]=\'+document.pageform.uid_private_key.value+\'&CMD[download_password]=\'+document.pageform.download_password.value; return false;">';
		$this->content.=$this->doc->spacer(20);
		$this->content.=$this->doc->section("Private extension lookup:",$content,0,1);

			// Upload:
		if ($this->importAtAll())	{
			$content= '</form><form action="index.php" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'" method="post">
			Upload extension file (.t3x):<br />
				<input type="file" size="60" name="upload_ext_file"><br />
				... in location:<br />
				<select name="CMD[loc]">';
				if ($this->importAsType("L"))	$content.='<option value="L">Local (../typo3conf/ext/)</option>';
				if ($this->importAsType("G"))	$content.='<option value="G">Global (typo3/ext/)</option>';
				if ($this->importAsType("S"))	$content.='<option value="S">System (typo3/sysext/)</option>';
			$content.='</select><br />
	<input type="checkbox" value="1" name="CMD[uploadOverwrite]"> Overwrite any existing extension!<br />
	<input type="submit" name="CMD[uploadExt]" value="Upload extension file"><br />
			';
			if (!$this->gzcompress)	{
				$content.='<br />'.$GLOBALS["TBE_TEMPLATE"]->rfw("NOTE: No decompression available! Don't upload a compressed extension - it will not succeed.");
			}
		} else $content=$this->noImportMsg();
		
		$this->content.=$this->doc->spacer(20);
		$this->content.=$this->doc->section("Upload extension file directly (.t3x):",$content,0,1);

			// Clear cache thing...
		$this->addClearCacheFiles();
	}

	/**
	 * Listing of available (installed) extensions
	 * 
	 * @return	[type]		...
	 */
	function extensionList_installed()	{
		global $TYPO3_LOADED_EXT;
		list($list,$cat)=$this->getInstalledExtensions();
#debug(strlen(serialize(array($list,$cat))));
#debug(array($list,$cat));
		
			// Available extensions
		if (is_array($cat[$this->MOD_SETTINGS["listOrder"]]))	{
			$content="";
			$lines=array();
			$lines[]=$this->extensionListRowHeader(' class="bgColor5"',array('<td><img src=clear.gif width=18 height=1></td>'));
			
			$allKeys=array();
			reset($cat[$this->MOD_SETTINGS["listOrder"]]);
			while(list($catName,$extEkeys)=each($cat[$this->MOD_SETTINGS["listOrder"]]))	{
				$allKeys[]="";
				$allKeys[]="TYPE: ".$catName;
				
				$lines[]='<tr><td colspan='.(3+$this->detailCols[$this->MOD_SETTINGS["display_details"]]).'><br /></td></tr>';
				$lines[]='<tr><td colspan='.(3+$this->detailCols[$this->MOD_SETTINGS["display_details"]]).'><img src="'.$GLOBALS["BACK_PATH"].'gfx/i/sysf.gif" width="18" height="16" align="top" alt="" /><strong>'.$this->listOrderTitle($this->MOD_SETTINGS["listOrder"],$catName).'</strong></td></tr>';

				asort($extEkeys);
				reset($extEkeys);
				while(list($eKey)=each($extEkeys))	{
					$allKeys[]=$eKey;
					if ($this->MOD_SETTINGS["display_shy"] || !$list[$eKey]["EM_CONF"]["shy"])	{
						$eConf = $TYPO3_LOADED_EXT[$eKey];
						$loadUnloadLink = t3lib_extMgm::isLoaded($eKey)?
							'<a href="index.php?CMD[showExt]='.$eKey.'&CMD[remove]=1&CMD[clrCmd]=1&SET[singleDetails]=info">'.$this->removeButton().'</a>':
							'<a href="index.php?CMD[showExt]='.$eKey.'&CMD[load]=1&CMD[clrCmd]=1&SET[singleDetails]=info">'.$this->installButton().'</a>';
						if (in_array($eKey,$this->requiredExt))	$loadUnloadLink="<strong>".$GLOBALS["TBE_TEMPLATE"]->rfw("Rq")."</strong>";

						if ($list[$eKey]["EM_CONF"]["private"])	{
							$theBgColor = "#F6CA96";
						} else {
							$theBgColor = t3lib_extMgm::isLoaded($eKey)?$this->doc->bgColor4:t3lib_div::modifyHTMLcolor($this->doc->bgColor4,20,20,20);
						}
						$lines[]=$this->extensionListRow($eKey,$eConf,$list[$eKey],array('<td valign=top class="bgColor">'.$loadUnloadLink.'</td>'),
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
			
#debug($this->MOD_SETTINGS["listOrder"]);
			$content.= 'Available extensions are extensions which are present in the extension folders. You can install any of the available extensions in this list. When you install an extension it will be loaded by TYPO3 from that moment.<br />
						In this list the extensions with dark background are installed (loaded) - the others just available (not loaded), ready to be installed on your request.<br />
						So if you want to use an extension in TYPO3, you should simply click the "plus" button '.$this->installButton().' . <br />
						Installed extensions can also be removed again - just click the remove button '.$this->removeButton().' .<br /><br />';
			$content.= '<table border=0 cellpadding=2 cellspacing=1>'.implode("",$lines).'</table>';
			
			$this->content.=$this->doc->section("Available Extensions - Order by: ".$this->MOD_MENU["listOrder"][$this->MOD_SETTINGS["listOrder"]],$content,0,1);
			$this->addClearCacheFiles();
		}
	}

	/**
	 * Returns detailed info about an extension in the online repository
	 * 
	 * @param	[type]		$extRepUid: ...
	 * @return	[type]		...
	 */
	function importExtInfo($extRepUid)	{
		$uidParts = t3lib_div::trimExplode("-",$extRepUid);
		if (count($uidParts)==2)	{
			$extRepUid=$uidParts[0];
			$addParams="&tx_extrep[pKey]=".rawurlencode(trim($uidParts[1]))
						."&tx_extrep[pPass]=".rawurlencode(trim($this->CMD["download_password"]));
			$addImportParams = "&CMD[download_password]=".rawurlencode(trim($this->CMD["download_password"]));
		} else $addParams="";
	
		$content='<a href="index.php" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg($GLOBALS["BACK_PATH"],'gfx/goback.gif','width="14" height="14"').' alt="" /> Go back</a>';
		$this->content.=$this->doc->section("",$content);
		$content="";


		$repositoryUrl=$this->repositoryUrl.
			$this->repTransferParams().
			$addParams.
			"&tx_extrep[cmd]=extensionInfo".
			"&tx_extrep[uid]=".$extRepUid;

		list($fetchData) = $this->fetchServerData($repositoryUrl);
		if (is_array($fetchData["_other_versions"]))	{
			$opt=array();
			$opt[]='<option value=""></option>';
			reset($fetchData["_other_versions"]);
			$selectWasSet=0;
			while(list(,$dat)=each($fetchData["_other_versions"]))	{
				$setSel = ($dat["uid"]==$extRepUid?" SELECTED":"");
				if ($setSel)	$selectWasSet=1;
				$opt[]='<option value="'.$dat["uid"].'"'.$setSel.'>'.$dat["version"].'</option>';
			}
			if (!$selectWasSet && $fetchData["emconf_private"])	{
				$opt[]='<option value="'.$fetchData["uid"].'-'.$fetchData["private_key"].'" SELECTED>'.$fetchData["version"].' (Private)</option>';
			}

			$select='<select name="repUid">'.implode("",$opt).'</select> <input type="submit" value="Load details" onClick="document.location=\'index.php?CMD[importExtInfo]=\'+document.pageform.repUid.options[document.pageform.repUid.selectedIndex].value; return false;"> or<br /><br />';
			if ($this->importAtAll())	{
				$select.='
				<input type="submit" value="Import/Update" onClick="
					document.location=\'index.php?CMD[importExt]=\'
						+document.pageform.repUid.options[document.pageform.repUid.selectedIndex].value
						+\'&CMD[loc]=\'+document.pageform.loc.options[document.pageform.loc.selectedIndex].value
						+\'&CMD[transl]=\'+(document.pageform.transl.checked?1:0)
						+\'&CMD[inc_manual]=\'+(document.pageform.inc_manual.checked?1:0)
						+\''.$addImportParams.'\'; return false;"> to: 
				<select name="loc">'.
					($this->importAsType("G",$fetchData["emconf_lockType"])?'<option value="G">Global: '.$this->typePaths["G"].$fetchData["extension_key"]."/".(@is_dir(PATH_site.$this->typePaths["G"].$fetchData["extension_key"])?" (OVERWRITE)":" (empty)").'</option>':'').
					($this->importAsType("L",$fetchData["emconf_lockType"])?'<option value="L">Local: '.$this->typePaths["L"].$fetchData["extension_key"]."/".(@is_dir(PATH_site.$this->typePaths["L"].$fetchData["extension_key"])?" (OVERWRITE)":" (empty)").'</option>':'').
					($this->importAsType("S",$fetchData["emconf_lockType"])?'<option value="S">System: '.$this->typePaths["S"].$fetchData["extension_key"]."/".(@is_dir(PATH_site.$this->typePaths["S"].$fetchData["extension_key"])?" (OVERWRITE)":" (empty)").'</option>':'').
					#'<option value="fileadmin">'.htmlspecialchars("TEST: fileadmin/_temp_/[extension key name + date]").'</option>'.
				'</select>
				<br /><input type="checkbox" name="transl" value="1">Include most recent translations
				<br /><input type="checkbox" name="inc_manual" value="1"'.($this->getDocManual($fetchData["extension_key"],@is_dir(PATH_site.$this->typePaths["G"].$fetchData["extension_key"])?"G":"L")?' CHECKED':'').'>Include "doc/manual.sxw", if any
				';
			} else $select.=$this->noImportMsg();
			$content.=$select;
			$this->content.=$this->doc->section("Select command",$content,0,1);
		}	

			// Details:
		$extKey = $fetchData["extension_key"];
		list($xList)=$this->getImportExtList(array($fetchData));
		$eInfo=$xList[$extKey];
		$eInfo["_TECH_INFO"]=unserialize($fetchData["techinfo"]);
		$tempFiles=unserialize($fetchData["files"]);

		if (is_array($tempFiles))	{
			reset($tempFiles);
			while(list($fk)=each($tempFiles))	{
				if (!strstr($fk,"/"))	$eInfo["files"][]=$fk;
			}
		}
		
		$content='<strong>'.$fetchData["_ICON"]." &nbsp;".$eInfo["EM_CONF"]["title"].'</strong><br /><br />';
		$content.=$this->extInformationArray($extKey,$eInfo,1);
		$this->content.=$this->doc->spacer(10);
		$this->content.=$this->doc->section("Remote Extension Details:",$content,0,1);

		if (is_array($fetchData["_MESSAGES"]))	{
#			$content = t3lib_div::view_array($fetchData["_MESSAGES"]);
			$content = implode("<HR>",$fetchData["_MESSAGES"]);
			$this->content.=$this->doc->section("Messages from repository server:",$content,0,1,1);
		}
	}
	
	/**
	 * Returns true if the doc/manual.sxw should be returned
	 * 
	 * @param	[type]		$extension_key: ...
	 * @param	[type]		$loc: ...
	 * @return	[type]		...
	 */
	function getDocManual($extension_key,$loc="")	{
		$res=0;
		if ($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["em_alwaysGetOOManual"])	$res=1;
		if ($loc && $this->typePaths[$loc] && @is_file(PATH_site.$this->typePaths[$loc].$extension_key."/doc/manual.sxw"))	$res=1;

#		debug(array($extension_key,$loc,$res));
		return $res;
	}

	/**
	 * Imports an extensions from the online repository
	 * 
	 * @param	[type]		$extRepUid: ...
	 * @param	[type]		$loc: ...
	 * @param	[type]		$uploadFlag: ...
	 * @param	[type]		$directInput: ...
	 * @param	[type]		$recentTranslations: ...
	 * @param	[type]		$incManual: ...
	 * @return	[type]		...
	 */
	function importExtFromRep($extRepUid,$loc,$uploadFlag=0,$directInput="",$recentTranslations=0,$incManual=0)	{
		if (is_array($directInput))	{
			$fetchData=array($directInput,"");
			$loc = !strcmp($loc,"G")?"G":"L";
		} elseif ($uploadFlag)	{
			if ($GLOBALS["HTTP_POST_FILES"]["upload_ext_file"]["tmp_name"])	{

				$uploadedTempFile = t3lib_div::upload_to_tempfile($GLOBALS["HTTP_POST_FILES"]["upload_ext_file"]["tmp_name"]);
				$fileContent = t3lib_div::getUrl($uploadedTempFile);
				t3lib_div::unlink_tempfile($uploadedTempFile);

				$fetchData=array($this->decodeExchangeData($fileContent),"");

				if (is_array($fetchData))	{
					$extKey = $fetchData[0]["extKey"];
					if ($extKey)	{
						if (!$this->CMD["uploadOverwrite"])	{
							$loc = !strcmp($loc,"G")?"G":"L";
							$comingExtPath = PATH_site.$this->typePaths[$loc].$extKey."/";
							if (@is_dir($comingExtPath))	{
#								debug("!");
								return "Extension was already present in '".$comingExtPath."' - and the overwrite flag was not set! So nothing done...";
							}	// ... else go on, install...
						}	// ... else go on, install...
					} else return "No extension key in file. Strange...";
				} else return "Wrong file format. No data recognized.";
			} else return "No file uploaded! Probably the file was too large for PHPs internal limit for uploadable files.";
		} else {
			$uidParts = t3lib_div::trimExplode("-",$extRepUid);
			if (count($uidParts)==2)	{
				$extRepUid=$uidParts[0];
				$addParams="&tx_extrep[pKey]=".rawurlencode(trim($uidParts[1]))
							."&tx_extrep[pPass]=".rawurlencode(trim($this->CMD["download_password"]));
			} else $addParams="";
			
				// If most recent translation should be delivered, send this:
			if ($recentTranslations)	{
				$addParams.="&tx_extrep[transl]=1";
			}
			
				// If manual should be included, send this:
			if ($incManual)	{
				$addParams.="&tx_extrep[inc_manual]=1";
			}
	
			$content='<a href="index.php" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg($GLOBALS["BACK_PATH"],'gfx/goback.gif','width="14" height="14"').' alt="" /> Go back</a>';
			$this->content.=$this->doc->section("",$content);
			$content="";
			
			$repositoryUrl=$this->repositoryUrl.
				$this->repTransferParams().
				$addParams.
				"&tx_extrep[cmd]=importExtension".
				"&tx_extrep[uid]=".$extRepUid;

			$fetchData = $this->fetchServerData($repositoryUrl);
		}		
		

		if ($this->importAsType($loc))	{
			if (is_array($fetchData))	{	// There was some data successfully transferred	
				if ($fetchData[0]["extKey"] && is_array($fetchData[0]["FILES"]))	{
					$extKey = $fetchData[0]["extKey"];
					$EM_CONF=$fetchData[0]["EM_CONF"];
					if (!$EM_CONF["lockType"] || !strcmp($EM_CONF["lockType"],$loc))	{
						$res = $this->clearAndMakeExtensionDir($fetchData[0],$loc);
						if (is_array($res))	{
							$extDirPath = trim($res[0]);
							if ($extDirPath && @is_dir($extDirPath) && substr($extDirPath,-1)=="/")	{

								$emConfFile = $this->construct_ext_emconf_file($extKey,$EM_CONF);
								$dirs = $this->extractDirsFromFileList(array_keys($fetchData[0]["FILES"]));
								
								$res=$this->createDirsInPath($dirs,$extDirPath);
								if (!$res)	{
									$writeFiles = $fetchData[0]["FILES"];
									$writeFiles["ext_emconf.php"]["content"] = $emConfFile;
									$writeFiles["ext_emconf.php"]["content_md5"] = md5($emConfFile);
		
									while(list($theFile,$fileData)=each($writeFiles))	{
										t3lib_div::writeFile($extDirPath.$theFile,$fileData["content"]);
										if (!@is_file($extDirPath.$theFile))	{
											$content.="Error: File '".$extDirPath.$theFile."' could not be created!!!<br />";
										} elseif (md5(t3lib_div::getUrl($extDirPath.$theFile)) != $fileData["content_md5"]) {
											$content.="Error: File '".$extDirPath.$theFile."' MD5 was different from the original files MD5 - so the file is corrupted!<br />";
										} elseif (TYPO3_OS!="WIN") {
										#debug($extDirPath.$theFile,1);
											chmod ($extDirPath.$theFile, 0755);   
										}
									}
									if (!$content)	{
										$content="SUCCESS: ".$extDirPath."<br />";
					
											// Fix TYPO3_MOD_PATH
										$modules = t3lib_div::trimExplode(",",$EM_CONF["module"],1);
										if (count($modules))	{
											reset($modules);
											while(list(,$mD)=each($modules))	{
												$confFileName = $extDirPath.$mD."/conf.php";
												if (@is_file($confFileName))	{
													$content.= $this->writeTYPO3_MOD_PATH($confFileName,$loc,$extKey."/".$mD."/")."<br />";
												} else $content.="Error: Couldn't find '".$confFileName."'"."<br />";
											}
										}
		// NOTICE: I used two hours trying to find out why a script, ext_emconf.php, written twice and in between included by PHP did not update correct the second time. Probably something with PHP-A cache and mtime-stamps. 
		// But this order of the code works.... (using the empty Array with type, EMCONF and files hereunder).
										
										
											// Writing to ext_emconf.php:
										$sEMD5A = $this->serverExtensionMD5Array($extKey,array(
											"type" => $loc,
											"EM_CONF" => array(),
											"files" => array()
										));
										$EM_CONF["_md5_values_when_last_written"] = serialize($sEMD5A);
										$emConfFile = $this->construct_ext_emconf_file($extKey,$EM_CONF);
										t3lib_div::writeFile($extDirPath."ext_emconf.php",$emConfFile);
		
										$content.="ext_emconf.php: ".$extDirPath."ext_emconf.php<br />";
										$content.="Type: ".$loc."<br />";
										if (t3lib_extMgm::isLoaded($extKey))	{
											if ($this->removeCacheFiles())	{
												$content.="Cache-files are removed and will be re-written upon next hit<br />";
											}
											
											list($new_list)=$this->getInstalledExtensions();
											$content.=$this->updatesForm($extKey,$new_list[$extKey],1,"index.php?CMD[showExt]=".$extKey."&SET[singleDetails]=info");
										}
		
										if (is_array($fetchData[0]["_MESSAGES"]))	{
											$content.="<HR><strong>Messages from repository:</strong><br /><br />".implode("<br />",$fetchData[0]["_MESSAGES"]);
										}
									}
								} else $content=$res;
							} else $content = "Error: The extension path '".$extDirPath."' was different than expected...";
						} else $content=$res;
					} else $content = "Error: The extension can only be installed in the path ".$this->typePaths[$EM_CONF["lockType"]]." (lockType=".$EM_CONF["lockType"].")";
				} else $content="Error: No extension key!!! Why? - nobody knows... (Or no files in the file-array...)";
			}  else $content="Error: The datatransfer did not succeed...";
		}  else $content="Error: Installation is not allowed in this path (".$this->typePaths[$loc].")";

		$this->content.=$this->doc->section("Extension copied to server",$content,0,1);
	}

	/**
	 * Display extensions details.
	 * 
	 * @param	[type]		$extKey: ...
	 * @return	[type]		...
	 */
	function showExtDetails($extKey)	{
		global $TYPO3_LOADED_EXT;
		
		list($list,$cat)=$this->getInstalledExtensions();
		$absPath = $this->getExtPath($extKey,$list[$extKey]);
		
			// Check updateModule:
		if (@is_file($absPath.'class.ext_update.php'))	{
			require_once($absPath.'class.ext_update.php');
			$updateObj = new ext_update;
			if (!$updateObj->access())	{
				unset($this->MOD_MENU["singleDetails"]['updateModule']);
			}
		} else {
			unset($this->MOD_MENU["singleDetails"]['updateModule']);
		}

			// Function menu here:
		$content='<table border=0 cellpadding=0 cellspacing=0 width="100%"><tr><td nowrap>Extension:&nbsp;<strong>'.$this->extensionTitleIconHeader($extKey,$list[$extKey],"absmiddle").'</strong> ('.$extKey.')</td><td align=right nowrap>'.
				t3lib_BEfunc::getFuncMenu(0,"SET[singleDetails]",$this->MOD_SETTINGS["singleDetails"],$this->MOD_MENU["singleDetails"],"","&CMD[showExt]=".$extKey)." &nbsp; &nbsp; ".
				'<a href="index.php" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/goback.gif','width="14" height="14"').' class="absmiddle" alt="" /> Go back</a></td></tr></table>';
		$this->content.=$this->doc->section("",$content);

		if ($list[$extKey])	{
			if (($this->CMD["remove"] || $this->CMD["load"]) && !in_array($extKey,$this->requiredExt))	{
				if (t3lib_extMgm::isLocalconfWritable())	{
					if ($this->CMD["remove"])	{
						$newExtList=$this->removeExtFromList($extKey,$list);
					} else {
						$newExtList=$this->addExtToList($extKey,$list);
					}
					if ($newExtList!=-1)	{
						$updates="";
						if ($this->CMD["load"])	{
							$updates=$this->updatesForm($extKey,$list[$extKey],1,"",'<input type="hidden" name="_do_install" value="1"><input type="hidden" name="_clrCmd" value="'.$this->CMD["clrCmd"].'">');
							if ($updates)	{
								$updates = 'Before the extension can be installed the database needs to be updated with new tables or fields. Please select which operations to perform:'.$updates;
								$this->content.=$this->doc->section("Installing ".$this->extensionTitleIconHeader($extKey,$list[$extKey]).strtoupper(": Database needs to be updated"),$updates,1,1,1,1);
							}
#							$updates.=$this->checkDBupdates($extKey,$list[$extKey]);
#							$updates.= $this->checkClearCache($extKey,$list[$extKey]); 
#							$updates.= $this->checkUploadFolder($extKey,$list[$extKey]); 
/*							if ($updates)	{
								$updates='
								Before the extension can be installed the database needs to be updated with new tables or fields. Please select which operations to perform:
								</form><form action="'.t3lib_div::linkThisScript().'" method="post">'.$updates.'
								<br /><input type="submit" name="write" value="Update database and install extension">
								<input type="hidden" name="_do_install" value="1">
								';
								$this->content.=$this->doc->section("Installing ".$this->extensionTitleIconHeader($extKey,$list[$extKey]).strtoupper(": Database needs to be updated"),$updates,1,1,1);
							}
	*/					} elseif ($this->CMD["remove"]) {
							$updates.= $this->checkClearCache($extKey,$list[$extKey]); 
							if ($updates)	{
								$updates='
								</form><form action="'.t3lib_div::linkThisScript().'" method="post">'.$updates.'
								<br /><input type="submit" name="write" value="Remove extension">
								<input type="hidden" name="_do_install" value="1">
								<input type="hidden" name="_clrCmd" value="'.$this->CMD["clrCmd"].'">
								';
								$this->content.=$this->doc->section("Installing ".$this->extensionTitleIconHeader($extKey,$list[$extKey]).strtoupper(": Database needs to be updated"),$updates,1,1,1,1);
							}
						}
						if (!$updates || t3lib_div::GPvar("_do_install")) {
							$this->writeNewExtensionList($newExtList);
		
		
							/*
							$content = $newExtList;
							$this->content.=$this->doc->section("Active status","
							<strong>Extension list is written to localconf.php!</strong><br />
							It may be necessary to reload TYPO3 depending on the change.<br />
							
							<em>(".$content.")</em>",0,1);
							*/
							if ($this->CMD["clrCmd"] || t3lib_div::GPvar("_clrCmd"))	{
								$vA = array("CMD"=>"");
							} else {
								$vA = array("CMD"=>Array("showExt"=>$extKey));
							}
							header("Location: ".t3lib_div::linkThisScript($vA));
						}
					}
				} else {
					$this->content.=$this->doc->section("Installing ".$this->extensionTitleIconHeader($extKey,$list[$extKey]).strtoupper(": Write access error"),"typo3conf/localconf.php seems not to be writable, so the extension cannot be installed automatically!",1,1,2);
				}
			} elseif ($this->CMD["downloadFile"] && !in_array($extKey,$this->requiredExt))	{
				$dlFile = $this->CMD["downloadFile"];
				if (t3lib_div::isFirstPartOfStr($dlFile,PATH_site) && t3lib_div::isFirstPartOfStr($dlFile,$absPath) && @is_file($dlFile))	{
					$mimeType = "application/octet-stream";
					Header("Content-Type: ".$mimeType);
					Header("Content-Disposition: attachment; filename=".basename($dlFile));
					echo t3lib_div::getUrl($dlFile);
					exit;
				} else die("error....");
			} elseif ($this->CMD["editFile"] && !in_array($extKey,$this->requiredExt))	{
				$editFile = $this->CMD["editFile"];
				if (t3lib_div::isFirstPartOfStr($editFile,PATH_site) && t3lib_div::isFirstPartOfStr($editFile,$absPath))	{	// Paranoia...
	
					$fI=t3lib_div::split_fileref($editFile);
					if (@is_file($editFile) && t3lib_div::inList($this->editTextExtensions,$fI["fileext"]))	{
						if (filesize($editFile)<($this->kbMax*1024))	{
							$outCode="";
							$info="";
							$submittedContent = t3lib_div::GPvar("edit",1);
							$saveFlag=0;
							if(isset($submittedContent["file"]))	{
								$info.=$GLOBALS["TBE_TEMPLATE"]->rfw("<br /><strong>File saved.</strong>")."<br />";
								$oldFileContent = t3lib_div::getUrl($editFile);
								$info.='MD5: <b>'.md5(str_replace(chr(13),"",$oldFileContent)).'</b> (Previous File)<br />';
								if (!$GLOBALS["TYPO3_CONF_VARS"]["EXT"]["noEdit"])	{
									t3lib_div::writeFile($editFile,$submittedContent["file"]);
									$saveFlag=1;
								} else die("Saving disabled!!!");
							}

							$fileContent = t3lib_div::getUrl($editFile);
							$numberOfRows = 35;

							$outCode.='File: <b>'.substr($editFile,strlen($absPath)).'</b> ('.t3lib_div::formatSize(filesize($editFile)).')<br />';
							$info.='MD5: <b>'.md5(str_replace(chr(13),"",$fileContent)).'</b> (File)<br />';
							if($saveFlag)	$info.='MD5: <b>'.md5(str_replace(chr(13),"",$submittedContent["file"])).'</b> (Saved)<br />';
							$outCode.='<textarea name="edit[file]" rows="'.$numberOfRows.'" wrap="off"'.$this->doc->formWidthText(48,"width:98%;height:70%","off").'>'.t3lib_div::formatForTextarea($fileContent).'</textarea>';
							$outCode.='<input type="Hidden" name="edit[filename]" value="'.$editFile.'">';
							$outCode.='<input type="Hidden" name="CMD[editFile]" value="'.htmlspecialchars($editFile).'">';
							$outCode.='<input type="Hidden" name="CMD[showExt]" value="'.$extKey.'">';
							$outCode.=$info;
							if (!$GLOBALS["TYPO3_CONF_VARS"]["EXT"]["noEdit"])	{
								$outCode.='<br /><input type="submit" name="save_file" value="Save file">';
							} else $outCode.=$GLOBALS["TBE_TEMPLATE"]->rfw('<br />[SAVING IS DISABLED - can be enabled by the TYPO3_CONF_VARS[EXT][noEdit]-flag] ');
							$outCode.='<input type="submit" name="cancel" value="Cancel" onClick="document.location=\'index.php?CMD[showExt]='.$extKey.'\';return false;">';
							$theOutput.=$this->doc->spacer(15);
							$theOutput.=$this->doc->section("Edit file:","",0,1);
							$theOutput.=$this->doc->sectionEnd().$outCode;
							$this->content.=$theOutput;
						} else {
							$theOutput.=$this->doc->spacer(15);
							$theOutput.=$this->doc->section('<font color=red>Filesize exceeded '.$this->kbMax.' Kbytes</font>','Files larger than '.$this->kbMax.' KBytes are not allowed to be edited.');
						}
					}
				} else die("Fatal Edit error: File '".$editFile."' was not inside the correct path of the TYPO3 Extension!");
			} else {	// MAIN:
			
				switch((string)$this->MOD_SETTINGS["singleDetails"])	{
					case "info":
							// Loaded / Not loaded:
						if (!in_array($extKey,$this->requiredExt))	{
							if ($TYPO3_LOADED_EXT[$extKey])	{
								$content = '<strong>The extension is installed (loaded and running)!</strong><br />'.
											'<a href="index.php?CMD[showExt]='.$extKey.'&CMD[remove]=1">Click here to remove the extension: '.$this->removeButton().'</a>';
							} else {
								$content = 'The extension is <strong>not</strong> installed yet.<br />'.
											'<a href="index.php?CMD[showExt]='.$extKey.'&CMD[load]=1">Click here to install the extension: '.$this->installButton().'</a>';
							}
						} else {
							$content = 'This extension is entered in the TYPO3_CONF_VARS[SYS][requiredExt] list and is therefore always loaded.';
						}
						$this->content.=$this->doc->spacer(10);
						$this->content.=$this->doc->section("Active status:",$content,0,1);

						if (t3lib_extMgm::isLoaded($extKey))	{
							$updates=$this->updatesForm($extKey,$list[$extKey]);
							if ($updates)	{
								$this->content.=$this->doc->spacer(10);
								$this->content.=$this->doc->section("Update needed:",$updates."<br /><br />Notice: 'Static data' may not <em>need</em> to be updated. You will only have to import static data each time you upgrade the extension.",0,1);
							}
						}

							// Show details:
						$content = $this->extInformationArray($extKey,$list[$extKey]);
						$this->content.=$this->doc->spacer(10);
						$this->content.=$this->doc->section("Details:",$content,0,1);

								// Config:
						if (@is_file($absPath."ext_conf_template.txt"))	{
							$this->content.=$this->doc->spacer(10);
							$this->content.=$this->doc->section("Configuration:","(<em>Notice: You may need to clear the cache after configuration of the extension. This is required if the extension adds TypoScript depending on these settings.</em>)<br /><br />",0,1);
							$this->tsStyleConfigForm($extKey,$list[$extKey]);
						}
					break;
					case "upload":
						$TER_CMD = t3lib_div::GPvar("TER_CMD",1);
						if (is_array($TER_CMD))	{
							$msg = $this->processRepositoryReturnData($TER_CMD);
							if ($msg)	{
								$this->content.=$this->doc->section("Local update of EM_CONF",$msg,0,1,1);
								$this->content.=$this->doc->spacer(10);
							}
								// Must reload this, because EM_CONF information has been updated!
							list($list,$cat)=$this->getInstalledExtensions();
						}
					
					
							// Upload:
						if (substr($extKey,0,5)!="user_")	{
							$content = $this->getRepositoryUploadForm($extKey,$list[$extKey]);
							$eC=0;
						} else {
							$content="The extensions has an extension key prefixed 'user_' which indicates that it is a user-defined extension with no official unique identification. Therefore it cannot be uploaded.<br />
							You are encouraged to register a unique extension key for all your TYPO3 extensions - even if the project is current not official.";
							$eC=2;
						}
						$this->content.=$this->doc->section("Upload extension to repository",$content,0,1,$eC);
					break;
					case "download":
					break;
					case "backup":
						$content = $this->extBackup($extKey,$list[$extKey]);
						$this->content.=$this->doc->section("Backup",$content,0,1);

						$content = $this->extDelete($extKey,$list[$extKey]);
						$this->content.=$this->doc->section("Delete",$content,0,1);
						
						$content = $this->extUpdateEMCONF($extKey,$list[$extKey]);
						$this->content.=$this->doc->section("Update EM_CONF",$content,0,1);

						$content = $this->extMakeNewFromFramework($extKey,$list[$extKey]);
						if ($content)	$this->content.=$this->doc->section("Make new extension",$content,0,1);
					break;
					case "dump":
						$this->extDumpTables($extKey,$list[$extKey]);
					break;
					case "edit":
							// Files:
						$content = $this->getFileListOfExtension($extKey,$list[$extKey]);
						$this->content.=$this->doc->section("Extension files",$content,0,1);
					break;
					case "updateModule":
						$this->content.=$this->doc->section("Update:",$updateObj->main(),0,1);
					break;
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$extKey: ...
	 * @param	[type]		$info: ...
	 * @param	[type]		$notSilent: ...
	 * @param	[type]		$script: ...
	 * @param	[type]		$addFields: ...
	 * @return	[type]		...
	 */
	function updatesForm($extKey,$info,$notSilent=0,$script="",$addFields="")	{
		$script = $script ? $script : t3lib_div::linkThisScript();
		$updates.=$this->checkDBupdates($extKey,$info);
		$uCache = $this->checkClearCache($extKey,$info); 
		if ($notSilent)	$updates.= $uCache;
		$updates.= $this->checkUploadFolder($extKey,$info); 

		$absPath = $this->getExtPath($extKey,$info);
		if ($notSilent && @is_file($absPath."ext_conf_template.txt"))	{
			$cForm=$this->tsStyleConfigForm($extKey,$info,1,$script,$updates.$addFields."<br />");
		}
		
		if ($updates || $cForm)	{
			if ($cForm)	{
				$updates = '</form>'.$cForm.'<form>';
			} else {
				$updates='</form><form action="'.$script.'" method="post">'.$updates.$addFields.'
					<br /><input type="submit" name="write" value="Make updates">
				';
			}
		}
		return $updates;
	}
	
	/**
	 * Dumping static tables and table/fields structures...
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @return	[type]		...
	 */
	function extDumpTables($eKey,$info)	{
			// Get dbInfo which holds the structure known from the tables.sql file
		$techInfo=$this->makeDetailedExtensionAnalysis($eKey,$info);
		$absPath = $this->getExtPath($eKey,$info);

#debug($techInfo);
		if (is_array($techInfo["static"]))	{
			if ($this->CMD["writeSTATICdump"])	{
				$writeFile = $absPath."ext_tables_static+adt.sql";
				if (@is_file($writeFile))	{
					$dump_static = $this->dumpStaticTables(implode(",",$techInfo["static"]));
					t3lib_div::writeFile($writeFile,$dump_static);
					$this->content.=$this->doc->section("Table and field structure required",t3lib_div::formatSize(strlen($dump_static))."bytes written to ".substr($writeFile,strlen(PATH_site)),0,1);
				}
			} else {
				$msg = "Dumping table content for static tables:<br />";
				$msg.= "<br />".implode("<br />",$techInfo["static"])."<br />";
				
					// ... then feed that to this function which will make new CREATE statements of the same fields but based on the current database content.
				$this->content.=$this->doc->section("Static tables",$msg.'<HR><strong><a href="index.php?CMD[showExt]='.$eKey.'&CMD[writeSTATICdump]=1">Write current static table contents to ext_tables_static+adt.sql now!</a></strong>',0,1);
				$this->content.=$this->doc->spacer(20);
			}							
		}
		
		if (is_array($techInfo["dump_tf"]))	{
			$dump_tf_array = $this->getTableAndFieldStructure($techInfo["dump_tf"]);
			$dump_tf = $this->dumpTableAndFieldStructure($dump_tf_array);
			if ($this->CMD["writeTFdump"])	{
				$writeFile = $absPath."ext_tables.sql";
				if (@is_file($writeFile))	{
					t3lib_div::writeFile($writeFile,$dump_tf);
					$this->content.=$this->doc->section("Table and field structure required",t3lib_div::formatSize(strlen($dump_tf))."bytes written to ".substr($writeFile,strlen(PATH_site)),0,1);
				}
			} else {
				$msg = "Dumping current database structure for:<br />";
				if (is_array($techInfo["tables"]))	{
					$msg.= "<br /><strong>Tables:</strong><br />".implode("<br />",$techInfo["tables"])."<br />";
				}
				if (is_array($techInfo["fields"]))	{
					$msg.= "<br /><strong>Solo-fields:</strong><br />".implode("<br />",$techInfo["fields"])."<br />";
				}
				
					// ... then feed that to this function which will make new CREATE statements of the same fields but based on the current database content.
				$this->content.=$this->doc->section("Table and field structure required",$msg.'<HR><strong><a href="index.php?CMD[showExt]='.$eKey.'&CMD[writeTFdump]=1">Write this dump to ext_tables.sql now!</a></strong><HR>
				<pre>'.htmlspecialchars($dump_tf).'</pre>',0,1);
				

				$details = '							This dump is based on two factors:<br />
				<ul>
				<li>1) All tablenames in ext_tables.sql which are <em>not</em> found in the "modify_tables" list in ext_emconf.php are dumped with the current database structure.</li>
				<li>2) For any tablenames which <em>are</em> listed in "modify_tables" all fields and keys found for the table in ext_tables.sql will be re-dumped with the fresh equalents from the database.</li>
				</ul>
				Bottomline is: Whole tables are dumped from database with no regard to which fields and keys are defined in ext_tables.sql. But for tables which are only modified, any NEW fields added to the database must in some form or the other exist in the ext_tables.sql file as well.<br />';
				$this->content.=$this->doc->section("",$details);
			}							
		}
	}

	/**
	 * Delete extension...
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @return	[type]		...
	 */
	function extDelete($eKey,$info)	{
		$absPath = $this->getExtPath($eKey,$info);
		if (t3lib_extMgm::isLoaded($eKey))	{
			return "This extension is currently installed (loaded and active) and so cannot be deleted!";
		} elseif (!$this->deleteAsType($info["type"])) {
			return "You cannot delete (and install/update) extensions in the ".$this->typeLabels[$info["type"]].' scope.';
		} elseif (t3lib_div::inList("G,L",$info["type"])) {
			if ($this->CMD["doDelete"] && !strcmp($absPath,$this->CMD["absPath"])) {
				$res = $this->removeExtDirectory($absPath);
				@rmdir($absPath);
				if ($res) return "ERROR: Could not remove extension directory '".$absPath."'";
				return "Removed extension in path '".$absPath."'!";
			} else {
				$onClick="if (confirm('Are you sure you want to delete this extension from the server?')) {document.location='index.php?CMD[showExt]=".$eKey."&CMD[doDelete]=1&CMD[absPath]=".rawurlencode($absPath)."';}";
				$content.= '<a href="#" onClick="'.$onClick.' return false;"><strong>DELETE EXTENSION FROM SERVER</strong> (in the "'.$this->typeLabels[$info["type"]].'" location "'.substr($absPath,strlen(PATH_site)).'")!</a>';
				$content.= '<br /><br />(Maybe you should make a backup first, see above.)';
				return $content;
			}
		} else return "Extension is not a global or local extension and cannot be removed.";
	}

	/**
	 * Update extension EM_CONF...
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @return	[type]		...
	 */
	function extUpdateEMCONF($eKey,$info)	{
		$absPath = $this->getExtPath($eKey,$info);
		if ($this->CMD["doUpdateEMCONF"]) {
			return $this->updateLocalEM_CONF($eKey,$info);
		} else {
			$onClick="if (confirm('Are you sure you want to update EM_CONF?')) {document.location='index.php?CMD[showExt]=".$eKey."&CMD[doUpdateEMCONF]=1';}";
			$content.= '<a href="#" onClick="'.$onClick.' return false;"><strong>Update extension EM_CONF file</strong> (in the "'.$this->typeLabels[$info["type"]].'" location "'.substr($absPath,strlen(PATH_site)).'")!</a>';
			$content.= '<br /><br />If files are changed, added or removed to an extension this is normally detected and displayed so you know that this extension has been locally altered and may need to be uploaded or at least not overridden.<br />
						Updating this file will first of all reset this registration.';
			return $content;
		}
	}
	
	/**
	 * make from framework
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @return	[type]		...
	 */
	function extMakeNewFromFramework($eKey,$info)	{
		$absPath = $this->getExtPath($eKey,$info);
		if (isset($this->MOD_MENU["function"][4]) && @is_file($absPath."doc/wizard_form.dat"))	{
			$content = "The file '".substr($absPath."doc/wizard_form.dat",strlen(PATH_site))."' contains the data which this extension was originally made from with the 'Kickstarter' wizard.<br />Pressing this button will allow you to create another extension based on the that framework.<br /><br />";
			$content.= '</form>
				<form action="index.php?SET[function]=4" method="post">
					<input type="submit" value="Start new">
					<input type="hidden" name="tx_extrep[wizArray_ser]" value="'.base64_encode(t3lib_div::getUrl($absPath."doc/wizard_form.dat")).'">
				</form>
			<form>';
			return $content;
		}
	}
		
	/**
	 * Makes Backup files
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @return	[type]		...
	 */
	function extBackup($eKey,$info)	{
		$uArr = $this->makeUploadArray($eKey,$info);
		if (is_array($uArr))	{
			$local_gzcompress = $this->gzcompress && !$this->CMD["dontCompress"];
			$backUpData = $this->makeUploadDataFromArray($uArr,intval($local_gzcompress));
			$filename="T3X_".$eKey."-".str_replace(".","_",$info["EM_CONF"]["version"]).($local_gzcompress?"-z":"").".t3x";
			if (intval($this->CMD["doBackup"])==1)	{
#die(md5($backUpData));

				$mimeType = "application/octet-stream";
				Header("Content-Type: ".$mimeType);
				Header("Content-Disposition: attachment; filename=".$filename);

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
			} elseif ($this->CMD["dumpTables"])	{
				$filename="T3X_".$eKey;
				$cTables = count(explode(",",$this->CMD["dumpTables"]));
				if ($cTables>1)	{
					$filename.='-'.$cTables.'tables';
				} else {
					$filename.='-'.$this->CMD["dumpTables"];
				}
				$filename.="+adt.sql";

				$mimeType = "application/octet-stream";
				Header("Content-Type: ".$mimeType);
				Header("Content-Disposition: attachment; filename=".$filename);
				echo $this->dumpStaticTables($this->CMD["dumpTables"]);
				exit;
			} else {	
				$techInfo = $this->makeDetailedExtensionAnalysis($eKey,$info);
//								if ($techInfo["tables"]||$techInfo["static"]||$techInfo["fields"])	{
#debug($techInfo);
				$lines=array();
				$lines[]='<tr class="bgColor5"><td colspan=2><strong>Make selection:</strong></td></tr>';
				$lines[]='<tr class="bgColor4"><td><strong>Extension files:</strong></td><td>'.
					'<a href="index.php?CMD[doBackup]=1&CMD[showExt]='.$eKey.'">Download extension "'.$eKey.'" as a file</a><br />('.$filename.', '.t3lib_div::formatSize(strlen($backUpData)).', MD5: '.md5($backUpData).')<br />'.
					($this->gzcompress ? '<br /><a href="index.php?CMD[doBackup]=1&CMD[dontCompress]=1&CMD[showExt]='.$eKey.'">(Click here to download extension without compression.)</a>':'').
					'</td></tr>';

				if (is_array($techInfo["tables"]))	{	$lines[]='<tr class="bgColor4"><td><strong>Data tables:</strong></td><td>'.$this->extBackup_dumpDataTablesLine($techInfo["tables"],$eKey).'</td></tr>';	}
				if (is_array($techInfo["static"]))	{	$lines[]='<tr class="bgColor4"><td><strong>Static tables:</strong></td><td>'.$this->extBackup_dumpDataTablesLine($techInfo["static"],$eKey).'</td></tr>';	}

				$content='<table border=0 cellpadding=2 cellspacing=2>'.implode("",$lines).'</table>';
				return $content;
			}
		} else die("Error...");
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$tablesArray: ...
	 * @param	[type]		$eKey: ...
	 * @return	[type]		...
	 */
	function extBackup_dumpDataTablesLine($tablesArray,$eKey)	{
		reset($tablesArray);
		$tables=array();
		$tablesNA=array();
		while(list(,$tN)=each($tablesArray))	{
			$q="SELECT count(*) FROM ".$tN;
			$res = @mysql(TYPO3_db,$q);
			if (!mysql_error())	{
				$row=mysql_fetch_row($res);
				$tables[$tN]='<tr><td>&nbsp;</td><td><a href="index.php?CMD[dumpTables]='.rawurlencode($tN).'&CMD[showExt]='.$eKey.'" title="Dump table \''.$tN.'\'">'.$tN."</a></td><td>&nbsp;&nbsp;&nbsp;</td><td>".$row[0]." records</td></tr>";
			} else {
				$tablesNA[$tN]='<tr><td>&nbsp;</td><td>'.$tN."</td><td>&nbsp;</td><td>Did not exist.</td></tr>";
			}
		}
		$label = '<table border=0 cellpadding=0 cellspacing=0>'.implode("",array_merge($tables,$tablesNA)).'</table>';// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
		if (count($tables))	{
			$label = '<a href="index.php?CMD[dumpTables]='.rawurlencode(implode(",",array_keys($tables))).'&CMD[showExt]='.$eKey.'" title="Dump all existing tables.">Download all data from:</a><br /><br />'.$label;
		} else $label = 'Nothing to dump...<br /><br />'.$label;
		return $label;
	}

	/**
	 * Prints a table with extension information in it.
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @param	[type]		$remote: ...
	 * @return	[type]		...
	 */
	function extInformationArray($eKey,$info,$remote=0)	{
		$lines=array();
		$lines[]='<tr class="bgColor5"><td colspan=2><strong>General information:</strong></td>'.$this->helpCol("").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Title:</td><td>'.$info["EM_CONF"]["_icon"].$info["EM_CONF"]["title"].'</td>'.$this->helpCol("title").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Description:</td><td>'.nl2br(htmlspecialchars($info["EM_CONF"]["description"])).'</td>'.$this->helpCol("description").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Author:</td><td>'.
			$this->wrapEmail($info["EM_CONF"]["author"].
			($info["EM_CONF"]["author_email"]?htmlspecialchars(" <".$info["EM_CONF"]["author_email"].">"):""),$info["EM_CONF"]["author_email"]).
			($info["EM_CONF"]["author_company"]?", ".$info["EM_CONF"]["author_company"]:"").
			'</td>'.$this->helpCol("description").'</tr>';

		$lines[]='<tr class="bgColor4"><td>Version:</td><td>'.$info["EM_CONF"]["version"].'</td>'.$this->helpCol("version").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Category:</td><td>'.$this->categories[$info["EM_CONF"]["category"]].'</td>'.$this->helpCol("category").'</tr>';
		$lines[]='<tr class="bgColor4"><td>State:</td><td>'.$this->states[$info["EM_CONF"]["state"]].'</td>'.$this->helpCol("state").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Shy?</td><td>'.($info["EM_CONF"]["shy"]?"Yes":"").'</td>'.$this->helpCol("shy").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Internal?</td><td>'.($info["EM_CONF"]["internal"]?"Yes":"").'</td>'.$this->helpCol("internal").'</tr>';

		$lines[]='<tr class="bgColor4"><td>Dependencies:</td><td>'.$info["EM_CONF"]["dependencies"].'</td>'.$this->helpCol("dependencies").'</tr>';
		if (!$remote)	{
			$lines[]='<tr class="bgColor4"><td>Conflicts:</td><td>'.$info["EM_CONF"]["conflicts"].'</td>'.$this->helpCol("conflicts").'</tr>';
			$lines[]='<tr class="bgColor4"><td>Priority:</td><td>'.$info["EM_CONF"]["priority"].'</td>'.$this->helpCol("priority").'</tr>';
			$lines[]='<tr class="bgColor4"><td>Clear cache?</td><td>'.($info["EM_CONF"]["clearCacheOnLoad"]?"Yes":"").'</td>'.$this->helpCol("clearCacheOnLoad").'</tr>';
			$lines[]='<tr class="bgColor4"><td>Includes modules:</td><td>'.$info["EM_CONF"]["module"].'</td>'.$this->helpCol("module").'</tr>';
		}
		$lines[]='<tr class="bgColor4"><td>Lock Type?</td><td>'.($info["EM_CONF"]["lockType"]?$info["EM_CONF"]["lockType"]:"").'</td>'.$this->helpCol("lockType").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Modifies tables:</td><td>'.$info["EM_CONF"]["modify_tables"].'</td>'.$this->helpCol("modify_tables").'</tr>';

		$lines[]='<tr class="bgColor4"><td>Private?</td><td>'.($info["EM_CONF"]["private"]?"Yes":"").'</td>'.$this->helpCol("private").'</tr>';
		if (!$remote)	$lines[]='<tr class="bgColor4"><td>Download password:</td><td>'.$info["EM_CONF"]["download_password"].'</td>'.$this->helpCol("download_password").'</tr>';

			// Installation status:
		$lines[]='<tr><td>&nbsp;</td><td></td>'.$this->helpCol("").'</tr>';
		$lines[]='<tr class="bgColor5"><td colspan=2><strong>Installation status:</strong></td>'.$this->helpCol("").'</tr>';
		if (!$remote)	{
			$lines[]='<tr class="bgColor4"><td>Type of install:</td><td>'.$this->typeLabels[$info["type"]].' - <em>'.$this->typeDescr[$info["type"]].'</em></td>'.$this->helpCol("type").'</tr>';
			$lines[]='<tr class="bgColor4"><td>Double installs?</td><td>'.$this->extInformationArray_dbInst($info["doubleInstall"],$info["type"]).'</td>'.$this->helpCol("doubleInstall").'</tr>';
		}
		if (is_array($info["files"]))	{
			sort($info["files"]);
			$lines[]='<tr class="bgColor4"><td>Root files:</td><td>'.implode("<br />",$info["files"]).'</td>'.$this->helpCol("rootfiles").'</tr>';
		}

		if (!$remote)	{
			$techInfo = $this->makeDetailedExtensionAnalysis($eKey,$info,1);
		} else $techInfo = $info["_TECH_INFO"];
#debug($techInfo);

		if ($techInfo["tables"]||$techInfo["static"]||$techInfo["fields"])	{
			if (!$remote && t3lib_extMgm::isLoaded($eKey))	{
				$tableStatus = $GLOBALS["TBE_TEMPLATE"]->rfw(($techInfo["tables_error"]?"<strong>Table error!</strong><br />Probably one or more required fields/tables are missing in the database!":"").
					($techInfo["static_error"]?"<strong>Static table error!</strong><br />The static tables are missing or empty!":""));
			} else {
				$tableStatus = $techInfo["tables_error"]||$techInfo["static_error"] ? "The database will need to be updated when this extension is installed." : "All required tables are already in the database!";
			}
		}

		$lines[]='<tr class="bgColor4"><td>Database requirements:</td><td>'.$this->extInformationArray_dbReq($techInfo,1).'</td>'.$this->helpCol("dbReq").'</tr>';
		if (!$remote)	$lines[]='<tr class="bgColor4"><td>Database status:</td><td>'.$tableStatus.'</td>'.$this->helpCol("dbStatus").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Flags:</td><td>'.(is_array($techInfo["flags"])?implode("<br />",$techInfo["flags"]):"").'</td>'.$this->helpCol("flags").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Config template?</td><td>'.($techInfo["conf"]?"Yes":"").'</td>'.$this->helpCol("conf").'</tr>';
		$lines[]='<tr class="bgColor4"><td>TypoScript files:</td><td>'.(is_array($techInfo["TSfiles"])?implode("<br />",$techInfo["TSfiles"]):"").'</td>'.$this->helpCol("TSfiles").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Language files:</td><td>'.(is_array($techInfo["locallang"])?implode("<br />",$techInfo["locallang"]):"").'</td>'.$this->helpCol("locallang").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Upload folder:</td><td>'.($techInfo["uploadfolder"]?$techInfo["uploadfolder"]:"").'</td>'.$this->helpCol("uploadfolder").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Create directories:</td><td>'.(is_array($techInfo["createDirs"])?implode("<br />",$techInfo["createDirs"]):"").'</td>'.$this->helpCol("createDirs").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Module names:</td><td>'.(is_array($techInfo["moduleNames"])?implode("<br />",$techInfo["moduleNames"]):"").'</td>'.$this->helpCol("moduleNames").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Class names:</td><td>'.(is_array($techInfo["classes"])?implode("<br />",$techInfo["classes"]):"").'</td>'.$this->helpCol("classNames").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Errors:</td><td>'.(is_array($techInfo["errors"])?$GLOBALS["TBE_TEMPLATE"]->rfw(implode("<HR>",$techInfo["errors"])):"").'</td>'.$this->helpCol("errors").'</tr>';
		$lines[]='<tr class="bgColor4"><td>Naming errors:</td><td>'.(is_array($techInfo["NSerrors"])?
				(!t3lib_div::inList($this->nameSpaceExceptions,$eKey)?t3lib_div::view_array($techInfo["NSerrors"]):$GLOBALS["TBE_TEMPLATE"]->dfw("[exception]"))
				:"").'</td>'.$this->helpCol("NSerrors").'</tr>';
				

		if (!$remote)	{
			$currentMd5Array = $this->serverExtensionMD5Array($eKey,$info);
			$affectedFiles="";

			$msgLines=array();
#			$msgLines[] = "Files: ".count($currentMd5Array);
			if (strcmp($info["EM_CONF"]["_md5_values_when_last_written"],serialize($currentMd5Array)))	{
				$msgLines[] = $GLOBALS["TBE_TEMPLATE"]->rfw("<br /><strong>A difference between the originally installed version and the current was detected!</strong>");
				$affectedFiles = $this->findMD5ArrayDiff($currentMd5Array,unserialize($info["EM_CONF"]["_md5_values_when_last_written"]));
				if (count($affectedFiles))	$msgLines[] = "<br /><strong>Modified files:</strong><br />".$GLOBALS["TBE_TEMPLATE"]->rfw(implode("<br />",$affectedFiles));
			}
			$lines[]='<tr class="bgColor4"><td>Files changed?</td><td>'.implode("<br />",$msgLines).'</td>'.$this->helpCol("filesChanged").'</tr>';
		}
				
		return '<table border=0 cellpadding=1 cellspacing=2>'.implode("",$lines).'</table>';
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$techInfo: ...
	 * @param	[type]		$tableHeader: ...
	 * @return	[type]		...
	 */
	function extInformationArray_dbReq($techInfo,$tableHeader=0)	{
		return nl2br(trim((is_array($techInfo["tables"])?($tableHeader?"\n\n<strong>Tables:</strong>\n":"").implode("\n",$techInfo["tables"]):"").
				(is_array($techInfo["static"])?"\n\n<strong>Static tables:</strong>\n".implode("\n",$techInfo["static"]):"").
				(is_array($techInfo["fields"])?"\n\n<strong>Additional fields:</strong>\n".implode("<HR>",$techInfo["fields"]):"")));
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$dbInst: ...
	 * @param	[type]		$current: ...
	 * @return	[type]		...
	 */
	function extInformationArray_dbInst($dbInst,$current)	{
		if (strlen($dbInst)>1)	{
			$others=array();
			for($a=0;$a<strlen($dbInst);$a++)	{
				if (substr($dbInst,$a,1)!=$current)	{
					$others[]="'".$this->typeLabels[substr($dbInst,$a,1)]."'";
				}
			}
			return $GLOBALS["TBE_TEMPLATE"]->rfw("A ".implode(" and ",$others)." extension with this key is also available on the server, but cannot be loaded because the '".$this->typeLabels[$current]."' version takes precendence.");
		} else return "";
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$str: ...
	 * @param	[type]		$email: ...
	 * @return	[type]		...
	 */
	function wrapEmail($str,$email)	{
		if ($email)	{
			$str='<a href="mailto:'.$email.'">'.$str.'</a>';
		}
		return $str;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$key: ...
	 * @return	[type]		...
	 */
	function helpCol($key)	{
		global $BE_USER;
		if ($BE_USER->uc["edit_showFieldHelp"])	{	
			$hT = trim(t3lib_BEfunc::helpText($this->descrTable,"emconf_".$key,$this->doc->backPath));
			return '<td>'.($hT?$hT:t3lib_BEfunc::helpTextIcon($this->descrTable,"emconf_".$key,$this->doc->backPath)).'</td>';
		}
	}
	
	/**
	 * Prints the upload form for extensions
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @return	[type]		...
	 */
	function getRepositoryUploadForm($eKey,$info)	{
		$uArr = $this->makeUploadArray($eKey,$info);
		if (is_array($uArr))	{
			$backUpData = $this->makeUploadDataFromArray($uArr);
			
#debug($this->decodeExchangeData($backUpData));
			$content.='Extension "'.$this->extensionTitleIconHeader($eKey,$info).'" is ready to be uploaded.<br />
			The size of the upload is <strong>'.t3lib_div::formatSize(strlen($backUpData)).'</strong><br />
			';
			
			$b64data = base64_encode($backUpData);
			$content='</form><form action="'.$this->repositoryUrl.'" method="post" enctype="application/x-www-form-urlencoded">
			<input type="hidden" name="tx_extrep[upload][returnUrl]" value="'.htmlspecialchars($this->makeReturnUrl()).'">
			<input type="hidden" name="tx_extrep[upload][data]" value="'.$b64data.'">
			<input type="hidden" name="tx_extrep[upload][typo3ver]" value="'.$GLOBALS["TYPO_VERSION"].'">
			<input type="hidden" name="tx_extrep[upload][os]" value="'.TYPO3_OS.'">
			<input type="hidden" name="tx_extrep[upload][sapi]" value="'.php_sapi_name().'">
			<input type="hidden" name="tx_extrep[upload][phpver]" value="'.phpversion().'">
			<input type="hidden" name="tx_extrep[upload][gzcompressed]" value="'.$this->gzcompress.'">
			<input type="hidden" name="tx_extrep[upload][data_md5]" value="'.md5($b64data).'">
			<table border=0 cellpadding=2 cellspacing=1>
			<tr class="bgColor4"><td>Repository Username:</td><td><input'.$this->doc->formWidth(20).' type="text" name="tx_extrep[user][fe_u]" value="'.$this->fe_user["username"].'"></td></tr>
			<tr class="bgColor4"><td>Repository Password:</td><td><input'.$this->doc->formWidth(20).' type="password" name="tx_extrep[user][fe_p]" value="'.$this->fe_user["password"].'"></td></tr>
			<tr class="bgColor4"><td>Upload password for this extension:</td><td><input'.$this->doc->formWidth(30).' type="password" name="tx_extrep[upload][upload_p]" value="'.$this->fe_user["uploadPass"].'"></td></tr>
			<tr class="bgColor4"><td>Comment to the upload:</td><td><textarea'.$this->doc->formWidth(30,1).' rows="5" name="tx_extrep[upload][comment]"></textarea></td></tr>
			<tr class="bgColor4"><td>Upload command:</td><td nowrap>
				<input type="radio" name="tx_extrep[upload][mode]" value="new_dev" checked> New development version (latest x.x.<strong>'.$GLOBALS["TBE_TEMPLATE"]->rfw("x+1").'</strong>)<br />
				<input type="radio" name="tx_extrep[upload][mode]" value="latest"> Override <em>this</em> development version ('.$info["EM_CONF"]["version"].')<br />
				<input type="radio" name="tx_extrep[upload][mode]" value="new_sub"> New sub version (latest x.<strong>'.$GLOBALS["TBE_TEMPLATE"]->rfw("x+1").'</strong>.0)<br />
				<input type="radio" name="tx_extrep[upload][mode]" value="new_main"> New main version (latest <strong>'.$GLOBALS["TBE_TEMPLATE"]->rfw("x+1").'</strong>.0.0)<br />
			</td></tr>
			<tr class="bgColor4"><td>Private?</td><td>
				<input type="checkbox" name="tx_extrep[upload][private]" value="1"'.($info["EM_CONF"]["private"]?" CHECKED":"").'>Yes, dont show <em>this upload</em> in the public list.<br />
				("Private" uploads requires you to manually enter a special key (which will be shown to you after the upload has been completed) to be able to import and view details for the upload. This is nice when you are working on something internally which you do not want others to look at.)<br />
				<br /><strong>Additional import password:</strong><br />
				<input'.$this->doc->formWidth(20).' type="text" name="tx_extrep[upload][download_password]" value="'.htmlspecialchars(trim($info["EM_CONF"]["download_password"])).'"> (Textfield!) <br />
				(Anybody who knows the "special key" assigned to the private upload will be able to import it. Specifying an import password allows you to give away the download key for private uploads and also require a password given in addition. The password can be changed later on.)<br />
			</td></tr>
			<tr class="bgColor4"><td>&nbsp;</td><td><input type="submit" name="submit" value="Upload extension"><br />
				'.t3lib_div::formatSize(strlen($b64data)).($this->gzcompress?", compressed":"").', base64<br />
				<br />
				Clicking "Save as file" will allow you to save the extension as a file. This provides you with a backup copy of your extension which can be imported later if needed. "Save as file" ignores the information entered in this form!
			</td></tr>
			</table>
			';

			return $content;
		} else {
			return $uArr;
		}
	}












	/**
	 * Prints the header row for the various listings
	 * 
	 * @param	[type]		$bgColor: ...
	 * @param	[type]		$cells: ...
	 * @param	[type]		$import: ...
	 * @return	[type]		...
	 */
	function extensionListRowHeader($bgColor,$cells,$import=0)	{
		$cells[]='<td></td>';
		$cells[]='<td nowrap><strong>Title:</strong></td>';

		if (!$this->MOD_SETTINGS["display_details"])	{
			$cells[]='<td nowrap><strong>Description:</strong></td>';
			$cells[]='<td nowrap><strong>Author:</strong></td>';
		} elseif ($this->MOD_SETTINGS["display_details"]==2)	{
			$cells[]='<td nowrap><strong>Priority:</strong></td>';
			$cells[]='<td nowrap><strong>Mod.Tables:</strong></td>';
			$cells[]='<td nowrap><strong>Modules:</strong></td>';
			$cells[]='<td nowrap><strong>Cl.Cache?</strong></td>';
			$cells[]='<td nowrap><strong>Internal?</strong></td>';
			$cells[]='<td nowrap><strong>Shy?</strong></td>';
		} elseif ($this->MOD_SETTINGS["display_details"]==3)	{
			$cells[]='<td nowrap><strong>Tables/Fields:</strong></td>';
			$cells[]='<td nowrap><strong>TS-files:</strong></td>';
			$cells[]='<td nowrap><strong>Affects:</strong></td>';
			$cells[]='<td nowrap><strong>Modules:</strong></td>';
			$cells[]='<td nowrap><strong>Config?</strong></td>';
			$cells[]='<td nowrap><strong>Errors:</strong></td>';
		} elseif ($this->MOD_SETTINGS["display_details"]==4)	{
			$cells[]='<td nowrap><strong>locallang:</strong></td>';
			$cells[]='<td nowrap><strong>Classes:</strong></td>';
			$cells[]='<td nowrap><strong>Errors:</strong></td>';
			$cells[]='<td nowrap><strong>NameSpace Errors:</strong></td>';
		} elseif ($this->MOD_SETTINGS["display_details"]==5)	{
			$cells[]='<td nowrap><strong>Changed files:</strong></td>';
		} else {
			$cells[]='<td nowrap><strong>Extension key:</strong></td>';
			$cells[]='<td nowrap><strong>Version:</strong></td>';
			if (!$import) {
				$cells[]='<td><strong>Doc:</strong></td>';
				$cells[]='<td nowrap><strong>Type:</strong></td>';
			} else {
				$cells[]='<td nowrap class="bgColor6"'.$this->labelInfo("Current version of the extension on this server. If colored red there is a newer version in repository! Then you should upgrade.").'><strong>Cur. Ver:</strong></td>';
				$cells[]='<td nowrap class="bgColor6"'.$this->labelInfo("Current type of installation of the extension on this server.").'><strong>Cur. Type:</strong></td>';
				$cells[]='<td nowrap'.$this->labelInfo("If blank, everyone has access to this extension. 'Owner' means that you see it ONLY because you are the owner. 'Member' means you see it ONLY because you are among the project members.").'><strong>Access:</strong></td>';
				$cells[]='<td nowrap'.$this->labelInfo("TYPO3 version of last uploading server.").'><strong>T3 ver:</strong></td>';
				$cells[]='<td nowrap'.$this->labelInfo("PHP version of last uploading server.").'><strong>PHP:</strong></td>';
				$cells[]='<td nowrap'.$this->labelInfo("Size of extension, uncompressed / compressed").'><strong>Size:</strong></td>';
				$cells[]='<td nowrap'.$this->labelInfo("Number of downloads, all versions/this version").'><strong>DL:</strong></td>';
			}
			$cells[]='<td nowrap><strong>State:</strong></td>';
			$cells[]='<td nowrap><strong>Dependencies:</strong></td>';
		}
		return '<tr'.$bgColor.'>'.implode('',$cells).'</tr>';
	}

	/**
	 * Prints a row with data for the various extension listings
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$eConf: ...
	 * @param	[type]		$info: ...
	 * @param	[type]		$cells: ...
	 * @param	[type]		$bgColor: ...
	 * @param	[type]		$inst_list: ...
	 * @param	[type]		$import: ...
	 * @param	[type]		$altLinkUrl: ...
	 * @return	[type]		...
	 */
	function extensionListRow($eKey,$eConf,$info,$cells,$bgColor="",$inst_list=array(),$import=0,$altLinkUrl="")	{
		$imgInfo = @getImageSize($this->getExtPath($eKey,$info)."/ext_icon.gif");
		$style = t3lib_extMgm::isLoaded($eKey) ? '' : ' style="color:#666666;"';

		if (is_array($imgInfo))	{
			$cells[]='<td valign=top><img src="'.$GLOBALS["BACK_PATH"].$this->typeRelPaths[$info["type"]].$eKey."/ext_icon.gif".'" '.$imgInfo[3].'></td>';
		} elseif ($info["_ICON"]) {
			$cells[]='<td valign=top>'.$info["_ICON"].'</td>';
		} else {
			$cells[]='<td><img src="clear.gif" width=1 height=1></td>';
		}
		$cells[]='<td nowrap valign=top><a href="'.($altLinkUrl?$altLinkUrl:"index.php?CMD[showExt]=".$eKey."&SET[singleDetails]=info").'" title="'.$eKey.'"'.$style.'>'.t3lib_div::fixed_lgd($info["EM_CONF"]["title"]?$info["EM_CONF"]["title"]:"<em>".$eKey."</em>",40).'</a></td>';

		if (isset($inst_list[$eKey]))	{
			unset($this->inst_keys[$eKey]);
		}
	
		if (!$this->MOD_SETTINGS["display_details"])	{
			$cells[]='<td valign=top>'.t3lib_div::fixed_lgd(htmlspecialchars($info["EM_CONF"]["description"]),400).'<br /><img src=clear.gif width=300 height=></td>';
			$cells[]='<td nowrap valign=top>'.$info["EM_CONF"]["author"].($info["EM_CONF"]["author_company"]?"<br />".$info["EM_CONF"]["author_company"]:"").'</td>';
		} elseif ($this->MOD_SETTINGS["display_details"]==2)	{
			$cells[]="<td nowrap valign=top>".$info["EM_CONF"]["priority"]."</td>";
			$cells[]="<td nowrap valign=top>".implode("<br />",t3lib_div::trimExplode(",",$info["EM_CONF"]["modify_tables"],1))."</td>";
			$cells[]="<td nowrap valign=top>".$info["EM_CONF"]["module"]."</td>";
			$cells[]="<td nowrap valign=top>".($info["EM_CONF"]["clearCacheOnLoad"]?"Yes":"")."</td>";
			$cells[]="<td nowrap valign=top>".($info["EM_CONF"]["internal"]?"Yes":"")."</td>";
			$cells[]="<td nowrap valign=top>".($info["EM_CONF"]["shy"]?"Yes":"")."</td>";
		} elseif ($this->MOD_SETTINGS["display_details"]==3)	{
			$techInfo=$this->makeDetailedExtensionAnalysis($eKey,$info);
			
			$cells[]="<td valign=top>".$this->extInformationArray_dbReq($techInfo).
				"</td>";
			$cells[]="<td nowrap valign=top>".(is_array($techInfo["TSfiles"])?implode("<br />",$techInfo["TSfiles"]):"")."</td>";
			$cells[]="<td nowrap valign=top>".(is_array($techInfo["flags"])?implode("<br />",$techInfo["flags"]):"")."</td>";
			$cells[]="<td nowrap valign=top>".(is_array($techInfo["moduleNames"])?implode("<br />",$techInfo["moduleNames"]):"")."</td>";
			$cells[]="<td nowrap valign=top>".($techInfo["conf"]?"Yes":"")."</td>";
			$cells[]="<td valign=top>".
				$GLOBALS["TBE_TEMPLATE"]->rfw((t3lib_extMgm::isLoaded($eKey)&&$techInfo["tables_error"]?"<strong>Table error!</strong><br />Probably one or more required fields/tables are missing in the database!":"").
				(t3lib_extMgm::isLoaded($eKey)&&$techInfo["static_error"]?"<strong>Static table error!</strong><br />The static tables are missing or empty!":"")).
				"</td>";
		} elseif ($this->MOD_SETTINGS["display_details"]==4)	{
			$techInfo=$this->makeDetailedExtensionAnalysis($eKey,$info,1);
			
			$cells[]="<td valign=top>".(is_array($techInfo["locallang"])?implode("<br />",$techInfo["locallang"]):"")."</td>";
			$cells[]="<td valign=top>".(is_array($techInfo["classes"])?implode("<br />",$techInfo["classes"]):"")."</td>";
			$cells[]="<td valign=top>".(is_array($techInfo["errors"])?$GLOBALS["TBE_TEMPLATE"]->rfw(implode("<HR>",$techInfo["errors"])):"")."</td>";
			$cells[]="<td valign=top>".(is_array($techInfo["NSerrors"])?
				(!t3lib_div::inList($this->nameSpaceExceptions,$eKey)?t3lib_div::view_array($techInfo["NSerrors"]):$GLOBALS["TBE_TEMPLATE"]->dfw("[exception]"))
				:"")."</td>";
		} elseif ($this->MOD_SETTINGS["display_details"]==5)	{
#			$techInfo=$this->makeDetailedExtensionAnalysis($eKey,$info,1);
			$currentMd5Array = $this->serverExtensionMD5Array($eKey,$info);
			$affectedFiles="";
			$msgLines=array();
			$msgLines[] = "Files: ".count($currentMd5Array);
			if (strcmp($info["EM_CONF"]["_md5_values_when_last_written"],serialize($currentMd5Array)))	{
				$msgLines[] = $GLOBALS["TBE_TEMPLATE"]->rfw("<br /><strong>A difference between the originally installed version and the current was detected!</strong>");
				$affectedFiles = $this->findMD5ArrayDiff($currentMd5Array,unserialize($info["EM_CONF"]["_md5_values_when_last_written"]));
				if (count($affectedFiles))	$msgLines[] = "<br /><strong>Modified files:</strong><br />".$GLOBALS["TBE_TEMPLATE"]->rfw(implode("<br />",$affectedFiles));
			}
			$cells[]="<td valign=top>".implode("<br />",$msgLines)."</td>";
		} else {
			$verDiff = $inst_list[$eKey] && $this->versionDifference($info["EM_CONF"]["version"],$inst_list[$eKey]["EM_CONF"]["version"],$this->versionDiffFactor);
			
			$cells[]="<td nowrap valign=top><em>".$eKey."</em></td>";
			$cells[]="<td nowrap valign=top>".($verDiff?"<strong>".$GLOBALS["TBE_TEMPLATE"]->rfw($info["EM_CONF"]["version"])."</strong>":$info["EM_CONF"]["version"])."</td>";
			if (!$import) {
				$fileP = PATH_site.$this->typePaths[$info["type"]].$eKey."/doc/manual.sxw";

				$cells[]='<td nowrap>'.
						($this->typePaths[$info["type"]] && @is_file($fileP)?'<img src="oodoc.gif" width="13" height="16" title="Local Open Office Manual" alt="" />':'').
						'</td>';
				$cells[]="<td nowrap valign=top>".$this->typeLabels[$info["type"]].(strlen($info["doubleInstall"])>1?'<strong> '.$GLOBALS["TBE_TEMPLATE"]->rfw($info["doubleInstall"]).'</strong>':"")."</td>";
			} else {
				$inst_curVer = $inst_list[$eKey]["EM_CONF"]["version"];
				if (isset($inst_list[$eKey]))	{
					if ($verDiff)	$inst_curVer = "<strong>".$GLOBALS["TBE_TEMPLATE"]->rfw($inst_curVer)."</strong>";
				}
				$cells[]="<td nowrap valign=top>".$inst_curVer."</td>";
				$cells[]="<td nowrap valign=top>".$this->typeLabels[$inst_list[$eKey]["type"]].(strlen($inst_list[$eKey]["doubleInstall"])>1?'<strong> '.$GLOBALS["TBE_TEMPLATE"]->rfw($inst_list[$eKey]["doubleInstall"]).'</strong>':"")."</td>";
				$cells[]="<td nowrap valign=top><strong>".$GLOBALS["TBE_TEMPLATE"]->rfw($this->remoteAccess[$info["_ACCESS"]])."</strong></td>";
				$cells[]="<td nowrap valign=top>".$info["EM_CONF"]["_typo3_ver"]."</td>";
				$cells[]="<td nowrap valign=top>".$info["EM_CONF"]["_php_ver"]."</td>";
				$cells[]="<td nowrap valign=top>".$info["EM_CONF"]["_size"]."</td>";
				$cells[]="<td nowrap valign=top>".($info["_STAT_IMPORT"]["extension_allversions"]?$info["_STAT_IMPORT"]["extension_allversions"]:"&nbsp;&nbsp;")."/".($info["_STAT_IMPORT"]["extension_thisversion"]?$info["_STAT_IMPORT"]["extension_thisversion"]:"&nbsp;")."</td>";
//debug($info);
			}
			$cells[]="<td nowrap valign=top>".$this->states[$info["EM_CONF"]["state"]]."</td>";
			$cells[]="<td nowrap valign=top>".$info["EM_CONF"]["dependencies"]."</td>";
		}
		$bgColor = ' bgColor="'.($bgColor?$bgColor:$this->doc->bgColor4).'"';
		return '<tr'.$bgColor.$style.'>'.implode('',$cells).'</tr>';
	}

	/**
	 * Returns title and style attribute for mouseover help text.
	 * 
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function labelInfo($str)	{
		return ' title="'.htmlspecialchars($str).'" style="cursor:help;"';
	}

	/**
	 * Creates directories in $extDirPath
	 * 
	 * @param	[type]		$dirs: ...
	 * @param	[type]		$extDirPath: ...
	 * @return	[type]		...
	 */
	function createDirsInPath($dirs,$extDirPath)	{
		if (is_array($dirs))	{
			reset($dirs);
			while(list(,$dir)=each($dirs))	{
				$allDirs = t3lib_div::trimExplode("/",$dir,1);
				reset($allDirs);
				$root="";
				while(list(,$dirParts)=each($allDirs))	{
					$root.=$dirParts.'/';
					if (!is_dir($extDirPath.$root))	{
						@mkdir(ereg_replace('\/$','',$extDirPath.$root), 0777);
						if (!@is_dir($extDirPath.$root))	{
							return "Error: The directory '".$extDirPath.$root."' could not be created...";
						}
					}
				}
			}
		}
	}

	/**
	 * Removes the extension directory
	 * 
	 * @param	[type]		$removePath: ...
	 * @param	[type]		$removeContentOnly: ...
	 * @return	[type]		...
	 */
	function removeExtDirectory($removePath,$removeContentOnly=0)	{
		if (@is_dir($removePath) && substr($removePath,-1)=="/" && (
			t3lib_div::isFirstPartOfStr($removePath,PATH_site.$this->typePaths["G"]) ||
			t3lib_div::isFirstPartOfStr($removePath,PATH_site.$this->typePaths["L"]) ||
			(t3lib_div::isFirstPartOfStr($removePath,PATH_site.$this->typePaths["S"]) && $this->systemInstall) ||
			t3lib_div::isFirstPartOfStr($removePath,PATH_site."fileadmin/_temp_/"))
			) {
			$this->noCVS=0;
			$fileArr = $this->getAllFilesAndFoldersInPath(array(),$removePath,"",1);
			if (is_array($fileArr))	{
					// Remove files in dirs:
#debug($fileArr);
				reset($fileArr);
				while(list(,$removeFile)=each($fileArr))	{
					if (!@is_dir($removeFile))	{
						if (@is_file($removeFile) && t3lib_div::isFirstPartOfStr($removeFile,$removePath) && strcmp($removeFile,$removePath))	{	// ... we are very paranoid, so we check what cannot go wrong: that the file is in fact within the prefix path!
							@unlink($removeFile);
	#debug($removeFile);
							clearstatcache();
							if (@is_file($removeFile))	{
								debug("Error: '".$removeFile."' could not be deleted!");
							}
						} else debug("Error: '".$removeFile."' was either not a file, or it was equal to the removed directory or simply outside the removed directory '".$removePath."'!");
					}
				}

					// REmove dirs:
				$remDirs = $this->extractDirsFromFileList($this->removePrefixPathFromList($fileArr,$removePath));
#debug($remDirs);
				$remDirs = array_reverse($remDirs);	// Must delete outer dirs first...
				reset($remDirs);
				while(list(,$removeRelDir)=each($remDirs))	{
					$removeDir = $removePath.$removeRelDir;
					if (@is_dir($removeDir))	{	// ... we are very paranoid, so we check what cannot go wrong: that the file is in fact within the prefix path!
						rmdir($removeDir);
						clearstatcache();
						if (@is_dir($removeDir))	{
							debug("Error: '".$removeDir."' could not be removed (are there files left?)");
						}
					} else debug("Error: '".$removeDir."' was not a directory!");
				}
				
				if (!$removeContentOnly)	{
					rmdir($removePath);
					clearstatcache();
					if (@is_dir($removePath))	{
						debug("Error: Extension directory '".$removePath."' could not be removed (are there files or folders left?)");
					}
				}
#debug("ALL REMOVED");
			} else debug("Error: ".$fileArr);
		} else debug("Error: Unallowed path to remove: ".$removePath);
	}

	/**
	 * Extracts the directories in the $files array
	 * 
	 * @param	[type]		$files: ...
	 * @return	[type]		...
	 */
	function extractDirsFromFileList($files)	{
		$dirs = array();
		if (is_array($files))	{
			reset($files);
			while(list(,$file)=each($files))	{
				if (substr($file,-1)=="/")	{
					$dirs[$file]=$file;
				} else {
					$pI=pathinfo($file);
					if (strcmp($pI["dirname"],"") && strcmp($pI["dirname"],"."))	{
						$dirs[$pI["dirname"]."/"]=$pI["dirname"]."/";
					}
				}
			}
		}
		return $dirs;
	}

	/**
	 * Removes the current extension of $type and creates the base folder for the new one (which is going to be imported)
	 * 
	 * @param	[type]		$importedData: ...
	 * @param	[type]		$type: ...
	 * @return	[type]		...
	 */
	function clearAndMakeExtensionDir($importedData,$type)	{
		if (!$importedData["extKey"])	return "FATAL ERROR: Extension key was not set for some VERY strange reason. Nothing done...";

		$path="";
		switch((string)$type)	{
			case "G":	
			case "L":
				$path=PATH_site.$this->typePaths[$type];
				$suffix="";
				if ((string)$type=="L" && !@is_dir($path))	{
					@mkdir(ereg_replace('\/$','',$path), 0777);
				}
			break;
			default:
				if ($this->systemInstall && (string)$type=="S")	{
					$path=PATH_site.$this->typePaths[$type];
					$suffix="";
				} else {
					$path=PATH_site."fileadmin/_temp_/";
					$suffix="_".date("dmy-His");
				}
			break;
		}
		if ($path && @is_dir($path))	{
			$extDirPath = $path.$importedData["extKey"].$suffix.'/';
			if (@is_dir($extDirPath))	{
				// Install dir was found
				$res = $this->removeExtDirectory($extDirPath);
				if ($res) return "ERROR: Could not remove extension directory '".$extDirPath."'";
			}
#die("stop here...");
			// we go create...
			@mkdir(ereg_replace('\/$','',$extDirPath), 0777);
			if (!is_dir($extDirPath))	return "ERROR: Could not create extension directory '".$extDirPath."'";
			return array($extDirPath);
		} else return "ERROR: The extension install path '".$path."' was not a directory.";
	}

	/**
	 * Evaluates differences in version numbers with three parts, x.x.x. Returns true if $v1 is greater than $v2
	 * 
	 * @param	[type]		$v1: ...
	 * @param	[type]		$v2: ...
	 * @param	[type]		$div: ...
	 * @return	[type]		...
	 */
	function versionDifference($v1,$v2,$div=1)	{
#		debug(array(floor($this->makeVersion($v1,"int")/$div),floor($this->makeVersion($v2,"int")/$div)));
		return floor($this->makeVersion($v1,"int")/$div) > floor($this->makeVersion($v2,"int")/$div);
	}

	/**
	 * Fetches data from the $repositoryUrl, un-compresses it, unserializes array and returns an array with the content if success.
	 * 
	 * @param	[type]		$repositoryUrl: ...
	 * @return	[type]		...
	 */
	function fetchServerData($repositoryUrl)	{
		$ps1 = t3lib_div::milliseconds();
		$externalData = t3lib_div::getUrl($repositoryUrl);
		$ps2 = t3lib_div::milliseconds()+1;

		$stat=Array(
			($ps2-$ps1),
			strlen($externalData),
			"Time: ".($ps2-$ps1)."ms",
			"Size: ".t3liB_div::formatSize(strlen($externalData)),
			"Transfer: ".t3liB_div::formatSize(strlen($externalData) / (($ps2-$ps1)/1000))."/sec"
		);

		return $this->decodeServerData($externalData,$stat);
	}
	
	/**
	 * Decode server data
	 * 
	 * @param	[type]		$externalData: ...
	 * @param	[type]		$stat: ...
	 * @return	[type]		...
	 */
	function decodeServerData($externalData,$stat=array())	{
		$parts = explode(":",$externalData,4);
		$dat = base64_decode($parts[2]);
		if ($parts[0]==md5($dat))	{
			if ($parts[1]=="gzcompress")	{
				if ($this->gzcompress)	{
					$dat=gzuncompress($dat);
				} else debug("Decoding Error: No decompressor available for compressed content. gzcompress()/gzuncompress() functions are not available!");
			}
			$listArr = unserialize($dat);
			return array($listArr,$stat);
		}	
	}
	
	/**
	 * Clearing of cache-files in typo3conf/ + menu
	 * 
	 * @return	[type]		...
	 */
	function addClearCacheFiles()	{
		global $TYPO3_CONF_VARS;
		if ($TYPO3_CONF_VARS["EXT"]["extCache"])	{
			if (t3lib_div::GPvar("_clearCacheFiles"))	{
				$this->removeCacheFiles();
				header("Location: ".t3lib_div::linkThisScript(array("_clearCacheFiles"=>0,"_cache_files_are_removed"=>1)));
			} else {
				$content="";
				if (t3lib_div::GPvar("_cache_files_are_removed"))	$content.=$GLOBALS["TBE_TEMPLATE"]->rfw("Cache files was removed.").'<br /><br />';
				$content.='Click here to <a href="'.t3lib_div::linkThisScript(array("_clearCacheFiles"=>1)).'"><strong>clear cache files in typo3conf/</strong></a>';
				$this->content.=$this->doc->spacer(20);
				$this->content.=$this->doc->section("Clear cache files",$content,0,1);	
			}
		}
	}

	/**
	 * Returns a header for an extensions including icon if any
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @param	[type]		$align: ...
	 * @return	[type]		...
	 */
	function extensionTitleIconHeader($eKey,$info,$align="top")	{
		$imgInfo = @getImageSize($this->getExtPath($eKey,$info)."/ext_icon.gif");
		$out="";
		if (is_array($imgInfo))	{
			$out.='<img src="'.$GLOBALS["BACK_PATH"].$this->typeRelPaths[$info["type"]].$eKey."/ext_icon.gif".'" '.$imgInfo[3].' align='.$align.'>';
		}
		$out.=t3lib_div::fixed_lgd($info["EM_CONF"]["title"]?$info["EM_CONF"]["title"]:"<em>".$eKey."</em>",40);
		return $out;	
	}

	/**
	 * Perform a detailed, technical analysis of the available extension on server!
	 * Includes all kinds of verifications
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @param	[type]		$validity: ...
	 * @return	[type]		...
	 */
	function makeDetailedExtensionAnalysis($eKey,$info,$validity=0)	{
		$absPath = $this->getExtPath($eKey,$info);
		
		$infoArray = array();

		$table_class_prefix = substr($eKey,0,5)=="user_" ? "user_" : "tx_".str_replace("_","",$eKey)."_";
		$module_prefix = substr($eKey,0,5)=="user_" ? "u" : "tx".str_replace("_","",$eKey);

			// Tables:
		$dbInfo=$this->checkDBupdates($eKey,$info,1);
		if (is_array($dbInfo["static"]))	{
			$infoArray["static"]=array_keys($dbInfo["static"]);
		}
		if (is_array($dbInfo["structure"]["tables_fields"]))	{
			$modify_tables = t3lib_div::trimExplode(",",$info["EM_CONF"]["modify_tables"],1);
			$infoArray["dump_tf"]=array();
			reset($dbInfo["structure"]["tables_fields"]);
			while(list($tN,$d)=each($dbInfo["structure"]["tables_fields"]))	{
				if (in_array($tN,$modify_tables))	{
					$infoArray["fields"][]=$tN.": <i>".
						(is_array($d["fields"])?implode(", ",array_keys($d["fields"])):"").
						(is_array($d["keys"])?" + ".count($d["keys"])." keys":"").
						"</i>";
					if (is_array($d["fields"]))	{
						reset($d["fields"]);
						while(list($fN)=each($d["fields"]))	{
							$infoArray["dump_tf"][]=$tN.".".$fN;
							if (!t3lib_div::isFirstPartOfStr($fN,$table_class_prefix))	{
								$infoArray["NSerrors"]["fields"][$fN]=$fN;
							} else $infoArray["NSok"]["fields"][$fN]=$fN;
						}
					}
					if (is_array($d["keys"]))	{
						reset($d["keys"]);
						while(list($fN)=each($d["keys"]))	{
							$infoArray["dump_tf"][]=$tN.".KEY:".$fN;
						}
					}
				} else {
					$infoArray["dump_tf"][]=$tN;
					$infoArray["tables"][]=$tN;
					if (!t3lib_div::isFirstPartOfStr($tN,$table_class_prefix))	{
						$infoArray["NSerrors"]["tables"][$tN]=$tN;
					} else $infoArray["NSok"]["tables"][$tN]=$tN;
				}
			}
			if (count($dbInfo["structure"]["diff"]["diff"]) || count($dbInfo["structure"]["diff"]["extra"]))	{
				$msg=array();
				if (count($dbInfo["structure"]["diff"]["diff"]))	$msg[]="missing";
				if (count($dbInfo["structure"]["diff"]["extra"]))	$msg[]="of wrong type";
				$infoArray["tables_error"]=1;
				if (t3lib_extMgm::isLoaded($eKey))	$infoArray["errors"][]="Some tables or fields are ".implode(" and ",$msg)."!";
			}
		}
		if (is_array($infoArray["static"]))	{
			reset($dbInfo["static"]);
			while(list($tN,$d)=each($dbInfo["static"]))	{
				if (!$d["exists"])	{
					$infoArray["static_error"]=1;
					if (t3lib_extMgm::isLoaded($eKey))	$infoArray["errors"][]="Static table(s) missing!";
					if (!t3lib_div::isFirstPartOfStr($tN,$table_class_prefix))	{
						$infoArray["NSerrors"]["tables"][$tN]=$tN;
					} else $infoArray["NSok"]["tables"][$tN]=$tN;
				}
			}
		}
		
			// Module-check:
		$knownModuleList = t3lib_div::trimExplode(",",$info["EM_CONF"]["module"],1);
		reset($knownModuleList);
		while(list(,$mod)=each($knownModuleList))	{
			if (@is_dir($absPath.$mod))	{
				if (@is_file($absPath.$mod."/conf.php"))	{
					$confFileInfo = $this->modConfFileAnalysis($absPath.$mod."/conf.php");
					if (is_array($confFileInfo["TYPO3_MOD_PATH"]))	{
						$shouldBePath = $this->typeRelPaths[$info["type"]].$eKey."/".$mod."/";
						if (strcmp($confFileInfo["TYPO3_MOD_PATH"][1][1],$shouldBePath))	{
							$infoArray["errors"][]="Configured TYPO3_MOD_PATH '".$confFileInfo["TYPO3_MOD_PATH"][1][1]."' different from '".$shouldBePath."'";
						}
					} else $infoArray["errors"][]="No definition of TYPO3_MOD_PATH constant found inside!";
					if (is_array($confFileInfo["MCONF_name"]))	{
						$mName = $confFileInfo["MCONF_name"][1][1];
						$mNameParts = explode("_",$mName);
						$infoArray["moduleNames"][]=$mName;
						if (!t3lib_div::isFirstPartOfStr($mNameParts[0],$module_prefix) && 
							(!$mNameParts[1] || !t3lib_div::isFirstPartOfStr($mNameParts[1],$module_prefix)))	{
							$infoArray["NSerrors"]["modname"][]=$mName;
						} else $infoArray["NSok"]["modname"][]=$mName;
					} else $infoArray["errors"][]="No definition of MCONF[name] variable found inside!";
				} else  $infoArray["errors"][]="Backend module conf file '".$mod."/conf.php' should exist but does not!";
			} else $infoArray["errors"][]="Backend module folder '".$mod."/' should exist but does not!";
		}
		$dirs = t3lib_div::get_dirs($absPath);
		if (is_array($dirs))	{
			reset($dirs);
			while(list(,$mod)=each($dirs))	{
				if (!in_array($mod,$knownModuleList) && @is_file($absPath.$mod."/conf.php"))	{
					$confFileInfo = $this->modConfFileAnalysis($absPath.$mod."/conf.php");
					if (is_array($confFileInfo))	{
						$infoArray["errors"][]="It seems like there is a backend module in '".$mod."/conf.php"."' which is not configured in ext_emconf.php";
					}					
				}
			}
		}
		
			// ext_tables.php:
		if (@is_file($absPath."ext_tables.php"))	{
			$content = t3lib_div::getUrl($absPath."ext_tables.php");
			if (eregi("t3lib_extMgm::addModule",$content))	$infoArray["flags"][]="Module";
			if (eregi("t3lib_extMgm::insertModuleFunction",$content))	$infoArray["flags"][]="Module+";
			if (stristr($content,'t3lib_div::loadTCA'))	$infoArray["flags"][]='loadTCA';
			if (stristr($content,'$TCA['))	$infoArray["flags"][]='TCA';
			if (eregi("t3lib_extMgm::addPlugin",$content))	$infoArray["flags"][]="Plugin";
		}

			// ext_localconf.php:
		if (@is_file($absPath."ext_localconf.php"))	{
			$content = t3lib_div::getUrl($absPath."ext_localconf.php");
			if (eregi("t3lib_extMgm::addPItoST43",$content))	$infoArray["flags"][]="Plugin/ST43";
			if (eregi("t3lib_extMgm::addPageTSConfig",$content))	$infoArray["flags"][]="Page-TSconfig";
			if (eregi("t3lib_extMgm::addUserTSConfig",$content))	$infoArray["flags"][]="User-TSconfig";
			if (eregi("t3lib_extMgm::addTypoScriptSetup",$content))	$infoArray["flags"][]="TS/Setup";
			if (eregi("t3lib_extMgm::addTypoScriptConstants",$content))	$infoArray["flags"][]="TS/Constants";
		}

		if (@is_file($absPath."ext_typoscript_constants.txt"))	{
			$infoArray["TSfiles"][]="Constants";
		}
		if (@is_file($absPath."ext_typoscript_setup.txt"))	{
			$infoArray["TSfiles"][]="Setup";
		}
		if (@is_file($absPath."ext_conf_template.txt"))	{
			$infoArray["conf"]=1;
		}
		
			// Classes:
		if ($validity)	{
			$filesInside = $this->getClassIndexLocallangFiles($absPath,$table_class_prefix,$eKey);
			if (is_array($filesInside["errors"]))	$infoArray["errors"]=array_merge($infoArray["errors"],$filesInside["errors"]);
			if (is_array($filesInside["NSerrors"]))	$infoArray["NSerrors"]=array_merge($infoArray["NSerrors"],$filesInside["NSerrors"]);
			if (is_array($filesInside["NSok"]))	$infoArray["NSok"]=array_merge($infoArray["NSok"],$filesInside["NSok"]);
			$infoArray["locallang"]=$filesInside["locallang"];
			$infoArray["classes"]=$filesInside["classes"];
		}
		
		
		if ($info["EM_CONF"]["uploadfolder"])	{
	 		$infoArray["uploadfolder"] = $this->ulFolder($eKey);
			if (!@is_dir(PATH_site.$infoArray["uploadfolder"]))	{
				$infoArray["errors"][]="Error: Upload folder '".$infoArray["uploadfolder"]."' did not exist!";
				$infoArray["uploadfolder"] = "";
			}
		}

		if ($info["EM_CONF"]["createDirs"])	{
	 		$infoArray["createDirs"] = array_unique(t3lib_div::trimExplode(",",$info["EM_CONF"]["createDirs"],1));
			while(list(,$crDir)=each($infoArray["createDirs"]))	{
				if (!@is_dir(PATH_site.$crDir))	{
					$infoArray["errors"][]="Error: Upload folder '".$crDir."' did not exist!";
				}
			}
		}
		return $infoArray;
	}

	/**
	 * Analyses the php-scripts of an available extension on server
	 * 
	 * @param	[type]		$absPath: ...
	 * @param	[type]		$table_class_prefix: ...
	 * @param	[type]		$eKey: ...
	 * @return	[type]		...
	 */
	function getClassIndexLocallangFiles($absPath,$table_class_prefix,$eKey)	{
		$filesInside = $this->removePrefixPathFromList($this->getAllFilesAndFoldersInPath(array(),$absPath,"php,inc"),$absPath);
		$out=array();
		reset($filesInside);
		while(list(,$fileName)=each($filesInside))	{
			if (substr($fileName,0,4)!="ext_")	{
				$baseName = basename($fileName);
				if (substr($baseName,0,9)=="locallang" && substr($baseName,-4)==".php")	{
					$out["locallang"][]=$fileName;
				} elseif ($baseName!="conf.php")	{
					if (filesize($absPath.$fileName)<500*1024)	{
						$fContent = t3lib_div::getUrl($absPath.$fileName);
						unset($reg);
						if (ereg("\n[[:space:]]*class[[:space:]]*([[:alnum:]_]+)([[:alnum:][:space:]_]*){",$fContent,$reg))	{
								// Find classes:
							$classesInFile=array();
							$lines = explode(chr(10),$fContent);
							reset($lines);
							while(list($k,$l)=each($lines))	{
								$line = trim($l);
								unset($reg);
								if (ereg("^class[[:space:]]*([[:alnum:]_]+)([[:alnum:][:space:]_]*){",$line,$reg))	{
									$out["classes"][]=$reg[1];
									$out["files"][$fileName]["classes"][]=$reg[1];
									if (substr($reg[1],0,3)!="ux_" && !t3lib_div::isFirstPartOfStr($reg[1],$table_class_prefix) && strcmp(substr($table_class_prefix,0,-1),$reg[1]))	{
										$out["NSerrors"]["classname"][]=$reg[1];
									} else $out["NSok"]["classname"][]=$reg[1];
								}
							}
								// If class file prefixed "class."....
							if (substr($baseName,0,6)=="class.")	{
								$fI=pathinfo($baseName);
								$testName=substr($baseName,6,-(1+strlen($fI["extension"])));
								if (substr($testName,0,3)!="ux_" && !t3lib_div::isFirstPartOfStr($testName,$table_class_prefix) && strcmp(substr($table_class_prefix,0,-1),$testName))	{
									$out["NSerrors"]["classfilename"][]=$baseName;
								} else {
									$out["NSok"]["classfilename"][]=$baseName;
									if (is_array($out["files"][$fileName]["classes"]) && $this->first_in_array($testName,$out["files"][$fileName]["classes"],1))	{
										$out["msg"][]="Class filename '".$fileName."' did contain the class '".$testName."' just as it should.";
									} else $out["errors"][]="Class filename '".$fileName."' did NOT contain the class '".$testName."'!";
								}
							}
								// 
							$XclassParts = explode('if (defined(\'TYPO3_MODE\') && $TYPO3_CONF_VARS[TYPO3_MODE][\'XCLASS\']',$fContent,2);
							if (count($XclassParts)==2)	{
								unset($reg);
								ereg('^\[\'([[:alnum:]_\/\.]*)\'\]',$XclassParts[1],$reg);
								if ($reg[1]) {
									$cmpF = "ext/".$eKey."/".$fileName;
									if (!strcmp($reg[1],$cmpF))	{
										if (strstr($XclassParts[1],'_once($TYPO3_CONF_VARS[TYPO3_MODE][\'XCLASS\'][\''.$cmpF.'\']);'))	{
											 $out["msg"][]="XCLASS OK in ".$fileName;
										} else $out["errors"][]="Couldn't find the include_once statement for XCLASS!";
									} else $out["errors"][]="The XCLASS filename-key '".$reg[1]."' was different from '".$cmpF."' which it should have been!";
								} else $out["errors"][]="No XCLASS filename-key found in file '".$fileName."'. Maybe a regex coding error here...";
							} elseif (!$this->first_in_array("ux_",$out["files"][$fileName]["classes"])) $out["errors"][]="No XCLASS inclusion code found in file '".$fileName."'";
						}
					}
				}
			}
		}
		return $out;
	}

	/**
	 * Returns true if the $str is found as the first part of a string in $array
	 * 
	 * @param	[type]		$str: ...
	 * @param	[type]		$array: ...
	 * @return	[type]		...
	 */
	function first_in_array($str,$array,$caseInsensitive=FALSE)	{
		if ($caseInsensitive)	$str = strtolower($str);
		if (is_array($array))	{
			reset($array);
			while(list(,$cl)=each($array))	{
				if ($caseInsensitive)	$cl = strtolower($cl);
				if (t3lib_div::isFirstPartOfStr($cl,$str))	return 1;
			}
		}
	}

	/**
	 * Reads $confFilePath (a module $conf-file) and returns information on the existence of TYPO3_MOD_PATH definition and MCONF_name
	 * 
	 * @param	[type]		$confFilePath: ...
	 * @return	[type]		...
	 */
	function modConfFileAnalysis($confFilePath)	{
		$lines = explode(chr(10),t3lib_div::getUrl($confFilePath));
		$confFileInfo=array();
		$confFileInfo["lines"]=$lines;
		
		reset($lines);
		while(list($k,$l)=each($lines))	{
			$line = trim($l);
			unset($reg);
			if (ereg('^define[[:space:]]*\([[:space:]]*["\']TYPO3_MOD_PATH["\'][[:space:]]*,[[:space:]]*["\']([[:alnum:]_\/\.]+)["\'][[:space:]]*\)[[:space:]]*;',$line,$reg))	{
				$confFileInfo["TYPO3_MOD_PATH"]=array($k,$reg);
			}
			unset($reg);
			if (ereg('^\$MCONF\[["\']?name["\']?\][[:space:]]*=[[:space:]]*["\']([[:alnum:]_]+)["\'];',$line,$reg))	{
				$confFileInfo["MCONF_name"]=array($k,$reg);
			}
		}
		return $confFileInfo;
	}

	/**
	 * Write new TYPO3_MOD_PATH
	 * 
	 * @param	[type]		$confFilePath: ...
	 * @param	[type]		$type: ...
	 * @param	[type]		$mP: ...
	 * @return	[type]		...
	 */
	function writeTYPO3_MOD_PATH($confFilePath,$type,$mP)	{
		$lines = explode(chr(10),t3lib_div::getUrl($confFilePath));
		$confFileInfo=array();
		$confFileInfo["lines"]=$lines;
		
		$flag_M=0;
		$flag_B=0;
		
		reset($lines);
		while(list($k,$l)=each($lines))	{
			$line = trim($l);
			unset($reg);
			if (ereg('^define[[:space:]]*\([[:space:]]*["\']TYPO3_MOD_PATH["\'][[:space:]]*,[[:space:]]*["\']([[:alnum:]_\/\.]+)["\'][[:space:]]*\)[[:space:]]*;',$line,$reg))	{
				$lines[$k]=str_replace($reg[0], 'define(\'TYPO3_MOD_PATH\', \''.$this->typeRelPaths[$type].$mP.'\');', $lines[$k]);
				$flag_M=$k+1;
			}
			if (ereg('^\$BACK_PATH[[:space:]]*=[[:space:]]*["\']([[:alnum:]_\/\.]+)["\'][[:space:]]*;',$line,$reg))	{
				$lines[$k]=str_replace($reg[0], '$BACK_PATH=\''.$this->typeBackPaths[$type].'\';', $lines[$k]);
				$flag_B=$k+1;
			}
		}
		
		if ($flag_B && $flag_M)	{
			t3lib_div::writeFile($confFilePath,implode(chr(10),$lines));
			return "TYPO3_MOD_PATH and \$BACK_PATH was updated in '".substr($confFilePath,strlen(PATH_site))."'";
		} else return "Error: Either TYPO3_MOD_PATH or \$BACK_PATH was not found in the '".$confFilePath."' file. You must manually configure that!";
	}

	/**
	 * Produces the config form for an extension (if any template file, ext_conf_template.txt is found)
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @param	[type]		$output: ...
	 * @param	[type]		$script: ...
	 * @param	[type]		$addFields: ...
	 * @return	[type]		...
	 */
	function tsStyleConfigForm($eKey,$info,$output=0,$script="",$addFields="")	{
		global $TYPO3_CONF_VARS;
		
		$absPath = $this->getExtPath($eKey,$info);
		$relPath = $this->typeRelPaths[$info["type"]].$eKey."/";

		if (@is_file($absPath."ext_conf_template.txt"))	{
			$tsStyleConfig = t3lib_div::makeInstance("t3lib_tsStyleConfig");	// Defined global here!
			$theConstants = $tsStyleConfig->ext_initTSstyleConfig(
				t3lib_div::getUrl($absPath."ext_conf_template.txt"),
				$relPath,
				$absPath,
				$GLOBALS["BACK_PATH"]
			);

			$tsStyleConfig->ext_loadResources($absPath."res/");

			$arr = unserialize($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["extConf"][$eKey]);
			$arr = is_array($arr) ? $arr : array();
			
				// Call processing function for constants config and data before write and form rendering:
			if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/mod/tools/em/index.php']['tsStyleConfigForm']))	{
				$_params = array('fields' => &$theConstants, 'data' => &$arr, 'extKey' => $eKey);
				foreach($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/mod/tools/em/index.php']['tsStyleConfigForm'] as $_funcRef)	{
					t3lib_div::callUserFunction($_funcRef,$_params,$this);
				}
				unset($_params);
			}				

			if ($GLOBALS["HTTP_POST_VARS"]["submit"])	{
				$tsStyleConfig->ext_procesInput($GLOBALS["HTTP_POST_VARS"],array(),$theConstants,array());
				$arr = $tsStyleConfig->ext_mergeIncomingWithExisting($arr);
				$this->writeTsStyleConfig($eKey,$arr);
			}

			$tsStyleConfig->ext_setValueArray($theConstants,$arr);
			
			$MOD_MENU=array();
			$MOD_MENU["constant_editor_cat"] = $tsStyleConfig->ext_getCategoriesForModMenu();
			$MOD_SETTINGS = t3lib_BEfunc::getModuleData($MOD_MENU, t3lib_div::GPvar("SET",1), "xMod_test");
			
				// Resetting the menu (stop)
			if (count($MOD_MENU)>1)	{
				$menu = "Category: ".t3lib_BEfunc::getFuncMenu(0,"SET[constant_editor_cat]",$MOD_SETTINGS["constant_editor_cat"],$MOD_MENU["constant_editor_cat"],"","&CMD[showExt]=".$extKey);
				$this->content.=$this->doc->section("",'<span class="nobr">'.$menu.'</span>');
				$this->content.=$this->doc->spacer(10);
			}
					// Category and constant editor config:
			$form = '<table border=0 cellpadding=0 cellspacing=0 width=600><tr><td>'.$tsStyleConfig->ext_getForm($MOD_SETTINGS["constant_editor_cat"],$theConstants,$script,$addFields).'</td></tr></table>';
			if ($output)	{
				return $form;
			} else $this->content.=$this->doc->section("",'</form>'.$form.'<form>');
		}
	}

	/**
	 * Writes the TSstyleconf values to localconf.php
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$arr: ...
	 * @return	[type]		...
	 */
	function writeTsStyleConfig($eKey,$arr)	{
			// Instance of install tool					
		$instObj = new em_install_class;
		$instObj->allowUpdateLocalConf =1;
		$instObj->updateIdentity = "TYPO3 Extension Manager";

			// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS["EXT"]["extConf"]["'.$eKey.'"]', serialize($arr));	// THis will be saved only if there are no linebreaks in it !
		$instObj->writeToLocalconf_control($lines,1);
		
		$this->removeCacheFiles();
	}

	/**
	 * Dump static table information
	 * Which tables are determined by the ext_tables_static+adt.sql
	 * 
	 * @param	[type]		$tableList: ...
	 * @return	[type]		...
	 */
	function dumpStaticTables($tableList)	{
		$instObj = new em_install_class;
		$dbFields = $instObj->getFieldDefinitions_database(TYPO3_db);

		$out="";
		$parts = t3lib_div::trimExplode(",",$tableList,1);
		reset($parts);
		while(list(,$table)=each($parts))	{
			if (is_array($dbFields[$table]["fields"]))	{
				$dHeader = $this->dumpHeader();
				$header = $this->dumpTableHeader($table,$dbFields[$table],1);
				$insertStatements = $this->dumpTableContent($table,$dbFields[$table]["fields"]);
				
				$out.= $dHeader.chr(10).chr(10).chr(10).$header.chr(10).chr(10).chr(10).$insertStatements.chr(10).chr(10).chr(10);
			} else {
				die("table not found in database...");
			}
		}
		return $out;
	}

	/**
	 * Makes a dump of the tables/fields for an extension
	 * 
	 * @param	[type]		$arr: ...
	 * @return	[type]		...
	 */
	function dumpTableAndFieldStructure($arr)	{
		$tables = array();
		if (count($arr))	{
			$tables[]=$this->dumpHeader();
			reset($arr);
			while(list($table,$fieldKeyInfo)=each($arr))	{
				$tables[]=$this->dumpTableHeader($table,$fieldKeyInfo);
			}
		}
		return implode(chr(10).chr(10).chr(10),$tables);
	}

	/**
	 * Dump-header
	 * 
	 * @return	[type]		...
	 */
	function dumpHeader()	{
		return trim("		
# TYPO3 Extension Manager dump 1.0
#
# Host: ".TYPO3_db_host."    Database: ".TYPO3_db."
#--------------------------------------------------------
");	
	}

	/**
	 * Dump table header
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$fieldKeyInfo: ...
	 * @param	[type]		$dropTableIfExists: ...
	 * @return	[type]		...
	 */
	function dumpTableHeader($table,$fieldKeyInfo,$dropTableIfExists=0)	{
		$lines=array();
		if (is_array($fieldKeyInfo["fields"]))	{
			reset($fieldKeyInfo["fields"]);
			while(list($fieldN,$data)=each($fieldKeyInfo["fields"]))	{
				$lines[]="  ".$fieldN." ".$data;
			}
		}
		if (is_array($fieldKeyInfo["keys"]))	{
			reset($fieldKeyInfo["keys"]);
			while(list($fieldN,$data)=each($fieldKeyInfo["keys"]))	{
				$lines[]="  ".$data;
			}
		}
		if (count($lines))	{
			return trim("
#
# Table structure for table '".$table."'
#
".($dropTableIfExists?"DROP TABLE IF EXISTS ".$table.";
":"")."CREATE TABLE ".$table." (
".implode(",".chr(10),$lines)."
);"
			);
		}
	}

	/**
	 * Dump table content
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$fieldStructure: ...
	 * @return	[type]		...
	 */
	function dumpTableContent($table,$fieldStructure)	{
			// Borrowed a some chunks of code from phpMyAdmin here...
		$search = array('\\', '\'', "\x00", "\x0a", "\x0d", "\x1a");
		$replace = array('\\\\', '\\\'', '\0', '\n', '\r', '\Z');

		$lines=array();
		$q = "SELECT * FROM ".$table;
		$result = mysql(TYPO3_db,$q);
		while ($row = mysql_fetch_assoc($result)) {
			$values=array();
			reset($fieldStructure);
			while(list($field)=each($fieldStructure))	{
				$values[]=isset($row[$field]) ? "'".str_replace($search, $replace, $row[$field])."'" : "NULL";
			}
			$lines[]='INSERT INTO '.$table.' VALUES ('.implode(', ',$values).');';
		}
        mysql_free_result($result);
		return implode(chr(10),$lines);
	}

	/**
	 * Writes the extension list
	 * 
	 * @param	[type]		$newExtList: ...
	 * @return	[type]		...
	 */
	function writeNewExtensionList($newExtList)	{
			// Instance of install tool					
		$instObj = new em_install_class;
		$instObj->allowUpdateLocalConf =1;
		$instObj->updateIdentity = "TYPO3 Extension Manager";

			// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS["EXT"]["extList"]', $newExtList);
		$instObj->writeToLocalconf_control($lines,1);
		
		$this->removeCacheFiles();
	}

	/**
	 * Unlink (delete) cache files
	 * 
	 * @return	[type]		...
	 */
	function removeCacheFiles()	{
		$cacheFiles=t3lib_extMgm::currentCacheFiles();
		$out=0;
		if (is_array($cacheFiles))	{
			reset($cacheFiles);
			while(list(,$cfile)=each($cacheFiles))	{
				@unlink($cfile);
				clearstatcache();
				$out++;
			}
		}
		return $out;
	}

	/**
	 * Check if clear-cache should be performed, otherwise show form (for installation of extension)
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @return	[type]		...
	 */
	function checkClearCache($eKey,$info)	{
		if ($info["EM_CONF"]["clearCacheOnLoad"])	{
			if (t3lib_div::GPvar("_clear_all_cache"))	{
				$tce = t3lib_div::makeInstance("t3lib_TCEmain");
				$tce->start(Array(),Array());
				$tce->clear_cacheCmd("all");
			} else {
				$instObj = new em_install_class;
				$content.="<br />".$instObj->fwheader("Clear cache",3).'This extension requests the cache to be cleared when it is installed/removed.<br />
				Clear all cache: <input type="checkbox" name="_clear_all_cache" CHECKED value="1"><br />
				<br />
				';
			}
		}
		return $content;
	}

	/**
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @return	[type]		...
	 */
	function checkUploadFolder($eKey,$info)	{
		$instObj = new em_install_class;
		$uploadFolder = PATH_site.$this->ulFolder($eKey);
		if ($info["EM_CONF"]["uploadfolder"] && !@is_dir($uploadFolder))	{
			if (t3lib_div::GPvar("_uploadfolder"))	{
				mkdir(ereg_replace('\/$','',$uploadFolder), 0777);
				$indexContent = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD>
	<TITLE></TITLE>
<META http-equiv=Refresh Content="0; Url=../../">
</HEAD>
</HTML>';
				t3lib_div::writeFile($uploadFolder."index.html",$indexContent);
			} else {
				$content.="<br />".$instObj->fwheader("Create upload folder",3).'The extension requires the upload folder "'.$this->ulFolder($eKey).'" to exist.<br />
				Create directory "'.$this->ulFolder($eKey).'": <input type="checkbox" name="_uploadfolder" CHECKED value="1"><br />
				<br />
				';
			}
		}
		
		
		if ($info["EM_CONF"]["createDirs"])	{
	 		$createDirs = array_unique(t3lib_div::trimExplode(",",$info["EM_CONF"]["createDirs"],1));
			while(list(,$crDir)=each($createDirs))	{
				if (!@is_dir(PATH_site.$crDir))	{
					if (t3lib_div::GPvar("_createDir_".md5($crDir)))	{
						$crDirStart="";
						$dirs_in_path=explode("/",ereg_replace("/$","",$crDir));
						while(list(,$dirP)=each($dirs_in_path))	{
							if (strcmp($dirP,""))	{
								$crDirStart.=$dirP.'/';
								if (!@is_dir(PATH_site.$crDirStart))	{
									mkdir(ereg_replace('\/$','',PATH_site.$crDirStart), 0777);
#debug(array(PATH_site.$crDirStart));
									$finalDir=PATH_site.$crDirStart;
								}
							} else die("ERROR: The path '".PATH_site.$crDir."' could not be created.");
						}
						if ($finalDir)	{
							$indexContent = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD>
	<TITLE></TITLE>
<META http-equiv=Refresh Content="0; Url=/">
</HEAD>
</HTML>';
							t3lib_div::writeFile($finalDir."index.html",$indexContent);
						}
#debug(array($indexContent,$finalDir."index.html"));
					} else {
						$content.="<br />".$instObj->fwheader("Create folder",3).'The extension requires the folder "'.$crDir.'" to exist.<br />
						Create directory "'.$crDir.'": <input type="checkbox" name="_createDir_'.md5($crDir).'" CHECKED value="1"><br />
						<br />
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
	 * 
	 * @param	[type]		$eKey: ...
	 * @param	[type]		$info: ...
	 * @param	[type]		$infoOnly: ...
	 * @return	[type]		...
	 */
	function checkDBupdates($eKey,$info,$infoOnly=0)	{

			// Getting statement array from
		$instObj = new em_install_class;
		$instObj->INSTALL = t3lib_div::GPvar("TYPO3_INSTALL",1);
		$dbStatus=array();
#		$instObj->mysqlVersion = "3.23";

			// Updating tables and fields?
		if (in_array("ext_tables.sql",$info["files"]))	{
			$fileContent = t3lib_div::getUrl($this->getExtPath($eKey,$info)."ext_tables.sql");

			$FDfile = $instObj->getFieldDefinitions_sqlContent($fileContent);
			if (count($FDfile))	{
				$FDdb = $instObj->getFieldDefinitions_database(TYPO3_db);
				$diff = $instObj->getDatabaseExtra($FDfile, $FDdb);
				$update_statements = $instObj->getUpdateSuggestions($diff);

				$dbStatus["structure"]["tables_fields"]=$FDfile;
				$dbStatus["structure"]["diff"]=$diff;

					// Updating database...
				if (!$infoOnly && is_array($instObj->INSTALL["database_update"]))	{
					$instObj->preformUpdateQueries($update_statements["add"],$instObj->INSTALL["database_update"]);
					$instObj->preformUpdateQueries($update_statements["change"],$instObj->INSTALL["database_update"]);
					$instObj->preformUpdateQueries($update_statements["create_table"],$instObj->INSTALL["database_update"]);
				} else {
					$content.=$instObj->generateUpdateDatabaseForm_checkboxes($update_statements["add"],"Add fields");
					$content.=$instObj->generateUpdateDatabaseForm_checkboxes($update_statements["change"],"Changing fields",1,0,$update_statements["change_currentValue"]);
					$content.=$instObj->generateUpdateDatabaseForm_checkboxes($update_statements["create_table"],"Add tables");
				}
			}
		}
		
		// Importing static tables?
		if (in_array("ext_tables_static+adt.sql",$info["files"]))	{
			$fileContent = t3lib_div::getUrl($this->getExtPath($eKey,$info)."ext_tables_static+adt.sql");

			$statements = $instObj->getStatementArray($fileContent,1);
			list($statements_table, $insertCount) = $instObj->getCreateTables($statements,1);
			
			if (!$infoOnly && is_array($instObj->INSTALL["database_import"]))	{
					// Traverse the tables
				reset($instObj->INSTALL["database_import"]);
				while(list($table,$md5str)=each($instObj->INSTALL["database_import"]))	{
					if ($md5str==md5($statements_table[$table]))	{
						$res=mysql(TYPO3_db, "DROP TABLE IF EXISTS ".$table);
						$err =mysql_error();
						if ($err)	echo $err."<br />";
						
						$res=mysql(TYPO3_db, $statements_table[$table]);
						$err =mysql_error();
						if ($err)	echo $err."<br />";

						if ($insertCount[$table])	{
							$statements_insert = $instObj->getTableInsertStatements($statements, $table);
							reset($statements_insert);
							while(list($k,$v)=each($statements_insert))	{
								$res=mysql(TYPO3_db, $v);
								$err =mysql_error();
								if ($err)	echo $err."<br />";
							}
						}
					}
				}
			} else {
				$whichTables=$instObj->getListOfTables();
				if (count($statements_table))	{
					reset($statements_table);
					$out='';
					while(list($table,$definition)=each($statements_table))	{
						$exist=isset($whichTables[$table]);

						$dbStatus["static"][$table]["exists"]=$exist;
						$dbStatus["static"][$table]["count"]=$insertCount[$table];

						$out.='<tr>
							<td><input type="checkbox" name="TYPO3_INSTALL[database_import]['.$table.']" CHECKED value="'.md5($definition).'"></td>
							<td><strong>'.$table.'</strong></td>
							<td><img src=clear.gif width=10 height=1></td>
							<td nowrap>'.($insertCount[$table]?"Rows: ".$insertCount[$table]:"").'</td>
							<td><img src=clear.gif width=10 height=1></td>
							<td nowrap>'.($exist?'<img src="'.$GLOBALS["BACK_PATH"].'t3lib/gfx/icon_warning.gif" width=18 height=16 align=top>Table exists!':'').'</td>
							</tr>';
					}
					$content.="<br />".$instObj->fwheader("Import static data",3).'<table border=0 cellpadding=0 cellspacing=0>'.$out.'</table>';
				}
			}
		}

		return $infoOnly ? $dbStatus : $content;
	}
	
	/**
	 * Compares two arrays with MD5-hash values for analysis of which files has changed.
	 * 
	 * @param	[type]		$current: ...
	 * @param	[type]		$past: ...
	 * @return	[type]		...
	 */
	function findMD5ArrayDiff($current,$past)	{
		if (!is_array($current))	$current=array();
		if (!is_array($past))		$past=array();
		$filesInCommon = array_intersect($current,$past);
		$diff1 =  array_keys(array_diff($past,$filesInCommon));
		$diff2 =  array_keys(array_diff($current,$filesInCommon));
		$affectedFiles = array_unique(array_merge($diff1,$diff2));
		return $affectedFiles;
	}

	/**
	 * Removes all entries in the array having the script CVS/ in it
	 * 
	 * @param	[type]		$arr: ...
	 * @return	[type]		...
	 */
	function removeCVSentries($arr)	{
		reset($arr);
		while(list($k,$v)=each($arr))	{
			#if (strstr($v,"CVS/"))	unset($arr[$k]);
		}
		return $arr;
	}

	/**
	 * Creates a MD5-hash array over the current files in the extension
	 * 
	 * @param	[type]		$extKey: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function serverExtensionMD5Array($extKey,$conf)	{
		$mUA = $this->makeUploadArray($extKey,$conf);
#debug($mUA);
		$md5Array=array();
		if (is_array($mUA["FILES"]))	{
			reset($mUA["FILES"]);
			while(list($fN,$d)=each($mUA["FILES"]))	{
				if ($fN!="ext_emconf.php")	{
					$md5Array[$fN]=substr($d["content_md5"],0,4);
				}
			}
		} else debug($mUA);
		return $md5Array;
	}
	
	/**
	 * Make upload array out of extension
	 * 
	 * @param	[type]		$extKey: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function makeUploadArray($extKey,$conf)	{
		$extPath=$this->getExtPath($extKey,$conf);
#debug(array($extPath,$conf));
		if ($extPath)	{
			$fileArr = array();
			$fileArr = $this->getAllFilesAndFoldersInPath($fileArr,$extPath);
			
			$totalSize=0;
			reset($fileArr);
			while(list(,$file)=each($fileArr))	{
				$totalSize+=filesize($file);
			}
			
			if ($totalSize < $this->maxUploadSize)	{
				$uploadArray=array();
				$uploadArray["extKey"]=$extKey;
				$uploadArray["EM_CONF"]=$conf["EM_CONF"];
				$uploadArray["misc"]["codelines"]=0;
				$uploadArray["misc"]["codebytes"]=0;
				
				$uploadArray["techInfo"] = $this->makeDetailedExtensionAnalysis($extKey,$conf,1);
				
				reset($fileArr);
				while(list(,$file)=each($fileArr))	{
					$relFileName = substr($file,strlen($extPath));
					$fI=pathinfo($relFileName);
					if ($relFileName!="ext_emconf.php")	{		// This file should be dynamically written...
						$uploadArray["FILES"][$relFileName] = array(
							"name" => $relFileName,
							"size" => filesize($file),
							"mtime" => filemtime($file),
							"is_executable" => (TYPO3_OS=='WIN' ? 0 : is_executable($file)),
							"content" => t3lib_div::getUrl($file)
						);
						if (t3lib_div::inList("php,inc",strtolower($fI["extension"])))	{
							$uploadArray["FILES"][$relFileName]["codelines"]=count(explode(chr(10),$uploadArray["FILES"][$relFileName]["content"]));
							$uploadArray["misc"]["codelines"]+=$uploadArray["FILES"][$relFileName]["codelines"];
							$uploadArray["misc"]["codebytes"]+=$uploadArray["FILES"][$relFileName]["size"];

								// locallang*.php files:
							if (substr($fI["basename"],0,9)=="locallang" && strstr($uploadArray["FILES"][$relFileName]["content"],'$LOCAL_LANG'))	{
								$uploadArray["FILES"][$relFileName]["LOCAL_LANG"]=$this->getSerializedLocalLang($file,$uploadArray["FILES"][$relFileName]["content"]);
							}
						}
						$uploadArray["FILES"][$relFileName]["content_md5"] = md5($uploadArray["FILES"][$relFileName]["content"]);
					}
				}
				
				return $uploadArray;
			} else return "Error: Total size of uncompressed upload (".$totalSize.") exceeds ".t3lib_div::formatSize($this->maxUploadSize);
		}
	}
	
	/**
	 * @param	[type]		$file: ...
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function getSerializedLocalLang($file,$content)	{
		$returnParts = explode('$LOCAL_LANG',$content,2);
		
		include($file);
		if (is_array($LOCAL_LANG))	{
			$returnParts[1]=serialize($LOCAL_LANG);
			return $returnParts;
		}
	}

	/**
	 * Gets the table and field structure from database. 
	 * Which fields and which tables are determined from the ext_tables.sql file
	 * 
	 * @param	[type]		$parts: ...
	 * @return	[type]		...
	 */
	function getTableAndFieldStructure($parts)	{
			// Instance of install tool
		$instObj = new em_install_class;
		$dbFields = $instObj->getFieldDefinitions_database(TYPO3_db);
		$outTables=array();
		reset($parts);
		while(list(,$table)=each($parts))	{
			$tP = explode(".",$table);
			if ($tP[0] && isset($dbFields[$tP[0]]))	{
				if ($tP[1])	{
					$kfP = explode("KEY:",$tP[1],2);
					if (count($kfP)==2 && !$kfP[0])	{	// key:
						if (isset($dbFields[$tP[0]]["keys"][$kfP[1]]))	$outTables[$tP[0]]["keys"][$kfP[1]]=$dbFields[$tP[0]]["keys"][$kfP[1]];
					} else {
						if (isset($dbFields[$tP[0]]["fields"][$tP[1]]))	$outTables[$tP[0]]["fields"][$tP[1]]=$dbFields[$tP[0]]["fields"][$tP[1]];
					}
				} else {
					$outTables[$tP[0]]=$dbFields[$tP[0]];
				}
			}
		}
		
		return $outTables;
	}

	/**
	 * Compiles the ext_emconf.php file
	 * 
	 * @param	[type]		$extKey: ...
	 * @param	[type]		$EM_CONF: ...
	 * @return	[type]		...
	 */
	function construct_ext_emconf_file($extKey,$EM_CONF)	{
		reset($EM_CONF);
		$fMsg = array(
			"version" => "	// Don't modify this! Managed automatically during upload to repository."
		);
			// clean version number:
		$vDat = $this->renderVersion($EM_CONF["version"]);
		$EM_CONF["version"]=$vDat["version"];
		
		$lines=array();
		$lines[]='<?php';
		$lines[]='';
		$lines[]='########################################################################';
		$lines[]="# Extension Manager/Repository config file for ext: '".$extKey."'";
		$lines[]='# ';
		$lines[]='# Auto generated '.date("d-m-Y H:i");
		$lines[]='# ';
		$lines[]='# Manual updates:';
		$lines[]='# Only the data in the array - anything else is removed by next write';
		$lines[]='########################################################################';
		$lines[]='';
		$lines[]='$EM_CONF[$_EXTKEY] = Array (';
		while(list($k,$v)=each($EM_CONF))	{
			$lines[]=chr(9)."'".$k."' => ".(
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
	 * Decodes extension upload array
	 * 
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function decodeExchangeData($str)	{
		$parts = explode(":",$str,3);
		if ($parts[1]=="gzcompress")	{
			if ($this->gzcompress)	{
				$parts[2] = gzuncompress($parts[2]);
			} else debug("Decoding Error: No decompressor available for compressed content. gzcompress()/gzuncompress() functions are not available!");
		}
		if (md5($parts[2]) == $parts[0])	{
			return unserialize($parts[2]);
		} else debug('MD5 mismatch. Maybe the extension file was downloaded and saved as a text file by the browser and thereby corrupted!? (Always select "All" filetype when saving extensions)');
	}

	/**
	 * Encodes extension upload array
	 * 
	 * @param	[type]		$uploadArray: ...
	 * @param	[type]		$local_gzcompress: ...
	 * @return	[type]		...
	 */
	function makeUploadDataFromArray($uploadArray,$local_gzcompress=-1)	{
		if (is_array($uploadArray))	{
			$serialized = serialize($uploadArray);
			$md5 = md5($serialized);

			$local_gzcompress = ($local_gzcompress>-1)?$local_gzcompress:$this->gzcompress;

			$content=$md5.":";
			if ($local_gzcompress)	{
				$content.="gzcompress:";
				$content.=gzcompress($serialized);
			} else {
				$content.=":";
				$content.=$serialized;
			}
		}
		return $content;
	}

	/**
	 * Returns file-listing of an extension
	 * 
	 * @param	[type]		$extKey: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function getFileListOfExtension($extKey,$conf)	{
		$extPath=$this->getExtPath($extKey,$conf);
		
		if ($extPath)	{
			$fileArr = array();
			$fileArr = $this->getAllFilesAndFoldersInPath($fileArr,$extPath);
			
			$lines=array();
			$totalSize=0;

				$lines[]='<tr class="bgColor5">
					<td><strong>File:</strong></td>
					<td><strong>Size:</strong></td>
					<td><strong>Edit:</strong></td>
				</tr>';

			reset($fileArr);
			while(list(,$file)=each($fileArr))	{
				$fI=t3lib_div::split_fileref($file);
				$lines[]='<tr class="bgColor4">
					<td><a href="index.php?CMD[showExt]='.$extKey.'&CMD[downloadFile]='.rawurlencode($file).'" title="Download...">'.substr($file,strlen($extPath)).'</a></td>
					<td>'.t3lib_div::formatSize(filesize($file)).'</td>
					<td>'.(!in_array($extKey,$this->requiredExt)&&t3lib_div::inList($this->editTextExtensions,$fI["fileext"])?'<a href="index.php?CMD[showExt]='.$extKey.'&CMD[editFile]='.rawurlencode($file).'">Edit file</a>':'').'</td>
				</tr>';
				$totalSize+=filesize($file);
			}

			$lines[]='<tr class="bgColor6">
				<td><strong>Total:</strong></td>
				<td><strong>'.t3lib_div::formatSize($totalSize).'</strong></td>
				<td>&nbsp;</td>
			</tr>';
			
			return '
			Path: '.$extPath.'<br /><br />
			<table border=0 cellpadding=1 cellspacing=2>'.implode("",$lines).'</table>';
		}
	}

	/**
	 * Recursively gather all files and folders of extension path.
	 * 
	 * @param	[type]		$fileArr: ...
	 * @param	[type]		$extPath: ...
	 * @param	[type]		$extList: ...
	 * @param	[type]		$regDirs: ...
	 * @return	[type]		...
	 */
	function getAllFilesAndFoldersInPath($fileArr,$extPath,$extList="",$regDirs=0)	{
		if ($regDirs)	$fileArr[]=$extPath;
		$fileArr=array_merge($fileArr,t3lib_div::getFilesInDir($extPath,$extList,1,1));		// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
		
		$dirs = t3lib_div::get_dirs($extPath);
		if (is_array($dirs))	{
			reset($dirs);
			while(list(,$subdirs)=each($dirs))	{
				if ($subdirs && (strcmp($subdirs,"CVS") || !$this->noCVS))	{
					$fileArr = $this->getAllFilesAndFoldersInPath($fileArr,$extPath.$subdirs."/",$extList,$regDirs);
				}
			}
		}
		return $fileArr;
	}

	/**
	 * Removes the absolute part of all files/folders in fileArr
	 * 
	 * @param	[type]		$fileArr: ...
	 * @param	[type]		$extPath: ...
	 * @return	[type]		...
	 */
	function removePrefixPathFromList($fileArr,$extPath)	{
		reset($fileArr);
		while(list($k,$absFileRef)=each($fileArr))	{
			if(t3lib_div::isFirstPartOfStr($absFileRef,$extPath))	{
				$fileArr[$k]=substr($absFileRef,strlen($extPath));
			} else return "ERROR: One or more of the files was NOT prefixed with the prefix-path!";
		}
		return $fileArr;
	}

	/**
	 * Returns the path of an available extension based on "type" (SGL)
	 * 
	 * @param	[type]		$extKey: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function getExtPath($extKey,$conf)	{
		$typeP = $this->typePaths[$conf["type"]];
		if ($typeP)	{
			$path = PATH_site.$typeP.$extKey."/";
			return @is_dir($path) ? $path : "";
		}
	}

	/**
	 * Adds extension to extension list and returns new list. If -1 is returned, an error happend.
	 * Checks dependencies etc.
	 * 
	 * @param	[type]		$extKey: ...
	 * @param	[type]		$list: ...
	 * @return	[type]		...
	 */
	function addExtToList($extKey,$list)	{
		global $TYPO3_LOADED_EXT;

		$conf = $list[$extKey]["EM_CONF"];

		if ($conf["dependencies"])	{
			$dep = t3lib_div::trimExplode(",",$conf["dependencies"],1);
			while(list(,$depK)=each($dep))	{
				if (!t3lib_extMgm::isLoaded($depK))	{
					if (!isset($list[$depK]))	{
						$msg = "Extension '".$depK."' was not available in the system. Please import it from the TYPO3 Extension Repository.";
					} else {
						$msg = "Extension '".$depK."' (".$list[$depK]["EM_CONF"]["title"].") was not installed. Please installed it first.";
					}
					$this->content.=$this->doc->section("Dependency Error",$msg,0,1,2);
					return -1;
				}
			}
		}
		
		if ($conf["conflicts"])	{
			$dep = t3lib_div::trimExplode(",",$conf["conflicts"],1);
			while(list(,$depK)=each($dep))	{
				if (t3lib_extMgm::isLoaded($depK))	{
					$msg = "The extention '".$extKey."' and '".$depK."' (".$list[$depK]["EM_CONF"]["title"].") will conflict with each other. Please remove '".$depK."' if you want to install '".$extKey."'.";
					$this->content.=$this->doc->section("Conflict Error",$msg,0,1,2);
					return -1;
				}
			}
		}

		$listArr = array_keys($TYPO3_LOADED_EXT);
		if ($conf["priority"]=="top")	{
			array_unshift($listArr,$extKey);
		} else {
			$listArr[]=$extKey;
		}
		$listArr = $this->managesPriorities($listArr,$list);
		$listArr = $this->removeRequiredExtFromListArr($listArr);
		$list = implode(",",array_unique($listArr));
		return $list;
	}

	/**
	 * Remove extension from list and returns list. If -1 is returned, an error happend.
	 * Checks dependencies etc.
	 * 
	 * @param	[type]		$extKey: ...
	 * @param	[type]		$list: ...
	 * @return	[type]		...
	 */
	function removeExtFromList($extKey,$list)	{
		global $TYPO3_LOADED_EXT;
		
		$depList=array();
		$listArr = array_keys($TYPO3_LOADED_EXT);
		reset($listArr);
		while(list($k,$ext)=each($listArr))	{
			if ($list[$ext]["EM_CONF"]["dependencies"])	{
				$dep = t3lib_div::trimExplode(",",$list[$ext]["EM_CONF"]["dependencies"],1);
				if (in_array($extKey,$dep))	{
					$depList[]=$ext;
				}
			}
			if (!strcmp($ext,$extKey))	unset($listArr[$k]);
		}

		if (count($depList))	{
			$msg = "The extension(s) '".implode(", ",$depList)."' depends on the extension you are trying to remove. The operation was not completed.";
			$this->content.=$this->doc->section("Dependency Error",$msg,0,1,2);
			return -1;
		} else {
			$listArr = $this->removeRequiredExtFromListArr($listArr);
			$list = implode(",",array_unique($listArr));
			return $list;
		}
	}

	/**
	 * This removes any required extensions from the $listArr - they should NOT be added to the common extension list, because they are found already in "requiredExt" list
	 * 
	 * @param	[type]		$listArr: ...
	 * @return	[type]		...
	 */
	function removeRequiredExtFromListArr($listArr)	{
		reset($listArr);
		while(list($k,$ext)=each($listArr))	{
			if (in_array($ext,$this->requiredExt) || !strcmp($ext,"_CACHEFILE"))	unset($listArr[$k]);
		}
		return $listArr;
	}

	/**
	 * Traverse the array and arranges extension in the priority order they should be in
	 * 
	 * @param	[type]		$listArr: ...
	 * @param	[type]		$list: ...
	 * @return	[type]		...
	 */
	function managesPriorities($listArr,$list)	{
		reset($listArr);
		$levels=array(
			"top"=>array(),
			"middle"=>array(),
			"bottom"=>array(),
		);
		while(list($k,$ext)=each($listArr))	{
			$prio=trim($list[$ext]["EM_CONF"]["priority"]);
			switch((string)$prio)	{
				case "top":
				case "bottom":
					$levels[$prio][]=$ext;
				break;
				default:
					$levels["middle"][]=$ext;
				break;
			}
		}
		return array_merge(
			$levels["top"],
			$levels["middle"],
			$levels["bottom"]
		);
	}

	/**
	 * Returns the list of available (installed) extensions
	 * 
	 * @return	[type]		...
	 */
	function getInstalledExtensions()	{
		$list=array();
		$cat=$this->defaultCategories;
		
		$path = PATH_site.TYPO3_mainDir."sysext/";
		list($list,$cat) = $this->getInstExtList($path,$list,$cat,"S");

		$path = PATH_site.TYPO3_mainDir."ext/";
		list($list,$cat) = $this->getInstExtList($path,$list,$cat,"G");

		$path = PATH_site."typo3conf/ext/";
		list($list,$cat) = $this->getInstExtList($path,$list,$cat,"L");

		return array($list,$cat);
	}

	/**
	 * Gathers all extensions in $path
	 * 
	 * @param	[type]		$path: ...
	 * @param	[type]		$list: ...
	 * @param	[type]		$cat: ...
	 * @param	[type]		$type: ...
	 * @return	[type]		...
	 */
	function getInstExtList($path,$list,$cat,$type)	{
		if (@is_dir($path))	{
			$globalExt = t3lib_div::get_dirs($path);
			if (is_array($globalExt))	{
				reset($globalExt);
				while(list(,$eKey)=each($globalExt))	{
					if (@is_file($path.$eKey."/ext_emconf.php"))	{
						$emConf = $this->includeEMCONF($path.$eKey."/ext_emconf.php",$eKey);
						if (is_array($emConf))	{
#							unset($emConf["_md5_values_when_last_written"]);		// Trying to save space - hope this doesn't break anything. Shaves of maybe 100K!
#							unset($emConf["description"]);		// Trying to save space - hope this doesn't break anything
							if (is_array($list[$eKey]))	{
								$list[$eKey]=array("doubleInstall"=>$list[$eKey]["doubleInstall"]);
							}
							$list[$eKey]["doubleInstall"].=$type;
							$list[$eKey]["type"]=$type;
							$list[$eKey]["EM_CONF"] = $emConf;
#							$list[$eKey]["files"] = array_keys(array_flip(t3lib_div::getFilesInDir($path.$eKey)));	// Shaves off a little by using num-indexes
							$list[$eKey]["files"] = t3lib_div::getFilesInDir($path.$eKey);

							$cat = $this->setCat($cat,$list,$eKey);
						}
					}
				}
			}
		}
		return array($list,$cat);
	}
	
	/**
	 * Maps remote extensions information into $cat/$list arrays for listing
	 * 
	 * @param	[type]		$listArr: ...
	 * @return	[type]		...
	 */
	function getImportExtList($listArr)	{
		$list=array();
		$cat=$this->defaultCategories;
		
		if (is_array($listArr))	{
			reset($listArr);
			while(list(,$dat)=each($listArr))	{
				$eKey = $dat["extension_key"];
				$list[$eKey]["type"]="_";
				$list[$eKey]["extRepUid"]= $dat["uid"];
				$list[$eKey]["_STAT_IMPORT"]= $dat["_STAT_IMPORT"];
				$list[$eKey]["_ACCESS"]= $dat["_ACCESS"];
				$list[$eKey]["_ICON"]= $dat["_ICON"];
				$list[$eKey]["_MEMBERS_ONLY"]= $dat["_MEMBERS_ONLY"];
				$list[$eKey]["EM_CONF"] = array(
					"title" => $dat["emconf_title"],
					"description" => $dat["emconf_description"],
					"category" => $dat["emconf_category"],
					"shy" => $dat["emconf_shy"],
					"dependencies" => $dat["emconf_dependencies"],
					"state" => $dat["emconf_state"],
					"private" => $dat["emconf_private"],
					"uploadfolder" => $dat["emconf_uploadfolder"],
					"createDirs" => $dat["emconf_createDirs"],
					"modify_tables" => $dat["emconf_modify_tables"],
					"module" => $dat["emconf_module"],
					"lockType" => $dat["emconf_lockType"],
					"clearCacheOnLoad" => $dat["emconf_clearCacheOnLoad"],
					"priority" => $dat["emconf_priority"],
					"version" => $dat["version"],
					"internal" => $dat["emconf_internal"],
					"author" => $dat["emconf_author"],
					"author_company" => $dat["emconf_author_company"],

					"_typo3_ver" => $dat["upload_typo3_version"],
					"_php_ver" => $dat["upload_php_version"],
					"_size" => t3lib_div::formatSize($dat["datasize"])."/".t3lib_div::formatSize($dat["datasize_gz"]),
				);
				$cat = $this->setCat($cat,$list,$eKey);
			}
		}
		return array($list,$cat);
	}

	/**
	 * Set category for extension listing
	 * 
	 * @param	[type]		$cat: ...
	 * @param	[type]		$list: ...
	 * @param	[type]		$eKey: ...
	 * @return	[type]		...
	 */
	function setCat($cat,$list,$eKey)	{
		$cat["cat"][$list[$eKey]["EM_CONF"]["category"]][$eKey]=$list[$eKey]["EM_CONF"]["title"];
		$cat["author_company"][$list[$eKey]["EM_CONF"]["author"].($list[$eKey]["EM_CONF"]["author_company"]?", ".$list[$eKey]["EM_CONF"]["author_company"]:"")][$eKey]=$list[$eKey]["EM_CONF"]["title"];
		$cat["state"][$list[$eKey]["EM_CONF"]["state"]][$eKey]=$list[$eKey]["EM_CONF"]["title"];
		$cat["private"][$list[$eKey]["EM_CONF"]["private"]?1:0][$eKey]=$list[$eKey]["EM_CONF"]["title"];
		$cat["type"][$list[$eKey]["type"]][$eKey]=$list[$eKey]["EM_CONF"]["title"];
		
		if ($list[$eKey]["EM_CONF"]["dependencies"])	{
			$depItems = t3lib_div::trimExplode(",",$list[$eKey]["EM_CONF"]["dependencies"],1);
			while(list(,$depKey)=each($depItems))	{
				$cat["dep"][$depKey][$eKey]=$list[$eKey]["EM_CONF"]["title"];
			}
		}
		
		return $cat;
	}

	/**
	 * Processes return-data from online repository.
	 * 
	 * @param	[type]		$TER_CMD: ...
	 * @return	[type]		...
	 */
	function processRepositoryReturnData($TER_CMD)	{
		switch((string)$TER_CMD["cmd"])	{
			case "EM_CONF":
				list($list,$cat)=$this->getInstalledExtensions();
				$extKey = $TER_CMD["extKey"];
				
				$data = $this->decodeServerData($TER_CMD["returnValue"]);
				$EM_CONF=$data[0];
				$EM_CONF["_md5_values_when_last_written"] = serialize($this->serverExtensionMD5Array($extKey,$list[$extKey]));
				$emConfFileContent = $this->construct_ext_emconf_file($extKey,$EM_CONF);
				if (is_array($list[$extKey]) && $emConfFileContent)	{
					$absPath = $this->getExtPath($extKey,$list[$extKey]);
					$emConfFileName =$absPath."ext_emconf.php";
					if (@is_file($emConfFileName))	{
						t3lib_div::writeFile($emConfFileName,$emConfFileContent);
						return "'".substr($emConfFileName,strlen($absPath))."' was updated with a cleaned up EM_CONF array.";
					} else die("Error: No file '".$emConfFileName."' found.");
				} else  die("Error: No EM_CONF content prepared...");
			break;
		}
	}

	/**
	 * Forces update of local EM_CONF. This will renew the information of changed files.
	 * 
	 * @param	[type]		$extKey: ...
	 * @param	[type]		$info: ...
	 * @return	[type]		...
	 */
	function updateLocalEM_CONF($extKey,$info)	{
		$EM_CONF=$info["EM_CONF"];
		$EM_CONF["_md5_values_when_last_written"] = serialize($this->serverExtensionMD5Array($extKey,$info));
		$emConfFileContent = $this->construct_ext_emconf_file($extKey,$EM_CONF);

		if ($emConfFileContent)	{
			$absPath = $this->getExtPath($extKey,$info);
			$emConfFileName =$absPath."ext_emconf.php";
#debug($emConfFileContent);
			if (@is_file($emConfFileName))	{
				t3lib_div::writeFile($emConfFileName,$emConfFileContent);
				return "'".substr($emConfFileName,strlen($absPath))."' was updated with a cleaned up EM_CONF array.";
			} else die("Error: No file '".$emConfFileName."' found.");
		}
	}

	/**
	 * Returns the $EM_CONF array from an extensions ext_emconf.php file
	 * 
	 * @param	[type]		$path: ...
	 * @param	[type]		$_EXTKEY: ...
	 * @return	[type]		...
	 */
	function includeEMCONF($path,$_EXTKEY)	{
		include($path);
#if ($_EXTKEY=="viewpage") debug(array($EM_CONF[$_EXTKEY],$_EXTKEY,t3lib_div::getUrl($path)));
		return $EM_CONF[$_EXTKEY];
	}

	/**
	 * Returns subtitles for the extension listings
	 * 
	 * @param	[type]		$listOrder: ...
	 * @param	[type]		$key: ...
	 * @return	[type]		...
	 */
	function listOrderTitle($listOrder,$key)	{
		switch($listOrder)	{
			case "cat":
				return isset($this->categories[$key])?$this->categories[$key]:'<em>['.$key.']</em>';
			break;
			case "author_company":
				return $key;
			break;
			case "dep":
				return $key;
			break;
			case "state":
				return $this->states[$key];
			break;
			case "private":
				return $key?"Private (Password required to download from repository)":"Public (Everyone can download this from Extention repository)";
			break;
			case "type":
				return $this->typeDescr[$key];
			break;
		}
	}

	/**
	 * Returns version information
	 * 
	 * @param	[type]		$v: ...
	 * @param	[type]		$mode: ...
	 * @return	[type]		...
	 */
	function makeVersion($v,$mode)	{
		$vDat = $this->renderVersion($v);
		return $vDat["version_".$mode];
	}

	/**
	 * Parses the version number x.x.x and returns an array with the various parts.
	 * 
	 * @param	[type]		$v: ...
	 * @param	[type]		$raise: ...
	 * @return	[type]		...
	 */
	function renderVersion($v,$raise="")	{
		$parts = t3lib_div::intExplode(".",$v."..");
		$parts[0] = t3lib_div::intInRange($parts[0],0,999);
		$parts[1] = t3lib_div::intInRange($parts[1],0,999);
		$parts[2] = t3lib_div::intInRange($parts[2],0,999);
		switch((string)$raise)	{
			case "main":
				$parts[0]++;
				$parts[1]=0;
				$parts[2]=0;
			break;
			case "sub":
				$parts[1]++;
				$parts[2]=0;
			break;
			case "dev":
				$parts[2]++;
			break;
		}
		$res=array();
		$res["version"]=$parts[0].".".$parts[1].".".$parts[2];
		$res["version_int"]=intval(str_pad($parts[0],3,"0",STR_PAD_LEFT).str_pad($parts[1],3,"0",STR_PAD_LEFT).str_pad($parts[2],3,"0",STR_PAD_LEFT));
		$res["version_main"]=$parts[0];
		$res["version_sub"]=$parts[1];
		$res["version_dev"]=$parts[2];
		return $res;
	}

	/**
	 * Returns the unique TYPO3 Install Identification (sent to repository for statistics)
	 * 
	 * @return	[type]		...
	 */
	function T3instID()	{
		return $GLOBALS["TYPO3_CONF_VARS"]["SYS"]["T3instID"];
	}

	/**
	 * Returns the return Url of the current script (for repository exchange)
	 * 
	 * @return	[type]		...
	 */
	function makeReturnUrl()	{
		return t3lib_div::getIndpEnv("TYPO3_REQUEST_URL");
	}

	/**
	 * Compiles the additional GET-parameters sent to the repository during requests for information.
	 * 
	 * @return	[type]		...
	 */
	function repTransferParams()	{
		return "&tx_extrep[T3instID]=".rawurlencode($this->T3instID()).
			"&tx_extrep[returnUrl]=".rawurlencode($this->makeReturnUrl()).
			"&tx_extrep[gzcompress]=".$this->gzcompress.
			"&tx_extrep[user][fe_u]=".$this->fe_user["username"].
			"&tx_extrep[user][fe_p]=".$this->fe_user["password"];
	}
	
	/**
	 * Returns upload folder for extension
	 * 
	 * @param	[type]		$eKey: ...
	 * @return	[type]		...
	 */
	function ulFolder($eKey)	{
		return "uploads/tx_".str_replace("_","",$eKey)."/";
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function removeButton()	{
		return '<img src="uninstall.gif" width="16" height="16" title="Remove extension" align="top" alt="" />';
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function installButton()	{
		return '<img src="install.gif" width="16" height="16" title="Install extension..." align="top" alt="" />';
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function importAtAll()	{
		return ($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["allowGlobalInstall"] || $GLOBALS["TYPO3_CONF_VARS"]["EXT"]["allowLocalInstall"]);
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function noImportMsg()	{
		return '<img src="'.$this->doc->backPath.'gfx/icon_warning2.gif" width="18" height="16" align="top" alt="" /><strong>Import to both local and global path is disabled in TYPO3_CONF_VARS!</strong>';
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$type: ...
	 * @param	[type]		$lockType: ...
	 * @return	[type]		...
	 */
	function importAsType($type,$lockType="")	{
		switch($type)	{
			case "G":
				return $GLOBALS["TYPO3_CONF_VARS"]["EXT"]["allowGlobalInstall"] && (!$lockType || !strcmp($lockType,$type));
			break;
			case "L":
				return $GLOBALS["TYPO3_CONF_VARS"]["EXT"]["allowLocalInstall"] && (!$lockType || !strcmp($lockType,$type));
			break;
			case "S":
				return $this->systemInstall;
			break;
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$type: ...
	 * @param	[type]		$lockType: ...
	 * @return	[type]		...
	 */
	function deleteAsType($type)	{
		switch($type)	{
			case "G":
				return $GLOBALS["TYPO3_CONF_VARS"]["EXT"]["allowGlobalInstall"];
			break;
			case "L":
				return $GLOBALS["TYPO3_CONF_VARS"]["EXT"]["allowLocalInstall"];
			break;
		}
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/mod/tools/em/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/mod/tools/em/index.php"]);
}









// Make instance:
$SOBE = t3lib_div::makeInstance("SC_mod_tools_em_index");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>