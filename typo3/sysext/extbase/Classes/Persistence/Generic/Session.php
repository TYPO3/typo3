<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
 *  All rights reserved
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
 * The persistence session - acts as a Unit of Work for Extbase persistence framework.
 */
class Session implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Reconstituted objects
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $reconstitutedEntities;

	/**
	 * Reconstituted entity data (effectively their clean state)
	 * Currently unused in Extbase
	 * TODO make use of it in Extbase
	 *
	 * @var array
	 */
	protected $reconstitutedEntitiesData = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $objectMap;

	/**
	 * @var array
	 */
	protected $identifierMap = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * Constructs a new Session
	 */
	public function __construct() {
		$this->reconstitutedEntities = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->objectMap = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Injects a Reflection Service instance
	 *
	 * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Registers data for a reconstituted object.
	 *
	 * $entityData format is described in
	 * "Documentation/PersistenceFramework object data format.txt"
	 *
	 * @param object $entity
	 * @param array $entityData
	 * @return void
	 */
	public function registerReconstitutedEntity($entity, array $entityData = array()) {
		$this->reconstitutedEntities->attach($entity);
		$this->reconstitutedEntitiesData[$entityData['identifier']] = $entityData;
	}

	/**
	 * Replace a reconstituted object, leaves the clean data unchanged.
	 *
	 * @param object $oldEntity
	 * @param object $newEntity
	 * @return void
	 */
	public function replaceReconstitutedEntity($oldEntity, $newEntity) {
		$this->reconstitutedEntities->detach($oldEntity);
		$this->reconstitutedEntities->attach($newEntity);
	}

	/**
	 * Unregisters data for a reconstituted object
	 *
	 * @param object $entity
	 * @return void
	 */
	public function unregisterReconstitutedEntity($entity) {
		if ($this->reconstitutedEntities->contains($entity)) {
			$this->reconstitutedEntities->detach($entity);
			unset($this->reconstitutedEntitiesData[$this->getIdentifierByObject($entity)]);
		}
	}

	/**
	 * Returns all objects which have been registered as reconstituted
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage All reconstituted objects
	 */
	public function getReconstitutedEntities() {
		return $this->reconstitutedEntities;
	}

	/**
	 * Tells whether the given object is a reconstituted entity.
	 *
	 * @param object $entity
	 * @return boolean
	 */
	public function isReconstitutedEntity($entity) {
		return $this->reconstitutedEntities->contains($entity);
	}

	// TODO implement the is dirty checking behaviour of the Flow persistence session here

	/**
	 * Checks whether the given object is known to the identity map
	 *
	 * @param object $object
	 * @return boolean
	 * @api
	 */
	public function hasObject($object) {
		return $this->objectMap->contains($object);
	}

	/**
	 * Checks whether the given identifier is known to the identity map
	 *
	 * @param string $identifier
	 * @param string $className
	 * @return boolean
	 */
	public function hasIdentifier($identifier, $className) {
		return isset($this->identifierMap[strtolower($className)][$identifier]);
	}

	/**
	 * Returns the object for the given identifier
	 *
	 * @param string $identifier
	 * @param string $className
	 * @return object
	 * @api
	 */
	public function getObjectByIdentifier($identifier, $className) {
		return $this->identifierMap[strtolower($className)][$identifier];
	}

	/**
	 * Returns the identifier for the given object from
	 * the session, if the object was registered.
	 *
	 *
	 * @param object $object
	 * @return string
	 * @api
	 */
	public function getIdentifierByObject($object) {
		if ($this->hasObject($object)) {
			return $this->objectMap[$object];
		}
		return NULL;
	}

	/**
	 * Register an identifier for an object
	 *
	 * @param object $object
	 * @param string $identifier
	 * @api
	 */
	public function registerObject($object, $identifier) {
		$this->objectMap[$object] = $identifier;
		$this->identifierMap[strtolower(get_class($object))][$identifier] = $object;
	}

	/**
	 * Unregister an object
	 *
	 * @param string $object
	 * @return void
	 */
	public function unregisterObject($object) {
		unset($this->identifierMap[strtolower(get_class($object))][$this->objectMap[$object]]);
		$this->objectMap->detach($object);
	}

	/**
	 * Destroy the state of the persistence session and reset
	 * all internal data.
	 *
	 * @return void
	 */
	public function destroy() {
		$this->identifierMap = array();
		$this->objectMap = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->reconstitutedEntities = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->reconstitutedEntitiesData = array();
	}

}

?>