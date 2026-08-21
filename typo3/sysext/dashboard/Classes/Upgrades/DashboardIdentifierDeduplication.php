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

namespace TYPO3\CMS\Dashboard\Upgrades;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

/**
 * @internal This class is only meant to be used within EXT:dashboard.
 */
#[UpgradeWizard('dashboardIdentifierDeduplication')]
final readonly class DashboardIdentifierDeduplication implements UpgradeWizardInterface
{
    private const string TABLE = 'be_dashboards';

    public function __construct(
        private ConnectionPool $connectionPool,
        private Random $random,
    ) {}

    public function getTitle(): string
    {
        return 'Make dashboard identifiers unique';
    }

    public function getDescription(): string
    {
        return 'Replaces duplicate dashboard identifiers. Run the database analyzer afterwards to create the unique identifier index.';
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    public function updateNecessary(): bool
    {
        return $this->duplicateRows() !== [];
    }

    /**
     * Schema updates can run before upgrade wizards. Keep the non-unique index
     * while duplicates exist to avoid a constraint violation during that update.
     * After deduplication, the next schema update creates the unique index.
     */
    #[AsEventListener('dashboard-identifier-deduplication')]
    public function deferUniqueIndex(AlterTableDefinitionStatementsEvent $event): void
    {
        if (!$this->updateNecessary()) {
            return;
        }
        $sqlData = $event->getSqlData();
        foreach ($sqlData as $key => $sql) {
            if (str_contains($sql, 'CREATE TABLE be_dashboards (')) {
                $sqlData[$key] = str_replace('UNIQUE identifier (identifier)', 'KEY identifier (identifier)', $sql);
            }
        }
        $event->setSqlData($sqlData);
    }

    public function executeUpdate(): bool
    {
        $duplicates = $this->duplicateRows();
        if ($duplicates === []) {
            return true;
        }
        $connection = $this->connection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $identifiers = array_fill_keys($queryBuilder->select('identifier')->from(self::TABLE)->executeQuery()->fetchFirstColumn(), true);
        foreach ($duplicates as $row) {
            do {
                $identifier = $this->random->generateRandomHexString(40);
            } while (isset($identifiers[$identifier]));
            $connection->update(
                self::TABLE,
                ['identifier' => $identifier],
                ['uid' => $row['uid']],
                [Connection::PARAM_STR, Connection::PARAM_INT],
            );
            $identifiers[$identifier] = true;
        }
        return true;
    }

    /**
     * @return list<array{uid: int, identifier: string}>
     */
    private function duplicateRows(): array
    {
        $connection = $this->connection();
        if (!$connection->createSchemaManager()->tablesExist([self::TABLE])) {
            return [];
        }
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'identifier')
            ->from(self::TABLE)
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $seen = [];
        $duplicates = [];
        foreach ($rows as $row) {
            if (isset($seen[$row['identifier']])) {
                $duplicates[] = ['uid' => (int)$row['uid'], 'identifier' => $row['identifier']];
            }
            $seen[$row['identifier']] = true;
        }
        return $duplicates;
    }

    private function connection(): Connection
    {
        return $this->connectionPool->getConnectionForTable(self::TABLE);
    }
}
