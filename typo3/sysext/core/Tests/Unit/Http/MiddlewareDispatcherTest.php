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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Unit\Http\Fixtures\MiddlewareFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MiddlewareDispatcherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function executesKernelWithEmptyMiddlewareStack()
    {
        $kernel = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response)->withStatus(204);
            }
        };

        $dispatcher = new MiddlewareDispatcher($kernel);
        $response = $dispatcher->handle(new ServerRequest);

        $this->assertSame(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function executesMiddlewaresLastInFirstOut()
    {
        $kernel = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response)
                    ->withStatus(204)
                    ->withHeader('X-SEQ-PRE-REQ-HANDLER', $request->getHeader('X-SEQ-PRE-REQ-HANDLER'));
            }
        };

        $middleware1 = new class implements MiddlewareInterface {
            public $id = '0';

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $request = $request->withAddedHeader('X-SEQ-PRE-REQ-HANDLER', $this->id);
                $response = $handler->handle($request);

                return $response->withAddedHeader('X-SEQ-POST-REQ-HANDLER', $this->id);
            }
        };

        $middleware2 = clone $middleware1;
        $middleware2->id = '1';

        MiddlewareFixture::$id = '2';

        $middleware4 = clone $middleware1;
        $middleware4->id = '3';

        $dispatcher = new MiddlewareDispatcher($kernel, [$middleware1, $middleware2]);
        $dispatcher->lazy(MiddlewareFixture::class);
        $dispatcher->add($middleware4);

        $response = $dispatcher->handle(new ServerRequest);

        $this->assertSame(['3', '2', '1', '0'], $response->getHeader('X-SEQ-PRE-REQ-HANDLER'));
        $this->assertSame(['0', '1', '2', '3'], $response->getHeader('X-SEQ-POST-REQ-HANDLER'));
        $this->assertSame(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function doesNotInstantiateLazyMiddlewareInCaseOfAnEarlyReturningOuterMiddleware()
    {
        $kernel = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response;
            }
        };
        $middleware = new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return (new Response)->withStatus(404);
            }
        };

        MiddlewareFixture::$hasBeenInstantiated = false;
        $dispatcher = new MiddlewareDispatcher($kernel, [MiddlewareFixture::class, $middleware]);
        $response = $dispatcher->handle(new ServerRequest);

        $this->assertFalse(MiddlewareFixture::$hasBeenInstantiated);
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function throwsExceptionForLazyNonMiddlewareInterfaceClasses()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1516821342);

        $kernel = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response;
            }
        };

        MiddlewareFixture::$hasBeenInstantiated = false;
        $dispatcher = new MiddlewareDispatcher($kernel);
        $dispatcher->lazy(\stdClass::class);
        $dispatcher->handle(new ServerRequest);
    }

    /**
     * @test
     */
    public function canBeExcutedMultipleTimes()
    {
        $kernel = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response;
            }
        };
        $middleware = new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return (new Response)->withStatus(204);
            }
        };

        $dispatcher = new MiddlewareDispatcher($kernel);
        $dispatcher->add($middleware);

        $response1 = $dispatcher->handle(new ServerRequest);
        $response2 = $dispatcher->handle(new ServerRequest);

        $this->assertSame(204, $response1->getStatusCode());
        $this->assertSame(204, $response2->getStatusCode());
    }

    /**
     * @test
     */
    public function canBeReExecutedRecursivelyDuringDispatch()
    {
        $kernel = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response;
            }
        };

        $dispatcher = new MiddlewareDispatcher($kernel);

        $dispatcher->add(new class($dispatcher) implements MiddlewareInterface {
            private $dispatcher;

            public function __construct(RequestHandlerInterface $dispatcher)
            {
                $this->dispatcher = $dispatcher;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                if ($request->hasHeader('X-NESTED')) {
                    return (new Response)->withStatus(204)->withAddedHeader('X-TRACE', 'nested');
                }

                $response = $this->dispatcher->handle($request->withAddedHeader('X-NESTED', '1'));

                return $response->withAddedHeader('X-TRACE', 'outer');
            }
        });

        $response = $dispatcher->handle(new ServerRequest);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame(['nested', 'outer'], $response->getHeader('X-TRACE'));
    }

    /**
     * @test
     */
    public function fetchesMiddlewareFromContainer()
    {
        $kernel = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response;
            }
        };

        $middleware = new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return (new Response)->withStatus(404);
            }
        };

        $containerProphecy = $this->prophesize();
        $containerProphecy->willImplement(ContainerInterface::class);
        $containerProphecy->has('somemiddlewarename')->willReturn(true);
        $containerProphecy->get('somemiddlewarename')->willReturn($middleware);

        $dispatcher = new MiddlewareDispatcher($kernel, ['somemiddlewarename'], $containerProphecy->reveal());
        $response = $dispatcher->handle(new ServerRequest);

        $this->assertSame(404, $response->getStatusCode());
    }
}
