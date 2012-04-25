<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Georg Ringer <typo3@ringerge.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Hook for the list module
 *
 * @package TYPO3
 * @subpackage sys_note
 * @author Georg Ringer <typo3@ringerge.org>
 */
class Tx_SysNote_Hooks_RecordList {

	/**
	 * Add sys_notes as additional content to the footer of the list module
	 *
	 * @param array $params
	 * @param SC_db_list $parentObject
	 * @return string
	 */
	public function render(array $params = array(), SC_db_list $parentObject) {
		$renderer = t3lib_div::makeInstance('Tx_SysNote_SysNote');

		$sysNotes = $renderer->renderByPid($parentObject->id);
		return $sysNotes;
	}
}

?>