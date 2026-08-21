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

namespace TYPO3\CMS\Dashboard\Tests\Functional\Repository;

use Doctrine\DBAL\Schema\Index;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Dashboard\DashboardPreset;
use TYPO3\CMS\Dashboard\Repository\DashboardRepository;
use TYPO3\CMS\Dashboard\Upgrades\DashboardIdentifierDeduplication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DashboardRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['dashboard'];

    #[Test]
    public function schemaIndexesIdentifiersAndOwners(): void
    {
        $indexes = $this->connection()->createSchemaManager()->listTableIndexes('be_dashboards');
        $identifierIndex = $this->findIndex($indexes, ['identifier']);
        $ownerIndex = $this->findIndex($indexes, ['cruser_id']);

        self::assertNotNull($identifierIndex);
        self::assertTrue($identifierIndex->isUnique());
        self::assertNotNull($ownerIndex);
    }

    #[Test]
    public function repeatedCreationUsesUniqueOpaqueIdentifiers(): void
    {
        $repository = $this->get(DashboardRepository::class);
        $preset = new DashboardPreset('default', '', '');

        $first = $repository->create($preset, 1, 'First');
        $second = $repository->create($preset, 1, 'Second');

        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $first->getIdentifier());
        self::assertNotSame($first->getIdentifier(), $second->getIdentifier());
    }

    #[Test]
    public function duplicateIdentifiersAreMigratedBeforeUniqueIndexIsCreated(): void
    {
        $this->restoreNonUniqueIdentifierIndex();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DuplicateDashboards.csv');

        $subject = $this->get(DashboardIdentifierDeduplication::class);
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        self::assertFalse($subject->updateNecessary());

        $identifiers = $this->allIdentifiers();
        self::assertSame('duplicate', $identifiers[0]);
        self::assertCount(6, $identifiers);
        self::assertCount(6, array_unique($identifiers));
        foreach (array_slice($identifiers, 1) as $identifier) {
            self::assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $identifier);
        }
        self::assertTrue($subject->executeUpdate());
        self::assertSame($identifiers, $this->allIdentifiers());
        $sqlReader = $this->get(SqlReader::class);
        $statements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        self::assertSame([], array_filter($this->get(SchemaMigrator::class)->install($statements)));
    }

    #[Test]
    public function schemaMigrationDefersUniqueIndexUntilDuplicatesAreMigrated(): void
    {
        $this->restoreNonUniqueIdentifierIndex();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DuplicateDashboards.csv');
        $sqlReader = $this->get(SqlReader::class);
        $schemaMigrator = $this->get(SchemaMigrator::class);

        $statements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        self::assertSame([], array_filter($schemaMigrator->install($statements)));
        $identifierIndex = $this->findIndex($this->connection()->createSchemaManager()->listTableIndexes('be_dashboards'), ['identifier']);
        self::assertNotNull($identifierIndex);
        self::assertFalse($identifierIndex->isUnique());
        self::assertSame(array_fill(0, 6, 'duplicate'), $this->allIdentifiers());

        self::assertTrue($this->get(DashboardIdentifierDeduplication::class)->executeUpdate());
        $statements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        self::assertSame([], array_filter($schemaMigrator->install($statements)));
        $identifierIndex = $this->findIndex($this->connection()->createSchemaManager()->listTableIndexes('be_dashboards'), ['identifier']);
        self::assertNotNull($identifierIndex);
        self::assertTrue($identifierIndex->isUnique());
    }

    #[Test]
    public function schemaMigrationCreatesUniqueIndexForNewDashboardTable(): void
    {
        $this->connection()->createSchemaManager()->dropTable('be_dashboards');
        $sqlReader = $this->get(SqlReader::class);
        $statements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        self::assertSame([], array_filter($this->get(SchemaMigrator::class)->install($statements)));
        $identifierIndex = $this->findIndex($this->connection()->createSchemaManager()->listTableIndexes('be_dashboards'), ['identifier']);
        self::assertNotNull($identifierIndex);
        self::assertTrue($identifierIndex->isUnique());
        self::assertFalse($this->get(DashboardIdentifierDeduplication::class)->updateNecessary());
    }

    private function restoreNonUniqueIdentifierIndex(): void
    {
        $schemaManager = $this->connection()->createSchemaManager();
        $identifierIndex = $this->findIndex($schemaManager->listTableIndexes('be_dashboards'), ['identifier']);
        self::assertNotNull($identifierIndex);
        $schemaManager->dropIndex($identifierIndex->getName(), 'be_dashboards');
        $schemaManager->createIndex(new Index($identifierIndex->getName(), ['identifier']), 'be_dashboards');
    }

    private function allIdentifiers(): array
    {
        $queryBuilder = $this->connection()->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder->select('identifier')->from('be_dashboards')->orderBy('uid')->executeQuery()->fetchFirstColumn();
    }

    private function connection(): \TYPO3\CMS\Core\Database\Connection
    {
        return $this->get(ConnectionPool::class)->getConnectionForTable('be_dashboards');
    }

    /**
     * @param array<string, Index> $indexes
     */
    private function findIndex(array $indexes, array $columns): ?Index
    {
        foreach ($indexes as $index) {
            if ($index->getColumns() === $columns) {
                return $index;
            }
        }
        return null;
    }
}
