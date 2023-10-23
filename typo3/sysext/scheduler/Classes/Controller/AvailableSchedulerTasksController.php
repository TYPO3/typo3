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
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Backend\Attribute\Controller as BackendController;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Scheduler\Service\TaskService;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

/**
 * Render information about available tasks and commands.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[BackendController]
final class AvailableSchedulerTasksController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly CommandRegistry $commandRegistry,
        private readonly TaskService $taskService,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();

        $tasks = $this->taskService->getAvailableTaskTypes();
        $commands = $this->getRegisteredCommands($tasks);

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle(
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.info')
        );
        $view->makeDocHeaderModuleMenu();
        $this->addDocHeaderShortcutButton($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.info'));
        $view->assignMultiple([
            'dateFormat' => [
                'day' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?? 'd-m-y',
                'time' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] ?? 'H:i',
            ],
            'registeredClasses' => $tasks,
            'commands' => $commands,
        ]);
        return $view->renderResponse('InfoScreen');
    }

    private function addDocHeaderShortcutButton(ModuleTemplate $moduleTemplate, string $name): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('scheduler_availabletasks')
            ->setDisplayName($name);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * If the "command" task is registered, create a list of available commands to be rendered.
     *
     * @return Command[]
     */
    private function getRegisteredCommands(array $tasks): array
    {
        $commands = [];
        if (array_key_exists(ExecuteSchedulableCommandTask::class, $tasks)) {
            foreach ($this->commandRegistry->getSchedulableCommands() as $commandIdentifier => $command) {
                $commands[$commandIdentifier] = $command;
            }
            ksort($commands);
        }
        return $commands;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
