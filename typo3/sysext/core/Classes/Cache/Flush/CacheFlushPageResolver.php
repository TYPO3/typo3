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

namespace TYPO3\CMS\Core\Cache\Flush;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Looks up the pages whose caches are affected by a changed record.
 *
 * @internal only to be used by CacheFlusher
 */
readonly class CacheFlushPageResolver
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * The page whose Page TSconfig applies to the record: a page itself, the
     * page a record is located on (0 on root level), for a workspace version
     * the page of its live record. Null if the record does not exist.
     */
    public function getPageTsConfigPageId(string $table, int $uid): ?int
    {
        if ($table === 'pages') {
            return $uid;
        }
        $row = $this->fetchPidAndLiveRecordUid($table, $uid);
        if ($row === null) {
            return null;
        }
        if ($row['liveRecordUid'] > 0) {
            return $this->fetchPidAndLiveRecordUid($table, $row['liveRecordUid'])['pid'] ?? null;
        }
        return $row['pid'];
    }

    /**
     * A record changed in a workspace leaves the live record untouched.
     */
    public function isWorkspaceVersion(string $table, int $uid): bool
    {
        return ($this->fetchPidAndLiveRecordUid($table, $uid)['liveRecordUid'] ?? 0) > 0;
    }

    /**
     * @return array{pid: int, liveRecordUid: int}|null
     */
    private function fetchPidAndLiveRecordUid(string $table, int $uid): ?array
    {
        $isWorkspaceAware = $this->tcaSchemaFactory->get($table)->hasCapability(TcaSchemaCapability::Workspace);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select(...($isWorkspaceAware ? ['pid', 't3ver_oid'] : ['pid']))
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        if ($row === false) {
            return null;
        }
        return ['pid' => (int)$row['pid'], 'liveRecordUid' => (int)($row['t3ver_oid'] ?? 0)];
    }

    /**
     * The page the record is located on, for "pages" the default language page itself.
     */
    public function getPageIdOfRecord(string $table, int $uid): int
    {
        if ($table !== 'pages') {
            return (int)(BackendUtility::getRecord($table, $uid, 'pid', '', false)['pid'] ?? 0);
        }
        $languageCapability = $this->tcaSchemaFactory->get('pages')->getCapability(TcaSchemaCapability::Language);
        $localizationParentFieldName = $languageCapability->getTranslationOriginPointerField()->getName();
        $row = BackendUtility::getRecord('pages', $uid, '*', '', false);
        $localizationParent = (int)($row[$localizationParentFieldName] ?? 0);
        return $localizationParent > 0 ? $localizationParent : $uid;
    }

    /**
     * For "pages", the page itself, its siblings and its parent page are
     * affected, for other tables the page the record is located on.
     * "TCEMAIN.clearCache_pageSiblingChildren" adds the subpages of the
     * siblings, "TCEMAIN.clearCache_pageGrandParent" the next page upwards.
     *
     * @param array $pageTsConfig Page TSconfig "TCEMAIN."
     * @return list<int>
     */
    public function getAffectedPageIds(string $table, int $pageId, array $pageTsConfig): array
    {
        if ($table === 'pages') {
            return $this->getAffectedPageIdsOfPage($pageId, $pageTsConfig);
        }
        if ($pageId <= 0) {
            return [];
        }
        $pageIds = [$pageId];
        if ($pageTsConfig['clearCache_pageGrandParent'] ?? false) {
            $grandParentPageId = (int)(BackendUtility::getRecord('pages', $pageId, 'pid')['pid'] ?? 0);
            if ($grandParentPageId > 0) {
                $pageIds[] = $grandParentPageId;
            }
        }
        return $pageIds;
    }

    /**
     * @return list<int>
     */
    private function getAffectedPageIdsOfPage(int $pageId, array $pageTsConfig): array
    {
        $pageIds = [];
        $queryBuilder = $this->getQueryBuilderForPages();
        $siblings = $queryBuilder
            ->select('A.pid AS pid', 'B.uid AS uid')
            ->from('pages', 'A')
            ->from('pages', 'B')
            ->where(
                $queryBuilder->expr()->eq('A.uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('B.pid', $queryBuilder->quoteIdentifier('A.pid')),
                $queryBuilder->expr()->gte('A.pid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery();
        $parentPageId = 0;
        while ($sibling = $siblings->fetchAssociative()) {
            $pageIds[] = (int)$sibling['uid'];
            $parentPageId = (int)$sibling['pid'];
            if ($pageTsConfig['clearCache_pageSiblingChildren'] ?? false) {
                $childrenQueryBuilder = $this->getQueryBuilderForPages();
                $children = $childrenQueryBuilder
                    ->select('uid')
                    ->from('pages')
                    ->where($childrenQueryBuilder->expr()->eq(
                        'pid',
                        $childrenQueryBuilder->createNamedParameter((int)$sibling['uid'], Connection::PARAM_INT)
                    ))
                    ->executeQuery();
                while ($child = $children->fetchAssociative()) {
                    $pageIds[] = (int)$child['uid'];
                }
            }
        }
        if ($parentPageId > 0) {
            $pageIds[] = $parentPageId;
        }
        if ($pageTsConfig['clearCache_pageGrandParent'] ?? false) {
            $parentQueryBuilder = $this->getQueryBuilderForPages();
            $parentPage = $parentQueryBuilder
                ->select('pid')
                ->from('pages')
                ->where($parentQueryBuilder->expr()->eq(
                    'uid',
                    $parentQueryBuilder->createNamedParameter($parentPageId, Connection::PARAM_INT)
                ))
                ->executeQuery()
                ->fetchAssociative();
            if (!empty($parentPage)) {
                $pageIds[] = (int)$parentPage['pid'];
            }
        }
        return $pageIds;
    }

    private function getQueryBuilderForPages(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());
        return $queryBuilder;
    }
}
