<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\FormProtection;

/*
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

use TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase
 */
class AbstractFormProtectionTest extends UnitTestCase
{
    /**
     * @var FormProtectionTesting
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new FormProtectionTesting();
    }

    /////////////////////////////////////////
    // Tests concerning the basic functions
    /////////////////////////////////////////
    /**
     * @test
     */
    public function generateTokenRetrievesTokenOnce(): void
    {
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->setMethods(['retrieveSessionToken'])
            ->getMock();
        $subject->expects($this->once())->method('retrieveSessionToken')->will($this->returnValue('token'));
        $subject->generateToken('foo');
        $subject->generateToken('foo');
    }

    /**
     * @test
     */
    public function validateTokenRetrievesTokenOnce(): void
    {
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->setMethods(['retrieveSessionToken'])
            ->getMock();
        $subject->expects($this->once())->method('retrieveSessionToken')->will($this->returnValue('token'));
        $subject->validateToken('foo', 'bar');
        $subject->validateToken('foo', 'bar');
    }

    /**
     * @test
     */
    public function cleanMakesTokenInvalid(): void
    {
        $formName = 'foo';
        $tokenId = $this->subject->generateToken($formName);
        $this->subject->clean();
        $this->assertFalse($this->subject->validateToken($tokenId, $formName));
    }

    /**
     * @test
     */
    public function cleanPersistsToken(): void
    {
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->setMethods(['persistSessionToken'])
            ->getMock();
        $subject->expects($this->once())->method('persistSessionToken');
        $subject->clean();
    }

    ///////////////////////////////////
    // Tests concerning generateToken
    ///////////////////////////////////
    /**
     * @test
     */
    public function generateTokenFormForEmptyFormNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1294586643);
        $this->subject->generateToken('', 'edit', 'bar');
    }

    /**
     * @test
     */
    public function generateTokenFormForEmptyActionNotThrowsException(): void
    {
        $this->subject->generateToken('foo', '', '42');
    }

    /**
     * @test
     */
    public function generateTokenFormForEmptyFormInstanceNameNotThrowsException(): void
    {
        $this->subject->generateToken('foo', 'edit', '');
    }

    /**
     * @test
     */
    public function generateTokenFormForOmittedActionAndFormInstanceNameNotThrowsException(): void
    {
        $this->subject->generateToken('foo');
    }

    /**
     * @test
     */
    public function generateTokenReturns32CharacterHexToken(): void
    {
        $this->assertRegExp('/^[0-9a-f]{40}$/', $this->subject->generateToken('foo'));
    }

    /**
     * @test
     */
    public function generateTokenCalledTwoTimesWithSameParametersReturnsSameTokens(): void
    {
        $this->assertEquals($this->subject->generateToken('foo', 'edit', 'bar'), $this->subject->generateToken('foo', 'edit', 'bar'));
    }

    ///////////////////////////////////
    // Tests concerning validateToken
    ///////////////////////////////////
    /**
     * @test
     */
    public function validateTokenWithFourEmptyParametersNotThrowsException(): void
    {
        $this->subject->validateToken('', '', '', '');
    }

    /**
     * @test
     */
    public function validateTokenWithTwoEmptyAndTwoMissingParametersNotThrowsException(): void
    {
        $this->subject->validateToken('', '');
    }

    /**
     * @test
     */
    public function validateTokenWithDataFromGenerateTokenWithFormInstanceNameReturnsTrue(): void
    {
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $this->assertTrue($this->subject->validateToken($this->subject->generateToken($formName, $action, $formInstanceName), $formName, $action, $formInstanceName));
    }

    /**
     * @test
     */
    public function validateTokenWithDataFromGenerateTokenWithMissingActionAndFormInstanceNameReturnsTrue(): void
    {
        $formName = 'foo';
        $this->assertTrue($this->subject->validateToken($this->subject->generateToken($formName), $formName));
    }

    /**
     * @test
     */
    public function validateTokenWithValidDataCalledTwoTimesReturnsTrueOnSecondCall(): void
    {
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
    public function validateTokenWithMismatchingTokenIdReturnsFalse(): void
    {
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $this->subject->generateToken($formName, $action, $formInstanceName);
        $this->assertFalse($this->subject->validateToken('Hello world!', $formName, $action, $formInstanceName));
    }

    /**
     * @test
     */
    public function validateTokenWithMismatchingFormNameReturnsFalse(): void
    {
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $tokenId = $this->subject->generateToken($formName, $action, $formInstanceName);
        $this->assertFalse($this->subject->validateToken($tokenId, 'espresso', $action, $formInstanceName));
    }

    /**
     * @test
     */
    public function validateTokenWithMismatchingActionReturnsFalse(): void
    {
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $tokenId = $this->subject->generateToken($formName, $action, $formInstanceName);
        $this->assertFalse($this->subject->validateToken($tokenId, $formName, 'delete', $formInstanceName));
    }

    /**
     * @test
     */
    public function validateTokenWithMismatchingFormInstanceNameReturnsFalse(): void
    {
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $tokenId = $this->subject->generateToken($formName, $action, $formInstanceName);
        $this->assertFalse($this->subject->validateToken($tokenId, $formName, $action, 'beer'));
    }

    /**
     * @test
     */
    public function validateTokenForValidTokenNotCallsCreateValidationErrorMessage(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormProtectionTesting $subject */
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->setMethods(['createValidationErrorMessage'])
            ->getMock();
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
    public function validateTokenForInvalidTokenCallsCreateValidationErrorMessage(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormProtectionTesting $subject */
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->setMethods(['createValidationErrorMessage'])
            ->getMock();
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
    public function validateTokenForInvalidFormNameCallsCreateValidationErrorMessage(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormProtectionTesting $subject */
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->setMethods(['createValidationErrorMessage'])
            ->getMock();
        $subject->expects($this->once())->method('createValidationErrorMessage');
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $token = $subject->generateToken($formName, $action, $formInstanceName);
        $subject->validateToken($token, 'another form name', $action, $formInstanceName);
        $subject->__destruct();
    }
}
