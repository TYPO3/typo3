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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\History\Event\AfterHistoryRollbackFinishedEvent;
use TYPO3\CMS\Backend\History\Event\BeforeHistoryRollbackStartEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[Autoconfigure(public: true)]
readonly class RecordHistoryRollback
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private TcaSchemaFactory $tcaSchemaFactory,
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * Perform rollback via DataHandler
     */
    public function performRollback(string $rollbackFields, array $diff, ?BackendUserAuthentication $backendUserAuthentication = null): void
    {
        $this->eventDispatcher->dispatch(new BeforeHistoryRollbackStartEvent($rollbackFields, $diff, $this, $backendUserAuthentication));
        $rollbackData = explode(':', $rollbackFields);
        $rollbackDataCount = count($rollbackData);
        // PROCESS INSERTS AND DELETES
        // rewrite inserts and deletes
        $commandMapArray = [];
        $data = [];
        if ($diff['insertsDeletes'] ?? []) {
            if ($rollbackDataCount === 1) {
                // all tables
                $data = $diff['insertsDeletes'];
            } elseif ($rollbackDataCount === 2 && !empty($diff['insertsDeletes'][$rollbackFields])) {
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
        // PROCESS MOVES
        // Moving a record back is a command of its own, the data map below only knows fields
        if ($diff['moves'] ?? []) {
            $moves = [];
            if ($rollbackDataCount === 1) {
                // all tables
                $moves = $diff['moves'];
            } elseif ($rollbackDataCount === 2 && !empty($diff['moves'][$rollbackFields])) {
                // one record
                $moves[$rollbackFields] = $diff['moves'][$rollbackFields];
            }
            foreach ($moves as $key => $previousPosition) {
                [$moveTable, $moveUid] = explode(':', $key);
                if (isset($commandMapArray[$moveTable][$moveUid]['delete'])) {
                    // The record is removed by this rollback, so it must not be moved anywhere
                    continue;
                }
                $target = $this->resolveMoveTarget($moveTable, (int)$moveUid, $previousPosition);
                if ($target !== null) {
                    $commandMapArray[$moveTable][$moveUid]['move'] = $target;
                }
            }
        }
        // Writes the data:
        $correlationId = null;
        if ($commandMapArray) {
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->dontProcessTransformations = true;
            $tce->start([], $commandMapArray, $backendUserAuthentication);
            $tce->process_cmdmap();
            // Both runs are one rollback, so the second one continues with the same correlation id
            $correlationId = $tce->getCorrelationId();
            unset($tce);
        }
        if ($diff['oldData'] ?? false) {
            // PROCESS CHANGES
            // create an array for process_datamap, the command map above used $data for its own shape
            $data = [];
            $diffModified = [];
            foreach ($diff['oldData'] as $key => $value) {
                $splitKey = explode(':', $key);
                $diffModified[$splitKey[0]][$splitKey[1]] = $value;
            }
            if ($rollbackDataCount === 1) {
                // all tables
                $data = $diffModified;
            } elseif ($rollbackDataCount === 2 && isset($diffModified[$rollbackData[0]][$rollbackData[1]])) {
                // one record
                $data[$rollbackData[0]][$rollbackData[1]] = $diffModified[$rollbackData[0]][$rollbackData[1]];
            } elseif ($rollbackDataCount === 3 && isset($diffModified[$rollbackData[0]][$rollbackData[1]][$rollbackData[2]])) {
                // one field in one record
                $data[$rollbackData[0]][$rollbackData[1]][$rollbackData[2]] = $diffModified[$rollbackData[0]][$rollbackData[1]][$rollbackData[2]];
            }
            // Writes the data:
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->dontProcessTransformations = true;
            $tce->start($data, [], $backendUserAuthentication, null, $correlationId);
            $tce->process_datamap();
            unset($tce);
        }
        if (isset($data['pages']) || isset($commandMapArray['pages'])) {
            BackendUtility::setUpdateSignal('updatePageTree');
        }
        $this->eventDispatcher->dispatch(new AfterHistoryRollbackFinishedEvent($rollbackFields, $diff, $data, $this, $backendUserAuthentication));
    }

    /**
     * Where a record has to go to sit at its previous position again. DataHandler takes the uid
     * of a page to put the record on top of it, or the negative uid of the record it should
     * follow. A stored sorting value alone says nothing, it only has a meaning next to the
     * records that are on the page now, so the predecessor is looked up at rollback time.
     *
     * Returns null when the previous position is unknown.
     */
    private function resolveMoveTarget(string $table, int $uid, array $previousPosition): ?int
    {
        $previousPageId = (int)($previousPosition['pid'] ?? -1);
        if ($previousPageId < 0) {
            return null;
        }
        if (!$this->tcaSchemaFactory->has($table)) {
            return null;
        }
        $schema = $this->tcaSchemaFactory->get($table);
        if (!$schema->hasCapability(TcaSchemaCapability::SortByField)) {
            return $previousPageId;
        }
        $sortByFieldName = $schema->getCapability(TcaSchemaCapability::SortByField)->getFieldName();
        if (!isset($previousPosition[$sortByFieldName])) {
            return $previousPageId;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $predecessor = $queryBuilder
            ->select('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($previousPageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->lt($sortByFieldName, $queryBuilder->createNamedParameter((int)$previousPosition[$sortByFieldName], Connection::PARAM_INT))
            )
            ->orderBy($sortByFieldName, 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        // Nothing came before it, so the record was the first one on that page
        return $predecessor === false ? $previousPageId : -(int)$predecessor;
    }
}
