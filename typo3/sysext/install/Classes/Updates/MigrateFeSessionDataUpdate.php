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
 */
class MigrateFeSessionDataUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Migrates existing fe_session_data into fe_sessions';

    /**
     * Checks if an update is needed
     *
     * @param string $description The description for the update
     *
     * @return bool Whether an update is needed (true) or not (false)
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        if (!$this->checkIfTableExists('fe_session_data')) {
            return false;
        }

        $description = 'With the new Session Framwework the session data is stored in fe_sessions.</p>'
            . ' <b>To avoid that data is truncated, ensure the columns of fe_sessions have been updated.</b></p>'
            . ' This wizard migrates the existing data from fe_session_data into fe_sessions.'
            . ' Existing entries in fe_sessions having an entry in fe_session_data are updated.'
            . ' Entries in fe_session_data not found in fe_sessions are inserted with ses_anonymous = true';

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
     * Moves data from fe_session_data into fe_sessions with respect to ses_anonymous
     *
     * @param array $databaseQueries Queries done in this update
     * @param string $customMessage Custom messages
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
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
        $databaseQueries[] = $queryBuilder->getSQL();

        $updateQueryBuilder = $connection->createQueryBuilder();
        $updateQueryBuilder->update('fe_sessions')
            ->where(
                $updateQueryBuilder->expr()->eq(
                    'ses_id',
                    $updateQueryBuilder->createPositionalParameter('', \PDO::PARAM_STR)
                )
            )
            ->set('ses_data', $updateQueryBuilder->createPositionalParameter('', \PDO::PARAM_STR), false);
        $databaseQueries[] = $updateQueryBuilder->getSQL();
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
        $databaseQueries[] = $insertSQL;

        try {
            $connection->beginTransaction();
            $connection->exec($insertSQL);
            $connection->commit();
        } catch (DBALException $e) {
            $connection->rollBack();
            throw $e;
        }

        $this->markWizardAsDone();
        return true;
    }
}
