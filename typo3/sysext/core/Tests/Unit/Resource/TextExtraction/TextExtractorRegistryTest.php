<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\TextExtraction;

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

use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;

/**
 * Test cases for TextExtractorRegistry
 */
class TextExtractorRegistryTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Initialize a TextExtractorRegistry and mock createTextExtractorInstance()
     *
     * @param array $createsTextExtractorInstances
     * @return \PHPUnit_Framework_MockObject_MockObject|TextExtractorRegistry
     */
    protected function getTextExtractorRegistry(array $createsTextExtractorInstances = [])
    {
        $textExtractorRegistry = $this->getMockBuilder(TextExtractorRegistry::class)
            ->setMethods(['createTextExtractorInstance'])
            ->getMock();

        if (!empty($createsTextExtractorInstances)) {
            $textExtractorRegistry->expects($this->any())
                ->method('createTextExtractorInstance')
                ->will($this->returnValueMap($createsTextExtractorInstances));
        }

        return $textExtractorRegistry;
    }

    /**
     * @test
     */
    public function registeredTextExtractorClassCanBeRetrieved()
    {
        $textExtractorClass = $this->getUniqueId('myTextExtractor');
        $textExtractorInstance = $this->getMockBuilder(TextExtractorInterface::class)
            ->setMockClassName($textExtractorClass)
            ->getMock();

        $textExtractorRegistry = $this->getTextExtractorRegistry([[$textExtractorClass, $textExtractorInstance]]);

        $textExtractorRegistry->registerTextExtractor($textExtractorClass);
        $this->assertContains($textExtractorInstance, $textExtractorRegistry->getTextExtractorInstances(), '', false, false);
    }

    /**
     * @test
     */
    public function registerTextExtractorThrowsExceptionIfClassDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1422906893);

        $textExtractorRegistry = $this->getTextExtractorRegistry();
        $textExtractorRegistry->registerTextExtractor($this->getUniqueId());
    }

    /**
     * @test
     */
    public function registerTextExtractorThrowsExceptionIfClassDoesNotImplementRightInterface()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1422771427);

        $textExtractorRegistry = $this->getTextExtractorRegistry();
        $textExtractorRegistry->registerTextExtractor(__CLASS__);
    }
}
