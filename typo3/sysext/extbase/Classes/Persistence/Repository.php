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
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap
	 * @deprecated since 6.1 will be removed two versions later, use the persistence session instead
	 */
	protected $identityMap;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface
	 * @deprecated since 6.1, will be removed two versions later, use the persistence manager instead
	 */
	protected $backend;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Session
	 * @deprecated since 6.1 will be removed two versions later, use the persistence manager instead
	 */
	protected $session;

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
	 * @deprecated since Extbase 6.0.0; will be removed in Extbase 6.2 - Use objectManager to instantiate repository objects instead of GeneralUtility::makeInstance
	 */
	public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager = NULL) {
		$nsSeparator = strpos($this->getRepositoryClassName(), '\\') !== FALSE ? '\\\\' : '_';
		$this->objectType = preg_replace(array('/' . $nsSeparator . 'Repository' . $nsSeparator . '(?!.*' . $nsSeparator . 'Repository' . $nsSeparator . ')/', '/Repository$/'), array($nsSeparator . 'Model' . $nsSeparator, ''), $this->getRepositoryClassName());
		if ($objectManager === NULL) {
			// Legacy creation, in case the object manager is NOT injected
			// If ObjectManager IS there, then all properties are automatically injected
			// @deprecated since Extbase 6.0.0, will be removed in Extbase 6.2
			\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

			$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
			$this->injectIdentityMap($this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\IdentityMap'));
			$this->injectPersistenceManager($this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager'));
			$this->injectBackend($this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\BackendInterface'));
			$this->injectSession($this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Session'));
		} else {
			$this->objectManager = $objectManager;
		}
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap $identityMap
	 * @return void
	 * @deprecated since 6.1, will be removed two versions later
	 */
	public function injectIdentityMap(\TYPO3\CMS\Extbase\Persistence\Generic\IdentityMap $identityMap) {
		$this->identityMap = $identityMap;
	}

	/**
	 * Injects the Persistence Backend
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface $backend The persistence backend
	 * @return void
	 * @deprecated since 6.1, will be removed two versions later
	 */
	public function injectBackend(\TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 * Injects the Persistence Session
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Session $session The persistence session
	 * @return void
	 * @deprecated since 6.1, will be removed two versions later
	 */
	public function injectSession(\TYPO3\CMS\Extbase\Persistence\Generic\Session $session) {
		$this->session = $session;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
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
	public function remove($object) {
		if (!$object instanceof $this->objectType) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException('The object given to remove() was not of the type (' . $this->objectType . ') this repository manages.', 1248363335);
		}
		$this->persistenceManager->remove($object);
	}

	/**
	 * Replaces an object by another.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @deprecated since 6.1, will be removed two versions later
	 */
	public function replace($existingObject, $newObject) {
		// Does nothing here as explicit update replaces objects in persistence session already
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
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException('The modified object given to update() was not of the type (' . $this->objectType . ') this repository manages.', 1249479625);
		}
		$this->persistenceManager->update($modifiedObject);
	}

	/**
	 * Returns all objects of this repository.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|array
	 * @api
	 */
	public function findAll() {
		return $this->createQuery()->execute();
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
		foreach ($this->findAll() AS $object) {
			$this->remove($object);
		}
	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @param integer $uid The identifier of the object to find
	 * @return object The matching object if found, otherwise NULL
	 * @api
	 */
	public function findByUid($uid) {
		return $this->persistenceManager->getObjectByIdentifier($uid, $this->objectType);
	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @param mixed $identifier The identifier of the object to find
	 * @return object The matching object if found, otherwise NULL
	 * @api
	 */
	public function findByIdentifier($identifier) {
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
		$query = $this->persistenceManager->createQueryForType($this->objectType);
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
			$propertyName = lcfirst(substr($methodName, 6));
			$query = $this->createQuery();
			$result = $query->matching($query->equals($propertyName, $arguments[0]))->execute();
			return $result;
		} elseif (substr($methodName, 0, 9) === 'findOneBy' && strlen($methodName) > 10) {
			$propertyName = lcfirst(substr($methodName, 9));
			$query = $this->createQuery();

			$result = $query->matching($query->equals($propertyName, $arguments[0]))->setLimit(1)->execute();
			if ($result instanceof \TYPO3\CMS\Extbase\Persistence\QueryResultInterface) {
				return $result->getFirst();
			} elseif (is_array($result)) {
				return isset($result[0]) ? $result[0] : NULL;
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
	protected function getRepositoryClassName() {
		return get_class($this);
	}
}

?>