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
 * Migrate backend shortcut urls
 */
class MigrateShortcutUrlsAgainUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Migrate backend shortcut urls';

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
        $shortcutsCount = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_be_shortcuts')
            ->count('uid', 'sys_be_shortcuts', []);
        if ($shortcutsCount > 0) {
            $description = 'Migrate old shortcut urls to the new module urls.';
        }
        return (bool)$shortcutsCount;
    }

    /**
     * Performs the database update if shortcuts are available
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_be_shortcuts');
        $statement = $connection->select(['uid', 'url'], 'sys_be_shortcuts', []);
        while ($shortcut = $statement->fetch()) {
            $decodedUrl = urldecode($shortcut['url']);
            $encodedUrl = str_replace(
                [
                    '/typo3/sysext/cms/layout/db_layout.php?&',
                    '/typo3/sysext/cms/layout/db_layout.php?',
                    '/typo3/file_edit.php?&',
                    // From 7.2 to 7.4
                    'mod.php',
                ],
                [
                    '/typo3/index.php?&M=web_layout&',
                    urlencode('/typo3/index.php?&M=web_layout&'),
                    '/typo3/index.php?&M=file_edit&',
                    // From 7.2 to 7.4
                    'index.php',
                ],
                $decodedUrl
            );
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->update('sys_be_shortcuts')
                ->set('url', $encodedUrl)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($shortcut['uid'], \PDO::PARAM_INT)
                    )
                );
            $databaseQueries[] = $queryBuilder->getSQL();
            $queryBuilder->execute();
        }
        $this->markWizardAsDone();
        return true;
    }
}
