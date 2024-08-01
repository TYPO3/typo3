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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordData;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordDataAwareInterface;

/**
 * Generate data for type=inline fields.
 * Special implementation for inline_1 of
 * tx_styleguide_inline_usecombination and tx_styleguide_inline_usecombinationbox
 *
 * @internal
 */
final class TypeInlineUsecombination extends AbstractFieldGenerator implements FieldGeneratorInterface, RecordDataAwareInterface
{
    protected array $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'inline',
                // additional check for foreign_table in check method
                'foreign_field' => 'select_parent',
                'foreign_selector' => 'select_child',
                'foreign_unique' => 'select_child',
            ],
        ],
    ];

    private ?RecordData $recordData = null;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function setRecordData(RecordData $recordData): void
    {
        $this->recordData = $recordData;
    }

    /**
     * Check for tx_styleguide_inline_usecombination and
     * tx_styleguide_inline_usecombinationbox table
     */
    public function match(array $data): bool
    {
        $match = parent::match($data);
        if ($match) {
            if ($data['fieldConfig']['config']['foreign_table'] !== 'tx_styleguide_inline_usecombination_mm'
                && $data['fieldConfig']['config']['foreign_table'] !== 'tx_styleguide_inline_usecombinationbox_mm'
            ) {
                $match = false;
            }
        }
        return $match;
    }

    /**
     * Generate 4 "child child" rows, connect 2 of them in mm table
     */
    public function generate(array $data): int
    {
        if ($this->recordData === null) {
            throw new \RuntimeException('Not initialized. Call setRecordData() first.', 1726780934);
        }
        if (!isset($GLOBALS['TCA'][$data['fieldConfig']['config']['foreign_table']]['columns']['select_child']['config']['foreign_table'])) {
            throw new \RuntimeException(
                'mm child table name not found',
                1459941569
            );
        }
        $childChildTableName = $GLOBALS['TCA'][$data['fieldConfig']['config']['foreign_table']]['columns']['select_child']['config']['foreign_table'];
        $numberOfChildChildRowsToCreate = 4;
        $uidsOfChildrenToConnect = [];
        for ($i = 0; $i < $numberOfChildChildRowsToCreate; $i++) {
            // Insert an empty row again to have the uid already. This is useful for
            // possible further inline that may be attached to this child.
            $childFieldValues = [
                'pid' => $data['fieldValues']['pid'],
            ];
            $connection = $this->connectionPool->getConnectionForTable($childChildTableName);
            $connection->insert($childChildTableName, $childFieldValues);
            $childFieldValues['uid'] = $connection->lastInsertId();
            if (count($uidsOfChildrenToConnect) < 2) {
                $uidsOfChildrenToConnect[] = $childFieldValues['uid'];
            }
            $childFieldValues = $this->recordData->generate($childChildTableName, $childFieldValues);
            $connection->update(
                $childChildTableName,
                $childFieldValues,
                [ 'uid' => (int)$childFieldValues['uid'] ]
            );
        }
        foreach ($uidsOfChildrenToConnect as $uid) {
            $mmFieldValues = [
                'pid' => $data['fieldValues']['pid'],
                'select_parent' => $data['fieldValues']['uid'],
                'select_child' => $uid,
            ];
            $tableName = $data['fieldConfig']['config']['foreign_table'];
            $connection = $this->connectionPool->getConnectionForTable($tableName);
            $connection->insert($tableName, $mmFieldValues);
        }
        return count($uidsOfChildrenToConnect);
    }
}
