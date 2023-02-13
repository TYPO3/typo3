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
 * A lazy result list that is returned by Query::execute()
 * @template TKey
 * @template TValue of object
 * @extends \Iterator<TKey,TValue>
 * @extends \ArrayAccess<TKey,TValue>
 */
interface QueryResultInterface extends \Countable, \Iterator, \ArrayAccess
{
    /**
     * @phpstan-param QueryInterface<TValue> $query
     */
    public function setQuery(QueryInterface $query): void;

    /**
     * Returns a clone of the query object
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
     * @phpstan-return QueryInterface<TValue>
     */
    public function getQuery();

    /**
     * Returns the first object in the result set
     *
     * @return object|null
     * @phpstan-return TValue|null
     */
    public function getFirst();

    /**
     * Returns an array with the objects in the result set
     *
     * @return array
     * @phpstan-return list<TValue>
     */
    public function toArray();
}
