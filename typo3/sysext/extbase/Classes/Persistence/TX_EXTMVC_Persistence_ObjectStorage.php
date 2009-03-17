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
 * SplObjectStorage introduced in a usable version in PHP 5.3.
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 */
class TX_EXTMVC_Persistence_ObjectStorage implements Iterator, Countable, ArrayAccess {
// SK: Why not use SplObjectStorage?
// JR: SplObjectStorage isn't fully implemented in PHP 5.2.x
	/**
	 * The array holding references to the stored objects.
	 *
	 * @var string
	 */
	private $storage = array();

	public function rewind() {
		reset($this->storage);
	}

	public function valid() {
		return $this->current() !== FALSE;
	}

	public function key() {
		return key($this->storage);
	}

	public function current() {
		return current($this->storage);
	}

	public function next() {
		next($this->storage);
	}

	public function count() {
		return count($this->storage);
	}

	public function offsetSet($offset, $obj) {
		if (is_object($obj) && !$this->contains($obj)) {
			$this->storage[$offset] = $obj;
		}
	}

	public function offsetExists($offset) {
		return isset($this->storage[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->storage[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->storage[$offset]) ? $this->storage[$offset] : NULL;
	}

	/**
	 * Does the Storage contains the given object
	 *
	 * @param Object $obj
	 * @return boolean TRUE|FALSE The result TRUE if the Storage contains the object; the result FALSE if not
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
	 * @param Object $obj
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
	 * @param Object $obj
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
	 * Removes all object from the storage
	 *
	 * @return void
	 */
	public function removeAll() {
		$this->storage = array();
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