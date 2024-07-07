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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordData;

/**
 * Generate data for type=select fields.
 * Special field for select_single_21
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final class TypeSelectRenderTypeSingleForeignTableGroupField extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    protected array $matchArray = [
        'fieldName' => 'select_single_21',
        'fieldConfig' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_elements_select_single_21_foreign',
                'foreign_table_item_group' => 'item_group',
            ],
        ],
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly RecordData $recordData,
    ) {}

    /**
     * Returns the generated value to be inserted into DB for this field
     */
    public function generate(array $data): int
    {
        $connection = $this->connectionPool->getConnectionForTable('tx_styleguide_elements_select_single_21_foreign');
        $groups = ['group3', 'group4', 'group5'];
        foreach ($groups as $group) {
            $childFieldValues = [
                'pid' => $data['fieldValues']['pid'],
            ];
            $connection->insert(
                'tx_styleguide_elements_select_single_21_foreign',
                $childFieldValues
            );
            $uid = (int)$connection->lastInsertId();
            $childFieldValues['uid'] = $uid;
            $childFieldValues['item_group'] = $group;
            $childFieldValues = $this->recordData->generate('tx_styleguide_elements_select_single_21_foreign', $childFieldValues);
            $connection->update(
                'tx_styleguide_elements_select_single_21_foreign',
                $childFieldValues,
                ['uid' => $uid]
            );
        }
        return $childFieldValues['uid'];
    }
}
