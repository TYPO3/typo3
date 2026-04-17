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

namespace TYPO3\CMS\Backend\Upgrades;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Authentication\UserSettingsFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

/**
 * Migrates user profile settings from the serialized "uc" field
 * to the new "user_settings" JSON field.
 *
 * @since 14.2
 * @internal This class is only meant to be used within EXT:backend and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('setup_userSettingsMigration')]
final readonly class UserSettingsMigration implements UpgradeWizardInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private UserSettingsFactory $userSettingsFactory,
    ) {}

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
        $connection = $this->connectionPool->getConnectionForTable('be_users');
        $hasFailures = false;
        foreach ($this->getRecordsToMigrate() as $record) {
            try {
                $uc = @unserialize($record['uc'], ['allowed_classes' => false]);
                if (!is_array($uc)) {
                    continue;
                }
                $userSettings = $this->userSettingsFactory->createFromUc($uc);
                $connection->update(
                    'be_users',
                    [
                        // The array must be passed directly to prevent double JSON encoding.
                        // See: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293
                        'user_settings' => $userSettings->toArray(),
                    ],
                    ['uid' => (int)$record['uid']],
                    [
                        // @todo This behavior cannot be modified yet; the array value must be passed directly
                        //       until https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293 is merged,
                        //       otherwise the value will be JSON-encoded twice.
                        'user_settings' => Type::getType(Types::JSON),
                    ],
                );
            } catch (\Throwable) {
                $hasFailures = true;
            }
        }
        return !$hasFailures;
    }

    private function hasRecordsToMigrate(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        return (bool)$queryBuilder
            ->count('uid')
            ->from('be_users')
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
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        return $queryBuilder
            ->select('uid', 'uc')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->isNotNull('uc'),
                $queryBuilder->expr()->neq('uc', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->isNull('user_settings')
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
