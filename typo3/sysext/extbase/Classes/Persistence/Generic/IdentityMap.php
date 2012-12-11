<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2012 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
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
 * An identity mapper to map nodes to objects
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @see \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
 * @see \TYPO3\CMS\Extbase\Persistence\Generic\Backend
 */
class IdentityMap implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $objectMap;

	/**
	 * @var array
	 */
	protected $uuidMap = array();

	/**
	 * Constructs a new Identity Map
	 *
	 */
	public function __construct() {
		$this->objectMap = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Checks whether the given object is known to the identity map
	 *
	 * @param object $object
	 * @return boolean
	 */
	public function hasObject($object) {
		return $this->objectMap->contains($object);
	}

	/**
	 * Checks whether the given UUID is known to the identity map
	 *
	 * @param string $uuid
	 * @param string $className
	 * @return boolean
	 */
	public function hasIdentifier($uuid, $className) {
		if (is_array($this->uuidMap[$className])) {
			return array_key_exists($uuid, $this->uuidMap[$className]);
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns the object for the given UUID
	 *
	 * @param string $uuid
	 * @param string $className
	 * @return object
	 */
	public function getObjectByIdentifier($uuid, $className) {
		return $this->uuidMap[$className][$uuid];
	}

	/**
	 * Returns the node identifier for the given object
	 *
	 * @param object $object
	 * @throws \InvalidArgumentException
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
	 * @return string
	 */
	public function getIdentifierByObject($object) {
		if (!is_object($object)) {
			throw new \InvalidArgumentException('Object expected, ' . gettype($object) . ' given.', 1246892972);
		}
		if (!isset($this->objectMap[$object])) {
			throw new \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException('The given object (class: ' . get_class($object) . ') is not registered in this Identity Map.', 1246892970);
		}
		return $this->objectMap[$object];
	}

	/**
	 * Register a node identifier for an object
	 *
	 * @param object $object
	 * @param string $uuid
	 */
	public function registerObject($object, $uuid) {
		$this->objectMap[$object] = $uuid;
		$this->uuidMap[get_class($object)][$uuid] = $object;
	}

	/**
	 * Unregister an object
	 *
	 * @param object $object
	 * @return void
	 */
	public function unregisterObject($object) {
		unset($this->uuidMap[get_class($object)][$this->objectMap[$object]]);
		$this->objectMap->detach($object);
	}
}

?>