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

use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Http\MiddlewareStackResolver;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MiddlewareStackResolverTest extends UnitTestCase
{
    /**
     * @test
     */
    public function resolveReturnsMiddlewareStack()
    {
        $middlewares =  array_replace_recursive(
            [],
            require __DIR__ . '/Fixtures/Package1/Configuration/RequestMiddlewares.php',
            require __DIR__ . '/Fixtures/Package2/Configuration/RequestMiddlewares.php'
        );
        $containerProphecy = $this->prophesize();
        $containerProphecy->willImplement(ContainerInterface::class);
        $containerProphecy->get('middlewares')->willReturn($middlewares);
        $dependencyOrderingServiceProphecy = $this->prophesize(DependencyOrderingService::class);
        $dependencyOrderingServiceProphecy->orderByDependencies(Argument::cetera())->willReturnArgument(0);
        $phpFrontendCacheProphecy = $this->prophesize(PhpFrontend::class);
        $phpFrontendCacheProphecy->has(Argument::cetera())->willReturn(false);
        $phpFrontendCacheProphecy->set(Argument::cetera())->willReturn(false);

        $subject = new MiddlewareStackResolver(
            $containerProphecy->reveal(),
            $dependencyOrderingServiceProphecy->reveal(),
            $phpFrontendCacheProphecy->reveal()
        );
        $expected = [
            'secondMiddleware' => 'anotherClassName',
            'firstMiddleware' => 'aClassName',
        ];
        $this->assertEquals($expected, $subject->resolve('testStack'));
    }

    /**
     * @test
     */
    public function resolveReturnsEmptyMiddlewareStackForZeroPackages()
    {
        $middlewares = [];
        $containerProphecy = $this->prophesize();
        $containerProphecy->willImplement(ContainerInterface::class);
        $containerProphecy->get('middlewares')->willReturn($middlewares);
        $dependencyOrderingServiceProphecy = $this->prophesize(DependencyOrderingService::class);
        $dependencyOrderingServiceProphecy->orderByDependencies(Argument::cetera())->willReturnArgument(0);
        $phpFrontendCacheProphecy = $this->prophesize(PhpFrontend::class);
        $phpFrontendCacheProphecy->has(Argument::cetera())->willReturn(false);
        $phpFrontendCacheProphecy->set(Argument::cetera())->willReturn(false);

        $subject = new MiddlewareStackResolver(
            $containerProphecy->reveal(),
            $dependencyOrderingServiceProphecy->reveal(),
            $phpFrontendCacheProphecy->reveal()
        );
        // empty array expected
        $expected = [];
        $this->assertEquals($expected, $subject->resolve('testStack'));
    }

    /**
     * @test
     */
    public function resolveAllowsDisablingAMiddleware()
    {
        $middlewares =  array_replace_recursive(
            [],
            require __DIR__ . '/Fixtures/Package1/Configuration/RequestMiddlewares.php',
            require __DIR__ . '/Fixtures/Package2Disables1/Configuration/RequestMiddlewares.php'
        );
        $containerProphecy = $this->prophesize();
        $containerProphecy->willImplement(ContainerInterface::class);
        $containerProphecy->get('middlewares')->willReturn($middlewares);
        $dependencyOrderingServiceProphecy = $this->prophesize(DependencyOrderingService::class);
        $dependencyOrderingServiceProphecy->orderByDependencies(Argument::cetera())->willReturnArgument(0);
        $phpFrontendCacheProphecy = $this->prophesize(PhpFrontend::class);
        $phpFrontendCacheProphecy->has(Argument::cetera())->willReturn(false);
        $phpFrontendCacheProphecy->set(Argument::cetera())->willReturn(false);

        $subject = new MiddlewareStackResolver(
            $containerProphecy->reveal(),
            $dependencyOrderingServiceProphecy->reveal(),
            $phpFrontendCacheProphecy->reveal()
        );
        $expected = [
            // firstMiddleware is missing, RequestMiddlewares.php of Package2 sets disables=true on firstMiddleware
            'secondMiddleware' => 'anotherClassName',
        ];
        $this->assertEquals($expected, $subject->resolve('testStack'));
    }

    /**
     * @test
     */
    public function resolveAllowsReplacingAMiddleware()
    {
        $middlewares =  array_replace_recursive(
            [],
            require __DIR__ . '/Fixtures/Package1/Configuration/RequestMiddlewares.php',
            require __DIR__ . '/Fixtures/Package2Replaces1/Configuration/RequestMiddlewares.php'
        );
        $containerProphecy = $this->prophesize();
        $containerProphecy->willImplement(ContainerInterface::class);
        $containerProphecy->get('middlewares')->willReturn($middlewares);
        $dependencyOrderingServiceProphecy = $this->prophesize(DependencyOrderingService::class);
        $dependencyOrderingServiceProphecy->orderByDependencies(Argument::cetera())->willReturnArgument(0);
        $phpFrontendCacheProphecy = $this->prophesize(PhpFrontend::class);
        $phpFrontendCacheProphecy->has(Argument::cetera())->willReturn(false);
        $phpFrontendCacheProphecy->set(Argument::cetera())->willReturn(false);

        $subject = new MiddlewareStackResolver(
            $containerProphecy->reveal(),
            $dependencyOrderingServiceProphecy->reveal(),
            $phpFrontendCacheProphecy->reveal()
        );
        $expected = [
            // firstMiddleware has been replaced, RequestMiddlewares.php of $package2 sets a new value for firstMiddleware
            'firstMiddleware' => 'replacedClassName',
            'secondMiddleware' => 'anotherClassName',
        ];
        $this->assertEquals($expected, $subject->resolve('testStack'));
    }
}
