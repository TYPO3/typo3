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
 * Module: Permission setting
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
 *   86: class SC_mod_web_perm_index 
 *  114:     function init()	
 *  139:     function menuConfig()	
 *  170:     function main()	
 *  202:     function checkChange(checknames, varname)	
 *  285:     function printContent()	
 *
 *              SECTION: OTHER FUNCTIONS:
 *  313:     function doEdit()	
 *  449:     function notEdit()	
 *  599:     function printCheckBox($checkName,$num)	
 *  609:     function printPerms($int)	
 *  627:     function groupPerms($row,$firstGroup)	
 *  644:     function getRecursiveSelect($id,$perms_clause)	
 *
 * TOTAL FUNCTIONS: 11
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
include (PATH_typo3.'sysext/lang/locallang_mod_web_perm.php');
require_once (PATH_t3lib.'class.t3lib_pagetree.php');
require_once (PATH_t3lib.'class.t3lib_page.php');

$BE_USER->modAccess($MCONF,1);






/**
 * Script Class for the Web > Access module
 * This module lets you view and change permissions for pages.
 * 
 * variables:
 * $this->depth 	: 	intval 1-3: decides the depth of the list
 * $this->mode		:	'perms' / '': decides if we view a user-overview or the permissions.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_mod_web_perm_index {

		// Internal, dynamic:
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();
	var $doc;	

	var $content;
	
	var $mode;
	var $depth;
	var $edit;
	var $return_id;
	var $lastEdited;
	var $perms_clause;
	var $pageinfo;
	var $color;
	var $color2;
	var $color3;
	var $editingAllowed;
	var $id;

	/**
	 * Initialization
	 * 
	 * @return	void		
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		$this->MCONF = $GLOBALS['MCONF'];

		$this->id = intval(t3lib_div::GPvar('id'));
		$this->mode = t3lib_div::GPvar('mode');
		$this->depth = t3lib_div::GPvar('depth');
		$this->edit = t3lib_div::GPvar('edit');
		$this->return_id = t3lib_div::GPvar('return_id');
		$this->lastEdited = t3lib_div::GPvar('lastEdited');
		
		
		// **************************
		// Functions and classes 
		// **************************
		$this->perms_clause = $BE_USER->getPagePermsClause(1);
		
		$this->menuConfig();
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	void		
	 */
	function menuConfig()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved. 
			// Values NOT in this array will not be saved in the settings-array for the module.
		$temp = $LANG->getLL('levels');
		$this->MOD_MENU = array(
			'depth' => array(
				1 => '1 '.$temp,
				2 => '2 '.$temp,
				3 => '3 '.$temp,
				4 => '4 '.$temp,
				10 => '10 '.$temp
			),
			'mode' => array(
				0 => $LANG->getLL('user_overview'),
				'perms' => $LANG->getLL('permissions')
			)
		);
		
			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::GPvar("SET"), $this->MCONF["name"]);
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	void		
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		
		if (($this->id && $access) || ($BE_USER->user["admin"] && !$this->id))	{
			if ($BE_USER->user["admin"] && !$this->id)	{
				$this->pageinfo=array("title" => "[root-level]","uid"=>0,"pid"=>0);
			}
		
				// This decides if the editform is drawn
			$this->editingAllowed = ($this->pageinfo["perms_userid"]==$BE_USER->user["uid"] || $BE_USER->isAdmin()); 
			$this->edit = $this->edit && $this->editingAllowed;	
		
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
		
				// Define some colors for the tables
			$this->color=' bgColor="'.t3lib_div::modifyHTMLColor($this->doc->bgColor,-20,-20,-20).'"';
			$this->color2=' bgColor="'.$this->doc->bgColor2.'"';
			$this->color3=' bgColor="'.$this->doc->bgColor2.'"';
		
		
				// The formtag
			$this->doc->form='<form action="'.$BACK_PATH.'tce_db.php" method="post" name="editform">';
				// JavaScript
			$this->doc->JScode = "
		<script language='JavaScript' SRC='".$BACK_PATH."t3lib/jsfunc.updateform.js'></script>
		<script language=\"javascript\" type=\"text/javascript\">
			function checkChange(checknames, varname)	{
				var res = 0;
				for (var a=1; a<=5; a++)	{
					if (document.editform[checknames+'['+a+']'].checked)	{
						res|=Math.pow(2,a-1);
					}
				}
				document.editform[varname].value = res | (checknames=='check[perms_user]'?1:0) ;
				setCheck (checknames,varname);
			}

			function setCheck(checknames, varname)	{ 	//
				if (document.editform[varname])	{
					var res = document.editform[varname].value;
					for (var a=1; a<=5; a++)	{
						document.editform[checknames+'['+a+']'].checked = (res & Math.pow(2,a-1));
					}
				}
			}

			function jumpToUrl(URL)	{	//
				document.location = URL;
			}
			
		</script>	
				";
			
		
		
		
				// If $this->edit then these functions are called in the end of the page...
			if ($this->edit)	{
				$this->doc->postCode= "
		<script language=\"javascript\" type=\"text/javascript\">
			setCheck('check[perms_user]','data[pages][".$this->id."][perms_user]');
			setCheck('check[perms_group]','data[pages][".$this->id."][perms_group]');
			setCheck('check[perms_everybody]','data[pages][".$this->id."][perms_everybody]');
		</script>
				";
			}
		
				// Draw the header.
			$this->content.=$this->doc->startPage($LANG->getLL("permissions"));
			$this->content.=$this->doc->header($LANG->getLL("permissions").($this->edit?": &nbsp;&nbsp;&nbsp;".$LANG->getLL("Edit"):""));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('',
				$this->doc->funcMenu(
					$this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"]).'<br>'.$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.path").': '.t3lib_div::fixed_lgd_pre($this->pageinfo["_thePath"],50),
					t3lib_BEfunc::getFuncMenu($this->id,"SET[mode]",$this->MOD_SETTINGS["mode"],$this->MOD_MENU["mode"])
				));
			$this->content.=$this->doc->divider(5);

			
				// MAIN FUNCTION:
			if (!$this->edit)	{
				$this->notEdit();
			} else {
				$this->doEdit();
			}
		
			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon("id,edit,return_id",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"]));
			}
		
			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
		
			$this->content.=$this->doc->startPage($LANG->getLL("permissions"));
			$this->content.=$this->doc->header($LANG->getLL("permissions"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Printing content
	 * 
	 * @return	void		
	 */
	function printContent()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->content.=$this->doc->middle();
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
	









	/*****************************
	 *
	 * OTHER FUNCTIONS:	
	 *
	 *****************************/

	/**
	 * Editing the permissions	($this->edit = true)
	 * 
	 * @return	[type]		...
	 */
	function doEdit()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

			// Get usernames and groupnames
		$be_group_Array=t3lib_BEfunc::getListGroupNames("title,uid");
		$groupArray=array_keys($be_group_Array);

//		
		$be_user_Array = t3lib_BEfunc::getUserNames();
		if (!$GLOBALS["BE_USER"]->isAdmin())		$be_user_Array = t3lib_BEfunc::blindUserNames($be_user_Array,$groupArray,1);
		$be_group_Array_o = $be_group_Array = t3lib_BEfunc::getGroupNames();
		if (!$GLOBALS["BE_USER"]->isAdmin())		$be_group_Array = t3lib_BEfunc::blindGroupNames($be_group_Array_o,$groupArray,1);
		$firstGroup = $groupArray[0] ? $be_group_Array[$groupArray[0]] : "";	// data of the first group, the user is member of
	
	

	// Owner selector:
		$options="";
		$userset=0;	// flag: is set if the page-userid equals one from the user-list
		reset($be_user_Array);
		while (list($uid,$row)=each($be_user_Array))	{
			if ($uid==$this->pageinfo["perms_userid"])	{
				$userset = 1;
				$selected=" selected";
			} else {$selected="";}
			$options.='<option value="'.$uid.'"'.$selected.'>'.$row["username"];
		}
		$options='<option value="0">'.$options;
		$selector='<select name="data[pages]['.$this->id.'][perms_userid]">'.$options.'</select>';

		$this->content.=$this->doc->section($LANG->getLL("Owner").":",$selector);
		

	// Group selector:
		$options="";
		$userset=0;
		reset($be_group_Array);
		while (list($uid,$row)=each($be_group_Array))	{
			if ($uid==$this->pageinfo["perms_groupid"])	{
				$userset = 1;
				$selected=" selected";
			} else {$selected="";}
			$options.='<option value="'.$uid.'"'.$selected.'>'.$row["title"];
		}
		if (!$userset && $this->pageinfo["perms_groupid"])	{	// If the group was not set AND there is a group for the page
			$options='<option value="'.$this->pageinfo["perms_groupid"].'" selected>'.$be_group_Array_o[$this->pageinfo["perms_groupid"]]["title"].$options;
		}
		$options='<option value="0">'.$options;
		$selector='<select name="data[pages]['.$this->id.'][perms_groupid]">'.$options.'</select>';

		$this->content.=$this->doc->divider(5);
		$this->content.=$this->doc->section($LANG->getLL("Group").":",$selector);

	

		
		
		
		

	// Permissions:

		$w=19;

		$code='
		<table border=0 cellspacing=2 cellpadding=0>
			<tr>
				<td></td>
				<td width=50 align="center" nowrap'.$this->color3.'><b>'.str_replace(" ","<BR>",$LANG->getLL("1")).'</b></td>
				<td width=50 align="center" nowrap'.$this->color3.'><b>'.str_replace(" ","<BR>",$LANG->getLL("16")).'</b></td>
				<td width=50 align="center" nowrap'.$this->color3.'><b>'.str_replace(" ","<BR>",$LANG->getLL("2")).'</b></td>
				<td width=50 align="center" nowrap'.$this->color3.'><b>'.str_replace(" ","<BR>",$LANG->getLL("4")).'</b></td>
				<td width=50 align="center" nowrap'.$this->color3.'><b>'.str_replace(" ","<BR>",$LANG->getLL("8")).'</b></td>
			</tr>
			<tr>
				<td align="right"'.$this->color2.'><b>&nbsp;'.$LANG->getLL("Owner").'&nbsp;</b></td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_user',1).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_user',5).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_user',2).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_user',3).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_user',4).'</td>
			</tr>
			<tr>
				<td align="right"'.$this->color2.'><b>&nbsp;'.$LANG->getLL("Group").'&nbsp;</b></td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_group',1).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_group',5).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_group',2).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_group',3).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_group',4).'</td>
			</tr>
			<tr>
				<td align="right"'.$this->color2.'><b>&nbsp;'.$LANG->getLL("Everybody").'&nbsp;</b></td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_everybody',1).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_everybody',5).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_everybody',2).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_everybody',3).'</td>
				<td align="center"'.$this->color.'>'.$this->printCheckBox('perms_everybody',4).'</td>
			</tr>






		</table>
<br>

			<input type="Hidden" name="data[pages]['.$this->id.'][perms_user]" value="'.$this->pageinfo["perms_user"].'">
			<input type="Hidden" name="data[pages]['.$this->id.'][perms_group]" value="'.$this->pageinfo["perms_group"].'">
			<input type="Hidden" name="data[pages]['.$this->id.'][perms_everybody]" value="'.$this->pageinfo["perms_everybody"].'">
			'.$this->getRecursiveSelect($this->id,$this->perms_clause).'
			<input type="Submit" name="submit" value="'.$LANG->getLL("Save").'">&nbsp;&nbsp;<input type="Submit" value="'.$LANG->getLL("Abort").'" onClick="jumpToUrl(\'index.php?id='.$this->id.'\'); return false;">
			<input type="Hidden" name="redirect" value="'.TYPO3_MOD_PATH.'index.php?mode='.$this->MOD_SETTINGS["mode"].'&depth='.$this->MOD_SETTINGS["depth"].'&id='.intval($this->return_id).'&lastEdited='.$this->id.'">
	';

		$this->content.=$this->doc->divider(5);
		$this->content.=$this->doc->section($LANG->getLL("permissions").":",$code);

		if ($BE_USER->uc["helpText"])	{
			$this->content.=$this->doc->divider(20);
			$legendText = "<b>".$LANG->getLL("1")."</b>: ".$LANG->getLL("1_t");
			$legendText.= "<BR><b>".$LANG->getLL("16")."</b>: ".$LANG->getLL("16_t");
			$legendText.= "<BR><b>".$LANG->getLL("2")."</b>: ".$LANG->getLL("2_t");
			$legendText.= "<BR><b>".$LANG->getLL("4")."</b>: ".$LANG->getLL("4_t");
			$legendText.= "<BR><b>".$LANG->getLL("8")."</b>: ".$LANG->getLL("8_t");
			
			$code=$legendText.'<BR><BR>'.$LANG->getLL("def");
			$this->content.=$this->doc->section($LANG->getLL("Legend").':',$code);
		}
	}
	
	/**
	 * Showing the permissions  ($this->edit = false)
	 * 
	 * @return	[type]		...
	 */
	function notEdit()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		$code.=$LANG->getLL("Depth").': ';
		$code.=t3lib_BEfunc::getFuncMenu($this->id,"SET[depth]",$this->MOD_SETTINGS["depth"],$this->MOD_MENU["depth"]);
		$code.='</NOBR>';

		$this->content.=$this->doc->section('',$code);
	



			// Get usernames and groupnames: The arrays we get in return contains only 1) users which are members of the groups of the current user, 2) groups that the current user is member of
		$groupArray = $BE_USER->userGroupsUID;
		$be_user_Array = t3lib_BEfunc::getUserNames();
		if (!$GLOBALS["BE_USER"]->isAdmin())		$be_user_Array = t3lib_BEfunc::blindUserNames($be_user_Array,$groupArray,0);
		$be_group_Array = t3lib_BEfunc::getGroupNames();
		if (!$GLOBALS["BE_USER"]->isAdmin())		$be_group_Array = t3lib_BEfunc::blindGroupNames($be_group_Array,$groupArray,0);

		
		$tLen= ($this->MOD_SETTINGS["mode"]=="perms" ? 20 : 30); 
		$this->content.=$this->doc->spacer(5);



			// Drawing tree:
		$tree = t3lib_div::makeInstance("t3lib_pageTree");
		$tree->init("AND ".$this->perms_clause);
		
		$tree->addField("perms_user",1);
		$tree->addField("perms_group",1);
		$tree->addField("perms_everybody",1);
		$tree->addField("perms_userid",1);
		$tree->addField("perms_groupid",1);
		$tree->addField("hidden");
		$tree->addField("fe_group");
		$tree->addField("starttime");
		$tree->addField("endtime");
	
		$HTML='<IMG src="'.$BACK_PATH.t3lib_iconWorks::getIcon("pages",$this->pageinfo).'" width="18" height="16" align="top">';
		$tree->tree[]=Array("row"=>$this->pageinfo,"HTML"=>$HTML);
		
		$tree->getTree($this->id,$this->MOD_SETTINGS["depth"],"");
		
		// 
		$code='';
		$code.='<table border=0 cellspacing=0 cellpadding=0>';



		if ($this->MOD_SETTINGS["mode"]=="perms")	{
			$code.='
				<tr>
				<td'.$this->color2.'colspan=2>&nbsp;</td>
				<td'.$this->color2.'><img src="'.$BACK_PATH.'gfx/line.gif" width=5 height=16 hspace=2></td>
				<td align="center"'.$this->color2.'><b>'.$LANG->getLL("Owner").'</b></td>
				<td'.$this->color2.'><img src="'.$BACK_PATH.'gfx/line.gif" width=5 height=16 hspace=2></td>
				<td align="center"'.$this->color2.'><b>'.$LANG->getLL("Group").'</b></td>
				<td'.$this->color2.'><img src="'.$BACK_PATH.'gfx/line.gif" width=5 height=16 hspace=2></td>
				<td align="center"'.$this->color2.'><b>'.$LANG->getLL("Everybody").'</b></td>
				</tr>
			';
		} else {
			$code.='
				<tr>
				<td'.$this->color2.'colspan=2>&nbsp;</td>
				<td'.$this->color2.'><img src="'.$BACK_PATH.'gfx/line.gif" width=5 height=16 hspace=2></td>
				<td align="center"'.$this->color2.' nowrap><b>'.$LANG->getLL("User").':</b> '.$BE_USER->user["username"].'</td>';
			if ($firstGroup)	{
				$code.='
					<td'.$this->color2.'><img src="'.$BACK_PATH.'gfx/line.gif" width=5 height=16 hspace=2></td>
					<td align="center"'.$this->color2.' nowrap><b>'.$LANG->getLL("Group").':</b> '.$firstGroup["title"].'&nbsp;</td>';
			}
			$code.='
				</tr>
			';
		}
		$temp = $LANG->getLL("ch_permissions");
		reset($tree->tree);
		while(list(,$data)=each($tree->tree))	{
			if ($this->lastEdited==$data["row"]["uid"])	{$bgCol = $this->color;} else {$bgCol = "";}
			$lE_bgCol = $bgCol;
			$userN = $be_user_Array[$data["row"]["perms_userid"]] ? $be_user_Array[$data["row"]["perms_userid"]]["username"] : ($data["row"]["perms_userid"] ? "<i>[".$data["row"]["perms_userid"]."]!</i>" : "");
			$groupN = $be_group_Array[$data["row"]["perms_groupid"]] ? $be_group_Array[$data["row"]["perms_groupid"]]["title"]  : ($data["row"]["perms_groupid"] ? "<i>[".$data["row"]["perms_groupid"]."]!</i>" : "");
			$groupN=t3lib_div::fixed_lgd($groupN,20);
			$editPermsAllowed=($data["row"]["perms_userid"]==$BE_USER->user["uid"] || $BE_USER->isAdmin());
			
			$code.='<tr>
 				<td align="left" nowrap'.$bgCol.'>'.$data["HTML"].t3lib_div::fixed_lgd($data["row"]["title"],$tLen).'&nbsp;</td>';
				
			if ($editPermsAllowed && $data["row"]["uid"])	{
				$code.='<td'.$bgCol.'><a href="index.php?mode='.$this->MOD_SETTINGS["mode"].'&depth='.$this->MOD_SETTINGS["depth"].'&id='.$data["row"]["uid"].'&return_id='.$this->id.'&edit=1"><img src="'.$BACK_PATH.'gfx/edit2.gif" width=11 height=12 border=0 title="'.$temp.'" align="top"></A></td>';
			} else {
				$code.='<td'.$bgCol.'></td>';
			}


			if ($this->MOD_SETTINGS["mode"]=="perms")	{
				$code.='				
					<td'.$bgCol.'><img src="'.$BACK_PATH.'gfx/line.gif" width=5 height=16 hspace=2></td>
					<td nowrap'.$bgCol.'>'.($data["row"]["uid"]?$this->printPerms($data["row"]["perms_user"])." ".$userN:"").'</td>
	
					<td'.$bgCol.'><img src="'.$BACK_PATH.'gfx/line.gif" width=5 height=16 hspace=2></td>
					<td nowrap'.$bgCol.'>'.($data["row"]["uid"]?$this->printPerms($data["row"]["perms_group"])." ".$groupN:"").'</td>
	
					<td'.$bgCol.'><img src="'.$BACK_PATH.'gfx/line.gif" width=5 height=16 hspace=2></td>
					<td nowrap'.$bgCol.'>'.($data["row"]["uid"]?" ".$this->printPerms($data["row"]["perms_everybody"]):"").'</td>
				';
			} else {
				$code.='<td'.$bgCol.'><img src="'.$BACK_PATH.'gfx/line.gif" width=5 height=16 hspace=2></td>';
				if ($BE_USER->user["uid"]==$data["row"]["perms_userid"])	{$bgCol = $this->color;} else {$bgCol = $lE_bgCol;}
				$code.='<td align="center" nowrap'.$bgCol.'>'.($data["row"]["uid"]?$owner.$this->printPerms($BE_USER->calcPerms($data["row"])):"").'</td>';
				$bgCol = $lE_bgCol;

				if ($firstGroup)	{
					$code.='<td'.$bgCol.'><img src="'.$BACK_PATH.'gfx/line.gif" width=5 height=16 hspace=2></td>';
					if ($firstGroup["uid"]==$data["row"]["perms_groupid"])	{$bgCol = $this->color;} else {$bgCol = $lE_bgCol;}
					$code.='<td align="center" nowrap'.$bgCol.'>'.($data["row"]["uid"]?$this->printPerms($this->groupPerms($data["row"],$firstGroup)):"").'</td>';
				}
			}
			$code.='
				</tr>
			';
		}
		$code.='</table>';	
		$this->content.=$this->doc->section('',$code);
		
		if ($BE_USER->uc["helpText"])	{
			$legendText = "<b>".$LANG->getLL("1")."</b>: ".$LANG->getLL("1_t");
			$legendText.= "<BR><b>".$LANG->getLL("16")."</b>: ".$LANG->getLL("16_t");
			$legendText.= "<BR><b>".$LANG->getLL("2")."</b>: ".$LANG->getLL("2_t");
			$legendText.= "<BR><b>".$LANG->getLL("4")."</b>: ".$LANG->getLL("4_t");
			$legendText.= "<BR><b>".$LANG->getLL("8")."</b>: ".$LANG->getLL("8_t");
			
			$code='<table border=0><tr><td valign="top"><img src="legend.gif" width=86 height=75></td><td valign="top" nowrap>'.$legendText.'</td></tr></table>';
			$code.='<BR>'.$LANG->getLL("def");
			$code.='<BR><BR><font color="green"><b>*</b></font>: '.$LANG->getLL("A_Granted");
			$code.='<BR><font color="red"><b>x</b></font>: '.$LANG->getLL("A_Denied");

			$this->content.=$this->doc->spacer(20);
			$this->content.=$this->doc->section($LANG->getLL("Legend").':',$code,0,1);
		}
	}

	/**
	 * Print a checkbox.
	 * 
	 * @param	[type]		$checkName: ...
	 * @param	[type]		$num: ...
	 * @return	string		HTML checkbox
	 */
	function printCheckBox($checkName,$num)	{
		return '<input type="Checkbox" name="check['.$checkName.']['.$num.']" onClick="checkChange(\'check['.$checkName.']\', \'data[pages]['.$GLOBALS["SOBE"]->id.']['.$checkName.']\')"><BR>';
	}

	/**
	 * Print a set of permissions
	 * 
	 * @param	[type]		$int: ...
	 * @return	[type]		...
	 */
	function printPerms($int)	{
		$str="";
		$str.= (($int&1)?'*':'<font color="red">x</font>');
		$str.= (($int&16)?'*':'<font color="red">x</font>');
		$str.= (($int&2)?'*':'<font color="red">x</font>');
		$str.= (($int&4)?'*':'<font color="red">x</font>');
		$str.= (($int&8)?'*':'<font color="red">x</font>');
		
		return '<B><font color="green">'.$str.'</font></b>';
	}

	/**
	 * Returns the permissions for a group based of the perms_groupid of $row. If the $row[perms_groupid] equals the $firstGroup[uid] then the function returns perms_everybody OR'ed with perms_group, else just perms_everybody
	 * 
	 * @param	[type]		$row: ...
	 * @param	[type]		$firstGroup: ...
	 * @return	[type]		...
	 */
	function groupPerms($row,$firstGroup)	{
		if (is_array($row))	{
			$out=intval($row["perms_everybody"]);
			if ($row["perms_groupid"] && $firstGroup["uid"]==$row["perms_groupid"])	{
				$out|= intval($row["perms_group"]);
			}
			return $out;
		}
	}

	/**
	 * Finding tree and offer setting of values recursively.
	 * 
	 * @param	[type]		$id: ...
	 * @param	[type]		$perms_clause: ...
	 * @return	[type]		...
	 */
	function getRecursiveSelect($id,$perms_clause)	{
		$tree = t3lib_div::makeInstance('t3lib_pageTree');
		$tree->init('AND '.$perms_clause);
		$tree->addField('perms_userid',1);
		$tree->makeHTML=0;
		$tree->setRecs = 1;
		$getLevels=3;
		$tree->getTree($id,$getLevels,'');
	
		if ($GLOBALS['BE_USER']->user['uid'] && count($tree->ids_hierarchy))	{
			reset($tree->ids_hierarchy);
			$label_recur = $GLOBALS['LANG']->getLL('recursive');
			$label_levels = $GLOBALS['LANG']->getLL('levels');
			$label_pA = $GLOBALS['LANG']->getLL('pages_affected');
			$theIdListArr=array();
			$opts='<option value=""></option>';
			for ($a=$getLevels;$a>0;$a--)	{
				if (is_array($tree->ids_hierarchy[$a]))	{
					reset($tree->ids_hierarchy[$a]);
					while(list(,$theId)=each($tree->ids_hierarchy[$a]))	{
						if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->user['uid']==$tree->recs[$theId]['perms_userid'])	{
							$theIdListArr[]=$theId;
						}
					}
					$lKey = $getLevels-$a+1;
					$opts.='<option value="'.htmlspecialchars(implode(',',$theIdListArr)).'">'.t3lib_div::deHSCentities(htmlspecialchars($label_recur.' '.$lKey.' '.$label_levels)).' ('.count($theIdListArr).' '.$label_pA.')</option>';
				}
			}
			$theRecursiveSelect = '<br /><select name="mirror[pages]['.$id.']">'.$opts.'</select><br /><br />';
		} else {
			$theRecursiveSelect = '';
		}		
		return $theRecursiveSelect;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/web/perm/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/web/perm/index.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_web_perm_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

if ($TYPO3_CONF_VARS['BE']['compressionLevel'])	{
	new gzip_encode($TYPO3_CONF_VARS['BE']['compressionLevel']);
}
?>