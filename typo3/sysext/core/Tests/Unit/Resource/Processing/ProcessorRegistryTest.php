<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Processing;

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

use TYPO3\CMS\Core\Resource\Processing\AbstractTask;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Processing\ProcessorRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class ProcessorRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getProcessorWhenOnlyOneIsRegistered()
    {
        $subject = $this->getAccessibleMockForAbstractClass(
            ProcessorRegistry::class,
            [],
            '',
            false
        );
        $subject->_set('registeredProcessors', [
            [
                'className' => LocalImageProcessor::class,
            ]
        ]);
        $taskMock = $this->getAccessibleMockForAbstractClass(
            AbstractTask::class,
            [],
            '',
            false,
            false,
            false,
            ['getType', 'getName']
        );
        $taskMock->expects(self::once())
            ->method('getType')
            ->willReturn('Image');
        $taskMock->expects(self::once())
            ->method('getName')
            ->willReturn('CropScaleMask');

        $processor = $subject->getProcessorByTask($taskMock);

        self::assertInstanceOf(LocalImageProcessor::class, $processor);
    }

    /**
     * @test
     */
    public function getProcessorWhenNoneIsRegistered()
    {
        $this->expectExceptionCode(1560876294);

        $subject = $this->getAccessibleMockForAbstractClass(
            ProcessorRegistry::class,
            [],
            '',
            false,
            false,
            false
        );
        $taskMock = $this->getAccessibleMockForAbstractClass(
            AbstractTask::class,
            [],
            '',
            false,
            false,
            false
        );

        $subject->getProcessorByTask($taskMock);
    }

    /**
     * @test
     */
    public function getProcessorWhenSameProcessorIsRegisteredTwice()
    {
        $subject = $this->getAccessibleMockForAbstractClass(
            ProcessorRegistry::class,
            [],
            '',
            false
        );
        $subject->_set('registeredProcessors', [
            'LocalImageProcessor' => [
                'className' => LocalImageProcessor::class,
            ],
            'AnotherLocalImageProcessor' => [
                'className' => LocalImageProcessor::class,
                'after' => 'LocalImageProcessor',
            ],
        ]);
        $taskMock = $this->getAccessibleMockForAbstractClass(
            AbstractTask::class,
            [],
            '',
            false,
            false,
            false,
            ['getType', 'getName']
        );
        $taskMock->expects(self::once())
            ->method('getType')
            ->willReturn('Image');
        $taskMock->expects(self::once())
            ->method('getName')
            ->willReturn('CropScaleMask');

        $processor = $subject->getProcessorByTask($taskMock);

        self::assertInstanceOf(LocalImageProcessor::class, $processor);
    }
}
