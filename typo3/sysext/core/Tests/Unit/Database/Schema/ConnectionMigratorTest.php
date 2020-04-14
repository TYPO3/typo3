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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use Prophecy\Argument;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Schema\ConnectionMigrator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for ConnectionMigrator
 */
class ConnectionMigratorTest extends UnitTestCase
{
    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $platform;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var int
     */
    protected $maxIdentifierLength = -1;

    /**
     * Set up the test subject
     */
    protected function setUp(): void
    {
        parent::setUp();

        $platformMock = $this->prophesize(MySqlPlatform::class);
        $platformMock->quoteIdentifier(Argument::any())->willReturnArgument(0);
        $this->platform = $platformMock->reveal();

        $connectionMock = $this->prophesize(Connection::class);
        $connectionMock->getDatabasePlatform()->willReturn($this->platform);
        $connectionMock->quoteIdentifier(Argument::any())->willReturnArgument(0);

        $this->maxIdentifierLength = PlatformInformation::getMaxIdentifierLength($this->platform);

        $this->subject = $this->getAccessibleMock(ConnectionMigrator::class, null, [], '', false);
        $this->subject->_set('connection', $connectionMock->reveal());
    }

    /**
     * @test
     */
    public function tableNamesStickToTheMaximumCharactersWhenPrefixedForRemoval()
    {
        $originalSchemaDiff = GeneralUtility::makeInstance(SchemaDiff::class, null, null, [$this->getTable()]);
        $renamedSchemaDiff = $this->subject->_call('migrateUnprefixedRemovedTablesToRenames', $originalSchemaDiff);

        self::assertStringStartsWith('zzz_deleted_', $renamedSchemaDiff->changedTables[0]->newName);
        self::assertEquals(
            $this->maxIdentifierLength,
            strlen($renamedSchemaDiff->changedTables[0]->newName)
        );
    }

    /**
     * @test
     */
    public function columnNamesStickToTheMaximumCharactersWhenPrefixedForRemoval()
    {
        $table = $this->getTable();
        $tableDiff = new TableDiff($table->getName());
        $originalSchemaDiff = new SchemaDiff(null, [$tableDiff]);
        $originalSchemaDiff->changedTables[0]->removedColumns[] = $this->getColumn();
        $renamedSchemaDiff = $this->subject->_call('migrateUnprefixedRemovedFieldsToRenames', $originalSchemaDiff);

        self::assertStringStartsWith(
            'zzz_deleted_',
            $renamedSchemaDiff->changedTables[0]->changedColumns[0]->column->getName()
        );
        self::assertEquals(
            $this->maxIdentifierLength,
            strlen($renamedSchemaDiff->changedTables[0]->changedColumns[0]->column->getName())
        );
    }

    /**
     * Utility method to create a table instance with name that exceeds the identifier limits.
     *
     * @return Table
     */
    protected function getTable(): Table
    {
        $tableName = 'table_name_that_is_ridiculously_long_' . bin2hex(random_bytes(100));
        $table = GeneralUtility::makeInstance(
            Table::class,
            $tableName
        );

        return $table;
    }

    /**
     * Utility method to create a column instance with name that exceeds the identifier limits.
     *
     *
     * @return Column
     */
    protected function getColumn(): Column
    {
        $columnName = 'column_name_that_is_ridiculously_long_' . bin2hex(random_bytes(100));
        $column = GeneralUtility::makeInstance(
            Column::class,
            $columnName,
            Type::getType('string')
        );

        return $column;
    }
}
