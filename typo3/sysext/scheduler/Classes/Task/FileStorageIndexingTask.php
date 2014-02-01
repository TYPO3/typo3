<?php
namespace TYPO3\CMS\Scheduler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Steffen Ritter <steffen.ritter@typo3.org>
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
 * This task tries to find changes in storage and writes them back to DB
 *
 */
class FileStorageIndexingTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * Storage Uid
	 *
	 * @var integer
	 */
	public $storageUid = -1;

	/**
	 * Function execute from the Scheduler
	 *
	 * @return boolean TRUE on successful execution, FALSE on error
	 */
	public function execute() {
		$success = FALSE;
		if ((int)$this->storageUid > 0) {
			$storage = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getStorageObject($this->storageUid);
			$storage->setEvaluatePermissions(FALSE);
			$indexer = $this->getIndexer($storage);
			try {
				$indexer->processChangesInStorages();
				$success = TRUE;
			} catch (\Exception $e) {
				$success = FALSE;
			}
			$storage->setEvaluatePermissions(TRUE);
		}
		return $success;
	}

	/**
	 * Gets the indexer
	 *
	 * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storage
	 * @return \TYPO3\CMS\Core\Resource\Index\Indexer
	 */
	protected function getIndexer(\TYPO3\CMS\Core\Resource\ResourceStorage $storage) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\Indexer', $storage);
	}


}
