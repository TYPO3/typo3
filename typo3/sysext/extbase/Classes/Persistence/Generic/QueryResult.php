<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

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

use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * A lazy result list that is returned by Query::execute()
 */
class QueryResult implements QueryResultInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected $dataMapper;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var int|null
     */
    protected $numberOfResults;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\QueryInterface
     */
    protected $query;

    /**
     * @var array
     */
    protected $queryResult;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Constructor
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
     */
    public function __construct(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query)
    {
        $this->query = $query;
    }

    /**
     * Object initialization called when object is created with ObjectManager, after constructor
     */
    public function initializeObject()
    {
        $this->dataMapper = $this->objectManager->get(DataMapper::class, $this->query);
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
     * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
     */
    public function getQuery()
    {
        return clone $this->query;
    }

    /**
     * Returns the first object in the result set
     *
     * @return object
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
    public function count()
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
     * @return bool
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        $this->initialize();
        return isset($this->queryResult[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @see ArrayAccess::offsetGet()
     */
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
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        $this->initialize();
        $this->queryResult[$offset] = $value;
    }

    /**
     * This method has no effect on the persisted objects but only on the result set
     *
     * @param mixed $offset
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        $this->initialize();
        unset($this->queryResult[$offset]);
    }

    /**
     * @return mixed
     * @see Iterator::current()
     */
    public function current()
    {
        $this->initialize();
        return current($this->queryResult);
    }

    /**
     * @return mixed
     * @see Iterator::key()
     */
    public function key()
    {
        $this->initialize();
        return key($this->queryResult);
    }

    /**
     * @see Iterator::next()
     */
    public function next()
    {
        $this->initialize();
        next($this->queryResult);
    }

    /**
     * @see Iterator::rewind()
     */
    public function rewind()
    {
        $this->initialize();
        reset($this->queryResult);
    }

    /**
     * @return bool
     * @see Iterator::valid()
     */
    public function valid()
    {
        $this->initialize();
        return current($this->queryResult) !== false;
    }

    /**
     * Ensures that the objectManager, persistenceManager and dataMapper are back when loading the QueryResult
     * from the cache
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function __wakeup()
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->persistenceManager = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
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
