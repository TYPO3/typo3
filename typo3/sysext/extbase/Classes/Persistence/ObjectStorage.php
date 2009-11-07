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
 * @package Extbase
 * @subpackage Persistence
 * @version $ID:$
 */
class Tx_Extbase_Persistence_ObjectStorage implements Iterator, Countable, ArrayAccess {

	/**
	 * The array holding references of the stored objects
	 *
	 * @var array
	 */
	protected $storage = array();

	/**
	 *
	 * @var bool
	 */
	protected $isInitialized = TRUE;

	/**
	 * This is a template function to be overwritten by a concrete implementation. It enables you to implement
	 * a lazy load implementation. 
	 *
	 * @return void
	 */
	protected function initializeStorage() {
	}
	
	/**
	 * Returns the state of the initialization
	 *
	 * @return void
	 */
	public function isInitialized() {
		return $this->isInitialized;
	}
	
	/**
	 * Resets the array pointer of the storage
	 *
	 * @return void
	 */
	public function rewind() {
		$this->initializeStorage();
		reset($this->storage);
	}

	/**
	 * Checks if the array pointer of the storage points to a valid position
	 *
	 * @return void
	 */
	public function valid() {
		$this->initializeStorage();
		return $this->current() !== FALSE;
	}

	/**
	 * Returns the current key storage array
	 *
	 * @return void
	 */
	public function key() {
		$this->initializeStorage();
		return key($this->storage);
	}

	/**
	 * Returns the current value of the storage array
	 *
	 * @return void
	 */
	public function current() {
		$this->initializeStorage();
		return current($this->storage);
	}

	/**
	 * Returns the next position of the storage array
	 *
	 * @return void
	 */
	public function next() {
		$this->initializeStorage();
		next($this->storage);
	}

	/**
	 * Counts the elements in the storage array
	 *
	 * @return void
	 */
	public function count() {
		$this->initializeStorage();
		return count($this->storage);
	}

	/**
	 * Loads the array at a given offset. Nothing happens if the object already exists in the storage
	 *
	 * @param string $offset
	 * @param string $obj The object
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		if (!is_object($offset)) throw new Tx_Extbase_MVC_Exception_InvalidArgumentType('Expected parameter 1 to be object, ' . gettype($offset) . ' given');
		// TODO Check implementation again
		// if (!is_object($obj)) throw new Tx_Extbase_MVC_Exception_InvalidArgumentType('Expected parameter 2 to be object, ' . gettype($offset) . ' given');
		// if (!($offset === $obj)) throw new Tx_Extbase_MVC_Exception_InvalidArgumentType('Parameter 1 and parameter 2 must be a reference to the same object.');
		$this->initializeStorage();
		if (!$this->contains($offset)) {
			$this->storage[spl_object_hash($offset)] = $value;
		}
	}

	/**
	 * Checks if a given offset exists in the storage
	 *
	 * @param string $offset
	 * @return boolean TRUE if the given offset exists; otherwise FALSE
	 */
	public function offsetExists($offset) {
		$this->isObject($offset);
		$this->initializeStorage();
		return isset($this->storage[spl_object_hash($offset)]);
	}

	/**
	 * Unsets the storage at the given offset
	 *
	 * @param string $offset The offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		$this->isObject($offset);
		$this->initializeStorage();
		unset($this->storage[spl_object_hash($offset)]);
	}

	/**
	 * Returns the object at the given offset
	 *
	 * @param string $offset The offset
	 * @return Object The object
	 */
	public function offsetGet($offset) {
		$this->isObject($offset);
		$this->initializeStorage();
		return isset($this->storage[spl_object_hash($offset)]) ? $this->storage[spl_object_hash($offset)] : NULL;
	}

	/**
	 * Checks if the storage contains the given object
	 *
	 * @param Object $object The object to be checked for
	 * @return boolean TRUE|FALSE Returns TRUE if the storage contains the object; otherwise FALSE
	 */
	public function contains($object) {
		$this->isObject($object);
		$this->initializeStorage();
		return array_key_exists(spl_object_hash($object), $this->storage);
	}

	/**
	 * Attaches an object to the storage
	 *
	 * @param Object $obj The Object to be attached
	 * @return void
	 */
	public function attach($object, $value = NULL) {
		$this->isObject($object);
		$this->initializeStorage();
		if (!$this->contains($object)) {
			if ($value === NULL) {
				$value = $object;
			}
			// TODO Revise this with Karsten
			$this->storage[spl_object_hash($object)] = $value;
		}
	}

	/**
	 * Detaches an object from the storage
	 *
	 * @param Object $object The object to be removed from the storage
	 * @return void
	 */
	public function detach($object) {
		$this->isObject($object);
		$this->initializeStorage();
		unset($this->storage[spl_object_hash($object)]);
	}

	/**
	 * Attach all objects to the storage
	 *
	 * @param array $objects The objects to be attached to the storage
	 * @return void
	 */
	public function addAll($objects) {
		if (is_array($objects) || $objects instanceof Iterator) {
			$this->initializeStorage();
			foreach ($objects as $object) {
				$this->attach($object);
			}
		}
	}

	/**
	 * Detaches all objects from the storage
	 *
	 * @param array $objects The objects to be detached from the storage
	 * @return void
	 */
	public function removeAll($objects) {
		if (is_array($objects) || $objects instanceof Iterator) {
			$this->initializeStorage();
			foreach ($objects as $object) {
				$this->detach($object);
			}
		}
	}

	/**
	 * Checks, if the given value is an object and throws an exception if not
	 *
	 * @param string $value The value to be tested
	 * @return bool TRUE, if the given value is an object
	 */
	protected function isObject($value) {
		if (!is_object($value)) {
			throw new Tx_Extbase_MVC_Exception_InvalidArgumentType('Expected parameter to be an object, ' . gettype($offset) . ' given');
		}
		return TRUE;
	}

	/**
	 * Returns this object storage as an array
	 *
	 * @return array The object storage
	 */
	public function toArray() {
		$this->initializeStorage();
		return $this->storage;
	}

}

?>