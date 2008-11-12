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
 * A caching backend which stores cache entries in files
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @version $Id$
 */
class t3lib_cache_backend_Db extends t3lib_cache_AbstractBackend {

	protected $cacheTable;

	/**
	 * Saves data in a cache file.
	 *
	 * @param string An identifier for this specific cache entry
	 * @param string The data to be stored
	 * @param array Tags to associate with this cache entry
	 * @param integer Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws t3lib_cache_Exception if the directory does not exist or is not writable, or if no cache frontend has been set.
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (is_null($lifetime)) {
			$lifetime = $this->defaultLifetime;
		}

		$this->remove($entryIdentifier);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$this->cacheTable,
			array(
				'identifier' => $entryIdentifier,
				'crdate'     => time(),
				'content'    => $data,
				'tags'       => implode(',', $tags),
				'lifetime'   => $lifetime
			)
		);
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string An identifier which describes the cache entry to load
	 * @return mixed The cache entry's data as a string or FALSE if the cache entry could not be loaded
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function get($entryIdentifier) {
		$cacheEntry = false;

		$caheEntries = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'content',
			$this->cacheTable,
			'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable) . ' '
				. 'AND ((crdate + lifetime) >= ' . time() . ' OR lifetime = 0)'
		);

		if (count($caheEntries) == 1) {
			$cacheEntry = $caheEntries[0]['content'];
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
		$hasEntry = false;

		$caheEntries = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'content',
			$this->cacheTable,
			'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable) . ' '
				. 'AND (crdate + lifetime) >= ' . time()
		);

		if (count($caheEntries) == 1) {
			$hasEntry = true;
		}

		return $hasEntry;
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
		$entryRemoved = false;

		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$this->cacheTable,
			'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable)
		);

		if($GLOBALS['TYPO3_DB']->sql_affected_rows($res) == 1) {
			$entryRemoved = true;
		}

		return $entryRemoved;
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
		$cacheEntries = array();

		$cacheEntryRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'identifier',
			$this->cacheTable,
			$this->getListQueryForTag($tag)
		);

		foreach ($cacheEntryRows as $cacheEntryRow) {
			$cacheEntries[$cacheEntryRow['identifier']] = $cacheEntryRow['identifier'];
		}

		return $cacheEntries;
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
		$cacheEntries = array();
		$whereClause  = array();

		foreach ($tags as $tag) {
			$whereClause[] = $this->getListQueryForTag($tag);
		}

		$cacheEntryRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'identifier',
			$this->cacheTable,
			implode(' AND ', $whereClause)
		);

		foreach ($cacheEntryRows as $cacheEntryRow) {
			$cacheEntries[$cacheEntryRow['identifier']] = $cacheEntryRow['identifier'];
		}

		return $cacheEntries;
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flush() {
		$GLOBALS['TYPO3_DB']->sql_query('TRUNCATE ' . $this->cacheTable);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string The tag the entries must have
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTag($tag) {
		foreach ($this->findEntriesByTag($tag) as $entryIdentifier) {
			$this->remove($entryIdentifier);
		}
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tags.
	 *
	 * @param array	The tags the entries must have
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTags(array $tags) {
		foreach ($this->findEntriesByTags($tags) as $entryIdentifier) {
			$this->remove($entryIdentifier);
		}
	}

	protected function setCacheTable($cacheTable) {
		$this->cacheTable = $cacheTable;
	}

	/**
	 * Gets the query to be used for selecting entries by a tag. The asterisk ("*")
	 * is allowed as a wildcard at the beginning and the end of a tag.
	 *
	 * @param string The tag to search for, the "*" wildcard is supported
	 * @return string the query to be used for selecting entries
	 * @author Oliver Hader <oliver@typo3.org>
	 */
	protected function getListQueryForTag($tag) {
		return str_replace('*', '%', $GLOBALS['TYPO3_DB']->listQuery('tags', $tag, $this->cacheTable));
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_db.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_db.php']);
}

?>