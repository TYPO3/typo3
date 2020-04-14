<?php

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper
 */
class LocalPreviewHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function processProvidesDefaultSizeIfNotConfigured()
    {
        $file = $this->createMock(File::class);
        // Use size slightly larger than default size to ensure processing
        $file->expects(self::any())->method('getProperty')->willReturnMap([
            ['width', 65],
            ['height', 65],
        ]);

        $task = $this->createMock(TaskInterface::class);
        $task->expects(self::once())->method('getSourceFile')->willReturn($file);
        $task->expects(self::once())->method('getConfiguration')->willReturn([]);

        $localPreviewHelper = $this->getMockBuilder(LocalPreviewHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTemporaryFilePath', 'generatePreviewFromFile'])
            ->getMock();
        $localPreviewHelper->expects(self::once())->method('getTemporaryFilePath')->willReturn('test/file');
        // Assert that by default 64x64 is used as preview size
        $localPreviewHelper->expects(self::once())->method('generatePreviewFromFile')
            ->with($file, ['width' => 64, 'height' => 64], 'test/file');

        $localPreviewHelper->process($task);
    }

    /**
     * @test
     */
    public function processDoesNotScaleUpImages()
    {
        $file = $this->createMock(File::class);
        $file->expects(self::any())->method('getProperty')->willReturnMap([
            ['width', 20],
            ['height', 20],
        ]);

        $localPreviewHelper = $this->getMockBuilder(LocalPreviewHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['dummy'])
            ->getMock();

        $task = $this->createMock(TaskInterface::class);
        $task->expects(self::once())->method('getSourceFile')->willReturn($file);
        $task->expects(self::once())->method('getConfiguration')->willReturn(['width' => 30, 'height' => 30]);

        self::assertNull($localPreviewHelper->process($task));
    }

    /**
     * @test
     */
    public function processGeneratesPreviewEvenIfSourceFileHasNoSize()
    {
        $file = $this->createMock(File::class);
        $file->expects(self::any())->method('getProperty')->willReturnMap([
            ['width', 0],
            ['height', 0],
        ]);

        $task = $this->createMock(TaskInterface::class);
        $task->expects(self::once())->method('getSourceFile')->willReturn($file);
        $task->expects(self::once())->method('getConfiguration')->willReturn([]);

        $localPreviewHelper = $this->getMockBuilder(LocalPreviewHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTemporaryFilePath', 'generatePreviewFromFile'])
            ->getMock();
        $expectedResult = ['width' => 20, 'height' => 20, 'filePath' => 'test/file'];
        $localPreviewHelper->expects(self::once())->method('generatePreviewFromFile')->willReturn($expectedResult);

        self::assertEquals($expectedResult, $localPreviewHelper->process($task));
    }
}
