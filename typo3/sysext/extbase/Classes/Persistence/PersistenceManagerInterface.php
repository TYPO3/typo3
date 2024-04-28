<?php

declare(strict_types=1);

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
    public function persistAll(): void;

    /**
     * Clears the in-memory state of the persistence.
     *
     * Managed instances become detached, any fetches will
     * return data directly from the persistence "backend".
     */
    public function clearState(): void;

    /**
     * Checks if the given object has ever been persisted.
     *
     * @param object $object The object to check
     * @return bool TRUE if the object is new, FALSE if the object exists in the repository
     */
    public function isNewObject(object $object): bool;

    /**
     * Returns the (internal) identifier for the object, if it is known to the
     * backend. Otherwise NULL is returned.
     *
     * Note: this returns an identifier even if the object has not been
     * persisted in case of AOP-managed entities. Use isNewObject() if you need
     * to distinguish those cases.
     *
     * @param object $object
     * @return string|null The identifier for the object if it is known, or NULL
     */
    public function getIdentifierByObject(object $object): ?string;

    /**
     * Returns the object with the (internal) identifier, if it is known to the
     * backend. Otherwise NULL is returned.
     *
     * @param bool $useLazyLoading Set to TRUE if you want to use lazy loading for this object
     * @return object|null The object for the identifier if it is known, or NULL
     */
    public function getObjectByIdentifier(string|int $identifier, ?string $objectType = null, bool $useLazyLoading = false): ?object;

    /**
     * Returns the number of records matching the query.
     */
    public function getObjectCountByQuery(QueryInterface $query): int;

    /**
     * Returns the object data matching the $query.
     */
    public function getObjectDataByQuery(QueryInterface $query): array;

    /**
     * Registers a repository
     *
     * @param string $className The class name of the repository to be registered
     */
    public function registerRepositoryClassName(string $className): void;

    /**
     * Adds an object to the persistence.
     *
     * @param object $object The object to add
     */
    public function add(object $object): void;

    /**
     * Removes an object to the persistence.
     *
     * @param object $object The object to remove
     */
    public function remove(object $object): void;

    /**
     * Update an object in the persistence.
     *
     * @param object $object The modified object
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function update(object $object): void;

    /**
     * Return a query object for the given type.
     *
     * @internal only to be used within Extbase, not part of TYPO3 Core API
     * .
     * @template T of object
     * @param class-string<T> $type
     * @return QueryInterface<T>
     */
    public function createQueryForType(string $type): QueryInterface;
}
