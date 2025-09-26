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

use Doctrine\DBAL\Exception as DoctrineException;
use Doctrine\DBAL\Platforms\TrimMode;
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
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * A utility resolving and Caching the Rootline generation
 */
class RootlineUtility
{
    // Note that having a nesting depth of 100 is quite high, but defined to be more on a "safe" side here. Main goal
    // is to mitigate unforeseen recursion which are not covered by the ancestor guard (checking page uid in path).
    private const MAX_CTE_TRAVERSAL_LEVELS = 100;

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
     *
     * @throws BrokenRootLineException
     * @throws CircularRootLineException
     * @throws PageNotFoundException
     * @throws DoctrineException
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
        $resultFieldUidArray = [];
        $localRelationColumns = [];
        $foreignRelationColumns = [];
        $foreignRelationColumnTableFieldMapping = [];
        $schema = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('pages');
        foreach ($schema->getFields() as $column => $fieldType) {
            $configuration = $fieldType->getConfiguration();
            if ($this->columnHasRelationToResolve($configuration)) {
                $resultFieldUidArray[$column] = [];
                if (!empty($configuration['MM']) && !empty($configuration['MM_opposite_field']) && !empty($configuration['foreign_table'])) {
                    $foreignRelationColumns[] = $column;
                    // This is a solution when multiple fields are on the foreign side in an MM relation to the same local side.
                    // For instance, when there are two category fields in pages.
                    $foreignRelationColumnTableFieldMapping[$configuration['foreign_table']][$configuration['MM_opposite_field']][$column] = 1;
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
     * @throws BrokenRootLineException
     * @throws CircularRootLineException
     * @throws PageNotFoundException
     * @throws DoctrineException
     */
    protected function generateRootlineCache(): void
    {
        $pageId = $this->pageUid;
        $page = $this->getRecordArray($pageId);
        $parentPageId = $page['pid'];
        $workspaceId = $this->workspaceUid;
        if ($this->isMountedPage($pageId)) {
            // If the current page is a mounted (according to the MP parameter) handle the mount-point
            $page = $this->getRecordArray($pageId);
            $mountPoint = $this->getRecordArray($this->parsedMountPointParameters[$pageId]);
            $page = $this->processMountedPage($page, $mountPoint);
            $parentPageId = $mountPoint['pid'];
            // Anyhow after reaching the mount-point, we have to go up that rootline
            unset($this->parsedMountPointParameters[$this->pageUid]);
        }
        $rootline = $this->getRootlineFromRuntimeCache($parentPageId);
        if (!is_array($rootline)) {
            $rootline = $this->getRootlineRecords($parentPageId, $workspaceId);
        }
        if (!is_array($rootline)) {
            $rootline = [];
        }
        $rootline[] = $page;
        $firstEntry = reset($rootline);
        // ensure valid rootline down to virtual tree root
        if (is_array($firstEntry) && $firstEntry['pid'] !== 0) {
            throw new PageNotFoundException('Broken rootline. Could not resolve full rootline for uid ' . $pageId . '.', 1721913589);
        }
        $cacheTags = [];
        foreach ($rootline as $entry) {
            $cacheTags[] = 'pageId_' . $entry['uid'];
        }
        krsort($rootline);
        $this->cache->set($this->cacheIdentifier, $rootline, $cacheTags);
        $this->runtimeCache->set('rootline-localcache-' . $this->cacheIdentifier, $rootline, [self::RUNTIME_CACHE_TAG]);

        // Reduce rootline page by page and set to runtime cache to eliminate the need to fetch rootline for a parent
        // page as a performance optimization when a children already generated the rootline.
        while ($rootline !== []) {
            // Behaves similar to array_shift(), but preserves the array keys.
            $rootline = array_slice($rootline, 1, null, true);
            if ($rootline !== []) {
                $cacheIdentifier = $this->getCacheIdentifier($rootline[array_key_first($rootline)]['uid']);
                $this->runtimeCache->set('rootline-localcache-' . $cacheIdentifier, $rootline, [self::RUNTIME_CACHE_TAG]);
            }
        }
    }

    /**
     * Checks whether the current Page is a Mounted Page
     * (according to the MP-URL-Parameter)
     */
    protected function isMountedPage(int $pageId): bool
    {
        return array_key_exists($pageId, $this->parsedMountPointParameters);
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
     * Get enriched RootLine page records.
     *
     * This method uses a recursive common-table-expression (CTE) doing the first workspace overlay handling withing the
     * RootLine traversal. Language overlay and record enrichment (references data) are applied on retrieved records.
     *
     * In case a received record is a mounted page, a new instance of `RootlineUtility` is created to retrieve the
     * mounted page rootline and result spliced together.
     *
     * In case only live-workspace rootline is required, for example in normal frontend visitors without backend login
     * and selected workspaces, the created CTE is simplified avoiding irrelevant joins and further improve performance
     * in that case.
     *
     * Note that the created recursive CTE contains two guards against cycling rootline issues:
     *
     * Guard 1 - ancestor path guard:
     * ------------------------------
     *
     * During the recursing a uid path is created (`__CTE_PATH__`) and in case that a page is already contained in the
     * parent record `__CTE_PATH__` the flag field `__CTE_IS_CYCLE__` is set to 1, otherwise it is 0.
     *
     * If the parent record `__CTE_IS_CYLCE__` is `1` no records are retrieved which retrieves a duplicate page record
     * except of this flag. During the record retrieval {@see CircularRootLineException} is thrown to abort and state
     * this crucial data corruption.
     *
     * This guard is designed to abort cycling recursion on the database side as early as possible while still transport
     * the cycling issue information to this method.
     *
     * Guard 2 - max recursion level guard:
     * ------------------------------------
     *
     * During the recursive CTE handling recursion level info is created (`__CTE_LEVEL__`) and used as a hard recursion
     * limit fence. That means, if the level reaches {@see self::MAX_CTE_TRAVERSAL_LEVELS} no more page records are
     * received. In the case that neither GUARD 1, nor PID=0 abort criteria is reached and max level hit a meaningful
     * {@see BrokenRootLineException} exception is thrown.
     *
     * Note that further improvements are possible, for example adding language overlay handling directly to the CTE
     * when strategy how to deal with the three dispatched PSR-14 events in PageRepository has been made up.
     *
     * @todo As already mentioned above, mountpoint pages are resolved by calling a new `RootlineUtility` instance
     *       which prevents the detection of circular mountpoint configuration like in the prior implementation. A
     *       way to add mountpoint page replacements within the CTE needs to be evaluated and implemented to make a
     *       circular mountpoint configuration finally detectable. At least first cycling rootline revealing database
     *       data corruption are now included.
     *
     * @throws BrokenRootLineException
     * @throws CircularRootLineException
     * @throws DoctrineException
     */
    protected function getRootlineRecords(int $pageId, int $workspaceId): array
    {
        if ($pageId === 0) {
            return [];
        }
        $cte = $this->createQueryBuilder('pages');
        $cte->getRestrictions()->removeAll();
        $expr = $cte->expr();
        $pagesFields = $this->getPagesFields();
        $resolvedPagesFields = array_filter($pagesFields, static fn($value) => $value !== 'uid');
        array_walk(
            $resolvedPagesFields,
            static function (string &$value, int|string $key, string $prefixAlias) {
                $value = sprintf('%s.%s', $prefixAlias, $value);
            },
            'finalpages',
        );
        $cte
            ->typo3_withRecursive(
                'cte',
                // Unique rows is omitted to avoid superfluous distinct row determination by the database query executor
                // due to having level based data in the traversal part they would be distinct anyway. Duplicates are
                // sorted out by the implemented ancestor guard - see self::createTraversalQueryBuilder().
                false,
                $this->createInitialQueryBuilder($cte, $pageId, $workspaceId),
                $this->createTraversalQueryBuilder($cte, $workspaceId),
                [
                    // data fields
                    $cte->quoteIdentifier('uid'),
                    $cte->quoteIdentifier('pid'),
                    // workspace handling values
                    $cte->quoteIdentifier('_ORIG_pid'),
                    $cte->quoteIdentifier('_ORIG_uid'),
                    // recursive handling fields
                    $cte->quoteIdentifier('__CTE_JOIN_UID__'),
                    $cte->quoteIdentifier('__CTE_LEVEL__'),
                    $cte->quoteIdentifier('__CTE_PATH__'),
                    $cte->quoteIdentifier('__CTE_IS_CYCLE__'),
                ],
            )
            ->select(...array_values([
                'cte.uid',
                ...array_values($resolvedPagesFields),
                'cte._ORIG_uid',
                'cte._ORIG_pid',
                // Cycle detection guard implemented manually due to the fact that CTE cycle is not implemented by
                // all supported Database vendors at all or not following the SQL standard. The guard stops cycling
                // rootline early to avoid use-less cycle retrieval until __CTE_LEVEL__ reaches maximal hard level.
                // Note that these values are removed from result rows by `self::cleanWorkspaceResolvedPageRecord()`
                'cte.__CTE_PATH__',
                'cte.__CTE_IS_CYCLE__',
                'cte.__CTE_LEVEL__',
            ]))
            ->from('cte')
            // It's important to traverse determined rootline records in the correct order, which means from the page
            // record down to the rootpage. The recursive CTE builds up the CTE level starting from current record as
            // level 1 and incrementing the level for each parent record, which means that we need to order by the
            // level in ascending order (1, 2, 3, 4).
            ->orderBy('cte.__CTE_LEVEL__', 'ASC')
            ->addOrderBy('cte.uid', 'ASC')
            ->innerJoin(
                'cte',
                'pages',
                'finalpages',
                $expr->eq('finalpages.uid', $cte->quoteIdentifier('cte.__CTE_JOIN_UID__'))
            );
        $records = [];
        $result = $cte->executeQuery();
        while ($record = $result->fetchAssociative()) {
            $cyclingDetected = (bool)($record['__CTE_IS_CYCLE__'] ?? false);
            $recordLevel = (int)($record['__CTE_LEVEL__'] ?? 0);
            $recordId = (int)$record['uid'];
            $recordPid = (int)$record['pid'];
            $recordCacheIdentifier = $this->getCacheIdentifier($recordId);
            $record = $this->cleanWorkspaceResolvedPageRecord($record);
            if ($cyclingDetected) {
                // Cycling page records found by CTE path guard.
                // @todo CTE does not handle mountpoint page resolving yet and calling RootlineUtility in recursive
                //       manner below not detecting cycling mountpoint configurations yet. CTE **must* be improved to
                //       handle mountpoint replacements directly and ensure cycling detection finally works - which has
                //       never been the case.
                throw new CircularRootLineException(
                    'Circular connection in rootline for page with uid ' . $this->pageUid . ' found. '
                    . 'Check your mountpoint configuration and page data with pid value pointing to a sub page.',
                    1343464103
                );
            }
            // Throw a concrete BrokenRootLineException with explaining message in case maximal CTE traversal limit has
            // been reached without ending on PID 0 - the virtual tree root node.
            // @todo Find a way to test this case which is not that easy because of the MAX_CTE_TRAVERSAL_LEVEL.
            if ($recordLevel >= self::MAX_CTE_TRAVERSAL_LEVELS && $recordPid !== 0) {
                throw new BrokenRootLineException(
                    sprintf(
                        'Broken rootline. Could not resolve full rootline for uid %s. '
                        . 'Reached max traversal level %s without ending on pid 0.',
                        $pageId,
                        self::MAX_CTE_TRAVERSAL_LEVELS,
                    ),
                    1722118090,
                );
            }
            if ($this->isMountedPage($recordId)) {
                // free result, because we will not iterator further through it and instead invoke RootlineUtility.
                $result->free();
                // @todo Find a way to implement mountpoint resolving directly in the CTE to remove calling and splicing
                //       recursive RootlineUtility call results and handle everything in one database query.
                // Get rootline of (and including) parent page
                $mountPointParameter = !empty($this->parsedMountPointParameters) ? $this->mountPointParameter : '';
                $rootlineUtility = GeneralUtility::makeInstance(self::class, $recordId, $mountPointParameter, $this->context);
                $rootline = $rootlineUtility->get();
                foreach ($rootline as $rootlineRecord) {
                    $records[] = $rootlineRecord;
                }
                break;
            }
            if (!$this->runtimeCache->has('rootline-recordcache-' . $recordCacheIdentifier)) {
                $record = $this->enrichPageRecordArray($record, $recordId);
                $this->runtimeCache->set('rootline-recordcache-' . $recordCacheIdentifier, $record, [self::RUNTIME_CACHE_TAG]);
            }
            $record = $this->runtimeCache->get('rootline-recordcache-' . $recordCacheIdentifier);
            if (!is_array($record)) {
                throw new PageNotFoundException('Broken rootline. Could not resolve page with uid ' . $recordId . '.', 1721982337);
            }
            $records[] = $record;

        }
        // `$records` are build having the current record as first item. We need to revers it here to ensure correct
        // rootline completion and indexing within `RootlineUtility::generateRootlineCache()`, which expects to have
        // a record with `pid=0` as first item in the returned records. Note, that keys are not preserved on purpose.
        return array_reverse($records);
    }

    /**
     * Creates the QueryBuilder for the recursive CTE initial part within {@see self::getRootlineRecords()}.
     *
     * Not to be used standalone. {@see self::getRootlineRecords()} method docblock for overall CTE information.
     */
    protected function createInitialQueryBuilder(QueryBuilder $cte, int $pageId, int $workspaceId): QueryBuilder
    {
        $expr = $cte->expr();
        $initial = $this->createQueryBuilder('pages');
        $initial->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        if ($workspaceId === 0) {
            // Return simplified initial expression for live workspace resolving only.
            return $initial
                ->selectLiteral(...array_values([
                    // data fields
                    $cte->quoteIdentifier('uid'),
                    $cte->quoteIdentifier('pid'),
                    // adding fake columns to be compatible with workspace aware columns
                    $expr->as('null', '_ORIG_pid'),
                    $expr->as('null', '_ORIG_uid'),
                    // recursive handling fields
                    $expr->as($cte->quoteIdentifier('uid'), '__CTE_JOIN_UID__'),
                    $expr->castInt('1', '__CTE_LEVEL__'),
                    // Cycle detection guard implemented manually due to the fact that CTE cycle is not implemented by
                    // all supported Database vendors at all or not following the SQL standard. The guard stops cycling
                    // rootline early to avoid use-less cycle retrieval until __CTE_LEVEL__ reaches maximal hard level.
                    $expr->castText($cte->quoteIdentifier('uid'), '__CTE_PATH__'),
                    // Because of Postgres we need to have this cte colum boolean type and thus needing a comparison here
                    $expr->castInt('0 <> 0', '__CTE_IS_CYCLE__'),
                ]))
                ->from('pages')
                ->where(...array_values([
                    $expr->eq('uid', $cte->createNamedParameter($pageId, Connection::PARAM_INT)),
                    // only select live workspace
                    $expr->eq('t3ver_wsid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                ]));
        }

        $initial
            ->selectLiteral(...array_values([
                // data fields
                $cte->quoteIdentifier('live.uid'),
                $cte->quoteIdentifier('workspace_resolved.pid'),
                // workspace handling
                // For move pointers, store the actual live PID in the _ORIG_pid
                // The only place where PID is actually different in a workspace
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('workspace.t3ver_state'),
                        $expr->eq(
                            'workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('live.pid'),
                    'null',
                    '_ORIG_pid'
                ),
                // For versions of single elements or page+content, preserve online UID
                // (this will produce true "overlay" of element _content_, not any references)
                // For new versions there is no online counterpart
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('workspace.t3ver_state'),
                        $expr->neq(
                            'workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('workspace.uid'),
                    'null',
                    '_ORIG_uid',
                ),
                // recursive handling fields
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('workspace.t3ver_state'),
                        $expr->neq(
                            'workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('workspace_resolved.uid'),
                    $cte->quoteIdentifier('live.uid'),
                    '__CTE_JOIN_UID__',
                ),
                $expr->castInt('1', '__CTE_LEVEL__'),
                // Cycle detection guard implemented manually due to the fact that CTE cycle is not implemented by
                // all supported Database vendors at all or not following the SQL standard. The guard stops cycling
                // rootline early to avoid use-less cycle retrieval until __CTE_LEVEL__ reaches maximal hard level.
                $expr->castText($cte->quoteIdentifier('live.uid'), '__CTE_PATH__'),
                // Because of Postgres we need to have this cte colum boolean type and thus needing a comparison here
                $expr->castInt('0 <> 0', '__CTE_IS_CYCLE__'),
            ]))
            ->from('pages', 'source')
            ->innerJoin(
                'source',
                'pages',
                'live',
                $expr->eq(
                    'live.uid',
                    $expr->if(
                        $expr->and(
                            $expr->gt('source.t3ver_oid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                            $expr->eq('source.t3ver_state', $cte->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT)),
                        ),
                        $cte->quoteIdentifier('source.t3ver_oid'),
                        $cte->quoteIdentifier('source.uid'),
                    )
                ),
            )
            ->leftJoin(
                'live',
                'pages',
                'workspace',
                $expr->and(
                    $expr->eq(
                        'workspace.t3ver_wsid',
                        $cte->createNamedParameter($workspaceId, Connection::PARAM_INT)
                    ),
                    $expr->or(
                        // t3ver_state=1 does not contain a t3ver_oid, and returns itself
                        $expr->and(
                            $expr->eq(
                                'workspace.uid',
                                $cte->quoteIdentifier('live.uid'),
                            ),
                            $expr->eq(
                                'workspace.t3ver_state',
                                $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                            ),
                        ),
                        $expr->eq(
                            'workspace.t3ver_oid',
                            $cte->quoteIdentifier('live.uid'),
                        )
                    )
                ),
            )
            ->innerJoin(
                'workspace',
                'pages',
                'workspace_resolved',
                (string)$expr->and(
                    $expr->eq(
                        'workspace_resolved.uid',
                        $expr->if(
                            $expr->isNotNull('workspace.uid'),
                            $cte->quoteIdentifier('workspace.uid'),
                            $cte->quoteIdentifier('live.uid'),
                        ),
                    ),
                ),
            )
            ->where(...array_values([
                $expr->eq('source.uid', $cte->createNamedParameter($pageId, Connection::PARAM_INT)),
                $expr->in('source.t3ver_wsid', $cte->createNamedParameter([0, $workspaceId], Connection::PARAM_INT_ARRAY)),
                $expr->or(
                    // retrieve live workspace if no overlays exists
                    $expr->isNull('workspace.uid'),
                    // discard(omit) record if it turned out to be deleted in workspace
                    $expr->and(
                        $expr->isNotNull('workspace.uid'),
                        $expr->neq(
                            'workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::DELETE_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                ),
            ]));

        return $initial;
    }

    /**
     * Creates the QueryBuilder for the recursive CTE traversal part within {@see self::getRootlineRecords()}.
     *
     * Not to be used standalone. {@see self::getRootlineRecords()} method docblock for overall CTE information.
     */
    protected function createTraversalQueryBuilder(QueryBuilder $cte, int $workspaceId): QueryBuilder
    {
        $expr = $cte->expr();
        $traversal = $this->createQueryBuilder('pages');
        $traversal->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        if ($workspaceId === 0) {
            $traversal
                ->selectLiteral(...array_values([
                    // data fields
                    $cte->quoteIdentifier('p.uid'),
                    $cte->quoteIdentifier('p.pid'),
                    // adding fake columns to be compatible with workspace aware columns
                    $expr->as('null', '_ORIG_pid'),
                    $expr->as('null', '_ORIG_uid'),
                    // recursive handling fields
                    $expr->as($cte->quoteIdentifier('p.uid'), '__CTE_JOIN_UID__'),
                    $expr->castInt(sprintf('(%s + 1)', $expr->castInt($cte->quoteIdentifier('c.__CTE_LEVEL__'))), '__CTE_LEVEL__'),
                    // Cycle detection guard implemented manually due to the fact that CTE cycle is not implemented by
                    // all supported Database vendors at all or not following the SQL standard. The guard stops cycling
                    // rootline early to avoid use-less cycle retrieval until __CTE_LEVEL__ reaches maximal hard level.
                    $expr->castText(
                        $expr->concat(
                            $expr->trim('c.__CTE_PATH__', TrimMode::TRAILING, ' '),
                            $cte->quote(','),
                            $cte->quoteIdentifier('p.uid')
                        ),
                        '__CTE_PATH__'
                    ),
                    $expr->as(
                        sprintf(
                            '%s <> 0',
                            $expr->castInt(sprintf(
                                // Nesting is needed because inSet() creates a comparision expression return a boolean value
                                '(%s)',
                                $expr->inSet(
                                    'c.__CTE_PATH__',
                                    $cte->quoteIdentifier('p.uid'),
                                    true,
                                ),
                            )),
                        ),
                        '__CTE_IS_CYCLE__'
                    ),
                ]))
                ->from('cte', 'c')
                ->innerJoin(
                    'c',
                    'pages',
                    'p',
                    (string)$expr->and(
                        $expr->eq('p.uid', $cte->quoteIdentifier('c.pid')),
                        // only select live workspace
                        $expr->eq('p.t3ver_wsid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                        // ensure that resolve page is not the child page
                        $expr->neq('c.uid', $cte->quoteIdentifier('p.uid')),
                    )
                )
                ->where(...array_values([
                    // If last parent has been detected as start of a recursive cycle, stop here. Note that this is done to
                    // keep the cycle detection value in the result set to allow proper handling later on retrieved rows.
                    $expr->eq('c.__CTE_IS_CYCLE__', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                    // do not try to fetch page with uid 0, which is the virtual tree root point
                    $expr->neq('c.pid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                    // place a maximal traversal level guard against invalid cycling rootlines to mitigate endless recursion
                    $expr->lt('c.__CTE_LEVEL__', $cte->createNamedParameter(self::MAX_CTE_TRAVERSAL_LEVELS, Connection::PARAM_INT)),
                ]));

            return $traversal;
        }

        $traversal
            ->selectLiteral(...array_values([
                // data fields
                $cte->quoteIdentifier('traversal_live.uid'),
                $cte->quoteIdentifier('traversal_workspace_resolved.pid'),
                // workspace handling
                // For move pointers, store the actual live PID in the _ORIG_pid
                // The only place where PID is actually different in a workspace
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('traversal_workspace.t3ver_state'),
                        $expr->eq(
                            'traversal_workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('traversal_live.pid'),
                    'null',
                    '_ORIG_pid'
                ),
                // For versions of single elements or page+content, preserve online UID
                // (this will produce true "overlay" of element _content_, not any references)
                // For new versions there is no online counterpart
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('traversal_workspace.t3ver_state'),
                        $expr->neq(
                            'traversal_workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('traversal_workspace.uid'),
                    'null',
                    '_ORIG_uid',
                ),
                // recursive handling fields
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('traversal_workspace.t3ver_state'),
                        $expr->neq(
                            'traversal_workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('traversal_workspace_resolved.uid'),
                    $cte->quoteIdentifier('traversal_live.uid'),
                    '__CTE_JOIN_UID__',
                ),
                $expr->castInt(sprintf('(%s + 1)', $expr->castInt($cte->quoteIdentifier('traversal_c.__CTE_LEVEL__'))), '__CTE_LEVEL__'),
                // Cycle detection guard implemented manually due to the fact that CTE cycle is not implemented by
                // all supported Database vendors at all or not following the SQL standard. The guard stops cycling
                // rootline early to avoid use-less cycle retrieval until __CTE_LEVEL__ reaches maximal hard level.
                $expr->castText(
                    $expr->concat(
                        $expr->trim('traversal_c.__CTE_PATH__', TrimMode::TRAILING, ' '),
                        $cte->quote(','),
                        $cte->quoteIdentifier('traversal_live.uid')
                    ),
                    '__CTE_PATH__'
                ),
                $expr->as(
                    sprintf(
                        '%s <> 0',
                        $expr->castInt(sprintf(
                            // Nesting is needed because inSet() creates a comparision expression return a boolean value
                            '(%s)',
                            $expr->inSet(
                                'traversal_c.__CTE_PATH__',
                                $cte->quoteIdentifier('traversal_live.uid'),
                                true,
                            )
                        )),
                    ),
                    '__CTE_IS_CYCLE__'
                ),
            ]))
            ->from('cte', 'traversal_c')
            ->innerJoin(
                'traversal_c',
                'pages',
                'traversal_source',
                (string)$expr->and(
                    $expr->eq(
                        'traversal_source.uid',
                        $cte->quoteIdentifier('traversal_c.pid')
                    ),
                    $expr->in('traversal_source.t3ver_wsid', $cte->createNamedParameter([0, $workspaceId], Connection::PARAM_INT_ARRAY)),
                ),
            )
            ->innerJoin(
                'traversal_source',
                'pages',
                'traversal_live',
                $expr->eq(
                    'traversal_live.uid',
                    $expr->if(
                        $expr->and(
                            $expr->gt('traversal_source.t3ver_oid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                            $expr->eq('traversal_source.t3ver_state', $cte->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT)),
                        ),
                        $cte->quoteIdentifier('traversal_source.t3ver_oid'),
                        $cte->quoteIdentifier('traversal_source.uid'),
                    )
                ),
            )
            ->leftJoin(
                'traversal_live',
                'pages',
                'traversal_workspace',
                (string)$expr->and(
                    $expr->eq(
                        'traversal_workspace.t3ver_wsid',
                        $cte->createNamedParameter($workspaceId, Connection::PARAM_INT)
                    ),
                    $expr->or(
                        // t3ver_state=1 does not contain a t3ver_oid, and returns itself
                        $expr->and(
                            $expr->eq(
                                'traversal_workspace.uid',
                                $cte->quoteIdentifier('traversal_live.uid'),
                            ),
                            $expr->eq(
                                'traversal_workspace.t3ver_state',
                                $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                            ),
                        ),
                        $expr->eq(
                            'traversal_workspace.t3ver_oid',
                            $cte->quoteIdentifier('traversal_live.uid'),
                        )
                    )
                ),
            )
            ->innerJoin(
                'traversal_workspace',
                'pages',
                'traversal_workspace_resolved',
                (string)$expr->and(
                    $expr->eq(
                        'traversal_workspace_resolved.uid',
                        $expr->if(
                            $expr->isNotNull('traversal_workspace.uid'),
                            $cte->quoteIdentifier('traversal_workspace.uid'),
                            $cte->quoteIdentifier('traversal_live.uid'),
                        ),
                    ),
                ),
            )
            ->where(...array_values([
                // If last parent has been detected as start of a recursive cycle, stop here. Note that this is done to
                // keep the cycle detection value in the result set to allow proper handling later on retrieved rows.
                $expr->eq('traversal_c.__CTE_IS_CYCLE__', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                // do not try to fetch page with uid 0, which is the virtual tree root point
                $expr->neq('traversal_c.pid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                // place a maximal traversal level guard against invalid cycling rootlines to mitigate endless recursion
                $expr->lt('traversal_c.__CTE_LEVEL__', $cte->createNamedParameter(self::MAX_CTE_TRAVERSAL_LEVELS, Connection::PARAM_INT)),
                // workspace handling
                $expr->or(
                    // retrieve live workspace if no overlays exists
                    $expr->isNull('traversal_workspace.uid'),
                    // discard(omit) record if it turned out to be deleted in workspace
                    $expr->and(
                        $expr->isNotNull('traversal_workspace.uid'),
                        $expr->neq(
                            'traversal_workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::DELETE_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                ),
            ]));

        return $traversal;
    }

    /**
     * {@see self::getRecordArray()} used {@see PageRepository::versionOL()} to compose workspace overlayed records in
     * the based, bypassing access checks (aka enableFields) resulting in multiple queries. This constellation of method
     * and database query chains can be condensed down to a single database query, which this method uses to retrieve
     * the equal workspace overlay database page record in one and thus reducing overall database query count.
     */
    protected function getWorkspaceResolvedPageRecord(int $pageId, int $workspaceId): ?array
    {
        $queryBuilder = $this->createQueryBuilder('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        if ($workspaceId === 0) {
            // For live workspace only we can simplify this even more
            $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                );
            return $queryBuilder->executeQuery()->fetchAssociative() ?: null;
        }
        $fields = $this->getPagesFields();
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
        // Remove cycle detection fields from result row
        unset(
            $row['__CTE_PATH__'],
            $row['__CTE_IS_CYCLE__'],
            $row['__CTE_LEVEL__'],
        );
        // Remove helper fields if null, keeping them only if they contain valid data to mimic the way PHP methods
        // throughout the TYPO3 core added these fields.
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

    /**
     * Uses a two-layer cache to ensure that this check is really called VERY VERY SELDOM.
     */
    protected function getPagesFields(): array
    {
        // SchemaInformation provides a 2-level cache (runtime and persisted), no need to cache this here in the class.
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->getSchemaInformation()->listTableColumnNames('pages');
    }

    protected function createQueryBuilder(string $tableName): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
    }

    private function getRootlineFromRuntimeCache(int $pageId): ?array
    {
        $cacheIdentifier = $this->getCacheIdentifier($pageId);
        if ($this->runtimeCache->has('rootline-localcache-' . $cacheIdentifier)) {
            $rootline = $this->runtimeCache->get('rootline-localcache-' . $cacheIdentifier);
            if (is_array($rootline)) {
                return array_reverse($rootline);
            }
        }
        return null;
    }
}
