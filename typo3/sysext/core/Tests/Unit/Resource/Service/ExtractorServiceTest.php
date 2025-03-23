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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Service\ExtractorService;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtractorServiceTest extends UnitTestCase
{
    #[Test]
    public function isFileTypeSupportedByExtractorReturnsFalseForFileTypeTextAndExtractorLimitedToFileTypeImage(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->method('getType')->willReturn(FileType::TEXT);

        $extractorMock = $this->createMock(ExtractorInterface::class);
        $extractorMock->method('getFileTypeRestrictions')->willReturn([FileType::IMAGE]);

        $extractorService = new ExtractorService();
        $method = new \ReflectionMethod($extractorService, 'isFileTypeSupportedByExtractor');
        $arguments = [
            $fileMock,
            $extractorMock,
        ];

        $result = $method->invokeArgs($extractorService, $arguments);
        self::assertFalse($result);
    }

    #[Test]
    public function isFileTypeSupportedByExtractorReturnsTrueForFileTypeImageAndExtractorLimitedToFileTypeImage(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->method('getType')->willReturn(FileType::IMAGE);

        $extractorMock = $this->createMock(ExtractorInterface::class);
        $extractorMock->method('getFileTypeRestrictions')->willReturn([FileType::IMAGE]);

        $extractorService = new ExtractorService();
        $method = new \ReflectionMethod($extractorService, 'isFileTypeSupportedByExtractor');
        $arguments = [
            $fileMock,
            $extractorMock,
        ];

        $result = $method->invokeArgs($extractorService, $arguments);
        self::assertTrue($result);
    }

    #[Test]
    public function isFileTypeSupportedByExtractorReturnsTrueForFileTypeTextAndExtractorHasNoFileTypeLimitation(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->method('getType')->willReturn(FileType::TEXT);

        $extractorMock = $this->createMock(ExtractorInterface::class);
        $extractorMock->method('getFileTypeRestrictions')->willReturn([]);

        $extractorService = new ExtractorService();
        $method = new \ReflectionMethod($extractorService, 'isFileTypeSupportedByExtractor');
        $arguments = [
            $fileMock,
            $extractorMock,
        ];

        $result = $method->invokeArgs($extractorService, $arguments);
        self::assertTrue($result);
    }

    #[Test]
    public function extractMetaDataComposesDataByAvailableExtractors(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getDriverType')->willReturn('Local');

        $subject = $this->getMockBuilder(ExtractorService::class)
            ->onlyMethods(['getExtractorRegistry'])
            ->getMock()
        ;

        $fileMock = $this->createMock(File::class);
        $fileMock->method('getUid')->willReturn(4711);
        $fileMock->method('getType')->willReturn(FileType::IMAGE);
        $fileMock->method('getStorage')->willReturn($storageMock);

        $extractorClass1 = StringUtility::getUniqueId('extractor');
        $extractorObject1 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass1)
            ->getMock();

        $extractorObject1->method('getPriority')->willReturn(10);
        $extractorObject1->method('getExecutionPriority')->willReturn(10);
        $extractorObject1->method('canProcess')->willReturn(true);
        $extractorObject1->method('getFileTypeRestrictions')->willReturn([FileType::IMAGE->value]);
        $extractorObject1->method('getDriverRestrictions')->willReturn([$storageMock->getDriverType()]);
        $extractorObject1->method('extractMetaData')->with($fileMock)->willReturn([
            'width' => 800,
            'height' => 600,
        ]);

        $extractorClass2 = StringUtility::getUniqueId('extractor');
        $extractorObject2 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass2)
            ->getMock();

        $extractorObject2->method('getPriority')->willReturn(20);
        $extractorObject2->method('getExecutionPriority')->willReturn(20);
        $extractorObject2->method('canProcess')->willReturn(true);
        $extractorObject2->method('getFileTypeRestrictions')->willReturn([FileType::IMAGE->value]);
        $extractorObject2->method('getDriverRestrictions')->willReturn([$storageMock->getDriverType()]);
        $extractorObject2->method('extractMetaData')->with($fileMock)->willReturn([
            'keywords' => 'typo3, cms',
        ]);

        $extractorRegistryMock = $this->getMockBuilder(ExtractorRegistry::class)
            ->onlyMethods(['createExtractorInstance'])
            ->getMock();

        $extractorRegistryMock->method('createExtractorInstance')->willReturnMap(
            [
                [$extractorClass1, $extractorObject1],
                [$extractorClass2, $extractorObject2],
            ]
        );
        $extractorRegistryMock->registerExtractionService($extractorClass1);
        $extractorRegistryMock->registerExtractionService($extractorClass2);

        $subject->method('getExtractorRegistry')->willReturn($extractorRegistryMock);

        self::assertSame(['width' => 800, 'height' => 600, 'keywords' => 'typo3, cms'], $subject->extractMetaData($fileMock));
    }

    public static function extractMetaDataComposesDataByAvailableExtractorsWithDifferentPrioritiesDataProvider(): array
    {
        return [
            'Second has higher data priority' => [
                10,
                10,
                20,
                10,
                [
                    'foo' => 'second',
                    'bar' => 'first',
                    'baz' => 'second',
                ],
            ],
            'Second has higher execution priority' => [
                10,
                10,
                10,
                20,
                [
                    'foo' => 'first',
                    'baz' => 'second',
                    'bar' => 'first',
                ],
            ],
            'Second has higher data and execution priority' => [
                10,
                10,
                20,
                20,
                [
                    'foo' => 'second',
                    'bar' => 'first',
                    'baz' => 'second',
                ],
            ],
            'Second has higher execution priority, but first higher data priority' => [
                20,
                10,
                10,
                20,
                [
                    'foo' => 'first',
                    'baz' => 'second',
                    'bar' => 'first',
                ],
            ],
        ];
    }

    #[DataProvider('extractMetaDataComposesDataByAvailableExtractorsWithDifferentPrioritiesDataProvider')]
    #[Test]
    public function extractMetaDataComposesDataByAvailableExtractorsWithDifferentPriorities(
        int $extractorOneDataPriority,
        int $extractorOneExecutionPriority,
        int $extractorTwoDataPriority,
        int $extractorTwoExecutionPriority,
        array $expectedMetaData
    ): void {
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getDriverType')->willReturn('Local');

        $subject = $this->getMockBuilder(ExtractorService::class)
            ->onlyMethods(['getExtractorRegistry'])
            ->getMock()
        ;

        $fileMock = $this->createMock(File::class);
        $fileMock->expects(self::any())->method('getUid')->willReturn(4711);
        $fileMock->expects(self::any())->method('getType')->willReturn(FileType::IMAGE);
        $fileMock->expects(self::any())->method('getStorage')->willReturn($storageMock);

        $extractorClass1 = StringUtility::getUniqueId('extractor');
        $extractorObject1 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass1)
            ->getMock();

        $extractorObject1->expects(self::any())->method('getPriority')->willReturn($extractorOneDataPriority);
        $extractorObject1->expects(self::any())->method('getExecutionPriority')->willReturn($extractorOneExecutionPriority);
        $extractorObject1->expects(self::any())->method('canProcess')->willReturn(true);
        $extractorObject1->expects(self::any())->method('getFileTypeRestrictions')->willReturn([FileType::IMAGE->value]);
        $extractorObject1->expects(self::any())->method('getDriverRestrictions')->willReturn([$storageMock->getDriverType()]);
        $extractorObject1->expects(self::any())->method('extractMetaData')->with($fileMock)->willReturn([
            'foo' => 'first',
            'bar' => 'first',
        ]);

        $extractorClass2 = StringUtility::getUniqueId('extractor');
        $extractorObject2 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass2)
            ->getMock();

        $extractorObject2->expects(self::any())->method('getPriority')->willReturn($extractorTwoDataPriority);
        $extractorObject2->expects(self::any())->method('getExecutionPriority')->willReturn($extractorTwoExecutionPriority);
        $extractorObject2->expects(self::any())->method('canProcess')->willReturn(true);
        $extractorObject2->expects(self::any())->method('getFileTypeRestrictions')->willReturn([FileType::IMAGE->value]);
        $extractorObject2->expects(self::any())->method('getDriverRestrictions')->willReturn([$storageMock->getDriverType()]);
        $extractorObject2->expects(self::any())->method('extractMetaData')->with($fileMock)->willReturn([
            'foo' => 'second',
            'baz' => 'second',
        ]);

        $extractorRegistryMock = $this->getMockBuilder(ExtractorRegistry::class)
            ->onlyMethods(['createExtractorInstance'])
            ->getMock();

        $extractorRegistryMock->expects(self::any())->method('createExtractorInstance')->willReturnMap(
            [
                [$extractorClass1, $extractorObject1],
                [$extractorClass2, $extractorObject2],
            ]
        );
        $extractorRegistryMock->registerExtractionService($extractorClass1);
        $extractorRegistryMock->registerExtractionService($extractorClass2);

        $subject->expects(self::any())->method('getExtractorRegistry')->willReturn($extractorRegistryMock);

        self::assertSame($expectedMetaData, $subject->extractMetaData($fileMock));
    }
}
