<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Module class for task module
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

require_once(PATH_t3lib."class.t3lib_extobjbase.php");

class mod_user_task extends t3lib_extobjbase {
	var $getUserNamesFields = "username,usergroup,usergroup_cached_list,uid,realName,email";
	var $userGroupArray=array();
	var $perms_clause="";

	var $backPath;
	var $BE_USER;

	function JScode()	{

	}
	function sendEmail($email,$subject,$message)	{
		$sender = $this->BE_USER->user["realName"]." <".$this->BE_USER->user["email"].">";
		$message.='

--------
'.sprintf($GLOBALS["LANG"]->getLL("messages_emailFooter"),$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["sitename"],t3lib_div::getIndpEnv("HTTP_HOST"));
		mail($email,$GLOBALS["TYPO3_CONF_VARS"]["BE"]["notificationPrefix"]." ".$subject,$message,"From: ".$sender);

//		debug($email);
//		debug($message);
	}
	function mod_user_task_init($BE_USER)	{
		$this->BE_USER = $BE_USER;
		$this->perms_clause = $this->BE_USER->getPagePermsClause(1);
	}
	function helpBubble()	{
		return '<img src="'.$this->backPath.'gfx/helpbubble.gif" width="14" height="14" hspace=2 align=top'.$GLOBALS["SOBE"]->doc->helpStyle().'>';
	}
	function loadLeftFrameJS()	{
		$str = '<script language="javascript" type="text/javascript">if (parent.nav_frame)	parent.nav_frame.document.location="overview.php";</script>';
		return $str;
	}
	function headLink($key,$dontLink=0,$params="")	{
		$str = $GLOBALS["SOBE"]->MOD_MENU["function"][$key];
		if (!$dontLink)	$str = '<a href="index.php?SET[function]='.$key.$params.'" target="list_frame" onClick="this.blur();">'.htmlspecialchars($str).'</a>';
		return $str;
	}
	function fixed_lgd($str,$len=0)	{
		return t3lib_div::fixed_lgd($str,$len?$len:$this->BE_USER->uc["titleLen"]);
	}
	function errorIcon()	{
		return '<img src="'.$this->backPath.'gfx/icon_fatalerror.gif" width="18" height="16" align=top>';
	}
	function getUserAndGroupArrays()	{
			// Get groupnames for todo-tasks
		$be_group_Array=t3lib_BEfunc::getListGroupNames("title,uid");
		$groupArray=array_keys($be_group_Array);
			// Usernames
		$be_user_Array = $be_user_Array_o = t3lib_BEfunc::getUserNames($this->getUserNamesFields);
		if (!$GLOBALS["BE_USER"]->isAdmin())		$be_user_Array = t3lib_BEfunc::blindUserNames($be_user_Array,$groupArray,1);

		$this->userGroupArray = array($be_user_Array,$be_group_Array,$be_user_Array_o);
		return $this->userGroupArray;
	}
	function dateTimeAge($tstamp,$prefix=1)	{
		return t3lib_BEfunc::dateTimeAge($tstamp,$prefix);
	}
	function accessMod($mod)	{
		return $this->BE_USER->modAccess(array("name"=>$mod,"access"=>"user,group"),0);
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/taskcenter/task/class.mod_user_task.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/taskcenter/task/class.mod_user_task.php"]);
}


?>