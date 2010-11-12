<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Update extension list task
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage em
 */
class tx_em_Tasks_UpdateExtensionList extends tx_scheduler_Task {
	/**
	 * Public method, usually called by scheduler.
	 *
	 * @return boolean True on success
	 */
	public function execute() {
		// Throws exceptions if something goes wrong
		$this->updateExtensionlist();

		return (TRUE);
	}

	/**
	 * Update extension list
	 *
	 * @throws tx_em_ConnectionException if fetch from mirror fails
	 * @return void
	 */
	protected function updateExtensionlist() {

			// get repositories
		$repositories = tx_em_Database::getRepositories();
		if (!is_array($repositories)) {
			return;
		}

			// update all repositories
		foreach ($repositories as $repository) {
			$objRepository = t3lib_div::makeInstance('tx_em_Repository', $repository['uid']);
			$objRepositoryUtility = t3lib_div::makeInstance('tx_em_Repository_Utility', $objRepository);
			$count = $objRepositoryUtility->updateExtList(FALSE);
			unset($objRepository, $objRepositoryUtility);
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/em/classes/tasks/class.tx_em_tasks_updateextensionlist.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/em/classes/tasks/class.tx_em_tasks_updateextensionlist.php']);
}

?>
