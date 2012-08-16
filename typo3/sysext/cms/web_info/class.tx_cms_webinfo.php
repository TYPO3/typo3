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
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */

require_once(t3lib_extMgm::extPath('cms').'layout/class.tx_cms_layout.php');


/**
 * Class for displaying page information (records, page record properties)
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_cms
 */
class tx_cms_webinfo_page extends t3lib_extobjbase {

	/**
	 * Returns the menu array
	 *
	 * @return	array
	 */
	function modMenu() {
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
	 * MAIN function for page information display
	 *
	 * @return string Output HTML for the module.
	 */
	function main() {
		global $BACK_PATH,$LANG,$SOBE;

		$dblist = t3lib_div::makeInstance('tx_cms_layout');
		$dblist->descrTable = '_MOD_'.$GLOBALS['MCONF']['name'];
		$dblist->backPath = $BACK_PATH;
		$dblist->thumbs = 0;
		$dblist->script = 'index.php';
		$dblist->showIcon = 0;
		$dblist->setLMargin = 0;
		$dblist->agePrefixes=$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears');

		$dblist->pI_showUser = 1;

			// PAGES:
		$this->pObj->MOD_SETTINGS['pages_levels']=$this->pObj->MOD_SETTINGS['depth'];		// ONLY for the sake of dblist module which uses this value.

		$h_func = t3lib_BEfunc::getFuncMenu($this->pObj->id, 'SET[depth]', $this->pObj->MOD_SETTINGS['depth'], $this->pObj->MOD_MENU['depth'], 'index.php');
		$h_func .= t3lib_BEfunc::getFuncMenu($this->pObj->id, 'SET[pages]', $this->pObj->MOD_SETTINGS['pages'], $this->pObj->MOD_MENU['pages'], 'index.php');
		$dblist->start($this->pObj->id, 'pages', 0);
		$dblist->generateList();

			// CSH
		$theOutput .= $this->pObj->doc->header($LANG->getLL('page_title'));
		$theOutput .= $this->pObj->doc->section('',
			t3lib_BEfunc::cshItem($dblist->descrTable, 'pagetree_overview', $GLOBALS['BACK_PATH'], '|<br />') . // CSH
				$h_func.
				$dblist->HTMLcode,
			0,
			1
		);

			// Additional footer content
		$footerContentHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/web_info/class.tx_cms_webinfo.php']['drawFooterHook'];
		if (is_array($footerContentHook)) {
			foreach ($footerContentHook as $hook) {
				$params = array();
				$theOutput .= t3lib_div::callUserFunction($hook, $params, $this);
			}
		}

		return $theOutput;
	}
}
?>