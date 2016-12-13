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
     * @var string
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
     * @param NULL|string $sortingStatement
     */
    public function __construct($tableName, array $liveIds, $sortingStatement = null)
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
        $versions = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid,t3ver_oid,t3ver_state',
            $this->tableName,
            'pid=-1 AND t3ver_oid IN (' . $this->intImplode(',', $ids) . ')'
            . ' AND t3ver_wsid=' . $this->workspaceId
        );

        if (!empty($versions)) {
            foreach ($versions as $version) {
                $liveReferenceId = $version['t3ver_oid'];
                $versionId = $version['uid'];
                if (isset($ids[$liveReferenceId])) {
                    if (!$this->keepDeletePlaceholder && VersionState::cast($version['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                        unset($ids[$liveReferenceId]);
                    } else {
                        $ids[$liveReferenceId] = $versionId;
                    }
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

        $movePlaceholders = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid,t3ver_move_id',
            $this->tableName,
            'pid<>-1 AND t3ver_state=' . VersionState::MOVE_PLACEHOLDER
            . ' AND t3ver_wsid=' . $this->workspaceId
            . ' AND t3ver_move_id IN (' . $this->intImplode(',', $ids) . ')'
        );

        if (!empty($movePlaceholders)) {
            foreach ($movePlaceholders as $movePlaceholder) {
                $liveReferenceId = $movePlaceholder['t3ver_move_id'];
                $movePlaceholderId = $movePlaceholder['uid'];
                // Substitute MOVE_PLACEHOLDER and purge live reference
                if (isset($ids[$movePlaceholderId])) {
                    $ids[$movePlaceholderId] = $liveReferenceId;
                    unset($ids[$liveReferenceId]);
                // Just purge live reference
                } elseif (!$this->keepMovePlaceholder) {
                    unset($ids[$liveReferenceId]);
                }
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

        $records = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid',
            $this->tableName,
            'uid IN (' . $this->intImplode(',', $ids) . ')',
            '',
            $this->sortingStatement,
            '',
            'uid'
        );

        if (!is_array($records)) {
            return [];
        }

        $ids = array_keys($records);
        return $ids;
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

        $records = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid,t3ver_oid',
            $this->tableName,
            'uid IN (' . $this->intImplode(',', $ids) . ')',
            '',
            '',
            '',
            'uid'
        );

        if (!is_array($records)) {
            return [];
        }

        foreach ($ids as $id) {
            if (!empty($records[$id]['t3ver_oid'])) {
                $ids[$id] = $records[$id]['t3ver_oid'];
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
        return BackendUtility::isTableWorkspaceEnabled($this->tableName);
    }

    /**
     * @return bool
     */
    protected function isLocalizationEnabled()
    {
        return BackendUtility::isTableLocalizable($this->tableName);
    }

    /**
     * Implodes an array of casted integer values.
     *
     * @param string $delimiter
     * @param array $values
     * @return string
     */
    protected function intImplode($delimiter, array $values)
    {
        return implode($delimiter, $this->getDatabaseConnection()->cleanIntArray($values));
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
