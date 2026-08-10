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

namespace TYPO3\CMS\Install\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Install\Middleware\PathGuard;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PathGuardTest extends UnitTestCase
{
    public static function requestIsRedirectedToSitePathDataProvider(): array
    {
        return [
            'legacy backend directory' => [
                '/index.php', // scriptName
                'https://example.org/typo3/?__typo3_install', // requestUri
                'https://example.org/?__typo3_install', // expected location
            ],
            'entry script' => [
                '/index.php',
                'https://example.org/index.php?__typo3_install',
                'https://example.org/?__typo3_install',
            ],
            'arbitrary frontend path' => [
                '/index.php',
                'https://example.org/some/page?__typo3_install&install[controller]=maintenance',
                'https://example.org/?__typo3_install&install%5Bcontroller%5D=maintenance',
            ],
            'instance in a sub directory' => [
                '/subdir/index.php',
                'https://example.org/subdir/some/page?__typo3_install',
                'https://example.org/subdir/?__typo3_install',
            ],
        ];
    }

    #[DataProvider('requestIsRedirectedToSitePathDataProvider')]
    #[Test]
    public function requestIsRedirectedToSitePath(string $scriptName, string $requestUri, string $expectedLocation): void
    {
        $response = new PathGuard()->process(
            $this->buildRequest($scriptName, $requestUri),
            $this->buildFailingHandler()
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame($expectedLocation, $response->getHeaderLine('location'));
    }

    public static function requestOnSitePathIsPassedThroughDataProvider(): array
    {
        return [
            'site root' => [
                '/index.php',
                'https://example.org/?__typo3_install',
            ],
            'site root in a sub directory' => [
                '/subdir/index.php',
                'https://example.org/subdir/?__typo3_install&install[controller]=upgrade',
            ],
        ];
    }

    #[DataProvider('requestOnSitePathIsPassedThroughDataProvider')]
    #[Test]
    public function requestOnSitePathIsPassedThrough(string $scriptName, string $requestUri): void
    {
        $request = $this->buildRequest($scriptName, $requestUri);
        $expectedResponse = new NullResponse();
        $handler = new class ($request, $expectedResponse) implements RequestHandlerInterface {
            public function __construct(
                private readonly ServerRequestInterface $expectedRequest,
                private readonly ResponseInterface $response
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                PathGuardTest::assertSame($this->expectedRequest, $request);
                return $this->response;
            }
        };

        self::assertSame($expectedResponse, new PathGuard()->process($request, $handler));
    }

    private function buildFailingHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                PathGuardTest::fail('Request handler must not be invoked for a redirected request.');
            }
        };
    }

    private function buildRequest(string $scriptName, string $requestUri): ServerRequestInterface
    {
        $requestUriInstance = new Uri($requestUri);
        $serverParams = [
            'HTTP_HOST' => $requestUriInstance->getHost(),
            'SCRIPT_NAME' => $scriptName,
            'REQUEST_URI' => $requestUriInstance->getPath() . ($requestUriInstance->getQuery() ? '?' . $requestUriInstance->getQuery() : ''),
            'HTTPS' => $requestUriInstance->getScheme() === 'https' ? 'on' : 'off',
        ];
        $request = new ServerRequest($requestUriInstance, null, null, [], $serverParams);
        return $request->withAttribute('normalizedParams', new NormalizedParams($serverParams, [], '', ''));
    }
}
