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
            'install tool entry script' => [
                '/typo3/install.php', // scriptName
                'https://example.org/typo3/install.php?install[controller]=maintenance', // requestUri
                'https://example.org/typo3/install.php', // referrer
            ],
            'install tool entry script with additional parameters' => [
                '/typo3/install.php',
                'https://example.org/typo3/install.php?install[controller]=maintenance',
                'https://example.org/typo3/install.php?install[controller]=upgrade',
            ],
            'install tool entry script after referrer refresh' => [
                '/typo3/install.php',
                'https://example.org/typo3/install.php?referrer-refresh=1234',
                'https://example.org/typo3/install.php?referrer-refresh=1234',
            ],
            'install tool entry script in sub directory' => [
                '/subdir/typo3/install.php',
                'https://example.org/subdir/typo3/install.php',
                'https://example.org/subdir/typo3/install.php?install[controller]=upgrade',
            ],
        ];
    }

    #[DataProvider('sameOriginReferrerIsAcceptedDataProvider')]
    #[Test]
    public function sameOriginReferrerIsAccepted(string $scriptName, string $requestUri, string $referrer): void
    {
        $subject = $this->buildSubject($scriptName, $requestUri, $referrer);
        self::assertNull($subject->handle(['flags' => ['refresh-always']]));
    }

    public static function nonSameOriginReferrerIsRejectedDataProvider(): array
    {
        return [
            // The actual regression: the backend is served from the same directory as the
            // install tool entry script, thus a same-site referrer must not be mistaken for
            // same-origin.
            'same-site backend module' => [
                '/typo3/install.php', // scriptName
                'https://example.org/typo3/install.php', // requestUri
                'https://example.org/typo3/module/web/layout', // referrer
            ],
            'same-site backend entry point' => [
                '/typo3/install.php',
                'https://example.org/typo3/install.php',
                'https://example.org/typo3/',
            ],
            'same-site frontend page' => [
                '/typo3/install.php',
                'https://example.org/typo3/install.php',
                'https://example.org/some-frontend-page',
            ],
            'entry script of another instance in a sub directory' => [
                '/subdir/typo3/install.php',
                'https://example.org/subdir/typo3/install.php',
                'https://example.org/typo3/install.php',
            ],
            'cross-site referrer' => [
                '/typo3/install.php',
                'https://example.org/typo3/install.php',
                'https://other-example.site/security/',
            ],
            'host prefix of the request host' => [
                '/typo3/install.php',
                'https://example.org/typo3/install.php',
                'https://example.org.security/typo3/install.php',
            ],
            'unparsable referrer' => [
                '/typo3/install.php',
                'https://example.org/typo3/install.php',
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
        $subject = $this->buildSubject($scriptName, $requestUri, $referrer);
        $subject->handle();
    }

    #[DataProvider('nonSameOriginReferrerIsRejectedDataProvider')]
    #[Test]
    public function nonSameOriginReferrerIsRefreshed(string $scriptName, string $requestUri, string $referrer): void
    {
        $subject = $this->buildSubject($scriptName, $requestUri, $referrer);
        $response = $subject->handle(['flags' => ['refresh-always']]);
        self::assertStringContainsString('id="referrer-refresh"', (string)$response->getBody());
    }

    #[Test]
    public function missingReferrerIsHandled(): void
    {
        $this->expectException(MissingReferrerException::class);
        $this->expectExceptionCode(1588095935);
        $subject = $this->buildSubject('/typo3/install.php', 'https://example.org/typo3/install.php', '');
        $subject->handle();
    }

    private function buildSubject(string $scriptName, string $requestUri, string $referrer): ReferrerEnforcer
    {
        $request = $this->buildPreparedRequest($scriptName, $requestUri, $referrer);
        $mock = $this->getMockBuilder(ReferrerEnforcer::class)
            ->onlyMethods(['resolveAbsoluteWebPath'])
            ->setConstructorArgs([$request])
            ->getMock();
        $mock->method('resolveAbsoluteWebPath')->willReturnCallback(static fn(string $target): string => '/' . $target);
        return $mock;
    }

    private function buildPreparedRequest(string $scriptName, string $requestUri, string $referrer): ServerRequestInterface
    {
        $requestUriInstance = new Uri($requestUri);
        $serverParams = [
            'HTTP_HOST' => $requestUriInstance->getHost(),
            'HTTP_REFERER' => $referrer,
            'SCRIPT_NAME' => $scriptName,
            'REQUEST_URI' => $requestUriInstance->getPath() . ($requestUriInstance->getQuery() !== '' ? '?' . $requestUriInstance->getQuery() : ''),
            'HTTPS' => $requestUriInstance->getScheme() === 'https' ? 'on' : 'off',
        ];
        parse_str($requestUriInstance->getQuery(), $queryParams);
        $request = new ServerRequest($requestUriInstance, null, null, [], $serverParams);
        return $request
            ->withQueryParams($queryParams)
            ->withAttribute('normalizedParams', new NormalizedParams($serverParams, [], '', ''));
    }
}
