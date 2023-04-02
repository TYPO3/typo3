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

namespace TYPO3\CMS\Core\Tests\Unit\Routing;

use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Core\Routing\RouteSorter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RouteSorterTest extends UnitTestCase
{
    public static function routesAreSortedForGenerationDataProvider(): array
    {
        return [
            'default route only' => [
                // routes
                [
                    self::createDefaultRoute('/default'),
                ],
                // given parameters
                [],
                // expected route paths order
                [
                    '/default',
                ],
            ],
            'static, default route' => [
                [
                    self::createDefaultRoute('/default-1'),
                    self::createRoute('/list'),
                    self::createDefaultRoute('/default-2'),
                ],
                [],
                [
                    '/list',
                    '/default-1',
                    '/default-2',
                ],
            ],
            'mandatory, static, default route' => [
                [
                    self::createDefaultRoute('/default'),
                    self::createRoute('/list'),
                    self::createRoute('/list/{page}', ['page' => 0]),
                ],
                [],
                [
                    '/list/{page}',
                    '/list',
                    '/default',
                ],
            ],
            // not really important, since missing mandatory
            // variables would have been skipped during generation
            'ambiguous routes, no parameters, most probable' => [
                [
                    self::createRoute('/list'),
                    self::createRoute('/list/{uid}'),
                    self::createRoute('/list/{uid}/{category}', ['category' => 0]),
                    self::createRoute('/list/{page}', ['page' => 0]),
                    self::createRoute('/list/{category}', ['category' => 0]),
                ],
                [],
                [
                    '/list/{page}', // no parameters given -> defaults take precedence
                    '/list/{category}', // no parameters given -> defaults take precedence
                    '/list/{uid}',
                    '/list/{uid}/{category}',
                    '/list',
                ],
            ],
            'mandatory first, ambiguous parameters' => [
                [
                    self::createRoute('/list'),
                    self::createRoute('/list/{uid}'),
                    self::createRoute('/list/{uid}/{category}', ['category' => 0]),
                    self::createRoute('/list/{page}', ['page' => 0]),
                    self::createRoute('/list/{category}', ['category' => 0]),
                ],
                [
                    'uid' => 123,
                    'page' => 1,
                ],
                [
                    '/list/{uid}', // value for {uid} given, complete mandatory first -> takes precedence
                    '/list/{page}', // value for {page} given, complete first -> takes precedence
                    '/list/{uid}/{category}',
                    '/list/{category}',
                    '/list',
                ],
            ],
            'complete first, ambiguous parameters #1' => [
                [
                    self::createRoute('/list'),
                    self::createRoute('/list/{uid}'),
                    self::createRoute('/list/{uid}/{category}', ['category' => 0]),
                    self::createRoute('/list/{page}', ['page' => 0]),
                    self::createRoute('/list/{category}', ['category' => 0]),
                ],
                [
                    'category' => 1,
                    'page' => 1,
                ],
                [
                    '/list/{page}', // value for default {page} given, complete first -> takes precedence
                    '/list/{category}',
                    '/list/{uid}',
                    '/list/{uid}/{category}',
                    '/list',
                ],
            ],
            'complete first, ambiguous parameters #2' => [
                [
                    self::createRoute('/list'),
                    self::createRoute('/list/{uid}'),
                    self::createRoute('/list/{uid}/{category}', ['category' => 0]),
                    self::createRoute('/list/{page}', ['page' => 0]),
                    self::createRoute('/list/{category}', ['category' => 0]),
                ],
                [
                    'uid' => 123,
                    'page' => 1,
                    'category' => 2,
                ],
                [
                    '/list/{uid}/{category}', // values for {uid} and {category} given, complete first -> takes precedence
                    '/list/{uid}',
                    '/list/{page}',
                    '/list/{category}',
                    '/list',
                ],
            ],
            // not really important, just to show order is kept
            'defaults only, no parameters given #1' => [
                [
                    self::createRoute('/list/{defA}/{defB}/{defC}', ['defA' => 0, 'defB' => 0, 'defC' => 0]),
                    self::createRoute('/list/{defD}/{defE}/{defF}', ['defD' => 0, 'defE' => 0, 'defF' => 0]),
                ],
                [
                ],
                [
                    '/list/{defA}/{defB}/{defC}',
                    '/list/{defD}/{defE}/{defF}',
                ],
            ],
            // not really important, just to show order is kept
            'defaults only, no parameters given #2' => [
                [
                    self::createRoute('/list/{defD}/{defE}/{defF}', ['defD' => 0, 'defE' => 0, 'defF' => 0]),
                    self::createRoute('/list/{defA}/{defB}/{defC}', ['defA' => 0, 'defB' => 0, 'defC' => 0]),
                ],
                [
                ],
                [
                    '/list/{defD}/{defE}/{defF}',
                    '/list/{defA}/{defB}/{defC}',
                ],
            ],
            'defaults only, {defF} given, best match' => [
                [
                    self::createRoute('/list/{defA}/{defB}/{defC}', ['defA' => 0, 'defB' => 0, 'defC' => 0]),
                    self::createRoute('/list/{defD}/{defE}/{defF}', ['defD' => 0, 'defE' => 0, 'defF' => 0]),
                ],
                [
                    'defF' => 1,
                ],
                [
                    '/list/{defD}/{defE}/{defF}', // {defF} given, best match -> takes precedence
                    '/list/{defA}/{defB}/{defC}',
                ],
            ],
            'mixed variables, ambiguous parameters, complete mandatory first #1' => [
                [
                    self::createRoute('/list/{d}/{e}/{defF}', ['defF' => 0]),
                    self::createRoute('/list/{a}/{defB}/{defC}', ['defB' => 0, 'defC' => 0]),
                ],
                [
                    'a' => 1,
                    'd' => 1,
                    'defF' => 1,
                ],
                [
                    '/list/{a}/{defB}/{defC}', // mandatory {a} given, complete mandatory first -> takes precedence
                    '/list/{d}/{e}/{defF}',
                ],
            ],
            'mixed variables, ambiguous parameters, complete mandatory first #2' => [
                [
                    self::createRoute('/list/{a}/{defB}/{defC}', ['defB' => 0, 'defC' => 0]),
                    self::createRoute('/list/{d}/{e}/{defF}', ['defF' => 0]),
                ],
                [
                    'd' => 1,
                    'e' => 1,
                    'defB' => 1,
                    'defC' => 1,
                ],
                [
                    '/list/{d}/{e}/{defF}', // mandatory {d} and {e} given, complete mandatory first -> takes precedence
                    '/list/{a}/{defB}/{defC}',
                ],
            ],
            'mixed variables, ambiguous parameters, complete first' => [
                [
                    self::createRoute('/list/{d}/{e}/{defF}', ['defF' => 0]),
                    self::createRoute('/list/{a}/{defB}/{defC}', ['defB' => 0, 'defC' => 0]),
                ],
                [
                    'd' => 1,
                    'e' => 1,
                    'a' => 1,
                    'defB' => 1,
                    'defC' => 1,
                ],
                [
                    '/list/{a}/{defB}/{defC}', // all parameters given, complete first -> takes precedence
                    '/list/{d}/{e}/{defF}',
                ],
            ],
        ];
    }

    /**
     * @param Route[] $givenRoutes
     * @param array<string, string> $givenParameters
     * @param string[] $expectation
     *
     * @test
     * @dataProvider routesAreSortedForGenerationDataProvider
     */
    public function routesAreSortedForGeneration(array $givenRoutes, array $givenParameters, array $expectation): void
    {
        $sorter = (new RouteSorter())
            ->withRoutes($givenRoutes)
            ->withOriginalParameters($givenParameters);
        $routes = $sorter->sortRoutesForGeneration()->getRoutes();
        $routePaths = array_map([$this, 'getRoutePath'], array_values($routes));
        self::assertSame($expectation, $routePaths);
    }

    private function getRoutePath(Route $route): string
    {
        return $route->getPath();
    }

    private static function createRoute(string $path, array $defaults = []): Route
    {
        $route = new Route($path);
        $route->setDefaults($defaults);
        return $route;
    }

    private static function createDefaultRoute(string $path): Route
    {
        $route = new Route($path);
        $route->setOption('_isDefault', true);
        return $route;
    }
}
