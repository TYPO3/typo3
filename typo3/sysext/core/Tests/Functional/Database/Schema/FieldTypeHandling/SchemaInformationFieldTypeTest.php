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

use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaInformation;
use TYPO3\CMS\Core\Tests\Functional\Database\Schema\AbstractSchemaBasedTestCase;

/**
 * `SchemaInformation` is the core internal, cached view on the database schema. It is what
 * `Connection::insert()` and friends consult to auto-add parameter types, so a column whose
 * doctrine type is not resolved back correctly is written and read with the wrong type.
 *
 * Resolving a custom type is not free on every platform: only PostgreSQL has native `json` and
 * `uuid` types. MySQL and MariaDB carry the doctrine type hint in a column comment, and SQLite
 * declares the type verbatim. Introspection has to map all of that back to the same doctrine type.
 *
 * The core schema relies on this - `be_users.user_settings` and the TCA `json` fields of
 * EXT:reactions, EXT:webhooks, EXT:scheduler and EXT:form are `json` columns, and
 * `sys_be_shortcuts_group.uuid` is a `uuid` column - so this is asserted on every supported
 * platform rather than for a single one.
 */
final class SchemaInformationFieldTypeTest extends AbstractSchemaBasedTestCase
{
    protected function setUp(): void
    {
        $this->tablesToDrop = [
            'a_test_table',
        ];
        parent::setUp();
    }

    /**
     * Built by hand instead of using {@see \TYPO3\CMS\Core\Database\Connection::getSchemaInformation()},
     * so the assertion cannot be answered from a persisted cache entry written by another test.
     */
    private function createSchemaInformation(string $tableName): SchemaInformation
    {
        return new SchemaInformation(
            $this->get(ConnectionPool::class)->getConnectionForTable($tableName),
            new NullFrontend('test-runtime'),
            new NullFrontend('test-schema'),
            $this->get('package-dependent-cache-identifier'),
        );
    }

    #[Test]
    public function customColumnTypesAreResolvedFromTheDatabaseSchema(): void
    {
        $this->prepareTestTable(
            $this->createSchemaMigrator(),
            __DIR__ . '/Fixtures/SchemaInformationFieldType/custom_field_types.sql'
        );

        $tableInfo = $this->createSchemaInformation('a_test_table')->getTableInfo('a_test_table');

        self::assertSame(Types::JSON, $tableInfo->getColumnInfo('json_field')?->typeName);
        self::assertSame(Types::GUID, $tableInfo->getColumnInfo('uuid_field')?->typeName);
        // A plain string column of the same length must not be mistaken for a uuid.
        self::assertSame(Types::STRING, $tableInfo->getColumnInfo('varchar_field')?->typeName);
    }

    #[Test]
    public function customColumnTypesSurviveTheColumnNameLookup(): void
    {
        $this->prepareTestTable(
            $this->createSchemaMigrator(),
            __DIR__ . '/Fixtures/SchemaInformationFieldType/custom_field_types.sql'
        );

        $schemaInformation = $this->createSchemaInformation('a_test_table');

        // `listTableColumnInfos()` is the array shaped counterpart used by consumers such as
        // `Connection::insert()`. It must expose the same types, keyed by the unmodified column name.
        $columnInfos = $schemaInformation->listTableColumnInfos('a_test_table');

        self::assertArrayHasKey('json_field', $columnInfos);
        self::assertArrayHasKey('uuid_field', $columnInfos);
        self::assertSame(Types::JSON, $columnInfos['json_field']->typeName);
        self::assertSame(Types::GUID, $columnInfos['uuid_field']->typeName);
    }
}
