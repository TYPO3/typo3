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

namespace TYPO3\CMS\Scheduler\Tests\Functional\Task;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExecuteSchedulableCommandAdditionalFieldProviderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['scheduler'];

    #[Test]
    public function argumentsAndOptionsWithSameNameAreAdded(): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        // Create a fake command and register it.
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
        $mockCommandRegistry = $this->getMockBuilder(CommandRegistry::class)->disableOriginalConstructor()->getMock();
        $mockCommandRegistry->method('getSchedulableCommands')->willReturn(
            (static function () use ($command) { yield $command->getName() => $command; })()
        );
        GeneralUtility::setSingletonInstance(CommandRegistry::class, $mockCommandRegistry);

        $task = new ExecuteSchedulableCommandTask();
        $task->setCommandIdentifier('some:test:command');
        $task->setDescription('Some test command');
        $task->setArguments(['action' => '']);
        $task->setOptions(['action' => false]);

        $subject = new ExecuteSchedulableCommandAdditionalFieldProvider();
        $taskInfo = [];
        $fields = $subject->getAdditionalFields(
            $taskInfo,
            $task,
            $this->get(SchedulerModuleController::class)
        );

        self::assertCount(2, $fields);
        self::assertArrayHasKey('arguments_action', $fields);
        self::assertArrayHasKey('options_action', $fields);
    }
}
