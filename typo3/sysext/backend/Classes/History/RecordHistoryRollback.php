<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\History;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\History\Event\AfterHistoryRollbackFinishedEvent;
use TYPO3\CMS\Backend\History\Event\BeforeHistoryRollbackStartEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RecordHistoryRollback
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Perform rollback via DataHandler
     * @param string $rollbackFields
     * @param array $diff
     * @param BackendUserAuthentication|null $backendUserAuthentication
     */
    public function performRollback(string $rollbackFields, array $diff, BackendUserAuthentication $backendUserAuthentication = null): void
    {
        $this->eventDispatcher->dispatch(new BeforeHistoryRollbackStartEvent($rollbackFields, $diff, $this, $backendUserAuthentication));
        $rollbackData = explode(':', $rollbackFields);
        $rollbackDataCount = count($rollbackData);
        // PROCESS INSERTS AND DELETES
        // rewrite inserts and deletes
        $commandMapArray = [];
        $data = [];
        if ($diff['insertsDeletes']) {
            if ($rollbackDataCount === 1) {
                // all tables
                $data = $diff['insertsDeletes'];
            } elseif ($rollbackDataCount === 2 && $diff['insertsDeletes'][$rollbackFields]) {
                // one record
                $data[$rollbackFields] = $diff['insertsDeletes'][$rollbackFields];
            }
            if (!empty($data)) {
                foreach ($data as $key => $action) {
                    $elParts = explode(':', $key);
                    if ((int)$action === 1) {
                        // inserted records should be deleted
                        $commandMapArray[$elParts[0]][$elParts[1]]['delete'] = 1;
                        // When the record is deleted, the contents of the record do not need to be updated
                        unset(
                            $diff['oldData'][$key],
                            $diff['newData'][$key]
                        );
                    } elseif ((int)$action === -1) {
                        // deleted records should be inserted again
                        $commandMapArray[$elParts[0]][$elParts[1]]['undelete'] = 1;
                    }
                }
            }
        }
        // Writes the data:
        if ($commandMapArray) {
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->dontProcessTransformations = true;
            $tce->start([], $commandMapArray, $backendUserAuthentication);
            $tce->process_cmdmap();
            unset($tce);
        }
        if (!$diff['insertsDeletes']) {
            // PROCESS CHANGES
            // create an array for process_datamap
            $diffModified = [];
            foreach ($diff['oldData'] as $key => $value) {
                $splitKey = explode(':', $key);
                $diffModified[$splitKey[0]][$splitKey[1]] = $value;
            }
            if ($rollbackDataCount === 1) {
                // all tables
                $data = $diffModified;
            } elseif ($rollbackDataCount === 2) {
                // one record
                $data[$rollbackData[0]][$rollbackData[1]] = $diffModified[$rollbackData[0]][$rollbackData[1]];
            } elseif ($rollbackDataCount === 3) {
                // one field in one record
                $data[$rollbackData[0]][$rollbackData[1]][$rollbackData[2]] = $diffModified[$rollbackData[0]][$rollbackData[1]][$rollbackData[2]];
            }
            // Writes the data:
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->dontProcessTransformations = true;
            $tce->start($data, [], $backendUserAuthentication);
            $tce->process_datamap();
            unset($tce);
        }
        if (isset($data['pages']) || isset($commandMapArray['pages'])) {
            BackendUtility::setUpdateSignal('updatePageTree');
        }
        $this->eventDispatcher->dispatch(new AfterHistoryRollbackFinishedEvent($rollbackFields, $diff, $data, $this, $backendUserAuthentication));
    }
}
