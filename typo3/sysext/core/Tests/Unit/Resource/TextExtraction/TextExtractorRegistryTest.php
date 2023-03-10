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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\TextExtraction;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test cases for TextExtractorRegistry
 */
class TextExtractorRegistryTest extends UnitTestCase
{
    /**
     * Initialize a TextExtractorRegistry and mock createTextExtractorInstance()
     */
    protected function getTextExtractorRegistry(array $createsTextExtractorInstances = []): TextExtractorRegistry&MockObject
    {
        $textExtractorRegistry = $this->getMockBuilder(TextExtractorRegistry::class)
            ->onlyMethods(['createTextExtractorInstance'])
            ->getMock();

        if (!empty($createsTextExtractorInstances)) {
            $textExtractorRegistry
                ->method('createTextExtractorInstance')
                ->willReturnMap($createsTextExtractorInstances);
        }

        return $textExtractorRegistry;
    }

    /**
     * @test
     */
    public function registeredTextExtractorClassCanBeRetrieved(): void
    {
        $textExtractorClass = StringUtility::getUniqueId('myTextExtractor');
        $textExtractorInstance = $this->getMockBuilder(TextExtractorInterface::class)
            ->setMockClassName($textExtractorClass)
            ->getMock();

        $textExtractorRegistry = $this->getTextExtractorRegistry([[$textExtractorClass, $textExtractorInstance]]);

        $textExtractorRegistry->registerTextExtractor($textExtractorClass);
        self::assertContains($textExtractorInstance, $textExtractorRegistry->getTextExtractorInstances());
    }

    /**
     * @test
     */
    public function registerTextExtractorThrowsExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1422906893);

        $textExtractorRegistry = $this->getTextExtractorRegistry();
        $textExtractorRegistry->registerTextExtractor(StringUtility::getUniqueId());
    }

    /**
     * @test
     */
    public function registerTextExtractorThrowsExceptionIfClassDoesNotImplementRightInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1422771427);

        $textExtractorRegistry = $this->getTextExtractorRegistry();
        $textExtractorRegistry->registerTextExtractor(__CLASS__);
    }
}
