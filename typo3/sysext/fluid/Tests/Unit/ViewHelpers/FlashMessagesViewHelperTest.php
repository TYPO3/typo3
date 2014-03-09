<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for FlashMessagesViewHelper
 */
class FlashMessagesViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper| \PHPUnit_Framework_MockObject_MockObject |\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $viewHelper;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder
	 */
	protected $mockTagBuilder;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 */
	public function setUp() {
		/** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue|\PHPUnit_Framework_MockObject_MockObject $mockFlashMessagingQueue */
		$mockFlashMessagingQueue = $this->getMock('TYPO3\\CMS\\Core\\Messaging\\FlashMessageQueue', array('getAllMessagesAndFlush'), array('foo'));
		$mockFlashMessagingQueue->expects($this->once())->method('getAllMessagesAndFlush')->will($this->returnValue(array()));

		$mockControllerContext = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerContext', array(), array(), '', FALSE);
		$mockControllerContext->expects($this->any())->method('getFlashMessageQueue')->will($this->returnValue($mockFlashMessagingQueue));

		$this->mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder');
		$this->viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\FlashMessagesViewHelper', array('dummy'));
		$this->viewHelper->_set('controllerContext', $mockControllerContext);
		$this->viewHelper->_set('tag', $this->mockTagBuilder);
		$this->viewHelper->initialize();
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyStringIfNoFlashMessagesAreInQueue() {
		$this->assertEmpty($this->viewHelper->render());
	}
}
