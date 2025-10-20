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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Index;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestMetadataExtraction\Resources\Metadata\Extractors\ImageFileExtractor;
use TYPO3Tests\TestMetadataExtraction\Resources\Metadata\Extractors\TextFileExtractor1;
use TYPO3Tests\TestMetadataExtraction\Resources\Metadata\Extractors\TextFileExtractor2;

final class ExtractorRegistryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_metadata_extraction'];

    #[Test]
    public function extractorFromTestExtensionGetRegistered(): void
    {
        $subject = $this->get(ExtractorRegistry::class);
        $extractorFound = false;
        foreach ($subject->getExtractors() as $extractor) {
            if ($extractor instanceof TextFileExtractor1) {
                $extractorFound = true;
                break;
            }
        }
        self::assertTrue($extractorFound);
    }

    #[Test]
    public function getExtractorsWithHighestPriorityIsFirstInResult(): void
    {
        $subject = $this->get(ExtractorRegistry::class);
        $extractors = $subject->getExtractors();
        self::assertInstanceOf(ImageFileExtractor::class, $extractors[0]); // prio 100
        self::assertInstanceOf(TextFileExtractor1::class, $extractors[1]); // prio 10
    }

    #[Test]
    public function getExtractorsWithSamePriorityAreBothIncluded(): void
    {
        $subject = $this->get(ExtractorRegistry::class);
        $extractors = $subject->getExtractors();
        self::assertInstanceOf(TextFileExtractor1::class, $extractors[1]); // prio 10
        self::assertInstanceOf(TextFileExtractor2::class, $extractors[2]); // prio 10, same as 1
    }

    #[Test]
    public function getExtractorsWithDriverSupportReturnsExtractorsWithoutRestriction(): void
    {
        $subject = $this->get(ExtractorRegistry::class);
        $extractors = $subject->getExtractorsWithDriverSupport('aDriverRestriction');
        // 1 and 2 are returned since they have no restriction
        self::assertInstanceOf(ImageFileExtractor::class, $extractors[0]); // no restriction
        self::assertInstanceOf(TextFileExtractor1::class, $extractors[1]); // no restriction
        self::assertInstanceOf(TextFileExtractor2::class, $extractors[2]); // matching restriction
    }

    #[Test]
    public function getExtractorsWithDriverSupportDoesNotReturnNotMatchingExtractor(): void
    {
        $subject = $this->get(ExtractorRegistry::class);
        $extractors = $subject->getExtractorsWithDriverSupport('doesNotMatchExtractor3');
        $extractor3Found = false;
        foreach ($extractors as $extractor) {
            if ($extractor instanceof TextFileExtractor2) {
                $extractor3Found = true;
            }
        }
        self::assertFalse($extractor3Found);
    }
}
