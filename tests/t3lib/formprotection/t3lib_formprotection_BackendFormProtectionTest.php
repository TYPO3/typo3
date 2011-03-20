<?php
/***************************************************************
* Copyright notice
*
* (c) 2010-2011 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for the t3lib_formprotection_BackendFormProtection class.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class t3lib_formprotection_BackendFormProtectionTest extends tx_phpunit_testcase {
	/**
	 * a backup of the current BE user
	 *
	 * @var t3lib_beUserAuth
	 */
	private $backEndUserBackup = NULL;

	/**
	 * @var t3lib_formprotection_BackendFormProtection
	 */
	private $fixture;

	public function setUp() {
		$this->backEndUserBackup = $GLOBALS['BE_USER'];
		$GLOBALS['BE_USER'] = $this->getMock(
			't3lib_beUserAuth',
			array('getSessionData', 'setAndSaveSessionData')
		);
		$GLOBALS['BE_USER']->user['uid'] = 1;

		$className = $this->createAccessibleProxyClass();
		$this->fixture = $this->getMock($className, array('acquireLock', 'releaseLock'));
	}

	public function tearDown() {
		$this->fixture->__destruct();
		unset($this->fixture);

		$GLOBALS['BE_USER'] = $this->backEndUserBackup;

		t3lib_FlashMessageQueue::getAllMessagesAndFlush();
	}


	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * Creates a subclass t3lib_formprotection_BackendFormProtection with retrieveTokens made
	 * public.
	 *
	 * @return string the name of the created class, will not be empty
	 */
	private function createAccessibleProxyClass() {
		$className = 't3lib_formprotection_BackendFormProtectionAccessibleProxy';
		if (!class_exists($className)) {
			eval(
				'class ' . $className . ' extends t3lib_formprotection_BackendFormProtection {' .
				'  public function createValidationErrorMessage() {' .
				'    parent::createValidationErrorMessage();' .
				'  }' .
				'  public function retrieveSessionToken() {' .
				'    return parent::retrieveSessionToken();' .
				'  }' .
				'  public function setSessionToken($sessionToken) {' .
				'    $this->sessionToken = $sessionToken;' .
				'  }' .
				'}'
			);
		}

		return $className;
	}

	/**
	 * Mock session methods in t3lib_beUserAuth
	 *
	 * @return t3lib_beUserAuth Instance of BE_USER object with mocked session storage methods
	 */
	private function createBackendUserSessionStorageStub() {
		$className = 't3lib_beUserAuthMocked';
		if (!class_exists($className)) {
			eval(
				'class ' . $className . ' extends t3lib_beUserAuth {' .
				'  protected $session=array();' .
				'  public function getSessionData($key) {' .
				'    return $this->session[$key];' .
				'  }' .
				'  public function setAndSaveSessionData($key,$data) {' .
				'    $this->session[$key] = $data;' .
				'  }' .
				'}'
			);
		}

		return $this->getMock($className, array('foo'));// $className;
	}

	////////////////////////////////////
	// Tests for the utility functions
	////////////////////////////////////

	/**
	 * @test
	 */
	public function createAccessibleProxyCreatesBackendFormProtectionSubclass() {
		$className = $this->createAccessibleProxyClass();

		$this->assertTrue(
			(new $className()) instanceof t3lib_formprotection_BackendFormProtection
		);
	}

	/**
	 * @test
	 */
	public function createBackendUserSessionStorageStubWorkProperly() {
		$GLOBALS['BE_USER'] = $this->createBackendUserSessionStorageStub();

		$allTokens = array(
			'12345678' => array(
					'formName' => 'foo',
					'action' => 'edit',
					'formInstanceName' => '42'
				),
		);

		$GLOBALS['BE_USER']->setAndSaveSessionData('tokens', $allTokens);

		$this->assertEquals($GLOBALS['BE_USER']->getSessionData('tokens'), $allTokens);
	}


	//////////////////////////////////////////////////////////
	// Tests concerning the reading and saving of the tokens
	//////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function retrieveTokenReadsTokenFromSessionData() {
		$GLOBALS['BE_USER']->expects($this->once())->method('getSessionData')
			->with('formSessionToken')->will($this->returnValue(array()));

		$this->fixture->retrieveSessionToken();
	}

	/**
	 * @test
	 */
	public function tokenFromSessionDataIsAvailableForValidateToken() {
		$sessionToken = '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd';
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = '42';

		$tokenId = t3lib_div::hmac($formName . $action . $formInstanceName . $sessionToken);

		$GLOBALS['BE_USER']->expects($this->atLeastOnce())->method('getSessionData')
			->with('formSessionToken')
			->will($this->returnValue($sessionToken));

		$this->fixture->retrieveSessionToken();

		$this->assertTrue(
			$this->fixture->validateToken($tokenId, $formName, $action, $formInstanceName)
		);
	}

	/**
	 * @test
	 */
	public function persistSessionTokenWritesTokenToSession() {
		$sessionToken = '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd';
		$this->fixture->setSessionToken($sessionToken);

		$GLOBALS['BE_USER']->expects($this->once())
			->method('setAndSaveSessionData')->with('formSessionToken', $sessionToken);

		$this->fixture->persistSessionToken();
	}


	//////////////////////////////////////////////////
	// Tests concerning createValidationErrorMessage
	//////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function createValidationErrorMessageAddsErrorFlashMessage() {
		$GLOBALS['BE_USER'] = $this->createBackendUserSessionStorageStub();
		$this->fixture->createValidationErrorMessage();

		$messages = t3lib_FlashMessageQueue::getAllMessagesAndFlush();
		$this->assertContains(
			$GLOBALS['LANG']->sL(
				'LLL:EXT:lang/locallang_core.xml:error.formProtection.tokenInvalid'
			),
			$messages[0]->render()
		);
	}
}
?>
