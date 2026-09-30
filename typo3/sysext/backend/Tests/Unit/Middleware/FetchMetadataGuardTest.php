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

namespace TYPO3\CMS\Backend\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Middleware\FetchMetadataGuard;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FetchMetadataGuardTest extends UnitTestCase
{
    public static function requestsDataProvider(): \Generator
    {
        $authenticated = new Route('/test', []);
        $anonymous = new Route('/test', ['access' => 'anonymous']);
        yield 'cross-site POST is denied' => [$authenticated, 'POST', ['Sec-Fetch-Site' => 'cross-site'], 403];
        yield 'same-site POST is denied' => [$authenticated, 'POST', ['Sec-Fetch-Site' => 'same-site'], 403];
        yield 'same-origin POST is allowed' => [$authenticated, 'POST', ['Sec-Fetch-Site' => 'same-origin'], 200];
        yield 'user initiated POST is allowed' => [$authenticated, 'POST', ['Sec-Fetch-Site' => 'none'], 200];
        yield 'cross-site GET is allowed' => [$authenticated, 'GET', ['Sec-Fetch-Site' => 'cross-site'], 200];
        yield 'cross-site DELETE is denied' => [$authenticated, 'DELETE', ['Sec-Fetch-Site' => 'cross-site'], 403];
        yield 'cross-site POST on anonymous route is allowed' => [$anonymous, 'POST', ['Sec-Fetch-Site' => 'cross-site'], 200];
        yield 'fetch site is preferred over origin' => [$authenticated, 'POST', ['Sec-Fetch-Site' => 'same-origin', 'Origin' => 'https://evil.example'], 200];
        yield 'foreign origin without fetch site is denied' => [$authenticated, 'POST', ['Origin' => 'https://evil.example'], 403];
        yield 'matching origin without fetch site is allowed' => [$authenticated, 'POST', ['Origin' => 'https://example.com'], 200];
        yield 'neither fetch site nor origin is allowed' => [$authenticated, 'POST', [], 200];
    }

    #[Test]
    #[DataProvider('requestsDataProvider')]
    public function requestsAreEvaluatedByOrigin(Route $route, string $method, array $headers, int $expectedStatus): void
    {
        $request = new ServerRequest('https://example.com/typo3/test', $method)
            ->withAttribute('route', $route)
            ->withAttribute('normalizedParams', new NormalizedParams(['HTTP_HOST' => 'example.com', 'HTTPS' => 'on'], [], '', ''));
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        self::assertSame($expectedStatus, $this->process($request, true)->getStatusCode());
    }

    #[Test]
    public function guardCanBeDisabledByFeatureToggle(): void
    {
        $request = new ServerRequest('https://example.com/typo3/test', 'POST')
            ->withAttribute('route', new Route('/test', []))
            ->withHeader('Sec-Fetch-Site', 'cross-site');

        self::assertSame(200, $this->process($request, false)->getStatusCode());
    }

    private function process(ServerRequestInterface $request, bool $enabled): ResponseInterface
    {
        $features = self::createStub(Features::class);
        $features->method('isFeatureEnabled')->willReturn($enabled);
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
        return new FetchMetadataGuard($features)->process($request, $handler);
    }
}
