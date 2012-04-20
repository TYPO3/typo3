<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Lorenz Ulrich <lorenz.ulrich@visol.ch>
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
 * This class provides Scheduler plugin implementation
 *
 * @author Lorenz Ulrich <lorenz.ulrich@visol.ch>
 * @package TYPO3
 * @subpackage media
 */
class tx_scheduler_FileIndexing extends tx_scheduler_Task {
	/**
	 * @var string
	 */
	protected $indexingConfiguration;

	/**
	 * @var string
	 */
	protected $paths;

	/**
	 * Get the value of the protected property indexingConfiguration
	 *
	 * @return string UID of indexing configuration used for the job
	 */
	public function getIndexingConfiguration() {
		return $this->indexingConfiguration;
	}

	/**
	 * Set the value of the private property indexingConfiguration
	 *
	 * @param string $indexingConfiguration UID of indexing configuration used for the job
	 * @return void
	 */
	public function setIndexingConfiguration($indexingConfiguration) {
		$this->indexingConfiguration = $indexingConfiguration;
	}

	/**
	 * Get the value of the protected property paths
	 *
	 * @return string path information for scheduler job (JSON encoded array)
	 */
	public function getPaths() {
		return $this->paths;
	}

	/**
	 * Set the value of the private property paths
	 *
	 * @param array $paths path information for scheduler job (JSON encoded array)
	 * @return void
	 */
	public function setPaths($paths) {
		$this->paths = $paths;
	}

	/**
	 * Function execute from the Scheduler
	 *
	 * @return boolean TRUE on successful execution, FALSE on error
	 */
	public function execute() {
		$successfullyExecuted = TRUE;
		/** @var $fileFactory t3lib_file_Factory */
		$fileFactory = t3lib_div::makeInstance('t3lib_file_Factory');
		/** @var $indexerService t3lib_file_Service_IndexerService */
		$indexerService = t3lib_div::makeInstance('t3lib_file_Service_IndexerService');
		$indexerService->setFactory($fileFactory);

			// run indexing of every storage
		$storageRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_file_storage',
			'deleted = 0'
		);
		foreach ($storageRecords as $storageRecord) {
			$storageObject = $fileFactory->getStorageObject($storageRecord['uid'], $storageRecord);
			$folder = $storageObject->getRootLevelFolder();
			$indexerService->indexFilesInFolder($folder);
		}

		return $successfullyExecuted;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_fileindexing.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_fileindexing.php']);
}

?>