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

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Migrate CTypes 'textmedia' to use 'assets' field instead of 'media'
 */
class MigrateMediaToAssetsForTextMediaCe extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Migrate CTypes textmedia database field "media" to "assets"';

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

        // No need to join the sys_file_references table here as we can rely on the reference
        // counter to check if the wizards has any textmedia content elements to upgrade.
        $queryBuilder= GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $numberOfUpgradeableRecords = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->gt('media', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('textmedia', \PDO::PARAM_STR))
            )
            ->execute()
            ->fetchColumn(0);

        if ($numberOfUpgradeableRecords > 0) {
            $description = 'The extension "fluid_styled_content" is using a new database field for mediafile'
                . ' references. This update wizard migrates these old references to use the new database field.';
        } else {
            $this->markWizardAsDone();
        }

        return (bool)$numberOfUpgradeableRecords;
    }

    /**
     * Performs the database update if old mediafile references are available
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('uid', 'media')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->gt('media', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('textmedia', \PDO::PARAM_STR))
            )
            ->execute();

        while ($content = $statement->fetch()) {
            $queryStack = [];
            // we will split the update in two separate queries, since the two tables
            // can possibly be on two different databases. We therefore have to care for
            // a possible rollback
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tt_content');
            $queryBuilder->update('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($content['uid'], \PDO::PARAM_INT)
                    )
                )
                ->set('media', 0)
                ->set('assets', (int)$content['media']);
            $queryStack[] = $queryBuilder->getSQL();
            try {
                $queryBuilder->execute();
            } catch (DBALException $e) {
                $customMessage = 'MySQL-Error: ' . $queryBuilder->getConnection()->errorInfo();
                return false;
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder->update('sys_file_reference')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter($content['uid'], \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter('tt_content', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter('media', \PDO::PARAM_STR)
                    )
                )
                ->set('fieldname', 'assets');
            $queryStack[] = $queryBuilder->getSQL();
            try {
                $queryBuilder->execute();
            } catch (DBALException $e) {
                $customMessage = 'MySQL-Error: ' . $queryBuilder->getConnection()->errorInfo();
                // if the second query is not successful but the first was we'll have
                // to get back to a consistent state by rolling back the first query.
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('tt_content');
                $queryBuilder->update('tt_content')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($content['uid'], \PDO::PARAM_INT)
                        )
                    )
                    ->set('media', (int)$content['media'])
                    ->execute();
                return false;
            }
            // only if both queries were successful, we add them to the databaseQuery array.
            $databaseQueries = array_merge($databaseQueries, $queryStack);
        }
        $this->markWizardAsDone();
        return true;
    }
}
