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

namespace TYPO3\CMS\Core\Tests\Functional\Database;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConnectionTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $sqlReader = $this->get(SqlReader::class);
        $creationStatements = $sqlReader->getCreateTableStatementArray(file_get_contents(__DIR__ . '/Fixtures/connectionTestTable.sql'));
        $subject = $this->get(SchemaMigrator::class);
        $subject->install($creationStatements);
    }

    #[Test]
    public function lastInsertIdReturnsExpectedConsecutiveUid(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/lastInsertId.csv');
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $connection->insert(
            'tt_content',
            [
                'pid' => 0,
                'header' => 'last-insert',
            ]
        );
        self::assertSame('5', $connection->lastInsertId());
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function datetimeInstanceCanBePersistedToDatabaseWithoutSpecifyingType(): void
    {
        $value = new \DateTime('2023-11-23T11:49:00+01:00');

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('datetime_tests');
        $connection->insert('datetime_tests', [
            'mutable_object' => $value,
        ]);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function datetimeInstanceCanBePersistedToDatabaseIfTypeIsExplicitlySpecified(): void
    {
        $value = new \DateTime('2023-11-23T11:49:00+01:00');

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('datetime_tests');
        $connection->insert('datetime_tests', [
            'mutable_object' => $value,
        ], [
            'mutable_object' => 'datetime',
        ]);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function datetimeImmutableInstanceCanBePersistedToDatabaseWithoutSpecifyingType(): void
    {
        $value = new \DateTimeImmutable('2023-11-23T11:49:00+01:00');

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('datetime_tests');
        $connection->insert('datetime_tests', [
            'immutable_object' => $value,
        ]);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function datetimeImmutableInstanceCanBePersistedToDatabaseIfTypeIsExplicitlySpecified(): void
    {
        $value = new \DateTimeImmutable('2023-11-23T11:49:00+01:00');

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('datetime_tests');
        $connection->insert('datetime_tests', [
            'immutable_object' => $value,
        ], [
            'immutable_object' => 'datetime_immutable',
        ]);
    }
}
