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

namespace TYPO3\CMS\Workspaces\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord;
use TYPO3\CMS\Workspaces\Domain\Model\WorkspaceStage;
use TYPO3\CMS\Workspaces\Domain\Repository\WorkspaceRepository;
use TYPO3\CMS\Workspaces\Domain\Repository\WorkspaceStageRepository;
use TYPO3\CMS\Workspaces\Exception\WorkspaceStageNotFoundException;
use TYPO3\CMS\Workspaces\Preview\PreviewUriBuilder;
use TYPO3\CMS\Workspaces\Service\GridDataService;
use TYPO3\CMS\Workspaces\Service\IntegrityService;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Implements the AJAX functionality for the various asynchronous calls.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
final readonly class WorkspacesAjaxController
{
    private const MAX_RECORDS_TO_PROCESS = 30;

    public function __construct(
        private WorkspaceService $workspaceService,
        private GridDataService $gridDataService,
        private IntegrityService $integrityService,
        private PreviewUriBuilder $previewUriBuilder,
        private StagesService $stagesService,
        private BackendViewFactory $backendViewFactory,
        private WorkspaceRepository $workspaceRepository,
        private WorkspaceStageRepository $workspaceStageRepository,
    ) {}

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $callStack = json_decode($request->getBody()->getContents());
        if (!is_array($callStack)) {
            $callStack = [$callStack];
        }
        $results = [];
        foreach ($callStack as $call) {
            $result = match ($call->method) {
                'getWorkspaceInfos' => $this->getWorkspaceInfos($call->data[0]),
                'checkIntegrity' => $this->checkIntegrity($call->data[0]),
                'getRowDetails' => $this->getRowDetails($call->data[0]),
                'publishSingleRecord' => $this->publishSingleRecord((string)$call->data[0], (int)$call->data[1], (int)$call->data[2]),
                'discardSingleRecord' => $this->discardSingleRecord((string)$call->data[0], (int)$call->data[1]),
                'generateWorkspacePreviewLinksForAllLanguages' => $this->generateWorkspacePreviewLinksForAllLanguages((int)$call->data[0]),
                'viewSingleRecord' => $this->viewSingleRecord((string)$call->data[0], (int)$call->data[1]),
                'executeSelectionAction' => $this->executeSelectionAction($call->data[0]),
                'sendToNextStageWindow' => $this->sendToNextStageWindow((int)$call->data[0], (string)$call->data[1], (int)$call->data[2]),
                'sendToPrevStageWindow' => $this->sendToPrevStageWindow((int)$call->data[0], (string)$call->data[1]),
                'sendToNextStageExecute' => $this->sendToNextStageExecute($call->data[0]),
                'sendToPrevStageExecute' => $this->sendToPrevStageExecute($call->data[0]),
                'sendToSpecificStageWindow' => $this->sendToSpecificStageWindow((int)$call->data[0]),
                'sendToSpecificStageExecute' => $this->sendToSpecificStageExecute($call->data[0]),
                'discardStagesFromPage' => $this->discardStagesFromPage((int)$call->data[0]),
                'sendCollectionToStage' => $this->sendCollectionToStage($call->data[0]),
                'sendPageToNextStage' => $this->sendPageToNextStage((int)$call->data[0]),
                'sendPageToPreviousStage' => $this->sendPageToPreviousStage((int)$call->data[0]),
                'updateStageChangeButtons' => $this->updateStageChangeButtons((int)$call->data[0], $request),
                'publishEntireWorkspace' => $this->publishEntireWorkspace($call->data[0]),
                'discardEntireWorkspace' => $this->discardEntireWorkspace($call->data[0]),
                default => throw new \RuntimeException('Not implemented', 1749983978),
            };
            $resultObject = new \stdClass();
            $resultObject->method = $call->method;
            $resultObject->result = $result;
            $results[] = $resultObject;
        }
        return new JsonResponse($results);
    }

    /**
     * Get List of workspace changes
     */
    private function getWorkspaceInfos(\stdClass $parameter): array
    {
        // To avoid too much work we use -1 to indicate that every page is relevant
        $pageId = $parameter->id > 0 ? $parameter->id : -1;
        if (!isset($parameter->language) || !MathUtility::canBeInterpretedAsInteger($parameter->language)) {
            $parameter->language = null;
        }
        if (!isset($parameter->stage) || !MathUtility::canBeInterpretedAsInteger($parameter->stage)) {
            // -99 disables stage filtering
            $parameter->stage = -99;
        }
        $backendUser = $this->getBackendUser();
        $currentWorkspace = $backendUser->workspace;
        $workspaceRecord = $this->workspaceRepository->findByUid($currentWorkspace);
        $stages = $this->workspaceStageRepository->findAllStagesByWorkspace($backendUser, $workspaceRecord);
        $versions = $this->workspaceService->selectVersionsInWorkspace(
            $currentWorkspace,
            (int)$parameter->stage,
            $pageId,
            (int)$parameter->depth,
            'tables_select',
            $parameter->language !== null ? (int)$parameter->language : null
        );
        return $this->gridDataService->generateGridListFromVersions($stages, $versions, $parameter);
    }

    /**
     * Checks integrity of elements before performing actions on them.
     */
    private function checkIntegrity(\stdClass $parameters): array
    {
        $affectedElements = [];
        if ($parameters->type === 'selection') {
            // Get affected elements on publishing/swapping actions. Affected elements have a dependency, e.g. translation
            // overlay and the default origin record - thus, the default record would be affected if the translation overlay
            // shall be published.
            foreach ((array)$parameters->selection as $element) {
                $affectedElements[] = CombinedRecord::create($element->table, (int)$element->liveId, (int)$element->versionId);
            }
        }
        $issues = $this->integrityService->check($affectedElements);
        return [
            'result' => $this->integrityService->getStatusRepresentation($issues),
        ];
    }

    private function getRowDetails(\stdClass $parameters): array
    {
        $backendUser = $this->getBackendUser();
        $workspaceRecord = $this->workspaceRepository->findByUid($backendUser->workspace);
        $stages = $this->workspaceStageRepository->findAllStagesByWorkspace($backendUser, $workspaceRecord);
        return $this->gridDataService->getRowDetails($stages, $parameters);
    }

    private function publishSingleRecord(string $table, int $t3ver_oid, int $orig_uid): array
    {
        $cmd[$table][$t3ver_oid]['version'] = [
            'action' => 'publish',
            'swapWith' => $orig_uid,
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();
        $result = [];
        if ($dataHandler->errorLog) {
            $result['error'] = implode('<br/>', $dataHandler->errorLog);
        }
        return $result;
    }

    private function discardSingleRecord(string $table, int $uid): array
    {
        $cmd[$table][$uid]['version'] = [
            'action' => 'clearWSID',
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();
        $result = [];
        if ($dataHandler->errorLog) {
            $result['error'] = implode('<br/>', $dataHandler->errorLog);
        }
        return $result;
    }

    private function generateWorkspacePreviewLinksForAllLanguages(int $uid): array
    {
        return $this->previewUriBuilder->buildUrisForAllLanguagesOfPage($uid);
    }

    private function viewSingleRecord(string $table, int $uid): string
    {
        return $this->previewUriBuilder->buildUriForElement($table, $uid);
    }

    /**
     * Execute an action (publish, discard) to a selection set.
     */
    private function executeSelectionAction(\stdClass $parameter): array
    {
        $result = [];
        if (empty($parameter->action) || empty($parameter->selection)) {
            $result['error'] = 'No action or record selection given';
            return $result;
        }
        $commands = [];
        if ($parameter->action === 'publish') {
            foreach ($parameter->selection as $record) {
                $commands[$record->table][$record->liveId]['version'] = [
                    'action' => 'publish',
                    'swapWith' => $record->versionId,
                ];
            }
        } elseif ($parameter->action === 'discard') {
            foreach ($parameter->selection as $record) {
                $commands[$record->table][$record->versionId]['version'] = [
                    'action' => 'clearWSID',
                ];
            }
        }
        if (empty($commands)) {
            $result['error'] = 'No commands given to be processed';
            return $result;
        }
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $commands);
        $dataHandler->process_cmdmap();
        $result['total'] = count($commands);
        return $result;
    }

    /**
     * Render the dialog window to be displayed before a record can be sent to the next stage.
     */
    private function sendToNextStageWindow(int $uid, string $table, int $t3ver_oid): array
    {
        $backendUser = $this->getBackendUser();
        $elementRecord = BackendUtility::getRecord($table, $uid);
        if (!is_array($elementRecord)) {
            return [
                'error' => [
                    'code' => 1287264776,
                    'message' => $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.sendToNextStage.noRecordFound'),
                ],
                'success' => false,
            ];
        }
        $workspaceRecord = $this->workspaceRepository->findByUid((int)$elementRecord['t3ver_wsid']);
        $stages = $this->workspaceStageRepository->findAllStagesByWorkspace($backendUser, $workspaceRecord);
        try {
            $nextStageRecord = $this->stagesService->getNextStage($stages, $elementRecord['t3ver_stage']);
        } catch (WorkspaceStageNotFoundException) {
            return [
                'error' => [
                    'code' => 1291111644,
                    'message' => $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.stageId.invalid'),
                ],
                'success' => false,
            ];
        }
        $result = $this->getSentToStageWindow($nextStageRecord);
        $result['affects'] = [
            'table' => $table,
            'nextStage' => $nextStageRecord->uid,
            // @todo: sendToPrevStageWindow() does not add this?
            't3ver_oid' => $t3ver_oid,
            'uid' => $uid,
        ];
        return $result;
    }

    /**
     * Render the dialog window to be displayed before a record can be sent to the previous stage.
     */
    private function sendToPrevStageWindow(int $uid, string $table): array
    {
        $backendUser = $this->getBackendUser();
        $elementRecord = BackendUtility::getRecord($table, $uid);
        if (!is_array($elementRecord)) {
            return [
                'error' => [
                    'code' => 1287264765,
                    'message' => $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.sendToNextStage.noRecordFound'),
                ],
                'success' => false,
            ];
        }
        $workspaceRecord = $this->workspaceRepository->findByUid((int)$elementRecord['t3ver_wsid']);
        $stages = $this->workspaceStageRepository->findAllStagesByWorkspace($backendUser, $workspaceRecord);
        try {
            $stageRecord = $this->stagesService->getStage($stages, $elementRecord['t3ver_stage']);
        } catch (WorkspaceStageNotFoundException) {
            return [
                'error' => [
                    'code' => 1291111644,
                    'message' => $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.stageId.invalid'),
                ],
                'success' => false,
            ];
        }
        if ($stageRecord->isEditStage) {
            return [
                'error' => [
                    'code' => 1287264746,
                    'message' => $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.sendToPrevStage.noPreviousStage'),
                ],
                'success' => false,
            ];
        }
        try {
            $previousStageRecord = $this->stagesService->getPreviousStage($stages, $stageRecord->uid);
        } catch (WorkspaceStageNotFoundException) {
            return [
                'error' => [
                    'code' => 1287264747,
                    'message' => $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:error.sendToPrevStage.noPreviousStage'),
                ],
                'success' => false,
            ];
        }
        $result = $this->getSentToStageWindow($previousStageRecord);
        $result['affects'] = [
            'table' => $table,
            'uid' => $uid,
            'nextStage' => $previousStageRecord->uid,
        ];
        return $result;
    }

    private function sendToNextStageExecute(\stdClass $parameters): array
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
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmdArray);
        $dataHandler->process_cmdmap();
        return [
            'success' => true,
        ];
    }

    private function sendToPrevStageExecute(\stdClass $parameters): array
    {
        $cmdArray = [];
        $setStageId = (int)$parameters->affects->nextStage;
        $comments = $parameters->comments;
        $table = $parameters->affects->table;
        $uid = $parameters->affects->uid;
        $recipients = $this->getRecipientList((array)($parameters->recipients ?? []), (string)($parameters->additional ?? ''), $setStageId);
        $cmdArray[$table][$uid]['version']['action'] = 'setStage';
        $cmdArray[$table][$uid]['version']['stageId'] = $setStageId;
        $cmdArray[$table][$uid]['version']['comment'] = $comments;
        $cmdArray[$table][$uid]['version']['notificationAlternativeRecipients'] = $recipients;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmdArray);
        $dataHandler->process_cmdmap();
        return [
            'success' => true,
        ];
    }

    private function sendToSpecificStageWindow(int $nextStageId): array
    {
        $backendUser = $this->getBackendUser();
        $workspaceRecord = $this->workspaceRepository->findByUid($backendUser->workspace);
        $stages = $this->workspaceStageRepository->findAllStagesByWorkspace($backendUser, $workspaceRecord);
        try {
            $nextStage = $this->stagesService->getStage($stages, $nextStageId);
        } catch (WorkspaceStageNotFoundException) {
            return [
                'success' => false,
            ];
        }
        $result = $this->getSentToStageWindow($nextStage);
        $result['affects'] = [
            'nextStage' => $nextStageId,
        ];
        return $result;
    }

    private function sendToSpecificStageExecute(\stdClass $parameters): array
    {
        $cmdArray = [];
        $setStageId = (int)$parameters->affects->nextStage;
        $comments = $parameters->comments;
        $elements = $parameters->affects->elements;
        $recipients = $this->getRecipientList((array)($parameters->recipients ?? []), (string)($parameters->additional ?? ''), $setStageId);
        foreach ($elements as $element) {
            // Avoid any action on records that have already been published to live
            $elementRecord = BackendUtility::getRecord($element->table, $element->uid);
            if ((int)($elementRecord['t3ver_wsid'] ?? 0) === 0) {
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
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmdArray);
        $dataHandler->process_cmdmap();
        return [
            'success' => true,
        ];
    }

    /**
     * Discard all items from given page id.
     */
    private function discardStagesFromPage(int $pageId): array
    {
        $cmdMapArray = [];
        $currentWorkspace = $this->getBackendUser()->workspace;
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace($currentWorkspace, -99, $pageId, 0, 'tables_modify');
        foreach ($workspaceItemsArray as $tableName => $items) {
            foreach ($items as $item) {
                $cmdMapArray[$tableName][$item['uid']]['version']['action'] = 'clearWSID';
            }
        }
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmdMapArray);
        $dataHandler->process_cmdmap();
        return [
            'success' => true,
        ];
    }

    /**
     * Push the given element collection to the next workspace stage.
     */
    private function sendCollectionToStage(\stdClass $parameters): array
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
                if ($stageId == StagesService::STAGE_PUBLISH_EXECUTE_ID) {
                    // Publishing uses live id in command map
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
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmdMapArray);
        $dataHandler->process_cmdmap();
        return [
            'success' => true,
        ];
    }

    private function sendPageToNextStage(int $id): array
    {
        $backendUser = $this->getBackendUser();
        $currentWorkspace = $backendUser->workspace;
        $workspaceRecord = $this->workspaceRepository->findByUid($currentWorkspace);
        $stages = $this->workspaceStageRepository->findAllStagesByWorkspace($backendUser, $workspaceRecord);
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace($currentWorkspace, -99, $id, 0, 'tables_modify');
        [$currentStage, $nextStage] = $this->stagesService->getNextStageForElementCollection($stages, $workspaceItemsArray);
        // get only the relevant items for processing
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace($currentWorkspace, $currentStage->uid, $id, 0, 'tables_modify');
        if (!$nextStage) {
            return [
                'success' => false,
            ];
        }
        $stageFormFields = $this->getSentToStageWindow($nextStage);
        return array_merge($stageFormFields, [
            'affects' => $workspaceItemsArray,
            'stageId' => $nextStage->uid,
        ]);
    }

    private function sendPageToPreviousStage(int $id): array
    {
        $backendUser = $this->getBackendUser();
        $currentWorkspace = $backendUser->workspace;
        $workspaceRecord = $this->workspaceRepository->findByUid($currentWorkspace);
        $stages = $this->workspaceStageRepository->findAllStagesByWorkspace($backendUser, $workspaceRecord);
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace($currentWorkspace, -99, $id, 0, 'tables_modify');
        [$currentStage, $previousStage] = $this->stagesService->getPreviousStageForElementCollection($stages, $workspaceItemsArray);
        // get only the relevant items for processing
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace($currentWorkspace, $currentStage->uid, $id, 0, 'tables_modify');
        if (!$previousStage) {
            return [
                'success' => false,
            ];
        }
        $stageFormFields = $this->getSentToStageWindow($previousStage);
        return array_merge($stageFormFields, [
            'affects' => $workspaceItemsArray,
            'stageId' => $previousStage->uid,
        ]);
    }

    /**
     * Fetch the current label and visible state of the stage buttons.
     * Used when records have been pushed to different stages in the preview module to update the button phalanx.
     */
    private function updateStageChangeButtons(int $id, ServerRequestInterface $request): string
    {
        $backendUser = $this->getBackendUser();
        $currentWorkspace = $backendUser->workspace;
        $workspaceRecord = $this->workspaceRepository->findByUid($currentWorkspace);
        $stages = $this->workspaceStageRepository->findAllStagesByWorkspace($backendUser, $workspaceRecord);
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace($currentWorkspace, -99, $id, 0, 'tables_modify');
        [, $nextStage] = $this->stagesService->getNextStageForElementCollection($stages, $workspaceItemsArray);
        [, $previousStage] = $this->stagesService->getPreviousStageForElementCollection($stages, $workspaceItemsArray);
        $view = $this->backendViewFactory->create($request, ['typo3/cms-workspaces']);
        $previousStageSendToTitle = $previousStage
            ? $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:actionSendToStage') . ' "' . $previousStage->title . '"'
            : '';
        $nextStageSendToTitle = '';
        if ($nextStage) {
            if ($nextStage->isExecuteStage) {
                $nextStageSendToTitle = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:publish_execute_action_option');
            } else {
                $nextStageSendToTitle = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:actionSendToStage') . ' "' . $nextStage->title . '"';
            }
        }
        $view->assignMultiple([
            'enablePreviousStageButton' => !is_null($previousStage),
            'enableNextStageButton' => !is_null($nextStage),
            'enableDiscardStageButton' => !is_null($previousStage) || !is_null($nextStage),
            'nextStage' => $nextStageSendToTitle,
            'nextStageId' => $nextStage->uid ?? 0,
            'prevStage' => $previousStageSendToTitle,
            'prevStageId' => $previousStage->uid ?? 0,
        ]);
        return $view->render('Preview/Ajax/StageButtons');
    }

    private function publishEntireWorkspace(\stdClass $parameters): array
    {
        // @todo: Needs refactoring (same with discard): Let's say this solution is well ... "creative". There
        //        is a first 'init' ajax call that prepares all the "publish" commands and stores "jobs" in user session,
        //        to then rely on the client side to call actions in batches. There is so much that could go wrong
        //        with this approach that its hard to number.
        $backendUser = $this->getBackendUser();
        $result = [
            'total' => 0,
            'processed' => 0,
            'error' => false,
        ];
        try {
            if (property_exists($parameters, 'init') && $parameters->init) {
                $language = null;
                if (isset($parameters->language) && MathUtility::canBeInterpretedAsInteger($parameters->language)) {
                    $language = (int)$parameters->language;
                }
                // workspace might be -98 a.k.a "All Workspaces" but that's safe here
                $publishData = $this->workspaceService->getCmdArrayForPublishWS($backendUser->workspace, false, $language);
                $recordCount = 0;
                foreach ($publishData as $recs) {
                    $recordCount += count($recs);
                }
                if ($recordCount > 0) {
                    $backendUser->setAndSaveSessionData('workspaceMassAction', $publishData);
                    $backendUser->setAndSaveSessionData('workspaceMassAction_total', $recordCount);
                    $backendUser->setAndSaveSessionData('workspaceMassAction_processed', 0);
                }
                $result['total'] = $recordCount;
            } else {
                $result['processed'] = $this->executeMassActionBatch();
                $result['total'] = $backendUser->getSessionData('workspaceMassAction_total');
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * @todo: This solution is quite expensive and similar to the "discard entire workspace when
     *        sys_workspace row is deleted" code of DataHandlerInternalWorkspaceTablesHook.
     *        It *may* be better to establish a dedicated DataHandler command for this.
     */
    private function discardEntireWorkspace(\stdClass $parameters): array
    {
        $backendUser = $this->getBackendUser();
        $result = [
            'total' => 0,
            'processed' => 0,
            'error' => false,
        ];
        try {
            if (property_exists($parameters, 'init') && $parameters->init) {
                $language = null;
                if (isset($parameters->language) && MathUtility::canBeInterpretedAsInteger($parameters->language)) {
                    $language = (int)$parameters->language;
                }
                // workspace might be -98 a.k.a "All Workspaces" but that's safe here
                $flushData = $this->workspaceService->getCmdArrayForFlushWS($backendUser->workspace, $language);
                $recordCount = 0;
                foreach ($flushData as $recs) {
                    $recordCount += count($recs);
                }
                if ($recordCount > 0) {
                    $backendUser->setAndSaveSessionData('workspaceMassAction', $flushData);
                    $backendUser->setAndSaveSessionData('workspaceMassAction_total', $recordCount);
                    $backendUser->setAndSaveSessionData('workspaceMassAction_processed', 0);
                }
                $result['total'] = $recordCount;
            } else {
                $result['processed'] = $this->executeMassActionBatch();
                $result['total'] = $backendUser->getSessionData('workspaceMassAction_total');
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Gets the dialog window to be displayed before a record can be sent to a stage.
     */
    private function getSentToStageWindow(WorkspaceStage $nextStage): array
    {
        $result = [];
        if ($nextStage->isDialogEnabled) {
            $result['sendMailTo'] = $this->getRecipientsOfStage($nextStage);
            $result['additional'] = [
                'type' => 'textarea',
                'value' => '',
            ];
        }
        $result['comments'] = [
            'type' => 'textarea',
            'value' => $nextStage->defaultComment,
        ];
        return $result;
    }

    /**
     * Gets all assigned recipients of a particular stage.
     */
    private function getRecipientsOfStage(WorkspaceStage $stageRecord): array
    {
        $result = [];
        $allRecipients = $this->stagesService->getResponsibleBeUser($stageRecord);
        $preselectedRecipients = $stageRecord->preselectedRecipients;
        foreach ($allRecipients as $backendUserId => $backendUser) {
            if (empty($backendUser['email']) || !GeneralUtility::validEmail($backendUser['email'])) {
                continue;
            }
            $name = (!empty($backendUser['realName']) ? $backendUser['realName'] : $backendUser['username']);
            $checked = in_array($backendUserId, $preselectedRecipients);
            $disabled = ($checked && !$stageRecord->isPreselectionChangeable);
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
     * Gets a merged variant of recipient defined by uid and custom ones.
     */
    private function getRecipientList(array $uidsOfRecipients, string $additionalRecipients, int $stageId): array
    {
        $backendUser = $this->getBackendUser();
        $workspaceRecord = $this->workspaceRepository->findByUid($backendUser->workspace);
        $stages = $this->workspaceStageRepository->findAllStagesByWorkspace($backendUser, $workspaceRecord);
        $stageRecord = $this->stagesService->getStage($stages, $stageId);
        $recipients = [];
        $finalRecipients = [];
        $backendUserIds = $stageRecord->allRecipients;
        foreach ($uidsOfRecipients as $userUid) {
            if (!in_array($userUid, $backendUserIds)) {
                // Ensure that only configured backend users are considered
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
        if (!$stageRecord->isPreselectionChangeable && !empty($stageRecord->preselectedRecipients)) {
            // Always notify preselected recipients if list is not changeable by user
            $preselectedBackendUsers = $this->stagesService->getBackendUsers($stageRecord->preselectedRecipients);
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

    private function executeMassActionBatch(): int
    {
        $backendUser = $this->getBackendUser();
        $processData = $backendUser->getSessionData('workspaceMassAction');
        $recordsProcessed = $backendUser->getSessionData('workspaceMassAction_processed');
        $limitedCmd = [];
        $numRecs = 0;
        foreach ($processData as $table => $recs) {
            foreach ($recs as $key => $value) {
                $numRecs++;
                $limitedCmd[$table][$key] = $value;
                if ($numRecs == self::MAX_RECORDS_TO_PROCESS) {
                    break;
                }
            }
            if ($numRecs == self::MAX_RECORDS_TO_PROCESS) {
                break;
            }
        }
        if ($numRecs == 0) {
            // All done
            $backendUser->setAndSaveSessionData('workspaceMassAction', null);
            $backendUser->setAndSaveSessionData('workspaceMassAction_total', 0);
        } else {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([], $limitedCmd);
            $dataHandler->process_cmdmap();
            $errors = $dataHandler->errorLog;
            if (!empty($errors)) {
                throw new \Exception(implode(', ', $errors), 1476048278);
            }
            // Unset processed records
            foreach ($limitedCmd as $table => $recs) {
                foreach ($recs as $key => $value) {
                    $recordsProcessed++;
                    unset($processData[$table][$key]);
                }
            }
            $backendUser->setAndSaveSessionData('workspaceMassAction', $processData);
            $backendUser->setAndSaveSessionData('workspaceMassAction_processed', $recordsProcessed);
        }
        return $recordsProcessed;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
