<?php
namespace TYPO3\CMS\Rtehtmlarea\Hook\Install;

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
use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Contains the update class for the replacement of deprecated acronym button by abbreviation button in Page TSconfig.
 * Used by the upgrade wizard in the install tool.
 */
class RteAcronymButtonRenamedToAbbreviation extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Rte "acronym" button renamed to "abbreviation"';

    /**
     * Function which checks if update is needed. Called in the beginning of an update process.
     *
     * @param string $description Pointer to description for the update
     * @return bool TRUE if update is needs to be performed, FALSE otherwise.
     */
    public function checkForUpdate(&$description)
    {
        $result = false;

        $pages = $this->getPagesWithDeprecatedRteProperties($customMessages);
        $pagesCount = count($pages);
        $description = '<p>The RTE "acronym" button is deprecated and replaced by the "abbreviation" button since TYPO3 CMS 7.0.</p>' . LF . '<p>Page TSconfig currently includes the string "acronym" on <strong>' . strval($pagesCount) . '&nbsp;pages</strong>  (including deleted and hidden pages).</p>' . LF;
        if ($pagesCount) {
            $pagesUids = [];
            foreach ($pages as $page) {
                $pagesUids[] = $page['uid'];
            }
            $description .= '<p>Pages id\'s: ' . implode(', ', $pagesUids) . '</p>';
        }
        if ($pagesCount) {
            $updateablePages = $this->findUpdateablePagesWithDeprecatedRteProperties($pages);
            if (!empty($updateablePages)) {
                $description .= '<p>This wizard will perform automatic replacement of the string "acronym" by the string "abbreviation" on the Page TSconfig of <strong>' . strval(count($updateablePages)) . '&nbsp;pages</strong> (including deleted and hidden):</p>' . LF;
            }
            $result = true;
        } else {
            // if we found no occurrence of deprecated settings and wizard was already executed, then
            // we do not show up anymore
            if ($this->isWizardDone()) {
                $result = false;
            }
        }
        $description .= '<p>Only page records are searched for the string "acronym". However, such string may also be used in BE group and BE user records. These are not searched nor updated by this wizard.</p>'
            . LF . '<p>Page TSconfig may also be included from external files. These are not updated by this wizard. If required, the update will need to be done manually.</p>'
            . LF . '<p>Note that this string replacement will apply to all contents of PageTSconfig.</p>'
            . LF . '<p>Note that the configuration of RTE processing options (RTE.default.proc) may also include the string "acronym".</p>';

        return $result;
    }

    /**
     * Performs the update itself
     *
     * @param array $dbQueries Pointer where to insert all DB queries made, so they can be shown to the user if wanted
     * @param string $customMessages Pointer to output custom messages
     * @return bool TRUE if update succeeded, FALSE otherwise
     */
    public function performUpdate(array &$dbQueries, &$customMessages)
    {
        $customMessages = '';
        $pages = $this->getPagesWithDeprecatedRteProperties($customMessages);
        if (empty($customMessages)) {
            $pagesCount = count($pages);
            if ($pagesCount) {
                $updateablePages = $this->findUpdateablePagesWithDeprecatedRteProperties($pages);
                if (!empty($updateablePages)) {
                    $this->updatePages($updateablePages, $dbQueries, $customMessages);
                    // If the update was successful
                    if (empty($customMessages)) {
                        if (count($updateablePages) !== $pagesCount) {
                            $customMessages = 'Some deprecated Page TSconfig properties were found. However, the wizard was unable to automatically replace all the deprecated properties found. Some properties will have to be replaced manually.';
                        }
                    }
                } else {
                    $customMessages = 'Some deprecated Page TSconfig properties were found. However, the wizard was unable to automatically replace any of the deprecated properties found. These properties will have to be replaced manually.';
                }
            }
        }
        $this->markWizardAsDone();
        return empty($customMessages);
    }

    /**
     * Gets the pages with deprecated RTE properties in TSconfig column
     *
     * @param string $customMessages Pointer to output custom messages
     * @return array uid and inclusion string for the pages with deprecated RTE properties in TSconfig column
     */
    protected function getPagesWithDeprecatedRteProperties(&$customMessages)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $isMySQL = strpos($queryBuilder->getConnection()->getServerVersion(), 'MySQL') === 0;
        if ($isMySQL) {
            $whereClause = $queryBuilder->expr()->comparison(
                $queryBuilder->quoteIdentifier('TSconfig'),
                'LIKE BINARY',
                $queryBuilder->createNamedParameter('%acronym%', \PDO::PARAM_STR)
            );
        } else {
            $whereClause = $queryBuilder->expr()->like(
                'TSconfig',
                $queryBuilder->createNamedParameter('%acronym%', \PDO::PARAM_STR)
            );
        }

        try {
            return $queryBuilder
                ->select('uid', 'TSconfig')
                ->from('pages')
                ->where($whereClause)
                ->execute()
                ->fetchAll();
        } catch (DBALException $e) {
            $customMessages = 'SQL-ERROR: ' . htmlspecialchars($e->getPrevious()->getMessage());
        }

        return [];
    }

    /**
     * Gets the pages with updateable deprecated RTE properties in TSconfig column
     *
     * @param array $pages reference to pages with deprecated property
     * @return array uid and inclusion string for the pages with deprecated RTE properties in TSconfig column
     */
    protected function findUpdateablePagesWithDeprecatedRteProperties(&$pages)
    {
        foreach ($pages as $index => $page) {
            $updatedPageTSConfig = str_replace('acronym', 'abbreviation', $page['TSconfig']);
            if ($updatedPageTSConfig === $page['TSconfig']) {
                unset($pages[$index]);
            } else {
                $pages[$index]['TSconfig'] = $updatedPageTSConfig;
            }
        }
        return $pages;
    }

    /**
     * updates the pages records with updateable Page TSconfig properties
     *
     * @param array $pages Page records to update, fetched by getTemplates() and filtered by
     * @param array $dbQueries Pointer where to insert all DB queries made, so they can be shown to the user if wanted
     * @param string $customMessages Pointer to output custom messages
     */
    protected function updatePages($pages, &$dbQueries, &$customMessages)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        foreach ($pages as $page) {
            try {
                $queryBuilder->update('pages')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($page['uid'], \PDO::PARAM_INT)
                        )
                    )
                    ->set('TSconfig', $page['TSconfig'])
                    ->execute();
            } catch (DBALException $e) {
                $customMessages .= 'SQL-ERROR: ' . htmlspecialchars($e->getPrevious()->getMessage()) . LF . LF;
            }
            $dbQueries[] = str_replace(LF, ' ', $queryBuilder->getSQL());
        }
    }
}
