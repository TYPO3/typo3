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
 * Module: Log-viewing
 *
 * This module lets you view the changelog.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
$LANG->includeLLFile("EXT:belog/mod/locallang.php");
require_once (PATH_t3lib."class.t3lib_bedisplaylog.php");
require_once (PATH_t3lib."class.t3lib_pagetree.php");

$BE_USER->modAccess($MCONF,1);




// ***************************
// Script Classes
// ***************************
class SC_mod_tools_log_index {
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();
	var $doc;

	var $content;
	var $lF;
	var $be_user_Array;

	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		$this->MCONF = $GLOBALS["MCONF"];

		$this->lF = t3lib_div::makeInstance("t3lib_BEDisplayLog");
		$this->menuConfig();

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
		$this->doc->tableLayout = Array (
			"0" => Array (
				"0" => Array('<TD valign="top"><b>','</b></td>'),
				"defCol" => Array('<TD><img src="'.$this->doc->backPath.'clear.gif" width=10 height=1></td><td valign="top"><b>','</b></td>')
			),
			"defRow" => Array (
				"0" => Array('<TD valign="top">','</td>'),
				"defCol" => Array('<TD><img src="'.$this->doc->backPath.'clear.gif" width=10 height=1></td><td valign="top">','</td>')
			)
		);
		$this->doc->form = '<form action="" method="post">';

		$this->be_user_Array = t3lib_BEfunc::getUserNames();
		$this->lF->be_user_Array = &$this->be_user_Array;
	}
	function menuConfig()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			"users" => array(
				0 => "All users",
				"-1" => "Self"
			),
			"time" => array(
				0 => "This week",
				1 => "Last week",
				2 => "Last 7 days",
				10 => "This month",
				11 => "Last month",
				12 => "Last 31 days",
				20 => "No limit"
			),
			"max" => array(
				20 => "20",
				50 => "50",
				100 => "100",
				200 => "200",
				500 => "500"
			),
			"action" => array(
				0 => "All",
				1 => "Database",
				2 => "File",
				254 => "Settings",
				255 => "Login",
				"-1" => "Errors"
			)
		);

			// Adding groups to the users_array
		$groups = t3lib_BEfunc::getGroupNames();
		if (is_array($groups))	{
			while(list(,$grVals)=each($groups))	{
				$this->MOD_MENU["users"][$grVals["uid"]] = "Group: ".$grVals["title"];
			}
		}

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP("SET"), $this->MCONF["name"]);
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->content.=$this->doc->startPage("Administration log");
		$this->content.=$this->doc->header("Administration log");
		$this->content.=$this->doc->spacer(5);


		// Example of a menu!
			$menuU= t3lib_BEfunc::getFuncMenu(0,"SET[users]",$this->MOD_SETTINGS["users"],$this->MOD_MENU["users"]);
			$menuM= t3lib_BEfunc::getFuncMenu(0,"SET[max]",$this->MOD_SETTINGS["max"],$this->MOD_MENU["max"]);
			$menuT= t3lib_BEfunc::getFuncMenu(0,"SET[time]",$this->MOD_SETTINGS["time"],$this->MOD_MENU["time"]);
			$menuA= t3lib_BEfunc::getFuncMenu(0,"SET[action]",$this->MOD_SETTINGS["action"],$this->MOD_MENU["action"]);


			$this->content.=$this->doc->section("",$this->doc->menuTable(
				array(
					array(fw("Users:"),$menuU),
					array(fw("Time:"),$menuT)
				),
				array(
					array(fw("Max:"),$menuM),
					array(fw("Action:"),$menuA)
				)
			));
			$this->content.=$this->doc->divider(5);


		$codeArr = $this->lF->initArray();
		$oldHeader="";
		$c=0;

		// Action (type):
		$where_part="";
		if ($this->MOD_SETTINGS["action"] > 0)	{
			$where_part.=" AND type=".intval($this->MOD_SETTINGS["action"]);
		} elseif ($this->MOD_SETTINGS["action"] == -1)	{
			$where_part.=" AND error";
		}


		$starttime=0;
		$endtime=time();

		// Time:
		switch($this->MOD_SETTINGS["time"])		{
			case 0:
				// This week
				$week = (date("w") ? date("w") : 7)-1;
				$starttime = mktime (0,0,0)-$week*3600*24;
			break;
			case 1:
				// Last week
				$week = (date("w") ? date("w") : 7)-1;
				$starttime = mktime (0,0,0)-($week+7)*3600*24;
				$endtime = mktime (0,0,0)-$week*3600*24;
			break;
			case 2:
				// Last 7 days
				$starttime = mktime (0,0,0)-7*3600*24;
			break;
			case 10:
				// This month
				$starttime = mktime (0,0,0, date("m"),1);
			break;
			case 11:
				// Last month
				$starttime = mktime (0,0,0, date("m")-1,1);
				$endtime = mktime (0,0,0, date("m"),1);
			break;
			case 12:
				// Last 31 days
				$starttime = mktime (0,0,0)-31*3600*24;
			break;
		}
		if ($starttime)	{
			$where_part.=" AND tstamp>=".$starttime." AND tstamp<".$endtime;
		}


			// Users
		if ($this->MOD_SETTINGS["users"] > 0)	{	// All users
			$this->be_user_Array = t3lib_BEfunc::blindUserNames($this->be_user_Array,array($this->MOD_SETTINGS["users"]),1);
			if (is_array($this->be_user_Array))	{
				while(list(,$val)=each($this->be_user_Array))	{
					if ($val[uid]!=$BE_USER->user[uid])	{
						$selectUsers[]=$val[uid];
					}
				}
			}
			$selectUsers[] = 0;
			$where_part.=" AND userid in (".implode($selectUsers,",").")";
		} elseif ($this->MOD_SETTINGS["users"]==-1) {
			$where_part.=" AND userid=".$BE_USER->user[uid];	// Self user
		}




		$log = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_log', '1=1'.$where_part, '', 'uid DESC', intval($this->MOD_SETTINGS['max']));

		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($log))	{
			$header=$this->doc->formatTime($row[tstamp],10);
			if (!$oldHeader)	$oldHeader=$header;

			if ($header!=$oldHeader)	{
				$this->content.=$this->doc->spacer(10);
				$this->content.=$this->doc->section($oldHeader,$this->doc->table($codeArr));
				$codeArr=$this->lF->initArray();
				$oldHeader=$header;
				$this->lF->reset();
			}

			$i++;
			$codeArr[$i][]=$this->lF->getTimeLabel($row[tstamp]);
			$codeArr[$i][]=$this->lF->getUserLabel($row[userid]);
			$codeArr[$i][]=$this->lF->getTypeLabel($row[type]);
			$codeArr[$i][]=$row[error] ? $this->lF->getErrorFormatting($this->lF->errorSign[$row[error]]) : "";
			$codeArr[$i][]=$this->lF->getActionLabel($row[type]."_".$row[action]);
			$codeArr[$i][]=$this->lF->formatDetailsForList($row);
		}
		$this->content.=$this->doc->spacer(10);
		$this->content.=$this->doc->section($header,$this->doc->table($codeArr));

		$GLOBALS['TYPO3_DB']->sql_free_result($log);

		if ($BE_USER->mayMakeShortcut())	{
			$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon("","users,time,max,action",$this->MCONF["name"]));
		}

	}
	function printContent()	{

		$this->content.=$this->doc->spacer(20);
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/belog/mod/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/belog/mod/index.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_mod_tools_log_index");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>