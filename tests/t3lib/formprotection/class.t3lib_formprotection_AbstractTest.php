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

require_once('fixtures/class.t3lib_formprotection_testing.php');

/**
 * Testcase for the t3lib_formprotection_Abstract class.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class t3lib_formprotection_AbstractTest extends tx_phpunit_testcase {
	/**
	 * @var t3lib_formProtection_Testing
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new t3lib_formProtection_Testing();
	}

	public function tearDown() {
		$this->fixture->__destruct();
		unset($this->fixture);
	}


	/////////////////////////////////////////
	// Tests concerning the basic functions
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function constructionRetrievesTokens() {
		$className = uniqid('t3lib_formProtection');
		eval(
			'class ' . $className . ' extends t3lib_formProtection_Testing {' .
				'public $tokensHaveBeenRetrieved = FALSE; ' .
				'protected function retrieveTokens() {' .
				'$this->tokensHaveBeenRetrieved = TRUE;' .
				'}' .
			'}'
		);

		$fixture = new $className();

		$this->assertTrue(
			$fixture->tokensHaveBeenRetrieved
		);
	}

	/**
	 * @test
	 */
	public function cleanMakesTokenInvalid() {
		$formName = 'foo';
		$tokenId = $this->fixture->generateToken($formName);

		$this->fixture->clean();

		$this->assertFalse(
			$this->fixture->validateToken($tokenId, $formName)
		);
	}

	/**
	 * @test
	 */
	public function cleanPersistsTokens() {
		$fixture = $this->getMock(
			't3lib_formProtection_Testing', array('persistTokens')
		);
		$fixture->expects($this->once())->method('persistTokens');

		$fixture->clean();
	}


	///////////////////////////////////
	// Tests concerning generateToken
	///////////////////////////////////

	/**
	 * @test
	 */
	public function generateTokenFormForEmptyFormNameThrowsException() {
		$this->setExpectedException(
			'InvalidArgumentException', '$formName must not be empty.'
		);

		$this->fixture->generateToken('', 'edit', 'bar');
	}

	/**
	 * @test
	 */
	public function generateTokenFormForEmptyActionNotThrowsException() {
		$this->fixture->generateToken('foo', '', '42');
	}

	/**
	 * @test
	 */
	public function generateTokenFormForEmptyFormInstanceNameNotThrowsException() {
		$this->fixture->generateToken('foo', 'edit', '');
	}

	/**
	 * @test
	 */
	public function generateTokenFormForOmittedActionAndFormInstanceNameNotThrowsException() {
		$this->fixture->generateToken('foo');
	}

	/**
	 * @test
	 */
	public function generateTokenReturns32CharacterHexToken() {
		$this->assertRegexp(
			'/^[0-9a-f]{32}$/',
			$this->fixture->generateToken('foo')
		);
	}

	/**
	 * @test
	 */
	public function generateTokenCalledTwoTimesWithSameParametersReturnsDifferentTokens() {
		$this->assertNotEquals(
			$this->fixture->generateToken('foo', 'edit', 'bar'),
			$this->fixture->generateToken('foo', 'edit', 'bar')
		);
	}

	/**
	 * @test
	 */
	public function generatingTooManyTokensInvalidatesOldestToken() {
		$this->fixture->setMaximumNumberOfTokens(2);

		$formName = 'foo';

		$token1 = $this->fixture->generateToken($formName);
		$token2 = $this->fixture->generateToken($formName);
		$token3 = $this->fixture->generateToken($formName);

		$this->assertFalse(
			$this->fixture->validateToken($token1, $formName)
		);
	}

	/**
	 * @test
	 */
	public function generatingTooManyTokensNotInvalidatesNewestToken() {
		$this->fixture->setMaximumNumberOfTokens(2);

		$formName = 'foo';
		$formInstanceName = 'bar';

		$token1 = $this->fixture->generateToken($formName);
		$token2 = $this->fixture->generateToken($formName);
		$token3 = $this->fixture->generateToken($formName);

		$this->assertTrue(
			$this->fixture->validateToken($token3, $formName)
		);
	}

	/**
	 * @test
	 */
	public function generatingTooManyTokensNotInvalidatesTokenInTheMiddle() {
		$this->fixture->setMaximumNumberOfTokens(2);

		$formName = 'foo';
		$formInstanceName = 'bar';

		$token1 = $this->fixture->generateToken($formName);
		$token2 = $this->fixture->generateToken($formName);
		$token3 = $this->fixture->generateToken($formName);

		$this->assertTrue(
			$this->fixture->validateToken($token2, $formName)
		);
	}


	///////////////////////////////////
	// Tests concerning validateToken
	///////////////////////////////////

	/**
	 * @test
	 */
	public function validateTokenWithFourEmptyParametersNotThrowsException() {
		$this->fixture->validateToken('', '', '', '');
	}

	/**
	 * @test
	 */
	public function validateTokenWithTwoEmptyAndTwoMissingParametersNotThrowsException() {
		$this->fixture->validateToken('', '');
	}

	/**
	 * @test
	 */
	public function validateTokenWithDataFromGenerateTokenWithFormInstanceNameReturnsTrue() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';

		$this->assertTrue(
			$this->fixture->validateToken(
				$this->fixture->generateToken($formName, $action, $formInstanceName),
				$formName,
				$action,
				$formInstanceName
			)
		);
	}

	/**
	 * @test
	 */
	public function validateTokenWithDataFromGenerateTokenWithMissingActionAndFormInstanceNameReturnsTrue() {
		$formName = 'foo';

		$this->assertTrue(
			$this->fixture->validateToken(
				$this->fixture->generateToken($formName), $formName
			)
		);
	}

	/**
	 * @test
	 */
	public function validateTokenWithValidDataDropsToken() {
		$formName = 'foo';

		$fixture = $this->getMock(
			't3lib_formProtection_Testing', array('dropToken')
		);

		$tokenId = $fixture->generateToken($formName);
		$fixture->expects($this->once())->method('dropToken')
			->with($tokenId);

		$fixture->validateToken($tokenId, $formName);
	}

	/**
	 * @test
	 */
	public function validateTokenWithValidDataCalledTwoTimesReturnsFalseOnSecondCall() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';

		$tokenId = $this->fixture->generateToken($formName, $action, $formInstanceName);

		$this->fixture->validateToken($tokenId, $formName, $action, $formInstanceName);

		$this->assertFalse(
			$this->fixture->validateToken($tokenId, $formName, $action, $formInstanceName)
		);
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingTokenIdReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';

		$this->fixture->generateToken($formName, $action, $formInstanceName);

		$this->assertFalse(
			$this->fixture->validateToken(
				'Hello world!', $formName, $action, $formInstanceName
			)
		);
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingFormNameReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';

		$tokenId = $this->fixture->generateToken($formName, $action, $formInstanceName);

		$this->assertFalse(
			$this->fixture->validateToken(
				$tokenId, 'espresso', $action, $formInstanceName
			)
		);
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingActionReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';

		$tokenId = $this->fixture->generateToken($formName, $action, $formInstanceName);

		$this->assertFalse(
			$this->fixture->validateToken(
				$tokenId, $formName, 'delete', $formInstanceName
			)
		);
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingFormInstanceNameReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';

		$tokenId = $this->fixture->generateToken($formName, $action, $formInstanceName);

		$this->assertFalse(
			$this->fixture->validateToken(
				$tokenId, $formName, $action, 'beer'
			)
		);
	}

	/**
	 * @test
	 */
	public function validateTokenWithTwoTokensForSameFormNameAndActionAndFormInstanceNameReturnsTrueForBoth() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';

		$tokenId1 = $this->fixture->generateToken($formName, $action, $formInstanceName);
		$tokenId2 = $this->fixture->generateToken($formName, $action, $formInstanceName);

		$this->assertTrue(
			$this->fixture->validateToken(
				$tokenId1, $formName, $action, $formInstanceName
			)
		);
		$this->assertTrue(
			$this->fixture->validateToken(
				$tokenId2, $formName, $action, $formInstanceName
			)
		);
	}

	/**
	 * @test
	 */
	public function validateTokenWithTwoTokensForSameFormNameAndActionAndFormInstanceNameCalledInReverseOrderReturnsTrueForBoth() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';

		$tokenId1 = $this->fixture->generateToken($formName, $action, $formInstanceName);
		$tokenId2 = $this->fixture->generateToken($formName, $action, $formInstanceName);

		$this->assertTrue(
			$this->fixture->validateToken(
				$tokenId2, $formName, $action, $formInstanceName
			)
		);
		$this->assertTrue(
			$this->fixture->validateToken(
				$tokenId1, $formName, $action, $formInstanceName
			)
		);
	}

	/**
	 * @test
	 */
	public function validateTokenForValidTokenNotCallsCreateValidationErrorMessage() {
		$fixture = $this->getMock(
			't3lib_formProtection_Testing', array('createValidationErrorMessage')
		);
		$fixture->expects($this->never())->method('createValidationErrorMessage');

		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';

		$token = $fixture->generateToken($formName, $action, $formInstanceName);
		$fixture->validateToken(
			$token, $formName, $action, $formInstanceName
		);

		$fixture->__destruct();
	}

	/**
	 * @test
	 */
	public function validateTokenForInvalidTokenCallsCreateValidationErrorMessage() {
		$fixture = $this->getMock(
			't3lib_formProtection_Testing', array('createValidationErrorMessage')
		);
		$fixture->expects($this->once())->method('createValidationErrorMessage');

		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';

		$fixture->generateToken($formName, $action, $formInstanceName);
		$fixture->validateToken(
			'an invalid token ...', $formName, $action, $formInstanceName
		);

		$fixture->__destruct();
	}

	/**
	 * @test
	 */
	public function validateTokenForInvalidFormNameCallsCreateValidationErrorMessage() {
		$fixture = $this->getMock(
			't3lib_formProtection_Testing', array('createValidationErrorMessage')
		);
		$fixture->expects($this->once())->method('createValidationErrorMessage');

		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';

		$token = $fixture->generateToken($formName, $action, $formInstanceName);
		$fixture->validateToken(
			$token, 'another form name', $action, $formInstanceName
		);

		$fixture->__destruct();
	}
}
?>