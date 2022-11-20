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

namespace TYPO3\CMS\Workspaces\Domain\Record;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Service\StagesService;

/**
 * Combined record class
 */
class WorkspaceRecord extends AbstractRecord
{
    /**
     * @var array
     */
    protected $internalStages = [
        StagesService::STAGE_EDIT_ID => [
            'name' => 'edit',
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod_user_ws.xlf:stage_editing',
        ],
        StagesService::STAGE_PUBLISH_ID => [
            'name' => 'publish',
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_ready_to_publish',
        ],
        StagesService::STAGE_PUBLISH_EXECUTE_ID => [
            'name' => 'execute',
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod_user_ws.xlf:stage_publish',
        ],
    ];

    /**
     * @var array
     */
    protected $internalStageFieldNames = [
        'notification_defaults',
        'notification_preselection',
        'allow_notificaton_settings',
    ];

    /**
     * @var array|null
     */
    protected $owners;

    /**
     * @var array|null
     */
    protected $members;

    /**
     * @var StageRecord[]|null
     */
    protected $stages;

    /**
     * @param int $uid
     * @return WorkspaceRecord
     */
    public static function get($uid, array $record = null)
    {
        if (empty($uid)) {
            $record = [];
        } elseif (empty($record)) {
            $record = static::fetch('sys_workspace', $uid);
        }

        return GeneralUtility::makeInstance(self::class, $record);
    }

    /**
     * @return array
     */
    public function getOwners()
    {
        if (!isset($this->owners)) {
            $this->owners = $this->getStagesService()->resolveBackendUserIds($this->record['adminusers']);
        }
        return $this->owners;
    }

    /**
     * @return array
     */
    public function getMembers()
    {
        if (!isset($this->members)) {
            $this->members = $this->getStagesService()->resolveBackendUserIds($this->record['members']);
        }
        return $this->members;
    }

    /**
     * @return StageRecord[]
     */
    public function getStages()
    {
        if (!isset($this->stages)) {
            $this->stages = [];
            $this->addStage($this->createInternalStage(StagesService::STAGE_EDIT_ID));

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_workspace_stage');

            $result = $queryBuilder
                ->select('*')
                ->from('sys_workspace_stage')
                ->where(
                    $queryBuilder->expr()->eq(
                        'parentid',
                        $queryBuilder->createNamedParameter($this->getUid(), Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'parenttable',
                        $queryBuilder->createNamedParameter('sys_workspace')
                    )
                )
                ->orderBy('sorting')
                ->executeQuery();

            while ($record = $result->fetchAssociative()) {
                $this->addStage(StageRecord::build($this, $record['uid'], $record));
            }

            $this->addStage($this->createInternalStage(StagesService::STAGE_PUBLISH_ID));
            $this->addStage($this->createInternalStage(StagesService::STAGE_PUBLISH_EXECUTE_ID));
        }

        return $this->stages;
    }

    /**
     * @param int $stageId
     * @return StageRecord|null
     */
    public function getStage($stageId)
    {
        $stageId = (int)$stageId;
        $this->getStages();
        if (!isset($this->stages[$stageId])) {
            return null;
        }
        return $this->stages[$stageId];
    }

    /**
     * @param int $stageId
     * @return StageRecord|null
     */
    public function getPreviousStage($stageId)
    {
        $stageId = (int)$stageId;
        $stageIds = array_keys($this->getStages());
        $stageIndex = array_search($stageId, $stageIds);

        // catches "0" (edit stage) as well
        if (empty($stageIndex)) {
            return null;
        }

        $previousStageId = $stageIds[$stageIndex - 1];
        return $this->stages[$previousStageId];
    }

    /**
     * @param int $stageId
     * @return StageRecord|null
     */
    public function getNextStage($stageId)
    {
        $stageId = (int)$stageId;
        $stageIds = array_keys($this->getStages());
        $stageIndex = array_search($stageId, $stageIds);

        if ($stageIndex === false || !isset($stageIds[$stageIndex + 1])) {
            return null;
        }

        $nextStageId = $stageIds[$stageIndex + 1];
        return $this->stages[$nextStageId];
    }

    protected function addStage(StageRecord $stage)
    {
        $this->stages[$stage->getUid()] = $stage;
    }

    /**
     * @param int $stageId
     * @return StageRecord
     * @throws \RuntimeException
     */
    protected function createInternalStage($stageId)
    {
        $stageId = (int)$stageId;

        if (!isset($this->internalStages[$stageId])) {
            throw new \RuntimeException('Invalid internal stage "' . $stageId . '"', 1476048246);
        }

        $record = [
            'uid' => $stageId,
            'title' => static::getLanguageService()->sL($this->internalStages[$stageId]['label']),
        ];

        $fieldNamePrefix = $this->internalStages[$stageId]['name'] . '_';
        foreach ($this->internalStageFieldNames as $fieldName) {
            $record[$fieldName] = $this->record[$fieldNamePrefix . $fieldName] ?? null;
        }

        $stage = StageRecord::build($this, $stageId, $record);
        $stage->setInternal(true);
        return $stage;
    }
}
