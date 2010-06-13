<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Oliver Hader <oliver@typo3.org>
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
 * Interface for hook in t3lib_TCEmain::checkModifyAccessList
 *
 * @author	Oliver Hader <oliver@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
interface t3lib_TCEmain_checkModifyAccessListHook {
	/**
	 * Hook that determines whether a user has access to modify a table.
	 *
	 * @param	boolean			&$accessAllowed: Whether the user has access to modify a table
	 * @param 	string			$table: The name of the table to be modified
	 * @param	t3lib_TCEmain	$parent: The calling parent object
	 * @return	void
	 */
	public function checkModifyAccessList(&$accessAllowed, $table, t3lib_TCEmain $parent);
}

?>