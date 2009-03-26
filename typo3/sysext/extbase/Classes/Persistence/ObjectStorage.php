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
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_ExtBase_Persistence_ObjectStorage implements Iterator, Countable, ArrayAccess {

	/**
	 * The array holding references of the stored objects
	 *
	 * @var array
	 */
	private $storage = array();

	/**
	 * Resets the array pointer of the storage
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
		return $this->current() !== FALSE;
	}

	/**
	 * Returns the current key storage array
	 *
	 * @return void
	 */
	public function key() {
		return key($this->storage);
	}

	/**
	 * Returns the current value of the storage array
	 *
	 * @return void
	 */
	public function current() {
		return current($this->storage);
	}

	/**
	 * Returns the next position of the storage array
	 *
	 * @return void
	 */
	public function next() {
		next($this->storage);
	}

	/**
	 * Counts the elements in the storage array
	 *
	 * @return void
	 */
	public function count() {
		return count($this->storage);
	}

	/**
	 * Loads the array at a given offset. Nothing happens if the object already exists in the storage 
	 *
	 * @param string $offset 
	 * @param string $obj The object
	 * @return void
	 */
	public function offsetSet($offset, $obj) {
		if (!is_object($offset)) throw new InvalidArgumentException('Expects Parameter 1 to be object, null given');
		if (is_object($obj) && !$this->contains($obj)) {
			$this->storage[$offset] = $obj;
		}
	}

	/**
	 * Checks if a given offset exists in the storage
	 *
	 * @param string $offset 
	 * @return boolean TRUE if the given offset exists; otherwise FALSE
	 */
	public function offsetExists($offset) {
		return isset($this->storage[$offset]);
	}

	/**
	 * Unsets the storage at the given offset
	 *
	 * @param string $offset The offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		unset($this->storage[$offset]);
	}

	/**
	 * Returns the object at the given offset
	 *
	 * @param string $offset The offset
	 * @return Object The object
	 */
	public function offsetGet($offset) {
		return isset($this->storage[$offset]) ? $this->storage[$offset] : NULL;
	}

	/**
	 * Checks if the storage contains the given object
	 *
	 * @param Object $obj The object to be checked for
	 * @return boolean TRUE|FALSE Returns TRUE if the storage contains the object; otherwise FALSE
	 */
	public function contains($obj) {
		if (is_object($obj)) {
			foreach($this->storage as $object) {
				if ($object === $obj) return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Attaches an object to the storage
	 *
	 * @param Object $obj The Object to be attached
	 * @return void
	 */
	public function attach($obj) {
		if (is_object($obj) && !$this->contains($obj)) {
			$this->storage[] = $obj;
		}
	}

	/**
	 * Detaches an object to the storage
	 *
	 * @param Object $obj The object to be removed from the storage
	 * @return void
	 */
	public function detach($obj) {
		if (is_object($obj)) {
			foreach($this->storage as $key => $object) {
				if ($object === $obj) {
					unset($this->storage[$key]);
					$this->rewind();
					return;
				}
			}
		}
	}

	/**
	 * Returns this object storage as an array
	 *
	 * @return array The object storage
	 */
	public function toArray() {
		return $this->storage;
	}

}

?>