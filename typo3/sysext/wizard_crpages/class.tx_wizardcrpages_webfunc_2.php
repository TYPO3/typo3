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
 * Contains class for "Create pages" wizard
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
 *   70: class tx_wizardcrpages_webfunc_2 extends t3lib_extobjbase
 *   78:     function modMenu()
 *   95:     function main()
 *  179:     function helpBubble()
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_t3lib.'class.t3lib_page.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_extobjbase.php');










/**
 * Creates the "Create pages" wizard
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_wizardcrpages
 */
class tx_wizardcrpages_webfunc_2 extends t3lib_extobjbase {

	/**
	 * Adds menu items... but I think this is not used at all. Looks very much like some testing code. If anyone cares to check it we can remove it some day...
	 *
	 * @return	array
	 * @ignore
	 */
	function modMenu()	{
		global $LANG;

		$modMenuAdd = array(
			'cr_333' => array(
				'0' => 'nul',
				'1' => 'et'
			)
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

		$theCode='';

		$m_perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(8);	// create new pages here?
		$pRec = t3lib_BEfunc::getRecord ('pages',$this->pObj->id,'uid',' AND '.$m_perms_clause);
		$sys_pages = t3lib_div::makeInstance('t3lib_pageSelect');
		$menuItems = $sys_pages->getMenu($this->pObj->id);
		if (is_array($pRec))	{
			$data = t3lib_div::_GP('data');
			if (is_array($data['pages']))	{
				if (t3lib_div::_GP('createInListEnd'))	{
					$endI = end($menuItems);
					$thePid = -intval($endI['uid']);
					if (!$thePid)	$thePid = $this->pObj->id;
				} else {
					$thePid = $this->pObj->id;
				}

				while(list($k,$dat)=each($data['pages']))	{
					if (!trim($dat['title']))	{
						unset($data['pages'][$k]);
					} else {
						$data['pages'][$k]['pid']=$thePid;
						$data['pages'][$k]['hidden'] = t3lib_div::_GP('hidePages') ? 1 : 0;
					}
				}
				if (count($data['pages']))	{
					reset($data);
					$tce = t3lib_div::makeInstance('t3lib_TCEmain');
					$tce->stripslashes_values=0;
					$tce->reverseOrder=1;
					$tce->start($data,array());
					$tce->process_datamap();
					t3lib_BEfunc::getSetUpdateSignal('updatePageTree');
				} else {
					$theCode.=$GLOBALS['TBE_TEMPLATE']->rfw($LANG->getLL('wiz_newPages_noCreate').'<br /><br />');
				}

					// Display result:
				$menuItems = $sys_pages->getMenu($this->pObj->id);
				reset($menuItems);
				$lines=array();
				while(list(,$rec)=each($menuItems))	{
					$lines[]= '<nobr>'.t3lib_iconWorks::getIconImage('pages',$rec,$GLOBALS['BACK_PATH'],'align="top" '.t3lib_BEfunc::titleAttribForPages($rec)).
						htmlspecialchars(t3lib_div::fixed_lgd_cs($rec['title'],$GLOBALS['BE_USER']->uc['titleLen'])).'</nobr>';
				}
				$theCode.= '<b>'.$LANG->getLL('wiz_newPages_currentMenu').':</b><br /><br />'.implode('<br />',$lines);
			} else {
					// Create loremIpsum code:
				if (t3lib_extMgm::isLoaded('lorem_ipsum'))	{
					$loremIpsumObj = t3lib_div::getUserObj('EXT:lorem_ipsum/class.tx_loremipsum_wiz.php:tx_loremipsum_wiz');
				}
					// Display create form
				$lines = array();
				for ($a=0;$a<9;$a++)	{
					$lines[] = $LANG->getLL('wiz_newPages_page').' '.($a+1).
						': <input type="text" name="data[pages][NEW'.$a.'][title]"'.$this->pObj->doc->formWidth(35).' />'.
						(is_object($loremIpsumObj) ? '<a href="#" onclick="'.htmlspecialchars($loremIpsumObj->getHeaderTitleJS('document.forms[0][\'data[pages][NEW'.$a.'][title]\'].value', 'title')).'">'.$loremIpsumObj->getIcon('',$this->pObj->doc->backPath).'</a>' : '');
				}

				$theCode.= '<b>'.$LANG->getLL('wiz_newPages').':</b><br /><br />'.implode('<br />',$lines).
				'<br /><br />
				<input type="checkbox" name="createInListEnd" value="1" />'.$LANG->getLL('wiz_newPages_listEnd').'<br />
				<input type="checkbox" name="hidePages" value="1" />'.$LANG->getLL('wiz_newPages_hidePages').'<br />
				<input type="submit" name="create" value="'.$LANG->getLL('wiz_newPages_lCreate').'" onclick="return confirm('.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('wiz_newPages_lCreate_msg1')).')"> <input type="reset" value="'.$LANG->getLL('wiz_newPages_lReset').'" />';
			}
		} else {
			$theCode.=$GLOBALS['TBE_TEMPLATE']->rfw($LANG->getLL('wiz_newPages_errorMsg1'));
		}

			// CSH
		$theCode.= t3lib_BEfunc::cshItem('_MOD_web_func', 'tx_wizardcrpages', $GLOBALS['BACK_PATH'],'<br/>|');

		$out=$this->pObj->doc->section($LANG->getLL('wiz_crMany'),$theCode,0,1);
		return $out;
	}

	/**
	 * Return the helpbubble image tag.
	 *
	 * @return	string		HTML code for a help-bubble image.
	 */
	function helpBubble()	{
		return '<img src="'.$GLOBALS['BACK_PATH'].'gfx/helpbubble.gif" width="14" height="14" hspace="2" align="top"'.$this->pObj->doc->helpStyle().' alt="" />';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wizard_crpages/class.tx_wizardcrpages_webfunc_2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wizard_crpages/class.tx_wizardcrpages_webfunc_2.php']);
}
?>