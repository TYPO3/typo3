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

namespace TYPO3\CMS\Core\Domain;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Domain\Record\PagePermissions;
use TYPO3\CMS\Core\Domain\Repository\PageAncestryRepository;
use TYPO3\CMS\Core\Exception\Page\BrokenRootLineException;
use TYPO3\CMS\Core\Exception\Page\CircularRootLineException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Resolves the ancestry of a page in a workspace with one database query, as the
 * backend sees it: pages deleted in the workspace are part of the ancestry, neither
 * language overlays nor mount points are applied, and a broken page tree results
 * in an incomplete ancestry instead of an exception.
 *
 * @internal not part of the public TYPO3 Core API yet.
 */
#[Autoconfigure(public: true)]
readonly class PageAncestryResolver
{
    public function __construct(
        private PageAncestryRepository $pageAncestryRepository,
        #[Autowire(lazy: true)]
        private RecordFactory $recordFactory,
        private ConnectionPool $connectionPool,
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
    ) {}

    public function resolve(int|Page $page, int $workspaceId, bool $includeDeleted = false): PageAncestry
    {
        $pageId = $page instanceof Page ? $page->getUid() : $page;
        $cacheIdentifier = 'page-ancestry-' . $pageId . '-' . $workspaceId . '-' . (int)$includeDeleted;
        $ancestry = $this->runtimeCache->get($cacheIdentifier);
        if ($ancestry instanceof PageAncestry) {
            return $ancestry;
        }
        $records = [];
        try {
            foreach ($this->pageAncestryRepository->findAncestry($pageId, $workspaceId, $includeDeleted, true) as $record) {
                $records[] = $record;
            }
        } catch (BrokenRootLineException|CircularRootLineException) {
            // The ancestry found so far is kept, it is reported as incomplete.
        }
        if ($records === [] && $pageId > 0) {
            // A page new in a workspace only exists in the ancestry of its own workspace
            $ownWorkspaceId = $this->findWorkspaceOfNewPage($pageId, $includeDeleted);
            if ($ownWorkspaceId !== null && $ownWorkspaceId !== $workspaceId) {
                $ancestry = $this->resolve($pageId, $ownWorkspaceId, $includeDeleted);
                $this->runtimeCache->set($cacheIdentifier, $ancestry);
                return $ancestry;
            }
        }
        $ancestry = new PageAncestry(
            $pageId,
            $records,
            $this->resolvePermissions($records),
            fn(array $record): Page => $this->createPage($record),
        );
        $this->runtimeCache->set($cacheIdentifier, $ancestry);
        return $ancestry;
    }

    private function findWorkspaceOfNewPage(int $pageId, bool $includeDeleted): ?int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        if (!$includeDeleted) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
        $workspaceId = $queryBuilder
            ->select('t3ver_wsid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_state', $queryBuilder->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT)),
                $queryBuilder->expr()->gt('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
        return $workspaceId === false ? null : (int)$workspaceId;
    }

    private function createPage(array $record): Page
    {
        $page = $this->recordFactory->createFromDatabaseRow('pages', $record);
        // A RecordCreationEvent listener may return a different record object
        return $page instanceof Page ? $page : new Page($record);
    }

    /**
     * Permission settings are taken from the live page, also if the page has a workspace version.
     *
     * @param list<array<string, mixed>> $records
     * @return array<int, PagePermissions|null>
     */
    private function resolvePermissions(array $records): array
    {
        $permissions = [];
        $versionedPageIds = [];
        foreach ($records as $record) {
            $pageId = (int)$record['uid'];
            if (isset($record['_ORIG_uid'])) {
                $versionedPageIds[] = $pageId;
                $permissions[$pageId] = null;
            } else {
                $permissions[$pageId] = PagePermissions::fromRecord($record);
            }
        }
        if ($versionedPageIds === []) {
            return $permissions;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('uid', 'perms_userid', 'perms_groupid', 'perms_user', 'perms_group', 'perms_everybody')
            ->from('pages')
            ->where($queryBuilder->expr()->in(
                'uid',
                $queryBuilder->createNamedParameter($versionedPageIds, Connection::PARAM_INT_ARRAY)
            ))
            ->executeQuery();
        while ($livePage = $result->fetchAssociative()) {
            $permissions[(int)$livePage['uid']] = PagePermissions::fromRecord($livePage);
        }
        return $permissions;
    }
}
