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

namespace TYPO3\CMS\Redirects\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Cache\BloomFilter;

/**
 * Caches the redirects per source host which cannot be looked up by an indexed database
 * query: regular expression redirects, which need to be evaluated one by one, and flat
 * redirects with multiple trailing slashes in their source path. All other redirects are
 * looked up directly from the database, see getFlatRedirects() and
 * getRedirectsRespectingQueryParameters(), so the cache stays small even for installations
 * with a huge number of redirects. A Bloom filter of their source paths is cached as well,
 * so requests which cannot match any redirect, e.g. regular page views, do not need any
 * database lookup.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
class RedirectCacheService
{
    public function __construct(
        #[Autowire(service: 'cache.pages')]
        protected readonly FrontendInterface $cache,
        protected readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Fetches the regular expression redirects of a source host, grouped by respect_query_parameters,
     * the flat redirects with multiple trailing slashes in their source path and a filter of the source paths
     * of all other redirects
     */
    public function getRedirects(string $sourceHost): array
    {
        $redirects = $this->cache->get($this->buildCacheIdentifier($sourceHost));
        // empty array is considered as valid cache, so we need to check for array type here.
        if (!is_array($redirects)) {
            $redirects = $this->rebuildForHost($sourceHost);
        }
        return $redirects;
    }

    /**
     * Rebuilds the cache for the regular expression redirects of a source host, grouped by respect_query_parameters,
     * the flat redirects with multiple trailing slashes in their source path and a filter of the source paths
     * of all other redirects.
     * Does not include hidden or deleted redirects, but includes the ones with dynamic starttime/endtime.
     */
    public function rebuildForHost(string $sourceHost): array
    {
        $redirects = [
            'source_path_filter' => $this->buildSourcePathFilter($sourceHost)->toArray(),
        ];
        $queryBuilder = $this->getQueryBuilderForSourceHost($sourceHost);
        $queryBuilder
            ->select('*')
            ->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('is_regexp', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                    // RedirectService looks up flat redirects via index with at most one trailing slash
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('respect_query_parameters', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                        $queryBuilder->expr()->like('source_path', $queryBuilder->createNamedParameter('%//')),
                    ),
                )
            );

        // Ensure we have redirects which respect query parameters first, paired with a
        // cross dbms deterministic sorting criteria (`uid`) as last criteria.
        $queryBuilder
            ->orderBy('respect_query_parameters', 'desc')
            ->addOrderBy('uid', 'asc');

        $statement = $queryBuilder->executeQuery();
        while ($row = $statement->fetchAssociative()) {
            // Field "description" is not needed for FE redirect handling. Don't add it to cache.
            unset($row['description']);
            if (!$row['is_regexp']) {
                $redirects['flat'][rtrim($row['source_path'], '/') . '/'][$row['uid']] = $row;
            } elseif ($row['respect_query_parameters']) {
                $redirects['regexp_query_parameters'][$row['source_path']][$row['uid']] = $row;
            } else {
                $redirects['regexp_flat'][$row['source_path']][$row['uid']] = $row;
            }
        }
        $this->cache->set($this->buildCacheIdentifier($sourceHost), $redirects);
        return $redirects;
    }

    /**
     * Rebuild cache for each distinct redirect source_host.
     */
    public function rebuildAll(): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
        // remove all restriction, as we need to retrieve the source host even for hidden or deleted redirects.
        $queryBuilder->getRestrictions()->removeAll();
        $resultSet = $queryBuilder
            ->select('source_host')
            ->distinct()
            ->from('sys_redirect')
            ->executeQuery();
        $sourceHosts = [];
        while ($row = $resultSet->fetchAssociative()) {
            // An empty source host shares the cache entry of the wildcard host
            $sourceHosts[] = ($row['source_host'] ?? '') ?: '*';
        }
        foreach (array_unique($sourceHosts) as $sourceHost) {
            $this->rebuildForHost($sourceHost);
        }
    }

    /**
     * Fetches the redirects respecting query parameters whose source path equals one of the given
     * paths, grouped by source path and ordered by uid. Does NOT take starttime/endtime into account.
     *
     * @param array $redirects The cached redirects of the source host, see getRedirects()
     * @param string[] $sourcePaths
     * @return array<string, list<array<string, mixed>>>
     */
    public function getRedirectsRespectingQueryParameters(string $sourceHost, array $redirects, array $sourcePaths): array
    {
        $sourcePathFilter = BloomFilter::fromArray($redirects['source_path_filter'] ?? []);
        $sourcePaths = array_values(array_filter(
            $sourcePaths,
            fn(string $sourcePath): bool => $this->mightContainSourcePath($sourcePathFilter, $sourcePath, true)
        ));
        if ($sourcePaths === []) {
            return [];
        }
        $queryBuilder = $this->getQueryBuilderForNonRegularExpressionRedirects($sourceHost, true);
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in('source_path', $queryBuilder->createNamedParameter($sourcePaths, Connection::PARAM_STR_ARRAY))
        );
        $possibleRedirects = [];
        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            // The database collation may compare case-insensitively or ignore trailing spaces
            if (!in_array($row['source_path'], $sourcePaths, true)) {
                continue;
            }
            unset($row['description']);
            $possibleRedirects[$row['source_path']][] = $row;
        }
        return $possibleRedirects;
    }

    /**
     * Fetches the redirects ignoring query parameters whose source path equals the given path,
     * regardless of trailing slashes, ordered by uid. Does NOT take starttime/endtime into account.
     *
     * @param array $redirects The cached redirects of the source host, see getRedirects()
     * @return list<array<string, mixed>>
     */
    public function getFlatRedirects(string $sourceHost, array $redirects, string $path): array
    {
        $path = rtrim($path, '/');
        $possibleRedirects = [];
        if ($this->mightContainSourcePath(BloomFilter::fromArray($redirects['source_path_filter'] ?? []), $path, false)) {
            $queryBuilder = $this->getQueryBuilderForNonRegularExpressionRedirects($sourceHost, false);
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in('source_path', $queryBuilder->createNamedParameter([$path, $path . '/'], Connection::PARAM_STR_ARRAY))
            );
            $result = $queryBuilder->executeQuery();
            while ($row = $result->fetchAssociative()) {
                // The database collation may compare case-insensitively or ignore trailing spaces
                if (rtrim((string)$row['source_path'], '/') !== $path) {
                    continue;
                }
                unset($row['description']);
                $possibleRedirects[] = $row;
            }
        }
        if (!empty($redirects['flat'][$path . '/'])) {
            // Source paths with multiple trailing slashes cannot be looked up via index, thus they are cached
            $possibleRedirects = array_merge($possibleRedirects, array_values($redirects['flat'][$path . '/']));
            usort($possibleRedirects, static fn(array $a, array $b): int => (int)$a['uid'] <=> (int)$b['uid']);
        }
        return $possibleRedirects;
    }

    /**
     * Without a filter, e.g. if the cached redirects were not built by rebuildForHost(), the database has to be asked.
     */
    private function mightContainSourcePath(?BloomFilter $sourcePathFilter, string $sourcePath, bool $respectQueryParameters): bool
    {
        return $sourcePathFilter?->mightContain($this->buildSourcePathFilterKey($sourcePath, $respectQueryParameters)) ?? true;
    }

    /**
     * Builds the key under which a non-regular expression redirect is added to the source path filter.
     * Flat redirects are added without trailing slashes, as they match with and without them.
     */
    private function buildSourcePathFilterKey(string $sourcePath, bool $respectQueryParameters): string
    {
        return $respectQueryParameters ? 'query:' . $sourcePath : 'path:' . rtrim($sourcePath, '/');
    }

    /**
     * Builds a filter of the source paths of all non-regular expression redirects of a source host,
     * which allows RedirectService to skip the database lookup if a request cannot match any of them.
     */
    private function buildSourcePathFilter(string $sourceHost): BloomFilter
    {
        $queryBuilder = $this->getQueryBuilderForSourceHost($sourceHost);
        $nonRegularExpressionConstraint = $queryBuilder->expr()->eq('is_regexp', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT));
        $count = (int)$queryBuilder
            ->count('uid')
            ->andWhere($nonRegularExpressionConstraint)
            ->executeQuery()
            ->fetchOne();
        $filter = BloomFilter::create($count);
        $statement = $queryBuilder
            ->select('source_path', 'respect_query_parameters')
            ->executeQuery();
        while ($row = $statement->fetchAssociative()) {
            $filter->add($this->buildSourcePathFilterKey((string)$row['source_path'], (bool)$row['respect_query_parameters']));
        }
        return $filter;
    }

    /**
     * Does not include hidden or deleted redirects, but includes the ones with dynamic starttime/endtime.
     */
    private function getQueryBuilderForSourceHost(string $sourceHost): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class))
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->from('sys_redirect');
        if ($sourceHost === '' || $sourceHost === '*') {
            $queryBuilder->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('source_host', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->eq('source_host', $queryBuilder->createNamedParameter('*')),
                )
            );
        } else {
            $queryBuilder->where(
                $queryBuilder->expr()->eq('source_host', $queryBuilder->createNamedParameter($sourceHost))
            );
        }
        return $queryBuilder;
    }

    private function getQueryBuilderForNonRegularExpressionRedirects(string $sourceHost, bool $respectQueryParameters): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilderForSourceHost($sourceHost);
        return $queryBuilder
            ->select('*')
            ->andWhere(
                $queryBuilder->expr()->eq('is_regexp', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('respect_query_parameters', $queryBuilder->createNamedParameter((int)$respectQueryParameters, Connection::PARAM_INT)),
            )
            ->orderBy('uid', 'asc');
    }

    private function buildCacheIdentifier(string $sourceHost): string
    {
        // An empty source host is treated as wildcard, see getQueryBuilderForSourceHost()
        return 'redirects_lookup_' . sha1($sourceHost === '' ? '*' : $sourceHost);
    }
}
