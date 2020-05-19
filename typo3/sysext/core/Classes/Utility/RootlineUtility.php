<?php
namespace TYPO3\CMS\Core\Utility;

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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Exception\Page\BrokenRootLineException;
use TYPO3\CMS\Core\Exception\Page\CircularRootLineException;
use TYPO3\CMS\Core\Exception\Page\MountPointsDisabledException;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Exception\Page\PagePropertyRelationNotFoundException;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * A utility resolving and Caching the Rootline generation
 */
class RootlineUtility
{
    /**
     * @var int
     */
    protected $pageUid;

    /**
     * @var string
     */
    protected $mountPointParameter;

    /**
     * @var array
     */
    protected $parsedMountPointParameters = [];

    /**
     * @var int
     */
    protected $languageUid = 0;

    /**
     * @var int
     */
    protected $workspaceUid = 0;

    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected static $cache;

    /**
     * @var array
     */
    protected static $localCache = [];

    /**
     * Fields to fetch when populating rootline data
     *
     * @var array
     */
    protected static $rootlineFields = [
        'pid',
        'uid',
        't3ver_oid',
        't3ver_wsid',
        't3ver_state',
        'title',
        'alias',
        'nav_title',
        'media',
        'layout',
        'hidden',
        'starttime',
        'endtime',
        'fe_group',
        'extendToSubpages',
        'doktype',
        'TSconfig',
        'tsconfig_includes',
        'is_siteroot',
        'mount_pid',
        'mount_pid_ol',
        'fe_login_mode',
        'backend_layout_next_level'
    ];

    /**
     * Rootline Context
     *
     * @var PageRepository
     */
    protected $pageContext;

    /**
     * Query context
     *
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $cacheIdentifier;

    /**
     * @var array
     */
    protected static $pageRecordCache = [];

    /**
     * @param int $uid
     * @param string $mountPointParameter
     * @param PageRepository|Context $context - @deprecated PageRepository is used until TYPO3 v9.4, but now the third parameter should be a Context object
     * @throws MountPointsDisabledException
     */
    public function __construct($uid, $mountPointParameter = '', $context = null)
    {
        $this->mountPointParameter = trim($mountPointParameter);
        if ($context instanceof PageRepository) {
            trigger_error('Calling RootlineUtility with PageRepository as third parameter will be unsupported with TYPO3 v10.0. Use a Context object directly.', E_USER_DEPRECATED);
            $this->pageContext = $context;
            $this->context = GeneralUtility::makeInstance(Context::class);
        } else {
            if (!($context instanceof Context)) {
                $context = GeneralUtility::makeInstance(Context::class);
            }
            $this->context = $context;
            $this->pageContext = GeneralUtility::makeInstance(PageRepository::class, $context);
        }

        $this->languageUid = $this->context->getPropertyFromAspect('language', 'id', 0);
        $this->workspaceUid = $this->context->getPropertyFromAspect('workspace', 'id', 0);
        if ($this->mountPointParameter !== '') {
            if (!$GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids']) {
                throw new MountPointsDisabledException('Mount-Point Pages are disabled for this installation. Cannot resolve a Rootline for a page with Mount-Points', 1343462896);
            }
            $this->parseMountPointParameter();
        }

        $this->pageUid = $this->resolvePageId(
            (int)$uid,
            (int)$this->workspaceUid
        );

        if (self::$cache === null) {
            self::$cache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('cache_rootline');
        }
        self::$rootlineFields = array_merge(self::$rootlineFields, GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'], true));
        self::$rootlineFields = array_unique(self::$rootlineFields);

        $this->cacheIdentifier = $this->getCacheIdentifier();
    }

    /**
     * Purges all rootline caches.
     *
     * @internal only used in EXT:core, no public API
     */
    public static function purgeCaches()
    {
        self::$localCache = [];
        self::$pageRecordCache = [];
    }

    /**
     * Constructs the cache Identifier
     *
     * @param int $otherUid
     * @return string
     */
    public function getCacheIdentifier($otherUid = null)
    {
        $mountPointParameter = (string)$this->mountPointParameter;
        if ($mountPointParameter !== '' && strpos($mountPointParameter, ',') !== false) {
            $mountPointParameter = str_replace(',', '__', $mountPointParameter);
        }

        return implode('_', [
            $otherUid !== null ? (int)$otherUid : $this->pageUid,
            $mountPointParameter,
            $this->languageUid,
            $this->workspaceUid
        ]);
    }

    /**
     * Returns the actual rootline without the tree root (uid=0), including the page with $this->pageUid
     *
     * @return array
     */
    public function get()
    {
        if ($this->pageUid === 0) {
            // pageUid 0 has no root line, return empty array right away
            return [];
        }
        if (!isset(static::$localCache[$this->cacheIdentifier])) {
            $entry = static::$cache->get($this->cacheIdentifier);
            if (!$entry) {
                $this->generateRootlineCache();
            } else {
                static::$localCache[$this->cacheIdentifier] = $entry;
                $depth = count($entry);
                // Populate the root-lines for parent pages as well
                // since they are part of the current root-line
                while ($depth > 1) {
                    --$depth;
                    $parentCacheIdentifier = $this->getCacheIdentifier($entry[$depth - 1]['uid']);
                    // Abort if the root-line of the parent page is
                    // already in the local cache data
                    if (isset(static::$localCache[$parentCacheIdentifier])) {
                        break;
                    }
                    // Behaves similar to array_shift(), but preserves
                    // the array keys - which contain the page ids here
                    $entry = array_slice($entry, 1, null, true);
                    static::$localCache[$parentCacheIdentifier] = $entry;
                }
            }
        }
        return static::$localCache[$this->cacheIdentifier];
    }

    /**
     * Queries the database for the page record and returns it.
     *
     * @param int $uid Page id
     * @throws PageNotFoundException
     * @return array
     */
    protected function getRecordArray($uid)
    {
        $currentCacheIdentifier = $this->getCacheIdentifier($uid);
        if (!isset(self::$pageRecordCache[$currentCacheIdentifier])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $row = $queryBuilder->select(...self::$rootlineFields)
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
                )
                ->execute()
                ->fetch();
            if (empty($row)) {
                throw new PageNotFoundException('Could not fetch page data for uid ' . $uid . '.', 1343589451);
            }
            $this->pageContext->versionOL('pages', $row, false, true);
            $this->pageContext->fixVersioningPid('pages', $row);
            if (is_array($row)) {
                if ($this->languageUid > 0) {
                    $row = $this->pageContext->getPageOverlay($row, $this->languageUid);
                }
                $row = $this->enrichWithRelationFields($row['_PAGES_OVERLAY_UID'] ??  $uid, $row);
                self::$pageRecordCache[$currentCacheIdentifier] = $row;
            }
        }
        if (!is_array(self::$pageRecordCache[$currentCacheIdentifier])) {
            throw new PageNotFoundException('Broken rootline. Could not resolve page with uid ' . $uid . '.', 1343464101);
        }
        return self::$pageRecordCache[$currentCacheIdentifier];
    }

    /**
     * Resolve relations as defined in TCA and add them to the provided $pageRecord array.
     *
     * @param int $uid page ID
     * @param array $pageRecord Page record (possibly overlaid) to be extended with relations
     * @throws PagePropertyRelationNotFoundException
     * @return array $pageRecord with additional relations
     */
    protected function enrichWithRelationFields($uid, array $pageRecord)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        // @todo Remove this special interpretation of relations by consequently using RelationHandler
        foreach ($GLOBALS['TCA']['pages']['columns'] as $column => $configuration) {
            // Ensure that only fields defined in $rootlineFields (and "addRootLineFields") are actually evaluated
            if (array_key_exists($column, $pageRecord) && $this->columnHasRelationToResolve($configuration)) {
                $configuration = $configuration['config'];
                if ($configuration['MM']) {
                    /** @var \TYPO3\CMS\Core\Database\RelationHandler $loadDBGroup */
                    $loadDBGroup = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\RelationHandler::class);
                    $loadDBGroup->start(
                        $pageRecord[$column],
                        // @todo That depends on the type (group, select, inline)
                        $configuration['allowed'] ?? $configuration['foreign_table'],
                        $configuration['MM'],
                        $uid,
                        'pages',
                        $configuration
                    );
                    $relatedUids = $loadDBGroup->tableArray[$configuration['foreign_table']] ?? [];
                } else {
                    // @todo The assumption is wrong, since group can be used without "MM", but having "allowed"
                    $table = $configuration['foreign_table'];

                    $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
                    $queryBuilder->getRestrictions()->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                        ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
                    $queryBuilder->select('uid')
                        ->from($table)
                        ->where(
                            $queryBuilder->expr()->eq(
                                $configuration['foreign_field'],
                                $queryBuilder->createNamedParameter(
                                    $uid,
                                    \PDO::PARAM_INT
                                )
                            )
                        );

                    if (isset($configuration['foreign_match_fields']) && is_array($configuration['foreign_match_fields'])) {
                        foreach ($configuration['foreign_match_fields'] as $field => $value) {
                            $queryBuilder->andWhere(
                                $queryBuilder->expr()->eq(
                                    $field,
                                    $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR)
                                )
                            );
                        }
                    }
                    if (isset($configuration['foreign_table_field'])) {
                        $queryBuilder->andWhere(
                            $queryBuilder->expr()->eq(
                                trim($configuration['foreign_table_field']),
                                $queryBuilder->createNamedParameter(
                                    'pages',
                                    \PDO::PARAM_STR
                                )
                            )
                        );
                    }
                    if (isset($configuration['foreign_sortby'])) {
                        $queryBuilder->orderBy($configuration['foreign_sortby']);
                    }
                    try {
                        $statement = $queryBuilder->execute();
                    } catch (DBALException $e) {
                        throw new PagePropertyRelationNotFoundException('Could to resolve related records for page ' . $uid . ' and foreign_table ' . htmlspecialchars($table), 1343589452);
                    }
                    $relatedUids = [];
                    while ($row = $statement->fetch()) {
                        $relatedUids[] = $row['uid'];
                    }
                }
                $pageRecord[$column] = implode(',', $relatedUids);
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
    protected function columnHasRelationToResolve(array $configuration)
    {
        $configuration = $configuration['config'] ?? null;
        if (!empty($configuration['MM']) && !empty($configuration['type']) && in_array($configuration['type'], ['select', 'inline', 'group'])) {
            return true;
        }
        if (!empty($configuration['foreign_field']) && !empty($configuration['type']) && in_array($configuration['type'], ['select', 'inline'])) {
            return true;
        }
        return false;
    }

    /**
     * Actual function to generate the rootline and cache it
     *
     * @throws CircularRootLineException
     */
    protected function generateRootlineCache()
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
            $rootline = GeneralUtility::makeInstance(self::class, $parentUid, $mountPointParameter, $this->context)->get();
            // retrieve cache tags of parent rootline
            foreach ($rootline as $entry) {
                $cacheTags[] = 'pageId_' . $entry['uid'];
                if ($entry['uid'] == $this->pageUid) {
                    throw new CircularRootLineException('Circular connection in rootline for page with uid ' . $this->pageUid . ' found. Check your mountpoint configuration.', 1343464103);
                }
            }
        } else {
            $rootline = [];
        }
        $rootline[] = $page;
        krsort($rootline);
        static::$cache->set($this->cacheIdentifier, $rootline, $cacheTags);
        static::$localCache[$this->cacheIdentifier] = $rootline;
    }

    /**
     * Checks whether the current Page is a Mounted Page
     * (according to the MP-URL-Parameter)
     *
     * @return bool
     */
    public function isMountedPage()
    {
        return array_key_exists($this->pageUid, $this->parsedMountPointParameters);
    }

    /**
     * Enhances with mount point information or replaces the node if needed
     *
     * @param array $mountedPageData page record array of mounted page
     * @param array $mountPointPageData page record array of mount point page
     * @throws BrokenRootLineException
     * @return array
     */
    protected function processMountedPage(array $mountedPageData, array $mountPointPageData)
    {
        $mountPid = $mountPointPageData['mount_pid'] ?? null;
        $uid = $mountedPageData['uid'] ?? null;
        if ($mountPid != $uid) {
            throw new BrokenRootLineException('Broken rootline. Mountpoint parameter does not match the actual rootline. mount_pid (' . $mountPid . ') does not match page uid (' . $uid . ').', 1343464100);
        }
        // Current page replaces the original mount-page
        $mountUid = $mountPointPageData['uid'] ?? null;
        if (!empty($mountPointPageData['mount_pid_ol'])) {
            $mountedPageData['_MOUNT_OL'] = true;
            $mountedPageData['_MOUNT_PAGE'] = [
                'uid' => $mountUid,
                'pid' => $mountPointPageData['pid'] ?? null,
                'title' => $mountPointPageData['title'] ?? null
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
     * Parse the MountPoint Parameters
     * Splits the MP-Param via "," for several nested mountpoints
     * and afterwords registers the mountpoint configurations
     */
    protected function parseMountPointParameter()
    {
        $mountPoints = GeneralUtility::trimExplode(',', $this->mountPointParameter);
        foreach ($mountPoints as $mP) {
            list($mountedPageUid, $mountPageUid) = GeneralUtility::intExplode('-', $mP);
            $this->parsedMountPointParameters[$mountedPageUid] = $mountPageUid;
        }
    }

    /**
     * @param int $pageId
     * @param int $workspaceId
     * @return int
     */
    protected function resolvePageId(int $pageId, int $workspaceId): int
    {
        if ($pageId === 0 || $workspaceId === 0) {
            return $pageId;
        }

        $page = $this->resolvePageRecord(
            $pageId,
            ['uid', 't3ver_oid', 't3ver_state']
        );
        if (!VersionState::cast($page['t3ver_state'])
            ->equals(VersionState::MOVE_POINTER)) {
            return $pageId;
        }

        $movePlaceholder = $this->resolveMovePlaceHolder(
            (int)$page['t3ver_oid'],
            ['uid'],
            $workspaceId
        );
        if (empty($movePlaceholder['uid'])) {
            return $pageId;
        }

        return (int)$movePlaceholder['uid'];
    }

    /**
     * @param int $pageId
     * @param array $fieldNames
     * @return array|null
     */
    protected function resolvePageRecord(int $pageId, array $fieldNames): ?array
    {
        $queryBuilder = $this->createQueryBuilder('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->from('pages')
            ->select(...$fieldNames)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->execute();

        $record = $statement->fetch(FetchMode::ASSOCIATIVE);
        return $record ?: null;
    }

    /**
     * @param int $liveId
     * @param array $fieldNames
     * @param int $workspaceId
     * @return array|null
     */
    protected function resolveMovePlaceHolder(int $liveId, array $fieldNames, int $workspaceId): ?array
    {
        $queryBuilder = $this->createQueryBuilder('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->from('pages')
            ->select(...$fieldNames)
            ->setMaxResults(1)
            ->where(
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($workspaceId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_state',
                    $queryBuilder->createNamedParameter(VersionState::MOVE_PLACEHOLDER, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_move_id',
                    $queryBuilder->createNamedParameter($liveId, \PDO::PARAM_INT)
                )
            )
            ->execute();

        $record = $statement->fetch(FetchMode::ASSOCIATIVE);
        return $record ?: null;
    }

    /**
     * @param string $tableName
     * @return QueryBuilder
     */
    protected function createQueryBuilder(string $tableName): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
    }
}
