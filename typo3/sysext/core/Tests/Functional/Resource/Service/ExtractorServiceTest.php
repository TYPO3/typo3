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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Service\ExtractorService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExtractorServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_metadata_extraction'];

    #[Test]
    public function isFileTypeSupportedByExtractorReturnsEmptyArrayForFileTypeWithNoMatchingExtractor(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->method('getType')->willReturn(FileType::AUDIO->value);
        $fileMock->method('getNameWithoutExtension')->willReturn('aNameWithoutExtension');
        $fileMock->method('getExtension')->willReturn('anExtension');
        self::assertEmpty($this->get(ExtractorService::class)->extractMetaData($fileMock));
    }

    #[Test]
    public function isFileTypeSupportedByExtractorReturnsExtraction(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->method('getType')->willReturn(FileType::TEXT->value);
        $fileMock->method('getNameWithoutExtension')->willReturn('aNameWithoutExtension');
        $fileMock->method('getExtension')->willReturn('anExtension');
        $result = $this->get(ExtractorService::class)->extractMetaData($fileMock);
        self::assertSame(['title' => 'aStaticTitle'], $result);
    }

    #[Test]
    public function extractMetaDataComposesDataByAvailableExtractorsAndPrefersHigherPriority(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->method('getType')->willReturn(FileType::TEXT->value);
        $fileMock->method('getNameWithoutExtension')->willReturn('aNameWithoutExtension');
        $fileMock->method('getExtension')->willReturn('anExtension');
        // Activate TextFileExtractor2
        $resourceStorageMock = $this->createMock(ResourceStorage::class);
        $resourceStorageMock->method('getDriverType')->willReturn('aDriverRestriction');
        $fileMock->method('getStorage')->willReturn($resourceStorageMock);
        $result = $this->get(ExtractorService::class)->extractMetaData($fileMock);
        self::assertSame(
            [
                'title' => 'aNameWithoutExtension',
                'extension' => 'anExtension',
            ],
            $result
        );
    }
}
