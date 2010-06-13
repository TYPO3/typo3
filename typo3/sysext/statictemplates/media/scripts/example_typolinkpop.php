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
 * Typo Link PopUp EXAMPLE!
 *
 * $Id: example_typolinkpop.php 5165 2009-03-09 18:28:59Z ohader $
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */














/**
 * Demonstrates how to make typolink tags (<link ...>) open in a pop-up window
 *
 * Example can be found in the testsite package at the page-path "/Intro/TypoScript examples/Small Tricks/Making Pop-up links/"
 * This TypoScript configuration will also demonstrate it:
 *
 * tt_content.text.20.parseFunc.tags.link.typolink.userFunc = user_typoLinkPopUp
 * includeLibs.popup = media/scripts/example_typolinkpop.php
 * config.setJS_openPic = 1
 *
 * page = PAGE
 * page.10 < styles.content.get
 *
 * (Plus the "content (default)" static template included as well)
 *
 * @param	array		In this case: An array with data you can use for processing; keys "url" and "aTagParams" contains something at least
 * @param	array		TypoScript array with custom properties for this function call.
 * @return	string		Return the new <a> tag
 * @see tslib_cObj::typoLink()
 */
function user_typoLinkPopUp($content,$conf)	{
	$aOnClick = 'openPic(\''.$GLOBALS['TSFE']->baseUrlWrap($content['url']).'\',\'popupwin\',\'width=400,height=500,status=0,menubar=0\'); return false;';
	$TAG = 	'<a href="#" onclick="'.htmlspecialchars($aOnClick).'"'.$content['aTagParams'].'>';
	return $TAG;
}


?>