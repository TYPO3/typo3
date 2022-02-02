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

namespace TYPO3\CMS\Workspaces\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Workspaces\Domain\Record\StageRecord;
use TYPO3\CMS\Workspaces\Domain\Record\WorkspaceRecord;

/**
 * Stages service
 */
class StagesService implements SingletonInterface
{
    const TABLE_STAGE = 'sys_workspace_stage';
    // if a record is in the "ready to publish" stage STAGE_PUBLISH_ID the nextStage is STAGE_PUBLISH_EXECUTE_ID, this id wont be saved at any time in db
    const STAGE_PUBLISH_EXECUTE_ID = -20;
    // ready to publish stage
    const STAGE_PUBLISH_ID = -10;
    const STAGE_EDIT_ID = 0;

    /**
     * Path to the locallang file
     *
     * @var string
     */
    private $pathToLocallang = 'LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf';

    protected ?RecordService $recordService;

    /**
     * Local cache to reduce number of database queries for stages, groups, etc.
     *
     * @var array
     */
    protected $workspaceStageCache = [];

    /**
     * @var array
     */
    protected $workspaceStageAllowedCache = [];

    /**
     * Getter for current workspace id
     *
     * @return int Current workspace id
     */
    public function getWorkspaceId()
    {
        return $this->getBackendUser()->workspace;
    }

    /**
     * Find the highest possible "previous" stage for all $byTableName
     *
     * @param array $workspaceItems
     * @param array $byTableName
     * @return array Current and next highest possible stage
     */
    public function getPreviousStageForElementCollection(
        $workspaceItems,
        array $byTableName = ['tt_content', 'pages']
    ) {
        $currentStage = [];
        $previousStage = [];
        $usedStages = [];
        $found = false;
        $availableStagesForWS = array_reverse($this->getStagesForWS());
        $availableStagesForWSUser = $this->getStagesForWSUser();
        $byTableName = array_flip($byTableName);
        foreach ($workspaceItems as $tableName => $items) {
            if (!array_key_exists($tableName, $byTableName)) {
                continue;
            }
            foreach ($items as $item) {
                $usedStages[$item['t3ver_stage'] ?? 0] = true;
            }
        }
        foreach ($availableStagesForWS as $stage) {
            if (isset($usedStages[$stage['uid']])) {
                $currentStage = $stage;
                $previousStage = $this->getPrevStage($stage['uid']);
                break;
            }
        }
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
     * @param array $workspaceItems
     * @param array $byTableName
     * @return array Current and next possible stage.
     */
    public function getNextStageForElementCollection(
        $workspaceItems,
        array $byTableName = ['tt_content', 'pages']
    ) {
        $currentStage = [];
        $usedStages = [];
        $nextStage = [];
        $availableStagesForWS = $this->getStagesForWS();
        $availableStagesForWSUser = $this->getStagesForWSUser();
        $byTableName = array_flip($byTableName);
        $found = false;
        foreach ($workspaceItems as $tableName => $items) {
            if (!array_key_exists($tableName, $byTableName)) {
                continue;
            }
            foreach ($items as $item) {
                $usedStages[$item['t3ver_stage'] ?? 0] = true;
            }
        }
        foreach ($availableStagesForWS as $stage) {
            if (isset($usedStages[$stage['uid']])) {
                $currentStage = $stage;
                $nextStage = $this->getNextStage($stage['uid']);
                break;
            }
        }
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
    public function getStagesForWS()
    {
        if (isset($this->workspaceStageCache[$this->getWorkspaceId()])) {
            $stages = $this->workspaceStageCache[$this->getWorkspaceId()];
        } elseif ($this->getWorkspaceId() === 0) {
            $stages = [];
        } else {
            $stages = $this->prepareStagesArray($this->getWorkspaceRecord()->getStages());
            $this->workspaceStageCache[$this->getWorkspaceId()] = $stages;
        }
        return $stages;
    }

    /**
     * Returns an array of stages, the user is allowed to send to
     *
     * @return array id and title of stages
     */
    public function getStagesForWSUser()
    {
        if ($this->getBackendUser()->isAdmin()) {
            return $this->getStagesForWS();
        }
        // The LIVE workspace has no stages
        if ($this->getWorkspaceId() === 0) {
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

        uasort($allowedStages, static function (StageRecord $first, StageRecord $second) {
            return $first->determineOrder($second);
        });
        return $this->prepareStagesArray($allowedStages);
    }

    /**
     * Prepares simplified stages array
     *
     * @param StageRecord[] $stageRecords
     * @return array
     */
    protected function prepareStagesArray(array $stageRecords)
    {
        $stagesArray = [];
        foreach ($stageRecords as $stageRecord) {
            $stage = [
                'uid' => $stageRecord->getUid(),
                'label' => $stageRecord->getTitle(),
            ];
            if (!$stageRecord->isExecuteStage()) {
                $stage['title'] = $this->getLanguageService()->sL($this->pathToLocallang . ':actionSendToStage') . ' "' . $stageRecord->getTitle() . '"';
            } else {
                $stage['title'] = $this->getLanguageService()->sL($this->pathToLocallang . ':publish_execute_action_option');
            }
            $stagesArray[] = $stage;
        }
        return $stagesArray;
    }

    /**
     * Gets the title of a stage.
     *
     * @param int $ver_stage
     * @return string
     */
    public function getStageTitle($ver_stage)
    {
        switch ($ver_stage) {
            case self::STAGE_PUBLISH_EXECUTE_ID:
                $stageTitle = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod_user_ws.xlf:stage_publish');
                break;
            case self::STAGE_PUBLISH_ID:
                $stageTitle = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stage_ready_to_publish');
                break;
            case self::STAGE_EDIT_ID:
                $stageTitle = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod_user_ws.xlf:stage_editing');
                break;
            default:
                $stageTitle = $this->getPropertyOfCurrentWorkspaceStage($ver_stage, 'title');
                if ($stageTitle == null) {
                    $stageTitle = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.getStageTitle.stageNotFound');
                }
        }
        return $stageTitle;
    }

    /**
     * Gets a particular stage record.
     *
     * @param int $stageid
     * @return array|null
     */
    public function getStageRecord($stageid)
    {
        return BackendUtility::getRecord('sys_workspace_stage', $stageid);
    }

    /**
     * Gets next stage in process for given stage id
     *
     * @param int $stageId Id of the stage to fetch the next one for
     * @return array The next stage (id + details)
     * @throws \InvalidArgumentException
     */
    public function getNextStage($stageId)
    {
        if (!MathUtility::canBeInterpretedAsInteger($stageId)) {
            throw new \InvalidArgumentException(
                $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.stageId.integer'),
                1291109987
            );
        }
        $nextStage = false;
        $workspaceStageRecs = $this->getStagesForWS();
        if (is_array($workspaceStageRecs) && !empty($workspaceStageRecs)) {
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
                    'title' => $this->getLanguageService()->sL($this->pathToLocallang . ':actionSendToStage') . ' "'
                        . $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod_user_ws.xlf:stage_editing') . '"',
                ],
            ];
        }
        return $nextStage;
    }

    /**
     * Recursive function to get all next stages for a record depending on user permissions
     *
     * @param array $nextStageArray Next stages
     * @param int $stageId Current stage id of the record
     * @return array Next stages
     */
    public function getNextStages(array &$nextStageArray, $stageId)
    {
        // Current stage is "Ready to publish" - there is no next stage
        if ($stageId == self::STAGE_PUBLISH_ID) {
            return $nextStageArray;
        }
        $nextStageRecord = $this->getNextStage($stageId);
        if (empty($nextStageRecord) || !is_array($nextStageRecord)) {
            // There is no next stage
            return $nextStageArray;
        }
        // Check if the user has the permission to for the current stage
        // If this next stage record is the first next stage after the current the user
        // has always the needed permission
        if ($this->isStageAllowedForUser($stageId)) {
            $nextStageArray[] = $nextStageRecord;
            return $this->getNextStages($nextStageArray, $nextStageRecord['uid']);
        }
        // He hasn't - return given next stage array
        return $nextStageArray;
    }

    /**
     * Get next stage in process for given stage id
     *
     * @param int $stageId Id of the stage to fetch the previous one for
     * @return bool|array The previous stage or false
     * @throws \InvalidArgumentException
     */
    public function getPrevStage($stageId)
    {
        if (!MathUtility::canBeInterpretedAsInteger($stageId)) {
            throw new \InvalidArgumentException(
                $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.stageId.integer'),
                1476048351
            );
        }
        $prevStage = false;
        $workspaceStageRecs = $this->getStagesForWS();
        if (is_array($workspaceStageRecs) && !empty($workspaceStageRecs)) {
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
     * Recursive function to get all prev stages for a record depending on user permissions
     *
     * @param array	$prevStageArray Prev stages
     * @param int $stageId Current stage id of the record
     * @return array prev stages
     */
    public function getPrevStages(array &$prevStageArray, $stageId)
    {
        // Current stage is "Editing" - there is no prev stage
        if ($stageId != self::STAGE_EDIT_ID) {
            $prevStageRecord = $this->getPrevStage($stageId);
            if (!empty($prevStageRecord) && is_array($prevStageRecord)) {
                // Check if the user has the permission to switch to that stage
                // If this prev stage record is the first previous stage before the current
                // the user has always the needed permission
                if ($this->isStageAllowedForUser($stageId)) {
                    $prevStageArray[] = $prevStageRecord;
                    $prevStageArray = $this->getPrevStages($prevStageArray, $prevStageRecord['uid']);
                }
            }
        }
        return $prevStageArray;
    }

    /**
     * Gets all backend user records that are considered to be responsible
     * for a particular stage or workspace.
     *
     * @param StageRecord|int $stageRecord Stage
     * @param bool $selectDefaultUserField If field notification_defaults should be selected instead of responsible users
     * @return array be_users with e-mail and name
     */
    public function getResponsibleBeUser($stageRecord, $selectDefaultUserField = false)
    {
        if (!$stageRecord instanceof StageRecord) {
            $stageRecord = $this->getWorkspaceRecord()->getStage($stageRecord);
        }

        $recipientArray = [];

        if (!$selectDefaultUserField) {
            $backendUserIds = $stageRecord->getAllRecipients();
        } else {
            $backendUserIds = $stageRecord->getDefaultRecipients();
        }

        $userList = implode(',', $backendUserIds);
        $userRecords = $this->getBackendUsers($userList);
        foreach ($userRecords as $userUid => $userRecord) {
            $recipientArray[$userUid] = $userRecord;
        }
        return $recipientArray;
    }

    /**
     * Resolves backend user ids from a mixed list of backend users
     * and backend user groups (e.g. "be_users_1,be_groups_3,be_users_4,...")
     *
     * @param string $backendUserGroupList
     * @return array
     */
    public function resolveBackendUserIds($backendUserGroupList)
    {
        $elements = GeneralUtility::trimExplode(',', $backendUserGroupList, true);
        $backendUserIds = [];
        $backendGroupIds = [];

        foreach ($elements as $element) {
            if (strpos($element, 'be_users_') === 0) {
                // Current value is a uid of a be_user record
                $backendUserIds[] = str_replace('be_users_', '', $element);
            } elseif (strpos($element, 'be_groups_') === 0) {
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
     *
     * @param string $backendUserList
     * @return array
     */
    public function getBackendUsers($backendUserList)
    {
        if (empty($backendUserList)) {
            return [];
        }

        $backendUserList = implode(',', GeneralUtility::intExplode(',', $backendUserList));
        $backendUsers = BackendUtility::getUserNames(
            'username, uid, email, realName, lang, uc',
            'AND uid IN (' . $backendUserList . ')' . BackendUtility::BEenableFields('be_users')
        );

        if (empty($backendUsers)) {
            $backendUsers = [];
        }
        return $backendUsers;
    }

    /**
     * @param StageRecord $stageRecord
     * @return array
     */
    public function getPreselectedRecipients(StageRecord $stageRecord)
    {
        if ($stageRecord->areEditorsPreselected()) {
            return array_merge(
                $stageRecord->getPreselectedRecipients(),
                $this->getRecordService()->getCreateUserIds()
            );
        }
        return $stageRecord->getPreselectedRecipients();
    }

    /**
     * @return WorkspaceRecord
     */
    protected function getWorkspaceRecord()
    {
        return WorkspaceRecord::get($this->getWorkspaceId());
    }

    /**
     * Gets a property of a workspaces stage.
     *
     * @param int $stageId
     * @param string $property
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getPropertyOfCurrentWorkspaceStage($stageId, $property)
    {
        $result = null;
        if (!MathUtility::canBeInterpretedAsInteger($stageId)) {
            throw new \InvalidArgumentException(
                $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.stageId.integer'),
                1476048371
            );
        }
        $workspaceStage = BackendUtility::getRecord(self::TABLE_STAGE, $stageId);
        if (is_array($workspaceStage) && isset($workspaceStage[$property])) {
            $result = $workspaceStage[$property];
        }
        return $result;
    }

    /**
     * Gets the position of the given workspace in the hole process
     * f.e. 3 means step 3 of 20, by which 1 is edit and 20 is ready to publish
     *
     * @param int $stageId
     * @return array position => 3, count => 20
     */
    public function getPositionOfCurrentStage($stageId)
    {
        $stagesOfWS = $this->getStagesForWS();
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
                foreach ($stagesOfWS as $key => $stageInfoArray) {
                    $position++;
                    if ($stageId == $stageInfoArray['uid']) {
                        break;
                    }
                }
        }
        return ['position' => $position, 'count' => $countOfStages];
    }

    /**
     * Check if the user has access to the previous stage, relative to the given stage
     *
     * @param int $stageId
     * @return bool
     */
    public function isPrevStageAllowedForUser($stageId)
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
     *
     * @param int $stageId
     * @return bool
     */
    public function isNextStageAllowedForUser($stageId)
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

    /**
     * @param int $stageId
     * @return bool
     */
    protected function isStageAllowedForUser($stageId)
    {
        $cacheKey = $this->getWorkspaceId() . '_' . $stageId;
        if (isset($this->workspaceStageAllowedCache[$cacheKey])) {
            return $this->workspaceStageAllowedCache[$cacheKey];
        }
        $isAllowed = $this->getBackendUser()->workspaceCheckStageForCurrent($stageId);
        $this->workspaceStageAllowedCache[$cacheKey] = $isAllowed;
        return $isAllowed;
    }

    /**
     * Determines whether a stage Id is valid.
     *
     * @param int $stageId The stage Id to be checked
     * @return bool
     */
    public function isValid($stageId)
    {
        $isValid = false;
        $stages = $this->getStagesForWS();
        foreach ($stages as $stage) {
            if ($stage['uid'] == $stageId) {
                $isValid = true;
                break;
            }
        }
        return $isValid;
    }

    /**
     * @return RecordService
     */
    public function getRecordService()
    {
        if (!isset($this->recordService)) {
            $this->recordService = GeneralUtility::makeInstance(RecordService::class);
        }
        return $this->recordService;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService|null
     */
    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
