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

namespace TYPO3\CMS\Core\Upgrades;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Migrates backend user language from "default" to "en".
 *
 * @since 14.2
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('backendUserLanguageMigration')]
final class BackendUserLanguageMigration implements UpgradeWizardInterface
{
    private const TABLE_NAME = 'be_users';

    public function __construct(
        private readonly ConnectionPool $connectionPool
    ) {}

    public function getTitle(): string
    {
        return 'Migrate backend user language from "default" to "en"';
    }

    public function getDescription(): string
    {
        $count = $this->getRecordsToUpdateCount();
        return sprintf(
            'The language key "default" for backend users has been replaced with "en". '
            . 'This wizard migrates %d backend user record(s) to use "en" instead of "default".',
            $count
        );
    }

    public function updateNecessary(): bool
    {
        return $this->getRecordsToUpdateCount() > 0;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function executeUpdate(): bool
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $connection->update(
            self::TABLE_NAME,
            ['lang' => 'en'],
            ['lang' => 'default']
        );
        return true;
    }

    private function getRecordsToUpdateCount(): int
    {
        $queryBuilder = $this->getPreparedQueryBuilder();
        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'lang',
                    $queryBuilder->createNamedParameter('default')
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder;
    }
}
