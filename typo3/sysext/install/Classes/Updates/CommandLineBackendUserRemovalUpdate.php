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
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Remove all backend users starting with _cli_
 */
class CommandLineBackendUserRemovalUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Remove unneeded CLI backend users';

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
        $needsExecution = false;
        $usersFound = $this->getUnneededCommandLineUsers();
        if (!empty($usersFound)) {
            $needsExecution = true;
            $description = 'The command line interface does not need to have custom _cli_* backend users anymore. They can safely be deleted.';
        }
        return $needsExecution;
    }

    /**
     * Shows information on the next step of the page
     * @param string $formFieldNamePrefix
     * @return string
     */
    public function getUserInput($formFieldNamePrefix)
    {
        $usersFound = $this->getUnneededCommandLineUsers();
        return '<p>The following backend users will be deleted:</p><ul><li>' . implode('</li><li>', $usersFound) . '</li></ul>';
    }

    /**
     * Performs the database update to set all be_users starting with _CLI_* to deleted
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $usersFound = $this->getUnneededCommandLineUsers();
        foreach ($usersFound as $userUid => $username) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
            $queryBuilder->update('be_users')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($userUid, \PDO::PARAM_INT)
                    )
                )
                // "false" is set as third parameter to have the final
                // value in $databaseQueries and not a statement placeholder
                ->set('deleted', 1, false)
                ->execute();
            $databaseQueries[] = $queryBuilder->getSQL();
        }
        $customMessage = '<p>The following backend users have been deleted:</p><ul><li>' . implode('</li><li>', $usersFound) . '</li></ul>';
        $this->markWizardAsDone();
        return true;
    }

    /**
     * Find all backend users starting with _CLI_ that are not deleted yet.
     *
     * @return array a list of uids
     */
    protected function getUnneededCommandLineUsers()
    {
        $commandLineUsers = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('uid', 'username')
            ->from('be_users')
            ->where(
                // Using query builder is complicated in this case. Get it straight, no user input is involved.
                'LOWER(username) LIKE \'_cli_%\'',
                $queryBuilder->expr()->neq(
                    'username',
                    $queryBuilder->createNamedParameter('_cli_', \PDO::PARAM_STR)
                )
            )
            ->execute();

        while ($row = $result->fetch()) {
            $commandLineUsers[$row['uid']] = $row['username'];
        }

        return $commandLineUsers;
    }
}
