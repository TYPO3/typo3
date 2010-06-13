<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Extension classes for log display in Web > Info and Tools > Log modules
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   65: class logFunctions_ext extends t3lib_BEDisplayLog
 *   72:     function initArray()
 *
 *
 *   97: class tx_belog_webinfo extends t3lib_extobjbase
 *  105:     function modMenu()
 *  136:     function localLang()
 *  147:     function main()
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



/**
 * Extending for Tools > Log. Just setting labels correctly
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_belog
 */
class logFunctions_ext extends t3lib_BEDisplayLog {

	/**
	 * Initialize the log table array with header labels.
	 *
	 * @return	array
	 */
	function initArray()	{
		global $LANG;
		$codeArr=Array();
		$codeArr[$i][]=$LANG->getLL('chLog_l_time');
		$codeArr[$i][]=$LANG->getLL('chLog_l_user');
		$codeArr[$i][]=$LANG->getLL('chLog_l_error');
		$codeArr[$i][]=$LANG->getLL('chLog_l_action');
		$codeArr[$i][]=$LANG->getLL('chLog_l_table');
		$codeArr[$i][]=$LANG->getLL('chLog_l_details');
		return $codeArr;
	}
}




/**
 * Extending for Web>Info
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_belog
 */
class tx_belog_webinfo extends t3lib_extobjbase {
	var $localLangFile = 'locallang.php';

	/**
	 * Setting up function menu
	 *
	 * @return	array		Menu items
	 */
	function modMenu()	{
		global $LANG;

		return array(
			'log_users' => array(
				0 => $LANG->getLL('chLog_users_0'),
				'-1' => $LANG->getLL('chLog_users_-1')
			),
			'log_time' => array(
				0 => $LANG->getLL('chLog_time_0'),
				1 => $LANG->getLL('chLog_time_1'),
				2 => $LANG->getLL('chLog_time_2'),
				10 => $LANG->getLL('chLog_time_10'),
				11 => $LANG->getLL('chLog_time_11'),
				12 => $LANG->getLL('chLog_time_12'),
				20 => $LANG->getLL('chLog_time_20')
			),
			'depth' => array(
				0 => $LANG->getLL('depth_0'),
				1 => $LANG->getLL('depth_1'),
				2 => $LANG->getLL('depth_2'),
				3 => $LANG->getLL('depth_3')
			)
		);
	}

	/**
	 * Include locallang file
	 *
	 * @return	void
	 */
	function localLang()	{
		$LOCAL_LANG = $GLOBALS['LANG']->includeLLFile('EXT:belog/mod/locallang.xml',FALSE);

		$GLOBALS['LOCAL_LANG']=t3lib_div::array_merge_recursive_overrule($GLOBALS['LOCAL_LANG'],$LOCAL_LANG);
	}

	/**
	 * Show the log entries for page
	 *
	 * @return	string		HTML output
	 */
	function main()	{
		global $SOBE,$LANG;

		$this->localLang();

		$lF = t3lib_div::makeInstance('logFunctions_ext');

		$theOutput='';
		$menu='';
		$menu.=  '&nbsp;'.$LANG->getLL('chLog_menuUsers').': '.t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[log_users]',$this->pObj->MOD_SETTINGS['log_users'],$this->pObj->MOD_MENU['log_users']);
		$menu.=  '&nbsp;'.$LANG->getLL('chLog_menuDepth').': '.t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[depth]',$this->pObj->MOD_SETTINGS['depth'],$this->pObj->MOD_MENU['depth']);
		$menu.=  '&nbsp;'.$LANG->getLL('chLog_menuTime').': '.t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[log_time]',$this->pObj->MOD_SETTINGS['log_time'],$this->pObj->MOD_MENU['log_time']);
		$theOutput.=$this->pObj->doc->section($LANG->getLL('chLog_title'),'<span class="nobr">'.$menu.'</span>',0,1);

			// Build query
		$where_part='';

			// Get the id-list of pages for the tree structure.
		$tree = t3lib_div::makeInstance('t3lib_pageTree');
		$tree->init('AND '.$this->pObj->perms_clause);
		$tree->makeHTML=0;
		$tree->fieldArray = Array('uid');
		if ($this->pObj->MOD_SETTINGS['depth'])	{
			$tree->getTree($this->pObj->id, $this->pObj->MOD_SETTINGS['depth'], '');
		}
		$tree->ids[]=$this->pObj->id;
		$idList = implode($tree->ids,',');

		$where_part.=' AND (event_pid in ('.$idList.'))';		// DB

			// Time:
		$starttime=0;
		$endtime = $GLOBALS['EXEC_TIME'];
		switch($this->pObj->MOD_SETTINGS['log_time'])		{
			case 0:
				// This week
				$week = (date('w') ? date('w') : 7)-1;
				$starttime = mktime (0,0,0)-$week*3600*24;
			break;
			case 1:
				// Last week
				$week = (date('w') ? date('w') : 7)-1;
				$starttime = mktime (0,0,0)-($week+7)*3600*24;
				$endtime = mktime (0,0,0)-$week*3600*24;
			break;
			case 2:
				// Last 7 days
				$starttime = mktime (0,0,0)-7*3600*24;
			break;
			case 10:
				// This month
				$starttime = mktime (0,0,0, date('m'),1);
			break;
			case 11:
				// Last month
				$starttime = mktime (0,0,0, date('m')-1,1);
				$endtime = mktime (0,0,0, date('m'),1);
			break;
			case 12:
				// Last 31 days
				$starttime = mktime (0,0,0)-31*3600*24;
			break;
		}
		if ($starttime)	{
			$where_part.=' AND tstamp>='.$starttime.' AND tstamp<'.$endtime;
		}

		$where_part.=' AND type=1';		// DB


			// Users
		$this->pObj->be_user_Array = t3lib_BEfunc::getUserNames();
		if (!$this->pObj->MOD_SETTINGS['log_users'])	{	// All users
				// Get usernames and groupnames
			if (!$GLOBALS['BE_USER']->isAdmin())		{
				$groupArray = explode(',',$GLOBALS['BE_USER']->user['usergroup_cached_list']);
				$this->pObj->be_user_Array = t3lib_BEfunc::blindUserNames($this->pObj->be_user_Array,$groupArray,1);
			}

			if (is_array($this->pObj->be_user_Array))	{
				foreach ($this->pObj->be_user_Array as $val) {
					$selectUsers[]=$val['uid'];
				}
			}
			$selectUsers[] = $GLOBALS['BE_USER']->user['uid'];
			$where_part.=' AND userid in ('.implode($selectUsers,',').')';
		} else {
			$where_part.=' AND userid='.$GLOBALS['BE_USER']->user['uid'];	// Self user
		}
		$lF->be_user_Array = &$this->pObj->be_user_Array;

		if ($GLOBALS['BE_USER']->workspace!==0)	{
			$where_part.=' AND workspace='.intval($GLOBALS['BE_USER']->workspace);
		}


			// Select 100 recent log entries:
		$log = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_log', '1=1'.$where_part, '', 'uid DESC', 100);

		$codeArr = $lF->initArray();
		$oldHeader = '';
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($log))	{
			$header = $this->pObj->doc->formatTime($row['tstamp'],10);
			if (!$oldHeader)	$oldHeader = $header;

			if ($header!=$oldHeader)	{
				$theOutput.=$this->pObj->doc->spacer(10);
				$theOutput.=$this->pObj->doc->section($oldHeader,$this->pObj->doc->table($codeArr));
				$codeArr=$lF->initArray();
				$oldHeader=$header;
				$lF->reset();
			}

			$i++;
			$codeArr[$i][]=$lF->getTimeLabel($row['tstamp']);
			$codeArr[$i][]=$lF->getUserLabel($row['userid'],$row['workspace']);
			$codeArr[$i][]=$row['error'] ? $lF->getErrorFormatting($lF->errorSign[$row['error']],$row['error']) : '';
			$codeArr[$i][]=$lF->getActionLabel($row['type'].'_'.$row['action']);
			$codeArr[$i][]=$row['tablename'];
			$codeArr[$i][]=$lF->formatDetailsForList($row);
		}
		$theOutput.=$this->pObj->doc->spacer(10);
		$theOutput.=$this->pObj->doc->section($header,$this->pObj->doc->table($codeArr));

		$GLOBALS['TYPO3_DB']->sql_free_result($log);

		return $theOutput;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/belog/class.tx_belog_webinfo.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/belog/class.tx_belog_webinfo.php']);
}

?>