<?php
namespace TYPO3\CMS\Version\Hook;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Contains some parts for staging, versioning and workspaces
 * to interact with the TYPO3 Core Engine
 */
class DataHandlerHook
{
    /**
     * For accumulating information about workspace stages raised
     * on elements so a single mail is sent as notification.
     * previously called "accumulateForNotifEmail" in DataHandler
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
     * @return void
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
     * @return void
     */
    public function processCmdmap($command, $table, $id, $value, &$commandIsProcessed, DataHandler $dataHandler)
    {
        // custom command "version"
        if ($command == 'version') {
            $commandIsProcessed = true;
            $action = (string)$value['action'];
            $comment = !empty($value['comment']) ? $value['comment'] : '';
            $notificationAlternativeRecipients = (isset($value['notificationAlternativeRecipients'])) && is_array($value['notificationAlternativeRecipients']) ? $value['notificationAlternativeRecipients'] : [];
            switch ($action) {
                case 'new':
                    $dataHandler->versionizeRecord($table, $id, $value['label']);
                    break;
                case 'swap':
                    $this->version_swap($table, $id, $value['swapWith'], $value['swapIntoWS'],
                        $dataHandler,
                        $comment,
                        true,
                        $notificationAlternativeRecipients
                    );
                    break;
                case 'clearWSID':
                    $this->version_clearWSID($table, $id, false, $dataHandler);
                    break;
                case 'flush':
                    $this->version_clearWSID($table, $id, true, $dataHandler);
                    break;
                case 'setStage':
                    $elementIds = GeneralUtility::trimExplode(',', $id, true);
                    foreach ($elementIds as $elementId) {
                        $this->version_setStage($table, $elementId, $value['stageId'],
                                $comment,
                                true,
                                $dataHandler,
                                $notificationAlternativeRecipients
                            );
                    }
                    break;
                default:
                    // Do nothing
            }
        }
    }

    /**
     * hook that is called AFTER all commands of the commandmap was
     * executed
     *
     * @param DataHandler $dataHandler reference to the main DataHandler object
     * @return void
     */
    public function processCmdmap_afterFinish(DataHandler $dataHandler)
    {
        // Empty accumulation array:
        foreach ($this->notificationEmailInfo as $notifItem) {
            $this->notifyStageChange($notifItem['shared'][0], $notifItem['shared'][1], implode(', ', $notifItem['elements']), 0, $notifItem['shared'][2], $dataHandler, $notifItem['alternativeRecipients']);
        }
        // Reset notification array
        $this->notificationEmailInfo = [];
        // Reset remapped IDs
        $this->remappedIds = [];
    }

    /**
     * hook that is called when an element shall get deleted
     *
     * @param string $table the table of the record
     * @param int $id the ID of the record
     * @param array $record The accordant database record
     * @param bool $recordWasDeleted can be set so that other hooks or
     * @param DataHandler $dataHandler reference to the main DataHandler object
     * @return void
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
        if ($record['pid'] != -1) {
            if ($wsVersion = BackendUtility::getWorkspaceVersionOfRecord($dataHandler->BE_USER->workspace, $table, $id)) {
                $record = $wsVersion;
                $id = $record['uid'];
            }
        }
        $recordVersionState = VersionState::cast($record['t3ver_state']);
        // Look, if record is an offline version, then delete directly:
        if ($record['pid'] == -1) {
            if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
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
                                [
                                    't3ver_label' => 'DELETED!',
                                    't3ver_state' => 2,
                                ],
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
                        $this->version_clearWSID($table, $id, false, $dataHandler);
                    }
                } else {
                    $dataHandler->newlog('Tried to delete record from another workspace', 1);
                }
            } else {
                $dataHandler->newlog('Versioning not enabled for record with PID = -1!', 2);
            }
        } elseif ($res = $dataHandler->BE_USER->workspaceAllowLiveRecordsInPID($record['pid'], $table)) {
            // Look, if record is "online" or in a versionized branch, then delete directly.
            if ($res > 0) {
                $dataHandler->deleteEl($table, $id);
            } else {
                $dataHandler->newlog('Stage of root point did not allow for deletion', 1);
            }
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
     * @return void
     */
    public function moveRecord($table, $uid, $destPid, array $propArr, array $moveRec, $resolvedPid, &$recordWasMoved, DataHandler $dataHandler)
    {
        // Only do something in Draft workspace
        if ($dataHandler->BE_USER->workspace === 0) {
            return;
        }
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
        $WSversion = BackendUtility::getWorkspaceVersionOfRecord($dataHandler->BE_USER->workspace, $table, $uid, 'uid,t3ver_oid');
        // Handle move-placeholders if the current record is not one already
        if (
            BackendUtility::isTableWorkspaceEnabled($table)
            && !$moveRecVersionState->equals(VersionState::MOVE_PLACEHOLDER)
        ) {
            // Create version of record first, if it does not exist
            if (empty($WSversion['uid'])) {
                $dataHandler->versionizeRecord($table, $uid, 'MovePointer');
                $WSversion = BackendUtility::getWorkspaceVersionOfRecord($dataHandler->BE_USER->workspace, $table, $uid, 'uid,t3ver_oid');
                $this->moveRecord_processFields($dataHandler, $resolvedPid, $table, $uid);
            // If the record has been versioned before (e.g. cascaded parent-child structure), create only the move-placeholders
            } elseif ($dataHandler->isRecordCopied($table, $uid) && (int)$dataHandler->copyMappingArray[$table][$uid] === (int)$WSversion['uid']) {
                $this->moveRecord_processFields($dataHandler, $resolvedPid, $table, $uid);
            }
        }
        // Check workspace permissions:
        $workspaceAccessBlocked = [];
        // Element was in "New/Deleted/Moved" so it can be moved...
        $recIsNewVersion = $moveRecVersionState->indicatesPlaceholder();
        $destRes = $dataHandler->BE_USER->workspaceAllowLiveRecordsInPID($resolvedPid, $table);
        $canMoveRecord = ($recIsNewVersion || BackendUtility::isTableWorkspaceEnabled($table));
        // Workspace source check:
        if (!$recIsNewVersion) {
            $errorCode = $dataHandler->BE_USER->workspaceCannotEditRecord($table, $WSversion['uid'] ? $WSversion['uid'] : $uid);
            if ($errorCode) {
                $workspaceAccessBlocked['src1'] = 'Record could not be edited in workspace: ' . $errorCode . ' ';
            } elseif (!$canMoveRecord && $dataHandler->BE_USER->workspaceAllowLiveRecordsInPID($moveRec['pid'], $table) <= 0) {
                $workspaceAccessBlocked['src2'] = 'Could not remove record from table "' . $table . '" from its page "' . $moveRec['pid'] . '" ';
            }
        }
        // Workspace destination check:
        // All records can be inserted if $destRes is greater than zero.
        // Only new versions can be inserted if $destRes is FALSE.
        // NO RECORDS can be inserted if $destRes is negative which indicates a stage
        //  not allowed for use. If "versioningWS" is version 2, moving can take place of versions.
        // since TYPO3 CMS 7, version2 is the default and the only option
        if (!($destRes > 0 || $canMoveRecord && !$destRes)) {
            $workspaceAccessBlocked['dest1'] = 'Could not insert record from table "' . $table . '" in destination PID "' . $resolvedPid . '" ';
        } elseif ($destRes == 1 && $WSversion['uid']) {
            $workspaceAccessBlocked['dest2'] = 'Could not insert other versions in destination PID ';
        }
        if (empty($workspaceAccessBlocked)) {
            // If the move operation is done on a versioned record, which is
            // NOT new/deleted placeholder and versioningWS is in version 2, then...
            // since TYPO3 CMS 7, version2 is the default and the only option
            if ($WSversion['uid'] && !$recIsNewVersion && BackendUtility::isTableWorkspaceEnabled($table)) {
                $this->moveRecord_wsPlaceholders($table, $uid, $destPid, $WSversion['uid'], $dataHandler);
            } else {
                // moving not needed, just behave like in live workspace
                $recordWasMoved = false;
            }
        } else {
            $dataHandler->newlog('Move attempt failed due to workspace restrictions: ' . implode(' // ', $workspaceAccessBlocked), 1);
        }
    }

    /**
     * Processes fields of a moved record and follows references.
     *
     * @param DataHandler $dataHandler Calling DataHandler instance
     * @param int $resolvedPageId Resolved real destination page id
     * @param string $table Name of parent table
     * @param int $uid UID of the parent record
     * @return void
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
                $dataHandler, $resolvedPageId,
                $table, $uid, $field, $value,
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
     * @param string $field Name of the field of the parent record
     * @param string $value Value of the field of the parent record
     * @param array $configuration TCA field configuration of the parent record
     * @return void
     */
    protected function moveRecord_processFieldValue(DataHandler $dataHandler, $resolvedPageId, $table, $uid, $field, $value, array $configuration)
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
     *****  Notifications  ******
     ****************************/
    /**
     * Send an email notification to users in workspace
     *
     * @param array $stat Workspace access array from \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::checkWorkspace()
     * @param int $stageId New Stage number: 0 = editing, 1= just ready for review, 10 = ready for publication, -1 = rejected!
     * @param string $table Table name of element (or list of element names if $id is zero)
     * @param int $id Record uid of element (if zero, then $table is used as reference to element(s) alone)
     * @param string $comment User comment sent along with action
     * @param DataHandler $dataHandler DataHandler object
     * @param array $notificationAlternativeRecipients List of recipients to notify instead of be_users selected by sys_workspace, list is generated by workspace extension module
     * @return void
     */
    protected function notifyStageChange(array $stat, $stageId, $table, $id, $comment, DataHandler $dataHandler, array $notificationAlternativeRecipients = [])
    {
        $workspaceRec = BackendUtility::getRecord('sys_workspace', $stat['uid']);
        // So, if $id is not set, then $table is taken to be the complete element name!
        $elementName = $id ? $table . ':' . $id : $table;
        if (!is_array($workspaceRec)) {
            return;
        }

        // Get the new stage title from workspaces library, if workspaces extension is installed
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
            $stageService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\StagesService::class);
            $newStage = $stageService->getStageTitle((int)$stageId);
        } else {
            // @todo CONSTANTS SHOULD BE USED - tx_service_workspace_workspaces
            // @todo use localized labels
            // Compile label:
            switch ((int)$stageId) {
                case 1:
                    $newStage = 'Ready for review';
                    break;
                case 10:
                    $newStage = 'Ready for publishing';
                    break;
                case -1:
                    $newStage = 'Element was rejected!';
                    break;
                case 0:
                    $newStage = 'Rejected element was noticed and edited';
                    break;
                default:
                    $newStage = 'Unknown state change!?';
            }
        }
        if (empty($notificationAlternativeRecipients)) {
            // Compile list of recipients:
            $emails = [];
            switch ((int)$stat['stagechg_notification']) {
                case 1:
                    switch ((int)$stageId) {
                        case 1:
                            $emails = $this->getEmailsForStageChangeNotification($workspaceRec['reviewers']);
                            break;
                        case 10:
                            $emails = $this->getEmailsForStageChangeNotification($workspaceRec['adminusers'], true);
                            break;
                        case -1:
                            // List of elements to reject:
                            $allElements = explode(',', $elementName);
                            // Traverse them, and find the history of each
                            foreach ($allElements as $elRef) {
                                list($eTable, $eUid) = explode(':', $elRef);

                                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                                    ->getQueryBuilderForTable('sys_log');

                                $queryBuilder->getRestrictions()->removeAll();

                                $result = $queryBuilder
                                    ->select('log_data', 'tstamp', 'userid')
                                    ->from('sys_log')
                                    ->where(
                                        $queryBuilder->expr()->eq(
                                            'action',
                                            $queryBuilder->createNamedParameter(6, \PDO::PARAM_INT)
                                        ),
                                        $queryBuilder->expr()->eq(
                                            'details_nr',
                                            $queryBuilder->createNamedParameter(30, \PDO::PARAM_INT)
                                        ),
                                        $queryBuilder->expr()->eq(
                                            'tablename',
                                            $queryBuilder->createNamedParameter($eTable, \PDO::PARAM_STR)
                                        ),
                                        $queryBuilder->expr()->eq(
                                            'recuid',
                                            $queryBuilder->createNamedParameter($eUid, \PDO::PARAM_INT)
                                        )
                                    )
                                    ->orderBy('uid', 'DESC')
                                    ->execute();

                                // Find all implicated since the last stage-raise from editing to review:
                                while ($dat = $result->fetch()) {
                                    $data = unserialize($dat['log_data']);
                                    $emails = $this->getEmailsForStageChangeNotification($dat['userid'], true) + $emails;
                                    if ($data['stage'] == 1) {
                                        break;
                                    }
                                }
                            }
                            break;
                        case 0:
                            $emails = $this->getEmailsForStageChangeNotification($workspaceRec['members']);
                            break;
                        default:
                            $emails = $this->getEmailsForStageChangeNotification($workspaceRec['adminusers'], true);
                    }
                    break;
                case 10:
                    $emails = $this->getEmailsForStageChangeNotification($workspaceRec['adminusers'], true);
                    $emails = $this->getEmailsForStageChangeNotification($workspaceRec['reviewers']) + $emails;
                    $emails = $this->getEmailsForStageChangeNotification($workspaceRec['members']) + $emails;
                    break;
                default:
                    // Do nothing
            }
        } else {
            $emails = $notificationAlternativeRecipients;
        }
        // prepare and then send the emails
        if (!empty($emails)) {
            // Path to record is found:
            list($elementTable, $elementUid) = explode(':', $elementName);
            $elementUid = (int)$elementUid;
            $elementRecord = BackendUtility::getRecord($elementTable, $elementUid);
            $recordTitle = BackendUtility::getRecordTitle($elementTable, $elementRecord);
            if ($elementTable == 'pages') {
                $pageUid = $elementUid;
            } else {
                BackendUtility::fixVersioningPid($elementTable, $elementRecord);
                $pageUid = ($elementUid = $elementRecord['pid']);
            }

            // new way, options are
            // pageTSconfig: tx_version.workspaces.stageNotificationEmail.subject
            // userTSconfig: page.tx_version.workspaces.stageNotificationEmail.subject
            $pageTsConfig = BackendUtility::getPagesTSconfig($pageUid);
            $emailConfig = $pageTsConfig['tx_version.']['workspaces.']['stageNotificationEmail.'];
            $markers = [
                '###RECORD_TITLE###' => $recordTitle,
                '###RECORD_PATH###' => BackendUtility::getRecordPath($elementUid, '', 20),
                '###SITE_NAME###' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                '###SITE_URL###' => GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir,
                '###WORKSPACE_TITLE###' => $workspaceRec['title'],
                '###WORKSPACE_UID###' => $workspaceRec['uid'],
                '###ELEMENT_NAME###' => $elementName,
                '###NEXT_STAGE###' => $newStage,
                '###COMMENT###' => $comment,
                // See: #30212 - keep both markers for compatibility
                '###USER_REALNAME###' => $dataHandler->BE_USER->user['realName'],
                '###USER_FULLNAME###' => $dataHandler->BE_USER->user['realName'],
                '###USER_USERNAME###' => $dataHandler->BE_USER->user['username']
            ];
            // add marker for preview links if workspace extension is loaded
            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
                $this->workspaceService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
                // only generate the link if the marker is in the template - prevents database from getting to much entries
                if (GeneralUtility::isFirstPartOfStr($emailConfig['message'], 'LLL:')) {
                    $tempEmailMessage = $this->getLanguageService()->sL($emailConfig['message']);
                } else {
                    $tempEmailMessage = $emailConfig['message'];
                }
                if (strpos($tempEmailMessage, '###PREVIEW_LINK###') !== false) {
                    $markers['###PREVIEW_LINK###'] = $this->workspaceService->generateWorkspacePreviewLink($elementUid);
                }
                unset($tempEmailMessage);
                $markers['###SPLITTED_PREVIEW_LINK###'] = $this->workspaceService->generateWorkspaceSplittedPreviewLink($elementUid, true);
            }
            // Hook for preprocessing of the content for formmails:
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/version/class.tx_version_tcemain.php']['notifyStageChange-postModifyMarkers'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/version/class.tx_version_tcemain.php']['notifyStageChange-postModifyMarkers'] as $_classRef) {
                    $_procObj =& GeneralUtility::getUserObj($_classRef);
                    $markers = $_procObj->postModifyMarkers($markers, $this);
                }
            }
            // send an email to each individual user, to ensure the
            // multilanguage version of the email
            $emailRecipients = [];
            // an array of language objects that are needed
            // for emails with different languages
            $languageObjects = [
                $this->getLanguageService()->lang => $this->getLanguageService()
            ];
            // loop through each recipient and send the email
            foreach ($emails as $recipientData) {
                // don't send an email twice
                if (isset($emailRecipients[$recipientData['email']])) {
                    continue;
                }
                $emailSubject = $emailConfig['subject'];
                $emailMessage = $emailConfig['message'];
                $emailRecipients[$recipientData['email']] = $recipientData['email'];
                // check if the email needs to be localized
                // in the users' language
                if (GeneralUtility::isFirstPartOfStr($emailSubject, 'LLL:') || GeneralUtility::isFirstPartOfStr($emailMessage, 'LLL:')) {
                    $recipientLanguage = $recipientData['lang'] ? $recipientData['lang'] : 'default';
                    if (!isset($languageObjects[$recipientLanguage])) {
                        // a LANG object in this language hasn't been
                        // instantiated yet, so this is done here
                        /** @var $languageObject \TYPO3\CMS\Lang\LanguageService */
                        $languageObject = GeneralUtility::makeInstance(\TYPO3\CMS\Lang\LanguageService::class);
                        $languageObject->init($recipientLanguage);
                        $languageObjects[$recipientLanguage] = $languageObject;
                    } else {
                        $languageObject = $languageObjects[$recipientLanguage];
                    }
                    if (GeneralUtility::isFirstPartOfStr($emailSubject, 'LLL:')) {
                        $emailSubject = $languageObject->sL($emailSubject);
                    }
                    if (GeneralUtility::isFirstPartOfStr($emailMessage, 'LLL:')) {
                        $emailMessage = $languageObject->sL($emailMessage);
                    }
                }
                $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
                $emailSubject = $templateService->substituteMarkerArray($emailSubject, $markers, '', true, true);
                $emailMessage = $templateService->substituteMarkerArray($emailMessage, $markers, '', true, true);
                // Send an email to the recipient
                /** @var $mail \TYPO3\CMS\Core\Mail\MailMessage */
                $mail = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
                if (!empty($recipientData['realName'])) {
                    $recipient = [$recipientData['email'] => $recipientData['realName']];
                } else {
                    $recipient = $recipientData['email'];
                }
                $mail->setTo($recipient)
                    ->setSubject($emailSubject)
                    ->setFrom(\TYPO3\CMS\Core\Utility\MailUtility::getSystemFrom())
                    ->setBody($emailMessage);
                $mail->send();
            }
            $emailRecipients = implode(',', $emailRecipients);
            $dataHandler->newlog2('Notification email for stage change was sent to "' . $emailRecipients . '"', $table, $id);
        }
    }

    /**
     * Return be_users that should be notified on stage change from input list.
     * previously called notifyStageChange_getEmails() in DataHandler
     *
     * @param string $listOfUsers List of backend users, on the form "be_users_10,be_users_2" or "10,2" in case noTablePrefix is set.
     * @param bool $noTablePrefix If set, the input list are integers and not strings.
     * @return array Array of emails
     */
    protected function getEmailsForStageChangeNotification($listOfUsers, $noTablePrefix = false)
    {
        $users = GeneralUtility::trimExplode(',', $listOfUsers, true);
        $emails = [];
        foreach ($users as $userIdent) {
            if ($noTablePrefix) {
                $id = (int)$userIdent;
            } else {
                list($table, $id) = GeneralUtility::revExplode('_', $userIdent, 2);
            }
            if ($table === 'be_users' || $noTablePrefix) {
                if ($userRecord = BackendUtility::getRecord('be_users', $id, 'uid,email,lang,realName', BackendUtility::BEenableFields('be_users'))) {
                    if (trim($userRecord['email']) !== '') {
                        $emails[$id] = $userRecord;
                    }
                }
            }
        }
        return $emails;
    }

    /****************************
     *****  Stage Changes  ******
     ****************************/
    /**
     * Setting stage of record
     *
     * @param string $table Table name
     * @param int $integer Record UID
     * @param int $stageId Stage ID to set
     * @param string $comment Comment that goes into log
     * @param bool $notificationEmailInfo Accumulate state changes in memory for compiled notification email?
     * @param DataHandler $dataHandler DataHandler object
     * @param array $notificationAlternativeRecipients comma separated list of recipients to notify instead of normal be_users
     * @return void
     */
    protected function version_setStage($table, $id, $stageId, $comment = '', $notificationEmailInfo = false, DataHandler $dataHandler, array $notificationAlternativeRecipients = [])
    {
        if ($errorCode = $dataHandler->BE_USER->workspaceCannotEditOfflineVersion($table, $id)) {
            $dataHandler->newlog('Attempt to set stage for record failed: ' . $errorCode, 1);
        } elseif ($dataHandler->checkRecordUpdateAccess($table, $id)) {
            $record = BackendUtility::getRecord($table, $id);
            $stat = $dataHandler->BE_USER->checkWorkspace($record['t3ver_wsid']);
            // check if the usere is allowed to the current stage, so it's also allowed to send to next stage
            if ($GLOBALS['BE_USER']->workspaceCheckStageForCurrent($record['t3ver_stage'])) {
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
                $dataHandler->newlog2('Stage for record was changed to ' . $stageId . '. Comment was: "' . substr($comment, 0, 100) . '"', $table, $id);
                // TEMPORARY, except 6-30 as action/detail number which is observed elsewhere!
                $dataHandler->log($table, $id, 6, 0, 0, 'Stage raised...', 30, ['comment' => $comment, 'stage' => $stageId]);
                if ((int)$stat['stagechg_notification'] > 0) {
                    if ($notificationEmailInfo) {
                        $this->notificationEmailInfo[$stat['uid'] . ':' . $stageId . ':' . $comment]['shared'] = [$stat, $stageId, $comment];
                        $this->notificationEmailInfo[$stat['uid'] . ':' . $stageId . ':' . $comment]['elements'][] = $table . ':' . $id;
                        $this->notificationEmailInfo[$stat['uid'] . ':' . $stageId . ':' . $comment]['alternativeRecipients'] = $notificationAlternativeRecipients;
                    } else {
                        $this->notifyStageChange($stat, $stageId, $table, $id, $comment, $dataHandler, $notificationAlternativeRecipients);
                    }
                }
            } else {
                $dataHandler->newlog('The member user tried to set a stage value "' . $stageId . '" that was not allowed', 1);
            }
        } else {
            $dataHandler->newlog('Attempt to set stage for record failed because you do not have edit access', 1);
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
     * @param bool $notificationEmailInfo Accumulate state changes in memory for compiled notification email?
     * @param array $notificationAlternativeRecipients comma separated list of recipients to notificate instead of normal be_users
     * @return void
     */
    protected function version_swap($table, $id, $swapWith, $swapIntoWS = 0, DataHandler $dataHandler, $comment = '', $notificationEmailInfo = false, $notificationAlternativeRecipients = [])
    {

        // Check prerequisites before start swapping

        // Skip records that have been deleted during the current execution
        if ($dataHandler->hasDeletedRecord($table, $id)) {
            return;
        }

        // First, check if we may actually edit the online record
        if (!$dataHandler->checkRecordUpdateAccess($table, $id)) {
            $dataHandler->newlog('Error: You cannot swap versions for a record you do not have access to edit!', 1);
            return;
        }
        // Select the two versions:
        $curVersion = BackendUtility::getRecord($table, $id, '*');
        $swapVersion = BackendUtility::getRecord($table, $swapWith, '*');
        $movePlh = [];
        $movePlhID = 0;
        if (!(is_array($curVersion) && is_array($swapVersion))) {
            $dataHandler->newlog('Error: Either online or swap version could not be selected!', 2);
            return;
        }
        if (!$dataHandler->BE_USER->workspacePublishAccess($swapVersion['t3ver_wsid'])) {
            $dataHandler->newlog('User could not publish records from workspace #' . $swapVersion['t3ver_wsid'], 1);
            return;
        }
        $wsAccess = $dataHandler->BE_USER->checkWorkspace($swapVersion['t3ver_wsid']);
        if (!($swapVersion['t3ver_wsid'] <= 0 || !($wsAccess['publish_access'] & 1) || (int)$swapVersion['t3ver_stage'] === -10)) {
            $dataHandler->newlog('Records in workspace #' . $swapVersion['t3ver_wsid'] . ' can only be published when in "Publish" stage.', 1);
            return;
        }
        if (!($dataHandler->doesRecordExist($table, $swapWith, 'show') && $dataHandler->checkRecordUpdateAccess($table, $swapWith))) {
            $dataHandler->newlog('You cannot publish a record you do not have edit and show permissions for', 1);
            return;
        }
        if ($swapIntoWS && !$dataHandler->BE_USER->workspaceSwapAccess()) {
            $dataHandler->newlog('Workspace #' . $swapVersion['t3ver_wsid'] . ' does not support swapping.', 1);
            return;
        }
        // Check if the swapWith record really IS a version of the original!
        if (!(((int)$swapVersion['pid'] == -1 && (int)$curVersion['pid'] >= 0) && (int)$swapVersion['t3ver_oid'] === (int)$id)) {
            $dataHandler->newlog('In swap version, either pid was not -1 or the t3ver_oid didn\'t match the id of the online version as it must!', 2);
            return;
        }
        // Lock file name:
        $lockFileName = PATH_site . 'typo3temp/var/swap_locking/' . $table . '_' . $id . '.ser';
        if (@is_file($lockFileName)) {
            $dataHandler->newlog('A swapping lock file was present. Either another swap process is already running or a previous swap process failed. Ask your administrator to handle the situation.', 2);
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
        if ($table !== 'pages_language_overlay' && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']) {
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
        $t3ver_state['curVersion'] = $curVersion['t3ver_state'];
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
                $this->version_swap_processFields($table, $field, $fieldConf['config'], $curVersion, $swapVersion, $dataHandler);
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
        $connection->update(
            $table,
            $swapVersion,
            ['uid' => (int)$id]
        );

        if ($connection->errorCode()) {
            $sqlErrors[] = $connection->errorInfo();
        } else {
            $connection->update(
                $table,
                $curVersion,
                ['uid' => (int)$swapWith]
            );

            if ($connection->errorCode()) {
                $sqlErrors[] = $connection->errorInfo();
            } else {
                unlink($lockFileName);
            }
        }
        if (!empty($sqlErrors)) {
            $dataHandler->newlog('During Swapping: SQL errors happened: ' . implode('; ', $sqlErrors), 2);
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
            $dataHandler->newlog2(($swapIntoWS ? 'Swapping' : 'Publishing') . ' successful for table "' . $table . '" uid ' . $id . '=>' . $swapWith, $table, $id, $swapVersion['pid']);
            // Update reference index of the live record:
            $dataHandler->addRemapStackRefIndex($table, $id);
            // Set log entry for live record:
            $propArr = $dataHandler->getRecordPropertiesFromRow($table, $swapVersion);
            if ($propArr['_ORIG_pid'] == -1) {
                $label = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_tcemain.xlf:version_swap.offline_record_updated');
            } else {
                $label = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_tcemain.xlf:version_swap.online_record_updated');
            }
            $theLogId = $dataHandler->log($table, $id, 2, $propArr['pid'], 0, $label, 10, [$propArr['header'], $table . ':' . $id], $propArr['event_pid']);
            $dataHandler->setHistory($table, $id, $theLogId);
            // Update reference index of the offline record:
            $dataHandler->addRemapStackRefIndex($table, $swapWith);
            // Set log entry for offline record:
            $propArr = $dataHandler->getRecordPropertiesFromRow($table, $curVersion);
            if ($propArr['_ORIG_pid'] == -1) {
                $label = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_tcemain.xlf:version_swap.offline_record_updated');
            } else {
                $label = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_tcemain.xlf:version_swap.online_record_updated');
            }
            $theLogId = $dataHandler->log($table, $swapWith, 2, $propArr['pid'], 0, $label, 10, [$propArr['header'], $table . ':' . $swapWith], $propArr['event_pid']);
            $dataHandler->setHistory($table, $swapWith, $theLogId);

            $stageId = -20; // \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_PUBLISH_EXECUTE_ID;
            if ($notificationEmailInfo) {
                $notificationEmailInfoKey = $wsAccess['uid'] . ':' . $stageId . ':' . $comment;
                $this->notificationEmailInfo[$notificationEmailInfoKey]['shared'] = [$wsAccess, $stageId, $comment];
                $this->notificationEmailInfo[$notificationEmailInfoKey]['elements'][] = $table . ':' . $id;
                $this->notificationEmailInfo[$notificationEmailInfoKey]['alternativeRecipients'] = $notificationAlternativeRecipients;
            } else {
                $this->notifyStageChange($wsAccess, $stageId, $table, $id, $comment, $dataHandler, $notificationAlternativeRecipients);
            }
                // Write to log with stageId -20
            $dataHandler->newlog2('Stage for record was changed to ' . $stageId . '. Comment was: "' . substr($comment, 0, 100) . '"', $table, $id);
            $dataHandler->log($table, $id, 6, 0, 0, 'Published', 30, ['comment' => $comment, 'stage' => $stageId]);

            // Clear cache:
            $dataHandler->registerRecordIdForPageCacheClearing($table, $id);
            // Checking for "new-placeholder" and if found, delete it (BUT FIRST after swapping!):
            if (!$swapIntoWS && $t3ver_state['curVersion'] > 0) {
                // For delete + completely delete!
                $dataHandler->deleteEl($table, $swapWith, true, true);
            }

            //Update reference index for live workspace too:
            /** @var $refIndexObj \TYPO3\CMS\Core\Database\ReferenceIndex */
            $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
            $refIndexObj->setWorkspaceId(0);
            $refIndexObj->updateRefIndexTable($table, $id);
            $refIndexObj->updateRefIndexTable($table, $swapWith);
        }
    }

    /**
     * Writes remapped foreign field (IRRE).
     *
     * @param \TYPO3\CMS\Core\Database\RelationHandler $dbAnalysis Instance that holds the sorting order of child records
     * @param array $configuration The TCA field configuration
     * @param int $parentId The uid of the parent record
     * @return void
     */
    public function writeRemappedForeignField(\TYPO3\CMS\Core\Database\RelationHandler $dbAnalysis, array $configuration, $parentId)
    {
        foreach ($dbAnalysis->itemArray as &$item) {
            if (isset($this->remappedIds[$item['table']][$item['id']])) {
                $item['id'] = $this->remappedIds[$item['table']][$item['id']];
            }
        }
        $dbAnalysis->writeForeignField($configuration, $parentId);
    }

    /**
     * Processes fields of a record for the publishing/swapping process.
     * Basically this takes care of IRRE (type "inline") child references.
     *
     * @param string $tableName Table name
     * @param string $fieldName: Field name
     * @param array $configuration TCA field configuration
     * @param array $liveData: Live record data
     * @param array $versionData: Version record data
     * @param DataHandler $dataHandler Calling data-handler object
     * @return void
     */
    protected function version_swap_processFields($tableName, $fieldName, array $configuration, array $liveData, array $versionData, DataHandler $dataHandler)
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
        if (count($liveRelations->itemArray)) {
            $dataHandler->addRemapAction(
                    $tableName, $liveData['uid'],
                    [$this, 'updateInlineForeignFieldSorting'],
                    [$tableName, $liveData['uid'], $foreignTable, $liveRelations->tableArray[$foreignTable], $configuration, $dataHandler->BE_USER->workspace]
            );
        }
        if (count($versionRelations->itemArray)) {
            $dataHandler->addRemapAction(
                    $tableName, $liveData['uid'],
                    [$this, 'updateInlineForeignFieldSorting'],
                    [$tableName, $liveData['uid'], $foreignTable, $versionRelations->tableArray[$foreignTable], $configuration, 0]
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
     * @param string $parentTableName
     * @param string $parentId
     * @param string $foreignTableName
     * @param int[] $foreignIds
     * @param array $configuration
     * @param int $targetWorkspaceId
     * @return void
     * @internal
     */
    public function updateInlineForeignFieldSorting($parentTableName, $parentId, $foreignTableName, $foreignIds, array $configuration, $targetWorkspaceId)
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
     * Release version from this workspace (and into "Live" workspace but as an offline version).
     *
     * @param string $table Table name
     * @param int $id Record UID
     * @param bool $flush If set, will completely delete element
     * @param DataHandler $dataHandler DataHandler object
     * @return void
     */
    protected function version_clearWSID($table, $id, $flush = false, DataHandler $dataHandler)
    {
        if ($errorCode = $dataHandler->BE_USER->workspaceCannotEditOfflineVersion($table, $id)) {
            $dataHandler->newlog('Attempt to reset workspace for record failed: ' . $errorCode, 1);
            return;
        }
        if (!$dataHandler->checkRecordUpdateAccess($table, $id)) {
            $dataHandler->newlog('Attempt to reset workspace for record failed because you do not have edit access', 1);
            return;
        }
        $liveRec = BackendUtility::getLiveVersionOfRecord($table, $id, 'uid,t3ver_state');
        if (!$liveRec) {
            return;
        }
        // Clear workspace ID:
        $updateData = [
            't3ver_wsid' => 0,
            't3ver_tstamp' => $GLOBALS['EXEC_TIME']
        ];
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $connection->update(
            $table,
            $updateData,
            ['uid' => (int)$id]
        );

        // Clear workspace ID for live version AND DELETE IT as well because it is a new record!
        if (
            VersionState::cast($liveRec['t3ver_state'])->equals(VersionState::NEW_PLACEHOLDER)
            || VersionState::cast($liveRec['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            $connection->update(
                $table,
                $updateData,
                ['uid' => (int)$liveRec['uid']]
            );

            // THIS assumes that the record was placeholder ONLY for ONE record (namely $id)
            $dataHandler->deleteEl($table, $liveRec['uid'], true);
        }
        // If "deleted" flag is set for the version that got released
        // it doesn't make sense to keep that "placeholder" anymore and we delete it completly.
        $wsRec = BackendUtility::getRecord($table, $id);
        if (
            $flush
            || (
                VersionState::cast($wsRec['t3ver_state'])->equals(VersionState::NEW_PLACEHOLDER)
                || VersionState::cast($wsRec['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
            )
        ) {
            $dataHandler->deleteEl($table, $id, true, true);
        }
        // Remove the move-placeholder if found for live record.
        if (BackendUtility::isTableWorkspaceEnabled($table)) {
            if ($plhRec = BackendUtility::getMovePlaceholder($table, $liveRec['uid'], 'uid')) {
                $dataHandler->deleteEl($table, $plhRec['uid'], true, true);
            }
        }
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
        if ($table != 'pages') {
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
            if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS'] && $table !== 'pages') {
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
                        $queryBuilder->expr()->eq(
                            'A.pid',
                            $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
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
     * @return void
     */
    public function findPageElementsForVersionStageChange(array $pageIdList, $workspaceId, array &$elementList)
    {
        if ($workspaceId == 0) {
            return;
        }
        // Traversing all tables supporting versioning:
        foreach ($GLOBALS['TCA'] as $table => $cfg) {
            if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS'] && $table !== 'pages') {
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
                        $queryBuilder->expr()->eq(
                            'A.pid',
                            $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
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
     * @param int $workspaceId Workspace ID. We need this parameter because user can be in LIVE but he still can publisg DRAFT from ws module!
     * @param array $pageIdList List of found page UIDs
     * @param array $elementList List of found element UIDs. Key is table name, value is list of UIDs
     * @return void
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
                $queryBuilder->expr()->eq(
                    'A.pid',
                    $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
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
     * @return void
     */
    public function findRealPageIds(array &$idList)
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
     * @param int $wsUid UID of offline version of online record
     * @param DataHandler $dataHandler DataHandler object
     * @return void
     * @see moveRecord()
     */
    protected function moveRecord_wsPlaceholders($table, $uid, $destPid, $wsUid, DataHandler $dataHandler)
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

            // Use property for move placeholders if set (since TYPO3 CMS 6.2)
            if (isset($GLOBALS['TCA'][$table]['ctrl']['shadowColumnsForMovePlaceholders'])) {
                $shadowColumnsForMovePlaceholder = $GLOBALS['TCA'][$table]['ctrl']['shadowColumnsForMovePlaceholders'];
            // Fallback to property for new placeholder (existed long time before TYPO3 CMS 6.2)
            } elseif (isset($GLOBALS['TCA'][$table]['ctrl']['shadowColumnsForNewPlaceholders'])) {
                $shadowColumnsForMovePlaceholder = $GLOBALS['TCA'][$table]['ctrl']['shadowColumnsForNewPlaceholders'];
            }

            // Set values from the versioned record to the move placeholder
            if (!empty($shadowColumnsForMovePlaceholder)) {
                $versionedRecord = BackendUtility::getRecord($table, $wsUid);
                $shadowColumns = GeneralUtility::trimExplode(',', $shadowColumnsForMovePlaceholder, true);
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
            if ($table == 'pages') {
                // Copy page access settings from original page to placeholder
                $perms_clause = $dataHandler->BE_USER->getPagePermsClause(1);
                $access = BackendUtility::readPageAccess($uid, $perms_clause);
                $newVersion_placeholderFieldArray['perms_userid'] = $access['perms_userid'];
                $newVersion_placeholderFieldArray['perms_groupid'] = $access['perms_groupid'];
                $newVersion_placeholderFieldArray['perms_user'] = $access['perms_user'];
                $newVersion_placeholderFieldArray['perms_group'] = $access['perms_group'];
                $newVersion_placeholderFieldArray['perms_everybody'] = $access['perms_everybody'];
            }
            $newVersion_placeholderFieldArray['t3ver_label'] = 'MovePlaceholder #' . $uid;
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
                    ['uid' => (int)$wsUid]
                );
        }
        // Check for the localizations of that element and move them as well
        $dataHandler->moveL10nOverlayRecords($table, $uid, $destPid, $originalRecordDestinationPid);
    }

    /**
     * Gets an instance of the command map helper.
     *
     * @param DataHandler $dataHandler DataHandler object
     * @return \TYPO3\CMS\Version\DataHandler\CommandMap
     */
    public function getCommandMap(DataHandler $dataHandler)
    {
        return GeneralUtility::makeInstance(
            \TYPO3\CMS\Version\DataHandler\CommandMap::class,
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
    protected function getUniqueFields($table)
    {
        $listArr = [];
        if (empty($GLOBALS['TCA'][$table]['columns'])) {
            return $listArr;
        }
        foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $configArr) {
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
     * @return \TYPO3\CMS\Core\Database\RelationHandler
     */
    protected function createRelationHandlerInstance()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\RelationHandler::class);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
