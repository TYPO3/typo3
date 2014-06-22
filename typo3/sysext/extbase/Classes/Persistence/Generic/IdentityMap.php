<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/**
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
	 * @inject
	 */
	protected $persistenceSession;

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
