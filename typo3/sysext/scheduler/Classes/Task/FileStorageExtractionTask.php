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
 * This task which indexes files in storage
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class FileStorageExtractionTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * Storage Uid
     *
     * @var int
     */
    public $storageUid = -1;

    /**
     * FileCount
     *
     * @var int
     */
    public $maxFileCount = 100;

    /**
     * Function execute from the Scheduler
     *
     * @return bool TRUE on successful execution, FALSE on error
     */
    public function execute()
    {
        $success = false;
        if ((int)$this->storageUid > 0) {
            $storage = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getStorageObject($this->storageUid);
            $storage->setEvaluatePermissions(false);
            $indexer = $this->getIndexer($storage);
            try {
                $indexer->runMetaDataExtraction((int)$this->maxFileCount);
                $success = true;
            } catch (\Exception $e) {
                $success = false;
                $this->logException($e);
            }
            $storage->setEvaluatePermissions(true);
        }
        return $success;
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
