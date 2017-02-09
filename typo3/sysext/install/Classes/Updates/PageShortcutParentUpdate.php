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
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Update all pages which have set the shortcut mode "Parent of selected or current page"
 * (PageRepository::SHORTCUT_MODE_PARENT_PAGE)to remove a possibly selected page as this
 * would cause a different behaviour of the shortcut now since the selected page is now
 * respected in this shortcut mode.
 */
class PageShortcutParentUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Update page shortcuts with shortcut type "Parent of selected or current page"';

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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $numberOfAffectedPages = $queryBuilder->count('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->neq('shortcut', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq(
                    'shortcut_mode',
                    $queryBuilder->createNamedParameter(PageRepository::SHORTCUT_MODE_PARENT_PAGE, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        if ($numberOfAffectedPages > 0) {
            $description = 'There are some shortcut pages that need to be updated in order to preserve their current'
                . ' behaviour.';
        }

        return (bool)$numberOfAffectedPages;
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->update('pages')
            ->where(
                $queryBuilder->expr()->neq('shortcut', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq(
                    'shortcut_mode',
                    $queryBuilder->createNamedParameter(PageRepository::SHORTCUT_MODE_PARENT_PAGE, \PDO::PARAM_INT)
                )
            )
            ->set('shortcut', 0, false);
        $databaseQueries[] = $queryBuilder->getSQL();
        $queryBuilder->execute();
        $this->markWizardAsDone();
        return true;
    }
}
