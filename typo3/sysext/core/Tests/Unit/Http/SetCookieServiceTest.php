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

namespace TYPO3\CMS\Core\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\SetCookieService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SetCookieServiceTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6';
    }

    public function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
        parent::tearDown();
    }

    public static function removalUsesSameIdentityAndAttributesAsCreationDataProvider(): \Generator
    {
        yield '[BE] host-only over https, strict' => ['BE', 'strict', '', true];
        yield '[FE] host-only over https, strict' => ['FE', 'strict', '', true];
        yield '[BE] host-only over http, lax' => ['BE', 'lax', '', false];
        yield '[FE] host-only over http, lax' => ['FE', 'lax', '', false];
        yield '[BE] cookieDomain over https, strict' => ['BE', 'strict', '.example.com', true];
        yield '[FE] cookieDomain over https, strict' => ['FE', 'strict', '.example.com', true];
        yield '[BE] cookieDomain over http, lax' => ['BE', 'lax', '.example.com', false];
        yield '[FE] cookieDomain over http, lax' => ['FE', 'lax', '.example.com', false];
        yield '[BE] none forces secure even over http' => ['BE', 'none', '', false];
        yield '[FE] none forces secure even over http' => ['FE', 'none', '', false];
    }

    #[Test]
    #[DataProvider('removalUsesSameIdentityAndAttributesAsCreationDataProvider')]
    public function removalUsesSameIdentityAndAttributesAsCreation(string $loginType, string $sameSite, string $cookieDomain, bool $https): void
    {
        $GLOBALS['TYPO3_CONF_VARS'][$loginType]['cookieSameSite'] = $sameSite;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'] = $cookieDomain;
        $normalizedParams = new NormalizedParams(
            ['HTTP_HOST' => 'example.com', 'HTTPS' => $https ? 'on' : 'off', 'SCRIPT_NAME' => '/index.php'],
            [],
            '/var/www/index.php',
            '/var/www'
        );

        $logger = new NullLogger();
        $logManager = self::createStub(LogManager::class);
        $logManager->method('getLogger')->willReturn($logger);
        GeneralUtility::setSingletonInstance(LogManager::class, $logManager);

        $subject = SetCookieService::create('typo_user', $loginType);

        GeneralUtility::removeSingletonInstance(LogManager::class, $logManager);

        $removalCookie = $subject->removeCookie($normalizedParams);
        $creationCookie = $subject->setSessionCookie(UserSession::createNonFixated('fdcba54321'), $normalizedParams);

        self::assertSame($https || $sameSite === 'none', $removalCookie->isSecure());
        self::assertSame($cookieDomain === '' ? null : 'example.com', $removalCookie->getDomain());
        self::assertSame($sameSite, $removalCookie->getSameSite());
        self::assertSame('', $removalCookie->getValue());
        self::assertStringContainsString('=deleted; expires=', (string)$removalCookie);
        self::assertStringContainsString('Max-Age=0', (string)$removalCookie);

        self::assertInstanceOf(Cookie::class, $creationCookie);
        self::assertSame($creationCookie->getName(), $removalCookie->getName());
        self::assertSame($creationCookie->getPath(), $removalCookie->getPath());
        self::assertSame($creationCookie->getDomain(), $removalCookie->getDomain());
        self::assertSame($creationCookie->isSecure(), $removalCookie->isSecure());
        self::assertSame($creationCookie->isHttpOnly(), $removalCookie->isHttpOnly());
        self::assertSame($creationCookie->getSameSite(), $removalCookie->getSameSite());
    }
}
