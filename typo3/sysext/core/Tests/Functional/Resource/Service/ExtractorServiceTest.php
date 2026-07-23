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
        $fileStub = self::createStub(File::class);
        $fileStub->method('getType')->willReturn(FileType::AUDIO->value);
        $fileStub->method('getNameWithoutExtension')->willReturn('aNameWithoutExtension');
        $fileStub->method('getExtension')->willReturn('anExtension');
        self::assertEmpty($this->get(ExtractorService::class)->extractMetaData($fileStub));
    }

    #[Test]
    public function isFileTypeSupportedByExtractorReturnsExtraction(): void
    {
        $fileStub = self::createStub(File::class);
        $fileStub->method('getType')->willReturn(FileType::TEXT->value);
        $fileStub->method('getNameWithoutExtension')->willReturn('aNameWithoutExtension');
        $fileStub->method('getExtension')->willReturn('anExtension');
        $result = $this->get(ExtractorService::class)->extractMetaData($fileStub);
        self::assertSame(['title' => 'aStaticTitle'], $result);
    }

    #[Test]
    public function extractMetaDataComposesDataByAvailableExtractorsAndPrefersHigherPriority(): void
    {
        $fileStub = self::createStub(File::class);
        $fileStub->method('getType')->willReturn(FileType::TEXT->value);
        $fileStub->method('getNameWithoutExtension')->willReturn('aNameWithoutExtension');
        $fileStub->method('getExtension')->willReturn('anExtension');
        // Activate TextFileExtractor2
        $resourceStorageStub = self::createStub(ResourceStorage::class);
        $resourceStorageStub->method('getDriverType')->willReturn('aDriverRestriction');
        $fileStub->method('getStorage')->willReturn($resourceStorageStub);
        $result = $this->get(ExtractorService::class)->extractMetaData($fileStub);
        self::assertSame(
            [
                'title' => 'aNameWithoutExtension',
                'extension' => 'anExtension',
            ],
            $result
        );
    }
}
