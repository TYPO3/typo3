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

namespace TYPO3\CMS\Install\Tests\Unit\Http\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Security\InvalidReferrerException;
use TYPO3\CMS\Core\Http\Security\MissingReferrerException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Install\Http\Security\ReferrerEnforcer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ReferrerEnforcerTest extends UnitTestCase
{
    public static function sameOriginReferrerIsAcceptedDataProvider(): array
    {
        return [
            'install tool entry point' => [
                '/index.php', // scriptName
                'https://example.org/?__typo3_install&install[controller]=maintenance', // requestUri
                'https://example.org/?__typo3_install', // referrer
            ],
            'install tool entry point with additional parameters' => [
                '/index.php',
                'https://example.org/?__typo3_install&install[controller]=maintenance',
                'https://example.org/?__typo3_install&install[controller]=maintenance&install[context]=backend',
            ],
            'install tool entry point after referrer refresh' => [
                '/index.php',
                'https://example.org/?__typo3_install&referrer-refresh=1234',
                'https://example.org/?__typo3_install&referrer-refresh=1234',
            ],
            'install tool entry point in sub directory' => [
                '/subdir/index.php',
                'https://example.org/subdir/?__typo3_install',
                'https://example.org/subdir/?__typo3_install&install[controller]=upgrade',
            ],
        ];
    }

    #[DataProvider('sameOriginReferrerIsAcceptedDataProvider')]
    #[Test]
    public function sameOriginReferrerIsAccepted(string $scriptName, string $requestUri, string $referrer): void
    {
        $subject = $this->buildSubject();
        $request = $this->buildPreparedRequest($scriptName, $requestUri, $referrer);
        self::assertNull($subject->handle($request, ['flags' => ['refresh-always']]));
    }

    public static function nonSameOriginReferrerIsRejectedDataProvider(): array
    {
        return [
            // The actual regression: all applications are served from the very same entry
            // script, thus a same-site referrer must not be mistaken for same-origin.
            'same-site frontend page' => [
                '/index.php',
                'https://example.org/?__typo3_install',
                'https://example.org/some-frontend-page',
            ],
            'same-site frontend page with query parameters' => [
                '/index.php',
                'https://example.org/?__typo3_install',
                'https://example.org/some-frontend-page?__typo3_install',
            ],
            'same-site backend module' => [
                '/index.php',
                'https://example.org/?__typo3_install',
                'https://example.org/typo3/module/web/layout',
            ],
            'site root without install tool parameter' => [
                '/index.php',
                'https://example.org/?__typo3_install',
                'https://example.org/?id=42',
            ],
            'site root of an instance in a sub directory' => [
                '/subdir/index.php',
                'https://example.org/subdir/?__typo3_install',
                'https://example.org/?__typo3_install',
            ],
            'cross-site referrer' => [
                '/index.php',
                'https://example.org/?__typo3_install',
                'https://other-example.site/security/',
            ],
            'host prefix of the request host' => [
                '/index.php',
                'https://example.org/?__typo3_install',
                'https://example.org.security/?__typo3_install',
            ],
            'unparsable referrer' => [
                '/index.php',
                'https://example.org/?__typo3_install',
                'http://',
            ],
        ];
    }

    #[DataProvider('nonSameOriginReferrerIsRejectedDataProvider')]
    #[Test]
    public function nonSameOriginReferrerIsRejected(string $scriptName, string $requestUri, string $referrer): void
    {
        $this->expectException(InvalidReferrerException::class);
        $this->expectExceptionCode(1588095936);
        $subject = $this->buildSubject();
        $request = $this->buildPreparedRequest($scriptName, $requestUri, $referrer);
        $subject->handle($request, []);
    }

    #[DataProvider('nonSameOriginReferrerIsRejectedDataProvider')]
    #[Test]
    public function nonSameOriginReferrerIsRefreshed(string $scriptName, string $requestUri, string $referrer): void
    {
        $subject = $this->buildSubject();
        $request = $this->buildPreparedRequest($scriptName, $requestUri, $referrer);
        $response = $subject->handle($request, ['flags' => ['refresh-always']]);
        self::assertStringContainsString('id="referrer-refresh"', (string)$response->getBody());
    }

    #[Test]
    public function missingReferrerIsHandled(): void
    {
        $this->expectException(MissingReferrerException::class);
        $this->expectExceptionCode(1588095935);
        $subject = $this->buildSubject();
        $request = $this->buildPreparedRequest('/index.php', 'https://example.org/?__typo3_install', '');
        $subject->handle($request, []);
    }

    private function buildSubject(): ReferrerEnforcer
    {
        $mock = $this->getMockBuilder(ReferrerEnforcer::class)
            ->onlyMethods(['resolveAbsoluteWebPath'])
            ->getMock();
        $mock->method('resolveAbsoluteWebPath')->willReturnCallback(static fn(string $target): string => '/' . $target);
        return $mock;
    }

    private function buildPreparedRequest(string $scriptName, string $requestUri, string $referrer): ServerRequestInterface
    {
        $requestUriInstance = new Uri($requestUri);
        $headers = [
            'HTTP_HOST' => $requestUriInstance->getHost(),
            'HTTP_REFERER' => $referrer,
            'SCRIPT_NAME' => $scriptName,
            'REQUEST_URI' => $requestUriInstance->getPath() . ($requestUriInstance->getQuery() ? '?' . $requestUriInstance->getQuery() : ''),
            'HTTPS' => $requestUriInstance->getScheme() === 'https' ? 'on' : 'off',
        ];
        $request = new ServerRequest(
            $requestUriInstance,
            null,
            null,
            [],
            $headers,
        );
        return $request->withAttribute('normalizedParams', new NormalizedParams($headers, [], '', ''));
    }
}
