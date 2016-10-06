<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandler\General;

/**
 * Extended by other provider that fetch records from database
 */
abstract class AbstractDatabaseRecordProvider
{
    /**
     * Fetch a record from database. Deleted records will NOT be fetched.
     * Method is similar to BackendUtility::getRecord, but is more picky
     * about input and result.
     *
     * @param string $tableName The table name to fetch record from
     * @param int $uid Uid of record to fetch
     * @return array Fetched record row
     * @throws DatabaseRecordException|\InvalidArgumentException|\UnexpectedValueException|\RuntimeException
     */
    protected function getRecordFromDatabase($tableName, $uid)
    {
        if ($uid <= 0) {
            throw new \InvalidArgumentException(
                '$uid must be positive integer, ' . $uid . ' given',
                1437656456
            );
        }
        $row = $this->getDatabaseRow($tableName, $uid);
        if (empty($row)) {
            // Indicates a runtime error (eg. record was killed by other editor meanwhile) can be caught elsewhere
            // and transformed to a message to the user or something
            throw new DatabaseRecordException(
                'Record with uid ' . $uid . ' from table ' . $tableName . ' not found',
                1437656081,
                null,
                $tableName,
                (int)$uid
            );
        }
        return $row;
    }

    /**
     * Retrieve the requested row from the database
     *
     * @param string $tableName
     * @param int $uid
     * @return array
     */
    protected function getDatabaseRow(string $tableName, int $uid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $row = $queryBuilder->select('*')
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
            ->execute()
            ->fetch();

        return $row ?: [];
    }
}
