<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Shortcut frame
 * Appears in the bottom frame of the backend frameset.
 * Provides links to registered shortcuts
 * If the 'cms' extension is loaded you will also have a field for entering page id/alias which will be found/edited
 *
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML compliant output
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   82: class SC_alt_shortcut
 *  121:     function preinit()
 *  146:     function preprocess()
 *  203:     function init()
 *  237:     function main()
 *  345:     function editLoadedFunc()
 *  406:     function editPageIdFunc()
 *  454:     function printContent()
 *
 *              SECTION: OTHER FUNCTIONS:
 *  482:     function mIconFilename($Ifilename,$backPath)
 *  495:     function getIcon($modName)
 *  519:     function itemLabel($inlabel,$modName,$M_modName='')
 *
 * TOTAL FUNCTIONS: 10
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


require('init.php');
require('template.php');
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');
require_once(PATH_t3lib.'class.t3lib_loadmodules.php');
require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');






/**
 * Script Class for the shortcut frame, bottom frame of the backend frameset
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_shortcut {

		// Internal, static: GPvar
	var $modName;
	var $M_modName;
	var $URL;
	var $editSC;
	var $deleteCategory;
	var $editName;
	var $editGroup;
	var $whichItem;

		// Internal, static:
	var $loadModules;		// Modules object
	var $doc;				// Document template object
	var $nGroups;			// Number of groups
	var $nGlobals;			// Number of globals

		// Internal, dynamic:
	var $content;			// Accumulation of output HTML (string)
	var $lines;				// Accumulation of table cells (array)

	var $editLoaded;		// Flag for defining whether we are editing
	var $editError;			// Can contain edit error message
	var $editPath;			// Set to the record path of the record being edited.
	var $editSC_rec;		// Holds the shortcut record when editing
	var $theEditRec;		// Page record to be edited
	var $editPage;			// Page alias or id to be edited
	var $selOpt;			// Select options.

	var $alternativeTableUid = array();	// Array with key 0/1 being table/uid of record to edit. Internally set.



	/**
	 * Pre-initialization - setting input variables for storing shortcuts etc.
	 *
	 * @return	void
	 */
	function preinit()	{
		global $TBE_MODULES;

			// Setting GPvars:
		$this->modName = t3lib_div::_GP('modName');
		$this->M_modName = t3lib_div::_GP('motherModName');
		$this->URL = t3lib_div::_GP('URL');
		$this->editSC = t3lib_div::_GP('editShortcut');

		$this->deleteCategory = t3lib_div::_GP('deleteCategory');
		$this->editPage = t3lib_div::_GP('editPage');
		$this->editName = t3lib_div::_GP('editName');
		$this->editGroup = t3lib_div::_GP('editGroup');
		$this->whichItem = t3lib_div::_GP('whichItem');

			// Creating modules object
		$this->loadModules = t3lib_div::makeInstance('t3lib_loadModules');
		$this->loadModules->load($TBE_MODULES);
	}

	/**
	 * Adding shortcuts, editing shortcuts etc.
	 *
	 * @return	void
	 */
	function preprocess()	{
		global $BE_USER;

			// Adding a shortcut being set from another frame
		if ($this->modName && $this->URL)	{
			$fields_values = array(
				'userid' => $BE_USER->user['uid'],
				'module_name' => $this->modName.'|'.$this->M_modName,
				'url' => $this->URL,
				'sorting' => time()
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_be_shortcuts', $fields_values);
		}

			// Selection-clause for users - so users can deleted only their own shortcuts (except admins)
		$addUSERWhere = (!$BE_USER->isAdmin()?' AND userid='.intval($BE_USER->user['uid']):'');

			// Deleting shortcuts:
		if (strcmp($this->deleteCategory,''))	{
			if (t3lib_div::testInt($this->deleteCategory))	{
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_be_shortcuts', 'sc_group='.intval($this->deleteCategory).$addUSERWhere);
			}
		}

			// If other changes in post-vars:
		if (is_array($_POST))	{
				// Saving:
			if (isset($_POST['_savedok_x']) || isset($_POST['_saveclosedok_x']))	{
				$fields_values = array(
					'description' => $this->editName,
					'sc_group' => intval($this->editGroup)
				);
				if ($fields_values['sc_group']<0 && !$BE_USER->isAdmin())	{
					$fields_values['sc_group']=0;
				}

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_be_shortcuts', 'uid='.intval($this->whichItem).$addUSERWhere, $fields_values);
			}
				// If save without close, keep the session going...
			if (isset($_POST['_savedok_x']))	{
				$this->editSC=$this->whichItem;
			}
				// Deleting a single shortcut ?
			if (isset($_POST['_deletedok_x']))	{
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_be_shortcuts', 'uid='.intval($this->whichItem).$addUSERWhere);

				if (!$this->editSC)	$this->editSC=-1;	// Just to have the checkbox set...
			}
		}

	}

	/**
	 * Initialize (page output)
	 *
	 * @return	void
	 */
	function init()	{
		global $BACK_PATH;

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form='<form action="alt_shortcut.php" name="shForm" method="post">';
		$this->doc->docType='xhtml_trans';
		$this->doc->divClass='typo3-shortcut';
		$this->doc->JScode.=$this->doc->wrapScriptTags('
			function jump(url,modName,mainModName)	{	//
					// Clear information about which entry in nav. tree that might have been highlighted.
				top.fsMod.navFrameHighlightedID = new Array();
				if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)	{
					top.content.nav_frame.refresh_nav();
				}

				top.nextLoadModuleUrl = url;
				top.goToModule(modName);
			}
			function editSh(uid)	{	//
				document.location="alt_shortcut.php?editShortcut="+uid;
			}
			function submitEditPage(id)	{	//
				document.location="alt_shortcut.php?editPage="+top.rawurlencode(id);
			}
			');
		$this->content.=$this->doc->startPage('Shortcut frame');
	}

	/**
	 * Main function, creating content in the frame
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$TCA;

			// Setting groups and globals
		$this->nGroups=4;
		$this->nGlobals=5;

		$globalGroups=array(-100);
		$shortCutGroups = $BE_USER->getTSConfig('options.shortcutGroups');
		for($a=1;$a<=$this->nGlobals;$a++)	{
			if ($BE_USER->isAdmin() || strcmp($shortCutGroups['properties'][$a],''))	{
				$globalGroups[]=-$a;
			}
		}

			// Fetching shortcuts to display for this user:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_be_shortcuts', '((userid='.$BE_USER->user['uid'].' AND sc_group>=0) OR sc_group IN ('.implode(',',$globalGroups).'))', '', 'sc_group,sorting');

			// Init vars:
		$this->lines=array();
		$this->editSC_rec='';
		$this->selOpt=array();
		$formerGr='';

			// Traverse shortcuts
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			if ($this->editSC && $row['uid']==$this->editSC)	{
				$this->editSC_rec=$row;
			}

			if (strcmp($formerGr,$row['sc_group']))	{
				if ($row['sc_group']!=-100)	{
					if ($row['sc_group']>=0)	{
						$onC = 'if (confirm('.$GLOBALS['LANG']->JScharCode($LANG->getLL('shortcut_delAllInCat')).')){document.location=\'alt_shortcut.php?deleteCategory='.$row['sc_group'].'\';}return false;';
						$this->lines[]='<td>&nbsp;</td><td class="bgColor5"><a href="#" onclick="'.htmlspecialchars($onC).'" title="'.$LANG->getLL('shortcut_delAllInCat',1).'">'.abs($row['sc_group']).'</a></td>';
					} else {
						$this->lines[]='<td>&nbsp;</td><td class="bgColor5">'.abs($row['sc_group']).'</td>';
					}
				}
			}

			$mParts = explode('|',$row['module_name']);
			$row['module_name']=$mParts[0];
			$row['M_module_name']=$mParts[1];
			$mParts = explode('_',$row['M_module_name']?$row['M_module_name']:$row['module_name']);
			$qParts = parse_url($row['url']);

			$bgColorClass = $row['uid']==$this->editSC ? 'bgColor5' : ($row['sc_group']<0 ? 'bgColor6' : 'bgColor4');
			$titleA = $this->itemLabel($row['description']&&($row['uid']!=$this->editSC) ? $row['description'] : t3lib_div::fixed_lgd(rawurldecode($qParts['query']),150),$row['module_name'],$row['M_module_name']);

			$editSH = ($row['sc_group']>=0 || $BE_USER->isAdmin()) ? 'editSh('.intval($row['uid']).');' : "alert('".$LANG->getLL('shortcut_onlyAdmin')."')";
			$jumpSC = 'jump(unescape(\''.rawurlencode($row['url']).'\'),\''.implode('_',$mParts).'\',\''.$mParts[0].'\');';
			$onC = 'if (document.shForm.editShortcut_check && document.shForm.editShortcut_check.checked){'.$editSH.'}else{'.$jumpSC.'}return false;';
			$this->lines[]='<td class="'.$bgColorClass.'"><a href="#" onclick="'.htmlspecialchars($onC).'"><img src="'.$this->getIcon($row['module_name']).'" title="'.htmlspecialchars($titleA).'" alt="" /></a></td>';
			if (trim($row['description']))	{
				$kkey = strtolower(substr($row['description'],0,20)).'_'.$row['uid'];
				$this->selOpt[$kkey]='<option value="'.htmlspecialchars($jumpSC).'">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($row['description'],50)).'</option>';
			}
			$formerGr=$row['sc_group'];
		}
		ksort($this->selOpt);
		array_unshift($this->selOpt,'<option>['.$LANG->getLL('shortcut_selSC',1).']</option>');

		$this->editLoadedFunc();
		$this->editPageIdFunc();

		if (!$this->editLoaded && t3lib_extMgm::isLoaded('cms'))	{
				$editIdCode = '<td nowrap="nowrap">'.$LANG->getLL('shortcut_editID',1).': <input type="text" value="'.($this->editError?htmlspecialchars($this->editPage):'').'" name="editPage"'.$this->doc->formWidth(5).' onchange="submitEditPage(this.value);" />'.
					($this->editError?'&nbsp;<strong><span class="typo3-red">'.htmlspecialchars($this->editError).'</span></strong>':'').
					(is_array($this->theEditRec)?'&nbsp;<strong>'.$LANG->getLL('shortcut_loadEdit',1).' \''.t3lib_BEfunc::getRecordTitle('pages',$this->theEditRec,1).'\'</strong> ('.htmlspecialchars($this->editPath).')':'').
					'</td>';
		} else $editIdCode = '';

			// Adding CSH:
		$editIdCode.= '<td>&nbsp;'.t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'shortcuts', $GLOBALS['BACK_PATH'],'',TRUE).'</td>';

		$this->content.='


			<!--
				Shortcut Display Table:
			-->
			<table border="0" cellpadding="0" cellspacing="2" id="typo3-shortcuts">
				<tr>
				'.implode('
				',$this->lines).$editIdCode.'
				</tr>
			</table>

			';

			// Launch Edit page:
		if ($this->theEditRec['uid'])	{
			$this->content.=$this->doc->wrapScriptTags('top.loadEditId('.$this->theEditRec['uid'].');');
		}

			// Load alternative table/uid into editing form.
		if (count($this->alternativeTableUid)==2 && isset($TCA[$this->alternativeTableUid[0]]) && t3lib_div::testInt($this->alternativeTableUid[1]))	{
			$JSaction = t3lib_BEfunc::editOnClick('&edit['.$this->alternativeTableUid[0].']['.$this->alternativeTableUid[1].']=edit','','dummy.php');
			$this->content.=$this->doc->wrapScriptTags('function editArbitraryElement() { top.content.'.$JSaction.'; } editArbitraryElement();');
		}
	}

	/**
	 * Creates lines for the editing form.
	 *
	 * @return	void
	 */
	function editLoadedFunc()	{
		global $BE_USER,$LANG;

		$this->editLoaded=0;
		if (is_array($this->editSC_rec) && ($this->editSC_rec['sc_group']>=0 || $BE_USER->isAdmin()))	{	// sc_group numbers below 0 requires admin to edit those. sc_group numbers above zero must always be owned by the user himself.
			$this->editLoaded=1;

			$opt=array();
			$opt[]='<option value="0"></option>';
			for($a=1;$a<=$this->nGroups;$a++)	{
				$opt[]='<option value="'.$a.'"'.(!strcmp($this->editSC_rec['sc_group'],$a)?' selected="selected"':'').'>'.$LANG->getLL('shortcut_group',1).' '.$a.'</option>';
			}
			if ($BE_USER->isAdmin())	{
				for($a=1;$a<=$this->nGlobals;$a++)	{
					$opt[]='<option value="-'.$a.'"'.(!strcmp($this->editSC_rec['sc_group'],'-'.$a)?' selected="selected"':'').'>'.$LANG->getLL('shortcut_GLOBAL',1).': '.$a.'</option>';
				}
				$opt[]='<option value="-100"'.(!strcmp($this->editSC_rec['sc_group'],'-100')?' selected="selected"':'').'>'.$LANG->getLL('shortcut_GLOBAL',1).': '.$LANG->getLL('shortcut_ALL',1).'</option>';
			}

				// border="0" hspace="2" width="21" height="16" - not XHTML compliant in <input type="image" ...>
			$manageForm='


				<!--
					Shortcut Editing Form:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-shortcuts-editing">
					<tr>
						<td>&nbsp;&nbsp;</td>
						<td><input type="image" class="c-inputButton" name="_savedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/savedok.gif','').' title="'.$LANG->getLL('shortcut_save',1).'" /></td>
						<td><input type="image" class="c-inputButton" name="_saveclosedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/saveandclosedok.gif','').' title="'.$LANG->getLL('shortcut_saveClose',1).'" /></td>
						<td><input type="image" class="c-inputButton" name="_closedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/closedok.gif','').' title="'.$LANG->getLL('shortcut_close',1).'" /></td>
						<td><input type="image" class="c-inputButton" name="_deletedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/deletedok.gif','').' title="'.$LANG->getLL('shortcut_delete',1).'" /></td>
						<td><input name="editName" type="text" value="'.htmlspecialchars($this->editSC_rec['description']).'"'.$this->doc->formWidth(15).' /></td>
						<td><select name="editGroup">'.implode('',$opt).'</select></td>
					</tr>
				</table>
				<input type="hidden" name="whichItem" value="'.$this->editSC_rec['uid'].'" />

				';
		} else $manageForm='';
//debug(count($opt));
		if (!$this->editLoaded && count($this->selOpt)>1)	{
			$this->lines[]='<td>&nbsp;</td>';
			$this->lines[]='<td><select name="_selSC" onchange="eval(this.options[this.selectedIndex].value);this.selectedIndex=0;">'.implode('',$this->selOpt).'</select></td>';
		}
		if (count($this->lines))	{
			if (!$BE_USER->getTSConfigVal('options.mayNotCreateEditShortcuts'))	{
				$this->lines=array_merge(array('<td><input type="checkbox" name="editShortcut_check" value="1"'.($this->editSC?' checked="checked"':'').' />'.$LANG->getLL('shortcut_edit',1).'&nbsp;</td>'),$this->lines);
				$this->lines[]='<td>'.$manageForm.'</td>';
			}
			$this->lines[]='<td><img src="clear.gif" width="10" height="1" alt="" /></td>';
		}
	}

	/**
	 * If "editPage" value is sent to script and it points to an accessible page, the internal var $this->theEditRec is set to the page record which should be loaded.
	 * Returns void
	 *
	 * @return	void
	 */
	function editPageIdFunc()	{
		global $BE_USER,$LANG;

		if (!t3lib_extMgm::isLoaded('cms'))	return;

			// EDIT page:
		$this->editPage = trim(strtolower($this->editPage));
		$this->editError = '';
		$this->theEditRec = '';
		if ($this->editPage)	{

				// First, test alternative value consisting of [table]:[uid] and if not found, proceed with traditional page ID resolve:
			$this->alternativeTableUid = explode(':',$this->editPage);
			if (!(count($this->alternativeTableUid)==2 && $BE_USER->isAdmin()))	{	// We restrict it to admins only just because I'm not really sure if alt_doc.php properly checks permissions of passed records for editing. If alt_doc.php does that, then we can remove this.

				$where = ' AND ('.$BE_USER->getPagePermsClause(2).' OR '.$BE_USER->getPagePermsClause(16).')';
				if (t3lib_div::testInt($this->editPage))	{
					$this->theEditRec = t3lib_BEfunc::getRecord ('pages',$this->editPage,'*',$where);
				} else {
					$records = t3lib_BEfunc::getRecordsByField('pages','alias',$this->editPage,$where);
					if (is_array($records))	{
						reset($records);
						$this->theEditRec = current($records);
					}
				}
				if (!is_array($this->theEditRec) || !$BE_USER->isInWebMount($this->theEditRec['uid']))	{
					unset($this->theEditRec);
					$this->editError=$LANG->getLL('shortcut_notEditable');
				} else {
						// Visual path set:
					$perms_clause = $BE_USER->getPagePermsClause(1);
					$this->editPath = t3lib_BEfunc::getRecordPath($this->theEditRec['pid'], $perms_clause, 30);

					if(!$BE_USER->getTSConfigVal('options.shortcut_onEditId_dontSetPageTree')) {

							// Expanding page tree:
						t3lib_BEfunc::openPageTree($this->theEditRec['pid'],!$BE_USER->getTSConfigVal('options.shortcut_onEditId_keepExistingExpanded'));
					}
				}
			}
		}
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}











	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/

	/**
	 * Returns relative filename for icon.
	 *
	 * @param	string		Absolute filename of the icon
	 * @param	string		Backpath string to prepend the icon after made relative
	 * @return	void
	 */
	function mIconFilename($Ifilename,$backPath)	{
		if (t3lib_div::isAbsPath($Ifilename))	{
			$Ifilename = '../'.substr($Ifilename,strlen(PATH_site));
		}
		return $backPath.$Ifilename;
	}

	/**
	 * Returns icon for shortcut display
	 *
	 * @param	string		Backend module name
	 * @return	string		Icon file name
	 */
	function getIcon($modName)	{
		global $LANG;
		if ($LANG->moduleLabels['tabs_images'][$modName.'_tab'])	{
			$icon = $this->mIconFilename($LANG->moduleLabels['tabs_images'][$modName.'_tab'],'');
		} elseif ($modName=='xMOD_alt_doc.php') {
			$icon = 'gfx/edit2.gif';
		} elseif ($modName=='xMOD_file_edit.php') {
			$icon = 'gfx/edit_file.gif';
		} elseif ($modName=='xMOD_wizard_rte.php') {
			$icon = 'gfx/edit_rtewiz.gif';
		} else {
			$icon = 'gfx/dummy_module.gif';
		}
		return $icon;
	}

	/**
	 * Returns title-label for icon
	 *
	 * @param	string		In-label
	 * @param	string		Backend module name (key)
	 * @param	string		Backend module label (user defined?)
	 * @return	string		Label for the shortcut item
	 */
	function itemLabel($inlabel,$modName,$M_modName='')	{
		global $LANG;
		if (substr($modName,0,5)=='xMOD_')	{
			$label=substr($modName,5);
		} else {
			$split = explode('_',$modName);
			$label = $LANG->moduleLabels['tabs'][$split[0].'_tab'];
			if (count($split)>1)	{
				$label.='>'.$LANG->moduleLabels['tabs'][$modName.'_tab'];
			}
		}
		if ($M_modName)	$label.=' ('.$M_modName.')';
		$label.=': '.$inlabel;
		return $label;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_shortcut.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_shortcut.php']);
}











// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_shortcut');
$SOBE->preinit();
$SOBE->preprocess();
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
