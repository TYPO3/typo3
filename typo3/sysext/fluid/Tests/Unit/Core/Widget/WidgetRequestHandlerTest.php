<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Widget;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is backported from the TYPO3 Flow package "TYPO3.Fluid".
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
 * Test case
 */
class WidgetRequestHandlerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetRequestHandler
	 */
	protected $widgetRequestHandler;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->widgetRequestHandler = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequestHandler', array('dummy'), array(), '', FALSE);
	}

	/**
	 * @test
	 */
	public function canHandleRequestReturnsTrueIfCorrectGetParameterIsSet() {
		$_GET['fluid-widget-id'] = 123;
		$this->assertTrue($this->widgetRequestHandler->canHandleRequest());
	}

	/**
	 * @test
	 */
	public function canHandleRequestReturnsFalsefGetParameterIsNotSet() {
		$_GET['some-other-id'] = 123;
		$this->assertFalse($this->widgetRequestHandler->canHandleRequest());
	}

	/**
	 * @test
	 */
	public function priorityIsHigherThanDefaultRequestHandler() {
		$defaultWebRequestHandler = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Web\\AbstractRequestHandler', array('handleRequest'), array(), '', FALSE);
		$this->assertTrue($this->widgetRequestHandler->getPriority() > $defaultWebRequestHandler->getPriority());
	}
}
