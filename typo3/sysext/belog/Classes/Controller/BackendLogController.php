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

namespace TYPO3\CMS\Belog\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Belog\Domain\Model\Constraint;
use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Belog\Domain\Model\Workspace;
use TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository;
use TYPO3\CMS\Belog\Domain\Repository\WorkspaceRepository;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Show log entries from sys_log
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendLogController extends ActionController
{
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected LogEntryRepository $logEntryRepository;
    protected WorkspaceRepository $workspaceRepository;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        LogEntryRepository $logEntryRepository,
        WorkspaceRepository $workspaceRepository
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->logEntryRepository = $logEntryRepository;
        $this->workspaceRepository = $workspaceRepository;
    }

    /**
     * Initialize list action
     */
    public function initializeListAction()
    {
        if (!isset($this->settings['dateFormat'])) {
            $this->settings['dateFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?: 'd-m-Y';
        }
        if (!isset($this->settings['timeFormat'])) {
            $this->settings['timeFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
        }
        // Static format needed for date picker (flatpickr), see BackendController::generateJavascript() and #91606
        $this->settings['dateTimeFormat'] = ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? 'H:i m-d-Y' : 'H:i d-m-Y');
        $constraintConfiguration = $this->arguments->getArgument('constraint')->getPropertyMappingConfiguration();
        $constraintConfiguration->allowAllProperties();
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/GlobalEventHandler');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Belog/BackendLog');
    }

    /**
     * Show general information and the installed modules
     *
     * @param Constraint|null $constraint
     * @param int|null $pageId
     * @param string $layout
     * @param string $operation
     * @return ResponseInterface
     */
    public function listAction(Constraint $constraint = null, int $pageId = null, string $layout = 'Default', string $operation = ''): ResponseInterface
    {
        if ($operation === 'reset-filters') {
            $constraint = new Constraint();
        } elseif ($constraint === null) {
            $constraint = $this->getConstraintFromBeUserData();
        }
        $this->persistConstraintInBeUserData($constraint);
        $constraint->setPageId($pageId);
        $this->resetConstraintsOnMemoryExhaustionError();
        $this->setStartAndEndTimeFromTimeSelector($constraint);
        $this->forceWorkspaceSelectionIfInWorkspace($constraint);
        $logEntries = $this->logEntryRepository->findByConstraint($constraint);
        $groupedLogEntries = $this->groupLogEntriesDay($logEntries);
        $this->view->assignMultiple([
            'pageId' => $pageId,
            'layout' => $layout,
            'groupedLogEntries' => $groupedLogEntries,
            'constraint' => $constraint,
            'userGroups' => $this->createUserAndGroupListForSelectOptions(),
            'workspaces' => $this->createWorkspaceListForSelectOptions(),
            'pageDepths' => $this->createPageDepthOptions(),
            'channels' => $this->logEntryRepository->getUsedChannels(),
            'channel' => $constraint->getChannel(),
            'levels' => $this->logEntryRepository->getUsedLevels(),
            'level' => $constraint->getLevel(),
        ]);

        if ($layout === 'Default') {
            $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
            $moduleTemplate->setTitle(LocalizationUtility::translate('LLL:EXT:belog/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'));
            $moduleTemplate->setContent($this->view->render());
            return $this->htmlResponse($moduleTemplate->renderContent());
        }
        return $this->htmlResponse();
    }

    /**
     * Delete all log entries that share the same message with the log entry given
     * in $errorUid
     *
     * @param int $errorUid
     */
    public function deleteMessageAction(int $errorUid): ResponseInterface
    {
        /** @var LogEntry $logEntry */
        $logEntry = $this->logEntryRepository->findByUid($errorUid);
        if (!$logEntry) {
            $this->addFlashMessage(LocalizationUtility::translate('actions.delete.noRowFound', 'belog') ?? '', '', AbstractMessage::WARNING);
            return new ForwardResponse('list');
        }
        $numberOfDeletedRows = $this->logEntryRepository->deleteByMessageDetails($logEntry);
        $this->addFlashMessage(sprintf(LocalizationUtility::translate('actions.delete.message', 'belog') ?? '', $numberOfDeletedRows));
        return new ForwardResponse('list');
    }

    /**
     * Get module states (the constraint object) from user data
     *
     * @return Constraint
     */
    protected function getConstraintFromBeUserData()
    {
        $serializedConstraint = $GLOBALS['BE_USER']->getModuleData(static::class);
        $constraint = null;
        if (is_string($serializedConstraint) && !empty($serializedConstraint)) {
            $constraint = @unserialize($serializedConstraint, ['allowed_classes' => [Constraint::class, \DateTime::class]]);
        }
        return $constraint ?: GeneralUtility::makeInstance(Constraint::class);
    }

    /**
     * Save current constraint object in be user settings (uC)
     *
     * @param Constraint $constraint
     */
    protected function persistConstraintInBeUserData(Constraint $constraint)
    {
        $GLOBALS['BE_USER']->pushModuleData(static::class, serialize($constraint));
    }

    /**
     * In case the script execution fails, because the user requested too many results
     * (memory exhaustion in php), reset the constraints in be user settings, so
     * the belog can be accessed again in the next call.
     */
    protected function resetConstraintsOnMemoryExhaustionError()
    {
        $reservedMemory = new \SplFixedArray(187500); // 3M
        register_shutdown_function(function () use (&$reservedMemory): void {
            $reservedMemory = null; // free the reserved memory
            $error = error_get_last();
            if (str_contains($error['message'] ?? '', 'Allowed memory size of')) {
                $constraint = GeneralUtility::makeInstance(Constraint::class);
                $this->persistConstraintInBeUserData($constraint);
            }
        });
    }

    /**
     * Create a sorted array for day from the query result of the sys log repository.
     *
     * pid is always -1 to render a flat list.
     * '12345' is a sub array to split entries by day, number is first second of day
     *
     * [pid][dayTimestamp][items]
     *
     * @param QueryResultInterface $logEntries
     * @return array
     */
    protected function groupLogEntriesDay(QueryResultInterface $logEntries): array
    {
        $targetStructure = [];
        /** @var LogEntry $entry */
        foreach ($logEntries as $entry) {
            $pid = -1;
            // Create array if it is not defined yet
            if (!is_array($targetStructure[$pid] ?? false)) {
                $targetStructure[-1] = [];
            }
            // Get day timestamp of log entry and create sub array if needed
            // @todo Replace deprecated strftime in php 8.1. Suppress warning in v11.
            $timestampDay = strtotime(@strftime('%d.%m.%Y', $entry->getTstamp()) ?: '');
            if (!is_array($targetStructure[$pid][$timestampDay] ?? false)) {
                $targetStructure[$pid][$timestampDay] = [];
            }
            // Add row
            $targetStructure[$pid][$timestampDay][] = $entry;
        }
        ksort($targetStructure);
        return $targetStructure;
    }

    /**
     * Create options for the user / group drop down.
     * This is not moved to a repository by intention to not mix up this 'meta' data
     * with real repository work
     *
     * @return array Key is the option name, value its label
     */
    protected function createUserAndGroupListForSelectOptions()
    {
        $userGroupArray = [];
        // Two meta entries: 'all' and 'self'
        $userGroupArray[0] = LocalizationUtility::translate('allUsers', 'Belog');
        $userGroupArray[-1] = LocalizationUtility::translate('self', 'Belog');
        // List of groups, key is gr-'uid'
        $groups = BackendUtility::getGroupNames();
        foreach ($groups as $group) {
            $userGroupArray['gr-' . $group['uid']] = LocalizationUtility::translate('group', 'Belog') . ' ' . $group['title'];
        }
        // List of users, key is us-'uid'
        $users = BackendUtility::getUserNames();
        foreach ($users as $user) {
            $userGroupArray['us-' . $user['uid']] = LocalizationUtility::translate('user', 'Belog') . ' ' . $user['username'];
        }
        return $userGroupArray;
    }

    /**
     * Create options for the workspace selector
     *
     * @return array Key is uid of workspace, value its label
     */
    protected function createWorkspaceListForSelectOptions()
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')) {
            return [];
        }
        $workspaceArray = [];
        // Two meta entries: 'all' and 'live'
        $workspaceArray[-99] = LocalizationUtility::translate('any', 'Belog');
        $workspaceArray[0] = LocalizationUtility::translate('live', 'Belog');
        $workspaces = $this->workspaceRepository->findAll();
        /** @var Workspace $workspace */
        foreach ($workspaces as $workspace) {
            $workspaceArray[$workspace->getUid()] = $workspace->getUid() . ': ' . $workspace->getTitle();
        }
        return $workspaceArray;
    }

    /**
     * If the user is in a workspace different than LIVE,
     * we force to show only log entries from the selected workspace,
     * and the workspace selector is not shown.
     *
     * @param Constraint $constraint
     */
    protected function forceWorkspaceSelectionIfInWorkspace(Constraint $constraint)
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')) {
            $this->view->assign('showWorkspaceSelector', false);
        } elseif ($GLOBALS['BE_USER']->workspace !== 0) {
            $constraint->setWorkspaceUid($GLOBALS['BE_USER']->workspace);
            $this->view->assign('showWorkspaceSelector', false);
        } else {
            $this->view->assign('showWorkspaceSelector', true);
        }
    }

    /**
     * Create options for the 'depth of page levels' selector.
     * This is shown if the module is displayed in page -> info
     *
     * @return array Key is depth identifier (1 = One level), value the localized select option label
     */
    protected function createPageDepthOptions()
    {
        $options = [
            0 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0', 'lang'),
            1 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1', 'lang'),
            2 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2', 'lang'),
            3 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3', 'lang'),
            4 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4', 'lang'),
            999 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi', 'lang'),
        ];
        return $options;
    }

    /**
     * Calculate the start- and end timestamp
     *
     * @param Constraint $constraint
     */
    protected function setStartAndEndTimeFromTimeSelector(Constraint $constraint)
    {
        $startTime = $constraint->getManualDateStart() ? $constraint->getManualDateStart()->getTimestamp() : 0;
        $endTime = $constraint->getManualDateStop() ? $constraint->getManualDateStop()->getTimestamp() : 0;
        if ($endTime <= $startTime) {
            $endTime = $GLOBALS['EXEC_TIME'];
        }
        $constraint->setStartTimestamp($startTime);
        $constraint->setEndTimestamp($endTime);
    }
}
