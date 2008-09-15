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
 * An abstract cache
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @version $Id$
 */
abstract class t3lib_cache_AbstractCache {

	const PATTERN_IDENTIFIER = '/^[a-zA-Z0-9_%]{1,250}$/';

	/**
	 * @var string Identifies this cache
	 */
	protected $identifier;

	/**
	 * @var t3lib_cache_AbstractBackend
	 */
	protected $backend;

	/**
	 * Constructs the cache
	 *
	 * @param string A identifier which describes this cache
	 * @param t3lib_cache_AbstractBackend Backend to be used for this cache
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws InvalidArgumentException if the identifier doesn't match PATTERN_IDENTIFIER
	 */
	public function __construct($identifier, t3lib_cache_AbstractBackend $backend) {
		if (!preg_match(self::PATTERN_IDENTIFIER, $identifier)) {
			throw new InvalidArgumentException('"' . $identifier . '" is not a valid cache identifier.', 1203584729);
		}

		$this->identifier = $identifier;
		$this->backend    = $backend;
		$this->backend->setCache($this);
	}

	/**
	 * Returns this cache's identifier
	 *
	 * @return string The identifier for this cache
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * Returns the backend used by this cache
	 *
	 * @return t3lib_cache_AbstractBackend The backend used by this cache
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBackend() {
		return $this->backend;
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string Something which identifies the data - depends on concrete cache
	 * @param mixed The data to cache - also depends on the concrete cache implementation
	 * @param array Tags to associate with this cache entry
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	abstract public function save($entryIdentifier, $data, array $tags = array());

	/**
	 * Loads data from the cache.
	 *
	 * @param string Something which identifies the cache entry - depends on concrete cache
	 * @return mixed
	 * @author Robert Lemke <robert@typo3.org>
	 */
	abstract public function load($entryIdentifier);

	/**
	 * Finds, loads, and returns all cache entries which are tagged by the specified tags.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * the tags.
	 *
	 * @param array An array of tags to search for, the "*" wildcard is supported
	 * @return array An array with all matching entries. An empty array if no entries matched
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function loadByTag(array $tags) {
		$loadedEntries = array();
		$foundEntries  = $this->findEntriesByTag($tags);

		foreach($foundEntries as $foundEntryIdentifier) {
			$loadedEntries[$foundEntryIdentifier] = $this->load($foundEntryIdentifier);
		}

		return $loadedEntries;
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the specified tags.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * a tag.
	 *
	 * @param array Array of tags to search for, the "*" wildcard is supported
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 */
	public function findEntriesByTag(array $tags) {
		return $this->backend->findEntriesByTags($tags);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Robert Lemke <robert@typo3.org>
	 */
	abstract public function has($entryIdentifier);

	/**
	 * Removes the given cache entry from the cache.
	 *
	 * @param string An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	abstract public function remove($entryIdentifier);

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flush() {
		$this->backend->flush();
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushByTag($tag) {
		$this->backend->flushByTag($tag);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_abstractcache.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_abstractcache.php']);
}

?>