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

namespace TYPO3\CMS\Core\Tests\Unit\Authentication\Mfa\Provider;

use TYPO3\CMS\Core\Authentication\Mfa\Provider\RecoveryCodes;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RecoveryCodesTest extends UnitTestCase
{
    protected RecoveryCodes $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(RecoveryCodes::class, 'BE');
    }

    /**
     * @test
     */
    public function generateRecoveryCodesTest(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing'] = [
            'className' => Argon2iPasswordHash::class,
            'options' => [
                // Reduce default costs for quicker unit tests
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 2,
            ],
        ];

        $codes = $this->subject->generateRecoveryCodes();

        self::assertCount(8, $codes);

        $plainCodes = array_keys($codes);
        $hashedCodes = array_values($codes);
        $hashInstance = (new Argon2iPasswordHash());

        foreach ($hashedCodes as $key => $code) {
            self::assertTrue($hashInstance->isValidSaltedPW($code));
            self::assertTrue($hashInstance->checkPassword((string)$plainCodes[$key], $code));
        }
    }

    /**
     * @test
     */
    public function generatePlainRecoveryCodesThrowsExceptionOnInvalidLengthTest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1613666803);
        $this->subject->generatePlainRecoveryCodes(6);
    }

    /**
     * @test
     * @dataProvider generatePlainRecoveryCodesTestDataProvider
     *
     * @param int $length
     * @param int $quantity
     */
    public function generatePlainRecoveryCodesTest(int $length, int $quantity): void
    {
        $recoveryCodes = $this->subject->generatePlainRecoveryCodes($length, $quantity);
        self::assertCount($quantity, $recoveryCodes);
        foreach ($recoveryCodes as $code) {
            self::assertIsNumeric($code);
            self::assertEquals($length, strlen($code));
        }
    }

    public function generatePlainRecoveryCodesTestDataProvider(): \Generator
    {
        yield 'Default 8 codes with 8 chars' => [8, 8];
        yield '8 codes with 10 chars' => [8, 10];
        yield '10 codes with 8 chars' => [10, 8];
        yield '0 codes with 8 chars' => [8, 0];
        yield '10 codes with 10 chars' => [10, 10];
    }

    /**
     * @test
     */
    public function generatedHashedRecoveryCodesAreHashedWithDefaultHashInstanceTest(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing'] = [
            'className' => BcryptPasswordHash::class,
            'options' => [
                // Reduce default costs for quicker unit tests
                'cost' => 10,
            ],
        ];

        $codes = $this->subject->generatedHashedRecoveryCodes(['12345678', '87654321']);

        self::assertTrue((new BcryptPasswordHash())->isValidSaltedPW((string)$codes[0]));
        self::assertCount(2, $codes);
    }

    /**
     * @test
     */
    public function verifyRecoveryCodeTest(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing'] = [
            'className' => Argon2iPasswordHash::class,
            'options' => [
                // Reduce default costs for quicker unit tests
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 2,
            ],
        ];

        $recoveryCode = '18742989';
        $codes = [];

        // False on empty codes
        self::assertFalse($this->subject->verifyRecoveryCode($recoveryCode, $codes));

        $codes = $this->subject->generatedHashedRecoveryCodes(
            array_merge([$recoveryCode], $this->subject->generatePlainRecoveryCodes(8, 2))
        );

        // Recovery code can be verified
        self::assertTrue($this->subject->verifyRecoveryCode($recoveryCode, $codes));
        // Verified code is removed from available codes
        self::assertCount(2, $codes);
        // Recovery code can not be verified again
        self::assertFalse($this->subject->verifyRecoveryCode($recoveryCode, $codes));
    }

    /**
     * @test
     */
    public function verifyRecoveryCodeUsesTheCorrectHashInstanceTest(): void
    {
        $code = '18742989';
        $argonOptionsSpeedup = [
            // Reduce default costs for quicker unit tests
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2,
        ];
        $codes = [(new Argon2iPasswordHash($argonOptionsSpeedup))->getHashedPassword($code)];

        // Ensure we have another default hash instance
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing'] = [
            'className' => BcryptPasswordHash::class,
            'options' => [
                // Reduce default costs for quicker unit tests
                'cost' => 10,
            ],
        ];

        self::assertTrue($this->subject->verifyRecoveryCode($code, $codes));
        self::assertEmpty($codes);
    }
}
