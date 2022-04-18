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

namespace TYPO3\CMS\Workspaces\Controller\Remote;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Workspaces\Domain\Record\StageRecord;
use TYPO3\CMS\Workspaces\Domain\Record\WorkspaceRecord;
use TYPO3\CMS\Workspaces\Preview\PreviewUriBuilder;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class ActionHandler
{
    /**
     * @var StagesService
     */
    protected $stageService;

    /**
     * @var WorkspaceService
     */
    protected $workspaceService;

    /**
     * Creates this object.
     */
    public function __construct()
    {
        $this->stageService = GeneralUtility::makeInstance(StagesService::class);
        $this->workspaceService = GeneralUtility::makeInstance(WorkspaceService::class);
    }

    /**
     * Generates a workspace preview link.
     *
     * @param int $uid The ID of the record to be linked
     * @return string the full domain including the protocol http:// or https://, but without the trailing '/'
     */
    public function generateWorkspacePreviewLink($uid)
    {
        return GeneralUtility::makeInstance(PreviewUriBuilder::class)->buildUriForPage((int)$uid, 0);
    }

    /**
     * Generates workspace preview links for all available languages of a page.
     *
     * @param int $uid
     * @return array
     */
    public function generateWorkspacePreviewLinksForAllLanguages($uid)
    {
        return GeneralUtility::makeInstance(PreviewUriBuilder::class)->buildUrisForAllLanguagesOfPage((int)$uid);
    }

    /**
     * Publishes a single record.
     *
     * @param string $table
     * @param int $t3ver_oid
     * @param int $orig_uid
     * @todo What about reporting errors back to the interface? /olly/
     */
    public function publishSingleRecord($table, $t3ver_oid, $orig_uid)
    {
        $cmd = [];
        $cmd[$table][$t3ver_oid]['version'] = [
            'action' => 'publish',
            'swapWith' => $orig_uid,
        ];
        $this->processTcaCmd($cmd);
    }

    /**
     * Deletes a single record.
     *
     * @param string $table
     * @param int $uid
     * @todo What about reporting errors back to the interface? /olly/
     */
    public function deleteSingleRecord($table, $uid)
    {
        $cmd = [];
        $cmd[$table][$uid]['version'] = [
            'action' => 'clearWSID',
        ];
        $this->processTcaCmd($cmd);
    }

    /**
     * Generates a view link for a page.
     *
     * @param string $table
     * @param string $uid
     * @return string
     */
    public function viewSingleRecord($table, $uid)
    {
        return GeneralUtility::makeInstance(PreviewUriBuilder::class)->buildUriForElement($table, (int)$uid);
    }

    /**
     * Executes an action (publish, discard) to a selection set.
     *
     * @param \stdClass $parameter
     * @return array
     */
    public function executeSelectionAction($parameter)
    {
        $result = [];

        if (empty($parameter->action) || empty($parameter->selection)) {
            $result['error'] = 'No action or record selection given';
            return $result;
        }

        $commands = [];
        if ($parameter->action === 'publish') {
            $commands = $this->getPublishCommands($parameter->selection);
        } elseif ($parameter->action === 'discard') {
            $commands = $this->getFlushCommands($parameter->selection);
        }

        $result = $this->processTcaCmd($commands);
        $result['total'] = count($commands);
        return $result;
    }

    /**
     * Get publish commands
     *
     * @param array|\stdClass[] $selection
     * @return array
     */
    protected function getPublishCommands(array $selection)
    {
        $commands = [];
        foreach ($selection as $record) {
            $commands[$record->table][$record->liveId]['version'] = [
                'action' => 'publish',
                'swapWith' => $record->versionId,
            ];
        }
        return $commands;
    }

    /**
     * Get flush commands
     *
     * @param array|\stdClass[] $selection
     * @return array
     */
    protected function getFlushCommands(array $selection)
    {
        $commands = [];
        foreach ($selection as $record) {
            $commands[$record->table][$record->versionId]['version'] = [
                'action' => 'clearWSID',
            ];
        }
        return $commands;
    }

    /**
     * Saves the selected columns to be shown to the preferences of the current backend user.
     *
     * @param array<\stdClass> $model
     */
    public function saveColumnModel($model)
    {
        $data = [];
        foreach ($model as $column) {
            $data[$column->column] = [
                'position' => $column->position,
                'hidden' => $column->hidden,
            ];
        }
        $this->getBackendUser()->uc['moduleData']['Workspaces'][$this->getBackendUser()->workspace]['columns'] = $data;
        $this->getBackendUser()->writeUC();
    }

    public function loadColumnModel()
    {
        if (is_array($this->getBackendUser()->uc['moduleData']['Workspaces'][$this->getBackendUser()->workspace]['columns'])) {
            return $this->getBackendUser()->uc['moduleData']['Workspaces'][$this->getBackendUser()->workspace]['columns'];
        }
        return [];
    }

    /**
     * Gets the dialog window to be displayed before a record can be sent to the next stage.
     *
     * @param int $uid
     * @param string $table
     * @param int $t3ver_oid
     * @return array
     */
    public function sendToNextStageWindow($uid, $table, $t3ver_oid)
    {
        $elementRecord = BackendUtility::getRecord($table, $uid);
        if (is_array($elementRecord)) {
            $workspaceRecord = WorkspaceRecord::get($elementRecord['t3ver_wsid']);
            $nextStageRecord = $workspaceRecord->getNextStage($elementRecord['t3ver_stage']);
            if ($nextStageRecord !== null) {
                $this->stageService->getRecordService()->add($table, $uid);
                $result = $this->getSentToStageWindow($nextStageRecord);
                $result['affects'] = [
                    'table' => $table,
                    'nextStage' => $nextStageRecord->getUid(),
                    't3ver_oid' => $t3ver_oid,
                    'uid' => $uid,
                ];
            } else {
                $result = $this->getErrorResponse('error.stageId.invalid', 1291111644);
            }
        } else {
            $result = $this->getErrorResponse('error.sendToNextStage.noRecordFound', 1287264776);
        }
        return $result;
    }

    /**
     * Gets the dialog window to be displayed before a record can be sent to the previous stage.
     *
     * @param int $uid
     * @param string $table
     * @return array
     */
    public function sendToPrevStageWindow($uid, $table)
    {
        $elementRecord = BackendUtility::getRecord($table, $uid);
        if (is_array($elementRecord)) {
            $workspaceRecord = WorkspaceRecord::get($elementRecord['t3ver_wsid']);
            $stageRecord = $workspaceRecord->getStage($elementRecord['t3ver_stage']);

            if ($stageRecord !== null) {
                if (!$stageRecord->isEditStage()) {
                    $this->stageService->getRecordService()->add($table, $uid);
                    $previousStageRecord = $stageRecord->getPrevious();
                    if ($previousStageRecord === null) {
                        return $this->getErrorResponse('error.sendToPrevStage.noPreviousStage', 1287264747);
                    }
                    $result = $this->getSentToStageWindow($previousStageRecord);
                    $result['affects'] = [
                        'table' => $table,
                        'uid' => $uid,
                        'nextStage' => $previousStageRecord->getUid(),
                    ];
                } else {
                    // element is already in edit stage, there is no prev stage - return an error message
                    $result = $this->getErrorResponse('error.sendToPrevStage.noPreviousStage', 1287264746);
                }
            } else {
                $result = $this->getErrorResponse('error.stageId.invalid', 1291111644);
            }
        } else {
            $result = $this->getErrorResponse('error.sendToNextStage.noRecordFound', 1287264765);
        }
        return $result;
    }

    /**
     * Gets the dialog window to be displayed before a record can be sent to a specific stage.
     *
     * @param int $nextStageId
     * @param array|\stdClass[] $elements
     * @return array
     */
    public function sendToSpecificStageWindow($nextStageId, array $elements)
    {
        foreach ($elements as $element) {
            $this->stageService->getRecordService()->add(
                $element->table,
                $element->uid
            );
        }

        $result = $this->getSentToStageWindow($nextStageId);
        $result['affects'] = [
            'nextStage' => $nextStageId,
        ];
        return $result;
    }

    /**
     * Gets a merged variant of recipient defined by uid and custom ones.
     *
     * @param array $uidOfRecipients list of recipients
     * @param string $additionalRecipients given user string of additional recipients
     * @param int $stageId stage id
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getRecipientList(array $uidOfRecipients, $additionalRecipients, $stageId)
    {
        $stageRecord = WorkspaceRecord::get($this->getCurrentWorkspace())->getStage($stageId);

        if ($stageRecord === null) {
            throw new \InvalidArgumentException(
                $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.stageId.integer'),
                1476044776
            );
        }

        $recipients = [];
        $finalRecipients = [];
        $backendUserIds = $stageRecord->getAllRecipients();
        foreach ($uidOfRecipients as $userUid) {
            // Ensure that only configured backend users are considered
            if (!in_array($userUid, $backendUserIds)) {
                continue;
            }
            $beUserRecord = BackendUtility::getRecord('be_users', (int)$userUid);
            if (is_array($beUserRecord) && $beUserRecord['email'] !== '') {
                $recipients[$beUserRecord['email']] = [
                    'email' => $beUserRecord['email'],
                    'lang' => $beUserRecord['lang'],
                ];
            }
        }

        if ($stageRecord->hasPreselection() && !$stageRecord->isPreselectionChangeable()) {
            $preselectedBackendUsers = $this->stageService->getBackendUsers(
                implode(',', $this->stageService->getPreselectedRecipients($stageRecord))
            );

            foreach ($preselectedBackendUsers as $preselectedBackendUser) {
                if (empty($preselectedBackendUser['email']) || !GeneralUtility::validEmail($preselectedBackendUser['email'])) {
                    continue;
                }
                if (!isset($recipients[$preselectedBackendUser['email']])) {
                    $uc = (!empty($preselectedBackendUser['uc']) ? unserialize($preselectedBackendUser['uc'], ['allowed_classes' => false]) : []);
                    $recipients[$preselectedBackendUser['email']] = [
                        'email' => $preselectedBackendUser['email'],
                        'lang' => $uc['lang'] ?? $preselectedBackendUser['lang'],
                    ];
                }
            }
        }

        if ($additionalRecipients !== '') {
            $emails = GeneralUtility::trimExplode(LF, $additionalRecipients, true);
            $additionalRecipients = [];
            foreach ($emails as $email) {
                $additionalRecipients[$email] = ['email' => $email];
            }
        } else {
            $additionalRecipients = [];
        }
        // We merge $recipients on top of $additionalRecipients because $recipients
        // possibly is more complete with a user language. Furthermore, the list of
        // recipients is automatically unique since we indexed $additionalRecipients
        // and $recipients with the email address
        $allRecipients = array_merge($additionalRecipients, $recipients);
        foreach ($allRecipients as $email => $recipientInformation) {
            if (GeneralUtility::validEmail((string)$email)) {
                $finalRecipients[] = $recipientInformation;
            }
        }
        return $finalRecipients;
    }

    /**
     * Discard all items from given page id.
     *
     * @param int $pageId
     * @return array
     */
    public function discardStagesFromPage($pageId)
    {
        $cmdMapArray = [];
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace(
            $this->stageService->getWorkspaceId(),
            -99,
            $pageId,
            0,
            'tables_modify'
        );
        foreach ($workspaceItemsArray as $tableName => $items) {
            foreach ($items as $item) {
                $cmdMapArray[$tableName][$item['uid']]['version']['action'] = 'clearWSID';
            }
        }
        $this->processTcaCmd($cmdMapArray);
        return [
            'success' => true,
        ];
    }

    /**
     * Push the given element collection to the next workspace stage.
     *
     * <code>
     * $parameters->additional = your@mail.com
     * $parameters->affects->__TABLENAME__
     * $parameters->comments
     * $parameters->recipients
     * $parameters->stageId
     * </code>
     *
     * @param \stdClass $parameters
     * @return array
     */
    public function sentCollectionToStage(\stdClass $parameters)
    {
        $cmdMapArray = [];
        $comment = $parameters->comments;
        $stageId = $parameters->stageId;
        if (MathUtility::canBeInterpretedAsInteger($stageId) === false) {
            throw new \InvalidArgumentException('Missing "stageId" in $parameters array.', 1319488194);
        }
        if (!is_object($parameters->affects) || empty($parameters->affects)) {
            throw new \InvalidArgumentException('Missing "affected items" in $parameters array.', 1319488195);
        }
        $recipients = $this->getRecipientList((array)($parameters->recipients ?? []), (string)($parameters->additional ?? ''), $stageId);
        foreach ($parameters->affects as $tableName => $items) {
            foreach ($items as $item) {
                // Publishing uses live id in command map
                if ($stageId == StagesService::STAGE_PUBLISH_EXECUTE_ID) {
                    $cmdMapArray[$tableName][$item->t3ver_oid]['version']['action'] = 'publish';
                    $cmdMapArray[$tableName][$item->t3ver_oid]['version']['swapWith'] = $item->uid;
                    $cmdMapArray[$tableName][$item->t3ver_oid]['version']['comment'] = $comment;
                    $cmdMapArray[$tableName][$item->t3ver_oid]['version']['notificationAlternativeRecipients'] = $recipients;
                } else {
                    // Setting stage uses version id in command map
                    $cmdMapArray[$tableName][$item->uid]['version']['action'] = 'setStage';
                    $cmdMapArray[$tableName][$item->uid]['version']['stageId'] = $stageId;
                    $cmdMapArray[$tableName][$item->uid]['version']['comment'] = $comment;
                    $cmdMapArray[$tableName][$item->uid]['version']['notificationAlternativeRecipients'] = $recipients;
                }
            }
        }
        $this->processTcaCmd($cmdMapArray);
        return [
            'success' => true,
            // force refresh after publishing changes
            'refreshLivePanel' => $parameters->stageId == -20,
        ];
    }

    /**
     * Process TCA command map array.
     *
     * @param array $cmdMapArray
     * @return array
     */
    protected function processTcaCmd(array $cmdMapArray)
    {
        $result = [];

        if (empty($cmdMapArray)) {
            $result['error'] = 'No commands given to be processed';
            return $result;
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmdMapArray);
        $dataHandler->process_cmdmap();

        if ($dataHandler->errorLog) {
            $result['error'] = implode('<br/>', $dataHandler->errorLog);
        }

        return $result;
    }

    /**
     * Gets an object with this structure:
     *
     * affects: object
     * table
     * t3ver_oid
     * nextStage
     * uid
     * recipients: array with uids
     * additional: string
     * comments: string
     *
     * @param \stdClass $parameters
     * @return array
     */
    public function sendToNextStageExecute(\stdClass $parameters)
    {
        $cmdArray = [];
        $setStageId = (int)$parameters->affects->nextStage;
        $comments = $parameters->comments;
        $table = $parameters->affects->table;
        $uid = $parameters->affects->uid;
        $t3ver_oid = $parameters->affects->t3ver_oid;

        $recipients = $this->getRecipientList((array)($parameters->recipients ?? []), (string)($parameters->additional ?? ''), $setStageId);
        if ($setStageId === StagesService::STAGE_PUBLISH_EXECUTE_ID) {
            $cmdArray[$table][$t3ver_oid]['version']['action'] = 'publish';
            $cmdArray[$table][$t3ver_oid]['version']['swapWith'] = $uid;
            $cmdArray[$table][$t3ver_oid]['version']['comment'] = $comments;
            $cmdArray[$table][$t3ver_oid]['version']['notificationAlternativeRecipients'] = $recipients;
        } else {
            $cmdArray[$table][$uid]['version']['action'] = 'setStage';
            $cmdArray[$table][$uid]['version']['stageId'] = $setStageId;
            $cmdArray[$table][$uid]['version']['comment'] = $comments;
            $cmdArray[$table][$uid]['version']['notificationAlternativeRecipients'] = $recipients;
        }
        $this->processTcaCmd($cmdArray);
        $result = [
            'success' => true,
        ];

        return $result;
    }

    /**
     * Gets an object with this structure:
     *
     * affects: object
     * table
     * t3ver_oid
     * nextStage
     * recipients: array with uids
     * additional: string
     * comments: string
     *
     * @param \stdClass $parameters
     * @return array
     */
    public function sendToPrevStageExecute(\stdClass $parameters)
    {
        $cmdArray = [];
        $setStageId = $parameters->affects->nextStage;
        $comments = $parameters->comments;
        $table = $parameters->affects->table;
        $uid = $parameters->affects->uid;

        $recipients = $this->getRecipientList((array)($parameters->recipients ?? []), (string)($parameters->additional ?? ''), $setStageId);
        $cmdArray[$table][$uid]['version']['action'] = 'setStage';
        $cmdArray[$table][$uid]['version']['stageId'] = $setStageId;
        $cmdArray[$table][$uid]['version']['comment'] = $comments;
        $cmdArray[$table][$uid]['version']['notificationAlternativeRecipients'] = $recipients;
        $this->processTcaCmd($cmdArray);
        $result = [
            'success' => true,
        ];

        return $result;
    }

    /**
     * Gets an object with this structure:
     *
     * affects: object
     * elements: array
     * 0: object
     * table
     * t3ver_oid
     * uid
     * 1: object
     * table
     * t3ver_oid
     * uid
     * nextStage
     * recipients: array with uids
     * additional: string
     * comments: string
     *
     * @param \stdClass $parameters
     * @return array
     */
    public function sendToSpecificStageExecute(\stdClass $parameters)
    {
        $cmdArray = [];
        $setStageId = (int)$parameters->affects->nextStage;
        $comments = $parameters->comments;
        $elements = $parameters->affects->elements;
        $recipients = $this->getRecipientList((array)($parameters->recipients ?? []), (string)($parameters->additional ?? ''), $setStageId);
        foreach ($elements as $element) {
            // Avoid any action on records that have already been published to live
            $elementRecord = BackendUtility::getRecord($element->table, $element->uid);
            if ((int)$elementRecord['t3ver_wsid'] === 0) {
                continue;
            }

            if ($setStageId === StagesService::STAGE_PUBLISH_EXECUTE_ID) {
                $cmdArray[$element->table][$element->t3ver_oid]['version']['action'] = 'publish';
                $cmdArray[$element->table][$element->t3ver_oid]['version']['swapWith'] = $element->uid;
                $cmdArray[$element->table][$element->t3ver_oid]['version']['comment'] = $comments;
                $cmdArray[$element->table][$element->t3ver_oid]['version']['notificationAlternativeRecipients'] = $recipients;
            } else {
                $cmdArray[$element->table][$element->uid]['version']['action'] = 'setStage';
                $cmdArray[$element->table][$element->uid]['version']['stageId'] = $setStageId;
                $cmdArray[$element->table][$element->uid]['version']['comment'] = $comments;
                $cmdArray[$element->table][$element->uid]['version']['notificationAlternativeRecipients'] = $recipients;
            }
        }
        $this->processTcaCmd($cmdArray);
        $result = [
            'success' => true,
        ];
        return $result;
    }

    /**
     * Gets the dialog window to be displayed before a record can be sent to a stage.
     *
     * @param StageRecord|int $nextStage
     * @return array
     */
    protected function getSentToStageWindow($nextStage)
    {
        if (!$nextStage instanceof StageRecord) {
            $nextStage = WorkspaceRecord::get($this->getCurrentWorkspace())->getStage($nextStage);
        }

        $result = [];
        // TODO: $nextStage might be null, error ignored in phpstan.neon
        if ($nextStage->isDialogEnabled()) {
            $result['sendMailTo'] = $this->getRecipientsOfStage($nextStage);
            $result['additional'] = [
                'type' => 'textarea',
                'value' => '',
            ];
        }
        $result['comments'] = [
            'type' => 'textarea',
            'value' => $nextStage->isInternal() ? '' : $nextStage->getDefaultComment(),
        ];

        return $result;
    }

    /**
     * Gets all assigned recipients of a particular stage.
     *
     * @param StageRecord|int $stageRecord
     * @return array
     */
    protected function getRecipientsOfStage($stageRecord)
    {
        if (!$stageRecord instanceof StageRecord) {
            $stageRecord = WorkspaceRecord::get($this->getCurrentWorkspace())->getStage($stageRecord);
        }

        $result = [];
        $allRecipients = $this->stageService->getResponsibleBeUser($stageRecord);
        $preselectedRecipients = $this->stageService->getPreselectedRecipients($stageRecord);
        $isPreselectionChangeable = $stageRecord->isPreselectionChangeable();

        foreach ($allRecipients as $backendUserId => $backendUser) {
            if (empty($backendUser['email']) || !GeneralUtility::validEmail($backendUser['email'])) {
                continue;
            }

            $name = (!empty($backendUser['realName']) ? $backendUser['realName'] : $backendUser['username']);
            $checked = in_array($backendUserId, $preselectedRecipients);
            $disabled = ($checked && !$isPreselectionChangeable);

            $result[] = [
                'label' => sprintf('%s (%s)', $name, $backendUser['email']),
                'value' => $backendUserId,
                'name' => 'recipients-' . $backendUserId,
                'checked' => $checked,
                'disabled' => $disabled,
            ];
        }

        return $result;
    }

    /**
     * Gets the default comment of a particular stage.
     *
     * @param int $stage
     * @return string
     */
    protected function getDefaultCommentOfStage($stage)
    {
        $result = $this->stageService->getPropertyOfCurrentWorkspaceStage($stage, 'default_mailcomment');
        return $result;
    }

    /**
     * Send all available workspace records to the previous stage.
     *
     * @param int $id Current page id to process items to previous stage.
     * @return array
     */
    public function sendPageToPreviousStage($id)
    {
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace(
            $this->stageService->getWorkspaceId(),
            -99,
            $id,
            0,
            'tables_modify'
        );
        [$currentStage, $previousStage] = $this->stageService->getPreviousStageForElementCollection($workspaceItemsArray);
        // get only the relevant items for processing
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace(
            $this->stageService->getWorkspaceId(),
            $currentStage['uid'],
            $id,
            0,
            'tables_modify'
        );
        $stageFormFields = $this->getSentToStageWindow($previousStage['uid']);
        $result = array_merge($stageFormFields, [
            'title' => 'Status message: Page send to next stage - ID: ' . $id . ' - Next stage title: ' . $previousStage['title'],
            'items' => $this->getSentToStageWindow($previousStage['uid']),
            'affects' => $workspaceItemsArray,
            'stageId' => $previousStage['uid'],
        ]);
        return $result;
    }

    /**
     * @param int $id Current Page id to select Workspace items from.
     * @return array
     */
    public function sendPageToNextStage($id)
    {
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace(
            $this->stageService->getWorkspaceId(),
            -99,
            $id,
            0,
            'tables_modify'
        );
        [$currentStage, $nextStage] = $this->stageService->getNextStageForElementCollection($workspaceItemsArray);
        // get only the relevant items for processing
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace(
            $this->stageService->getWorkspaceId(),
            $currentStage['uid'],
            $id,
            0,
            'tables_modify'
        );
        $stageFormFields = $this->getSentToStageWindow($nextStage['uid']);
        $result = array_merge($stageFormFields, [
            'title' => 'Status message: Page send to next stage - ID: ' . $id . ' - Next stage title: ' . $nextStage['title'],
            'affects' => $workspaceItemsArray,
            'stageId' => $nextStage['uid'],
        ]);
        return $result;
    }

    /**
     * Fetch the current label and visible state of the buttons.
     *
     * @param int $id
     * @return string The pre-rendered HTML for the stage buttons
     */
    public function updateStageChangeButtons($id)
    {
        // fetch the next and previous stage
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace(
            $this->stageService->getWorkspaceId(),
            -99,
            $id,
            0,
            'tables_modify'
        );
        [, $nextStage] = $this->stageService->getNextStageForElementCollection($workspaceItemsArray);
        [, $previousStage] = $this->stageService->getPreviousStageForElementCollection($workspaceItemsArray);

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $extensionPath = ExtensionManagementUtility::extPath('workspaces');
        $view->setPartialRootPaths(['default' => $extensionPath . 'Resources/Private/Partials']);
        $view->setTemplatePathAndFilename($extensionPath . 'Resources/Private/Templates/Preview/Ajax/StageButtons.html');
        $request = $view->getRequest();
        $request->setControllerExtensionName('workspaces');
        $view->assignMultiple([
            'enablePreviousStageButton' => is_array($previousStage) && !empty($previousStage),
            'enableNextStageButton' => is_array($nextStage) && !empty($nextStage),
            'enableDiscardStageButton' => (is_array($nextStage) && !empty($nextStage)) || (is_array($previousStage) && !empty($previousStage)),
            'nextStage' => $nextStage['title'] ?? '',
            'nextStageId' => $nextStage['uid'] ?? 0,
            'prevStage' => $previousStage['title'] ?? '',
            'prevStageId' => $previousStage['uid'] ?? 0,
        ]);
        $renderedView = $view->render();
        return $renderedView;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Gets an error response to be shown in the grid component.
     *
     * @param string $errorLabel Name of the label in the locallang.xlf file
     * @param int $errorCode The error code to be used
     * @param bool $successFlagValue Value of the success flag to be delivered back (might be FALSE in most cases)
     * @return array
     */
    protected function getErrorResponse($errorLabel, $errorCode = 0, $successFlagValue = false)
    {
        $localLangFile = 'LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf';
        $response = [
            'error' => [
                'code' => $errorCode,
                'message' => $this->getLanguageService()->sL($localLangFile . ':' . $errorLabel),
            ],
            'success' => $successFlagValue,
        ];
        return $response;
    }

    /**
     * Gets the current workspace ID.
     *
     * @return int The current workspace ID
     */
    protected function getCurrentWorkspace()
    {
        return $this->workspaceService->getCurrentWorkspace();
    }
}
