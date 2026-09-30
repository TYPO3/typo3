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

namespace TYPO3\CMS\Backend\Tests\Unit\Routing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Routing\Route as SymfonyRoute;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteAccess;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RouteAccessTest extends UnitTestCase
{
    public static function routeOptionsDataProvider(): \Generator
    {
        yield 'no option is authenticated' => [[], RouteAccess::Authenticated, true, true];
        yield 'anonymous needs neither user nor token' => [['access' => 'anonymous'], RouteAccess::Anonymous, false, false];
        yield 'authenticated needs user and token' => [['access' => 'authenticated'], RouteAccess::Authenticated, true, true];
        yield 'authenticated without token needs only the user' => [['access' => 'authenticated-without-token'], RouteAccess::AuthenticatedWithoutToken, true, false];
        yield 'legacy public maps to authenticated without token' => [['access' => 'public'], RouteAccess::AuthenticatedWithoutToken, true, false];
        yield 'unknown value fails closed' => [['access' => 'anonymus'], RouteAccess::Authenticated, true, true];
        yield 'module access gate identifier is authenticated' => [['access' => 'systemMaintainer'], RouteAccess::Authenticated, true, true];
        yield 'module routes can never be anonymous' => [['access' => 'anonymous', 'module' => new \stdClass()], RouteAccess::Authenticated, true, true];
        yield 'module routes carry the module access and are authenticated' => [['access' => 'admin', 'module' => new \stdClass()], RouteAccess::Authenticated, true, true];
    }

    #[Test]
    #[DataProvider('routeOptionsDataProvider')]
    public function backendRouteResolvesAccess(array $options, RouteAccess $expectedAccess, bool $expectedAuthentication, bool $expectedToken): void
    {
        $subject = new Route('/test', $options)->getAccess();
        self::assertSame($expectedAccess, $subject);
        self::assertSame($expectedAuthentication, $subject->requiresAuthentication());
        self::assertSame($expectedToken, $subject->requiresRequestToken());
    }

    #[Test]
    #[DataProvider('routeOptionsDataProvider')]
    public function symfonyRouteResolvesAccess(array $options, RouteAccess $expectedAccess, bool $expectedAuthentication, bool $expectedToken): void
    {
        $subject = RouteAccess::fromRoute(new SymfonyRoute('/test', [], [], $options));
        self::assertSame($expectedAccess, $subject);
        self::assertSame($expectedAuthentication, $subject->requiresAuthentication());
        self::assertSame($expectedToken, $subject->requiresRequestToken());
    }
}
