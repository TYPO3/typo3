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

namespace TYPO3\CMS\Belog\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Belog\Domain\Model\Constraint;
use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly LogEntryRepository $logEntryRepository,
        protected readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Initialize list action
     */
    public function initializeListAction(): void
    {
        if (!isset($this->settings['dateFormat'])) {
            $this->settings['dateFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?: 'd-m-Y';
        }
        if (!isset($this->settings['timeFormat'])) {
            $this->settings['timeFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
        }
        // Static format needed for date picker (flatpickr), see BackendController::generateJavascript() and #91606
        $this->settings['dateTimeFormat'] = 'H:i d-m-Y';
        $constraintConfiguration = $this->arguments->getArgument('constraint')->getPropertyMappingConfiguration();
        $constraintConfiguration->allowAllProperties();
    }

    /**
     * Show general information and the installed modules
     */
    public function listAction(Constraint $constraint = null, string $operation = ''): ResponseInterface
    {
        if ($operation === 'reset-filters') {
            $constraint = new Constraint();
        } elseif ($constraint === null) {
            $constraint = $this->getConstraintFromBeUserData();
        }

        $access = true;
        $pageId = $constraint->getPageId();
        $permsClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        if ($pageId === 0 || (BackendUtility::readPageAccess($pageId, $permsClause) ?: []) === []) {
            if (!$this->getBackendUser()->isAdmin()) {
                // User does not have access to selected site
                $access = false;
            }

            if ($pageId === 0) {
                // In case no page is selected, set depth to 0 to display only "global" logs
                $constraint->setDepth(0);
            }
        }

        $this->persistConstraintInBeUserData($constraint);
        $this->resetConstraintsOnMemoryExhaustionError();
        $this->setStartAndEndTimeFromTimeSelector($constraint);
        $showWorkspaceSelector = $this->forceWorkspaceSelectionIfInWorkspace($constraint);

        $viewVariables = [
            'access' => $access,
            'settings' => $this->settings,
            'pageId' => $pageId,
            'constraint' => $constraint,
            'userGroups' => $this->createUserAndGroupListForSelectOptions(),
            'selectableNumberOfLogEntries' => $this->createSelectableNumberOfLogEntriesOptions(),
            'workspaces' => $this->createWorkspaceListForSelectOptions(),
            'pageDepths' => $this->createPageDepthOptions(),
            'channels' => $this->logEntryRepository->getUsedChannels(),
            'channel' => $constraint->getChannel(),
            'levels' => $this->logEntryRepository->getUsedLevels(),
            'level' => $constraint->getLevel(),
            'showWorkspaceSelector' => $showWorkspaceSelector,
        ];

        if ($access) {
            // Only fetch log entries if user has access
            $logEntries = $this->logEntryRepository->findByConstraint($constraint);
            $groupedLogEntries = $this->groupLogEntriesDay($logEntries);
            $viewVariables['groupedLogEntries'] = $groupedLogEntries;
        }

        return $this->moduleTemplateFactory
            ->create($this->request)
            ->setFlashMessageQueue($this->getFlashMessageQueue())
            ->setTitle(LocalizationUtility::translate('LLL:EXT:belog/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'))
            ->assignMultiple($viewVariables)
            ->renderResponse('BackendLog/List');
    }

    /**
     * Delete all log entries that share the same message with the log entry given
     * in $errorUid
     */
    public function deleteMessageAction(int $errorUid): ResponseInterface
    {
        /** @var LogEntry|null $logEntry */
        $logEntry = $this->logEntryRepository->findByUid($errorUid);
        if (!$logEntry) {
            $this->addFlashMessage(LocalizationUtility::translate('actions.delete.noRowFound', 'belog') ?? '', '', ContextualFeedbackSeverity::WARNING);
            return $this->redirect('list');
        }
        $numberOfDeletedRows = $this->logEntryRepository->deleteByMessageDetails($logEntry);
        $this->addFlashMessage(sprintf(LocalizationUtility::translate('actions.delete.message', 'belog') ?? '', $numberOfDeletedRows));
        return $this->redirect('list');
    }

    /**
     * Get module states (the constraint object) from user data
     */
    protected function getConstraintFromBeUserData(): Constraint
    {
        $serializedConstraint = $this->request->getAttribute('moduleData')->get('constraint');
        $constraint = null;
        if (is_string($serializedConstraint) && !empty($serializedConstraint)) {
            $constraint = @unserialize($serializedConstraint, ['allowed_classes' => [Constraint::class, \DateTime::class]]);
        }
        return $constraint ?: GeneralUtility::makeInstance(Constraint::class);
    }

    /**
     * Save current constraint object in be user settings (uC)
     */
    protected function persistConstraintInBeUserData(Constraint $constraint): void
    {
        $moduleData = $this->request->getAttribute('moduleData');
        $moduleData->set('constraint', serialize($constraint));
        $this->getBackendUser()->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
    }

    /**
     * In case the script execution fails, because the user requested too many results
     * (memory exhaustion in php), reset the constraints in be user settings, so
     * the belog can be accessed again in the next call.
     */
    protected function resetConstraintsOnMemoryExhaustionError(): void
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
            $entryTimestamp = \DateTimeImmutable::createFromFormat('U', (string)$entry->getTstamp());
            $timestampDay = strtotime($entryTimestamp->format('d.m.Y'));
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
     * with real repository work.
     *
     * @return array Key is the option name, value its label
     */
    protected function createUserAndGroupListForSelectOptions(): array
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
     * Options for the "max" drop down
     */
    protected function createSelectableNumberOfLogEntriesOptions(): array
    {
        return [
            50 => 50,
            100 => 100,
            200 => 200,
            500 => 500,
            1000 => 1000,
            1000000 => LocalizationUtility::translate('any', 'Belog'),
        ];
    }

    /**
     * Create options for the workspace selector
     *
     * @return array Key is uid of workspace, value its label
     */
    protected function createWorkspaceListForSelectOptions(): array
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')) {
            return [];
        }
        $workspaceArray = [];
        // Two meta entries: 'all' and 'live'
        $workspaceArray[-99] = LocalizationUtility::translate('any', 'Belog');
        $workspaceArray[0] = LocalizationUtility::translate('live', 'Belog');
        $resultSet = $this->connectionPool->getQueryBuilderForTable('sys_workspace')
            ->select('uid', 'title')
            ->from('sys_workspace')
            ->executeQuery();
        while ($row = $resultSet->fetchAssociative()) {
            $workspaceArray[$row['uid']] = $row['uid'] . ': ' . $row['title'];
        }
        return $workspaceArray;
    }

    /**
     * If the user is in a workspace different than LIVE,
     * we force to show only log entries from the selected workspace,
     * and the workspace selector is not shown.
     */
    protected function forceWorkspaceSelectionIfInWorkspace(Constraint $constraint): bool
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')) {
            return false;
        }

        if ($this->getBackendUser()->workspace !== 0) {
            $constraint->setWorkspaceUid($this->getBackendUser()->workspace);
            return false;
        }
        return true;
    }

    /**
     * Create options for the 'depth of page levels' selector.
     *
     * @return array Key is depth identifier (1 = One level), value the localized select option label
     */
    protected function createPageDepthOptions(): array
    {
        return [
            0 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
            1 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
            2 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
            3 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
            4 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
            999 => LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
        ];
    }

    /**
     * Calculate the start- and end timestamp
     */
    protected function setStartAndEndTimeFromTimeSelector(Constraint $constraint): void
    {
        $startTime = $constraint->getManualDateStart() ? $constraint->getManualDateStart()->getTimestamp() : 0;
        $endTime = $constraint->getManualDateStop() ? $constraint->getManualDateStop()->getTimestamp() : 0;
        if ($endTime <= $startTime) {
            $endTime = $GLOBALS['EXEC_TIME'];
        }
        $constraint->setStartTimestamp($startTime);
        $constraint->setEndTimestamp($endTime);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
