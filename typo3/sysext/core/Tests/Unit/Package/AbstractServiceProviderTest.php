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
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PseudoServiceProvider;
use TYPO3\CMS\Core\Tests\Unit\Package\Mocks\Package1ServiceProviderMock;
use TYPO3\CMS\Core\Tests\Unit\Package\Mocks\Package2ServiceProviderMock;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\GeneralUtilityMakeInstanceInjectLoggerFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractServiceProviderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function configureMiddlewaresReturnsMergedMiddlewares(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);

        $middlewares = new ArrayObject();
        $middlewares = Package1ServiceProviderMock::configureMiddlewares($containerMock, $middlewares);
        $middlewares = Package2ServiceProviderMock::configureMiddlewares($containerMock, $middlewares);

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
        $containerMock = $this->createMock(ContainerInterface::class);

        $package2 = $this->createMock(Package::class);
        $package2->method('getPackagePath')->willReturn(__DIR__ . '/../Http/Fixtures/Package2/');
        $package2->method('getValueFromComposerManifest')->with('name')->willReturn('typo3/cms-testing');
        $package2ServiceProvider = new PseudoServiceProvider($package2);

        $middlewares = new ArrayObject();
        $middlewares = Package1ServiceProviderMock::configureMiddlewares($containerMock, $middlewares);
        $middlewares = $package2ServiceProvider->getExtensions()['middlewares']($containerMock, $middlewares);

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
        $containerMock = $this->createMock(ContainerInterface::class);

        $package2 = $this->createMock(Package::class);
        $package2->method('getPackagePath')->willReturn(__DIR__ . '/../Http/Fixtures/Package2Disables1/');
        $package2->method('getValueFromComposerManifest')->with('name')->willReturn('typo3/cms-testing');
        $package2ServiceProvider = new PseudoServiceProvider($package2);

        $middlewares = new ArrayObject();
        $middlewares = Package1ServiceProviderMock::configureMiddlewares($containerMock, $middlewares);
        $middlewares = $package2ServiceProvider->getExtensions()['middlewares']($containerMock, $middlewares);

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
        $containerMock = $this->createMock(ContainerInterface::class);

        $package2 = $this->createMock(Package::class);
        $package2->method('getPackagePath')->willReturn(__DIR__ . '/../Http/Fixtures/Package2Replaces1/');
        $package2->method('getValueFromComposerManifest')->with('name')->willReturn('typo3/cms-testing');
        $package2ServiceProvider = new PseudoServiceProvider($package2);

        $middlewares = new ArrayObject();
        $middlewares = Package1ServiceProviderMock::configureMiddlewares($containerMock, $middlewares);
        $middlewares = $package2ServiceProvider->getExtensions()['middlewares']($containerMock, $middlewares);

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
        $newClosure = $this->getClosureForNew();
        $instance = $newClosure($this->createMock(ContainerInterface::class), \stdClass::class);
        self::assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * @test
     */
    public function newInjectsLogger(): void
    {
        $containerMock = $this->createMock(ContainerInterface::class);

        $logManagerMock = $this->createMock(LogManager::class);
        $logManagerMock->method('getLogger')->with(GeneralUtilityMakeInstanceInjectLoggerFixture::class)
            ->willReturn(new NullLogger());
        $containerMock->method('get')->with(LogManager::class)->willReturn($logManagerMock);
        $className = GeneralUtilityMakeInstanceInjectLoggerFixture::class;
        $newClosure = $this->getClosureForNew();
        $instance = $newClosure($containerMock, $className);
        self::assertInstanceOf(LoggerInterface::class, $instance->getLogger());
    }

    protected function getClosureForNew(): \Closure
    {
        return \Closure::bind(
            static function ($container, $className, $arguments = []) {
                return AbstractServiceProvider::new($container, $className, $arguments);
            },
            null,
            AbstractServiceProvider::class
        );
    }
}
