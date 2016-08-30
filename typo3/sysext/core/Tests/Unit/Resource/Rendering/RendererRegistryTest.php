<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Rendering;

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

/**
 * Test cases for RendererRegistry
 */
class RendererRegistryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Initialize a RendererRegistry and mock createRendererInstance()
     *
     * @param array $createsRendererInstances
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry
     */
    protected function getTestRendererRegistry(array $createsRendererInstances = [])
    {
        $rendererRegistry = $this->getMockBuilder(\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::class)
            ->setMethods(['createRendererInstance'])
            ->getMock();

        if (!empty($createsRendererInstances)) {
            $rendererRegistry->expects($this->any())
                ->method('createRendererInstance')
                ->will($this->returnValueMap($createsRendererInstances));
        }

        return $rendererRegistry;
    }

    /**
     * @test
     */
    public function registeredFileRenderClassCanBeRetrieved()
    {
        $rendererClass = $this->getUniqueId('myRenderer');
        $rendererObject = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, [], [], $rendererClass);

        $rendererRegistry = $this->getTestRendererRegistry([[$rendererClass, $rendererObject]]);

        $rendererRegistry->registerRendererClass($rendererClass);
        $this->assertContains($rendererObject, $rendererRegistry->getRendererInstances(), '', false, false);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1411840171
     */
    public function registerRendererClassThrowsExceptionIfClassDoesNotExist()
    {
        $rendererRegistry = $this->getTestRendererRegistry();
        $rendererRegistry->registerRendererClass($this->getUniqueId());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1411840172
     */
    public function registerRendererClassThrowsExceptionIfClassDoesNotImplementRightInterface()
    {
        $className = __CLASS__;
        $rendererRegistry = $this->getTestRendererRegistry();
        $rendererRegistry->registerRendererClass($className);
    }

    /**
     * @test
     */
    public function registerRendererClassWithHighestPriorityIsFirstInResult()
    {
        $rendererClass1 = $this->getUniqueId('myRenderer1');
        $rendererObject1 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, [], [], $rendererClass1);
        $rendererObject1->expects($this->any())->method('getPriority')->will($this->returnValue(1));

        $rendererClass2 = $this->getUniqueId('myRenderer2');
        $rendererObject2 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, [], [], $rendererClass2);
        $rendererObject2->expects($this->any())->method('getPriority')->will($this->returnValue(10));

        $rendererClass3 = $this->getUniqueId('myRenderer3');
        $rendererObject3 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, [], [], $rendererClass3);
        $rendererObject3->expects($this->any())->method('getPriority')->will($this->returnValue(2));

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
        $this->assertTrue($rendererInstances[0] instanceof $rendererClass2);
        $this->assertTrue($rendererInstances[1] instanceof $rendererClass3);
        $this->assertTrue($rendererInstances[2] instanceof $rendererClass1);
    }

    /**
     * @test
     */
    public function registeredFileRendererClassWithSamePriorityAreAllReturned()
    {
        $rendererClass1 = $this->getUniqueId('myRenderer1');
        $rendererObject1 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, [], [], $rendererClass1);
        $rendererObject1->expects($this->any())->method('getPriority')->will($this->returnValue(1));

        $rendererClass2 = $this->getUniqueId('myRenderer2');
        $rendererObject2 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, [], [], $rendererClass2);
        $rendererObject2->expects($this->any())->method('getPriority')->will($this->returnValue(1));

        $createdRendererInstances = [
            [$rendererClass1, $rendererObject1],
            [$rendererClass2, $rendererObject2],
        ];

        $rendererRegistry = $this->getTestRendererRegistry($createdRendererInstances);
        $rendererRegistry->registerRendererClass($rendererClass1);
        $rendererRegistry->registerRendererClass($rendererClass2);

        $rendererInstances = $rendererRegistry->getRendererInstances();
        $this->assertContains($rendererObject1, $rendererInstances);
        $this->assertContains($rendererObject2, $rendererInstances);
    }

    /**
     * @test
     */
    public function getRendererReturnsCorrectInstance()
    {
        $this->markTestSkipped('Test triggers an error. This is a known PHP bug (http://stackoverflow.com/questions/3235387/usort-array-was-modified-by-the-user-comparison-function)');

        $rendererClass1 = $this->getUniqueId('myVideoRenderer');
        $rendererObject1 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, ['getPriority', 'canRender', 'render'], [], $rendererClass1);
        $rendererObject1->expects($this->any())->method('getPriority')->will($this->returnValue(1));
        $rendererObject1->expects($this->once())->method('canRender')->will($this->returnValue(true));

        $rendererClass2 = $this->getUniqueId('myAudioRenderer');
        $rendererObject2 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, ['getPriority', 'canRender', 'render'], [], $rendererClass2);
        $rendererObject2->expects($this->any())->method('getPriority')->will($this->returnValue(10));
        $rendererObject2->expects($this->once())->method('canRender')->will($this->returnValue(false));

        $fileResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);

        $createdRendererInstances = [
            [$rendererClass1, $rendererObject1],
            [$rendererClass2, $rendererObject2],
        ];

        $rendererRegistry = $this->getTestRendererRegistry($createdRendererInstances);
        $rendererRegistry->registerRendererClass($rendererClass1);
        $rendererRegistry->registerRendererClass($rendererClass2);

        $rendererRegistry->getRendererInstances();

        $renderer = $rendererRegistry->getRenderer($fileResourceMock);

        $this->assertTrue($renderer instanceof $rendererClass1);
    }

    /**
     * @test
     */
    public function getRendererReturnsCorrectInstance2()
    {
        $rendererRegistry = \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::getInstance();
        $rendererRegistry->registerRendererClass(\TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer::class);
        $rendererRegistry->registerRendererClass(\TYPO3\CMS\Core\Resource\Rendering\VideoTagRenderer::class);

        $fileResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, [], [], '', false);
        $fileResourceMock->expects($this->any())->method('getMimeType')->will($this->returnValue('video/mp4'));

        $rendererRegistry->getRendererInstances();

        $renderer = $rendererRegistry->getRenderer($fileResourceMock);

        $this->assertInstanceOf(\TYPO3\CMS\Core\Resource\Rendering\VideoTagRenderer::class, $renderer);
    }
}
