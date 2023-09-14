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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;

/**
 * CLI command for EXT:scheduler to list tasks
 */
class SchedulerListCommand extends Command
{
    protected SymfonyStyle $io;

    public function __construct(
        protected readonly Context $context,
        protected readonly SchedulerTaskRepository $taskRepository,
    ) {
        parent::__construct();
    }

    public function configure()
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
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.id'),
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:task'),
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.description'),
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.frequency'),
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:status'),
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
        $table->render();

        $bufferedData = $tableBuffer->fetch();
        $tableSection->overwrite($bufferedData);

        $doWatch = $input->hasParameterOption('--watch') || $input->hasParameterOption('-w');
        if ($doWatch) {
            $infoSection = $output->section();
            $interval = (int)($input->getOption('watch') ?: 1);
            $infoSection->write('Watching tasks every ' . $interval . ' seconds, press CTRL+C to stop watching');

            /** @phpstan-ignore-next-line  */
            while (true) {
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
            $groupDisabledLabel = $group['hidden'] ? '<fg=yellow>' . $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:status.disabled') . '</>' : '';
            $groupLabel = ($group['groupName'] ?? $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.noGroup')) . ' (id:' . $uid . ') ' . $groupDisabledLabel;

            $rows[] = [new TableSeparator(['colspan' => 5])];
            $rows[] = [new TableCell('<options=bold>' . $groupLabel . '</>', ['colspan' => 5])];
            $rows[] = [new TableSeparator(['colspan' => 5])];

            foreach ($group['tasks'] as $task) {
                $progress = $task['progress'] ?? false;
                $runningLabel = '<fg=green>' . $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:status.running') . ($progress ? ' (' . $progress . ')' : '') . '</>';
                $taskStatus = [];

                if ($task['isRunning']) {
                    $taskStatus[] =  $runningLabel;
                }

                $now = $this->context->getAspect('date')->get('timestamp');

                // Flag as late
                if ($task['nextExecution'] && $task['nextExecution'] < $now && !(int)$group['hidden'] && !(int)$task['disabled']) {
                    $taskStatus[] = '<fg=yellow>' . $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:status.late') . '</>';
                }

                // Flag as disabled
                if ($task['disabled'] && !$task['isRunning']) {
                    $taskStatus[] = '<fg=yellow>' . $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:status.disabled') . '</>';
                }

                // Flag as error
                if ($task['lastExecutionFailureMessage'] ?? false) {
                    $taskStatus[] = '<fg=red>' . $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:status.failure') . '</>';
                }

                // Flag as disabled by group
                if ($group['hidden'] && !$task['isRunning']) {
                    $taskStatus[] = '<fg=yellow>' . $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:status.disabledByGroup') . '</>';
                }

                $taskTitle = $task['classTitle'] . (empty($task['additionalInformation']) ? '' : ' (' . $task['additionalInformation'] . ')');
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
        return GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
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
