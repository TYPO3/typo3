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

use Base32\Base32;
use TYPO3\CMS\Core\Authentication\Mfa\Provider\Totp;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TotpTest extends UnitTestCase
{
    protected string $secret;
    protected int $timestamp = 1613652061;
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        // We generate the secret here to ensure TOTP works with a secret encoded by the 3rd party package
        $this->secret = Base32::encode('TYPO3IsAwesome!'); // KRMVATZTJFZUC53FONXW2ZJB
    }

    /**
     * @test
     */
    public function throwsExceptionOnDisallowedAlogTest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1611748791);
        GeneralUtility::makeInstance(Totp::class, 'some-secret', 'md5');
    }

    /**
     * @test
     */
    public function throwsExceptionOnInvalidTotpLengthTest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1611748792);
        GeneralUtility::makeInstance(Totp::class, 'some-secret', 'sha1', 4);
    }

    /**
     * @test
     * @dataProvider totpDataProvider
     */
    public function generateTotpTest(string $expectedTotp, array $arguments): void
    {
        $counter = (int)floor(($this->timestamp - 0) / 30); // see Totp::getTimeCounter()

        self::assertEquals(
            $expectedTotp,
            GeneralUtility::makeInstance(Totp::class, $this->secret, ...$arguments)->generateTotp($counter)
        );
    }

    /**
     * @test
     * @dataProvider totpDataProvider
     */
    public function verifyTotpTest(string $totp, array $arguments): void
    {
        GeneralUtility::makeInstance(Context::class)
            ->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@' . $this->timestamp)));

        self::assertTrue(
            GeneralUtility::makeInstance(Totp::class, $this->secret, ...$arguments)->verifyTotp($totp)
        );
    }

    public function totpDataProvider(): \Generator
    {
        yield 'Default' => ['337475', []];
        yield 'sha256 algo' => ['874487', ['sha256']];
        yield 'sha512 algo' => ['497852', ['sha512']];
        yield '7 digit code' => ['8337475', ['sha1', 7]];
        yield '8 digit code' => ['48337475', ['sha1', 8]];
    }

    /**
     * @test
     */
    public function verifyTotpWithGracePeriodTest(): void
    {
        GeneralUtility::makeInstance(Context::class)
            ->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@' . $this->timestamp)));

        $totpInstance = GeneralUtility::makeInstance(Totp::class, $this->secret);

        $totpFuture = $totpInstance->generateTotp((int)floor((($this->timestamp + 90) - 0) / 30));
        self::assertFalse($totpInstance->verifyTotp($totpFuture, 3));

        $totpFuture = $totpInstance->generateTotp((int)floor((($this->timestamp + 60) - 0) / 30));
        self::assertTrue($totpInstance->verifyTotp($totpFuture, 3));

        $totpFuture = $totpInstance->generateTotp((int)floor((($this->timestamp + 30) - 0) / 30));
        self::assertTrue($totpInstance->verifyTotp($totpFuture, 3));

        $totpPast = $totpInstance->generateTotp((int)floor((($this->timestamp - 30) - 0) / 30));
        self::assertTrue($totpInstance->verifyTotp($totpPast, 3));

        $totpPast = $totpInstance->generateTotp((int)floor((($this->timestamp - 60) - 0) / 30));
        self::assertTrue($totpInstance->verifyTotp($totpPast, 3));

        $totpPast = $totpInstance->generateTotp((int)floor((($this->timestamp - 90) - 0) / 30));
        self::assertFalse($totpInstance->verifyTotp($totpPast, 3));
    }

    /**
     * @test
     * @dataProvider getTotpAuthUrlTestDataProvider
     */
    public function getTotpAuthUrlTest(array $constructorArguments, array $methodArguments, string $expected): void
    {
        $totp = GeneralUtility::makeInstance(Totp::class, ...$constructorArguments);

        self::assertEquals($expected, $totp->getTotpAuthUrl(...$methodArguments));
    }

    public function getTotpAuthUrlTestDataProvider(): \Generator
    {
        yield 'Default Totp with account and additional params' => [
            [
                'N5WGS4ZNOR4XA3ZTFVZWS5DF',
            ],
            [
                'Oli`s awesome site`',
                'user@typo3.org',
                [
                    'foo' => 'bar',
                    'bar' => [
                        'baz' => 123,
                    ],
                ],
            ],
            'otpauth://totp/Oli%60s%20awesome%20site%60%3Auser%40typo3.org?secret=N5WGS4ZNOR4XA3ZTFVZWS5DF&issuer=Oli%60s%20awesome%20site%60&foo=bar&bar%5Bbaz%5D=123',
        ];
        yield 'Custom Totp settings with account without additional params' => [
            [
                'N5WGS4ZNOR4XA3ZTFVZWS5DF',
                'sha256',
                8,
                20,
                12345,
            ],
            [
                'Some other site',
                'user@typo3.org',
            ],
            'otpauth://totp/Some%20other%20site%3Auser%40typo3.org?secret=N5WGS4ZNOR4XA3ZTFVZWS5DF&issuer=Some%20other%20site&algorithm=sha256&period=20&digits=8&epoch=12345',
        ];
    }

    /**
     * @test
     */
    public function generateEncodedSecretTest(): void
    {
        // Check 100 times WITHOUT additional auth factors
        for ($i=0; $i<100; $i++) {
            // Assert correct length and secret only contains allowed alphabet
            self::assertMatchesRegularExpression('/^[ABCDEFGHIJKLMNOPQRSTUVWXYZ234567]{32}$/', Totp::generateEncodedSecret());
        }

        // Check 100 times WITH additional auth factors
        for ($i=0; $i<100; $i++) {
            $authFactors = ['uid' => 5, 'username' => 'non.admin'];
            // Assert correct length and secret only contains allowed alphabet
            self::assertMatchesRegularExpression('/^[ABCDEFGHIJKLMNOPQRSTUVWXYZ234567]{32}$/', Totp::generateEncodedSecret($authFactors));
        }
    }
}
