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

namespace TYPO3\CMS\Extbase\Persistence\Generic;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
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
 * @implements QueryResultInterface<int,TValue>
 */
#[Autoconfigure(public: true, shared: false)]
class QueryResult implements QueryResultInterface
{
    protected DataMapper $dataMapper;
    protected PersistenceManagerInterface $persistenceManager;

    protected ?int $numberOfResults = null;

    /**
     * @var QueryInterface<TValue>|null
     */
    protected ?QueryInterface $query = null;

    /**
     * @var list<TValue>|null
     */
    protected ?array $queryResult = null;

    public function __construct(
        DataMapper $dataMapper,
        PersistenceManagerInterface $persistenceManager
    ) {
        $this->dataMapper = $dataMapper;
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * @param QueryInterface<TValue> $query
     */
    public function setQuery(QueryInterface $query): void
    {
        $this->query = $query;
        $this->dataMapper->setQuery($query);
    }

    /**
     * Loads the objects this QueryResult is supposed to hold
     */
    protected function initialize(): void
    {
        if (!is_array($this->queryResult)) {
            $this->queryResult = $this->dataMapper->map($this->query->getType(), $this->persistenceManager->getObjectDataByQuery($this->query));
        }
    }

    /**
     * Returns a clone of the query object
     *
     * @return QueryInterface<TValue>
     */
    public function getQuery(): QueryInterface
    {
        return clone $this->query;
    }

    /**
     * Returns the first object in the result set
     *
     * @return TValue|null
     */
    public function getFirst(): ?object
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
     * @return list<TValue>
     */
    public function toArray(): array
    {
        $this->initialize();
        return iterator_to_array($this);
    }

    /**
     * This method is needed to implement the ArrayAccess interface,
     * but it isn't very useful as the offset has to be an integer
     *
     * @param array-key $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        $this->initialize();
        return isset($this->queryResult[$offset]);
    }

    /**
     * @param array-key $offset
     * @return TValue|null
     */
    public function offsetGet(mixed $offset): ?object
    {
        $this->initialize();
        return $this->queryResult[$offset] ?? null;
    }

    /**
     * This method has no effect on the persisted objects but only on the result set
     *
     * @param array-key $offset
     * @param TValue $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->initialize();
        $this->numberOfResults = null;
        $this->queryResult[$offset] = $value;
    }

    /**
     * This method has no effect on the persisted objects but only on the result set
     *
     * @param array-key $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->initialize();
        $this->numberOfResults = null;
        unset($this->queryResult[$offset]);
    }

    /**
     * @see Iterator::current()
     * @return TValue|false
     */
    public function current(): object|false
    {
        $this->initialize();
        return current($this->queryResult);
    }

    /**
     * @see Iterator::key()
     */
    public function key(): ?int
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
    public function __wakeup(): void
    {
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManagerInterface::class);
        $this->dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        if ($this->query !== null) {
            // Mirrors setQuery(): the data mapper needs the query to resolve the
            // language aspect and the parent query of lazy relations.
            $this->dataMapper->setQuery($this->query);
        }
    }

    /**
     * @return array{0: non-empty-string}
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function __sleep(): array
    {
        return ['query'];
    }
}
