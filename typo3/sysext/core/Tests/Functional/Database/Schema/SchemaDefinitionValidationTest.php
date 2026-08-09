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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Schema;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Schema\Exception\InvalidIndexDefinitionException;

/**
 * A table definition that indexes a column it does not declare is invalid, and has to be rejected
 * before any of it reaches the database.
 *
 * MySQL and PostgreSQL reject such an index themselves, so there the damage is limited to a failed
 * statement. SQLite does not: a quoted identifier that resolves to no column is taken as a string
 * literal, so the index is silently created over a constant expression. Introspecting a database in
 * that state is not possible any more - the index reports no column name at all - which takes the
 * whole schema analysis down and leaves no way back through the install tool.
 */
final class SchemaDefinitionValidationTest extends AbstractSchemaBasedTestCase
{
    protected array $tablesToDrop = ['a_test_table'];

    #[Test]
    public function anIndexOverAnUndeclaredColumnIsRejected(): void
    {
        $schemaMigrator = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(
            (string)file_get_contents(__DIR__ . '/../Fixtures/indexOnUndeclaredColumn.sql')
        );

        $exception = null;
        try {
            $schemaMigrator->install($statements);
        } catch (InvalidIndexDefinitionException $caught) {
            $exception = $caught;
        }

        self::assertInstanceOf(
            InvalidIndexDefinitionException::class,
            $exception,
            'an index over a column that is not declared has to be rejected'
        );
        // All three have to be named, or the message does not point at the typo it is about.
        self::assertStringContainsString('a_test_table', $exception->getMessage());
        self::assertStringContainsString('sorting', $exception->getMessage());
        self::assertStringContainsString('undefined_column', $exception->getMessage());
    }

    #[Test]
    public function anIndexOverAnUndeclaredColumnDoesNotReachTheDatabase(): void
    {
        $schemaMigrator = $this->createSchemaMigrator();
        $statements = $this->createSqlReader()->getCreateTableStatementArray(
            (string)file_get_contents(__DIR__ . '/../Fixtures/indexOnUndeclaredColumn.sql')
        );

        try {
            $schemaMigrator->install($statements);
        } catch (InvalidIndexDefinitionException) {
            // The point of this test is what the database looks like afterwards.
        }

        // Rejecting the definition as a whole is what keeps the database introspectable. A table
        // created without its broken index would be tempting, but it would silently drop an index
        // the definition asked for.
        self::assertFalse($this->getSchemaManager()->tablesExist(['a_test_table']));
    }

    #[Test]
    public function anIndexMayCoverAColumnDeclaredByAnotherStatementForTheSameTable(): void
    {
        $schemaMigrator = $this->createSchemaMigrator();
        $sqlCode = (string)file_get_contents(__DIR__ . '/../Fixtures/indexOnColumnOfAnotherStatement.sql');

        // Extensions add indexes to tables owned by other extensions, in a statement of their own
        // that does not repeat the column. The definitions are merged before anything is applied,
        // so this is valid and has to stay valid.
        $this->verifyMigrationResult(
            $schemaMigrator->install($this->createSqlReader()->getCreateTableStatementArray($sqlCode))
        );

        // Not looked up by name: SQLite needs index names to be unique for the whole database, so
        // the comparison gives them a suffix there.
        $indexedColumns = array_map(
            static fn($index): array => $index->getColumns(),
            array_values($this->getTableDetails('a_test_table')->getIndexes())
        );
        self::assertContains(['sorting'], $indexedColumns);
        $this->verifyCleanDatabaseState($sqlCode);
    }
}
