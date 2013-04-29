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
 * A proxy that can replace any object and replaces itself in it's parent on
 * first access (call, get, set, isset, unset).
 */
class LazyObjectStorage extends \TYPO3\CMS\Extbase\Persistence\ObjectStorage implements \TYPO3\CMS\Extbase\Persistence\Generic\LoadingStrategyInterface {

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
			if (!$this->isStorageAlreadyMemorizedInParentCleanState()) {
				$this->parentObject->_memorizeCleanState($this->propertyName);
			}
		}
	}

	/**
	 * @return bool
	 */
	protected function isStorageAlreadyMemorizedInParentCleanState() {
		return $this->parentObject->_getCleanProperty($this->propertyName) === $this;
	}

	// Delegation to the ObjectStorage methods below
	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $storage
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::addAll
	 */
	public function addAll($storage) {
		$this->initialize();
		parent::addAll($storage);
	}

	/**
	 * @param object $object The object to add.
	 * @param mixed $data The data to associate with the object.
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::attach
	 */
	public function attach($object, $data = NULL) {
		$this->initialize();
		parent::attach($object, $data);
	}

	/**
	 * @param object $object The object to look for.
	 * @return boolean
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::contains
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
	 * @return object The object at the current iterator position.
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::current
	 */
	public function current() {
		$this->initialize();
		return parent::current();
	}

	/**
	 * @param object $object The object to remove.
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::detach
	 */
	public function detach($object) {
		$this->initialize();
		parent::detach($object);
	}

	/**
	 * @return string The index corresponding to the position of the iterator.
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::key
	 */
	public function key() {
		$this->initialize();
		return parent::key();
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::next
	 */
	public function next() {
		$this->initialize();
		parent::next();
	}

	/**
	 * @param object $object The object to look for.
	 * @return boolean
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::offsetExists
	 */
	public function offsetExists($object) {
		$this->initialize();
		return parent::offsetExists($object);
	}

	/**
	 * @param object $object The object to look for.
	 * @return mixed
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::offsetGet
	 */
	public function offsetGet($object) {
		$this->initialize();
		return parent::offsetGet($object);
	}

	/**
	 * @param object $object The object to add.
	 * @param mixed $info The data to associate with the object.
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::offsetSet
	 */
	public function offsetSet($object, $info) {
		$this->initialize();
		parent::offsetSet($object, $info);
	}

	/**
	 * @param object $object The object to remove.
	 * @return void
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::offsetUnset
	 */
	public function offsetUnset($object) {
		$this->initialize();
		parent::offsetUnset($object);
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $storage The storage containing the elements to remove.
	 * @return void
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::removeAll
	 */
	public function removeAll($storage) {
		$this->initialize();
		parent::removeAll($storage);
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::rewind
	 */
	public function rewind() {
		$this->initialize();
		parent::rewind();
	}

	/**
	 * @return boolean
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::valid
	 */
	public function valid() {
		$this->initialize();
		return parent::valid();
	}

	/**
	 * @return array
	 *
	 * @see \TYPO3\CMS\Extbase\Persistence\ObjectStorage::toArray
	 */
	public function toArray() {
		$this->initialize();
		return parent::toArray();
	}

	/**
	 * @param mixed $object
	 * @return integer|NULL
	 */
	public function getPosition($object) {
		$this->initialize();
		return parent::getPosition($object);
	}
}

?>