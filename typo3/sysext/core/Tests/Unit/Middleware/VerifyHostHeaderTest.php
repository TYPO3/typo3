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

namespace TYPO3\CMS\Core\Tests\Unit\Middleware;

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Middleware\VerifyHostHeader;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class VerifyHostHeaderTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function isAllowedHostHeaderValueReturnsFalseIfTrustedHostsIsNotConfigured(): void
    {
        $subject = new VerifyHostHeader('');
        $serverParams = $_SERVER;
        self::assertFalse($subject->isAllowedHostHeaderValue('evil.foo.bar', $serverParams));
    }

    public static function hostnamesMatchingTrustedHostsConfigurationDataProvider(): array
    {
        return [
            'hostname without port matching' => ['lolli.did.this', '.*\.did\.this'],
            'other hostname without port matching' => ['helmut.did.this', '.*\.did\.this'],
            'two different hostnames without port matching 1st host' => ['helmut.is.secure', '(helmut\.is\.secure|lolli\.is\.secure)'],
            'two different hostnames without port matching 2nd host' => ['lolli.is.secure', '(helmut\.is\.secure|lolli\.is\.secure)'],
            'hostname with port matching' => ['lolli.did.this:42', '.*\.did\.this:42'],
            'hostnames are case insensitive 1' => ['lolli.DID.this:42', '.*\.did.this:42'],
            'hostnames are case insensitive 2' => ['lolli.did.this:42', '.*\.DID.this:42'],
        ];
    }

    public static function hostnamesNotMatchingTrustedHostsConfigurationDataProvider(): array
    {
        return [
            'hostname without port' => ['lolli.did.this', 'helmut\.did\.this'],
            'hostname with port, but port not allowed' => ['lolli.did.this:42', 'helmut\.did\.this'],
            'two different hostnames in pattern but host header starts with different value #1' => ['sub.helmut.is.secure', '(helmut\.is\.secure|lolli\.is\.secure)'],
            'two different hostnames in pattern but host header starts with different value #2' => ['sub.lolli.is.secure', '(helmut\.is\.secure|lolli\.is\.secure)'],
            'two different hostnames in pattern but host header ends with different value #1' => ['helmut.is.secure.tld', '(helmut\.is\.secure|lolli\.is\.secure)'],
            'two different hostnames in pattern but host header ends with different value #2' => ['lolli.is.secure.tld', '(helmut\.is\.secure|lolli\.is\.secure)'],
        ];
    }

    /**
     * @param string $httpHost HTTP_HOST string
     * @param string $hostNamePattern trusted hosts pattern
     * @test
     * @dataProvider hostnamesMatchingTrustedHostsConfigurationDataProvider
     */
    public function isAllowedHostHeaderValueReturnsTrueIfHostValueMatches(string $httpHost, string $hostNamePattern): void
    {
        $serverParams = $_SERVER;

        $subject = new VerifyHostHeader($hostNamePattern);
        self::assertTrue($subject->isAllowedHostHeaderValue($httpHost, $serverParams));
    }

    /**
     * @param string $httpHost HTTP_HOST string
     * @param string $hostNamePattern trusted hosts pattern
     * @test
     * @dataProvider hostnamesNotMatchingTrustedHostsConfigurationDataProvider
     */
    public function isAllowedHostHeaderValueReturnsFalseIfHostValueMatches(string $httpHost, string $hostNamePattern): void
    {
        $serverParams = $_SERVER;

        $subject = new VerifyHostHeader($hostNamePattern);
        self::assertFalse($subject->isAllowedHostHeaderValue($httpHost, $serverParams));
    }

    public function serverNamePatternDataProvider(): array
    {
        return [
            'host value matches server name and server port is default http' => [
                'httpHost' => 'secure.web.server',
                'serverName' => 'secure.web.server',
                'isAllowed' => true,
                'serverPort' => '80',
                'ssl' => 'Off',
            ],
            'host value matches server name if compared case insensitive 1' => [
                'httpHost' => 'secure.web.server',
                'serverName' => 'secure.WEB.server',
                'isAllowed' => true,
            ],
            'host value matches server name if compared case insensitive 2' => [
                'httpHost' => 'secure.WEB.server',
                'serverName' => 'secure.web.server',
                'isAllowed' => true,
            ],
            'host value matches server name and server port is default https' => [
                'httpHost' => 'secure.web.server',
                'serverName' => 'secure.web.server',
                'isAllowed' => true,
                'serverPort' => '443',
                'ssl' => 'On',
            ],
            'host value matches server name and server port' => [
                'httpHost' => 'secure.web.server:88',
                'serverName' => 'secure.web.server',
                'isAllowed' => true,
                'serverPort' => '88',
            ],
            'host value matches server name case insensitive 1 and server port' => [
                'httpHost' => 'secure.WEB.server:88',
                'serverName' => 'secure.web.server',
                'isAllowed' => true,
                'serverPort' => '88',
            ],
            'host value matches server name case insensitive 2 and server port' => [
                'httpHost' => 'secure.web.server:88',
                'serverName' => 'secure.WEB.server',
                'isAllowed' => true,
                'serverPort' => '88',
            ],
            'host value is ipv6 but matches server name and server port' => [
                'httpHost' => '[::1]:81',
                'serverName' => '[::1]',
                'isAllowed' => true,
                'serverPort' => '81',
            ],
            'host value does not match server name' => [
                'httpHost' => 'insecure.web.server',
                'serverName' => 'secure.web.server',
                'isAllowed' => false,
            ],
            'host value does not match server port' => [
                'httpHost' => 'secure.web.server:88',
                'serverName' => 'secure.web.server',
                'isAllowed' => false,
                'serverPort' => '89',
            ],
            'host value has default port that does not match server port' => [
                'httpHost' => 'secure.web.server',
                'serverName' => 'secure.web.server',
                'isAllowed' => false,
                'serverPort' => '81',
                'ssl' => 'Off',
            ],
            'host value has default port that does not match server ssl port' => [
                'httpHost' => 'secure.web.server',
                'serverName' => 'secure.web.server',
                'isAllowed' => false,
                'serverPort' => '444',
                'ssl' => 'On',
            ],
        ];
    }

    /**
     * @param string $httpHost
     * @param string $serverName
     * @param bool $isAllowed
     * @param string $serverPort
     * @param string $ssl
     *
     * @test
     * @dataProvider serverNamePatternDataProvider
     */
    public function isAllowedHostHeaderValueWorksCorrectlyWithWithServerNamePattern(
        string $httpHost,
        string $serverName,
        bool $isAllowed,
        string $serverPort = '80',
        string $ssl = 'Off'
    ): void {
        $serverParams = $_SERVER;
        $serverParams['SERVER_NAME'] = $serverName;
        $serverParams['SERVER_PORT'] = $serverPort;
        $serverParams['HTTPS'] = $ssl;

        $subject = new VerifyHostHeader(VerifyHostHeader::ENV_TRUSTED_HOSTS_PATTERN_SERVER_NAME);

        self::assertSame($isAllowed, $subject->isAllowedHostHeaderValue($httpHost, $serverParams));
    }

    /**
     * @param string $httpHost
     * @param string $serverName
     * @param bool $isAllowed
     * @param string $serverPort
     * @param string $ssl
     *
     * @test
     * @dataProvider serverNamePatternDataProvider
     */
    public function isAllowedHostHeaderValueWorksCorrectlyWithWithServerNamePatternAndSslProxy(
        string $httpHost,
        string $serverName,
        bool $isAllowed,
        string $serverPort = '80',
        string $ssl = 'Off'
    ): void {
        $serverParams = $_SERVER;
        $serverParams['REMOTE_ADDR'] = '10.0.0.1';
        $serverParams['SERVER_NAME'] = $serverName;
        $serverParams['SERVER_PORT'] = $serverPort;
        $serverParams['HTTPS'] = $ssl;

        $subject = new VerifyHostHeader(VerifyHostHeader::ENV_TRUSTED_HOSTS_PATTERN_SERVER_NAME);

        self::assertSame($isAllowed, $subject->isAllowedHostHeaderValue($httpHost, $serverParams));
    }

    /**
     * @param string $httpHost HTTP_HOST string
     * @param string $hostNamePattern trusted hosts pattern
     * @test
     * @dataProvider hostnamesNotMatchingTrustedHostsConfigurationDataProvider
     */
    public function processThrowsExceptionForNotAllowedHostnameValues(string $httpHost, string $hostNamePattern): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1396795884);

        $serverParams = $_SERVER;
        $serverParams['HTTP_HOST'] = $httpHost;

        $subject = new VerifyHostHeader($hostNamePattern);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getServerParams()->willReturn($serverParams);

        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);

        $subject->process($requestProphecy->reveal(), $requestHandlerProphecy->reveal());
    }

    /**
     * @param string $httpHost HTTP_HOST string
     * @param string $hostNamePattern trusted hosts pattern (not used in this test currently)
     * @test
     * @dataProvider hostnamesNotMatchingTrustedHostsConfigurationDataProvider
     */
    public function processAllowsAllHostnameValuesIfHostPatternIsSetToAllowAll(string $httpHost, string $hostNamePattern): void
    {
        $serverParams = $_SERVER;
        $serverParams['HTTP_HOST'] = $httpHost;

        $subject = new VerifyHostHeader(VerifyHostHeader::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getServerParams()->willReturn($serverParams);

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestHandlerProphecy->handle($requestProphecy)->willReturn($responseProphecy->reveal())->shouldBeCalled();

        $subject->process($requestProphecy->reveal(), $requestHandlerProphecy->reveal());
    }
}
