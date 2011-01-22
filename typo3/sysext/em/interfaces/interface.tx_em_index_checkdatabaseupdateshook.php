<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Xavier Perseguers <typo3@perseguers.ch>
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
 * Interface for hook in SC_mod_tools_em_index::checkDBupdates.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @package TYPO3
 * @subpackage em
 */
interface tx_em_Index_CheckDatabaseUpdatesHook {

	/**
	 * Hook that allows pre-processing of database structure modifications.
	 * The hook implementation may return a user form that will temporarily
	 * replace the standard database update form. This allows additional
	 * operations to be performed before the database structure gets updated.
	 *
	 * @param string $extKey: Extension key
	 * @param array $extInfo: Extension information array
	 * @param array $diff: Database differences
	 * @param t3lib_install $instObj: Instance of the installer
	 * @param SC_mod_tools_em_index $parent: The calling parent object
	 * @return string Either empty string or a pre-processing user form
	 */
	public function preProcessDatabaseUpdates($extKey, array $extInfo, array $diff, t3lib_install $instObj, SC_mod_tools_em_index $parent);

}

?>