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

use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Routing\Exception\MethodNotAllowedException;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\RouteCollection;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RouterTest extends FunctionalTestCase
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
        $serverParams = array_replace($_SERVER, ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/index.php']);
        $request = new ServerRequest('https://example.com/typo3/login', 'GET', null, [], $serverParams);
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
        $serverParams = array_replace($_SERVER, ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/index.php']);
        $request = new ServerRequest('https://example.com/typo3/this-path/does-not-exist', 'GET', null, [], $serverParams);
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
        $serverParams = array_replace($_SERVER, ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/index.php']);
        $request = new ServerRequest('https://example.com/typo3/login/password-reset/initiate-reset', 'GET', null, [], $serverParams);
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
        $serverParams = array_replace($_SERVER, ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/index.php']);
        $request = new ServerRequest('https://example.com/typo3/login/password-reset/initiate-reset', 'POST', null, [], $serverParams);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $result = $subject->matchResult($request);
        self::assertEquals('/login/password-reset/initiate-reset', $result->getRoute()->getPath());
    }

    /**
     * @test
     */
    public function matchResultReturnsRouteForBackendModuleWithMethodLimitation(): void
    {
        $subject = $this->get(Router::class);
        $serverParams = array_replace($_SERVER, ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/index.php']);
        $request = new ServerRequest('https://example.com/typo3/module/site/configuration/delete', 'POST', null, [], $serverParams);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $result = $subject->matchResult($request);
        self::assertEquals('/module/site/configuration/delete', $result->getRoute()->getPath());
        self::assertInstanceOf(ModuleInterface::class, $result->getRoute()->getOption('module'));
    }

    /**
     * @test
     */
    public function matchResultThrowsExceptionForWrongHttpMethod(): void
    {
        $this->expectException(MethodNotAllowedException::class);
        $this->expectExceptionCode(1612649842);

        $subject = $this->get(Router::class);
        $serverParams = array_replace($_SERVER, ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/index.php']);
        $request = new ServerRequest('https://example.com/typo3/module/site/configuration/delete', 'GET', null, [], $serverParams);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $subject->matchResult($request);
    }

    /**
     * @test
     */
    public function matchResultReturnsRouteWithPlaceholderAndMethodLimitation(): void
    {
        $subject = $this->get(Router::class);
        $subject->addRoute('custom-route', (new Route('/my-path/{identifier}', []))->setMethods(['POST']));
        $serverParams = array_replace($_SERVER, ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/index.php']);
        $request = new ServerRequest('https://example.com/typo3/my-path/my-identifier', 'POST', null, [], $serverParams);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $result = $subject->matchResult($request);
        self::assertEquals('custom-route', $result->getRouteName());
        self::assertEquals(['identifier' => 'my-identifier'], $result->getArguments());
    }

    /**
     * @test
     */
    public function matchResultReturnsRouteForSubRoute(): void
    {
        $subject = $this->get(Router::class);
        $subject->addRoute('main_module', new Route('/module/main/module', []));
        $routeCollection = new RouteCollection();
        $routeCollection->add('subroute', new Route('/subroute', []));
        $routeCollection->addNamePrefix('main_module.');
        $routeCollection->addPrefix('/module/main/module');
        $subject->addRouteCollection($routeCollection);

        $serverParams = array_replace($_SERVER, ['HTTP_HOST' => 'example.com', 'HTTPS' => 'on', 'SCRIPT_NAME' => '/index.php']);

        $request = new ServerRequest('/typo3/module/main/module', 'GET', null, [], $serverParams);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $resultMainModule = $subject->matchResult($request);
        self::assertEquals('main_module', $resultMainModule->getRouteName());
        self::assertEquals('/module/main/module', $resultMainModule->getRoute()->getPath());

        $request = new ServerRequest('/typo3/module/main/module/subroute', 'GET', null, [], $serverParams);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $resultSubRoute = $subject->matchResult($request);
        self::assertEquals('main_module.subroute', $resultSubRoute->getRouteName());
        self::assertEquals('/module/main/module/subroute', $resultSubRoute->getRoute()->getPath());
    }
}
