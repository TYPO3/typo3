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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Index;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Service\ExtractorService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class IndexerTest
 */
class IndexerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function extractMetaDataCallsSubsequentMethodsWithCorrectArguments(): void
    {
        $mockStorage = $this->createMock(ResourceStorage::class);

        /** @var Indexer|\PHPUnit\Framework\MockObject\MockObject $subject */
        $subject = $this->getMockBuilder(Indexer::class)
            ->setConstructorArgs([$mockStorage])
            ->setMethods(['getFileIndexRepository', 'extractRequiredMetaData', 'getExtractorService'])
            ->getMock();

        $indexFileRepositoryMock = $this->createMock(FileIndexRepository::class);
        $subject->expects(self::any())->method('getFileIndexRepository')->willReturn($indexFileRepositoryMock);

        $fileMock = $this->createMock(File::class);
        $fileMock->expects(self::any())->method('getUid')->willReturn(42);
        $fileMock->expects(self::any())->method('getType')->willReturn(File::FILETYPE_TEXT);
        $fileMock->expects(self::any())->method('getStorage')->willReturn($mockStorage);

        $extractorServiceMock = $this->getMockBuilder(ExtractorService::class)->getMock();
        $extractorServiceMock->expects(self::once())->method('extractMetaData')->with($fileMock);
        $subject->expects(self::any())->method('getExtractorService')->willReturn($extractorServiceMock);

        $indexFileRepositoryMock->expects(self::once())->method('updateIndexingTime')->with($fileMock->getUid());

        $subject->extractMetaData($fileMock);
    }
}
