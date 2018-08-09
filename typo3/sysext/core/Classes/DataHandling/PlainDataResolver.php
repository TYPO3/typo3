<?php
namespace TYPO3\CMS\Core\DataHandling;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
     * @var array
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
     * @var int[]
     */
    protected $resolvedIds;

    /**
     * @param string $tableName
     * @param int[] $liveIds
     * @param array|null $sortingStatement
     */
    public function __construct($tableName, array $liveIds, array $sortingStatement = null)
    {
        $this->tableName = $tableName;
        $this->liveIds = $this->reindex($liveIds);
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
        if (isset($this->resolvedIds)) {
            return $this->resolvedIds;
        }

        $this->resolvedIds = $this->processVersionOverlays($this->liveIds);
        if ($this->resolvedIds !== $this->liveIds) {
            $this->resolvedIds = $this->reindex($this->resolvedIds);
        }

        $tempIds = $this->processSorting($this->resolvedIds);
        if ($tempIds !== $this->resolvedIds) {
            $this->resolvedIds = $this->reindex($tempIds);
        }

        $tempIds = $this->applyLiveIds($this->resolvedIds);
        if ($tempIds !== $this->resolvedIds) {
            $this->resolvedIds = $this->reindex($tempIds);
        }

        return $this->resolvedIds;
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
        if (empty($this->workspaceId) || !$this->isWorkspaceEnabled() || empty($ids)) {
            return $ids;
        }

        $ids = $this->reindex(
            $this->processVersionMovePlaceholders($ids)
        );
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('uid', 't3ver_oid', 't3ver_state')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)),
                $queryBuilder->expr()->in(
                    't3ver_oid',
                    $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($this->workspaceId, \PDO::PARAM_INT)
                )
            )
            ->execute();

        while ($version = $result->fetch()) {
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
        // Early return on insufficient data-set
        if (empty($this->workspaceId) || !$this->isWorkspaceEnabled() || empty($ids)) {
            return $ids;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('uid', 't3ver_move_id')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->neq('pid', $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq(
                    't3ver_state',
                    $queryBuilder->createNamedParameter((string)VersionState::MOVE_PLACEHOLDER, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($this->workspaceId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    't3ver_move_id',
                    $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->execute();

        while ($movePlaceholder = $result->fetch()) {
            $liveReferenceId = $movePlaceholder['t3ver_move_id'];
            $movePlaceholderId = $movePlaceholder['uid'];
            // Substitute MOVE_PLACEHOLDER and purge live reference
            if (isset($ids[$movePlaceholderId])) {
                $ids[$movePlaceholderId] = $liveReferenceId;
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
        // Early return on missing sorting statement or insufficient data-set
        if (empty($this->sortingStatement) || count($ids) < 2) {
            return $ids;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->removeAll();

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

        $sortedIds = $queryBuilder->execute()->fetchAll();

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
        if (!$this->keepLiveIds || !$this->isWorkspaceEnabled() || empty($ids)) {
            return $ids;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('uid', 't3ver_oid')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->execute();

        $versionIds = [];
        while ($record = $result->fetch()) {
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
