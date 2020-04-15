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

use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ExtractorRegistryTest
 */
class ExtractorRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function registeredExtractorClassCanBeRetrieved()
    {
        $extractorClass = 'a9f4d5e4ebb4b03547a2a6094e1170ac';
        $extractorObject = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass)
            ->getMock();

        $extractorRegistry = $this->getMockExtractorRegistry([[$extractorClass, $extractorObject]]);

        $extractorRegistry->registerExtractionService($extractorClass);
        self::assertContains($extractorObject, $extractorRegistry->getExtractors());
    }

    /**
     * @test
     */
    public function registerExtractorClassThrowsExceptionIfClassDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1422705270);

        $className = 'e1f9aa4e1cd3aa7ff05dcdccb117156a';
        $extractorRegistry = $this->getMockExtractorRegistry();
        $extractorRegistry->registerExtractionService($className);
    }

    /**
     * @test
     */
    public function registerExtractorClassThrowsExceptionIfClassDoesNotImplementRightInterface()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1422705271);

        $className = __CLASS__;
        $extractorRegistry = $this->getMockExtractorRegistry();
        $extractorRegistry->registerExtractionService($className);
    }

    /**
     * @test
     */
    public function registerExtractorClassWithHighestPriorityIsFirstInResult()
    {
        $extractorClass1 = 'db76010e5c24658c35ea1605cce2391d';
        $extractorObject1 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass1)
            ->getMock();
        $extractorObject1->expects(self::any())->method('getPriority')->willReturn(1);

        $extractorClass2 = 'ad9195e2487eea33c8a2abd5cf33cba4';
        $extractorObject2 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass2)
            ->getMock();
        $extractorObject2->expects(self::any())->method('getPriority')->willReturn(10);

        $extractorClass3 = 'cef9aa4e1cd3aa7ff05dcdccb117156a';
        $extractorObject3 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass3)
            ->getMock();
        $extractorObject3->expects(self::any())->method('getPriority')->willReturn(2);

        $createdExtractorInstances = [
            [$extractorClass1, $extractorObject1],
            [$extractorClass2, $extractorObject2],
            [$extractorClass3, $extractorObject3],
        ];

        $extractorRegistry = $this->getMockExtractorRegistry($createdExtractorInstances);
        $extractorRegistry->registerExtractionService($extractorClass1);
        $extractorRegistry->registerExtractionService($extractorClass2);
        $extractorRegistry->registerExtractionService($extractorClass3);

        $extractorInstances = $extractorRegistry->getExtractors();

        self::assertTrue($extractorInstances[0] instanceof $extractorClass2);
        self::assertTrue($extractorInstances[1] instanceof $extractorClass3);
        self::assertTrue($extractorInstances[2] instanceof $extractorClass1);
    }

    /**
     * @test
     */
    public function registeredExtractorClassWithSamePriorityAreAllReturned()
    {
        $extractorClass1 = 'b70551b2b2db62b6b15a9bbfcbd50614';
        $extractorObject1 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass1)
            ->getMock();
        $extractorObject1->expects(self::any())->method('getPriority')->willReturn(1);

        $extractorClass2 = 'ac318f1659d278b79b38262f23a78d5d';
        $extractorObject2 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass2)
            ->getMock();
        $extractorObject2->expects(self::any())->method('getPriority')->willReturn(1);

        $createdExtractorInstances = [
            [$extractorClass1, $extractorObject1],
            [$extractorClass2, $extractorObject2],
        ];

        $extractorRegistry = $this->getMockExtractorRegistry($createdExtractorInstances);
        $extractorRegistry->registerExtractionService($extractorClass1);
        $extractorRegistry->registerExtractionService($extractorClass2);

        $extractorInstances = $extractorRegistry->getExtractors();
        self::assertContains($extractorObject1, $extractorInstances);
        self::assertContains($extractorObject2, $extractorInstances);
    }

    /**
     * Initialize an ExtractorRegistry and mock createExtractorInstance()
     *
     * @param array $createsExtractorInstances
     * @return \PHPUnit\Framework\MockObject\MockObject|\TYPO3\CMS\Core\Resource\Index\ExtractorRegistry
     */
    protected function getMockExtractorRegistry(array $createsExtractorInstances = [])
    {
        $extractorRegistry = $this->getMockBuilder(ExtractorRegistry::class)
            ->setMethods(['createExtractorInstance'])
            ->getMock();

        if (!empty($createsExtractorInstances)) {
            $extractorRegistry->expects(self::any())
                ->method('createExtractorInstance')
                ->willReturnMap($createsExtractorInstances);
        }

        return $extractorRegistry;
    }
}
