<?php

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

namespace TYPO3\CMS\Core\DataHandling;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Plain data resolving.
 *
 * This component resolves data constraints for given IDs of a
 * particular table on a plain/raw database level. Thus, workspaces
 * placeholders and overlay related resorting is applied automatically.
 */
class PlainDataResolver
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var int[]
     */
    protected $liveIds;

    /**
     * @var array|null
     */
    protected $sortingStatement;

    /**
     * @var int
     */
    protected $workspaceId;

    /**
     * @var bool
     */
    protected $keepLiveIds = false;

    /**
     * @var bool
     */
    protected $keepDeletePlaceholder = false;

    /**
     * @var bool
     */
    protected $keepMovePlaceholder = true;

    /**
     * @param string $tableName
     * @param int[] $liveIds
     * @param array|null $sortingStatement
     */
    public function __construct($tableName, array $liveIds, array $sortingStatement = null)
    {
        $this->tableName = $tableName;
        $this->liveIds = $this->reindex($this->sanitizeIds($liveIds));
        $this->sortingStatement = $sortingStatement;
    }

    /**
     * Sets the target workspace ID the final result shall use.
     *
     * @param int $workspaceId
     */
    public function setWorkspaceId($workspaceId)
    {
        $this->workspaceId = (int)$workspaceId;
    }

    /**
     * Sets whether live IDs shall be kept in the final result set.
     *
     * @param bool $keepLiveIds
     * @return PlainDataResolver
     */
    public function setKeepLiveIds($keepLiveIds)
    {
        $this->keepLiveIds = (bool)$keepLiveIds;
        return $this;
    }

    /**
     * Sets whether delete placeholders shall be kept in the final result set.
     *
     * @param bool $keepDeletePlaceholder
     * @return PlainDataResolver
     */
    public function setKeepDeletePlaceholder($keepDeletePlaceholder)
    {
        $this->keepDeletePlaceholder = (bool)$keepDeletePlaceholder;
        return $this;
    }

    /**
     * Sets whether move placeholders shall be kept in case they cannot be substituted.
     *
     * @param bool $keepMovePlaceholder
     * @return PlainDataResolver
     */
    public function setKeepMovePlaceholder($keepMovePlaceholder)
    {
        $this->keepMovePlaceholder = (bool)$keepMovePlaceholder;
        return $this;
    }

    /**
     * @return int[]
     */
    public function get()
    {
        $resolvedIds = $this->processVersionOverlays($this->liveIds);
        if ($resolvedIds !== $this->liveIds) {
            $resolvedIds = $this->reindex($resolvedIds);
        }

        $tempIds = $this->processSorting($resolvedIds);
        if ($tempIds !== $resolvedIds) {
            $resolvedIds = $this->reindex($tempIds);
        }

        $tempIds = $this->applyLiveIds($resolvedIds);
        if ($tempIds !== $resolvedIds) {
            $resolvedIds = $this->reindex($tempIds);
        }

        return $resolvedIds;
    }

    /**
     * Processes version overlays on the final result set.
     *
     * @param int[] $ids
     * @return int[]
     * @internal
     */
    public function processVersionOverlays(array $ids)
    {
        $ids = $this->sanitizeIds($ids);
        if (empty($this->workspaceId) || !$this->isWorkspaceEnabled() || empty($ids)) {
            return $ids;
        }

        $ids = $this->reindex(
            $this->processVersionMovePlaceholders($ids)
        );
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('uid', 't3ver_oid', 't3ver_state')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->in(
                    't3ver_oid',
                    $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($this->workspaceId, \PDO::PARAM_INT)
                )
            )
            ->executeQuery();

        while ($version = $result->fetchAssociative()) {
            $liveReferenceId = $version['t3ver_oid'];
            $versionId = $version['uid'];
            if (isset($ids[$liveReferenceId])) {
                if (!$this->keepDeletePlaceholder
                    && VersionState::cast($version['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
                ) {
                    unset($ids[$liveReferenceId]);
                } else {
                    $ids[$liveReferenceId] = $versionId;
                }
            }
        }

        return $ids;
    }

    /**
     * Processes and resolves move placeholders on the final result set.
     *
     * @param int[] $ids
     * @return int[]
     * @internal
     */
    public function processVersionMovePlaceholders(array $ids)
    {
        $ids = $this->sanitizeIds($ids);
        // Early return on insufficient data-set
        if (empty($this->workspaceId) || !$this->isWorkspaceEnabled() || empty($ids)) {
            return $ids;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('uid', 't3ver_oid')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    't3ver_state',
                    $queryBuilder->createNamedParameter(VersionState::MOVE_POINTER, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($this->workspaceId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    't3ver_oid',
                    $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery();

        while ($movedRecord = $result->fetchAssociative()) {
            $liveReferenceId = (int)$movedRecord['t3ver_oid'];
            $movedVersionId = (int)$movedRecord['uid'];
            // Substitute moved record and purge live reference
            if (isset($ids[$movedVersionId])) {
                $ids[$movedVersionId] = $liveReferenceId;
                unset($ids[$liveReferenceId]);
            } elseif (!$this->keepMovePlaceholder) {
                // Just purge live reference
                unset($ids[$liveReferenceId]);
            }
        }

        return $ids;
    }

    /**
     * Processes sorting of the final result set, if
     * a sorting statement (table column/expression) is given.
     *
     * @param int[] $ids
     * @return int[]
     * @internal
     */
    public function processSorting(array $ids)
    {
        $ids = $this->sanitizeIds($ids);
        // Early return on missing sorting statement or insufficient data-set
        if (empty($this->sortingStatement) || count($ids) < 2) {
            return $ids;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder
            ->select('uid')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    // do not use named parameter here as the list can get too long
                    array_map('intval', $ids)
                )
            );

        if (!empty($this->sortingStatement)) {
            foreach ($this->sortingStatement as $sortingStatement) {
                $queryBuilder->add('orderBy', $sortingStatement, true);
            }
        }
        // Always add explicit order by uid to have deterministic rows from dbms like postgres.
        // Scenario (see workspace FAL/Modify/ActionTest modifyContentAndDeleteFileReference):
        // A content element with two images - sys_file_reference uid=23 with sorting_foreign=2 (!)
        // and sys_file_reference uid=42 with sorting_foreign=1. The references have been added
        // and later changed their sorting that uid 42 is before 23.
        // Then, in workspaces, image reference 42 is deleted and 23 is changed (eg. title). This
        // creates two overlays: a 'delete placeholder' t3ver_state=2 with sorting_foreign=1 for 42,
        // and a 'changed' record t3ver_state=0 with sorting_foreign=1 for 23.
        // So both overlay records end up with sorting_foreign=1. This is technically ok since the
        // 'delete placeholder' "does not exist" from a live relation point of view, so the next
        // "real" record starts with 1 when published.
        // BUT, this scenario makes the order of returned rows non-deterministic for dbms that
        // do not implicitly order by uid (mysql does, postgres does not): The usual orderBy
        // is 'sorting_foreign' but both are 1 now.
        // We thus add a general explicit order by uid here to force deterministic row returns.
        $queryBuilder->addOrderBy('uid');

        $sortedIds = $queryBuilder->executeQuery()->fetchAllAssociative();

        return array_map('intval', array_column($sortedIds, 'uid'));
    }

    /**
     * Applies live IDs to the final result set, if
     * the current table is enabled for workspaces and
     * the keepLiveIds class member is enabled.
     *
     * @param int[] $ids
     * @return int[]
     * @internal
     */
    public function applyLiveIds(array $ids)
    {
        $ids = $this->sanitizeIds($ids);
        if (!$this->keepLiveIds || !$this->isWorkspaceEnabled() || empty($ids)) {
            return $ids;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('uid', 't3ver_oid')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery();

        $versionIds = [];
        while ($record = $result->fetchAssociative()) {
            $liveId = $record['uid'];
            $versionIds[$liveId] = $record['t3ver_oid'];
        }

        foreach ($ids as $id) {
            if (!empty($versionIds[$id])) {
                $ids[$id] = $versionIds[$id];
            }
        }

        return $ids;
    }

    /**
     * Re-indexes the given IDs.
     *
     * @param int[] $ids
     * @return int[]
     */
    protected function reindex(array $ids)
    {
        if (empty($ids)) {
            return $ids;
        }
        $ids = array_values($ids);
        $ids = array_combine($ids, $ids);
        return $ids;
    }

    /**
     * Removes empty values (null, '0', 0, false).
     *
     * @param int[] $ids
     * @return array
     */
    protected function sanitizeIds(array $ids): array
    {
        return array_filter($ids);
    }

    /**
     * @return bool
     */
    protected function isWorkspaceEnabled()
    {
        if (ExtensionManagementUtility::isLoaded('workspaces')) {
            return BackendUtility::isTableWorkspaceEnabled($this->tableName);
        }
        return false;
    }
}
