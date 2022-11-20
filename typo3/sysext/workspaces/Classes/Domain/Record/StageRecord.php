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

use TYPO3\CMS\Workspaces\Service\StagesService;

/**
 * Combined record class
 */
class StageRecord extends AbstractRecord
{
    /**
     * @var WorkspaceRecord
     */
    protected $workspace;

    /**
     * @var bool
     */
    protected $internal = false;

    /**
     * @var array|null
     */
    protected $responsiblePersons;

    /**
     * @var array|null
     */
    protected $defaultRecipients;

    /**
     * @var array|null
     */
    protected $preselectedRecipients;

    /**
     * @var array|null
     */
    protected $allRecipients;

    /**
     * @param int $uid
     * @return StageRecord|null
     */
    public static function get($uid, array $record = null)
    {
        if (empty($record)) {
            $record = static::fetch('sys_workspace_stage', $uid);
        }
        return WorkspaceRecord::get($record['parentid'])->getStage($uid);
    }

    /**
     * @param int $uid
     * @return StageRecord
     */
    public static function build(WorkspaceRecord $workspace, $uid, array $record = null)
    {
        if (empty($record)) {
            $record = static::fetch('sys_workspace_stage', $uid);
        }
        return new self($workspace, $record);
    }

    public function __construct(WorkspaceRecord $workspace, array $record)
    {
        parent::__construct($record);
        $this->workspace = $workspace;
    }

    /**
     * @return WorkspaceRecord
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @return StageRecord|null
     */
    public function getPrevious()
    {
        return $this->getWorkspace()->getPreviousStage($this->getUid());
    }

    /**
     * @return StageRecord|null
     */
    public function getNext()
    {
        return $this->getWorkspace()->getNextStage($this->getUid());
    }

    /**
     * @return int
     */
    public function determineOrder(StageRecord $stageRecord)
    {
        if ($this->getUid() === $stageRecord->getUid()) {
            return 0;
        }
        if ($this->isEditStage() || $stageRecord->isExecuteStage() || $this->isPreviousTo($stageRecord)) {
            return -1;
        }
        if ($this->isExecuteStage() || $stageRecord->isEditStage() || $this->isNextTo($stageRecord)) {
            return 1;
        }
        return 0;
    }

    /**
     * Determines whether $this is in a previous stage compared to $stageRecord.
     *
     * @return bool
     */
    public function isPreviousTo(StageRecord $stageRecord)
    {
        $current = $stageRecord;
        while ($previous = $current->getPrevious()) {
            if ($this->getUid() === $previous->getUid()) {
                return true;
            }
            $current = $previous;
        }
        return false;
    }

    /**
     * Determines whether $this is in a later stage compared to $stageRecord.
     *
     * @return bool
     */
    public function isNextTo(StageRecord $stageRecord)
    {
        $current = $stageRecord;
        while ($next = $current->getNext()) {
            if ($this->getUid() === $next->getUid()) {
                return true;
            }
            $current = $next;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getDefaultComment()
    {
        $defaultComment = '';
        if (isset($this->record['default_mailcomment'])) {
            $defaultComment = $this->record['default_mailcomment'];
        }
        return $defaultComment;
    }

    /**
     * @param bool $internal
     */
    public function setInternal($internal = true)
    {
        $this->internal = (bool)$internal;
    }

    /**
     * @return bool
     */
    public function isInternal()
    {
        return $this->internal;
    }

    /**
     * @return bool
     */
    public function isEditStage()
    {
        return $this->getUid() === StagesService::STAGE_EDIT_ID;
    }

    /**
     * @return bool
     */
    public function isPublishStage()
    {
        return $this->getUid() === StagesService::STAGE_PUBLISH_ID;
    }

    /**
     * @return bool
     */
    public function isExecuteStage()
    {
        return $this->getUid() === StagesService::STAGE_PUBLISH_EXECUTE_ID;
    }

    /**
     * @return bool
     */
    public function isDialogEnabled()
    {
        return ((int)$this->record['allow_notificaton_settings'] & 1) > 0;
    }

    /**
     * @return bool
     */
    public function isPreselectionChangeable()
    {
        return ((int)$this->record['allow_notificaton_settings'] & 2) > 0;
    }

    /**
     * @return bool
     */
    public function areOwnersPreselected()
    {
        return ((int)$this->record['notification_preselection'] & 1) > 0;
    }

    /**
     * @return bool
     */
    public function areMembersPreselected()
    {
        return ((int)$this->record['notification_preselection'] & 2) > 0;
    }

    /**
     * @return bool
     */
    public function areEditorsPreselected()
    {
        return ((int)$this->record['notification_preselection'] & 4) > 0;
    }

    /**
     * @return bool
     */
    public function areResponsiblePersonsPreselected()
    {
        return ((int)$this->record['notification_preselection'] & 8) > 0;
    }

    public function hasDefaultRecipients(): bool
    {
        return $this->record['notification_defaults'] !== '';
    }

    /**
     * @return bool
     */
    public function hasPreselection()
    {
        return
            $this->areOwnersPreselected()
            || $this->areMembersPreselected()
            || $this->areEditorsPreselected()
            || $this->areResponsiblePersonsPreselected()
            || $this->hasDefaultRecipients()
        ;
    }

    /**
     * @return array
     */
    public function getResponsiblePersons()
    {
        if (!isset($this->responsiblePersons)) {
            $this->responsiblePersons = [];
            if (!empty($this->record['responsible_persons'])) {
                $this->responsiblePersons = $this->getStagesService()->resolveBackendUserIds($this->record['responsible_persons']);
            }
        }
        return $this->responsiblePersons;
    }

    /**
     * @return array
     */
    public function getDefaultRecipients()
    {
        if (!isset($this->defaultRecipients)) {
            $this->defaultRecipients = $this->getStagesService()->resolveBackendUserIds($this->record['notification_defaults']);
        }
        return $this->defaultRecipients;
    }

    /**
     * Gets all recipients (backend user ids).
     *
     * @return array
     */
    public function getAllRecipients()
    {
        if (!isset($this->allRecipients)) {
            $allRecipients = $this->getDefaultRecipients();

            if ($this->isInternal() || $this->areOwnersPreselected()) {
                $allRecipients = array_merge($allRecipients, $this->getWorkspace()->getOwners());
            }
            if ($this->isInternal() || $this->areMembersPreselected()) {
                $allRecipients = array_merge($allRecipients, $this->getWorkspace()->getMembers());
            }
            if (!$this->isInternal()) {
                $allRecipients = array_merge($allRecipients, $this->getResponsiblePersons());
            }

            $this->allRecipients = array_unique($allRecipients);
        }

        return $this->allRecipients;
    }

    /**
     * @return int[]
     */
    public function getPreselectedRecipients()
    {
        if (!isset($this->preselectedRecipients)) {
            $preselectedRecipients = $this->getDefaultRecipients();

            if ($this->areOwnersPreselected()) {
                $preselectedRecipients = array_merge($preselectedRecipients, $this->getWorkspace()->getOwners());
            }
            if ($this->areMembersPreselected()) {
                $preselectedRecipients = array_merge($preselectedRecipients, $this->getWorkspace()->getMembers());
            }
            if ($this->areResponsiblePersonsPreselected()) {
                $preselectedRecipients = array_merge($preselectedRecipients, $this->getResponsiblePersons());
            }

            $this->preselectedRecipients = array_unique($preselectedRecipients);
        }

        return $this->preselectedRecipients;
    }

    /**
     * @return bool
     */
    public function isAllowed()
    {
        return
            $this->isEditStage()
            || static::getBackendUser()->workspaceCheckStageForCurrent($this->getUid())
            || $this->isExecuteStage() && static::getBackendUser()->workspacePublishAccess($this->workspace->getUid())
        ;
    }
}
