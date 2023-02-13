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

namespace TYPO3\CMS\Extbase\Persistence\Generic;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * A lazy result list that is returned by Query::execute()
 *
 * @todo v12: Candidate to declare final - Can be decorated or standalone class implementing the interface
 * @template TValue of object
 * @implements QueryResultInterface<mixed,TValue>
 */
class QueryResult implements QueryResultInterface
{
    protected DataMapper $dataMapper;
    protected PersistenceManagerInterface $persistenceManager;

    /**
     * @var int|null
     */
    protected $numberOfResults;

    /**
     * @phpstan-var QueryInterface<TValue>|null
     */
    protected ?QueryInterface $query = null;

    /**
     * @var array|null
     * @phpstan-var list<TValue>|null
     */
    protected $queryResult;

    public function __construct(
        DataMapper $dataMapper,
        PersistenceManagerInterface $persistenceManager
    ) {
        $this->dataMapper = $dataMapper;
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * @phpstan-param QueryInterface<TValue> $query
     */
    public function setQuery(QueryInterface $query): void
    {
        $this->query = $query;
        $this->dataMapper->setQuery($query);
    }

    /**
     * Loads the objects this QueryResult is supposed to hold
     */
    protected function initialize()
    {
        if (!is_array($this->queryResult)) {
            $this->queryResult = $this->dataMapper->map($this->query->getType(), $this->persistenceManager->getObjectDataByQuery($this->query));
        }
    }

    /**
     * Returns a clone of the query object
     *
     * @return QueryInterface
     * @phpstan-return QueryInterface<TValue>
     */
    public function getQuery()
    {
        return clone $this->query;
    }

    /**
     * Returns the first object in the result set
     *
     * @return object
     * @phpstan-return TValue|null
     */
    public function getFirst()
    {
        if (is_array($this->queryResult)) {
            $queryResult = $this->queryResult;
            reset($queryResult);
        } else {
            $query = $this->getQuery();
            $query->setLimit(1);
            $queryResult = $this->dataMapper->map($query->getType(), $this->persistenceManager->getObjectDataByQuery($query));
        }
        $firstResult = current($queryResult);
        if ($firstResult === false) {
            $firstResult = null;
        }
        return $firstResult;
    }

    /**
     * Returns the number of objects in the result
     *
     * @return int The number of matching objects
     */
    public function count(): int
    {
        if ($this->numberOfResults === null) {
            if (is_array($this->queryResult)) {
                $this->numberOfResults = count($this->queryResult);
            } else {
                $this->numberOfResults = $this->persistenceManager->getObjectCountByQuery($this->query);
            }
        }
        return $this->numberOfResults;
    }

    /**
     * Returns an array with the objects in the result set
     *
     * @return array
     * @phpstan-return list<TValue>
     */
    public function toArray()
    {
        $this->initialize();
        return iterator_to_array($this);
    }

    /**
     * This method is needed to implement the ArrayAccess interface,
     * but it isn't very useful as the offset has to be an integer
     *
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        $this->initialize();
        return isset($this->queryResult[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @phpstan-return TValue|null
     * @todo: Set return type to mixed in v13
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $this->initialize();
        return $this->queryResult[$offset] ?? null;
    }

    /**
     * This method has no effect on the persisted objects but only on the result set
     *
     * @param mixed $offset
     * @param mixed $value
     * @phpstan-param TValue $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->initialize();
        $this->numberOfResults = null;
        $this->queryResult[$offset] = $value;
    }

    /**
     * This method has no effect on the persisted objects but only on the result set
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        $this->initialize();
        $this->numberOfResults = null;
        unset($this->queryResult[$offset]);
    }

    /**
     * @return mixed
     * @see Iterator::current()
     * @todo: Set return type to mixed in v13
     * @phpstan-return TValue|false
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        $this->initialize();
        return current($this->queryResult);
    }

    /**
     * @return mixed
     * @see Iterator::key()
     * @todo: Set return type to mixed in v13
     * @phpstan-return int|null
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        $this->initialize();
        return key($this->queryResult);
    }

    /**
     * @see Iterator::next()
     */
    public function next(): void
    {
        $this->initialize();
        next($this->queryResult);
    }

    /**
     * @see Iterator::rewind()
     */
    public function rewind(): void
    {
        $this->initialize();
        reset($this->queryResult);
    }

    /**
     * @see Iterator::valid()
     */
    public function valid(): bool
    {
        $this->initialize();
        return current($this->queryResult) !== false;
    }

    /**
     * Ensures that the persistenceManager and dataMapper are back when loading the QueryResult
     * from the cache
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function __wakeup()
    {
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManagerInterface::class);
        $this->dataMapper = GeneralUtility::makeInstance(DataMapper::class);
    }

    /**
     * @return array
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function __sleep()
    {
        return ['query'];
    }
}
