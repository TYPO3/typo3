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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Abstract repository implementing the basic repository methods
 * @template T of object
 * @implements RepositoryInterface<T>
 */
abstract class AbstractRepository implements RepositoryInterface, SingletonInterface
{
    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var ResourceFactory
     */
    protected $factory;

    /**
     * @var string
     */
    protected $typeField = '';

    /**
     * @var string
     */
    protected $type = '';

    /**
     * The main object type of this class
     *
     * @var string
     */
    protected $objectType;

    /**
     * Creates this object.
     */
    public function __construct()
    {
        $this->factory = GeneralUtility::makeInstance(ResourceFactory::class);
    }

    /**
     * Adds an object to this repository.
     *
     * @param object $object The object to add
     * @phpstan-param T $object
     */
    public function add($object)
    {
    }

    /**
     * Removes an object from this repository.
     *
     * @param object $object The object to remove
     * @phpstan-param T $object
     */
    public function remove($object)
    {
    }

    /**
     * Replaces an object by another.
     *
     * @param object $existingObject The existing object
     * @param object $newObject The new object
     * @phpstan-param T $existingObject
     * @phpstan-param T $newObject
     */
    public function replace($existingObject, $newObject)
    {
    }

    /**
     * Replaces an existing object with the same identifier by the given object
     *
     * @param object $modifiedObject The modified object
     * @phpstan-param T $modifiedObject
     */
    public function update($modifiedObject)
    {
    }

    /**
     * Returns all objects of this repository add()ed but not yet persisted to
     * the storage layer.
     *
     * @return array An array of objects
     * @phpstan-return T[]
     * @internal
     */
    public function getAddedObjects()
    {
        return [];
    }

    /**
     * Returns an array with objects remove()d from the repository that
     * had been persisted to the storage layer before.
     *
     * @return array
     * @phpstan-return T[]
     * @internal
     */
    public function getRemovedObjects()
    {
        return [];
    }

    /**
     * Returns all objects of this repository.
     *
     * @return array An array of objects, empty if no objects found
     * @phpstan-return iterable<T>
     */
    public function findAll()
    {
        $items = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        if ($this->getEnvironmentMode() === 'FE') {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        }
        $queryBuilder
            ->select('*')
            ->from($this->table);

        if (!empty($this->type)) {
            $queryBuilder->where(
                $queryBuilder->expr()->eq(
                    $this->typeField,
                    $queryBuilder->createNamedParameter($this->type)
                )
            );
        }
        $result = $queryBuilder->executeQuery();

        // fetch all records and create objects out of them
        while ($row = $result->fetchAssociative()) {
            $items[] = $this->createDomainObject($row);
        }
        return $items;
    }

    /**
     * Creates an object managed by this repository.
     *
     * @abstract
     * @return object
     * @phpstan-return T
     */
    abstract protected function createDomainObject(array $databaseRow);

    /**
     * Returns the total number objects of this repository.
     *
     * @return int The object count
     */
    public function countAll()
    {
        return 0;
    }

    /**
     * Removes all objects of this repository as if remove() was called for
     * all of them.
     */
    public function removeAll()
    {
    }

    /**
     * Finds an object matching the given identifier.
     *
     * @param int $uid The identifier of the object to find
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return object The matching object
     * @phpstan-return T
     */
    public function findByUid($uid)
    {
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            throw new \InvalidArgumentException('The UID has to be an integer. UID given: "' . $uid . '"', 1316779798);
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        if ($this->getEnvironmentMode() === 'FE') {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        }
        $row = $queryBuilder
            ->select('*')
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();
        if (!is_array($row)) {
            throw new \RuntimeException('Could not find row with UID "' . $uid . '" in table "' . $this->table . '"', 1314354065);
        }
        return $this->createDomainObject($row);
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
     *
     * @throws \BadMethodCallException
     */
    public function setDefaultOrderings(array $defaultOrderings)
    {
        throw new \BadMethodCallException('Repository does not support the setDefaultOrderings() method.', 1313185906);
    }

    /**
     * Sets the default query settings to be used in this repository
     *
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings The query settings to be used by default
     *
     * @throws \BadMethodCallException
     */
    public function setDefaultQuerySettings(QuerySettingsInterface $defaultQuerySettings)
    {
        throw new \BadMethodCallException('Repository does not support the setDefaultQuerySettings() method.', 1313185907);
    }

    /**
     * Returns a query for objects of this repository
     *
     * @throws \BadMethodCallException
     * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
     */
    public function createQuery()
    {
        throw new \BadMethodCallException('Repository does not support the createQuery() method.', 1313185908);
    }

    /**
     * Finds an object matching the given identifier.
     *
     * @param mixed $identifier The identifier of the object to find
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return object The matching object if found
     * @phpstan-return T
     */
    public function findByIdentifier($identifier)
    {
        return $this->findByUid($identifier);
    }

    /**
     * Magic call method for repository methods.
     *
     * @param string $method Name of the method
     * @param array $arguments The arguments
     *
     * @throws \BadMethodCallException
     * @internal
     */
    public function __call($method, $arguments)
    {
        throw new \BadMethodCallException('Repository method "' . $method . '" is not implemented.', 1378918410);
    }

    /**
     * Returns the object type this repository is managing.
     *
     * @return string
     */
    public function getEntityClassName()
    {
        return $this->objectType;
    }

    /**
     * Function to return the current application type based on $GLOBALS['TSFE'].
     * This function can be mocked in unit tests to be able to test frontend behaviour.
     *
     * @return string
     */
    protected function getEnvironmentMode()
    {
        return ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController ? 'FE' : 'BE';
    }
}
