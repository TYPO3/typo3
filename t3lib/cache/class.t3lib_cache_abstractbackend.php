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
 * An abstract caching backend
 *
 * This file is a backport from FLOW3
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib_cache
 * @version $Id$
 */
abstract class t3lib_cache_AbstractBackend {

	/**
	 * Pattern an entry identifer must match.
	 */
	const PATTERN_ENTRYIDENTIFIER = '/^[a-zA-Z0-9_%]{1,250}$/';

	/**
	 * Pattern a tag identifer must match.
	 */
	const PATTERN_TAG = '/^[a-zA-Z0-9_%]{1,250}$/';

	/**
	 * @var t3lib_cache_AbstractCache Reference to the cache which uses this backend
	 */
	protected $cache;

	/**
	 * @var integer Default lifetime of a cache entry in seconds
	 */
	protected $defaultLifetime = 3600;


	/**
	 * Constructs this backend
	 *
	 * @param mixed Configuration options - depends on the actual backend
	 */
	public function __construct(array $options = array()) {
		if (is_array($options) || $options instanceof ArrayAccess) {
			foreach ($options as $optionKey => $optionValue) {
				$methodName = 'set' . ucfirst($optionKey);
				if (method_exists($this, $methodName)) {
					$this->$methodName($optionValue);
				}
			}
		}
	}

	/**
	 * Sets a reference to the cache which uses this backend
	 *
	 * @param t3lib_cache_AbstractCache The frontend for this backend
	 * @return void
	 */
	public function setCache(t3lib_cache_AbstractCache $cache) {
		$this->cache = $cache;
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string An identifier for this specific cache entry
	 * @param string The data to be stored
	 * @param array Tags to associate with this cache entry
	 * @param integer Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws t3lib_cache_Exception if no cache frontend has been set.
	 * @throws InvalidArgumentException if the identifier is not valid
	 * @throws t3lib_cache_Exception_InvalidData if the data is not a string
	 */
	abstract public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL);

	/**
	 * Loads data from the cache.
	 *
	 * @param string An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 */
	abstract public function get($entryIdentifier);

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 */
	abstract public function has($entryIdentifier);

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 */
	abstract public function remove($entryIdentifier);

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 */
	abstract public function flush();

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string The tag the entries must have
	 * @return void
	 */
	abstract public function flushByTag($tag);

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tags.
	 *
	 * @param	array	The tags the entries must have
	 * @return void
	 */
	abstract public function flushByTags(array $tags);

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the specified tag.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * the tag.
	 *
	 * @param string The tag to search for, the "*" wildcard is supported
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 */
	abstract public function findEntriesByTag($tag);

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the specified tags.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * a tag.
	 *
	 * @param array Array of tags to search for, the "*" wildcard is supported
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 */
	abstract public function findEntriesByTags(array $tags);

	/**
	 * Checks the validity of an entry identifier. Returns true if it's valid.
	 *
	 * @param string An identifier to be checked for validity
	 * @return boolean
	 * @author Christian Jul Jensen <julle@typo3.org>
	 */
	static public function isValidEntryIdentifier($identifier) {
		return preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) === 1;
	}

	/**
	 * Checks the validity of a tag. Returns true if it's valid.
	 *
	 * @param string An identifier to be checked for validity
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function isValidTag($tag) {
		return preg_match(self::PATTERN_TAG, $tag) === 1;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_abstractbackend.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_abstractbackend.php']);
}

?>