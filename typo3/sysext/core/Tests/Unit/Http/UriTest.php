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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UriTest extends UnitTestCase
{
    #[Test]
    public function constructorSetsAllProperties(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        self::assertEquals('https', $uri->getScheme());
        self::assertEquals('user:pass', $uri->getUserInfo());
        self::assertEquals('local.example.com', $uri->getHost());
        self::assertEquals(3001, $uri->getPort());
        self::assertEquals('user:pass@local.example.com:3001', $uri->getAuthority());
        self::assertEquals('/foo', $uri->getPath());
        self::assertEquals('bar=baz', $uri->getQuery());
        self::assertEquals('quz', $uri->getFragment());
    }

    public static function canSerializeToStringDataProvider(): array
    {
        return [
            'full uri' => [ 'https://user:pass@local.example.com:3001/foo?bar=baz#quz' ],
            'double slash' => [ 'https://user:pass@local.example.com:3001//' ],
            'websocket uri' => [ 'ws://user:pass@local.example.com:3001/foo?bar=baz#quz' ],
            'secure websocket uri' => [ 'wss://user:pass@local.example.com:3001/foo?bar=baz#quz' ],
        ];
    }

    #[DataProvider('canSerializeToStringDataProvider')]
    #[Test]
    public function canSerializeToString(string $uri): void
    {
        self::assertEquals($uri, (string)(new Uri($uri)));
    }

    #[Test]
    public function withSchemeReturnsNewInstanceWithNewScheme(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withScheme('http');
        self::assertNotSame($uri, $new);
        self::assertEquals('http', $new->getScheme());
        self::assertEquals('http://user:pass@local.example.com:3001/foo?bar=baz#quz', (string)$new);
    }

    #[Test]
    public function withUserInfoReturnsNewInstanceWithProvidedUser(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo('matthew');
        self::assertNotSame($uri, $new);
        self::assertEquals('matthew', $new->getUserInfo());
        self::assertEquals('https://matthew@local.example.com:3001/foo?bar=baz#quz', (string)$new);
    }

    #[Test]
    public function withUserInfoReturnsNewInstanceWithProvidedUserAndPassword(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo('matthew', 'zf2');
        self::assertNotSame($uri, $new);
        self::assertEquals('matthew:zf2', $new->getUserInfo());
        self::assertEquals('https://matthew:zf2@local.example.com:3001/foo?bar=baz#quz', (string)$new);
    }

    #[Test]
    public function withHostReturnsNewInstanceWithProvidedHost(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withHost('framework.zend.com');
        self::assertNotSame($uri, $new);
        self::assertEquals('framework.zend.com', $new->getHost());
        self::assertEquals('https://user:pass@framework.zend.com:3001/foo?bar=baz#quz', (string)$new);
    }

    #[Test]
    public function withPortAndNullValueReturnsInstanceWithProvidedPort(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPort(null);
        self::assertEquals(
            'https://user:pass@local.example.com/foo?bar=baz#quz',
            (string)$new
        );
    }

    #[Test]
    public function noSchemeWithDomainAlikeIsInterpretedAsPath(): void
    {
        $subject = new Uri('www.example.com');
        // This is counter intuitive, but interpreted as path.
        // Although we'd like to see protocol independent `//` here,
        // we must not change this, as…
        self::assertEquals('/www.example.com', (string)$subject);

        // …this behaviour is security relevant.
        // This invalid domain name – given without a scheme – is
        // only "save" because they are considered to be paths
        // (invalid hostname alike is not parsed as a hostname):
        $subject = new Uri('evil.tld\\@host.tld');
        self::assertEquals('/evil.tld%5C@host.tld', (string)$subject);

        $subject = new Uri('evil.tld\\\\\\@host.tld');
        self::assertEquals('/evil.tld%5C%5C%5C@host.tld', (string)$subject);
    }

    #[Test]
    public function noSchemeWithDomainAlikeAndTrailingSlashIsInterpretedAsPath(): void
    {
        $subject = new Uri('www.example.com/');
        self::assertEquals('/www.example.com/', (string)$subject);

        $subject = new Uri('evil.tld\\@host.tld/');
        self::assertEquals('/evil.tld%5C@host.tld/', (string)$subject);

        $subject = new Uri('evil.tld\\\\\\@host.tld/');
        self::assertEquals('/evil.tld%5C%5C%5C@host.tld/', (string)$subject);
    }

    public static function validPortsDataProvider(): array
    {
        return [
            'int 1' => [1],
            'int 3000' => [3000],
            'int 65535' => [65535],
        ];
    }

    #[DataProvider('validPortsDataProvider')]
    #[Test]
    public function withPortReturnsNewInstanceWithProvidedPort($port): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPort($port);
        self::assertNotSame($uri, $new);
        self::assertEquals($port, $new->getPort());
        self::assertEquals(
            sprintf('https://user:pass@local.example.com:%d/foo?bar=baz#quz', $port),
            (string)$new
        );
    }

    public static function invalidPortsDataProviderRange(): array
    {
        return [
            'zero'      => [0],
            'too-small' => [-1],
            'too-big'   => [65536],
        ];
    }

    #[DataProvider('invalidPortsDataProviderRange')]
    #[Test]
    public function withPortRaisesExceptionForInvalidPortsByRange($port): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717326);
        $uri->withPort($port);
    }

    #[Test]
    public function standardPortAndSchemeDoesNotRenderPort(): void
    {
        $subject = new Uri('http://www.example.com:80');
        self::assertEquals('http://www.example.com', (string)$subject);
    }

    #[Test]
    public function standardPortAndNoSchemeDoesRenderPort(): void
    {
        $subject = new Uri('www.example.com:80');
        self::assertEquals('//www.example.com:80', (string)$subject);
    }

    #[Test]
    public function noPortAndNoSchemeDoesNotRenderPort(): void
    {
        $subject = new Uri('www.example.com');
        self::assertEquals('/www.example.com', (string)$subject);
    }

    #[Test]
    public function withPathReturnsNewInstanceWithProvidedPath(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPath('/bar/baz');
        self::assertNotSame($uri, $new);
        self::assertEquals('/bar/baz', $new->getPath());
        self::assertEquals('https://user:pass@local.example.com:3001/bar/baz?bar=baz#quz', (string)$new);
    }

    #[Test]
    public function withPathRaisesExceptionForInvalidPathsWithQuery(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717330);
        $uri->withPath('/bar/baz?bat=quz');
    }

    #[Test]
    public function withPathRaisesExceptionForInvalidPathsWithFragment(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717332);
        $uri->withPath('/bar/baz#bat');
    }

    #[Test]
    public function withQueryReturnsNewInstanceWithProvidedQuery(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withQuery('baz=bat');
        self::assertNotSame($uri, $new);
        self::assertEquals('baz=bat', $new->getQuery());
        self::assertEquals('https://user:pass@local.example.com:3001/foo?baz=bat#quz', (string)$new);
    }

    #[Test]
    public function withQueryRaisesExceptionForInvalidQueryStringsByFragment(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717336);
        $uri->withQuery('baz=bat#quz');
    }

    #[Test]
    public function withFragmentReturnsNewInstanceWithProvidedFragment(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withFragment('qat');
        self::assertNotSame($uri, $new);
        self::assertEquals('qat', $new->getFragment());
        self::assertEquals('https://user:pass@local.example.com:3001/foo?bar=baz#qat', (string)$new);
    }

    public static function authorityInfoDataProvider(): array
    {
        return [
            'host-only'      => ['http://foo.com/bar', 'foo.com'],
            'host-port'      => ['http://foo.com:3000/bar', 'foo.com:3000'],
            'user-host'      => ['http://me@foo.com/bar', 'me@foo.com'],
            'user-host-port' => ['http://me@foo.com:3000/bar', 'me@foo.com:3000'],
        ];
    }

    #[DataProvider('authorityInfoDataProvider')]
    #[Test]
    public function getAuthorityReturnsExpectedValues($url, $expected): void
    {
        $uri = new Uri($url);
        self::assertEquals($expected, $uri->getAuthority());
    }

    #[Test]
    public function canEmitOriginFormUrl(): void
    {
        $url = '/foo/bar?baz=bat';
        $uri = new Uri($url);
        self::assertEquals($url, (string)$uri);
    }

    #[Test]
    public function settingEmptyPathOnAbsoluteUriReturnsAnEmptyPath(): void
    {
        $uri = new Uri('http://example.com/foo');
        $new = $uri->withPath('');
        self::assertEquals('', $new->getPath());
    }

    #[Test]
    public function stringRepresentationOfAbsoluteUriWithNoPathSetsAnEmptyPath(): void
    {
        $uri = new Uri('http://example.com');
        self::assertEquals('http://example.com', (string)$uri);
    }

    #[Test]
    public function getPathOnOriginFormRemainsAnEmptyPath(): void
    {
        $uri = new Uri('?foo=bar');
        self::assertEquals('', $uri->getPath());
    }

    #[Test]
    public function stringRepresentationOfOriginFormWithNoPathRetainsEmptyPath(): void
    {
        $uri = new Uri('?foo=bar');
        self::assertEquals('?foo=bar', (string)$uri);
    }

    public static function invalidConstructorUrisDataProvider(): array
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [['http://example.com/']],
            'object' => [(object)['uri' => 'http://example.com/']],
        ];
    }

    #[DataProvider('invalidConstructorUrisDataProvider')]
    public function constructorRaisesExceptionForNonStringURI($uri): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Uri($uri);
    }

    #[Test]
    public function constructorRaisesExceptionForSeriouslyMalformedURI(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Uri('http:///www.php-fig.org/');
    }

    #[Test]
    public function withSchemeStripsOffDelimiter(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withScheme('https://');
        self::assertEquals('https', $new->getScheme());
    }

    public static function invalidSchemesDataProvider(): array
    {
        return [
            'mailto' => ['mailto'],
            'ftp'    => ['ftp'],
            'telnet' => ['telnet'],
            'ssh'    => ['ssh'],
            'git'    => ['git'],
        ];
    }

    #[DataProvider('invalidSchemesDataProvider')]
    #[Test]
    public function constructWithUnsupportedSchemeRaisesAnException($scheme): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717338);
        new Uri($scheme . '://example.com');
    }

    #[DataProvider('invalidSchemesDataProvider')]
    #[Test]
    public function fromAnySchemeWithUnsupportedSchemeIsAllowed($scheme): void
    {
        $uri = Uri::fromAnyScheme($scheme . '://example.com/path?query');
        self::assertSame($scheme . '://example.com/path?query', (string)$uri);
    }

    #[DataProvider('invalidSchemesDataProvider')]
    #[Test]
    public function withSchemeUsingUnsupportedSchemeRaisesAnException($scheme): void
    {
        $uri = new Uri('http://example.com');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717338);
        $uri->withScheme($scheme);
    }

    #[Test]
    public function withPathIsNotPrefixedWithSlashIfSetWithoutOne(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withPath('foo/bar');
        self::assertEquals('foo/bar', $new->getPath());
    }

    #[Test]
    public function withEmptySchemeReturnsNewInstanceWithAbsoluteUri(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withScheme('');
        self::assertNotSame($uri, $new);
        self::assertNotSame((string)$uri, (string)$new);
        self::assertEquals('', $new->getScheme());
        self::assertEquals('//user:pass@local.example.com:3001/foo?bar=baz#quz', (string)$new);
    }

    #[Test]
    public function withPathNotSlashPrefixedIsEmittedWithSlashDelimiterWhenUriIsCastToString(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withPath('foo/bar');
        self::assertEquals('http://example.com/foo/bar', $new->__toString());
    }

    #[Test]
    public function withQueryStripsQueryPrefixIfPresent(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withQuery('?foo=bar');
        self::assertEquals('foo=bar', $new->getQuery());
    }

    #[Test]
    public function withFragmentStripsFragmentPrefixIfPresent(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withFragment('#/foo/bar');
        self::assertEquals('/foo/bar', $new->getFragment());
    }

    public static function standardSchemePortCombinationsDataProvider(): array
    {
        return [
            'http'  => ['http', 80],
            'https' => ['https', 443],
        ];
    }

    #[DataProvider('standardSchemePortCombinationsDataProvider')]
    #[Test]
    public function getAuthorityOmitsPortForStandardSchemePortCombinations($scheme, $port): void
    {
        $uri = (new Uri())
            ->withHost('example.com')
            ->withScheme($scheme)
            ->withPort($port);
        self::assertEquals('example.com', $uri->getAuthority());
    }

    #[Test]
    public function getPathIsProperlyEncoded(): void
    {
        $uri = (new Uri())->withPath('/foo^bar');
        $expected = '/foo%5Ebar';
        self::assertEquals($expected, $uri->getPath());
    }

    #[Test]
    public function getPathDoesNotBecomeDoubleEncoded(): void
    {
        $uri = (new Uri())->withPath('/foo%5Ebar');
        $expected = '/foo%5Ebar';
        self::assertEquals($expected, $uri->getPath());
    }

    public static function queryStringsForEncodingDataProvider(): array
    {
        return [
            'key-only'        => ['k^ey', 'k%5Eey'],
            'key-value'       => ['k^ey=valu`', 'k%5Eey=valu%60'],
            'array-key-only'  => ['key[]', 'key%5B%5D'],
            'array-key-value' => ['key[]=valu`', 'key%5B%5D=valu%60'],
            'complex'         => ['k^ey&key[]=valu`&f<>=`bar', 'k%5Eey&key%5B%5D=valu%60&f%3C%3E=%60bar'],
        ];
    }

    #[DataProvider('queryStringsForEncodingDataProvider')]
    #[Test]
    public function getQueryIsProperlyEncoded($query, $expected): void
    {
        $uri = (new Uri())->withQuery($query);
        self::assertEquals($expected, $uri->getQuery());
    }

    #[DataProvider('queryStringsForEncodingDataProvider')]
    #[Test]
    public function getQueryIsNotDoubleEncoded($query, $expected): void
    {
        $uri = (new Uri())->withQuery($expected);
        self::assertEquals($expected, $uri->getQuery());
    }

    #[Test]
    public function getFragmentIsProperlyEncoded(): void
    {
        $uri = (new Uri())->withFragment('/p^th?key^=`bar#b@z');
        $expected = '/p%5Eth?key%5E=%60bar%23b@z';
        self::assertEquals($expected, $uri->getFragment());
    }

    #[Test]
    public function getFragmentIsNotDoubleEncoded(): void
    {
        $expected = '/p%5Eth?key%5E=%60bar%23b@z';
        $uri = (new Uri())->withFragment($expected);
        self::assertEquals($expected, $uri->getFragment());
    }

    #[Test]
    public function canParseInternationalizedDomainName(): void
    {
        $uri = new Uri('https://ουτοπία.δπθ.gr/');
        // @todo normalize to ascii?
        self::assertEquals('https://ουτοπία.δπθ.gr/', (string)$uri);
    }

    public static function invalidHostDataProvider(): \Generator
    {
        yield [
            'url' => 'https://evil.tld\\@host.tld/',
            'parseUrl' => 'host.tld',
            'chrome' => 'evil.tld',
        ];

        yield [
            'url' => 'https://evil.tld\\\\@host.tld/',
            'parseUrl' => 'host.tld',
            'chrome' => 'evil.tld',
        ];

        yield [
            'url' => 'https://evil.tld\\\\\\@host.tld/',
            'parseUrl' => 'host.tld',
            'chrome' => 'evil.tld',
        ];

        yield [
            'url' => '//evil.tld\\@host.tld/',
            'parseUrl' => 'host.tld',
            'chrome' => 'evil.tld',
        ];

        yield [
            'url' => '//evil.tld\\\\\\@host.tld',
            'parseUrl' => 'host.tld',
            'chrome' => 'evil.tld',
        ];

        yield [
            'url' => 'http://normal.com[@evil.tld]/',
            'parseUrl' => 'evil.tld]',
            'chrome' => 'evil.tld',
        ];

        yield [
            'url' => 'http://evil.tld\\',
            'parseUrl' => 'evil.tld\\',
            'chrome' => 'evil.tld',
        ];
    }

    #[DataProvider('invalidHostDataProvider')]
    #[Test]
    public function uriParsingThrowsExceptionForParserDeviatingHostValues($url, $parseUrl, $chrome): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1728057216);
        new Uri($url);
    }

    #[DataProvider('invalidHostDataProvider')]
    #[Test]
    public function parseUrlReturnsExpectedInvalidHostForInvalidHostURLs($url, $parseUrl, $chrome): void
    {
        $result = parse_url($url, PHP_URL_HOST);
        self::assertSame($parseUrl, $result);
        self::assertNotSame($chrome, $result);
    }

    #[Test]
    public function canParseAllPropertiesWithIPv6(): void
    {
        $uri = new Uri('https://user:pass@[fe80::200:5aee:feaa:20a2]:3001/foo?bar=baz#quz');
        self::assertSame('https', $uri->getScheme());
        self::assertSame('user:pass', $uri->getUserInfo());
        self::assertSame('[fe80::200:5aee:feaa:20a2]', $uri->getHost());
        self::assertSame(3001, $uri->getPort());
        self::assertSame('user:pass@[fe80::200:5aee:feaa:20a2]:3001', $uri->getAuthority());
        self::assertSame('/foo', $uri->getPath());
        self::assertSame('bar=baz', $uri->getQuery());
        self::assertSame('quz', $uri->getFragment());
    }

    #[Test]
    public function canStringifyIPv6(): void
    {
        $input = 'https://user:pass@[fe80::200:5aee:feaa:20a2]:3001/foo?bar=baz#quz';
        $uri = new Uri($input);
        self::assertSame($input, (string)$uri);
    }

    #[Test]
    public function canParseAllPropertiesWithShorthandIPv6(): void
    {
        $uri = new Uri('https://user:pass@[::1]:3001/foo?bar=baz#quz');
        self::assertSame('https', $uri->getScheme());
        self::assertSame('user:pass', $uri->getUserInfo());
        self::assertSame('[::1]', $uri->getHost());
        self::assertSame(3001, $uri->getPort());
        self::assertSame('user:pass@[::1]:3001', $uri->getAuthority());
        self::assertSame('/foo', $uri->getPath());
        self::assertSame('bar=baz', $uri->getQuery());
        self::assertSame('quz', $uri->getFragment());
    }

    #[Test]
    public function canStringifyWithShorthandIPv6(): void
    {
        $input = 'https://user:pass@[::1]:3001/foo?bar=baz#quz';
        $uri = new Uri($input);
        self::assertSame($input, (string)$uri);
    }

    #[Test]
    public function canParseAllPropertiesWithMalformedBracketlessIPv6(): void
    {
        $uri = new Uri('https://user:pass@fe80::200:5aee:feaa:20a2:3001/foo?bar=baz#quz');
        self::assertSame('https', $uri->getScheme());
        self::assertSame('user:pass', $uri->getUserInfo());
        self::assertSame('[fe80::200:5aee:feaa:20a2]', $uri->getHost());
        self::assertSame(3001, $uri->getPort());
        self::assertSame('user:pass@[fe80::200:5aee:feaa:20a2]:3001', $uri->getAuthority());
        self::assertSame('/foo', $uri->getPath());
        self::assertSame('bar=baz', $uri->getQuery());
        self::assertSame('quz', $uri->getFragment());
    }

    #[Test]
    public function canStringifyMalformedBracketlessIPv6toValidIPv6(): void
    {
        $input = 'https://user:pass@fe80::200:5aee:feaa:20a2:3001/foo?bar=baz#quz';
        $expected = 'https://user:pass@[fe80::200:5aee:feaa:20a2]:3001/foo?bar=baz#quz';
        $uri = new Uri($input);
        self::assertSame($expected, (string)$uri);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function invalidUriProvider(): iterable
    {
        yield 'Unsupported scheme ftp' => ['ftp://user:pass@local.example.com:3001/foo?bar=baz#quz'];
        yield 'Unsupported scheme ssh' => ['ssh://user:pass@local.example.com:3001/foo?bar=baz#quz'];
        yield 'Unsupported scheme git' => ['git://user:pass@local.example.com:3001/foo?bar=baz#quz'];

        yield 'Invalid port -1 (too small)' => ['https://user:pass@local.example.com:-1/foo?bar=baz#quz'];
        yield 'Invalid port 0 (zero)' => ['https://user:pass@local.example.com:0/foo?bar=baz#quz'];
        yield 'Invalid port 65536 (too big)' => ['https://user:pass@local.example.com:65536/foo?bar=baz#quz'];

        yield from [
            'Malformed URI'             => ['http://invalid:%20https://example.com'],
            'Colon in non-IPv6 host'    => ['https://user:pass@local:example.com:3001/foo?bar=baz#quz'],
            'Wrong bracket in the IPv6' => ['https://user:pass@fe80[::200:5aee:feaa:20a2]:3001/foo?bar=baz#quz'],
            // percent encoding is allowed in URI but not in web urls particularly with idn encoding for dns.
            // no validation for correct percent encoding either
            // 'Percent in the host' => ["https://user:pass@local%example.com:3001/foo?bar=baz#quz"],
            'Bracket in the host' => ['https://user:pass@[local.example.com]:3001/foo?bar=baz#quz'],
        ];
    }

    #[DataProvider('invalidUriProvider')]
    #[Test]
    public function invalidUriRaisesAnException(string $invalidUri): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Uri($invalidUri);
    }
}
