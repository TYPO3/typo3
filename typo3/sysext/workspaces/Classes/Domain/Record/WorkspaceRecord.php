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

namespace TYPO3\CMS\Workspaces\Domain\Record;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Service\StagesService;

/**
 * Represents a workspace record in the TYPO3 Workspaces extension.
 *
 * @internal
 */
class WorkspaceRecord
{
    protected array $internalStages = [
        StagesService::STAGE_EDIT_ID => [
            'name' => 'edit',
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_editing',
        ],
        StagesService::STAGE_PUBLISH_ID => [
            'name' => 'publish',
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_ready_to_publish',
        ],
        StagesService::STAGE_PUBLISH_EXECUTE_ID => [
            'name' => 'execute',
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_publish',
        ],
    ];

    protected array $internalStageFieldNames = [
        'notification_defaults',
        'notification_preselection',
        'allow_notificaton_settings',
    ];

    protected ?array $owners;
    protected ?array $members;
    protected array $record;

    /**
     * @var StageRecord[]|null
     */
    protected ?array $stages;

    public static function get(int $uid, ?array $record = null): WorkspaceRecord
    {
        if (empty($uid)) {
            $record = [];
        } elseif (empty($record)) {
            $record = static::fetch('sys_workspace', $uid);
        }

        return new self($record);
    }

    public function __construct(array $record)
    {
        $this->record = $record;
    }

    public function __toString(): string
    {
        return (string)$this->getUid();
    }

    public function getUid(): int
    {
        return (int)$this->record['uid'];
    }

    public function getTitle(): string
    {
        return (string)$this->record['title'];
    }

    public function getOwners(): array
    {
        if (!isset($this->owners)) {
            $this->owners = $this->getStagesService()->resolveBackendUserIds($this->record['adminusers']);
        }
        return $this->owners;
    }

    public function getMembers(): array
    {
        if (!isset($this->members)) {
            $this->members = $this->getStagesService()->resolveBackendUserIds($this->record['members']);
        }
        return $this->members;
    }

    /**
     * @return StageRecord[]
     */
    public function getStages(): array
    {
        if (!isset($this->stages)) {
            $this->stages = [];
            $stage = $this->createInternalStage(StagesService::STAGE_EDIT_ID);
            $this->stages[$stage->getUid()] = $stage;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_workspace_stage');
            $result = $queryBuilder
                ->select('*')
                ->from('sys_workspace_stage')
                ->where(
                    $queryBuilder->expr()->eq(
                        'parentid',
                        $queryBuilder->createNamedParameter($this->getUid(), Connection::PARAM_INT)
                    ),
                )
                ->orderBy('sorting')
                ->executeQuery();
            while ($record = $result->fetchAssociative()) {
                $stage = new StageRecord($this, $record, false);
                $this->stages[$stage->getUid()] = $stage;
            }
            $stage = $this->createInternalStage(StagesService::STAGE_PUBLISH_ID);
            $this->stages[$stage->getUid()] = $stage;
            $stage = $this->createInternalStage(StagesService::STAGE_PUBLISH_EXECUTE_ID);
            $this->stages[$stage->getUid()] = $stage;
        }
        return $this->stages;
    }

    public function getStage(int $stageId): ?StageRecord
    {
        $this->getStages();
        if (!isset($this->stages[$stageId])) {
            return null;
        }
        return $this->stages[$stageId];
    }

    public function getPreviousStage(int $stageId): ?StageRecord
    {
        $stageIds = array_keys($this->getStages());
        $stageIndex = array_search($stageId, $stageIds);

        // catches "0" (edit stage) as well
        if (empty($stageIndex)) {
            return null;
        }

        $previousStageId = $stageIds[$stageIndex - 1];
        return $this->stages[$previousStageId];
    }

    public function getNextStage(int $stageId): ?StageRecord
    {
        $stageIds = array_keys($this->getStages());
        $stageIndex = array_search($stageId, $stageIds);

        if ($stageIndex === false || !isset($stageIds[$stageIndex + 1])) {
            return null;
        }

        $nextStageId = $stageIds[$stageIndex + 1];
        return $this->stages[$nextStageId];
    }

    protected function createInternalStage(int $stageId): StageRecord
    {
        if (!isset($this->internalStages[$stageId])) {
            throw new \RuntimeException('Invalid internal stage "' . $stageId . '"', 1476048246);
        }
        $record = [
            'uid' => $stageId,
            'title' => $this->getLanguageService()->sL($this->internalStages[$stageId]['label']),
        ];
        $fieldNamePrefix = $this->internalStages[$stageId]['name'] . '_';
        foreach ($this->internalStageFieldNames as $fieldName) {
            $record[$fieldName] = $this->record[$fieldNamePrefix . $fieldName] ?? null;
        }
        return new StageRecord($this, $record, true);
    }

    protected static function fetch(string $tableName, int $uid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $record = $queryBuilder->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();
        if (empty($record)) {
            throw new \RuntimeException('Record "' . $tableName . ': ' . $uid . '" not found', 1476122008);
        }
        return $record;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getStagesService(): StagesService
    {
        return GeneralUtility::makeInstance(StagesService::class);
    }
}
