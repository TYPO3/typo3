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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Connection;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TransactionalTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        __DIR__ . '/../../Fixtures/Extensions/test_connection_transaction',
    ];

    #[Test]
    public function transactionInsertValidRecordIsPersisted(): void
    {
        $connection = (new ConnectionPool())->getConnectionForTable('tx_testconnectiontransaction');
        $expectedRecord = [
            'uid' => 1,
            'pid' => 0,
            'title' => 'Inserted record',
        ];
        $record = $connection->transactional(function (Connection $connection): ?array {
            $lastInsertId = $connection->insert(
                'tx_testconnectiontransaction',
                [
                    'pid' => 0,
                    'title' => 'Inserted record',
                ],
            );
            return $connection->select(
                ['uid', 'pid', 'title'],
                'tx_testconnectiontransaction',
                [
                    'uid' => $lastInsertId,
                ]
            )->fetchAssociative() ?: null;
        });
        self::assertIsArray($record);
        self::assertSame($expectedRecord, $record);
    }

    #[Test]
    public function transactionRecordsAreRolledBackWhenExceptionIsThrown(): void
    {
        $connection = (new ConnectionPool())->getConnectionForTable('tx_testconnectiontransaction');
        try {
            $connection->transactional(function (Connection $connection): array {
                // valid record
                $connection->insert(
                    'tx_testconnectiontransaction',
                    [
                        'pid' => 0,
                        'title' => 'Inserted record',
                    ],
                );
                // invalid record - throws exception
                $connection->insert(
                    'tx_testconnectiontransaction',
                    [
                        'pid' => 0,
                        'title' => 'Inserted record',
                        'not_existing_field' => 0,
                    ],
                );
                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder->getRestrictions()->removeAll();
                return $queryBuilder
                    ->select('uid', 'pid', 'title')
                    ->from('tx_testconnectiontransaction')
                    ->executeQuery()
                    ->fetchAllAssociative();
            });
        } catch (\Throwable $t) {
            // Exception not checked here by intention. We want to check if database is still clean.
        }
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $records = $queryBuilder
            ->select('uid', 'pid', 'title')
            ->from('tx_testconnectiontransaction')
            ->executeQuery()
            ->fetchAllAssociative();
        self::assertSame([], $records);
    }
}
