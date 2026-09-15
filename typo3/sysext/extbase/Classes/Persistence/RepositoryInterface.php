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

use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

/**
 * Contract for a repository
 * @template T of object
 */
interface RepositoryInterface
{
    /**
     * Adds an object to this repository.
     *
     * @param T $object The object to add
     */
    public function add($object);

    /**
     * Removes an object from this repository.
     *
     * @param T $object The object to remove
     */
    public function remove($object);

    /**
     * Replaces an existing object with the same identifier by the given object
     *
     * @param T $modifiedObject The modified object
     */
    public function update($modifiedObject);

    /**
     * Returns all objects of this repository.
     *
     * @return iterable<T> The iterable query result
     */
    public function findAll();

    /**
     * Returns the total number objects of this repository.
     *
     * @return int The object count
     */
    public function countAll();

    /**
     * Removes all objects of this repository as if remove() was called for
     * all of them.
     */
    public function removeAll();

    /**
     * Finds an object matching the given identifier.
     *
     * @param int $uid The identifier of the object to find
     * @return T|null The matching object if found, otherwise NULL
     */
    public function findByUid($uid);

    /**
     * Finds an object matching the given identifier.
     *
     * @param mixed $identifier The identifier of the object to find
     * @return T|null The matching object if found, otherwise NULL
     */
    public function findByIdentifier($identifier);

    /**
     * Sets the property names to order the result by per default.
     * Expected like this:
     * array(
     * 'foo' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
     * 'bar' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
     * )
     *
     * @param array $defaultOrderings The property names to order by
     */
    public function setDefaultOrderings(array $defaultOrderings);

    /**
     * Sets the default query settings to be used in this repository
     *
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings The query settings to be used by default
     */
    public function setDefaultQuerySettings(QuerySettingsInterface $defaultQuerySettings);

    /**
     * Returns a query for objects of this repository
     *
     * @return QueryInterface<T>
     */
    public function createQuery();
}
