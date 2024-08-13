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

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Schema\ConnectionMigrator;
use TYPO3\CMS\Core\Database\Schema\SchemaDiff;
use TYPO3\CMS\Core\Database\Schema\TableDiff;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ConnectionMigratorTest extends UnitTestCase
{
    protected MySQLPlatform $platform;
    protected AccessibleObjectInterface&MockObject $subject;
    protected int $maxIdentifierLength = -1;

    protected function setUp(): void
    {
        parent::setUp();

        $platformMock = $this->createMock(MySQLPlatform::class);
        $platformMock->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);
        $this->platform = $platformMock;

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('getDatabasePlatform')->willReturn($this->platform);
        $connectionMock->method('quoteIdentifier')->with(self::anything())->willReturnArgument(0);

        $this->maxIdentifierLength = PlatformInformation::getMaxIdentifierLength($this->platform);

        $this->subject = $this->getAccessibleMock(ConnectionMigrator::class, null, ['Default', $connectionMock, []]);
    }

    #[Test]
    public function tableNamesStickToTheMaximumCharactersWhenPrefixedForRemoval(): void
    {
        $originalSchemaDiff = new SchemaDiff(
            // createdSchemas
            [],
            // droppedSchemas
            [],
            // createdTables
            [],
            // alteredTables
            [],
            // droppedTables
            [$this->getTable()->getName() => $this->getTable()],
            // createdSequences
            [],
            // alteredSequences
            [],
            // droppedSequences
            [],
        );
        /** @var SchemaDiff $renamedSchemaDiff */
        $renamedSchemaDiff = $this->subject->_call('migrateUnprefixedRemovedTablesToRenames', $originalSchemaDiff);
        $firstAlteredTableName = array_key_first($renamedSchemaDiff->getAlteredTables());
        $firstAlteredTableNewName = $renamedSchemaDiff->getAlteredTables()[$firstAlteredTableName]->newName;

        self::assertStringStartsWith('zzz_deleted_', $firstAlteredTableNewName);
        self::assertEquals($this->maxIdentifierLength, strlen($firstAlteredTableNewName));
    }

    #[Test]
    public function columnNamesStickToTheMaximumCharactersWhenPrefixedForRemoval(): void
    {
        $table = $this->getTable();
        $tableDiff = new TableDiff(
            // oldTable
            $table,
            // addedColumns
            [],
            // changedColumns
            [],
            // droppedColumns
            [$this->getColumn()->getName() => $this->getColumn()],
            // addedIndexes
            [],
            // modifiedIndexes
            [],
            // droppedIndexes
            [],
            // renamedIndexes
            [],
            // addedForeignKeys
            [],
            // modifiedForeignKeys
            [],
            // droppedForeignKeys
            [],
        );
        $originalSchemaDiff = new SchemaDiff(
            // createdSchemas
            [],
            // droppedSchemas
            [],
            // createdTables
            [],
            // alteredTables
            [$tableDiff->getOldTable()->getName() => $tableDiff],
            // droppedTables
            [],
            // createdSequences
            [],
            // alteredSequences
            [],
            // droppedSequences
            [],
        );
        /** @var SchemaDiff $renamedSchemaDiff */
        $renamedSchemaDiff = $this->subject->_call('migrateUnprefixedRemovedFieldsToRenames', $originalSchemaDiff);
        $firstColumnName = array_key_first($renamedSchemaDiff->getAlteredTables()[$table->getName()]->getChangedColumns());
        $firstColumn = $renamedSchemaDiff->getAlteredTables()[$table->getName()]->getChangedColumns()[$firstColumnName];

        self::assertStringStartsWith(
            'zzz_deleted_',
            $firstColumn->getNewColumn()->getName()
        );
        self::assertEquals(
            $this->maxIdentifierLength,
            strlen($firstColumn->getNewColumn()->getName())
        );
    }

    /**
     * Utility method to create a table instance with name that exceeds the identifier limits.
     */
    protected function getTable(): Table
    {
        $tableName = 'table_name_that_is_ridiculously_long_' . bin2hex(random_bytes(100));
        return new Table($tableName);
    }

    /**
     * Utility method to create a column instance with name that exceeds the identifier limits.
     */
    protected function getColumn(): Column
    {
        $columnName = 'column_name_that_is_ridiculously_long_' . bin2hex(random_bytes(100));
        return new Column(
            $columnName,
            Type::getType('string')
        );
    }
}
