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
class AbstractFormProtectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting
	 */
	protected $subject;

	public function setUp() {
		$this->subject = new \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting();
	}

	/////////////////////////////////////////
	// Tests concerning the basic functions
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function generateTokenRetrievesTokenOnce() {
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Tests\\Unit\\FormProtection\\Fixtures\\FormProtectionTesting', array('retrieveSessionToken'));
		$subject->expects($this->once())->method('retrieveSessionToken')->will($this->returnValue('token'));
		$subject->generateToken('foo');
		$subject->generateToken('foo');
	}

	/**
	 * @test
	 */
	public function validateTokenRetrievesTokenOnce() {
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Tests\\Unit\\FormProtection\\Fixtures\\FormProtectionTesting', array('retrieveSessionToken'));
		$subject->expects($this->once())->method('retrieveSessionToken')->will($this->returnValue('token'));
		$subject->validateToken('foo', 'bar');
		$subject->validateToken('foo', 'bar');
	}

	/**
	 * @test
	 */
	public function cleanMakesTokenInvalid() {
		$formName = 'foo';
		$tokenId = $this->subject->generateToken($formName);
		$this->subject->clean();
		$this->assertFalse($this->subject->validateToken($tokenId, $formName));
	}

	/**
	 * @test
	 */
	public function cleanPersistsToken() {
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Tests\\Unit\\FormProtection\\Fixtures\\FormProtectionTesting', array('persistSessionToken'));
		$subject->expects($this->once())->method('persistSessionToken');
		$subject->clean();
	}

	///////////////////////////////////
	// Tests concerning generateToken
	///////////////////////////////////
	/**
	 * @test
	 */
	public function generateTokenFormForEmptyFormNameThrowsException() {
		$this->setExpectedException('InvalidArgumentException', '$formName must not be empty.');
		$this->subject->generateToken('', 'edit', 'bar');
	}

	/**
	 * @test
	 */
	public function generateTokenFormForEmptyActionNotThrowsException() {
		$this->subject->generateToken('foo', '', '42');
	}

	/**
	 * @test
	 */
	public function generateTokenFormForEmptyFormInstanceNameNotThrowsException() {
		$this->subject->generateToken('foo', 'edit', '');
	}

	/**
	 * @test
	 */
	public function generateTokenFormForOmittedActionAndFormInstanceNameNotThrowsException() {
		$this->subject->generateToken('foo');
	}

	/**
	 * @test
	 */
	public function generateTokenReturns32CharacterHexToken() {
		$this->assertRegexp('/^[0-9a-f]{40}$/', $this->subject->generateToken('foo'));
	}

	/**
	 * @test
	 */
	public function generateTokenCalledTwoTimesWithSameParametersReturnsSameTokens() {
		$this->assertEquals($this->subject->generateToken('foo', 'edit', 'bar'), $this->subject->generateToken('foo', 'edit', 'bar'));
	}

	///////////////////////////////////
	// Tests concerning validateToken
	///////////////////////////////////
	/**
	 * @test
	 */
	public function validateTokenWithFourEmptyParametersNotThrowsException() {
		$this->subject->validateToken('', '', '', '');
	}

	/**
	 * @test
	 */
	public function validateTokenWithTwoEmptyAndTwoMissingParametersNotThrowsException() {
		$this->subject->validateToken('', '');
	}

	/**
	 * @test
	 */
	public function validateTokenWithDataFromGenerateTokenWithFormInstanceNameReturnsTrue() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$this->assertTrue($this->subject->validateToken($this->subject->generateToken($formName, $action, $formInstanceName), $formName, $action, $formInstanceName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithDataFromGenerateTokenWithMissingActionAndFormInstanceNameReturnsTrue() {
		$formName = 'foo';
		$this->assertTrue($this->subject->validateToken($this->subject->generateToken($formName), $formName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithValidDataCalledTwoTimesReturnsTrueOnSecondCall() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$tokenId = $this->subject->generateToken($formName, $action, $formInstanceName);
		$this->subject->validateToken($tokenId, $formName, $action, $formInstanceName);
		$this->assertTrue($this->subject->validateToken($tokenId, $formName, $action, $formInstanceName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingTokenIdReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$this->subject->generateToken($formName, $action, $formInstanceName);
		$this->assertFalse($this->subject->validateToken('Hello world!', $formName, $action, $formInstanceName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingFormNameReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$tokenId = $this->subject->generateToken($formName, $action, $formInstanceName);
		$this->assertFalse($this->subject->validateToken($tokenId, 'espresso', $action, $formInstanceName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingActionReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$tokenId = $this->subject->generateToken($formName, $action, $formInstanceName);
		$this->assertFalse($this->subject->validateToken($tokenId, $formName, 'delete', $formInstanceName));
	}

	/**
	 * @test
	 */
	public function validateTokenWithMismatchingFormInstanceNameReturnsFalse() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$tokenId = $this->subject->generateToken($formName, $action, $formInstanceName);
		$this->assertFalse($this->subject->validateToken($tokenId, $formName, $action, 'beer'));
	}

	/**
	 * @test
	 */
	public function validateTokenForValidTokenNotCallsCreateValidationErrorMessage() {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting $subject */
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Tests\\Unit\\FormProtection\\Fixtures\\FormProtectionTesting', array('createValidationErrorMessage'));
		$subject->expects($this->never())->method('createValidationErrorMessage');
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$token = $subject->generateToken($formName, $action, $formInstanceName);
		$subject->validateToken($token, $formName, $action, $formInstanceName);
		$subject->__destruct();
	}

	/**
	 * @test
	 */
	public function validateTokenForInvalidTokenCallsCreateValidationErrorMessage() {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting $subject */
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Tests\\Unit\\FormProtection\\Fixtures\\FormProtectionTesting', array('createValidationErrorMessage'));
		$subject->expects($this->once())->method('createValidationErrorMessage');
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$subject->generateToken($formName, $action, $formInstanceName);
		$subject->validateToken('an invalid token ...', $formName, $action, $formInstanceName);
		$subject->__destruct();
	}

	/**
	 * @test
	 */
	public function validateTokenForInvalidFormNameCallsCreateValidationErrorMessage() {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting $subject */
		$subject = $this->getMock('TYPO3\\CMS\\Core\\Tests\\Unit\\FormProtection\\Fixtures\\FormProtectionTesting', array('createValidationErrorMessage'));
		$subject->expects($this->once())->method('createValidationErrorMessage');
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = 'bar';
		$token = $subject->generateToken($formName, $action, $formInstanceName);
		$subject->validateToken($token, 'another form name', $action, $formInstanceName);
		$subject->__destruct();
	}

}
