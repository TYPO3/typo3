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
 * Module: Log-viewing
 *
 * This module lets you view the changelog.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


$GLOBALS['LANG']->includeLLFile('EXT:belog/mod/locallang.xml');

$BE_USER->modAccess($MCONF,1);




/**
 * Tools log script class
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_belog
 */
class SC_mod_tools_log_index {
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();

	/**
	 * Document template object
	 *
	 * @var noDoc
	 */
	var $doc;

	var $content;
	var $lF;
	var $be_user_Array;

	var $theTime = 0;
	var $theTime_end = 0;

	/**
	 * Initialize module
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		$this->MCONF = $GLOBALS['MCONF'];

		$this->lF = t3lib_div::makeInstance('t3lib_BEDisplayLog');
		$this->menuConfig();

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/belog.html');

				// JavaScript
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL)	{
				window.location.href = URL;
			}
		</script>
		';

		$this->doc->tableLayout = Array (
			'0' => Array (
				'defCol' => Array('<td valign="top" class="c-headLineTable"><b>', '</b></td><td class="c-headLineTable"><img src="' . $this->doc->backPath . 'clear.gif" width="10" height="1" alt="" /></td>')
			),
			'defRow' => Array (
				'0' => Array('<td valign="top">','</td>'),
				'defCol' => Array('<td><img src="' . $this->doc->backPath . 'clear.gif" width="10" height="1" alt="" /></td><td valign="top">', '</td>')
			)
		);
		$this->doc->table_TABLE = '<table border="0" cellspacing="0" cellpadding="0" class="typo3-dblist">';
		$this->doc->form = '<form action="" method="post">';

		$this->be_user_Array = t3lib_BEfunc::getUserNames();
		$this->lF->be_user_Array = &$this->be_user_Array;
	}

	/**
	 * Menu configuration
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS,$TYPO3_DB;

			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'users' => array(
				0 => $GLOBALS['LANG']->getLL('any'),
				'-1' => $GLOBALS['LANG']->getLL('self')
			),
			'workspaces' => array(
				'-99' => $GLOBALS['LANG']->getLL('any'),
				0 => $GLOBALS['LANG']->getLL('live'),
				'-1' => $GLOBALS['LANG']->getLL('draft'),
			),
			'time' => array(
				0 => $GLOBALS['LANG']->getLL('thisWeek'),
				1 => $GLOBALS['LANG']->getLL('lastWeek'),
				2 => $GLOBALS['LANG']->getLL('last7Days'),
				10 => $GLOBALS['LANG']->getLL('thisMonth'),
				11 => $GLOBALS['LANG']->getLL('lastMonth'),
				12 => $GLOBALS['LANG']->getLL('last31Days'),
				20 => $GLOBALS['LANG']->getLL('noLimit')
			),
			'max' => array(
				20 => $GLOBALS['LANG']->getLL('20'),
				50 => $GLOBALS['LANG']->getLL('50'),
				100 => $GLOBALS['LANG']->getLL('100'),
				200 => $GLOBALS['LANG']->getLL('200'),
				500 => $GLOBALS['LANG']->getLL('500'),
				1000 => $GLOBALS['LANG']->getLL('1000'),
				1000000 => $GLOBALS['LANG']->getLL('any')
			),
			'action' => array(
				0 => $GLOBALS['LANG']->getLL('any'),
				1 => $GLOBALS['LANG']->getLL('actionDatabase'),
				2 => $GLOBALS['LANG']->getLL('actionFile'),
				254 => $GLOBALS['LANG']->getLL('actionSettings'),
				255 => $GLOBALS['LANG']->getLL('actionLogin'),
				'-1' => $GLOBALS['LANG']->getLL('actionErrors')
			),
			'manualdate' => '',
			'manualdate_end' => '',
			'groupByPage' => '',
		);

		// Add custom workspaces (selecting all, filtering by BE_USER check):
		$workspaces = $TYPO3_DB->exec_SELECTgetRows('uid,title','sys_workspace','pid=0'.t3lib_BEfunc::deleteClause('sys_workspace'),'','title');
		if (count($workspaces))	{
			foreach ($workspaces as $rec)	{
				$this->MOD_MENU['workspaces'][$rec['uid']] = $rec['uid'].': '.$rec['title'];
			}
		}

		// Adding groups to the users_array
		$groups = t3lib_BEfunc::getGroupNames();
			if (is_array($groups))	{
			while(list(,$grVals)=each($groups))	{
				$this->MOD_MENU['users']['gr-'.$grVals['uid']] = 'Group: '.$grVals['title'];
			}
		}

		$users = t3lib_BEfunc::getUserNames();
		if (is_array($users))	{
			while(list(,$grVals)=each($users))	{
				$this->MOD_MENU['users']['us-'.$grVals['uid']] = 'User: '.$grVals['username'];
			}
		}

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);

			//
		if (!trim($this->MOD_SETTINGS['manualdate']))	{
			$this->MOD_SETTINGS['manualdate'] = 'YYYY-MM-DD';//"-HH-MM-SS";
		} else {
			$parts = t3lib_div::trimExplode('-',trim($this->MOD_SETTINGS['manualdate']));
			$this->theTime = mktime((int)$parts[3],(int)$parts[4],(int)$parts[5],$parts[1]?(int)$parts[1]:1,$parts[2]?(int)$parts[2]:1,(int)$parts[0]);
			$this->MOD_SETTINGS['manualdate'] = date('Y-m-d-H-i-s',$this->theTime);
		}

		if (!trim($this->MOD_SETTINGS['manualdate_end']))	{
			$this->MOD_SETTINGS['manualdate_end'] = 'YYYY-MM-DD';//"-HH-MM-SS";
		} else {
			$parts = t3lib_div::trimExplode('-',trim($this->MOD_SETTINGS['manualdate_end']));
			$this->theTime_end = mktime((int)$parts[3],(int)$parts[4],(int)$parts[5],$parts[1]?(int)$parts[1]:1,$parts[2]?(int)$parts[2]:1,(int)$parts[0]);
			$this->MOD_SETTINGS['manualdate_end'] = date('Y-m-d-H-i-s',$this->theTime_end);
		}
	}

	/**
	 * Main function
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->content.= $this->doc->header($GLOBALS['LANG']->getLL('adminLog'));
		$this->content.=$this->doc->spacer(5);


			// Menu compiled:
		$menuU= t3lib_BEfunc::getFuncMenu(0,'SET[users]',$this->MOD_SETTINGS['users'],$this->MOD_MENU['users']);
		$menuM= t3lib_BEfunc::getFuncMenu(0,'SET[max]',$this->MOD_SETTINGS['max'],$this->MOD_MENU['max']);
		$menuT= t3lib_BEfunc::getFuncMenu(0,'SET[time]',$this->MOD_SETTINGS['time'],$this->MOD_MENU['time']);
		$menuA= t3lib_BEfunc::getFuncMenu(0,'SET[action]',$this->MOD_SETTINGS['action'],$this->MOD_MENU['action']);
		$menuW= t3lib_BEfunc::getFuncMenu(0,'SET[workspaces]',$this->MOD_SETTINGS['workspaces'],$this->MOD_MENU['workspaces']);

		$groupByPage= t3lib_BEfunc::getFuncCheck(0,'SET[groupByPage]',$this->MOD_SETTINGS['groupByPage']);
		$inputDate= t3lib_BEfunc::getFuncInput(0,'SET[manualdate]',$this->MOD_SETTINGS['manualdate'],20);
		$inputDate_end= t3lib_BEfunc::getFuncInput(0,'SET[manualdate_end]',$this->MOD_SETTINGS['manualdate_end'],20);


		$this->content.=$this->doc->section('',$this->doc->menuTable(
			array(
				array($GLOBALS['LANG']->getLL('users'), $menuU),
				array($GLOBALS['LANG']->getLL('time'), ($this->MOD_SETTINGS['manualdate'] == 'YYYY-MM-DD' ? $menuT : '') . $inputDate . ($this->MOD_SETTINGS['manualdate'] != 'YYYY-MM-DD' ? '<br /> - ' . $inputDate_end : ''))
			),
			array(
				array($GLOBALS['LANG']->getLL('max'), $menuM),
				array($GLOBALS['LANG']->getLL('action'), $menuA)
			),
			array(
				$GLOBALS['BE_USER']->workspace!==0 ? array('Workspace:','<b>'.$GLOBALS['BE_USER']->workspace.'</b>') : array('Workspace:',$menuW),
				array('Group by page:',$groupByPage)
			)
		));
		#$this->content.=$this->doc->divider(5);


		$codeArr = $this->lF->initArray();
		$oldHeader='';
		$c=0;

		// Action (type):
		$where_part='';
		if ($this->MOD_SETTINGS['action'] > 0)	{
			$where_part.=' AND type='.intval($this->MOD_SETTINGS['action']);
		} elseif ($this->MOD_SETTINGS['action'] == -1)	{
			$where_part .= ' AND error != 0';
		}


		$starttime=0;
		$endtime = $GLOBALS['EXEC_TIME'];

		// Time:
		if ($this->theTime)	{
			$starttime = $this->theTime;
			if ($this->theTime_end)	{
				$endtime = $this->theTime_end;
			} else {
				$endtime = $GLOBALS['EXEC_TIME'];
			}
		} else {
			switch($this->MOD_SETTINGS['time'])		{
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
		}
		if ($starttime)	{
			$where_part.=' AND tstamp>='.$starttime.' AND tstamp<'.$endtime;
		}


			// Users
		$selectUsers = array();
		if (substr($this->MOD_SETTINGS['users'],0,3) == "gr-")	{	// All users
			$this->be_user_Array = t3lib_BEfunc::blindUserNames($this->be_user_Array,array(substr($this->MOD_SETTINGS['users'],3)),1);
			if (is_array($this->be_user_Array))	{
				while(list(,$val)=each($this->be_user_Array))	{
					if ($val['uid']!=$BE_USER->user['uid'])	{
						$selectUsers[]=$val['uid'];
					}
				}
			}
			$selectUsers[] = 0;
			$where_part.=' AND userid in ('.implode($selectUsers,',').')';
		} elseif (substr($this->MOD_SETTINGS['users'],0,3) == "us-")	{	// All users
			$selectUsers[] = intval(substr($this->MOD_SETTINGS['users'],3));
			$where_part.=' AND userid in ('.implode($selectUsers,',').')';
		} elseif ($this->MOD_SETTINGS['users']==-1) {
			$where_part.=' AND userid='.$BE_USER->user['uid'];	// Self user
		}

			// Workspace
		if ($GLOBALS['BE_USER']->workspace!==0)	{
			$where_part.=' AND workspace='.intval($GLOBALS['BE_USER']->workspace);
		} elseif ($this->MOD_SETTINGS['workspaces']!=-99)	{
			$where_part.=' AND workspace='.intval($this->MOD_SETTINGS['workspaces']);
		}

			// Finding out which page ids are in the log:
		$logPids = array();
		if ($this->MOD_SETTINGS['groupByPage'])	{
			$log = $GLOBALS['TYPO3_DB']->exec_SELECTquery('event_pid', 'sys_log', '1=1'.$where_part, 'event_pid');
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($log))	{
				$logPids[] = $row['event_pid'];
			}

				// Overview:
			$overviewList = array();
			foreach($logPids as $pid)	{
				if ((int)$pid>0)	{
					$overviewList[]= htmlspecialchars(t3lib_BEfunc::getRecordPath($pid,'',20).'" [UID:'.$pid.']');
				}
			}
			sort($overviewList);
			$this->content.=$this->doc->divider(5);
			$this->content.= $this->doc->section('Overview', 'These pages have log messages from ' . date('Y-m-d H:i:s', $starttime) . ' to ' . date('Y-m-d H:i:s', $endtime) . '<br /><br /><br />' . implode('<br />', $overviewList), 1, 1, 0);
			$this->content.=$this->doc->spacer(30);
		} else $logPids[] = '_SINGLE';


		foreach($logPids as $pid)	{
			$codeArr = $this->lF->initArray();
			$this->lF->reset();
			$oldHeader='';

			$this->content.=$this->doc->divider(5);
			switch($pid)	{
				case '_SINGLE':
					$insertMsg = '';
				break;
				case '-1':
					$insertMsg = ' for NON-PAGE related actions ';
				break;
				case '0':
					$insertMsg = ' for ROOT LEVEL ';
				break;
				default:
					$insertMsg = ' for PAGE "'.t3lib_BEfunc::getRecordPath($pid,'',20).'" ('.$pid.') ';
				break;
			}
			$this->content.=$this->doc->section('Log '.$insertMsg.'from '.date('Y-m-d H:i:s',$starttime).' to '.date('Y-m-d H:i:s',$endtime),'',1,1,0);

			$log = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_log', '1=1'.$where_part.($pid!='_SINGLE'?' AND event_pid='.intval($pid):''), '', 'uid DESC', intval($this->MOD_SETTINGS['max']));

			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($log))	{
				$header=$this->doc->formatTime($row['tstamp'],10);
				if (!$oldHeader)	$oldHeader=$header;

				if ($header!=$oldHeader)	{
					$this->content.=$this->doc->spacer(10);
					$this->content.=$this->doc->section($oldHeader,$this->doc->table($codeArr));
					$codeArr=$this->lF->initArray();
					$oldHeader=$header;
					$this->lF->reset();
				}

				$i++;
				$codeArr[$i][]=$this->lF->getTimeLabel($row['tstamp']);
				$codeArr[$i][]=$this->lF->getUserLabel($row['userid'],$row['workspace']);
				$codeArr[$i][]=$this->lF->getTypeLabel($row['type']);
				$codeArr[$i][]=$row['error'] ? $this->lF->getErrorFormatting($this->lF->errorSign[$row['error']],$row['error']) : '';
				$codeArr[$i][]=$this->lF->getActionLabel($row['type'].'_'.$row['action']);
				$codeArr[$i][]=$this->lF->formatDetailsForList($row);
			}
			$this->content.=$this->doc->spacer(10);
			$this->content.=$this->doc->section($header,$this->doc->table($codeArr));

			$GLOBALS['TYPO3_DB']->sql_free_result($log);
		}

			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		//$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->startPage('Administration log');
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Output content
	 *
	 * @return	string		HTML
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{

		$buttons = array(
			'csh' => '',
			'shortcut' => ''
		);
			// CSH
		//$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('','users,time,max,action',$this->MCONF['name']);
		}

		return $buttons;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/belog/mod/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/belog/mod/index.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_log_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>