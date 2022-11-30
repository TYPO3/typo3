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
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RouterTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function routerReturnsRouteForAlias(): void
    {
        $subject = $this->get(Router::class);
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
    public function matchResultFindsProperRoute(): void
    {
        $subject = $this->get(Router::class);
        $request = new ServerRequest('https://example.com/login', 'GET');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $result = $subject->matchResult($request);
        self::assertEquals('/login', $result->getRoute()->getPath());
    }

    /**
     * @test
     */
    public function matchResultThrowsExceptionOnInvalidRoute(): void
    {
        $subject = $this->get(Router::class);
        $request = new ServerRequest('https://example.com/this-path/does-not-exist', 'GET');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $this->expectException(ResourceNotFoundException::class);
        $subject->matchResult($request);
    }

    /**
     * @test
     */
    public function matchResultThrowsInvalidMethodForValidRoute(): void
    {
        $subject = $this->get(Router::class);
        $request = new ServerRequest('https://example.com/login/password-reset/initiate-reset', 'GET');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $this->expectException(MethodNotAllowedException::class);
        $subject->matchResult($request);
    }

    /**
     * @test
     */
    public function matchResultReturnsRouteWithMethodLimitation(): void
    {
        $subject = $this->get(Router::class);
        $request = new ServerRequest('https://example.com/login/password-reset/initiate-reset', 'POST');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $result = $subject->matchResult($request);
        self::assertEquals('/login/password-reset/initiate-reset', $result->getRoute()->getPath());
    }

    /**
     * @test
     */
    public function matchResultReturnsRouteWithPlaceholderAndMethodLimitation(): void
    {
        $subject = $this->get(Router::class);
        $subject->addRoute('custom-route', new Route('/my-path/{identifier}', []));
        $request = new ServerRequest('https://example.com/my-path/my-identifier', 'POST');
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $result = $subject->matchResult($request);
        self::assertEquals('custom-route', $result->getRouteName());
        self::assertEquals(['identifier' => 'my-identifier'], $result->getArguments());
    }
}
