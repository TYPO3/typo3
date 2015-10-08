<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Storage;

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

/**
 * Storage backend interface
 */
interface BackendInterface
{
    /**
     * Adds a row to the storage
     *
     * @param string $tableName The database table name
     * @param array $fieldValues The fieldValues to insert
     * @param bool $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
     * @return int the UID of the inserted row
     */
    public function addRow($tableName, array $fieldValues, $isRelation = false);

    /**
     * Updates a row in the storage
     *
     * @param string $tableName The database table name
     * @param array $fieldValues The fieldValues to update
     * @param bool $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
     * @return mixed|void
     */
    public function updateRow($tableName, array $fieldValues, $isRelation = false);

    /**
     * Updates a relation row in the storage
     *
     * @param string $tableName The database relation table name
     * @param array $fieldValues The fieldValues to be updated
     * @return bool
     */
    public function updateRelationTableRow($tableName, array $fieldValues);

    /**
     * Deletes a row in the storage
     *
     * @param string $tableName The database table name
     * @param array $where An array of where array('fieldname' => value). This array will be transformed to a WHERE clause
     * @param bool $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
     * @return mixed|void
     */
    public function removeRow($tableName, array $where, $isRelation = false);

    /**
     * Fetches maximal value for given table column
     *
     * @param string $tableName The database table name
     * @param array $where An array of where array('fieldname' => value). This array will be transformed to a WHERE clause
     * @param string $columnName column name to get the max value from
     * @return mixed the max value
     */
    public function getMaxValueFromTable($tableName, array $where, $columnName);

    /**
     * Returns the number of items matching the query.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
     * @return int
     * @api
     */
    public function getObjectCountByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query);

    /**
     * Returns the object data matching the $query.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
     * @return array
     * @api
     */
    public function getObjectDataByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query);

    /**
     * Checks if a Value Object equal to the given Object exists in the data base
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject $object The Value Object
     * @return mixed The matching uid if an object was found, else FALSE
     * @todo this is the last monster in this persistence series. refactor!
     */
    public function getUidOfAlreadyPersistedValueObject(\TYPO3\CMS\Extbase\DomainObject\AbstractValueObject $object);
}
