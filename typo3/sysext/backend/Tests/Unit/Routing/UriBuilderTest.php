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

namespace TYPO3\CMS\Backend\Tests\Unit\Routing;

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UriBuilderTest extends UnitTestCase
{
    public function validRoutesAreBuiltDataProvider(): array
    {
        return [
            'plain route' => [
                [ 'route' => new Route('/test/route', []) ],
                'route',
                [],
                '/typo3/test/route?token=dummyToken',
            ],
            'AJAX route' => [
                [ 'route' => new Route('/test/route', [ 'ajax' => true ]) ],
                'route',
                [],
                '/typo3/test/route?token=dummyToken',
            ],
            'plain route with default parameters' => [
                [ 'route' => new Route('/test/route', [ 'parameters' => [ 'key' => 'value' ] ]) ],
                'route',
                [],
                '/typo3/test/route?token=dummyToken&key=value',
            ],
            'AJAX route with default parameters' => [
                [ 'route' => new Route('/test/route', [ 'ajax' => true, 'parameters' => [ 'key' => 'value' ] ]) ],
                'route',
                [],
                '/typo3/test/route?token=dummyToken&key=value',
            ],
            'plain route with overridden parameters' => [
                [ 'route' => new Route('/test/route', [ 'parameters' => [ 'key' => 'value' ] ]) ],
                'route',
                ['key' => 'overridden'],
                '/typo3/test/route?token=dummyToken&key=overridden',
            ],
            'AJAX route with overridden parameters' => [
                [ 'route' => new Route('/test/route', [ 'ajax' => true, 'parameters' => [ 'key' => 'value' ] ]) ],
                'route',
                ['key' => 'overridden'],
                '/typo3/test/route?token=dummyToken&key=overridden',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validRoutesAreBuiltDataProvider
     */
    public function validRoutesAreBuilt(
        array $routes,
        string $routeName,
        array $routeParameters,
        string $expectation
    ): void {
        $router = new Router();
        foreach ($routes as $nameRoute => $route) {
            $router->addRoute($nameRoute, $route);
        }
        $subject = new UriBuilder($router, new BackendEntryPointResolver());
        $uri = $subject->buildUriFromRoute(
            $routeName,
            $routeParameters
        );

        self::assertEquals($expectation, $uri->__toString());
    }

    /**
     * @test
     */
    public function nonExistingRouteThrowsException(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionCode(1476050190);
        $subject = new UriBuilder(new Router(), new BackendEntryPointResolver());
        $subject->buildUriFromRoute(StringUtility::getUniqueId('any'));
    }
}
