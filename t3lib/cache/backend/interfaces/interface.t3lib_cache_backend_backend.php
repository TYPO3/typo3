<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Ingo Renner <ingo@typo3.org>
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
 * interface for a Cache Backend
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @api
 * @subpackage t3lib
 */
interface t3lib_cache_backend_Backend {

	/**
	 * Sets a reference to the cache frontend which uses this backend
	 *
	 * @param t3lib_cache_frontend_Frontend $cache The frontend for this backend
	 * @return void
	 */
	public function setCache(t3lib_cache_frontend_Frontend $cache);

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
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL);

	/**
	 * Loads data from the cache.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 */
	public function get($entryIdentifier);

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier: An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 */
	public function has($entryIdentifier);

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier: Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 */
	public function remove($entryIdentifier);

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 */
	public function flush();

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 */
	public function flushByTag($tag);

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tags.
	 *
	 * @param	array	The tags the entries must have
	 * @return void
	 * @author	Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTags(array $tags);

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 */
	public function findIdentifiersByTag($tag);

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tags.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end
	 * of a tag.
	 *
	 * @param array Array of tags to search for, the "*" wildcard is supported
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author	Ingo Renner <ingo@typo3.org>
	 */
	public function findIdentifiersByTags(array $tags);

	/**
	 * Does garbage collection
	 *
	 * @return void
	 */
	public function collectGarbage();

}


?>