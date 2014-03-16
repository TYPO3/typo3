<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Stefan Neufeind <info@speedpartner.de>
 *  (c) 2013 Steffen Müller <typo3@t3node.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 */
class AbstractControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function constructResolvesExtensionnameFromOldStyle() {
		$className = uniqid('Tx_Extbase_Tests_Fixtures_Controller');
		eval('class ' . $className . ' extends \\TYPO3\\CMS\\Extbase\\Mvc\\Controller\\AbstractController { function getExtensionName() { return $this->extensionName; } }');
		$mockController = new $className();
		$expectedResult = 'Extbase';
		$actualResult = $mockController->getExtensionName();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function constructResolvesExtensionnameFromNamespaced() {
		$className = uniqid('DummyController');
		eval('namespace ' . __NAMESPACE__ . '; class ' . $className . ' extends \\TYPO3\\CMS\\Extbase\\Mvc\\Controller\\AbstractController { function getExtensionName() { return $this->extensionName; } }');
		$classNameNamespaced = __NAMESPACE__ . '\\' . $className;
		$mockController = new $classNameNamespaced();
		$expectedResult = 'Extbase';
		$actualResult = $mockController->getExtensionName();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @return array
	 */
	public function addFlashMessageDataProvider() {
		return array(
			array(
				new \TYPO3\CMS\Core\Messaging\FlashMessage('Simple Message'),
				'Simple Message',
				'',
				\TYPO3\CMS\Core\Messaging\FlashMessage::OK,
				FALSE
			),
			array(
				new \TYPO3\CMS\Core\Messaging\FlashMessage('Some OK', 'Message Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, TRUE),
				'Some OK',
				'Message Title',
				\TYPO3\CMS\Core\Messaging\FlashMessage::OK,
				TRUE
			),
			array(
				new \TYPO3\CMS\Core\Messaging\FlashMessage('Some Info', 'Message Title', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO, TRUE),
				'Some Info',
				'Message Title',
				\TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
				TRUE
			),
			array(
				new \TYPO3\CMS\Core\Messaging\FlashMessage('Some Notice', 'Message Title', \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE, TRUE),
				'Some Notice',
				'Message Title',
				\TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE,
				TRUE
			),

			array(
				new \TYPO3\CMS\Core\Messaging\FlashMessage('Some Warning', 'Message Title', \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING, TRUE),
				'Some Warning',
				'Message Title',
				\TYPO3\CMS\Core\Messaging\FlashMessage::WARNING,
				TRUE
			),
			array(
				new \TYPO3\CMS\Core\Messaging\FlashMessage('Some Error', 'Message Title', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, TRUE),
				'Some Error',
				'Message Title',
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
				TRUE
			)
		);
	}

	/**
	 * @test
	 * @dataProvider addFlashMessageDataProvider
	 */
	public function addFlashMessageAddsFlashMessageObjectToFlashMessageQueue($expectedMessage, $messageBody, $messageTitle = '', $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK, $storeInSession = TRUE) {
		$flashMessageQueue = $this->getMock(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessageQueue',
			array('enqueue'),
			array(),
			'',
			FALSE
		);
		$flashMessageQueue->expects($this->once())->method('enqueue')->with($this->equalTo($expectedMessage));

		$controllerContext = $this->getMock(
			'\\TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerContext',
			array('getFlashMessageQueue')
		);
		$controllerContext->expects($this->once())->method('getFlashMessageQueue')->will($this->returnValue($flashMessageQueue));

		$controller = $this->getMockForAbstractClass('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\AbstractController',
			array(),
			'',
			FALSE,
			TRUE,
			TRUE,
			array('dummy')
		);
		$this->inject($controller, 'controllerContext', $controllerContext);

		$controller->addFlashMessage($messageBody, $messageTitle, $severity, $storeInSession);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function addFlashMessageThrowsExceptionOnInvalidMessageBody() {
		$controller = $this->getMockForAbstractClass('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\AbstractController',
			array(),
			'',
			FALSE,
			TRUE,
			TRUE,
			array('dummy')
		);

		$controller->addFlashMessage(new \stdClass());
	}
}
