<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Keywords menu
 * Can be used with the HMENU cObject, special property set to "userdefined".
 *
 * The "testsite" package has an example of this script in the page-path "/Intro/TypoScript examples/Menu object examples/Keywords-Updated/"
 * TypoScript code that will demonstrate it could look like:
 *  
 * page = PAGE
 * page.typeNum = 0
 * page.10 = HMENU
 * page.10.special= userdefined
 * page.10.special.file = media/scripts/makeMenu_keywords_updated.inc
 * page.10.1 = TMENU
 * page.10.1.NO {
 *   linkWrap = <b>|</b><hr />
 * }
 *  
 *
 * $Id$
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 *
 * @author	Stephan Niebrügge <stephan.niebruegge@gmx.de>
 * Adapted by Kasper Skaarhoj
 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=358&cHash=5400c1c06a
 */
 

if (!is_object($this)) die ('Error: No parent object present.');





$altSortFieldValue = trim($this->mconf['alternativeSortingField']);

list($value)=t3lib_div::intExplode(',',$conf['value']);
if (!$value) {
	$value=$GLOBALS['TSFE']->page['uid'];
}
if ($conf['setKeywords'] || $conf['setKeywords.']) {
	$kw = $this->parent_cObj->stdWrap($conf['setKeywords'], $conf['setKeywords.']);
	} else {
		$value_rec=$this->sys_page->getPage($value);	// The page record of the 'value'.
	$kw = trim(tslib_cObj::keywords($value_rec['keywords']));		// keywords.
	}

$mode = $conf['mode'];	// *'auto', 'manual', 'tstamp'
switch($mode)	{
	case 'starttime':
		$sortField = 'starttime';
	break;						
	case 'lastUpdated':
	case 'manual':
		$sortField = 'lastUpdated';
	break;
	case 'tstamp':
		$sortField = 'tstamp';
	break;
	case 'crdate':
		$sortField = 'crdate';
	break;						
	default:
		$sortField = 'SYS_LASTCHANGED';
	break;
}

	// depth, limit, extra where
if (t3lib_div::testInt($conf['depth']))	{
	$depth = t3lib_div::intInRange($conf['depth'],0,20);		// Tree depth
} else {
	$depth=20;
}				
$limit = t3lib_div::intInRange($conf['limit'],0,100);	// max number of items
$extraWhere = ' AND pages.uid!='.$value.' AND pages.doktype!=5';
if ($conf['excludeNoSearchPages']) {
	$extraWhere.= ' AND pages.no_search=0';
}
	// start point
$eLevel = tslib_cObj::getKey (intval($conf['entryLevel']),$this->tmpl->rootLine);
$startUid = intval($this->tmpl->rootLine[$eLevel]['uid']);

	// which field is for keywords
$kfield = 'keywords';
if ( $conf['keywordsField'] ) {
	list($kfield) = explode(' ',trim ($conf['keywordsField']));
}	

	// If there are keywords and the startuid is present.
if ($kw && $startUid)	{
	$bA = t3lib_div::intInRange($conf['beginAtLevel'],0,100);
	$id_list=tslib_cObj::getTreeList($startUid,$depth-1+$bA,$bA-1).($bA?0:$startUid);

	$kwArr = explode(',',$kw);
	reset($kwArr);
	while(list(,$word)=each($kwArr))	{
		$word = trim($word);
		if ($word)	{
			$keyWordsWhereArr[]=$kfield.' LIKE "%'.addslashes($word).'%"';
		}
	}
	$query = $GLOBALS['TSFE']->cObj->getQuery('pages',Array('pidInList'=>'0', 'uidInList'=>$id_list, 'where'=>'('.implode($keyWordsWhereArr,' OR ').')'.$extraWhere, 'orderBy'=>($altSortFieldValue ? $altSortFieldValue : $sortField.' desc'),'max'=>$limit));
}

if ($value=='') {
	$value=$GLOBALS['TSFE']->page['uid'];
}
$items=t3lib_div::intExplode(',',$value);
if (t3lib_div::testInt($conf['depth']))	{
	$depth = t3lib_div::intInRange($conf['depth'],1,20);		// Tree depth
} else {
	$depth=20;
}				
$limit = t3lib_div::intInRange($conf['limit'],0,100);	// max number of items
$maxAge = intval(tslib_cObj::calc($conf['maxAge']));
if (!$limit)	$limit=10;
$mode = $conf['mode'];	// *'auto', 'manual', 'tstamp'
	// Get id's
$id_list_arr = Array();
reset($items);
while(list(,$id)=each($items))	{
	$bA = t3lib_div::intInRange($conf['beginAtLevel'],0,100);
	$id_list_arr[]=tslib_cObj::getTreeList($id,$depth-1+$bA,$bA-1).($bA?0:$id);
}
$id_list = implode($id_list_arr, ',');
	// Get sortField (mode)
switch($mode)	{
	case 'starttime':
		$sortField = 'starttime';
	break;						
	case 'lastUpdated':
	case 'manual':
		$sortField = 'lastUpdated';
	break;
	case 'tstamp':
		$sortField = 'tstamp';
	break;
	case 'crdate':
		$sortField = 'crdate';
	break;						
	default:
		$sortField = 'SYS_LASTCHANGED';
	break;
}
	// Get 
$extraWhere = ' AND pages.doktype!=5';

if ($conf['excludeNoSearchPages']) {
	$extraWhere.= ' AND pages.no_search=0';
}
if ($maxAge>0)	{
	$extraWhere.=' AND '.$sortField.'>'.($GLOBALS['SIM_EXEC_TIME']-$maxAge);
}

// query from keywords - temporary:
$keywordWhere = $query;
if ($keywordWhere > '')
{
$keywordWhere = strstr($keywordWhere, 'uid');
$keywordWhere = ' OR (' . substr($keywordWhere,0,strpos($keywordWhere, 'AND pages.doktype')) . ')' ;
}	

$query = $GLOBALS['TSFE']->cObj->getQuery('pages',Array('pidInList'=>'0', 'uidInList'=>$id_list, 'where'=>$sortField.'>=0'.$extraWhere, 'orderBy'=>($altSortFieldValue ? $altSortFieldValue : $sortField.' desc'),'max'=>$limit));

// Now a combination from the keyword-query and update-query - this can be also in one line; but here I understand it better:
$query1 = substr($query,0,strpos($query, 'uid'));
$query2 = ' (';
$query3 = strstr($query, 'uid');
$query3 = '(' . substr($query3,0,strpos($query3, 'AND pages.doktype')) . ')';
$query4 = ') ';
$query5 = strstr($query, 'AND pages.doktype');
$query = $query1.$query2.$query3.$keywordWhere.$query4.$query5;


$res = mysql(TYPO3_db, $query);

// How much entries in the array ?
$pieces = 0;

while ($row = mysql_fetch_assoc($res))	{
	$menuItemsArray[$row['uid']]=$GLOBALS['TSFE']->sys_page->getPageOverlay($row);
	$pieces = $pieces + 1;
}

// Count of entries in the array:
$GLOBALS['TSFE']->LISTUS=$pieces;

?>