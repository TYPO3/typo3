<?php
namespace TYPO3\CMS\Core\Compatibility;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Thomas Maroschik <tmaroschik@dfau.de>
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
 * Enter descriptions here
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 * @internal
 */
class LoadedExtensionsArray implements \Iterator, \ArrayAccess, \Serializable, \Countable {

	/**
	 * @var \TYPO3\CMS\Core\Package\PackageManager
	 */
	protected $packageManager;

	/**
	 * @var array
	 */
	protected $loadedExtensionArrayElementCache = array();

	/**
	 * @var string
	 */
	protected $iteratorPosition;

	/**
	 * @param \TYPO3\CMS\Core\Package\PackageManager
	 */
	public function __construct(\TYPO3\CMS\Core\Package\PackageManager $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 */
	public function offsetExists($offset) {
		return $this->packageManager->isPackageActive($offset);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 * @return mixed Can return all value types.
	 */
	public function offsetGet($offset) {
		// Pass it through the package manager, as it resolves package aliases
		$package = $this->packageManager->getPackage($offset);
		$packageKey = $package->getPackageKey();
		if (!isset($this->loadedExtensionArrayElementCache[$packageKey])) {
			$this->loadedExtensionArrayElementCache[$packageKey] = new LoadedExtensionArrayElement($package);
		}
		return $this->loadedExtensionArrayElementCache[$packageKey];
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		throw new \InvalidArgumentException('The array $GLOBALS[\'TYPO3_LOADED_EXT\'] may not be modified.', 1361915596);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 * @return void
	 */
	public function offsetUnset($offset) {
		throw new \InvalidArgumentException('The array $GLOBALS[\'TYPO3_LOADED_EXT\'] may not be modified.', 1361915610);
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize() {
		return serialize($this->loadedExtensionArrayElementCache);
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized <p>
	 * The string representation of the object.
	 * </p>
	 * @return mixed the original value unserialized.
	 */
	public function unserialize($serialized) {
		$this->loadedExtensionArrayElementCache = unserialize($serialized);
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 */
	public function count() {
		return count($this->packageManager->getActivePackages());
	}


	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the current element
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 */
	public function current() {
		return $this->offsetGet($this->iteratorPosition);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Move forward to next element
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 */
	public function next() {
		$packageKeys = array_keys($this->packageManager->getActivePackages());
		$position = array_search($this->iteratorPosition, $packageKeys);
		if (isset($packageKeys[$position + 1])) {
			$this->iteratorPosition = $packageKeys[$position + 1];
		} else {
			$this->iteratorPosition = NULL;
		}
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the key of the current element
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 */
	public function key() {
		return $this->iteratorPosition;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Checks if current position is valid
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 */
	public function valid() {
		return $this->offsetExists($this->iteratorPosition);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Rewind the Iterator to the first element
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 */
	public function rewind() {
		$this->iteratorPosition = array_shift(array_keys($this->packageManager->getActivePackages()));
	}

	/**
	 *
	 */
	public function reset() {
		$this->loadedExtensionArrayElementCache = array();
		$this->rewind();
	}

	public function hasPackageManager() {
		return $this->packageManager !== NULL;
	}
}

?>