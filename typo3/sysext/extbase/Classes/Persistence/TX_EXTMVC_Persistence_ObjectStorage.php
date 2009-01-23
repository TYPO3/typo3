<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The storage for objects. It ensures the uniqueness of an object in the storage. It's a remake of the
 * SplObjectStorage introduced in a usable version in PHP 5.3.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Persistence_ObjectStorage implements Iterator, Countable {
	
	/**
	 * The array holding references to the stored objects.
	 *
	 * @var string
	 **/
	private $storage = array();
	
	/**
	 * An index for the Iterator interface
	 *
	 * @var string
	 **/
	private $index = 0;

	function rewind() {
		rewind($this->storage);
	}

	function valid() {
		return key($this->storage) !== false;
	}

	function key() {
		return $this->index;
	}

	function current() {
		return current($this->storage);
	}

	function next() {
		next($this->storage);
		$this->index++;
	}

	function count() {
		return count($this->storage);
	}

	/**
	 * Does the Storage contains the given object
	 *
	 * @param Object $obj 
	 * @return boolean TRUE|FALSE The result TRUE if the Storage contains the object; the result FALSE if not
	 */
	function contains($obj) {
		if (is_object($obj)) {
			foreach($this->storage as $object) {
				if ($object === $obj) return true;
			}
		}
		return false;
	}

	/**
	 * Attaches an object to the storage
	 *
	 * @param Object $obj 
	 * @return void
	 */
	function attach($obj) {
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
	function detach($obj) {
		if (is_object($obj)) {
			foreach($this->storage as $idx => $object) {
				if ($object === $obj) {
					unset($this->storage[$idx]);
					$this->rewind();
					return;
				}
			}
		}
	}
}

?>