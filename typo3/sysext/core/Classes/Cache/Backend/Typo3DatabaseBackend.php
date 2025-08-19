<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Cache\Backend;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A caching backend which stores cache entries in database tables
 */
class Typo3DatabaseBackend extends AbstractBackend implements TaggableBackendInterface
{
    /**
     * @var int Timestamp of 2038-01-01
     */
    protected const FAKED_UNLIMITED_EXPIRE = 2145909600;
    /**
     * @var string Name of the cache data table
     */
    protected string $cacheTable;

    /**
     * @var string Name of the cache tags table
     */
    protected string $tagsTable;

    /**
     * @var bool Indicates whether data is compressed or not (requires php zlib)
     */
    protected bool $compression = false;

    /**
     * @var int -1 to 9, indicates zlib compression level: -1 = default level 6, 0 = no compression, 9 maximum compression
     */
    protected int $compressionLevel = -1;

    /**
     * @var int Maximum lifetime to stay with expire field below FAKED_UNLIMITED_LIFETIME
     */
    protected int $maximumLifetime;

    public function setCache(FrontendInterface $cache): void
    {
        parent::setCache($cache);
        $this->cacheTable = 'cache_' . $this->cacheIdentifier;
        $this->tagsTable = 'cache_' . $this->cacheIdentifier . '_tags';
        $this->maximumLifetime = self::FAKED_UNLIMITED_EXPIRE - $GLOBALS['EXEC_TIME'];
    }

    public function set(string $entryIdentifier, string $data, array $tags = [], $lifetime = null): void
    {
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
                ->bulkInsert($this->tagsTable, $tagRows, ['identifier', 'tag'], ['identifier' => Connection::PARAM_STR, 'tag' => Connection::PARAM_STR]);
        }
    }

    public function get(string $entryIdentifier): mixed
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->cacheTable);
        $cacheRow = $queryBuilder->select('content')
            ->from($this->cacheTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'identifier',
                    $queryBuilder->createNamedParameter($entryIdentifier)
                ),
                $queryBuilder->expr()->gte(
                    'expires',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        $content = '';
        if (!empty($cacheRow)) {
            $content = $cacheRow['content'];
        }
        if ($this->compression && (string)$content !== '') {
            $content = gzuncompress($content);
        }
        return empty($cacheRow) ? false : $content;
    }

    public function has(string $entryIdentifier): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->cacheTable);
        $count = $queryBuilder->count('*')
            ->from($this->cacheTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'identifier',
                    $queryBuilder->createNamedParameter($entryIdentifier)
                ),
                $queryBuilder->expr()->gte(
                    'expires',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
        return (bool)$count;
    }

    public function remove(string $entryIdentifier): bool
    {
        $numberOfRowsRemoved = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->cacheTable)
            ->delete(
                $this->cacheTable,
                ['identifier' => $entryIdentifier],
                ['identifier' => Connection::PARAM_STR]
            );
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tagsTable)
            ->delete(
                $this->tagsTable,
                ['identifier' => $entryIdentifier],
                ['identifier' => Connection::PARAM_STR]
            );
        return (bool)$numberOfRowsRemoved;
    }

    public function findIdentifiersByTag(string $tag): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tagsTable);
        $result = $queryBuilder->select($this->cacheTable . '.identifier')
            ->from($this->cacheTable)
            ->from($this->tagsTable)
            ->where(
                $queryBuilder->expr()->eq($this->cacheTable . '.identifier', $queryBuilder->quoteIdentifier($this->tagsTable . '.identifier')),
                $queryBuilder->expr()->eq(
                    $this->tagsTable . '.tag',
                    $queryBuilder->createNamedParameter($tag)
                ),
                $queryBuilder->expr()->gte(
                    $this->cacheTable . '.expires',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], Connection::PARAM_INT)
                )
            )
            ->groupBy($this->cacheTable . '.identifier')
            ->executeQuery();
        $identifiers = $result->fetchFirstColumn();
        return array_combine($identifiers, $identifiers);
    }

    public function flush(): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable)->truncate($this->cacheTable);
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tagsTable)->truncate($this->tagsTable);
    }

    public function flushByTags(array $tags): void
    {
        if (empty($tags)) {
            return;
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);
        // A large set of tags was detected. Process it in chunks to guard against exceeding
        // maximum SQL query limits.
        if (count($tags) > 100) {
            $chunks = array_chunk($tags, 100);
            array_walk($chunks, $this->flushByTags(...));
            return;
        }
        $queryBuilder = $connection->createQueryBuilder();
        $result = $queryBuilder->select('identifier')
            ->from($this->tagsTable)
            ->where(
                $queryBuilder->expr()->in('tag', $queryBuilder->quoteArrayBasedValueListToStringList($tags)),
            )
            // group by is like DISTINCT and used here to suppress possible duplicate identifiers
            ->groupBy('identifier')
            ->executeQuery();
        $cacheEntryIdentifiers = $result->fetchFirstColumn();
        $this->flushCacheByCacheEntryIdentifiers($cacheEntryIdentifiers);
    }

    public function flushByTag(string $tag): void
    {
        if (empty($tag)) {
            return;
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);
        $queryBuilder = $connection->createQueryBuilder();
        $result = $queryBuilder->select('identifier')
            ->from($this->tagsTable)
            ->where(
                $queryBuilder->expr()->eq('tag', $queryBuilder->quote($tag)),
            )
            // group by is like DISTINCT and used here to suppress possible duplicate identifiers
            ->groupBy('identifier')
            ->executeQuery();
        $cacheEntryIdentifiers = $result->fetchFirstColumn();
        $this->flushCacheByCacheEntryIdentifiers($cacheEntryIdentifiers);
    }

    private function flushCacheByCacheEntryIdentifiers(array $cacheEntryIdentifiers): void
    {
        if ($cacheEntryIdentifiers === []) {
            // Nothing to do, return early.
            return;
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);
        $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());
        foreach (array_chunk($cacheEntryIdentifiers, $maxBindParameters) as $chunk) {
            // Don't reuse QueryBuilder instance, create new one.
            $queryBuilder = $connection->createQueryBuilder();
            // Using string-list here directly is okay and mitigates additional processing
            // for database driver without named placeholder support, which comes with a
            // performance penalty we can work around and also do it only once per chunk.
            $quotedIdentifiers = $queryBuilder->quoteArrayBasedValueListToStringList($chunk);
            $queryBuilder->delete($this->cacheTable)
                ->where($queryBuilder->expr()->in('identifier', $quotedIdentifiers))
                ->executeStatement();
            // Don't reuse QueryBuilder instance, create new one.
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->delete($this->tagsTable)
                ->where($queryBuilder->expr()->in('identifier', $quotedIdentifiers))
                ->executeStatement();
        }
    }

    public function collectGarbage(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);
        $queryBuilder = $connection->createQueryBuilder();
        $result = $queryBuilder->select('identifier')
            ->from($this->cacheTable)
            ->where($queryBuilder->expr()->lt(
                'expires',
                $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], Connection::PARAM_INT)
            ))
            // group by is like DISTINCT and used here to suppress possible duplicate identifiers
            ->groupBy('identifier')
            ->executeQuery();

        // Get identifiers of expired cache entries
        $cacheEntryIdentifiers = $result->fetchFirstColumn();
        if (!empty($cacheEntryIdentifiers)) {
            // Delete tag rows connected to expired cache entries
            $quotedIdentifiers = $queryBuilder->createNamedParameter($cacheEntryIdentifiers, Connection::PARAM_STR_ARRAY);
            $queryBuilder->delete($this->tagsTable)
                ->where($queryBuilder->expr()->in('identifier', $quotedIdentifiers))
                ->executeStatement();
        }
        $queryBuilder->delete($this->cacheTable)
            ->where($queryBuilder->expr()->lt(
                'expires',
                $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], Connection::PARAM_INT)
            ))
            ->executeStatement();

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
            ->executeQuery();
        $tagsEntryIdentifiers = $result->fetchFirstColumn();

        if (!empty($tagsEntryIdentifiers)) {
            $queryBuilder = $connection->createQueryBuilder();
            $quotedIdentifiers = $queryBuilder->createNamedParameter($tagsEntryIdentifiers, Connection::PARAM_STR_ARRAY);
            $queryBuilder->delete($this->tagsTable)
                ->where($queryBuilder->expr()->in('identifier', $quotedIdentifiers))
                ->executeStatement();
        }
    }

    protected function setCompression(bool $compression): void
    {
        $this->compression = $compression;
    }

    /**
     * Set data compression level.
     * If compression is enabled and this is not set,
     * gzcompress default level will be used
     *
     * @param int $compressionLevel -1 to 9: Compression level
     */
    protected function setCompressionLevel(int $compressionLevel): void
    {
        if ($compressionLevel >= -1 && $compressionLevel <= 9) {
            $this->compressionLevel = $compressionLevel;
        }
    }

    /**
     * Calculate needed table definitions for this cache.
     * This helper method is used by install tool and extension manager
     * and is not part of the public API!
     *
     * @return string SQL of table definitions
     */
    public function getTableDefinitions(): string
    {
        $cacheTableSql = (string)file_get_contents(
            ExtensionManagementUtility::extPath('core') .
            'Resources/Private/Sql/Cache/Backend/Typo3DatabaseBackendCache.sql'
        );
        $requiredTableStructures = str_replace('###CACHE_TABLE###', $this->cacheTable, $cacheTableSql) . LF . LF;
        $tagsTableSql = (string)file_get_contents(
            ExtensionManagementUtility::extPath('core') .
            'Resources/Private/Sql/Cache/Backend/Typo3DatabaseBackendTags.sql'
        );
        return $requiredTableStructures . (str_replace('###TAGS_TABLE###', $this->tagsTable, $tagsTableSql) . LF);
    }
}
