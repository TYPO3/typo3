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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandler;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordData;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;
use TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandlerInterface;

/**
 * Generate data for table tx_styleguide_inline_mnsymmetric
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final class InlineMnSymmetric extends AbstractTableHandler implements TableHandlerInterface
{
    /**
     * @var string Table name to match
     */
    protected $tableName = 'tx_styleguide_inline_mnsymmetric';

    public function __construct(private readonly RecordData $recordData) {}

    /**
     * Create 4 rows, add row 2 and 3 as branch to row 1
     *
     * @param string $tableName
     */
    public function handle(string $tableName): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        $pidOfMainTable = $recordFinder->findPidOfMainTableRecord($tableName);
        $context = GeneralUtility::makeInstance(Context::class);

        $isFirst = true;
        $numberOfRelationsForFirstRecord = 2;
        $relationUids = [];
        $uidOfFirstRecord = null;
        for ($i = 0; $i < 4; $i++) {
            $fieldValues = [
                'pid' => $pidOfMainTable,
                'tstamp' => $context->getAspect('date')->get('timestamp'),
                'crdate' => $context->getAspect('date')->get('timestamp'),
            ];
            $connection = $connectionPool->getConnectionForTable($tableName);
            $connection->insert($tableName, $fieldValues);
            $fieldValues['uid'] = $connection->lastInsertId();
            if ($isFirst) {
                $fieldValues['branches'] = $numberOfRelationsForFirstRecord;
                $uidOfFirstRecord = $fieldValues['uid'];
            }
            $fieldValues = $this->recordData->generate($tableName, $fieldValues);
            // Do not update primary identifier uid anymore, db's choke on that for good reason
            $updateValues = $fieldValues;
            unset($updateValues['uid']);
            $connection->update(
                $tableName,
                $updateValues,
                [ 'uid' => $fieldValues['uid'] ]
            );

            $this->generateTranslatedRecords($tableName, $fieldValues);

            if (!$isFirst && count($relationUids) < $numberOfRelationsForFirstRecord) {
                $relationUids[] = $fieldValues['uid'];
            }

            $isFirst = false;
        }

        foreach ($relationUids as $uid) {
            $mmFieldValues = [
                'pid' => $pidOfMainTable,
                'tstamp' => $context->getAspect('date')->get('timestamp'),
                'crdate' => $context->getAspect('date')->get('timestamp'),
                'hotelid' => $uidOfFirstRecord,
                'branchid' => $uid,
            ];
            $connection = $connectionPool->getConnectionForTable('tx_styleguide_inline_mnsymmetric_mm');
            $connection->insert(
                'tx_styleguide_inline_mnsymmetric_mm',
                $mmFieldValues
            );
        }
    }
}
