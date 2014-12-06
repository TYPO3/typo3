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
class RendererRegistryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Initialize an RendererRegistry and mock createRendererInstance()
	 *
	 * @param array $createsRendererInstances
	 * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry
	 */
	protected function getTestRendererRegistry(array $createsRendererInstances = array()) {
		$rendererRegistry = $this->getMockBuilder(\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::class)
			->setMethods(array('createRendererInstance'))
			->getMock();

		if (count($createsRendererInstances)) {
			$rendererRegistry->expects($this->any())
				->method('createRendererInstance')
				->will($this->returnValueMap($createsRendererInstances));
		}

		return $rendererRegistry;
	}

	/**
	 * @test
	 */
	public function registeredFileRenderClassCanBeRetrieved() {
		$rendererClass = uniqid('myRenderer');
		$rendererObject = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, array(), array(), $rendererClass);

		$rendererRegistry = $this->getTestRendererRegistry(array(array($rendererClass, $rendererObject)));

		$rendererRegistry->registerRendererClass($rendererClass);
		$this->assertContains($rendererObject, $rendererRegistry->getRendererInstances(), '', FALSE, FALSE);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1411840171
	 */
	public function registerRendererClassThrowsExceptionIfClassDoesNotExist() {
		$rendererRegistry = $this->getTestRendererRegistry();
		$rendererRegistry->registerRendererClass(uniqid());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1411840172
	 */
	public function registerRendererClassThrowsExceptionIfClassDoesNotImplementRightInterface() {
		$className = __CLASS__;
		$rendererRegistry = $this->getTestRendererRegistry();
		$rendererRegistry->registerRendererClass($className);
	}

	/**
	 * @test
	 */
	public function registerRendererClassWithHighestPriorityIsFirstInResult() {
		$rendererClass1 = uniqid('myRenderer1');
		$rendererObject1 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, array(), array(), $rendererClass1);
		$rendererObject1->expects($this->any())->method('getPriority')->will($this->returnValue(1));

		$rendererClass2 = uniqid('myRenderer2');
		$rendererObject2 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, array(), array(), $rendererClass2);
		$rendererObject2->expects($this->any())->method('getPriority')->will($this->returnValue(10));

		$rendererClass3 = uniqid('myRenderer3');
		$rendererObject3 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, array(), array(), $rendererClass3);
		$rendererObject3->expects($this->any())->method('getPriority')->will($this->returnValue(2));

		$createdRendererInstances = array(
			array($rendererClass1, $rendererObject1),
			array($rendererClass2, $rendererObject2),
			array($rendererClass3, $rendererObject3),
		);

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
	public function registeredFileRendererClassWithSamePriorityAreReturnedInSameOrderAsTheyWereAdded() {
		$rendererClass1 = uniqid('myRenderer1');
		$rendererObject1 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, array(), array(), $rendererClass1);
		$rendererObject1->expects($this->any())->method('getPriority')->will($this->returnValue(1));

		$rendererClass2 = uniqid('myRenderer2');
		$rendererObject2 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, array(), array(), $rendererClass2);
		$rendererObject2->expects($this->any())->method('getPriority')->will($this->returnValue(1));

		$createdRendererInstances = array(
			array($rendererClass1, $rendererObject1),
			array($rendererClass2, $rendererObject2),
		);

		$rendererRegistry = $this->getTestRendererRegistry($createdRendererInstances);
		$rendererRegistry->registerRendererClass($rendererClass1);
		$rendererRegistry->registerRendererClass($rendererClass2);

		$rendererInstances = $rendererRegistry->getRendererInstances();
		$this->assertTrue($rendererInstances[0] instanceof $rendererClass1);
		$this->assertTrue($rendererInstances[1] instanceof $rendererClass2);
	}

	/**
	 * @test
	 */
	public function getRendererReturnsCorrectInstance() {

		$this->markTestSkipped('Test triggers a error this is known PHP bug - http://stackoverflow.com/questions/3235387/usort-array-was-modified-by-the-user-comparison-function)');

		$rendererClass1 = uniqid('myVideoRenderer');
		$rendererObject1 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, array('getPriority', 'canRender', 'render'), array(), $rendererClass1);
		$rendererObject1->expects($this->any())->method('getPriority')->will($this->returnValue(1));
		$rendererObject1->expects($this->once())->method('canRender')->will($this->returnValue(TRUE));

		$rendererClass2 = uniqid('myAudioRenderer');
		$rendererObject2 = $this->getMock(\TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface::class, array('getPriority', 'canRender', 'render'), array(), $rendererClass2);
		$rendererObject2->expects($this->any())->method('getPriority')->will($this->returnValue(10));
		$rendererObject2->expects($this->once())->method('canRender')->will($this->returnValue(FALSE));

		$fileResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, array(), array(), '', FALSE);

		$createdRendererInstances = array(
			array($rendererClass1, $rendererObject1),
			array($rendererClass2, $rendererObject2),
		);

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
	public function getRendererReturnsCorrectInstance2() {

		$rendererRegistry = \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::getInstance();
		$rendererRegistry->registerRendererClass(\TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer::class);
		$rendererRegistry->registerRendererClass(\TYPO3\CMS\Core\Resource\Rendering\VideoTagRenderer::class);

		$fileResourceMock = $this->getMock(\TYPO3\CMS\Core\Resource\File::class, array(), array(), '', FALSE);
		$fileResourceMock->expects($this->any())->method('getMimeType')->will($this->returnValue('video/mp4'));


		$rendererRegistry->getRendererInstances();

		$renderer = $rendererRegistry->getRenderer($fileResourceMock);

		$this->assertInstanceOf(\TYPO3\CMS\Core\Resource\Rendering\VideoTagRenderer::class, $renderer);
	}
}

