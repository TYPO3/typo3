<?php
namespace TYPO3\CMS\Core\Tests\Unit\Messaging;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Alexander Schnitzler <alex.schnitzler@typovision.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for the TYPO3\CMS\Core\Messaging\FlashMessageQueue class.
 *
 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
 */
class FlashMessageQueueTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject
	 */
	protected $flashMessageQueue;

	/**
	 * @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $frontendUser;

	public function setUp() {
		$this->frontendUser = $this->getMock('TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication', array('dummy'));
		$this->flashMessageQueue = $this->getAccessibleMock(
			'TYPO3\CMS\Core\Messaging\FlashMessageQueue',
			array('getUserByContext'),
			array('core.template.flashMessages')
		);

		$this->flashMessageQueue->expects($this->any())->method('getUserByContext')->will($this->returnValue($this->frontendUser));
	}

	/**
	 * @test
	 */
	public function userSessionInitiallyIsEmpty() {
		$this->assertSame(array(), $this->flashMessageQueue->_call('getFlashMessagesFromSession'));
	}

	/**
	 * @test
	 */
	public function enqueueTransientFlashMessageKeepsSessionEmpty() {
		$this->flashMessageQueue->enqueue(new \TYPO3\CMS\Core\Messaging\FlashMessage('Foo', 'Bar', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, FALSE));

		$this->assertSame(array(), $this->flashMessageQueue->_call('getFlashMessagesFromSession'));
	}

	/**
	 * @test
	 */
	public function enqueueSessionFlashMessageWritesSessionEntry() {
		$flashMessage = new \TYPO3\CMS\Core\Messaging\FlashMessage('Foo', 'Bar', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, TRUE);
		$this->flashMessageQueue->enqueue($flashMessage);

		$this->assertSame(array($flashMessage), $this->flashMessageQueue->_call('getFlashMessagesFromSession'));
	}

	/**
	 * @test
	 */
	public function getAllMessagesReturnsSessionFlashMessageAndTransientFlashMessage() {
		$flashMessage1 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, FALSE);
		$flashMessage2 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Session', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, TRUE);
		$this->flashMessageQueue->enqueue($flashMessage1);
		$this->flashMessageQueue->enqueue($flashMessage2);

		$this->assertCount(2, $this->flashMessageQueue->getAllMessages());
	}

	/**
	 * @test
	 */
	public function clearClearsTheQueue() {
		$flashMessage1 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, FALSE);
		$flashMessage2 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, FALSE);
		$this->flashMessageQueue->enqueue($flashMessage1);
		$this->flashMessageQueue->enqueue($flashMessage2);
		$this->flashMessageQueue->clear();

		$this->assertSame(0, $this->flashMessageQueue->count());
	}

	/**
	 * @test
	 */
	public function toArrayOnlyRespectsTransientFlashMessages() {
		$flashMessage1 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, FALSE);
		$flashMessage2 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, TRUE);
		$this->flashMessageQueue->enqueue($flashMessage1);
		$this->flashMessageQueue->enqueue($flashMessage2);

		$this->assertCount(1, $this->flashMessageQueue->toArray());
	}

	/**
	 * @test
	 */
	public function toArrayReturnsEmptyArrayWithForEmptyQueue() {
		$this->assertSame(array(), $this->flashMessageQueue->toArray());
	}

	/**
	 * @test
	 */
	public function getAllMessagesAndFlushClearsSessionStack() {
		$flashMessage = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, TRUE);
		$this->flashMessageQueue->enqueue($flashMessage);
		$this->flashMessageQueue->getAllMessagesAndFlush();

		/** @var $frontendUser \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication */
		$frontendUser = $this->flashMessageQueue->_call('getUserByContext');

		$this->assertNull($frontendUser->getSessionData('core.template.flashMessages'));
	}

}

?>