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
 * Dummy menu of templates/dummy pages on the FreeSite Application.
 *
 * This displays a menu with links to each dummy page set following the uid of the base template to use. Used for preview of the base templates.
 * Required: The Freesite Application (extension: "freesite")
 *
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */


if (!is_object($this)) {
	die ('No cObj object present. This script must be included as a PHP_SCRIPT cObject in TypoScript!');
}

	// Template
$pid = intval($conf['pid_templateArchive']);
$content = '';

$specialComment='';
if ($pid)	{
		// Select templates in root
		// Does NOT take TSFE->showHiddenRecords into account!
	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_template', 'pid='.intval($pid).' AND NOT deleted AND NOT hidden AND NOT starttime AND NOT endtime', '', 'sorting');
	while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
		if (!$firstUID) $firstUID = $row['uid'];
		$key = $row['uid'];
		$val = $row['title'];
		$content.= '<a target="testTemplate" href="'.htmlspecialchars($GLOBALS['TSFE']->absRefPrefix.'index.php?id='.$GLOBALS['TSFE']->id.'&based_on_uid='.$key).'">'.$val.'</a><br />';
		$specialComment.= '[globalVar= based_on_uid='.$key.']'.chr(10);
	}
		// Select subcategories of template folder.
	$page_res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'pid='.intval($pid).' AND NOT deleted AND NOT hidden AND NOT starttime AND NOT endtime AND NOT fe_group', '', 'sorting');
	while($page_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($page_res))	{
			// Subcategory templates
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_template', 'pid='.intval($page_row['uid']).' AND NOT deleted AND NOT hidden AND NOT starttime AND NOT endtime', '', 'sorting');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			if (!$firstUID) $firstUID = $row['uid'];
			$key = $row['uid'];
			$val = $page_row['title'].' / '.$row['title'];
			$content.= '<a target="testTemplate" href="'.htmlspecialchars($GLOBALS['TSFE']->absRefPrefix.'index.php?id='.$GLOBALS['TSFE']->id.'&based_on_uid='.$key).'">'.$val.'</a><br />';
			$specialComment.= '[globalVar= based_on_uid='.$key.']'.chr(10);
		}
	}
}		

$content.='
<!--

NOTE:
When updating the template archive, these TypoScript conditions should replace the current conditions found in the DUMMY PAGE test template:


'.$specialComment.'

-->
';	

?>