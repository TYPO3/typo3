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

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Merge sessions from old fe_session_data table into new structure from fe_sessions
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class MigrateFeSessionDataUpdate implements UpgradeWizardInterface
{
    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'migrateFeSessionDataUpdate';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Migrates existing fe_session_data into fe_sessions';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'With the new Session Framwework the session data is stored in fe_sessions.'
            . ' To avoid that data is truncated, ensure the columns of fe_sessions have been updated.'
            . ' This wizard migrates the existing data from fe_session_data into fe_sessions.'
            . ' Existing entries in fe_sessions having an entry in fe_session_data are updated.'
            . ' Entries in fe_session_data not found in fe_sessions are inserted with ses_anonymous = true';
    }

    /**
     * Checks if an update is needed
     *
     * @return bool Whether an update is needed (true) or not (false)
     */
    public function updateNecessary(): bool
    {
        if (!$this->checkIfTableExists('fe_session_data')) {
            return false;
        }
        // Check if there is data to migrate
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_session_data');
        $queryBuilder->getRestrictions()->removeAll();
        $count = $queryBuilder->count('*')
            ->from('fe_session_data')
            ->execute()
            ->fetchColumn(0);

        return $count > 0;
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
     * Moves data from fe_session_data into fe_sessions with respect to ses_anonymous
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('fe_sessions');

        // Process records that have entries in fe_sessions and fe_session_data
        $queryBuilder = $connection->createQueryBuilder();
        $statement = $queryBuilder->select('fe_session_data.hash', 'fe_session_data.content')
            ->from('fe_sessions')
            ->join(
                'fe_sessions',
                'fe_session_data',
                'fe_session_data',
                $queryBuilder->expr()->eq(
                    'fe_sessions.ses_id',
                    $queryBuilder->quoteIdentifier('fe_session_data.hash')
                )
            )
            ->execute();

        $updateQueryBuilder = $connection->createQueryBuilder();
        $updateQueryBuilder->update('fe_sessions')
            ->where(
                $updateQueryBuilder->expr()->eq(
                    'ses_id',
                    $updateQueryBuilder->createPositionalParameter('', \PDO::PARAM_STR)
                )
            )
            ->set('ses_data', $updateQueryBuilder->createPositionalParameter('', \PDO::PARAM_STR), false);
        $updateStatement = $connection->prepare($updateQueryBuilder->getSQL());

        $connection->beginTransaction();
        try {
            while ($row = $statement->fetch()) {
                $updateStatement->execute([$row['hash'], $row['content']]);
            }
            $connection->commit();
        } catch (DBALException $e) {
            $connection->rollBack();
            throw $e;
        }

        // Move records from fe_session_data that are not in fe_sessions
        $queryBuilder = $connection->createQueryBuilder();
        $selectSQL = $queryBuilder->select('fe_session_data.hash', 'fe_session_data.content', 'fe_session_data.tstamp')
            ->addSelectLiteral('1')
            ->from('fe_session_data')
            ->leftJoin(
                'fe_session_data',
                'fe_sessions',
                'fe_sessions',
                $queryBuilder->expr()->eq(
                    'fe_session_data.hash',
                    $queryBuilder->quoteIdentifier('fe_sessions.ses_id')
                )
            )
            ->where($queryBuilder->expr()->isNull('fe_sessions.ses_id'))
            ->getSQL();

        $insertSQL = sprintf(
            'INSERT INTO %s(%s, %s, %s, %s) %s',
            $connection->quoteIdentifier('fe_sessions'),
            $connection->quoteIdentifier('ses_id'),
            $connection->quoteIdentifier('ses_data'),
            $connection->quoteIdentifier('ses_tstamp'),
            $connection->quoteIdentifier('ses_anonymous'),
            $selectSQL
        );

        try {
            $connection->beginTransaction();
            $connection->exec($insertSQL);
            $connection->commit();
        } catch (DBALException $e) {
            $connection->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Check if given table exists
     *
     * @param string $table
     * @return bool
     */
    protected function checkIfTableExists($table): bool
    {
        $tableExists = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->getSchemaManager()
            ->tablesExist([$table]);

        return $tableExists;
    }
}
