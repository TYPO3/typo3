<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
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
 * 
 * Appears in the bottom frame of the backend frameset.
 * Provides links to registered shortcuts
 * If the 'cms' extension is loaded you will also have a field for entering page id/alias which will be found/edited
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML compliant output
 */

 
require ('init.php');
require ('template.php');
include ('sysext/lang/locallang_misc.php');
require_once (PATH_t3lib.'class.t3lib_loadmodules.php');
require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');



// ***************************
// Script Classes
// ***************************
class SC_alt_shortcut {
	var $content;
	var $loadModules;
	var $modName;
	var $M_modName;
	var $URL;
	var $editSC;
	var $nGroups;
	var $nGlobals;
	var $lines;
	var $editSC_rec;
	var $editLoaded;
	var $editError;
	var $theEditRec;
	var $editPage;
	var $doc;	
	var $selOpt;

	/**
	 * Pre-initialization - setting input variables for storing shortcuts etc.
	 */
	function preinit()	{
		global $TBE_MODULES;

		$this->loadModules = t3lib_div::makeInstance('t3lib_loadModules');
		$this->loadModules->load($TBE_MODULES);
		
		$this->modName = t3lib_div::GPvar('modName');
		$this->M_modName = t3lib_div::GPvar('motherModName');
		$this->URL = t3lib_div::GPvar('URL');
		$this->editSC = t3lib_div::GPvar('editShortcut');
	}

	/**
	 * Adding shortcuts, editing shortcuts etc.
	 */
	function preprocess()	{
		global $BE_USER,$HTTP_POST_VARS;

			// Adding a shortcut being set from another frame
		if ($this->modName && $this->URL)	{
			$fields_values=array();
			$fields_values['userid']=$BE_USER->user['uid'];
			$fields_values['module_name']=$this->modName.'|'.$this->M_modName;
			$fields_values['url']=$this->URL;
			$fields_values['sorting']=time();
			$query = t3lib_BEfunc::DBcompileInsert('sys_be_shortcuts',$fields_values);
			$res = mysql(TYPO3_db,$query);
			echo mysql_error();
		}

			// Selection-clause for users - so users can deleted only their own shortcuts (except admins)
		$addUSERWhere = (!$BE_USER->isAdmin()?' AND userid='.intval($BE_USER->user['uid']):'');
			
			// Deleting shortcuts:
		if (strcmp(t3lib_div::GPvar('deleteCategory'),''))	{
			$delCat = t3lib_div::GPvar('deleteCategory');
			if (t3lib_div::testInt($delCat))	{
				$q = 'DELETE FROM sys_be_shortcuts WHERE sc_group='.$delCat.$addUSERWhere;
				$res=mysql(TYPO3_db,$q);
			}
		}
		
			// If other changes in post-vars:
		if ($HTTP_POST_VARS)	{
				// Saving:
			if (isset($HTTP_POST_VARS['_savedok_x']) || isset($HTTP_POST_VARS['_saveclosedok_x']))	{
				$fields_values=array();
				$fields_values['description']=t3lib_div::GPvar('editName');
				$fields_values['sc_group']=intval(t3lib_div::GPvar('editGroup'));
				if ($fields_values['sc_group']<0 && !$BE_USER->isAdmin())	{
					$fields_values['sc_group']=0;
				}
		
				$q = t3lib_BEfunc::DBcompileUpdate('sys_be_shortcuts','uid='.intval(t3lib_div::GPvar('whichItem')).$addUSERWhere,$fields_values);
				$res=mysql(TYPO3_db,$q);
			}
				// If save without close, keep the session going...
			if (isset($HTTP_POST_VARS['_savedok_x']))	{
				$this->editSC=t3lib_div::GPvar('whichItem');
			}
				// Deleting a single shortcut ?
			if (isset($HTTP_POST_VARS['_deletedok_x']))	{
				$q = 'DELETE FROM sys_be_shortcuts WHERE uid='.intval(t3lib_div::GPvar('whichItem')).$addUSERWhere;
				$res=mysql(TYPO3_db,$q);
				if (!$this->editSC)	$this->editSC=-1;	// Just to have the checkbox set...
			}
		}
	
	}

	/**
	 * Initialize (page output)
	 */
	function init()	{
		global $BACK_PATH;

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form='<form name="shForm" action="alt_shortcut.php" method="post">';
		$this->doc->docType='xhtml_trans';
		$this->doc->divClass='typo3-shortcut';
		$this->doc->JScode.='
		<script type="text/javascript">
			'.$this->doc->wrapInCData('
			function jump(url,modName,mainModName)	{
				top.nextLoadModuleUrl = url;
				top.goToModule(modName);
			}
			function editSh(uid)	{
				document.location="alt_shortcut.php?editShortcut="+uid;
			}
			function submitEditPage(id)	{
				document.location="alt_shortcut.php?editPage="+top.rawurlencode(id);
			}
			function loadEditId(id)	{
				top.fsMod.recentIds["web"]=id;
				if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)	{
					top.content.nav_frame.refresh_nav();
				}
				top.goToModule("web_layout");
			}
			').'
		</script>
		';
		$this->content.=$this->doc->startPage('Shortcut frame');
	}

	/**
	 * Main function, creating content in the frame
	 */
	function main()	{
		global $BE_USER,$LANG;

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
		$query = 'SELECT * FROM sys_be_shortcuts WHERE ((userid='.$BE_USER->user['uid'].' AND sc_group>=0) OR sc_group IN ('.implode(',',$globalGroups).')) ORDER BY sc_group,sorting';
		$res = mysql(TYPO3_db,$query);

			// Init vars:
		$this->lines=array();
		$this->editSC_rec='';
		$this->selOpt=array();
		$formerGr='';
		
			// Traverse shortcuts
		while($row=mysql_fetch_assoc($res))	{
			if ($this->editSC && $row['uid']==$this->editSC)	{
				$this->editSC_rec=$row;
			}
			
			if (strcmp($formerGr,$row['sc_group']))	{
				if ($row['sc_group']!=-100)	{
					if ($row['sc_group']>=0)	{
						$onC = 'if (confirm('.$GLOBALS['LANG']->JScharCode($LANG->getLL('shortcut_delAllInCat')).')){document.location=\'alt_shortcut.php?deleteCategory='.$row['sc_group'].'\';}return false;';
						$this->lines[]='<td>&nbsp;</td><td bgcolor="'.$this->doc->bgColor5.'"><a href="#" onclick="'.htmlspecialchars($onC).'"'.t3lib_BEfunc::titleAttrib($LANG->getLL('shortcut_delAllInCat'),1).'>'.abs($row['sc_group']).'</a></td>';
					} else {
						$this->lines[]='<td>&nbsp;</td><td bgcolor="'.$this->doc->bgColor5.'">'.abs($row['sc_group']).'</td>';
					}
				}
			}
			
			$mParts = explode('|',$row['module_name']);
			$row['module_name']=$mParts[0];
			$row['M_module_name']=$mParts[1];
			$mParts = explode('_',$row['M_module_name']?$row['M_module_name']:$row['module_name']);
			$qParts = parse_url($row['url']);

			$bgColor = $row['uid']==$this->editSC?' bgcolor="'.$this->doc->bgColor5.'"':($row['sc_group']<0?' bgcolor="'.$this->doc->bgColor6.'"':' bgcolor="'.$this->doc->bgColor4.'"');
			$titleA = t3lib_BEfunc::titleAttrib($this->itemLabel($row['description']&&($row['uid']!=$this->editSC)?$row['description']:t3lib_div::fixed_lgd(rawurldecode($qParts['query']),150),$row['module_name'],$row['M_module_name']),1);
		
			$editSH = ($row['sc_group']>=0 || $BE_USER->isAdmin()) ? 'editSh('.intval($row['uid']).');' : "alert('".$LANG->getLL('shortcut_onlyAdmin')."')";
			$jumpSC = 'jump(unescape(\''.rawurlencode($row['url']).'\'),\''.implode('_',$mParts).'\',\''.$mParts[0].'\');';
			$onC = 'if (document.shForm.editShortcut_check && document.shForm.editShortcut_check.checked){'.$editSH.'}else{'.$jumpSC.'}return false;';
			$this->lines[]='<td'.$bgColor.'><a href="#" onclick="'.htmlspecialchars($onC).'"><img src="'.$this->getIcon($row['module_name']).'" border="0"'.$titleA.' alt="" /></a></td>';
			if (trim($row['description']))	{
				$kkey = strtolower(substr($row['description'],0,20)).'_'.$row['uid'];
				$this->selOpt[$kkey]='<option value="'.htmlspecialchars($jumpSC).'">'.htmlspecialchars(t3lib_div::fixed_lgd($row['description'],50)).'</option>';
			}
			$formerGr=$row['sc_group'];
		}
		ksort($this->selOpt);
		array_unshift($this->selOpt,'<option>['.$LANG->getLL('shortcut_selSC',1).']</option>');

		$this->editLoadedFunc();
		$this->editPageIdFunc();

		if (!$this->editLoaded && t3lib_extMgm::isLoaded('cms'))	{
				$editIdCode = '<td nowrap>'.$LANG->getLL('shortcut_editID').': <input type="text" value="'.($this->editError?htmlspecialchars($this->editPage):'').'" name="editPage"'.$this->doc->formWidth(5).' onchange="submitEditPage(this.value);" />'.
					($this->editError?'&nbsp;<strong>'.$GLOBALS['TBE_TEMPLATE']->rfw($this->editError).'</strong>':'').
					(is_array($this->theEditRec)?'&nbsp;<strong>'.$LANG->getLL('shortcut_loadEdit').' \''.t3lib_BEfunc::getRecordTitle('pages',$this->theEditRec,1).'\'</strong>':'').
					'</td>';
		} else $editIdCode='';
		
		$this->content.='<table border="0" cellpadding="0" cellspacing="2">
			<tr>
			'.implode('',$this->lines).$editIdCode.'
			</tr>
		</table>';
		
		if ($this->theEditRec['uid'])	{
			$this->content.='<script type="text/javascript">loadEditId('.$this->theEditRec['uid'].');</script>';
		}
	}

	/**
	 * Creates lines for the editing form.
	 */
	function editLoadedFunc()	{
		global $BE_USER,$LANG;

		$this->editLoaded=0;
		if (is_array($this->editSC_rec) && ($this->editSC_rec['sc_group']>=0 || $BE_USER->isAdmin()))	{	// sc_group numbers below 0 requires admin to edit those. sc_group numbers above zero must always be owned by the user himself.
			$this->editLoaded=1;
		
			$opt=array();
			$opt[]='<option value="0"></option>';
			for($a=1;$a<=$this->nGroups;$a++)	{
				$opt[]='<option value="'.$a.'"'.(!strcmp($this->editSC_rec['sc_group'],$a)?' selected="selected"':'').'>'.$LANG->getLL('shortcut_group').' '.$a.'</option>';
			}
			if ($BE_USER->isAdmin())	{
				for($a=1;$a<=$this->nGlobals;$a++)	{
					$opt[]='<option value="-'.$a.'"'.(!strcmp($this->editSC_rec['sc_group'],'-'.$a)?' selected="selected"':'').'>'.$LANG->getLL('shortcut_GLOBAL').': '.$a.'</option>';
				}
				$opt[]='<option value="-100"'.(!strcmp($this->editSC_rec['sc_group'],'-100')?' selected="selected"':'').'>'.$LANG->getLL('shortcut_GLOBAL').': '.$LANG->getLL('shortcut_ALL').'</option>';
			}
		
				// border="0" hspace="2" width="21" height="16" - not XHTML compliant in <input type="image" ...>
			$manageForm='<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>&nbsp;&nbsp;</td>
					<td><input type="image" name="_savedok" src="gfx/savedok.gif" '.t3lib_BEfunc::titleAttrib($LANG->getLL('shortcut_save')).' /></td>
					<td><input type="image" name="_saveclosedok" src="gfx/saveandclosedok.gif" '.t3lib_BEfunc::titleAttrib($LANG->getLL('shortcut_saveClose')).' /></td>
					<td><input type="image" name="_closedok" src="gfx/closedok.gif" '.t3lib_BEfunc::titleAttrib($LANG->getLL('shortcut_close')).' /></td>
					<td><input type="image" name="_deletedok" src="gfx/deletedok.gif" '.t3lib_BEfunc::titleAttrib($LANG->getLL('shortcut_delete')).' /></td>
					<td><input name="editName" type="text" value="'.htmlspecialchars($this->editSC_rec['description']).'"'.$this->doc->formWidth(15).' /></td>
					<td><select name="editGroup">'.implode('',$opt).'</select></td>
				</tr>
			</table>
			<input type="hidden" name="whichItem" value="'.$this->editSC_rec['uid'].'" />';
		} else $manageForm='';
//debug(count($opt));
		if (!$this->editLoaded && count($this->selOpt)>1)	{
			$this->lines[]='<td>&nbsp;</td>';
			$this->lines[]='<td><select name="_selSC" onchange="eval(this.options[this.selectedIndex].value);this.selectedIndex=0;">'.implode('',$this->selOpt).'</select></td>';
		}
		if (count($this->lines))	{
			if (!$BE_USER->getTSConfigVal('options.mayNotCreateEditShortcuts'))	{
				$this->lines=array_merge(array('<td><input type="checkbox" name="editShortcut_check" value="1"'.($this->editSC?' checked="checked"':'').' />'.$LANG->getLL('shortcut_edit').'&nbsp;</td>'),$this->lines);
				$this->lines[]='<td>'.$manageForm.'</td>';
			}
			$this->lines[]='<td><img src="clear.gif" width="10" height="1" alt="" /></td>';
		}
	}

	/**
	 * If "editPage" value is sent to script and it points to an accessible page, the internal var $this->theEditRec is set to the page record which should be loaded.
	 * Returns void
	 */
	function editPageIdFunc()	{
		global $BE_USER,$LANG;

		if (!t3lib_extMgm::isLoaded('cms'))	return;
		// EDIT page:
		$this->editPage = trim(strtolower(t3lib_div::GPvar('editPage')));
		$this->editError='';
		$this->theEditRec='';
		if ($this->editPage)	{
			$where=' AND ('.$BE_USER->getPagePermsClause(2).' OR '.$BE_USER->getPagePermsClause(16).')';
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
			} elseif(!$BE_USER->getTSConfigVal('options.shortcut_onEditId_dontSetPageTree')) {
				$expandedPages=unserialize($BE_USER->uc['browsePages']);
				if (!$BE_USER->getTSConfigVal('options.shortcut_onEditId_keepExistingExpanded'))	$expandedPages=array();	
				$rL=t3lib_BEfunc::BEgetRootLine($this->theEditRec['pid']);
				reset($rL);
				while(list(,$rLDat)=each($rL))	{
					$expandedPages[0][$rLDat['uid']]=1;
		//			debug($rLDat['uid']);
				}
				$BE_USER->uc['browsePages'] = serialize($expandedPages);
				$BE_USER->writeUC();
			}
		}
	}

	/**
	 * Output content
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
	

	// ***************************
	// OTHER FUNCTIONS:	
	// ***************************

	/**
	 * Returns relative filename for icon.
	 */
	function mIconFilename($Ifilename,$backPath)	{
		if (t3lib_div::isAbsPath($Ifilename))	{
			$Ifilename = '../'.substr($Ifilename,strlen(PATH_site));
		}
		return $backPath.$Ifilename;
	}

	/**
	 * Returns icon for shortcut display
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