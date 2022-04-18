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

namespace TYPO3\CMS\Workspaces\Hook;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform as PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLServer2012Platform as SQLServerPlatform;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Localization\LanguageService;
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
        $comment = $value['comment'] ?? '';
        $notificationAlternativeRecipients = $value['notificationAlternativeRecipients'] ?? [];
        switch ($action) {
            case 'new':
                $dataHandler->versionizeRecord($table, $id, $value['label']);
                break;
            case 'swap':
            case 'publish':
                $this->version_swap(
                    $table,
                    $id,
                    $value['swapWith'],
                    $dataHandler,
                    $comment,
                    $notificationAlternativeRecipients
                );
                break;
            case 'clearWSID':
            case 'flush':
                $dataHandler->discard($table, (int)$id);
                break;
            case 'setStage':
                $elementIds = GeneralUtility::intExplode(',', (string)$id, true);
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
            $workspaceRec = $groupedNotificationInformation['shared'][0];
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
                $dataHandler->log($elementTable, $elementUid, DatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::MESSAGE, 'Notification email for stage change was sent to "{recipients}"', -1, ['recipients' => implode('", "', array_column($emails, 'email'))], $dataHandler->eventPid($elementTable, $elementUid, $pid));
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
        $recordVersionState = VersionState::cast($record['t3ver_state'] ?? 0);
        // Look, if record is an offline version, then delete directly:
        if ((int)($record['t3ver_oid'] ?? 0) > 0) {
            if (BackendUtility::isTableWorkspaceEnabled($table)) {
                // In Live workspace, delete any. In other workspaces there must be match.
                if ($dataHandler->BE_USER->workspace == 0 || (int)$record['t3ver_wsid'] == $dataHandler->BE_USER->workspace) {
                    $liveRec = BackendUtility::getLiveVersionOfRecord($table, $id, 'uid,t3ver_state');
                    // Processing can be skipped if a delete placeholder shall be published
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
                    } elseif ($recordVersionState->equals(VersionState::NEW_PLACEHOLDER)) {
                        $placeholderRecord = BackendUtility::getLiveVersionOfRecord($table, (int)$id);
                        $dataHandler->deleteEl($table, (int)$id);
                        if (is_array($placeholderRecord)) {
                            $this->softOrHardDeleteSingleRecord($table, (int)$placeholderRecord['uid']);
                        }
                    }
                } else {
                    $dataHandler->log($table, (int)$id, DatabaseAction::DELETE, 0, SystemLogErrorClassification::USER_ERROR, 'Tried to delete record from another workspace');
                }
            } else {
                $dataHandler->log($table, (int)$id, DatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Versioning not enabled for record with an online ID (t3ver_oid) given');
            }
        } elseif ($recordVersionState->equals(VersionState::NEW_PLACEHOLDER)) {
            // If it is a new versioned record, delete it directly.
            $dataHandler->deleteEl($table, $id);
        } elseif ($dataHandler->BE_USER->workspaceAllowsLiveEditingInTable($table)) {
            // Look, if record is "online" then delete directly.
            $dataHandler->deleteEl($table, $id);
        } else {
            // Otherwise, try to delete by versioning:
            $copyMappingArray = $dataHandler->copyMappingArray;
            $dataHandler->versionizeRecord($table, $id, 'DELETED!', true);
            // Determine newly created versions:
            // (remove placeholders are copied and modified, thus they appear in the copyMappingArray)
            $versionizedElements = ArrayUtility::arrayDiffKeyRecursive($dataHandler->copyMappingArray, $copyMappingArray);
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
                $this->emitUpdateTopbarSignal();
            }
        }
    }

    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        if (isset($dataHandler->datamap[WorkspaceService::TABLE_WORKSPACE])) {
            $this->emitUpdateTopbarSignal();
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
        $recordWasMoved = true;
        $moveRecVersionState = VersionState::cast((int)($moveRec['t3ver_state'] ?? VersionState::DEFAULT_STATE));
        // Get workspace version of the source record, if any:
        $versionedRecord = BackendUtility::getWorkspaceVersionOfRecord($dataHandler->BE_USER->workspace, $table, $uid, 'uid,t3ver_oid');
        if ($tableSupportsVersioning) {
            // Create version of record first, if it does not exist
            if (empty($versionedRecord['uid'])) {
                $dataHandler->versionizeRecord($table, $uid, 'MovePointer');
                $versionedRecord = BackendUtility::getWorkspaceVersionOfRecord($dataHandler->BE_USER->workspace, $table, $uid, 'uid,t3ver_oid');
                if ((int)$resolvedPid !== (int)$propArr['pid']) {
                    $this->moveRecord_processFields($dataHandler, $resolvedPid, $table, $uid);
                }
            } elseif ($dataHandler->isRecordCopied($table, $uid) && (int)$dataHandler->copyMappingArray[$table][$uid] === (int)$versionedRecord['uid']) {
                // If the record has been versioned before (e.g. cascaded parent-child structure), create only the move-placeholders
                if ((int)$resolvedPid !== (int)$propArr['pid']) {
                    $this->moveRecord_processFields($dataHandler, $resolvedPid, $table, $uid);
                }
            }
        }
        // Check workspace permissions:
        $workspaceAccessBlocked = [];
        // Element was in "New/Deleted/Moved" so it can be moved...
        $recIsNewVersion = $moveRecVersionState->equals(VersionState::NEW_PLACEHOLDER) || $moveRecVersionState->indicatesPlaceholder();
        $recordMustNotBeVersionized = $dataHandler->BE_USER->workspaceAllowsLiveEditingInTable($table);
        $canMoveRecord = $recIsNewVersion || $tableSupportsVersioning;
        // Workspace source check:
        if (!$recIsNewVersion) {
            $errorCode = $dataHandler->workspaceCannotEditRecord($table, $versionedRecord['uid'] ?: $uid);
            if ($errorCode) {
                $workspaceAccessBlocked['src1'] = 'Record could not be edited in workspace: ' . $errorCode . ' ';
            } elseif (!$canMoveRecord && !$recordMustNotBeVersionized) {
                $workspaceAccessBlocked['src2'] = 'Could not remove record from table "' . $table . '" from its page "' . $moveRec['pid'] . '" ';
            }
        }
        // Workspace destination check:
        // All records can be inserted if $recordMustNotBeVersionized is true.
        // Only new versions can be inserted if $recordMustNotBeVersionized is FALSE.
        if (!($recordMustNotBeVersionized || $canMoveRecord)) {
            $workspaceAccessBlocked['dest1'] = 'Could not insert record from table "' . $table . '" in destination PID "' . $resolvedPid . '" ';
        }

        if (empty($workspaceAccessBlocked)) {
            $versionedRecordUid = (int)$versionedRecord['uid'];
            // custom moving not needed, just behave like in live workspace (also for newly versioned records)
            if (!$versionedRecordUid || !$tableSupportsVersioning || $recIsNewVersion) {
                $recordWasMoved = false;
            } else {
                // If the move operation is done on a versioned record, which is
                // NOT new/deleted placeholder, then mark the versioned record as "moved"
                $this->moveRecord_moveVersionedRecord($table, (int)$uid, (int)$destPid, $versionedRecordUid, $dataHandler);
            }
        } else {
            $dataHandler->log($table, $versionedRecord['uid'] ?: $uid, DatabaseAction::MOVE, 0, SystemLogErrorClassification::USER_ERROR, 'Move attempt failed due to workspace restrictions: {reason}', -1, ['reason' => implode(' // ', $workspaceAccessBlocked)]);
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
                if (empty($versionedRecord)) {
                    continue;
                }
                $versionState = VersionState::cast($versionedRecord['t3ver_state']);
                if ($versionState->indicatesPlaceholder()) {
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
        $record = BackendUtility::getRecord($table, $id);
        if (!is_array($record)) {
            $dataHandler->log($table, $id, DatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to set stage for record failed: No Record');
        } elseif ($errorCode = $dataHandler->workspaceCannotEditOfflineVersion($table, $record)) {
            $dataHandler->log($table, $id, DatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to set stage for record failed: {reason}', -1, ['reason' => $errorCode]);
        } elseif ($dataHandler->checkRecordUpdateAccess($table, $id)) {
            $workspaceInfo = $dataHandler->BE_USER->checkWorkspace($record['t3ver_wsid']);
            $workspaceId = (int)$workspaceInfo['uid'];
            $currentStage = (int)$record['t3ver_stage'];
            // check if the user is allowed to the current stage, so it's also allowed to send to next stage
            if ($dataHandler->BE_USER->workspaceCheckStageForCurrent($currentStage)) {
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
                    $dataHandler->log($table, $id, DatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::MESSAGE, 'Stage for record was changed to {stage}. Comment was: "{comment}"', -1, ['stage' =>  $stageId, 'comment' => mb_substr($comment, 0, 100)], $dataHandler->eventPid($table, $id, $pid));
                }
                // Write the stage change to history
                $historyStore = $this->getRecordHistoryStore($workspaceId, $dataHandler->BE_USER);
                $historyStore->changeStageForRecord($table, (int)$id, ['current' => $currentStage, 'next' => $stageId, 'comment' => $comment]);
                if ((int)$workspaceInfo['stagechg_notification'] > 0) {
                    $this->notificationEmailInfo[$workspaceInfo['uid'] . ':' . $stageId . ':' . $comment]['shared'] = [$workspaceInfo, $stageId, $comment];
                    $this->notificationEmailInfo[$workspaceInfo['uid'] . ':' . $stageId . ':' . $comment]['elements'][] = [$table, $id];
                    $this->notificationEmailInfo[$workspaceInfo['uid'] . ':' . $stageId . ':' . $comment]['recipients'] = $notificationAlternativeRecipients;
                }
            } else {
                $dataHandler->log($table, $id, DatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'The member user tried to set a stage value "{stage}" that was not allowed', -1, ['stage' => $stageId]);
            }
        } else {
            $dataHandler->log($table, $id, DatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::USER_ERROR, 'Attempt to set stage for record failed because you do not have edit access');
        }
    }

    /*****************************
     *****  CMD versioning  ******
     *****************************/

    /**
     * Publishing / Swapping (= switching) versions of a record
     * Version from archive (future/past, called "swap version") will get the uid of the "t3ver_oid", the official element with uid = "t3ver_oid" will get the new versions old uid. PIDs are swapped also
     *
     * @param string $table Table name
     * @param int $id UID of the online record to swap
     * @param int $swapWith UID of the archived version to swap with!
     * @param DataHandler $dataHandler DataHandler object
     * @param string $comment Notification comment
     * @param array $notificationAlternativeRecipients comma separated list of recipients to notify instead of normal be_users
     */
    protected function version_swap($table, $id, $swapWith, DataHandler $dataHandler, string $comment, $notificationAlternativeRecipients = [])
    {
        // Check prerequisites before start publishing
        // Skip records that have been deleted during the current execution
        if ($dataHandler->hasDeletedRecord($table, $id)) {
            return;
        }

        // First, check if we may actually edit the online record
        if (!$dataHandler->checkRecordUpdateAccess($table, $id)) {
            $dataHandler->log(
                $table,
                $id,
                DatabaseAction::PUBLISH,
                0,
                SystemLogErrorClassification::USER_ERROR,
                'Error: You cannot swap versions for record %s:%d you do not have access to edit!',
                -1,
                [$table, $id]
            );
            return;
        }
        // Select the two versions:
        // Currently live version, contents will be removed.
        $curVersion = BackendUtility::getRecord($table, $id, '*');
        // Versioned records which contents will be moved into $curVersion
        $isNewRecord = ((int)($curVersion['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER);
        if ($isNewRecord && is_array($curVersion)) {
            // @todo: This early return is odd. It means version_swap_processFields() and versionPublishManyToManyRelations()
            //        below are not called for new records to be published. This is "fine" for mm since mm tables have no
            //        t3ver_wsid and need no publish as such. For inline relation publishing, this is indirectly resolved by the
            //        processCmdmap_beforeStart() hook, which adds additional commands for child records - a construct we
            //        may want to avoid altogether due to its complexity. It would be easier to follow if publish here would
            //        handle that instead.
            $this->publishNewRecord($table, $curVersion, $dataHandler, $comment, (array)$notificationAlternativeRecipients);
            return;
        }
        $swapVersion = BackendUtility::getRecord($table, $swapWith, '*');
        if (!(is_array($curVersion) && is_array($swapVersion))) {
            $dataHandler->log(
                $table,
                $id,
                DatabaseAction::PUBLISH,
                0,
                SystemLogErrorClassification::SYSTEM_ERROR,
                'Error: Either online or swap version for %s:%d->%d could not be selected!',
                -1,
                [$table, $id, $swapWith]
            );
            return;
        }
        $workspaceId = (int)$swapVersion['t3ver_wsid'];
        $currentStage = (int)$swapVersion['t3ver_stage'];
        if (!$dataHandler->BE_USER->workspacePublishAccess($workspaceId)) {
            $dataHandler->log($table, (int)$id, DatabaseAction::PUBLISH, 0, SystemLogErrorClassification::USER_ERROR, 'User could not publish records from workspace #{workspace}', -1, ['workspace' => $workspaceId]);
            return;
        }
        $wsAccess = $dataHandler->BE_USER->checkWorkspace($workspaceId);
        if (!($workspaceId <= 0 || !($wsAccess['publish_access'] & 1) || $currentStage === StagesService::STAGE_PUBLISH_ID)) {
            $dataHandler->log($table, (int)$id, DatabaseAction::PUBLISH, 0, SystemLogErrorClassification::USER_ERROR, 'Records in workspace #{workspace} can only be published when in "Publish" stage.', -1, ['workspace' => $workspaceId]);
            return;
        }
        if (!($dataHandler->doesRecordExist($table, $swapWith, Permission::PAGE_SHOW) && $dataHandler->checkRecordUpdateAccess($table, $swapWith))) {
            $dataHandler->log($table, $swapWith, DatabaseAction::PUBLISH, 0, SystemLogErrorClassification::USER_ERROR, 'You cannot publish a record you do not have edit and show permissions for');
            return;
        }
        // Check if the swapWith record really IS a version of the original!
        if (!(((int)$swapVersion['t3ver_oid'] > 0 && (int)$curVersion['t3ver_oid'] === 0) && (int)$swapVersion['t3ver_oid'] === (int)$id)) {
            $dataHandler->log($table, $swapWith, DatabaseAction::PUBLISH, 0, SystemLogErrorClassification::SYSTEM_ERROR, 'In offline record, either t3ver_oid was not set or the t3ver_oid didn\'t match the id of the online version as it must!');
            return;
        }
        $versionState = new VersionState($swapVersion['t3ver_state']);

        // Find fields to keep
        $keepFields = $this->getUniqueFields($table);
        // Sorting needs to be exchanged for moved records
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['sortby']) && !$versionState->equals(VersionState::MOVE_POINTER)) {
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
        // Set pid for ONLINE (but not for moved records)
        if (!$versionState->equals(VersionState::MOVE_POINTER)) {
            $swapVersion['pid'] = (int)$curVersion['pid'];
        }
        // We clear this because t3ver_oid only make sense for offline versions
        // and we want to prevent unintentional misuse of this
        // value for online records.
        $swapVersion['t3ver_oid'] = 0;
        // In case of swapping and the offline record has a state
        // (like 2 or 4 for deleting or move-pointer) we set the
        // current workspace ID so the record is not deselected.
        // @todo: It is odd these information are updated in $swapVersion *before* version_swap_processFields
        //        version_swap_processFields() and versionPublishManyToManyRelations() are called. This leads
        //        to the situation that versionPublishManyToManyRelations() needs another argument to transfer
        //        the "from workspace" information which would usually be retrieved by accessing $swapVersion['t3ver_wsid']
        $swapVersion['t3ver_wsid'] = 0;
        $swapVersion['t3ver_stage'] = 0;
        $swapVersion['t3ver_state'] = (string)new VersionState(VersionState::DEFAULT_STATE);
        // Take care of relations in each field (e.g. IRRE):
        if (is_array($GLOBALS['TCA'][$table]['columns'])) {
            foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $fieldConf) {
                if (isset($fieldConf['config']) && is_array($fieldConf['config'])) {
                    $this->version_swap_processFields($table, $fieldConf['config'], $curVersion, $swapVersion, $dataHandler);
                }
            }
        }
        $dataHandler->versionPublishManyToManyRelations($table, $curVersion, $swapVersion, $workspaceId);
        unset($swapVersion['uid']);
        // Modify online version to become offline:
        unset($curVersion['uid']);
        // Mark curVersion to contain the oid
        $curVersion['t3ver_oid'] = (int)$id;
        $curVersion['t3ver_wsid'] = 0;
        // Increment lifecycle counter
        $curVersion['t3ver_stage'] = 0;
        $curVersion['t3ver_state'] = (string)new VersionState(VersionState::DEFAULT_STATE);
        // Generating proper history data to prepare logging
        $dataHandler->compareFieldArrayWithCurrentAndUnset($table, $id, $swapVersion);
        $dataHandler->compareFieldArrayWithCurrentAndUnset($table, $swapWith, $curVersion);

        // Execute swapping:
        $sqlErrors = [];
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        $platform = $connection->getDatabasePlatform();
        $tableDetails = null;
        if ($platform instanceof SQLServerPlatform || $platform instanceof PostgreSQLPlatform) {
            // mssql and postgres needs to set proper PARAM_LOB and others to update fields.
            $tableDetails = $connection->createSchemaManager()->listTableDetails($table);
        }

        try {
            $types = [];

            if ($platform instanceof SQLServerPlatform || $platform instanceof PostgreSQLPlatform) {
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
                if ($platform instanceof SQLServerPlatform || $platform instanceof PostgreSQLPlatform) {
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
            } catch (DBALException $e) {
                $sqlErrors[] = $e->getPrevious()->getMessage();
            }
        }

        if (!empty($sqlErrors)) {
            $dataHandler->log($table, $swapWith, DatabaseAction::PUBLISH, 0, SystemLogErrorClassification::SYSTEM_ERROR, 'During Swapping: SQL errors happened: {reason}', -1, ['reason' => implode('; ', $sqlErrors)]);
        } else {
            // Update localized elements to use the live l10n_parent now
            $this->updateL10nOverlayRecordsOnPublish($table, $id, $swapWith, $workspaceId, $dataHandler);
            // Register swapped ids for later remapping:
            $this->remappedIds[$table][$id] = $swapWith;
            $this->remappedIds[$table][$swapWith] = $id;
            if ((int)$t3ver_state['swapVersion'] === VersionState::DELETE_PLACEHOLDER) {
                // We're publishing a delete placeholder t3ver_state = 2. This means the live record should
                // be set to deleted. We're currently in some workspace and deal with a live record here. Thus,
                // we temporarily set backend user workspace to 0 so all operations happen as in live.
                $currentUserWorkspace = $dataHandler->BE_USER->workspace;
                $dataHandler->BE_USER->workspace = 0;
                $dataHandler->deleteEl($table, $id, true);
                $dataHandler->BE_USER->workspace = $currentUserWorkspace;
            }
            $dataHandler->log($table, $id, DatabaseAction::PUBLISH, 0, SystemLogErrorClassification::MESSAGE, 'Publishing successful for table "{table}" uid {liveId}=>{versionId}', -1, ['table' => $table, 'versionId' => $swapWith, 'liveId' => $id], $dataHandler->eventPid($table, $id, $swapVersion['pid']));

            // Set log entry for live record:
            $propArr = $dataHandler->getRecordPropertiesFromRow($table, $swapVersion);
            if (($propArr['t3ver_oid'] ?? 0) > 0) {
                $label = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_tcemain.xlf:version_swap.offline_record_updated');
            } else {
                $label = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_tcemain.xlf:version_swap.online_record_updated');
            }
            $dataHandler->log($table, $id, DatabaseAction::UPDATE, $propArr['pid'], SystemLogErrorClassification::MESSAGE, $label, 10, [$propArr['header'], $table . ':' . $id], $propArr['event_pid']);
            $dataHandler->setHistory($table, $id);
            // Set log entry for offline record:
            $propArr = $dataHandler->getRecordPropertiesFromRow($table, $curVersion);
            if (($propArr['t3ver_oid'] ?? 0) > 0) {
                $label = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_tcemain.xlf:version_swap.offline_record_updated');
            } else {
                $label = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_tcemain.xlf:version_swap.online_record_updated');
            }
            $dataHandler->log($table, $swapWith, DatabaseAction::UPDATE, $propArr['pid'], SystemLogErrorClassification::MESSAGE, $label, 10, [$propArr['header'], $table . ':' . $swapWith], $propArr['event_pid']);
            $dataHandler->setHistory($table, $swapWith);

            $stageId = StagesService::STAGE_PUBLISH_EXECUTE_ID;
            $notificationEmailInfoKey = $wsAccess['uid'] . ':' . $stageId . ':' . $comment;
            $this->notificationEmailInfo[$notificationEmailInfoKey]['shared'] = [$wsAccess, $stageId, $comment];
            $this->notificationEmailInfo[$notificationEmailInfoKey]['elements'][] = [$table, $id];
            $this->notificationEmailInfo[$notificationEmailInfoKey]['recipients'] = $notificationAlternativeRecipients;
            // Write to log with stageId -20 (STAGE_PUBLISH_EXECUTE_ID)
            if ($dataHandler->enableLogging) {
                $propArr = $dataHandler->getRecordProperties($table, $id);
                $pid = $propArr['pid'];
                $dataHandler->log($table, $id, DatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::MESSAGE, 'Stage for record was changed to ' . $stageId . '. Comment was: "' . substr($comment, 0, 100) . '"', -1, [], $dataHandler->eventPid($table, $id, $pid));
            }
            // Write the stage change to the history
            $historyStore = $this->getRecordHistoryStore((int)$wsAccess['uid'], $dataHandler->BE_USER);
            $historyStore->changeStageForRecord($table, (int)$id, ['current' => $currentStage, 'next' => StagesService::STAGE_PUBLISH_EXECUTE_ID, 'comment' => $comment]);

            // Clear cache:
            $dataHandler->registerRecordIdForPageCacheClearing($table, $id);
            // If published, delete the record from the database
            if ($table === 'pages') {
                // Note on fifth argument false: At this point both $curVersion and $swapVersion page records are
                // identical in DB. deleteEl() would now usually find all records assigned to our obsolete
                // page which at the same time belong to our current version page, and would delete them.
                // To suppress this, false tells deleteEl() to only delete the obsolete page but not its assigned records.
                $dataHandler->deleteEl($table, $swapWith, true, true, false);
            } else {
                $dataHandler->deleteEl($table, $swapWith, true, true);
            }

            // Update reference index of the live record - which could have been a workspace record in case 'new'
            $dataHandler->updateRefIndex($table, $id, 0);
            // The 'swapWith' record has been deleted, so we can drop any reference index the record is involved in
            $dataHandler->registerReferenceIndexRowsForDrop($table, $swapWith, (int)$dataHandler->BE_USER->workspace);
        }
    }

    /**
     * If an editor is doing "partial" publishing, the translated children need to be "linked" to the now pointed
     * live record, as if the versioned record (which is deleted) would have never existed.
     *
     * This is related to the l10n_source and l10n_parent fields.
     *
     * This needs to happen before the hook calls DataHandler->deleteEl() otherwise the children get deleted as well.
     *
     * @param string $table the database table of the published record
     * @param int $liveId the live version / online version of the record that was just published
     * @param int $previouslyUsedVersionId the versioned record ID (wsid>0) which is about to be deleted
     * @param int $workspaceId the workspace ID
     * @param DataHandler $dataHandler
     */
    protected function updateL10nOverlayRecordsOnPublish(string $table, int $liveId, int $previouslyUsedVersionId, int $workspaceId, DataHandler $dataHandler): void
    {
        if (!BackendUtility::isTableLocalizable($table)) {
            return;
        }
        if (!BackendUtility::isTableWorkspaceEnabled($table)) {
            return;
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $l10nParentFieldName = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        $constraints = $queryBuilder->expr()->eq(
            $l10nParentFieldName,
            $queryBuilder->createNamedParameter($previouslyUsedVersionId, \PDO::PARAM_INT)
        );
        $translationSourceFieldName = $GLOBALS['TCA'][$table]['ctrl']['translationSource'] ?? null;
        if ($translationSourceFieldName) {
            $constraints = $queryBuilder->expr()->orX(
                $constraints,
                $queryBuilder->expr()->eq(
                    $translationSourceFieldName,
                    $queryBuilder->createNamedParameter($previouslyUsedVersionId, \PDO::PARAM_INT)
                )
            );
        }

        $queryBuilder
            ->select('uid', $l10nParentFieldName)
            ->from($table)
            ->where(
                $constraints,
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($workspaceId, \PDO::PARAM_INT)
                )
            );

        if ($translationSourceFieldName) {
            $queryBuilder->addSelect($translationSourceFieldName);
        }

        $statement = $queryBuilder->executeQuery();
        while ($record = $statement->fetchAssociative()) {
            $updateFields = [];
            $dataTypes = [\PDO::PARAM_INT];
            if ((int)$record[$l10nParentFieldName] === $previouslyUsedVersionId) {
                $updateFields[$l10nParentFieldName] = $liveId;
                $dataTypes[] = \PDO::PARAM_INT;
            }
            if ($translationSourceFieldName && (int)$record[$translationSourceFieldName] === $previouslyUsedVersionId) {
                $updateFields[$translationSourceFieldName] = $liveId;
                $dataTypes[] = \PDO::PARAM_INT;
            }

            if (empty($updateFields)) {
                continue;
            }

            $connection->update(
                $table,
                $updateFields,
                ['uid' => (int)$record['uid']],
                $dataTypes
            );
            $dataHandler->updateRefIndex($table, $record['uid']);
        }
    }

    /**
     * Processes fields of a record for the publishing/swapping process.
     * Basically this takes care of IRRE (type "inline") child references.
     *
     * @param string $tableName Table name
     * @param array $configuration TCA field configuration
     * @param array $liveData Live record data
     * @param array $versionData Version record data
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
     * When a new record in a workspace is published, there is no "replacing" the online version with
     * the versioned record, but instead the workspace ID and the state is changed.
     *
     * @param string $table
     * @param array $newRecordInWorkspace
     * @param DataHandler $dataHandler
     * @param string $comment
     * @param array $notificationAlternativeRecipients
     */
    protected function publishNewRecord(string $table, array $newRecordInWorkspace, DataHandler $dataHandler, string $comment, array $notificationAlternativeRecipients): void
    {
        $id = (int)$newRecordInWorkspace['uid'];
        $workspaceId = (int)$newRecordInWorkspace['t3ver_wsid'];
        if (!$dataHandler->BE_USER->workspacePublishAccess($workspaceId)) {
            $dataHandler->log($table, $id, DatabaseAction::PUBLISH, 0, SystemLogErrorClassification::USER_ERROR, 'User could not publish records from workspace #{workspace}', -1, ['workspace' => $workspaceId]);
            return;
        }
        $wsAccess = $dataHandler->BE_USER->checkWorkspace($workspaceId);
        if (!($workspaceId <= 0 || !($wsAccess['publish_access'] & 1) || (int)$newRecordInWorkspace['t3ver_stage'] === StagesService::STAGE_PUBLISH_ID)) {
            $dataHandler->log($table, $id, DatabaseAction::PUBLISH, 0, SystemLogErrorClassification::USER_ERROR, 'Records in workspace #{workspace} can only be published when in "Publish" stage.', -1, ['workspace' => $workspaceId]);
            return;
        }
        if (!($dataHandler->doesRecordExist($table, $id, Permission::PAGE_SHOW) && $dataHandler->checkRecordUpdateAccess($table, $id))) {
            $dataHandler->log($table, $id, DatabaseAction::PUBLISH, 0, SystemLogErrorClassification::USER_ERROR, 'You cannot publish a record you do not have edit and show permissions for');
            return;
        }

        // Modify versioned record to become online
        $updatedFields = [
            't3ver_oid' => 0,
            't3ver_wsid' => 0,
            't3ver_stage' => 0,
            't3ver_state' => VersionState::DEFAULT_STATE,
        ];

        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
            $connection->update(
                $table,
                $updatedFields,
                [
                    'uid' => (int)$id,
                ],
                [
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                    \PDO::PARAM_INT,
                ]
            );
        } catch (DBALException $e) {
            $dataHandler->log($table, $id, DatabaseAction::PUBLISH, 0, SystemLogErrorClassification::SYSTEM_ERROR, 'During Publishing: SQL errors happened: {reason}', -1, ['reason' => $e->getPrevious()->getMessage()]);
        }

        if ($dataHandler->enableLogging) {
            $dataHandler->log($table, $id, DatabaseAction::PUBLISH, 0, SystemLogErrorClassification::MESSAGE, 'Publishing successful for table "{table}" uid {uid} (new record)', -1, ['table' => $table, 'uid' => $id], $dataHandler->eventPid($table, $id, $newRecordInWorkspace['pid']));
        }

        // Set log entry for record
        $propArr = $dataHandler->getRecordPropertiesFromRow($table, $newRecordInWorkspace);
        $label = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_tcemain.xlf:version_swap.online_record_updated');
        $dataHandler->log($table, $id, DatabaseAction::UPDATE, $propArr['pid'], SystemLogErrorClassification::MESSAGE, $label, 10, [$propArr['header'], $table . ':' . $id], $propArr['event_pid']);
        $dataHandler->setHistory($table, $id);

        $stageId = StagesService::STAGE_PUBLISH_EXECUTE_ID;
        $notificationEmailInfoKey = $wsAccess['uid'] . ':' . $stageId . ':' . $comment;
        $this->notificationEmailInfo[$notificationEmailInfoKey]['shared'] = [$wsAccess, $stageId, $comment];
        $this->notificationEmailInfo[$notificationEmailInfoKey]['elements'][] = [$table, $id];
        $this->notificationEmailInfo[$notificationEmailInfoKey]['recipients'] = $notificationAlternativeRecipients;
        // Write to log with stageId -20 (STAGE_PUBLISH_EXECUTE_ID)
        $dataHandler->log($table, $id, DatabaseAction::VERSIONIZE, 0, SystemLogErrorClassification::MESSAGE, 'Stage for record was changed to {stage}. Comment was: "{comment}"', -1, ['stage' => $stageId, 'comment' => substr($comment, 0, 100)], $dataHandler->eventPid($table, $id, $newRecordInWorkspace['pid']));
        // Write the stage change to the history (usually this is done in updateDB in DataHandler, but we do a manual SQL change)
        $historyStore = $this->getRecordHistoryStore((int)$wsAccess['uid'], $dataHandler->BE_USER);
        $historyStore->changeStageForRecord($table, $id, ['current' => (int)$newRecordInWorkspace['t3ver_stage'], 'next' => StagesService::STAGE_PUBLISH_EXECUTE_ID, 'comment' => $comment]);

        // Clear cache
        $dataHandler->registerRecordIdForPageCacheClearing($table, $id);
        // Update the reference index: Drop the references in the workspace, but update them in the live workspace
        $dataHandler->registerReferenceIndexRowsForDrop($table, $id, $workspaceId);
        $dataHandler->updateRefIndex($table, $id, 0);
        $this->updateReferenceIndexForL10nOverlays($table, $id, $workspaceId, $dataHandler);

        // When dealing with mm relations on local side, existing refindex rows of the new workspace record
        // need to be re-calculated for the now live record. Scenario ManyToMany Publish createContentAndAddRelation
        // These calls are similar to what is done in DH->versionPublishManyToManyRelations() and can not be
        // used from there since publishing new records does not call that method, see @todo in version_swap().
        $dataHandler->registerReferenceIndexUpdateForReferencesToItem($table, $id, $workspaceId, 0);
        $dataHandler->registerReferenceIndexUpdateForReferencesToItem($table, $id, $workspaceId);
    }

    /**
     * A new record was just published, but the reference index for the localized elements needs
     * an update too.
     *
     * @param string $table
     * @param int $newVersionedRecordId
     * @param int $workspaceId
     * @param DataHandler $dataHandler
     */
    protected function updateReferenceIndexForL10nOverlays(string $table, int $newVersionedRecordId, int $workspaceId, DataHandler $dataHandler): void
    {
        if (!BackendUtility::isTableLocalizable($table)) {
            return;
        }
        if (!BackendUtility::isTableWorkspaceEnabled($table)) {
            return;
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $l10nParentFieldName = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        $constraints = $queryBuilder->expr()->eq(
            $l10nParentFieldName,
            $queryBuilder->createNamedParameter($newVersionedRecordId, \PDO::PARAM_INT)
        );
        $translationSourceFieldName = $GLOBALS['TCA'][$table]['ctrl']['translationSource'] ?? null;
        if ($translationSourceFieldName) {
            $constraints = $queryBuilder->expr()->orX(
                $constraints,
                $queryBuilder->expr()->eq(
                    $translationSourceFieldName,
                    $queryBuilder->createNamedParameter($newVersionedRecordId, \PDO::PARAM_INT)
                )
            );
        }

        $queryBuilder
            ->select('uid', $l10nParentFieldName)
            ->from($table)
            ->where(
                $constraints,
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($workspaceId, \PDO::PARAM_INT)
                )
            );

        if ($translationSourceFieldName) {
            $queryBuilder->addSelect($translationSourceFieldName);
        }

        $statement = $queryBuilder->executeQuery();
        while ($record = $statement->fetchAssociative()) {
            $dataHandler->updateRefIndex($table, $record['uid']);
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
                    ->executeStatement();
            }
        }
    }

    /**
     * Flushes (remove, no soft delete!) elements of a particular workspace to avoid orphan records.
     * This is used if an admin deletes a sys_workspace record.
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
                $queryBuilder->getRestrictions()->removeAll();
                $result = $queryBuilder
                    ->select('uid')
                    ->from($tcaTable)
                    ->where(
                        $queryBuilder->expr()->eq(
                            't3ver_wsid',
                            $queryBuilder->createNamedParameter($workspaceId, \PDO::PARAM_INT)
                        ),
                        // t3ver_oid >= 0 basically omits placeholder records here, those would otherwise
                        // fail to delete later in DH->discard() and would create "can't do that" log entries.
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->gt(
                                't3ver_oid',
                                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                't3ver_state',
                                $queryBuilder->createNamedParameter(VersionState::NEW_PLACEHOLDER, \PDO::PARAM_INT)
                            )
                        )
                    )
                    ->orderBy('uid')
                    ->executeQuery();

                while (($recordId = $result->fetchOne()) !== false) {
                    $command[$tcaTable][$recordId]['version']['action'] = 'flush';
                }
            }
        }
        if (!empty($command)) {
            // Execute the command array via DataHandler to flush all records from this workspace.
            // Switch to target workspace temporarily, otherwise DH->discard() do not
            // operate on correct workspace if fetching additional records.
            $backendUser = $GLOBALS['BE_USER'];
            $savedWorkspace = $backendUser->workspace;
            $backendUser->workspace = $workspaceId;
            $context = GeneralUtility::makeInstance(Context::class);
            $savedWorkspaceContext = $context->getAspect('workspace');
            $context->setAspect('workspace', new WorkspaceAspect($workspaceId));

            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([], $command, $backendUser);
            $dataHandler->process_cmdmap();

            $backendUser->workspace = $savedWorkspace;
            $context->setAspect('workspace', $savedWorkspaceContext);
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
     * Flushes the workspace cache for current workspace and for the virtual "all workspaces" too.
     *
     * @param int $workspaceId The workspace to be flushed in cache
     */
    protected function flushWorkspaceCacheEntriesByWorkspaceId(int $workspaceId): void
    {
        $workspacesCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('workspaces_cache');
        $workspacesCache->flushByTag($workspaceId);
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
                    ->executeQuery();

                while ($row = $statement->fetchAssociative()) {
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
                    ->executeQuery();

                while ($row = $statement->fetchAssociative()) {
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
            ->executeQuery();

        while ($row = $statement->fetchAssociative()) {
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
     * Moves a versioned record, which is not new or deleted.
     *
     * This is critical for a versioned record to be marked as MOVED (t3ver_state=4)
     *
     * @param string $table Table name to move
     * @param int $liveUid Record uid to move (online record)
     * @param int $destPid Position to move to: $destPid: >=0 then it points to a page-id on which to insert the record (as the first element). <0 then it points to a uid from its own table after which to insert it (works if
     * @param int $versionedRecordUid UID of offline version of online record
     * @param DataHandler $dataHandler DataHandler object
     * @see moveRecord()
     */
    protected function moveRecord_moveVersionedRecord(string $table, int $liveUid, int $destPid, int $versionedRecordUid, DataHandler $dataHandler): void
    {
        // If a record gets moved after a record that already has a versioned record
        // then the versioned record needs to be placed after the existing one
        $originalRecordDestinationPid = $destPid;
        $movedTargetRecordInWorkspace = BackendUtility::getWorkspaceVersionOfRecord($dataHandler->BE_USER->workspace, $table, abs($destPid), 'uid');
        if (is_array($movedTargetRecordInWorkspace) && $destPid < 0) {
            $destPid = -$movedTargetRecordInWorkspace['uid'];
        }
        $dataHandler->moveRecord_raw($table, $versionedRecordUid, $destPid);

        $versionedRecord = BackendUtility::getRecord($table, $versionedRecordUid, 'uid,t3ver_state');
        if (!VersionState::cast($versionedRecord['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
            // Update the state of this record to a move placeholder. This is allowed if the
            // record is a 'changed' (t3ver_state=0) record: Changing a record and moving it
            // around later, should switch it from 'changed' to 'moved'. Deleted placeholders
            // however are an 'end-state', they should not be switched to a move placeholder.
            // Scenario: For a live page that has a localization, the localization is first
            // marked as to-delete in workspace, creating a delete placeholder for that
            // localization. Later, the page is moved around, moving the localization along
            // with the default language record. The localization should then NOT be switched
            // from 'to-delete' to 'moved', this would loose the 'to-delete' information.
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($table)
                ->update(
                    $table,
                    [
                        't3ver_state' => (string)new VersionState(VersionState::MOVE_POINTER),
                    ],
                    [
                        'uid' => (int)$versionedRecordUid,
                    ]
                );
        }

        // Check for the localizations of that element and move them as well
        $dataHandler->moveL10nOverlayRecords($table, $liveUid, $destPid, $originalRecordDestinationPid);
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

    protected function emitUpdateTopbarSignal(): void
    {
        BackendUtility::setUpdateSignal('updateTopbar');
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
                $evalCodesArray = GeneralUtility::trimExplode(',', $configArr['config']['eval'] ?? '', true);
                if (in_array('uniqueInPid', $evalCodesArray) || in_array('unique', $evalCodesArray)) {
                    $listArr[] = $field;
                }
            }
        }
        return $listArr;
    }

    /**
     * Straight db based record deletion: sets deleted = 1 for soft-delete
     * enabled tables, or removes row from table. Used for move placeholder
     * records sometimes.
     */
    protected function softOrHardDeleteSingleRecord(string $table, int $uid): void
    {
        $deleteField = $GLOBALS['TCA'][$table]['ctrl']['delete'] ?? null;
        if ($deleteField) {
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($table)
                ->update(
                    $table,
                    [$deleteField => 1],
                    ['uid' => $uid],
                    [\PDO::PARAM_INT]
                );
        } else {
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($table)
                ->delete(
                    $table,
                    ['uid' => $uid]
                );
        }
    }

    /**
     * Makes an instance for RecordHistoryStore. This is needed as DataHandler would usually trigger the setHistory()
     * but has no support for tracking "stage change" information.
     *
     * So we have to do this manually. Usually a $dataHandler->updateDB() could do this, but we use raw update statements
     * here in workspaces for the time being, mostly because we also want to add "comment"
     */
    protected function getRecordHistoryStore(int $workspaceId, BackendUserAuthentication $user): RecordHistoryStore
    {
        return GeneralUtility::makeInstance(
            RecordHistoryStore::class,
            RecordHistoryStore::USER_BACKEND,
            (int)$user->user['uid'],
            $user->getOriginalUserIdWhenInSwitchUserMode(),
            $GLOBALS['EXEC_TIME'],
            $workspaceId
        );
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
