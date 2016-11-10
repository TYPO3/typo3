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

use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Schema\ConnectionMigrator;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tests for ConnectionMigrator
 */
class ConnectionMigratorTest extends UnitTestCase
{

    /**
     * @test
     */
    public function tableNamesStickToTheMaximumCharactersWhenPrefixedForRemoval()
    {
        $ridiculouslyLongTableName = 'table_name_that_is_ridiculously_long_' . random_bytes(200);
        $tableMock = $this->getAccessibleMock(Table::class, ['getQuotedName'], [$ridiculouslyLongTableName]);
        $tableMock->expects($this->any())->method('getQuotedName')->withAnyParameters()->will($this->returnValue($ridiculouslyLongTableName));

        $platform = $this->getMockBuilder(\Doctrine\DBAL\Platforms\AbstractPlatform::class)->disableOriginalConstructor()->getMock();

        $connectionMock = $this->getMockBuilder(Connection::class)->setMethods(['getDatabasePlatform'])->disableOriginalConstructor()->getMock();
        $connectionMock->method('getDatabasePlatform')->willReturn($platform);

        $connectionMigrator = $this->getAccessibleMock(ConnectionMigrator::class, null, [], '', false);
        $connectionMigrator->_set('connection', $connectionMock);

        $originalSchemaDiff = GeneralUtility::makeInstance(SchemaDiff::class, null, null, [$tableMock]);

        $renamedSchemaDiff = $connectionMigrator->_call('migrateUnprefixedRemovedTablesToRenames', $originalSchemaDiff);

        $this->assertStringStartsWith('zzz_deleted_', $renamedSchemaDiff->changedTables[0]->newName);
        $this->assertLessThanOrEqual(
            strlen($renamedSchemaDiff->changedTables[0]->newName),
            $this->maxTableNameLength
        );
    }
}
