<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Ingo Renner <ingo@typo3.org>
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
 * A caching backend which saves it's data in $GLOBALS - a very short living cache, probably useful only during page rendering
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @version $Id$
 */
class t3lib_cache_backend_Globals extends t3lib_cache_AbstractBackend {

	/**
	 * Constructs this backend
	 *
	 * @param mixed Configuration options - depends on the actual backend
	 */
	public function __construct(array $options = array()) {
		parent::__construct($options);

		if (!isset($GLOBALS['typo3CacheStorage'])) {
			$GLOBALS['typo3CacheStorage'] = array();
		}

		if (!is_object($this->cache)) {
			throw new t3lib_cache_Exception(
				'No cache frontend has been set yet via setCache().',
				1217611408
			);
		}

		$GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()] = array(
			'data' => array(),
			'tags' => array()
		);
	}

	/**
	 * Saves data in a cache file.
	 *
	 * @param string An identifier for this specific cache entry
	 * @param string The data to be stored
	 * @param array Tags to associate with this cache entry
	 * @param integer Ignored as $GLOBALS lasts for the time of the script execution only anyway
	 * @return void
	 * @throws t3lib_cache_Exception if the directory does not exist or is not writable, or if no cache frontend has been set.
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function save($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!self::isValidEntryIdentifier($entryIdentifier)) {
			throw new InvalidArgumentException(
				'"' . $entryIdentifier . '" is not a valid cache entry identifier.',
				1217611184
			);
		}

		if (!is_object($this->cache)) {
			throw new t3lib_cache_Exception(
				'No cache frontend has been set yet via setCache().',
				1217611191
			);
		}

		if (!is_string($data)) {
			throw new t3lib_cache_Exception_InvalidData(
				'The specified data is of type "' . gettype($data) . '" but a string is expected.',
				1217611199
			);
		}

		foreach ($tags as $tag) {
			if (!self::isValidTag($tag)) {
				throw new InvalidArgumentException(
					'"' . $tag . '" is not a valid tag for a cache entry.',
					1217611205
				);
			}
		}

			// saving data
		$GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['data'][$entryIdentifier] = $data;

			// tagging
		foreach ($tags as $tag) {
			if (!isset($GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['tags'][$tag])) {
				$GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['tags'][$tag] = array();
			}

			$GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['tags'][$tag][] = $entryIdentifier;
		}

	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function load($entryIdentifier) {
		$cacheEntry = FALSE;

		if (isset($GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['data'][$entryIdentifier])) {
			$cacheEntry = $GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['data'][$entryIdentifier];
		}

		return $cacheEntry;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param unknown_type
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function has($entryIdentifier) {
		return isset($GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['data'][$entryIdentifier]);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry.
	 *
	 * @param string Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function remove($entryIdentifier) {
		$cacheEntryFound = $this->has($entryIdentifier);

		if ($cacheEntryFound) {
			unset($GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['data'][$entryIdentifier]);
		}

		return $cacheEntryFound;
	}

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * the tag.
	 *
	 * @param string The tag to search for, the "*" wildcard is supported
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findEntriesByTag($tag) {
		$taggedEntries = array();

		if (!empty($GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['tags'][$tag])) {
			$taggedEntries = $GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['tags'][$tag];
		}

		return $taggedEntries;
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the specified tags.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * a tag.
	 *
	 * @param array Array of tags to search for, the "*" wildcard is supported
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findEntriesByTags(array $tags) {
		$taggedEntries = array();
		$foundEntries  = array();

		foreach ($tags as $tag) {
			$taggedEntries[$tag] = $this->findEntriesByTag($tag);
		}

		$intersectedTaggedEntries = call_user_func_array('array_intersect', $taggedEntries);

		foreach ($intersectedTaggedEntries as $entryIdentifier) {
			$foundEntries[$entryIdentifier] = $entryIdentifier;
		}

		return $foundEntries;
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flush() {
		$GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['data'] = array();
		$GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['tags'] = array();
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string The tag the entries must have
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTag($tag) {
		unset($GLOBALS['typo3CacheStorage'][$this->cache->getIdentifier()]['tags'][$tag]);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param array	The tags the entries must have
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTags(array $tags) {
		foreach ($tags as $tag) {
			$this->flushByTag($tag);
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_globals.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_globals.php']);
}

?>