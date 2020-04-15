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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Rendering;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
use TYPO3\CMS\Core\Resource\Rendering\VideoTagRenderer;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RendererRegistryTest extends UnitTestCase
{
    /**
     * Initialize a RendererRegistry and mock createRendererInstance()
     *
     * @param array $createsRendererInstances
     * @return \PHPUnit\Framework\MockObject\MockObject|RendererRegistry
     */
    protected function getTestRendererRegistry(array $createsRendererInstances = [])
    {
        $rendererRegistry = $this->getMockBuilder(RendererRegistry::class)
            ->setMethods(['createRendererInstance'])
            ->getMock();

        if (!empty($createsRendererInstances)) {
            $rendererRegistry->expects(self::any())
                ->method('createRendererInstance')
                ->willReturnMap($createsRendererInstances);
        }

        return $rendererRegistry;
    }

    /**
     * @test
     */
    public function registeredFileRenderClassCanBeRetrieved()
    {
        $rendererClass = StringUtility::getUniqueId('myRenderer');
        $rendererObject = $this->getMockBuilder(FileRendererInterface::class)
            ->setMockClassName($rendererClass)
            ->getMock();

        $rendererRegistry = $this->getTestRendererRegistry([[$rendererClass, $rendererObject]]);

        $rendererRegistry->registerRendererClass($rendererClass);
        self::assertContains($rendererObject, $rendererRegistry->getRendererInstances());
    }

    /**
     * @test
     */
    public function registerRendererClassThrowsExceptionIfClassDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1411840171);

        $rendererRegistry = $this->getTestRendererRegistry();
        $rendererRegistry->registerRendererClass(StringUtility::getUniqueId());
    }

    /**
     * @test
     */
    public function registerRendererClassThrowsExceptionIfClassDoesNotImplementRightInterface()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1411840172);

        $className = __CLASS__;
        $rendererRegistry = $this->getTestRendererRegistry();
        $rendererRegistry->registerRendererClass($className);
    }

    /**
     * @test
     */
    public function registerRendererClassWithHighestPriorityIsFirstInResult()
    {
        $rendererClass1 = StringUtility::getUniqueId('myRenderer1');
        $rendererObject1 = $this->getMockBuilder(FileRendererInterface::class)
            ->setMockClassName($rendererClass1)
            ->getMock();
        $rendererObject1->expects(self::any())->method('getPriority')->willReturn(1);

        $rendererClass2 = StringUtility::getUniqueId('myRenderer2');
        $rendererObject2 = $this->getMockBuilder(FileRendererInterface::class)
            ->setMockClassName($rendererClass2)
            ->getMock();
        $rendererObject2->expects(self::any())->method('getPriority')->willReturn(10);

        $rendererClass3 = StringUtility::getUniqueId('myRenderer3');
        $rendererObject3 = $this->getMockBuilder(FileRendererInterface::class)
            ->setMockClassName($rendererClass3)
            ->getMock();
        $rendererObject3->expects(self::any())->method('getPriority')->willReturn(2);

        $createdRendererInstances = [
            [$rendererClass1, $rendererObject1],
            [$rendererClass2, $rendererObject2],
            [$rendererClass3, $rendererObject3],
        ];

        $rendererRegistry = $this->getTestRendererRegistry($createdRendererInstances);
        $rendererRegistry->registerRendererClass($rendererClass1);
        $rendererRegistry->registerRendererClass($rendererClass2);
        $rendererRegistry->registerRendererClass($rendererClass3);

        $rendererInstances = $rendererRegistry->getRendererInstances();
        self::assertTrue($rendererInstances[0] instanceof $rendererClass2);
        self::assertTrue($rendererInstances[1] instanceof $rendererClass3);
        self::assertTrue($rendererInstances[2] instanceof $rendererClass1);
    }

    /**
     * @test
     */
    public function registeredFileRendererClassWithSamePriorityAreAllReturned()
    {
        $rendererClass1 = StringUtility::getUniqueId('myRenderer1');
        $rendererObject1 = $this->getMockBuilder(FileRendererInterface::class)
            ->setMockClassName($rendererClass1)
            ->getMock();
        $rendererObject1->expects(self::any())->method('getPriority')->willReturn(1);

        $rendererClass2 = StringUtility::getUniqueId('myRenderer2');
        $rendererObject2 = $this->getMockBuilder(FileRendererInterface::class)
            ->setMockClassName($rendererClass2)
            ->getMock();
        $rendererObject2->expects(self::any())->method('getPriority')->willReturn(1);

        $createdRendererInstances = [
            [$rendererClass1, $rendererObject1],
            [$rendererClass2, $rendererObject2],
        ];

        $rendererRegistry = $this->getTestRendererRegistry($createdRendererInstances);
        $rendererRegistry->registerRendererClass($rendererClass1);
        $rendererRegistry->registerRendererClass($rendererClass2);

        $rendererInstances = $rendererRegistry->getRendererInstances();
        self::assertContains($rendererObject1, $rendererInstances);
        self::assertContains($rendererObject2, $rendererInstances);
    }

    /**
     * @test
     */
    public function getRendererReturnsCorrectInstance()
    {
        $rendererClass1 = StringUtility::getUniqueId('myVideoRenderer');
        $rendererObject1 = $this->getMockBuilder(FileRendererInterface::class)
            ->setMethods(['getPriority', 'canRender', 'render'])
            ->setMockClassName($rendererClass1)
            ->getMock();
        $rendererObject1->expects(self::any())->method('getPriority')->willReturn(1);
        $rendererObject1->expects(self::once())->method('canRender')->willReturn(true);

        $rendererClass2 = StringUtility::getUniqueId('myAudioRenderer');
        $rendererObject2 = $this->getMockBuilder(FileRendererInterface::class)
            ->setMethods(['getPriority', 'canRender', 'render'])
            ->setMockClassName($rendererClass2)
            ->getMock();
        $rendererObject2->expects(self::any())->method('getPriority')->willReturn(10);
        $rendererObject2->expects(self::once())->method('canRender')->willReturn(false);

        $fileResourceMock = $this->createMock(File::class);

        $createdRendererInstances = [
            [$rendererClass1, $rendererObject1],
            [$rendererClass2, $rendererObject2],
        ];

        $rendererRegistry = $this->getTestRendererRegistry($createdRendererInstances);
        $rendererRegistry->registerRendererClass($rendererClass1);
        $rendererRegistry->registerRendererClass($rendererClass2);

        $rendererRegistry->getRendererInstances();

        $renderer = $rendererRegistry->getRenderer($fileResourceMock);

        self::assertTrue($renderer instanceof $rendererClass1);
    }

    /**
     * @test
     */
    public function getRendererReturnsCorrectInstance2()
    {
        $this->resetSingletonInstances = true;
        $rendererRegistry = RendererRegistry::getInstance();
        $rendererRegistry->registerRendererClass(AudioTagRenderer::class);
        $rendererRegistry->registerRendererClass(VideoTagRenderer::class);

        $fileResourceMock = $this->createMock(File::class);
        $fileResourceMock->expects(self::any())->method('getMimeType')->willReturn('video/mp4');

        $rendererRegistry->getRendererInstances();

        $renderer = $rendererRegistry->getRenderer($fileResourceMock);

        self::assertInstanceOf(VideoTagRenderer::class, $renderer);
    }
}
