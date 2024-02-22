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

namespace TYPO3\CMS\Core\Tests\Unit\Crypto;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Exception\Crypto\EmptyAdditionalSecretException;
use TYPO3\CMS\Core\Exception\Crypto\InvalidHashStringException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class HashServiceTest extends UnitTestCase
{
    protected HashService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new HashService();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
    }

    #[Test]
    public function hmacThrowsExceptionIfEmptyAdditionalSecretProvided(): void
    {
        $this->expectException(EmptyAdditionalSecretException::class);

        // @phpstan-ignore-next-line We are explicitly testing a contract violation here.
        $this->subject->hmac('message', '');
    }

    #[Test]
    public function hmacReturnsHashOfProperLength(): void
    {
        $hmac = $this->subject->hmac('message', 'additional-secret');
        self::assertSame(strlen($hmac), 40);
    }

    #[Test]
    public function hmacReturnsEqualHashesForEqualInput(): void
    {
        $additionalSecret = 'additional-secret';
        $string1 = 'input';
        $string2 = 'input';
        self::assertSame($this->subject->hmac($string1, $additionalSecret), $this->subject->hmac($string2, $additionalSecret));
    }

    #[Test]
    public function hmacReturnsNoEqualHashesForNonEqualInput(): void
    {
        $additionalSecret = 'additional-secret';
        $string1 = 'input1';
        $string2 = 'input2';
        self::assertNotEquals($this->subject->hmac($string1, $additionalSecret), $this->subject->hmac($string2, $additionalSecret));
    }

    #[Test]
    public function generatedHmacCanBeValidatedAgain(): void
    {
        $string = 'some input';
        $additionalSecret = 'additional-secret';
        $hash = $this->subject->hmac($string, $additionalSecret);
        self::assertTrue($this->subject->validateHmac($string, $additionalSecret, $hash));
    }

    #[Test]
    public function generatedHmacValidationFailsIfHashIsInvalid(): void
    {
        $string = 'some input';
        $additionalSecret = 'additional-secret';
        $hash = 'myhash';
        self::assertFalse($this->subject->validateHmac($string, $additionalSecret, $hash));
    }

    #[Test]
    public function appendHmacAppendsHmacToGivenString(): void
    {
        $string = 'This is some arbitrary string ';
        $additionalSecret = 'additional-secret';
        $hashedString = $this->subject->appendHmac($string, $additionalSecret);
        self::assertSame($string, substr($hashedString, 0, -40));
    }

    #[Test]
    public function validateAndStripHmacThrowsExceptionIfGivenStringIsTooShort(): void
    {
        $additionalSecret = 'additional-secret';
        $this->expectException(InvalidHashStringException::class);
        $this->expectExceptionCode(1704454152);
        $this->subject->validateAndStripHmac('string with less than 40 characters', $additionalSecret);
    }

    #[Test]
    public function validateAndStripHmacThrowsExceptionIfGivenStringHasNoHashAppended(): void
    {
        $additionalSecret = 'additional-secret';
        $this->expectException(InvalidHashStringException::class);
        $this->expectExceptionCode(1704454157);
        $this->subject->validateAndStripHmac('string with exactly a length 40 of chars', $additionalSecret);
    }

    #[Test]
    public function validateAndStripHmacThrowsExceptionIfTheAppendedHashIsInvalid(): void
    {
        $additionalSecret = 'additional-secret';
        $this->expectException(InvalidHashStringException::class);
        $this->expectExceptionCode(1704454157);
        $this->subject->validateAndStripHmac('some Stringac43682075d36592d4cb320e69ff0aa515886eab', $additionalSecret);
    }

    #[Test]
    public function validateAndStripHmacReturnsTheStringWithoutHmac(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'Testing';
        $additionalSecret = 'additional-secret';
        $string = ' Some arbitrary string with special characters: öäüß!"§$ ';
        $hashedString = $this->subject->appendHmac($string, $additionalSecret);
        $actualResult = $this->subject->validateAndStripHmac($hashedString, $additionalSecret);
        self::assertSame($string, $actualResult);
    }
}
