<?php
declare(strict_types = 1);

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Update the backend user "uc" array to use arrays for its structure, as old TYPO3 versions sometimes used stdClasses
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class BackendUserConfigurationUpdate implements UpgradeWizardInterface
{
    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'backendUsersConfiguration';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Update backend user configuration array';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'The backend user "uc" array, which is persisted in the db, now only allows for'
            . ' arrays inside its structure instead of stdClass objects.'
            . ' Update the uc structure for all backend users.';
    }

    /**
     * Checks if an update is needed
     *
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
        $statement = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_users')
            ->select(['uid', 'uc'], 'be_users', []);
        $needsExecution = false;
        while ($backendUser = $statement->fetch()) {
            if ($backendUser['uc'] !== null) {
                $userConfig = unserialize($backendUser['uc']);

                array_walk_recursive($userConfig, function (&$item, $_) use (&$needsExecution) {
                    if ($item instanceof \stdClass) {
                        $needsExecution = true;
                    }
                });

                if ($needsExecution) {
                    break;
                }
            }
        }
        return $needsExecution;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Performs the database update for be_users
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $statement = $queryBuilder->select('uid', 'uc')->from('be_users')->execute();
        while ($backendUser = $statement->fetch()) {
            if ($backendUser['uc'] !== null) {
                $userConfig = unserialize($backendUser['uc']);

                array_walk_recursive($userConfig, function (&$item, $_) {
                    if ($item instanceof \stdClass) {
                        $item = json_decode(json_encode($item), true);
                    }
                });

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
                $queryBuilder->update('be_users')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($backendUser['uid'], \PDO::PARAM_INT)
                        )
                    )
                    ->set('uc', serialize($userConfig))
                    ->execute();
            }
        }
        return true;
    }
}
