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
 */
class MigrateFscStaticTemplateUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Migrate "fluid_styled_content" static template location';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }
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
        if ($elementCount) {
            $description = 'Static templates have been relocated to EXT:fluid_styled_content/Configuration/TypoScript/';
        }
        return (bool)$elementCount;
    }

    /**
     * Performs the database update
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
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
            $databaseQueries[] = $queryBuilder->getSQL();
            $queryBuilder->execute();
        }
        $this->markWizardAsDone();
        return true;
    }
}
