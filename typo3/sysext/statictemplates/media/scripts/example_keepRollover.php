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
 * Demonstrates how to manipulate menu generation so that a click on a menu item will trigger two (or more) frames to load an URL
 * Used in the "testsite" package
 *
 * $Id: example_keepRollover.php 5165 2009-03-09 18:28:59Z ohader $
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */







/**
 * Example can be found in the testsite package at the page-path "/Intro/TypoScript examples/Menu object examples/Loading multiple.../"
 *
 * @param	array		The menu item array, $this->I (in the parent object)
 * @param	array		TypoScript configuration for the function. Notice that the property "parentObj" is a reference to the parent (calling) object (the tslib_Xmenu class instantiated)
 * @return	array		The processed $I array returned (and stored in $this->I of the parent object again)
 * @see tslib_menu::userProcess(), tslib_tmenu::writeMenu(), tslib_gmenu::writeMenu()
 */
function user_keepRolloverAtOnClick($I,$conf)	{
	$itemRow = $conf['parentObj']->menuArr[$I['key']];

		// Setting the document status content to the value of the page title on mouse over
	if (!$I['linkHREF']['TARGET'])	{
		$I['linkHREF']['HREF']='#';
		$I['linkHREF']['onClick'].='ARO_setLocation'.($conf['setLocation']).'('.$itemRow['uid'].',\''.$I['theName'].'\'); return false;';
	} else {
		$I['linkHREF']['onClick'].='ARO_setActiveImg'.'(\''.$I['theName'].'\');';
	}
	if ($I['linkHREF']['onMouseover'])	$I['linkHREF']['onMouseover']='ARO_'.$I['linkHREF']['onMouseover'];
	if ($I['linkHREF']['onMouseout'])	$I['linkHREF']['onMouseout']='ARO_'.$I['linkHREF']['onMouseout'];

	if ($conf['parentObj']->isActive($itemRow['uid']))	{
		$conf['parentObj']->WMextraScript.='
<script type="text/javascript">
	/*<![CDATA[*/
 ARO_Image = "'.$I['theName'].'";
 '.$I['linkHREF']['onMouseover'].'
	/*]]>*/
</script>
		';
	}

		// Update the link in the parent object:
	$conf['parentObj']->I = $I;	// setting internal $I - needed by setATagParts() function!
	$conf['parentObj']->setATagParts();	// Setting the A1 and A2 of the internal $I
	$I = $conf['parentObj']->I;	// retrieving internal $I
	$I['parts']['ATag_begin']=$I['A1'];	// Setting the ATag_begin to the value of this $I

		// Debug:
	if ($conf['debug'])	{
			// Outputting for debug example:
		echo 'ITEM: <h2>'.htmlspecialchars($itemRow['uid'].': '.$itemRow['title']).'</h2>';
		t3lib_div::debug($itemRow);
		t3lib_div::debug($I);
		echo '<hr />';
	}

		// Returns $I:
	return $I;
}

?>