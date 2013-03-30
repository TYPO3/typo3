<?php
namespace TYPO3\CMS\Core\Cache\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Ingo Renner <ingo@typo3.org>
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
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @author Ingo Renner <ingo@typo3.org>
 * @api
 */
class Typo3DatabaseBackend extends \TYPO3\CMS\Core\Cache\Backend\AbstractBackend implements \TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface {

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
	 * @var string Join condition for data and tags table
	 */
	protected $tableJoin;

	/**
	 * Set cache frontend instance and calculate data and tags table name
	 *
	 * @param \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache The frontend for this backend
	 * @return void
	 * @api
	 */
	public function setCache(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache) {
		parent::setCache($cache);
		$this->cacheTable = 'cf_' . $this->cacheIdentifier;
		$this->tagsTable = 'cf_' . $this->cacheIdentifier . '_tags';
		$this->initializeCommonReferences();
	}

	/**
	 * Initializes common references used in this backend.
	 *
	 * @return void
	 */
	protected function initializeCommonReferences() {
		$this->identifierField = $this->cacheTable . '.identifier';
		$this->expiresField = $this->cacheTable . '.expires';
		$this->maximumLifetime = self::FAKED_UNLIMITED_EXPIRE - $GLOBALS['EXEC_TIME'];
		$this->tableList = $this->cacheTable . ', ' . $this->tagsTable;
		$this->tableJoin = $this->identifierField . ' = ' . $this->tagsTable . '.identifier';
		$this->expiredStatement = $this->expiresField . ' < ' . $GLOBALS['EXEC_TIME'];
		$this->notExpiredStatement = $this->expiresField . ' >= ' . $GLOBALS['EXEC_TIME'];
	}

	/**
	 * Saves data in a cache file.
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws \TYPO3\CMS\Core\Cache\Exception if no cache frontend has been set.
	 * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if the data to be stored is not a string.
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		$this->throwExceptionIfFrontendDoesNotExist();
		if (!is_string($data)) {
			throw new \TYPO3\CMS\Core\Cache\Exception\InvalidDataException(
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
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->cacheTable, array(
			'identifier' => $entryIdentifier,
			'expires' => $expires,
			'content' => $data
		));
		if (count($tags)) {
			$fields = array();
			$fields[] = 'identifier';
			$fields[] = 'tag';
			$tagRows = array();
			foreach ($tags as $tag) {
				$tagRow = array();
				$tagRow[] = $entryIdentifier;
				$tagRow[] = $tag;
				$tagRows[] = $tagRow;
			}
			$GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows($this->tagsTable, $fields, $tagRows);
		}
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's data as a string or FALSE if the cache entry could not be loaded
	 */
	public function get($entryIdentifier) {
		$this->throwExceptionIfFrontendDoesNotExist();

		$cacheEntry = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'content',
			$this->cacheTable,
			'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable) . ' AND ' . $this->notExpiredStatement
		);
		if (is_array($cacheEntry)) {
			$cacheEntry = $cacheEntry['content'];
		}
		if ($this->compression && strlen($cacheEntry)) {
			$cacheEntry = gzuncompress($cacheEntry);
		}
		return $cacheEntry !== NULL ? $cacheEntry : FALSE;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier Specifies the identifier to check for existence
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 */
	public function has($entryIdentifier) {
		$this->throwExceptionIfFrontendDoesNotExist();
		$hasEntry = FALSE;
		$cacheEntries = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'*',
			$this->cacheTable,
			'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable) . ' AND ' . $this->notExpiredStatement
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
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 */
	public function remove($entryIdentifier) {
		$this->throwExceptionIfFrontendDoesNotExist();
		$entryRemoved = FALSE;
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$this->cacheTable,
			'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable)
		);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$this->tagsTable,
			'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->tagsTable)
		);
		if ($GLOBALS['TYPO3_DB']->sql_affected_rows($res) == 1) {
			$entryRemoved = TRUE;
		}
		return $entryRemoved;
	}

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 */
	public function findIdentifiersByTag($tag) {
		$this->throwExceptionIfFrontendDoesNotExist();
		$cacheEntryIdentifiers = array();
		$cacheEntryIdentifierRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			$this->identifierField,
			$this->tableList,
			$this->tagsTable . '.tag = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tag, $this->tagsTable) . ' AND ' . $this->tableJoin . ' AND ' . $this->notExpiredStatement,
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
	 */
	public function flush() {
		$this->throwExceptionIfFrontendDoesNotExist();
		$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery($this->cacheTable);
		$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery($this->tagsTable);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 */
	public function flushByTag($tag) {
		$this->throwExceptionIfFrontendDoesNotExist();
		$tagsTableWhereClause = $this->tagsTable . '.tag = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tag, $this->tagsTable);
		$this->deleteCacheTableRowsByTagsTableWhereClause($tagsTableWhereClause);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery($this->tagsTable, $tagsTableWhereClause);
	}

	/**
	 * Does garbage collection
	 *
	 * @return void
	 */
	public function collectGarbage() {
		$this->throwExceptionIfFrontendDoesNotExist();
		// Get identifiers of expired cache entries
		$tagsEntryIdentifierRowsResource = $GLOBALS['TYPO3_DB']->exec_SELECTquery('identifier', $this->cacheTable, $this->expiredStatement);
		$tagsEntryIdentifiers = array();
		while ($tagsEntryIdentifierRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($tagsEntryIdentifierRowsResource)) {
			$tagsEntryIdentifiers[] = $GLOBALS['TYPO3_DB']->fullQuoteStr($tagsEntryIdentifierRow['identifier'], $this->tagsTable);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($tagsEntryIdentifierRowsResource);
		// Delete tag rows connected to expired cache entries
		if (count($tagsEntryIdentifiers)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery($this->tagsTable, 'identifier IN (' . implode(', ', $tagsEntryIdentifiers) . ')');
		}
		// Delete expired cache rows
		$GLOBALS['TYPO3_DB']->exec_DELETEquery($this->cacheTable, $this->expiredStatement);
	}

	/**
	 * Returns the table where the cache entries are stored.
	 *
	 * @return string The cache table.
	 */
	public function getCacheTable() {
		$this->throwExceptionIfFrontendDoesNotExist();
		return $this->cacheTable;
	}

	/**
	 * Gets the table where cache tags are stored.
	 *
	 * @return string Name of the table storing tags
	 */
	public function getTagsTable() {
		$this->throwExceptionIfFrontendDoesNotExist();
		return $this->tagsTable;
	}

	/**
	 * Enable data compression
	 *
	 * @param boolean $compression TRUE to enable compression
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
	 * @throws \TYPO3\CMS\Core\Cache\Exception If there is no frontend instance in $this->cache
	 * @return void
	 */
	protected function throwExceptionIfFrontendDoesNotExist() {
		if (!$this->cache instanceof \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface) {
			throw new \TYPO3\CMS\Core\Cache\Exception('No cache frontend has been set via setCache() yet.', 1236518288);
		}
	}

	/**
	 * Calculate needed table definitions for this cache.
	 * This helper method is used by install tool and extension manager
	 * and is not part of the public API!
	 *
	 * @return string SQL of table definitions
	 */
	public function getTableDefinitions() {
		$cacheTableSql = file_get_contents(
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('core') .
			'Resources/Private/Sql/Cache/Backend/Typo3DatabaseBackendCache.sql'
		);
		$requiredTableStructures = str_replace('###CACHE_TABLE###', $this->cacheTable, $cacheTableSql) . LF . LF;
		$tagsTableSql = file_get_contents(
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('core') .
			'Resources/Private/Sql/Cache/Backend/Typo3DatabaseBackendTags.sql'
		);
		$requiredTableStructures .= str_replace('###TAGS_TABLE###', $this->tagsTable, $tagsTableSql) . LF;
		return $requiredTableStructures;
	}

	/**
	 * Deletes rows in cache table found by where clause on tags table
	 *
	 * @param string $tagsTableWhereClause The where clause for the tags table
	 * @return void
	 */
	protected function deleteCacheTableRowsByTagsTableWhereClause($tagsTableWhereClause) {
		$cacheEntryIdentifierRowsResource = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT identifier', $this->tagsTable, $tagsTableWhereClause);
		$cacheEntryIdentifiers = array();
		while ($cacheEntryIdentifierRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($cacheEntryIdentifierRowsResource)) {
			$cacheEntryIdentifiers[] = $GLOBALS['TYPO3_DB']->fullQuoteStr($cacheEntryIdentifierRow['identifier'], $this->cacheTable);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($cacheEntryIdentifierRowsResource);
		if (count($cacheEntryIdentifiers)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery($this->cacheTable, 'identifier IN (' . implode(', ', $cacheEntryIdentifiers) . ')');
		}
	}

}

?>