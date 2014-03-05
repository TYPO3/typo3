<?php
namespace TYPO3\CMS\Core\Cache\Backend;

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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A caching backend customized explicitly for the class loader.
 * This backend is NOT public API
 *
 * @internal
 */
class EarlyClassLoaderBackend extends \TYPO3\CMS\Core\Cache\Backend\AbstractBackend implements \TYPO3\CMS\Core\Cache\Backend\PhpCapableBackendInterface {

	/**
	 * @var array Holds cache entries
	 */
	protected $memoryBackend = array();

	/**
	 * Construct this backend
	 */
	public function __construct() {
		parent::__construct('production', array());
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry. If the backend does not support tags, this option can be ignored.
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
	 * @return void
	 * @throws \TYPO3\CMS\Core\Cache\Exception if no cache frontend has been set.
	 * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if the data is not a string
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		$this->memoryBackend[$entryIdentifier] = $data;
	}

	/**
	 * Loads data from the cache.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @api
	 */
	public function get($entryIdentifier) {
		return isset($this->memoryBackend[$entryIdentifier]) ? $this->memoryBackend[$entryIdentifier] : FALSE;
	}

	/**
	 * A method that returns all records
	 *
	 * @return array
	 */
	public function getAll() {
		return $this->memoryBackend;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @api
	 */
	public function has($entryIdentifier) {
		return isset($this->memoryBackend[$entryIdentifier]);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @api
	 */
	public function remove($entryIdentifier) {
		if (isset($this->memoryBackend[$entryIdentifier])) {
			unset($this->memoryBackend[$entryIdentifier]);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @api
	 */
	public function flush() {
		$this->memoryBackend = array();
	}

	/**
	 * Does garbage collection
	 *
	 * @return void
	 * @api
	 */
	public function collectGarbage() {
	}

	/**
	 * Loads PHP code from the cache and require_onces it right away.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed Potential return value from the include operation
	 * @api
	 */
	public function requireOnce($entryIdentifier) {
		return isset($this->memoryBackend[$entryIdentifier]) ? require_once ($this->memoryBackend[$entryIdentifier]) : FALSE;
	}

	/**
	 * Used to set alias for class
	 *
	 * @TODO: Rename method
	 * @param string $entryIdentifier
	 * @param string $filePath
	 * @internal This is not an API method
	 */
	public function setLinkToPhpFile($entryIdentifier, $filePath) {
		$this->memoryBackend[$entryIdentifier] = $filePath;
	}

}
