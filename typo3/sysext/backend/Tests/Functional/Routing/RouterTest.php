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

namespace TYPO3\CMS\Backend\Tests\Functional\Routing;

use TYPO3\CMS\Backend\Routing\Exception\MethodNotAllowedException;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RouterTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function routerReturnsRouteForAlias(): void
    {
        $subject = GeneralUtility::makeInstance(Router::class);
        $subject->addRoute(
            'new_route_identifier',
            new Route('/new/route/path', []),
            ['old_route_identifier']
        );
        self::assertTrue($subject->hasRoute('new_route_identifier'));
        self::assertTrue($subject->hasRoute('old_route_identifier'));
    }

    /**
     * @test
     */
    public function matchRequestFindsProperRoute(): void
    {
        $subject = GeneralUtility::makeInstance(Router::class);
        $request = new ServerRequest('https://example.com/login', 'GET');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $resultRoute = $subject->matchRequest($request);
        self::assertInstanceOf(Route::class, $resultRoute);
        self::assertEquals('/login', $resultRoute->getPath());
    }

    /**
     * @test
     */
    public function matchRequestThrowsExceptionOnInvalidRoute(): void
    {
        $subject = GeneralUtility::makeInstance(Router::class);
        $request = new ServerRequest('https://example.com/this-path/does-not-exist', 'GET');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $this->expectException(ResourceNotFoundException::class);
        $subject->matchRequest($request);
    }

    /**
     * @test
     */
    public function matchRequestThrowsInvalidMethodForValidRoute(): void
    {
        $subject = GeneralUtility::makeInstance(Router::class);
        $request = new ServerRequest('https://example.com/login/password-reset/initiate-reset', 'GET');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $this->expectException(MethodNotAllowedException::class);
        $subject->matchRequest($request);
    }

    /**
     * @test
     */
    public function matchRequestReturnsRouteWithMethodLimitation(): void
    {
        $subject = GeneralUtility::makeInstance(Router::class);
        $request = new ServerRequest('https://example.com/login/password-reset/initiate-reset', 'POST');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $resultRoute = $subject->matchRequest($request);
        self::assertInstanceOf(Route::class, $resultRoute);
        self::assertEquals('/login/password-reset/initiate-reset', $resultRoute->getPath());
    }
}
