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

use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

/**
 * Removes confidential data "password" and "password2" from user settings.
 *
 * @since 14.3
 * @internal This class is only meant to be used within EXT:backend and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('setup_userSettingsScrubbingMigration')]
final readonly class UserSettingsScrubbingMigration implements UpgradeWizardInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Scrub user settings to remove confidential credential data from serialized data (uc and user_settings)';
    }

    public function getDescription(): string
    {
        return 'Evaluates all uc and user_settings profile data in the "be_users" database table, removes "password" and "password2" field contents.';
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

        $result = $this->getRecordsToMigrate();
        while ($record = $result->fetchAssociative()) {
            try {
                // Redact "user_settings" (may fall back to "uc" for data)
                $userSettings = $record['user_settings'];
                if (!is_string($userSettings)) {
                    continue;
                }
                $userSettings = json_decode(json: $userSettings, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
                if (is_string($userSettings)) {
                    // https://review.typo3.org/c/Packages/TYPO3.CMS/+/93395 introduced a double `json_encode()` issue,
                    // which is resolved here as well. The fallback remains in place in case the generic cleanup upgrade
                    // wizard from https://review.typo3.org/c/Packages/TYPO3.CMS/+/93726 has not been executed yet,
                    // ensuring affected records are cleaned up during this step.
                    $userSettings = json_decode(json: $userSettings, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
                }
                if (!is_array($userSettings)) {
                    $hasFailures = true;
                    continue;
                }
                // Remove invalid entries.
                unset($userSettings['password'], $userSettings['password2']);

                $uc = @unserialize($record['uc'], ['allowed_classes' => false]);
                // Update "uc", only if unserializable - redact two keys, only if set.
                if (is_array($uc)) {
                    // Remove invalid entries.
                    unset($uc['password'], $uc['password2']);
                    $connection->update(
                        'be_users',
                        [
                            'uc' => serialize($uc),
                            // The array must be passed directly to prevent double JSON encoding.
                            // See: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293
                            'user_settings' => $userSettings,
                        ],
                        [
                            'uid' => (int)$record['uid'],
                        ],
                        [
                            'uc' => Connection::PARAM_LOB,
                            // @todo This behavior cannot be modified yet; the array value must be passed directly
                            //       until https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293 is merged,
                            //       otherwise the value will be JSON-encoded twice.
                            'user_settings' => Type::getType(Types::JSON),
                        ],
                    );
                } else {
                    // No "uc" serialization, only update user_settings.
                    $connection->update(
                        'be_users',
                        [
                            'user_settings' => $userSettings,
                        ],
                        ['uid' => (int)$record['uid']],
                        [
                            // @todo This behavior cannot be modified yet; the array value must be passed directly
                            //       until https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293 is merged,
                            //       otherwise the value will be JSON-encoded twice.
                            'user_settings' => Type::getType(Types::JSON),
                        ],
                    );
                }
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
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->like('uc', $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards(':"password') . '%')),
                    $queryBuilder->expr()->like('user_settings', $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards('"password') . '%')),
                ),
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function getRecordsToMigrate(): Result
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        return $queryBuilder
            ->select('uid', 'uc', 'user_settings')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->like('uc', $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards(':"password') . '%')),
                    $queryBuilder->expr()->like('user_settings', $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards('"password') . '%')),
                ),
            )
            ->executeQuery();
    }
}
