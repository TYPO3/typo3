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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

/**
 * The base repository - will usually be extended by a more concrete repository.
 */
class Repository implements RepositoryInterface, SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @deprecated since v11, will be removed in v12
     */
    protected $objectManager;

    /**
     * @var class-string
     */
    protected $objectType;

    /**
     * @var array<non-empty-string, QueryInterface::ORDER_*>
     */
    protected $defaultOrderings = [];

    /**
     * Override query settings created by extbase natively.
     * Be careful if using this, see the comment on `setDefaultQuerySettings()` for more insights.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
     */
    protected $defaultQuerySettings;

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Constructs a new Repository
     *
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        // @deprecated since v11, will be removed in v12
        $this->objectManager = $objectManager;
        $this->objectType = ClassNamingUtility::translateRepositoryNameToModelName($this->getRepositoryClassName());
    }

    /**
     * Adds an object to this repository
     *
     * @param object $object The object to add
     * @throws Exception\IllegalObjectTypeException
     */
    public function add($object)
    {
        if (!$object instanceof $this->objectType) {
            throw new IllegalObjectTypeException('The object given to add() was not of the type (' . $this->objectType . ') this repository manages.', 1248363335);
        }
        $this->persistenceManager->add($object);
    }

    /**
     * Removes an object from this repository.
     *
     * @param object $object The object to remove
     * @throws Exception\IllegalObjectTypeException
     */
    public function remove($object)
    {
        if (!$object instanceof $this->objectType) {
            throw new IllegalObjectTypeException('The object given to remove() was not of the type (' . $this->objectType . ') this repository manages.', 1248363336);
        }
        $this->persistenceManager->remove($object);
    }

    /**
     * Replaces an existing object with the same identifier by the given object
     *
     * @param object $modifiedObject The modified object
     * @throws Exception\UnknownObjectException
     * @throws Exception\IllegalObjectTypeException
     */
    public function update($modifiedObject)
    {
        if (!$modifiedObject instanceof $this->objectType) {
            throw new IllegalObjectTypeException('The modified object given to update() was not of the type (' . $this->objectType . ') this repository manages.', 1249479625);
        }
        $this->persistenceManager->update($modifiedObject);
    }

    /**
     * Returns all objects of this repository.
     *
     * @return QueryResultInterface|array
     */
    public function findAll()
    {
        return $this->createQuery()->execute();
    }

    /**
     * Returns the total number objects of this repository.
     *
     * @return int The object count
     */
    public function countAll()
    {
        return $this->createQuery()->execute()->count();
    }

    /**
     * Removes all objects of this repository as if remove() was called for
     * all of them.
     */
    public function removeAll()
    {
        foreach ($this->findAll() as $object) {
            $this->remove($object);
        }
    }

    /**
     * Finds an object matching the given identifier.
     *
     * @param int $uid The identifier of the object to find
     * @return object|null The matching object if found, otherwise NULL
     */
    public function findByUid($uid)
    {
        return $this->findByIdentifier($uid);
    }

    /**
     * Finds an object matching the given identifier.
     *
     * @param mixed $identifier The identifier of the object to find
     * @return object|null The matching object if found, otherwise NULL
     */
    public function findByIdentifier($identifier)
    {
        return $this->persistenceManager->getObjectByIdentifier($identifier, $this->objectType);
    }

    /**
     * Sets the property names to order the result by per default.
     * Expected like this:
     * array(
     * 'foo' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
     * 'bar' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
     * )
     *
     * @param array<non-empty-string, QueryInterface::ORDER_*> $defaultOrderings The property names to order by
     */
    public function setDefaultOrderings(array $defaultOrderings)
    {
        $this->defaultOrderings = $defaultOrderings;
    }

    /**
     * Sets the default query settings to be used in this repository.
     *
     * A typical use case is an initializeObject() method that creates a QuerySettingsInterface
     * object, configures it and sets it to be used for all queries created by the repository.
     *
     * Warning: Using this setter *fully overrides* native query settings created by
     * QueryFactory->create(). This especially means that storagePid settings from
     * configuration are not applied anymore, if not explicitly set. Make sure to apply these
     * to your own QuerySettingsInterface object if needed, when using this method.
     */
    public function setDefaultQuerySettings(QuerySettingsInterface $defaultQuerySettings)
    {
        $this->defaultQuerySettings = $defaultQuerySettings;
    }

    /**
     * Returns a query for objects of this repository
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
     */
    public function createQuery()
    {
        $query = $this->persistenceManager->createQueryForType($this->objectType);
        if ($this->defaultOrderings !== []) {
            $query->setOrderings($this->defaultOrderings);
        }
        if ($this->defaultQuerySettings !== null) {
            $query->setQuerySettings(clone $this->defaultQuerySettings);
        }
        return $query;
    }

    /**
     * Dispatches magic methods (findBy[Property]())
     *
     * @param non-empty-string $methodName The name of the magic method
     * @param array<int, mixed> $arguments The arguments of the magic method
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException
     * @return mixed
     */
    public function __call($methodName, $arguments)
    {
        if (strpos($methodName, 'findBy') === 0 && strlen($methodName) > 7) {
            $propertyName = lcfirst(substr($methodName, 6));
            $query = $this->createQuery();
            $result = $query->matching($query->equals($propertyName, $arguments[0]))->execute();
            return $result;
        }
        if (strpos($methodName, 'findOneBy') === 0 && strlen($methodName) > 10) {
            $propertyName = lcfirst(substr($methodName, 9));
            $query = $this->createQuery();

            $result = $query->matching($query->equals($propertyName, $arguments[0]))->setLimit(1)->execute();
            if ($result instanceof QueryResultInterface) {
                return $result->getFirst();
            }
            if (is_array($result)) {
                return $result[0] ?? null;
            }
        } elseif (strpos($methodName, 'countBy') === 0 && strlen($methodName) > 8) {
            $propertyName = lcfirst(substr($methodName, 7));
            $query = $this->createQuery();
            $result = $query->matching($query->equals($propertyName, $arguments[0]))->execute()->count();
            return $result;
        }
        throw new UnsupportedMethodException('The method "' . $methodName . '" is not supported by the repository.', 1233180480);
    }

    /**
     * Returns the class name of this class.
     *
     * @return class-string<RepositoryInterface> Class name of the repository.
     */
    protected function getRepositoryClassName()
    {
        return static::class;
    }
}
