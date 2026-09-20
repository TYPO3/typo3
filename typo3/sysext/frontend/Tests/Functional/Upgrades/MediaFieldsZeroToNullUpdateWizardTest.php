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

namespace TYPO3\CMS\Frontend\Tests\Functional\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Frontend\Upgrades\MediaFieldsZeroToNullUpdateWizard;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MediaFieldsZeroToNullUpdateWizardTest extends FunctionalTestCase
{
    private string $baseDataSet = __DIR__ . '/Fixtures/MediaFieldsZeroToNullUpdateWizardBase.csv';
    private string $fullMigrationResultDataSet = __DIR__ . '/Fixtures/MediaFieldsZeroToNullUpdateWizardMigrated.csv';

    private MediaFieldsZeroToNullUpdateWizard $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $connectionPool = $this->get(ConnectionPool::class);
        $this->subject = new MediaFieldsZeroToNullUpdateWizard($connectionPool);
    }

    #[Test]
    public function mediaFieldsAreUpdated(): void
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

    #[TestWith([true])]
    #[TestWith([false])]
    #[Test]
    public function mediaFieldsAndSchemaAreUpdated(bool $nonNullable): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('tt_content');
        $schemaManager = $connection->createSchemaManager();
        $oldTable = $schemaManager->introspectTable('tt_content');
        $newTable = clone $oldTable;
        foreach (['imagewidth', 'imageheight'] as $fieldName) {
            $newTable->getColumn($fieldName)->setNotnull($nonNullable)->setDefault(0);
        }
        $diff = $schemaManager->createComparator()->compareTables($oldTable, $newTable);
        foreach ($connection->getDatabasePlatform()->getAlterTableSQL($diff) as $statement) {
            $connection->executeStatement($statement);
        }
        $connection->insert('tt_content', ['uid' => 1, 'imagewidth' => 0, 'imageheight' => 0]);
        $connection->insert('tt_content', ['uid' => 2, 'imagewidth' => 128, 'imageheight' => 256]);

        self::assertTrue($this->subject->updateNecessary());
        self::assertTrue($this->subject->executeUpdate());

        self::assertSame(
            ['imagewidth' => null, 'imageheight' => null],
            $connection->fetchAssociative('SELECT imagewidth, imageheight FROM tt_content WHERE uid = 1')
        );
        self::assertSame(
            ['imagewidth' => 128, 'imageheight' => 256],
            $connection->fetchAssociative('SELECT imagewidth, imageheight FROM tt_content WHERE uid = 2')
        );
        $updatedTable = $schemaManager->introspectTable('tt_content');
        self::assertFalse($updatedTable->getColumn('imagewidth')->getNotnull());
        self::assertFalse($updatedTable->getColumn('imageheight')->getNotnull());
        self::assertNull($updatedTable->getColumn('imagewidth')->getDefault());
        self::assertNull($updatedTable->getColumn('imageheight')->getDefault());
        self::assertFalse($this->subject->updateNecessary());
    }

    #[Test]
    public function schemaIsUpdatedWithoutRecordsToMigrate(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('tt_content');
        $schemaManager = $connection->createSchemaManager();
        $oldTable = $schemaManager->introspectTable('tt_content');
        $newTable = clone $oldTable;
        $newTable->getColumn('imagewidth')->setNotnull(true)->setDefault(0);
        $diff = $schemaManager->createComparator()->compareTables($oldTable, $newTable);
        foreach ($connection->getDatabasePlatform()->getAlterTableSQL($diff) as $statement) {
            $connection->executeStatement($statement);
        }

        self::assertTrue($this->subject->updateNecessary());
        self::assertTrue($this->subject->executeUpdate());

        $updatedTable = $schemaManager->introspectTable('tt_content');
        self::assertFalse($updatedTable->getColumn('imagewidth')->getNotnull());
        self::assertNull($updatedTable->getColumn('imagewidth')->getDefault());
        self::assertFalse($this->subject->updateNecessary());
    }
}
