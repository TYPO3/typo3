<?php
namespace TYPO3\CMS\Belog\Controller;

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

use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Belog\Domain\Model\Constraint;
use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Abstract class to show log entries from sys_log
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendLogController extends ActionController
{
    /**
     * @var int
     */
    private const TIMEFRAME_THISWEEK = 0;

    /**
     * @var int
     */
    private const TIMEFRAME_LASTWEEK = 1;

    /**
     * @var int
     */
    private const TIMEFRAME_LASTSEVENDAYS = 2;

    /**
     * @var int
     */
    private const TIMEFRAME_THISMONTH = 10;

    /**
     * @var int
     */
    private const TIMEFRAME_LASTMONTH = 11;

    /**
     * @var int
     */
    private const TIMEFRAME_LAST31DAYS = 12;

    /**
     * @var int
     */
    private const TIMEFRAME_CUSTOM = 30;

    /**
     * @var \TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository
     */
    protected $logEntryRepository;

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * @param \TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository $logEntryRepository
     */
    public function injectLogEntryRepository(\TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository $logEntryRepository)
    {
        $this->logEntryRepository = $logEntryRepository;
    }

    /**
     * Initialize list action
     */
    public function initializeListAction()
    {
        if (!isset($this->settings['dateFormat'])) {
            $this->settings['dateFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? 'm-d-Y' : 'd-m-Y';
        }
        if (!isset($this->settings['timeFormat'])) {
            $this->settings['timeFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
        }
        $constraintConfiguration = $this->arguments->getArgument('constraint')->getPropertyMappingConfiguration();
        $constraintConfiguration->allowAllProperties();
    }

    /**
     * Show general information and the installed modules
     *
     * @param Constraint $constraint
     * @param int $pageId
     * @param string $layout
     */
    public function listAction(Constraint $constraint = null, int $pageId = null, string $layout = 'Default')
    {
        // Constraint object handling:
        // If there is none from GET, try to get it from BE user data, else create new
        if ($constraint === null) {
            $constraint = $this->getConstraintFromBeUserData();
        } else {
            $this->persistConstraintInBeUserData($constraint);
        }
        $constraint->setPageId($pageId);
        $this->resetConstraintsOnMemoryExhaustionError();
        $this->setStartAndEndTimeFromTimeSelector($constraint);
        $this->forceWorkspaceSelectionIfInWorkspace($constraint);
        $logEntries = $this->logEntryRepository->findByConstraint($constraint);
        $groupedLogEntries = $this->groupLogEntriesByPageAndDay($logEntries, $constraint->getGroupByPage());
        $this->view->assignMultiple([
            'pageId' => $pageId,
            'layout' => $layout,
            'groupedLogEntries' => $groupedLogEntries,
            'constraint' => $constraint,
            'userGroups' => $this->createUserAndGroupListForSelectOptions(),
            'workspaces' => $this->createWorkspaceListForSelectOptions(),
            'pageDepths' => $this->createPageDepthOptions(),
        ]);
    }

    /**
     * Delete all log entries that share the same message with the log entry given
     * in $errorUid
     *
     * @param int $errorUid
     */
    public function deleteMessageAction(int $errorUid)
    {
        /** @var \TYPO3\CMS\Belog\Domain\Model\LogEntry $logEntry */
        $logEntry = $this->logEntryRepository->findByUid($errorUid);
        if (!$logEntry) {
            $this->addFlashMessage(LocalizationUtility::translate('actions.delete.noRowFound', 'belog'), '', AbstractMessage::WARNING);
            $this->redirect('list');
        }
        $numberOfDeletedRows = $this->logEntryRepository->deleteByMessageDetails($logEntry);
        $this->addFlashMessage(sprintf(LocalizationUtility::translate('actions.delete.message', 'belog'), $numberOfDeletedRows));
        $this->redirect('list');
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
        return $constraint ?: $this->objectManager->get(Constraint::class);
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
        register_shutdown_function(function () use (&$reservedMemory) {
            $reservedMemory = null; // free the reserved memory
            $error = error_get_last();
            if (strpos($error['message'], 'Allowed memory size of') !== false) {
                $constraint = $this->objectManager->get(Constraint::class);
                $this->persistConstraintInBeUserData($constraint);
            }
        });
    }

    /**
     * Create a sorted array for day and page view from
     * the query result of the sys log repository.
     *
     * If group by page is FALSE, pid is always -1 (will render a flat list),
     * otherwise the output is split by pages.
     * '12345' is a sub array to split entries by day, number is first second of day
     *
     * [pid][dayTimestamp][items]
     *
     * @param QueryResultInterface $logEntries
     * @param bool $groupByPage Whether or not log entries should be grouped by page
     * @return array
     */
    protected function groupLogEntriesByPageAndDay(QueryResultInterface $logEntries, $groupByPage = false)
    {
        $targetStructure = [];
        /** @var LogEntry $entry */
        foreach ($logEntries as $entry) {
            // Create page split list or flat list
            if ($groupByPage) {
                $pid = $entry->getEventPid();
            } else {
                $pid = -1;
            }
            // Create array if it is not defined yet
            if (!is_array($targetStructure[$pid])) {
                $targetStructure[$pid] = [];
            }
            // Get day timestamp of log entry and create sub array if needed
            $timestampDay = strtotime(strftime('%d.%m.%Y', $entry->getTstamp()));
            if (!is_array($targetStructure[$pid][$timestampDay])) {
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
        $userGroupArray[0] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('allUsers', 'Belog');
        $userGroupArray[-1] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('self', 'Belog');
        // List of groups, key is gr-'uid'
        $groups = \TYPO3\CMS\Backend\Utility\BackendUtility::getGroupNames();
        foreach ($groups as $group) {
            $userGroupArray['gr-' . $group['uid']] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('group', 'Belog') . ' ' . $group['title'];
        }
        // List of users, key is us-'uid'
        $users = \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames();
        foreach ($users as $user) {
            $userGroupArray['us-' . $user['uid']] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('user', 'Belog') . ' ' . $user['username'];
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
        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
            return [];
        }
        $workspaceArray = [];
        // Two meta entries: 'all' and 'live'
        $workspaceArray[-99] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('any', 'Belog');
        $workspaceArray[0] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('live', 'Belog');
        $workspaces = $this->objectManager->get(\TYPO3\CMS\Belog\Domain\Repository\WorkspaceRepository::class)->findAll();
        /** @var \TYPO3\CMS\Belog\Domain\Model\Workspace $workspace */
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
        if ($GLOBALS['BE_USER']->workspace !== 0) {
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
            0 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0', 'lang'),
            1 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1', 'lang'),
            2 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2', 'lang'),
            3 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3', 'lang'),
            4 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4', 'lang'),
            999 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi', 'lang')
        ];
        return $options;
    }

    /**
     * Calculate the start- and end timestamp from the different time selector options
     *
     * @param Constraint $constraint
     */
    protected function setStartAndEndTimeFromTimeSelector(Constraint $constraint)
    {
        $startTime = 0;
        $endTime = $GLOBALS['EXEC_TIME'];
        // @TODO: Refactor this construct
        switch ($constraint->getTimeFrame()) {
            case self::TIMEFRAME_THISWEEK:
                // This week
                $week = (date('w') ?: 7) - 1;
                $startTime = mktime(0, 0, 0) - $week * 3600 * 24;
                break;
            case self::TIMEFRAME_LASTWEEK:
                // Last week
                $week = (date('w') ?: 7) - 1;
                $startTime = mktime(0, 0, 0) - ($week + 7) * 3600 * 24;
                $endTime = mktime(0, 0, 0) - $week * 3600 * 24;
                break;
            case self::TIMEFRAME_LASTSEVENDAYS:
                // Last 7 days
                $startTime = mktime(0, 0, 0) - 7 * 3600 * 24;
                break;
            case self::TIMEFRAME_THISMONTH:
                // This month
                $startTime = mktime(0, 0, 0, date('m'), 1);
                break;
            case self::TIMEFRAME_LASTMONTH:
                // Last month
                $startTime = mktime(0, 0, 0, date('m') - 1, 1);
                $endTime = mktime(0, 0, 0, date('m'), 1);
                break;
            case self::TIMEFRAME_LAST31DAYS:
                // Last 31 days
                $startTime = mktime(0, 0, 0) - 31 * 3600 * 24;
                break;
            case self::TIMEFRAME_CUSTOM:
                $startTime = $constraint->getManualDateStart() ? $constraint->getManualDateStart()->getTimestamp() : 0;
                $endTime = $constraint->getManualDateStop() ? $constraint->getManualDateStop()->getTimestamp() : 0;
                if ($endTime <= $startTime) {
                    $endTime = $GLOBALS['EXEC_TIME'];
                }
                break;
            default:
        }
        $constraint->setStartTimestamp($startTime);
        $constraint->setEndTimestamp($endTime);
    }
}
