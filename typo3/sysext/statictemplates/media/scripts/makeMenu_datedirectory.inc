<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Date directory
 * Can be used with the HMENU cObject, special property set to "userdefined".
 *
 * $Id: makeMenu_datedirectory.inc 5165 2009-03-09 18:28:59Z ohader $
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 *
 * @author	Olaf.Schuemann@erz.be.ch
 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=358&cHash=5400c1c06a
 * @see makeMenu_keywords_updated.inc
 */


if (!is_object($this)) die ('Error: No parent object present.');





$day = $conf['day'];
$name =	$conf['name'];
$menuItemsArray = array();

if ($day==-1) $day=date('w');
if ($day==date('w')) {
	$son=mktime(0,0,0,date('m'),date('d'),date('Y'));
} else {
	for ($i=1;;$i++) {
		$son=mktime(0,0,0,date('m'),date('d')-$i,date('Y'));
		if ($day==date('w',$son)) break;
		if ($i>100) break;	// Security that it's not an eternal loop.
	}
}

$date = date('Y-m-d', $son);
$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'title LIKE '.$GLOBALS['TYPO3_DB']->fullQuoteStr($name.$date, 'pages'));  // get the uid of the page
$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

$value=$row['uid'];

if ($value=='') {
	$value=$GLOBALS['TSFE']->page['uid'];
}
$items=t3lib_div::intExplode(',',$value);
foreach ($items as $id) {
	$res = $GLOBALS['TSFE']->cObj->exec_getQuery('pages',Array('pidInList'=>$id,'orderBy'=>$altSortField));
	while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))        {
		$menuItemsArray[]=$row;
	}
}

?>