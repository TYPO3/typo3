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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RandomTest extends UnitTestCase
{
    #[Test]
    public function generateRandomBytesReturnsExpectedAmountOfBytes(): void
    {
        $subject = new Random();
        self::assertEquals(4, strlen($subject->generateRandomBytes(4)));
    }

    public static function lengthDataProvider(): iterable
    {
        yield [1];
        yield [2];
        yield [3];
        yield [4];
        yield [7];
        yield [8];
        yield [31];
        yield [32];
        yield [100];
        yield [102];
        yield [4000];
        yield [4095];
        yield [4096];
        yield [4097];
        yield [8000];
    }

    #[Test]
    #[DataProvider('lengthDataProvider')]
    public function generateRandomHexStringReturnsExpectedAmountOfChars(int $length): void
    {
        $subject = new Random();
        $result = $subject->generateRandomHexString($length);
        self::assertMatchesRegularExpression('/^[[:xdigit:]]{' . $length . '}$/', $result);
    }

    #[Test]
    #[DataProvider('lengthDataProvider')]
    public function generateRandomBase64StringReturnsExpectedAmountOfChars(int $length): void
    {
        $subject = new Random();
        $result = $subject->generateRandomBase64String($length);
        self::assertMatchesRegularExpression('/^[[:alnum:]_-]{' . $length . '}$/', $result);
    }

    public static function generateRandomPasswordThrowsInvalidPasswordRulesExceptionDataProvider(): \Generator
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

    #[DataProvider('generateRandomPasswordThrowsInvalidPasswordRulesExceptionDataProvider')]
    #[Test]
    public function generateRandomPasswordThrowsInvalidPasswordRulesException(
        array $passwordRules,
        int $exceptionCode
    ): void {
        $this->expectException(InvalidPasswordRulesException::class);
        $this->expectExceptionCode($exceptionCode);

        (new Random())->generateRandomPassword($passwordRules);
    }

    public static function generateRandomPasswordGeneratesRandomWithEncodingDataProvider(): \Generator
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

    #[DataProvider('generateRandomPasswordGeneratesRandomWithEncodingDataProvider')]
    #[Test]
    public function generateRandomPasswordGeneratesRandomWithEncoding(
        array $passwordRules,
        string $pattern
    ): void {
        self::assertMatchesRegularExpression($pattern, (new Random())->generateRandomPassword($passwordRules));
    }

    public static function generateRandomPasswordGeneratesRandomWithCharacterSetsDataProvider(): \Generator
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

    #[DataProvider('generateRandomPasswordGeneratesRandomWithCharacterSetsDataProvider')]
    #[Test]
    public function generateRandomPasswordGeneratesRandomWithCharacterSets(
        array $passwordRules,
        string $pattern
    ): void {
        self::assertMatchesRegularExpression($pattern, (new Random())->generateRandomPassword($passwordRules));
    }

    public static function generateRandomPasswordGeneratesRandomWithLengthDataProvider(): \Generator
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

    #[DataProvider('generateRandomPasswordGeneratesRandomWithLengthDataProvider')]
    #[Test]
    public function generateRandomPasswordGeneratesRandomWithLength(
        array $passwordRules,
        int $length
    ): void {
        self::assertEquals($length, strlen((new Random())->generateRandomPassword($passwordRules)));
    }

    #[Test]
    public function generateRandomPasswordIsUnpredictable(): void
    {
        $subject = new Random();
        $max = 1000;
        $count = 0;
        for ($i = 0; $i < $max; $i++) {
            $result = $subject->generateRandomPassword(['passwordLength' => 12]);
            if (preg_match('/^[a-z][A-Z][0-9]/', $result)) {
                $count++;
            }
        }
        self::assertNotEquals($max, $count);
        self::assertLessThan(0.1, $count / $max);
    }
}
