<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Main frameset of the TYPO3 backend
 * Sending the GET var "alt_main.php?edit=[page id]" will load the page id in the editing module configured.
 *
 *
 *    IMPORTANT!
 *
 *    This file is deprecated since TYPO3 4.2 and will be removed with TYPO3 4.4
 *
 *
 *
 *
 *
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML Compliant (almost)
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   72: class SC_alt_main
 *   91:     function init()
 *  113:     function generateJScode()
 *  386:     function editPageHandling()
 *  437:     function startModule()
 *  459:     function main()
 *  533:     function printContent()
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require ('init.php');
require ('template.php');
require_once ('class.alt_menu_functions.inc');
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');




/**
 * Script Class for rendering of the main frameset for the TYPO3 backend.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_main {

		// Internal, dynamic:
	var $content;
	var $mainJScode;

	/**
	 * Object for backend modules, load modules-object
	 *
	 * @var t3lib_loadModules
	 */
	var $loadModules;		// Load modules-object

	/**
	 * Menu functions object
	 *
	 * @var alt_menu_functions
	 */
	var $alt_menuObj;		// Menu functions object.

		// Internal, static:
	var $leftMenuFrameW = 130;
	var $selMenuFrame = 130;
	var $topFrameH = 32;
	var $shortcutFrameH = 30;

	/**
	 * Initialization of the script class
	 *
	 * @return	void
	 */
	function init()	{
		global $TBE_MODULES,$TBE_STYLES;

			// Initializes the backend modules structure for use later.
		$this->loadModules = t3lib_div::makeInstance('t3lib_loadModules');
		$this->loadModules->load($TBE_MODULES);

			// Instantiates the menu object which will generate some JavaScript for the goToModule() JS function in this frameset.
		$this->alt_menuObj = t3lib_div::makeInstance('alt_menu_functions');

			// Check for distances defined in the styles array:
		if ($TBE_STYLES['dims']['leftMenuFrameW'])		$this->leftMenuFrameW = $TBE_STYLES['dims']['leftMenuFrameW'];
		if ($TBE_STYLES['dims']['topFrameH'])			$this->topFrameH = $TBE_STYLES['dims']['topFrameH'];
		if ($TBE_STYLES['dims']['shortcutFrameH'])		$this->shortcutFrameH = $TBE_STYLES['dims']['shortcutFrameH'];
		if ($TBE_STYLES['dims']['selMenuFrame'])		$this->selMenuFrame = $TBE_STYLES['dims']['selMenuFrame'];
	}

	/**
	 * Generates the JavaScript code for the frameset.
	 *
	 * @return	void
	 */
	function generateJScode()	{
		global $BE_USER,$LANG;

		$pt3 = t3lib_div::dirname(t3lib_div::getIndpEnv('SCRIPT_NAME')).'/';
		$goToModule_switch = $this->alt_menuObj->topMenu($this->loadModules->modules,0,"",4);
		$fsMod = implode(chr(10),$this->alt_menuObj->fsMod);

			// If another page module was specified, replace the default Page module with the new one
		$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
		$pageModule = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

		$menuFrameName = 'menu';
		if ($GLOBALS['BE_USER']->uc['noMenuMode'] === 'icons') {
			$menuFrameName = 'topmenuFrame';
		}

		$this->mainJScode='
	/**
	 * Function similar to PHPs  rawurlencode();
	 */
	function rawurlencode(str)	{	//
		var output = escape(str);
		output = str_replace("*","%2A", output);
		output = str_replace("+","%2B", output);
		output = str_replace("/","%2F", output);
		output = str_replace("@","%40", output);
		return output;
	}

	/**
	 * String-replace function
	 */
	function str_replace(match,replace,string)	{	//
		var input = ""+string;
		var matchStr = ""+match;
		if (!matchStr)	{return string;}
		var output = "";
		var pointer=0;
		var pos = input.indexOf(matchStr);
		while (pos!=-1)	{
			output+=""+input.substr(pointer, pos-pointer)+replace;
			pointer=pos+matchStr.length;
			pos = input.indexOf(match,pos+1);
		}
		output+=""+input.substr(pointer);
		return output;
	}

	/**
	 * TypoSetup object.
	 */
	function typoSetup()	{	//
		this.PATH_typo3 = "'.$pt3.'";
		this.PATH_typo3_enc = "'.rawurlencode($pt3).'";
		this.username = "'.htmlspecialchars($BE_USER->user['username']).'";
		this.uniqueID = "'.t3lib_div::shortMD5(uniqid('')).'";
		this.navFrameWidth = 0;
	}
	var TS = new typoSetup();

	/**
	 * Functions for session-expiry detection:
	 */
	function busy()	{	//
		this.loginRefreshed = busy_loginRefreshed;
		this.checkLoginTimeout = busy_checkLoginTimeout;
		this.openRefreshWindow = busy_OpenRefreshWindow;
		this.busyloadTime=0;
		this.openRefreshW=0;
		this.reloginCancelled=0;
	}
	function busy_loginRefreshed()	{	//
		var date = new Date();
		this.busyloadTime = Math.floor(date.getTime()/1000);
		this.openRefreshW=0;
	}
	function busy_checkLoginTimeout()	{	//
		var date = new Date();
		var theTime = Math.floor(date.getTime()/1000);
		if (theTime > this.busyloadTime+'.intval($BE_USER->auth_timeout_field).'-30)	{
			return true;
		}
	}
	function busy_OpenRefreshWindow()	{	//
		vHWin=window.open("login_frameset.php","relogin_"+TS.uniqueID,"height=350,width=700,status=0,menubar=0,location=1");
		vHWin.focus();
		this.openRefreshW=1;
	}
	function busy_checkLoginTimeout_timer()	{	//
		if (busy.checkLoginTimeout() && !busy.reloginCancelled && !busy.openRefreshW)	{
			if (confirm('.$GLOBALS['LANG']->JScharCode($LANG->sL('LLL:EXT:lang/locallang_core.php:mess.refresh_login')).'))	{
				busy.openRefreshWindow();
			} else	{
				busy.reloginCancelled = 1;
			}
		}
		window.setTimeout("busy_checkLoginTimeout_timer();",2*1000);	// Each 2nd second is enough for checking. The popup will be triggered 10 seconds before the login expires (see above, busy_checkLoginTimeout())

			// Detecting the frameset module navigation frame widths (do this AFTER setting new timeout so that any errors in the code below does not prevent another time to be set!)
		if (top && top.content && top.content.nav_frame && top.content.nav_frame.document && top.content.nav_frame.document.body)	{
			TS.navFrameWidth = (top.content.nav_frame.document.documentElement && top.content.nav_frame.document.documentElement.clientWidth) ? top.content.nav_frame.document.documentElement.clientWidth : top.content.nav_frame.document.body.clientWidth;
		}
	}

	/**
	 * Launcing information window for records/files (fileref as "table" argument)
	 */
	function launchView(table,uid,bP)	{	//
		var backPath= bP ? bP : "";
		var thePreviewWindow="";
		thePreviewWindow = window.open(TS.PATH_typo3+"show_item.php?table="+encodeURIComponent(table)+"&uid="+encodeURIComponent(uid),"ShowItem"+TS.uniqueID,"height=400,width=550,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
		if (thePreviewWindow && thePreviewWindow.focus)	{
			thePreviewWindow.focus();
		}
	}

	/**
	 * Opens plain window with url
	 */
	function openUrlInWindow(url,windowName)	{	//
		regularWindow = window.open(url,windowName,"status=1,menubar=1,resizable=1,location=1,directories=0,scrollbars=1,toolbar=1");
		regularWindow.focus();
		return false;
	}

	/**
	 * Loads a URL in the topmenuFrame
	 */
	function loadTopMenu(url)	{	//
		top.topmenuFrame.location = url;
	}

	/**
	 * Loads a page id for editing in the page edit module:
	 */
	function loadEditId(id,addGetVars)	{	//
		top.fsMod.recentIds["web"]=id;
		top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_0";		// For highlighting

		if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)	{
			top.content.nav_frame.refresh_nav();
		}

		top.goToModule("'.$pageModule.'", 0, addGetVars?addGetVars:"");
	}

	/**
	 * Returns incoming URL (to a module) unless nextLoadModuleUrl is set. If that is the case nextLoadModuleUrl is returned (and cleared)
	 * Used by the shortcut frame to set a "intermediate URL"
	 */
	var nextLoadModuleUrl="";
	function getModuleUrl(inUrl)	{	//
		var nMU;
		if (top.nextLoadModuleUrl)	{
			nMU=top.nextLoadModuleUrl;
			top.nextLoadModuleUrl="";
			return nMU;
		} else {
			return inUrl;
		}
	}

	/**
	 * Print properties of an object
	 */
	function debugObj(obj,name)	{	//
		var acc;
		for (i in obj) {
			if (obj[i])	{
				acc+=i+":  "+obj[i]+"\n";
			}
		}
		alert("Object: "+name+"\n\n"+acc);
	}

	/**
	 * Initialize login expiration warning object
	 */
	var busy = new busy();
	busy.loginRefreshed();
	busy_checkLoginTimeout_timer();


	/**
	 * Highlight module:
	 */
	var currentlyHighLightedId = "";
	var currentlyHighLighted_restoreValue = "";
	var currentlyHighLightedMain = "";
	function highlightModuleMenuItem(trId, mainModule)	{	//
		currentlyHighLightedMain = mainModule;
			// Get document object:
		if (top.menu && top.menu.document)	{
			var docObj = top.menu.document;
			var HLclass = mainModule ? "c-mainitem-HL" : "c-subitem-row-HL";
		} else if (top.topmenuFrame && top.topmenuFrame.document)	{
			var docObj = top.topmenuFrame.document;
			var HLclass = mainModule ? "c-mainitem-HL" : "c-subitem-HL";
		}

		if (docObj)	{
				// Reset old:
			if (currentlyHighLightedId && docObj.getElementById(currentlyHighLightedId))	{
				docObj.getElementById(currentlyHighLightedId).attributes.getNamedItem("class").nodeValue = currentlyHighLighted_restoreValue;
			}
				// Set new:
			currentlyHighLightedId = trId;
			if (currentlyHighLightedId && docObj.getElementById(currentlyHighLightedId))	{
				var classAttribObject = docObj.getElementById(currentlyHighLightedId).attributes.getNamedItem("class");
				currentlyHighLighted_restoreValue = classAttribObject.nodeValue;
				classAttribObject.nodeValue = HLclass;
			}
		}
	}

	/**
	 * Function restoring previous selection in left menu after clearing cache
	 */
	function restoreHighlightedModuleMenuItem() {	//
		if (currentlyHighLightedId) {
			highlightModuleMenuItem(currentlyHighLightedId,currentlyHighLightedMain);
		}
	}

	/**
	 * Wrapper for the actual goToModule function in the menu frame
	 */
	var currentModuleLoaded = "";
	function goToModule(modName, cMR_flag, addGetVars)	{	//
		if (top.'.$menuFrameName.' && top.'.$menuFrameName.'.goToModule) {
			currentModuleLoaded = modName;
			top.'.$menuFrameName.'.goToModule(modName, cMR_flag, addGetVars);
		} else {
			window.setTimeout(function() { top.goToModule(modName, cMR_flag, addGetVars); }, 500);
		}
	}

	/**
	 * reloads the menu frame
	 */
	function refreshMenu() {
		top.'.$menuFrameName.'.location.href = top.'.$menuFrameName.'.document.URL
	}

	/**
	 * Frameset Module object
	 *
	 * Used in main modules with a frameset for submodules to keep the ID between modules
	 * Typically that is set by something like this in a Web>* sub module:
	 *		if (top.fsMod) top.fsMod.recentIds["web"] = "\'.intval($this->id).\'";
	 * 		if (top.fsMod) top.fsMod.recentIds["file"] = "...(file reference/string)...";
	 */
	function fsModules()	{	//
		this.recentIds=new Array();					// used by frameset modules to track the most recent used id for list frame.
		this.navFrameHighlightedID=new Array();		// used by navigation frames to track which row id was highlighted last time
		this.currentMainLoaded="";
		this.currentBank="0";
	}
	var fsMod = new fsModules();
	'.$fsMod.'

		// Used by Frameset Modules
	var condensedMode = '.($BE_USER->uc['condensedMode']?1:0).';
	var currentSubScript = "";
	var currentSubNavScript = "";

		// Used for tab-panels:
	var DTM_currentTabs = new Array();
		';

			// Check editing of page:
		$this->editPageHandling();
		$this->startModule();
	}

	/**
	 * Checking if the "&edit" variable was sent so we can open for editing the page.
	 * Code based on code from "alt_shortcut.php"
	 *
	 * @return	void
	 */
	function editPageHandling()	{
		global $BE_USER;

		if (!t3lib_extMgm::isLoaded('cms'))	return;

			// EDIT page:
		$editId = preg_replace('/[^[:alnum:]_]/','',t3lib_div::_GET('edit'));
		$theEditRec = '';

		if ($editId)	{

				// Looking up the page to edit, checking permissions:
			$where = ' AND ('.$BE_USER->getPagePermsClause(2).' OR '.$BE_USER->getPagePermsClause(16).')';
			if (t3lib_div::testInt($editId))	{
				$theEditRec = t3lib_BEfunc::getRecordWSOL('pages',$editId,'*',$where);
			} else {
				$records = t3lib_BEfunc::getRecordsByField('pages','alias',$editId,$where);
				if (is_array($records))	{
					reset($records);
					$theEditRec = current($records);
					t3lib_BEfunc::workspaceOL('pages', $theEditRec);
				}
			}

				// If the page was accessible, then let the user edit it.
			if (is_array($theEditRec) && $BE_USER->isInWebMount($theEditRec['uid']))	{
					// Setting JS code to open editing:
				$this->mainJScode.='
		// Load page to edit:
	window.setTimeout("top.loadEditId('.intval($theEditRec['uid']).');",500);
			';
					// Checking page edit parameter:
				if(!$BE_USER->getTSConfigVal('options.shortcut_onEditId_dontSetPageTree')) {

						// Expanding page tree:
					t3lib_BEfunc::openPageTree(intval($theEditRec['pid']),!$BE_USER->getTSConfigVal('options.shortcut_onEditId_keepExistingExpanded'));
				}
			} else {
				$this->mainJScode.='
		// Warning about page editing:
	alert('.$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->getLL('noEditPage'),$editId)).');
			';
			}
		}
	}

	/**
	 * Sets the startup module from either GETvars module and mpdParams or user configuration.
	 *
	 * @return	void
	 */
	function startModule() {
		global $BE_USER;
		$module = preg_replace('/[^[:alnum:]_]/','',t3lib_div::_GET('module'));

		if (!$module)	{
			if ($BE_USER->uc['startModule'])	{
				$module = $BE_USER->uc['startModule'];
			} elseif ($BE_USER->uc['startInTaskCenter'])	{
				$module = 'user_task';
			}
		}

		$params = t3lib_div::_GET('modParams');
		if ($module) {
			$this->mainJScode.='
		// open in module:
	top.goToModule(\''.$module.'\',false,'.t3lib_div::quoteJSvalue($params).');
			';
		}
	}


	/**
	 * Creates the header and frameset of the backend interface
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$TYPO3_CONF_VARS;

			// Set doktype:
		$GLOBALS['TBE_TEMPLATE']->docType='xhtml_frames';

			// Make JS:
		$this->generateJScode();
		$GLOBALS['TBE_TEMPLATE']->JScode= '
			<script type="text/javascript" src="md5.js"></script>
			<script type="text/javascript" src="../t3lib/jsfunc.evalfield.js"></script>
			<script type="text/javascript" src="js/backend.js"></script>
			';
		$GLOBALS['TBE_TEMPLATE']->JScode.=$GLOBALS['TBE_TEMPLATE']->wrapScriptTags($this->mainJScode);

			// Title:
		$title = $TYPO3_CONF_VARS['SYS']['sitename'] ? $TYPO3_CONF_VARS['SYS']['sitename'].' [TYPO3 '.TYPO3_version.']' : 'TYPO3 '.TYPO3_version;

			// Start page header:
		$this->content.=$GLOBALS['TBE_TEMPLATE']->startPage($title);

			// Creates frameset
		$fr_content = '<frame name="content" src="alt_intro.php" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto" noresize="noresize" />';
		$fr_toplogo = '<frame name="toplogo" src="alt_toplogo.php" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />';
		$fr_topmenu = '<frame name="topmenuFrame" src="alt_topmenu_dummy.php" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />';

		$shortcutFrame=array();
		if ($BE_USER->getTSConfigVal('options.shortcutFrame'))	{
			$shortcutFrame['rowH']=','.$this->shortcutFrameH;
			$shortcutFrame['frameDef']='<frame name="shortcutFrame" src="alt_shortcut.php" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />';
		}

			// XHTML notice: ' framespacing="0" frameborder="0" border="0"' in FRAMESET elements breaks compatibility with XHTML-frames, but HOW ELSE can I control the visual appearance?
		if ($GLOBALS['BE_USER']->uc['noMenuMode'])	{
			$this->content.= '
			<frameset rows="'.$this->topFrameH.',*'.$shortcutFrame['rowH'].'" framespacing="0" frameborder="0" border="0">
				'.(!strcmp($BE_USER->uc['noMenuMode'],'icons') ? '
				<frameset cols="'.$this->leftMenuFrameW.',*" framespacing="0" frameborder="0" border="0">
					'.$fr_toplogo.'
					'.$fr_topmenu.'
				</frameset>' : '
				<frameset cols="'.$this->leftMenuFrameW.','.$this->selMenuFrame.',*" framespacing="0" frameborder="0" border="0">
					'.$fr_toplogo.'
					<frame name="menu" src="alt_menu_sel.php" scrolling="no" noresize="noresize" />
					'.$fr_topmenu.'
				</frameset>').'
				'.$fr_content.'
				'.$shortcutFrame['frameDef'].'
			</frameset>
			';
		} else {
			$this->content.='
			<frameset rows="'.$this->topFrameH.',*'.$shortcutFrame['rowH'].'" framespacing="0" frameborder="0" border="0">
				<frameset cols="'.$this->leftMenuFrameW.',*" framespacing="0" frameborder="0" border="0">
					'.$fr_toplogo.'
					'.$fr_topmenu.'
				</frameset>
				<frameset cols="'.$this->leftMenuFrameW.',*" framespacing="0" frameborder="0" border="0">
					<frame name="menu" src="alt_menu.php" marginwidth="0" marginheight="0" scrolling="auto" noresize="noresize" />
					'.$fr_content.'
				</frameset>
				'.$shortcutFrame['frameDef'].'
			</frameset>
			';
		}
		$this->content.='

</html>';
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_main.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_main.php']);
}



// ******************************
// Starting document output
// ******************************

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_main');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>