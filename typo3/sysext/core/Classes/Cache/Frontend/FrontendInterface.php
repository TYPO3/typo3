<?php
namespace TYPO3\CMS\Core\Cache\Frontend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
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
 * Contract for a Cache (frontend)
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @api
 */
interface FrontendInterface
{
	/**
	 * "Magic" tag for class-related entries
	 */
	const TAG_CLASS = '%CLASS%';
	/**
	 * "Magic" tag for package-related entries
	 */
	const TAG_PACKAGE = '%PACKAGE%';
	/**
	 * Pattern an entry identifer must match.
	 */
	const PATTERN_ENTRYIDENTIFIER = '/^[a-zA-Z0-9_%\\-&]{1,250}$/';
	/**
	 * Pattern a tag must match.
	 */
	const PATTERN_TAG = '/^[a-zA-Z0-9_%\\-&]{1,250}$/';
	/**
	 * Returns this cache's identifier
	 *
	 * @return string The identifier for this cache
	 * @api
	 */
	public function getIdentifier();

	/**
	 * Returns the backend used by this cache
	 *
	 * @return \TYPO3\CMS\Core\Cache\Backend\BackendInterface The backend used by this cache
	 */
	public function getBackend();

	/**
	 * Saves data in the cache.
	 *
	 * @param string $entryIdentifier Something which identifies the data - depends on concrete cache
	 * @param mixed $data The data to cache - also depends on the concrete cache implementation
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL);

	/**
	 * Finds and returns data from the cache.
	 *
	 * @param string $entryIdentifier Something which identifies the cache entry - depends on concrete cache
	 * @return mixed
	 * @api
	 */
	public function get($entryIdentifier);

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with the content of all matching entries. An empty array if no entries matched
	 * @api
	 */
	public function getByTag($tag);

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @api
	 */
	public function has($entryIdentifier);

	/**
	 * Removes the given cache entry from the cache.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
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
	 * @api
	 */
	public function flushByTag($tag);

	/**
	 * Does garbage collection
	 *
	 * @return void
	 * @api
	 */
	public function collectGarbage();

	/**
	 * Checks the validity of an entry identifier. Returns TRUE if it's valid.
	 *
	 * @param string $identifier An identifier to be checked for validity
	 * @return boolean
	 * @api
	 */
	public function isValidEntryIdentifier($identifier);

	/**
	 * Checks the validity of a tag. Returns TRUE if it's valid.
	 *
	 * @param string $tag A tag to be checked for validity
	 * @return boolean
	 * @api
	 */
	public function isValidTag($tag);

}

?>