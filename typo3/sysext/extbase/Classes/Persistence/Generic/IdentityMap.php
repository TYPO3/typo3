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
 * An identity mapper to map nodes to objects
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @see \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
 * @see \TYPO3\CMS\Extbase\Persistence\Generic\Backend
 * @deprecated since 6.1, will be removed two versions later, use the persistence session instead
 */
class IdentityMap implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Session
	 */
	protected $persistenceSession;

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Session
	 */
	public function injectPersistenceSession(\TYPO3\CMS\Extbase\Persistence\Generic\Session $persistenceSession) {
		$this->persistenceSession = $persistenceSession;
	}

	/**
	 * Checks whether the given object is known to the identity map
	 *
	 * @param object $object
	 * @return boolean
	 * @deprecated since 6.1, will be removed two versions later, use the persistence session instead
	 */
	public function hasObject($object) {
		return $this->persistenceSession->hasObject($object);
	}

	/**
	 * Checks whether the given UUID is known to the identity map
	 *
	 * @param string $uuid
	 * @param string $className
	 * @return boolean
	 * @deprecated since 6.1, will be removed two versions later, use the persistence session instead
	 */
	public function hasIdentifier($uuid, $className) {
		return $this->persistenceSession->hasIdentifier($uuid, $className);
	}

	/**
	 * Returns the object for the given UUID
	 *
	 * @param string $uuid
	 * @param string $className
	 * @return object
	 * @deprecated since 6.1, will be removed two versions later, use the persistence session instead
	 */
	public function getObjectByIdentifier($uuid, $className) {
		return $this->persistenceSession->getObjectByIdentifier($uuid, $className);
	}

	/**
	 * Returns the node identifier for the given object
	 *
	 * @param object $object
	 * @throws \InvalidArgumentException
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
	 * @return string
	 * @deprecated since 6.1, will be removed two versions later, use the persistence session instead
	 */
	public function getIdentifierByObject($object) {
		return $this->persistenceSession->getIdentifierByObject($object);
	}

	/**
	 * Register a node identifier for an object
	 *
	 * @param object $object
	 * @param string $uuid
	 * @deprecated since 6.1, will be removed two versions later, use the persistence session instead
	 */
	public function registerObject($object, $uuid) {
		$this->persistenceSession->registerObject($object, $uuid);
	}

	/**
	 * Unregister an object
	 *
	 * @param object $object
	 * @return void
	 * @deprecated since 6.1, will be removed two versions later, use the persistence session instead
	 */
	public function unregisterObject($object) {
		$this->persistenceSession->unregisterObject($object);
	}
}

?>