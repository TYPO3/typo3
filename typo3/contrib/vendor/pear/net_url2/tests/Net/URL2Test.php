<?php
/**
 * Net_URL2, a class representing a URL as per RFC 3986.
 *
 * PHP version 5
 *
 * @category Networking
 * @package  Net_URL2
 * @author   Some Pear Developers <pear@php.net>
 * @license  https://spdx.org/licenses/BSD-3-Clause BSD-3-Clause
 * @link     https://tools.ietf.org/html/rfc3986
 */

/**
 * Test class for Net_URL2.
 *
 * @category Networking
 * @package  Net_URL2
 * @author   Some Pear Developers <pear@php.net>
 * @license  https://spdx.org/licenses/BSD-3-Clause BSD-3-Clause
 * @version  Release: @package_version@
 * @link     https://pear.php.net/package/Net_URL2
 */
class Net_URL2Test extends PHPUnit_Framework_TestCase
{
    /**
     * Tests setting a zero-length string and false as authority
     * Also: Regression test for Bug #20420
     *
     * @covers Net_URL2::setAuthority
     * @return void
     * @link https://pear.php.net/bugs/bug.php?id=20420
     */
    public function testSetEmptyAuthority()
    {
        $url = new Net_URL2('http://www.example.com/');
        $url->setAuthority('');
        $this->assertSame('', $url->getAuthority());
        $this->assertSame('', $url->getHost());
        $this->assertSame(false, $url->getPort());
        $this->assertSame(false, $url->getUserinfo());
        $this->assertSame(false, $url->getUser());

        $url->setAuthority(false);
        $this->assertSame(false, $url->getAuthority());
    }

    /**
     * Tests setting an empty userinfo part
     * Also: Regression test for Bug #20013 and Bug #20399
     *
     * @covers Net_URL2::setUserinfo
     * @covers Net_URL2::getUserinfo
     * @covers Net_URL2::getURL
     * @return void
     * @link https://pear.php.net/bugs/bug.php?id=20013
     * @link https://pear.php.net/bugs/bug.php?id=20399
     */
    public function testSetEmptyUserinfo()
    {
        $url = new Net_URL2('http://@www.example.com/');
        $this->assertSame('http://www.example.com/', $url->getURL());

        $url = new Net_URL2('http://www.example.com/');
        $this->assertSame('http://www.example.com/', $url->getURL());
        $url->setUserinfo('');
        $this->assertSame('http://www.example.com/', $url->getURL());
        $this->assertSame('', $url->getUserinfo());
        $url->setUserinfo(false);
        $this->assertSame('http://www.example.com/', $url->getURL());
        $this->assertFalse($url->getUserinfo());
    }

    /**
     * Tests an URL with no userinfo and normalization
     *
     * Also: Regression test for Bug #20385
     *
     * @covers Net_URL2::getUserinfo
     * @covers Net_URL2::normalize
     * @covers Net_URL2::getNormalizedURL
     * @return void
     * @link https://pear.php.net/bugs/bug.php?id=20385
     */
    public function testNoUserinfoAndNormalize()
    {
        $testUrl = 'http://www.example.com/';

        $url = new Net_URL2($testUrl);
        $this->assertFalse($url->getUserinfo());

        $url->normalize();
        $this->assertFalse($url->getUserinfo());

        $this->assertEquals($testUrl, $url->getURL());
        $this->assertEquals($testUrl, $url->getNormalizedURL());
    }

    /**
     * Tests setQueryVariable().
     *
     * @return void
     */
    public function testSetQueryVariable()
    {

        $url = new Net_URL2('http://www.example.com/');
        $url->setQueryVariable('pear', 'fun');
        $this->assertEquals($url->getURL(), 'http://www.example.com/?pear=fun');
    }

    /**
     * Tests setQueryVariables().
     *
     * @return void
     */
    public function testSetQueryVariables()
    {

        $url = new Net_URL2('http://www.example.com/');
        $url->setQueryVariables(array('pear' => 'fun'));
        $this->assertEquals('http://www.example.com/?pear=fun', $url->getURL());
        $url->setQueryVariables(array('pear' => 'fun for sure'));
        $this->assertEquals(
            'http://www.example.com/?pear=fun%20for%20sure', $url->getURL()
        );
    }

    /**
     * Tests unsetQueryVariable()
     *
     * @return void
     */
    public function testUnsetQueryVariable()
    {
        $url = new Net_URL2(
            'http://www.example.com/?name=david&pear=fun&fish=slippery'
        );

        $removes = array(
            'pear' => 'http://www.example.com/?name=david&fish=slippery',
            'name' => 'http://www.example.com/?fish=slippery',
            'fish' => 'http://www.example.com/',
        );

        foreach ($removes as $name => $expected) {
            $url->unsetQueryVariable($name);
            $this->assertEquals($expected, $url);
        }
    }

    /**
     * Tests setQuery().
     *
     * @return void
     */
    public function testSetQuery()
    {

        $url = new Net_URL2('http://www.example.com/');
        $url->setQuery('flapdoodle&dilly%20all%20day');
        $this->assertEquals(
            $url->getURL(), 'http://www.example.com/?flapdoodle&dilly%20all%20day'
        );
    }

    /**
     * Tests getQuery().
     *
     * @return void
     */
    public function testGetQuery()
    {

        $url = new Net_URL2('http://www.example.com/?foo');
        $this->assertEquals($url->getQuery(), 'foo');
        $url = new Net_URL2('http://www.example.com/?pear=fun&fruit=fruity');
        $this->assertEquals($url->getQuery(), 'pear=fun&fruit=fruity');
    }

    /**
     * Tests setScheme().
     *
     * @return void
     */
    public function testSetScheme()
    {

        $url = new Net_URL2('http://www.example.com/');
        $url->setScheme('ftp');
        $this->assertEquals($url->getURL(), 'ftp://www.example.com/');
        $url->setScheme('gopher');
        $this->assertEquals($url->getURL(), 'gopher://www.example.com/');
    }

    /**
     * Tests setting the fragment.
     *
     * @return void
     */
    public function testSetFragment()
    {

        $url = new Net_URL2('http://www.example.com/');
        $url->setFragment('pear');
        $this->assertEquals('http://www.example.com/#pear', $url->getURL());
    }

    /**
     * A dataProvider for paths that are solved to a base URI.
     *
     * @see testResolveUrls
     * @return array
     */
    public function provideResolveUrls()
    {
        return array(
            array(
                // Examples from RFC 3986, section 5.4.
                // relative base-URI, (URL => absolute URL), [(options)]
                'http://a/b/c/d;p?q',
                array(
                    'g:h'           => 'g:h',
                    'g'             => 'http://a/b/c/g',
                    './g'           => 'http://a/b/c/g',
                    'g/'            => 'http://a/b/c/g/',
                    '/g'            => 'http://a/g',
                    '//g'           => 'http://g',
                    '?y'            => 'http://a/b/c/d;p?y',
                    'g?y'           => 'http://a/b/c/g?y',
                    '#s'            => 'http://a/b/c/d;p?q#s',
                    'g#s'           => 'http://a/b/c/g#s',
                    'g?y#s'         => 'http://a/b/c/g?y#s',
                    ';x'            => 'http://a/b/c/;x',
                    'g;x'           => 'http://a/b/c/g;x',
                    'g;x?y#s'       => 'http://a/b/c/g;x?y#s',
                    ''              => 'http://a/b/c/d;p?q',
                    '.'             => 'http://a/b/c/',
                    './'            => 'http://a/b/c/',
                    '..'            => 'http://a/b/',
                    '../'           => 'http://a/b/',
                    '../g'          => 'http://a/b/g',
                    '../..'         => 'http://a/',
                    '../../'        => 'http://a/',
                    '../../g'       => 'http://a/g',
                    '../../../g'    => 'http://a/g',
                    '../../../../g' => 'http://a/g',
                    '/./g'          => 'http://a/g',
                    '/../g'         => 'http://a/g',
                    'g.'            => 'http://a/b/c/g.',
                    '.g'            => 'http://a/b/c/.g',
                    'g..'           => 'http://a/b/c/g..',
                    '..g'           => 'http://a/b/c/..g',
                    './../g'        => 'http://a/b/g',
                    './g/.'         => 'http://a/b/c/g/',
                    'g/./h'         => 'http://a/b/c/g/h',
                    'g/../h'        => 'http://a/b/c/h',
                    'g;x=1/./y'     => 'http://a/b/c/g;x=1/y',
                    'g;x=1/../y'    => 'http://a/b/c/y',
                    'g?y/./x'       => 'http://a/b/c/g?y/./x',
                    'g?y/../x'      => 'http://a/b/c/g?y/../x',
                    'g#s/./x'       => 'http://a/b/c/g#s/./x',
                    'g#s/../x'      => 'http://a/b/c/g#s/../x',
                    'http:g'        => 'http:g',
                ),
            ),
            array(
                'http://a/b/c/d;p?q',
                array('http:g' => 'http://a/b/c/g'),
                array('::OPTION_STRICT' => false)
            ),
        );
    }

    /**
     * Test the resolve() function to resolve URLs to each other.
     *
     * @param string $baseURL               base-URI
     * @param array  $relativeAbsolutePairs url-pairs, relative => resolved
     * @param array  $options               Net_URL2 options
     *
     * @dataProvider provideResolveUrls
     * @covers Net_URL2::resolve
     * @return void
     */
    public function testResolveUrls($baseURL, array $relativeAbsolutePairs,
        array $options = array()
    ) {
        $options = $this->_translateOptionData($options);
        $base    = new Net_URL2($baseURL, $options);
        $count = count($relativeAbsolutePairs);
        $this->assertGreaterThan(0, $count, 'relative-absolute-pairs data is empty');
        foreach ($relativeAbsolutePairs as $relativeURL => $absoluteURL) {
            $this->assertSame($absoluteURL, (string) $base->resolve($relativeURL));
        }
    }

    /**
     * Helper method to turn options with strings as the constant names
     * (to allow to externalize the fixtures) into a concrete options
     * array that uses the values from the Net_URL2 class constants.
     *
     * @param array $options options
     *
     * @return array
     */
    private function _translateOptionData(array $options)
    {
        // translate string option-names to class constant starting with a colon.
        foreach ($options as $name => $value) {
            if ($name[0] === ':') {
                unset($options[$name]);
                $options[constant("Net_URL2$name")] = $value;
            }
        }
        return $options;
    }

    /**
     * Test the resolve() function throwing an exception with invalid data.
     *
     * @covers Net_URL2::resolve
     * @return void
     */
    public function testResolveException()
    {
        // resolving a relative to a relative URL throws an exception
        $base = new Net_URL2('news.html?category=arts');
        $this->addToAssertionCount(1);
        try {
            $base->resolve('../arts.html#section-2.4');
        } catch (Exception $e) {
            $expected = 'Base-URL must be absolute if reference is not fragment-onl';
            $this->assertStringStartsWith($expected, $e->getMessage());
            return;
        }
        $this->fail('Expected exception not thrown.');
    }

    /**
     * Assert that there is a last error message and it contains needle.
     *
     * @param string $needle needle
     *
     * @return void
     */
    private function _assertLastErrorContains($needle)
    {
        $error = error_get_last();
        $this->assertArrayHasKey('message', $error, 'there was an error previously');
        $pos = strpos($error['message'], $needle);

        $this->assertTrue(
            false !== $pos,
            sprintf(
                'Last error message "%s" contains "%s"', $error['message'], $needle
            )
        );
    }

    /**
     * Test UrlEncoding
     *
     * @return void
     * @link   https://pear.php.net/bugs/bug.php?id=18267
     */
    public function testUrlEncoding()
    {
        $options = array(Net_URL2::OPTION_DROP_SEQUENCE => false);
        $url     = new Net_URL2('http://localhost/bug.php', $options);
        $url->setQueryVariables(
            array(
                'indexed' => array(
                    'first value', 'second value', array('foo', 'bar'),
                )
            )
        );
        $this->assertEquals(
            'http://localhost/bug.php?indexed[0]=first%20value&indexed[1]' .
            '=second%20value&indexed[2][0]=foo&indexed[2][1]=bar',
            strval($url)
        );
    }

    /**
     * A test to verify that keys in QUERY_STRING are encoded by default.
     *
     * @return void
     * @see    Net_URL2::OPTION_ENCODE_KEYS
     * @see    Net_URL2::buildQuery()
     */
    public function testEncodeKeys()
    {
        $url = new Net_URL2('http://example.org');
        $url->setQueryVariables(array('helgi rulez' => 'till too'));
        $this->assertEquals(
            'http://example.org?helgi%20rulez=till%20too',
            strval($url)
        );
    }

    /**
     * A test to verify that keys in QUERY_STRING are not encoded when we supply
     * 'false' via {@link Net_URL2::__construct()}.
     *
     * @return void
     * @see    Net_URL2::OPTION_ENCODE_KEYS
     * @see    Net_URL2::buildQuery()
     */
    public function testDontEncodeKeys()
    {
        $url = new Net_URL2(
            'http://example.org',
            array(Net_URL2::OPTION_ENCODE_KEYS => false)
        );
        $url->setQueryVariables(array('till rulez' => 'helgi too'));
        $this->assertEquals(
            'http://example.org?till rulez=helgi%20too',
            strval($url)
        );
    }

    /**
     * Brackets for array query variables
     *
     * Also text to not encode zero based integer sequence into brackets
     *
     * @return void
     *
     * @link https://pear.php.net/bugs/bug.php?id=20427
     */
    public function testUseBrackets()
    {
        $url = new Net_URL2('http://example.org/');
        $url->setQueryVariables(array('foo' => array('bar', 'baz')));
        $expected = 'http://example.org/?foo[]=bar&foo[]=baz';
        $this->assertEquals($expected, $url->getURL());

        $options = array(Net_URL2::OPTION_DROP_SEQUENCE => false);
        $url     = new Net_URL2('http://example.org/', $options);
        $url->setQueryVariables(array('foo' => array('bar', 'foobar')));
        $expected = 'http://example.org/?foo[0]=bar&foo[1]=foobar';
        $this->assertEquals($expected, $url->getURL());
    }

    /**
     * Do not use brackets for query variables passed as array
     *
     * @return void
     */
    public function testDontUseBrackets()
    {
        $url = new Net_URL2(
            'http://example.org/',
            array(Net_URL2::OPTION_USE_BRACKETS => false)
        );
        $url->setQueryVariables(array('foo' => array('bar', 'foobar')));
        $this->assertEquals(
            'http://example.org/?foo=bar&foo=foobar',
            strval($url)
        );
    }

    /**
     * A dataProvider for example URIs from RFC 3986 Section 1.1.2
     *
     * @return array
     * @link http://tools.ietf.org/html/rfc3986#section-1.1.2
     * @see  testExampleUri
     */
    public function provideExampleUri()
    {
        return array(
            array('ftp://ftp.is.co.za/rfc/rfc1808.txt'),
            array('http://www.ietf.org/rfc/rfc2396.txt'),
            array('ldap://[2001:db8::7]/c=GB?objectClass?one'),
            array('mailto:John.Doe@example.com'),
            array('news:comp.infosystems.www.servers.unix'),
            array('tel:+1-816-555-1212'),
            array('telnet://192.0.2.16:80/'),
            array('urn:oasis:names:specification:docbook:dtd:xml:4.1.2'),
        );
    }

    /**
     * test that Net_URL2 works with the example URIs from RFC 3986 Section 1.1.2
     *
     * @param string $uri example URI
     *
     * @return       void
     * @dataProvider provideExampleUri
     * @link         http://tools.ietf.org/html/rfc3986#section-1.1.2
     * @see          testComponentRecompositionAndNormalization
     */
    public function testExampleUri($uri)
    {
        $url = new Net_URL2($uri);
        $this->assertSame($uri, $url->__toString());
        $url->normalize();
        $this->assertSame($uri, $url->__toString());
    }

    /**
     * A dataProvider for pairs of paths with dot segments and
     * their form when removed.
     *
     * @see testRemoveDotSegments
     * @return array
     */
    public function providePath()
    {
        // The numbers behind are in reference to sections
        // in RFC 3986 5.2.4. Remove Dot Segments
        return array(
            array('../', ''), // 2. A.
            array('./', ''), // 2. A.
            array('/./', '/'), // 2. B.
            array('/.', '/'), // 2. B.
            array('/../', '/'), // 2. C.
            array('/..', '/'), // 2. C.
            array('..', ''), // 2. D.
            array('.', ''), // 2. D.
            array('a', 'a'), // 2. E.
            array('/a', '/a'), // 2. E.
            array('/a/b/c/./../../g', '/a/g'), // 3.
            array('mid/content=5/../6', 'mid/6'), // 3.
            array('../foo/bar.php', 'foo/bar.php'),
            array('/foo/../bar/boo.php', '/bar/boo.php'),
            array('/boo/..//foo//bar.php', '//foo//bar.php'),
            array('/./foo/././bar.php', '/foo/bar.php'),
            array('./.', ''),
        );
    }

    /**
     * Test removal of dot segments
     *
     * @param string $path      Path
     * @param string $assertion Assertion
     *
     * @dataProvider providePath
     * @covers Net_URL2::removeDotSegments
     * @return void
     */
    public function testRemoveDotSegments($path, $assertion)
    {
        $this->assertEquals($assertion, Net_URL2::removeDotSegments($path));
    }

    /**
     * Test removeDotSegments() loop limit warning
     *
     * @covers Net_URL2::removeDotSegments
     * @return void
     */
    public function testRemoveDotSegmentsLoopLimit()
    {
        $loopLimit = 256;
        $segments  = str_repeat('a/', $loopLimit);

        @Net_URL2::removeDotSegments($segments . 'b/');

        $this->_assertLastErrorContains(sprintf(' loop limit %d ', $loopLimit + 1));
        $this->_assertLastErrorContains(" (left: '/b/')");
    }

    /**
     * A dataProvider for query strings and their array representation
     *
     * @see testGetQueryVariables
     * @return array
     */
    public function provideQueryStrings()
    {
        // If the second (expected) value is set or not null, parse_str() differs.
        // Notes on PHP differences with each entry/block
        return array(
            // Net_URL2::getQueryVariables() non-bracket mode
            array('test=1&t%65st=%41&extra=',
                array('test' => array('1', 'A'), 'extra' => ''),
                array('::OPTION_USE_BRACKETS' => false)),
            array(''),
            array('='),
            array('key'),
            array('key='),
            array('=value'),
            array('k=v'),
            // no space as var-name in PHP (array()):
            array(' ',   array(' ' => '' )),
            array(' =v', array(' ' => 'v')),
            array('key=value'),
            // PHP replaces ".", " " and "[" in name replaced by "_":
            array('key.=value' , array('key.'  => 'value')),
            array('key =value' , array('key '  => 'value')),
            array('key[=value' , array('key['  => 'value')),
            array("key\0=value", array("key\0" => 'value')),
            array('key]=value'),
            array('key[]=value'),
            array('[]=value'),
            array(']=value'),
            array(']]=value'),
            // PHP drops variables that are an open bracket only
            array('[=value', array('[' => 'value')),
            // PHP drops spaces in brackets:
            array('key[ ]=value', array('key'  => array(' ' => 'value'))),
            // PHP replaces space " " in name by "_"
            array('key []=1'    , array('key ' => array('1'           ))) ,
            // PHP does not support "\0" in var-names:
            array("key[\0]=value"   , array('key' => array("\0" => 'value'  ))),
            array("key[a\0]=value"  , array('key' => array("a\0" => 'value' ))),
            array("key[a\0b]=value" , array('key' => array("a\0b" => 'value'))),
            array('var[]=1&var[0][]=2'),
            array('key[] []=1'),
            array('key[] [] []=1'),
            array('key[] [] []'),
            array('key[] [] []=[] []'),
            array('[] [] []=[] []'),
        );
    }

    /**
     * Test parsing of query variables
     *
     * @param string $query    string
     * @param mixed  $expected null to test against parse_str() behavior
     * @param array  $options  Net_URL2 options
     *
     * @dataProvider provideQueryStrings
     * @covers       Net_URL2::getQueryVariables
     * @covers       Net_URL2::_queryArrayByKey
     * @covers       Net_URL2::_queryArrayByBrackets
     * @covers       Net_URL2::_queryKeyBracketOffset
     * @return void
     */
    public function testGetQueryVariables($query, $expected = null,
        array $options = array()
    ) {
        $options = $this->_translateOptionData($options);

        $url = new Net_URL2('', $options);

        if ($expected === null) {
            // parse_str() is in PHP before copy on write, therefore
            // it uses pass-by-reference for $expected to return
            // the array
            parse_str($query, $expected);
        }

        // Xdebug: If breakpoints are ignored, see Xdebug Issue 0000924
        $url->setQuery($query);
        $actual = $url->getQueryVariables();

        // Do two assertions, because the first one shows a more nice diff in case
        // it fails and the second one is actually strict which is what has to be
        // tested.
        $this->assertEquals($expected, $actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * data provider of host and port
     *
     * @return array
     * @see testHostAndPort
     */
    public function provideHostAndPort()
    {
        return array(
            array('[::1]', '[::1]', false),
            array('[::1]:', '[::1]', false),
            array('[::1]:128', '[::1]', '128'),
            array('127.0.0.1', '127.0.0.1', false),
            array('127.0.0.1:', '127.0.0.1', false),
            array('127.0.0.1:128', '127.0.0.1', '128'),
            array('localhost', 'localhost', false),
            array('localhost:', 'localhost', false),
            array('localhost:128', 'localhost', '128'),
        );
    }

    /**
     * test that an authority containing host and port maps to expected host and port
     *
     * This is also a regression test to test that using ip-literals works along-
     * side ipv4 and reg-name hosts incl. port numbers
     *
     * It was reported as Bug #20423 on 2014-10-06 18:25 UTC that
     * http://[::1]// URI drops the host
     *
     * @param string      $authority    string
     * @param string      $expectedHost string
     * @param string|bool $expectedPort string or FALSE
     *
     * @return void
     * @dataProvider provideHostAndPort
     * @covers       Net_URL2::setAuthority()
     * @link         https://pear.php.net/bugs/bug.php?id=20423
     * @link         http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @link         http://tools.ietf.org/html/rfc3986#section-3.2
     * @link         http://tools.ietf.org/html/rfc3986#section-3.2.3
     */
    public function testHostAndPort($authority, $expectedHost, $expectedPort)
    {
        $uri = "http://{$authority}";
        $url = new Net_URL2($uri);
        $this->assertSame($expectedHost, $url->getHost());
        $this->assertSame($expectedPort, $url->getPort());
    }

    /**
     * This is a regression test to test that Net_URL2::getQueryVariables() does
     * not have a problem with nested array values in form of stacked brackets and
     * was reported as Bug #17036 on 2010-01-26 15:48 UTC that there would be
     * a problem with parsed query string.
     *
     * @link   https://pear.php.net/bugs/bug.php?id=17036
     * @covers Net_URL2::getQueryVariables
     * @return void
     */
    public function test17036()
    {
        $queryString = 'start=10&test[0][first][1.1][20]=coucou';
        $url         = new Net_URL2('?' . $queryString);
        $vars = $url->getQueryVariables();

        $expected = array();
        $expected['start'] = '10';
        $expected['test'][0]['first']['1.1'][20] = 'coucou';

        $this->assertEquals($expected, $vars); // give nice diff in case of failuer
        $this->assertSame($expected, $vars);   // strictly assert the premise
    }

    /**
     * This is a regression test to test that resolve() does
     * merge the path if the base path is empty as the opposite
     * was reported as Bug #19176 on 2011-12-31 02:07 UTC
     *
     * @return void
     */
    public function test19176()
    {
        $foo  = new Net_URL2('http://www.example.com');
        $test = $foo->resolve('test.html')->getURL();
        $this->assertEquals('http://www.example.com/test.html', $test);
    }

    /**
     * This is a regression test that removeDotSegments('0') is
     * working as it was reported as not-working in Bug #19315
     * on 2012-03-04 04:18 UTC.
     *
     * @return void
     */
    public function test19315()
    {
        $actual = Net_URL2::removeDotSegments('0');
        $this->assertSame('0', $actual);

        $nonStringObject = (object)array();
        try {
            Net_URL2::removeDotSegments($nonStringObject);
        } catch (PHPUnit_Framework_Error $error) {
            $this->addToAssertionCount(1);
        }

        if (!isset($error)) {
            $this->fail('Failed to verify that error was given.');
        }
        unset($error);
    }

    /**
     * This is a regression test to test that recovering from
     * a wrongly encoded URL is possible.
     *
     * It was requested as Request #19684 on 2011-12-31 02:07 UTC
     * that redirects containing spaces should work.
     *
     * @return void
     */
    public function test19684()
    {
        // Location: URL obtained Thu, 25 Apr 2013 20:51:31 GMT
        $urlWithSpace = 'http://www.sigmaaldrich.com/catalog/search?interface=CAS N'
            . 'o.&term=108-88-3&lang=en&region=US&mode=match+partialmax&N=0+2200030'
            . '48+219853269+219853286';

        $urlCorrect = 'http://www.sigmaaldrich.com/catalog/search?interface=CAS%20N'
            . 'o.&term=108-88-3&lang=en&region=US&mode=match+partialmax&N=0+2200030'
            . '48+219853269+219853286';

        $url = new Net_URL2($urlWithSpace);

        $this->assertTrue($url->isAbsolute());

        $urlPart = parse_url($urlCorrect, PHP_URL_PATH);
        $this->assertSame($urlPart, $url->getPath());

        $urlPart = parse_url($urlCorrect, PHP_URL_QUERY);
        $this->assertSame($urlPart, $url->getQuery());

        $this->assertSame($urlCorrect, (string)$url);

        $input    = 'http://example.com/get + + to my nose/';
        $expected = 'http://example.com/get%20+%20+%20to%20my%20nose/';
        $actual   = new Net_URL2($input);
        $this->assertEquals($expected, $actual);
        $actual->normalize();
    }

    /**
     * data provider of list of equivalent URLs.
     *
     * @see testNormalize
     * @see testConstructSelf
     * @return array
     */
    public function provideEquivalentUrlLists()
    {
        return array(
            // String equivalence:
            array('http://example.com/', 'http://example.com/'),

            // Originally first dataset:
            array('http://www.example.com/%9a', 'http://www.example.com/%9A'),

            // Example from RFC 3986 6.2.2.:
            array('example://a/b/c/%7Bfoo%7D', 'eXAMPLE://a/./b/../b/%63/%7bfoo%7d'),

            // Example from RFC 3986 6.2.2.1.:
            array('HTTP://www.EXAMPLE.com/', 'http://www.example.com/'),

            // Example from RFC 3986 6.2.3.:
            array(
                'http://example.com', 'http://example.com/',
                'http://example.com:/', 'http://example.com:80/'
            ),

            // Bug #20161: URLs with "0" as host fail to normalize with empty path
            array('http://0/', 'http://0'),

            // Bug #20473: Normalize query and fragment broken
            array('foo:///?%66%6f%6f#%62%61%72', 'foo:///?foo#bar'),
        );
    }

    /**
     * This is a coverage test to invoke the normalize()
     * method.
     *
     * @return void
     *
     * @dataProvider provideEquivalentUrlLists
     */
    public function testNormalize()
    {
        $urls = func_get_args();

        $this->assertGreaterThanOrEqual(2, count($urls));

        $last = null;

        foreach ($urls as $index => $url) {
            $url = new Net_Url2($url);
            $url->normalize();
            if ($index) {
                $this->assertSame((string)$last, (string)$url);
            }
            $last = $url;
        }
    }

    /**
     * This is a coverage test to invoke __get and __set
     *
     * @covers Net_URL2::__get
     * @covers Net_URL2::__set
     * @return void
     */
    public function testMagicSetGet()
    {
        $url = new Net_URL2('');

        $property       = 'authority';
        $url->$property = $value = 'value';
        $this->assertEquals($value, $url->$property);

        $property       = 'unsetProperty';
        $url->$property = $value;
        $this->assertEquals(false, $url->$property);
    }

    /**
     * data provider of uri and normal URIs
     *
     * @return array
     * @see testComponentRecompositionAndNormalization
     */
    public function provideComposedAndNormalized()
    {
        return array(
            array(''),
            array('http:g'),
            array('user@host'),
            array('mailto:user@host'),
        );
    }

    /**
     * Tests Net_URL2 RFC 3986 5.3. Component Recomposition in the light
     * of normalization
     *
     * This is also a regression test to test that a missing authority works well
     * with normalization
     *
     * It was reported as Bug #20418 on 2014-10-02 22:10 UTC that there is an
     * Incorrect normalization of URI with missing authority
     *
     * @param string $uri URI
     *
     * @return       void
     * @covers       Net_URL2::getUrl()
     * @covers       Net_URL2::normalize()
     * @dataProvider provideComposedAndNormalized
     * @link         https://pear.php.net/bugs/bug.php?id=20418
     * @see          testExampleUri
     */
    public function testComponentRecompositionAndNormalization($uri)
    {
        $url = new Net_URL2($uri);
        $this->assertSame($uri, $url->getURL());
        $url->normalize();
        $this->assertSame($uri, $url->getURL());
    }

    /**
     * Tests Net_URL2 ctors URL parameter works with objects implementing
     * __toString().
     *
     * @dataProvider provideEquivalentUrlLists
     * @coversNothing
     * @return void
     */
    public function testConstructSelf()
    {
        $urls = func_get_args();
        foreach ($urls as $url) {
            $urlA = new Net_URL2($url);
            $urlB = new Net_URL2($urlA);
            $this->assertSame((string)$urlA, (string)$urlB);
        }
    }

    /**
     * This is a feature test to see that the userinfo's data is getting
     * encoded as outlined in #19684.
     *
     * @covers Net_URL2::setAuthority
     * @covers Net_URL2::setUserinfo
     * @return void
     */
    public function testEncodeDataUserinfoAuthority()
    {
        $url = new Net_URL2('http://john doe:secret@example.com/');
        $this->assertSame('http://john%20doe:secret@example.com/', (string)$url);

        $url->setUserinfo('john doe');
        $this->assertSame('http://john%20doe@example.com/', (string)$url);

        $url->setUserinfo('john doe', 'pa wd');
        $this->assertSame('http://john%20doe:pa%20wd@example.com/', (string)$url);
    }

    /**
     * This is a regression test to test that using the
     * host-name "0" does work with getAuthority()
     *
     * It was reported as Bug #20156 on 2013-12-27 22:56 UTC
     * that setAuthority() with "0" as host would not work
     *
     * @covers Net_URL2::setAuthority
     * @covers Net_URL2::getAuthority
     * @covers Net_URL2::setHost
     * @return void
     */
    public function test20156()
    {
        $url  = new Net_URL2('http://user:pass@example.com:127/');
        $host = '0';
        $url->setHost($host);
        $this->assertSame('user:pass@0:127', $url->getAuthority());

        $url->setHost(false);
        $this->assertSame(false, $url->getAuthority());

        $url->setAuthority($host);
        $this->assertSame($host, $url->getAuthority());
    }

    /**
     * This is a regression test to test that setting "0" as path
     * does not break normalize().
     *
     * It was reported as Bug #20157 on 2013-12-27 23:42 UTC that
     * normalize() with "0" as path would not work.
     *
     * @covers Net_URL2::normalize
     * @return void
     */
    public function test20157()
    {
        $subject = 'http://example.com';
        $url     = new Net_URL2($subject);
        $url->setPath('0');
        $url->normalize();
        $this->assertSame("$subject/0", (string)$url);
    }

    /**
     * This is a regression test to ensure that fragment-only references can be
     * resolved to a non-absolute Base-URI.
     *
     * It was reported as Bug #20158 2013-12-28 14:49 UTC that fragment-only
     * references would not be resolved to non-absolute base URI
     *
     * @covers Net_URL2::resolve
     * @covers Net_URL2::_isFragmentOnly
     * @return void
     */
    public function test20158()
    {
        $base     = new Net_URL2('myfile.html');
        $resolved = $base->resolve('#world');
        $this->assertSame('myfile.html#world', (string)$resolved);
    }

    /**
     * This is a regression test to ensure that authority and path are properly
     * combined when the path does not start with a slash which is the separator
     * character between authority and path.
     *
     * It was reported as Bug #20159 2013-12-28 17:18 UTC that authority
     * would not be terminated by slash
     *
     * @covers Net_URL2::getUrl
     * @return void
     */
    public function test20159()
    {
        $url = new Net_URL2('index.html');
        $url->setHost('example.com');
        $this->assertSame('//example.com/index.html', (string)$url);
    }

    /**
     * This is a regression test to test that using the file:// URI scheme with
     * an empty (default) hostname has the empty authority preserved when the
     * full URL is build.
     *
     * It was reported as Bug #20304 on 2014-06-21 00:06 UTC
     * that file:// URI are crippled.
     *
     * Tests with a default authority for the "file" URI scheme
     *
     * @covers Net_URL2::getURL
     * @return void
     * @link https://pear.php.net/bugs/bug.php?id=20304
     */
    public function test20304()
    {
        $file = 'file:///path/to/file';
        $url = new Net_URL2($file);
        $this->assertSame($file, (string) $url);

        $file = 'file://localhost/path/to/file';
        $url = new Net_URL2($file);
        $this->assertSame($file, (string) $url);

        $file = 'file://user@/path/to/file';
        $url = new Net_URL2($file);
        $this->assertSame($file, (string) $url);

        $file = 'FILE:///path/to/file';
        $url = new Net_URL2($file);
        $this->assertSame($file, (string) $url);
    }
}
