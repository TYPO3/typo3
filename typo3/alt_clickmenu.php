<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * Context menu
 *
 * The script is called in the top frame of the backend typically by a click on an icon for which a context menu should appear.
 * Either this script displays the context menu horizontally in the top frame or alternatively (default in MSIE, Mozilla) it writes the output to a <div>-layer in the calling document (which then appears as a layer/context sensitive menu)
 * Writing content back into a <div>-layer is necessary if we want individualized context menus with any specific content for any specific element.
 * Context menus can appear for either database elements or files 
 * The input to this script is basically the "&init" var which is divided by "|" - each part is a reference to table|uid|listframe-flag.
 *
 * If you want to integrate a context menu in your scripts, please see template::getContextMenuCode()
 *
 * $Id$ 
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  123: class clickMenu 
 *  154:     function init($item)	
 *  194:     function doDisplayTopFrameCM()	
 *
 *              SECTION: DATABASE
 *  222:     function printDBClickMenu($table,$uid)	
 *  309:     function printNewDBLevel($table,$uid)	
 *  346:     function externalProcessingOfDBMenuItems($menuItems)	
 *  358:     function processingByExtClassArray($menuItems,$table,$uid)	
 *  377:     function urlRefForCM($url,$retUrl='',$hideCM=1)	
 *  394:     function DB_copycut($table,$uid,$type)	
 *  417:     function DB_paste($table,$uid,$type,$elInfo)	
 *  438:     function DB_info($table,$uid)	
 *  454:     function DB_history($table,$uid)	
 *  473:     function DB_perms($table,$uid,$rec)	
 *  492:     function DB_db_list($table,$uid,$rec)	
 *  511:     function DB_moveWizard($table,$uid,$rec)	
 *  532:     function DB_newWizard($table,$uid,$rec)	
 *  550:     function DB_editAccess($table,$uid)	
 *  568:     function DB_editPageHeader($uid)	
 *  586:     function DB_edit($table,$uid)	
 *  625:     function DB_new($table,$uid)	
 *  650:     function DB_hideUnhide($table,$rec,$hideField)	
 *  674:     function DB_delete($table,$uid,$elInfo)	
 *  695:     function DB_view($id,$anchor='')	
 *
 *              SECTION: FILE
 *  724:     function printFileClickMenu($path)	
 *  788:     function externalProcessingOfFileMenuItems($menuItems)	
 *  802:     function FILE_launch($path,$script,$type,$image)	
 *  821:     function FILE_copycut($path,$type)	
 *  841:     function FILE_delete($path)	
 *  863:     function FILE_paste($path,$target,$elInfo)	
 *
 *              SECTION: COMMON
 *  903:     function printItems($menuItems,$item)	
 *  945:     function printLayerJScode($menuItems)	
 *  980:     function wrapColorTableCM($str)	
 *  995:     function menuItemsForTopFrame($menuItems)	
 * 1012:     function menuItemsForClickMenu($menuItems)	
 * 1047:     function linkItem($str,$icon,$onClick,$onlyCM=0,$dontHide=0)	
 * 1071:     function excludeIcon($iconCode)	
 * 1081:     function label($label)	
 * 1090:     function isCMlayers()	
 * 1100:     function frameLocation($str)	
 *
 *
 * 1125: class SC_alt_clickmenu 
 * 1143:     function init()	
 * 1226:     function main()	
 * 1266:     function printContent()	
 *
 * TOTAL FUNCTIONS: 41
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

 
require ('init.php');
require ('template.php');
require_once (PATH_t3lib.'class.t3lib_clipboard.php');
$LANG->includeLLFile('EXT:lang/locallang_misc.php');




/**
 * Class for generating the click menu
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 * @internal
 */
class clickMenu {

		// Internal, static: GPvar:
	var $cmLevel=0;				// Defines if the click menu is first level or second. Second means the click menu is triggered from another menu.
	var $CB;					// Clipboard array (submitted by eg. pressing the paste button)

		// Internal, static:
	var $backPath='';			// Backpath for scripts/images.
	var $listFrame=0;			// If set, the calling document should be in the listframe of a frameset.
	var $isDBmenu=0;			// If set, the menu is about database records, not files. (set if part 2 [1] of the item-var is NOT blank)
	var $alwaysContentFrame=0;	// If true, the "content" frame is always used for reference (when condensed mode is enabled)
	var $iParts=array();		// Stores the parts of the input $item string, splitted by "|"
	var $disabledItems=array();	// Contains list of keywords of items to disable in the menu
	var $dontDisplayTopFrameCM=0;	// If true, the context sensitive menu will not appear in the top frame, only as a layer.
	var $leftIcons=0;			// If true, Show icons on the left.
	var $extClassArray=array();		// Array of classes to be used for user processing of the menu content. This is for the API of adding items to the menu from outside.

		// Internal, dynamic:	
	var $elCount=0;				// Counter for elements in the menu. Used to number the name / id of the mouse-over icon.
	var $editPageIconSet=0;		// Set, when edit icon is drawn.
	var $editOK=0;				// Set to true, if editing of the element is OK.
	var $rec=array();			
	


	/**
	 * Initialize click menu
	 * 
	 * @param	string		Input "item" GET var.
	 * @return	string		The clickmenu HTML content
	 */
	function init($item)	{

			// Setting GPvars:
		$this->cmLevel = intval(t3lib_div::GPvar('cmLevel'));
		$this->CB = t3lib_div::GPvar('CB');


			// Explode the incoming command:
		$this->iParts = explode('|',$item);

			// Setting flags:
		if ($this->iParts[2])	$this->listFrame=1;
		if ($GLOBALS['BE_USER']->uc['condensedMode']) $this->alwaysContentFrame=1;
		if (strcmp($this->iParts[1],''))	$this->isDBmenu=1;

		$TSkey =($this->isDBmenu?'page':'folder').($this->listFrame?'List':'Tree');
		$this->disabledItems = t3lib_div::trimExplode(',',$GLOBALS['BE_USER']->getTSConfigVal('options.contextMenu.'.$TSkey.'.disableItems'),1);
		$this->leftIcons = $GLOBALS['BE_USER']->getTSConfigVal('options.contextMenu.options.leftIcons');

			// &cmLevel flag detected (2nd level menu)
		if (!$this->cmLevel)	{
				// Make 1st level clickmenu:
			if ($this->isDBmenu)	{
				return $this->printDBClickMenu($this->iParts[0],$this->iParts[1]);
			} else {
				return $this->printFileClickMenu($this->iParts[0]);
			}
		} else {
				// Make 2nd level clickmenu (only for DBmenus)
			if ($this->isDBmenu)	{
				return $this->printNewDBLevel($this->iParts[0],$this->iParts[1]);
			}
		}
	}

	/**
	 * Returns true if the menu should (also?) be displayed in topframe, not just <div>-layers
	 * 
	 * @return	boolean		
	 */
	function doDisplayTopFrameCM()	{
		return !$GLOBALS['SOBE']->doc->isCMlayers() || !$this->dontDisplayTopFrameCM;
	}












	/***************************************
	 *
	 * DATABASE
	 *
	 ***************************************/

	/**
	 * Make 1st level clickmenu:
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @return	string		HTML content
	 */
	function printDBClickMenu($table,$uid)	{
		global $TCA, $BE_USER;
		
			// Get record:
		$this->rec = t3lib_BEfunc::getRecord($table,$uid);
		$menuItems=array();
		$root=0;
		if ($table=='pages' && !strcmp($uid,'0'))	{	// Rootlevel
			$root=1;
		}

			// If record found (or root), go ahead and fill the $menuItems array which will contain data for the elements to render.
		if (is_array($this->rec) || $root)	{
				// Get permissions
			$lCP = $BE_USER->calcPerms(t3lib_BEfunc::getRecord('pages',($table=='pages'?$this->rec['uid']:$this->rec['pid'])));

				// View
			if (!in_array('view',$this->disabledItems))	{
				if ($table=='pages')	$menuItems['view']=$this->DB_view($uid);
				if ($table==$GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'])	$menuItems['view']=$this->DB_view($this->rec['pid'],'#'.$uid);
			}

				// Edit: 
			if(!$root && ($BE_USER->isPSet($lCP,$table,'edit')||$BE_USER->isPSet($lCP,$table,'editcontent')))	{
				if (!in_array('edit',$this->disabledItems))		$menuItems['edit']=$this->DB_edit($table,$uid);
				$this->editOK=1;
			}

				// New: 
			if (!in_array('new',$this->disabledItems) && $BE_USER->isPSet($lCP,$table,'new'))	$menuItems['new']=$this->DB_new($table,$uid);
			
				// Info:
			if(!in_array('info',$this->disabledItems) && !$root)	$menuItems['info']=$this->DB_info($table,$uid);

			$menuItems[]='spacer';
			
				// Copy:
			if(!in_array('copy',$this->disabledItems) && !$root)	$menuItems['copy']=$this->DB_copycut($table,$uid,'copy');
				// Cut:
			if(!in_array('cut',$this->disabledItems) && !$root)	$menuItems['cut']=$this->DB_copycut($table,$uid,'cut');

				// Paste:
			$elFromAllTables = count($this->clipObj->elFromTable(''));
			if (!in_array('paste',$this->disabledItems) && $elFromAllTables)	{
				$selItem = $this->clipObj->getSelectedRecord();
				$elInfo=array(
					$selItem['_RECORD_TITLE'],
					($root?$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']:t3lib_div::fixed_lgd(t3lib_BEfunc::getRecordTitle($table,$this->rec),$GLOBALS['BE_USER']->uc['titleLen'])),
					$this->clipObj->currentMode()
				);
				if ($table=='pages' && ($lCP & 8))	{
					if ($elFromAllTables)	$menuItems['pasteinto']=$this->DB_paste('',$uid,'into',$elInfo);
				}

				$elFromTable = count($this->clipObj->elFromTable($table));
				if (!$root && $elFromTable  && $TCA[$table]['ctrl']['sortby'])	$menuItems['pasteafter']=$this->DB_paste($table,-$uid,'after',$elInfo);
			}

				// Delete:
			$elInfo=array(t3lib_div::fixed_lgd(t3lib_BEfunc::getRecordTitle($table,$this->rec),$GLOBALS['BE_USER']->uc['titleLen']));
			if(!in_array('delete',$this->disabledItems) && !$root && $BE_USER->isPSet($lCP,$table,'delete'))	{
				$menuItems[]='spacer';
				$menuItems['delete']=$this->DB_delete($table,$uid,$elInfo);
			}
		}
		
			// Adding external elements to the menuItems array
		$menuItems = $this->processingByExtClassArray($menuItems,$table,$uid);
		
			// Processing by external functions?
		$menuItems = $this->externalProcessingOfDBMenuItems($menuItems);
		
			// Return the printed elements:
		return $this->printItems($menuItems,
			$root?
			'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/i/_icon_website.gif','width="18" height="16"').' class="absmiddle" alt="" />'.htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']):
			t3lib_iconWorks::getIconImage($table,$this->rec,$this->backPath,' class="absmiddle" title="'.htmlspecialchars(t3lib_BEfunc::getRecordIconAltText($this->rec,$table)).'"').t3lib_BEfunc::getRecordTitle($table,$this->rec,1)
		);
	}

	/**
	 * Make 2nd level clickmenu (only for DBmenus)
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @return	string		HTML content
	 */
	function printNewDBLevel($table,$uid)	{
		global $TCA, $BE_USER;
		
			// Setting internal record to the table/uid :
		$this->rec = t3lib_BEfunc::getRecord($table,$uid);
		$menuItems=array();
		$root=0;
		if ($table=='pages' && !strcmp($uid,'0'))	{	// Rootlevel
			$root=1;
		}
		
			// If record was found, check permissions and get menu items.
		if (is_array($this->rec) || $root)	{
			$lCP = $BE_USER->calcPerms(t3lib_BEfunc::getRecord('pages',($table=='pages'?$this->rec['uid']:$this->rec['pid'])));
				// Edit: 
			if(!$root && ($BE_USER->isPSet($lCP,$table,'edit')||$BE_USER->isPSet($lCP,$table,'editcontent')))	{
				$this->editOK=1;
			}

			$menuItems = $this->processingByExtClassArray($menuItems,$table,$uid);
		}
		
			// Return the printed elements:
		if (!is_array($menuItems))	$menuItems=array();
		return $this->printItems($menuItems,
			$root?
			'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/i/_icon_website.gif','width="18" height="16"').' class="absmiddle" alt="" />'.htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']):
			t3lib_iconWorks::getIconImage($table,$this->rec,$this->backPath,' class="absmiddle" title="'.htmlspecialchars(t3lib_BEfunc::getRecordIconAltText($this->rec,$table)).'"').t3lib_BEfunc::getRecordTitle($table,$this->rec,1)
		);
	}

	/**
	 * Processing the $menuItems array (for extension classes) (DATABASE RECORDS)
	 * 
	 * @param	array		$menuItems array for manipulation.
	 * @return	array		Processed $menuItems array
	 */
	function externalProcessingOfDBMenuItems($menuItems)	{
		return $menuItems;
	}

	/**
	 * Processing the $menuItems array by external classes (typ. adding items)
	 * 
	 * @param	array		$menuItems array for manipulation.
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @return	array		Processed $menuItems array
	 */
	function processingByExtClassArray($menuItems,$table,$uid)	{
		if (is_array($this->extClassArray))	{
			reset($this->extClassArray);
			while(list(,$conf)=each($this->extClassArray))	{
				$obj=t3lib_div::makeInstance($conf['name']);
				$menuItems = $obj->main($this,$menuItems,$table,$uid);
			}
		}
		return $menuItems;
	}

	/**
	 * Returning JavaScript for the onClick event linking to the input URL.
	 * 
	 * @param	string		The URL relative to TYPO3_mainDir
	 * @param	string		The return_url-parameter
	 * @param	boolean		If set, the "hideCM()" will be called
	 * @return	string		JavaScript for an onClick event.
	 */
	function urlRefForCM($url,$retUrl='',$hideCM=1)	{
		$loc='top.content'.($this->listFrame && !$this->alwaysContentFrame ?'.list_frame':'');
		$editOnClick='var docRef=(top.content.list_frame)?top.content.list_frame:'.$loc.'; docRef.document.location=top.TS.PATH_typo3+\''.$url.'\''.
			($retUrl?"+'&".$retUrl."='+top.rawurlencode(".$this->frameLocation('docRef.document').')':'').';'.
			($hideCM?'return hideCM();':'');
		return $editOnClick;
	}

	/**
	 * Adding CM element for Clipboard "copy" and "cut"
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @param	string		Type: "copy" or "cut"
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_copycut($table,$uid,$type)	{
		if ($this->clipObj->current=='normal')	{
			$isSel = $this->clipObj->isSelected($table,$uid);
		}	
		return $this->linkItem(
			$this->label($type),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/clip_'.$type.($isSel==$type?'_h':'').'.gif','width="12" height="12"').' alt="" />'),
			"top.loadTopMenu('".$this->clipObj->selUrlDB($table,$uid,($type=='copy'?1:0),($isSel==$type))."');return false;"
		);
	}

	/**
	 * Adding CM element for Clipboard "paste into"/"paste after"
	 * NOTICE: $table and $uid should follow the special syntax for paste, see clipboard-class :: pasteUrl();
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record. NOTICE: Special syntax!
	 * @param	string		Type: "into" or "after"
	 * @param	array		Contains instructions about whether to copy or cut an element.
	 * @return	array		Item array, element in $menuItems
	 * @see t3lib_clipboard::pasteUrl()
	 * @internal
	 */
	function DB_paste($table,$uid,$type,$elInfo)	{	
		$editOnClick='';
		$loc='top.content'.($this->listFrame && !$this->alwaysContentFrame ?'.list_frame':'');
		$conf=$loc.' && confirm('.$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.'.($elInfo[2]=='copy'?'copy':'move').'_'.$type),$elInfo[0],$elInfo[1])).')';
		$editOnClick='if('.$conf.'){'.$loc.'.document.location=top.TS.PATH_typo3+\''.$this->clipObj->pasteUrl($table,$uid,0).'&redirect=\'+top.rawurlencode('.$this->frameLocation($loc.'.document').'); hideCM();}';
		
		return $this->linkItem(
			$this->label('paste'.$type),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/clip_paste'.$type.'.gif','width="12" height="12"').' alt="" />'),
			$editOnClick.'return false;'
		);
	}

	/**
	 * Adding CM element for Info
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_info($table,$uid)	{
		return $this->linkItem(
			$this->label('info'),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom2.gif','width="12" height="12"').' alt="" />'),
			"top.launchView('".$table."', '".$uid."'); return hideCM();"			
		);
	}

	/**
	 * Adding CM element for History
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_history($table,$uid)	{
		$url = 'show_rechis.php?element='.rawurlencode($table.':'.$uid);
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_history')),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/history2.gif','width="13" height="12"').' alt="" />'),
			$this->urlRefForCM($url,'returnUrl'),
			0
		);
	}

	/**
	 * Adding CM element for Permission setting
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @param	array		The "pages" record with "perms_*" fields inside.
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_perms($table,$uid,$rec)	{
		$url = 'mod/web/perm/index.php?id='.$uid.($rec['perms_userid']==$GLOBALS['BE_USER']->user['uid']||$GLOBALS['BE_USER']->isAdmin()?'&return_id='.$uid.'&edit=1':'');
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_perms')),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/perm.gif','width="7" height="12"').' alt="" />'),
			$this->urlRefForCM($url),
			0
		);
	}

	/**
	 * Adding CM element for DBlist
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @param	array		Record of the element (needs "pid" field if not pages-record)
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_db_list($table,$uid,$rec)	{
		$url = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR').'db_list.php?table='.($table=='pages'?'':$table).'&id='.($table=='pages'?$uid:$rec['pid']);
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_db_list')),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/list.gif','width="11" height="11"').' alt="" />'),
			"top.nextLoadModuleUrl='".$url."';top.goToModule('web_list',1);",
			0
		);
	}

	/**
	 * Adding CM element for Moving wizard
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @param	array		Record. Needed for tt-content elements which will have the sys_language_uid sent
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_moveWizard($table,$uid,$rec)	{
		$url = 'move_el.php?table='.$table.'&uid='.$uid.
				($table=='tt_content'?'&sys_language_uid='.intval($rec['sys_language_uid']):'');	// Hardcoded field for tt_content elements.
				
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_moveWizard'.($table=='pages'?'_page':''))),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/move_'.($table=='pages'?'page':'record').'.gif','width="11" height="12"').' alt="" />'),
			$this->urlRefForCM($url,'returnUrl'),
			0
		);
	}

	/**
	 * Adding CM element for Create new wizard (either db_new.php or sysext/cms/layout/db_new_content_el.php)
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @param	array		Record.
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_newWizard($table,$uid,$rec)	{
		$url = ($table=='pages' || !t3lib_extMgm::isLoaded('cms')) ? 'db_new.php?id='.$uid.'&pagesOnly=1' : 'sysext/cms/layout/db_new_content_el.php?id='.$rec['pid'].'&sys_language_uid='.intval($rec['sys_language_uid']);
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_newWizard')),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/new_'.($table=='pages'?'page':'record').'.gif','width="'.($table=='pages'?'13':'16').'" height="12"').' alt="" />'),
			$this->urlRefForCM($url,'returnUrl'),
			0
		);
	}

	/**
	 * Adding CM element for Editing of the access related fields of a table (disable, starttime, endtime, fe_groups)
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_editAccess($table,$uid)	{
		$addParam='&columnsOnly='.implode(',',$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']);
		$url = 'alt_doc.php?edit['.$table.']['.$uid.']=edit'.$addParam;
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_editAccess')),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/editaccess.gif','width="12" height="12"').' alt="" />'),
			$this->urlRefForCM($url,'returnUrl'),
			1	// no top frame CM!
		);
	}

	/**
	 * Adding CM element for edit page header
	 * 
	 * @param	integer		page uid to edit (PID)
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_editPageHeader($uid)	{
		$url = 'alt_doc.php?edit[pages]['.$uid.']=edit';
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_editPageHeader')),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2.gif','width="11" height="12"').' alt="" />'),
			$this->urlRefForCM($url,'returnUrl'),
			1	// no top frame CM!
		);
	}

	/**
	 * Adding CM element for regular editing of the element!
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_edit($table,$uid)	{
		global $BE_USER;

		$editOnClick='';
		$loc='top.content'.($this->listFrame && !$this->alwaysContentFrame ?'.list_frame':'');
		$addParam='';
		$theIcon = t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2.gif','width="11" height="12"');
		if (
				$this->iParts[0]=='pages' && 
				$this->iParts[1] && 
				$GLOBALS['BE_USER']->check('modules','web_layout')
			)	{
			$theIcon = t3lib_iconWorks::skinImg($this->backPath,'gfx/edit_page.gif','width="12" height="12"');
			$this->editPageIconSet=1;
			if ($BE_USER->uc['classicPageEditMode'] || !t3lib_extMgm::isLoaded('cms'))	{
				$addParam='&editRegularContentFromId='.intval($this->iParts[1]);
			} else {
				$editOnClick="top.fsMod.recentIds['web']=".intval($this->iParts[1]).";top.goToModule('web_layout',1);";
			}
		}
		if (!$editOnClick)	{
			$editOnClick="if(".$loc."){".$loc.".document.location=top.TS.PATH_typo3+'alt_doc.php?returnUrl='+top.rawurlencode(".$this->frameLocation($loc.'.document').")+'&edit[".$table."][".$uid."]=edit".$addParam."';}";
		}
		
		return $this->linkItem(
			$this->label('edit'),
			$this->excludeIcon('<img'.$theIcon.' alt="" />'),
			$editOnClick.'return hideCM();'
		);
	}

	/**
	 * Adding CM element for regular Create new element
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_new($table,$uid)	{
		$editOnClick='';
		$loc='top.content'.(!$this->alwaysContentFrame?'.list_frame':'');
		$editOnClick='if('.$loc.'){'.$loc.".document.location=top.TS.PATH_typo3+'".
			($this->listFrame?
				$this->backPath."alt_doc.php?returnUrl='+top.rawurlencode(".$this->frameLocation($loc.'.document').")+'&edit[".$table."][-".$uid."]=new'":
				$this->backPath.'db_new.php?id='.intval($uid)."'").
			';}';
			
		return $this->linkItem(
			$this->label('new'),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/new_'.($table=='pages'&&$this->listFrame?'page':'el').'.gif','width="'.($table=='pages'?'13':'11').'" height="12"').' alt="" />'),
			$editOnClick.'return hideCM();'
		);
	}

	/**
	 * Adding CM element for hide/unhide of the input record
	 * 
	 * @param	string		Table name
	 * @param	array		Record array
	 * @param	string		Name of the hide field
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_hideUnhide($table,$rec,$hideField)	{
		$uid=$rec['uid'];
		$editOnClick='';
		$loc='top.content'.($this->listFrame && !$this->alwaysContentFrame ?'.list_frame':'');
		$editOnClick='if('.$loc.'){'.$loc.".document.location=top.TS.PATH_typo3+'tce_db.php?redirect='+top.rawurlencode(".$this->frameLocation($loc.'.document').")+'".
			"&data[".$table.']['.$uid.']['.$hideField.']='.($rec[$hideField]?0:1).'&prErr=1&vC='.$GLOBALS['BE_USER']->veriCode()."';hideCM();}";

		return $this->linkItem(
			$this->label(($rec[$hideField]?'un':'').'hide'),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_'.($rec[$hideField]?'un':'').'hide.gif','width="11" height="10"').' alt="" />'),
			$editOnClick.'return false;',
			1
		);
	}

	/**
	 * Adding CM element for Delete
	 * 
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @param	array		Label for including in the confirmation message, EXT:lang/locallang_core.php:mess.delete
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_delete($table,$uid,$elInfo)	{
		$editOnClick='';
		$loc='top.content'.($this->listFrame && !$this->alwaysContentFrame ?'.list_frame':'');
		$editOnClick='if('.$loc." && confirm(".$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.delete'),$elInfo[0])).")){".$loc.".document.location=top.TS.PATH_typo3+'tce_db.php?redirect='+top.rawurlencode(".$this->frameLocation($loc.'.document').")+'".
			"&cmd[".$table.']['.$uid.'][delete]=1&prErr=1&vC='.$GLOBALS['BE_USER']->veriCode()."';hideCM();}";

		return $this->linkItem(
			$this->label('delete'),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/garbage.gif','width="11" height="12"').' alt="" />'),
			$editOnClick.'return false;'
		);
	}

	/**
	 * Adding CM element for View Page
	 * 
	 * @param	integer		Page uid (PID)
	 * @param	string		Anchor, if any
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_view($id,$anchor='')	{
		return $this->linkItem(
			$this->label('view'),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom.gif','width="12" height="12"').' alt="" />'),
			t3lib_BEfunc::viewOnClick($id,$this->backPath,t3lib_BEfunc::BEgetRootLine($id),$anchor).'return hideCM();'
		);
	}

	
	
	
	
	
	
	
	
	
	/***************************************
	 *
	 * FILE
	 *
	 ***************************************/

	/**
	 * Make 1st level clickmenu:
	 * 
	 * @param	string		The absolute path
	 * @return	string		HTML content
	 */
	function printFileClickMenu($path)	{
		$menuItems=array();
		
		if (@file_exists($path) && t3lib_div::isAllowedAbsPath($path))	{
			$fI = pathinfo($path);
			$icon = is_dir($path) ? 'folder.gif' : t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));
			$size=' ('.t3lib_div::formatSize(filesize($path)).'bytes)';
			$icon = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/fileicons/'.$icon,'width="18" height="16"').' class="absmiddle" title="'.htmlspecialchars($fI['basename'].$size).'" alt="" />';

				// edit
			if (!in_array('edit',$this->disabledItems) && is_file($path) && t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'],$fI['extension'])) $menuItems['edit']=$this->FILE_launch($path,'file_edit.php','edit','edit_file.gif');
				// rename
			if (!in_array('rename',$this->disabledItems))	$menuItems['rename']=$this->FILE_launch($path,'file_rename.php','rename','rename.gif');
				// upload
			if (!in_array('upload',$this->disabledItems) && is_dir($path)) $menuItems['upload']=$this->FILE_launch($path,'file_upload.php','upload','upload.gif');
				// new
			if (!in_array('new',$this->disabledItems) && is_dir($path)) $menuItems['new']=$this->FILE_launch($path,'file_newfolder.php','new','new_file.gif');
				// info
			if (!in_array('info',$this->disabledItems))	$menuItems['info']=$this->DB_info($path,'');

			$menuItems[]='spacer';
			
				// copy:
			if (!in_array('copy',$this->disabledItems))	$menuItems['copy']=$this->FILE_copycut($path,'copy');
				// cut:
			if (!in_array('cut',$this->disabledItems))	$menuItems['cut']=$this->FILE_copycut($path,'cut');

				// Paste:
			$elFromAllTables = count($this->clipObj->elFromTable('_FILE'));
			if (!in_array('paste',$this->disabledItems) && $elFromAllTables && is_dir($path))	{
				$elArr = $this->clipObj->elFromTable('_FILE');
				reset($elArr);
				$selItem = current($elArr);
				$elInfo=array(
					basename($selItem),
					basename($path),
					$this->clipObj->currentMode()
				);
				$menuItems['pasteinto']=$this->FILE_paste($path,$selItem,$elInfo);
			}

			$menuItems[]='spacer';

				// delete:
			if (!in_array('delete',$this->disabledItems))	$menuItems['delete']=$this->FILE_delete($path);
		}

			// Adding external elements to the menuItems array
		$menuItems = $this->processingByExtClassArray($menuItems,$path,0);

			// Processing by external functions?
		$menuItems = $this->externalProcessingOfFileMenuItems($menuItems);

			// Return the printed elements:
		return $this->printItems($menuItems,$icon.basename($path));
	}


	/**
	 * Processing the $menuItems array (for extension classes) (FILES)
	 * 
	 * @param	array		$menuItems array for manipulation.
	 * @return	array		Processed $menuItems array
	 */
	function externalProcessingOfFileMenuItems($menuItems)	{
		return $menuItems;
	}

	/**
	 * Multi-function for adding an entry to the $menuItems array
	 * 
	 * @param	string		Path to the file/directory (target)
	 * @param	string		Script (eg. file_edit.php) to pass &target= to
	 * @param	string		"type" is the code which fetches the correct label for the element from "cm."
	 * @param	string		icon image-filename from "gfx/" (12x12 icon)
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function FILE_launch($path,$script,$type,$image)	{
		$loc='top.content'.(!$this->alwaysContentFrame?'.list_frame':'');
		$editOnClick='if('.$loc.'){'.$loc.".document.location=top.TS.PATH_typo3+'".$script.'?target='.rawurlencode($path)."';}";
		
		return $this->linkItem(
			$this->label($type),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/'.$image,'width="12" height="12"').' alt="" />'),
			$editOnClick.'return hideCM();'
		);
	}

	/**
	 * Returns element for copy or cut of files.
	 * 
	 * @param	string		Path to the file/directory (target)
	 * @param	string		Type: "copy" or "cut"
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function FILE_copycut($path,$type)	{
		$table='_FILE';		// Pseudo table name for use in the clipboard.
		$uid = t3lib_div::shortmd5($path);
		if ($this->clipObj->current=='normal')	{
			$isSel = $this->clipObj->isSelected($table,$uid);
		}	
		return $this->linkItem(
			$this->label($type),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/clip_'.$type.($isSel==$type?'_h':'').'.gif','width="12" height="12"').' alt="" />'),
			"top.loadTopMenu('".$this->clipObj->selUrlFile($path,($type=='copy'?1:0),($isSel==$type))."');return false;"
		);
	}

	/**
	 * Creates element for deleting of target
	 * 
	 * @param	string		Path to the file/directory (target)
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function FILE_delete($path)	{
		$editOnClick='';
		$loc='top.content'.($this->listFrame && !$this->alwaysContentFrame ?'.list_frame':'');
		$editOnClick='if('.$loc." && confirm(".$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.delete'),basename($path))).")){".$loc.".document.location=top.TS.PATH_typo3+'tce_file.php?redirect='+top.rawurlencode(".$this->frameLocation($loc.'.document').")+'".
			"&file[delete][0][data]=".rawurlencode($path).'&vC='.$GLOBALS['BE_USER']->veriCode()."';hideCM();}";
		
		return $this->linkItem(
			$this->label('delete'),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/garbage.gif','width="11" height="12"').' alt="" />'),
			$editOnClick.'return false;'
		);
	}

	/**
	 * Creates element for pasting files.
	 * 
	 * @param	string		Path to the file/directory (target)
	 * @param	string		target - NOT USED.
	 * @param	array		Various values for the labels.
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function FILE_paste($path,$target,$elInfo)	{	
		$editOnClick='';
		$loc='top.content'.($this->listFrame && !$this->alwaysContentFrame ?'.list_frame':'');
		$conf=$loc." && confirm(".$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess'.($elInfo[2]=='copy'?'copy':'move').'_into'),$elInfo[0],$elInfo[1])).")";
		$editOnClick='if('.$conf.'){'.$loc.".document.location=top.TS.PATH_typo3+'".$this->clipObj->pasteUrl('_FILE',$path,0).
			"&redirect='+top.rawurlencode(".$this->frameLocation($loc.'.document').'); hideCM();}';
		
		return $this->linkItem(
			$this->label('pasteinto'),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/clip_pasteinto.gif','width="12" height="12"').' alt="" />'),
			$editOnClick.'return false;'
		);
	}


	
	
	
	
	
	
	
	
	
	
	
	/***************************************
	 *
	 * COMMON
	 *
	 **************************************/

	/**
	 * Prints the items from input $menuItems array - both as topframe menu AND the JS section for writing to the div-layers. 
	 * Of course the topframe menu will appear only if $this->doDisplayTopFrameCM() returns true
	 * 
	 * @param	array		$menuItems array
	 * @param	string		HTML code for the element which was clicked - shown in the end of the horizontal menu in topframe after the close-button.
	 * @return	string		HTML code
	 */
	function printItems($menuItems,$item)	{
		$out='';

			// Adding topframe part (horizontal clickmenu)
		if ($this->doDisplayTopFrameCM())	{
			$out.= '
			
				<!--
					Table, which contains the click menu when shown in the top frame of the backend:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-CSM-top">
					<tr>
							
							<!-- Items: -->
						<td class="c-item">'.
							implode('</td>
						<td><img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/acm_spacer2.gif','width="8" height="12"').' alt="" /></td>
						<td class="c-item">',$this->menuItemsForTopFrame($menuItems)).
						'</td>
						
							<!-- Close button: -->
						<td class="c-closebutton"><a href="#" onclick="hideCM();return false;"><img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/close_12h.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.close',1).'" alt="" /></a></td>
						
							<!-- The item of the clickmenu: -->
						<td class="c-itemicon">'.$item.'</td>
					</tr>
				</table>
			';
		}
			// Adding JS part:
		$out.=$this->printLayerJScode($menuItems);
		
			// Return the content
		return $out;
	}

	/**
	 * Create the JavaScript section
	 * 
	 * @param	array		The $menuItems array to print
	 * @return	string		The JavaScript section which will print the content of the CM to the div-layer in the target frame.
	 */
	function printLayerJScode($menuItems)	{
		$script='';
		if ($this->isCMlayers())	{	// Clipboard must not be submitted - then it's probably a copy/cut situation.
			$frameName = '.'.($this->listFrame ? 'list_frame' : 'nav_frame');
			if ($this->alwaysContentFrame)	$frameName='';

				// Create the table displayed in the clickmenu layer:			
			$CMtable = '
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-CSM bgColor4">
					'.implode('',$this->menuItemsForClickMenu($menuItems)).'
				</table>';

				// Wrap the inner table in another table to create outer border:
			$CMtable = $this->wrapColorTableCM($CMtable);
				
				// Create JavaScript section:
			$script=$GLOBALS['TBE_TEMPLATE']->wrapScriptTags('
			
if (top.content && top.content'.$frameName.' && top.content'.$frameName.'.setLayerObj)	{
	top.content'.$frameName.'.setLayerObj(unescape("'.t3lib_div::rawurlencodeJS($CMtable).'"),'.$this->cmLevel.');
}
'.(!$this->doDisplayTopFrameCM()?'hideCM();':'')
);
		}
		
		return $script;
	}

	/**
	 * Wrapping the input string in a table with background color 4 and a black border style.
	 * For the pop-up menu
	 * 
	 * @param	string		HTML content to wrap in table.
	 * @return	string		
	 */
	function wrapColorTableCM($str)	{
		$str = '<table border="0" cellspacing="0" class="typo3-CSM-wrapperCM">
				<tr><td class="c-aa">'.$str.'</td><td class="c-ab"></td></tr>
				<tr><td class="c-ba"></td><td class="c-bb"></td></tr>
			</table>';
		return $str;
	}

	/**
	 * Traverses the menuItems and generates an output array for implosion in the topframe horizontal menu
	 * 
	 * @param	array		$menuItem array
	 * @param	array		Array with HTML content to be imploded between <td>-tags
	 * @return	array		Array of menu items for top frame.
	 */
	function menuItemsForTopFrame($menuItems)	{
		reset($menuItems);
		$out=array();
		while(list(,$i)=each($menuItems))	{
			if ($i[4]==1 && !$GLOBALS['SOBE']->doc->isCMlayers())	$i[4]=0;	// IF the topbar is the ONLY means of the click menu, then items normally disabled from the top menu will appear anyways IF they are disabled with a "1" (2+ will still disallow them in the topbar)
			if (is_array($i) && !$i[4])	$out[]=$i[0];
		}
		return $out;
	}

	/**
	 * Traverses the menuItems and generates an output array for implosion in the CM div-layers table.
	 * 
	 * @param	array		$menuItem array
	 * @param	array		Array with HTML content to be imploded between <td>-tags
	 * @return	array		array for implosion in the CM div-layers table.
	 */
	function menuItemsForClickMenu($menuItems)	{
		reset($menuItems);
		$out=array();
		while(list($cc,$i)=each($menuItems))	{
			if (is_string($i) && $i=='spacer')	{	// MAKE horizontal spacer
				$out[]='
					<tr class="bgColor2">
						<td colspan="2"><img src="clear.gif" width="1" height="1" alt="" /></td>
					</tr>';
			} else {	// Just make normal element:
				$onClick=$i[3];
				$onClick=eregi_replace('return[[:space:]]+hideCM\(\)[[:space:]]*;','',$onClick);
				$onClick=eregi_replace('return[[:space:]]+false[[:space:]]*;','',$onClick);
				$onClick=eregi_replace('hideCM\(\);','',$onClick);
				if (!$i[5])	$onClick.='hideEmpty();';
				
				$out[]='
					<tr class="typo3-CSM-itemRow" onclick="'.htmlspecialchars($onClick).'" onmouseover="this.bgColor=\''.$GLOBALS['TBE_TEMPLATE']->bgColor5.'\';" onmouseout="this.bgColor=\'\';">
						'.(!$this->leftIcons?'<td class="typo3-CSM-item">'.$i[1].'</td><td align="center">'.$i[2].'</td>' : '<td align="center">'.$i[2].'</td><td class="typo3-CSM-item">'.$i[1].'</td>').'
					</tr>';
			}
		}
		return $out;
	}

	/**
	 * Creating an array with various elements for the clickmenu entry
	 * 
	 * @param	string		The label, htmlspecialchar'ed already
	 * @param	string		<img>-tag for the icon
	 * @param	string		JavaScript onclick event for label/icon
	 * @param	boolean		==1 and the element will NOT appear in clickmenus in the topframe (unless clickmenu is totally unavailable)! ==2 and the item will NEVER appear in top frame. (This is mostly for "less important" options since the top frame is not capable of holding so many elements horizontally)
	 * @param	boolean		If set, the clickmenu layer will not hide itself onclick - used for secondary menus to appear...
	 * @return	array		$menuItem entry with 6 numerical entries: [0] is the HTML for display of the element with link and icon an mouseover etc., [1]-[5] is simply the input params passed through!
	 */
	function linkItem($str,$icon,$onClick,$onlyCM=0,$dontHide=0)	{
		$this->elCount++;
		
		$WHattribs = t3lib_iconWorks::skinImg($BACK_PATH,'gfx/content_client.gif','width="7" height="10"',2);
		
		return array(
			'<img src="clear.gif" '.$WHattribs.' class="c-roimg" name="roimg_'.$this->elCount.'" alt="" />'.
				'<a href="#" onclick="'.htmlspecialchars($onClick).'" onmouseover="mo('.$this->elCount.');" onmouseout="mout('.$this->elCount.');">'.
				$str.$icon.
				'</a>',
			$str,
			$icon,
			$onClick,
			$onlyCM,
			$dontHide
		);
	}

	/**
	 * Returns the input string IF not a user setting has disabled display of icons.
	 * 
	 * @param	string		The icon-image tag
	 * @return	string		The icon-image tag prefixed with space char IF the icon should be printed at all due to user settings
	 */
	function excludeIcon($iconCode)	{
		return ($GLOBALS['BE_USER']->uc['noMenuMode'] && strcmp($GLOBALS['BE_USER']->uc['noMenuMode'],'icons')) ? '' : ' '.$iconCode;
	}

	/**
	 * Get label from locallang_core.php:cm.*
	 * 
	 * @param	string		The "cm."-suffix to get.
	 * @return	string		
	 */
	function label($label)	{
		return $GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:cm.'.$label,1));
	}

	/**
	 * Returns true if there should be writing to the div-layers (commands sent to clipboard MUST NOT write to div-layers)
	 * 
	 * @return	boolean		
	 */
	function isCMlayers()	{
		return $GLOBALS['SOBE']->doc->isCMlayers() && !$this->CB;
	}

	/**
	 * Appends ".location" to input string
	 * 
	 * @param	string		Input string, probably a JavaScript document reference
	 * @return	string		
	 */
	function frameLocation($str)	{
		return $str.'.location';
	}
}













/**
 * Script Class for the Context Sensitive Menu in TYPO3 (rendered in top frame, normally writing content dynamically to list frames).
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 * @see template::getContextMenuCode()
 */
class SC_alt_clickmenu {
	
		// Internal, static: GPvar:
	var $backPath;					// Back path.
	var $item;						// Definition of which item the click menu should be made for.

		// Internal:
	var $content='';				// Content accumulation
	var $doc;						// Template object 
	var $include_once=array();		// Files to include_once() - set in init() function
	var $extClassArray=array();		// Internal array of classes for extending the clickmenu
	var $dontDisplayTopFrameCM=0;	// If set, then the clickmenu will NOT display in the top frame.

	/**
	 * Constructor function for script class.
	 * 
	 * @return	void		
	 */
	function init()	{
		global $BE_USER,$BACK_PATH;
		
			// Setting GPvars:
		$this->backPath = t3lib_div::GPvar('backPath');
		$this->item = t3lib_div::GPvar('item');

			// Setting pseudo module name
		$this->MCONF['name']='xMOD_alt_clickmenu.php';

			// Takes the backPath as a parameter BUT since we are worried about someone forging a backPath (XSS security hole) we will check with sent md5 hash:
		$inputBP = explode('|',$this->backPath);
		if (count($inputBP)==2 && $inputBP[1]==t3lib_div::shortMD5($inputBP[0].'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
			$this->backPath = $inputBP[0];
		} else {
			$this->backPath = $BACK_PATH;
		}

			// Setting internal array of classes for extending the clickmenu:
		$this->extClassArray = $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'];

			// Traversing that array and setting files for inclusion:
		if (is_array($this->extClassArray))	{
			foreach($this->extClassArray as $extClassConf)	{
				if ($extClassConf['path'])	$this->include_once[]=$extClassConf['path'];
			}
		}

			// Initialize template object
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType='xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		
			// Setting mode for display and background image in the top frame
		$this->dontDisplayTopFrameCM= $this->doc->isCMlayers() && !$GLOBALS['BE_USER']->getTSConfigVal('options.contextMenu.options.alwaysShowClickMenuInTopFrame');
		if ($this->dontDisplayTopFrameCM)	{
			$this->doc->bodyTagId.= '-notop';
		}

			// Setting clickmenu timeout		
		$secs = t3lib_div::intInRange($BE_USER->getTSConfigVal('options.contextMenu.options.clickMenuTimeOut'),1,100,5);	// default is 5

			// Setting the JavaScript controlling the timer on the page
		$this->doc->JScode.=$this->doc->wrapScriptTags('
	var date = new Date();
	var mo_timeout = Math.floor(date.getTime()/1000);

	roImg =new Image(); 
	roImg.src = "'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/content_client.gif','width="7" height="10"',1).'";

	routImg =new Image(); 
	routImg.src = "clear.gif";

	function mo(c)	{	//
		var name="roimg_"+c;
		document[name].src = roImg.src;
		updateTime();
	}
	function mout(c)	{	//
		var name="roimg_"+c;
		document[name].src = routImg.src;
		updateTime();
	}
	function updateTime()	{	//
		date = new Date();
		mo_timeout = Math.floor(date.getTime()/1000);
	}	
	function timeout_func()	{	//
		date = new Date();
		if (Math.floor(date.getTime()/1000)-mo_timeout > '.$secs.')	{
			hideCM();
			return false;
		} else {
			window.setTimeout("timeout_func();",1*1000);
		}
	}
	function hideCM()	{	//
		document.location="alt_topmenu_dummy.php";
		return false;
	}
	
		// Start timer
	timeout_func();
		');
	}

	/**
	 * Main function - generating the click menu in whatever form it has.
	 * 
	 * @return	void		
	 */
	function main()	{
		global $HTTP_GET_VARS;

			// Initialize Clipboard object:
		$clipObj = t3lib_div::makeInstance('t3lib_clipboard');
		$clipObj->initializeClipboard();
		$clipObj->lockToNormal();	// This locks the clipboard to the Normal for this request.
		
			// Update clipboard if some actions are sent.
		$CB = $HTTP_GET_VARS['CB'];
		$clipObj->setCmd($CB);
		$clipObj->cleanCurrent();
		$clipObj->endClipboard();	// Saves

			// Create clickmenu object
		$clickMenu = t3lib_div::makeInstance('clickMenu');
			
			// Set internal vars in clickmenu object:
		$clickMenu->clipObj = $clipObj;
		$clickMenu->extClassArray = $this->extClassArray;
		$clickMenu->dontDisplayTopFrameCM = $this->dontDisplayTopFrameCM;
		$clickMenu->backPath = $this->backPath;
		
			// Takes the backPath as a parameter BUT since we are worried about someone forging a backPath (XSS security hole) we will check with sent md5 hash:
		$inputBP = explode('|',$this->backPath);
		if (count($inputBP)==2 && $inputBP[1]==md5($inputBP[0].'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
			$clickMenu->backPath = $inputBP[0];
		}

			// Start page 
		$this->content.=$this->doc->startPage('Context Sensitive Menu');
		
			// Set content of the clickmenu with the incoming var, "item"
		$this->content.= $clickMenu->init($this->item);
	}

	/**
	 * End page and output content.
	 * 
	 * @return	void		
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_clickmenu.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_clickmenu.php']);
}










// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_clickmenu');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();
?>