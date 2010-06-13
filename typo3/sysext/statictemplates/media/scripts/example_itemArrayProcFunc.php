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
 * This is an example of how to manipulate menu item arrays.
 * Used in the "testsite" package
 *
 * $Id: example_itemArrayProcFunc.php 5165 2009-03-09 18:28:59Z ohader $
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */










/**
 * This function basically adds the parent page to the sublevel but only if the sublevel is empty!
 * It is also used to demonstrate the menu state items
 *
 * Example can be found in the testsite package at the page-path "/Intro/TypoScript examples/Menu object examples/Fake menu items/" and "/Intro/TypoScript examples/Menu object examples/Menu state test/"
 * This TypoScript configuration will also demonstrate it ("fake menu items"):
 *
 * includeLibs.fakemenuitems = media/scripts/example_itemArrayProcFunc.php
 * page = PAGE
 * page.10 = HMENU
 * page.10.1 = TMENU
 * page.10.1.expAll = 1
 * page.10.1.NO {
 *   allWrap = | <br />
 *   linkWrap = <b>|</b>
 * }
 * page.10.2 = TMENU
 * page.10.2.itemArrayProcFunc = user_itemArrayProcFuncTest
 * page.10.2.NO {
 *   allWrap = | <br />
 *   linkWrap = <b> - |</b>
 * }
 *
 * @param	array		The $menuArr array which simply is a num-array of page records which goes into the menu.
 * @param	array		TypoScript configuration for the function. Notice that the property "parentObj" is a reference to the parent (calling) object (the tslib_Xmenu class instantiated)
 * @return	array		The modified $menuArr array
 */
function user_itemArrayProcFuncTest($menuArr,$conf)	{
	if ($conf['demoItemStates'])	{		// Used in the example of item states
		$c=0;
		$teststates=explode(',','NO,ACT,IFSUB,CUR,USR,SPC,USERDEF1,USERDEF2');
		foreach ($menuArr as $k => $v) {
			$menuArr[$k]['ITEM_STATE']=$teststates[$c];
			$menuArr[$k]['title'].= ($teststates[$c] ? ' ['.$teststates[$c].']' : '');
			$c++;
		}
	} else {	// used in the fake menu item example!
		if (!count($menuArr))	{		// There must be no menu items if we add the parent page to the submenu:
			$parentPageId = $conf['parentObj']->id;	// id of the parent page
			$parentPageRow = $GLOBALS['TSFE']->sys_page->getPage($parentPageId);	// ... and get the record...
			if (is_array($parentPageRow))	{	// ... and if that page existed (a row was returned) then add it!
				$menuArr[]=$parentPageRow;
			}
		}
	}
	return $menuArr;
}

/**
 * Used in the menu item state example of the "testsite" package at page-path "/Intro/TypoScript examples/Menu object examples/Menu state test/"
 *
 * @param	array		The menu item array, $this->I (in the parent object)
 * @param	array		TypoScript configuration for the function. Notice that the property "parentObj" is a reference to the parent (calling) object (the tslib_Xmenu class instantiated)
 * @return	array		The processed $I array returned (and stored in $this->I of the parent object again)
 * @see tslib_menu::userProcess(), tslib_tmenu::writeMenu(), tslib_gmenu::writeMenu()
 */
function user_IProcFuncTest($I,$conf)	{
	$itemRow = $conf['parentObj']->menuArr[$I['key']];

		// Setting the document status content to the value of the page title on mouse over
	$I['linkHREF']['onMouseover'].='extraRollover(\''.rawurlencode($itemRow['title']).'\');';
	$conf['parentObj']->I = $I;
	$conf['parentObj']->setATagParts();
	$I = $conf['parentObj']->I;
	if ($I['parts']['ATag_begin'])	$I['parts']['ATag_begin']=$I['A1'];

	if ($conf['debug'])	{
			// Outputting for debug example:
		echo 'ITEM: <h2>'.htmlspecialchars($itemRow['uid'].': '.$itemRow['title']).'</h2>';
		t3lib_div::debug($itemRow);
		t3lib_div::debug($I);
		echo '<hr />';
	}
		// Returns:
	return $I;
}


?>