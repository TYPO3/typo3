<?php
namespace TYPO3\CMS\Extbase\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
 *  All rights reserved.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * The base repository - will usually be extended by a more concrete repository.
 *
 * @api
 */
class Repository implements \TYPO3\CMS\Extbase\Persistence\RepositoryInterface, \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Warning: if you think you want to set this,
	 * look at RepositoryInterface::ENTITY_CLASSNAME first!
	 *
	 * @var string
	 */
	protected $entityClassName;

	/**
	 * @var array
	 */
	protected $defaultOrderings = array();

	/**
	 * Initializes a new Repository.
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @deprecated since Extbase 6.0.0; will be removed in Extbase 6.2 - Use objectManager to instantiate repository objects instead of GeneralUtility::makeInstance
	 */
	public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager = NULL) {

		if ($objectManager === NULL) {
			// Legacy creation, in case the object manager is NOT injected
			// If ObjectManager IS there, then all properties are automatically injected
			// @deprecated since Extbase 6.0.0, will be removed in Extbase 6.2
			\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

			$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
			$this->injectPersistenceManager($this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface'));
		} else {
			$this->objectManager = $objectManager;
		}

		if (static::ENTITY_CLASSNAME === NULL) {
			$this->entityClassName = preg_replace(array('/\\\Domain\\\Repository\\\/', '/_Domain_Repository_/', '/Repository$/'), array('\\Domain\\Model\\', '_Domain_Model_', ''), get_class($this));
		} else {
			$this->entityClassName = static::ENTITY_CLASSNAME;
		}
	}

	/**
	 * Injects the persistence manager
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Adds an object to this repository.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 * @api
	 */
	public function add($object) {
		if (!is_object($object) || !($object instanceof $this->entityClassName)) {
			$type = (is_object($object) ? get_class($object) : gettype($object));
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException('The value given to add() was ' . $type . ' , however the ' . get_class($this) . ' can only store ' . $this->entityClassName . ' instances.', 1298403438);
		}
		$this->persistenceManager->add($object);
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 * @api
	 */
	public function remove($object) {
		if (!is_object($object) || !($object instanceof $this->entityClassName)) {
			$type = (is_object($object) ? get_class($object) : gettype($object));
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException('The value given to remove() was ' . $type . ' , however the ' . get_class($this) . ' can only handle ' . $this->entityClassName . ' instances.', 1298403442);
		}
		$this->persistenceManager->remove($object);
	}

	/**
	 * Replaces an object by another.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @throws Exception\IllegalObjectTypeException
	 * @return void
	 * @api
	 * @deprecated since Extbase 6.0, will be removed in Extbase 7.0
	 */
	public function replace($existingObject, $newObject) {
		if (!$existingObject instanceof $this->entityClassName) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException('The existing object given to replace was not of the type (' . $this->entityClassName . ') this repository manages.', 1248363434);
		}
		if (!$newObject instanceof $this->entityClassName) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException('The new object given to replace was not of the type (' . $this->entityClassName . ') this repository manages.', 1248363439);
		}

		$this->persistenceManager->replace($existingObject, $newObject);
	}

	/**
	 * Schedules a modified object for persistence.
	 *
	 * @param object $object The modified object
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
	 * @api
	 */
	public function update($object) {
		if (!is_object($object) || !($object instanceof $this->entityClassName)) {
			$type = (is_object($object) ? get_class($object) : gettype($object));
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException('The value given to update() was ' . $type . ' , however the ' . get_class($this) . ' can only store ' . $this->entityClassName . ' instances.', 1249479625);
		}

		$this->persistenceManager->update($object);
	}

	/**
	 * Returns all objects of this repository add()ed but not yet persisted to
	 * the storage layer.
	 *
	 * @return array An array of objects
	 * @deprecated since Extbase 6.0, will be removed in Extbase 7.0
	 */
	public function getAddedObjects() {
		return $this->persistenceManager->getAddedObjects();
	}

	/**
	 * Returns an array with objects remove()d from the repository that
	 * had been persisted to the storage layer before.
	 *
	 * @return array
	 * @deprecated since Extbase 6.0, will be removed in Extbase 7.0
	 */
	public function getRemovedObjects() {
		return $this->persistenceManager->getRemovedObjects();
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface The query result
	 * @api
	 * @see \TYPO3\CMS\Extbase\Persistence\QueryInterface::execute()
	 */
	public function findAll() {
		return $this->createQuery()->execute();
	}

	/**
	 * Counts all objects of this repository
	 *
	 * @return integer
	 * @api
	 */
	public function countAll() {
		return $this->createQuery()->count();
	}

	/**
	 * Removes all objects of this repository as if remove() was called for
	 * all of them.
	 *
	 * @return void
	 * @api
	 */
	public function removeAll() {
		foreach ($this->findAll() as $object) {
			$this->remove($object);
		}
	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @param integer $uid The identifier of the object to find
	 * @return object The matching object if found, otherwise NULL
	 * @api
	 * @deprecated since Extbase 6.0, will be removed in Extbase 7.0
	 */
	public function findByUid($uid) {
		return $this->findByIdentifier($uid);
	}

	/**
	 * Sets the property names to order results by. Expected like this:
	 * array(
	 *  'foo' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @param array $defaultOrderings The property names to order by by default
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
	 * @deprecated since Extbase 6.0, will be removed in Extbase 7.0
	 */
	public function setDefaultQuerySettings(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $defaultQuerySettings) {
		$this->persistenceManager->setDefaultQuerySettings($defaultQuerySettings);
	}

	/**
	 * Returns a query for objects of this repository
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function createQuery() {
		$query = $this->persistenceManager->createQueryForType($this->entityClassName);
		if ($this->defaultOrderings !== array()) {
			$query->setOrderings($this->defaultOrderings);
		}
		return $query;
	}

	/**
	 * Magic call method for repository methods.
	 *
	 * Provides three methods
	 *  - findBy<PropertyName>($value, $caseSensitive = TRUE)
	 *  - findOneBy<PropertyName>($value, $caseSensitive = TRUE)
	 *  - countBy<PropertyName>($value, $caseSensitive = TRUE)
	 *
	 * @param string $method Name of the method
	 * @param array $arguments The arguments
	 *
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException
	 *
	 * @return mixed The result of the repository method
	 * @api
	 */
	public function __call($method, $arguments) {
		$query = $this->createQuery();
		$caseSensitive = isset($arguments[1]) ? (boolean)$arguments[1] : TRUE;

		if (substr($method, 0, 6) === 'findBy' && strlen($method) > 7) {
			$propertyName = lcfirst(substr($method, 6));
			return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->execute();
		} elseif (substr($method, 0, 7) === 'countBy' && strlen($method) > 8) {
			$propertyName = lcfirst(substr($method, 7));
			return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->count();
		} elseif (substr($method, 0, 9) === 'findOneBy' && strlen($method) > 10) {
			$propertyName = lcfirst(substr($method, 9));
			return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->execute()->getFirst();
		}

		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException('The method "' . $method . '" is not supported by the repository.', 1233180480);
	}

	/**
	 * Returns the classname of the entities this repository is managing.
	 *
	 * Note that anything that is an "instanceof" this class is accepted
	 * by the repository.
	 *
	 * @return string
	 * @api
	 */
	public function getEntityClassName() {
		return $this->entityClassName;
	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @param mixed $identifier The identifier of the object to find
	 * @return object The matching object if found, otherwise NULL
	 * @api
	 */
	public function findByIdentifier($identifier) {
		return $this->persistenceManager->getObjectByIdentifier($identifier, $this->entityClassName);
	}
}

?>