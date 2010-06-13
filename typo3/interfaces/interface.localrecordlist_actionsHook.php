<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Bernhard Kraft  <kraftb@kraftb.at>
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
 * interface for classes which hook into localRecordList and modify clip-icons
 *
 * @author	Bernhard Kraft  <kraftb@kraftb.at>
 * @package TYPO3
 * @subpackage t3lib
 */
interface localRecordList_actionsHook	{

	/**
	 * modifies Web>List clip icons (copy, cut, paste, etc.) of a displayed row
	 *
	 * @param	string		the current database table
	 * @param	array		the current record row
	 * @param	array		the default clip-icons to get modified
	 * @param	object		Instance of calling object
	 * @return	array		the modified clip-icons
	 */
	public function makeClip($table, $row, $cells, &$parentObject);


	/**
	 * modifies Web>List control icons of a displayed row
	 *
	 * @param	string		the current database table
	 * @param	array		the current record row
	 * @param	array		the default control-icons to get modified
	 * @param	object		Instance of calling object
	 * @return	array		the modified control-icons
	 */
	public function makeControl($table, $row, $cells, &$parentObject);


	/**
	 * modifies Web>List header row columns/cells
	 *
	 * @param	string		the current database table
	 * @param	array		Array of the currently displayed uids of the table
	 * @param	array		An array of rendered cells/columns
	 * @param	object		Instance of calling (parent) object
	 * @return	array		Array of modified cells/columns
	 */
	public function renderListHeader($table, $currentIdList, $headerColumns, &$parentObject);


	/**
	 * modifies Web>List header row clipboard/action icons
	 *
	 * @param	string		the current database table
	 * @param	array		Array of the currently displayed uids of the table
	 * @param	array		An array of the current clipboard/action icons
	 * @param	object		Instance of calling (parent) object
	 * @return	array		Array of modified clipboard/action icons
	 */
	public function renderListHeaderActions($table, $currentIdList, $cells, &$parentObject);


}

?>