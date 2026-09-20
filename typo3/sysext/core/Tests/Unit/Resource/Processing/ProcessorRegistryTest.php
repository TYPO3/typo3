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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Processing;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Processing\AbstractTask;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\ProcessorRegistry;
use TYPO3\CMS\Core\Resource\Processing\ResourceAwareProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[BackupGlobals(true)]
final class ProcessorRegistryTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private function getTaskStubForFalFile(): AbstractTask
    {
        $taskStub = self::createStub(AbstractTask::class);
        $taskStub->method('getType')->willReturn('Image');
        $taskStub->method('getName')->willReturn('CropScaleMask');
        $taskStub->method('getSourceFile')->willReturn(self::createStub(File::class));
        return $taskStub;
    }

    #[Test]
    public function getProcessorWhenOnlyOneIsRegistered(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors'] = [
            [
                'className' => LocalImageProcessor::class,
            ],
        ];
        $subject = new ProcessorRegistry(
            new DependencyOrderingService()
        );

        $processor = $subject->getProcessorByTask($this->getTaskStubForFalFile());

        self::assertInstanceOf(LocalImageProcessor::class, $processor);
    }

    #[Test]
    public function getProcessorWhenNoneIsRegistered(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors'] = [];
        $this->expectExceptionCode(1560876294);

        $subject = new ProcessorRegistry(
            new DependencyOrderingService()
        );
        $subject->getProcessorByTask($this->getTaskStubForFalFile());
    }

    #[Test]
    public function getProcessorWhenSameProcessorIsRegisteredTwice(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors'] = [
            'LocalImageProcessor' => [
                'className' => LocalImageProcessor::class,
            ],
            'AnotherLocalImageProcessor' => [
                'className' => LocalImageProcessor::class,
                'after' => 'LocalImageProcessor',
            ],
        ];
        $subject = new ProcessorRegistry(
            new DependencyOrderingService()
        );

        $processor = $subject->getProcessorByTask($this->getTaskStubForFalFile());

        self::assertInstanceOf(LocalImageProcessor::class, $processor);
    }

    #[Test]
    public function nonResourceAwareProcessorIsSkippedForANonFalSourceFile(): void
    {
        $nonAwareProcessor = new class implements ProcessorInterface {
            public function canProcessTask(TaskInterface $task): bool
            {
                return true;
            }

            public function processTask(TaskInterface $task): void {}
        };
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors'] = [
            [
                'className' => $nonAwareProcessor::class,
            ],
        ];
        $subject = new ProcessorRegistry(
            new DependencyOrderingService()
        );
        $taskStub = self::createStub(AbstractTask::class);
        $taskStub->method('getType')->willReturn('Image');
        $taskStub->method('getName')->willReturn('CropScaleMask');
        $taskStub->method('getSourceFile')->willReturn(self::createStub(SystemResourceInterface::class));

        $this->expectExceptionCode(1560876294);
        $subject->getProcessorByTask($taskStub);
    }

    #[Test]
    public function resourceAwareProcessorIsOfferedANonFalSourceFile(): void
    {
        $resourceAwareProcessor = new class implements ResourceAwareProcessorInterface {
            public function canProcessTask(TaskInterface $task): bool
            {
                return true;
            }

            public function processTask(TaskInterface $task): void {}
        };
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors'] = [
            [
                'className' => $resourceAwareProcessor::class,
            ],
        ];
        $subject = new ProcessorRegistry(
            new DependencyOrderingService()
        );
        $taskStub = self::createStub(AbstractTask::class);
        $taskStub->method('getSourceFile')->willReturn(self::createStub(SystemResourceInterface::class));

        $processor = $subject->getProcessorByTask($taskStub);

        self::assertSame($resourceAwareProcessor::class, $processor::class);
    }
}
