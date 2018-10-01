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

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Remove all backend users starting with _cli_
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class CommandLineBackendUserRemovalUpdate implements UpgradeWizardInterface, ChattyInterface, RepeatableInterface, ConfirmableInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Confirmation
     */
    protected $confirmation;

    public function __construct()
    {
        $this->confirmation = new Confirmation(
            'Are you sure?',
            'The following backend users will be removed: ' . implode(', ', $this->getUnneededCommandLineUsers()),
            true
        );
    }

    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'commandLineBackendUserRemovalUpdate';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Remove unneeded CLI backend users';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'The command line interface does not need to have custom _cli_* backend users anymore.'
               . ' They can safely be deleted.';
    }

    /**
     * Checks if an update is needed
     *
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
        $needsExecution = false;
        $usersFound = $this->getUnneededCommandLineUsers();
        if (!empty($usersFound)) {
            $needsExecution = true;
        }
        return $needsExecution;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Performs the database update to set all be_users starting with _CLI_* to deleted
     *
     * @return bool
     */
    public function executeUpdate(): bool
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
        }
        $this->output->writeln('The following backend users have been deleted:');
        foreach ($usersFound as $user) {
            $this->output->writeln('* ' . $user);
        }
        return true;
    }

    /**
     * Find all backend users starting with _CLI_ that are not deleted yet.
     *
     * @return array a list of uids
     */
    protected function getUnneededCommandLineUsers(): array
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

    /**
     * Return a confirmation message instance
     *
     * @return Confirmation
     */
    public function getConfirmation(): Confirmation
    {
        return $this->confirmation;
    }
}
