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
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A caching backend which stores cache entries in database tables
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
     * @var int Maximum lifetime to stay with expire field below FAKED_UNLIMITED_LIFETIME
     */
    protected $maximumLifetime;

    /**
     * Set cache frontend instance and calculate data and tags table name
     *
     * @param FrontendInterface $cache The frontend for this backend
     */
    public function setCache(FrontendInterface $cache)
    {
        parent::setCache($cache);
        $this->cacheTable = 'cf_' . $this->cacheIdentifier;
        $this->tagsTable = 'cf_' . $this->cacheIdentifier . '_tags';
        $this->maximumLifetime = self::FAKED_UNLIMITED_EXPIRE - $GLOBALS['EXEC_TIME'];
    }

    /**
     * Saves data in a cache file.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws Exception if no cache frontend has been set.
     * @throws InvalidDataException if the data to be stored is not a string.
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        $this->throwExceptionIfFrontendDoesNotExist();
        if (!is_string($data)) {
            throw new InvalidDataException(
                'The specified data is of type "' . gettype($data) . '" but a string is expected.',
                1236518298
            );
        }
        if ($lifetime === null) {
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
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->cacheTable)
            ->insert(
                $this->cacheTable,
                [
                    'identifier' => $entryIdentifier,
                    'expires' => $expires,
                    'content' => $data,
                ],
                [
                    'content' => Connection::PARAM_LOB,
                ]
            );
        if (!empty($tags)) {
            $tagRows = [];
            foreach ($tags as $tag) {
                $tagRows[] = [$entryIdentifier, $tag];
            }
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($this->tagsTable)
                ->bulkInsert($this->tagsTable, $tagRows, ['identifier', 'tag']);
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->cacheTable);
        $cacheRow = $queryBuilder->select('content')
            ->from($this->cacheTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'identifier',
                    $queryBuilder->createNamedParameter($entryIdentifier, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->gte(
                    'expires',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        $content = '';
        if (!empty($cacheRow)) {
            $content = $cacheRow['content'];
        }
        if ($this->compression && (string)$content !== '') {
            $content = gzuncompress($content);
        }
        return !empty($cacheRow) ? $content : false;
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->cacheTable);
        $count = $queryBuilder->count('*')
            ->from($this->cacheTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'identifier',
                    $queryBuilder->createNamedParameter($entryIdentifier, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->gte(
                    'expires',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);
        return (bool)$count;
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
        $numberOfRowsRemoved = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->cacheTable)
            ->delete(
                $this->cacheTable,
                ['identifier' => $entryIdentifier]
            );
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tagsTable)
            ->delete(
                $this->tagsTable,
                ['identifier' => $entryIdentifier]
            );
        return (bool)$numberOfRowsRemoved;
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tagsTable);
        $result = $queryBuilder->select($this->cacheTable . '.identifier')
            ->from($this->cacheTable)
            ->from($this->tagsTable)
            ->where(
                $queryBuilder->expr()->eq($this->cacheTable . '.identifier', $queryBuilder->quoteIdentifier($this->tagsTable . '.identifier')),
                $queryBuilder->expr()->eq(
                    $this->tagsTable . '.tag',
                    $queryBuilder->createNamedParameter($tag, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->gte(
                    $this->cacheTable . '.expires',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                )
            )
            ->execute();
        while ($row = $result->fetch()) {
            $cacheEntryIdentifiers[$row['identifier']] = $row['identifier'];
        }
        return $cacheEntryIdentifiers;
    }

    /**
     * Removes all cache entries of this cache.
     */
    public function flush()
    {
        $this->throwExceptionIfFrontendDoesNotExist();
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->cacheTable)
            ->truncate($this->cacheTable);
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tagsTable)
            ->truncate($this->tagsTable);
    }

    /**
     * Removes all entries tagged by any of the specified tags. Performs the SQL
     * operation as a bulk query for better performance.
     *
     * @param string[] $tags
     */
    public function flushByTags(array $tags)
    {
        $this->throwExceptionIfFrontendDoesNotExist();

        if (empty($tags)) {
            return;
        }

        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);

        // A large set of tags was detected. Process it in chunks to guard against exceeding
        // maximum SQL query limits.
        if (count($tags) > 100) {
            array_walk(array_chunk($tags, 100), [$this, 'flushByTags']);
            return;
        }
        // VERY simple quoting of tags is sufficient here for performance. Tags are already
        // validated to not contain any bad characters, e.g. they are automatically generated
        // inside this class and suffixed with a pure integer enforced by DB.
        $quotedTagList = array_map(function ($value) {
            return '\'' . $value . '\'';
        }, $tags);

        if ($this->isConnectionMysql($connection)) {
            // Use a optimized query on mysql ... don't use on your own
            // * ansi sql does not know about multi table delete
            // * doctrine query builder does not support join on delete()
            $connection->executeQuery(
                'DELETE tags2, cache1'
                . ' FROM ' . $this->tagsTable . ' AS tags1'
                . ' JOIN ' . $this->tagsTable . ' AS tags2 ON tags1.identifier = tags2.identifier'
                . ' JOIN ' . $this->cacheTable . ' AS cache1 ON tags1.identifier = cache1.identifier'
                . ' WHERE tags1.tag IN (' . implode(',', $quotedTagList) . ')'
            );
        } else {
            $queryBuilder = $connection->createQueryBuilder();
            $result = $queryBuilder->select('identifier')
                ->from($this->tagsTable)
                ->where('tag IN (' . implode(',', $quotedTagList) . ')')
                // group by is like DISTINCT and used here to suppress possible duplicate identifiers
                ->groupBy('identifier')
                ->execute();
            $cacheEntryIdentifiers = [];
            while ($row = $result->fetch()) {
                $cacheEntryIdentifiers[] = $row['identifier'];
            }
            $quotedIdentifiers = $queryBuilder->createNamedParameter($cacheEntryIdentifiers, Connection::PARAM_STR_ARRAY);
            $queryBuilder->delete($this->cacheTable)
                ->where($queryBuilder->expr()->in('identifier', $quotedIdentifiers))
                ->execute();
            $queryBuilder->delete($this->tagsTable)
                ->where($queryBuilder->expr()->in('identifier', $quotedIdentifiers))
                ->execute();
        }
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     */
    public function flushByTag($tag)
    {
        $this->throwExceptionIfFrontendDoesNotExist();

        if (empty($tag)) {
            return;
        }

        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);

        $quotedTag = '\'' . $tag . '\'';

        if ($this->isConnectionMysql($connection)) {
            // Use a optimized query on mysql ... don't use on your own
            // * ansi sql does not know about multi table delete
            // * doctrine query builder does not support join on delete()
            $connection->executeQuery(
                'DELETE tags2, cache1'
                . ' FROM ' . $this->tagsTable . ' AS tags1'
                . ' JOIN ' . $this->tagsTable . ' AS tags2 ON tags1.identifier = tags2.identifier'
                . ' JOIN ' . $this->cacheTable . ' AS cache1 ON tags1.identifier = cache1.identifier'
                . ' WHERE tags1.tag = ' . $quotedTag
            );
        } else {
            $queryBuilder = $connection->createQueryBuilder();
            $result = $queryBuilder->select('identifier')
                ->from($this->tagsTable)
                ->where('tag = ' . $quotedTag)
                // group by is like DISTINCT and used here to suppress possible duplicate identifiers
                ->groupBy('identifier')
                ->execute();
            $cacheEntryIdentifiers = [];
            while ($row = $result->fetch()) {
                $cacheEntryIdentifiers[] = $row['identifier'];
            }
            $quotedIdentifiers = $queryBuilder->createNamedParameter($cacheEntryIdentifiers, Connection::PARAM_STR_ARRAY);
            $queryBuilder->delete($this->cacheTable)
                ->where($queryBuilder->expr()->in('identifier', $quotedIdentifiers))
                ->execute();
            $queryBuilder->delete($this->tagsTable)
                ->where($queryBuilder->expr()->in('identifier', $quotedIdentifiers))
                ->execute();
        }
    }

    /**
     * Does garbage collection
     */
    public function collectGarbage()
    {
        $this->throwExceptionIfFrontendDoesNotExist();

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);
        if ($this->isConnectionMysql($connection)) {
            // Use a optimized query on mysql ... don't use on your own
            // * ansi sql does not know about multi table delete
            // * doctrine query builder does not support join on delete()
            // First delete all expired rows from cache table and their connected tag rows
            $connection->executeQuery(
                'DELETE cache, tags'
                . ' FROM ' . $this->cacheTable . ' AS cache'
                . ' LEFT OUTER JOIN ' . $this->tagsTable . ' AS tags ON cache.identifier = tags.identifier'
                . ' WHERE cache.expires < ?',
                [(int)$GLOBALS['EXEC_TIME']]
            );
            // Then delete possible "orphaned" rows from tags table - tags that have no cache row for whatever reason
            $connection->executeQuery(
                'DELETE tags'
                . ' FROM ' . $this->tagsTable . ' AS tags'
                . ' LEFT OUTER JOIN ' . $this->cacheTable . ' as cache ON tags.identifier = cache.identifier'
                . ' WHERE cache.identifier IS NULL'
            );
        } else {
            $queryBuilder = $connection->createQueryBuilder();
            $result = $queryBuilder->select('identifier')
                ->from($this->cacheTable)
                ->where($queryBuilder->expr()->lt(
                    'expires',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                ))
                // group by is like DISTINCT and used here to suppress possible duplicate identifiers
                ->groupBy('identifier')
                ->execute();

            // Get identifiers of expired cache entries
            $cacheEntryIdentifiers = [];
            while ($row = $result->fetch()) {
                $cacheEntryIdentifiers[] = $row['identifier'];
            }
            if (!empty($cacheEntryIdentifiers)) {
                // Delete tag rows connected to expired cache entries
                $quotedIdentifiers = $queryBuilder->createNamedParameter($cacheEntryIdentifiers, Connection::PARAM_STR_ARRAY);
                $queryBuilder->delete($this->tagsTable)
                    ->where($queryBuilder->expr()->in('identifier', $quotedIdentifiers))
                    ->execute();
            }
            $queryBuilder->delete($this->cacheTable)
                ->where($queryBuilder->expr()->lt(
                    'expires',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                ))
                ->execute();

            // Find out which "orphaned" tags rows exists that have no cache row and delete those, too.
            $queryBuilder = $connection->createQueryBuilder();
            $result = $queryBuilder->select('tags.identifier')
                ->from($this->tagsTable, 'tags')
                ->leftJoin(
                    'tags',
                    $this->cacheTable,
                    'cache',
                    $queryBuilder->expr()->eq('tags.identifier', $queryBuilder->quoteIdentifier('cache.identifier'))
                )
                ->where($queryBuilder->expr()->isNull('cache.identifier'))
                ->groupBy('tags.identifier')
                ->execute();
            $tagsEntryIdentifiers = [];
            while ($row = $result->fetch()) {
                $tagsEntryIdentifiers[] = $row['identifier'];
            }
            if (!empty($tagsEntryIdentifiers)) {
                $quotedIdentifiers = $queryBuilder->createNamedParameter($tagsEntryIdentifiers, Connection::PARAM_STR_ARRAY);
                $queryBuilder->delete($this->tagsTable)
                    ->where($queryBuilder->expr()->in('identifier', $quotedIdentifiers))
                    ->execute();
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
     * This database backend uses some optimized queries for mysql
     * to get maximum performance.
     *
     * @param Connection $connection
     * @return bool
     */
    protected function isConnectionMysql(Connection $connection): bool
    {
        $serverVersion = $connection->getServerVersion();
        return (bool)(strpos($serverVersion, 'MySQL') === 0);
    }

    /**
     * Check if required frontend instance exists
     *
     * @throws Exception If there is no frontend instance in $this->cache
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
}
