<?php
namespace TYPO3\CMS\Workspaces\Controller\Remote;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Class encapsulates all actions which are triggered for all elements within the current workspace.
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class MassActionHandler
{
    const MAX_RECORDS_TO_PROCESS = 30;

    /**
     * Path to the locallang file
     *
     * @var string
     */
    private $pathToLocallang = 'LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf';

    /**
     * @var WorkspaceService
     */
    protected $workspaceService;

    public function __construct()
    {
        $this->workspaceService = GeneralUtility::makeInstance(WorkspaceService::class);
    }

    /**
     * Get list of available mass workspace actions.
     *
     * @return array $data
     */
    public function getMassStageActions()
    {
        $actions = [];
        $currentWorkspace = $this->getCurrentWorkspace();
        $backendUser = $this->getBackendUser();
        $massActionsEnabled = (bool)($backendUser->getTSConfig()['options.']['workspaces.']['enableMassActions'] ?? true);
        // in case we're working within "All Workspaces" we can't provide Mass Actions
        if ($currentWorkspace != WorkspaceService::SELECT_ALL_WORKSPACES && $massActionsEnabled) {
            $publishAccess = $backendUser->workspacePublishAccess($currentWorkspace);
            if ($publishAccess && !($backendUser->workspaceRec['publish_access'] & 1)) {
                $actions[] = ['action' => 'publish', 'title' => $this->getLanguageService()->sL($this->pathToLocallang . ':label_doaction_publish')];
                if ($backendUser->workspaceSwapAccess()) {
                    $actions[] = ['action' => 'swap', 'title' => $this->getLanguageService()->sL($this->pathToLocallang . ':label_doaction_swap')];
                }
            }
            if ($currentWorkspace !== WorkspaceService::LIVE_WORKSPACE_ID) {
                $actions[] = ['action' => 'discard', 'title' => $this->getLanguageService()->sL($this->pathToLocallang . ':label_doaction_discard')];
            }
        }
        $result = [
            'total' => count($actions),
            'data' => $actions
        ];
        return $result;
    }

    /**
     * Publishes the current workspace.
     *
     * @param \stdClass $parameters
     * @return array
     */
    public function publishWorkspace(\stdClass $parameters)
    {
        $result = [
            'init' => false,
            'total' => 0,
            'processed' => 0,
            'error' => false
        ];
        try {
            if ($parameters->init) {
                $language = $this->validateLanguageParameter($parameters);
                $cnt = $this->initPublishData($this->getCurrentWorkspace(), $parameters->swap, $language);
                $result['total'] = $cnt;
            } else {
                $result['processed'] = $this->processData();
                $result['total'] = $this->getBackendUser()->getSessionData('workspaceMassAction_total');
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Flushes the current workspace.
     *
     * @param \stdClass $parameters
     * @return array
     */
    public function flushWorkspace(\stdClass $parameters)
    {
        $result = [
            'init' => false,
            'total' => 0,
            'processed' => 0,
            'error' => false
        ];
        try {
            if ($parameters->init) {
                $language = $this->validateLanguageParameter($parameters);
                $result['total'] = $this->initFlushData($this->getCurrentWorkspace(), $language);
            } else {
                $result['processed'] = $this->processData();
                $result['total'] = $this->getBackendUser()->getSessionData('workspaceMassAction_total');
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Initializes the command map to be used for publishing.
     *
     * @param int $workspace
     * @param bool $swap
     * @param int $language
     * @return int
     */
    protected function initPublishData($workspace, $swap, $language = null)
    {
        // workspace might be -98 a.k.a "All Workspaces but that's save here
        $publishData = $this->workspaceService->getCmdArrayForPublishWS($workspace, $swap, 0, $language);
        $recordCount = 0;
        foreach ($publishData as $table => $recs) {
            $recordCount += count($recs);
        }
        if ($recordCount > 0) {
            $this->getBackendUser()->setAndSaveSessionData('workspaceMassAction', $publishData);
            $this->getBackendUser()->setAndSaveSessionData('workspaceMassAction_total', $recordCount);
            $this->getBackendUser()->setAndSaveSessionData('workspaceMassAction_processed', 0);
        }
        return $recordCount;
    }

    /**
     * Initializes the command map to be used for flushing.
     *
     * @param int $workspace
     * @param int $language
     * @return int
     */
    protected function initFlushData($workspace, $language = null)
    {
        // workspace might be -98 a.k.a "All Workspaces but that's save here
        $flushData = $this->workspaceService->getCmdArrayForFlushWS($workspace, true, 0, $language);
        $recordCount = 0;
        foreach ($flushData as $table => $recs) {
            $recordCount += count($recs);
        }
        if ($recordCount > 0) {
            $this->getBackendUser()->setAndSaveSessionData('workspaceMassAction', $flushData);
            $this->getBackendUser()->setAndSaveSessionData('workspaceMassAction_total', $recordCount);
            $this->getBackendUser()->setAndSaveSessionData('workspaceMassAction_processed', 0);
        }
        return $recordCount;
    }

    /**
     * Processes the data.
     *
     * @return int
     */
    protected function processData()
    {
        $processData = $this->getBackendUser()->getSessionData('workspaceMassAction');
        $recordsProcessed = $this->getBackendUser()->getSessionData('workspaceMassAction_processed');
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
            $this->getBackendUser()->setAndSaveSessionData('workspaceMassAction', null);
            $this->getBackendUser()->setAndSaveSessionData('workspaceMassAction_total', 0);
        } else {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            // Execute the commands:
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
            $this->getBackendUser()->setAndSaveSessionData('workspaceMassAction', $processData);
            $this->getBackendUser()->setAndSaveSessionData('workspaceMassAction_processed', $recordsProcessed);
        }
        return $recordsProcessed;
    }

    /**
     * Validates whether the submitted language parameter can be
     * interpreted as integer value.
     *
     * @param \stdClass $parameters
     * @return int|null
     */
    protected function validateLanguageParameter(\stdClass $parameters)
    {
        $language = null;
        if (isset($parameters->language) && MathUtility::canBeInterpretedAsInteger($parameters->language)) {
            $language = $parameters->language;
        }
        return $language;
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
}
