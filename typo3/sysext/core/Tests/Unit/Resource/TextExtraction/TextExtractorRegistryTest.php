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
class TextExtractorRegistryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
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
        $textExtractorInstance = $this->getMock(TextExtractorInterface::class, [], [], $textExtractorClass);

        $textExtractorRegistry = $this->getTextExtractorRegistry([[$textExtractorClass, $textExtractorInstance]]);

        $textExtractorRegistry->registerTextExtractor($textExtractorClass);
        $this->assertContains($textExtractorInstance, $textExtractorRegistry->getTextExtractorInstances(), '', false, false);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1422906893
     */
    public function registerTextExtractorThrowsExceptionIfClassDoesNotExist()
    {
        $textExtractorRegistry = $this->getTextExtractorRegistry();
        $textExtractorRegistry->registerTextExtractor($this->getUniqueId());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1422771427
     */
    public function registerTextExtractorThrowsExceptionIfClassDoesNotImplementRightInterface()
    {
        $textExtractorRegistry = $this->getTextExtractorRegistry();
        $textExtractorRegistry->registerTextExtractor(__CLASS__);
    }
}
