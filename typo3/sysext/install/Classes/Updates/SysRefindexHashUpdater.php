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
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
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
        $this->deleteDuplicateRecords();

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
            $customMessage = 'SQL-ERROR: ' . htmlspecialchars($e->getPrevious()->getMessage());
            $connection->rollBack();
            return false;
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

    /**
     * Remove records from the sys_refindex table which will end up with identical hash values
     * when used with hash version 2. These records can show up when the rows are identical in
     * all fields besides hash and sorting. Due to sorting being ignored in the new hash version
     * these will end up having identical hashes and resulting in a DUPLICATE KEY violation due
     * to the hash field being the primary (unique) key.
     */
    public function deleteDuplicateRecords()
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_refindex');

        // Find all rows which are identical except for the hash and sorting value
        $dupesQueryBuilder = $connection->createQueryBuilder();
        $dupesQueryBuilder->select(...$this->hashMemberFields)
            ->addSelectLiteral($dupesQueryBuilder->expr()->min('sorting', 'min_sorting'))
            ->from('sys_refindex')
            ->groupBy(...$this->hashMemberFields)
            ->having(
                $dupesQueryBuilder->expr()->comparison(
                    $dupesQueryBuilder->expr()->count('sorting'),
                    ExpressionBuilder::GT,
                    1
                )
            );

        // Find all hashes for rows which would have identical hashes using the new algorithm.
        // This query will not return the row with the lowest sorting value. In the next step
        // this will ensure we keep it to be updated to the new hash format.
        $hashQueryBuilder = $connection->createQueryBuilder();
        // Add the derived table for finding identical hashes.
        $hashQueryBuilder->getConcreteQueryBuilder()->from(
            sprintf('(%s)', $dupesQueryBuilder->getSQL()),
            $hashQueryBuilder->quoteIdentifier('t')
        );
        $hashQueryBuilder->select('s.hash')
            ->from('sys_refindex', 's')
            ->where($hashQueryBuilder->expr()->gt('s.sorting', $hashQueryBuilder->quoteIdentifier('t.min_sorting')));

        foreach ($this->hashMemberFields as $field) {
            $hashQueryBuilder->andWhere(
                $hashQueryBuilder->expr()->eq('s.' . $field, $hashQueryBuilder->quoteIdentifier('t.' . $field))
            );
        }

        // Wrap the previous query in another derived table. This indirection is required to use the
        // sys_refindex table in the final delete statement as well as in the subselect used to determine
        // the records to be deleted.
        $selectorQueryBuilder = $connection->createQueryBuilder()->select('d.hash');
        $selectorQueryBuilder->getConcreteQueryBuilder()->from(
            sprintf(('(%s)'), $hashQueryBuilder->getSQL()),
            $selectorQueryBuilder->quoteIdentifier('d')
        );

        $deleteQueryBuilder = $connection->createQueryBuilder();
        $deleteQueryBuilder->delete('sys_refindex')
            ->where(
                $deleteQueryBuilder->expr()->comparison(
                    $deleteQueryBuilder->quoteIdentifier('sys_refindex.hash'),
                    'IN',
                    sprintf('(%s)', $selectorQueryBuilder->getSQL())
                )
            )
            ->execute();
    }
}
