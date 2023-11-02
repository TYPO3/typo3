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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordData;

/**
 * Generate data for type=select fields.
 * Special field for select_single_12
 *
 * @internal
 */
final class TypeSelectRenderTypeSingleForeignTable extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=select
     */
    protected $matchArray = [
        'fieldName' => 'select_single_12',
        'fieldConfig' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_elements_select_single_12_foreign',
            ],
        ],
    ];

    /**
     * Returns the generated value to be inserted into DB for this field
     *
     * @param array $data
     * @return string
     */
    public function generate(array $data): string
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tx_styleguide_elements_select_single_12_foreign');
        $childFieldValues = [];
        $childRowsToCreate = 2;
        for ($i = 0; $i < $childRowsToCreate; $i++) {
            // Insert an empty row again to have the uid already. This is useful for
            // possible further inline that may be attached to this child.
            $childFieldValues = [
                'pid' => $data['fieldValues']['pid'],
            ];
            $connection->insert(
                'tx_styleguide_elements_select_single_12_foreign',
                $childFieldValues
            );
            $childFieldValues['uid'] = $connection->lastInsertId('tx_styleguide_elements_select_single_12_foreign');
            $recordData = GeneralUtility::makeInstance(RecordData::class);
            $childFieldValues = $recordData->generate('tx_styleguide_elements_select_single_12_foreign', $childFieldValues);
            $connection->update(
                'tx_styleguide_elements_select_single_12_foreign',
                $childFieldValues,
                [ 'uid' => $childFieldValues['uid'] ]
            );
        }
        return (string)$childFieldValues['uid'];
    }
}
