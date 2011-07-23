<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Ingo Renner <ingo@typo3.org>
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
 * A caching backend which stores cache entries in database tables
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @api
 * @scope prototype
 */
class t3lib_cache_backend_DbBackend extends t3lib_cache_backend_AbstractBackend {

	/**
	 * @var integer Timestamp of 2038-01-01)
	 */
	const FAKED_UNLIMITED_EXPIRE = 2145909600;

	/**
	 * @var string Name of the cache data table
	 */
	protected $cacheTable;

	/**
	 * @var string Name of the cache tags table
	 */
	protected $tagsTable;

	/**
	 * @var string Name of the cache MM table
	 */
	protected $cacheMMTable;

	/**
	 * @var boolean Indicates wether data is compressed or not (requires php zlib)
	 */
	protected $compression = FALSE;

	/**
	 * @var integer -1 to 9, indicates zlib compression level: -1 = default level 6, 0 = no compression, 9 maximum compression
	 */
	protected $compressionLevel = -1;

	/**
	 * @var string Name of the identifier field, 'table_name.identifier'
	 */
	protected $identifierField;

	/**
	 * @var string Name of the expire field, 'table_name.expires'
	 */
	protected $expiresField;

	/**
	 * @var integer Maximum lifetime to stay with expire field below FAKED_UNLIMITED_LIFETIME
	 */
	protected $maximumLifetime;

	/**
	 * @var string SQL where for a not expired entry
	 */
	protected $notExpiredStatement;

	/**
	 * @var string Opposite of notExpiredStatement
	 */
	protected $expiredStatement;

	/**
	 * @var string Data and tags table name comma separated
	 */
	protected $tableList;

	/**
	 * @var string Data, relation and tags table name comma separated
	 */
	protected $tableMMList;

	/**
	 * @var string Join condition for data and tags table
	 */
	protected $tableJoin;

	/**
	 * Set cache frontend instance and calculate data and tags table name
	 *
	 * @param t3lib_cache_frontend_Frontend $cache The frontend for this backend
	 * @return void
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 * @author Michael Staatz <mstaatz@ymail.com>
	 * @api
	 */
	public function setCache(t3lib_cache_frontend_Frontend $cache) {
		parent::setCache($cache);

		$this->cacheTable = 'cachingframework_' . $this->cacheIdentifier;
		$this->tagsTable = 'cachingframework_' . $this->cacheIdentifier . '_tags';
		$this->cacheMMTable = 'cachingframework_' . $this->cacheIdentifier . '_MM';
		$this->initializeCommonReferences();
	}

	/**
	 * Initializes common references used in this backend.
	 *
	 * @return	void
	 * @author Michael Staatz <mstaatz@ymail.com>
	 */
	protected function initializeCommonReferences() {
		$this->identifierField = $this->cacheTable . '.identifier';
		$this->expiresField = $this->cacheTable . '.expires';
		$this->maximumLifetime = self::FAKED_UNLIMITED_EXPIRE - $GLOBALS['EXEC_TIME'];
		$this->tableList = $this->cacheTable . ', ' . $this->tagsTable;
		$this->tableMMList = $this->cacheTable . ', ' . $this->cacheMMTable . ', ' . $this->tagsTable;
		$this->tableJoin = $this->identifierField . ' = ' . $this->tagsTable . '.identifier';
		$this->expiredStatement = $this->expiresField . ' < ' . $GLOBALS['EXEC_TIME'];
		$this->notExpiredStatement = $this->expiresField . ' >= ' . $GLOBALS['EXEC_TIME'];
	}

	/**
	 * Saves data in a cache file.
	 *
	 * @param string An identifier for this specific cache entry
	 * @param string The data to be stored
	 * @param array Tags to associate with this cache entry
	 * @param integer Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws t3lib_cache_Exception if no cache frontend has been set.
	 * @throws t3lib_cache_exception_InvalidData if the data to be stored is not a string.
	 * @author Ingo Renner <ingo@typo3.org>
	 * @author Michael Staatz <mstaatz@ymail.com>
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		$this->throwExceptionIfFrontendDoesNotExist();

		if (!is_string($data)) {
			throw new t3lib_cache_exception_InvalidData(
				'The specified data is of type "' . gettype($data) . '" but a string is expected.',
				1236518298
			);
		}

		if (is_null($lifetime)) {
			$lifetime = $this->defaultLifetime;
		}
		if ($lifetime === 0 || $lifetime > $this->maximumLifetime) {
			$lifetime = $this->maximumLifetime;
		}
		$expires = $GLOBALS['EXEC_TIME'] + $lifetime;

		$this->remove($entryIdentifier);

		if ($this->compression) {
			$data = gzcompress($data, $this->compressionLevel);
		}

		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$this->cacheTable,
			array(
				'identifier' => $entryIdentifier,
				'expires' => $expires,
				'content' => $data,
			)
		);

		$cacheEntryId = $GLOBALS['TYPO3_DB']->sql_insert_id();

		if (count($tags)) {
			foreach ($tags as $tag) {
				$tagEntryId = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
					'id',
					$this->tagsTable,
					'tag = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tag, $this->tagsTable)
				);

				if ($tagEntryId === FALSE) {
					$GLOBALS['TYPO3_DB']->exec_INSERTquery(
						$this->tagsTable,
						array(
							'tag' => $tag
						)
					);
					$tagEntryId = $GLOBALS['TYPO3_DB']->sql_insert_id();
				} else {
					$tagEntryId = $tagEntryId['id'];
				}

				$GLOBALS['TYPO3_DB']->exec_INSERTquery(
					$this->cacheMMTable,
					array(
						'id_cache' => $cacheEntryId,
						'id_tags' => $tagEntryId
					)
				);
			}
		}
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string An identifier which describes the cache entry to load
	 * @return mixed The cache entry's data as a string or FALSE if the cache entry could not be loaded
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function get($entryIdentifier) {
		$this->throwExceptionIfFrontendDoesNotExist();

		$cacheEntry = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'content',
			$this->cacheTable,
			'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable) .
				' AND ' . $this->notExpiredStatement
		);

		if (strlen($GLOBALS['TYPO3_DB']->sql_error()) > 0) {
			$this->flush();
		}

		if (is_array($cacheEntry)) {
			$cacheEntry = $cacheEntry['content'];
		}

		if ($this->compression && strlen($cacheEntry)) {
			$cacheEntry = gzuncompress($cacheEntry);
		}

		return $cacheEntry;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string Specifies the identifier to check for existence
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function has($entryIdentifier) {
		$this->throwExceptionIfFrontendDoesNotExist();

		$hasEntry = FALSE;

		$cacheEntries = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'*',
			$this->cacheTable,
			'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable) .
				' AND ' . $this->notExpiredStatement
		);
		if ($cacheEntries >= 1) {
			$hasEntry = TRUE;
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
	 * @author Michael Staatz <mstaatz@ymail.com>
	 */
	public function remove($entryIdentifier) {
		$this->throwExceptionIfFrontendDoesNotExist();

		$entryRemoved = FALSE;

		$cacheEntryId = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'id',
			$this->cacheTable,
			'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable) .
				' AND ' . $this->notExpiredStatement
		);

		if ($cacheEntryId === NULL) {
			return $entryRemoved;
		}

		$cacheEntryId = (int) $cacheEntryId['id'];

		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$this->cacheTable,
			'id = ' . $cacheEntryId
		);

		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$this->cacheMMTable,
			'id_cache = ' . $cacheEntryId
		);

		if ($GLOBALS['TYPO3_DB']->sql_affected_rows($res) == 1) {
			$entryRemoved = TRUE;
		}

		return $entryRemoved;
	}

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 *
	 * @param string The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Ingo Renner <ingo@typo3.org>
	 * @author Michael Staatz <mstaatz@ymail.com>
	 */
	public function findIdentifiersByTag($tag) {
		$this->throwExceptionIfFrontendDoesNotExist();

		$cacheEntryIdentifiers = array();

		$cacheEntryIdentifierRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			$this->identifierField,
			$this->tableMMList,
			$this->cacheMMTable . '.id_cache = ' . $this->cacheTable . '.id' .
				' AND ' . $this->tagsTable . '.id = ' . $this->cacheMMTable . '.id_tags' .
				' AND ' . $this->tagsTable . '.tag = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tag, $this->tagsTable) .
				' AND ' . $this->notExpiredStatement,
			$this->identifierField
		);

		foreach ($cacheEntryIdentifierRows as $cacheEntryIdentifierRow) {
			$cacheEntryIdentifiers[$cacheEntryIdentifierRow['identifier']] = $cacheEntryIdentifierRow['identifier'];
		}

		return $cacheEntryIdentifiers;
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 * @author Michael Staatz <mstaatz@ymail.com>
	 */
	public function flush() {
		$this->throwExceptionIfFrontendDoesNotExist();

		$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery($this->cacheTable);
		$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery($this->tagsTable);
		$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery($this->cacheMMTable);
		$GLOBALS['TYPO3_DB']->admin_query('DROP TABLE IF EXISTS ' . $this->cacheTable);
		$GLOBALS['TYPO3_DB']->admin_query('DROP TABLE IF EXISTS ' . $this->tagsTable);
		$GLOBALS['TYPO3_DB']->admin_query('DROP TABLE IF EXISTS ' . $this->cacheMMTable);
		$this->createCacheTable();
		$this->createTagsTable();
		$this->createCacheMMTable();
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string The tag the entries must have
	 * @return void
	 */
	public function flushByTag($tag) {
		$this->throwExceptionIfFrontendDoesNotExist();

		$tagsTableWhereClause = $this->tagsTable . '.tag = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tag, $this->tagsTable);

		$this->deleteCacheTableRowsByTagsTableWhereClause($tagsTableWhereClause);

		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$this->tagsTable,
			$tagsTableWhereClause
		);
	}

	/**
	 * Does garbage collection
	 *
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 * @author Michael Staatz <mstaatz@ymail.com>
	 */
	public function collectGarbage() {
		$this->throwExceptionIfFrontendDoesNotExist();

			// Get id's of expired cache entries
		$cacheEntryIdentifierRowsResource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'id',
			$this->cacheTable,
			$this->expiredStatement
		);

		$cacheMMEntryIdentifiersRows = array();
		while ($cacheMMEntryIdentifierRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($cacheEntryIdentifierRowsResource)) {
			$cacheMMEntryIdentifiersRows[] = t3lib_utility_Math::convertToPositiveInteger($cacheMMEntryIdentifierRow['id']);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($cacheEntryIdentifierRowsResource);

			// Delete rows connected to expired cache entries from MM table
		if (count($cacheMMEntryIdentifiersRows)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				$this->cacheMMTable,
				'id_cache IN (' . implode(', ', $cacheMMEntryIdentifiersRows) . ')'
			);
		}

			// Delete expired cache rows
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$this->cacheTable,
			$this->expiredStatement
		);
	}

	/**
	 * Sets the table where the cache entries are stored.
	 *
	 * @deprecated since TYPO3 4.6: The backend calculates the
	 * 		table name internally, this method does nothing anymore
	 * @param string $cacheTable Table name
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setCacheTable($cacheTable) {
		t3lib_div::logDeprecatedFunction();
	}

	/**
	 * Returns the table where the cache entries are stored.
	 *
	 * @return	string	The cache table.
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function getCacheTable() {
		$this->throwExceptionIfFrontendDoesNotExist();

		return $this->cacheTable;
	}

	/**
	 * Sets the table where cache tags are stored.
	 *
	 * @deprecated since TYPO3 4.6: The backend calculates the
	 * 		table name internally, this method does nothing anymore
	 * @param string $tagsTable: Tags table name
	 * @return void
	 */
	public function setTagsTable($tagsTable) {
		t3lib_div::logDeprecatedFunction();
	}

	/**
	 * Gets the table where cache tags are stored.
	 *
	 * @return	string		Name of the table storing tags
	 */
	public function getTagsTable() {
		$this->throwExceptionIfFrontendDoesNotExist();

		return $this->tagsTable;
	}

	/**
	 * Gets the table where cache tags relations are stored.
	 *
	 * @return	string		Name of the table storing cache tags relation
	 * @author Michael Staatz <mstaatz@ymail.com>
	 */
	public function getCacheMMTable() {
		$this->throwExceptionIfFrontendDoesNotExist();

		return $this->cacheMMTable;
	}

	/**
	 * Enable data compression
	 *
	 * @param boolean TRUE to enable compression
	 */
	public function setCompression($compression) {
		$this->compression = $compression;
	}

	/**
	 * Set data compression level.
	 * If compression is enabled and this is not set,
	 * gzcompress default level will be used
	 *
	 * @param integer -1 to 9: Compression level
	 */
	public function setCompressionLevel($compressionLevel) {
		if ($compressionLevel >= -1 && $compressionLevel <= 9) {
			$this->compressionLevel = $compressionLevel;
		}
	}

	/**
	 * Check if required frontend instance exists
	 *
	 * @throws t3lib_cache_Exception If there is no frontend instance in $this->cache
	 * @return void
	 */
	protected function throwExceptionIfFrontendDoesNotExist() {
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'No cache frontend has been set via setCache() yet.',
				1236518288
			);
		}
	}

	/**
	 * Create data table of cache
	 *
	 * @return void
	 */
	protected function createCacheTable() {
		$sql = file_get_contents(PATH_t3lib . 'cache/backend/resources/dbbackend-layout-cache.sql');
		$sql = str_replace('###CACHE_TABLE###', $this->cacheTable, $sql);
		$GLOBALS['TYPO3_DB']->admin_query($sql);
	}

	/**
	 * Create tags table of cache
	 *
	 * @return void
	 */
	protected function createTagsTable() {
		$sql = file_get_contents(PATH_t3lib . 'cache/backend/resources/dbbackend-layout-tags.sql');
		$sql = str_replace('###TAGS_TABLE###', $this->tagsTable, $sql);
		$GLOBALS['TYPO3_DB']->admin_query($sql);
	}

	/**
	 * Create tags table of cache
	 *
	 * @param void
	 * @return void
	 * @author Michael Staatz <mstaatz@ymail.com>
	 */
	protected function createCacheMMTable() {
		$sql = file_get_contents(PATH_t3lib . 'cache/backend/resources/dbbackend-layout-cache-mm.sql');
		$sql = str_replace('###CACHE_MM_TABLE###', $this->cacheMMTable, $sql);
		$GLOBALS['TYPO3_DB']->admin_query($sql);
	}

	/**
	 * Deletes rows in cache table found by where clause on tags table
	 * and deletes also the entries in the relation table.
	 *
	 * @param string The where clause for the tags table
	 * @return void
	 * @author Michael Staatz <mstaatz@ymail.com>
	 */
	protected function deleteCacheTableRowsByTagsTableWhereClause($tagsTableWhereClause) {

		$tagEntryIdentifier = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'id',
			$this->tagsTable,
			$tagsTableWhereClause
		);

		$cacheMMEntryIdentifierRowsRessource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'id_cache',
			$this->cacheMMTable,
			'id_tags = ' . t3lib_utility_Math::convertToPositiveInteger($tagEntryIdentifier['id'])
		);

		$cacheEntryIdentifiers = array();
		while ($cacheEntryIdentifierRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($cacheMMEntryIdentifierRowsRessource)) {
			$cacheEntryIdentifiers[] = $cacheEntryIdentifierRow['id_cache'];
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($cacheMMEntryIdentifierRowsRessource);

		if (count($cacheEntryIdentifiers)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				$this->cacheTable,
				'id IN (' . implode(', ', $cacheEntryIdentifiers) . ')'
			);

			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				$this->cacheMMTable,
				'id_cache IN (' . implode(', ', $cacheEntryIdentifiers) . ')'
			);
		}
	}
}
?>
