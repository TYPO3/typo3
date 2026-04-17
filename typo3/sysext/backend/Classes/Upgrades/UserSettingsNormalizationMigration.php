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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

/**
 * Migrate double-json encoded user settings to single-encoded user settings.
 *
 * @since 14.2
 * @internal This class is only meant to be used within EXT:backend and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('setup_userSettingsCleanUpMigration')]
final readonly class UserSettingsNormalizationMigration implements UpgradeWizardInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate double json encoded user_settings to valid json encoded values.';
    }

    public function getDescription(): string
    {
        return 'Finds double json_encoded user settings and write it single encoded.';
    }

    public function getPrerequisites(): array
    {
        return [DatabaseUpdatedPrerequisite::class];
    }

    public function executeUpdate(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $connection = $queryBuilder->getConnection();
        $result = $queryBuilder
            ->select('uid', 'user_settings')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->like('user_settings', $queryBuilder->quote('"{%}"')),
            )
            ->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $userSettings = json_decode(
                json: json_decode(
                    json: $row['user_settings'],
                    flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY
                ),
                flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY,
            );
            if (!is_array($userSettings)) {
                continue;
            }
            $connection->update(
                'be_users',
                [
                    // The array must be passed directly to prevent double JSON encoding.
                    // See: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293
                    'user_settings' => $userSettings,
                ],
                [
                    'uid' => (int)($row['uid']),
                ],
                [
                    // @todo This behavior cannot be modified yet; the array value must be passed directly
                    //       until https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293 is merged,
                    //       otherwise the value will be JSON-encoded twice.
                    'user_settings' => Type::getType(Types::JSON),
                ],
            );
        }
        return $this->updateNecessary() === false;
    }

    public function updateNecessary(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        return (bool)($queryBuilder
            ->count('*')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->like('user_settings', $queryBuilder->quote('"{%}"')),
            )
            ->executeQuery()
            ->fetchOne());
    }
}
