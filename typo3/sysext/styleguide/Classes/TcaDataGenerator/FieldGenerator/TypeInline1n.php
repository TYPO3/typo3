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
 * Generate data for type=inline fields
 *
 * @internal
 */
final class TypeInline1n extends AbstractFieldGenerator implements FieldGeneratorInterface, RecordDataAwareInterface
{
    protected array $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'inline',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
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
     * Additionally check that "foreign_table" is set to something.
     */
    public function match(array $data): bool
    {
        $result = $this->checkMatchArray($data, $this->matchArray);
        if ($result && isset($data['fieldConfig']['config']['foreign_table'])) {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    public function generate(array $data): int
    {
        if ($this->recordData === null) {
            throw new \RuntimeException('Not initialized. Call setRecordData() first.', 1726780936);
        }
        $childTable = $data['fieldConfig']['config']['foreign_table'];
        // Insert an empty row again to have the uid already. This is useful for
        // possible further inline that may be attached to this child.
        $childFieldValues = [
            'pid' => $data['fieldValues']['pid'],
            'parentid' => $data['fieldValues']['uid'],
            'parenttable' => $data['tableName'],
        ];
        if ($data['fieldConfig']['config']['foreign_match_fields']['role'] ?? false) {
            $childFieldValues['role'] = $data['fieldConfig']['config']['foreign_match_fields']['role'];
        }
        $connection = $this->connectionPool->getConnectionForTable($childTable);
        $connection->insert($childTable, $childFieldValues);
        $childFieldValues['uid'] = (int)$connection->lastInsertId();
        $childFieldValues = $this->recordData->generate($childTable, $childFieldValues);
        $connection->update(
            $childTable,
            $childFieldValues,
            [ 'uid' => $childFieldValues['uid'] ]
        );
        return 1;
    }
}
