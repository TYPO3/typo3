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

namespace TYPO3\CMS\Core\Tests\Unit\Http\Security;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Security\InvalidReferrerException;
use TYPO3\CMS\Core\Http\Security\MissingReferrerException;
use TYPO3\CMS\Core\Http\Security\ReferrerEnforcer;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ReferrerEnforcerTest extends UnitTestCase
{
    use ProphecyTrait;

    private static function buildRefreshContentPattern(string $uri): string
    {
        return sprintf(
            '#.+href="%s\d+" id="referrer-refresh".+#',
            preg_quote(
                htmlspecialchars($uri . (strpos($uri, '?') !== false ? '&' : '?') . 'referrer-refresh='),
                '#'
            )
        );
    }

    public function validReferrerIsHandledDataProvider(): array
    {
        return [
            // Without query parameters
            [
                'https://example.org/typo3/login', // requestUri
                'https://example.org/typo3/index.php', // referrer
                null, // options
                null, // response
            ],
            [
                'https://example.org/typo3/login',
                '',
                ['flags' => ['refresh-empty']],
                self::buildRefreshContentPattern(
                    'https://example.org/typo3/login'
                ),
            ],
            [
                'https://example.org/typo3/login',
                'https://example.org/?eID=handler',
                ['flags' => ['refresh-same-site']],
                self::buildRefreshContentPattern(
                    'https://example.org/typo3/login'
                ),
            ],
            [
                'https://example.org/typo3/login',
                'https://other-example.site/security/',
                ['flags' => ['refresh-always']],
                self::buildRefreshContentPattern(
                    'https://example.org/typo3/login'
                ),
            ],
            // With query parameters
            [
                'https://example.org/typo3/login?query=parameter',
                'https://example.org/typo3/index.php',
                null,
                null,
            ],
            [
                'https://example.org/typo3/login?query=parameter',
                '',
                ['flags' => ['refresh-empty']],
                self::buildRefreshContentPattern(
                    'https://example.org/typo3/login?query=parameter'
                ),
            ],
            [
                'https://example.org/typo3/login?query=parameter',
                'https://example.org/?eID=handler',
                ['flags' => ['refresh-same-site']],
                self::buildRefreshContentPattern(
                    'https://example.org/typo3/login?query=parameter'
                ),
            ],
            [
                'https://example.org/typo3/login?query=parameter',
                'https://other-example.site/security/',
                ['flags' => ['refresh-always']],
                self::buildRefreshContentPattern(
                    'https://example.org/typo3/login?query=parameter'
                ),
            ],
        ];
    }

    /**
     * @param string $requestUri
     * @param string $referrer
     * @param string[]|null $options
     * @param string|null $expectedResponse
     *
     * @test
     * @dataProvider validReferrerIsHandledDataProvider
     */
    public function validReferrerIsHandled(string $requestUri, string $referrer, ?array $options, ?string $expectedResponse): void
    {
        $subject = $this->buildSubject($requestUri, $referrer);
        $response = $subject->handle($options);

        if ($expectedResponse === null) {
            self::assertNull($response);
        } else {

            // @todo remove condition and else branch as soon as phpunit v8 goes out of support
            if (method_exists($this, 'assertMatchesRegularExpression')) {
                self::assertMatchesRegularExpression($expectedResponse, (string)$response->getBody());
            } else {
                self::assertMatchesRegularExpression($expectedResponse, (string)$response->getBody());
            }
        }
    }

    public function invalidReferrerIsHandledDataProvider(): array
    {
        return [
            [
                'https://example.org/typo3/login', // requestUri
                'https://example.org/?eID=handler', // referrer
                null, // options
            ],
            [
                'https://example.org/typo3/login',
                'https://example.org/?eID=handler',
                ['flags' => ['refresh-empty']],
            ],
            [
                'https://example.org/typo3/login',
                'https://example.org.security/?eID=handler',
                ['flags' => ['refresh-same-site']],
            ],
            [
                'https://example.org/typo3/login',
                'https://other-example.site/security/',
                null,
            ],
        ];
    }

    /**
     * @param string $requestUri
     * @param string $referrer
     * @param string[]|null $options
     *
     * @test
     * @dataProvider invalidReferrerIsHandledDataProvider
     */
    public function invalidReferrerIsHandled(string $requestUri, string $referrer, ?array $options): void
    {
        $this->expectException(InvalidReferrerException::class);
        $this->expectExceptionCode(1588095936);
        $subject = $this->buildSubject($requestUri, $referrer);
        $subject->handle($options);
    }

    /**
     * @test
     */
    public function missingReferrerIsHandled(): void
    {
        $this->expectException(MissingReferrerException::class);
        $this->expectExceptionCode(1588095935);
        $subject = $this->buildSubject(
            'https://example.org/typo3/login',
            ''
        );
        $subject->handle();
    }

    private function buildSubject(string $requestUri, string $referrer): ReferrerEnforcer
    {
        $requestUriInstance = new Uri($requestUri);
        $host = sprintf(
            '%s://%s',
            $requestUriInstance->getScheme(),
            $requestUriInstance->getHost()
        );
        $dir = $host . rtrim(dirname($requestUriInstance->getPath()), '/') . '/';
        parse_str($requestUriInstance->getQuery(), $queryParams);

        /** @var NormalizedParams|ObjectProphecy $normalizedParams */
        $normalizedParams = $this->prophesize(NormalizedParams::class);
        $normalizedParams->getRequestHost()->willReturn($host);
        $normalizedParams->getRequestDir()->willReturn($dir);
        /** @var ServerRequestInterface|ObjectProphecy $request */
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('normalizedParams')->willReturn($normalizedParams);
        $request->getServerParams()->willReturn(['HTTP_REFERER' => $referrer]);
        $request->getUri()->willReturn($requestUriInstance);
        $request->getQueryParams()->willReturn($queryParams);

        return new ReferrerEnforcer($request->reveal());
    }
}
