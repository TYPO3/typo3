<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Unit\FormProtection;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase
 */
final class AbstractFormProtectionTest extends UnitTestCase
{
    protected ?FormProtectionTesting $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new FormProtectionTesting();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
    }

    #[Test]
    public function generateTokenRetrievesTokenOnce(): void
    {
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->onlyMethods(['retrieveSessionToken'])
            ->getMock();
        $subject->expects(self::once())->method('retrieveSessionToken')->willReturn('token');
        $subject->generateToken('foo');
        $subject->generateToken('foo');
    }

    #[Test]
    public function validateTokenRetrievesTokenOnce(): void
    {
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->onlyMethods(['retrieveSessionToken'])
            ->getMock();
        $subject->expects(self::once())->method('retrieveSessionToken')->willReturn('token');
        $subject->validateToken('foo', 'bar');
        $subject->validateToken('foo', 'bar');
    }

    #[Test]
    public function cleanMakesTokenInvalid(): void
    {
        $formName = 'foo';
        $tokenId = $this->subject->generateToken($formName);
        $this->subject->clean();
        self::assertFalse($this->subject->validateToken($tokenId, $formName));
    }

    #[Test]
    public function cleanPersistsToken(): void
    {
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->onlyMethods(['persistSessionToken'])
            ->getMock();
        $subject->expects(self::once())->method('persistSessionToken');
        $subject->clean();
    }

    #[Test]
    public function generateTokenFormForEmptyFormNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1294586643);
        $this->subject->generateToken('', 'edit', 'bar');
    }

    #[Test]
    public function generateTokenFormForEmptyActionNotThrowsException(): void
    {
        $this->subject->generateToken('foo', '', '42');
    }

    #[Test]
    public function generateTokenFormForEmptyFormInstanceNameNotThrowsException(): void
    {
        $this->subject->generateToken('foo', 'edit', '');
    }

    #[Test]
    public function generateTokenFormForOmittedActionAndFormInstanceNameNotThrowsException(): void
    {
        $this->subject->generateToken('foo');
    }

    #[Test]
    public function generateTokenReturns32CharacterHexToken(): void
    {
        self::assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $this->subject->generateToken('foo'));
    }

    #[Test]
    public function generateTokenCalledTwoTimesWithSameParametersReturnsSameTokens(): void
    {
        self::assertEquals($this->subject->generateToken('foo', 'edit', 'bar'), $this->subject->generateToken('foo', 'edit', 'bar'));
    }

    #[Test]
    public function validateTokenWithFourEmptyParametersNotThrowsException(): void
    {
        $this->subject->validateToken('', '', '', '');
    }

    #[Test]
    public function validateTokenWithTwoEmptyAndTwoMissingParametersNotThrowsException(): void
    {
        $this->subject->validateToken('', '');
    }

    #[Test]
    public function validateTokenWithDataFromGenerateTokenWithFormInstanceNameReturnsTrue(): void
    {
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        self::assertTrue($this->subject->validateToken($this->subject->generateToken($formName, $action, $formInstanceName), $formName, $action, $formInstanceName));
    }

    #[Test]
    public function validateTokenWithDataFromGenerateTokenWithMissingActionAndFormInstanceNameReturnsTrue(): void
    {
        $formName = 'foo';
        self::assertTrue($this->subject->validateToken($this->subject->generateToken($formName), $formName));
    }

    #[Test]
    public function validateTokenWithValidDataCalledTwoTimesReturnsTrueOnSecondCall(): void
    {
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $tokenId = $this->subject->generateToken($formName, $action, $formInstanceName);
        $this->subject->validateToken($tokenId, $formName, $action, $formInstanceName);
        self::assertTrue($this->subject->validateToken($tokenId, $formName, $action, $formInstanceName));
    }

    #[Test]
    public function validateTokenWithMismatchingTokenIdReturnsFalse(): void
    {
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $this->subject->generateToken($formName, $action, $formInstanceName);
        self::assertFalse($this->subject->validateToken('Hello world!', $formName, $action, $formInstanceName));
    }

    #[Test]
    public function validateTokenWithMismatchingFormNameReturnsFalse(): void
    {
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $tokenId = $this->subject->generateToken($formName, $action, $formInstanceName);
        self::assertFalse($this->subject->validateToken($tokenId, 'espresso', $action, $formInstanceName));
    }

    #[Test]
    public function validateTokenWithMismatchingActionReturnsFalse(): void
    {
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $tokenId = $this->subject->generateToken($formName, $action, $formInstanceName);
        self::assertFalse($this->subject->validateToken($tokenId, $formName, 'delete', $formInstanceName));
    }

    #[Test]
    public function validateTokenWithMismatchingFormInstanceNameReturnsFalse(): void
    {
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $tokenId = $this->subject->generateToken($formName, $action, $formInstanceName);
        self::assertFalse($this->subject->validateToken($tokenId, $formName, $action, 'beer'));
    }

    #[Test]
    public function validateTokenForValidTokenNotCallsCreateValidationErrorMessage(): void
    {
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->onlyMethods(['createValidationErrorMessage'])
            ->getMock();
        $subject->expects(self::never())->method('createValidationErrorMessage');
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $token = $subject->generateToken($formName, $action, $formInstanceName);
        $subject->validateToken($token, $formName, $action, $formInstanceName);
    }

    #[Test]
    public function validateTokenForInvalidTokenCallsCreateValidationErrorMessage(): void
    {
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->onlyMethods(['createValidationErrorMessage'])
            ->getMock();
        $subject->expects(self::once())->method('createValidationErrorMessage');
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $subject->generateToken($formName, $action, $formInstanceName);
        $subject->validateToken('an invalid token ...', $formName, $action, $formInstanceName);
    }

    #[Test]
    public function validateTokenForInvalidFormNameCallsCreateValidationErrorMessage(): void
    {
        $subject = $this->getMockBuilder(FormProtectionTesting::class)
            ->onlyMethods(['createValidationErrorMessage'])
            ->getMock();
        $subject->expects(self::once())->method('createValidationErrorMessage');
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = 'bar';
        $token = $subject->generateToken($formName, $action, $formInstanceName);
        $subject->validateToken($token, 'another form name', $action, $formInstanceName);
    }
}
