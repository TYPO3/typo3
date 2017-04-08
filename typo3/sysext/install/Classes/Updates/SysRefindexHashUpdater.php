<?php
declare(strict_types=1);
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
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Storing new hashes without sorting column in sys_refindex
 */
class SysRefindexHashUpdater extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Update the hash field of sys_refindex to exclude the sorting field';

    /**
     * Fields that make up the hash value
     *
     * @var array
     */
    protected $hashMemberFields = [
        'tablename',
        'recuid',
        'field',
        'flexpointer',
        'softref_key',
        'softref_id',
        'deleted',
        'workspace',
        'ref_table',
        'ref_uid',
        'ref_string'
    ];

    /**
     * The new hash version
     *
     * @var int
     */
    protected $hashVersion = 2;

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (true) or not (false)
     * @throws \InvalidArgumentException
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        $description = 'The hash calculation for records within the table sys_refindex was changed'
            . ' to exclude the sorting field. The records need to be updated with a newly calculated hash.<br />'
            . '<b>Important:</b> If this online migration times out you can perform an offline update using the'
            . ' command-line instead of the wizard, by executing the following command: '
            . '<code>TYPO3_PATH_ROOT=$PWD/web vendor/bin/typo3 referenceindex:update</code>';

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_refindex');

        // SQLite does not have any helpful string/hash functions, unless the wizard is marked done
        // we need to assume this updater needs to run.
        if ($connection->getDatabasePlatform() instanceof SqlitePlatform) {
            return true;
        }

        $queryBuilder = $connection->createQueryBuilder();
        $count = (int)$queryBuilder->count('*')
            ->from('sys_refindex')
            ->where($queryBuilder->expr()->neq('hash', $this->calculateHashFragment()))
            ->execute()
            ->fetchColumn(0);

        return $count !== 0;
    }

    /**
     * Performs the hash update for sys_refindex records
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom messages
     *
     * @return bool
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_refindex');
        $queryBuilder = $connection->createQueryBuilder();

        $statement = $queryBuilder->select('hash', ...$this->hashMemberFields)
            ->from('sys_refindex')
            ->where($queryBuilder->expr()->neq('hash', $this->calculateHashFragment()))
            ->execute();

        $updateQueryBuilder = $connection->createQueryBuilder();
        $updateQueryBuilder->update('sys_refindex')
            ->set('hash', $updateQueryBuilder->createPositionalParameter('', \PDO::PARAM_STR), false)
            ->where(
                $updateQueryBuilder->expr()->eq(
                    'hash',
                    $updateQueryBuilder->createPositionalParameter('', \PDO::PARAM_STR)
                )
            );
        $databaseQueries[] = $updateQueryBuilder->getSQL();
        $updateStatement = $connection->prepare($updateQueryBuilder->getSQL());

        $connection->beginTransaction();
        try {
            while ($row = $statement->fetch()) {
                $newHash = md5(implode('///', array_diff_key($row, ['hash' => true])) . '///' . $this->hashVersion);
                $updateStatement->execute([$newHash, $row['hash']]);
            }
            $connection->commit();
            $this->markWizardAsDone();
        } catch (DBALException $e) {
            $connection->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Build the DBMS specific SQL fragment that calculates the MD5 hash for the given fields within the database.
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function calculateHashFragment(): string
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_refindex');
        $databasePlatform = $connection->getDatabasePlatform();

        $quotedFields = array_map(
            function ($fieldName) use ($connection) {
                return sprintf('CAST(%s AS CHAR)', $connection->quoteIdentifier($fieldName));
            },
            $this->hashMemberFields
        );

        // Add the new hash version to the list of fields
        $quotedFields[] = $connection->quote('2');

        if ($databasePlatform instanceof SQLServerPlatform) {
            $concatFragment = sprintf('CONCAT_WS(%s, %s)', $connection->quote('///'), implode(', ', $quotedFields));
            return sprintf(
                'LOWER(CONVERT(NVARCHAR(32),HashBytes(%s, %s), 2))',
                $connection->quote('MD5'),
                $concatFragment
            );
        } elseif ($databasePlatform instanceof SqlitePlatform) {
            // SQLite cannot do MD5 in database, so update all records which have a hash
            return $connection->quote('');
        } else {
            $concatFragment = sprintf('CONCAT_WS(%s, %s)', $connection->quote('///'), implode(', ', $quotedFields));
            return sprintf('LOWER(MD5(%s))', $concatFragment);
        }
    }
}
