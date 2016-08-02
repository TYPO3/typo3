<?php
namespace TYPO3\CMS\Core\Cache\Backend;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * A caching backend which stores cache entries in database tables
 * @api
 */
class Typo3DatabaseBackend extends AbstractBackend implements TaggableBackendInterface
{
    /**
     * @var int Timestamp of 2038-01-01)
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
     * @var bool Indicates whether data is compressed or not (requires php zlib)
     */
    protected $compression = false;

    /**
     * @var int -1 to 9, indicates zlib compression level: -1 = default level 6, 0 = no compression, 9 maximum compression
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
     * @var int Maximum lifetime to stay with expire field below FAKED_UNLIMITED_LIFETIME
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
    public function setCache(FrontendInterface $cache)
    {
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
    protected function initializeCommonReferences()
    {
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
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
     * @return void
     * @throws \TYPO3\CMS\Core\Cache\Exception if no cache frontend has been set.
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if the data to be stored is not a string.
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        $this->throwExceptionIfFrontendDoesNotExist();
        if (!is_string($data)) {
            throw new Exception\InvalidDataException(
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
        $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->cacheTable, [
            'identifier' => $entryIdentifier,
            'expires' => $expires,
            'content' => $data
        ]);
        if (!empty($tags)) {
            $fields = [];
            $fields[] = 'identifier';
            $fields[] = 'tag';
            $tagRows = [];
            foreach ($tags as $tag) {
                $tagRow = [];
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
    public function get($entryIdentifier)
    {
        $this->throwExceptionIfFrontendDoesNotExist();

        $cacheEntry = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'content',
            $this->cacheTable,
            'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable) . ' AND ' . $this->notExpiredStatement
        );
        if (is_array($cacheEntry)) {
            $cacheEntry = $cacheEntry['content'];
        }
        if ($this->compression && (string)$cacheEntry !== '') {
            $cacheEntry = gzuncompress($cacheEntry);
        }
        return $cacheEntry !== null ? $cacheEntry : false;
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier Specifies the identifier to check for existence
     * @return bool TRUE if such an entry exists, FALSE if not
     */
    public function has($entryIdentifier)
    {
        $this->throwExceptionIfFrontendDoesNotExist();
        $hasEntry = false;
        $cacheEntries = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
            '*',
            $this->cacheTable,
            'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable) . ' AND ' . $this->notExpiredStatement
        );
        if ($cacheEntries >= 1) {
            $hasEntry = true;
        }
        return $hasEntry;
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     */
    public function remove($entryIdentifier)
    {
        $this->throwExceptionIfFrontendDoesNotExist();
        $entryRemoved = false;
        $GLOBALS['TYPO3_DB']->exec_DELETEquery(
            $this->cacheTable,
            'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->cacheTable)
        );
        // we need to save the affected rows as mysqli_affected_rows just returns the amount of affected rows
        // of the last call
        $affectedRows = $GLOBALS['TYPO3_DB']->sql_affected_rows();
        $GLOBALS['TYPO3_DB']->exec_DELETEquery(
            $this->tagsTable,
            'identifier = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($entryIdentifier, $this->tagsTable)
        );
        if ($affectedRows == 1) {
            $entryRemoved = true;
        }
        return $entryRemoved;
    }

    /**
     * Finds and returns all cache entries which are tagged by the specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     */
    public function findIdentifiersByTag($tag)
    {
        $this->throwExceptionIfFrontendDoesNotExist();
        $cacheEntryIdentifiers = [];
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
    public function flush()
    {
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
    public function flushByTag($tag)
    {
        $this->throwExceptionIfFrontendDoesNotExist();

        if ($this->isConnectionMysql()) {
            $GLOBALS['TYPO3_DB']->sql_query('
                DELETE tags2, cache1'
                . ' FROM ' . $this->tagsTable . ' AS tags1'
                . ' JOIN ' . $this->tagsTable . ' AS tags2 ON tags1.identifier = tags2.identifier'
                . ' JOIN ' . $this->cacheTable . ' AS cache1 ON tags1.identifier = cache1.identifier'
                . ' WHERE tags1.tag = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tag, $this->tagsTable)
            );
        } else {
            $tagsTableWhereClause = $this->tagsTable . '.tag = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tag, $this->tagsTable);
            $cacheEntryIdentifierRowsResource = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT identifier', $this->tagsTable, $tagsTableWhereClause);
            $cacheEntryIdentifiers = [];
            while ($cacheEntryIdentifierRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($cacheEntryIdentifierRowsResource)) {
                $cacheEntryIdentifiers[] = $GLOBALS['TYPO3_DB']->fullQuoteStr($cacheEntryIdentifierRow['identifier'], $this->cacheTable);
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($cacheEntryIdentifierRowsResource);
            if (!empty($cacheEntryIdentifiers)) {
                $deleteWhereClause = 'identifier IN (' . implode(', ', $cacheEntryIdentifiers) . ')';
                $GLOBALS['TYPO3_DB']->exec_DELETEquery($this->cacheTable, $deleteWhereClause);
                $GLOBALS['TYPO3_DB']->exec_DELETEquery($this->tagsTable, $deleteWhereClause);
            }
        }
    }

    /**
     * Does garbage collection
     *
     * @return void
     */
    public function collectGarbage()
    {
        $this->throwExceptionIfFrontendDoesNotExist();

        if ($this->isConnectionMysql()) {
            // First delete all expired rows from cache table and their connected tag rows
            $GLOBALS['TYPO3_DB']->sql_query(
                'DELETE cache, tags'
                . ' FROM ' . $this->cacheTable . ' AS cache'
                . ' LEFT OUTER JOIN ' . $this->tagsTable . ' AS tags ON cache.identifier = tags.identifier'
                . ' WHERE cache.expires < ' . $GLOBALS['EXEC_TIME']
            );
            // Then delete possible "orphaned" rows from tags table - tags that have no cache row for whatever reason
            $GLOBALS['TYPO3_DB']->sql_query(
                'DELETE tags'
                . ' FROM ' . $this->tagsTable . ' AS tags'
                . ' LEFT OUTER JOIN ' . $this->cacheTable . ' AS cache ON tags.identifier = cache.identifier'
                . ' WHERE cache.identifier IS NULL'
            );
        } else {
            // Get identifiers of expired cache entries
            $cacheEntryIdentifierRowsResource = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT identifier', $this->cacheTable, 'expires < ' . $GLOBALS['EXEC_TIME']);
            $cacheEntryIdentifiers = [];
            while ($cacheEntryIdentifierRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($cacheEntryIdentifierRowsResource)) {
                $cacheEntryIdentifiers[] = $GLOBALS['TYPO3_DB']->fullQuoteStr($cacheEntryIdentifierRow['identifier'], $this->tagsTable);
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($cacheEntryIdentifierRowsResource);
            // Delete tag rows connected to expired cache entries
            if (!empty($cacheEntryIdentifiers)) {
                $GLOBALS['TYPO3_DB']->exec_DELETEquery($this->tagsTable, 'identifier IN (' . implode(', ', $cacheEntryIdentifiers) . ')');
            }
            // Delete expired cache rows
            $GLOBALS['TYPO3_DB']->exec_DELETEquery($this->cacheTable, 'expires < ' . $GLOBALS['EXEC_TIME']);

            // Find out which "orphaned" tags rows exists that have no cache row and delete those, too.
            $result = $GLOBALS['TYPO3_DB']->sql_query(
                'SELECT tags.identifier'
                . ' FROM ' . $this->tagsTable . ' AS tags'
                . ' LEFT OUTER JOIN ' . $this->cacheTable . ' AS cache ON tags.identifier = cache.identifier'
                . ' WHERE cache.identifier IS NULL'
                . ' GROUP BY tags.identifier'
            );

            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                $tagsEntryIdentifiers[] = $GLOBALS['TYPO3_DB']->fullQuoteStr($row['identifier'], $this->tagsTable);
            }

            if (!empty($tagsEntryIdentifiers)) {
                $GLOBALS['TYPO3_DB']->sql_query(
                    'DELETE'
                    . ' FROM ' . $this->tagsTable
                    . ' WHERE identifier IN (' . implode(',', $tagsEntryIdentifiers) . ')'
                );
            }
        }
    }

    /**
     * Returns the table where the cache entries are stored.
     *
     * @return string The cache table.
     */
    public function getCacheTable()
    {
        $this->throwExceptionIfFrontendDoesNotExist();
        return $this->cacheTable;
    }

    /**
     * Gets the table where cache tags are stored.
     *
     * @return string Name of the table storing tags
     */
    public function getTagsTable()
    {
        $this->throwExceptionIfFrontendDoesNotExist();
        return $this->tagsTable;
    }

    /**
     * Enable data compression
     *
     * @param bool $compression TRUE to enable compression
     */
    public function setCompression($compression)
    {
        $this->compression = $compression;
    }

    /**
     * Set data compression level.
     * If compression is enabled and this is not set,
     * gzcompress default level will be used
     *
     * @param int -1 to 9: Compression level
     */
    public function setCompressionLevel($compressionLevel)
    {
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
    protected function throwExceptionIfFrontendDoesNotExist()
    {
        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('No cache frontend has been set via setCache() yet.', 1236518288);
        }
    }

    /**
     * Calculate needed table definitions for this cache.
     * This helper method is used by install tool and extension manager
     * and is not part of the public API!
     *
     * @return string SQL of table definitions
     */
    public function getTableDefinitions()
    {
        $cacheTableSql = file_get_contents(
            ExtensionManagementUtility::extPath('core') .
            'Resources/Private/Sql/Cache/Backend/Typo3DatabaseBackendCache.sql'
        );
        $requiredTableStructures = str_replace('###CACHE_TABLE###', $this->cacheTable, $cacheTableSql) . LF . LF;
        $tagsTableSql = file_get_contents(
            ExtensionManagementUtility::extPath('core') .
            'Resources/Private/Sql/Cache/Backend/Typo3DatabaseBackendTags.sql'
        );
        $requiredTableStructures .= str_replace('###TAGS_TABLE###', $this->tagsTable, $tagsTableSql) . LF;
        return $requiredTableStructures;
    }

    /**
     * This database backend uses some optimized queries for mysql
     * to get maximum performance.
     *
     * @return bool
     */
    protected function isConnectionMysql()
    {
        return !((bool)ExtensionManagementUtility::isLoaded('dbal'));
    }
}
