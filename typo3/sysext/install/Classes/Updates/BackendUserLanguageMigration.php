<?php

declare(strict_types=1);

namespace TYPO3\CMS\Install\Updates;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class BackendUserLanguageMigration implements UpgradeWizardInterface
{
    private const TABLE_NAME = 'be_users';

    public function getIdentifier(): string
    {
        return 'backendUserLanguage';
    }

    public function getTitle(): string
    {
        return 'Migrate backend users\' selected UI languages to new format.';
    }

    public function getDescription(): string
    {
        return 'Backend users now keep their preferred UI language for TYPO3 Backend in its own database field. This updates all backend user records.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->hasRecordsToUpdate();
    }

    public function executeUpdate(): bool
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME);

        foreach ($this->getRecordsToUpdate() as $record) {
            $currentDatabaseFieldValue = (string)($record['lang'] ?? '');
            $uc = unserialize($user['uc'] ?? '', ['allowed_classes' => false]);
            // Check if the user has a preference set, otherwise use the default from the database field
            // however, "default" is now explicitly set.
            $selectedLanguage = $uc['lang'] ?? $currentDatabaseFieldValue;
            if ($selectedLanguage === '') {
                $selectedLanguage = 'default';
            }

            // Everything set already in the DB field, so this can be skipped
            if ($selectedLanguage === $currentDatabaseFieldValue) {
                continue;
            }
            $connection->update(
                self::TABLE_NAME,
                ['lang' => $selectedLanguage],
                ['uid' => (int)$record['uid']]
            );
        }
        return true;
    }

    protected function hasRecordsToUpdate(): bool
    {
        $queryBuilder = $this->getPreparedQueryBuilder();
        return (bool)$queryBuilder
            ->count('uid')
            ->executeQuery()
            ->fetchOne();
    }

    protected function getRecordsToUpdate(): array
    {
        return $this->getPreparedQueryBuilder()->select(...['uid', 'uc', 'lang'])->executeQuery()->fetchAllAssociative();
    }

    protected function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->from(self::TABLE_NAME);
        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
