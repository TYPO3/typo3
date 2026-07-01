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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Task\TaskStatus;

/**
 * CLI command for EXT:scheduler to list tasks
 */
#[AsCommand('scheduler:list', 'List all TYPO3 Scheduler tasks.')]
#[AsNonSchedulableCommand]
class SchedulerListCommand extends Command
{
    protected SymfonyStyle $io;

    public function __construct(
        protected readonly SchedulerTaskRepository $taskRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'group',
                'g',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Show only groups with given uid',
            )
            ->addOption(
                'watch',
                'w',
                InputOption::VALUE_OPTIONAL,
                'Start watcher mode (polling)',
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \InvalidArgumentException('This command accepts only an instance of "ConsoleOutputInterface".', 1678645754);
        }

        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();
        $this->io = new SymfonyStyle($input, $output);
        $languageService = $this->getLanguageService();

        $tableHeader = [
            $languageService->sL('scheduler.messages:label.id'),
            $languageService->sL('scheduler.messages:task'),
            $languageService->sL('scheduler.messages:label.description'),
            $languageService->sL('scheduler.messages:label.frequency'),
            $languageService->sL('scheduler.messages:status'),
        ];

        $tableSection = $output->section();
        $tableBuffer = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
        $table = new Table($tableBuffer);
        $table->setHeaders($tableHeader);
        $showGroups = $input->getOption('group');
        $rows = $this->getTableRows($showGroups);
        $table->setRows($rows);
        $table->setColumnMaxWidth(1, 50);
        $table->setColumnMaxWidth(2, 50);
        $table->setColumnMaxWidth(4, 50);
        $table->render();

        $bufferedData = $tableBuffer->fetch();
        $tableSection->overwrite($bufferedData);

        $doWatch = $input->hasParameterOption('--watch') || $input->hasParameterOption('-w');
        if ($doWatch) {
            $infoSection = $output->section();
            $interval = (int)($input->getOption('watch') ?: 1);
            $infoSection->write('Watching tasks every ' . $interval . ' seconds, press CTRL+C to stop watching');

            while (true) { // @phpstan-ignore while.alwaysTrue (intentional infinite loop for CLI watch mode, terminated by CTRL+C signal)
                sleep($interval);
                $this->updateTable($input, $table, $tableBuffer, $tableSection);
            }
        }

        return Command::SUCCESS;
    }

    private function getTableRows(array $groups = []): array
    {
        $tasks = $this->taskRepository->getGroupedTasks();
        $languageService = $this->getLanguageService();

        $rows = [];
        foreach ($tasks['taskGroupsWithTasks'] as $uid => $group) {
            if (!in_array($uid, $groups) && count($groups) > 0) {
                continue;
            }

            // Flag as disabled group
            $groupDisabledLabel = $group['hidden'] ? '<fg=yellow>' . $this->getLanguageService()->sL('scheduler.messages:status.disabled') . '</>' : '';
            $groupLabel = ($group['groupName'] ?? $languageService->sL('scheduler.messages:label.noGroup')) . ' (id:' . $uid . ') ' . $groupDisabledLabel;

            $rows[] = [new TableSeparator(['colspan' => 5])];
            $rows[] = [new TableCell('<options=bold>' . $groupLabel . '</>', ['colspan' => 5])];
            $rows[] = [new TableSeparator(['colspan' => 5])];

            foreach ($group['tasks'] as $task) {
                $progress = $task['progress'] ?? false;
                $taskStatus = [];

                /** @var TaskStatus $status */
                foreach ($task['statuses'] as $status) {
                    $color = match ($status->severity) {
                        ContextualFeedbackSeverity::OK => 'green',
                        ContextualFeedbackSeverity::INFO => 'blue',
                        ContextualFeedbackSeverity::WARNING => 'yellow',
                        ContextualFeedbackSeverity::ERROR => 'red',
                        ContextualFeedbackSeverity::NOTICE => 'gray',
                    };
                    $label = $languageService->sL($status->label);
                    if ($status->type === 'running' && $progress) {
                        $label .= ' (' . $progress . ')';
                    }
                    // The console has no tooltip, so the more detailed message
                    // (e.g. the failure reason) is shown inline instead.
                    if ($status->message !== '') {
                        $label .= ' (' . vsprintf($languageService->sL($status->message), $status->messageArguments) . ')';
                    }
                    $taskStatus[] = '<fg=' . $color . '>' . $label . '</>';
                }

                $taskTitle = $task['fullTitle'] . (empty($task['additionalInformation']) ? '' : ' (' . $task['additionalInformation'] . ')');
                $rows[] = [
                    $task['uid'],
                    $taskTitle,
                    $task['description'],
                    $task['frequency'],
                    implode(', ', $taskStatus),
                ];
            }
        }

        return $rows;
    }

    private function getLanguageService(): LanguageService
    {
        return GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('en');
    }

    protected function updateTable(InputInterface $input, Table $table, BufferedOutput $buffer, ConsoleSectionOutput $tableSection): void
    {
        $tableSection->overwrite('');
        $rows = $this->getTableRows($input->getOption('group'));
        $table->setRows($rows)->render();
        $bufferedData = $buffer->fetch();
        $tableSection->overwrite($bufferedData);
    }
}
