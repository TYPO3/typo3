<?php

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Security\Cryptography;

use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException;
use TYPO3\CMS\Extbase\Security\Exception\InvalidHashException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class HashServiceTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Security\Cryptography\HashService
     */
    protected $hashService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hashService = new HashService();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'Testing';
    }

    /**
     * @test
     */
    public function generateHmacReturnsHashStringIfStringIsGiven()
    {
        $hash = $this->hashService->generateHmac('asdf');
        self::assertTrue(is_string($hash));
    }

    /**
     * @test
     */
    public function generateHmacReturnsHashStringWhichContainsSomeSalt()
    {
        $hash = $this->hashService->generateHmac('asdf');
        self::assertNotEquals(sha1('asdf'), $hash);
    }

    /**
     * @test
     */
    public function generateHmacReturnsDifferentHashStringsForDifferentInputStrings()
    {
        $hash1 = $this->hashService->generateHmac('asdf');
        $hash2 = $this->hashService->generateHmac('blubb');
        self::assertNotEquals($hash1, $hash2);
    }

    /**
     * @test
     */
    public function generatedHmacCanBeValidatedAgain()
    {
        $string = 'asdf';
        $hash = $this->hashService->generateHmac($string);
        self::assertTrue($this->hashService->validateHmac($string, $hash));
    }

    /**
     * @test
     */
    public function generatedHmacWillNotBeValidatedIfHashHasBeenChanged()
    {
        $string = 'asdf';
        $hash = 'myhash';
        self::assertFalse($this->hashService->validateHmac($string, $hash));
    }

    /**
     * @test
     */
    public function appendHmacAppendsHmacToGivenString()
    {
        $string = 'This is some arbitrary string ';
        $hashedString = $this->hashService->appendHmac($string);
        self::assertSame($string, substr($hashedString, 0, -40));
    }

    /**
     * @test
     */
    public function validateAndStripHmacThrowsExceptionIfGivenStringIsTooShort()
    {
        $this->expectException(InvalidArgumentForHashGenerationException::class);
        $this->expectExceptionCode(1320830276);
        $this->hashService->validateAndStripHmac('string with less than 40 characters');
    }

    /**
     * @test
     */
    public function validateAndStripHmacThrowsExceptionIfGivenStringHasNoHashAppended()
    {
        $this->expectException(InvalidHashException::class);
        $this->expectExceptionCode(1320830018);
        $this->hashService->validateAndStripHmac('string with exactly a length 40 of chars');
    }

    /**
     * @test
     */
    public function validateAndStripHmacThrowsExceptionIfTheAppendedHashIsInvalid()
    {
        $this->expectException(InvalidHashException::class);
        $this->expectExceptionCode(1320830018);
        $this->hashService->validateAndStripHmac('some Stringac43682075d36592d4cb320e69ff0aa515886eab');
    }

    /**
     * @test
     */
    public function validateAndStripHmacReturnsTheStringWithoutHmac()
    {
        $string = ' Some arbitrary string with special characters: öäüß!"§$ ';
        $hashedString = $this->hashService->appendHmac($string);
        $actualResult = $this->hashService->validateAndStripHmac($hashedString);
        self::assertSame($string, $actualResult);
    }
}
