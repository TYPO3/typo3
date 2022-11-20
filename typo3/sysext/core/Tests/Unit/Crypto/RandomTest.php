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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RandomTest extends UnitTestCase
{
    /**
     * @test
     */
    public function generateRandomBytesReturnsExpectedAmountOfBytes(): void
    {
        $subject = new Random();
        self::assertEquals(4, strlen($subject->generateRandomBytes(4)));
    }

    /**
     * Data provider for generateRandomHexStringReturnsExpectedAmountOfChars
     */
    public function generateRandomHexStringReturnsExpectedAmountOfCharsDataProvider(): array
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [7],
            [8],
            [31],
            [32],
            [100],
            [102],
            [4000],
            [4095],
            [4096],
            [4097],
            [8000],
        ];
    }

    /**
     * @test
     * @dataProvider generateRandomHexStringReturnsExpectedAmountOfCharsDataProvider
     * @param int $numberOfChars Number of Chars to generate
     */
    public function generateRandomHexStringReturnsExpectedAmountOfChars($numberOfChars): void
    {
        $subject = new Random();
        self::assertEquals($numberOfChars, strlen($subject->generateRandomHexString($numberOfChars)));
    }

    public function generateRandomPasswordThrowsInvalidPasswordRulesExceptionDataProvider(): \Generator
    {
        yield 'Invalid length' => [
            [
                'length' => 4,
            ],
            1667557900,
        ];
        yield 'Invalid random value' => [
            [
                'random' => 'invalid',
            ],
            1667557901,
        ];
        yield 'Invalid characters definition' => [
            [
                'lowerCaseCharacters' => false,
                'upperCaseCharacters' => false,
                'digitCharacters' => false,
            ],
            1667557902,
        ];
    }

    /**
     * @test
     * @dataProvider generateRandomPasswordThrowsInvalidPasswordRulesExceptionDataProvider
     */
    public function generateRandomPasswordThrowsInvalidPasswordRulesException(
        array $passwordRules,
        int $exceptionCode
    ): void {
        $this->expectException(InvalidPasswordRulesException::class);
        $this->expectExceptionCode($exceptionCode);

        (new Random())->generateRandomPassword($passwordRules);
    }

    public function generateRandomPasswordGeneratesRandomWithEncodingDataProvider(): \Generator
    {
        yield 'Hex with 42 chars' => [
            [
                'length' => 42,
                'random' => 'hex',
            ],
            '/^[a-fA-F0-9]{42}$/',
        ];
        yield 'Base64 with 37 chars' => [
            [
                'length' => 37,
                'random' => 'base64',
                'digitCharacters' => false, // Won't be evaluated
            ],
            '/^[a-zA-Z0-9\-\_]{37}$/',
        ];
    }

    /**
     * @test
     * @dataProvider generateRandomPasswordGeneratesRandomWithEncodingDataProvider
     */
    public function generateRandomPasswordGeneratesRandomWithEncoding(
        array $passwordRules,
        string $pattern
    ): void {
        self::assertMatchesRegularExpression($pattern, (new Random())->generateRandomPassword($passwordRules));
    }

    public function generateRandomPasswordGeneratesRandomWithCharacterSetsDataProvider(): \Generator
    {
        yield 'lowercase' => [
            [
                'lowerCaseCharacters' => true,
            ],
            '/[a-z]+/',
        ];
        yield 'uppercase' => [
            [
                'upperCaseCharacters' => true,
            ],
            '/[A-Z]+/',
        ];
        yield 'digits' => [
            [
                'digitCharacters' => true,
            ],
            '/[0-9]+/',
        ];
        yield 'special' => [
            [
                'specialCharacters' => true,
            ],
            '/[\'!"#$%&()*+,\-.\/:;<=>?@\[\]^_`{|}~]+/',
        ];
    }

    /**
     * @test
     * @dataProvider generateRandomPasswordGeneratesRandomWithCharacterSetsDataProvider
     */
    public function generateRandomPasswordGeneratesRandomWithCharacterSets(
        array $passwordRules,
        string $pattern
    ): void {
        self::assertMatchesRegularExpression($pattern, (new Random())->generateRandomPassword($passwordRules));
    }

    public function generateRandomPasswordGeneratesRandomWithLengthDataProvider(): \Generator
    {
        yield 'fallback' => [
            [],
            16,
        ];
        yield 'length=40' => [
            [
                'length' => 40,
            ],
            40,
        ];
        yield 'length=36 with random=hex' => [
            [
                'length' => 36,
                'random' => 'hex',
            ],
            36,
        ];
        yield 'length=42 with random=hex' => [
            [
                'length' => 42,
                'random' => 'base64',
            ],
            42,
        ];
    }

    /**
     * @test
     * @dataProvider generateRandomPasswordGeneratesRandomWithLengthDataProvider
     */
    public function generateRandomPasswordGeneratesRandomWithLength(
        array $passwordRules,
        int $length
    ): void {
        self::assertEquals($length, strlen((new Random())->generateRandomPassword($passwordRules)));
    }
}
