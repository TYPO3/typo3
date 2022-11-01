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

use TYPO3\CMS\Core\Resource\Processing\AbstractTask;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Processing\ProcessorRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ProcessorRegistryTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @test
     */
    public function getProcessorWhenOnlyOneIsRegistered(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors'] = [
            [
                'className' => LocalImageProcessor::class,
            ],
        ];
        $subject = new ProcessorRegistry();
        $taskMock = $this->createMock(AbstractTask::class);
        $taskMock->method('getType')->willReturn('Image');
        $taskMock->method('getName')->willReturn('CropScaleMask');

        $processor = $subject->getProcessorByTask($taskMock);

        self::assertInstanceOf(LocalImageProcessor::class, $processor);
    }

    /**
     * @test
     */
    public function getProcessorWhenNoneIsRegistered(): void
    {
        $this->expectExceptionCode(1560876294);

        $subject = new ProcessorRegistry();
        $taskMock = $this->createMock(AbstractTask::class);
        $subject->getProcessorByTask($taskMock);
    }

    /**
     * @test
     */
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
        $subject = new ProcessorRegistry();
        $taskMock = $this->createMock(AbstractTask::class);
        $taskMock->method('getType')->willReturn('Image');
        $taskMock->method('getName')->willReturn('CropScaleMask');

        $processor = $subject->getProcessorByTask($taskMock);

        self::assertInstanceOf(LocalImageProcessor::class, $processor);
    }
}
