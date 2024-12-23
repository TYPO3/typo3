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

use Doctrine\DBAL\Platforms\TrimMode;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
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

    #[Group('not-postgres')]
    #[Test]
    public function fixedTitleWithLesserDataLengthCanBeInserted(): void
    {
        $value = str_repeat('t', 50);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('string_tests');
        $expectedRow = [
            'uid' => 1,
            'fixed_title' => $value,
        ];
        $lastInsertId = $connection->insert(
            'string_tests',
            [
                'fixed_title' => $value,
            ],
            [
                'fixed_title' => Connection::PARAM_STR,
            ],
        );
        // can ve retrieved with last insert id
        $row = $connection->select(
            ['uid', 'fixed_title'],
            'string_tests',
            [
                'uid' => $lastInsertId,
            ],
            [],
            [],
            1
        )->fetchAssociative();
        self::assertIsArray($row);
        self::assertSame($expectedRow, $row);
        // can be retrieved with lesser data key
        $filteredRow = $connection->select(
            ['uid', 'fixed_title'],
            'string_tests',
            [
                'fixed_title' => $value,
            ],
            [],
            [],
            1
        )->fetchAssociative();
        self::assertIsArray($filteredRow);
        self::assertSame($expectedRow, $filteredRow);
    }

    #[Test]
    public function fixedTitleWithLesserDataLengthCanBeInsertedAndRetrievedWithEnforcedTrim(): void
    {
        $value = str_repeat('t', 50);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('string_tests');
        $expectedRow = [
            'uid' => 1,
            'fixed_title' => $value,
        ];
        $connection->insert(
            'string_tests',
            [
                'fixed_title' => $value,
            ],
            [
                'fixed_title' => Connection::PARAM_STR,
            ],
        );
        // can be retrieved with lesser data key and enforced value trim
        $queryBuilder = $connection->createQueryBuilder();
        $enforcedValueTrimRow = $queryBuilder
            ->select('uid')
            ->addSelectLiteral(
                $queryBuilder->expr()->as(
                    $queryBuilder->expr()->trim(
                        'fixed_title',
                        TrimMode::TRAILING,
                        ' '
                    ),
                    'fixed_title',
                ),
            )
            ->from('string_tests')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        self::assertIsArray($enforcedValueTrimRow);
        self::assertSame($expectedRow, $enforcedValueTrimRow);
    }

    #[Group('not-mysql')]
    #[Group('not-mariadb')]
    #[Group('not-sqlite')]
    #[Test]
    public function fixedTitleWithLesserDataLengthCanBeInsertedButReturnsFilledUpValueWithSpacesOnPostgres(): void
    {
        $value = str_repeat('t', 50);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('string_tests');
        // PostgreSQL behaves differently regarding `CHAR` fields than MySQL, MariaDB and SQLite and pads the persisted
        // value to the full fixed length of the field and returns the padded value on reading. The difference in the
        // behaviour is known and respected by padding the expected value for PostgresSQL with spaced in the end.
        $fillUpExpectedFixedTitle = $value . str_repeat(' ', 50);
        $expectedRow = [
            'uid' => 1,
            'fixed_title' => $fillUpExpectedFixedTitle,
        ];
        $lastInsertId = $connection->insert(
            'string_tests',
            [
                'fixed_title' => $value,
            ],
            [
                'fixed_title' => Connection::PARAM_STR,
            ],
        );
        // can ve retrieved with last insert id
        $row = $connection->select(
            ['uid', 'fixed_title'],
            'string_tests',
            [
                'uid' => $lastInsertId,
            ],
            [],
            [],
            1
        )->fetchAssociative();
        self::assertIsArray($row);
        self::assertSame($expectedRow, $row);
        // can be retrieved with lesser data key
        $filteredRow = $connection->select(
            ['uid', 'fixed_title'],
            'string_tests',
            [
                'fixed_title' => $value,
            ],
            [],
            [],
            1
        )->fetchAssociative();
        self::assertIsArray($filteredRow);
        self::assertSame($expectedRow, $filteredRow);
        // can also be retrieved with filled up data key
        $filteredWithFullLengthValueRow = $connection->select(
            ['uid', 'fixed_title'],
            'string_tests',
            [
                'fixed_title' => $fillUpExpectedFixedTitle,
            ],
            [],
            [],
            1
        )->fetchAssociative();
        self::assertIsArray($filteredWithFullLengthValueRow);
        self::assertSame($expectedRow, $filteredWithFullLengthValueRow);
    }

    #[Test]
    public function flexibleTitleDataCanBeInserted(): void
    {
        $value = str_repeat('t', 50);
        $expectedRow = [
            'uid' => 1,
            'flexible_title' => $value,
        ];
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('string_tests');
        $lastInsertId = $connection->insert(
            'string_tests',
            [
                'flexible_title' => $value,
            ],
            [
                'flexible_title' => Connection::PARAM_STR,
            ],
        );
        $row = $connection->select(
            ['uid', 'flexible_title'],
            'string_tests',
            [
                'uid' => $lastInsertId,
            ],
            [],
            [],
            1
        )->fetchAssociative();
        self::assertIsArray($row);
        self::assertSame($expectedRow, $row);
    }
}
