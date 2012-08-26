<?php
namespace TYPO3\CMS\Extbase\Persistence;

/*                                                                        *
 * This script belongs to the Extbase framework.                          *
 *                                                                        *
 * This class is a backport of the corresponding class of FLOW3.          *
 * All credits go to the v5 team.                                         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * The base repository - will usually be extended by a more concrete repository.
 *
 * @package Extbase
 * @subpackage Persistence
 * @api
 */
class Repository implements \TYPO3\CMS\Extbase\Persistence\RepositoryInterface, \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap
	 */
	protected $identityMap;

	/**
	 * Objects of this repository
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage
	 */
	protected $addedObjects;

	/**
	 * Objects removed but not found in $this->addedObjects at removal time
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage
	 */
	protected $removedObjects;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface
	 */
	protected $queryFactory;

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
	protected $defaultOrderings = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
	 */
	protected $defaultQuerySettings = NULL;

	/**
	 * Constructs a new Repository
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 */
	public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager = NULL) {
		$this->addedObjects = new \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage();
		$this->removedObjects = new \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage();
		$nsSeperator = strpos($this->getRepositoryClassName(), '\\') !== FALSE ? '\\\\' : '_';
		$this->objectType = preg_replace(array('/' . $nsSeperator . 'Repository' . $nsSeperator . '(?!.*' . $nsSeperator . 'Repository' . $nsSeperator . ')/', '/Repository$/'), array($nsSeperator . 'Model' . $nsSeperator, ''), $this->getRepositoryClassName());
		if ($objectManager === NULL) {
			// Legacy creation, in case the object manager is NOT injected
			// If ObjectManager IS there, then all properties are automatically injected
			$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
			$this->injectIdentityMap($this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\IdentityMap'));
			$this->injectQueryFactory($this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryFactory'));
			$this->injectPersistenceManager($this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager'));
		} else {
			$this->objectManager = $objectManager;
		}
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap $identityMap
	 * @return void
	 */
	public function injectIdentityMap(\TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap $identityMap) {
		$this->identityMap = $identityMap;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory $queryFactory
	 * @return void
	 */
	public function injectQueryFactory(\TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
		$this->persistenceManager->registerRepositoryClassName($this->getRepositoryClassName());
	}

	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @throws Exception\IllegalObjectTypeException
	 * @return void
	 * @api
	 */
	public function add($object) {
		if (!$object instanceof $this->objectType) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException(('The object given to add() was not of the type (' . $this->objectType) . ') this repository manages.', 1248363335);
		}
		$this->addedObjects->attach($object);
		if ($this->removedObjects->contains($object)) {
			$this->removedObjects->detach($object);
		}
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @throws Exception\IllegalObjectTypeException
	 * @return void
	 * @api
	 */
	public function remove($object) {
		if (!$object instanceof $this->objectType) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException(('The object given to remove() was not of the type (' . $this->objectType) . ') this repository manages.', 1248363335);
		}
		if ($this->addedObjects->contains($object)) {
			$this->addedObjects->detach($object);
		}
		if (!$object->_isNew()) {
			$this->removedObjects->attach($object);
		}
	}

	/**
	 * Replaces an object by another.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @throws Exception\UnknownObjectException
	 * @throws Exception\IllegalObjectTypeException
	 * @return void
	 * @api
	 */
	public function replace($existingObject, $newObject) {
		if (!$existingObject instanceof $this->objectType) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException(('The existing object given to replace was not of the type (' . $this->objectType) . ') this repository manages.', 1248363434);
		}
		if (!$newObject instanceof $this->objectType) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException(('The new object given to replace was not of the type (' . $this->objectType) . ') this repository manages.', 1248363439);
		}
		$backend = $this->persistenceManager->getBackend();
		$session = $this->persistenceManager->getSession();
		$uuid = $backend->getIdentifierByObject($existingObject);
		if ($uuid !== NULL) {
			$backend->replaceObject($existingObject, $newObject);
			$session->unregisterReconstitutedObject($existingObject);
			$session->registerReconstitutedObject($newObject);
			if ($this->removedObjects->contains($existingObject)) {
				$this->removedObjects->detach($existingObject);
				$this->removedObjects->attach($newObject);
			} elseif ($this->addedObjects->contains($existingObject)) {
				$this->addedObjects->detach($existingObject);
				$this->addedObjects->attach($newObject);
			}
		} elseif ($this->addedObjects->contains($existingObject)) {
			$this->addedObjects->detach($existingObject);
			$this->addedObjects->attach($newObject);
		} else {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException('The "existing object" is unknown to the persistence backend.', 1238068475);
		}
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
	public function update($modifiedObject) {
		if (!$modifiedObject instanceof $this->objectType) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException(('The modified object given to update() was not of the type (' . $this->objectType) . ') this repository manages.', 1249479625);
		}
		$uid = $modifiedObject->getUid();
		if ($uid !== NULL) {
			$existingObject = $this->findByUid($uid);
			$this->replace($existingObject, $modifiedObject);
		} else {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException('The "modified object" is does not have an existing counterpart in this repository.', 1249479819);
		}
	}

	/**
	 * Returns all addedObjects that have been added to this repository with add().
	 *
	 * This is a service method for the persistence manager to get all addedObjects
	 * added to the repository. Those are only objects *added*, not objects
	 * fetched from the underlying storage.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage the objects
	 */
	public function getAddedObjects() {
		return $this->addedObjects;
	}

	/**
	 * Returns an Tx_Extbase_Persistence_ObjectStorage with objects remove()d from the repository
	 * that had been persisted to the storage layer before.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage the objects
	 */
	public function getRemovedObjects() {
		return $this->removedObjects;
	}

	/**
	 * Returns all objects of this repository.
	 *
	 * @return Tx_Extbase_Persistence_QueryResultInterface|array
	 * @api
	 */
	public function findAll() {
		$result = $this->createQuery()->execute();
		return $result;
	}

	/**
	 * Returns the total number objects of this repository.
	 *
	 * @return integer The object count
	 * @api
	 */
	public function countAll() {
		return $this->createQuery()->execute()->count();
	}

	/**
	 * Removes all objects of this repository as if remove() was called for
	 * all of them.
	 *
	 * @return void
	 * @api
	 */
	public function removeAll() {
		$this->addedObjects = new \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage();
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
	public function findByUid($uid) {
		if ($this->identityMap->hasIdentifier($uid, $this->objectType)) {
			$object = $this->identityMap->getObjectByIdentifier($uid, $this->objectType);
		} else {
			$query = $this->createQuery();
			$query->getQuerySettings()->setRespectSysLanguage(FALSE);
			$query->getQuerySettings()->setRespectStoragePage(FALSE);
			$object = $query->matching($query->equals('uid', $uid))->execute()->getFirst();
		}
		return $object;
	}

	/**
	 * Sets the property names to order the result by per default.
	 * Expected like this:
	 * array(
	 * 'foo' => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING,
	 * 'bar' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @param array $defaultOrderings The property names to order by
	 * @return void
	 * @api
	 */
	public function setDefaultOrderings(array $defaultOrderings) {
		$this->defaultOrderings = $defaultOrderings;
	}

	/**
	 * Sets the default query settings to be used in this repository
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings The query settings to be used by default
	 * @return void
	 * @api
	 */
	public function setDefaultQuerySettings(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings) {
		$this->defaultQuerySettings = $defaultQuerySettings;
	}

	/**
	 * Returns a query for objects of this repository
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function createQuery() {
		$query = $this->queryFactory->create($this->objectType);
		if ($this->defaultOrderings !== array()) {
			$query->setOrderings($this->defaultOrderings);
		}
		if ($this->defaultQuerySettings !== NULL) {
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
	public function __call($methodName, $arguments) {
		if (substr($methodName, 0, 6) === 'findBy' && strlen($methodName) > 7) {
			$propertyName = strtolower(substr(substr($methodName, 6), 0, 1)) . substr(substr($methodName, 6), 1);
			$query = $this->createQuery();
			$result = $query->matching($query->equals($propertyName, $arguments[0]))->execute();
			return $result;
		} elseif (substr($methodName, 0, 9) === 'findOneBy' && strlen($methodName) > 10) {
			$propertyName = strtolower(substr(substr($methodName, 9), 0, 1)) . substr(substr($methodName, 9), 1);
			$query = $this->createQuery();
			$object = $query->matching($query->equals($propertyName, $arguments[0]))->setLimit(1)->execute()->getFirst();
			return $object;
		} elseif (substr($methodName, 0, 7) === 'countBy' && strlen($methodName) > 8) {
			$propertyName = strtolower(substr(substr($methodName, 7), 0, 1)) . substr(substr($methodName, 7), 1);
			$query = $this->createQuery();
			$result = $query->matching($query->equals($propertyName, $arguments[0]))->execute()->count();
			return $result;
		}
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException(('The method "' . $methodName) . '" is not supported by the repository.', 1233180480);
	}

	/**
	 * Returns the class name of this class.
	 *
	 * @return string Class name of the repository.
	 */
	protected function getRepositoryClassName() {
		return get_class($this);
	}

}


?>