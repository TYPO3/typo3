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

namespace TYPO3\CMS\Backend\Tests\Functional\Routing;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteAccess;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Pins the anonymously reachable backend surface of the core. Adding an anonymous route
 * has to be a deliberate decision, which is why this list must be extended by hand.
 */
final class RouteAccessTest extends FunctionalTestCase
{
    private const EXPECTED_ANONYMOUS_ROUTES = [
        'ajax_login',
        'ajax_login_preflight',
        'ajax_login_refresh',
        'ajax_login_timedout',
        'ajax_logout',
        'install.php.redirect',
        'install.redirect',
        'install.server-response-check.host',
        'login',
        'login_frameset',
        'password_forget',
        'password_forget_initiate_reset',
        'password_reset_finish',
        'password_reset_validate',
        'login_request_token',
        'reaction',
    ];

    private const EXPECTED_WITHOUT_TOKEN = [
        'state-tracker',
        'language_domain',
    ];

    protected array $coreExtensionsToLoad = ['install', 'reactions'];

    #[Test]
    public function anonymousRoutesMatchTheAllowlist(): void
    {
        $actual = [];
        foreach ($this->get(Router::class)->getRoutes() as $identifier => $route) {
            if (RouteAccess::fromRoute($route) === RouteAccess::Anonymous) {
                $actual[] = $identifier;
            }
        }
        sort($actual);
        $expected = self::EXPECTED_ANONYMOUS_ROUTES;
        sort($expected);
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function routesWithoutRequestTokenMatchTheAllowlist(): void
    {
        $actual = [];
        foreach ($this->get(Router::class)->getRoutes() as $identifier => $route) {
            if (RouteAccess::fromRoute($route) === RouteAccess::AuthenticatedWithoutToken) {
                $actual[] = $identifier;
            }
        }
        sort($actual);
        $expected = self::EXPECTED_WITHOUT_TOKEN;
        sort($expected);
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function everyRouteResolvedFromTheRouterKnowsItsAccess(): void
    {
        $route = $this->get(Router::class)->match('/login');
        self::assertInstanceOf(Route::class, $route);
        self::assertSame(RouteAccess::Anonymous, $route->getAccess());
    }
}
