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
 * Generate data for type=inline fields.
 * Special implementation for inline_1 of tx_styleguide_inline_expandsingle creates
 * multiple child rows.
 */
class TypeInlineExpandsingle extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=input
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'inline',
                // matches only field inline_1 of tx_styleguide_inline_expandsingle
                'foreign_table' => 'tx_styleguide_inline_expandsingle_child',
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
        return $this->checkMatchArray($data, $this->matchArray);
    }

    /**
     * Generate 3 child rows
     *
     * @param array $data
     * @return string
     */
    public function generate(array $data): string
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tx_styleguide_inline_expandsingle_child');
        $childRowsToCreate = 3;
        for ($i = 0; $i < $childRowsToCreate; $i++) {
            // Insert an empty row again to have the uid already. This is useful for
            // possible further inline that may be attached to this child.
            $childFieldValues = [
                'pid' => $data['fieldValues']['pid'],
                'parentid' => $data['fieldValues']['uid'],
                'parenttable' => $data['tableName'],
            ];
            $connection->insert(
                'tx_styleguide_inline_expandsingle_child',
                $childFieldValues
            );
            $childFieldValues['uid'] = $connection->lastInsertId('tx_styleguide_inline_expandsingle_child');
            $recordData = GeneralUtility::makeInstance(RecordData::class);
            $childFieldValues = $recordData->generate('tx_styleguide_inline_expandsingle_child', $childFieldValues);
            $connection->update(
                'tx_styleguide_inline_expandsingle_child',
                $childFieldValues,
                [ 'uid' => $childFieldValues['uid'] ]
            );
        }
        return (string)$childRowsToCreate;
    }
}
