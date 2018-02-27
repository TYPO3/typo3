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
 * Update sys_language records to use the newly sorting column,
 * set default sorting from title
 */
class LanguageSortingUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Update sorting of sys_language records';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     *
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description): bool
    {
        if ($this->isWizardDone()) {
            $this->markWizardAsDone();

            return false;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_language');
        $hasAffectedRows = (bool)$queryBuilder->count('uid')
            ->from('sys_language')
            ->where(
                $queryBuilder->expr()->eq('sorting', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->isNotNull('sorting')
            )
            ->execute()
            ->fetchColumn(0);

        if ($hasAffectedRows === true) {
            $description = 'The sys_language records have unsorted rows. '
                . ' This upgrade wizard adds values depending on the language title';
        }

        return $hasAffectedRows;
    }

    /**
     * Performs the database update if the sorting field is 0 or null
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     *
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_language');
        $statement = $queryBuilder->select('uid')
            ->from('sys_language')
            ->where(
                $queryBuilder->expr()->eq('sorting', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->orderBy('title')
            ->execute();
        $sortCounter = 128;
        while ($languageRecord = $statement->fetch()) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_language');
            $queryBuilder->update('sys_language')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($languageRecord['uid'], \PDO::PARAM_INT)
                    )
                )
                ->set('sorting', $sortCounter);
            $databaseQueries[] = $queryBuilder->getSQL();
            $queryBuilder->execute();
            $sortCounter *= 2;
        }
        $this->markWizardAsDone();

        return true;
    }
}
