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
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

/**
 * Generate data for type=group fields with MM table
 *
 * @internal
 */
final class TypeGroupAllowedBeUsersBeGroupsMM extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    protected array $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users,be_groups',
                'MM' => 'tx_styleguide_element_group_group_13_mm',
            ],
        ],
    ];

    public function __construct(
        private readonly RecordFinder $recordFinder,
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function generate(array $data): int
    {
        $beGroupUids = $this->recordFinder->findUidsOfDemoBeGroups();
        $beUserUids = $this->recordFinder->findUidsOfDemoBeUsers();
        $mMTableName = $data['fieldConfig']['config']['MM'];
        $relationCount = 0;

        foreach ($beGroupUids as $beGroupUid) {
            $mMFieldValues = [
                'uid_local' => $data['fieldValues']['uid'],
                'uid_foreign' => $beGroupUid,
                'tablenames' => 'be_groups',
            ];
            $connection = $this->connectionPool->getConnectionForTable($mMTableName);
            $connection->insert($mMTableName, $mMFieldValues);
            $relationCount++;
        }
        foreach ($beUserUids as $beUserUid) {
            $mMFieldValues = [
                'uid_local' => $data['fieldValues']['uid'],
                'uid_foreign' => $beUserUid,
                'tablenames' => 'be_users',
            ];
            $connection = $this->connectionPool->getConnectionForTable($mMTableName);
            $connection->insert($mMTableName, $mMFieldValues);
            $relationCount++;
        }

        return $relationCount;
    }
}
