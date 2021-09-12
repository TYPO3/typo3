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

namespace TYPO3\CMS\Core\Tests\Unit\Package;

use ArrayObject;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PseudoServiceProvider;
use TYPO3\CMS\Core\Tests\Unit\Package\Mocks\Package1ServiceProviderMock;
use TYPO3\CMS\Core\Tests\Unit\Package\Mocks\Package2ServiceProviderMock;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\GeneralUtilityMakeInstanceInjectLoggerFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractServiceProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function configureMiddlewaresReturnsMergedMiddlewares(): void
    {
        $containerProphecy = $this->prophesize();
        $containerProphecy->willImplement(ContainerInterface::class);

        $middlewares = new ArrayObject();
        $middlewares = Package1ServiceProviderMock::configureMiddlewares(
            $containerProphecy->reveal(),
            $middlewares
        );
        $middlewares = Package2ServiceProviderMock::configureMiddlewares(
            $containerProphecy->reveal(),
            $middlewares
        );

        $expected = new ArrayObject([
            'testStack' => [
                'firstMiddleware' => [
                    'target' => 'aClassName',
                ],
                'secondMiddleware' => [
                    'target' => 'anotherClassName',
                ],
            ],
        ]);
        self::assertEquals($expected, $middlewares);
    }

    /**
     * @test
     */
    public function configureMiddlewaresReturnsMergedMiddlewaresWithPseudoServiceProvider(): void
    {
        $containerProphecy = $this->prophesize();
        $containerProphecy->willImplement(ContainerInterface::class);

        $package2 = $this->prophesize(Package::class);
        $package2->getPackagePath()->willReturn(__DIR__ . '/../Http/Fixtures/Package2/');
        $package2ServiceProvider = new PseudoServiceProvider($package2->reveal());

        $middlewares = new ArrayObject();
        $middlewares = Package1ServiceProviderMock::configureMiddlewares(
            $containerProphecy->reveal(),
            $middlewares
        );
        $middlewares = $package2ServiceProvider->getExtensions()['middlewares'](
            $containerProphecy->reveal(),
            $middlewares
        );

        $expected = new ArrayObject([
            'testStack' => [
                'firstMiddleware' => [
                    'target' => 'aClassName',
                ],
                'secondMiddleware' => [
                    'target' => 'anotherClassName',
                ],
            ],
        ]);
        self::assertEquals($expected, $middlewares);
    }

    /**
     * @test
     */
    public function configureMiddlewaresReturnsMergedMiddlewaresWithOverrides(): void
    {
        $containerProphecy = $this->prophesize();
        $containerProphecy->willImplement(ContainerInterface::class);

        $package2 = $this->prophesize(Package::class);
        $package2->getPackagePath()->willReturn(__DIR__ . '/../Http/Fixtures/Package2Disables1/');
        $package2ServiceProvider = new PseudoServiceProvider($package2->reveal());

        $middlewares = new ArrayObject();
        $middlewares = Package1ServiceProviderMock::configureMiddlewares(
            $containerProphecy->reveal(),
            $middlewares
        );
        $middlewares = $package2ServiceProvider->getExtensions()['middlewares'](
            $containerProphecy->reveal(),
            $middlewares
        );

        $expected = new ArrayObject([
            'testStack' => [
                'firstMiddleware' => [
                    'target' => 'aClassName',
                    'disabled' => true,
                ],
                'secondMiddleware' => [
                    'target' => 'anotherClassName',
                ],
            ],
        ]);
        self::assertEquals($expected, $middlewares);
    }

    /**
     * @test
     */
    public function configureMiddlewaresReturnsMergedMiddlewaresWithReplacements(): void
    {
        $containerProphecy = $this->prophesize();
        $containerProphecy->willImplement(ContainerInterface::class);

        $package2 = $this->prophesize(Package::class);
        $package2->getPackagePath()->willReturn(__DIR__ . '/../Http/Fixtures/Package2Replaces1/');
        $package2ServiceProvider = new PseudoServiceProvider($package2->reveal());

        $middlewares = new ArrayObject();
        $middlewares = Package1ServiceProviderMock::configureMiddlewares(
            $containerProphecy->reveal(),
            $middlewares
        );
        $middlewares = $package2ServiceProvider->getExtensions()['middlewares'](
            $containerProphecy->reveal(),
            $middlewares
        );

        $expected = new ArrayObject([
            'testStack' => [
                'firstMiddleware' => [
                    'target' => 'replacedClassName',
                ],
                'secondMiddleware' => [
                    'target' => 'anotherClassName',
                ],
            ],
        ]);
        self::assertEquals($expected, $middlewares);
    }

    /**
     * @test
     */
    public function newReturnsClassInstance(): void
    {
        $containerProphecy = $this->prophesize();
        $containerProphecy->willImplement(ContainerInterface::class);

        $className = get_class($this->getMockBuilder('foo')->getMock());
        $newClosure = $this->getClosureForNew();
        $instance = $newClosure($containerProphecy->reveal(), $className);
        self::assertInstanceOf($className, $instance);
    }

    /**
     * @test
     */
    public function newInjectsLogger(): void
    {
        $containerProphecy = $this->prophesize();
        $containerProphecy->willImplement(ContainerInterface::class);

        $loggerProphecy = $this->prophesize();
        $loggerProphecy->willImplement(LoggerInterface::class);

        $logManagerProphecy = $this->prophesize(LogManager::class);
        $logManagerProphecy->getLogger(GeneralUtilityMakeInstanceInjectLoggerFixture::class)->willReturn($loggerProphecy->reveal());

        $containerProphecy->get(LogManager::class)->willReturn($logManagerProphecy->reveal());
        $className = GeneralUtilityMakeInstanceInjectLoggerFixture::class;
        $newClosure = $this->getClosureForNew();
        $instance = $newClosure($containerProphecy->reveal(), $className);
        self::assertInstanceOf(LoggerInterface::class, $instance->getLogger());
    }

    /**
     * @return \Closure
     */
    protected function getClosureForNew(): \Closure
    {
        return \Closure::bind(
            function ($container, $className, $arguments = []) {
                return AbstractServiceProvider::new($container, $className, $arguments);
            },
            null,
            AbstractServiceProvider::class
        );
    }
}
