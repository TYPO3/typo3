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

require_once 'Fixtures/FormProtectionTesting.php';

/**
 * Testcase
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class AbstractFormProtectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting();
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
	public function constructionRetrievesToken() {
		$className = uniqid('FormProtection');
		eval('class ' . $className . ' extends \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting {' . 'public $tokenHasBeenRetrieved = FALSE; ' . 'protected function retrieveSessionToken() {' . '$this->tokenHasBeenRetrieved = TRUE;' . '}' . '}');
		$fixture = new $className();
		$this->assertTrue($fixture->tokenHasBeenRetrieved);
	}

	/**
	 * @test
	 */
	public function cleanMakesTokenInvalid() {
		$formName = 'foo';
		$tokenId = $this->fixture->generateToken($formName);
		$this->fixture->clean();
		$this->assertFalse($this->fixture->validateToken($tokenId, $formName));
	}

	/**
	 * @test
	 */
	public function cleanPersistsToken() {
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Tests\\Unit\\FormProtection\\Fixtures\\FormProtectionTesting', array('persistSessionToken'));
		$fixture->expects($this->once())->method('persistSessionToken');
		$fixture->clean();
	}

	///////////////////////////////////
	// Tests concerning generateToken
	///////////////////////////////////
	/**
	 * @test
	 */
	public function generateTokenFormForEmptyFormNameThrowsException() {
		$this->setExpectedException('InvalidArgumentException', '$formName must not be empty.');
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
		$this->assertRegexp('/^[0-9a-f]{40}$/', $this->fixture->generateToken('foo'));
	}

	/**
	 * @test
	 */
	public function generateTokenCalledTwoTimesWithSameParametersReturnsSameTokens() {
		$this->assertEquals($this->fixture->generateToken('foo', 'edit', 'bar'), $this->fixture->generateToken('foo', 'edit', 'bar'));
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
		$this->assertTrue($this->fixture->validateToken($this->fixture->generateToken($formName, $action, $formInstanceName), $formName, $action, $formInstanceName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithDataFromGenerateTokenWithMissingActionAndFormInstanceNameReturnsTrue() {
		$formName = 'foo';
		$this->assertTrue($this->fixture->validateToken($this->fixture->generateToken($formName), $formName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithValidDataCalledTwoTimesReturnsTrueOnSecondCall() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$tokenId = $this->fixture->generateToken($formName, $action, $formInstanceName);
		$this->fixture->validateToken($tokenId, $formName, $action, $formInstanceName);
		$this->assertTrue($this->fixture->validateToken($tokenId, $formName, $action, $formInstanceName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingTokenIdReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$this->fixture->generateToken($formName, $action, $formInstanceName);
		$this->assertFalse($this->fixture->validateToken('Hello world!', $formName, $action, $formInstanceName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingFormNameReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$tokenId = $this->fixture->generateToken($formName, $action, $formInstanceName);
		$this->assertFalse($this->fixture->validateToken($tokenId, 'espresso', $action, $formInstanceName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingActionReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$tokenId = $this->fixture->generateToken($formName, $action, $formInstanceName);
		$this->assertFalse($this->fixture->validateToken($tokenId, $formName, 'delete', $formInstanceName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingFormInstanceNameReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$tokenId = $this->fixture->generateToken($formName, $action, $formInstanceName);
		$this->assertFalse($this->fixture->validateToken($tokenId, $formName, $action, 'beer'));
	}

	/**
	 * @test
	 */
	public function validateTokenForValidTokenNotCallsCreateValidationErrorMessage() {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting $fixture */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Tests\\Unit\\FormProtection\\Fixtures\\FormProtectionTesting', array('createValidationErrorMessage'));
		$fixture->expects($this->never())->method('createValidationErrorMessage');
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$token = $fixture->generateToken($formName, $action, $formInstanceName);
		$fixture->validateToken($token, $formName, $action, $formInstanceName);
		$fixture->__destruct();
	}

	/**
	 * @test
	 */
	public function validateTokenForInvalidTokenCallsCreateValidationErrorMessage() {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting $fixture */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Tests\\Unit\\FormProtection\\Fixtures\\FormProtectionTesting', array('createValidationErrorMessage'));
		$fixture->expects($this->once())->method('createValidationErrorMessage');
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$fixture->generateToken($formName, $action, $formInstanceName);
		$fixture->validateToken('an invalid token ...', $formName, $action, $formInstanceName);
		$fixture->__destruct();
	}

	/**
	 * @test
	 */
	public function validateTokenForInvalidFormNameCallsCreateValidationErrorMessage() {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting $fixture */
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\Tests\\Unit\\FormProtection\\Fixtures\\FormProtectionTesting', array('createValidationErrorMessage'));
		$fixture->expects($this->once())->method('createValidationErrorMessage');
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$token = $fixture->generateToken($formName, $action, $formInstanceName);
		$fixture->validateToken($token, 'another form name', $action, $formInstanceName);
		$fixture->__destruct();
	}

}

?>