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
 * Fill translation source field (l10n_source)
 */
class FillTranslationSourceField extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Fill translation source field (l10n_source)';

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
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $query = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where($queryBuilder->expr()->andX(
                $queryBuilder->expr()->gt('l18n_parent', $queryBuilder->createNamedParameter(0)),
                $queryBuilder->expr()->eq('l10n_source', $queryBuilder->createNamedParameter(0))
            ));
        $count = (int)$query->execute()->fetchColumn(0);

        if ($count > 0) {
            $description = 'Fill translation source field (l10n_source) for tt_contents which have l18n_parent set.';
        }
        return (bool)$count;
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
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->update('tt_content', 't')
            ->set('t.l10n_source', 't.l18n_parent', false)
            ->where($queryBuilder->expr()->andX(
                $queryBuilder->expr()->gt('t.l18n_parent', $queryBuilder->createNamedParameter(0)),
                $queryBuilder->expr()->eq('t.l10n_source', $queryBuilder->createNamedParameter(0))
            ));
        $databaseQueries[] = $queryBuilder->getSQL();
        $queryBuilder->execute();
        $this->markWizardAsDone();
        return true;
    }
}
