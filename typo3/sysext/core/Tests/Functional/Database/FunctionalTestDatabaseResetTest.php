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

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FunctionalTestDatabaseResetTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $sqlReader = $this->get(SqlReader::class);
        $statements = $sqlReader->getCreateTableStatementArray(
            file_get_contents(__DIR__ . '/Fixtures/functionalTestDatabaseReset.sql')
        );
        $this->get(SchemaMigrator::class)->install($statements);
    }

    #[Test]
    public function databaseStateCanBeChanged(): bool
    {
        $connectionPool = $this->get(ConnectionPool::class);
        $parentConnection = $connectionPool->getConnectionForTable('functional_test_reset_parent');
        $parentConnection->insert('functional_test_reset_parent', ['title' => 'parent']);
        self::assertSame('1', $parentConnection->lastInsertId());

        $connectionPool->getConnectionForTable('functional_test_reset_child')->insert(
            'functional_test_reset_child',
            ['parent_uid' => 1, 'value' => 'child']
        );

        return true;
    }

    #[Test]
    #[Depends('databaseStateCanBeChanged')]
    public function databaseStateAndIdentityAreResetBeforeNextTest(bool $previousTestPassed): void
    {
        self::assertTrue($previousTestPassed);

        $connectionPool = $this->get(ConnectionPool::class);
        $parentConnection = $connectionPool->getConnectionForTable('functional_test_reset_parent');
        $childConnection = $connectionPool->getConnectionForTable('functional_test_reset_child');
        self::assertSame(0, $parentConnection->count('*', 'functional_test_reset_parent', []));
        self::assertSame(0, $childConnection->count('*', 'functional_test_reset_child', []));

        $parentConnection->insert('functional_test_reset_parent', ['title' => 'next parent']);
        self::assertSame('1', $parentConnection->lastInsertId());
    }
}
