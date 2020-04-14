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

namespace TYPO3\CMS\Frontend\Tests\Unit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\Middleware\StaticRouteResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StaticRouteResolverTest extends UnitTestCase
{
    protected $requestHandler;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        // A request handler which expects a site to be found.
        $this->requestHandler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new NullResponse();
            }
        };
    }

    /**
     * @test
     */
    public function invalidStaticRouteDoesNotWork()
    {
        $requestFactoryProphecy = $this->prophesize(RequestFactory::class);
        $linkServiceProphecy = $this->prophesize(LinkService::class);
        $subject = new StaticRouteResolver(
            $requestFactoryProphecy->reveal(),
            $linkServiceProphecy->reveal()
        );
        $site = new Site('lotus-flower', 13, [
            'base' => 'https://example.com/',
            'languages' => [
                0 => [
                    'languageId' => 0,
                    'locale' => 'en_US.UTF-8',
                    'base' => '/en/'
                ],
            ],
            'routes' => [
                [
                    'route' => '/lotus/',
                    'type' => 'staticText',
                    'content' => 'nice'
                ],
                [
                    'route' => null,
                    'type' => 'staticText',
                    'content' => 'no-route'
                ],
                [
                    'route' => '',
                    'type' => 'staticText',
                    'content' => 'empty-route'
                ],
                [
                    'route' => '/empty-type',
                    'type' => ''
                ],
                [
                    'route' => '/no-type'
                ],
                [
                    'route' => '',
                    'type' => ''
                ]
            ]
        ]);

        $request = new ServerRequest('https://example.com/lotus/');
        $request = $request->withAttribute('site', $site);
        $response = $subject->process($request, $this->requestHandler);
        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertEquals('nice', $response->getBody()->getContents());

        $request = new ServerRequest('https://example.com/nothing');
        $request = $request->withAttribute('site', $site);
        $response = $subject->process($request, $this->requestHandler);
        self::assertInstanceOf(NullResponse::class, $response);

        $request = new ServerRequest('https://example.com/no-type');
        $request = $request->withAttribute('site', $site);
        $response = $subject->process($request, $this->requestHandler);
        self::assertInstanceOf(NullResponse::class, $response);
    }
}
