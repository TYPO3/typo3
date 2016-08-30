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

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * The Extbase Persistence Manager
 *
 * @api
 */
class PersistenceManager implements \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface, \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var array
     */
    protected $newObjects = [];

    /**
     * @var ObjectStorage
     */
    protected $changedObjects;

    /**
     * @var ObjectStorage
     */
    protected $addedObjects;

    /**
     * @var ObjectStorage
     */
    protected $removedObjects;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface
     */
    protected $queryFactory;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface
     */
    protected $backend;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Session
     */
    protected $persistenceSession;

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface $queryFactory
     */
    public function injectQueryFactory(\TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface $backend
     */
    public function injectBackend(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface $backend)
    {
        $this->backend = $backend;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Session $persistenceSession
     */
    public function injectPersistenceSession(\TYPO3\CMS\Extbase\Persistence\Generic\Session $persistenceSession)
    {
        $this->persistenceSession = $persistenceSession;
    }

    /**
     * Create new instance
     */
    public function __construct()
    {
        $this->addedObjects = new ObjectStorage();
        $this->removedObjects = new ObjectStorage();
        $this->changedObjects = new ObjectStorage();
    }

    /**
     * Registers a repository
     *
     * @param string $className The class name of the repository to be registered
     * @return void
     */
    public function registerRepositoryClassName($className)
    {
    }

    /**
     * Returns the number of records matching the query.
     *
     * @param QueryInterface $query
     * @return int
     * @api
     */
    public function getObjectCountByQuery(QueryInterface $query)
    {
        return $this->backend->getObjectCountByQuery($query);
    }

    /**
     * Returns the object data matching the $query.
     *
     * @param QueryInterface $query
     * @return array
     * @api
     */
    public function getObjectDataByQuery(QueryInterface $query)
    {
        return $this->backend->getObjectDataByQuery($query);
    }

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
     * @api
     */
    public function getIdentifierByObject($object)
    {
        return $this->backend->getIdentifierByObject($object);
    }

    /**
     * Returns the object with the (internal) identifier, if it is known to the
     * backend. Otherwise NULL is returned.
     *
     * @param mixed $identifier
     * @param string $objectType
     * @param bool $useLazyLoading Set to TRUE if you want to use lazy loading for this object
     * @return object The object for the identifier if it is known, or NULL
     * @api
     */
    public function getObjectByIdentifier($identifier, $objectType = null, $useLazyLoading = false)
    {
        if (isset($this->newObjects[$identifier])) {
            return $this->newObjects[$identifier];
        }
        if ($this->persistenceSession->hasIdentifier($identifier, $objectType)) {
            return $this->persistenceSession->getObjectByIdentifier($identifier, $objectType);
        } else {
            return $this->backend->getObjectByIdentifier($identifier, $objectType);
        }
    }

    /**
     * Commits new objects and changes to objects in the current persistence
     * session into the backend
     *
     * @return void
     * @api
     */
    public function persistAll()
    {
        // hand in only aggregate roots, leaving handling of subobjects to
        // the underlying storage layer
        // reconstituted entities must be fetched from the session and checked
        // for changes by the underlying backend as well!
        $this->backend->setAggregateRootObjects($this->addedObjects);
        $this->backend->setChangedEntities($this->changedObjects);
        $this->backend->setDeletedEntities($this->removedObjects);
        $this->backend->commit();

        $this->addedObjects = new ObjectStorage();
        $this->removedObjects = new ObjectStorage();
        $this->changedObjects = new ObjectStorage();
    }

    /**
     * Return a query object for the given type.
     *
     * @param string $type
     * @return QueryInterface
     */
    public function createQueryForType($type)
    {
        return $this->queryFactory->create($type);
    }

    /**
     * Adds an object to the persistence.
     *
     * @param object $object The object to add
     * @return void
     * @api
     */
    public function add($object)
    {
        $this->addedObjects->attach($object);
        $this->removedObjects->detach($object);
    }

    /**
     * Removes an object to the persistence.
     *
     * @param object $object The object to remove
     * @return void
     * @api
     */
    public function remove($object)
    {
        if ($this->addedObjects->contains($object)) {
            $this->addedObjects->detach($object);
        } else {
            $this->removedObjects->attach($object);
        }
    }

    /**
     * Update an object in the persistence.
     *
     * @param object $object The modified object
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @api
     */
    public function update($object)
    {
        if ($this->isNewObject($object)) {
            throw new \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException('The object of type "' . get_class($object) . '" given to update must be persisted already, but is new.', 1249479819);
        }
        $this->changedObjects->attach($object);
    }

    /**
     * Injects the Extbase settings, called by Extbase.
     *
     * @param array $settings
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @api
     */
    public function injectSettings(array $settings)
    {
        throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
    }

    /**
     * Initializes the persistence manager, called by Extbase.
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->backend->setPersistenceManager($this);
    }

    /**
     * Clears the in-memory state of the persistence.
     *
     * Managed instances become detached, any fetches will
     * return data directly from the persistence "backend".
     *
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @return void
     */
    public function clearState()
    {
        $this->newObjects = [];
        $this->addedObjects = new ObjectStorage();
        $this->removedObjects = new ObjectStorage();
        $this->changedObjects = new ObjectStorage();
        $this->persistenceSession->destroy();
    }

    /**
     * Checks if the given object has ever been persisted.
     *
     * @param object $object The object to check
     * @return bool TRUE if the object is new, FALSE if the object exists in the persistence session
     * @api
     */
    public function isNewObject($object)
    {
        return $this->persistenceSession->hasObject($object) === false;
    }

    /**
     * Registers an object which has been created or cloned during this request.
     *
     * A "new" object does not necessarily
     * have to be known by any repository or be persisted in the end.
     *
     * Objects registered with this method must be known to the getObjectByIdentifier()
     * method.
     *
     * @param object $object The new object to register
     * @return void
     */
    public function registerNewObject($object)
    {
        $identifier = $this->getIdentifierByObject($object);
        $this->newObjects[$identifier] = $object;
    }

    /**
     * Converts the given object into an array containing the identity of the domain object.
     *
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @param object $object The object to be converted
     * @return array
     * @api
     */
    public function convertObjectToIdentityArray($object)
    {
        throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
    }

    /**
     * Recursively iterates through the given array and turns objects
     * into arrays containing the identity of the domain object.
     *
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @param array $array The array to be iterated over
     * @return array
     * @api
     * @see convertObjectToIdentityArray()
     */
    public function convertObjectsToIdentityArrays(array $array)
    {
        throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
    }

    /**
     * Tear down the persistence
     *
     * This method is called in functional tests to reset the storage between tests.
     * The implementation is optional and depends on the underlying persistence backend.
     *
     * @return void
     */
    public function tearDown()
    {
        if (method_exists($this->backend, 'tearDown')) {
            $this->backend->tearDown();
        }
    }
}
