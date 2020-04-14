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

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class UriBuilderTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function validRoutesAreBuiltDataProvider()
    {
        return [
            'plain route' => [
                [ 'route' => new Route('/test/route', []) ],
                'route',
                [],
                '/typo3/index.php?route=%2Ftest%2Froute&token=dummyToken',
            ],
            'AJAX route' => [
                [ 'route' => new Route('/test/route', [ 'ajax' => true ]) ],
                'route',
                [],
                '/typo3/index.php?route=%2Ftest%2Froute&token=dummyToken',
            ],
            'plain route with default parameters' => [
                [ 'route' => new Route('/test/route', [ 'parameters' => [ 'key' => 'value' ] ]) ],
                'route',
                [],
                '/typo3/index.php?route=%2Ftest%2Froute&token=dummyToken&key=value',
            ],
            'AJAX route with default parameters' => [
                [ 'route' => new Route('/test/route', [ 'ajax' => true, 'parameters' => [ 'key' => 'value' ] ]) ],
                'route',
                [],
                '/typo3/index.php?route=%2Ftest%2Froute&token=dummyToken&key=value',
            ],
            'plain route with overridden parameters' => [
                [ 'route' => new Route('/test/route', [ 'parameters' => [ 'key' => 'value' ] ]) ],
                'route',
                ['key' => 'overridden'],
                '/typo3/index.php?route=%2Ftest%2Froute&token=dummyToken&key=overridden',
            ],
            'AJAX route with overridden parameters' => [
                [ 'route' => new Route('/test/route', [ 'ajax' => true, 'parameters' => [ 'key' => 'value' ] ]) ],
                'route',
                ['key' => 'overridden'],
                '/typo3/index.php?route=%2Ftest%2Froute&token=dummyToken&key=overridden',
            ],
        ];
    }

    /**
     * @param Route[] $routes
     * @param string $routeName
     * @param array $routeParameters
     * @param string $expectation
     *
     * @test
     * @dataProvider validRoutesAreBuiltDataProvider
     */
    public function validRoutesAreBuilt(
        array $routes,
        string $routeName,
        array $routeParameters,
        string $expectation
    ) {
        $router = new Router();
        foreach ($routes as $routeName => $route) {
            $router->addRoute($routeName, $route);
        }
        $subject = new UriBuilder($router);
        $uri = $subject->buildUriFromRoute(
            $routeName,
            $routeParameters
        );

        self::assertEquals($expectation, $uri->__toString());
    }

    /**
     * @test
     */
    public function nonExistingRouteThrowsException()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionCode(1476050190);
        $subject = new UriBuilder(new Router());
        $subject->buildUriFromRoute(uniqid('any'));
    }
}
