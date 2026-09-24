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

namespace TYPO3\CMS\IndexedSearch\Hook;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\IndexedSearch\Service\RecordIndexer;

/**
 * DataHandler hook to keep the index up to date when records are changed
 * in the backend, as configured by indexing configurations of type "Database records".
 *
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
final readonly class RecordIndexingDataHandlerHook
{
    public function __construct(
        private RecordIndexer $recordIndexer,
        private RecordFactory $recordFactory,
    ) {}

    public function processDatamap_afterDatabaseOperations(string $status, string $table, string|int $id, array $fieldArray, DataHandler $dataHandler): void
    {
        if ($fieldArray === []) {
            return;
        }
        if ($status === 'new') {
            $id = $dataHandler->substNEWwithIDs[$id] ?? 0;
        }
        $row = BackendUtility::getRecord($table, (int)$id);
        if ($row === null || (int)($row['t3ver_wsid'] ?? 0) > 0) {
            return;
        }
        $record = $this->recordFactory->createFromDatabaseRow($table, $row);
        if (!$record instanceof Record) {
            return;
        }

        if ($table === 'pages' && $status === 'update' && (($fieldArray['hidden'] ?? 0) || ($fieldArray['no_search'] ?? 0))) {
            $this->recordIndexer->removePage($record->getUid());
        }

        foreach ($this->recordIndexer->findIndexOnChangeConfigurations($record) as $configuration) {
            if ($record->getSystemProperties()?->isDisabled()) {
                $this->recordIndexer->removeRecord($record, $configuration);
                continue;
            }
            $this->recordIndexer->indexRecord($record, $configuration);
        }
    }

    public function processCmdmap_deleteAction(string $table, int|string $id, array $recordToDelete, bool &$recordWasDeleted, DataHandler $dataHandler): void
    {
        if ($table === 'pages') {
            $this->recordIndexer->removePage((int)$id);
            return;
        }
        if ((int)($recordToDelete['t3ver_wsid'] ?? 0) > 0) {
            return;
        }
        $record = $this->recordFactory->createFromDatabaseRow($table, $recordToDelete);
        foreach ($this->recordIndexer->findIndexOnChangeConfigurations($record) as $configuration) {
            $this->recordIndexer->removeRecord($record, $configuration);
        }
    }
}
