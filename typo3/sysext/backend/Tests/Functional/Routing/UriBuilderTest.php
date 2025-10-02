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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UriBuilderTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = ['workspaces'];

    #[Test]
    public function buildUriFromRouteResolvesAliasWhenLinking(): void
    {
        $subject = $this->get(UriBuilder::class);
        $route = $subject->buildUriFromRoute('workspaces_admin');
        $routeFromAlias = $subject->buildUriFromRoute('web_WorkspacesWorkspaces');
        self::assertEquals($routeFromAlias->getPath(), $route->getPath());
    }

    #[Test]
    public function buildUriFromRouteResolvesSubModule(): void
    {
        $subject = $this->get(UriBuilder::class);
        $uri = $subject->buildUriFromRoute('site_configuration.edit');
        self::assertStringEndsWith('/module/site/configuration/edit', $uri->getPath());
    }

    #[Test]
    public function buildUriFromRequestCanLinkToValidRoute(): void
    {
        $subject = $this->get(UriBuilder::class);
        $route = $this->get(Router::class)->getRoute('site_configuration.edit');
        $route->setOption('_identifier', 'site_configuration.edit');
        $request = new ServerRequest('https://example.com/', 'GET');
        $request = $request->withAttribute('route', $route);
        $uri = $subject->buildUriFromRequest($request, ['foo' => 'bar']);
        self::assertStringEndsWith('/module/site/configuration/edit', $uri->getPath());
    }

    #[Test]
    public function buildUriFromRequestWithInvalidRouteThrowsException(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionCode(1476050190);
        $subject = $this->get(UriBuilder::class);
        $route = $this->get(Router::class)->getRoute('site_configuration.edit');
        $request = new ServerRequest('https://example.com/', 'GET');
        // Route is not found in the registry of routes
        $route->setOption('_identifier', 'foo.bar');
        $request = $request->withAttribute('route', $route);
        $subject->buildUriFromRequest($request, ['foo' => 'bar']);
    }

    #[Test]
    public function buildUriFromRequestWithoutRouteThrowsException(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionCode(1691423325);
        $subject = $this->get(UriBuilder::class);
        $request = new ServerRequest('https://example.com/', 'GET');
        $subject->buildUriFromRequest($request, ['foo' => 'bar']);
    }
}
