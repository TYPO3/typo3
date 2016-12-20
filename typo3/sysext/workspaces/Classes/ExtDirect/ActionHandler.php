<?php
namespace TYPO3\CMS\Workspaces\ExtDirect;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Domain\Record\StageRecord;
use TYPO3\CMS\Workspaces\Domain\Record\WorkspaceRecord;
use TYPO3\CMS\Workspaces\Service\StagesService;

/**
 * ExtDirect action handler
 */
class ActionHandler extends AbstractHandler
{
    /**
     * @var StagesService
     */
    protected $stageService;

    /**
     * Creates this object.
     */
    public function __construct()
    {
        $this->stageService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\StagesService::class);
    }

    /**
     * Generates a workspace preview link.
     *
     * @param int $uid The ID of the record to be linked
     * @return string the full domain including the protocol http:// or https://, but without the trailing '/'
     */
    public function generateWorkspacePreviewLink($uid)
    {
        return $this->getWorkspaceService()->generateWorkspacePreviewLink($uid);
    }

    /**
     * Generates workspace preview links for all available languages of a page.
     *
     * @param int $uid
     * @return array
     */
    public function generateWorkspacePreviewLinksForAllLanguages($uid)
    {
        return $this->getWorkspaceService()->generateWorkspacePreviewLinksForAllLanguages($uid);
    }

    /**
     * Swaps a single record.
     *
     * @param string $table
     * @param int $t3ver_oid
     * @param int $orig_uid
     * @return void
     * @todo What about reporting errors back to the ExtJS interface? /olly/
     */
    public function swapSingleRecord($table, $t3ver_oid, $orig_uid)
    {
        $versionRecord = BackendUtility::getRecord($table, $orig_uid);
        $currentWorkspace = $this->setTemporaryWorkspace($versionRecord['t3ver_wsid']);

        $cmd[$table][$t3ver_oid]['version'] = [
            'action' => 'swap',
            'swapWith' => $orig_uid,
            'swapIntoWS' => 1
        ];
        $this->processTcaCmd($cmd);

        $this->setTemporaryWorkspace($currentWorkspace);
    }

    /**
     * Deletes a single record.
     *
     * @param string $table
     * @param int $uid
     * @return void
     * @todo What about reporting errors back to the ExtJS interface? /olly/
     */
    public function deleteSingleRecord($table, $uid)
    {
        $versionRecord = BackendUtility::getRecord($table, $uid);
        $currentWorkspace = $this->setTemporaryWorkspace($versionRecord['t3ver_wsid']);

        $cmd[$table][$uid]['version'] = [
            'action' => 'clearWSID'
        ];
        $this->processTcaCmd($cmd);

        $this->setTemporaryWorkspace($currentWorkspace);
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
        return \TYPO3\CMS\Workspaces\Service\WorkspaceService::viewSingleRecord($table, $uid);
    }

    /**
     * Executes an action (publish, discard, swap) to a selection set.
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
        $swapIntoWorkspace = ($parameter->action === 'swap');
        if ($parameter->action === 'publish' || $swapIntoWorkspace) {
            $commands = $this->getPublishSwapCommands($parameter->selection, $swapIntoWorkspace);
        } elseif ($parameter->action === 'discard') {
            $commands = $this->getFlushCommands($parameter->selection);
        }

        $result = $this->processTcaCmd($commands);
        $result['total'] = count($commands);
        return $result;
    }

    /**
     * Get publish swap commands
     *
     * @param array|\stdClass[] $selection
     * @param bool $swapIntoWorkspace
     * @return array
     */
    protected function getPublishSwapCommands(array $selection, $swapIntoWorkspace)
    {
        $commands = [];
        foreach ($selection as $record) {
            $commands[$record->table][$record->liveId]['version'] = [
                'action' => 'swap',
                'swapWith' => $record->versionId,
                'swapIntoWS' => (bool)$swapIntoWorkspace,
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
     * @param \stdClass $model
     * @return void
     */
    public function saveColumnModel($model)
    {
        $data = [];
        foreach ($model as $column) {
            $data[$column->column] = [
                'position' => $column->position,
                'hidden' => $column->hidden
            ];
        }
        $GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['columns'] = $data;
        $GLOBALS['BE_USER']->writeUC();
    }

    public function loadColumnModel()
    {
        if (is_array($GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['columns'])) {
            return $GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['columns'];
        } else {
            return [];
        }
    }

    /**
     * Saves the selected language.
     *
     * @param int|string $language
     * @return void
     */
    public function saveLanguageSelection($language)
    {
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($language) === false && $language !== 'all') {
            $language = 'all';
        }
        $GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['language'] = $language;
        $GLOBALS['BE_USER']->writeUC();
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
        $currentWorkspace = $this->setTemporaryWorkspace($elementRecord['t3ver_wsid']);

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
                    'uid' => $uid
                ];
            } else {
                $result = $this->getErrorResponse('error.stageId.invalid', 1291111644);
            }
        } else {
            $result = $this->getErrorResponse('error.sendToNextStage.noRecordFound', 1287264776);
        }

        $this->setTemporaryWorkspace($currentWorkspace);
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
        $currentWorkspace = $this->setTemporaryWorkspace($elementRecord['t3ver_wsid']);

        if (is_array($elementRecord)) {
            $workspaceRecord = WorkspaceRecord::get($elementRecord['t3ver_wsid']);
            $stageRecord = $workspaceRecord->getStage($elementRecord['t3ver_stage']);

            if ($stageRecord !== null) {
                if (!$stageRecord->isEditStage()) {
                    $this->stageService->getRecordService()->add($table, $uid);
                    $previousStageRecord = $stageRecord->getPrevious();
                    $result = $this->getSentToStageWindow($previousStageRecord);
                    $result['affects'] = [
                        'table' => $table,
                        'uid' => $uid,
                        'nextStage' => $previousStageRecord->getUid()
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

        $this->setTemporaryWorkspace($currentWorkspace);
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
            'nextStage' => $nextStageId
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
            throw new \InvalidArgumentException($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.stageId.integer'));
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
                $uc = $beUserRecord['uc'] ? unserialize($beUserRecord['uc']) : [];
                $recipients[$beUserRecord['email']] = [
                    'email' => $beUserRecord['email'],
                    'lang' => isset($uc['lang']) ? $uc['lang'] : $beUserRecord['lang']
                ];
            }
        }

        if ($stageRecord->hasPreselection() && !$stageRecord->isPreselectionChangeable()) {
            $preselectedBackendUsers = $this->getStageService()->getBackendUsers(
                implode(',', $this->stageService->getPreselectedRecipients($stageRecord))
            );

            foreach ($preselectedBackendUsers as $preselectedBackendUser) {
                if (empty($preselectedBackendUser['email']) || !GeneralUtility::validEmail($preselectedBackendUser['email'])) {
                    continue;
                }
                if (!isset($recipients[$preselectedBackendUser['email']])) {
                    $uc = (!empty($preselectedBackendUser['uc']) ? unserialize($preselectedBackendUser['uc']) : []);
                    $recipients[$preselectedBackendUser['email']] = [
                        'email' => $preselectedBackendUser['email'],
                        'lang' => (isset($uc['lang']) ? $uc['lang'] : $preselectedBackendUser['lang'])
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
            if (GeneralUtility::validEmail($email)) {
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
        /** @var $workspaceService \TYPO3\CMS\Workspaces\Service\WorkspaceService */
        $workspaceService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
        /** @var $stageService StagesService */
        $stageService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\StagesService::class);
        $workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($stageService->getWorkspaceId(), ($filter = 1), ($stage = -99), $pageId, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
        foreach ($workspaceItemsArray as $tableName => $items) {
            foreach ($items as $item) {
                $cmdMapArray[$tableName][$item['uid']]['version']['action'] = 'clearWSID';
            }
        }
        $this->processTcaCmd($cmdMapArray);
        return [
            'success' => true
        ];
    }

    /**
     * Push the given element collection to the next workspace stage.
     *
     * <code>
     * $parameters->additional = your@mail.com
     * $parameters->affects->__TABLENAME__
     * $parameters->comments
     * $parameters->receipients
     * $parameters->stageId
     * </code>
     *
     * @param stdClass $parameters
     * @return array
     */
    public function sentCollectionToStage(\stdClass $parameters)
    {
        $cmdMapArray = [];
        $comment = $parameters->comments;
        $stageId = $parameters->stageId;
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($stageId) === false) {
            throw new \InvalidArgumentException('Missing "stageId" in $parameters array.', 1319488194);
        }
        if (!is_object($parameters->affects) || empty($parameters->affects)) {
            throw new \InvalidArgumentException('Missing "affected items" in $parameters array.', 1319488195);
        }
        $recipients = $this->getRecipientList($parameters->receipients, $parameters->additional, $stageId);
        foreach ($parameters->affects as $tableName => $items) {
            foreach ($items as $item) {
                // Publishing uses live id in command map
                if ($stageId == StagesService::STAGE_PUBLISH_EXECUTE_ID) {
                    $cmdMapArray[$tableName][$item->t3ver_oid]['version']['action'] = 'swap';
                    $cmdMapArray[$tableName][$item->t3ver_oid]['version']['swapWith'] = $item->uid;
                    $cmdMapArray[$tableName][$item->t3ver_oid]['version']['comment'] = $comment;
                    $cmdMapArray[$tableName][$item->t3ver_oid]['version']['notificationAlternativeRecipients'] = $recipients;
                // Setting stage uses version id in command map
                } else {
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
            'refreshLivePanel' => $parameters->stageId == -20
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

        /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
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
     * receipients: array with uids
     * additional: string
     * comments: string
     *
     * @param stdClass $parameters
     * @return array
     */
    public function sendToNextStageExecute(\stdClass $parameters)
    {
        $cmdArray = [];
        $setStageId = $parameters->affects->nextStage;
        $comments = $parameters->comments;
        $table = $parameters->affects->table;
        $uid = $parameters->affects->uid;
        $t3ver_oid = $parameters->affects->t3ver_oid;

        $elementRecord = BackendUtility::getRecord($table, $uid);
        $currentWorkspace = $this->setTemporaryWorkspace($elementRecord['t3ver_wsid']);

        $recipients = $this->getRecipientList($parameters->receipients, $parameters->additional, $setStageId);
        if ($setStageId == StagesService::STAGE_PUBLISH_EXECUTE_ID) {
            $cmdArray[$table][$t3ver_oid]['version']['action'] = 'swap';
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
            'success' => true
        ];

        $this->setTemporaryWorkspace($currentWorkspace);
        return $result;
    }

    /**
     * Gets an object with this structure:
     *
     * affects: object
     * table
     * t3ver_oid
     * nextStage
     * receipients: array with uids
     * additional: string
     * comments: string
     *
     * @param stdClass $parameters
     * @return array
     */
    public function sendToPrevStageExecute(\stdClass $parameters)
    {
        $cmdArray = [];
        $setStageId = $parameters->affects->nextStage;
        $comments = $parameters->comments;
        $table = $parameters->affects->table;
        $uid = $parameters->affects->uid;

        $elementRecord = BackendUtility::getRecord($table, $uid);
        $currentWorkspace = $this->setTemporaryWorkspace($elementRecord['t3ver_wsid']);

        $recipients = $this->getRecipientList($parameters->receipients, $parameters->additional, $setStageId);
        $cmdArray[$table][$uid]['version']['action'] = 'setStage';
        $cmdArray[$table][$uid]['version']['stageId'] = $setStageId;
        $cmdArray[$table][$uid]['version']['comment'] = $comments;
        $cmdArray[$table][$uid]['version']['notificationAlternativeRecipients'] = $recipients;
        $this->processTcaCmd($cmdArray);
        $result = [
            'success' => true
        ];

        $this->setTemporaryWorkspace($currentWorkspace);
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
        $setStageId = $parameters->affects->nextStage;
        $comments = $parameters->comments;
        $elements = $parameters->affects->elements;
        $recipients = $this->getRecipientList($parameters->receipients, $parameters->additional, $setStageId);
        foreach ($elements as $element) {
            // Avoid any action on records that have already been published to live
            $elementRecord = BackendUtility::getRecord($element->table, $element->uid);
            if ((int)$elementRecord['t3ver_wsid'] === 0) {
                continue;
            }

            if ($setStageId == StagesService::STAGE_PUBLISH_EXECUTE_ID) {
                $cmdArray[$element->table][$element->t3ver_oid]['version']['action'] = 'swap';
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
            'success' => true
        ];
        return $result;
    }

    /**
     * Gets the dialog window to be displayed before a record can be sent to a stage.
     *
     * @param StageRecord|int $nextStageId
     * @return array
     */
    protected function getSentToStageWindow($nextStage)
    {
        if (!$nextStage instanceof StageRecord) {
            $nextStage = WorkspaceRecord::get($this->getCurrentWorkspace())->getStage($nextStage);
        }

        $result = [
            'title' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:actionSendToStage'),
            'items' => [
                [
                    'xtype' => 'panel',
                    'bodyStyle' => 'margin-bottom: 7px; border: none;',
                    'html' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:window.sendToNextStageWindow.itemsWillBeSentTo') . ' ' . $nextStage->getTitle()
                ]
            ]
        ];

        if ($nextStage->isDialogEnabled()) {
            $result['items'][] = [
                'fieldLabel' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:window.sendToNextStageWindow.sendMailTo'),
                'xtype' => 'checkboxgroup',
                'itemCls' => 'x-check-group-alt',
                'columns' => 1,
                'style' => 'max-height: 200px',
                'autoScroll' => true,
                'items' => [
                    $this->getReceipientsOfStage($nextStage)
                ]
            ];
            $result['items'][] = [
                'fieldLabel' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:window.sendToNextStageWindow.additionalRecipients'),
                'name' => 'additional',
                'xtype' => 'textarea',
                'width' => 250
            ];
        }
        $result['items'][] = [
            'fieldLabel' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:window.sendToNextStageWindow.comments'),
            'name' => 'comments',
            'xtype' => 'textarea',
            'width' => 250,
            'value' => ($nextStage->isInternal() ? '' : $nextStage->getDefaultComment())
        ];

        return $result;
    }

    /**
     * Gets all assigned recipients of a particular stage.
     *
     * @param StageRecord|int $stageRecord
     * @return array
     */
    protected function getReceipientsOfStage($stageRecord)
    {
        if (!$stageRecord instanceof StageRecord) {
            $stageRecord = WorkspaceRecord::get($this->getCurrentWorkspace())->getStage($stageRecord);
        }

        $result = [];
        $allRecipients = $this->getStageService()->getResponsibleBeUser($stageRecord);
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
                'boxLabel' => sprintf('%s (%s)', $name, $backendUser['email']),
                'name' => 'receipients-' . $backendUserId,
                'checked' => $checked,
                'disabled' => $disabled
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
        $result = $this->getStageService()->getPropertyOfCurrentWorkspaceStage($stage, 'default_mailcomment');
        return $result;
    }

    /**
     * Gets an instance of the Stage service.
     *
     * @return StagesService
     */
    protected function getStageService()
    {
        if (!isset($this->stageService)) {
            $this->stageService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\StagesService::class);
        }
        return $this->stageService;
    }

    /**
     * Send all available workspace records to the previous stage.
     *
     * @param int $id Current page id to process items to previous stage.
     * @return array
     */
    public function sendPageToPreviousStage($id)
    {
        $workspaceService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
        $workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($this->stageService->getWorkspaceId(), ($filter = 1), ($stage = -99), $id, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
        list($currentStage, $previousStage) = $this->getStageService()->getPreviousStageForElementCollection($workspaceItemsArray);
        // get only the relevant items for processing
        $workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($this->stageService->getWorkspaceId(), ($filter = 1), $currentStage['uid'], $id, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
        return [
            'title' => 'Status message: Page send to next stage - ID: ' . $id . ' - Next stage title: ' . $previousStage['title'],
            'items' => $this->getSentToStageWindow($previousStage['uid']),
            'affects' => $workspaceItemsArray,
            'stageId' => $previousStage['uid']
        ];
    }

    /**
     * @param int $id Current Page id to select Workspace items from.
     * @return array
     */
    public function sendPageToNextStage($id)
    {
        $workspaceService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
        $workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($this->stageService->getWorkspaceId(), ($filter = 1), ($stage = -99), $id, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
        list($currentStage, $nextStage) = $this->getStageService()->getNextStageForElementCollection($workspaceItemsArray);
        // get only the relevant items for processing
        $workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($this->stageService->getWorkspaceId(), ($filter = 1), $currentStage['uid'], $id, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
        return [
            'title' => 'Status message: Page send to next stage - ID: ' . $id . ' - Next stage title: ' . $nextStage['title'],
            'items' => $this->getSentToStageWindow($nextStage['uid']),
            'affects' => $workspaceItemsArray,
            'stageId' => $nextStage['uid']
        ];
    }

    /**
     * Fetch the current label and visible state of the buttons.
     *
     * @param int $id
     * @return array Contains the visibility state and label of the stage change buttons.
     */
    public function updateStageChangeButtons($id)
    {
        $stageService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\StagesService::class);
        $workspaceService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
        // fetch the next and previous stage
        $workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($stageService->getWorkspaceId(), ($filter = 1), ($stage = -99), $id, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
        list(, $nextStage) = $stageService->getNextStageForElementCollection($workspaceItemsArray);
        list(, $previousStage) = $stageService->getPreviousStageForElementCollection($workspaceItemsArray);
        $toolbarButtons = [
            'feToolbarButtonNextStage' => [
                'visible' => is_array($nextStage) && !empty($nextStage),
                'text' => $nextStage['title']
            ],
            'feToolbarButtonPreviousStage' => [
                'visible' => is_array($previousStage) && !empty($previousStage),
                'text' => $previousStage['title']
            ],
            'feToolbarButtonDiscardStage' => [
                'visible' => is_array($nextStage) && !empty($nextStage) || is_array($previousStage) && !empty($previousStage),
                'text' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:label_doaction_discard', true)
            ]
        ];
        return $toolbarButtons;
    }

    /**
     * @param int $workspaceId
     * @return int Id of the original workspace
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function setTemporaryWorkspace($workspaceId)
    {
        $workspaceId = (int)$workspaceId;
        $currentWorkspace = (int)$this->getBackendUser()->workspace;

        if ($currentWorkspace !== $workspaceId) {
            if (!$this->getBackendUser()->setTemporaryWorkspace($workspaceId)) {
                throw new \TYPO3\CMS\Core\Exception(
                    'Cannot set temporary workspace to "' . $workspaceId . '"',
                    1371484524
                );
            }
        }

        return $currentWorkspace;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
