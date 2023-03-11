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

namespace TYPO3\CMS\Scheduler\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Scheduler;

/**
 * CLI command for EXT:scheduler to execute tasks
 */
class SchedulerExecuteCommand extends Command
{
    protected SymfonyStyle $io;

    public function __construct(
        protected readonly Context $context,
        protected readonly SchedulerTaskRepository $taskRepository,
        protected Scheduler $scheduler,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this
            ->addOption(
                'task',
                't',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Execute tasks by given id. To run all tasks of a group prefix the group id with "g:", e.g. "g:1"',
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();
        $this->io = new SymfonyStyle($input, $output);

        if (count($input->getOption('task')) > 0) {
            $taskGroups = $this->taskRepository->getGroupedTasks()['taskGroupsWithTasks'];
            $tasksToRun = $this->getTasksToRun($taskGroups, $input->getOption('task'));
            $this->runTasks($tasksToRun, $taskGroups);

            return Command::SUCCESS;
        }

        $this->askForTasksAndRun($input, $output);
        return Command::SUCCESS;
    }

    private function askForTasksAndRun(InputInterface $input, OutputInterface $output): void
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $taskGroups = $this->taskRepository->getGroupedTasks()['taskGroupsWithTasks'];
        $selectableTasks = $this->getSelectableTasks($taskGroups);
        if ($selectableTasks === []) {
            $this->io->note('No tasks available.');
            return;
        }

        $tasksToRunQuestion = new ChoiceQuestion('Run tasks (comma seperated list): ', $selectableTasks);
        $tasksToRunQuestion->setAutocompleterValues(array_keys($selectableTasks));
        $tasksToRunQuestion->setMultiselect(true);
        $tasksToRun = $questionHelper->ask($input, $output, $tasksToRunQuestion);

        $this->runTasks($tasksToRun, $taskGroups);
    }

    private function runTasks($selectedTasks, $taskGroups): void
    {
        $taskUids = $this->getTaskUidsFromSelection($selectedTasks, $taskGroups);
        ksort($taskUids);
        $numLength = strlen((string)array_reverse($taskUids)[0]);

        foreach ($taskUids as $taskUid) {
            try {
                $uid = (int)$taskUid;
                $task = $this->taskRepository->findByUid($uid);
                $additionalInformation = $task->getAdditionalInformation() === '' ? '' : ' (' . $task->getAdditionalInformation() . ')';
                $space = str_repeat(' ', $numLength - strlen((string)$task->getTaskUid()));
                $this->io->writeln('[ <fg=green>TASK:' . $task->getTaskUid() . $space . '</> ] Running "' . $task->getTaskTitle() . $additionalInformation . '"');
                $this->scheduler->executeTask($task);
            } catch (\Throwable $exception) {
                $this->io->writeln($exception->getMessage());
            }
        }
    }

    private function getTaskUidsFromSelection(array $list, array $groups): array
    {
        $taskUids = [];
        foreach ($list as $uid) {
            [$keyword, $group] = [...explode(':', (string)$uid), null];
            if ($keyword === 'g') {
                if (!array_key_exists($group, $groups)) {
                    throw new \InvalidArgumentException('Group with id "' . $group . '" does not exist.', 1679683415);
                }
                $taskUidsInGroup = array_column($groups[$group]['tasks'], 'uid');
                $taskUids += $taskUidsInGroup;
            } else {
                $taskUids[] = $uid;
            }
        }
        return $taskUids;
    }

    private function getLanguageService(): LanguageService
    {
        return GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
    }

    private function getTasksToRun(array $taskGroups, array $taskList): array
    {
        $taskUids = array_unique($this->getTaskUidsFromSelection($taskList, $taskGroups));
        foreach ($taskUids as $taskUid) {
            // This will throw an exception if the task uid was not found and print it to the console.
            $this->taskRepository->findByUid((int)$taskUid);
        }
        return $taskUids;
    }

    protected function getSelectableTasks(mixed $taskGroups): array
    {
        $selectableTasks = [];
        foreach ($taskGroups as $uid => $group) {
            $groupLabel = ($group['groupName'] ?? $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.noGroup'));
            $selectableTasks['g:' . $uid] = '<fg=yellow>' . $groupLabel . '</>';

            foreach ($group['tasks'] as $task) {
                $additionalInformation = $task['additionalInformation'] === '' ? '' : '(' . $task['additionalInformation'] . ')';
                $selectableTasks[$task['uid']] = $task['classTitle'] . $additionalInformation;
            }
        }
        return $selectableTasks;
    }
}
