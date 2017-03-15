<?php
declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordData;

/**
 * Generate data for type=inline fields
 */
class TypeInline1n extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=input
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'inline',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ],
        ],
    ];

    /**
     * Additionally check that "foreign_table" is set to something.
     *
     * @param array $data
     * @return bool
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

    /**
     * Returns the generated value to be inserted into DB for this field
     *
     * @param array $data
     * @return string
     */
    public function generate(array $data): string
    {
        $childTable = $data['fieldConfig']['config']['foreign_table'];
        // Insert an empty row again to have the uid already. This is useful for
        // possible further inline that may be attached to this child.
        $childFieldValues = [
            'pid' => $data['fieldValues']['pid'],
            'parentid' => $data['fieldValues']['uid'],
            'parenttable' => $data['tableName'],
        ];
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($childTable);
        $connection->insert($childTable, $childFieldValues);
        $childFieldValues['uid'] = (int)$connection->lastInsertId($childTable);
        $recordData = GeneralUtility::makeInstance(RecordData::class);
        $childFieldValues = $recordData->generate($childTable, $childFieldValues);
        $connection->update(
            $childTable,
            $childFieldValues,
            [ 'uid' => $childFieldValues['uid'] ]
        );
        return (string)1;
    }
}
