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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Domain\Record\StageRecord;
use TYPO3\CMS\Workspaces\Domain\Record\WorkspaceRecord;

/**
 * @internal
 */
class StagesService implements SingletonInterface
{
    // if a record is in the "ready to publish" stage STAGE_PUBLISH_ID the nextStage is STAGE_PUBLISH_EXECUTE_ID, this id wont be saved at any time in db
    public const STAGE_PUBLISH_EXECUTE_ID = -20;
    // ready to publish stage
    public const STAGE_PUBLISH_ID = -10;
    public const STAGE_EDIT_ID = 0;

    /**
     * Local cache to reduce number of database queries for stages, groups, etc.
     */
    protected array $workspaceStageCache = [];
    protected array $workspaceStageAllowedCache = [];

    /**
     * Find the highest possible "previous" stage for all $byTableName
     *
     * @return array Current and next highest possible stage
     */
    public function getPreviousStageForElementCollection(array $workspaceItems): array
    {
        $availableStagesForWS = array_reverse($this->getAllStagesOfWorkspace());
        $availableStagesForWSUser = $this->getStagesForWSUser();
        $usedStages = [];
        foreach ($workspaceItems as $tableName => $items) {
            if ($tableName !== 'pages' && $tableName !== 'tt_content') {
                continue;
            }
            foreach ($items as $item) {
                $usedStages[$item['t3ver_stage'] ?? 0] = true;
            }
        }
        $currentStage = [];
        $previousStage = [];
        foreach ($availableStagesForWS as $stage) {
            if (isset($usedStages[$stage['uid']])) {
                $currentStage = $stage;
                $previousStage = $this->getPrevStage($stage['uid']);
                break;
            }
        }
        $found = false;
        foreach ($availableStagesForWSUser as $userWS) {
            if ($previousStage && $previousStage['uid'] == $userWS['uid']) {
                $found = true;
                break;
            }
        }
        if ($found === false || !$this->isStageAllowedForUser($currentStage['uid'])) {
            $previousStage = [];
        }
        return [
            $currentStage,
            $previousStage,
        ];
    }

    /**
     * Retrieve the next stage based on the lowest stage given in the $workspaceItems record array.
     *
     * @return array Current and next possible stage.
     */
    public function getNextStageForElementCollection(array $workspaceItems): array
    {
        $availableStagesForWS = $this->getAllStagesOfWorkspace();
        $availableStagesForWSUser = $this->getStagesForWSUser();
        $usedStages = [];
        foreach ($workspaceItems as $tableName => $items) {
            if ($tableName !== 'pages' && $tableName !== 'tt_content') {
                continue;
            }
            foreach ($items as $item) {
                $usedStages[$item['t3ver_stage'] ?? 0] = true;
            }
        }
        $currentStage = [];
        $nextStage = [];
        foreach ($availableStagesForWS as $stage) {
            if (isset($usedStages[$stage['uid']])) {
                $currentStage = $stage;
                $nextStage = $this->getNextStage($stage['uid']);
                break;
            }
        }
        $found = false;
        foreach ($availableStagesForWSUser as $userWS) {
            if ($nextStage && $nextStage['uid'] == $userWS['uid']) {
                $found = true;
                break;
            }
        }
        if ($found === false || !$this->isStageAllowedForUser($currentStage['uid'])) {
            $nextStage = [];
        }
        return [
            $currentStage,
            $nextStage,
        ];
    }

    /**
     * Building an array with all stage ids and titles related to the given workspace
     *
     * @return array id and title of the stages
     */
    protected function getAllStagesOfWorkspace(): array
    {
        $currentWorkspace = $this->getBackendUser()->workspace;
        if ($currentWorkspace === 0) {
            return [];
        }
        if (isset($this->workspaceStageCache[$currentWorkspace])) {
            return $this->workspaceStageCache[$currentWorkspace];
        }
        $stages = $this->prepareStagesArray($this->getWorkspaceRecord()->getStages());
        $this->workspaceStageCache[$currentWorkspace] = $stages;
        return $stages;
    }

    /**
     * Returns an array of stages, the user is allowed to send to
     *
     * @return array id and title of stages
     */
    public function getStagesForWSUser(): array
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            return $this->getAllStagesOfWorkspace();
        }
        $currentWorkspace = $backendUser->workspace;
        // LIVE workspace has no stages
        if ($currentWorkspace === 0) {
            return [];
        }

        /** @var StageRecord[] $allowedStages */
        $allowedStages = [];
        $stageRecords = $this->getWorkspaceRecord()->getStages();

        // Only use stages that are allowed for current backend user
        foreach ($stageRecords as $stageRecord) {
            if ($stageRecord->isAllowed()) {
                $allowedStages[$stageRecord->getUid()] = $stageRecord;
            }
        }

        // Add previous and next stages (even if they are not allowed!)
        foreach ($allowedStages as $allowedStage) {
            $previousStage = $allowedStage->getPrevious();
            $nextStage = $allowedStage->getNext();
            if ($previousStage !== null && !isset($allowedStages[$previousStage->getUid()])) {
                $allowedStages[$previousStage->getUid()] = $previousStage;
            }
            if ($nextStage !== null && !isset($allowedStages[$nextStage->getUid()])) {
                $allowedStages[$nextStage->getUid()] = $nextStage;
            }
        }

        uasort($allowedStages, static fn(StageRecord $first, StageRecord $second): int => $first->determineOrder($second));
        return $this->prepareStagesArray($allowedStages);
    }

    /**
     * Prepares simplified stages array
     *
     * @param StageRecord[] $stageRecords
     */
    protected function prepareStagesArray(array $stageRecords): array
    {
        $stagesArray = [];
        foreach ($stageRecords as $stageRecord) {
            $stage = [
                'uid' => $stageRecord->getUid(),
                'label' => $stageRecord->getTitle(),
            ];
            if (!$stageRecord->isExecuteStage()) {
                $stage['title'] = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:actionSendToStage') . ' "' . $stageRecord->getTitle() . '"';
            } else {
                $stage['title'] = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:publish_execute_action_option');
            }
            $stagesArray[] = $stage;
        }
        return $stagesArray;
    }

    /**
     * Gets the title of a stage
     */
    public function getStageTitle(int $stageId): string
    {
        switch ($stageId) {
            case self::STAGE_PUBLISH_EXECUTE_ID:
                return $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_publish');
            case self::STAGE_PUBLISH_ID:
                return $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_ready_to_publish');
            case self::STAGE_EDIT_ID:
                return $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_editing');
            default:
                $workspaceStage = BackendUtility::getRecord('sys_workspace_stage', $stageId);
                if (is_array($workspaceStage) && isset($workspaceStage['title'])) {
                    return $workspaceStage['title'];
                }
                return $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.getStageTitle.stageNotFound');
        }
    }

    /**
     * Gets next stage in process for given stage id
     *
     * @param int $stageId Id of the stage to fetch the next one for
     * @return array The next stage (id + details)
     */
    public function getNextStage(int $stageId): array
    {
        $nextStage = false;
        $workspaceStageRecs = $this->getAllStagesOfWorkspace();
        if ($workspaceStageRecs !== []) {
            reset($workspaceStageRecs);
            while (key($workspaceStageRecs) !== null) {
                $workspaceStageRec = current($workspaceStageRecs);
                if ($workspaceStageRec['uid'] == $stageId) {
                    $nextStage = next($workspaceStageRecs);
                    break;
                }
                next($workspaceStageRecs);
            }
        }
        if ($nextStage === false) {
            $nextStage = [
                [
                    'uid' => self::STAGE_EDIT_ID,
                    'title' => $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:actionSendToStage') . ' "'
                        . $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_editing') . '"',
                ],
            ];
        }
        return $nextStage;
    }

    /**
     * Get next stage in process for given stage id
     *
     * @param int $stageId Id of the stage to fetch the previous one for
     * @return false|array The previous stage or false
     */
    public function getPrevStage(int $stageId): false|array
    {
        $prevStage = false;
        $workspaceStageRecs = $this->getAllStagesOfWorkspace();
        if ($workspaceStageRecs !== []) {
            end($workspaceStageRecs);
            while (key($workspaceStageRecs) !== null) {
                $workspaceStageRec = current($workspaceStageRecs);
                if ($workspaceStageRec['uid'] == $stageId) {
                    $prevStage = prev($workspaceStageRecs);
                    break;
                }
                prev($workspaceStageRecs);
            }
        }

        return $prevStage;
    }

    /**
     * Gets all backend user records that are considered to be responsible
     * for a particular stage or workspace.
     *
     * @param bool $selectDefaultUserField If field notification_defaults should be selected instead of responsible users
     * @return array be_users with e-mail and name
     */
    public function getResponsibleBeUser(StageRecord|int $stageRecord, bool $selectDefaultUserField = false): array
    {
        if (!$stageRecord instanceof StageRecord) {
            $stageRecord = $this->getWorkspaceRecord()->getStage($stageRecord);
        }

        if (!$selectDefaultUserField) {
            $backendUserIds = $stageRecord->getAllRecipients();
        } else {
            $backendUserIds = $stageRecord->getDefaultRecipients();
        }

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
     */
    public function resolveBackendUserIds(string $backendUserGroupList): array
    {
        $elements = GeneralUtility::trimExplode(',', $backendUserGroupList, true);
        $backendUserIds = [];
        $backendGroupIds = [];

        foreach ($elements as $element) {
            if (str_starts_with($element, 'be_users_')) {
                // Current value is a uid of a be_user record
                $backendUserIds[] = str_replace('be_users_', '', $element);
            } elseif (str_starts_with($element, 'be_groups_')) {
                $backendGroupIds[] = (int)str_replace('be_groups_', '', $element);
            } elseif ((int)$element) {
                $backendUserIds[] = (int)$element;
            }
        }

        if (!empty($backendGroupIds)) {
            $groupResolver = GeneralUtility::makeInstance(GroupResolver::class);
            $backendUsersInGroups = $groupResolver->findAllUsersInGroups($backendGroupIds, 'be_groups', 'be_users');
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

    public function getPreselectedRecipients(StageRecord $stageRecord): array
    {
        return $stageRecord->getPreselectedRecipients();
    }

    protected function getWorkspaceRecord(): WorkspaceRecord
    {
        return WorkspaceRecord::get($this->getBackendUser()->workspace);
    }

    /**
     * Gets the position of the given workspace in the hole process
     * f.e. 3 means step 3 of 20, by which 1 is edit and 20 is ready to publish
     *
     * @return array position => 3, count => 20
     */
    public function getPositionOfCurrentStage(int $stageId): array
    {
        $stagesOfWS = $this->getAllStagesOfWorkspace();
        $countOfStages = count($stagesOfWS);
        switch ($stageId) {
            case self::STAGE_PUBLISH_ID:
                $position = $countOfStages;
                break;
            case self::STAGE_EDIT_ID:
                $position = 1;
                break;
            default:
                $position = 1;
                foreach ($stagesOfWS as $stageInfoArray) {
                    $position++;
                    if ($stageId === (int)$stageInfoArray['uid']) {
                        break;
                    }
                }
        }
        return ['position' => $position, 'count' => $countOfStages];
    }

    /**
     * Check if the user has access to the previous stage, relative to the given stage
     */
    public function isPrevStageAllowedForUser(int $stageId): bool
    {
        $isAllowed = false;
        try {
            $prevStage = $this->getPrevStage($stageId);
            // if there's no prev-stage the stageIds match,
            // otherwise we've to check if the user is permitted to use the stage
            if (!empty($prevStage) && $prevStage['uid'] != $stageId) {
                // if the current stage is allowed for the user, the user is also allowed to send to prev
                $isAllowed = $this->isStageAllowedForUser($stageId);
            }
        } catch (\Exception $e) {
        }
        return $isAllowed;
    }

    /**
     * Check if the user has access to the next stage, relative to the given stage
     */
    public function isNextStageAllowedForUser(int $stageId): bool
    {
        $isAllowed = false;
        try {
            $nextStage = $this->getNextStage($stageId);
            // if there's no next-stage the stageIds match,
            // otherwise we've to check if the user is permitted to use the stage
            if (!empty($nextStage) && $nextStage['uid'] != $stageId) {
                // if the current stage is allowed for the user, the user is also allowed to send to next
                $isAllowed = $this->isStageAllowedForUser($stageId);
            }
        } catch (\Exception $e) {
        }
        return $isAllowed;
    }

    protected function isStageAllowedForUser(int $stageId): bool
    {
        $cacheKey = $this->getBackendUser()->workspace . '_' . $stageId;
        if (isset($this->workspaceStageAllowedCache[$cacheKey])) {
            return $this->workspaceStageAllowedCache[$cacheKey];
        }
        $isAllowed = $this->getBackendUser()->workspaceCheckStageForCurrent($stageId);
        $this->workspaceStageAllowedCache[$cacheKey] = $isAllowed;
        return $isAllowed;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
