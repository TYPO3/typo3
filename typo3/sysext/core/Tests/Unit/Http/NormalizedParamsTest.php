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

namespace TYPO3\CMS\Core\Tests\Unit\Http;

use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class NormalizedParamsTest extends UnitTestCase
{
    /**
     * @return array[]
     */
    public function getHttpHostReturnsSanitizedValueDataProvider(): array
    {
        return [
            'simple HTTP_HOST' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                ],
                [],
                'www.domain.com',
            ],
            'first HTTP_X_FORWARDED_HOST from configured proxy, HTTP_HOST empty' => [
                [
                    'HTTP_HOST' => '',
                    'REMOTE_ADDR' => '123.123.123.123',
                    'HTTP_X_FORWARDED_HOST' => 'www.domain1.com, www.domain2.com,',
                ],
                [
                    'reverseProxyIP' => ' 123.123.123.123',
                    'reverseProxyHeaderMultiValue' => 'first',
                ],
                'www.domain1.com',
            ],
            'first HTTP_X_FORWARDED_HOST from configured proxy, HTTP_HOST given' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '123.123.123.123',
                    'HTTP_X_FORWARDED_HOST' => 'www.domain1.com, www.domain2.com,',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123',
                    'reverseProxyHeaderMultiValue' => 'first',
                ],
                'www.domain1.com',
            ],
            'last HTTP_X_FORWARDED_HOST from configured proxy' => [
                [
                    'HTTP_HOST' => '',
                    'REMOTE_ADDR' => '123.123.123.123',
                    'HTTP_X_FORWARDED_HOST' => 'www.domain1.com, www.domain2.com,',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123',
                    'reverseProxyHeaderMultiValue' => 'last',
                ],
                'www.domain2.com',
            ],
            'simple HTTP_HOST if reverseProxyHeaderMultiValue is not configured' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '123.123.123.123',
                    'HTTP_X_FORWARDED_HOST' => 'www.domain1.com',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123',
                ],
                'www.domain.com',
            ],
            'simple HTTP_HOST if proxy IP does not match' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '123.123.123.123',
                    'HTTP_X_FORWARDED_HOST' => 'www.domain1.com',
                ],
                [
                    'reverseProxyIP' => '234.234.234.234',
                    'reverseProxyHeaderMultiValue' => 'last',
                ],
                'www.domain.com',
            ],
            'simple HTTP_HOST if REMOTE_ADDR misses' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTP_X_FORWARDED_HOST' => 'www.domain1.com',
                ],
                [
                    'reverseProxyIP' => '234.234.234.234',
                    'reverseProxyHeaderMultiValue' => 'last',
                ],
                'www.domain.com',
            ],
            'simple HTTP_HOST if HTTP_X_FORWARDED_HOST is empty' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '123.123.123.123',
                    'HTTP_X_FORWARDED_HOST' => '',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123',
                    'reverseProxyHeaderMultiValue' => 'last',
                ],
                'www.domain.com',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getHttpHostReturnsSanitizedValueDataProvider
     */
    public function getHttpHostReturnsSanitizedValue(array $serverParams, array $configuration, string $expected): void
    {
        $serverRequestParameters = new NormalizedParams($serverParams, $configuration, '', '');
        self::assertSame($expected, $serverRequestParameters->getHttpHost());
    }

    /**
     * @return array[]
     */
    public function isHttpsReturnSanitizedValueDataProvider(): array
    {
        return [
            'false if nothing special is set' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                ],
                [],
                false,
            ],
            'true if SSL_SESSION_ID is set' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'SSL_SESSION_ID' => 'foo',
                ],
                [],
                true,
            ],
            'false if SSL_SESSION_ID is empty' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'SSL_SESSION_ID' => '',
                ],
                [],
                false,
            ],
            'true if HTTPS is "ON"' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => 'ON',
                ],
                [],
                true,
            ],
            'true if HTTPS is "on"' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => 'on',
                ],
                [],
                true,
            ],
            'true if HTTPS is "1"' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => '1',
                ],
                [],
                true,
            ],
            'true if HTTPS is int(1)"' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => 1,
                ],
                [],
                true,
            ],
            'true if HTTPS is bool(true)' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => true,
                ],
                [],
                true,
            ],
            // https://secure.php.net/manual/en/reserved.variables.server.php
            // "Set to a non-empty value if the script was queried through the HTTPS protocol."
            'true if HTTPS is "somethingrandom"' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => 'somethingrandom',
                ],
                [],
                true,
            ],
            'false if HTTPS is "0"' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => '0',
                ],
                [],
                false,
            ],
            'false if HTTPS is int(0)' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => 0,
                ],
                [],
                false,
            ],
            'false if HTTPS is float(0)' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => 0.0,
                ],
                [],
                false,
            ],
            'false if HTTPS is not on' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => 'off',
                ],
                [],
                false,
            ],
            'false if HTTPS is empty' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => '',
                ],
                [],
                false,
            ],
            'false if HTTPS is null' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => null,
                ],
                [],
                false,
            ],
            'false if HTTPS is bool(false)' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'HTTPS' => false,
                ],
                [],
                false,
            ],
            // Per PHP documentation 'HTTPS' is:
            //   "Set to a non-empty value if the script
            //   was queried through the HTTPS protocol."
            // So theoretically an empty array means HTTPS is off.
            // We do not support that. Therefore this test is disabled.
            //'false if HTTPS is an empty Array' => [
            //    [
            //        'HTTP_HOST' => 'www.domain.com',
            //        'HTTPS' => [],
            //    ],
            //    [],
            //    false,
            //],
            'true if ssl proxy IP matches REMOTE_ADDR' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '123.123.123.123 ',
                ],
                [
                    'reverseProxySSL' => ' 123.123.123.123',
                ],
                true,
            ],
            'false if ssl proxy IP does not match REMOTE_ADDR' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '123.123.123.123',
                ],
                [
                    'reverseProxySSL' => '234.234.234.234',
                ],
                false,
            ],
            'true if SSL proxy is * and reverse proxy IP matches REMOTE_ADDR' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '123.123.123.123',
                ],
                [
                    'reverseProxySSL' => '*',
                    'reverseProxyIP' => '123.123.123.123',
                ],
                true,
            ],
            'false if SSL proxy is * and reverse proxy IP does not match REMOTE_ADDR' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '123.123.123.123',
                ],
                [
                    'reverseProxySSL' => '*',
                    'reverseProxyIP' => '234.234.234.234',
                ],
                false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isHttpsReturnSanitizedValueDataProvider
     */
    public function isHttpsReturnSanitizedValue(array $serverParams, array $configuration, bool $expected): void
    {
        $serverRequestParameters = new NormalizedParams($serverParams, $configuration, '', '');
        self::assertSame($expected, $serverRequestParameters->isHttps());
    }

    /**
     * @test
     */
    public function getRequestHostReturnsRequestHost(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'www.domain.com',
            'HTTPS' => 'on',
        ];
        $expected = 'https://www.domain.com';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getRequestHost());
    }

    /**
     * @return array[]
     */
    public function getScriptNameReturnsExpectedValueDataProvider(): array
    {
        return [
            'empty string if nothing is set' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                ],
                [],
                '',
            ],
            'use ORIG_SCRIPT_NAME if ORIG_PATH_INFO is set but empty' => [
                [
                    'ORIG_PATH_INFO' => '',
                    'PATH_INFO' => '',
                    'ORIG_SCRIPT_NAME' => '/orig/script/name.php',
                    'SCRIPT_NAME' => '/script/name.php',
                ],
                [],
                '/orig/script/name.php',
            ],
            'use ORIG_SCRIPT_NAME if PATH_INFO is set but empty' => [
                [
                    'PATH_INFO' => '',
                    'ORIG_SCRIPT_NAME' => '/orig/script/name.php',
                    'SCRIPT_NAME' => '/script/name.php',
                ],
                [],
                '/orig/script/name.php',
            ],
            'use SCRIPT_NAME if ORIG_PATH_INFO is set but empty' => [
                [
                    'ORIG_PATH_INFO' => '',
                    'PATH_INFO' => '',
                    'ORIG_SCRIPT_NAME' => '',
                    'SCRIPT_NAME' => '/script/name.php',
                ],
                [],
                '/script/name.php',
            ],
            'use SCRIPT_NAME if PATH_INFO is set but empty' => [
                [
                    'PATH_INFO' => '',
                    'ORIG_SCRIPT_NAME' => '',
                    'SCRIPT_NAME' => '/script/name.php',
                ],
                [],
                '/script/name.php',
            ],
            'use SCRIPT_NAME if ORIG_PATH_INFO is set' => [
                [
                    'ORIG_PATH_INFO' => '/foo/bar',
                    'PATH_INFO' => '',
                    'ORIG_SCRIPT_NAME' => '',
                    'SCRIPT_NAME' => '/script/name.php',
                ],
                [],
                '/script/name.php',
            ],
            'use SCRIPT_NAME if PATH_INFO is set' => [
                [
                    'PATH_INFO' => '/foo/bar',
                    'ORIG_SCRIPT_NAME' => '',
                    'SCRIPT_NAME' => '/script/name.php',
                ],
                [],
                '/script/name.php',
            ],
            'use ORIG_SCRIPT_NAME' => [
                [
                    'ORIG_SCRIPT_NAME' => '/orig/script/name.php',
                    'SCRIPT_NAME' => '/script/name.php',
                ],
                [],
                '/orig/script/name.php',
            ],
            'use SCRIPT_NAME' => [
                [
                    'SCRIPT_NAME' => '/script/name.php',
                ],
                [],
                '/script/name.php',
            ],
            'add proxy ssl prefix' => [
                [
                    'REMOTE_ADDR' => '123.123.123.123',
                    'HTTPS' => 'on',
                    'SCRIPT_NAME' => '/path/info.php',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123',
                    'reverseProxyPrefixSSL' => '/proxyPrefixSSL',
                ],
                '/proxyPrefixSSL/path/info.php',
            ],
            'add proxy prefix' => [
                [
                    'REMOTE_ADDR' => '123.123.123.123',
                    'SCRIPT_NAME' => '/path/info.php',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123',
                    'reverseProxyPrefix' => '/proxyPrefix',
                ],
                '/proxyPrefix/path/info.php',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getScriptNameReturnsExpectedValueDataProvider
     */
    public function getScriptNameReturnsExpectedValue(array $serverParams, array $configuration, string $expected): void
    {
        $serverRequestParameters = new NormalizedParams($serverParams, $configuration, '', '');
        self::assertSame($expected, $serverRequestParameters->getScriptName());
    }

    /**
     * @return array[]
     */
    public function getRequestUriReturnsExpectedValueDataProvider(): array
    {
        return [
            'slash if nothing is set' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                ],
                [],
                '/',
            ],
            'use REQUEST_URI' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REQUEST_URI' => 'typo3/bar?id=42',
                ],
                [],
                '/typo3/bar?id=42',
            ],
            'use query string and script name if REQUEST_URI is not set' => [
                [
                    'QUERY_STRING' => 'parameter=foo/bar&id=42',
                    'SCRIPT_NAME' => '/typo3/index.php',
                ],
                [],
                '/typo3/index.php?parameter=foo/bar&id=42',
            ],
            'prefix with proxy prefix with ssl if using REQUEST_URI' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '123.123.123.123',
                    'HTTPS' => 'on',
                    'REQUEST_URI' => 'typo3/foo/bar?id=42',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123',
                    'reverseProxyPrefixSSL' => '/proxyPrefixSSL',
                ],
                '/proxyPrefixSSL/typo3/foo/bar?id=42',
            ],
            'prefix with proxy prefix if using REQUEST_URI' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '123.123.123.123',
                    'REQUEST_URI' => 'typo3/foo/bar?id=42',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123',
                    'reverseProxyPrefix' => '/proxyPrefix',
                ],
                '/proxyPrefix/typo3/foo/bar?id=42',
            ],
            'prefix with proxy prefix with ssl if using query string and script name' => [
                [
                    'REMOTE_ADDR' => '123.123.123.123',
                    'HTTPS' => 'on',
                    'QUERY_STRING' => 'parameter=foo/bar&id=42',
                    'SCRIPT_NAME' => '/typo3/index.php',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123',
                    'reverseProxyPrefixSSL' => '/proxyPrefixSSL',
                ],
                '/proxyPrefixSSL/typo3/index.php?parameter=foo/bar&id=42',
            ],
            'prefix with proxy prefix if using query string and script name' => [
                [
                    'REMOTE_ADDR' => '123.123.123.123',
                    'HTTPS' => 'on',
                    'QUERY_STRING' => 'parameter=foo/bar&id=42',
                    'SCRIPT_NAME' => '/typo3/index.php',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123',
                    'reverseProxyPrefix' => '/proxyPrefix',
                ],
                '/proxyPrefix/typo3/index.php?parameter=foo/bar&id=42',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getRequestUriReturnsExpectedValueDataProvider
     */
    public function getRequestUriReturnsExpectedValue(array $serverParams, array $configuration, string $expected): void
    {
        $serverRequestParameters = new NormalizedParams($serverParams, $configuration, '', '');
        self::assertSame($expected, $serverRequestParameters->getRequestUri());
    }

    /**
     * @test
     */
    public function getRequestUriFetchesFromConfiguredRequestUriVar(): void
    {
        $GLOBALS['foo']['bar'] = '/foo/bar.php';
        $serverParams = [
            'HTTP_HOST' => 'www.domain.com',
        ];
        $configuration = [
            'requestURIvar' => 'foo|bar',
        ];
        $expected = '/foo/bar.php';
        $serverRequestParameters = new NormalizedParams($serverParams, $configuration, '', '');
        self::assertSame($expected, $serverRequestParameters->getRequestUri());
    }

    /**
     * @test
     */
    public function getRequestUrlReturnsExpectedValue(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'www.domain.com',
            'REQUEST_URI' => 'typo3/foo/bar?id=42',
        ];
        $expected = 'http://www.domain.com/typo3/foo/bar?id=42';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getRequestUrl());
    }

    /**
     * @test
     */
    public function getRequestScriptReturnsExpectedValue(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'www.domain.com',
            'SCRIPT_NAME' => '/typo3/index.php',
        ];
        $expected = 'http://www.domain.com/typo3/index.php';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getRequestScript());
    }

    /**
     * @test
     */
    public function getRequestDirReturnsExpectedValue(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'www.domain.com',
            'SCRIPT_NAME' => '/typo3/index.php',
        ];
        $expected = 'http://www.domain.com/typo3/';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getRequestDir());
    }

    /**
     * @return array[]
     */
    public function isBehindReverseProxyReturnsExpectedValueDataProvider(): array
    {
        return [
            'false with empty data' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                ],
                [],
                false,
            ],
            'false if REMOTE_ADDR and reverseProxyIP do not match' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '100.100.100.100',
                ],
                [
                    'reverseProxyIP' => '200.200.200.200',
                ],
                false,
            ],
            'true if REMOTE_ADDR matches configured reverseProxyIP' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => '100.100.100.100',
                ],
                [
                    'reverseProxyIP' => '100.100.100.100',
                ],
                true,
            ],
            'true if trimmed REMOTE_ADDR matches configured trimmed reverseProxyIP' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => ' 100.100.100.100 ',
                ],
                [
                    'reverseProxyIP' => '  100.100.100.100  ',
                ],
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isBehindReverseProxyReturnsExpectedValueDataProvider
     */
    public function isBehindReverseProxyReturnsExpectedValue(array $serverParams, array $configuration, bool $expected): void
    {
        $serverRequestParameters = new NormalizedParams($serverParams, $configuration, '', '');
        self::assertSame($expected, $serverRequestParameters->isBehindReverseProxy());
    }

    /**
     * @return array[]
     */
    public function getRemoteAddressReturnsExpectedValueDataProvider(): array
    {
        return [
            'simple REMOTE_ADDR' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => ' 123.123.123.123 ',
                ],
                [],
                '123.123.123.123',
            ],
            'reverse proxy with last HTTP_X_FORWARDED_FOR' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => ' 123.123.123.123 ',
                    'HTTP_X_FORWARDED_FOR' => ' 234.234.234.234, 235.235.235.235,',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123',
                    'reverseProxyHeaderMultiValue' => ' last ',
                ],
                '235.235.235.235',
            ],
            'reverse proxy with first HTTP_X_FORWARDED_FOR' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => ' 123.123.123.123 ',
                    'HTTP_X_FORWARDED_FOR' => ' 234.234.234.234, 235.235.235.235,',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123 ',
                    'reverseProxyHeaderMultiValue' => ' first ',
                ],
                '234.234.234.234',
            ],
            'reverse proxy with broken reverseProxyHeaderMultiValue returns REMOTE_ADDR' => [
                [
                    'HTTP_HOST' => 'www.domain.com',
                    'REMOTE_ADDR' => ' 123.123.123.123 ',
                    'HTTP_X_FORWARDED_FOR' => ' 234.234.234.234, 235.235.235.235,',
                ],
                [
                    'reverseProxyIP' => '123.123.123.123 ',
                    'reverseProxyHeaderMultiValue' => ' foo ',
                ],
                '123.123.123.123',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getRemoteAddressReturnsExpectedValueDataProvider
     */
    public function getRemoteAddressReturnsExpectedValue(array $serverParams, array $configuration, string $expected): void
    {
        $serverRequestParameters = new NormalizedParams($serverParams, $configuration, '', '');
        self::assertSame($expected, $serverRequestParameters->getRemoteAddress());
    }

    public static function getRequestHostOnlyReturnsExpectedValueDataProvider(): array
    {
        return [
            'localhost ipv4 without port' => [
                [
                    'HTTP_HOST' => '127.0.0.1',
                ],
                '127.0.0.1',
            ],
            'localhost ipv4 with port' => [
                [
                    'HTTP_HOST' => '127.0.0.1:81',
                ],
                '127.0.0.1',
            ],
            'localhost ipv6 without port' => [
                [
                    'HTTP_HOST' => '[::1]',
                ],
                '[::1]',
            ],
            'localhost ipv6 with port' => [
                [
                    'HTTP_HOST' => '[::1]:81',
                ],
                '[::1]',
            ],
            'ipv6 without port' => [
                [
                    'HTTP_HOST' => '[2001:DB8::1]',
                ],
                '[2001:DB8::1]',
            ],
            'ipv6 with port' => [
                [
                    'HTTP_HOST' => '[2001:DB8::1]:81',
                ],
                '[2001:DB8::1]',
            ],
            'hostname without port' => [
                [
                    'HTTP_HOST' => 'lolli.did.this',
                ],
                'lolli.did.this',
            ],
            'hostname with port' => [
                [
                    'HTTP_HOST' => 'lolli.did.this:42',
                ],
                'lolli.did.this',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getRequestHostOnlyReturnsExpectedValueDataProvider
     */
    public function getRequestHostOnlyReturnsExpectedValue(array $serverParams, string $expected): void
    {
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getRequestHostOnly());
    }

    public static function getRequestPortOnlyReturnsExpectedValueDataProvider(): array
    {
        return [
            'localhost ipv4 without port' => [
                [
                    'HTTP_HOST' => '127.0.0.1',
                ],
                0,
            ],
            'localhost ipv4 with port' => [
                [
                    'HTTP_HOST' => '127.0.0.1:81',
                ],
                81,
            ],
            'localhost ipv6 without port' => [
                [
                    'HTTP_HOST' => '[::1]',
                ],
                0,
            ],
            'localhost ipv6 with port' => [
                [
                    'HTTP_HOST' => '[::1]:81',
                ],
                81,
            ],
            'ipv6 without port' => [
                [
                    'HTTP_HOST' => '[2001:DB8::1]',
                ],
                0,
            ],
            'ipv6 with port' => [
                [
                    'HTTP_HOST' => '[2001:DB8::1]:81',
                ],
                81,
            ],
            'hostname without port' => [
                [
                    'HTTP_HOST' => 'lolli.did.this',
                ],
                0,
            ],
            'hostname with port' => [
                [
                    'HTTP_HOST' => 'lolli.did.this:42',
                ],
                42,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getRequestPortOnlyReturnsExpectedValueDataProvider
     */
    public function getRequestPortReturnsExpectedValue(array $serverParams, int $expected): void
    {
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getRequestPort());
    }

    /**
     * @test
     */
    public function getScriptFilenameReturnsThirdConstructorArgument(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'www.domain.com',
            'SCRIPT_NAME' => '/typo3/index.php',
        ];
        $pathSite = '/var/www/';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '/var/www/typo3/index.php', $pathSite);
        self::assertSame('/var/www/typo3/index.php', $serverRequestParameters->getScriptFilename());
    }

    /**
     * @test
     */
    public function getDocumentRootReturnsExpectedPath(): void
    {
        $serverParams = [
            'HTTP_HOST' => 'www.domain.com',
            'SCRIPT_NAME' => '/typo3/index.php',
        ];
        $pathThisScript = '/var/www/myInstance/Web/typo3/index.php';
        $pathSite = '/var/www/myInstance/Web/';
        $expected = '/var/www/myInstance/Web';
        $serverRequestParameters = new NormalizedParams($serverParams, [], $pathThisScript, $pathSite);
        self::assertSame($expected, $serverRequestParameters->getDocumentRoot());
    }

    /**
     * @test
     */
    public function getSiteUrlReturnsExpectedUrl(): void
    {
        $serverParams = [
            'SCRIPT_NAME' => '/typo3/index.php',
            'HTTP_HOST' => 'www.domain.com',
            'PATH_INFO' => '/typo3/index.php',
        ];
        $pathThisScript = '/var/www/myInstance/Web/typo3/index.php';
        $pathSite = '/var/www/myInstance/Web';
        $expected = 'http://www.domain.com/';
        $serverRequestParameters = new NormalizedParams($serverParams, [], $pathThisScript, $pathSite);
        self::assertSame($expected, $serverRequestParameters->getSiteUrl());
    }

    /**
     * @test
     */
    public function getSiteUrlReturnsExpectedUrlForCliCommand(): void
    {
        $serverParams = [];
        $pathThisScript = '/var/www/html/typo3temp/var/tests/acceptance/typo3/sysext/core/bin/typo3';
        $pathSite = '/var/www/html/typo3temp/var/tests/acceptance/';
        $expected = '/';
        $serverRequestParameters = new NormalizedParams($serverParams, [], $pathThisScript, $pathSite);
        self::assertSame($expected, $serverRequestParameters->getSiteUrl());
    }

    /**
     * @return array[]
     */
    public function getSitePathReturnsExpectedPathDataProvider(): array
    {
        return [
            'empty config' => [
                [],
                '',
                '',
                '',
            ],
            'not in a sub directory' => [
                [
                    'SCRIPT_NAME' => '/typo3/index.php',
                    'HTTP_HOST' => 'www.domain.com',
                ],
                '/var/www/myInstance/Web/typo3/index.php',
                '/var/www/myInstance/Web',
                '/',
            ],
            'in a sub directory' => [
                [
                    'SCRIPT_NAME' => '/some/sub/dir/typo3/index.php',
                    'HTTP_HOST' => 'www.domain.com',
                ],
                '/var/www/myInstance/Web/typo3/index.php',
                '/var/www/myInstance/Web',
                '/some/sub/dir/',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getSitePathReturnsExpectedPathDataProvider
     */
    public function getSitePathReturnsExpectedPath(array $serverParams, string $pathThisScript, string $pathSite, string $expected): void
    {
        $serverRequestParameters = new NormalizedParams($serverParams, [], $pathThisScript, $pathSite);
        self::assertSame($expected, $serverRequestParameters->getSitePath());
    }

    /**
     * @return array[]
     */
    public function getSiteScriptReturnsExpectedPathDataProvider(): array
    {
        return [
            'not in a sub directory' => [
                [
                    'SCRIPT_NAME' => '/typo3/index.php?id=42&foo=bar',
                    'HTTP_HOST' => 'www.domain.com',
                ],
                '/var/www/myInstance/Web/typo3/index.php',
                '/var/www/myInstance/Web',
                'typo3/index.php?id=42&foo=bar',
            ],
            'in a sub directory' => [
                [
                    'SCRIPT_NAME' => '/some/sub/dir/typo3/index.php?id=42&foo=bar',
                    'HTTP_HOST' => 'www.domain.com',
                ],
                '/var/www/myInstance/Web/typo3/index.php',
                '/var/www/myInstance/Web',
                'typo3/index.php?id=42&foo=bar',
            ],
            'redirected to a sub directory' => [
                'serverParams' => [
                    'REQUEST_URI' => '/',
                    'SCRIPT_NAME' => '/public/',
                    'HTTP_HOST' => 'www.domain.com',
                ],
                'pathThisScript' => '/var/www/html/public/index.php',
                'pathSite' => '/var/www/html/html/public',
                'expected' => '',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getSiteScriptReturnsExpectedPathDataProvider
     */
    public function getSiteScriptReturnsExpectedPath(array $serverParams, string $pathThisScript, string $pathSite, string $expected): void
    {
        $serverRequestParameters = new NormalizedParams($serverParams, [], $pathThisScript, $pathSite);
        self::assertSame($expected, $serverRequestParameters->getSiteScript());
    }

    /**
     * @test
     */
    public function getPathInfoReturnsExpectedValue(): void
    {
        $serverParams = [
            'PATH_INFO' => '/typo3/index.php',
        ];
        $expected = '/typo3/index.php';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getPathInfo());
    }

    /**
     * @test
     */
    public function getHttpRefererReturnsExpectedValue(): void
    {
        $serverParams = [
            'HTTP_REFERER' => 'https://www.domain.com/typo3/index.php?id=42',
        ];
        $expected = 'https://www.domain.com/typo3/index.php?id=42';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getHttpReferer());
    }

    /**
     * @test
     */
    public function getHttpUserAgentReturnsExpectedValue(): void
    {
        $serverParams = [
            'HTTP_USER_AGENT' => 'the client browser',
        ];
        $expected = 'the client browser';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getHttpUserAgent());
    }

    /**
     * @test
     */
    public function getHttpAcceptEncodingReturnsExpectedValue(): void
    {
        $serverParams = [
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
        ];
        $expected = 'gzip, deflate';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getHttpAcceptEncoding());
    }

    /**
     * @test
     */
    public function getHttpAcceptLanguageReturnsExpectedValue(): void
    {
        $serverParams = [
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
        ];
        $expected = 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getHttpAcceptLanguage());
    }

    /**
     * @test
     */
    public function getRemoteHostReturnsExpectedValue(): void
    {
        $serverParams = [
            'REMOTE_HOST' => 'www.clientDomain.com',
        ];
        $expected = 'www.clientDomain.com';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getRemoteHost());
    }

    /**
     * @test
     */
    public function getQueryStringReturnsExpectedValue(): void
    {
        $serverParams = [
            'QUERY_STRING' => 'id=42&foo=bar',
        ];
        $expected = 'id=42&foo=bar';
        $serverRequestParameters = new NormalizedParams($serverParams, [], '', '');
        self::assertSame($expected, $serverRequestParameters->getQueryString());
    }
}
