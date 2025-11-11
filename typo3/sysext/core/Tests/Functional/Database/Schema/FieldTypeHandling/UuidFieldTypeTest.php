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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Schema\FieldTypeHandling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\Database\Schema\AbstractSchemaBasedTestCase;

final class UuidFieldTypeTest extends AbstractSchemaBasedTestCase
{
    protected function setUp(): void
    {
        $this->tablesToDrop = [
            'a_test_table',
        ];
        parent::setUp();
    }

    private function createRecord(string $tableName, array $record): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder
            ->insert($tableName)
            ->values($record)
            ->executeStatement();
        return (int)$queryBuilder->getConnection()->lastInsertId();
    }

    private function getSingleRecordByFieldValue(string $tableName, string $fieldName, string $value): ?array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq($fieldName, $queryBuilder->createNamedParameter($value, Connection::PARAM_STR)),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative() ?: null;
    }

    private function getSingleRecordByUid(string $tableName, int $recordId): ?array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($recordId, Connection::PARAM_INT)),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative() ?: null;
    }

    public static function uuidVersions(): \Generator
    {
        yield 'Uuid::v4()' => [
            'value' => Uuid::v4()->toString(),
        ];
        yield 'Uuid::v6()' => [
            'value' => Uuid::v6()->toString(),
        ];
        yield 'Uuid::v7()' => [
            'value' => Uuid::v7()->toString(),
        ];
    }

    #[DataProvider('uuidVersions')]
    #[Test]
    public function valueCanBePersistedAndRetrievedOnNullableField(string $value): void
    {
        $this->prepareTestTable($this->createSchemaMigrator(), __DIR__ . '/Fixtures/UuidFieldType/new_nullable_and_no_default.sql');
        $insertedId = $this->createRecord('a_test_table', ['pid' => 0, 'test_field' => $value]);
        self::assertGreaterThan(0, $insertedId);
        $recordByUid = $this->getSingleRecordByUid('a_test_table', $insertedId);
        self::assertIsArray($recordByUid);
        self::assertNotSame([], $recordByUid);
        self::assertArrayHasKey('test_field', $recordByUid);
        self::assertSame($value, $recordByUid['test_field']);
        $recordByValue = $this->getSingleRecordByFieldValue('a_test_table', 'test_field', $value);
        self::assertIsArray($recordByValue);
        self::assertNotSame([], $recordByValue);
        self::assertArrayHasKey('test_field', $recordByValue);
        self::assertSame($value, $recordByValue['test_field']);
    }

    #[DataProvider('uuidVersions')]
    #[Test]
    public function valueCanBePersistedAndRetrievedOnNotNullableField(string $value): void
    {
        $this->prepareTestTable($this->createSchemaMigrator(), __DIR__ . '/Fixtures/UuidFieldType/new_not_nullable_and_no_default.sql');
        $insertedId = $this->createRecord('a_test_table', ['pid' => 0, 'test_field' => $value]);
        self::assertGreaterThan(0, $insertedId);
        $recordByUid = $this->getSingleRecordByUid('a_test_table', $insertedId);
        self::assertIsArray($recordByUid);
        self::assertNotSame([], $recordByUid);
        self::assertArrayHasKey('test_field', $recordByUid);
        self::assertSame($value, $recordByUid['test_field']);
        $recordByValue = $this->getSingleRecordByFieldValue('a_test_table', 'test_field', $value);
        self::assertIsArray($recordByValue);
        self::assertNotSame([], $recordByValue);
        self::assertArrayHasKey('test_field', $recordByValue);
        self::assertSame($value, $recordByValue['test_field']);
    }
}
