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

namespace TYPO3\CMS\Frontend\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Middleware\StaticRouteResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class StaticRouteResolverTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;
    protected RequestHandlerInterface $requestHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestHandler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new NullResponse();
            }
        };
    }

    #[Test]
    public function invalidStaticRouteDoesNotWork(): void
    {
        $subject = $this->get(StaticRouteResolver::class);
        $site = new Site('lotus-flower', 13, [
            'base' => 'https://example.com/',
            'languages' => [
                0 => [
                    'languageId' => 0,
                    'locale' => 'en_US.UTF-8',
                    'base' => '/en/',
                ],
            ],
            'routes' => [
                [
                    'route' => '/lotus/',
                    'type' => 'staticText',
                    'content' => 'nice',
                ],
                [
                    'route' => null,
                    'type' => 'staticText',
                    'content' => 'no-route',
                ],
                [
                    'route' => '',
                    'type' => 'staticText',
                    'content' => 'empty-route',
                ],
                [
                    'route' => '/empty-type',
                    'type' => '',
                ],
                [
                    'route' => '/no-type',
                ],
                [
                    'route' => '',
                    'type' => '',
                ],
            ],
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

    #[Test]
    public function assetRoutesProvideProperResponse(): void
    {
        $subject = $this->get(StaticRouteResolver::class);
        $site = new Site('lotus-flower', 13, [
            'base' => 'https://example.com/',
            'languages' => [
                0 => [
                    'languageId' => 0,
                    'locale' => 'en_US.UTF-8',
                    'base' => '/',
                ],
            ],
            'routes' => [
                [
                    'route' => '/extension.svg',
                    'type' => 'asset',
                    'asset' => 'EXT:frontend/Resources/Public/Icons/Extension.svg',
                ],
                [
                    'route' => '/some/subdirectory/extension.svg',
                    'type' => 'asset',
                    'asset' => 'EXT:frontend/Resources/Public/Icons/Extension.svg',
                ],
            ],
        ]);

        $request = new ServerRequest('https://example.com/extension.svg');
        $request = $request->withAttribute('site', $site);
        $response = $subject->process($request, $this->requestHandler);
        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertEquals(file_get_contents(GeneralUtility::getFileAbsFileName('EXT:frontend/Resources/Public/Icons/Extension.svg')), $response->getBody()->getContents());

        $request = new ServerRequest('https://example.com/some/subdirectory/extension.svg');
        $request = $request->withAttribute('site', $site);
        $response = $subject->process($request, $this->requestHandler);
        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertEquals(file_get_contents(GeneralUtility::getFileAbsFileName('EXT:frontend/Resources/Public/Icons/Extension.svg')), $response->getBody()->getContents());

        $request = new ServerRequest('https://example.com/invalid-file');
        $request = $request->withAttribute('site', $site);
        $response = $subject->process($request, $this->requestHandler);
        self::assertInstanceOf(NullResponse::class, $response);
    }

    public static function assetRoutesResponseTriggersExceptionForInvalidAssetDataProvider(): \Generator
    {
        yield 'empty asset' => [
            'some-route.svg',
            '',
            1721134959,
        ];
        yield 'invalid asset, with invalid path' => [
            'some-route-2.svg',
            '../path-to-invalid-asset.svg',
            1721134960,
        ];
        yield 'missing asset, with valid path' => [
            'some-route-2.svg',
            './././path-to-invalid-asset.svg',
            1721134960,
        ];
        yield 'missing asset, with valid path and subdirectory route' => [
            '/path/to/somedirectory/with/some-route-2.svg',
            './././path-to-invalid-asset.svg',
            1721134960,
        ];
        yield 'missing asset, with EXT notation' => [
            'some-route-2.svg',
            'EXT:missingExt/Resources/NoPath/NoFile.svg',
            1721134960,
        ];
    }

    #[Test]
    #[DataProvider('assetRoutesResponseTriggersExceptionForInvalidAssetDataProvider')]
    public function assetRoutesResponseTriggersExceptionForInvalidAsset(string $route, string $asset, int $exceptionCode): void
    {
        $subject = $this->get(StaticRouteResolver::class);
        $site = new Site('lotus-flower', 13, [
            'base' => 'https://example.com/',
            'languages' => [
                0 => [
                    'languageId' => 0,
                    'locale' => 'en_US.UTF-8',
                    'base' => '/',
                ],
            ],
            'routes' => [
                [
                    'route' => $route,
                    'type' => 'asset',
                    'asset' => $asset,
                ],
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($exceptionCode);

        $request = new ServerRequest('https://example.com/' . $route);
        $request = $request->withAttribute('site', $site);
        $subject->process($request, $this->requestHandler);
    }
}
