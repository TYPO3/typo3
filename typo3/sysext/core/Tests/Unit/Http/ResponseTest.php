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

use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;

/**
 * Testcase for \TYPO3\CMS\Core\Http\Response
 *
 * Adapted from https://github.com/phly/http/
 */
class ResponseTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var Response
     */
    protected $response;

    protected function setUp()
    {
        $this->response = new Response();
    }

    /**
     * @test
     */
    public function testStatusCodeIs200ByDefault()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    /**
     * @test
     */
    public function testStatusCodeMutatorReturnsCloneWithChanges()
    {
        $response = $this->response->withStatus(400);
        $this->assertNotSame($this->response, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function invalidStatusCodesDataProvider()
    {
        return [
            'too-low'  => [99],
            'too-high' => [600],
            'null'     => [null],
            'bool'     => [true],
            'string'   => ['foo'],
            'array'    => [[200]],
            'object'   => [(object) [200]],
        ];
    }

    /**
     * @dataProvider invalidStatusCodesDataProvider
     * @test
     */
    public function testCannotSetInvalidStatusCode($code)
    {
        $this->setExpectedException('InvalidArgumentException');
        $response = $this->response->withStatus($code);
    }

    /**
     * @test
     */
    public function testReasonPhraseDefaultsToStandards()
    {
        $response = $this->response->withStatus(422);
        $this->assertEquals('Unprocessable Entity', $response->getReasonPhrase());
    }

    /**
     * @test
     */
    public function testCanSetCustomReasonPhrase()
    {
        $response = $this->response->withStatus(422, 'Foo Bar!');
        $this->assertEquals('Foo Bar!', $response->getReasonPhrase());
    }

    /**
     * @test
     */
    public function testConstructorRaisesExceptionForInvalidStream()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Response(['TOTALLY INVALID']);
    }

    /**
     * @test
     */
    public function testConstructorCanAcceptAllMessageParts()
    {
        $body = new Stream('php://memory');
        $status = 302;
        $headers = [
            'location' => ['http://example.com/'],
        ];

        $response = new Response($body, $status, $headers);
        $this->assertSame($body, $response->getBody());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($headers, $response->getHeaders());
    }

    /**
     * @return array
     */
    public function invalidStatusDataProvider()
    {
        return [
            'true'       => [true],
            'false'      => [false],
            'float'      => [100.1],
            'bad-string' => ['Two hundred'],
            'array'      => [[200]],
            'object'     => [(object) ['statusCode' => 200]],
            'too-small'  => [1],
            'too-big'    => [600],
        ];
    }

    /**
     * @dataProvider invalidStatusDataProvider
     * @test
     */
    public function testConstructorRaisesExceptionForInvalidStatus($code)
    {
        $this->setExpectedException('InvalidArgumentException', 'The given status code is not a valid HTTP status code.');
        new Response('php://memory', $code);
    }

    /**
     * @return array
     */
    public function invalidResponseBodyDataProvider()
    {
        return [
            'true'     => [true],
            'false'    => [false],
            'int'      => [1],
            'float'    => [1.1],
            'array'    => [['BODY']],
            'stdClass' => [(object) ['body' => 'BODY']],
        ];
    }

    /**
     * @dataProvider invalidResponseBodyDataProvider
     * @test
     */
    public function testConstructorRaisesExceptionForInvalidBody($body)
    {
        $this->setExpectedException('InvalidArgumentException', 'stream');
        new Response($body);
    }

    /**
     * @test
     */
    public function constructorIgonoresInvalidHeaders()
    {
        $headers = [
            ['INVALID'],
            'x-invalid-null'   => null,
            'x-invalid-true'   => true,
            'x-invalid-false'  => false,
            'x-invalid-int'    => 1,
            'x-invalid-object' => (object) ['INVALID'],
            'x-valid-string'   => 'VALID',
            'x-valid-array'    => ['VALID'],
        ];
        $expected = [
            'x-valid-string' => ['VALID'],
            'x-valid-array'  => ['VALID'],
        ];
        $response = new Response('php://memory', 200, $headers);
        $this->assertEquals($expected, $response->getHeaders());
    }

    /**
     * @return array
     */
    public function headersWithInjectionVectorsDataProvider()
    {
        return [
            'name-with-cr'           => ["X-Foo\r-Bar", 'value'],
            'name-with-lf'           => ["X-Foo\n-Bar", 'value'],
            'name-with-crlf'         => ["X-Foo\r\n-Bar", 'value'],
            'name-with-2crlf'        => ["X-Foo\r\n\r\n-Bar", 'value'],
            'value-with-cr'          => ['X-Foo-Bar', "value\rinjection"],
            'value-with-lf'          => ['X-Foo-Bar', "value\ninjection"],
            'value-with-crlf'        => ['X-Foo-Bar', "value\r\ninjection"],
            'value-with-2crlf'       => ['X-Foo-Bar', "value\r\n\r\ninjection"],
            'array-value-with-cr'    => ['X-Foo-Bar', ["value\rinjection"]],
            'array-value-with-lf'    => ['X-Foo-Bar', ["value\ninjection"]],
            'array-value-with-crlf'  => ['X-Foo-Bar', ["value\r\ninjection"]],
            'array-value-with-2crlf' => ['X-Foo-Bar', ["value\r\n\r\ninjection"]],
        ];
    }

    /**
     * @test
     * @dataProvider headersWithInjectionVectorsDataProvider
     */
    public function cnstructorRaisesExceptionForHeadersWithCRLFVectors($name, $value)
    {
        $this->setExpectedException('InvalidArgumentException');
        $request = new Response('php://memory', 200, [$name => $value]);
    }
}
