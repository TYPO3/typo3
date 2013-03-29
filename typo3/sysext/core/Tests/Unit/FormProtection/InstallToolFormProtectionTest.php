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
class InstallToolFormProtectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $fixture;

	/**
	 * @var array Backup of $_SESSION
	 */
	protected $sessionBackup;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->sessionBackup = $_SESSION;

		$this->fixture = $this->getAccessibleMock(
			'TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection',
			array('dummy')
		);
	}

	/**
	 * Tear down
	 */
	public function tearDown() {
		$this->fixture->__destruct();
		$_SESSION = $this->sessionBackup;
	}

	//////////////////////////////////////////////////////////
	// Tests concerning the reading and saving of the tokens
	//////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function tokenFromSessionDataIsAvailableForValidateToken() {
		$sessionToken = '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd';
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = '42';

		$tokenId = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac($formName . $action . $formInstanceName . $sessionToken);

		$_SESSION['installToolFormToken'] = $sessionToken;

		$this->fixture->_call('retrieveSessionToken');

		$this->assertTrue(
			$this->fixture->validateToken($tokenId, $formName, $action, $formInstanceName)
		);
	}

	/**
	 * @test
	 */
	public function persistSessionTokenWritesTokensToSession() {
		$_SESSION['installToolFormToken'] = 'foo';

		$this->fixture->_set('sessionToken', '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd');

		$this->fixture->persistSessionToken();

		$this->assertEquals(
			'881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd',
			$_SESSION['installToolFormToken']
		);
	}


	//////////////////////////////////////////////////
	// Tests concerning createValidationErrorMessage
	//////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function createValidationErrorMessageAddsErrorMessage() {
		$installTool = $this->getMock(
			'TYPO3\\CMS\\Install\\Installer', array('addErrorMessage'), array(), '', FALSE
		);
		$installTool->expects($this->once())->method('addErrorMessage')
			->with(
				'Validating the security token of this form has failed. ' .
					'Please reload the form and submit it again.'
			);
		$this->fixture->injectInstallTool($installTool);

		$this->fixture->_call('createValidationErrorMessage');
	}
}
?>