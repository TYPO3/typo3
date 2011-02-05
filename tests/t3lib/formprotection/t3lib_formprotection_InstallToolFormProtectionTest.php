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

require_once(t3lib_extMgm::extPath('install') . 'mod/class.tx_install.php');

/**
 * Testcase for the t3lib_formprotection_InstallToolFormProtection class.
 *
 * $Id$
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class t3lib_formprotection_InstallToolFormProtectionTest extends tx_phpunit_testcase {
	/**
	 * @var t3lib_formprotection_InstallToolFormProtection
	 */
	private $fixture;

	/**
	 * backup of $_SESSION
	 *
	 * @var array
	 */
	private $sessionBackup;

	public function setUp() {
		$this->sessionBackup = $_SESSION;

		$className = $this->createAccessibleProxyClass();
		$this->fixture = new $className();
	}

	public function tearDown() {
		$this->fixture->__destruct();
		unset($this->fixture);

		t3lib_FlashMessageQueue::getAllMessagesAndFlush();

		$_SESSION = $this->sessionBackup;
	}


	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * Creates a subclass t3lib_formprotection_InstallToolFormProtection with retrieveTokens made
	 * public.
	 *
	 * @return string the name of the created class, will not be empty
	 */
	private function createAccessibleProxyClass() {
		$className = 't3lib_formprotection_InstallToolFormProtectionAccessibleProxy';
		if (!class_exists($className)) {
			eval(
				'class ' . $className . ' extends t3lib_formprotection_InstallToolFormProtection {' .
				'  public function createValidationErrorMessage() {' .
				'    parent::createValidationErrorMessage();' .
				'  }' .
				'  public function retrieveTokens() {' .
				'    return $this->tokens = parent::retrieveTokens();' .
				'  }' .
				'}'
			);
		}

		return $className;
	}


	////////////////////////////////////
	// Tests for the utility functions
	////////////////////////////////////

	/**
	 * @test
	 */
	public function createAccessibleProxyCreatesInstallToolFormProtectionSubclass() {
		$className = $this->createAccessibleProxyClass();

		$this->assertTrue(
			(new $className()) instanceof t3lib_formprotection_InstallToolFormProtection
		);
	}


	//////////////////////////////////////////////////////////
	// Tests concerning the reading and saving of the tokens
	//////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function tokensFromSessionDataAreAvailableForValidateToken() {
		$tokenId = '51a655b55c54d54e5454c5f521f6552a';
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = '42';

		$_SESSION['installToolFormTokens'] = array(
			$tokenId => array(
				'formName' => $formName,
				'action' => $action,
				'formInstanceName' => $formInstanceName,
			),
		);

		$this->fixture->retrieveTokens();

		$this->assertTrue(
			$this->fixture->validateToken(
				$tokenId, $formName, $action,  $formInstanceName
			)
		);
	}

	/**
	 * @test
	 */
	public function persistTokensWritesTokensToSession() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = '42';

		$tokenId = $this->fixture->generateToken(
			$formName, $action, $formInstanceName
		);

		$this->fixture->persistTokens();

		$this->assertEquals(
			array(
				$tokenId => array(
						'formName' => $formName,
						'action' => $action,
						'formInstanceName' => $formInstanceName,
					),
			),
			$_SESSION['installToolFormTokens']
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
			'tx_install', array('addErrorMessage'), array(), '', FALSE
		);
		$installTool->expects($this->once())->method('addErrorMessage')
			->with(
				'Validating the security token of this form has failed. ' .
					'Please reload the form and submit it again.'
			);
		$this->fixture->injectInstallTool($installTool);

		$this->fixture->createValidationErrorMessage();
	}
}
?>
