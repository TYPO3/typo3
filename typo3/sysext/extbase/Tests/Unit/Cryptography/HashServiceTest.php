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

namespace TYPO3\CMS\Extbase\Tests\Unit\Cryptography;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService as CoreHashService;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException;
use TYPO3\CMS\Extbase\Security\Exception\InvalidHashException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @deprecated Remove together with HashService in v14.
 */
final class HashServiceTest extends UnitTestCase
{
    protected HashService $hashService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hashService = new HashService(new CoreHashService());
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'Testing';
    }

    #[Test]
    #[IgnoreDeprecations]
    public function generateHmacReturnsHashStringIfStringIsGiven(): void
    {
        $hash = $this->hashService->generateHmac('asdf');
        self::assertIsString($hash);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function generateHmacReturnsHashStringWhichContainsSomeSalt(): void
    {
        $hash = $this->hashService->generateHmac('asdf');
        self::assertNotEquals(sha1('asdf'), $hash);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function generateHmacReturnsDifferentHashStringsForDifferentInputStrings(): void
    {
        $hash1 = $this->hashService->generateHmac('asdf');
        $hash2 = $this->hashService->generateHmac('blubb');
        self::assertNotEquals($hash1, $hash2);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function generatedHmacCanBeValidatedAgain(): void
    {
        $string = 'asdf';
        $hash = $this->hashService->generateHmac($string);
        self::assertTrue($this->hashService->validateHmac($string, $hash));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function generatedHmacWillNotBeValidatedIfHashHasBeenChanged(): void
    {
        $string = 'asdf';
        $hash = 'myhash';
        self::assertFalse($this->hashService->validateHmac($string, $hash));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function appendHmacAppendsHmacToGivenString(): void
    {
        $string = 'This is some arbitrary string ';
        $hashedString = $this->hashService->appendHmac($string);
        self::assertSame($string, substr($hashedString, 0, -40));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function validateAndStripHmacThrowsExceptionIfGivenStringIsTooShort(): void
    {
        $this->expectException(InvalidArgumentForHashGenerationException::class);
        $this->expectExceptionCode(1320830276);
        $this->hashService->validateAndStripHmac('string with less than 40 characters');
    }

    #[Test]
    #[IgnoreDeprecations]
    public function validateAndStripHmacThrowsExceptionIfGivenStringHasNoHashAppended(): void
    {
        $this->expectException(InvalidHashException::class);
        $this->expectExceptionCode(1320830018);
        $this->hashService->validateAndStripHmac('string with exactly a length 40 of chars');
    }

    #[Test]
    #[IgnoreDeprecations]
    public function validateAndStripHmacThrowsExceptionIfTheAppendedHashIsInvalid(): void
    {
        $this->expectException(InvalidHashException::class);
        $this->expectExceptionCode(1320830018);
        $this->hashService->validateAndStripHmac('some Stringac43682075d36592d4cb320e69ff0aa515886eab');
    }

    #[Test]
    #[IgnoreDeprecations]
    public function validateAndStripHmacReturnsTheStringWithoutHmac(): void
    {
        $string = ' Some arbitrary string with special characters: öäüß!"§$ ';
        $hashedString = $this->hashService->appendHmac($string);
        $actualResult = $this->hashService->validateAndStripHmac($hashedString);
        self::assertSame($string, $actualResult);
    }
}
