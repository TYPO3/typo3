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

namespace TYPO3\CMS\Core\Utility;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\Page\BrokenRootLineException;
use TYPO3\CMS\Core\Exception\Page\CircularRootLineException;
use TYPO3\CMS\Core\Exception\Page\MountPointsDisabledException;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Exception\Page\PagePropertyRelationNotFoundException;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * A utility resolving and Caching the Rootline generation
 */
class RootlineUtility
{
    /** @internal */
    public const RUNTIME_CACHE_TAG = 'rootline-utility';

    protected int $pageUid;

    protected string $mountPointParameter;

    /** @var int[] */
    protected array $parsedMountPointParameters = [];

    protected int $languageUid = 0;

    protected int $workspaceUid = 0;

    protected FrontendInterface $cache;
    protected FrontendInterface $runtimeCache;

    protected PageRepository $pageRepository;
    protected Context $context;

    protected string $cacheIdentifier;

    /**
     * @throws MountPointsDisabledException
     */
    public function __construct(int $uid, string $mountPointParameter = '', ?Context $context = null)
    {
        $this->mountPointParameter = $this->sanitizeMountPointParameter($mountPointParameter);
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
        $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class, $this->context);
        $this->languageUid = $this->context->getPropertyFromAspect('language', 'id', 0);
        $this->workspaceUid = (int)$this->context->getPropertyFromAspect('workspace', 'id', 0);
        if ($this->mountPointParameter !== '') {
            if (!($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] ?? false)) {
                throw new MountPointsDisabledException('Mount-Point Pages are disabled for this installation. Cannot resolve a Rootline for a page with Mount-Points', 1343462896);
            }
            $this->parseMountPointParameter();
        }
        $this->pageUid = $this->resolvePageId($uid);
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('rootline');
        $this->runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $this->cacheIdentifier = $this->getCacheIdentifier();
    }

    /**
     * Returns the actual rootline without the tree root (uid=0), including the page with $this->pageUid
     */
    public function get(): array
    {
        if ($this->pageUid === 0) {
            // pageUid 0 has no root line, return empty array right away
            return [];
        }
        if (!$this->runtimeCache->has('rootline-localcache-' . $this->cacheIdentifier)) {
            $entry = $this->cache->get($this->cacheIdentifier);
            if (!$entry) {
                $this->generateRootlineCache();
            } else {
                $this->runtimeCache->set('rootline-localcache-' . $this->cacheIdentifier, $entry, [self::RUNTIME_CACHE_TAG]);
                $depth = count($entry);
                // Populate the root-lines for parent pages as well
                // since they are part of the current root-line
                while ($depth > 1) {
                    --$depth;
                    $parentCacheIdentifier = $this->getCacheIdentifier($entry[$depth - 1]['uid']);
                    // Abort if the root-line of the parent page is
                    // already in the local cache data
                    if ($this->runtimeCache->has('rootline-localcache-' . $parentCacheIdentifier)) {
                        break;
                    }
                    // Behaves similar to array_shift(), but preserves
                    // the array keys - which contain the page ids here
                    $entry = array_slice($entry, 1, null, true);
                    $this->runtimeCache->set('rootline-localcache-' . $parentCacheIdentifier, $entry, [self::RUNTIME_CACHE_TAG]);
                }
            }
        }
        return $this->runtimeCache->get('rootline-localcache-' . $this->cacheIdentifier);
    }

    protected function getCacheIdentifier(?int $otherUid = null): string
    {
        $mountPointParameter = $this->mountPointParameter;
        if ($mountPointParameter !== '' && str_contains($mountPointParameter, ',')) {
            $mountPointParameter = str_replace(',', '__', $mountPointParameter);
        }
        return implode('_', [
            $otherUid ?? $this->pageUid,
            $mountPointParameter,
            $this->languageUid,
            $this->workspaceUid,
            $this->context->getAspect('visibility')->includeHiddenContent() ? '1' : '0',
            $this->context->getAspect('visibility')->includeHiddenPages() ? '1' : '0',
        ]);
    }

    /**
     * Queries the database for the page record and returns it.
     *
     * @param int $uid Page id
     * @throws PageNotFoundException
     * @return array<string, string|int|float|null>
     */
    protected function getRecordArray(int $uid): array
    {
        $currentCacheIdentifier = $this->getCacheIdentifier($uid);
        if (!$this->runtimeCache->has('rootline-recordcache-' . $currentCacheIdentifier)) {
            $row = $this->getWorkspaceResolvedPageRecord($uid, $this->workspaceUid);
            if (is_array($row)) {
                $row = $this->enrichPageRecordArray($row, $uid);
                $this->runtimeCache->set('rootline-recordcache-' . $currentCacheIdentifier, $row, [self::RUNTIME_CACHE_TAG]);
            }
        }
        if (!is_array($this->runtimeCache->get('rootline-recordcache-' . $currentCacheIdentifier) ?? false)) {
            throw new PageNotFoundException('Broken rootline. Could not resolve page with uid ' . $uid . '.', 1343464101);
        }
        return $this->runtimeCache->get('rootline-recordcache-' . $currentCacheIdentifier);
    }

    /**
     * Resolve relations as defined in TCA and add them to the provided $pageRecord array.
     *
     * @param int $uid page ID
     * @param array<string, string|int|float|null> $pageRecord Page record (possibly overlaid) to be extended with relations
     * @throws PagePropertyRelationNotFoundException
     * @return array<string, string|int|float|null> $pageRecord with additional relations
     */
    protected function enrichWithRelationFields(int $uid, array $pageRecord): array
    {
        if (!is_array($GLOBALS['TCA']['pages']['columns'] ?? false)) {
            throw new \LogicException(
                'Main ext:core configuration $GLOBALS[\'TCA\'][\'pages\'][\'columns\'] not found.',
                1712572738
            );
        }

        $resultFieldUidArray = [];
        $localRelationColumns = [];
        $foreignRelationColumns = [];
        $foreignRelationColumnTableFieldMapping = [];
        foreach ($GLOBALS['TCA']['pages']['columns'] as $column => $configuration) {
            if ($this->columnHasRelationToResolve($configuration)) {
                $resultFieldUidArray[$column] = [];
                if (!empty($configuration['config']['MM']) && !empty($configuration['config']['MM_opposite_field']) && !empty($configuration['config']['foreign_table'])) {
                    $foreignRelationColumns[] = $column;
                    // This is a solution when multiple fields are on the foreign side in an MM relation to the same local side.
                    // For instance, when there are two category fields in pages.
                    $foreignRelationColumnTableFieldMapping[$configuration['config']['foreign_table']][$configuration['config']['MM_opposite_field']][$column] = 1;
                } else {
                    $localRelationColumns[] = $column;
                }
            }
        }
        if (empty($localRelationColumns) && empty($foreignRelationColumns)) {
            // Early return if there are no relations to resolve at all. Typically, this does not kick in with pages, though.
            return $pageRecord;
        }

        // @todo: There is a general issue with starttime & endtime restrictions: The date aspect of course always changes.
        //        Since the result of this operation is cached into rootline cache, resolving restrictions based on time
        //        may result in invalid caches. We cannot add the time restriction to the cache identifier since that
        //        would not match cache rows constantly. That cache may also kick in when admin panel "simulate time"
        //        is used and no-cache is not forced in admin panel, also leading to invalid results.
        //        This is currently not a *huge* issue, since timed records attached to pages are "relatively" seldom, and
        //        FE instances that use it most likely do something like "clearCacheAtMidnight" anyways.
        //        To ultimately solve the issue, we should either drop the persisted rootline_cache altogether (which should
        //        be do-able when the main rootline query switches to a CTE and does not need the cache anymore), OR we
        //        remove the starttime/endtime handling here again, and let consumers sort out timed records on their own,
        //        which would be a pity.
        // @todo: We could potentially handle ['enablecolumns']['fe_group'] here as well. This however is more work
        //        since we then need two further fields in refindex to track it. Also fe_group is one of those CSV
        //        fields that has "virtual" db connections "-2" and "-1" that don't point to true records. refindex
        //        already behaves funny with those (and has no good test coverage). Also, having (negative) int uids
        //        is a violation for select, those should at least use non-int strings and set "allowNonIdValues".
        //        At best, we'd find some other way for -2 and -1 to get rid of those virtual values entirely. Everything
        //        in this area is probably breaking and needs upgrade wizards.
        // @todo: The queries below may benefit from being prepared and then fired with values, since they are potentially
        //        executed often. This requires storing the prepared query in runtime cache, and requires switching from
        //        named parameters to positional parameters.
        // @todo: Note the entire thing currently handles only non-CSV relations (there must be a TCA foreign_field or MM),
        //        CSV values are not "filtered" and processed regarding hidden, starttime and similar at all. This could be
        //        added later, but needs a careful implementation, for instance because of "allowNonIdValues", and combined
        //        "table_uid" in type=group, and fe_group "virtual" -2 fields.
        // @todo: This operation always returns already workspace uids if they exist. It however does *not* return localization
        //        uids in most cases, this still needs to be done manually, when working with localized pages. Also,
        //        hidden, starttime and endtime of the default language record kicks in, not of the localization overlay
        //        row. We could potentially model this in refindex, by de-normalizing localization overlays in refindex
        //        as well, but this needs work and some decisions since language overlays may need to consider fallback chains.
        $visibilityAspect = $this->context->getAspect('visibility');
        $includeHiddenContent = $visibilityAspect->includeHiddenContent();
        $includeScheduledRecords = $visibilityAspect->includeScheduledRecords();
        $dateTimestamp = (int)$this->context->getAspect('date')->get('timestamp');

        if (!empty($localRelationColumns) && empty($foreignRelationColumns)) {
            // We only have local side relations. Run a simple refindex query. Typically, this does not kick in with pages since it has categories MM.
            // @todo: Add at least one test that manipulates TCA to verify this code branch works.
            $queryBuilder = $this->createQueryBuilder('sys_refindex');
            $result = $queryBuilder->select('tablename', 'field', 'ref_uid')
                ->from('sys_refindex')
                ->orderBy('sorting')
                ->where(
                    $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter('pages')),
                    $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($pageRecord['_ORIG_uid'] ?? $uid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->in('field', $queryBuilder->createNamedParameter($localRelationColumns, Connection::PARAM_STR_ARRAY)),
                    $queryBuilder->expr()->eq('workspace', $this->workspaceUid),
                    $queryBuilder->expr()->neq('ref_t3ver_state', VersionState::DELETE_PLACEHOLDER->value),
                    $includeHiddenContent
                        ? $queryBuilder->expr()->in('ref_hidden', [0, 1]) // Dummy restriction to not break combined index.
                        : $queryBuilder->expr()->eq('ref_hidden', 0),
                    $includeScheduledRecords
                        ? $queryBuilder->expr()->lte('ref_starttime', 2147483647) // Dummy restriction to not break combined index.
                        : $queryBuilder->expr()->lt('ref_starttime', $dateTimestamp),
                    $includeScheduledRecords
                        ? $queryBuilder->expr()->gte('ref_endtime', 0) // Dummy restriction to not break combined index.
                        : $queryBuilder->expr()->gt('ref_endtime', $dateTimestamp)
                )
                ->executeQuery();
            while ($row = $result->fetchAssociative()) {
                $resultFieldUidArray[$row['field']][] = (int)$row['ref_uid'];
            }
            foreach ($resultFieldUidArray as $column => $connectedUids) {
                if (empty($connectedUids)) {
                    $pageRecord[$column] = '';
                } else {
                    $pageRecord[$column] = implode(',', $connectedUids);
                }
            }
            return $pageRecord;
        }

        if (empty($localRelationColumns)) {
            // We only have foreign side relations. Run a simple refindex query. Typically, this does not kick in with pages since it has inline media.
            // @todo: Add at least one test that manipulates TCA to verify this code branch works.
            $queryBuilder = $this->createQueryBuilder('sys_refindex');
            $result = $queryBuilder->select('tablename', 'field', 'recuid', 'ref_field')
                ->from('sys_refindex')
                ->orderBy('ref_sorting')
                ->where(
                    $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter('pages')),
                    // Use workspace-uid if the record is an overlay.
                    $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($pageRecord['_ORIG_uid'] ?? $uid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->in('tablename', $queryBuilder->createNamedParameter(array_keys($foreignRelationColumnTableFieldMapping), Connection::PARAM_STR_ARRAY)),
                    $queryBuilder->expr()->eq('workspace', $queryBuilder->createNamedParameter($this->workspaceUid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->neq('t3ver_state', VersionState::DELETE_PLACEHOLDER->value),
                    $includeHiddenContent
                        ? $queryBuilder->expr()->in('hidden', [0, 1]) // Dummy restriction to not break combined index.
                        : $queryBuilder->expr()->eq('hidden', 0),
                    $includeScheduledRecords
                        ? $queryBuilder->expr()->lte('starttime', 2147483647)
                        : $queryBuilder->expr()->lt('starttime', $dateTimestamp),
                    $includeScheduledRecords
                        ? $queryBuilder->expr()->gte('endtime', 0)
                        : $queryBuilder->expr()->gt('endtime', $dateTimestamp)
                )
                ->executeQuery();
            while ($row = $result->fetchAssociative()) {
                if (isset($foreignRelationColumnTableFieldMapping[$row['tablename']][$row['field']][$row['ref_field']])) {
                    $resultFieldUidArray[$row['ref_field']][] = (int)$row['recuid'];
                }
            }
            foreach ($resultFieldUidArray as $column => $connectedUids) {
                if (empty($connectedUids)) {
                    $pageRecord[$column] = '';
                } else {
                    $pageRecord[$column] = implode(',', $connectedUids);
                }
            }
            return $pageRecord;
        }

        // We need rows from refindex by looking at both local side and foreign side.
        // This is done using a UNION of two distinct queries. This is pretty useful
        // since it saves a round trip and can use distinct indexes per "sub" query.
        // Named arguments however are global for both queries, so we use a dummy query
        // builder to gather them all.
        // Also, postgres and sqlite don't support sorting on single queries with UNION,
        // so we sort the final result set just before imploding to the final CSV per field.
        $namedArgumentsQB = $this->createQueryBuilder('sys_refindex');
        $localQB = $this->createQueryBuilder('sys_refindex');
        $localQB->select('tablename', 'field', 'sorting', 'recuid', 'ref_uid', 'ref_field', 'ref_sorting')
            ->from('sys_refindex')
            ->where(
                $localQB->expr()->eq('tablename', $namedArgumentsQB->createNamedParameter('pages')),
                $localQB->expr()->eq('recuid', $namedArgumentsQB->createNamedParameter($pageRecord['_ORIG_uid'] ?? $uid, Connection::PARAM_INT)),
                $localQB->expr()->in('field', $namedArgumentsQB->createNamedParameter($localRelationColumns, Connection::PARAM_STR_ARRAY)),
                $localQB->expr()->eq('workspace', $this->workspaceUid),
                $localQB->expr()->neq('ref_t3ver_state', VersionState::DELETE_PLACEHOLDER->value),
                $includeHiddenContent
                    ? $localQB->expr()->in('ref_hidden', [0, 1]) // Dummy restriction to not break combined index.
                    : $localQB->expr()->eq('ref_hidden', 0),
                $includeScheduledRecords
                    ? $localQB->expr()->lte('ref_starttime', 2147483647) // Dummy restriction to not break combined index.
                    : $localQB->expr()->lt('ref_starttime', $dateTimestamp),
                $includeScheduledRecords
                    ? $localQB->expr()->gte('ref_endtime', 0) // Dummy restriction to not break combined index.
                    : $localQB->expr()->gt('ref_endtime', $dateTimestamp)
            );
        $foreignQB = $this->createQueryBuilder('sys_refindex');
        $foreignQB->select('tablename', 'field', 'sorting', 'recuid', 'ref_uid', 'ref_field', 'ref_sorting')
            ->from('sys_refindex')
            ->where(
                $foreignQB->expr()->eq('ref_table', $namedArgumentsQB->createNamedParameter('pages')),
                // Use workspace-uid if the record is an overlay.
                $foreignQB->expr()->eq('ref_uid', $namedArgumentsQB->createNamedParameter($pageRecord['_ORIG_uid'] ?? $uid, Connection::PARAM_INT)),
                $foreignQB->expr()->in('tablename', $namedArgumentsQB->createNamedParameter(array_keys($foreignRelationColumnTableFieldMapping), Connection::PARAM_STR_ARRAY)),
                $foreignQB->expr()->eq('workspace', $namedArgumentsQB->createNamedParameter($this->workspaceUid, Connection::PARAM_INT)),
                $foreignQB->expr()->neq('t3ver_state', VersionState::DELETE_PLACEHOLDER->value),
                $includeHiddenContent
                    ? $foreignQB->expr()->in('hidden', [0, 1]) // Dummy restriction to not break combined index.
                    : $foreignQB->expr()->eq('hidden', 0),
                $includeScheduledRecords
                    ? $foreignQB->expr()->lte('starttime', 2147483647)
                    : $foreignQB->expr()->lt('starttime', $dateTimestamp),
                $includeScheduledRecords
                    ? $foreignQB->expr()->gte('endtime', 0)
                    : $foreignQB->expr()->gt('endtime', $dateTimestamp)
            );
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_refindex');
        $result = $connection->executeQuery(
            $localQB->getSQL() . ' UNION ALL ' . $foreignQB->getSQL(),
            $namedArgumentsQB->getParameters(),
            $namedArgumentsQB->getParameterTypes()
        );
        while ($row = $result->fetchAssociative()) {
            if ($row['tablename'] === 'pages') {
                $resultFieldUidArray[$row['field']][(int)$row['sorting']] = (int)$row['ref_uid'];
            } elseif (isset($foreignRelationColumnTableFieldMapping[$row['tablename']][$row['field']][$row['ref_field']])) {
                $resultFieldUidArray[$row['ref_field']][(int)$row['ref_sorting']] = (int)$row['recuid'];
            }
        }
        foreach ($resultFieldUidArray as $column => $connectedUids) {
            if (empty($connectedUids)) {
                $pageRecord[$column] = '';
            } else {
                ksort($connectedUids, SORT_NUMERIC);
                $pageRecord[$column] = implode(',', $connectedUids);
            }
        }
        return $pageRecord;
    }

    /**
     * Checks whether the TCA Configuration array of a column
     * describes a relation which is not stored as CSV in the record
     *
     * @param array $configuration TCA configuration to check
     * @return bool TRUE, if it describes a non-CSV relation
     */
    protected function columnHasRelationToResolve(array $configuration): bool
    {
        $configuration = $configuration['config'] ?? [];
        if (!empty($configuration['MM']) && !empty($configuration['type']) && in_array($configuration['type'], ['select', 'inline', 'group'])) {
            return true;
        }
        if (!empty($configuration['foreign_field']) && !empty($configuration['type']) && in_array($configuration['type'], ['inline', 'file'])) {
            return true;
        }
        if (($configuration['type'] ?? '') === 'category' && ($configuration['relationship'] ?? '') === 'manyToMany') {
            return true;
        }
        return false;
    }

    /**
     * Actual function to generate the rootline and cache it
     *
     * @throws CircularRootLineException
     */
    protected function generateRootlineCache(): void
    {
        $page = $this->getRecordArray($this->pageUid);
        // If the current page is a mounted (according to the MP parameter) handle the mount-point
        if ($this->isMountedPage()) {
            $mountPoint = $this->getRecordArray($this->parsedMountPointParameters[$this->pageUid]);
            $page = $this->processMountedPage($page, $mountPoint);
            $parentUid = $mountPoint['pid'];
            // Anyhow after reaching the mount-point, we have to go up that rootline
            unset($this->parsedMountPointParameters[$this->pageUid]);
        } else {
            $parentUid = $page['pid'];
        }
        $cacheTags = ['pageId_' . $page['uid']];
        if ($parentUid > 0) {
            // Get rootline of (and including) parent page
            $mountPointParameter = !empty($this->parsedMountPointParameters) ? $this->mountPointParameter : '';
            $rootlineUtility = GeneralUtility::makeInstance(self::class, $parentUid, $mountPointParameter, $this->context);
            $rootline = $rootlineUtility->get();
            // retrieve cache tags of parent rootline
            foreach ($rootline as $entry) {
                $cacheTags[] = 'pageId_' . $entry['uid'];
                if ($entry['uid'] == $this->pageUid) {
                    // @todo: Bug. This detection is broken since it happens *after* the child ->get() call, and thus
                    //        triggers infinite recursion already. To fix this, the child needs to know the list of
                    //        resolved children to except on duplicate *before* going up itself. Cover this case with
                    //        a functional test when fixing.
                    throw new CircularRootLineException(
                        'Circular connection in rootline for page with uid ' . $this->pageUid . ' found. Check your mountpoint configuration.',
                        1343464103
                    );
                }
            }
        } else {
            $rootline = [];
        }
        $rootline[] = $page;
        krsort($rootline);
        $this->cache->set($this->cacheIdentifier, $rootline, $cacheTags);
        $this->runtimeCache->set('rootline-localcache-' . $this->cacheIdentifier, $rootline, [self::RUNTIME_CACHE_TAG]);
    }

    /**
     * Checks whether the current Page is a Mounted Page
     * (according to the MP-URL-Parameter)
     */
    protected function isMountedPage(): bool
    {
        return array_key_exists($this->pageUid, $this->parsedMountPointParameters);
    }

    /**
     * Enhances with mount point information or replaces the node if needed
     *
     * @param array<string, string|int|float|null> $mountedPageData page record array of mounted page
     * @param array<string, string|int|float|null> $mountPointPageData page record array of mount point page
     * @throws BrokenRootLineException
     * @return array<string, string|int|float|null>
     */
    protected function processMountedPage(array $mountedPageData, array $mountPointPageData): array
    {
        $mountPid = $mountPointPageData['mount_pid'] ?? null;
        $uid = $mountedPageData['uid'] ?? null;
        if ((int)$mountPid !== (int)$uid) {
            throw new BrokenRootLineException('Broken rootline. Mountpoint parameter does not match the actual rootline. mount_pid (' . $mountPid . ') does not match page uid (' . $uid . ').', 1343464100);
        }
        // Current page replaces the original mount-page
        $mountUid = $mountPointPageData['uid'] ?? null;
        if (!empty($mountPointPageData['mount_pid_ol'])) {
            $mountedPageData['_MOUNT_OL'] = true;
            $mountedPageData['_MOUNT_PAGE'] = [
                'uid' => $mountUid,
                'pid' => $mountPointPageData['pid'] ?? null,
                'title' => $mountPointPageData['title'] ?? null,
            ];
        } else {
            // The mount-page is not replaced, the mount-page itself has to be used
            $mountedPageData = $mountPointPageData;
        }
        $mountedPageData['_MOUNTED_FROM'] = $this->pageUid;
        $mountedPageData['_MP_PARAM'] = $this->pageUid . '-' . $mountUid;
        return $mountedPageData;
    }

    /**
     * Sanitize the MountPoint Parameter
     * Splits the MP-Param via "," and removes mountpoints
     * that don't have the format \d+-\d+
     */
    protected function sanitizeMountPointParameter(string $mountPointParameter): string
    {
        $mountPointParameter = trim($mountPointParameter);
        if ($mountPointParameter === '') {
            return '';
        }
        $mountPoints = GeneralUtility::trimExplode(',', $mountPointParameter);
        foreach ($mountPoints as $key => $mP) {
            // If MP has incorrect format, discard it
            if (!preg_match('/^\d+-\d+$/', $mP)) {
                unset($mountPoints[$key]);
            }
        }
        return implode(',', $mountPoints);
    }

    /**
     * Parse the MountPoint Parameters
     * Splits the MP-Param via "," for several nested mountpoints
     * and afterwords registers the mountpoint configurations
     */
    protected function parseMountPointParameter(): void
    {
        $mountPoints = GeneralUtility::trimExplode(',', $this->mountPointParameter);
        foreach ($mountPoints as $mP) {
            [$mountedPageUid, $mountPageUid] = GeneralUtility::intExplode('-', $mP);
            $this->parsedMountPointParameters[$mountedPageUid] = $mountPageUid;
        }
    }

    /**
     * Fetches the UID of the page.
     *
     * If the page was moved in a workspace, actually returns the UID
     * of the moved version in the workspace.
     */
    protected function resolvePageId(int $pageId): int
    {
        if ($pageId === 0 || $this->workspaceUid === 0) {
            return $pageId;
        }

        $page = $this->resolvePageRecord($pageId);
        if (!isset($page['t3ver_state']) || VersionState::tryFrom($page['t3ver_state']) !== VersionState::MOVE_POINTER) {
            return $pageId;
        }

        $movePointerId = $this->resolveMovePointerId((int)$page['t3ver_oid']);
        return $movePointerId ?: $pageId;
    }

    protected function resolvePageRecord(int $pageId): ?array
    {
        $queryBuilder = $this->createQueryBuilder('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->from('pages')
            ->select('uid', 't3ver_oid', 't3ver_state')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery();

        $record = $statement->fetchAssociative();
        return $record ?: null;
    }

    /**
     * Fetched the UID of the versioned record if the live record has been moved in a workspace.
     */
    protected function resolveMovePointerId(int $liveId): ?int
    {
        $queryBuilder = $this->createQueryBuilder('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->from('pages')
            ->select('uid')
            ->setMaxResults(1)
            ->where(
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($this->workspaceUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_state',
                    $queryBuilder->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_oid',
                    $queryBuilder->createNamedParameter($liveId, Connection::PARAM_INT)
                )
            )
            ->executeQuery();

        $movePointerId = $statement->fetchOne();
        return $movePointerId ? (int)$movePointerId : null;
    }

    /**
     * {@see self::getRecordArray()} used {@see PageRepository::versionOL()} to compose workspace overlayed records in
     * the based, bypassing access checks (aka enableFields) resulting in multiple queries. This constellation of method
     * and database query chains can be condensed down to a single database query, which this method uses to retrieve
     * the equal workspace overlay database page record in one and thus reducing overall database query count.
     */
    protected function getWorkspaceResolvedPageRecord(int $pageId, int $workspaceId): ?array
    {
        $createForLiveWorkspace = ($workspaceId <= 0);
        $queryBuilder = $this->createQueryBuilder('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $fields = $this->getPagesFields();
        if ($createForLiveWorkspace) {
            // For live workspace only we can even more simplify this
            $queryBuilder
                ->select(...array_values($fields))
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
                    $queryBuilder->expr()->in('t3ver_wsid', $queryBuilder->createNamedParameter([0, $workspaceId], Connection::PARAM_INT_ARRAY)),
                )
                ->setMaxResults(1);
            return $queryBuilder->executeQuery()->fetchAssociative() ?: null;
        }
        $prefixedFields = array_filter($fields, static fn($value) => $value !== 'uid');
        array_walk(
            $prefixedFields,
            static function (string &$value, int|string $key, QueryBuilder $queryBuilder) {
                $value = $queryBuilder->quoteIdentifier(sprintf('%s.%s', 'workspace_resolved', $value));
            },
            $queryBuilder,
        );
        $queryBuilder
            ->selectLiteral(
                $queryBuilder->quoteIdentifier('live.uid'),
                ...array_values($prefixedFields),
                ...array_values([
                    //------------------------------------------------------------------------------------------------------
                    // For move pointers, store the actual live PID in the _ORIG_pid
                    // The only place where PID is actually different in a workspace
                    $queryBuilder->expr()->if(
                        $queryBuilder->expr()->and(
                            $queryBuilder->expr()->isNotNull('workspace.t3ver_state'),
                            $queryBuilder->expr()->eq('workspace.t3ver_state', $queryBuilder->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT)),
                        ),
                        $queryBuilder->quoteIdentifier('live.pid'),
                        'null',
                        '_ORIG_pid'
                    ),
                    // For versions of single elements or page+content, preserve online UID
                    // (this will produce true "overlay" of element _content_, not any references)
                    // For new versions there is no online counterpart
                    $queryBuilder->expr()->if(
                        $queryBuilder->expr()->and(
                            $queryBuilder->expr()->isNotNull('workspace.t3ver_state'),
                            $queryBuilder->expr()->neq('workspace.t3ver_state', $queryBuilder->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT)),
                        ),
                        $queryBuilder->quoteIdentifier('workspace.uid'),
                        'null',
                        '_ORIG_uid',
                    ),
                ])
            )
            ->from('pages', 'source')
            ->innerJoin(
                'source',
                'pages',
                'live',
                $queryBuilder->expr()->eq(
                    'live.uid',
                    $queryBuilder->expr()->if(
                        (string)$queryBuilder->expr()->and(
                            $queryBuilder->expr()->gt('source.t3ver_oid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                            $queryBuilder->expr()->eq('source.t3ver_state', $queryBuilder->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT)),
                        ),
                        $queryBuilder->quoteIdentifier('source.t3ver_oid'),
                        $queryBuilder->quoteIdentifier('source.uid'),
                    )
                ),
            )
            ->leftJoin(
                'live',
                'pages',
                'workspace',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'workspace.t3ver_wsid',
                        $queryBuilder->createNamedParameter($workspaceId, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->or(
                        // t3ver_state=1 does not contain a t3ver_oid, and returns itself
                        $queryBuilder->expr()->and(
                            $queryBuilder->expr()->eq(
                                'workspace.uid',
                                $queryBuilder->quoteIdentifier('live.uid'),
                            ),
                            $queryBuilder->expr()->eq(
                                'workspace.t3ver_state',
                                $queryBuilder->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                            ),
                        ),
                        $queryBuilder->expr()->eq(
                            'workspace.t3ver_oid',
                            $queryBuilder->quoteIdentifier('live.uid'),
                        )
                    )
                ),
            )
            ->innerJoin(
                'workspace',
                'pages',
                'workspace_resolved',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'workspace_resolved.uid',
                        $queryBuilder->expr()->if(
                            $queryBuilder->expr()->isNotNull('workspace.uid'),
                            $queryBuilder->quoteIdentifier('workspace.uid'),
                            $queryBuilder->quoteIdentifier('live.uid'),
                        ),
                    ),
                ),
            )
            ->where(
                $queryBuilder->expr()->eq('source.uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->in('source.t3ver_wsid', $queryBuilder->createNamedParameter([0, $workspaceId], Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->or(
                    // retrieve live workspace if no overlays exists
                    $queryBuilder->expr()->isNull('workspace.uid'),
                    // discard(omit) record if it turned out to be deleted in workspace
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->isNotNull('workspace.uid'),
                        $queryBuilder->expr()->neq(
                            'workspace.t3ver_state',
                            $queryBuilder->createNamedParameter(VersionState::DELETE_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                ),
            )
            ->setMaxResults(1);
        $row = $queryBuilder->executeQuery()->fetchAssociative() ?: null;
        return $this->cleanWorkspaceResolvedPageRecord($row);
    }

    protected function cleanWorkspaceResolvedPageRecord(?array $row = null): ?array
    {
        if ($row === null) {
            return $row;
        }
        $removeNullableFields = [
            '_ORIG_uid',
            '_ORIG_pid',
        ];
        foreach ($removeNullableFields as $removeNullableField) {
            if (array_key_exists($removeNullableField, $row) && $row[$removeNullableField] === null) {
                unset($row[$removeNullableField]);
            }
        }
        return $row;
    }

    protected function enrichPageRecordArray(array $row, int $pageId): array
    {
        $row = $this->pageRepository->getLanguageOverlay('pages', $row, $this->context->getAspect('language'));
        $row = $this->enrichWithRelationFields($row['_LOCALIZED_UID'] ?? $pageId, $row);
        return $row;
    }

    protected function getPagesFields(): array
    {
        $fieldNames = [];
        $columns = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->getSchemaInformation()->introspectTable('pages')
            ->getColumns();
        foreach ($columns as $column) {
            $fieldNames[] = $column->getName();
        }
        return $fieldNames;
    }

    protected function createQueryBuilder(string $tableName): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
    }
}
