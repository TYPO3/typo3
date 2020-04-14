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

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Http\RequestFactory
 */
class RequestFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function implementsPsr17FactoryInterface()
    {
        $factory = new RequestFactory();
        self::assertInstanceOf(RequestFactoryInterface::class, $factory);
    }

    /**
     * @test
     */
    public function testRequestHasMethodSet()
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('POST', '/');
        self::assertSame('POST', $request->getMethod());
    }

    /**
     * @test
     */
    public function testRequestFactoryHasAWritableEmptyBody()
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('GET', '/');
        $body = $request->getBody();

        self::assertInstanceOf(RequestInterface::class, $request);

        self::assertSame('', $body->__toString());
        self::assertSame(0, $body->getSize());
        self::assertTrue($body->isSeekable());

        $body->write('Foo');
        self::assertSame(3, $body->getSize());
        self::assertSame('Foo', $body->__toString());
    }

    /**
     * @return array
     */
    public function invalidRequestUriDataProvider()
    {
        return [
            'true'     => [true],
            'false'    => [false],
            'int'      => [1],
            'float'    => [1.1],
            'array'    => [['http://example.com']],
            'stdClass' => [(object)['href' => 'http://example.com']],
        ];
    }

    /**
     * @dataProvider invalidRequestUriDataProvider
     * @test
     */
    public function constructorRaisesExceptionForInvalidUri($uri)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717272);
        $factory = new RequestFactory();
        $factory->createRequest('GET', $uri);
    }

    /**
     * @test
     */
    public function raisesExceptionForInvalidMethod()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717275);
        $factory = new RequestFactory();
        $factory->createRequest('BOGUS-BODY', '/');
    }
}
