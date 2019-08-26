<?php
declare(strict_types = 1);
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

use Psr\Http\Message\ResponseFactoryInterface;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Http\ResponseFactory
 */
class ResponseFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function implementsPsr17FactoryInterface()
    {
        $factory = new ResponseFactory();
        $this->assertInstanceOf(ResponseFactoryInterface::class, $factory);
    }

    /**
     * @test
     */
    public function testResponseHasStatusCode200ByDefault()
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse();
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function testResponseHasStatusCodeSet()
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse(201);
        $this->assertSame(201, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function testResponseHasDefaultReasonPhrase()
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse(301);
        $this->assertSame('Moved Permanently', $response->getReasonPhrase());
    }

    /**
     * @test
     */
    public function testResponseHasCustomReasonPhrase()
    {
        $factory = new ResponseFactory();
        $response = $factory->createResponse(201, 'custom message');
        $this->assertSame('custom message', $response->getReasonPhrase());
    }
}
