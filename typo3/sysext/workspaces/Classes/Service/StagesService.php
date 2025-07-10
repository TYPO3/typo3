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

namespace TYPO3\CMS\Workspaces\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Domain\Model\WorkspaceStage;
use TYPO3\CMS\Workspaces\Exception\WorkspaceStageNotFoundException;

/**
 * @internal
 */
#[Autoconfigure(public: true)]
readonly class StagesService
{
    // If a record is in the "ready to publish" stage STAGE_PUBLISH_ID the
    // next stage is STAGE_PUBLISH_EXECUTE_ID, this id is never saved in db
    public const STAGE_PUBLISH_EXECUTE_ID = -20;
    // "Ready to publish" stage
    public const STAGE_PUBLISH_ID = -10;
    public const STAGE_EDIT_ID = 0;

    public function __construct(
        private GroupResolver $groupResolver,
    ) {}

    /**
     * @param WorkspaceStage[] $stages
     * @throws WorkspaceStageNotFoundException
     */
    public function getStage(array $stages, int $stageId): WorkspaceStage
    {
        foreach ($stages as $stage) {
            if ($stage->uid === $stageId) {
                return $stage;
            }
        }
        throw new WorkspaceStageNotFoundException('Workspace stage ' . $stageId . ' does not exist in current workspace', 1752336098);
    }

    /**
     * @param WorkspaceStage[] $stages
     * @throws WorkspaceStageNotFoundException
     */
    public function getPreviousStage(array $stages, int $stageId): WorkspaceStage
    {
        $previousStage = null;
        foreach ($stages as $stage) {
            if ($stage->uid === $stageId) {
                if ($previousStage === null) {
                    throw new WorkspaceStageNotFoundException('Could not find stage before ' . $stageId, 1752356149);
                }
                return $previousStage;
            }
            $previousStage = $stage;
        }
        throw new WorkspaceStageNotFoundException('Stage ' . $stageId . ' is not a valid stage in this workspace' . $stageId, 1752356193);
    }

    /**
     * @param WorkspaceStage[] $stages
     * @throws WorkspaceStageNotFoundException
     */
    public function getNextStage(array $stages, int $stageId): WorkspaceStage
    {
        $foundCurrentStage = false;
        foreach ($stages as $stage) {
            if ($foundCurrentStage) {
                return $stage;
            }
            if ($stage->uid === $stageId) {
                $foundCurrentStage = true;
            }
        }
        throw new WorkspaceStageNotFoundException('Could not find next stage after ' . $stageId, 1752356030);
    }

    /**
     * Find the highest possible "previous" stage for all $byTableName
     *
     * @param WorkspaceStage[] $stages
     * @return array<?WorkspaceStage, ?WorkspaceStage> Current and next possible stage.
     */
    public function getPreviousStageForElementCollection(array $stages, array $workspaceItems): array
    {
        $availableStagesForWSUser = $this->getStagesForWSUser($stages);
        $usedStages = [];
        foreach ($workspaceItems as $tableName => $items) {
            if ($tableName !== 'pages' && $tableName !== 'tt_content') {
                continue;
            }
            foreach ($items as $item) {
                $usedStages[$item['t3ver_stage'] ?? 0] = true;
            }
        }
        $currentStage = null;
        $previousStage = null;
        foreach (array_reverse($stages) as $stage) {
            if (isset($usedStages[$stage->uid])) {
                $currentStage = $this->getStage($stages, $stage->uid);
                try {
                    $previousStage = $this->getPreviousStage($stages, $stage->uid);
                } catch (WorkspaceStageNotFoundException) {
                    // Keep null
                }
                break;
            }
        }
        $found = false;
        foreach ($availableStagesForWSUser as $userWS) {
            if ($previousStage && $previousStage->uid === $userWS->uid) {
                $found = true;
                break;
            }
        }
        if ($found === false || !$currentStage->isAllowed) {
            // If current stage is not allowed for user, it can not send record away to other stage
            $previousStage = null;
        }
        return [
            $currentStage,
            $previousStage,
        ];
    }

    /**
     * Retrieve the next stage based on the lowest stage given in the $workspaceItems record array.
     *
     * @param WorkspaceStage[] $stages
     * @return array<?WorkspaceStage, ?WorkspaceStage> Current and next possible stage.
     */
    public function getNextStageForElementCollection(array $stages, array $workspaceItems): array
    {
        $availableStagesForWSUser = $this->getStagesForWSUser($stages);
        $usedStages = [];
        foreach ($workspaceItems as $tableName => $items) {
            if ($tableName !== 'pages' && $tableName !== 'tt_content') {
                continue;
            }
            foreach ($items as $item) {
                $usedStages[$item['t3ver_stage'] ?? 0] = true;
            }
        }
        $currentStage = null;
        $nextStage = null;
        foreach ($stages as $stage) {
            if (isset($usedStages[$stage->uid])) {
                $currentStage = $this->getStage($stages, $stage->uid);
                $nextStage = $this->getNextStage($stages, $stage->uid);
                break;
            }
        }
        $found = false;
        foreach ($availableStagesForWSUser as $userWS) {
            if ($nextStage && $nextStage->uid === $userWS->uid) {
                $found = true;
                break;
            }
        }
        if ($found === false || !$currentStage->isAllowed) {
            $nextStage = null;
        }
        return [
            $currentStage,
            $nextStage,
        ];
    }

    /**
     * Returns an array of stages, the user is allowed to send to
     *
     * @param WorkspaceStage[] $stages
     * @return WorkspaceStage[]
     */
    public function getStagesForWSUser(array $stages): array
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            return $stages;
        }
        $currentWorkspace = $backendUser->workspace;
        if ($currentWorkspace === 0) {
            // LIVE workspace has no stages
            return [];
        }

        $allowedStages = [];
        foreach ($stages as $stage) {
            if ($stage->isAllowed) {
                // Only use stages that are allowed for current backend user
                try {
                    $previousStage = $this->getPreviousStage($stages, $stage->uid);
                    if (!isset($allowedStages[$previousStage->uid])) {
                        // Add previous stage, even if they are not allowed: Users can send records
                        // TO this stage, but not away from it.
                        $allowedStages[$previousStage->uid] = $previousStage;
                    }
                } catch (WorkspaceStageNotFoundException) {
                    // Don't add previous stage if there is none
                }
                if (!isset($allowedStages[$stage->uid])) {
                    // Add if not already added
                    $allowedStages[$stage->uid] = $stage;
                }
                try {
                    $nextStage = $this->getNextStage($stages, $stage->uid);
                    if (!isset($allowedStages[$nextStage->uid])) {
                        // Add next stage, even if they are not allowed: Users can send records
                        // TO this stage, but not away from it.
                        $allowedStages[$nextStage->uid] = $nextStage;
                    }
                } catch (WorkspaceStageNotFoundException) {
                    // Don't add next stage if there is none
                }
            }
        }
        return array_values($allowedStages);
    }

    /**
     * Gets the title of a stage.
     * Used by hooks that don't know Workspace and WorkspaceStage data objects.
     * Use WorkspaceStage->title in workspace BE module context.
     */
    public function getStageTitle(int $stageId): string
    {
        $languageService = $this->getLanguageService();
        switch ($stageId) {
            case self::STAGE_PUBLISH_EXECUTE_ID:
                return $languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_publish');
            case self::STAGE_PUBLISH_ID:
                return $languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_ready_to_publish');
            case self::STAGE_EDIT_ID:
                return $languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_editing');
            default:
                $workspaceStage = BackendUtility::getRecord('sys_workspace_stage', $stageId);
                if (is_array($workspaceStage) && isset($workspaceStage['title'])) {
                    return $workspaceStage['title'];
                }
                return $languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.getStageTitle.stageNotFound');
        }
    }

    /**
     * Gets all backend user records that are considered to be responsible
     * for a particular stage or workspace.
     *
     * @return array be_users with e-mail and name
     */
    public function getResponsibleBeUser(WorkspaceStage $stageRecord): array
    {
        $backendUserIds = $stageRecord->allRecipients;
        $userRecords = $this->getBackendUsers($backendUserIds);
        $recipientArray = [];
        foreach ($userRecords as $userUid => $userRecord) {
            $recipientArray[$userUid] = $userRecord;
        }
        return $recipientArray;
    }

    /**
     * Resolves backend user ids from a mixed list of backend users
     * and backend user groups (e.g. "be_users_1,be_groups_3,be_users_4,...")
     *
     * @return int[]
     */
    public function resolveBackendUserIds(string $backendUserGroupList): array
    {
        $elements = GeneralUtility::trimExplode(',', $backendUserGroupList, true);
        // Unique values to prevent calculating members of the same group multiple times
        $elements = array_unique($elements);
        $backendUserIds = [];
        $backendGroupIds = [];
        foreach ($elements as $element) {
            if (str_starts_with($element, 'be_users_')) {
                // Current value is a uid of a be_user record
                $backendUserIds[] = (int)str_replace('be_users_', '', $element);
            } elseif (str_starts_with($element, 'be_groups_')) {
                $backendGroupIds[] = (int)str_replace('be_groups_', '', $element);
            } elseif ((int)$element) {
                $backendUserIds[] = (int)$element;
            }
        }
        if (!empty($backendGroupIds)) {
            $backendUsersInGroups = $this->groupResolver->findAllUsersInGroups($backendGroupIds, 'be_groups', 'be_users');
            foreach ($backendUsersInGroups as $backendUsers) {
                $backendUserIds[] = (int)$backendUsers['uid'];
            }
        }
        return array_unique($backendUserIds);
    }

    /**
     * Gets backend user records from a given list of ids.
     */
    public function getBackendUsers(array $backendUserIds): array
    {
        if ($backendUserIds === []) {
            return [];
        }
        $backendUserList = implode(',', GeneralUtility::intExplode(',', implode(',', $backendUserIds)));
        return BackendUtility::getUserNames(
            'username, uid, email, realName, lang, uc',
            'AND uid IN (' . $backendUserList . ')' . BackendUtility::BEenableFields('be_users')
        );
    }

    /**
     * @param WorkspaceStage[] $stages
     */
    public function getPositionOfCurrentStage(array $stages, int $stageId): int
    {
        $position = 1;
        foreach ($stages as $stage) {
            if ($stage->uid === $stageId) {
                return $position;
            }
            $position++;
        }
        throw new \RuntimeException('Stage not found in stage list', 1752334655);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
