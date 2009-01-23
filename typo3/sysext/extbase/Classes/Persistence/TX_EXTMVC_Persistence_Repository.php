<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The base repository - will usually be extended by a more concrete repository.
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Repository implements F3_FLOW3_Persistence_RepositoryInterface {

	/**
	 * Objects of this repository
	 *
	 * @var SplObjectStorage
	 */
	protected $objects;

	/**
	 * Objects removed but not found in $this->objects at removal time
	 *
	 * @var SplObjectStorage
	 */
	protected $removedObjects;

	/**
	 * @var F3_FLOW3_Persistence_QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * Constructs a new Repository
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct() {
		$this->objects = new SplObjectStorage();
		$this->removedObjects = new SplObjectStorage();
	}

	/**
	 * Injects a QueryFactory instance
	 *
	 * @param F3_FLOW3_Persistence_QueryFactoryInterface $queryFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectQueryFactory(_F3_FLOW3_Persistence_QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function add($object) {
		$this->objects->attach($object);
		$this->removedObjects->detach($object);
	}

	/**
	 * Removes an object from this repository. If it is contained in $this->objects
	 * we just remove it there, since this means it has never been persisted yet.
	 *
	 * Else we keep the object around to check if we need to remove it from the
	 * storage layer.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function remove($object) {
		if ($this->objects->contains($object)) {
			$this->objects->detach($object);
		} else {
			$this->removedObjects->attach($object);
		}
	}

	/**
	 * Returns all objects that have been added to this repository with add().
	 *
	 * This is a service method for the persistence manager to get all objects
	 * added to the repository. Those are only objects *added*, not objects
	 * fetched from the underlying storage.
	 *
	 * @return SplObjectStorage the objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjects() {
		return $this->objects;
	}

	/**
	 * Returns an SplObjectStorage with objects remove()d from the repository
	 * that had been persisted to the storage layer before.
	 *
	 * @return SplObjectStorage the objects
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRemovedObjects() {
		return $this->removedObjects;
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects, empty if no objects found
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findAll() {
		return $this->createQuery()->execute();
	}

	/**
	 * Returns a query for objects of this repository
	 *
	 * @return F3_FLOW3_Persistence_QueryInterface
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createQuery() {
		$type = str_replace('Repository', '', $this->AOPProxyGetProxyTargetClassName());
		return $this->queryFactory->create($type);
	}

	/**
	 * Returns the class name of this class. Seems useless until you think about
	 * the possibility of $this *not* being an AOP proxy. If $this is an AOP proxy
	 * this method will be overridden.
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function AOPProxyGetProxyTargetClassName() {
		return get_class($this);
	}

}
?>