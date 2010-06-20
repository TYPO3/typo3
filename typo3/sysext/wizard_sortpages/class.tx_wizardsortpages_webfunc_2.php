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
 * Contains class for "Sort pages" wizard
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   67: class tx_wizardsortpages_webfunc_2 extends t3lib_extobjbase
 *   75:     function modMenu()
 *   88:     function main()
 *  175:     function wiz_linkOrder($title,$order)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
/**
 * Creates the "Sort pages" wizard
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_wizardsortpages
 */
class tx_wizardsortpages_webfunc_2 extends t3lib_extobjbase {

	/**
	 * Adds menu items... but I think this is not used at all. Looks very much like some testing code. If anyone cares to check it we can remove it some day...
	 *
	 * @return	array
	 * @ignore
	 */
	function modMenu()	{
		global $LANG;

		$modMenuAdd = array(
		);
		return $modMenuAdd;
	}

	/**
	 * Main function creating the content for the module.
	 *
	 * @return	string		HTML content for the module, actually a "section" made through the parent object in $this->pObj
	 */
	function main()	{
		global $SOBE,$LANG;

		if ($GLOBALS['BE_USER']->workspace===0)	{

			$theCode='';

				// check if user has modify permissions to
			$sys_pages = t3lib_div::makeInstance('t3lib_pageSelect');
			$sortByField = t3lib_div::_GP('sortByField');
			if ($sortByField)	{
				$menuItems=array();
				if (t3lib_div::inList('title,subtitle,crdate,tstamp',$sortByField))	{
					$menuItems = $sys_pages->getMenu($this->pObj->id,'uid,pid,title',$sortByField,'',0);
				} elseif ($sortByField=='REV') {
					$menuItems = $sys_pages->getMenu($this->pObj->id,'uid,pid,title','sorting','',0);
					$menuItems = array_reverse($menuItems);
				}
				if (count($menuItems))	{
					$tce = t3lib_div::makeInstance('t3lib_TCEmain');
					$tce->stripslashes_values=0;
					$menuItems = array_reverse($menuItems);
					$cmd=array();
					foreach ($menuItems as $r) {
						$cmd['pages'][$r['uid']]['move']=$this->pObj->id;
					}
					$tce->start(array(),$cmd);
					$tce->process_cmdmap();
					t3lib_BEfunc::setUpdateSignal('updatePageTree');
				}
			}

				//
			$menuItems = $sys_pages->getMenu($this->pObj->id,'*','sorting','',0);
			$lines=array();
				$lines[]= '<tr class="t3-row-header">
					<td>' . $this->wiz_linkOrder($LANG->getLL('wiz_changeOrder_title'), 'title') . '</td>
					' . (t3lib_extMgm::isLoaded('cms') ? '<td> ' . $this->wiz_linkOrder($LANG->getLL('wiz_changeOrder_subtitle'), 'subtitle') . '</td>' : '').'
					<td>' . $this->wiz_linkOrder($LANG->getLL('wiz_changeOrder_tChange'), 'tstamp') . '</td>
					<td>' . $this->wiz_linkOrder($LANG->getLL('wiz_changeOrder_tCreate'), 'crdate') . '</td>
					</tr>';
			foreach ($menuItems as $rec) {
				$m_perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(2);	// edit permissions for that page!
				$pRec = t3lib_BEfunc::getRecord ('pages',$rec['uid'],'uid',' AND '.$m_perms_clause);
				$lines[]= '<tr><td nowrap="nowrap">'.t3lib_iconWorks::getSpriteIconForRecord('pages',$rec).
					(!is_array($pRec)?$GLOBALS['TBE_TEMPLATE']->rfw('<strong>'.$LANG->getLL('wiz_W',1).'</strong> '):'').
					htmlspecialchars(t3lib_div::fixed_lgd_cs($rec['title'],$GLOBALS['BE_USER']->uc['titleLen'])).'&nbsp;</td>
					'.(t3lib_extMgm::isLoaded('cms')?'<td nowrap="nowrap">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($rec['subtitle'],$GLOBALS['BE_USER']->uc['titleLen'])).'&nbsp;</td>':'').'
					<td nowrap="nowrap">'.t3lib_Befunc::datetime($rec['tstamp']).'&nbsp;&nbsp;</td>
					<td nowrap="nowrap">'.t3lib_Befunc::datetime($rec['crdate']).'&nbsp;&nbsp;</td>
					</tr>';
			}

			$theCode .= '<h4>' . $LANG->getLL('wiz_currentPageOrder', TRUE) . '</h4>
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">' . implode('', $lines) . '</table><br />';

			if (count($menuItems))	{
					// Menu:
				$lines=array();
				$lines[] = $this->wiz_linkOrder($LANG->getLL('wiz_changeOrder_title'),'title');
				if (t3lib_extMgm::isLoaded('cms')) $lines[] = $this->wiz_linkOrder($LANG->getLL('wiz_changeOrder_subtitle'),'subtitle');
				$lines[] = $this->wiz_linkOrder($LANG->getLL('wiz_changeOrder_tChange'),'tstamp');
				$lines[] = $this->wiz_linkOrder($LANG->getLL('wiz_changeOrder_tCreate'),'crdate');
				$lines[] = '';
				$lines[] = $this->wiz_linkOrder($LANG->getLL('wiz_changeOrder_REVERSE'),'REV');
				$theCode.= '<h4>' . $LANG->getLL('wiz_changeOrder') . '</h4>' . implode('<br />', $lines);
			}

				// CSH:
			$theCode.= t3lib_BEfunc::cshItem('_MOD_web_func', 'tx_wizardsortpages', $GLOBALS['BACK_PATH'], '<br />|');

			$out=$this->pObj->doc->section($LANG->getLL('wiz_sort'),$theCode,0,1);
		} else {
			$out=$this->pObj->doc->section($LANG->getLL('wiz_sort'),'Sorry, this function is not available in the current draft workspace!',0,1,1);
		}
		return $out;
	}

	/**
	 * Creates a link for the sorting order
	 *
	 * @param	string		Title of the link
	 * @param	string		Field to sort by
	 * @return	string		HTML string
	 */
	function wiz_linkOrder($title,$order)	{
		return '&nbsp; &nbsp;<a class="t3-link" href="' . htmlspecialchars('index.php?id=' . $GLOBALS['SOBE']->id . '&sortByField=' . $order) . '" onclick="return confirm('.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('wiz_changeOrder_msg1')) . ')">' . htmlspecialchars($title) . '</a>';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wizard_sortpages/class.tx_wizardsortpages_webfunc_2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wizard_sortpages/class.tx_wizardsortpages_webfunc_2.php']);
}
?>