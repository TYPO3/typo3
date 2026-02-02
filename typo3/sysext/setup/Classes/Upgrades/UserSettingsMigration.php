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

namespace TYPO3\CMS\Setup\Upgrades;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Authentication\UserSettingsFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Migrates user profile settings from the serialized "uc" field
 * to the new "user_settings" JSON field.
 *
 * @internal This class is only meant to be used within EXT:setup and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('setup_userSettingsMigration')]
final class UserSettingsMigration implements UpgradeWizardInterface
{
    private const TABLE = 'be_users';

    public function getTitle(): string
    {
        return 'Migrate user profile settings to JSON format';
    }

    public function getDescription(): string
    {
        return 'Extracts profile settings from the serialized "uc" field '
             . 'into the new "user_settings" JSON field for improved structure and security.';
    }

    public function getPrerequisites(): array
    {
        return [DatabaseUpdatedPrerequisite::class];
    }

    public function updateNecessary(): bool
    {
        return $this->hasRecordsToMigrate();
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);
        $factory = GeneralUtility::makeInstance(UserSettingsFactory::class);
        $hasFailures = false;

        foreach ($this->getRecordsToMigrate() as $record) {
            try {
                $uc = @unserialize($record['uc'], ['allowed_classes' => false]);
                if (!is_array($uc)) {
                    continue;
                }

                $userSettings = $factory->createFromUc($uc);

                $connection->update(
                    self::TABLE,
                    ['user_settings' => json_encode($userSettings->toArray(), JSON_THROW_ON_ERROR)],
                    ['uid' => (int)$record['uid']]
                );
            } catch (\Throwable) {
                $hasFailures = true;
            }
        }

        return !$hasFailures;
    }

    private function hasRecordsToMigrate(): bool
    {
        $queryBuilder = $this->getQueryBuilder();
        return (bool)$queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->isNotNull('uc'),
                $queryBuilder->expr()->neq('uc', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->isNull('user_settings')
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function getRecordsToMigrate(): array
    {
        $queryBuilder = $this->getQueryBuilder();
        return $queryBuilder
            ->select('uid', 'uc')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->isNotNull('uc'),
                $queryBuilder->expr()->neq('uc', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->isNull('user_settings')
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
    }
}
