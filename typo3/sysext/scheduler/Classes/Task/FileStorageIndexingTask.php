<?php

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

namespace TYPO3\CMS\Scheduler\Task;

use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This task tries to find changes in storage and writes them back to DB
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class FileStorageIndexingTask extends AbstractTask
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
            $storage = GeneralUtility::makeInstance(StorageRepository::class)->findByUid($this->storageUid);
            if ($storage === null) {
                throw new \RuntimeException(self::class . ' misconfiguration: "Storage to index" must be an existing storage.', 1615020908);
            }
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
    protected function getIndexer(ResourceStorage $storage)
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }
}
