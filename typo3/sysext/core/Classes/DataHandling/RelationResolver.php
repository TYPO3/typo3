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

namespace TYPO3\CMS\Core\DataHandling;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Domain\Persistence\GreedyDatabaseBackend;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Finds relations for a RelationalFieldType field such as:
 * - inline
 * - select with foreign_table or MM
 * - group with allowed or MM
 * - category
 * - file
 *
 * Files are handled differently with specific File / FileReference objects
 * instead of general Record objects. Therefore, resolveFileReferences() is used.
 *
 * What it does to the outside world:
 * - You have a record and a field with relations and you get a collection of the related raw DB records.
 *
 * What it hides:
 * - How the DB queries are made.
 *
 * The result is usually wrapped in a Closure, so it is only called when needed,
 * however this piece of code does not care about how it is used.
 *
 * @internal not part of public API, as this needs to be streamlined and proven.
 */
readonly class RelationResolver
{
    public function __construct(
        #[Autowire(service: 'cache.runtime')]
        protected FrontendInterface $runtimeCache,
        protected ResourceFactory $resourceFactory,
        protected GreedyDatabaseBackend $greedyDatabaseBackend
    ) {}

    /**
     * This method currently returns an array with "table" and "row" pairs,
     * but will probably return something else in the future.
     *
     * @return list<array{table: string, row: array<string, mixed>}>
     */
    public function resolve(RecordInterface $record, FieldTypeInterface $fieldInformation, Context $context): array
    {
        $sortedAndGroupedIds = $this->getGroupedRelationIds($record, $fieldInformation, $context);

        $groupedByTable = [];
        // group sorted items by table
        foreach ($sortedAndGroupedIds as $item) {
            $groupedByTable[$item['table']][] = (int)$item['id'];
        }
        $unorderedRows = $this->getRelationalRows($groupedByTable, $context);
        $sortedRows = [];
        // Sort the relation rows based on the field value
        foreach ($sortedAndGroupedIds as $item) {
            if (isset($unorderedRows[$item['table']][(int)$item['id']])) {
                $sortedRows[] = $unorderedRows[$item['table']][(int)$item['id']];
            }
        }
        return $sortedRows;
    }

    /**
     * @return FileReference[]
     */
    public function resolveFileReferences(RecordInterface $record, FieldTypeInterface $fieldInformation, Context $context): array
    {
        $sortedAndGroupedIds = $this->getGroupedRelationIds($record, $fieldInformation, $context);
        $sortedFileReferenceIds = array_map(static fn(array $item) => (int)$item['id'], $sortedAndGroupedIds);
        $unorderedRows = $this->greedyDatabaseBackend->getRows('sys_file_reference', $sortedFileReferenceIds, $context);
        $unorderedRowsByUid = [];
        foreach ($unorderedRows as $row) {
            $unorderedRowsByUid[(int)$row['uid']] = $row;
        }
        $fileReferenceObjects = [];
        foreach ($sortedAndGroupedIds as $item) {
            if (isset($unorderedRowsByUid[(int)$item['id']])) {
                $fileReferenceRow = $unorderedRowsByUid[(int)$item['id']];
                $fileReferenceObjects[] = $this->resourceFactory->createFileReferenceObject($fileReferenceRow);
            }
        }
        return $fileReferenceObjects;
    }

    /**
     * We currently use the RelationHandler to resolve all records attached to a given field.
     * @todo This will be replaced by querying the RefIndex directly in the future.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getGroupedRelationIds(RecordInterface $record, FieldTypeInterface $fieldInformation, Context $context): array
    {
        $rawRecord = $record instanceof Record ? $record->getRawRecord() : $record;
        $recordData = $rawRecord->toArray();
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->setWorkspaceId($context->getPropertyFromAspect('workspace', 'id', 0));
        if ($rawRecord instanceof RawRecord && $rawRecord->getComputedProperties()->getLocalizedUid() > 0) {
            $relationHandler->initializeForField($record->getMainType(), $fieldInformation, $rawRecord->getComputedProperties()->getLocalizedUid(), $recordData[$fieldInformation->getName()] ?? null);
        } else {
            $relationHandler->initializeForField($record->getMainType(), $fieldInformation, $recordData, $recordData[$fieldInformation->getName()] ?? null);
        }
        $relationHandler->processDeletePlaceholder();
        return $relationHandler->itemArray;
    }

    /**
     * Find the relations relevant for this field. This could be multiple tables!
     *
     * Note: While $necessaryRelationsOfRequestedField is sorted, the result will be the plain unsorted database rows.
     *
     * @return array<string, array<int, array{table: string, row: array<string, mixed>}>>
     */
    protected function getRelationalRows(array $necessaryRelationsOfRequestedField, Context $context): array
    {
        $finalRows = [];
        foreach ($necessaryRelationsOfRequestedField as $dbTable => $uids) {
            // Let's loop over all tables, and fetch all records of the PIDs of the given UIDs in a greedy way
            $rows = $this->greedyDatabaseBackend->getRows($dbTable, $uids, $context);
            foreach ($rows as $row) {
                $finalRows[$dbTable][(int)$row['uid']] = [
                    'table' => $dbTable,
                    'row' => $row,
                ];
            }
        }
        return $finalRows;
    }
}
