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
 * USER cObject EXAMPLE FILE
 *
 * This is an example of how to use your own functions and classes directly from TYPO3.
 * Used in the "testsite" package
 *
 * $Id: example_callfunction.php 5165 2009-03-09 18:28:59Z ohader $
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */







/**
 * Call custom function from TypoScript for data processing
 *
 * Example can be found in the testsite package at the page-path "/Intro/TypoScript examples/Custom Dynamic Co.../Passing a string.../"
 * This TypoScript configuration will also demonstrate it:
 *
 * includeLibs.something = media/scripts/example_callfunction.php
 * page = PAGE
 * page.10 = TEXT
 * page.10 {
 *     value = Hello World
 *     preUserFunc = user_reverseString
 *     preUserFunc.uppercase = 1
 * }
 *
 * @param	string		When custom functions are used for data processing the $content variable will hold the value to be processed. When functions are meant to just return some generated content this variable is empty.
 * @param	array		TypoScript properties passed on to this function.
 * @return	string		The input string reversed. If the TypoScript property "uppercase" was set it will also be in uppercase.
 */
function user_reverseString($content,$conf)	{
	$content = strrev($content);
	if ($conf['uppercase'])	{
		$content=strtoupper($content);
	}
	return $content;
}

/**
 * Simply outputting the current time in red letters.
 *
 * Example can be found in the testsite package at the page-path "/Intro/TypoScript examples/Custom Dynamic Co.../Mixing cached and.../"
 * This TypoScript configuration will also demonstrate it:
 *
 * includeLibs.something = media/scripts/example_callfunction.php
 * page = PAGE
 * page.10 = USER_INT
 * page.10 {
 *   userFunc = user_printTime
 * }
 *
 * @param	string		Empty string (no content to process)
 * @param	array		TypoScript configuration
 * @return	string		HTML output, showing the current server time.
 */
function user_printTime($content,$conf)	{
	return '<font color="red">Dynamic time: '.date('H:i:s').'</font><br />';
}



/**
 * Example of calling a method in a PHP class from TypoScript
 *
 */
class user_various	{
	var $cObj;		// Reference to the parent (calling) cObj set from TypoScript

	/**
	 * Doing the same as user_reverseString() but with a class. Also demonstrates how this gives us the ability to use methods in the parent object.
	 *
	 * @param	string		String to process (from stdWrap)
	 * @param	array		TypoScript properties passed on to this method.
	 * @return	string		The input string reversed. If the TypoScript property "uppercase" was set it will also be in uppercase. May also be linked.
	 * @see user_reverseString()
	 */
	function reverseString($content,$conf)	{
		$content = strrev($content);
		if ($conf['uppercase'])	{
			$content=$this->cObj->caseshift($content,'upper');
		}
		if ($conf['typolink'])	{
			$content=$this->cObj->getTypoLink($content,$conf['typolink']);
		}
		return $content;
	}

	/**
	 * Testing USER cObject:
	 *
	 * Example can be found in the testsite package at the page-path "/Intro/TypoScript examples/Custom Dynamic Co.../Calling a method.../"
	 * This TypoScript configuration will also demonstrate it:
	 *
	 * includeLibs.something = media/scripts/example_callfunction.php
	 * page = PAGE
	 * page.30 = USER
	 * page.30 {
	 *   userFunc = user_various->listContentRecordsOnPage
	 *   reverseOrder = 1
	 * }
	 *
	 * @param	string		Empty string (no content to process)
	 * @param	array		TypoScript configuration
	 * @return	string		HTML output, showing content elements (in reverse order if configured.)
	 */
	function listContentRecordsOnPage($content,$conf)	{
		$query = $GLOBALS['TYPO3_DB']->SELECTquery(
						'header',
						'tt_content',
						'pid='.intval($GLOBALS['TSFE']->id).$this->cObj->enableFields('tt_content'),
						'',
						'sorting'.($conf['reverseOrder'] ? ' DESC' : '')
					);
		$output = 'This is the query: <strong>'.$query.'</strong><br /><br />';
		return $output.$this->selectThem($query);
	}

	/**
	 * Selecting the records by input $query and returning the header field values
	 *
	 * @param	string		SQL query selecting the content elements.
	 * @return	string		The header field values of the content elements imploded by a <br /> tag
	 * @access private
	 */
	function selectThem($query)	{
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		$output=array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$output[]=$row['header'];
		}
		return implode($output,'<br />');
	}
}

?>