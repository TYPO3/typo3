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
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

/**
 * The base repository - will usually be extended by a more concrete repository.
 * @template T of \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface
 * @implements RepositoryInterface<T>
 */
class Repository implements RepositoryInterface, SingletonInterface
{
    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var string
     * @phpstan-var class-string<T>
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
     * @var QuerySettingsInterface
     */
    protected $defaultQuerySettings;

    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Constructs a new Repository
     */
    public function __construct()
    {
        $this->objectType = ClassNamingUtility::translateRepositoryNameToModelName($this->getRepositoryClassName());
    }

    /**
     * Adds an object to this repository
     *
     * @param object $object The object to add
     * @phpstan-param T $object
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
     * @phpstan-param T $object
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
     * @phpstan-param T $modifiedObject
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
     * @phpstan-return QueryResultInterface|iterable<T>
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
     * @phpstan-return T|null
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
     * @phpstan-return T|null
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
     * @return QueryInterface
     * @phpstan-return QueryInterface<T>
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
     * @throws UnsupportedMethodException
     * @return mixed
     * @deprecated since v12, will be removed in v14, use {@see findBy}, {@see findOneBy} and {@see count} instead
     */
    public function __call($methodName, $arguments)
    {
        if (str_starts_with($methodName, 'findBy') && strlen($methodName) > 7) {
            // @todo Enable in version 13.0
            // trigger_error(
            //     'Usage of magic method ' . static::class . '->findBy[Property]() is deprecated, use method findBy() instead.',
            //     E_USER_DEPRECATED
            // );
            $propertyName = lcfirst(substr($methodName, 6));
            $query = $this->createQuery();
            $result = $query->matching($query->equals($propertyName, $arguments[0]))->execute();
            return $result;
        }
        if (str_starts_with($methodName, 'findOneBy') && strlen($methodName) > 10) {
            // @todo Enable in version 13.0
            // trigger_error(
            //     'Usage of magic method ' . static::class . '->findOneBy[Property]() is deprecated, use method findOneBy() instead.',
            //     E_USER_DEPRECATED
            // );
            $propertyName = lcfirst(substr($methodName, 9));
            $query = $this->createQuery();

            $result = $query->matching($query->equals($propertyName, $arguments[0]))->setLimit(1)->execute();
            if ($result instanceof QueryResultInterface) {
                return $result->getFirst();
            }
            if (is_array($result)) {
                return $result[0] ?? null;
            }
        } elseif (str_starts_with($methodName, 'countBy') && strlen($methodName) > 8) {
            // @todo Enable in version 13.0
            // trigger_error(
            //     'Usage of magic method ' . static::class . '->countBy[Property]() is deprecated, use method count() instead.',
            //     E_USER_DEPRECATED
            // );
            $propertyName = lcfirst(substr($methodName, 7));
            $query = $this->createQuery();
            $result = $query->matching($query->equals($propertyName, $arguments[0]))->execute()->count();
            return $result;
        }
        throw new UnsupportedMethodException('The method "' . $methodName . '" is not supported by the repository.', 1233180480);
    }

    /**
     * @phpstan-param array<non-empty-string, mixed> $criteria
     * @phpstan-param array<non-empty-string, QueryInterface::ORDER_*>|null $orderBy
     * @phpstan-param 0|positive-int|null $limit
     * @phpstan-param 0|positive-int|null $offset
     * @phpstan-return QueryResultInterface<T>
     * @return QueryResultInterface
     */
    public function findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = [];
        foreach ($criteria as $propertyName => $propertyValue) {
            $constraints[] = $query->equals($propertyName, $propertyValue);
        }

        if (($numberOfConstraints = count($constraints)) === 1) {
            $query->matching(...$constraints);
        } elseif ($numberOfConstraints > 1) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        if (is_array($orderBy)) {
            $query->setOrderings($orderBy);
        }

        if (is_int($limit)) {
            $query->setLimit($limit);
        }

        if (is_int($offset)) {
            $query->setOffset($offset);
        }

        return $query->execute();
    }

    /**
     * @phpstan-param array<non-empty-string, mixed> $criteria
     * @phpstan-param array<non-empty-string, QueryInterface::ORDER_*>|null $orderBy
     * @phpstan-return T|null
     */
    public function findOneBy(array $criteria, array $orderBy = null): object|null
    {
        return $this->findBy($criteria, $orderBy, 1)->getFirst();
    }

    /**
     * @phpstan-param array<non-empty-string, mixed> $criteria
     * @phpstan-return 0|positive-int
     */
    public function count(array $criteria): int
    {
        return $this->findBy($criteria)->count();
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
