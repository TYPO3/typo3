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

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\SetCookieService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SetCookieServiceTest extends UnitTestCase
{
    #[Test]
    public function removeCookiePreservesCookieAttributes(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cookieSameSite'] = Cookie::SAMESITE_NONE;
        $GLOBALS['TYPO3_CONF_VARS']['FE']['lifetime'] = 0;

        $service = SetCookieService::create('fe_typo_user', 'FE');
        $normalizedParams = new NormalizedParams([
            'HTTP_HOST' => 'www.example.com',
            'HTTPS' => 'ON',
        ], [], '/index.php', 'D:/typo3');

        $cookie = $service->removeCookie($normalizedParams);

        self::assertSame('fe_typo_user', $cookie->getName());
        self::assertSame('', $cookie->getValue());
        self::assertSame(-1, $cookie->getExpiresTime());
        self::assertSame('/', $cookie->getPath());
        self::assertNull($cookie->getDomain());
        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame(Cookie::SAMESITE_NONE, $cookie->getSameSite());
    }

    #[Test]
    public function removeCookieFallsBackToStrictSameSiteOnHttp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cookieSameSite'] = Cookie::SAMESITE_NONE;
        $GLOBALS['TYPO3_CONF_VARS']['FE']['lifetime'] = 0;

        $service = SetCookieService::create('fe_typo_user', 'FE');
        $normalizedParams = new NormalizedParams([
            'HTTP_HOST' => 'www.example.com',
            'HTTPS' => '0',
        ], [], '/index.php', 'D:/typo3');

        $cookie = $service->removeCookie($normalizedParams);

        self::assertFalse($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
    }
}
