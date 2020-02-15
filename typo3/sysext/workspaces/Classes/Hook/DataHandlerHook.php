<?php
namespace TYPO3\CMS\Workspaces\Hook;

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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\PlaceholderShadowColumnsResolver;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SysLog\Action as SystemLogGenericAction;
use TYPO3\CMS\Core\SysLog\Action\Database as DatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\DataHandler\CommandMap;
use TYPO3\CMS\Workspaces\Notification\StageChangeNotification;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Contains some parts for staging, versioning and workspaces
 * to interact with the TYPO3 Core Engine
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class DataHandlerHook
{
    /**
     * For accumulating information about workspace stages raised
     * on elements so a single mail is sent as notification.
     *
     * @var array
     */
    protected $notificationEmailInfo = [];

    /**
     * Contains remapped IDs.
     *
     * @var array
     */
    protected $remappedIds = [];

    /****************************
     *****  Cmdmap  Hooks  ******
     ****************************/
    /**
     * hook that is called before any cmd of the commandmap is executed
     *
     * @param DataHandler $dataHandler reference to the main DataHandler object
     */
    public function processCmdmap_beforeStart(DataHandler $dataHandler)
    {
        // Reset notification array
        $this->notificationEmailInfo = [];
        // Resolve dependencies of version/workspaces actions:
        $dataHandler->cmdmap = $this->getCommandMap($dataHandler)->process()->get();
    }

    /**
     * hook that is called when no prepared command was found
     *
     * @param string $command the command to be executed
     * @param string $table the table of the record
     * @param int $id the ID of the record
     * @param mixed $value the value containing the data
     * @param bool $commandIsProcessed can be set so that other hooks or
     * @param DataHandler $dataHandler reference to the main DataHandler object
     */
    public function processCmdmap($command, $table, $id, $value, &$commandIsProcessed, DataHandler $dataHandler)
    {
        // custom command "version"
        if ($command !== 'version') {
            return;
        }
        $commandIsProcessed = true;
        $action = (string)$value['action'];
        $comment = $value['comment'] ?: '';
        $notificationAlternativeRecipients = $value['notificationAlternativeRecipients'] ?? [];
        switch ($action) {
            case 'new':
                $dataHandler->versionizeRecord($table, $id, $value['label']);
                break;
            case 'swap':
                $this->version_swap(
                    $table,
                    $id,
                    $value['swapWith'],
                    (bool)$value['swapIntoWS'],
                    $dataHandler,
                    $comment,
                    $notificationAlternativeRecipients
                );
                break;
            case 'clearWSID':
                $this->version_clearWSID($table, (int)$id, false, $dataHandler);
                break;
            case 'flush':
                $this->version_clearWSID($table, (int)$id, true, $dataHandler);
                break;
            case 'setStage':
                $elementIds = GeneralUtility::trimExplode(',', $id, true);
                foreach ($elementIds as $elementId) {
                    $this->version_setStage(
                        $table,
                        $elementId,
                        $value['stageId'],
                        $comment,
                        $dataHandler,
                        $notificationAlternativeRecipients
                    );
                }
                break;
            default:
                // Do nothing
        }
    }

    /**
     * hook that is called AFTER all commands of the commandmap was
     * executed
     *
     * @param DataHandler $dataHandler reference to the main DataHandler object
     */
    public function processCmdmap_afterFinish(DataHandler $dataHandler)
    {
        // Empty accumulation array
        $emailNotificationService = GeneralUtility::makeInstance(StageChangeNotification::class);
        $this->sendStageChangeNotification(
            $this->notificationEmailInfo,
            $emailNotificationService,
            $dataHandler
        );

        // Reset notification array
        $this->notificationEmailInfo = [];
        // Reset remapped IDs
        $this->remappedIds = [];

        $this->flushWorkspaceCacheEntriesByWorkspaceId((int)$dataHandler->BE_USER->workspace);
    }

    protected function sendStageChangeNotification(
        array $accumulatedNotificationInformation,
        StageChangeNotification $notificationService,
        DataHandler $dataHandler
    ): void {
        foreach ($accumulatedNotificationInformation as $groupedNotificationInformation) {
            $emails = (array)$groupedNotificationInformation['recipients'];
            if (empty($emails)) {
                continue;
            }
            $workspaceRec = BackendUtility::getRecord('sys_workspace', $groupedNotificationInformation['shared'][0]);
            if (!is_array($workspaceRec)) {
                continue;
            }
            $notificationService->notifyStageChange(
                $workspaceRec,
                (int)$groupedNotificationInformation['shared'][1],
                $groupedNotificationInformation['elements'],
                $groupedNotificationInformation['shared'][2],
                $emails,
                $dataHandler->BE_USER
            );

            if ($dataHandler->enableLogging) {
                [$elementTable, $elementUid] = reset($groupedNotificationInformation['elements']);
                $propertyArray = $dataHandler->getRecordProperties($elementTable, $elementUid);
                $pid = $propertyArray['pid'];
                $dataHandler->log($elementTable, $elementUid, SystemLogGenericAction::UNDEFINED, 0, SystemLogErrorClassification::MESSAGE, 'Notification email for stage change was sent to "' . implode('", "', $emails) . '"', -1, [], $dataHandler->eventPid($elementTable, $elementUid, $pid));
            }
        }
    }

    /**
     * hook that is called when an element shall get deleted
     *
     * @param string $table the table of the record
     * @param int $id the ID of the record
     * @param array $record The accordant database record
     * @param bool $recordWasDeleted can be set so that other hooks or
     * @param DataHandler $dataHandler reference to the main DataHandler object
     */
    public function processCmdmap_deleteAction($table, $id, array $record, &$recordWasDeleted, DataHandler $dataHandler)
    {
        // only process the hook if it wasn't processed
        // by someone else before
        if ($recordWasDeleted) {
            return;
        }
        $recordWasDeleted = true;
        // For Live version, try if there is a workspace version because if so, rather "delete" that instead
        // Look, if record is an offline version, then delete directly:
        if ((int)($record['t3ver_oid'] ?? 0) === 0) {
            if ($wsVersion = BackendUtility::getWorkspaceVersionOfRecord($dataHandler->BE_USER->workspace, $table, $id)) {
                $record = $wsVersion;
                $id = $record['uid'];
            }
        }
        $recordVersionState = VersionState::cast($record['t3ver_state']);
        // Look, if record is an offline version, then delete directly:
        if ((int)($record['t3ver_oid'] ?? 0) > 0) {
            if (BackendUtility::isTableWorkspaceEnabled($table)) {
                // In Live workspace, delete any. In other workspaces there must be match.
                if ($dataHandler->BE_USER->workspace == 0 || (int)$record['t3ver_wsid'] == $dataHandler->BE_USER->workspace) {
                    $liveRec = BackendUtility::getLiveVersionOfRecord($table, $id, 'uid,t3ver_state');
                    // Processing can be skipped if a delete placeholder shall be swapped/published
                    // during the current request. Thus it will be deleted later on...
                    $liveRecordVersionState = VersionState::cast($liveRec['t3ver_state']);
                    if ($recordVersionState->equals(VersionState::DELETE_PLACEHOLDER) && !empty($liveRec['uid'])
                        && !empty($dataHandler->cmdmap[$table][$liveRec['uid']]['version']['action'])
                        && !empty($dataHandler->cmdmap[$table][$liveRec['uid']]['version']['swapWith'])
                        && $dataHandler->cmdmap[$table][$liveRec['uid']]['version']['action'] === 'swap'
                        && $dataHandler->cmdmap[$table][$liveRec['uid']]['version']['swapWith'] == $id
                    ) {
                        return null;
                    }

                    if ($record['t3ver_wsid'] > 0 && $recordVersionState->equals(VersionState::DEFAULT_STATE)) {
                        // Change normal versioned record to delete placeholder
                        // Happens when an edited record is deleted
                        GeneralUtility::makeInstance(ConnectionPool::class)
                            ->getConnectionForTable($table)
                            ->update(
                                $table,
                                ['t3ver_state' => VersionState::DELETE_PLACEHOLDER],
                                ['uid' => $id]
                            );

                        // Delete localization overlays:
                        $dataHandler->deleteL10nOverlayRecords($table, $id);
                    } elseif ($record['t3ver_wsid'] == 0 || !$liveRecordVersionState->indicatesPlaceholder()) {
                        // Delete those in WS 0 + if their live records state was not "Placeholder".
                        $dataHandler->deleteEl($table, $id);
                        // Delete move-placeholder if current version record is a move-to-pointer
                        if ($recordVersionState->equals(VersionState::MOVE_POINTER)) {
                            $movePlaceholder = BackendUtility::getMovePlaceholder($table, $liveRec['uid'], 'uid', $record['t3ver_wsid']);
                            if (!empty($movePlaceholder)) {
                                $dataHandler->deleteEl($table, $movePlaceholder['uid']);
                            }
                        }
                    } else {
                        // If live record was placeholder (new/deleted), rather clear
                        // it from workspace (because it clears both version and placeholder).
                        $this->version_clearWSID($table, (int)$id, false, $dataHandler);
                    }
                } else {
                    $dataHandler->newlog('Tried to delete record from another workspace', SystemLogErrorClassification::USER_ERROR);
                }
            } else {
                $dataHandler->newlog('Versioning not enabled for record with an online ID (t3ver_oid) given', SystemLogErrorClassification::SYSTEM_ERROR);
            }
        } elseif ($dataHandler->BE_USER->workspaceAllowsLiveEditingInTable($table)) {
            // Look, if record is "online" then delete directly.
            $dataHandler->deleteEl($table, $id);
        } elseif ($recordVersionState->equals(VersionState::MOVE_PLACEHOLDER)) {
            // Placeholders for moving operations are deletable directly.
            // Get record which its a placeholder for and reset the t3ver_state of that:
            if ($wsRec = BackendUtility::getWorkspaceVersionOfRecord($record['t3ver_wsid'], $table, $record['t3ver_move_id'], 'uid')) {
                // Clear the state flag of the workspace version of the record
                // Setting placeholder state value for version (so it can know it is currently a new version...)

                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable($table)
                    ->update(
                        $table,
                        [
                            't3ver_state' => (string)new VersionState(VersionState::DEFAULT_STATE)
                        ],
                        ['uid' => (int)$wsRec['uid']]
                    );
            }
            $dataHandler->deleteEl($table, $id);
        } else {
            // Otherwise, try to delete by versioning:
            $copyMappingArray = $dataHandler->copyMappingArray;
            $dataHandler->versionizeRecord($table, $id, 'DELETED!', true);
            // Determine newly created versions:
            // (remove placeholders are copied and modified, thus they appear in the copyMappingArray)
            $versionizedElements = ArrayUtility::arrayDiffAssocRecursive($dataHandler->copyMappingArray, $copyMappingArray);
            // Delete localization overlays:
            foreach ($versionizedElements as $versionizedTableName => $versionizedOriginalIds) {
                foreach ($versionizedOriginalIds as $versionizedOriginalId => $_) {
                    $dataHandler->deleteL10nOverlayRecords($versionizedTableName, $versionizedOriginalId);
                }
            }
        }
    }

    /**
     * In case a sys_workspace_stage record is deleted we do a hard reset
     * for all existing records in that stage to avoid that any of these end up
     * as orphan records.
     *
     * @param string $command
     * @param string $table
     * @param string $id
     * @param string $value
     * @param DataHandler $dataHandler
     */
    public function processCmdmap_postProcess($command, $table, $id, $value, DataHandler $dataHandler)
    {
        if ($command === 'delete') {
            if ($table === StagesService::TABLE_STAGE) {
                $this->resetStageOfElements((int)$id);
            } elseif ($table === WorkspaceService::TABLE_WORKSPACE) {
                $this->flushWorkspaceElements((int)$id);
            }
        }
    }

    /**
     * Hook for \TYPO3\CMS\Core\DataHandling\DataHandler::moveRecord that cares about
     * moving records that are *not* in the live workspace
     *
     * @param string $table the table of the record
     * @param int $uid the ID of the record
     * @param int $destPid Position to move to: $destPid: >=0 then it points to
     * @param array $propArr Record properties, like header and pid (includes workspace overlay)
     * @param array $moveRec Record properties, like header and pid (without workspace overlay)
     * @param int $resolvedPid The final page ID of the record
     * @param bool $recordWasMoved can be set so that other hooks or
     * @param DataHandler $dataHandler
     */
    public function moveRecord($table, $uid, $destPid, array $propArr, array $moveRec, $resolvedPid, &$recordWasMoved, DataHandler $dataHandler)
    {
        // Only do something in Draft workspace
        if ($dataHandler->BE_USER->workspace === 0) {
            return;
        }
        $tableSupportsVersioning = BackendUtility::isTableWorkspaceEnabled($table);
        if ($destPid < 0) {
            // Fetch move placeholder, since it might point to a new page in the current workspace
            $movePlaceHolder = BackendUtility::getMovePlaceholder($table, abs($destPid), 'uid,pid');
            if ($movePlaceHolder !== false) {
                $resolvedPid = $movePlaceHolder['pid'];
            }
        }
        $recordWasMoved = true;
        $moveRecVersionState = VersionState::cast($moveRec['t3ver_state']);
        // Get workspace version of the source record, if any:
        $workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($dataHandler->BE_USER->workspace, $table, $uid, 'uid,t3ver_oid');
        // Handle move-placeholders if the current record is not one already
        if (
            $tableSupportsVersioning
            && !$moveRecVersionState->equals(VersionState::MOVE_PLACEHOLDER)
        ) {
            // Create version of record first, if it does not exist
            if (empty($workspaceVersion['uid'])) {
                $dataHandler->versionizeRecord($table, $uid, 'MovePointer');
                $workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($dataHandler->BE_USER->workspace, $table, $uid, 'uid,t3ver_oid');
                if ((int)$resolvedPid !== (int)$propArr['pid']) {
                    $this->moveRecord_processFields($dataHandler, $resolvedPid, $table, $uid);
                }
            } elseif ($dataHandler->isRecordCopied($table, $uid) && (int)$dataHandler->copyMappingArray[$table][$uid] === (int)$workspaceVersion['uid']) {
                // If the record has been versioned before (e.g. cascaded parent-child structure), create only the move-placeholders
                if ((int)$resolvedPid !== (int)$propArr['pid']) {
                    $this->moveRecord_processFields($dataHandler, $resolvedPid, $table, $uid);
                }
            }
        }
        // Check workspace permissions:
        $workspaceAccessBlocked = [];
        // Element was in "New/Deleted/Moved" so it can be moved...
        $recIsNewVersion = $moveRecVersionState->indicatesPlaceholder();
        $recordMustNotBeVersionized = $dataHandler->BE_USER->workspaceAllowsLiveEditingInTable($table);
        $canMoveRecord = $recIsNewVersion || $tableSupportsVersioning;
        // Workspace source check:
        if (!$recIsNewVersion) {
            $errorCode = $dataHandler->BE_USER->workspaceCannotEditRecord($table, $workspaceVersion['uid'] ?: $uid);
            if ($errorCode) {
                $workspaceAccessBlocked['src1'] = 'Record could not be edited in workspace: ' . $errorCode . ' ';
            } elseif (!$canMoveRecord && !$recordMustNotBeVersionized) {
                $workspaceAccessBlocked['src2'] = 'Could not remove record from table "' . $table . '" from its page "' . $moveRec['pid'] . '" ';
            }
        }
        // Workspace destination check:
        // All records can be inserted if $recordMustNotBeVersionized is true.
        // Only new versions can be inserted if $recordMustNotBeVersionized is FALSE.
        if (!($recordMustNotBeVersionized || $canMoveRecord && !$recordMustNotBeVersionized)) {
            $workspaceAccessBlocked['dest1'] = 'Could not insert record from table "' . $table . '" in destination PID "' . $resolvedPid . '" ';
        }

        if (empty($workspaceAccessBlocked)) {
            // If the move operation is done on a versioned record, which is
            // NOT new/deleted placeholder, then also create a move placeholder
            if ($workspaceVersion['uid'] && !$recIsNewVersion && BackendUtility::isTableWorkspaceEnabled($table)) {
                $this->moveRecord_wsPlaceholders($table, (int)$uid, (int)$destPid, (int)$workspaceVersion['uid'], $dataHandler);
            } else {
                // moving not needed, just behave like in live workspace
                $recordWasMoved = false;
            }
        } else {
            $dataHandler->newlog('Move attempt failed due to workspace restrictions: ' . implode(' // ', $workspaceAccessBlocked), SystemLogErrorClassification::USER_ERROR);
        }
    }

    /**
     * Processes fields of a moved record and follows references.
     *
     * @param DataHandler $dataHandler Calling DataHandler instance
     * @param int $resolvedPageId Resolved real destination page id
     * @param string $table Name of parent table
     * @param int $uid UID of the parent record
     */
    protected function moveRecord_processFields(DataHandler $dataHandler, $resolvedPageId, $table, $uid)
    {
        $versionedRecord = BackendUtility::getWorkspaceVersionOfRecord($dataHandler->BE_USER->workspace, $table, $uid);
        if (empty($versionedRecord)) {
            return;
        }
        foreach ($versionedRecord as $field => $value) {
            if (empty($GLOBALS['TCA'][$table]['columns'][$field]['config'])) {
                continue;
            }
            $this->moveRecord_processFieldValue(
                $dataHandler,
                $resolvedPageId,
                $table,
                $uid,
                $value,
                $GLOBALS['TCA'][$table]['columns'][$field]['config']
            );
        }
    }

    /**
     * Processes a single field of a moved record and follows references.
     *
     * @param DataHandler $dataHandler Calling DataHandler instance
     * @param int $resolvedPageId Resolved real destination page id
     * @param string $table Name of parent table
     * @param int $uid UID of the parent record
     * @param string $value Value of the field of the parent record
     * @param array $configuration TCA field configuration of the parent record
     */
    protected function moveRecord_processFieldValue(DataHandler $dataHandler, $resolvedPageId, $table, $uid, $value, array $configuration): void
    {
        $inlineFieldType = $dataHandler->getInlineFieldType($configuration);
        $inlineProcessing = (
            ($inlineFieldType === 'list' || $inlineFieldType === 'field')
            && BackendUtility::isTableWorkspaceEnabled($configuration['foreign_table'])
            && (!isset($configuration['behaviour']['disableMovingChildrenWithParent']) || !$configuration['behaviour']['disableMovingChildrenWithParent'])
        );

        if ($inlineProcessing) {
            if ($table === 'pages') {
                // If the inline elements are related to a page record,
                // make sure they reside at that page and not at its parent
                $resolvedPageId = $uid;
            }

            $dbAnalysis = $this->createRelationHandlerInstance();
            $dbAnalysis->start($value, $configuration['foreign_table'], '', $uid, $table, $configuration);

            // Moving records to a positive destination will insert each
            // record at the beginning, thus the order is reversed here:
            foreach ($dbAnalysis->itemArray as $item) {
                $versionedRecord = BackendUtility::getWorkspaceVersionOfRecord($dataHandler->BE_USER->workspace, $item['table'], $item['id'], 'uid,t3ver_state');
                if (empty($versionedRecord) || VersionState::cast($versionedRecord['t3ver_state'])->indicatesPlaceholder()) {
                    continue;
                }
                $dataHandler->moveRecord($item['table'], $item['id'], $resolvedPageId);
            }
        }
    }

    /****************************
     *****  Stage Changes  ******
     ****************************/
    /**
     * Setting stage of record
     *
     * @param string $table Table name
     * @param int $id
     * @param int $stageId Stage ID to set
     * @param string $comment Comment that goes into log
     * @param DataHandler $dataHandler DataHandler object
     * @param array $notificationAlternativeRecipients comma separated list of recipients to notify instead of normal be_users
     */
    protected function version_setStage($table, $id, $stageId, string $comment, DataHandler $dataHandler, array $notificationAlternativeRecipients = [])
    {
        if ($errorCode = $dataHandler->BE_USER->workspaceCannotEditOfflineVersion($table, $id)) {
            $dataHandler->newlog('Attempt to set stage for record failed: ' . $errorCode, SystemLogErrorClassification::USER_ERROR);
        } elseif ($dataHandler->checkRecordUpdateAccess($table, $id)) {
            $record = BackendUtility::getRecord($table, $id);
            $workspaceInfo = $dataHandler->BE_USER->checkWorkspace($record['t3ver_wsid']);
            // check if the user is allowed to the current stage, so it's also allowed to send to next stage
            if ($dataHandler->BE_USER->workspaceCheckStageForCurrent($record['t3ver_stage'])) {
                // Set stage of record:
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable($table)
                    ->update(
                        $table,
                        [
                            't3ver_stage' => $stageId,
                        ],
                        ['uid' => (int)$id]
                    );

                if ($dataHandler->enableLogging) {
                    $propertyArray = $dataHandler->getRecordProperties($table, $id);
                    $pid = $propertyArray['pid'];
                    $dataHandler->log($table, $id, SystemLogGenericAction::UNDEFINED, 0, SystemLogErrorClassification::MESSAGE, 'Stage for record was changed to ' . $stageId . '. Comment was: "' . substr($comment, 0, 100) . '"', -1, [], $dataHandler->eventPid($table, $id, $pid));
                }
                // TEMPORARY, except 6-30 as action/detail number which is observed elsewhere!
                $dataHandler->log($table, $id, DatabaseAction::UPDATE, 0, SystemLogErrorClassification::MESSAGE, 'Stage raised...', 30, ['comment' => $comment, 'stage' => $stageId]);
                if ((int)$workspaceInfo['stagechg_notification'] > 0) {
                    $this->notificationEmailInfo[$workspaceInfo['uid'] . ':' . $stageId . ':' . $comment]['shared'] = [$workspaceInfo, $stageId, $comment];
                    $this->notificationEmailInfo[$workspaceInfo['uid'] . ':' . $stageId . ':' . $comment]['elements'][] = [$table, $id];
                    $this->notificationEmailInfo[$workspaceInfo['uid'] . ':' . $stageId . ':' . $comment]['recipients'] = $notificationAlternativeRecipients;
                }
            } else {
                $dataHandler->newlog('The member user tried to set a stage value "' . $stageId . '" that was not allowed', SystemLogErrorClassification::USER_ERROR);
            }
        } else {
            $dataHandler->newlog('Attempt to set stage for record failed because you do not have edit access', SystemLogErrorClassification::USER_ERROR);
        }
    }

    /*****************************
     *****  CMD versioning  ******
     *****************************/

    /**
     * Swapping versions of a record
     * Version from archive (future/past, called "swap version") will get the uid of the "t3ver_oid", the official element with uid = "t3ver_oid" will get the new versions old uid. PIDs are swapped also
     *
     * @param string $table Table name
     * @param int $id UID of the online record to swap
     * @param int $swapWith UID of the archived version to swap with!
     * @param bool $swapIntoWS If set, swaps online into workspace instead of publishing out of workspace.
     * @param DataHandler $dataHandler DataHandler object
     * @param string $comment Notification comment
     * @param array $notificationAlternativeRecipients comma separated list of recipients to notify instead of normal be_users
     */
    protected function version_swap($table, $id, $swapWith, bool $swapIntoWS, DataHandler $dataHandler, string $comment, $notificationAlternativeRecipients = [])
    {
        // Check prerequisites before start swapping

        // Skip records that have been deleted during the current execution
        if ($dataHandler->hasDeletedRecord($table, $id)) {
            return;
        }

        // First, check if we may actually edit the online record
        if (!$dataHandler->checkRecordUpdateAccess($table, $id)) {
            $dataHandler->newlog(
                sprintf(
                    'Error: You cannot swap versions for record %s:%d you do not have access to edit!',
                    $table,
                    $id
                ),
                SystemLogErrorClassification::USER_ERROR
            );
            return;
        }
        // Select the two versions:
        $curVersion = BackendUtility::getRecord($table, $id, '*');
        $swapVersion = BackendUtility::getRecord($table, $swapWith, '*');
        $movePlh = [];
        $movePlhID = 0;
        if (!(is_array($curVersion) && is_array($swapVersion))) {
            $dataHandler->newlog(
                sprintf(
                    'Error: Either online or swap version for %s:%d->%d could not be selected!',
                    $table,
                    $id,
                    $swapWith
                ),
                SystemLogErrorClassification::SYSTEM_ERROR
            );
            return;
        }
        if (!$dataHandler->BE_USER->workspacePublishAccess($swapVersion['t3ver_wsid'])) {
            $dataHandler->newlog('User could not publish records from workspace #' . $swapVersion['t3ver_wsid'], SystemLogErrorClassification::USER_ERROR);
            return;
        }
        $wsAccess = $dataHandler->BE_USER->checkWorkspace($swapVersion['t3ver_wsid']);
        if (!($swapVersion['t3ver_wsid'] <= 0 || !($wsAccess['publish_access'] & 1) || (int)$swapVersion['t3ver_stage'] === -10)) {
            $dataHandler->newlog('Records in workspace #' . $swapVersion['t3ver_wsid'] . ' can only be published when in "Publish" stage.', SystemLogErrorClassification::USER_ERROR);
            return;
        }
        if (!($dataHandler->doesRecordExist($table, $swapWith, Permission::PAGE_SHOW) && $dataHandler->checkRecordUpdateAccess($table, $swapWith))) {
            $dataHandler->newlog('You cannot publish a record you do not have edit and show permissions for', SystemLogErrorClassification::USER_ERROR);
            return;
        }
        if ($swapIntoWS && !$dataHandler->BE_USER->workspaceSwapAccess()) {
            $dataHandler->newlog('Workspace #' . $swapVersion['t3ver_wsid'] . ' does not support swapping.', SystemLogErrorClassification::USER_ERROR);
            return;
        }
        // Check if the swapWith record really IS a version of the original!
        if (!(((int)$swapVersion['t3ver_oid'] > 0 && (int)$curVersion['t3ver_oid'] === 0) && (int)$swapVersion['t3ver_oid'] === (int)$id)) {
            $dataHandler->newlog('In swap version, either t3ver_oid was not set or the t3ver_oid didn\'t match the id of the online version as it must!', SystemLogErrorClassification::SYSTEM_ERROR);
            return;
        }
        // Lock file name:
        $lockFileName = Environment::getVarPath() . '/lock/swap' . $table . '_' . $id . '.ser';
        if (@is_file($lockFileName)) {
            $dataHandler->newlog('A swapping lock file was present. Either another swap process is already running or a previous swap process failed. Ask your administrator to handle the situation.', SystemLogErrorClassification::SYSTEM_ERROR);
            return;
        }

        // Now start to swap records by first creating the lock file

        // Write lock-file:
        GeneralUtility::writeFileToTypo3tempDir($lockFileName, serialize([
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'user' => $dataHandler->BE_USER->user['username'],
            'curVersion' => $curVersion,
            'swapVersion' => $swapVersion
        ]));
        // Find fields to keep
        $keepFields = $this->getUniqueFields($table);
        if ($GLOBALS['TCA'][$table]['ctrl']['sortby']) {
            $keepFields[] = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
        }
        // l10n-fields must be kept otherwise the localization
        // will be lost during the publishing
        if ($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']) {
            $keepFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        }
        // Swap "keepfields"
        foreach ($keepFields as $fN) {
            $tmp = $swapVersion[$fN];
            $swapVersion[$fN] = $curVersion[$fN];
            $curVersion[$fN] = $tmp;
        }
        // Preserve states:
        $t3ver_state = [];
        $t3ver_state['swapVersion'] = $swapVersion['t3ver_state'];
        // Modify offline version to become online:
        $tmp_wsid = $swapVersion['t3ver_wsid'];
        // Set pid for ONLINE
        $swapVersion['pid'] = (int)$curVersion['pid'];
        // We clear this because t3ver_oid only make sense for offline versions
        // and we want to prevent unintentional misuse of this
        // value for online records.
        $swapVersion['t3ver_oid'] = 0;
        // In case of swapping and the offline record has a state
        // (like 2 or 4 for deleting or move-pointer) we set the
        // current workspace ID so the record is not deselected
        // in the interface by BackendUtility::versioningPlaceholderClause()
        $swapVersion['t3ver_wsid'] = 0;
        if ($swapIntoWS) {
            if ($t3ver_state['swapVersion'] > 0) {
                $swapVersion['t3ver_wsid'] = $dataHandler->BE_USER->workspace;
            } else {
                $swapVersion['t3ver_wsid'] = (int)$curVersion['t3ver_wsid'];
            }
        }
        $swapVersion['t3ver_tstamp'] = $GLOBALS['EXEC_TIME'];
        $swapVersion['t3ver_stage'] = 0;
        if (!$swapIntoWS) {
            $swapVersion['t3ver_state'] = (string)new VersionState(VersionState::DEFAULT_STATE);
        }
        // Moving element.
        if (BackendUtility::isTableWorkspaceEnabled($table)) {
            //  && $t3ver_state['swapVersion']==4   // Maybe we don't need this?
            if ($plhRec = BackendUtility::getMovePlaceholder($table, $id, 't3ver_state,pid,uid' . ($GLOBALS['TCA'][$table]['ctrl']['sortby'] ? ',' . $GLOBALS['TCA'][$table]['ctrl']['sortby'] : ''))) {
                $movePlhID = $plhRec['uid'];
                $movePlh['pid'] = $swapVersion['pid'];
                $swapVersion['pid'] = (int)$plhRec['pid'];
                $curVersion['t3ver_state'] = (int)$swapVersion['t3ver_state'];
                $swapVersion['t3ver_state'] = (string)new VersionState(VersionState::DEFAULT_STATE);
                if ($GLOBALS['TCA'][$table]['ctrl']['sortby']) {
                    // sortby is a "keepFields" which is why this will work...
                    $movePlh[$GLOBALS['TCA'][$table]['ctrl']['sortby']] = $swapVersion[$GLOBALS['TCA'][$table]['ctrl']['sortby']];
                    $swapVersion[$GLOBALS['TCA'][$table]['ctrl']['sortby']] = $plhRec[$GLOBALS['TCA'][$table]['ctrl']['sortby']];
                }
            }
        }
        // Take care of relations in each field (e.g. IRRE):
        if (is_array($GLOBALS['TCA'][$table]['columns'])) {
            foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $fieldConf) {
                if (isset($fieldConf['config']) && is_array($fieldConf['config'])) {
                    $this->version_swap_processFields($table, $fieldConf['config'], $curVersion, $swapVersion, $dataHandler);
                }
            }
        }
        unset($swapVersion['uid']);
        // Modify online version to become offline:
        unset($curVersion['uid']);
        // Set pid for OFFLINE
        $curVersion['pid'] = -1;
        $curVersion['t3ver_oid'] = (int)$id;
        $curVersion['t3ver_wsid'] = $swapIntoWS ? (int)$tmp_wsid : 0;
        $curVersion['t3ver_tstamp'] = $GLOBALS['EXEC_TIME'];
        $curVersion['t3ver_count'] = $curVersion['t3ver_count'] + 1;
        // Increment lifecycle counter
        $curVersion['t3ver_stage'] = 0;
        if (!$swapIntoWS) {
            $curVersion['t3ver_state'] = (string)new VersionState(VersionState::DEFAULT_STATE);
        }
        // Registering and swapping MM relations in current and swap records:
        $dataHandler->version_remapMMForVersionSwap($table, $id, $swapWith);
        // Generating proper history data to prepare logging
        $dataHandler->compareFieldArrayWithCurrentAndUnset($table, $id, $swapVersion);
        $dataHandler->compareFieldArrayWithCurrentAndUnset($table, $swapWith, $curVersion);

        // Execute swapping:
        $sqlErrors = [];
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        $platform = $connection->getDatabasePlatform();
        $tableDetails = null;
        if ($platform instanceof SQLServerPlatform) {
            // mssql needs to set proper PARAM_LOB and others to update fields
            $tableDetails = $connection->getSchemaManager()->listTableDetails($table);
        }

        try {
            $types = [];

            if ($platform instanceof SQLServerPlatform) {
                foreach ($curVersion as $columnName => $columnValue) {
                    $types[$columnName] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
                }
            }

            $connection->update(
                $table,
                $swapVersion,
                ['uid' => (int)$id],
                $types
            );
        } catch (DBALException $e) {
            $sqlErrors[] = $e->getPrevious()->getMessage();
        }

        if (empty($sqlErrors)) {
            try {
                $types = [];
                if ($platform instanceof SQLServerPlatform) {
                    foreach ($curVersion as $columnName => $columnValue) {
                        $types[$columnName] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
                    }
                }

                $connection->update(
                    $table,
                    $curVersion,
                    ['uid' => (int)$swapWith],
                    $types
                );
                unlink($lockFileName);
            } catch (DBALException $e) {
                $sqlErrors[] = $e->getPrevious()->getMessage();
            }
        }

        if (!empty($sqlErrors)) {
            $dataHandler->newlog('During Swapping: SQL errors happened: ' . implode('; ', $sqlErrors), SystemLogErrorClassification::SYSTEM_ERROR);
        } else {
            // Register swapped ids for later remapping:
            $this->remappedIds[$table][$id] = $swapWith;
            $this->remappedIds[$table][$swapWith] = $id;
            // If a moving operation took place...:
            if ($movePlhID) {
                // Remove, if normal publishing:
                if (!$swapIntoWS) {
                    // For delete + completely delete!
                    $dataHandler->deleteEl($table, $movePlhID, true, true);
                } else {
                    // Otherwise update the movePlaceholder:
                    GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getConnectionForTable($table)
                        ->update(
                            $table,
                            $movePlh,
                            ['uid' => (int)$movePlhID]
                        );
                    $dataHandler->addRemapStackRefIndex($table, $movePlhID);
                }
            }
            // Checking for delete:
            // Delete only if new/deleted placeholders are there.
            if (!$swapIntoWS && ((int)$t3ver_state['swapVersion'] === 1 || (int)$t3ver_state['swapVersion'] === 2)) {
                // Force delete
                $dataHandler->deleteEl($table, $id, true);
            }
            if ($dataHandler->enableLogging) {
                $dataHandler->log($table, $id, SystemLogGenericAction::UNDEFINED, 0, SystemLogErrorClassification::MESSAGE, ($swapIntoWS ? 'Swapping' : 'Publishing') . ' successful for table "' . $table . '" uid ' . $id . '=>' . $swapWith, -1, [], $dataHandler->eventPid($table, $id, $swapVersion['pid']));
            }

            // Update reference index of the live record:
            $dataHandler->addRemapStackRefIndex($table, $id);
            // Set log entry for live record:
            $propArr = $dataHandler->getRecordPropertiesFromRow($table, $swapVersion);
            if ($propArr['t3ver_oid'] ?? 0 > 0) {
                $label = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_tcemain.xlf:version_swap.offline_record_updated');
            } else {
                $label = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_tcemain.xlf:version_swap.online_record_updated');
            }
            $theLogId = $dataHandler->log($table, $id, DatabaseAction::UPDATE, $propArr['pid'], SystemLogErrorClassification::MESSAGE, $label, 10, [$propArr['header'], $table . ':' . $id], $propArr['event_pid']);
            $dataHandler->setHistory($table, $id, $theLogId);
            // Update reference index of the offline record:
            $dataHandler->addRemapStackRefIndex($table, $swapWith);
            // Set log entry for offline record:
            $propArr = $dataHandler->getRecordPropertiesFromRow($table, $curVersion);
            if ($propArr['t3ver_oid'] ?? 0 > 0) {
                $label = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_tcemain.xlf:version_swap.offline_record_updated');
            } else {
                $label = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_tcemain.xlf:version_swap.online_record_updated');
            }
            $theLogId = $dataHandler->log($table, $swapWith, DatabaseAction::UPDATE, $propArr['pid'], SystemLogErrorClassification::MESSAGE, $label, 10, [$propArr['header'], $table . ':' . $swapWith], $propArr['event_pid']);
            $dataHandler->setHistory($table, $swapWith, $theLogId);

            $stageId = StagesService::STAGE_PUBLISH_EXECUTE_ID;
            $notificationEmailInfoKey = $wsAccess['uid'] . ':' . $stageId . ':' . $comment;
            $this->notificationEmailInfo[$notificationEmailInfoKey]['shared'] = [$wsAccess, $stageId, $comment];
            $this->notificationEmailInfo[$notificationEmailInfoKey]['elements'][] = [$table, $id];
            $this->notificationEmailInfo[$notificationEmailInfoKey]['recipients'] = $notificationAlternativeRecipients;
            // Write to log with stageId -20 (STAGE_PUBLISH_EXECUTE_ID)
            if ($dataHandler->enableLogging) {
                $propArr = $dataHandler->getRecordProperties($table, $id);
                $pid = $propArr['pid'];
                $dataHandler->log($table, $id, SystemLogGenericAction::UNDEFINED, 0, SystemLogErrorClassification::MESSAGE, 'Stage for record was changed to ' . $stageId . '. Comment was: "' . substr($comment, 0, 100) . '"', -1, [], $dataHandler->eventPid($table, $id, $pid));
            }
            $dataHandler->log($table, $id, DatabaseAction::UPDATE, 0, SystemLogErrorClassification::MESSAGE, 'Published', 30, ['comment' => $comment, 'stage' => $stageId]);

            // Clear cache:
            $dataHandler->registerRecordIdForPageCacheClearing($table, $id);
            // If not swapped, delete the record from the database
            if (!$swapIntoWS) {
                $dataHandler->deleteEl($table, $swapWith, true, true);
            }

            //Update reference index for live workspace too:
            /** @var \TYPO3\CMS\Core\Database\ReferenceIndex $refIndexObj */
            $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
            $refIndexObj->setWorkspaceId(0);
            $refIndexObj->updateRefIndexTable($table, $id);
            $refIndexObj->updateRefIndexTable($table, $swapWith);
        }
    }

    /**
     * Processes fields of a record for the publishing/swapping process.
     * Basically this takes care of IRRE (type "inline") child references.
     *
     * @param string $tableName Table name
     * @param array $configuration TCA field configuration
     * @param array $liveData: Live record data
     * @param array $versionData: Version record data
     * @param DataHandler $dataHandler Calling data-handler object
     */
    protected function version_swap_processFields($tableName, array $configuration, array $liveData, array $versionData, DataHandler $dataHandler)
    {
        $inlineType = $dataHandler->getInlineFieldType($configuration);
        if ($inlineType !== 'field') {
            return;
        }
        $foreignTable = $configuration['foreign_table'];
        // Read relations that point to the current record (e.g. live record):
        $liveRelations = $this->createRelationHandlerInstance();
        $liveRelations->setWorkspaceId(0);
        $liveRelations->start('', $foreignTable, '', $liveData['uid'], $tableName, $configuration);
        // Read relations that point to the record to be swapped with e.g. draft record):
        $versionRelations = $this->createRelationHandlerInstance();
        $versionRelations->setUseLiveReferenceIds(false);
        $versionRelations->start('', $foreignTable, '', $versionData['uid'], $tableName, $configuration);
        // Update relations for both (workspace/versioning) sites:
        if (!empty($liveRelations->itemArray)) {
            $dataHandler->addRemapAction(
                $tableName,
                $liveData['uid'],
                [$this, 'updateInlineForeignFieldSorting'],
                [$liveData['uid'], $foreignTable, $liveRelations->tableArray[$foreignTable], $configuration, $dataHandler->BE_USER->workspace]
            );
        }
        if (!empty($versionRelations->itemArray)) {
            $dataHandler->addRemapAction(
                $tableName,
                $liveData['uid'],
                [$this, 'updateInlineForeignFieldSorting'],
                [$liveData['uid'], $foreignTable, $versionRelations->tableArray[$foreignTable], $configuration, 0]
            );
        }
    }

    /**
     * Updates foreign field sorting values of versioned and live
     * parents after(!) the whole structure has been published.
     *
     * This method is used as callback function in
     * DataHandlerHook::version_swap_procBasedOnFieldType().
     * Sorting fields ("sortby") are not modified during the
     * workspace publishing/swapping process directly.
     *
     * @param string $parentId
     * @param string $foreignTableName
     * @param int[] $foreignIds
     * @param array $configuration
     * @param int $targetWorkspaceId
     * @internal
     */
    public function updateInlineForeignFieldSorting($parentId, $foreignTableName, $foreignIds, array $configuration, $targetWorkspaceId)
    {
        $remappedIds = [];
        // Use remapped ids (live id <-> version id)
        foreach ($foreignIds as $foreignId) {
            if (!empty($this->remappedIds[$foreignTableName][$foreignId])) {
                $remappedIds[] = $this->remappedIds[$foreignTableName][$foreignId];
            } else {
                $remappedIds[] = $foreignId;
            }
        }

        $relationHandler = $this->createRelationHandlerInstance();
        $relationHandler->setWorkspaceId($targetWorkspaceId);
        $relationHandler->setUseLiveReferenceIds(false);
        $relationHandler->start(implode(',', $remappedIds), $foreignTableName);
        $relationHandler->processDeletePlaceholder();
        $relationHandler->writeForeignField($configuration, $parentId);
    }

    /**
     * Remove a versioned record from this workspace. Often referred to as "discarding a version" = throwing away a version.
     * This means to delete the record and remove any placeholders that are not needed anymore.
     *
     * In previous versions, this meant that the versioned record was marked as deleted and moved into "live" workspace.
     *
     * @param string $table Database table name
     * @param int $versionId Version record uid
     * @param bool $flush If set, will completely delete element
     * @param DataHandler $dataHandler DataHandler object
     */
    protected function version_clearWSID(string $table, int $versionId, bool $flush, DataHandler $dataHandler): void
    {
        if ($errorCode = $dataHandler->BE_USER->workspaceCannotEditOfflineVersion($table, $versionId)) {
            $dataHandler->newlog('Attempt to reset workspace for record failed: ' . $errorCode, SystemLogErrorClassification::USER_ERROR);
            return;
        }
        if (!$dataHandler->checkRecordUpdateAccess($table, $versionId)) {
            $dataHandler->newlog('Attempt to reset workspace for record failed because you do not have edit access', SystemLogErrorClassification::USER_ERROR);
            return;
        }
        $liveRecord = BackendUtility::getLiveVersionOfRecord($table, $versionId, 'uid,t3ver_state');
        if (!$liveRecord) {
            // Attempting to discard a record that has no live version, don't do anything
            return;
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $liveState = VersionState::cast($liveRecord['t3ver_state']);
        $versionRecord = BackendUtility::getRecord($table, $versionId);
        $versionState = VersionState::cast($versionRecord['t3ver_state']);
        $deleteField = $GLOBALS['TCA'][$table]['ctrl']['delete'] ?? null;

        // purge delete placeholder since it would not contain any modified information
        if ($flush || $versionState->equals(VersionState::DELETE_PLACEHOLDER)) {
            $dataHandler->deleteEl($table, $versionRecord['uid'], true, true);
        // let DataHandler decide how to delete the record that does not have a deleted field
        } elseif ($deleteField === null) {
            $dataHandler->deleteEl($table, $versionRecord['uid'], true);
        // update record directly in order to avoid delete cascades on this version
        } else {
            $connection->update(
                $table,
                ['t3ver_tstamp' => $GLOBALS['EXEC_TIME'], $deleteField => 1],
                ['uid' => (int)$versionId]
            );
        }

        // purge move placeholder as it has been created just for the sake of pointing to a version
        if ($liveState->equals(VersionState::MOVE_PLACEHOLDER)) {
            $dataHandler->deleteEl($table, $liveRecord['uid'], true, true);
        // purge new placeholder as it has been created just for the sake of pointing to a version
        } elseif ($liveState->equals(VersionState::NEW_PLACEHOLDER)) {
            $connection->update(
                $table,
                ['t3ver_tstamp' => $GLOBALS['EXEC_TIME']],
                ['uid' => (int)$liveRecord['uid']]
            );
            // THIS assumes that the record was placeholder ONLY for ONE record (namely $id)
            $dataHandler->deleteEl($table, $liveRecord['uid'], true);
        }
    }

    /**
     * In case a sys_workspace_stage record is deleted we do a hard reset
     * for all existing records in that stage to avoid that any of these end up
     * as orphan records.
     *
     * @param int $stageId Elements with this stage are reset
     */
    protected function resetStageOfElements(int $stageId): void
    {
        foreach ($this->getTcaTables() as $tcaTable) {
            if (BackendUtility::isTableWorkspaceEnabled($tcaTable)) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($tcaTable);

                $queryBuilder
                    ->update($tcaTable)
                    ->set('t3ver_stage', StagesService::STAGE_EDIT_ID)
                    ->where(
                        $queryBuilder->expr()->eq(
                            't3ver_stage',
                            $queryBuilder->createNamedParameter($stageId, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->gt(
                            't3ver_wsid',
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        )
                    )
                    ->execute();
            }
        }
    }

    /**
     * Flushes elements of a particular workspace to avoid orphan records.
     *
     * @param int $workspaceId The workspace to be flushed
     */
    protected function flushWorkspaceElements(int $workspaceId): void
    {
        $command = [];
        foreach ($this->getTcaTables() as $tcaTable) {
            if (BackendUtility::isTableWorkspaceEnabled($tcaTable)) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($tcaTable);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                    ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class, $workspaceId, false));

                $result = $queryBuilder
                    ->select('uid')
                    ->from($tcaTable)
                    ->orderBy('uid')
                    ->execute();

                while (($recordId = $result->fetchColumn()) !== false) {
                    $command[$tcaTable][$recordId]['version']['action'] = 'flush';
                }
            }
        }
        if (!empty($command)) {
            $dataHandler = $this->getDataHandler();
            $dataHandler->start([], $command);
            $dataHandler->process_cmdmap();
        }
    }

    /**
     * Gets all defined TCA tables.
     *
     * @return array
     */
    protected function getTcaTables(): array
    {
        return array_keys($GLOBALS['TCA']);
    }

    /**
     * @return DataHandler
     */
    protected function getDataHandler(): DataHandler
    {
        return GeneralUtility::makeInstance(DataHandler::class);
    }

    /**
     * Flushes the workspace cache for current workspace and for the virtual "all workspaces" too.
     *
     * @param int $workspaceId The workspace to be flushed in cache
     */
    protected function flushWorkspaceCacheEntriesByWorkspaceId(int $workspaceId): void
    {
        $workspacesCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('workspaces_cache');
        $workspacesCache->flushByTag($workspaceId);
        $workspacesCache->flushByTag(WorkspaceService::SELECT_ALL_WORKSPACES);
    }

    /*******************************
     *****  helper functions  ******
     *******************************/

    /**
     * Finds all elements for swapping versions in workspace
     *
     * @param string $table Table name of the original element to swap
     * @param int $id UID of the original element to swap (online)
     * @param int $offlineId As above but offline
     * @return array Element data. Key is table name, values are array with first element as online UID, second - offline UID
     */
    public function findPageElementsForVersionSwap($table, $id, $offlineId)
    {
        $rec = BackendUtility::getRecord($table, $offlineId, 't3ver_wsid');
        $workspaceId = (int)$rec['t3ver_wsid'];
        $elementData = [];
        if ($workspaceId === 0) {
            return $elementData;
        }
        // Get page UID for LIVE and workspace
        if ($table !== 'pages') {
            $rec = BackendUtility::getRecord($table, $id, 'pid');
            $pageId = $rec['pid'];
            $rec = BackendUtility::getRecord('pages', $pageId);
            BackendUtility::workspaceOL('pages', $rec, $workspaceId);
            $offlinePageId = $rec['_ORIG_uid'];
        } else {
            $pageId = $id;
            $offlinePageId = $offlineId;
        }
        // Traversing all tables supporting versioning:
        foreach ($GLOBALS['TCA'] as $table => $cfg) {
            if (BackendUtility::isTableWorkspaceEnabled($table) && $table !== 'pages') {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($table);

                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

                $statement = $queryBuilder
                    ->select('A.uid AS offlineUid', 'B.uid AS uid')
                    ->from($table, 'A')
                    ->from($table, 'B')
                    ->where(
                        $queryBuilder->expr()->gt(
                            'A.t3ver_oid',
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'B.pid',
                            $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'A.t3ver_wsid',
                            $queryBuilder->createNamedParameter($workspaceId, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq('A.t3ver_oid', $queryBuilder->quoteIdentifier('B.uid'))
                    )
                    ->execute();

                while ($row = $statement->fetch()) {
                    $elementData[$table][] = [$row['uid'], $row['offlineUid']];
                }
            }
        }
        if ($offlinePageId && $offlinePageId != $pageId) {
            $elementData['pages'][] = [$pageId, $offlinePageId];
        }

        return $elementData;
    }

    /**
     * Searches for all elements from all tables on the given pages in the same workspace.
     *
     * @param array $pageIdList List of PIDs to search
     * @param int $workspaceId Workspace ID
     * @param array $elementList List of found elements. Key is table name, value is array of element UIDs
     */
    public function findPageElementsForVersionStageChange(array $pageIdList, $workspaceId, array &$elementList)
    {
        if ($workspaceId == 0) {
            return;
        }
        // Traversing all tables supporting versioning:
        foreach ($GLOBALS['TCA'] as $table => $cfg) {
            if (BackendUtility::isTableWorkspaceEnabled($table) && $table !== 'pages') {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($table);

                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

                $statement = $queryBuilder
                    ->select('A.uid')
                    ->from($table, 'A')
                    ->from($table, 'B')
                    ->where(
                        $queryBuilder->expr()->gt(
                            'A.t3ver_oid',
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->in(
                            'B.pid',
                            $queryBuilder->createNamedParameter($pageIdList, Connection::PARAM_INT_ARRAY)
                        ),
                        $queryBuilder->expr()->eq(
                            'A.t3ver_wsid',
                            $queryBuilder->createNamedParameter($workspaceId, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq('A.t3ver_oid', $queryBuilder->quoteIdentifier('B.uid'))
                    )
                    ->groupBy('A.uid')
                    ->execute();

                while ($row = $statement->fetch()) {
                    $elementList[$table][] = $row['uid'];
                }
                if (is_array($elementList[$table])) {
                    // Yes, it is possible to get non-unique array even with DISTINCT above!
                    // It happens because several UIDs are passed in the array already.
                    $elementList[$table] = array_unique($elementList[$table]);
                }
            }
        }
    }

    /**
     * Finds page UIDs for the element from table <code>$table</code> with UIDs from <code>$idList</code>
     *
     * @param string $table Table to search
     * @param array $idList List of records' UIDs
     * @param int $workspaceId Workspace ID. We need this parameter because user can be in LIVE but he still can publish DRAFT from ws module!
     * @param array $pageIdList List of found page UIDs
     * @param array $elementList List of found element UIDs. Key is table name, value is list of UIDs
     */
    public function findPageIdsForVersionStateChange($table, array $idList, $workspaceId, array &$pageIdList, array &$elementList)
    {
        if ($workspaceId == 0) {
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->select('B.pid')
            ->from($table, 'A')
            ->from($table, 'B')
            ->where(
                $queryBuilder->expr()->gt(
                    'A.t3ver_oid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'A.t3ver_wsid',
                    $queryBuilder->createNamedParameter($workspaceId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'A.uid',
                    $queryBuilder->createNamedParameter($idList, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq('A.t3ver_oid', $queryBuilder->quoteIdentifier('B.uid'))
            )
            ->groupBy('B.pid')
            ->execute();

        while ($row = $statement->fetch()) {
            $pageIdList[] = $row['pid'];
            // Find ws version
            // Note: cannot use BackendUtility::getRecordWSOL()
            // here because it does not accept workspace id!
            $rec = BackendUtility::getRecord('pages', $row[0]);
            BackendUtility::workspaceOL('pages', $rec, $workspaceId);
            if ($rec['_ORIG_uid']) {
                $elementList['pages'][$row[0]] = $rec['_ORIG_uid'];
            }
        }
        // The line below is necessary even with DISTINCT
        // because several elements can be passed by caller
        $pageIdList = array_unique($pageIdList);
    }

    /**
     * Finds real page IDs for state change.
     *
     * @param array $idList List of page UIDs, possibly versioned
     */
    public function findRealPageIds(array &$idList): void
    {
        foreach ($idList as $key => $id) {
            $rec = BackendUtility::getRecord('pages', $id, 't3ver_oid');
            if ($rec['t3ver_oid'] > 0) {
                $idList[$key] = $rec['t3ver_oid'];
            }
        }
    }

    /**
     * Creates a move placeholder for workspaces.
     * USE ONLY INTERNALLY
     * Moving placeholder: Can be done because the system sees it as a placeholder for NEW elements like t3ver_state=VersionState::NEW_PLACEHOLDER
     * Moving original: Will either create the placeholder if it doesn't exist or move existing placeholder in workspace.
     *
     * @param string $table Table name to move
     * @param int $uid Record uid to move (online record)
     * @param int $destPid Position to move to: $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
     * @param int $offlineUid UID of offline version of online record
     * @param DataHandler $dataHandler DataHandler object
     * @see moveRecord()
     */
    protected function moveRecord_wsPlaceholders(string $table, int $uid, int $destPid, int $offlineUid, DataHandler $dataHandler): void
    {
        // If a record gets moved after a record that already has a placeholder record
        // then the new placeholder record needs to be after the existing one
        $originalRecordDestinationPid = $destPid;
        if ($destPid < 0) {
            $movePlaceHolder = BackendUtility::getMovePlaceholder($table, abs($destPid), 'uid');
            if ($movePlaceHolder !== false) {
                $destPid = -$movePlaceHolder['uid'];
            }
        }
        if ($plh = BackendUtility::getMovePlaceholder($table, $uid, 'uid')) {
            // If already a placeholder exists, move it:
            $dataHandler->moveRecord_raw($table, $plh['uid'], $destPid);
        } else {
            // First, we create a placeholder record in the Live workspace that
            // represents the position to where the record is eventually moved to.
            $newVersion_placeholderFieldArray = [];

            $factory = GeneralUtility::makeInstance(
                PlaceholderShadowColumnsResolver::class,
                $table,
                $GLOBALS['TCA'][$table] ?? []
            );
            $shadowColumns = $factory->forMovePlaceholder();
            // Set values from the versioned record to the move placeholder
            if (!empty($shadowColumns)) {
                $versionedRecord = BackendUtility::getRecord($table, $offlineUid);
                foreach ($shadowColumns as $shadowColumn) {
                    if (isset($versionedRecord[$shadowColumn])) {
                        $newVersion_placeholderFieldArray[$shadowColumn] = $versionedRecord[$shadowColumn];
                    }
                }
            }

            if ($GLOBALS['TCA'][$table]['ctrl']['crdate']) {
                $newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['crdate']] = $GLOBALS['EXEC_TIME'];
            }
            if ($GLOBALS['TCA'][$table]['ctrl']['cruser_id']) {
                $newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']] = $dataHandler->userid;
            }
            if ($GLOBALS['TCA'][$table]['ctrl']['tstamp']) {
                $newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
            }
            if ($table === 'pages') {
                // Copy page access settings from original page to placeholder
                $perms_clause = $dataHandler->BE_USER->getPagePermsClause(Permission::PAGE_SHOW);
                $access = BackendUtility::readPageAccess($uid, $perms_clause);
                $newVersion_placeholderFieldArray['perms_userid'] = $access['perms_userid'];
                $newVersion_placeholderFieldArray['perms_groupid'] = $access['perms_groupid'];
                $newVersion_placeholderFieldArray['perms_user'] = $access['perms_user'];
                $newVersion_placeholderFieldArray['perms_group'] = $access['perms_group'];
                $newVersion_placeholderFieldArray['perms_everybody'] = $access['perms_everybody'];
            }
            $newVersion_placeholderFieldArray['t3ver_move_id'] = $uid;
            // Setting placeholder state value for temporary record
            $newVersion_placeholderFieldArray['t3ver_state'] = (string)new VersionState(VersionState::MOVE_PLACEHOLDER);
            // Setting workspace - only so display of place holders can filter out those from other workspaces.
            $newVersion_placeholderFieldArray['t3ver_wsid'] = $dataHandler->BE_USER->workspace;
            $newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['label']] = $dataHandler->getPlaceholderTitleForTableLabel($table, 'MOVE-TO PLACEHOLDER for #' . $uid);
            // moving localized records requires to keep localization-settings for the placeholder too
            if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField']) && isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])) {
                $l10nParentRec = BackendUtility::getRecord($table, $uid);
                $newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['languageField']] = $l10nParentRec[$GLOBALS['TCA'][$table]['ctrl']['languageField']];
                $newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] = $l10nParentRec[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']];
                if (isset($GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField'])) {
                    $newVersion_placeholderFieldArray[$GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']] = $l10nParentRec[$GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']];
                }
                unset($l10nParentRec);
            }
            // Initially, create at root level.
            $newVersion_placeholderFieldArray['pid'] = 0;
            $id = 'NEW_MOVE_PLH';
            // Saving placeholder as 'original'
            $dataHandler->insertDB($table, $id, $newVersion_placeholderFieldArray, false);
            // Move the new placeholder from temporary root-level to location:
            $dataHandler->moveRecord_raw($table, $dataHandler->substNEWwithIDs[$id], $destPid);
            // Move the workspace-version of the original to be the version of the move-to-placeholder:
            // Setting placeholder state value for version (so it can know it is currently a new version...)
            $updateFields = [
                't3ver_state' => (string)new VersionState(VersionState::MOVE_POINTER)
            ];

            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($table)
                ->update(
                    $table,
                    $updateFields,
                    ['uid' => (int)$offlineUid]
                );
        }
        // Check for the localizations of that element and move them as well
        $dataHandler->moveL10nOverlayRecords($table, $uid, $destPid, $originalRecordDestinationPid);
    }

    /**
     * Gets an instance of the command map helper.
     *
     * @param DataHandler $dataHandler DataHandler object
     * @return CommandMap
     */
    public function getCommandMap(DataHandler $dataHandler): CommandMap
    {
        return GeneralUtility::makeInstance(
            CommandMap::class,
            $this,
            $dataHandler,
            $dataHandler->cmdmap,
            $dataHandler->BE_USER->workspace
        );
    }

    /**
     * Returns all fieldnames from a table which have the unique evaluation type set.
     *
     * @param string $table Table name
     * @return array Array of fieldnames
     */
    protected function getUniqueFields($table): array
    {
        $listArr = [];
        foreach ($GLOBALS['TCA'][$table]['columns'] ?? [] as $field => $configArr) {
            if ($configArr['config']['type'] === 'input') {
                $evalCodesArray = GeneralUtility::trimExplode(',', $configArr['config']['eval'], true);
                if (in_array('uniqueInPid', $evalCodesArray) || in_array('unique', $evalCodesArray)) {
                    $listArr[] = $field;
                }
            }
        }
        return $listArr;
    }

    /**
     * @return RelationHandler
     */
    protected function createRelationHandlerInstance(): RelationHandler
    {
        return GeneralUtility::makeInstance(RelationHandler::class);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
