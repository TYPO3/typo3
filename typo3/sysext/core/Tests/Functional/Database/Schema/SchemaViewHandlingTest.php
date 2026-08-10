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
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * A table name that exists as a view belongs to whatever provides that view, not to TYPO3.
 */
final class SchemaViewHandlingTest extends AbstractSchemaBasedTestCase
{
    protected array $tablesToDrop = ['a_test_table'];

    #[Test]
    public function aTableThatExistsAsAViewIsLeftAlone(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $this->prepareTestTable($this->createSchemaMigrator(), __DIR__ . '/../Fixtures/newTable.sql');
        // The declaration asks for a table of this name while the database has a view of it, which
        // is the reported situation: a table mapped to a connection where it exists as a view.
        $connection->executeStatement('CREATE VIEW a_test_view AS SELECT uid FROM a_test_table');

        try {
            $statements = $this->createSqlReader()->getCreateTableStatementArray(
                (string)file_get_contents(__DIR__ . '/../Fixtures/viewBackedTable.sql')
            );
            $suggestions = $this->createSchemaMigrator()->getUpdateSuggestions($statements);
            $withRemovals = $this->createSchemaMigrator()->getUpdateSuggestions($statements, true);

            $everything = '';
            foreach ([$suggestions, $withRemovals] as $perConnection) {
                foreach ($perConnection as $operations) {
                    foreach ($operations as $key => $statementsOfOperation) {
                        if (!is_array($statementsOfOperation)) {
                            continue;
                        }
                        $everything .= $key . ' ' . implode(' ', $statementsOfOperation) . "\n";
                    }
                }
            }
            self::assertStringNotContainsString(
                'a_test_view',
                $everything,
                'a view must be neither created, altered, renamed nor dropped'
            );
        } finally {
            $connection->executeStatement('DROP VIEW a_test_view');
        }
    }
}
