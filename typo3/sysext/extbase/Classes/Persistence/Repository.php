<?php
namespace TYPO3\CMS\Extbase\Persistence;

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

use TYPO3\CMS\Core\Utility\ClassNamingUtility;

/**
 * The base repository - will usually be extended by a more concrete repository.
 *
 * @api
 */
class Repository implements RepositoryInterface, \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $objectType;

    /**
     * @var array
     */
    protected $defaultOrderings = [];

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
     */
    protected $defaultQuerySettings = null;

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Constructs a new Repository
     *
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->objectType = ClassNamingUtility::translateRepositoryNameToModelName($this->getRepositoryClassName());
    }

    /**
     * Adds an object to this repository
     *
     * @param object $object The object to add
     * @throws Exception\IllegalObjectTypeException
     * @return void
     * @api
     */
    public function add($object)
    {
        if (!$object instanceof $this->objectType) {
            throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException('The object given to add() was not of the type (' . $this->objectType . ') this repository manages.', 1248363335);
        }
        $this->persistenceManager->add($object);
    }

    /**
     * Removes an object from this repository.
     *
     * @param object $object The object to remove
     * @throws Exception\IllegalObjectTypeException
     * @return void
     * @api
     */
    public function remove($object)
    {
        if (!$object instanceof $this->objectType) {
            throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException('The object given to remove() was not of the type (' . $this->objectType . ') this repository manages.', 1248363336);
        }
        $this->persistenceManager->remove($object);
    }

    /**
     * Replaces an existing object with the same identifier by the given object
     *
     * @param object $modifiedObject The modified object
     * @throws Exception\UnknownObjectException
     * @throws Exception\IllegalObjectTypeException
     * @return void
     * @api
     */
    public function update($modifiedObject)
    {
        if (!$modifiedObject instanceof $this->objectType) {
            throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException('The modified object given to update() was not of the type (' . $this->objectType . ') this repository manages.', 1249479625);
        }
        $this->persistenceManager->update($modifiedObject);
    }

    /**
     * Returns all objects of this repository.
     *
     * @return QueryResultInterface|array
     * @api
     */
    public function findAll()
    {
        return $this->createQuery()->execute();
    }

    /**
     * Returns the total number objects of this repository.
     *
     * @return int The object count
     * @api
     */
    public function countAll()
    {
        return $this->createQuery()->execute()->count();
    }

    /**
     * Removes all objects of this repository as if remove() was called for
     * all of them.
     *
     * @return void
     * @api
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
     * @return object The matching object if found, otherwise NULL
     * @api
     */
    public function findByUid($uid)
    {
        return $this->findByIdentifier($uid);
    }

    /**
     * Finds an object matching the given identifier.
     *
     * @param mixed $identifier The identifier of the object to find
     * @return object The matching object if found, otherwise NULL
     * @api
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
     * @param array $defaultOrderings The property names to order by
     * @return void
     * @api
     */
    public function setDefaultOrderings(array $defaultOrderings)
    {
        $this->defaultOrderings = $defaultOrderings;
    }

    /**
     * Sets the default query settings to be used in this repository
     *
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings The query settings to be used by default
     * @return void
     * @api
     */
    public function setDefaultQuerySettings(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings)
    {
        $this->defaultQuerySettings = $defaultQuerySettings;
    }

    /**
     * Returns a query for objects of this repository
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
     * @api
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
     * @param string $methodName The name of the magic method
     * @param string $arguments The arguments of the magic method
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException
     * @return mixed
     * @api
     */
    public function __call($methodName, $arguments)
    {
        if (substr($methodName, 0, 6) === 'findBy' && strlen($methodName) > 7) {
            $propertyName = lcfirst(substr($methodName, 6));
            $query = $this->createQuery();
            $result = $query->matching($query->equals($propertyName, $arguments[0]))->execute();
            return $result;
        } elseif (substr($methodName, 0, 9) === 'findOneBy' && strlen($methodName) > 10) {
            $propertyName = lcfirst(substr($methodName, 9));
            $query = $this->createQuery();

            $result = $query->matching($query->equals($propertyName, $arguments[0]))->setLimit(1)->execute();
            if ($result instanceof QueryResultInterface) {
                return $result->getFirst();
            } elseif (is_array($result)) {
                return isset($result[0]) ? $result[0] : null;
            }
        } elseif (substr($methodName, 0, 7) === 'countBy' && strlen($methodName) > 8) {
            $propertyName = lcfirst(substr($methodName, 7));
            $query = $this->createQuery();
            $result = $query->matching($query->equals($propertyName, $arguments[0]))->execute()->count();
            return $result;
        }
        throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException('The method "' . $methodName . '" is not supported by the repository.', 1233180480);
    }

    /**
     * Returns the class name of this class.
     *
     * @return string Class name of the repository.
     */
    protected function getRepositoryClassName()
    {
        return get_class($this);
    }
}
