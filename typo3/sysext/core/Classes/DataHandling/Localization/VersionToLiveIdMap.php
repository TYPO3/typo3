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

namespace TYPO3\CMS\Core\DataHandling\Localization;

use TYPO3\CMS\Core\DataHandling\PlainDataResolver;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Version to live id map.
 *
 * This is on a per-workspace and per-table setup, so it is recommended
 * to use a runtime cache to hold these instances.
 *
 * @internal
 */
class VersionToLiveIdMap
{
    protected string $tableName;
    protected int $workspaceId;

    /**
     * @var int[]
     */
    protected array $map = [];

    public function __construct(string $tableName, int $workspaceId)
    {
        $this->tableName = $tableName;
        $this->workspaceId = $workspaceId;
    }

    /**
     * @param int[] $ids
     */
    public function update(array $ids): self
    {
        $ids = array_map(intval(...), $ids);
        $candidateIds = array_diff(
            $ids,
            array_keys($this->map),
            array_values($this->map)
        );

        if (empty($candidateIds)) {
            return $this;
        }

        $candidateIdMap = array_combine($candidateIds, $candidateIds);
        $schemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        if (
            !$schemaFactory->has($this->tableName)
            || $this->workspaceId === 0
            || !$schemaFactory->get($this->tableName)->isWorkspaceAware()
        ) {
            $this->map += $candidateIdMap;
            return $this;
        }

        $plainDataResolver = GeneralUtility::makeInstance(
            PlainDataResolver::class,
            $this->tableName,
            []
        );
        $plainDataResolver->setWorkspaceId($this->workspaceId);
        $plainDataResolver->setKeepLiveIds(true);
        $versionLiveIdMap = $plainDataResolver->applyLiveIds($candidateIdMap);
        $this->map += $versionLiveIdMap;

        return $this;
    }

    public function getVersionId(int $liveId): int
    {
        $versionId = array_search($liveId, $this->map, true);
        return $versionId ?: $liveId;
    }

    public function getLiveId(int $versionId): string|int
    {
        return $this->map[$versionId] ?? $versionId;
    }

    /**
     * @param int[] $versionIds
     * @return int[]
     */
    public function getLiveIds(array $versionIds): array
    {
        return array_map(
            function (int $versionId) {
                return $this->getLiveId($versionId);
            },
            $versionIds
        );
    }
}
