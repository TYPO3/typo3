<?php
declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Unit\Database;

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

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Schema\ConnectionMigrator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tests for ConnectionMigrator
 */
class ConnectionMigratorTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var array
     */
    protected $tableAndFieldMaxNameLengthsPerDbPlatform = [
        'default' => [
            'tables' => 10,
            'columns' => 10,
        ],
        'dbplatform_type1' => [
            'tables' => 15,
            'columns' => 15,
        ],
        'dbplatform_type2' => 'dbplatform_type1'
    ];

    /**
     * Utility method to quickly create a 'ConnectionMigratorMock' instance for
     * a specific database platform.
     *
     * @param string $databasePlatformName
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    private function getConnectionMigratorMock($databasePlatformName = 'default')
    {
        $platformMock = $this->getMockBuilder(\Doctrine\DBAL\Platforms\AbstractPlatform::class)->disableOriginalConstructor()->getMock();
        $platformMock->method('getName')->willReturn($databasePlatformName);
        $platformMock->method('quoteIdentifier')->willReturnArgument(0);

        $connectionMock = $this->getMockBuilder(Connection::class)->setMethods(['getDatabasePlatform', 'quoteIdentifier'])->disableOriginalConstructor()->getMock();
        $connectionMock->method('getDatabasePlatform')->willReturn($platformMock);
        $connectionMock->method('quoteIdentifier')->willReturnArgument(0);

        $connectionMigrator = $this->getAccessibleMock(ConnectionMigrator::class, null, [], '', false);
        $connectionMigrator->_set('connection', $connectionMock);
        $connectionMigrator->_set('tableAndFieldMaxNameLengthsPerDbPlatform', $this->tableAndFieldMaxNameLengthsPerDbPlatform);

        return $connectionMigrator;
    }

    /**
     * Utility method to create a table mock instance with a much too long
     * table name in any case.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    private function getTableMock()
    {
        $ridiculouslyLongTableName = 'table_name_that_is_ridiculously_long_' . bin2hex(random_bytes(100));
        $tableMock = $this->getAccessibleMock(Table::class, ['getQuotedName', 'getName'], [$ridiculouslyLongTableName]);
        $tableMock->expects($this->any())
            ->method('getQuotedName')
            ->withAnyParameters()
            ->willReturn($ridiculouslyLongTableName);
        $tableMock->expects($this->any())
            ->method('getName')
            ->withAnyParameters()
            ->willReturn($ridiculouslyLongTableName);

        return $tableMock;
    }

    /**
     * @test
     */
    public function tableNamesStickToTheMaximumCharactersWhenPrefixedForRemoval()
    {
        $connectionMigrator = $this->getConnectionMigratorMock('dbplatform_type1');
        $tableMock = $this->getTableMock();

        $originalSchemaDiff = GeneralUtility::makeInstance(SchemaDiff::class, null, null, [$tableMock]);
        $renamedSchemaDiff = $connectionMigrator->_call('migrateUnprefixedRemovedTablesToRenames', $originalSchemaDiff);

        $this->assertStringStartsWith('zzz_deleted_', $renamedSchemaDiff->changedTables[0]->newName);
        $this->assertLessThanOrEqual(
            $this->tableAndFieldMaxNameLengthsPerDbPlatform['dbplatform_type1']['tables'],
            strlen($renamedSchemaDiff->changedTables[0]->newName)
        );
    }

    /**
     * @test
     */
    public function databasePlatformNamingRestrictionGetsResolved()
    {
        $connectionMigrator = $this->getConnectionMigratorMock('dbplatform_type2');
        $tableMock = $this->getTableMock();

        $originalSchemaDiff = GeneralUtility::makeInstance(SchemaDiff::class, null, null, [$tableMock]);
        $renamedSchemaDiff = $connectionMigrator->_call('migrateUnprefixedRemovedTablesToRenames', $originalSchemaDiff);

        $this->assertLessThanOrEqual(
            $this->tableAndFieldMaxNameLengthsPerDbPlatform['dbplatform_type1']['tables'],
            strlen($renamedSchemaDiff->changedTables[0]->newName)
        );
    }

    /**
     * @test
     */
    public function whenPassingAnUnknownDatabasePlatformTheDefaultTableAndFieldNameRestrictionsApply()
    {
        $connectionMigrator = $this->getConnectionMigratorMock('dummydbplatformthatdoesntexist');
        $tableMock = $this->getTableMock();

        $originalSchemaDiff = GeneralUtility::makeInstance(SchemaDiff::class, null, null, [$tableMock]);
        $renamedSchemaDiff = $connectionMigrator->_call('migrateUnprefixedRemovedTablesToRenames', $originalSchemaDiff);

        $this->assertLessThanOrEqual(
            $this->tableAndFieldMaxNameLengthsPerDbPlatform['default']['tables'],
            strlen($renamedSchemaDiff->changedTables[0]->newName)
        );
    }

    /**
     * @test
     */
    public function columnNamesStickToTheMaximumCharactersWhenPrefixedForRemoval()
    {
        $connectionMigrator = $this->getConnectionMigratorMock('dbplatform_type1');
        $tableMock = $this->getAccessibleMock(Table::class, ['getQuotedName'], ['test_table']);
        $columnMock = $this->getAccessibleMock(
            Column::class,
            ['getQuotedName'],
            [
                'a_column_name_waaaaay_over_20_characters',
                $this->getAccessibleMock(\Doctrine\DBAL\Types\StringType::class, [], [], '', false)
            ]
        );
        $columnMock->expects($this->any())->method('getQuotedName')->withAnyParameters()->will($this->returnValue('a_column_name_waaaaay_over_20_characters'));

        $originalSchemaDiff = GeneralUtility::makeInstance(SchemaDiff::class, null, null, [$tableMock]);
        $originalSchemaDiff->changedTables[0]->removedColumns[] = $columnMock;
        $renamedSchemaDiff = $connectionMigrator->_call('migrateUnprefixedRemovedFieldsToRenames', $originalSchemaDiff);

        $this->assertStringStartsWith('zzz_deleted_', $renamedSchemaDiff->changedTables[0]->changedColumns[0]->column->getName());
        $this->assertLessThanOrEqual(
            $this->tableAndFieldMaxNameLengthsPerDbPlatform['dbplatform_type1']['columns'],
            strlen($renamedSchemaDiff->changedTables[0]->changedColumns[0]->column->getName())
        );
    }
}
