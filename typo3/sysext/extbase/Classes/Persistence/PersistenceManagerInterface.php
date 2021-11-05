<?php

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

namespace TYPO3\CMS\Extbase\Persistence;

/**
 * The Extbase Persistence Manager interface
 */
interface PersistenceManagerInterface
{
    /**
     * Commits new objects and changes to objects in the current persistence
     * session into the backend
     */
    public function persistAll();

    /**
     * Clears the in-memory state of the persistence.
     *
     * Managed instances become detached, any fetches will
     * return data directly from the persistence "backend".
     */
    public function clearState();

    /**
     * Checks if the given object has ever been persisted.
     *
     * @param object $object The object to check
     * @return bool TRUE if the object is new, FALSE if the object exists in the repository
     */
    public function isNewObject($object);

    // @todo realign with Flow PersistenceManager again

    /**
     * Returns the (internal) identifier for the object, if it is known to the
     * backend. Otherwise NULL is returned.
     *
     * Note: this returns an identifier even if the object has not been
     * persisted in case of AOP-managed entities. Use isNewObject() if you need
     * to distinguish those cases.
     *
     * @param object $object
     * @return mixed The identifier for the object if it is known, or NULL
     */
    public function getIdentifierByObject($object);

    /**
     * Returns the object with the (internal) identifier, if it is known to the
     * backend. Otherwise NULL is returned.
     *
     * @param mixed $identifier
     * @param string $objectType
     * @param bool $useLazyLoading Set to TRUE if you want to use lazy loading for this object
     * @return object|null The object for the identifier if it is known, or NULL
     */
    public function getObjectByIdentifier($identifier, $objectType = null, $useLazyLoading = false);

    /**
     * Returns the number of records matching the query.
     *
     * @param QueryInterface $query
     * @return int
     */
    public function getObjectCountByQuery(QueryInterface $query);

    /**
     * Returns the object data matching the $query.
     *
     * @param QueryInterface $query
     * @return array
     */
    public function getObjectDataByQuery(QueryInterface $query);

    /**
     * Registers a repository
     *
     * @param string $className The class name of the repository to be registered
     */
    public function registerRepositoryClassName($className);

    /**
     * Adds an object to the persistence.
     *
     * @param object $object The object to add
     */
    public function add($object);

    /**
     * Removes an object to the persistence.
     *
     * @param object $object The object to remove
     */
    public function remove($object);

    /**
     * Update an object in the persistence.
     *
     * @param object $object The modified object
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function update($object);

    /**
     * Return a query object for the given type.
     *
     * @param string $type
     * @return QueryInterface
     */
    public function createQueryForType($type);
}
