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
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Abstract class to show log entries from sys_log
 */
abstract class AbstractController extends ActionController
{
    /**
     * @var int
     */
    const TIMEFRAME_THISWEEK = 0;

    /**
     * @var int
     */
    const TIMEFRAME_LASTWEEK = 1;

    /**
     * @var int
     */
    const TIMEFRAME_LASTSEVENDAYS = 2;

    /**
     * @var int
     */
    const TIMEFRAME_THISMONTH = 10;

    /**
     * @var int
     */
    const TIMEFRAME_LASTMONTH = 11;

    /**
     * @var int
     */
    const TIMEFRAME_LAST31DAYS = 12;

    /**
     * @var int
     */
    const TIMEFRAME_CUSTOM = 30;

    /**
     * Whether plugin is running in page context (sub module of Web > Info)
     *
     * @var bool
     */
    protected $isInPageContext = false;

    /**
     * Page ID in page context
     *
     * @var int
     */
    protected $pageId = 0;

    /**
     * @var \TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository
     */
    protected $logEntryRepository = null;

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
     * Initialize the view
     *
     * @param ViewInterface $view The view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        if ($view instanceof BackendTemplateView) {
            parent::initializeView($view);
            $view->getModuleTemplate()->getPageRenderer()->loadExtJS();
            $view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');
        }
    }

    /**
     * init all actions
     * @return void
     */
    public function initializeAction()
    {
        if ($this->isInPageContext === false) {
            $this->defaultViewObjectName = BackendTemplateView::class;
        }
    }

    /**
     * Initialize index action
     *
     * @return void
     * @throws \RuntimeException
     */
    public function initializeIndexAction()
    {
        // @TODO: Extbase backend modules rely on frontend TypoScript for view, persistence
        // and settings. Thus, we need a TypoScript root template, that then loads the
        // ext_typoscript_setup.txt file of this module. This is nasty, but can not be
        // circumvented until there is a better solution in extbase.
        // For now we throw an exception if no settings are detected.
        if (empty($this->settings)) {
            throw new \RuntimeException(
                'No settings detected. This usually happens if there is no frontend TypoScript template with root flag set. Please create one.',
                1333650506
            );
        }
        if (!isset($this->settings['dateFormat'])) {
            $this->settings['dateFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? 'm-d-Y' : 'd-m-Y';
        }
        if (!isset($this->settings['timeFormat'])) {
            $this->settings['timeFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
        }
    }

    /**
     * Show general information and the installed modules
     *
     * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
     * @return void
     */
    public function indexAction(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint = null)
    {
        // Constraint object handling:
        // If there is none from GET, try to get it from BE user data, else create new
        if ($constraint === null) {
            $constraint = $this->getConstraintFromBeUserData();
            if ($constraint === null) {
                $constraint = $this->objectManager->get(\TYPO3\CMS\Belog\Domain\Model\Constraint::class);
            }
        } else {
            $this->persistConstraintInBeUserData($constraint);
        }
        $constraint->setIsInPageContext($this->isInPageContext);
        $constraint->setPageId($this->pageId);
        $this->setStartAndEndTimeFromTimeSelector($constraint);
        $this->forceWorkspaceSelectionIfInWorkspace($constraint);
        $logEntries = $this->logEntryRepository->findByConstraint($constraint);
        $groupedLogEntries = $this->groupLogEntriesByPageAndDay($logEntries, $constraint->getGroupByPage());
        $this->view->assign('groupedLogEntries', $groupedLogEntries)->assign('constraint', $constraint)->assign('userGroups', $this->createUserAndGroupListForSelectOptions())->assign('workspaces', $this->createWorkspaceListForSelectOptions())->assign('pageDepths', $this->createPageDepthOptions());
    }

    /**
     * Get module states (the constraint object) from user data
     *
     * @return \TYPO3\CMS\Belog\Domain\Model\Constraint|NULL
     */
    protected function getConstraintFromBeUserData()
    {
        $serializedConstraint = $GLOBALS['BE_USER']->getModuleData(get_class($this));
        if (!is_string($serializedConstraint) || empty($serializedConstraint)) {
            return null;
        }
        return @unserialize($serializedConstraint);
    }

    /**
     * Save current constraint object in be user settings (uC)
     *
     * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
     * @return void
     */
    protected function persistConstraintInBeUserData(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint)
    {
        $GLOBALS['BE_USER']->pushModuleData(get_class($this), serialize($constraint));
    }

    /**
     * Create a sorted array for day and page view from
     * the query result of the sys log repository.
     *
     * If group by page is FALSE, pid is always -1 (will render a flat list),
     * otherwise the output is splitted by pages.
     * '12345' is a sub array to split entries by day, number is first second of day
     *
     * [pid][dayTimestamp][items]
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface<\TYPO3\CMS\Belog\Domain\Model\LogEntry> $logEntries
     * @param bool $groupByPage Whether or not log entries should be grouped by page
     * @return array
     */
    protected function groupLogEntriesByPageAndDay(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface $logEntries, $groupByPage = false)
    {
        $targetStructure = [];
        /** @var $entry \TYPO3\CMS\Belog\Domain\Model\LogEntry */
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
        /** @var $workspace \TYPO3\CMS\Belog\Domain\Model\Workspace */
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
     * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
     * @return void
     */
    protected function forceWorkspaceSelectionIfInWorkspace(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint)
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
            0 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lang/locallang_core.xlf:labels.depth_0', 'lang'),
            1 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lang/locallang_core.xlf:labels.depth_1', 'lang'),
            2 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lang/locallang_core.xlf:labels.depth_2', 'lang'),
            3 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lang/locallang_core.xlf:labels.depth_3', 'lang'),
            4 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lang/locallang_core.xlf:labels.depth_4', 'lang'),
            999 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi', 'lang')
        ];
        return $options;
    }

    /**
     * Calculate the start- and end timestamp from the different time selector options
     *
     * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
     * @return void
     */
    protected function setStartAndEndTimeFromTimeSelector(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint)
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
                $startTime = $constraint->getStartTimestamp();
                if ($constraint->getEndTimestamp() > $constraint->getStartTimestamp()) {
                    $endTime = $constraint->getEndTimestamp();
                } else {
                    $endTime = $GLOBALS['EXEC_TIME'];
                }
                break;
            default:
        }
        $constraint->setStartTimestamp($startTime);
        $constraint->setEndTimestamp($endTime);
    }
}
