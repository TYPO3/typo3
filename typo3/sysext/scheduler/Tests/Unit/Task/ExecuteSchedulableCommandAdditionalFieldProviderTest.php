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

namespace TYPO3\CMS\Scheduler\Tests\Unit\Task;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExecuteSchedulableCommandAdditionalFieldProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function argumentsAndOptionsWithSameNameAreAdded(): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL(Argument::cetera())->willReturn('');
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $mockScheduler = $this->getAccessibleMock(Scheduler::class, ['saveTask'], [], '', false);
        GeneralUtility::setSingletonInstance(Scheduler::class, $mockScheduler);
        $mockScheduler->method('saveTask')->willReturn(false);
        GeneralUtility::addInstance(Execution::class, $this->prophesize(Execution::class)->reveal());

        $command = new class () extends Command {
            protected function configure(): void
            {
                $this
                    ->setName('some:test:command')
                    ->setDescription('Some test command')
                    ->addArgument('action')
                    ->addOption('action');
            }
        };

        $commandRegistryProphecy = $this->prophesize(CommandRegistry::class);
        $commandRegistryProphecy->getSchedulableCommands()->willReturn(
            (static function () use ($command) { yield $command->getName() => $command; })()
        );
        GeneralUtility::setSingletonInstance(CommandRegistry::class, $commandRegistryProphecy->reveal());

        $task = GeneralUtility::makeInstance(ExecuteSchedulableCommandTask::class);
        $task->setCommandIdentifier('some:test:command');
        $task->setDescription('Some test command');
        $task->setArguments(['action' => '']);
        $task->setOptions(['action' => false]);

        $subject = new ExecuteSchedulableCommandAdditionalFieldProvider();
        $taskInfo = [];
        $fields = $subject->getAdditionalFields(
            $taskInfo,
            $task,
            $this->prophesize(SchedulerModuleController::class)->reveal()
        );

        self::assertCount(4, $fields);
        self::assertTrue(isset($fields['schedulableCommands'], $fields['description'], $fields['arguments_action'], $fields['options_action']));
    }
}
