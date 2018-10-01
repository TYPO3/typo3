<?php
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
 * Migrate "fluid_styled_content" static template location
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class MigrateFscStaticTemplateUpdate implements UpgradeWizardInterface
{
    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'migrateFscStaticTemplateUpdate';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Migrate "fluid_styled_content" static template location';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Static templates have been relocated to EXT:fluid_styled_content/Configuration/TypoScript/';
    }

    /**
     * Checks if an update is needed
     *
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_template');
        $queryBuilder->getRestrictions()->removeAll();
        $elementCount = $queryBuilder->count('uid')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like(
                        'constants',
                        $queryBuilder->createNamedParameter('%EXT:fluid_styled_content/Configuration/TypoScript/Static%', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->like(
                        'config',
                        $queryBuilder->createNamedParameter('%EXT:fluid_styled_content/Configuration/TypoScript/Static%', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->like(
                        'include_static_file',
                        $queryBuilder->createNamedParameter('%EXT:fluid_styled_content/Configuration/TypoScript/Static%', \PDO::PARAM_STR)
                    )
                )
            )
            ->execute()->fetchColumn(0);
        return (bool)$elementCount;
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
     * Performs the database update
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_template');
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('uid', 'include_static_file', 'constants', 'config')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like(
                        'constants',
                        $queryBuilder->createNamedParameter('%EXT:fluid_styled_content/Configuration/TypoScript/Static%', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->like(
                        'config',
                        $queryBuilder->createNamedParameter('%EXT:fluid_styled_content/Configuration/TypoScript/Static%', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->like(
                        'include_static_file',
                        $queryBuilder->createNamedParameter('%EXT:fluid_styled_content/Configuration/TypoScript/Static%', \PDO::PARAM_STR)
                    )
                )
            )
            ->execute();
        while ($record = $statement->fetch()) {
            $search = 'EXT:fluid_styled_content/Configuration/TypoScript/Static';
            $replace = 'EXT:fluid_styled_content/Configuration/TypoScript';
            $record['include_static_file'] = str_replace($search, $replace, $record['include_static_file']);
            $record['constants'] = str_replace($search, $replace, $record['constants']);
            $record['config'] = str_replace($search, $replace, $record['config']);
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->update('sys_template')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($record['uid'], \PDO::PARAM_INT)
                    )
                )
                ->set('include_static_file', $record['include_static_file'])
                ->set('constants', $record['constants'])
                ->set('config', $record['config']);
            $queryBuilder->execute();
        }
        return true;
    }
}
