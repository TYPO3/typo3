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

namespace TYPO3\CMS\Scheduler\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController as BackendController;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\SchedulerManagementAction;
use TYPO3\CMS\Scheduler\Service\TaskService;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;

/**
 * Scheduler backend module.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[BackendController]
final class SchedulerModuleController
{
    protected SchedulerManagementAction $currentAction;

    public function __construct(
        protected readonly Scheduler $scheduler,
        protected readonly TaskSerializer $taskSerializer,
        protected readonly SchedulerTaskRepository $taskRepository,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly Context $context,
        protected readonly TaskService $taskService,
        protected readonly PageRenderer $pageRenderer,
    ) {}

    /**
     * Entry dispatcher method.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();

        $view = $this->moduleTemplateFactory->create($request);
        $view->assign('dateFormat', [
            'day' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?? 'd-m-y',
            'time' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] ?? 'H:i',
        ]);

        $moduleData = $request->getAttribute('moduleData');

        // Simple actions from list view.
        if (!empty($parsedBody['action']['toggleHidden'])) {
            $this->toggleDisabledFlag($view, (int)$parsedBody['action']['toggleHidden']);
        } elseif (!empty($parsedBody['action']['stop'])) {
            $this->stopTask($view, (int)$parsedBody['action']['stop']);
        } elseif (!empty($parsedBody['action']['execute'])) {
            $this->executeTasks($view, (string)$parsedBody['action']['execute']);
        } elseif (!empty($parsedBody['action']['scheduleCron'])) {
            $this->scheduleCrons($view, (string)$parsedBody['action']['scheduleCron']);
        } elseif (!empty($parsedBody['action']['group']['uid'])) {
            $this->groupDisable((int)$parsedBody['action']['group']['uid'], (int)($parsedBody['action']['group']['hidden'] ?? 0));
        } elseif (!empty($parsedBody['action']['delete'])) {
            $this->deleteTask($view, (int)$parsedBody['action']['delete']);
        } elseif (!empty($parsedBody['action']['groupRemove'])) {
            $rows = $this->groupRemove((int)$parsedBody['action']['groupRemove']);
            if ($rows > 0) {
                $view->addFlashMessage($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.group.deleted'));
            } else {
                $view->addFlashMessage($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.group.delete.failed'), '', ContextualFeedbackSeverity::WARNING);
            }
        }
        return $this->renderListTasksView($view, $moduleData, $request);
    }

    /**
     * This is (unfortunately) used by additional field providers to distinct between "create new task" and "edit task".
     */
    public function getCurrentAction(): SchedulerManagementAction
    {
        return $this->currentAction;
    }

    /**
     * This is (unfortunately) needed so getCurrentAction() used by additional field providers - it is required
     * to distinct between "create new task" and "edit task".
     */
    public function setCurrentAction(SchedulerManagementAction $currentAction): void
    {
        $this->currentAction = $currentAction;
    }

    /**
     * Mark a task as deleted.
     */
    protected function deleteTask(ModuleTemplate $view, int $taskUid): void
    {
        $languageService = $this->getLanguageService();
        if ($taskUid <= 0) {
            throw new \RuntimeException('Expecting a valid task uid', 1641670374);
        }
        try {
            // Try to fetch the task and delete it
            $task = $this->taskRepository->findByUid($taskUid);
            if ($this->taskRepository->isTaskMarkedAsRunning($task)) {
                // If the task is currently running, it may not be deleted
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.canNotDeleteRunningTask'), ContextualFeedbackSeverity::ERROR);
            } else {
                if ($this->taskRepository->remove($task)) {
                    $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.deleteSuccess'));
                } else {
                    $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.deleteError'));
                }
            }
        } catch (\UnexpectedValueException) {
            // The task could not be unserialized, simply update the database record setting it to deleted
            $result = $this->taskRepository->remove($taskUid);
            if ($result) {
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.deleteSuccess'));
            } else {
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.deleteError'), ContextualFeedbackSeverity::ERROR);
            }
        } catch (\OutOfBoundsException) {
            // The task was not found, for some reason
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $taskUid), ContextualFeedbackSeverity::ERROR);
        }
    }

    /**
     * Clears the registered running executions from the task.
     * Note this doesn't actually stop the running script. It just unmarks execution.
     * @todo find a way to really kill the running task.
     */
    protected function stopTask(ModuleTemplate $view, int $taskUid): void
    {
        $languageService = $this->getLanguageService();
        if ($taskUid <= 0) {
            throw new \RuntimeException('Expecting a valid task uid', 1641670375);
        }
        try {
            // Try to fetch the task and stop it
            $task = $this->taskRepository->findByUid($taskUid);
            if ($this->taskRepository->isTaskMarkedAsRunning($task)) {
                // If the task is indeed currently running, clear marked executions
                $result = $this->taskRepository->removeAllRegisteredExecutionsForTask($task);
                if ($result) {
                    $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.stopSuccess'));
                } else {
                    $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.stopError'), ContextualFeedbackSeverity::ERROR);
                }
            } else {
                // The task is not running, nothing to unmark
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.maynotStopNonRunningTask'), ContextualFeedbackSeverity::WARNING);
            }
        } catch (\OutOfBoundsException $e) {
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $taskUid), ContextualFeedbackSeverity::ERROR);
        } catch (\UnexpectedValueException $e) {
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.stopTaskFailed'), $taskUid, $e->getMessage()), ContextualFeedbackSeverity::ERROR);
        }
    }

    /**
     * Toggle the disabled state of a task and register for next execution if a task is of type "single execution".
     */
    protected function toggleDisabledFlag(ModuleTemplate $view, int $taskUid): void
    {
        $languageService = $this->getLanguageService();
        if ($taskUid <= 0) {
            throw new \RuntimeException('Expecting a valid task uid to toggle disabled state', 1641670373);
        }
        try {
            $task = $this->taskRepository->findByUid($taskUid);
            // Toggle the task state and add a flash message
            $taskName = $this->taskService->getHumanReadableTaskName($task);
            $isTaskDisabled = $task->isDisabled();
            // If a disabled single task is enabled again, register it for a single execution at next scheduler run.
            if ($isTaskDisabled && $task->getExecution()->isSingleRun()) {
                $task->setDisabled(false);
                $task->setRunOnNextCronJob(true);
                $execution = Execution::createSingleExecution($this->context->getAspect('date')->get('timestamp'));
                $task->setExecution($execution);
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskEnabledAndQueuedForExecution'), $taskName, $taskUid));
            } elseif ($isTaskDisabled) {
                $task->setDisabled(false);
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskEnabled'), $taskName, $taskUid));
            } else {
                $task->setDisabled(true);
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskDisabled'), $taskName, $taskUid));
            }
            $this->taskRepository->updateExecution($task);
        } catch (\OutOfBoundsException) {
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $taskUid), ContextualFeedbackSeverity::ERROR);
        } catch (\UnexpectedValueException $e) {
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.toggleDisableFailed'), $taskUid, $e->getMessage()), ContextualFeedbackSeverity::ERROR);
        }
    }

    /**
     * Execute a list of tasks.
     */
    protected function executeTasks(ModuleTemplate $view, string $taskUids): void
    {
        $taskUids = GeneralUtility::intExplode(',', $taskUids, true);
        if (empty($taskUids)) {
            throw new \RuntimeException('Expecting a list of task uids to execute', 1641715832);
        }
        // Loop selected tasks and execute.
        $languageService = $this->getLanguageService();
        foreach ($taskUids as $uid) {
            try {
                $task = $this->taskRepository->findByUid($uid);
                $name = $this->taskService->getHumanReadableTaskName($task);
                // Try to execute it and report result
                $result = $this->scheduler->executeTask($task);
                if ($result) {
                    $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.executed'), $name, $uid));
                } else {
                    $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.notExecuted'), $name, $uid), ContextualFeedbackSeverity::ERROR);
                }
                $this->scheduler->recordLastRun('manual');
            } catch (\OutOfBoundsException $e) {
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $uid), ContextualFeedbackSeverity::ERROR);
            } catch (\Exception $e) {
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.executionFailed'), $uid, $e->getMessage()), ContextualFeedbackSeverity::ERROR);
            }
        }
    }

    /**
     * Schedule selected tasks to be executed on next cron run
     */
    protected function scheduleCrons(ModuleTemplate $view, string $taskUids): void
    {
        $taskUids = GeneralUtility::intExplode(',', $taskUids, true);
        if (empty($taskUids)) {
            throw new \RuntimeException('Expecting a list of task uids to schedule', 1641715833);
        }
        // Loop selected tasks and register for next cron run.
        $languageService = $this->getLanguageService();
        foreach ($taskUids as $uid) {
            try {
                $task = $this->taskRepository->findByUid($uid);
                $name = $this->taskService->getHumanReadableTaskName($task);
                $task->setRunOnNextCronJob(true);
                if ($task->isDisabled()) {
                    $task->setDisabled(false);
                    $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskEnabledAndQueuedForExecution'), $name, $uid));
                } else {
                    $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskQueuedForExecution'), $name, $uid));
                }
                $this->taskRepository->updateExecution($task);
            } catch (\OutOfBoundsException $e) {
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $uid), ContextualFeedbackSeverity::ERROR);
            } catch (\UnexpectedValueException $e) {
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.schedulingFailed'), $uid, $e->getMessage()), ContextualFeedbackSeverity::ERROR);
            }
        }
    }

    /**
     * Assemble a listing of scheduled tasks
     */
    protected function renderListTasksView(ModuleTemplate $view, ModuleData $moduleData, ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $data = $this->taskRepository->getGroupedTasks();
        $hasAvailableTaskTypes = $this->taskService->getAllTaskTypes() !== [];

        $groups = $data['taskGroupsWithTasks'] ?? [];
        $groups = array_map(
            static fn(int $key, array $group): array => array_merge($group, ['taskGroupCollapsed' => (bool)($moduleData->get('task-group-' . $key, false))]),
            array_keys($groups),
            $groups
        );

        $this->pageRenderer->loadJavaScriptModule('@typo3/scheduler/new-scheduler-task-wizard-button.js');

        $view->assignMultiple([
            'groups' => $groups,
            'groupsWithoutTasks' => $this->getGroupsWithoutTasks($groups),
            'hasAvailableTaskTypes' => $hasAvailableTaskTypes,
            'now' => $this->context->getAspect('date')->get('timestamp'),
            'errorClasses' => $data['errorClasses'],
            'returnUrl' => $this->uriBuilder->buildUriFromRoute('scheduler_manage'),
            'errorClassesCollapsed' => (bool)($moduleData->get('task-group-missing', false)),
        ]);
        $view->setTitle(
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.scheduler')
        );
        $view->makeDocHeaderModuleMenu();
        $this->addDocHeaderReloadButton($view);
        if ($hasAvailableTaskTypes) {
            $addTaskUrl = (string)$this->uriBuilder->buildUriFromRoute('ajax_new_scheduler_task_wizard', [
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
            ]);
            $view->assign('addTaskUrl', $addTaskUrl);
            $this->addDocHeaderAddTaskButton($view, $addTaskUrl);
            $this->addDocHeaderAddTaskGroupButton($view);
        }
        $this->addDocHeaderShortcutButton($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.scheduler'));
        return $view->renderResponse('ListTasks');
    }

    protected function addDocHeaderReloadButton(ModuleTemplate $moduleTemplate): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $reloadButton = $buttonBar->makeLinkButton()
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', IconSize::SMALL))
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('scheduler_manage'));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);
    }

    protected function addDocHeaderAddTaskButton(ModuleTemplate $moduleTemplate, string $url): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $addButton = $buttonBar->makeFullyRenderedButton()->setHtmlSource(
            '<typo3-scheduler-new-task-wizard-button url="' . $url . '" subject="' . htmlspecialchars($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.add')) . '">'
            . $this->iconFactory->getIcon('actions-plus', IconSize::SMALL) . htmlspecialchars($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.add')) .
            '</typo3-scheduler-new-task-wizard-button>'
        );

        $buttonBar->addButton($addButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
    }

    private function addDocHeaderAddTaskGroupButton(ModuleTemplate $moduleTemplate): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $addButton = $buttonBar->makeInputButton()
            ->setTitle($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.group.add'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL))
            ->setName('createSchedulerGroup')
            ->setValue('1')
            ->setClasses('t3js-create-group');
        $buttonBar->addButton($addButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
    }

    protected function addDocHeaderShortcutButton(ModuleTemplate $moduleTemplate, string $name): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('scheduler_manage')
            ->setDisplayName($name);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Add a flash message to the flash message queue of this module.
     */
    protected function addMessage(ModuleTemplate $moduleTemplate, string $message, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK): void
    {
        $moduleTemplate->addFlashMessage($message, '', $severity);
    }

    private function getGroupsWithoutTasks(array $taskGroupsWithTasks): array
    {
        $uidGroupsWithTasks = array_filter(array_column($taskGroupsWithTasks, 'uid'));
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_scheduler_task_group');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $resultEmptyGroups = $queryBuilder->select('*')
            ->from('tx_scheduler_task_group')
            ->orderBy('groupName');

        // Only add where statement if we have taskGroups to consider.
        if (!empty($uidGroupsWithTasks)) {
            $resultEmptyGroups->where($queryBuilder->expr()->notIn('uid', $uidGroupsWithTasks));
        }

        return $resultEmptyGroups->executeQuery()->fetchAllAssociative();
    }

    private function groupRemove(int $groupId): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_scheduler_task_group');
        return $queryBuilder->update('tx_scheduler_task_group')
            ->where($queryBuilder->expr()->eq('uid', $groupId))
            ->set('deleted', 1)
            ->executeStatement();
    }

    private function groupDisable(int $groupId, int $hidden): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_scheduler_task_group');
        $queryBuilder->update('tx_scheduler_task_group')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($groupId)))
            ->set('hidden', $hidden)
            ->executeStatement();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
