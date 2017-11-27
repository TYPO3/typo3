<?php
namespace TYPO3\CMS\Core\Resource;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

/**
 * Abstract repository implementing the basic repository methods
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
     * @api
     */
    public function add($object)
    {
    }

    /**
     * Removes an object from this repository.
     *
     * @param object $object The object to remove
     * @api
     */
    public function remove($object)
    {
    }

    /**
     * Replaces an object by another.
     *
     * @param object $existingObject The existing object
     * @param object $newObject The new object
     * @api
     */
    public function replace($existingObject, $newObject)
    {
    }

    /**
     * Replaces an existing object with the same identifier by the given object
     *
     * @param object $modifiedObject The modified object
     * @api
     */
    public function update($modifiedObject)
    {
    }

    /**
     * Returns all objects of this repository add()ed but not yet persisted to
     * the storage layer.
     *
     * @return array An array of objects
     */
    public function getAddedObjects()
    {
    }

    /**
     * Returns an array with objects remove()d from the repository that
     * had been persisted to the storage layer before.
     *
     * @return array
     */
    public function getRemovedObjects()
    {
    }

    /**
     * Returns all objects of this repository.
     *
     * @return array An array of objects, empty if no objects found
     * @api
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
                    $queryBuilder->createNamedParameter($this->type, \PDO::PARAM_STR)
                )
            );
        }
        $result = $queryBuilder->execute();

        // fetch all records and create objects out of them
        while ($row = $result->fetch()) {
            $items[] = $this->createDomainObject($row);
        }
        return $items;
    }

    /**
     * Creates an object managed by this repository.
     *
     * @abstract
     * @param array $databaseRow
     * @return object
     */
    abstract protected function createDomainObject(array $databaseRow);

    /**
     * Returns the total number objects of this repository.
     *
     * @return int The object count
     * @api
     */
    public function countAll()
    {
    }

    /**
     * Removes all objects of this repository as if remove() was called for
     * all of them.
     *
     * @api
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
     * @api
     */
    public function findByUid($uid)
    {
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            throw new \InvalidArgumentException('The UID has to be an integer. UID given: "' . $uid . '"', 1316779798);
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        if ($this->getEnvironmentMode() === 'FE' && !empty($GLOBALS['TSFE']->sys_page)) {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        }
        $row = $queryBuilder
            ->select('*')
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetch();
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
     * @api
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
     * @api
     */
    public function setDefaultQuerySettings(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings)
    {
        throw new \BadMethodCallException('Repository does not support the setDefaultQuerySettings() method.', 1313185907);
    }

    /**
     * Returns a query for objects of this repository
     *
     * @throws \BadMethodCallException
     * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
     * @api
     */
    public function createQuery()
    {
        throw new \BadMethodCallException('Repository does not support the createQuery() method.', 1313185908);
    }

    /**
     * Finds an object matching the given identifier.
     *
     * @param mixed $identifier The identifier of the object to find
     * @return object|null The matching object if found, otherwise NULL
     * @api
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
     */
    public function __call($method, $arguments)
    {
        throw new \BadMethodCallException('Repository method "' . $method . '" is not implemented.', 1378918410);
    }

    /**
     * Returns the object type this repository is managing.
     *
     * @return string
     * @api
     */
    public function getEntityClassName()
    {
        return $this->objectType;
    }

    /**
     * Function to return the current TYPO3_MODE.
     * This function can be mocked in unit tests to be able to test frontend behaviour.
     *
     * @return string
     */
    protected function getEnvironmentMode()
    {
        return TYPO3_MODE;
    }
}
