<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Sebastian Kurfuerst (sebastian@garbage-group.de)
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
 * Contains the update class for not in menu pages. Used by the update wizard in the install tool.
 *
 * @author	Sebastian Kurfuerst <sebastian@garbage-group.de
 */
class tx_coreupdates_notinmenu {
	var $versionNumber;	// version number coming from t3lib_div::int_from_ver()
	var $pObj;	// parent object (tx_install)
	var $userInput;	// user input

	function checkForUpdate(&$description)	{
		$description = 'Removes the doctype "Not in menu" which is deprecated and sets the successing page flag "Not in menu" instead.';

		if ($this->versionNumber >= 3009000)	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid','pages','doktype=5');
			if($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
				return 1;
			}
		}
		return 0;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$dbQueries: ...
	 * @param	[type]		$customMessages: ...
	 * @return	[type]		...
	 */
	function performUpdate(&$dbQueries, &$customMessages)	{
		if($this->versionNumber >= 3009000)	{
			$updateArray = array(
				'doktype' => 1,
				'nav_hide' => 1
			);

			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'doktype=5', $updateArray);
			$dbQueries[] = str_replace(chr(10), ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);

			if ($GLOBALS['TYPO3_DB']->sql_error())	{
				return 0;	// something went wrong
			}
			return 1;
		}
	}
}
?>