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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordData;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;
use TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandlerInterface;

/**
 * Generate data for table tx_styleguide_inline_mngroup
 *
 * @internal
 */
final class InlineMnGroup extends AbstractTableHandler implements TableHandlerInterface
{
    /**
     * @var string Table name to match
     */
    protected $tableName = 'tx_styleguide_inline_mngroup';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly RecordData $recordData,
        private readonly RecordFinder $recordFinder,
        private readonly Context $context,
    ) {}

    /**
     * Create 1 main row, 4 child child rows, add 2 child child rows in mn
     *
     * @param string $tableName
     * @throws \TYPO3\CMS\Styleguide\TcaDataGenerator\Exception
     */
    public function handle(string $tableName): void
    {
        $pidOfMainTable = $this->recordFinder->findPidOfMainTableRecord($tableName);

        $childRelationUids = [];
        $numberOfChildRelationsToCreate = 2;
        $numberOfChildRows = 4;
        for ($i = 0; $i < $numberOfChildRows; $i++) {
            $fieldValues = [
                'pid' => $pidOfMainTable,
                'tstamp' => $this->context->getAspect('date')->get('timestamp'),
                'crdate' => $this->context->getAspect('date')->get('timestamp'),
            ];
            $connection = $this->connectionPool->getConnectionForTable('tx_styleguide_inline_mngroup_child');
            $connection->insert('tx_styleguide_inline_mngroup_child', $fieldValues);
            $fieldValues['uid'] = $connection->lastInsertId();
            if (count($childRelationUids) < $numberOfChildRelationsToCreate) {
                $childRelationUids[] = $fieldValues['uid'];
            }
            $fieldValues = $this->recordData->generate('tx_styleguide_inline_mngroup_child', $fieldValues);
            // Do not update primary identifier uid anymore, db's choke on that for good reason
            $updateValues = $fieldValues;
            unset($updateValues['uid']);
            $connection->update(
                'tx_styleguide_inline_mngroup_child',
                $updateValues,
                [ 'uid' => (int)$fieldValues['uid'] ]
            );
        }

        $fieldValues = [
            'pid' => $pidOfMainTable,
            'tstamp' => $this->context->getAspect('date')->get('timestamp'),
            'crdate' => $this->context->getAspect('date')->get('timestamp'),
            'inline_1' => $numberOfChildRelationsToCreate,
        ];
        $this->connectionPool
            ->getConnectionForTable($tableName)
            ->insert($tableName, $fieldValues);
        $parentid = $fieldValues['uid'] = $connection->lastInsertId();
        $fieldValues = $this->recordData->generate($tableName, $fieldValues);
        // Do not update primary identifier uid anymore, db's choke on that for good reason
        $updateValues = $fieldValues;
        unset($updateValues['uid']);
        $connection->update(
            $tableName,
            $updateValues,
            [ 'uid' => (int)$fieldValues['uid'] ]
        );

        $this->generateTranslatedRecords($tableName, $fieldValues);

        foreach ($childRelationUids as $uid) {
            $mmFieldValues = [
                'pid' => $pidOfMainTable,
                'tstamp' => $this->context->getAspect('date')->get('timestamp'),
                'crdate' => $this->context->getAspect('date')->get('timestamp'),
                'parentid' => $parentid,
                'childid' => $uid,
            ];
            $this->connectionPool
                ->getConnectionForTable('tx_styleguide_inline_mngroup_mm')
                ->insert('tx_styleguide_inline_mngroup_mm', $mmFieldValues);
        }
    }
}
