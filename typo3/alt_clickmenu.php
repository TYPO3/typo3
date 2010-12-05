<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  136: class clickMenu
 *  168:     function init()
 *  222:     function doDisplayTopFrameCM()
 *
 *              SECTION: DATABASE
 *  254:     function printDBClickMenu($table,$uid)
 *  346:     function printNewDBLevel($table,$uid)
 *  383:     function externalProcessingOfDBMenuItems($menuItems)
 *  395:     function processingByExtClassArray($menuItems,$table,$uid)
 *  414:     function urlRefForCM($url,$retUrl='',$hideCM=1)
 *  431:     function DB_copycut($table,$uid,$type)
 *  460:     function DB_paste($table,$uid,$type,$elInfo)
 *  485:     function DB_info($table,$uid)
 *  501:     function DB_history($table,$uid)
 *  520:     function DB_perms($table,$uid,$rec)
 *  539:     function DB_db_list($table,$uid,$rec)
 *  558:     function DB_moveWizard($table,$uid,$rec)
 *  579:     function DB_newWizard($table,$uid,$rec)
 *  602:     function DB_editAccess($table,$uid)
 *  621:     function DB_editPageHeader($uid)
 *  632:     function DB_editPageProperties($uid)
 *  650:     function DB_edit($table,$uid)
 *  692:     function DB_new($table,$uid)
 *  717:     function DB_delete($table,$uid,$elInfo)
 *  743:     function DB_view($id,$anchor='')
 *  758:     function DB_tempMountPoint($page_id)
 *  775:     function DB_hideUnhide($table,$rec,$hideField)
 *  790:     function DB_changeFlag($table, $rec, $flagField, $title, $name, $iconRelPath='gfx/')
 *
 *              SECTION: FILE
 *  824:     function printFileClickMenu($path)
 *  888:     function externalProcessingOfFileMenuItems($menuItems)
 *  902:     function FILE_launch($path,$script,$type,$image)
 *  922:     function FILE_copycut($path,$type)
 *  948:     function FILE_delete($path)
 *  975:     function FILE_paste($path,$target,$elInfo)
 *
 *              SECTION: DRAG AND DROP
 * 1012:     function printDragDropClickMenu($table,$srcId,$dstId)
 * 1054:     function externalProcessingOfDragDropMenuItems($menuItems)
 * 1069:     function dragDrop_copymovepage($srcUid,$dstUid,$action,$into)
 * 1094:     function dragDrop_copymovefolder($srcPath,$dstPath,$action)
 *
 *              SECTION: COMMON
 * 1130:     function printItems($menuItems,$item)
 * 1182:     function printLayerJScode($menuItems)
 * 1223:     function wrapColorTableCM($str)
 * 1246:     function menuItemsForTopFrame($menuItems)
 * 1263:     function menuItemsForClickMenu($menuItems)
 * 1301:     function addMenuItems($menuItems,$newMenuItems,$position='')
 * 1377:     function linkItem($str,$icon,$onClick,$onlyCM=0,$dontHide=0)
 * 1406:     function excludeIcon($iconCode)
 * 1416:     function enableDisableItems($menuItems)
 * 1454:     function cleanUpSpacers($menuItems)
 * 1496:     function label($label)
 * 1505:     function isCMlayers()
 * 1519:     function frameLocation($str)
 *
 *
 * 1544: class SC_alt_clickmenu
 * 1563:     function init()
 * 1663:     function main()
 * 1699:     function printContent()
 *
 * TOTAL FUNCTIONS: 51
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


require ('init.php');
require ('template.php');
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');




/**
 * Class for generating the click menu
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
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
	var $PH_backPath='###BACK_PATH###';		// BackPath place holder: We need different backPath set whether the clickmenu is written back to a frame which is not in typo3/ dir or if the clickmenu is shown in the top frame (no backpath)
	var $listFrame=0;			// If set, the calling document should be in the listframe of a frameset.
	var $isDBmenu=0;			// If set, the menu is about database records, not files. (set if part 2 [1] of the item-var is NOT blank)
	var $alwaysContentFrame=0;	// If true, the "content" frame is always used for reference (when condensed mode is enabled)
	var $iParts=array();		// Stores the parts of the input $item string, splitted by "|": [0] = table/file, [1] = uid/blank, [2] = flag: If set, listFrame, If "2" then "content frame" is forced  [3] = ("+" prefix = disable all by default, enable these. Default is to disable) Items key list
	var $disabledItems=array();	// Contains list of keywords of items to disable in the menu
	var $dontDisplayTopFrameCM=0;	// If true, the context sensitive menu will not appear in the top frame, only as a layer.
	var $leftIcons=0;			// If true, Show icons on the left.
	var $extClassArray=array();		// Array of classes to be used for user processing of the menu content. This is for the API of adding items to the menu from outside.
	var $ajax=0; // enable/disable ajax behavior

		// Internal, dynamic:
	var $elCount=0;				// Counter for elements in the menu. Used to number the name / id of the mouse-over icon.
	var $editPageIconSet=0;		// Set, when edit icon is drawn.
	var $editOK=0;				// Set to true, if editing of the element is OK.
	var $rec=array();



	/**
	 * Initialize click menu
	 *
	 * @return	string		The clickmenu HTML content
	 */
	function init()	{
			// Setting GPvars:
		$this->cmLevel = intval(t3lib_div::_GP('cmLevel'));
		$this->CB = t3lib_div::_GP('CB');
		if(t3lib_div::_GP('ajax'))	{
			$this->ajax = 1;
			// XML has to be parsed, no parse errors allowed
			@ini_set('display_errors', 0);
		}

			// Deal with Drag&Drop context menus
		if (strcmp(t3lib_div::_GP('dragDrop'),''))	{
			$CMcontent = $this->printDragDropClickMenu(t3lib_div::_GP('dragDrop'),t3lib_div::_GP('srcId'),t3lib_div::_GP('dstId'));
			return $CMcontent;
		}

			// can be set differently as well
		$this->iParts[0] = t3lib_div::_GP('table');
		$this->iParts[1] = t3lib_div::_GP('uid');
		$this->iParts[2] = t3lib_div::_GP('listFr');
		$this->iParts[3] = t3lib_div::_GP('enDisItems');

			// Setting flags:
		if ($this->iParts[2])	$this->listFrame=1;
		if ($GLOBALS['BE_USER']->uc['condensedMode'] || $this->iParts[2]==2) $this->alwaysContentFrame=1;
		if (strcmp($this->iParts[1],''))	$this->isDBmenu=1;

		$TSkey =($this->isDBmenu?'page':'folder').($this->listFrame?'List':'Tree');
		$this->disabledItems = t3lib_div::trimExplode(',',$GLOBALS['BE_USER']->getTSConfigVal('options.contextMenu.'.$TSkey.'.disableItems'),1);
		$this->leftIcons = $GLOBALS['BE_USER']->getTSConfigVal('options.contextMenu.options.leftIcons');

			// &cmLevel flag detected (2nd level menu)
		if (!$this->cmLevel)	{
				// Make 1st level clickmenu:
			if ($this->isDBmenu)	{
				$CMcontent = $this->printDBClickMenu($this->iParts[0],$this->iParts[1]);
			} else {
				$CMcontent = $this->printFileClickMenu($this->iParts[0]);
			}
		} else {
				// Make 2nd level clickmenu (only for DBmenus)
			if ($this->isDBmenu)	{
				$CMcontent = $this->printNewDBLevel($this->iParts[0],$this->iParts[1]);
			}
		}

			// Return clickmenu content:
		return $CMcontent;
	}

	/**
	 * Returns true if the menu should (also?) be displayed in topframe, not just <div>-layers
	 *
	 * @return	boolean
	 */
	function doDisplayTopFrameCM()	{
		if($this->ajax)	{
			return false;
		} else {
			return !$GLOBALS['SOBE']->doc->isCMlayers() || !$this->dontDisplayTopFrameCM;
		}
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
		$this->rec = t3lib_BEfunc::getRecordWSOL($table,$uid);
		$menuItems=array();

		$root=0;
		$DBmount = FALSE;
		if ($table=='pages' && !strcmp($uid,'0'))	{	// Rootlevel
			$root=1;
		}

		if ($table=='pages' && in_array($uid,$GLOBALS['BE_USER']->returnWebmounts()))	{	// DB mount
			$DBmount = TRUE;
		}
			// used to hide cut,copy icons for l10n-records
		$l10nOverlay = false;
			// should only be performed for overlay-records within the same table
		if (t3lib_BEfunc::isTableLocalizable($table) && !isset($TCA[$table]['ctrl']['transOrigPointerTable'])) {
			$l10nOverlay = intval($this->rec[$TCA[$table]['ctrl']['transOrigPointerField']]) != 0;
		}

			// If record found (or root), go ahead and fill the $menuItems array which will contain data for the elements to render.
		if (is_array($this->rec) || $root)	{

				// Get permissions
			$lCP = $BE_USER->calcPerms(t3lib_BEfunc::getRecord('pages',($table=='pages'?$this->rec['uid']:$this->rec['pid'])));

				// View
			if (!in_array('view',$this->disabledItems))	{
				if ($table=='pages')	$menuItems['view']=$this->DB_view($uid);
				if ($table==$GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'])	{
					$ws_rec = t3lib_BEfunc::getRecordWSOL($table, $this->rec['uid']);
					$menuItems['view']=$this->DB_view($ws_rec['pid']);
				}
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

			$menuItems['spacer1']='spacer';

				// Copy:
			if (!in_array('copy', $this->disabledItems) && !$root && !$DBmount && !$l10nOverlay)	$menuItems['copy'] = $this->DB_copycut($table, $uid, 'copy');
				// Cut:
			if (!in_array('cut', $this->disabledItems) && !$root && !$DBmount && !$l10nOverlay)	$menuItems['cut'] = $this->DB_copycut($table, $uid, 'cut');

				// Paste:
			$elFromAllTables = count($this->clipObj->elFromTable(''));
			if (!in_array('paste',$this->disabledItems) && $elFromAllTables)	{
				$selItem = $this->clipObj->getSelectedRecord();
				$elInfo=array(
					t3lib_div::fixed_lgd_cs($selItem['_RECORD_TITLE'],$BE_USER->uc['titleLen']),
					($root?$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']:t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table,$this->rec),$BE_USER->uc['titleLen'])),
					$this->clipObj->currentMode()
				);
				if ($table=='pages' && ($lCP & 8))	{
					if ($elFromAllTables)	$menuItems['pasteinto']=$this->DB_paste('',$uid,'into',$elInfo);
				}

				$elFromTable = count($this->clipObj->elFromTable($table));
				if (!$root && !$DBmount && $elFromTable  && $TCA[$table]['ctrl']['sortby'])	$menuItems['pasteafter']=$this->DB_paste($table,-$uid,'after',$elInfo);
			}

				// Delete:
			$elInfo=array(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table,$this->rec),$BE_USER->uc['titleLen']));
			if(!in_array('delete',$this->disabledItems) && !$root && !$DBmount && $BE_USER->isPSet($lCP,$table,'delete'))	{
				$menuItems['spacer2']='spacer';
				$menuItems['delete']=$this->DB_delete($table,$uid,$elInfo);
			}

			if(!in_array('history',$this->disabledItems))	{
				$menuItems['history']=$this->DB_history($table,$uid,$elInfo);
			}
		}

			// Adding external elements to the menuItems array
		$menuItems = $this->processingByExtClassArray($menuItems,$table,$uid);

			// Processing by external functions?
		$menuItems = $this->externalProcessingOfDBMenuItems($menuItems);

		if (!is_array($this->rec)) {
			$this->rec = array();
		}

			// Return the printed elements:
		return $this->printItems($menuItems,
			$root?
			t3lib_iconWorks::getSpriteIcon('apps-pagetree-root') . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) :
			t3lib_iconWorks::getSpriteIconForRecord($table, $this->rec, array('title'=> htmlspecialchars(t3lib_BEfunc::getRecordIconAltText($this->rec, $table)))) . t3lib_BEfunc::getRecordTitle($table, $this->rec, TRUE)
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
		$this->rec = t3lib_BEfunc::getRecordWSOL($table,$uid);
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
			t3lib_iconWorks::getSpriteIcon('apps-pagetree-root') . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']):
			t3lib_iconWorks::getSpriteIconForRecord($table, $this->rec,
				array('title' => htmlspecialchars(t3lib_BEfunc::getRecordIconAltText($this->rec, $table)))) .
				t3lib_BEfunc::getRecordTitle($table, $this->rec, TRUE)
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
			foreach ($this->extClassArray as $conf) {
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
	 * @param	string		If set, gives alternative location to load in (for example top frame or somewhere else)
	 * @return	string		JavaScript for an onClick event.
	 */
	function urlRefForCM($url,$retUrl='',$hideCM=1,$overrideLoc='')	{
		$loc = 'top.content.list_frame';
		$editOnClick= ($overrideLoc ? 'var docRef='.$overrideLoc : 'var docRef=(top.content.list_frame)?top.content.list_frame:'.$loc).'; docRef.location.href=top.TS.PATH_typo3+\''.$url.'\''.
			($retUrl ? "+'&" . $retUrl . "='+top.rawurlencode(" . $this->frameLocation('docRef.document') . ')' :'') . ';' .
			($hideCM ? 'return hideCM();' : '');
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

		$addParam = array();
		if ($this->listFrame)	{
			$addParam['reloadListFrame'] = ($this->alwaysContentFrame ? 2 : 1);
		}

		return $this->linkItem(
			$this->label($type),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-edit-' . $type . ($isSel === $type ? '-release' : ''))),
			"top.loadTopMenu('" . $this->clipObj->selUrlDB($table, $uid, ($type == 'copy' ? 1: 0), ($isSel==$type), $addParam) . "');return false;"
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
		$editOnClick = '';
		$loc = 'top.content.list_frame';
		if($GLOBALS['BE_USER']->jsConfirmation(2))	{
		$conf = $loc.' && confirm('.$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.'.($elInfo[2]=='copy'?'copy':'move').'_'.$type),$elInfo[0],$elInfo[1])).')';
		} else {
			$conf = $loc;
		}
		$editOnClick = 'if(' . $conf . '){' . $loc . '.location.href=top.TS.PATH_typo3+\'' . $this->clipObj->pasteUrl($table, $uid, 0) . '&redirect=\'+top.rawurlencode(' . $this->frameLocation($loc . '.document') . '); hideCM();}';

		return $this->linkItem(
			$this->label('paste'.$type),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-document-paste-' . $type)),
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
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-document-info')),
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
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-document-history-open')),
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
		if (!t3lib_extMgm::isLoaded('perm')) {
            return '';
        }
        $url = t3lib_extMgm::extRelPath('perm') . 'mod1/index.php?id=' . $uid . ($rec['perms_userid'] == $GLOBALS['BE_USER']->user['uid'] || $GLOBALS['BE_USER']->isAdmin() ? '&return_id=' . $uid . '&edit=1' : '');
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_perms')),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('status-status-locked')),
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
		$url = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR') . t3lib_extMgm::extRelPath('list') . 'mod1/db_list.php?table='.($table=='pages'? '' : $table) . '&id=' . ($table == 'pages' ? $uid : $rec['pid']);
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_db_list')),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-system-list-open')),
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
		$url = 'move_el.php?table=' . $table . '&uid=' . $uid .
				($table=='tt_content' ? '&sys_language_uid=' . intval($rec['sys_language_uid']) : '');	// Hardcoded field for tt_content elements.

		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_moveWizard' . ($table=='pages' ? '_page' : ''))),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-' . ($table === 'pages' ? 'page' : 'document' ) . '-move')),
			$this->urlRefForCM($url,'returnUrl'),
			0
		);
	}

	/**
	 * Adding CM element for Create new wizard (either db_new.php or sysext/cms/layout/db_new_content_el.php or custom wizard)
	 *
	 * @param	string		Table name
	 * @param	integer		UID for the current record.
	 * @param	array		Record.
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_newWizard($table,$uid,$rec)	{
			//  If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's create new content wizard instead:
		$tmpTSc = t3lib_BEfunc::getModTSconfig($this->pageinfo['uid'],'mod.web_list');
		$tmpTSc = $tmpTSc ['properties']['newContentWiz.']['overrideWithExtension'];
		$newContentWizScriptPath = t3lib_extMgm::isLoaded($tmpTSc) ? (t3lib_extMgm::extRelPath($tmpTSc).'mod1/db_new_content_el.php') : 'sysext/cms/layout/db_new_content_el.php';

		$url = ($table=='pages' || !t3lib_extMgm::isLoaded('cms')) ? 'db_new.php?id='.$uid.'&pagesOnly=1' : $newContentWizScriptPath.'?id='.$rec['pid'].'&sys_language_uid='.intval($rec['sys_language_uid']);
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_newWizard')),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-' . ($table === 'pages' ? 'page' : 'document' ) . '-new')),
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
		$addParam='&columnsOnly='.rawurlencode(implode(',',$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']).($table=='pages' ? ',extendToSubpages' :''));
		$url = 'alt_doc.php?edit['.$table.']['.$uid.']=edit'.$addParam;
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_editAccess')),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-document-edit-access')),
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
	 * @deprecated since TYPO3 4.0, will be removed in TYPO3 4.6 - Use DB_editPageProperties instead
	 */
	function DB_editPageHeader($uid)	{
		t3lib_div::logDeprecatedFunction();
		return $this->DB_editPageProperties($uid);
	}

	/**
	 * Adding CM element for edit page properties
	 *
	 * @param	integer		page uid to edit (PID)
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_editPageProperties($uid)	{
		$url = 'alt_doc.php?edit[pages]['.$uid.']=edit';
		return $this->linkItem(
			$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLL('CM_editPageProperties')),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-page-open')),
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
			// If another module was specified, replace the default Page module with the new one
		$newPageModule = trim($BE_USER->getTSConfigVal('options.overridePageModule'));
		$pageModule = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

		$editOnClick='';
		$loc = 'top.content.list_frame';
		$addParam='';
		$theIcon = 'actions-document-open';
		if (
				$this->iParts[0]=='pages' &&
				$this->iParts[1] &&
				$BE_USER->check('modules', $pageModule)
			)	{
			$theIcon = 'actions-page-open';
			$this->editPageIconSet=1;
			if ($BE_USER->uc['classicPageEditMode'] || !t3lib_extMgm::isLoaded('cms'))	{
				$addParam='&editRegularContentFromId='.intval($this->iParts[1]);
			} else {
				$editOnClick='if(' . $loc . '){' . $loc . ".location.href=top.TS.PATH_typo3+'alt_doc.php?returnUrl='+top.rawurlencode(" . $this->frameLocation($loc . '.document') . ")+'&edit[".$table."][".$uid."]=edit".$addParam."';}";
			}
		}
		if (!$editOnClick)	{
			$editOnClick='if(' . $loc . '){' . $loc . ".location.href=top.TS.PATH_typo3+'alt_doc.php?returnUrl='+top.rawurlencode(" . $this->frameLocation($loc . '.document') . ")+'&edit[".$table."][".$uid."]=edit".$addParam."';}";
		}

		return $this->linkItem(
			$this->label('edit'),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon($theIcon)),
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
		$loc = 'top.content.list_frame';
		$editOnClick='if('.$loc.'){'.$loc.".location.href=top.TS.PATH_typo3+'".
			($this->listFrame?
				"alt_doc.php?returnUrl='+top.rawurlencode(" . $this->frameLocation($loc . '.document') . ")+'&edit[".$table."][-".$uid."]=new'":
				'db_new.php?id='.intval($uid)."'").
			';}';

		return $this->linkItem(
			$this->label('new'),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-' . ($table === 'pages' ? 'page' : 'document' ) . '-new')),
			$editOnClick.'return hideCM();'
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
		$loc = 'top.content.list_frame';
		if($GLOBALS['BE_USER']->jsConfirmation(4))	{
			$conf = "confirm(".$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.delete'),$elInfo[0]) .
						t3lib_BEfunc::referenceCount($table,$uid,' (There are %s reference(s) to this record!)') .
						t3lib_BEfunc::translationCount($table, $uid, ' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.translationsOfRecord'))
					) . ")";
		} else {
			$conf = '1==1';
		}
		$editOnClick = 'if(' . $loc . " && " . $conf . " ){" . $loc . ".location.href=top.TS.PATH_typo3+'tce_db.php?redirect='+top.rawurlencode(" . $this->frameLocation($loc . '.document') . ")+'".
			"&cmd[".$table.']['.$uid.'][delete]=1&prErr=1&vC='.$GLOBALS['BE_USER']->veriCode()."';}hideCM();top.nav.refresh();";

		return $this->linkItem(
			$this->label('delete'),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-edit-delete')),
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
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-document-view')),
			t3lib_BEfunc::viewOnClick($id,$this->PH_backPath,t3lib_BEfunc::BEgetRootLine($id),$anchor).'return hideCM();'
		);
	}

	/**
	 * Adding element for setting temporary mount point.
	 *
	 * @param	integer		Page uid (PID)
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function DB_tempMountPoint($page_id)	{
		return $this->linkItem(
			$this->label('tempMountPoint'),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('apps-pagetree-page-mountpoint')),
			"if (top.content.nav_frame) { top.content.nav_frame.location.href = 'alt_db_navframe.php?setTempDBmount=".intval($page_id)."'; } return hideCM();"
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
		return $this->DB_changeFlag($table, $rec, $hideField, $this->label(($rec[$hideField]?'un':'').'hide'), 'hide');
	}

	/**
	 * Adding CM element for a flag field of the input record
	 *
	 * @param	string		Table name
	 * @param	array		Record array
	 * @param	string		Name of the flag field
	 * @param	string		Menu item Title
	 * @param	string		Name of the item used for icons and labels
	 * @param	string		Icon path relative to typo3/ folder
	 * @return	array		Item array, element in $menuItems
	 */
	function DB_changeFlag($table, $rec, $flagField, $title, $name, $iconRelPath='gfx/')    {
		$uid = $rec['_ORIG_uid'] ? $rec['_ORIG_uid'] : $rec['uid'];
		$editOnClick='';
		$loc = 'top.content.list_frame';
		$editOnClick = 'if(' . $loc . '){' . $loc . ".location.href=top.TS.PATH_typo3+'tce_db.php?redirect='+top.rawurlencode(" . $this->frameLocation($loc . '.document') . ")+'" .
			"&data[" . $table . '][' . $uid . '][' . $flagField . ']=' .
                ($rec[$flagField] ? 0 : 1) .'&prErr=1&vC=' . $GLOBALS['BE_USER']->veriCode()."';}hideCM();top.nav.refresh();";

		return $this->linkItem(
			$title,
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-edit-' . ( $rec[$flagField] ? 'un' : '') . 'hide')),
			$editOnClick.'return false;',
			1
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

		if (file_exists($path) && t3lib_div::isAllowedAbsPath($path))	{
			$fI = pathinfo($path);
			$size=' ('.t3lib_div::formatSize(filesize($path)).'bytes)';
			$icon = t3lib_iconWorks::getSpriteIconForFile(is_dir($path) ? 'folder' : strtolower($fI['extension']),
				array('class'=>'absmiddle', 'title' => htmlspecialchars($fI['basename'] . $size)));

				// edit
			if (!in_array('edit',$this->disabledItems) && is_file($path) && t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'],$fI['extension'])) $menuItems['edit']=$this->FILE_launch($path,'file_edit.php','edit','edit_file.gif');
				// rename
			if (!in_array('rename',$this->disabledItems))	$menuItems['rename']=$this->FILE_launch($path,'file_rename.php','rename','rename.gif');
				// upload
			if (!in_array('upload',$this->disabledItems) && is_dir($path)) {
				$menuItems['upload'] = $this->FILE_upload($path);
			}

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
	 * @param	boolean		If set, the return URL parameter will not be set in the link
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function FILE_launch($path,$script,$type,$image,$noReturnUrl=FALSE)	{
		$loc = 'top.content.list_frame';

		$editOnClick = 'if(' . $loc . '){' . $loc . ".location.href=top.TS.PATH_typo3+'".$script.'?target=' . rawurlencode($path) . ($noReturnUrl ? "'" : "&returnUrl='+top.rawurlencode(" . $this->frameLocation($loc . '.document') . ")") . ";}";

		return $this->linkItem(
			$this->label($type),
			$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->PH_backPath,'gfx/'.$image,'width="12" height="12"').' alt="" />'),
			$editOnClick . 'top.nav.refresh();return hideCM();'
		);
	}

	/**
	 * function for adding an upload entry to the $menuItems array
	 *
	 * @param	string		Path to the file/directory (target)
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function FILE_upload($path) {
		$script = 'file_upload.php';
		$type = 'upload';
		$image = 'upload.gif';
		if ($GLOBALS['BE_USER']->uc['enableFlashUploader']) {
			$loc = 'top.content.list_frame';

			$editOnClick = 'if (top.TYPO3.FileUploadWindow.isFlashAvailable()) { initFlashUploader("' . rawurlencode($path) . '"); } else if(' . $loc . '){' . $loc . ".location.href=top.TS.PATH_typo3+'".$script.'?target=' . rawurlencode($path) . "';}";

			return $this->linkItem(
				$this->label($type),
				$this->excludeIcon('<img'.t3lib_iconWorks::skinImg($this->PH_backPath,'gfx/'.$image,'width="12" height="12"').' alt="" />'),
				$editOnClick . 'top.nav.refresh();return hideCM();'
				);
		} else {
			return $this->FILE_launch($path, $script, $type, $image, true);
		}
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
		$table = '_FILE';		// Pseudo table name for use in the clipboard.
		$uid = t3lib_div::shortmd5($path);
		if ($this->clipObj->current=='normal')	{
			$isSel = $this->clipObj->isSelected($table,$uid);
		}

		$addParam = array();
		if ($this->listFrame)	{
			$addParam['reloadListFrame'] = ($this->alwaysContentFrame ? 2 : 1);
		}

		return $this->linkItem(
			$this->label($type),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-edit-' . $type . ($isSel === $type ? '-release' : ''))),
			"top.loadTopMenu('".$this->clipObj->selUrlFile($path,($type=='copy'?1:0),($isSel==$type),$addParam)."');return false;"
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
		$loc = 'top.content.list_frame';
		if($GLOBALS['BE_USER']->jsConfirmation(4))	{
			$conf = "confirm(".$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.delete'),basename($path)).t3lib_BEfunc::referenceCount('_FILE',$path,' (There are %s reference(s) to this file!)')).")";
		} else {
			$conf = '1==1';
		}
		$editOnClick = 'if(' . $loc . " && " . $conf . " ){" . $loc . ".location.href=top.TS.PATH_typo3+'tce_file.php?redirect='+top.rawurlencode(" . $this->frameLocation($loc . '.document') . ")+'" .
			"&file[delete][0][data]=".rawurlencode($path).'&vC='.$GLOBALS['BE_USER']->veriCode()."';}hideCM();";

		return $this->linkItem(
			$this->label('delete'),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-edit-delete')),
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
		$loc = 'top.content.list_frame';
		if($GLOBALS['BE_USER']->jsConfirmation(2))	{
		$conf=$loc." && confirm(".$GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.'.($elInfo[2]=='copy'?'copy':'move').'_into'),$elInfo[0],$elInfo[1])).")";
		} else {
			$conf=$loc;
		}

		$editOnClick='if('.$conf.'){'.$loc.".location.href=top.TS.PATH_typo3+'".$this->clipObj->pasteUrl('_FILE',$path,0).
			"&redirect='+top.rawurlencode(" . $this->frameLocation($loc . '.document') .'); }hideCM();top.nav.refresh();';

		return $this->linkItem(
			$this->label('pasteinto'),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-document-paste-into')),
			$editOnClick.'return false;'
		);
	}





	/***************************************
	 *
	 * DRAG AND DROP
	 *
	 ***************************************/

	/**
	 * Make 1st level clickmenu:
	 *
	 * @param	string		The absolute path
	 * @param	integer		UID for the current record.
	 * @param	integer		Destination ID
	 * @return	string		HTML content
	 */
	function printDragDropClickMenu($table,$srcId,$dstId)	{
		$menuItems=array();

			// If the drag and drop menu should apply to PAGES use this set of menu items
		if ($table == 'pages')	{
				// Move Into:
			$menuItems['movePage_into']=$this->dragDrop_copymovepage($srcId,$dstId,'move','into');
				// Move After:
			$menuItems['movePage_after']=$this->dragDrop_copymovepage($srcId,$dstId,'move','after');
				// Copy Into:
			$menuItems['copyPage_into']=$this->dragDrop_copymovepage($srcId,$dstId,'copy','into');
				// Copy After:
			$menuItems['copyPage_after']=$this->dragDrop_copymovepage($srcId,$dstId,'copy','after');
		}

			// If the drag and drop menu should apply to FOLDERS use this set of menu items
		if ($table == 'folders')	{
				// Move Into:
			$menuItems['moveFolder_into']=$this->dragDrop_copymovefolder($srcId,$dstId,'move');
				// Copy Into:
			$menuItems['copyFolder_into']=$this->dragDrop_copymovefolder($srcId,$dstId,'copy');
		}

			// Adding external elements to the menuItems array
		$menuItems = $this->processingByExtClassArray($menuItems,"dragDrop_".$table,$srcId);  // to extend this, you need to apply a Context Menu to a "virtual" table called "dragDrop_pages" or similar

			// Processing by external functions?
		$menuItems = $this->externalProcessingOfDBMenuItems($menuItems);

			// Return the printed elements:
		return $this->printItems($menuItems,
			t3lib_iconWorks::getSpriteIconForRecord($table,$this->rec,array('title'=> t3lib_BEfunc::getRecordTitle($table,$this->rec,TRUE)))
		);
	}


	/**
	 * Processing the $menuItems array (for extension classes) (DRAG'N DROP)
	 *
	 * @param	array		$menuItems array for manipulation.
	 * @return	array		Processed $menuItems array
	 */
	function externalProcessingOfDragDropMenuItems($menuItems)	{
		return $menuItems;
	}


	/**
	 * Adding CM element for Copying/Moving a Page Into/After from a drag & drop action
	 *
	 * @param	integer		source UID code for the record to modify
	 * @param	integer		destination UID code for the record to modify
	 * @param	string		Action code: either "move" or "copy"
	 * @param	string		Parameter code: either "into" or "after"
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function dragDrop_copymovepage($srcUid,$dstUid,$action,$into)	{
		$negativeSign = ($into == 'into') ? '' : '-';
		$editOnClick='';
		$loc = 'top.content.list_frame';
		$editOnClick = 'if(' . $loc . '){' . $loc . '.document.location=top.TS.PATH_typo3+"tce_db.php?redirect="+top.rawurlencode(' . $this->frameLocation($loc . '.document') . ')+"' .
			'&cmd[pages]['.$srcUid.']['.$action.']='.$negativeSign.$dstUid.'&prErr=1&vC='.$GLOBALS['BE_USER']->veriCode().'";}hideCM();top.nav.refresh();';

		return $this->linkItem(
			$this->label($action.'Page_'.$into),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-document-paste-' . $into)),
			$editOnClick.'return false;',
			0
		);
	}


	/**
	 * Adding CM element for Copying/Moving a Folder Into from a drag & drop action
	 *
	 * @param	string		source path for the record to modify
	 * @param	string		destination path for the records to modify
	 * @param	string		Action code: either "move" or "copy"
	 * @return	array		Item array, element in $menuItems
	 * @internal
	 */
	function dragDrop_copymovefolder($srcPath,$dstPath,$action)	{
		$editOnClick='';
		$loc = 'top.content.list_frame';
		$editOnClick = 'if(' . $loc . '){' . $loc . '.document.location=top.TS.PATH_typo3+"tce_file.php?redirect="+top.rawurlencode(' . $this->frameLocation($loc . '.document') .')+"' .
			'&file['.$action.'][0][data]='.$srcPath.'&file['.$action.'][0][target]='.$dstPath.'&prErr=1&vC='.$GLOBALS['BE_USER']->veriCode().'";}hideCM();top.nav.refresh();';

		return $this->linkItem(
			$this->label($action.'Folder_into'),
			$this->excludeIcon(t3lib_iconWorks::getSpriteIcon('apps-pagetree-drag-move-into')),
			$editOnClick.'return false;',
			0
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

			// Enable/Disable items:
		$menuItems = $this->enableDisableItems($menuItems);

			// Clean up spacers:
		$menuItems = $this->cleanUpSpacers($menuItems);

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
						<td><img'.t3lib_iconWorks::skinImg($this->PH_backPath,'gfx/acm_spacer2.gif','width="8" height="12"').' alt="" /></td>
						<td class="c-item">',$this->menuItemsForTopFrame($menuItems)).
						'</td>

							<!-- Close button: -->
						<td class="c-closebutton"><a href="#" onclick="hideCM();return false;">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-close', array(
								'title'=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.close', 1)
							)) . '</a></td>

							<!-- The item of the clickmenu: -->
						<td class="c-itemicon">'.$item.'</td>
					</tr>
				</table>
			';

				// Set remaining BACK_PATH to blank (if any)
			$out = str_replace($this->PH_backPath,'',$out);
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
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-CSM">
					'.implode('',$this->menuItemsForClickMenu($menuItems)).'
				</table>';

				// Wrap the inner table in another table to create outer border:
			$CMtable = $this->wrapColorTableCM($CMtable);

				// Set back path place holder to real back path
			$CMtable = str_replace($this->PH_backPath,$this->backPath,$CMtable);
			if ($this->ajax)	{
				$innerXML = '<data><clickmenu><htmltable><![CDATA['.$CMtable.']]></htmltable><cmlevel>'.$this->cmLevel.'</cmlevel></clickmenu></data>';
				return $innerXML;
			} else {
					// Create JavaScript section:
				$script=$GLOBALS['TBE_TEMPLATE']->wrapScriptTags('

				if (top.content && top.content'.$frameName.' && top.content'.$frameName.'.Clickmenu)	{
					top.content'.$frameName.'.Clickmenu.populateData(unescape("'.t3lib_div::rawurlencodeJS($CMtable).'"),'.$this->cmLevel.');
				}
				'.(!$this->doDisplayTopFrameCM()?'hideCM();':'')
				);
				return $script;
			}
		}
	}

	/**
	 * Wrapping the input string in a table with background color 4 and a black border style.
	 * For the pop-up menu
	 *
	 * @param	string		HTML content to wrap in table.
	 * @return	string
	 */
	function wrapColorTableCM($str)	{

		return '<div class="typo3-CSM-wrapperCM">
			' . $str . '
			</div>';
	}

	/**
	 * Traverses the menuItems and generates an output array for implosion in the topframe horizontal menu
	 *
	 * @param	array		$menuItem array
	 * @param	array		Array with HTML content to be imploded between <td>-tags
	 * @return	array		Array of menu items for top frame.
	 */
	function menuItemsForTopFrame($menuItems)	{
		$out=array();
		foreach ($menuItems as $i) {
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
		$out=array();
		foreach ($menuItems as $cc => $i) {
			if (is_string($i) && $i=='spacer')	{	// MAKE horizontal spacer
				$out[]='
					<tr class="bgColor2">
						<td colspan="2"><img src="clear.gif" width="1" height="1" alt="" /></td>
					</tr>';
			} else {	// Just make normal element:
				$onClick=$i[3];
				$onClick=preg_replace('/return[[:space:]]+hideCM\(\)[[:space:]]*;/i','',$onClick);
				$onClick=preg_replace('/return[[:space:]]+false[[:space:]]*;/i','',$onClick);
				$onClick=preg_replace('/hideCM\(\);/i','',$onClick);
				if (!$i[5])	$onClick.='Clickmenu.hideAll();';

				if ($GLOBALS['TYPO3_CONF_VARS']['BE']['useOnContextMenuHandler'])   {
					$CSM = ' oncontextmenu="'.htmlspecialchars($onClick).';return false;"';
				}

				$out[]='
					<tr class="typo3-CSM-itemRow" onclick="'.htmlspecialchars($onClick).'" onmouseover="this.bgColor=\''.$GLOBALS['TBE_TEMPLATE']->bgColor5.'\';" onmouseout="this.bgColor=\'\';"'.$CSM.'>
						'.(!$this->leftIcons?'<td class="typo3-CSM-item">'.$i[1].'</td><td align="center">'.$i[2].'</td>' : '<td align="center">'.$i[2].'</td><td class="typo3-CSM-item">'.$i[1].'</td>').'
					</tr>';
			}
		}
		return $out;
	}

	/**
	 * Adds or inserts a menu item
	 * Can be used to set the position of new menu entries within the list of existing menu entries. Has this syntax: [cmd]:[menu entry key],[cmd].... cmd can be "after", "before" or "top" (or blank/"bottom" which is default). If "after"/"before" then menu items will be inserted after/before the existing entry with [menu entry key] if found. "after-spacer" and "before-spacer" do the same, but inserts before or after an item and a spacer. If not found, the bottom of list. If "top" the items are inserted in the top of the list.
	 *
	 * @param	array		Menu items array
	 * @param	array		Menu items array to insert
	 * @param	string		Position command string. Has this syntax: [cmd]:[menu entry key],[cmd].... cmd can be "after", "before" or "top" (or blank/"bottom" which is default). If "after"/"before" then menu items will be inserted after/before the existing entry with [menu entry key] if found. "after-spacer" and "before-spacer" do the same, but inserts before or after an item and a spacer. If not found, the bottom of list. If "top" the items are inserted in the top of the list.
	 * @return	array		Menu items array, processed.
	 */
	function addMenuItems($menuItems,$newMenuItems,$position='')	{
		if (is_array($newMenuItems))	{

			if($position) {

				$posArr = t3lib_div::trimExplode(',', $position, 1);
				foreach($posArr as $pos) {
					list($place,$menuEntry) = t3lib_div::trimExplode(':', $pos, 1);
					list($place,$placeExtra) = t3lib_div::trimExplode('-', $place, 1);

						// bottom
					$pointer = count($menuItems);

					$found=FALSE;

					if ($place) {
						switch(strtolower($place))	{
							case 'after':
							case 'before':
								if ($menuEntry) {
									$p=1;
									reset ($menuItems);
									while (true) {
										if (!strcmp(key($menuItems), $menuEntry))	{
											$pointer = $p;
											$found=TRUE;
											break;
										}
										if (!next($menuItems)) break;
										$p++;
									}
									if (!$found) break;

									if ($place=='before') {
										$pointer--;
										if ($placeExtra=='spacer' AND prev($menuItems)=='spacer') {
											$pointer--;
										}
									} elseif ($place=='after') {
										if ($placeExtra=='spacer' AND next($menuItems)=='spacer') {
											$pointer++;
										}
									}
								}
							break;
							default:
								if (strtolower($place)=='top')	{
									$pointer = 0;
								} else {
									$pointer = count($menuItems);
								}
								$found=TRUE;
							break;
						}
					}
					if($found) break;
				}
			}
			$pointer=max(0,$pointer);
			$menuItemsBefore = array_slice($menuItems, 0, ($pointer?$pointer:0));
			$menuItemsAfter = array_slice($menuItems, $pointer);
			$menuItems = $menuItemsBefore + $newMenuItems + $menuItemsAfter;
		}
		return $menuItems;
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
		global $BACK_PATH;

		$this->elCount++;
		if($this->ajax)	{
			$onClick = str_replace('top.loadTopMenu', 'showClickmenu_raw', $onClick);
		}


		return array(
			t3lib_iconWorks::getSpriteIcon('empty-empty', array(
				'class' => 'c-roimg',
				'id'=> 'roimg_' . $this->elCount
			)) .
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
	 * Enabling / Disabling items based on list provided from GET var ($this->iParts[3])
	 *
	 * @param	array		Menu items array
	 * @return	array		Menu items array, processed.
	 */
	function enableDisableItems($menuItems)	{
		if ($this->iParts[3])	{

				// Detect "only" mode: (only showing listed items)
			if (substr($this->iParts[3],0,1)=='+')	{
				$this->iParts[3] = substr($this->iParts[3],1);
				$only = TRUE;
			} else {
				$only = FALSE;
			}

				// Do filtering:
			if ($only)	{	// Transfer ONLY elements which are mentioned (or are spacers)
				$newMenuArray = array();
				foreach($menuItems as $key => $value)	{
					if (t3lib_div::inList($this->iParts[3], $key) || (is_string($value) && $value=='spacer'))	{
						$newMenuArray[$key] = $value;
					}
				}
				$menuItems = $newMenuArray;
			} else {	// Traverse all elements except those listed (just unsetting them):
				$elements = t3lib_div::trimExplode(',',$this->iParts[3],1);
				foreach($elements as $value)	{
					unset($menuItems[$value]);
				}
			}
		}

			// Return processed menu items:
		return $menuItems;
	}

	/**
	 * Clean up spacers; Will remove any spacers in the start/end of menu items array plus any duplicates.
	 *
	 * @param	array		Menu items array
	 * @return	array		Menu items array, processed.
	 */
	function cleanUpSpacers($menuItems)	{

			// Remove doubles:
		$prevItemWasSpacer = FALSE;
		foreach($menuItems as $key => $value)	{
			if (is_string($value) && $value=='spacer')	{
				if ($prevItemWasSpacer)	{
					unset($menuItems[$key]);
				}
				$prevItemWasSpacer = TRUE;
			} else {
				$prevItemWasSpacer = FALSE;
			}
		}

			// Remove first:
		reset($menuItems);
		$key = key($menuItems);
		$value = current($menuItems);
		if (is_string($value) && $value=='spacer')	{
			unset($menuItems[$key]);
		}


			// Remove last:
		end($menuItems);
		$key = key($menuItems);
		$value = current($menuItems);
		if (is_string($value) && $value=='spacer')	{
			unset($menuItems[$key]);
		}

			// Return processed menu items:
		return $menuItems;
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
		if($this->ajax)	{
			return !$this->CB;
		} else {
			return $GLOBALS['SOBE']->doc->isCMlayers() && !$this->CB;
		}
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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 * @see template::getContextMenuCode()
 */
class SC_alt_clickmenu {

		// Internal, static: GPvar:
	var $backPath;					// Back path.
	var $item;						// Definition of which item the click menu should be made for.
	var $reloadListFrame;			// Defines the name of the document object for which to reload the URL.

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
		$this->backPath = t3lib_div::_GP('backPath');
		$this->item = t3lib_div::_GP('item');
		$this->reloadListFrame = t3lib_div::_GP('reloadListFrame');

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
		if (!$this->ajax)	{
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $BACK_PATH;
		}

			// Setting mode for display and background image in the top frame
		$this->dontDisplayTopFrameCM= $this->doc->isCMlayers() && !$BE_USER->getTSConfigVal('options.contextMenu.options.alwaysShowClickMenuInTopFrame');
		if ($this->dontDisplayTopFrameCM)	{
			$this->doc->bodyTagId.= '-notop';
		}

			// Setting clickmenu timeout
		$secs = t3lib_div::intInRange($BE_USER->getTSConfigVal('options.contextMenu.options.clickMenuTimeOut'),1,100,5);	// default is 5

			// Setting the JavaScript controlling the timer on the page
		$listFrameDoc = $this->reloadListFrame!=2 ? 'top.content.list_frame' : 'top.content';
		$this->doc->JScode.=$this->doc->wrapScriptTags('
	var date = new Date();
	var mo_timeout = Math.floor(date.getTime()/1000);

	roImg = "' . t3lib_iconWorks::getSpriteIconClasses('status-status-current') . '";

	routImg = "t3-icon-empty";

	function mo(c)	{	//
		var name="roimg_"+c;
		document.getElementById(name).className = roImg;
		updateTime();
	}
	function mout(c)	{	//
		var name="roimg_"+c;
		document[name].src = routImg;
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
		window.location.href="alt_topmenu_dummy.php";
		return false;
	}

		// Start timer
	timeout_func();

	'.($this->reloadListFrame ? '
		// Reload list frame:
	if('.$listFrameDoc.'){'.$listFrameDoc.'.location.href='.$listFrameDoc.'.location.href;}' :
	'').'
		');
	}

	/**
	 * Main function - generating the click menu in whatever form it has.
	 *
	 * @return	void
	 */
	function main()	{
		$this->ajax = t3lib_div::_GP('ajax') ? TRUE : FALSE;

			// Initialize Clipboard object:
		$clipObj = t3lib_div::makeInstance('t3lib_clipboard');
		$clipObj->initializeClipboard();
		$clipObj->lockToNormal();	// This locks the clipboard to the Normal for this request.

			// Update clipboard if some actions are sent.
		$CB = t3lib_div::_GET('CB');
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

			// Start page
		if(!$this->ajax)	{
			$this->content.= $this->doc->startPage('Context Sensitive Menu');
		}
			// Set content of the clickmenu with the incoming var, "item"
		$this->content.= $clickMenu->init();
	}

	/**
	 * End page and output content.
	 *
	 * @return	void
	 */
	function printContent()	{
		if (!$this->ajax)	{
			$this->content.= $this->doc->endPage();
			$this->content = $this->doc->insertStylesAndJS($this->content);
			echo $this->content;
		} else {
			$this->content = $GLOBALS['LANG']->csConvObj->utf8_encode($this->content,$GLOBALS['LANG']->charSet);
			t3lib_ajax::outputXMLreply($this->content);
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_clickmenu.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_clickmenu.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_clickmenu');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
