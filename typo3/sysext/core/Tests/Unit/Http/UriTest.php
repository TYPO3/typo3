<?php
namespace TYPO3\CMS\Core\Tests\Unit\Http;

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

use TYPO3\CMS\Core\Http\Uri;

/**
 * Testcase for \TYPO3\CMS\Core\Http\Uri
 *
 * Adapted from https://github.com/phly/http/
 */
class UriTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function constructorSetsAllProperties()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('user:pass', $uri->getUserInfo());
        $this->assertEquals('local.example.com', $uri->getHost());
        $this->assertEquals(3001, $uri->getPort());
        $this->assertEquals('user:pass@local.example.com:3001', $uri->getAuthority());
        $this->assertEquals('/foo', $uri->getPath());
        $this->assertEquals('bar=baz', $uri->getQuery());
        $this->assertEquals('quz', $uri->getFragment());
    }

    /**
     * @test
     */
    public function canSerializeToString()
    {
        $url = 'https://user:pass@local.example.com:3001/foo?bar=baz#quz';
        $uri = new Uri($url);
        $this->assertEquals($url, (string) $uri);
    }

    /**
     * @test
     */
    public function withSchemeReturnsNewInstanceWithNewScheme()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withScheme('http');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('http', $new->getScheme());
        $this->assertEquals('http://user:pass@local.example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    /**
     * @test
     */
    public function withUserInfoReturnsNewInstanceWithProvidedUser()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo('matthew');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('matthew', $new->getUserInfo());
        $this->assertEquals('https://matthew@local.example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    /**
     * @test
     */
    public function withUserInfoReturnsNewInstanceWithProvidedUserAndPassword()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo('matthew', 'zf2');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('matthew:zf2', $new->getUserInfo());
        $this->assertEquals('https://matthew:zf2@local.example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    /**
     * @test
     */
    public function withHostReturnsNewInstanceWithProvidedHost()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withHost('framework.zend.com');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('framework.zend.com', $new->getHost());
        $this->assertEquals('https://user:pass@framework.zend.com:3001/foo?bar=baz#quz', (string) $new);
    }

    /**
     * @return array
     */
    public function validPortsDataProvider()
    {
        return [
            'int'    => [3000],
            'string' => ['3000']
        ];
    }

    /**
     * @dataProvider validPortsDataProvider
     * @test
     */
    public function withPortReturnsNewInstanceWithProvidedPort($port)
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPort($port);
        $this->assertNotSame($uri, $new);
        $this->assertEquals($port, $new->getPort());
        $this->assertEquals(
            sprintf('https://user:pass@local.example.com:%d/foo?bar=baz#quz', $port),
            (string) $new
        );
    }

    /**
     * @return array
     */
    public function invalidPortsDataProviderType()
    {
        return [
            'null'      => [null],
            'false'     => [false],
            'string'    => ['string'],
            'array'     => [[3000]],
            'object'    => [(object) [3000]],
        ];
    }

    /**
     * @dataProvider invalidPortsDataProviderType
     * @test
     */
    public function withPortRaisesExceptionForInvalidPortsByType($port)
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717324);
        $uri->withPort($port);
    }

    /**
     * @return array
     */
    public function invalidPortsDataProviderRange()
    {
        return [
            'zero'      => [0],
            'too-small' => [-1],
            'too-big'   => [65536],
        ];
    }

    /**
     * @test
     * @todo: Currently, boolean true is interpreted as 1 by canBeInterpretedAsInteger().
     * @todo: This test shows that, but there is an inconsistency and maybe it would be better
     * @todo: if the code would not accept 'true' as valid port but throw an exception instead.
     * @todo: If that is changed, 'true' should be added to the 'invalid type' data provider above.
     */
    public function withPortAcceptsBooleanTrueAsPortOne()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPort(true);
        $this->assertNotSame($uri, $new);
        $this->assertEquals(1, $new->getPort());
    }

    /**
     * @dataProvider invalidPortsDataProviderRange
     * @test
     */
    public function withPortRaisesExceptionForInvalidPortsByRange($port)
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717326);
        $new = $uri->withPort($port);
    }

    /**
     * @test
     */
    public function withPathReturnsNewInstanceWithProvidedPath()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPath('/bar/baz');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('/bar/baz', $new->getPath());
        $this->assertEquals('https://user:pass@local.example.com:3001/bar/baz?bar=baz#quz', (string) $new);
    }

    /**
     * @return array
     */
    public function invalidPathsDataProvider()
    {
        return [
            'null'     => [null],
            'true'     => [true],
            'false'    => [false],
            'array'    => [['/bar/baz']],
            'object'   => [(object) ['/bar/baz']],
        ];
    }

    /**
     * @dataProvider invalidPathsDataProvider
     * @test
     */
    public function withPathRaisesExceptionForInvalidPaths($path)
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717328);
        $new = $uri->withPath($path);
    }

    /**
     * @test
     */
    public function withPathRaisesExceptionForInvalidPathsWithQuery()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717330);
        $new = $uri->withPath('/bar/baz?bat=quz');
    }

    /**
     * @test
     */
    public function withPathRaisesExceptionForInvalidPathsWithFragment()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717332);
        $new = $uri->withPath('/bar/baz#bat');
    }

    /**
     * @test
     */
    public function withQueryReturnsNewInstanceWithProvidedQuery()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withQuery('baz=bat');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('baz=bat', $new->getQuery());
        $this->assertEquals('https://user:pass@local.example.com:3001/foo?baz=bat#quz', (string) $new);
    }

    /**
     * @return array
     */
    public function invalidQueryStringsDataProvider()
    {
        return [
            'null'     => [null],
            'true'     => [true],
            'false'    => [false],
            'array'    => [['baz=bat']],
            'object'   => [(object) ['baz=bat']],
        ];
    }

    /**
     * @dataProvider invalidQueryStringsDataProvider
     * @test
     */
    public function withQueryRaisesExceptionForInvalidQueryStringsByType($query)
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717334);
        $new = $uri->withQuery($query);
    }

    /**
     * @test
     */
    public function withQueryRaisesExceptionForInvalidQueryStringsByFragment()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717336);
        $new = $uri->withQuery('baz=bat#quz');
    }

    /**
     * @test
     */
    public function withFragmentReturnsNewInstanceWithProvidedFragment()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withFragment('qat');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('qat', $new->getFragment());
        $this->assertEquals('https://user:pass@local.example.com:3001/foo?bar=baz#qat', (string) $new);
    }

    /**
     * @return array
     */
    public function authorityInfoDataProvider()
    {
        return [
            'host-only'      => ['http://foo.com/bar', 'foo.com'],
            'host-port'      => ['http://foo.com:3000/bar', 'foo.com:3000'],
            'user-host'      => ['http://me@foo.com/bar', 'me@foo.com'],
            'user-host-port' => ['http://me@foo.com:3000/bar', 'me@foo.com:3000'],
        ];
    }

    /**
     * @dataProvider authorityInfoDataProvider
     * @test
     */
    public function getAuthorityReturnsExpectedValues($url, $expected)
    {
        $uri = new Uri($url);
        $this->assertEquals($expected, $uri->getAuthority());
    }

    /**
     * @test
     */
    public function canEmitOriginFormUrl()
    {
        $url = '/foo/bar?baz=bat';
        $uri = new Uri($url);
        $this->assertEquals($url, (string) $uri);
    }

    /**
     * @test
     */
    public function settingEmptyPathOnAbsoluteUriReturnsAnEmptyPath()
    {
        $uri = new Uri('http://example.com/foo');
        $new = $uri->withPath('');
        $this->assertEquals('', $new->getPath());
    }

    /**
     * @test
     */
    public function stringRepresentationOfAbsoluteUriWithNoPathSetsAnEmptyPath()
    {
        $uri = new Uri('http://example.com');
        $this->assertEquals('http://example.com', (string) $uri);
    }

    /**
     * @test
     */
    public function getPathOnOriginFormRemainsAnEmptyPath()
    {
        $uri = new Uri('?foo=bar');
        $this->assertEquals('', $uri->getPath());
    }

    /**
     * @test
     */
    public function stringRepresentationOfOriginFormWithNoPathRetainsEmptyPath()
    {
        $uri = new Uri('?foo=bar');
        $this->assertEquals('?foo=bar', (string) $uri);
    }

    /**
     * @return array
     */
    public function invalidConstructorUrisDataProvider()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [['http://example.com/']],
            'object' => [(object) ['uri' => 'http://example.com/']],
        ];
    }

    /**
     * @dataProvider invalidConstructorUrisDataProvider
     */
    public function constructorRaisesExceptionForNonStringURI($uri)
    {
        $this->expectException(\InvalidArgumentException::class);
        new Uri($uri);
    }

    /**
     * @test
     */
    public function constructorRaisesExceptionForSeriouslyMalformedURI()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Uri('http:///www.php-fig.org/');
    }

    /**
     * @test
     */
    public function withSchemeStripsOffDelimiter()
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withScheme('https://');
        $this->assertEquals('https', $new->getScheme());
    }

    /**
     * @return array
     */
    public function invalidSchemesDataProvider()
    {
        return [
            'mailto' => ['mailto'],
            'ftp'    => ['ftp'],
            'telnet' => ['telnet'],
            'ssh'    => ['ssh'],
            'git'    => ['git'],
        ];
    }

    /**
     * @dataProvider invalidSchemesDataProvider
     * @test
     */
    public function constructWithUnsupportedSchemeRaisesAnException($scheme)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717338);
        $uri = new Uri($scheme . '://example.com');
    }

    /**
     * @dataProvider invalidSchemesDataProvider
     * @test
     */
    public function withSchemeUsingUnsupportedSchemeRaisesAnException($scheme)
    {
        $uri = new Uri('http://example.com');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717338);
        $uri->withScheme($scheme);
    }

    /**
     * @test
     */
    public function withPathIsNotPrefixedWithSlashIfSetWithoutOne()
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withPath('foo/bar');
        $this->assertEquals('foo/bar', $new->getPath());
    }

    /**
     * @test
     */
    public function withPathNotSlashPrefixedIsEmittedWithSlashDelimiterWhenUriIsCastToString()
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withPath('foo/bar');
        $this->assertEquals('http://example.com/foo/bar', $new->__toString());
    }

    /**
     * @test
     */
    public function withQueryStripsQueryPrefixIfPresent()
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withQuery('?foo=bar');
        $this->assertEquals('foo=bar', $new->getQuery());
    }

    /**
     * @test
     */
    public function withFragmentStripsFragmentPrefixIfPresent()
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withFragment('#/foo/bar');
        $this->assertEquals('/foo/bar', $new->getFragment());
    }

    /**
     * @return array
     */
    public function standardSchemePortCombinationsDataProvider()
    {
        return [
            'http'  => ['http', 80],
            'https' => ['https', 443],
        ];
    }

    /**
     * @dataProvider standardSchemePortCombinationsDataProvider
     * @test
     */
    public function getAuthorityOmitsPortForStandardSchemePortCombinations($scheme, $port)
    {
        $uri = (new Uri())
            ->withHost('example.com')
            ->withScheme($scheme)
            ->withPort($port);
        $this->assertEquals('example.com', $uri->getAuthority());
    }

    /**
     * @test
     */
    public function getPathIsProperlyEncoded()
    {
        $uri = (new Uri())->withPath('/foo^bar');
        $expected = '/foo%5Ebar';
        $this->assertEquals($expected, $uri->getPath());
    }

    /**
     * @test
     */
    public function getPathDoesNotBecomeDoubleEncoded()
    {
        $uri = (new Uri())->withPath('/foo%5Ebar');
        $expected = '/foo%5Ebar';
        $this->assertEquals($expected, $uri->getPath());
    }

    /**
     * @return array
     */
    public function queryStringsForEncodingDataProvider()
    {
        return [
            'key-only'        => ['k^ey', 'k%5Eey'],
            'key-value'       => ['k^ey=valu`', 'k%5Eey=valu%60'],
            'array-key-only'  => ['key[]', 'key%5B%5D'],
            'array-key-value' => ['key[]=valu`', 'key%5B%5D=valu%60'],
            'complex'         => ['k^ey&key[]=valu`&f<>=`bar', 'k%5Eey&key%5B%5D=valu%60&f%3C%3E=%60bar'],
        ];
    }

    /**
     * @dataProvider queryStringsForEncodingDataProvider
     * @test
     */
    public function getQueryIsProperlyEncoded($query, $expected)
    {
        $uri = (new Uri())->withQuery($query);
        $this->assertEquals($expected, $uri->getQuery());
    }

    /**
     * @dataProvider queryStringsForEncodingDataProvider
     * @test
     */
    public function getQueryIsNotDoubleEncoded($query, $expected)
    {
        $uri = (new Uri())->withQuery($expected);
        $this->assertEquals($expected, $uri->getQuery());
    }

    /**
     * @test
     */
    public function getFragmentIsProperlyEncoded()
    {
        $uri = (new Uri())->withFragment('/p^th?key^=`bar#b@z');
        $expected = '/p%5Eth?key%5E=%60bar%23b@z';
        $this->assertEquals($expected, $uri->getFragment());
    }

    /**
     * @test
     */
    public function getFragmentIsNotDoubleEncoded()
    {
        $expected = '/p%5Eth?key%5E=%60bar%23b@z';
        $uri = (new Uri())->withFragment($expected);
        $this->assertEquals($expected, $uri->getFragment());
    }
}
