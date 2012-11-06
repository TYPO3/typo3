<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2012 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
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
	 * Objects which were reconstituted. The relevant objects are registered by
	 * the \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper.
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $reconstitutedObjects;

	/**
	 * Constructs a new Session
	 */
	public function __construct() {
		$this->reconstitutedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Registers a reconstituted object
	 *
	 * @param object|\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object $object
	 * @return void
	 */
	public function registerReconstitutedObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object) {
		$this->reconstitutedObjects->attach($object);
	}

	/**
	 * Unregisters a reconstituted object
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
	 * @return void
	 */
	public function unregisterReconstitutedObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object) {
		$this->reconstitutedObjects->detach($object);
	}

	/**
	 * Returns all objects which have been registered as reconstituted objects
	 *
	 * @return array All reconstituted objects
	 */
	public function getReconstitutedObjects() {
		return $this->reconstitutedObjects;
	}

}


?>