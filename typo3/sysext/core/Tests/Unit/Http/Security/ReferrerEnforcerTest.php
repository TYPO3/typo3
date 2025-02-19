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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Security\InvalidReferrerException;
use TYPO3\CMS\Core\Http\Security\MissingReferrerException;
use TYPO3\CMS\Core\Http\Security\ReferrerEnforcer;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ReferrerEnforcerTest extends UnitTestCase
{
    private static function buildRefreshContentPattern(string $uri): string
    {
        return sprintf(
            '#.+href="%s\d+" id="referrer-refresh".+#',
            preg_quote(
                htmlspecialchars($uri . (str_contains($uri, '?') ? '&' : '?') . 'referrer-refresh='),
                '#'
            )
        );
    }

    public static function validReferrerIsHandledDataProvider(): array
    {
        return [
            // Without query parameters
            [
                'https://example.org/typo3/login', // requestUri
                'https://example.org/typo3/index.php', // referrer
                [], // options
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
                [],
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
     * @param string[] $options
     */
    #[DataProvider('validReferrerIsHandledDataProvider')]
    #[Test]
    public function validReferrerIsHandled(string $requestUri, string $referrer, array $options, ?string $expectedResponse): void
    {
        $subject = $this->buildSubject();
        $request = $this->buildPreparedRequest($requestUri, $referrer);
        $response = $subject->handle($request, $options);

        if ($expectedResponse === null) {
            self::assertNull($response);
        } else {
            self::assertMatchesRegularExpression($expectedResponse, (string)$response->getBody());
        }
    }

    public static function invalidReferrerIsHandledDataProvider(): array
    {
        return [
            [
                'https://example.org/typo3/login', // requestUri
                'https://example.org/?eID=handler', // referrer
                [], // options
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
                [],
            ],
        ];
    }

    /**
     * @param string[] $options
     */
    #[DataProvider('invalidReferrerIsHandledDataProvider')]
    #[Test]
    public function invalidReferrerIsHandled(string $requestUri, string $referrer, array $options): void
    {
        $this->expectException(InvalidReferrerException::class);
        $this->expectExceptionCode(1588095936);
        $subject = $this->buildSubject();
        $request = $this->buildPreparedRequest($requestUri, $referrer);
        $subject->handle($request, $options);
    }

    #[Test]
    public function missingReferrerIsHandled(): void
    {
        $this->expectException(MissingReferrerException::class);
        $this->expectExceptionCode(1588095935);
        $subject = $this->buildSubject();
        $request = $this->buildPreparedRequest(
            'https://example.org/typo3/login',
            ''
        );
        $subject->handle($request, []);
    }

    #[Test]
    public function nonceIsAppliedToResponse(): void
    {
        $nonce = new ConsumableNonce();
        $subject = $this->buildSubject();
        $request = $this->buildPreparedRequest(
            'https://example.org/typo3/login',
            '',
            $nonce
        );
        $response = $subject->handle($request, ['flags' => ['refresh-always']]);
        self::assertStringContainsString(
            'nonce="' . htmlspecialchars($nonce->value) . '">',
            (string)$response->getBody()
        );
    }

    private function buildSubject(): ReferrerEnforcer
    {
        $mock = $this->getMockBuilder(ReferrerEnforcer::class)
            ->onlyMethods(['resolveAbsoluteWebPath'])
            ->getMock();
        $mock->method('resolveAbsoluteWebPath')->willReturnCallback(static fn(string $target): string => '/' . $target);
        return $mock;
    }

    private function buildPreparedRequest(string $requestUri, string $referrer, ?ConsumableNonce $nonce = null): ServerRequestInterface
    {
        $requestUriInstance = new Uri($requestUri);
        $host = sprintf(
            '%s://%s',
            $requestUriInstance->getScheme(),
            $requestUriInstance->getHost()
        );
        $dir = $host . rtrim(dirname($requestUriInstance->getPath()), '/') . '/';
        parse_str($requestUriInstance->getQuery(), $queryParams);

        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getRequestHost')->willReturn($host);
        $normalizedParams->method('getRequestDir')->willReturn($dir);
        $request = new ServerRequest(
            $requestUriInstance,
            null,
            null,
            [],
            ['HTTP_REFERER' => $referrer]
        );
        return $request
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('nonce', $nonce);
    }
}
