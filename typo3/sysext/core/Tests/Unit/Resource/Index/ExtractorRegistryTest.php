<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Index;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;

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
        $extractorObject = $this->getMock(\TYPO3\CMS\Core\Resource\Index\ExtractorInterface::class, [], [], $extractorClass);

        $extractorRegistry = $this->getMockExtractorRegistry([[$extractorClass, $extractorObject]]);

        $extractorRegistry->registerExtractionService($extractorClass);
        $this->assertContains($extractorObject, $extractorRegistry->getExtractors(), '', false, false);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1422705270
     */
    public function registerExtractorClassThrowsExceptionIfClassDoesNotExist()
    {
        $className = 'e1f9aa4e1cd3aa7ff05dcdccb117156a';
        $extractorRegistry = $this->getMockExtractorRegistry();
        $extractorRegistry->registerExtractionService($className);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1422705271
     */
    public function registerExtractorClassThrowsExceptionIfClassDoesNotImplementRightInterface()
    {
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
        $extractorObject1 = $this->getMock(\TYPO3\CMS\Core\Resource\Index\ExtractorInterface::class, [], [], $extractorClass1);
        $extractorObject1->expects($this->any())->method('getPriority')->will($this->returnValue(1));

        $extractorClass2 = 'ad9195e2487eea33c8a2abd5cf33cba4';
        $extractorObject2 = $this->getMock(\TYPO3\CMS\Core\Resource\Index\ExtractorInterface::class, [], [], $extractorClass2);
        $extractorObject2->expects($this->any())->method('getPriority')->will($this->returnValue(10));

        $extractorClass3 = 'cef9aa4e1cd3aa7ff05dcdccb117156a';
        $extractorObject3 = $this->getMock(\TYPO3\CMS\Core\Resource\Index\ExtractorInterface::class, [], [], $extractorClass3);
        $extractorObject3->expects($this->any())->method('getPriority')->will($this->returnValue(2));

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

        $this->assertTrue($extractorInstances[0] instanceof $extractorClass2);
        $this->assertTrue($extractorInstances[1] instanceof $extractorClass3);
        $this->assertTrue($extractorInstances[2] instanceof $extractorClass1);
    }

    /**
     * @test
     */
    public function registeredExtractorClassWithSamePriorityAreAllReturned()
    {
        $extractorClass1 = 'b70551b2b2db62b6b15a9bbfcbd50614';
        $extractorObject1 = $this->getMock(\TYPO3\CMS\Core\Resource\Index\ExtractorInterface::class, [], [], $extractorClass1);
        $extractorObject1->expects($this->any())->method('getPriority')->will($this->returnValue(1));

        $extractorClass2 = 'ac318f1659d278b79b38262f23a78d5d';
        $extractorObject2 = $this->getMock(\TYPO3\CMS\Core\Resource\Index\ExtractorInterface::class, [], [], $extractorClass2);
        $extractorObject2->expects($this->any())->method('getPriority')->will($this->returnValue(1));

        $createdExtractorInstances = [
            [$extractorClass1, $extractorObject1],
            [$extractorClass2, $extractorObject2],
        ];

        $extractorRegistry = $this->getMockExtractorRegistry($createdExtractorInstances);
        $extractorRegistry->registerExtractionService($extractorClass1);
        $extractorRegistry->registerExtractionService($extractorClass2);

        $extractorInstances = $extractorRegistry->getExtractors();
        $this->assertContains($extractorObject1, $extractorInstances);
        $this->assertContains($extractorObject2, $extractorInstances);
    }

    /**
     * Initialize an ExtractorRegistry and mock createExtractorInstance()
     *
     * @param array $createsExtractorInstances
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Resource\Index\ExtractorRegistry
     */
    protected function getMockExtractorRegistry(array $createsExtractorInstances = [])
    {
        $extractorRegistry = $this->getMockBuilder(\TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::class)
            ->setMethods(['createExtractorInstance'])
            ->getMock();

        if (!empty($createsExtractorInstances)) {
            $extractorRegistry->expects($this->any())
                ->method('createExtractorInstance')
                ->will($this->returnValueMap($createsExtractorInstances));
        }

        return $extractorRegistry;
    }
}
