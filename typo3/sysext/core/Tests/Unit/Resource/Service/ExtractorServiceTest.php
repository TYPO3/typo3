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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Service;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Service\ExtractorService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ExtractorServiceTest
 */
class ExtractorServiceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function isFileTypeSupportedByExtractorReturnsFalseForFileTypeTextAndExtractorLimitedToFileTypeImage(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->expects(self::any())->method('getType')->willReturn(File::FILETYPE_TEXT);

        $extractorMock = $this->createMock(ExtractorInterface::class);
        $extractorMock->expects(self::any())->method('getFileTypeRestrictions')->willReturn([File::FILETYPE_IMAGE]);

        $extractorService = new ExtractorService();
        $method = new \ReflectionMethod($extractorService, 'isFileTypeSupportedByExtractor');
        $method->setAccessible(true);
        $arguments = [
            $fileMock,
            $extractorMock
        ];

        $result = $method->invokeArgs($extractorService, $arguments);
        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function isFileTypeSupportedByExtractorReturnsTrueForFileTypeImageAndExtractorLimitedToFileTypeImage(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->expects(self::any())->method('getType')->willReturn(File::FILETYPE_IMAGE);

        $extractorMock = $this->createMock(ExtractorInterface::class);
        $extractorMock->expects(self::any())->method('getFileTypeRestrictions')->willReturn([File::FILETYPE_IMAGE]);

        $extractorService = new ExtractorService();
        $method = new \ReflectionMethod($extractorService, 'isFileTypeSupportedByExtractor');
        $method->setAccessible(true);
        $arguments = [
            $fileMock,
            $extractorMock
        ];

        $result = $method->invokeArgs($extractorService, $arguments);
        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function isFileTypeSupportedByExtractorReturnsTrueForFileTypeTextAndExtractorHasNoFileTypeLimitation(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->expects(self::any())->method('getType')->willReturn(File::FILETYPE_TEXT);

        $extractorMock = $this->createMock(ExtractorInterface::class);
        $extractorMock->expects(self::any())->method('getFileTypeRestrictions')->willReturn([]);

        $extractorService = new ExtractorService();
        $method = new \ReflectionMethod($extractorService, 'isFileTypeSupportedByExtractor');
        $method->setAccessible(true);
        $arguments = [
            $fileMock,
            $extractorMock
        ];

        $result = $method->invokeArgs($extractorService, $arguments);
        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function extractMetaDataComposesDataByAvailableExtractors(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getDriverType')->willReturn('Local');

        /** @var ExtractorService|\PHPUnit\Framework\MockObject\MockObject $subject */
        $subject = $this->getMockBuilder(ExtractorService::class)
            ->setMethods(['getExtractorRegistry'])
            ->getMock()
        ;

        $fileMock = $this->createMock(File::class);
        $fileMock->expects(self::any())->method('getUid')->willReturn(4711);
        $fileMock->expects(self::any())->method('getType')->willReturn(File::FILETYPE_IMAGE);
        $fileMock->expects(self::any())->method('getStorage')->willReturn($storageMock);

        $extractorClass1 = md5('1');
        $extractorObject1 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass1)
            ->getMock();

        $extractorObject1->expects(self::any())->method('getPriority')->willReturn(10);
        $extractorObject1->expects(self::any())->method('getExecutionPriority')->willReturn(10);
        $extractorObject1->expects(self::any())->method('canProcess')->willReturn(true);
        $extractorObject1->expects(self::any())->method('getFileTypeRestrictions')->willReturn([File::FILETYPE_IMAGE]);
        $extractorObject1->expects(self::any())->method('getDriverRestrictions')->willReturn([$storageMock->getDriverType()]);
        $extractorObject1->expects(self::any())->method('extractMetaData')->with($fileMock)->willReturn([
            'width' => 800,
            'height' => 600,
        ]);

        $extractorClass2 = md5('2');
        $extractorObject2 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass2)
            ->getMock();

        $extractorObject2->expects(self::any())->method('getPriority')->willReturn(20);
        $extractorObject2->expects(self::any())->method('getExecutionPriority')->willReturn(20);
        $extractorObject2->expects(self::any())->method('canProcess')->willReturn(true);
        $extractorObject2->expects(self::any())->method('getFileTypeRestrictions')->willReturn([File::FILETYPE_IMAGE]);
        $extractorObject2->expects(self::any())->method('getDriverRestrictions')->willReturn([$storageMock->getDriverType()]);
        $extractorObject2->expects(self::any())->method('extractMetaData')->with($fileMock)->willReturn([
            'keywords' => 'typo3, cms',
        ]);

        /** @var ExtractorRegistry|\PHPUnit\Framework\MockObject\MockObject $extractorRegistryMock */
        $extractorRegistryMock = $this->getMockBuilder(ExtractorRegistry::class)
            ->setMethods(['createExtractorInstance'])
            ->getMock();

        $extractorRegistryMock->expects(self::any())->method('createExtractorInstance')->willReturnMap(
            [
                [$extractorClass1, $extractorObject1],
                [$extractorClass2, $extractorObject2]
            ]
        );
        $extractorRegistryMock->registerExtractionService($extractorClass1);
        $extractorRegistryMock->registerExtractionService($extractorClass2);

        $subject->expects(self::any())->method('getExtractorRegistry')->willReturn($extractorRegistryMock);

        self::assertSame(['width' => 800, 'height' => 600, 'keywords' => 'typo3, cms'], $subject->extractMetaData($fileMock));
    }
}
