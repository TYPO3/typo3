<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of FLOW3.
 *  All credits go to the v5 team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A proxy that can replace any object and replaces itself in it's parent on
 * first access (call, get, set, isset, unset).
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id$
 */
class LazyObjectStorage extends \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage implements \TYPO3\CMS\Extbase\Persistence\Generic\LoadingStrategyInterface {

	/**
	 * This field is only needed to make debugging easier:
	 * If you call current() on a class that implements Iterator, PHP will return the first field of the object
	 * instead of calling the current() method of the interface.
	 * We use this unusual behavior of PHP to return the warning below in this case.
	 *
	 * @var string
	 */
	private $warning = 'You should never see this warning. If you do, you probably used PHP array functions like current() on the TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LazyObjectStorage. To retrieve the first result, you can use the rewind() and current() methods.';

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 */
	protected $dataMapper;

	/**
	 * The object this property is contained in.
	 *
	 * @var \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface
	 */
	protected $parentObject;

	/**
	 * The name of the property represented by this proxy.
	 *
	 * @var string
	 */
	protected $propertyName;

	/**
	 * The raw field value.
	 *
	 * @var mixed
	 */
	protected $fieldValue;

	/**
	 * @var bool
	 */
	protected $isInitialized = FALSE;

	/**
	 * Returns the state of the initialization
	 *
	 * @return boolean
	 */
	public function isInitialized() {
		return $this->isInitialized;
	}

	/**
	 * Constructs this proxy instance.
	 *
	 * @param object $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 */
	public function __construct($parentObject, $propertyName, $fieldValue) {
		$this->parentObject = $parentObject;
		$this->propertyName = $propertyName;
		$this->fieldValue = $fieldValue;
		reset($this->storage);
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * This is a function lazy load implementation.
	 *
	 * @return void
	 */
	protected function initialize() {
		if (!$this->isInitialized) {
			$this->isInitialized = TRUE;
			$objects = $this->dataMapper->fetchRelated($this->parentObject, $this->propertyName, $this->fieldValue, FALSE);
			foreach ($objects as $object) {
				parent::attach($object);
			}
			$this->_memorizeCleanState();
			$this->parentObject->_memorizeCleanState($this->propertyName);
		}
	}

	// Delegation to the ObjectStorage methods below
	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::addAll
	 */
	public function addAll($storage) {
		$this->initialize();
		parent::addAll($storage);
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::attach
	 */
	public function attach($object, $data = NULL) {
		$this->initialize();
		parent::attach($object, $data);
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::contains
	 */
	public function contains($object) {
		$this->initialize();
		return parent::contains($object);
	}

	/**
	 * Counts the elements in the storage array
	 *
	 * @throws Exception
	 * @return int The number of elements in the ObjectStorage
	 */
	public function count() {
		$columnMap = $this->dataMapper->getDataMap(get_class($this->parentObject))->getColumnMap($this->propertyName);
		$numberOfElements = NULL;
		if ($columnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
			$numberOfElements = $this->dataMapper->countRelated($this->parentObject, $this->propertyName, $this->fieldValue);
		} else {
			$this->initialize();
			$numberOfElements = count($this->storage);
		}
		if (is_null($numberOfElements)) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception('The number of elements could not be determined.', 1252514486);
		}
		return $numberOfElements;
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::current
	 */
	public function current() {
		$this->initialize();
		return parent::current();
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::detach
	 */
	public function detach($object) {
		$this->initialize();
		parent::detach($object);
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::key
	 */
	public function key() {
		$this->initialize();
		return parent::key();
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::next
	 */
	public function next() {
		$this->initialize();
		parent::next();
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::offsetExists
	 */
	public function offsetExists($object) {
		$this->initialize();
		return parent::offsetExists($object);
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::offsetGet
	 */
	public function offsetGet($object) {
		$this->initialize();
		return parent::offsetGet($object);
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::offsetSet
	 */
	public function offsetSet($object, $info) {
		$this->initialize();
		parent::offsetSet($object, $info);
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::offsetUnset
	 */
	public function offsetUnset($object) {
		$this->initialize();
		parent::offsetUnset($object);
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::removeAll
	 */
	public function removeAll($storage) {
		$this->initialize();
		parent::removeAll($storage);
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::rewind
	 */
	public function rewind() {
		$this->initialize();
		parent::rewind();
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::valid
	 */
	public function valid() {
		$this->initialize();
		return parent::valid();
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\Generic\ObjectStorage::toArray
	 */
	public function toArray() {
		$this->initialize();
		return parent::toArray();
	}

}


?>