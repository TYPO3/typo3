<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains a class with functions for page related statistics added to the backend Info module
 *
 * Revised for TYPO3 3.6 5/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

require_once(PATH_typo3.'class.db_list.inc');
require_once(t3lib_extMgm::extPath('cms').'layout/class.tx_cms_layout.php');


/**
 * Class for displaying page information (records, page record properties)
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_cms
 */
class tx_cms_webinfo_page extends t3lib_extobjbase {

	/**
	 * Returns the menu array
	 *
	 * @return	array
	 */
	function modMenu()	{
		global $LANG;
		return array (
			'pages' => array (
				0 => $LANG->getLL('pages_0'),
				2 => $LANG->getLL('pages_2'),
				1 => $LANG->getLL('pages_1')
			),
			'stat_type' => array(
				0 => $LANG->getLL('stat_type_0'),
				1 => $LANG->getLL('stat_type_1'),
				2 => $LANG->getLL('stat_type_2'),
			),
			'depth' => array(
				0 => $LANG->getLL('depth_0'),
				1 => $LANG->getLL('depth_1'),
				2 => $LANG->getLL('depth_2'),
				3 => $LANG->getLL('depth_3'),
				999 => $LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_infi'),
			)
		);
	}

	/**
	 * MAIN function for page information display (including hit statistics)
	 *
	 * @return	string		Output HTML for the module.
	 */
	function main()	{
		global $BACK_PATH,$LANG,$SOBE;

		$dblist = t3lib_div::makeInstance('tx_cms_layout');
		$dblist->descrTable = '_MOD_'.$GLOBALS['MCONF']['name'];
		$dblist->backPath = $BACK_PATH;
		$dblist->thumbs = 0;
		$dblist->script = 'index.php';
		$dblist->showIcon = 0;
		$dblist->setLMargin=0;
		$dblist->agePrefixes=$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears');

		$dblist->pI_showUser=1;
		$dblist->pI_showStat=0;


			// PAGES:
		$this->pObj->MOD_SETTINGS['pages_levels']=$this->pObj->MOD_SETTINGS['depth'];		// ONLY for the sake of dblist module which uses this value.

		$h_func = t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[depth]',$this->pObj->MOD_SETTINGS['depth'],$this->pObj->MOD_MENU['depth'],'index.php');
		if ($this->pObj->MOD_SETTINGS['function']=='tx_cms_webinfo_hits')	{
			$h_func.= t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[stat_type]',$this->pObj->MOD_SETTINGS['stat_type'],$this->pObj->MOD_MENU['stat_type'],'index.php');

			if ($this->pObj->MOD_SETTINGS['stat_type']==1)	$dblist->stat_select_field='rl0';
			if ($this->pObj->MOD_SETTINGS['stat_type']==2)	$dblist->stat_select_field='rl1';

				// Timespan
			for ($a=0;$a<30;$a++)	{
				$dblist->stat_codes[]='HITS_days:'.(-$a);
			}
			$timespan_b = mktime (0,0,0);
			$timespan_e = mktime (0,0,0)-(30-1)*3600*24+1;
			$header='<br />'.sprintf($LANG->getLL('stat_period'),t3lib_BEfunc::date($timespan_b),t3lib_BEfunc::date($timespan_e)).'<br />';

				//
			$dblist->start($this->pObj->id,'pages',0);
			$dblist->pages_noEditColumns=1;
			$dblist->generateList();


			$theOutput .= $this->pObj->doc->header($LANG->getLL('hits_title'));
			$theOutput .= $this->pObj->doc->section('',
				t3lib_BEfunc::cshItem($dblist->descrTable, 'stat', $GLOBALS['BACK_PATH'], '|<br />') . // CSH
					$h_func.
					$header.
					$dblist->HTMLcode,
				0,
				1
			);
		} else {
			$h_func.= t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[pages]',$this->pObj->MOD_SETTINGS['pages'],$this->pObj->MOD_MENU['pages'],'index.php');
			$dblist->start($this->pObj->id,'pages',0);
			$dblist->generateList();

				// CSH
			$theOutput .= $this->pObj->doc->header($LANG->getLL('page_title'));
			$theOutput .=$this->pObj->doc->section('',
				t3lib_BEfunc::cshItem($dblist->descrTable, 'pagetree_overview', $GLOBALS['BACK_PATH'], '|<br />') . // CSH
					$h_func.
					$dblist->HTMLcode,
				0,
				1
			);

				// SYS_NOTES:
			if (t3lib_extMgm::isLoaded('sys_note'))	{
				$dblist->start($this->pObj->id,'sys_note',0);
				$dblist->generateList();
				if ($dblist->HTMLcode)	{
					$theOutput.=$this->pObj->doc->spacer(10);
					$theOutput.=$this->pObj->doc->section($LANG->getLL('page_sysnote'),
						$dblist->HTMLcode,
						0,
						1
					);
				}
			}

				// PAGE INFORMATION
			if ($this->pObj->pageinfo['uid'])	{
				$theOutput.=$this->pObj->doc->spacer(10);
				$theOutput.=$this->pObj->doc->section($LANG->getLL('pageInformation'),$dblist->getPageInfoBox($this->pObj->pageinfo,$this->pObj->CALC_PERMS&2),0,1);
			}
		}

		return $theOutput;
	}
}

/**
 * Extension class for hits display, basically using tx_cms_webinfo_page (internally this is detected).
 * This construction is due to the old "pre-extensions" structure
 *
 * IMPORTANT: This class is used by the extension "sys_stat" and will be added to the Info module only when "sys_stat" is installed.
 * The display of statistics goes on in "tx_cms_webinfo_page" though
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_cms
 */
class tx_cms_webinfo_hits extends tx_cms_webinfo_page {
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cms/web_info/class.tx_cms_webinfo.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cms/web_info/class.tx_cms_webinfo.php']);
}
?>