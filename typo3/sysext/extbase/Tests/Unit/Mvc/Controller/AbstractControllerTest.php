<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

/**
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
 * Test case
 */
class AbstractControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function constructResolvesExtensionnameFromOldStyle() {
		$className = $this->getUniqueId('Tx_Extbase_Tests_Fixtures_Controller');
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
		$className = $this->getUniqueId('DummyController');
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
			array($this->getUniqueId('identifier_'))
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
