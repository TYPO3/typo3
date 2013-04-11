<?php
namespace TYPO3\CMS\Core\Tests\Unit\FormProtection;

/***************************************************************
* Copyright notice
*
* (c) 2010-2013 Oliver Klee (typo3-coding@oliverklee.de)
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Testcase
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class BackendFormProtectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var $fixture \TYPO3\CMS\Core\FormProtection\BackendFormProtection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $fixture;

	/**
	 * Backup of current singleton instances
	 */
	protected $singletonInstances;

	/**
	 * Set up
	 */
	public function setUp() {
		$GLOBALS['BE_USER'] = $this->getMock(
			'TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication',
			array('getSessionData', 'setAndSaveSessionData')
		);
		$GLOBALS['BE_USER']->user['uid'] = 1;

		$this->fixture = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\FormProtection\BackendFormProtection',
			array('acquireLock', 'releaseLock')
		);

		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
	}

	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
		$this->fixture->__destruct();
	}

	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	////////////////////////////////////
	// Tests for the utility functions
	////////////////////////////////////

	/**
	 * @test
	 */
	public function getBackendUserReturnsInstanceOfBackendUserAuthenticationClass() {
		$this->assertInstanceOf(
			'TYPO3\\CMS\\Core\\Authentication\BackendUserAuthentication',
			$this->getBackendUser()
		);
	}

	//////////////////////////////////////////////////////////
	// Tests concerning the reading and saving of the tokens
	//////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function retrieveTokenReadsTokenFromSessionData() {
		$this->getBackendUser()
			->expects($this->once())
			->method('getSessionData')
			->with('formSessionToken')
			->will($this->returnValue(array()));
		$this->fixture->_call('retrieveSessionToken');
	}

	/**
	 * @test
	 */
	public function tokenFromSessionDataIsAvailableForValidateToken() {
		$sessionToken = '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd';
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = '42';

		$tokenId = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(
			$formName . $action . $formInstanceName . $sessionToken
		);

		$this->getBackendUser()
			->expects($this->atLeastOnce())
			->method('getSessionData')
			->with('formSessionToken')
			->will($this->returnValue($sessionToken));

		$this->fixture->_call('retrieveSessionToken');

		$this->assertTrue(
			$this->fixture->validateToken($tokenId, $formName, $action, $formInstanceName)
		);
	}

	/**
	 * @expectedException \UnexpectedValueException
	 * @test
	 */
	public function restoreSessionTokenFromRegistryThrowsExceptionIfSessionTokenIsEmpty() {
		/** @var $registryMock \TYPO3\CMS\Core\Registry */
		$registryMock = $this->getMock('TYPO3\\CMS\\Core\\Registry');
		$this->fixture->injectRegistry($registryMock);
		$this->fixture->setSessionTokenFromRegistry();
	}

	/**
	 * @test
	 */
	public function persistSessionTokenWritesTokenToSession() {
		$sessionToken = uniqid('test_');
		$this->fixture->_set('sessionToken', $sessionToken);
		$this->getBackendUser()
			->expects($this->once())
			->method('setAndSaveSessionData')
			->with('formSessionToken', $sessionToken);
		$this->fixture->persistSessionToken();
	}


	//////////////////////////////////////////////////
	// Tests concerning createValidationErrorMessage
	//////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function createValidationErrorMessageAddsFlashMessage() {
		/** @var $flashMessageServiceMock \TYPO3\CMS\Core\Messaging\FlashMessageService|\PHPUnit_Framework_MockObject_MockObject */
		$flashMessageServiceMock = $this->getMock('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessageService',
			$flashMessageServiceMock
		);
		$flashMessageQueueMock = $this->getMock(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessageQueue',
			array(),
			array(),
			'',
			FALSE
		);
		$flashMessageServiceMock
			->expects($this->once())
			->method('getMessageQueueByIdentifier')
			->will($this->returnValue($flashMessageQueueMock));

		$flashMessageQueueMock
			->expects($this->once())
			->method('enqueue')
			->with($this->isInstanceOf('TYPO3\\CMS\\Core\\Messaging\\FlashMessage'))
			->will($this->returnCallback(array($this, 'enqueueFlashMessageCallback')));

		$this->fixture->_call('createValidationErrorMessage');
	}

	/**
	 * @param \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage
	 */
	public function enqueueFlashMessageCallback(\TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage) {
		$this->assertEquals(\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, $flashMessage->getSeverity());
	}

	/**
	 * @test
	 */
	public function createValidationErrorMessageAddsErrorFlashMessageButNotInSessionInAjaxRequest() {
		/** @var $flashMessageServiceMock \TYPO3\CMS\Core\Messaging\FlashMessageService|\PHPUnit_Framework_MockObject_MockObject */
		$flashMessageServiceMock = $this->getMock('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessageService',
			$flashMessageServiceMock
		);
		$flashMessageQueueMock = $this->getMock(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessageQueue',
			array(),
			array(),
			'',
			FALSE
		);
		$flashMessageServiceMock
			->expects($this->once())
			->method('getMessageQueueByIdentifier')
			->will($this->returnValue($flashMessageQueueMock));

		$flashMessageQueueMock
			->expects($this->once())
			->method('enqueue')
			->with($this->isInstanceOf('TYPO3\\CMS\\Core\\Messaging\\FlashMessage'))
			->will($this->returnCallback(array($this, 'enqueueAjaxFlashMessageCallback')));

		$GLOBALS['TYPO3_AJAX'] = TRUE;
		$this->fixture->_call('createValidationErrorMessage');
	}

	/**
	 * @param \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage
	 */
	public function enqueueAjaxFlashMessageCallback(\TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage) {
		$this->assertFalse($flashMessage->isSessionMessage());
	}
}
?>