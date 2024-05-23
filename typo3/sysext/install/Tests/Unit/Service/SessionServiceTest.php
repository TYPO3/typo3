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

namespace TYPO3\CMS\Install\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Tests\Unit\Service\Fixtures\AccessibleSessionService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SessionServiceTest extends UnitTestCase
{
    public static function effectiveSettingsDataProvider(): \Generator
    {
        yield 'both settings are effective over https' => [
            true,
            ['session.cookie_secure' => true, 'session.cookie_httponly' => true],
        ];
        yield 'a disabled cookie_secure is expected without https' => [
            false,
            ['session.cookie_secure' => false, 'session.cookie_httponly' => true],
        ];
    }

    #[DataProvider('effectiveSettingsDataProvider')]
    #[Test]
    public function logInsecureSessionCookieSettingsKeepsQuietOnEffectiveSettings(bool $https, array $iniValues): void
    {
        $logger = self::createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $subject = new AccessibleSessionService(self::createStub(LateBootService::class), $logger);
        $subject->iniValues = $iniValues;

        $subject->callLogInsecureSessionCookieSettings($https);
    }

    public static function ineffectiveSettingsDataProvider(): \Generator
    {
        yield 'cookie_secure is not applied over https' => [
            true,
            ['session.cookie_secure' => false, 'session.cookie_httponly' => true],
            'session.cookie_secure',
        ];
        yield 'cookie_httponly is not applied' => [
            false,
            ['session.cookie_secure' => false, 'session.cookie_httponly' => false],
            'session.cookie_httponly',
        ];
        yield 'both are not applied over https' => [
            true,
            ['session.cookie_secure' => false, 'session.cookie_httponly' => false],
            'session.cookie_secure, session.cookie_httponly',
        ];
        yield 'a value php does not report as boolean counts as not applied' => [
            true,
            ['session.cookie_secure' => null, 'session.cookie_httponly' => true],
            'session.cookie_secure',
        ];
    }

    #[DataProvider('ineffectiveSettingsDataProvider')]
    #[Test]
    public function logInsecureSessionCookieSettingsLogsTheSettingsNotInEffect(bool $https, array $iniValues, string $expectedSettings): void
    {
        $logger = self::createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            self::stringContains('not in effect'),
            ['settings' => $expectedSettings]
        );

        $subject = new AccessibleSessionService(self::createStub(LateBootService::class), $logger);
        $subject->iniValues = $iniValues;

        $subject->callLogInsecureSessionCookieSettings($https);
    }

    #[Test]
    public function logInsecureSessionCookieSettingsLogsThatVerificationIsImpossibleWithoutIniGet(): void
    {
        $logger = self::createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(self::stringContains('"ini_get" is disabled'));

        $subject = new AccessibleSessionService(self::createStub(LateBootService::class), $logger);
        $subject->iniGetIsDisabled = true;

        $subject->callLogInsecureSessionCookieSettings(true);
    }
}
