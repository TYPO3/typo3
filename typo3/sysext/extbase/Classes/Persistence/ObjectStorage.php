<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * The storage for objects. It ensures the uniqueness of an object in the storage. It's a remake of the
 * SplObjectStorage introduced in PHP 5.3.
 * 
 * Opposed to the SplObjectStorage the ObjectStorage does not implement the Serializable interface.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $ID:$
 */
class Tx_Extbase_Persistence_ObjectStorage implements Countable, Iterator, ArrayAccess, Tx_Extbase_Persistence_ObjectMonitoringInterface {

	/**
	 * An array holding the objects and the stored information. The key of the array items ist the 
	 * spl_object_hash of the given object.
	 *
	 * array(
	 * 	spl_object_hash =>
	 * 		array(
	 *			'obj' => $object,
	 * 			'inf' => $information
	 *		)
	 * )
	 *
	 * @var array
	 */
	protected $storage = array();

	/**
	 * A flag indication if the object storage was modified after reconstitution (eg. by adding a new object)
	 * @var bool
	 */
	protected $isModified = FALSE;
		
	/**
	 * Rewind the iterator to the first storage element.
	 *
	 * @return void
	 */
	public function rewind() {
		reset($this->storage);
	}

	/**
	 * Checks if the array pointer of the storage points to a valid position
	 *
	 * @return void
	 */
	public function valid() {
		return current($this->storage);
	}

	/**
	 * Returns the index at which the iterator currently is. This is different from the SplObjectStorage 
	 * as the key in this implementation is the object hash.
	 *
	 * @return string The index corresponding to the position of the iterator.
	 */
	public function key() {
		return key($this->storage);
	}

	/**
	 * Returns the current storage entry.
	 *
	 * @return object The object at the current iterator position.
	 */
	public function current() {
		$item = current($this->storage);
		return $item['obj'];
	}

	/**
	 * Moves the iterator to the next object in the storage.
	 *
	 * @return void
	 */
	public function next() {
		next($this->storage);
	}

	/**
	 * Counts the number of objects in the storage.
	 *
	 * @return int The number of objects in the storage.
	 */
	public function count() {
		return count($this->storage);
	}

	/**
	 * Associate data to an object in the storage. offsetSet() is an alias of attach(). 
	 *
	 * @param object $object The object to add.
	 * @param mixed $information The data to associate with the object.
	 * @return void
	 */
	public function offsetSet($object, $information) {
		$this->isModified = TRUE;
		$this->storage[spl_object_hash($object)] = array('obj' => $object, 'inf' => $information);
	}

	/**
	 * Checks whether an object exists in the storage.
	 *
	 * @param string $object The object to look for.
	 * @return boolean Returns TRUE if the object exists in the storage, and FALSE otherwise.
	 */
	public function offsetExists($object) {
		return isset($this->storage[spl_object_hash($object)]);
	}

	/**
	 * Removes an object from the storage. offsetUnset() is an alias of detach().
	 *
	 * @param Object $object The object to remove.
	 * @return void
	 */
	public function offsetUnset($object) {
		$this->isModified = TRUE;
		unset($this->storage[spl_object_hash($object)]);
	}

	/**
	 * Returns the data associated with an object in the storage.
	 *
	 * @param string $object The object to look for.
	 * @return mixed The data previously associated with the object in the storage. 
	 */
	public function offsetGet($object) {
		return $this->storage[spl_object_hash($object)]['inf'];
	}

	/**
	 * Checks if the storage contains the object provided.
	 *
	 * @param Object $object The object to look for.
	 * @return boolean Returns TRUE if the object is in the storage, FALSE otherwise.
	 */
	public function contains($object) {
		return $this->offsetExists($object);
	}

	/**
	 * Adds an object inside the storage, and optionaly associate it to some data.
	 *
	 * @param object $object The object to add.
	 * @param mixed $information The data to associate with the object.
	 * @return void
	 */
	public function attach($object, $information = NULL) {
		$this->offsetSet($object, $information);
	}

	/**
	 * Removes the object from the storage.
	 *
	 * @param Object $object The object to remove.
	 * @return void
	 */
	public function detach($object) {
		$this->offsetUnset($object);
	}
	
	/**
	 * Returns the data, or info, associated with the object pointed by the current iterator position.
	 *
	 * @return mixed The data associated with the current iterator position.
	 */
	public function getInfo() {
		$item = current($this->storage);
		return $item['inf'];
	}
	
	public function setInfo($data) {
		$this->isModified = TRUE;
		$key = key($this->storage);
		$this->storage[$key]['inf']  = $data;
	}

	/**
	 * Adds all objects-data pairs from a different storage in the current storage.
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage $storage The storage you want to import.
	 * @return void
	 */
	public function addAll(Tx_Extbase_Persistence_ObjectStorage $storage) {
		foreach ($storage as $object) {
			$this->attach($object, $storage->getInfo());
		}
	}

	/**
	 * Removes objects contained in another storage from the current storage.
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage $storage The storage containing the elements to remove.
	 * @return void
	 */
	public function removeAll(Tx_Extbase_Persistence_ObjectStorage $storage) {
		foreach ($storage as $object) {
			$this->detach($object);
		}
	}
	
	/**
	 * Returns this object storage as an array
	 *
	 * @return array The object storage
	 */
	public function toArray() {
		$array = array();
		foreach ($this->storage as $item) {
			$array[] = $item['obj'];
		}
		return $array;
	}

	public function serialize() {
		throw new RuntimeException('An ObjectStorage instance cannot be serialized.', 1267700868);
	}

	public function unserialize($serialized) {
		throw new RuntimeException('A ObjectStorage instance cannot be unserialized.', 1267700870);
	}
	
	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the database
	 *
	 * @return void
	 */
	public function _memorizeCleanState() {
		$this->isModified = FALSE;
	}

	/**
	 * Returns TRUE if the properties were modified after reconstitution
	 *
	 * @return boolean
	 */
	public function _isDirty() {
		return $this->isModified;
	}
	
}

?>