<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Tools for scripts using the eID feature of index.php
 * Included from index_ts.php
 * Since scripts using the eID feature does not
 * have a full FE environment initialized by default
 * this class seeks to provide functions that can
 * initialize parts of the FE environment as needed,
 * eg. Frontend User session, Database connection etc.
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   74: class tslib_eidtools
 *   81:     function initFeUser()
 *  108:     function connectDB()
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */














/**
 * Tools for scripts using the eID feature of index.php
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */
class tslib_eidtools {

	/**
	 * Load and initialize Frontend User
	 *
	 * @return	object		Frontend User object (usually known as TSFE->fe_user)
	 */
	public static function initFeUser()	{
			// Include classes necessary for initializing frontend user:
			// We will use tslib_fe to do that:
		require_once(PATH_tslib.'class.tslib_fe.php');
		require_once(PATH_t3lib.'class.t3lib_cs.php');
		require_once(PATH_t3lib.'class.t3lib_userauth.php');
		require_once(PATH_tslib.'class.tslib_feuserauth.php');

			// Make new instance of TSFE object for initializing user:
		$temp_TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
		$TSFE = new $temp_TSFEclassName($GLOBALS['TYPO3_CONF_VARS'],0,0);
		$TSFE->connectToDB();

			// Initialize FE user:
		$TSFE->initFEuser();

			// Return FE user object:
		return $TSFE->fe_user;
	}

	/**
	 * Connecting to database
	 *
	 * @return	void
	 */
	public static function connectDB()	{
		/* @var $TYPO3_DB t3lib_db */
		$GLOBALS['TYPO3_DB']->connectDB();
	}
}

?>