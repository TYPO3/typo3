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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Authorization\WorkspacePublishGate;
use TYPO3\CMS\Workspaces\Service\StagesService;

/**
 * Represents a stage of a workspace record in the TYPO3 Workspaces extension.
 *
 * @internal
 */
class StageRecord
{
    protected ?array $responsiblePersons;
    protected ?array $defaultRecipients;
    protected ?array $preselectedRecipients;
    protected ?array $allRecipients;

    public function __construct(
        protected readonly WorkspaceRecord $workspace,
        protected readonly array $record,
        protected readonly bool $internal,
    ) {}

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

    public function getWorkspace(): WorkspaceRecord
    {
        return $this->workspace;
    }

    public function getPrevious(): ?StageRecord
    {
        return $this->getWorkspace()->getPreviousStage($this->getUid());
    }

    public function getNext(): ?StageRecord
    {
        return $this->getWorkspace()->getNextStage($this->getUid());
    }

    public function determineOrder(StageRecord $stageRecord): int
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
     */
    public function isPreviousTo(StageRecord $stageRecord): bool
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
     */
    public function isNextTo(StageRecord $stageRecord): bool
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

    public function getDefaultComment(): string
    {
        $defaultComment = '';
        if (isset($this->record['default_mailcomment'])) {
            $defaultComment = $this->record['default_mailcomment'];
        }
        return $defaultComment;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function isEditStage(): bool
    {
        return $this->getUid() === StagesService::STAGE_EDIT_ID;
    }

    public function isPublishStage(): bool
    {
        return $this->getUid() === StagesService::STAGE_PUBLISH_ID;
    }

    public function isExecuteStage(): bool
    {
        return $this->getUid() === StagesService::STAGE_PUBLISH_EXECUTE_ID;
    }

    public function isDialogEnabled(): bool
    {
        return ((int)$this->record['allow_notificaton_settings'] & 1) > 0;
    }

    public function isPreselectionChangeable(): bool
    {
        return ((int)$this->record['allow_notificaton_settings'] & 2) > 0;
    }

    public function areOwnersPreselected(): bool
    {
        return ((int)$this->record['notification_preselection'] & 1) > 0;
    }

    public function areMembersPreselected(): bool
    {
        return ((int)$this->record['notification_preselection'] & 2) > 0;
    }

    public function areResponsiblePersonsPreselected(): bool
    {
        return ((int)$this->record['notification_preselection'] & 8) > 0;
    }

    public function hasDefaultRecipients(): bool
    {
        return $this->record['notification_defaults'] !== '';
    }

    public function hasPreselection(): bool
    {
        return
            $this->areOwnersPreselected()
            || $this->areMembersPreselected()
            || $this->areResponsiblePersonsPreselected()
            || $this->hasDefaultRecipients();
    }

    public function getResponsiblePersons(): array
    {
        if (!isset($this->responsiblePersons)) {
            $this->responsiblePersons = [];
            if (!empty($this->record['responsible_persons'])) {
                $this->responsiblePersons = $this->getStagesService()->resolveBackendUserIds($this->record['responsible_persons']);
            }
        }
        return $this->responsiblePersons;
    }

    public function getDefaultRecipients(): array
    {
        if (!isset($this->defaultRecipients)) {
            $this->defaultRecipients = $this->getStagesService()->resolveBackendUserIds($this->record['notification_defaults']);
        }
        return $this->defaultRecipients;
    }

    /**
     * Gets all recipients (backend user ids).
     */
    public function getAllRecipients(): array
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
    public function getPreselectedRecipients(): array
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

    public function isAllowed(): bool
    {
        return
            $this->isEditStage()
            || $this->getBackendUser()->workspaceCheckStageForCurrent($this->getUid())
            || (
                $this->isExecuteStage()
                && GeneralUtility::makeInstance(WorkspacePublishGate::class)->isGranted($this->getBackendUser(), $this->workspace->getUid())
            );
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getStagesService(): StagesService
    {
        return GeneralUtility::makeInstance(StagesService::class);
    }
}
