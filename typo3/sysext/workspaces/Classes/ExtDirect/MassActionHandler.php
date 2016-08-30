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

/**
 * Class encapsulates all actions which are triggered for all elements within the current workspace.
 */
class MassActionHandler extends AbstractHandler
{
    const MAX_RECORDS_TO_PROCESS = 30;

    /**
     * Path to the locallang file
     *
     * @var string
     */
    private $pathToLocallang = 'LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf';

    /**
     * Get list of available mass workspace actions.
     *
     * @param \stdClass $parameter
     * @return array $data
     */
    public function getMassStageActions($parameter)
    {
        $actions = [];
        $currentWorkspace = $this->getCurrentWorkspace();
        $massActionsEnabled = $GLOBALS['BE_USER']->getTSConfigVal('options.workspaces.enableMassActions') !== '0';
        // in case we're working within "All Workspaces" we can't provide Mass Actions
        if ($currentWorkspace != \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES && $massActionsEnabled) {
            $publishAccess = $GLOBALS['BE_USER']->workspacePublishAccess($currentWorkspace);
            if ($publishAccess && !($GLOBALS['BE_USER']->workspaceRec['publish_access'] & 1)) {
                $actions[] = ['action' => 'publish', 'title' => $GLOBALS['LANG']->sL($this->pathToLocallang . ':label_doaction_publish')];
                if ($GLOBALS['BE_USER']->workspaceSwapAccess()) {
                    $actions[] = ['action' => 'swap', 'title' => $GLOBALS['LANG']->sL($this->pathToLocallang . ':label_doaction_swap')];
                }
            }
            if ($currentWorkspace !== \TYPO3\CMS\Workspaces\Service\WorkspaceService::LIVE_WORKSPACE_ID) {
                $actions[] = ['action' => 'discard', 'title' => $GLOBALS['LANG']->sL($this->pathToLocallang . ':label_doaction_discard')];
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
     * @param stdclass $parameters
     * @return array
     */
    public function publishWorkspace(\stdclass $parameters)
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
                $result['processed'] = $this->processData($this->getCurrentWorkspace());
                $result['total'] = $GLOBALS['BE_USER']->getSessionData('workspaceMassAction_total');
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Flushes the current workspace.
     *
     * @param stdclass $parameters
     * @return array
     */
    public function flushWorkspace(\stdclass $parameters)
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
                $cnt = $this->initFlushData($this->getCurrentWorkspace(), $language);
                $result['total'] = $cnt;
            } else {
                $result['processed'] = $this->processData($this->getCurrentWorkspace());
                $result['total'] = $GLOBALS['BE_USER']->getSessionData('workspaceMassAction_total');
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
        $publishData = $this->getWorkspaceService()->getCmdArrayForPublishWS($workspace, $swap, 0, $language);
        $recordCount = 0;
        foreach ($publishData as $table => $recs) {
            $recordCount += count($recs);
        }
        if ($recordCount > 0) {
            $GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction', $publishData);
            $GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_total', $recordCount);
            $GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_processed', 0);
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
        $flushData = $this->getWorkspaceService()->getCmdArrayForFlushWS($workspace, true, 0, $language);
        $recordCount = 0;
        foreach ($flushData as $table => $recs) {
            $recordCount += count($recs);
        }
        if ($recordCount > 0) {
            $GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction', $flushData);
            $GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_total', $recordCount);
            $GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_processed', 0);
        }
        return $recordCount;
    }

    /**
     * Processes the data.
     *
     * @param int $workspace
     * @return int
     */
    protected function processData($workspace)
    {
        $processData = $GLOBALS['BE_USER']->getSessionData('workspaceMassAction');
        $recordsProcessed = $GLOBALS['BE_USER']->getSessionData('workspaceMassAction_processed');
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
            $GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction', null);
            $GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_total', 0);
        } else {
            /** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
            $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
            $tce->stripslashes_values = 0;
            // Execute the commands:
            $tce->start([], $limitedCmd);
            $tce->process_cmdmap();
            $errors = $tce->errorLog;
            if (!empty($errors)) {
                throw new \Exception(implode(', ', $errors));
            }
            // Unset processed records
            foreach ($limitedCmd as $table => $recs) {
                foreach ($recs as $key => $value) {
                    $recordsProcessed++;
                    unset($processData[$table][$key]);
                }
            }
            $GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction', $processData);
            $GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_processed', $recordsProcessed);
        }
        return $recordsProcessed;
    }
}
