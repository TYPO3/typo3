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
 * Creates a language-selector menu with three flags, an english, a danish and a german flag for each language supported on the site.
 *
 * THIS IS AN EXAMPLE designed to work with the official TYPO3 testsite, section "Another site in the ..."
 * You will have to program a similar menu for your own case.
 *
 * $Id: example_languageMenu.php 5165 2009-03-09 18:28:59Z ohader $
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */


if (!is_object($this)) die ('Error: No parent object present.');




 // First, select all pages_language_overlay records on the current page. Each represents a possibility for a language.
$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages_language_overlay', 'pid='.intval($GLOBALS['TSFE']->id).$GLOBALS['TSFE']->sys_page->enableFields('pages_language_overlay'), 'sys_language_uid');

$langArr = array();
while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
	$langArr[$row['sys_language_uid']] = $row['title'];
}

// Little red arrow, which is inserted to the left of the flag-icon if the TSFE->sys_language_uid equals the language uid (notice that 0=english, 1=danish and 2=german is SPECIFIC to this database, because these numbers refer to uid's of the table sys_language)
$pointer = '<img src="'.t3lib_extMgm::siteRelPath('cms').'tslib/media/icons_misc/content_client.gif" width="7" height="10" align="middle" alt="" />';

// Set each icon. If the language is the current, red arrow is printed to the left. If the language is NOT found (represented by a pages_language_overlay record on this page), the icon is dimmed.
$flags = array();
$flags[] = ($GLOBALS['TSFE']->sys_language_uid==0?$pointer:'').'<a href="'.htmlspecialchars('index.php?id='.$GLOBALS['TSFE']->id.'&L=0').'" target="_top"><img src="typo3/sysext/cms/tslib/media/uploads/flag_uk.gif" width="21" height="13" hspace="5" border="0" alt="" /></a>';
$flags[] = ($GLOBALS['TSFE']->sys_language_uid==1?$pointer:'').'<a href="'.htmlspecialchars('index.php?id='.$GLOBALS['TSFE']->id.'&L=1').'" target="_top"><img src="typo3/sysext/cms/tslib/media/uploads/flag_dk'.($langArr[1]?'':'_d').'.gif" width="21" height="13" hspace="5" border="0" alt="" /></a>';
$flags[] = ($GLOBALS['TSFE']->sys_language_uid==2?$pointer:'').'<a href="'.htmlspecialchars('index.php?id='.$GLOBALS['TSFE']->id.'&L=2').'" target="_top"><img src="typo3/sysext/cms/tslib/media/uploads/flag_de'.($langArr[2]?'':'_d').'.gif" width="21" height="13" hspace="5" border="0" alt="" /></a>';

// Make the little menu. Notice, the menu does ONLY transfer the page-id and the "L" variable (which is also SPECIFIC for this website because "L" has been used in the extension template used to control the language setup)
$content = '<table border="0" cellpadding="0" cellspacing="0"><tr><td><img src="clear.gif" width="30" height="1" alt="" /></td><td>'.implode('',$flags).'</td></tr></table>';

?>