<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Manage storing and restoring of $GLOBALS['SOBE']->MOD_SETTINGS settings.
 * Provides a presets box for BE modules.
 * 
 * This class is in pre-beta state!!
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  104: class t3lib_modSettings
 *  114:     function init($prefix='',$storeList='')
 *  123:     function setStoreList($storeList)
 *  132:     function addToStoreList($storeList)
 *  144:     function addToStoreListFromPrefix ($prefix='')
 *  167:     function initStoreArray()
 *  187:     function cleanStoreConfigs($storeConfigs,$storeArray)
 *  204:     function addToStoreConfigs($storeConfigs,$index)
 *  222:     function loadStoreConfigs($storeConfigs,$storeIndex,$writeArray)
 *  238:     function getStoreControl($show='load,remove,save')
 *  294:     function procesStoreControl($mconfName='')
 *  374:     function saveQueryInAction($uid)
 *
 * TOTAL FUNCTIONS: 11
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */








/**
 * usage inside of scbase class
 *
 * ....
 *
 * $this->MOD_MENU = array(
 * 	'function' => array(
 * 		'xxx ...
 * 	),
 * 	'tx_dam_selectStoreArray' => '',
 * 	'tx_dam_selectStoreConfigs' => '',
 *
 * ....
 *
 *
 * function main()	{
 * 	// reStore settings
 * $store = t3lib_div::makeInstance('t3lib_modSettings');
 * $store->init('tx_dam_select');
 * $store->addToStoreListFromPrefix('tx_dam_select');
 * $storeMsg=$store->procesStoreControl();
 *
 * 	// show control panel
 * $this->content.= $this->doc->section('store',$store->makeStoreControl(),0,1);
 * if ($storeMsg)	{
 * 	$this->content.= $this->doc->section('','<strong>'.$storeMsg.'</strong>');
 * }
 */

/**
 * Manage storing and restoring of $GLOBALS['SOBE']->MOD_SETTINGS settings.
 * Provides a presets box for BE modules.
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_modSettings {

	var $prefix='';
	var $storeList=array();

	/**
	 * @param	string		prefix of MOD_SETTING array keys that should be stored
	 * @param	array		additional names of keys of the MOD_SETTING array which should be stored
	 * @return	[type]		...
	 */
	function init($prefix='',$storeList='')	{
		$this->prefix = $prefix;
		$this->setStoreList($storeList);
	}

	/**
	 * @param	mixed		array or string (,) - set additional names of keys of the MOD_SETTING array which should be stored
	 * @return	[type]		...
	 */
	function setStoreList($storeList)	{
		$this->storeList = is_array($storeList) ? $storeList : t3lib_div::trimExplode(',',$storeList,1);
#debug($this->storeList, '$this->storeList', __LINE__, __FILE__);
	}

	/**
	 * @param	mixed		array or string (,) - add names of keys of the MOD_SETTING array which should be stored
	 * @return	[type]		...
	 */
	function addToStoreList($storeList)	{
		$storeList = is_array($storeList) ? $storeList : t3lib_div::trimExplode(',',$storeList,1);
		$this->storeList = array_merge($this->storeList, $storeList);
#debug($this->storeList, '$this->storeList', __LINE__, __FILE__);
	}

	/**
	 * add names of keys of the MOD_SETTING array which should be stored by a prefix
	 *
	 * @param	string		prefix of MOD_SETTING array keys that should be stored
	 * @return	[type]		...
	 */
	function addToStoreListFromPrefix ($prefix='') {
		$prefix = $prefix ? $prefix : $this->prefix;

		reset($GLOBALS['SOBE']->MOD_SETTINGS);
		while(list($key)=each($GLOBALS['SOBE']->MOD_SETTINGS))	{
			if (ereg('^'.$prefix,$key)) {
				$this->storeList[$key]=$key;
			}
		}

		unset($this->storeList[$this->prefix.'StoreArray']);
		unset($this->storeList[$this->prefix.'StoreConfigs']);

#debug($GLOBALS['SOBE']->MOD_SETTINGS, 'store: $GLOBALS[SOBE]->MOD_SETTINGS', __LINE__, __FILE__);
#debug($prefix, '$prefix', __LINE__, __FILE__);
#debug($this->storeList, '$this->storeList', __LINE__, __FILE__);
	}

	/**
	 * get and init the stored settings
	 *
	 * @return	[type]		...
	 */
	function initStoreArray()	{
		$storeArray=array(
			'0' => ' '
		);

		$savedStoreArray = unserialize($GLOBALS['SOBE']->MOD_SETTINGS[$this->prefix.'StoreArray']);

		if (is_array($savedStoreArray))	{
			$storeArray = array_merge($storeArray,$savedStoreArray);
		}
		return $storeArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$storeConfigs: ...
	 * @param	[type]		$storeArray: ...
	 * @return	[type]		...
	 */
	function cleanStoreConfigs($storeConfigs,$storeArray)	{
		if (is_array($storeConfigs))	{
			reset($storeConfigs);
			while(list($k,$v)=each($storeConfigs))	{
				if (!isset($storeArray[$k]))	unset($storeConfigs[$k]);
			}
		}
		return $storeConfigs;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$storeConfigs: ...
	 * @param	[type]		$index: ...
	 * @return	[type]		...
	 */
	function addToStoreConfigs($storeConfigs,$index)	{
		reset($this->storeList);
		$storeConfigs[$index]=array();
		foreach($this->storeList as $k)	{
			$storeConfigs[$index][$k]=$GLOBALS['SOBE']->MOD_SETTINGS[$k];
		}
#debug($storeConfigs.'$storeConfigs', __LINE__, __FILE__);
		return $storeConfigs;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$storeConfigs: ...
	 * @param	[type]		$storeIndex: ...
	 * @param	[type]		$writeArray: ...
	 * @return	[type]		...
	 */
	function loadStoreConfigs($storeConfigs,$storeIndex,$writeArray)	{
		if ($storeConfigs[$storeIndex])	{
			foreach($this->storeList as $k)	{
#debug($k,'key', __LINE__, __FILE__);
				$writeArray[$k]=$storeConfigs[$storeIndex][$k];
			}
		}
		return $writeArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$show: ...
	 * @return	[type]		...
	 */
	function getStoreControl($show='load,remove,save')	{
			// Load/Save
		$show = t3lib_div::trimexplode(',',$show,1);
		$storeArray = $this->initStoreArray();
		$cur='';

			// Store Array:
		$opt=array();
		reset($storeArray);
		while(list($k,$v)=each($storeArray))	{
			$opt[]='<option value="'.$k.'"'.(!strcmp($cur,$v)?" selected":"").'>'.htmlspecialchars($v).'</option>';
		}

			// Actions:
		if (t3lib_extMgm::isLoaded('sys_action'))	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_action', 'type=2', '', 'title');
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
				$opt[]='<option value="0">__Save to Action:__</option>';
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$opt[]='<option value="-'.$row['uid'].'"'.(!strcmp($cur,"-".$row['uid'])?" selected":"").'>'.htmlspecialchars($row['title'].' ['.$row['uid'].']').'</option>';
				}
			}
		}

		$TDparams=' nowrap="nowrap" class="bgColor4"';
		$tmpCode='
		<table border=0 cellpadding=3 cellspacing=1 width="100%">
		<tr'.$TDparams.'>
		<td width="1%">Preset:</td>';

		if(in_array('load',$show) OR in_array('remove',$show)) {
			$tmpCode.='<td nowrap>';
			$tmpCode.='<select name="storeControl[STORE]" onChange="document.forms[0][\'storeControl[title]\'].value= this.options[this.selectedIndex].value!=0 ? this.options[this.selectedIndex].text : \'\';">'.implode(chr(10),$opt).'</select>';
			if(in_array('load',$show)) {
					$tmpCode.=' <input type="submit" name="storeControl[LOAD]" value="Load" /> ';
			}
			if(in_array('remove',$show)) {
					$tmpCode.=' <input type="submit" name="storeControl[REMOVE]" value="Remove" /> ';
			}
			$tmpCode.='&nbsp;&nbsp;</td>';
		}
		if(in_array('save',$show)) {
			$tmpCode.='<td nowrap><input name="storeControl[title]" value="" type="text" max=80 width="25"><input type="submit" name="storeControl[SAVE]" value="Save" onClick="if (document.forms[0][\'storeControl[STORE]\'].options[document.forms[0][\'storeControl[STORE]\'].selectedIndex].value<0) return confirm(\'Are you sure you want to overwrite the existing query in this action?\');" /></td>';
		}
		$tmpCode.='</tr>
		</table>
		';
		return $tmpCode;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mconfName: ...
	 * @return	[type]		...
	 */
	function procesStoreControl($mconfName='')	{
		$storeArray = $this->initStoreArray();
		$storeConfigs = unserialize($GLOBALS['SOBE']->MOD_SETTINGS[$this->prefix.'StoreConfigs']);
#debug($storeConfigs);
		$storeControl = t3lib_div::_GP('storeControl');
		$storeIndex = intval($storeControl['STORE']);
#debug($storeControl);
		$saveStoreArray=0;
		$writeArray=array();
		if (is_array($storeControl))	{
			if ($storeControl['LOAD'])	{
				if ($storeIndex>0)	{
					$writeArray=$this->loadStoreConfigs($storeConfigs,$storeIndex,$writeArray);
					$saveStoreArray=1;
					$msg="'".$storeArray[$storeIndex]."' preset loaded!";
				} elseif ($storeIndex<0 && t3lib_extMgm::isLoaded('sys_action'))	{
					$actionRecord=t3lib_BEfunc::getRecord('sys_action',abs($storeIndex));
					if (is_array($actionRecord))	{
						$dA = unserialize($actionRecord['t2_data']);
						$dbSC=array();
						if (is_array($dA['qC']))	{
							$dbSC[0] = $dA['qC'];
						}
						$writeArray=$this->loadStoreConfigs($dbSC,'0',$writeArray);
						$saveStoreArray=1;
						$acTitle=htmlspecialchars($actionRecord['title']);
						$msg="Query from action '".$acTitle."' loaded!";
					}
				}

			} elseif ($storeControl['SAVE'])	{
				if ($storeIndex<0)	{
					$qOK = $this->saveQueryInAction(abs($storeIndex));
					if ($qOK)	{
						$msg='Preset OK and saved.';
					} else {
						$msg='No preset saved!';
					}
				} else {
					if (trim($storeControl['title']))	{
						if ($storeIndex>0)	{
							$storeArray[$storeIndex]=$storeControl['title'];
						} else {
							$storeArray[]=$storeControl['title'];
							end($storeArray);
							$storeIndex=key($storeArray);
						}
						$storeConfigs=$this->addToStoreConfigs($storeConfigs,$storeIndex);
						$saveStoreArray=1;
						$msg="'".$storeArray[$storeIndex]."' preset saved!";
					}
				}
			} elseif ($storeControl['REMOVE'])	{
				if ($storeIndex>0)	{
					$msg="'".$storeArray[$storeControl['STORE']]."' preset entry removed!";
					unset($storeArray[$storeControl['STORE']]);	// Removing
					$saveStoreArray=1;
				}
			}
		}
		if ($saveStoreArray)	{
			unset($storeArray[0]);	// making sure, index 0 is not set!
			$writeArray[$this->prefix.'StoreArray']=serialize($storeArray);
			$writeArray[$this->prefix.'StoreConfigs']=serialize($this->cleanStoreConfigs($storeConfigs,$storeArray));
			$GLOBALS['SOBE']->MOD_SETTINGS = t3lib_BEfunc::getModuleData($GLOBALS['SOBE']->MOD_MENU, $writeArray, ($mconfName?$mconfName:$GLOBALS['SOBE']->MCONF['name']), 'ses');

#debug($GLOBALS['SOBE']->MOD_MENU, '$GLOBALS[SOBE]->MOD_MENU', __LINE__, __FILE__);
#debug($storeArray, '$storeArray', __LINE__, __FILE__);
#debug($writeArray, '$writeArray', __LINE__, __FILE__);
#debug($GLOBALS['SOBE']->MOD_SETTINGS, 'store: $GLOBALS[SOBE]->MOD_SETTINGS', __LINE__, __FILE__);
		}
		return $msg;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function saveQueryInAction($uid)	{
		if (t3lib_extMgm::isLoaded('sys_action'))	{
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_modSettings.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_modSettings.php']);
}
?>