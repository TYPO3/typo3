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

namespace TYPO3\CMS\Install\Tests\Functional\Updates;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\TableDiff;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\SysFileCollectionIdentifierMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SysFileCollectionIdentifierMigrationTest extends FunctionalTestCase
{
    private const TABLE_NAME = 'sys_file_collection';

    protected string $baseDataSet = __DIR__ . '/Fixtures/FileCollectionBase.csv';
    protected string $resultDataSet = __DIR__ . '/Fixtures/FileCollectionMigrated.csv';

    /**
     * @test
     */
    public function sysFileCollectionRecordsUpdated(): void
    {
        $subject = new SysFileCollectionIdentifierMigration();

        $schemaManager = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE_NAME)
            ->createSchemaManager();

        $schemaManager->alterTable(
            new TableDiff(
                self::TABLE_NAME,
                [
                    new Column('storage', new IntegerType(), ['default' => '0', 'notnull' => true]),
                    new Column('folder', new StringType(), ['length' => 255, 'default' => '', 'notnull' => true]),
                ]
            )
        );

        $this->importCSVDataSet($this->baseDataSet);
        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        self::assertFalse($subject->updateNecessary());
        $this->assertCSVDataSet($this->resultDataSet);

        // Just ensure that running the upgrade again does not change anything
        $subject->executeUpdate();
        $this->assertCSVDataSet($this->resultDataSet);
    }
}
