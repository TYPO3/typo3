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
use Doctrine\DBAL\Types\StringType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\TableDiff;
use TYPO3\CMS\Install\Updates\IndexedSearchCTypeMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class IndexedSearchCTypeMigrationTest extends FunctionalTestCase
{
    protected const TABLE_CONTENT = 'tt_content';
    protected const TABLE_BACKEND_USER_GROUPS = 'be_groups';

    protected string $baseDataSet = __DIR__ . '/Fixtures/IndexedSearchBase.csv';
    protected string $baseDataSetPartiallyMigration = __DIR__ . '/Fixtures/IndexedSearchBasePartiallyMigration.csv';
    protected string $fullMigrationResultDataSet = __DIR__ . '/Fixtures/IndexedSearchMigrated.csv';
    protected string $partiallyMigrationResultDataSet = __DIR__ . '/Fixtures/IndexedSearchPartiallyMigrated.csv';

    protected IndexedSearchCTypeMigration $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $connectionPool = $this->get(ConnectionPool::class);
        $schemaManager = $connectionPool
            ->getConnectionForTable('tt_content')
            ->createSchemaManager();

        if (!$schemaManager->introspectSchema()->getTable('tt_content')->hasColumn('list_type')) {
            $schemaManager->alterTable(
                new TableDiff(
                    $schemaManager->introspectSchema()->getTable('tt_content'),
                    [
                        'list_type' => new Column('list_type', new StringType(), ['length' => 255, 'default' => '']),
                    ]
                )
            );
        }

        $this->subject = new IndexedSearchCTypeMigration($connectionPool);
    }

    #[Test]
    public function contentElementsAndBackendUserGroupsUpdated(): void
    {
        $this->importCSVDataSet($this->baseDataSet);
        self::assertTrue($this->subject->updateNecessary());
        $this->subject->executeUpdate();
        self::assertFalse($this->subject->updateNecessary());
        $this->assertCSVDataSet($this->fullMigrationResultDataSet);

        // Just ensure that running the upgrade again does not change anything
        $this->subject->executeUpdate();
        $this->assertCSVDataSet($this->fullMigrationResultDataSet);
    }

    #[Test]
    public function backendUserGroupsNotUpdated(): void
    {
        $this->importCSVDataSet($this->baseDataSetPartiallyMigration);
        self::assertTrue($this->subject->updateNecessary());
        $this->subject->executeUpdate();
        self::assertFalse($this->subject->updateNecessary());
        $this->assertCSVDataSet($this->partiallyMigrationResultDataSet);

        // Just ensure that running the upgrade again does not change anything
        $this->subject->executeUpdate();
        $this->assertCSVDataSet($this->partiallyMigrationResultDataSet);
    }
}
