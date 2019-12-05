<?php
namespace TYPO3\CMS\Scheduler\Task;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * This task tries to find changes in storage and writes them back to DB
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class FileStorageIndexingTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * Storage Uid
     *
     * @var int
     */
    public $storageUid = -1;

    /**
     * Function execute from the Scheduler
     *
     * @return bool TRUE on successful execution, FALSE on error
     */
    public function execute()
    {
        if ((int)$this->storageUid > 0) {
            $storage = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getStorageObject($this->storageUid);
            $currentEvaluatePermissionsValue = $storage->getEvaluatePermissions();
            $storage->setEvaluatePermissions(false);
            $indexer = $this->getIndexer($storage);
            $indexer->processChangesInStorages();
            $storage->setEvaluatePermissions($currentEvaluatePermissionsValue);
        }
        return true;
    }

    /**
     * Gets the indexer
     *
     * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storage
     * @return \TYPO3\CMS\Core\Resource\Index\Indexer
     */
    protected function getIndexer(\TYPO3\CMS\Core\Resource\ResourceStorage $storage)
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Index\Indexer::class, $storage);
    }
}
