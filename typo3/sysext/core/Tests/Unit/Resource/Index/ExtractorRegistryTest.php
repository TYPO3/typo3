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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Index;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExtractorRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function registeredExtractorClassCanBeRetrieved(): void
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
    public function registerExtractorClassThrowsExceptionIfClassDoesNotExist(): void
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
    public function registerExtractorClassThrowsExceptionIfClassDoesNotImplementRightInterface(): void
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
    public function registerExtractorClassWithHighestPriorityIsFirstInResult(): void
    {
        $extractorClass1 = 'db76010e5c24658c35ea1605cce2391d';
        $extractorObject1 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass1)
            ->getMock();
        $extractorObject1->method('getExecutionPriority')->willReturn(1);

        $extractorClass2 = 'ad9195e2487eea33c8a2abd5cf33cba4';
        $extractorObject2 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass2)
            ->getMock();
        $extractorObject2->method('getExecutionPriority')->willReturn(10);

        $extractorClass3 = 'cef9aa4e1cd3aa7ff05dcdccb117156a';
        $extractorObject3 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass3)
            ->getMock();
        $extractorObject3->method('getExecutionPriority')->willReturn(2);

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

        self::assertInstanceOf($extractorClass2, $extractorInstances[0]);
        self::assertInstanceOf($extractorClass3, $extractorInstances[1]);
        self::assertInstanceOf($extractorClass1, $extractorInstances[2]);
    }

    /**
     * @test
     */
    public function registeredExtractorClassWithSamePriorityAreAllReturned(): void
    {
        $extractorClass1 = 'b70551b2b2db62b6b15a9bbfcbd50614';
        $extractorObject1 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass1)
            ->getMock();
        $extractorObject1->method('getExecutionPriority')->willReturn(1);

        $extractorClass2 = 'ac318f1659d278b79b38262f23a78d5d';
        $extractorObject2 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass2)
            ->getMock();
        $extractorObject2->method('getExecutionPriority')->willReturn(1);

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
     * @test
     */
    public function registeredExtractorsCanBeFilteredByDriverTypeButNoTyeREstrictionIsTreatedAsCompatible(): void
    {
        $extractorClass1 = 'b70551b2b2db62b6b15a9bbfcbd50614';
        $extractorObject1 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass1)
            ->getMock();
        $extractorObject1->method('getExecutionPriority')->willReturn(1);
        $extractorObject1->method('getDriverRestrictions')->willReturn([]);

        $extractorClass2 = 'ac318f1659d278b79b38262f23a78d5d';
        $extractorObject2 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass2)
            ->getMock();
        $extractorObject2->method('getExecutionPriority')->willReturn(1);
        $extractorObject2->method('getDriverRestrictions')->willReturn(['Bla']);

        $createdExtractorInstances = [
            [$extractorClass1, $extractorObject1],
            [$extractorClass2, $extractorObject2],
        ];

        $extractorRegistry = $this->getMockExtractorRegistry($createdExtractorInstances);
        $extractorRegistry->registerExtractionService($extractorClass1);
        $extractorRegistry->registerExtractionService($extractorClass2);

        $extractorInstances = $extractorRegistry->getExtractorsWithDriverSupport('Bla');
        self::assertContains($extractorObject1, $extractorInstances);
        self::assertContains($extractorObject2, $extractorInstances);
    }

    /**
     * @test
     */
    public function registeredExtractorsCanBeFilteredByDriverType(): void
    {
        $extractorClass1 = 'b70551b2b2db62b6b15a9bbfcbd50614';
        $extractorObject1 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass1)
            ->getMock();
        $extractorObject1->method('getExecutionPriority')->willReturn(1);
        $extractorObject1->method('getDriverRestrictions')->willReturn(['Foo']);

        $extractorClass2 = 'ac318f1659d278b79b38262f23a78d5d';
        $extractorObject2 = $this->getMockBuilder(ExtractorInterface::class)
            ->setMockClassName($extractorClass2)
            ->getMock();
        $extractorObject2->method('getExecutionPriority')->willReturn(1);
        $extractorObject2->method('getDriverRestrictions')->willReturn(['Bla']);

        $createdExtractorInstances = [
            [$extractorClass1, $extractorObject1],
            [$extractorClass2, $extractorObject2],
        ];

        $extractorRegistry = $this->getMockExtractorRegistry($createdExtractorInstances);
        $extractorRegistry->registerExtractionService($extractorClass1);
        $extractorRegistry->registerExtractionService($extractorClass2);

        $extractorInstances = $extractorRegistry->getExtractorsWithDriverSupport('Bla');
        self::assertNotContains($extractorObject1, $extractorInstances);
        self::assertContains($extractorObject2, $extractorInstances);
    }

    /**
     * Initialize an ExtractorRegistry and mock createExtractorInstance()
     */
    protected function getMockExtractorRegistry(array $createsExtractorInstances = []): ExtractorRegistry&MockObject
    {
        $extractorRegistry = $this->getMockBuilder(ExtractorRegistry::class)
            ->onlyMethods(['createExtractorInstance'])
            ->getMock();

        if (!empty($createsExtractorInstances)) {
            $extractorRegistry
                ->method('createExtractorInstance')
                ->willReturnMap($createsExtractorInstances);
        }

        return $extractorRegistry;
    }
}
