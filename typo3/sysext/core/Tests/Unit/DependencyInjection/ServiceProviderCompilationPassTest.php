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

namespace TYPO3\CMS\Core\Tests\Unit\DependencyInjection;

use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderCompilationPass;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderRegistry;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestServiceProvider;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestServiceProviderFactoryOverride;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestServiceProviderOverride;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestServiceProviderOverride2;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ServiceProviderCompilationPassTest extends UnitTestCase
{
    protected function getContainer(array $serviceProviders, callable $configure = null): ContainerBuilder
    {
        static $id = 0;

        $packages = [];
        foreach ($serviceProviders as $serviceProvider) {
            $package = $this->createMock(Package::class);
            $package->method('getPackageKey')->willReturn($serviceProvider);
            $package->method('getServiceProvider')->willReturn($serviceProvider);
            $packages[$serviceProvider] = $package;
        }

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn($packages);

        $registry = new ServiceProviderRegistry($packageManager);
        $registryServiceName = 'service_provider_registry_' . ++$id;

        $container = new ContainerBuilder();
        if ($configure !== null) {
            $configure($container);
        }
        $logger = new Definition(NullLogger::class);
        $logger->setPublic(true);
        $container->setDefinition('logger', $logger);

        $container->addCompilerPass(new ServiceProviderCompilationPass($registry, $registryServiceName));
        $container->compile();
        $container->set($registryServiceName, $registry);

        return $container;
    }

    /**
     * @test
     */
    public function simpleServiceProvider(): void
    {
        $container = $this->getContainer([
            TestServiceProvider::class,
        ]);

        $serviceA = $container->get('serviceA');
        $serviceD = $container->get('serviceD');

        self::assertInstanceOf(\stdClass::class, $serviceA);
        self::assertInstanceOf(\stdClass::class, $serviceD);
        self::assertIsObject($container->get('function'));
    }

    /**
     * @test
     */
    public function serviceProviderOverrides(): void
    {
        $container = $this->getContainer([
            TestServiceProvider::class,
            TestServiceProviderOverride::class,
            TestServiceProviderOverride2::class,
        ]);

        $serviceA = $container->get('serviceA');
        $serviceC = $container->get('serviceC');

        self::assertInstanceOf(\stdClass::class, $serviceA);
        self::assertEquals('foo', $serviceA->newProperty);
        self::assertEquals('bar', $serviceA->newProperty2);
        self::assertEquals('localhost', $serviceC->serviceB->parameter);
    }

    /**
     * @test
     */
    public function serviceProviderFactoryOverrides(): void
    {
        $container = $this->getContainer([
            TestServiceProvider::class,
            TestServiceProviderFactoryOverride::class,
        ]);

        $serviceA = $container->get('serviceA');

        self::assertInstanceOf(\stdClass::class, $serviceA);
        self::assertEquals('remotehost', $serviceA->serviceB->parameter);
    }

    /**
     * @test
     */
    public function serviceProviderFactoryOverridesForSymfonyDefinedServices(): void
    {
        $container = $this->getContainer(
            [
                TestServiceProvider::class,
                TestServiceProviderFactoryOverride::class,
            ],
            static function (ContainerBuilder $container) {
                $definition = new Definition(\stdClass::class);
                // property should be overridden by service provider
                $definition->setProperty('parameter', 'remotehost');
                // property should not be "deleted" by service provider
                $definition->setProperty('symfony_defined_parameter', 'foobar');
                $container->setDefinition('serviceB', $definition);
            }
        );

        $serviceA = $container->get('serviceA');

        self::assertInstanceOf(\stdClass::class, $serviceA);
        self::assertEquals('remotehost', $serviceA->serviceB->parameter);
        self::assertEquals('foobar', $serviceA->serviceB->symfony_defined_parameter);
    }

    /**
     * @test
     */
    public function serviceProviderFactoryOverrideResetsAutowiring(): void
    {
        $container = $this->getContainer(
            [
                TestServiceProvider::class,
                TestServiceProviderFactoryOverride::class,
            ],
            static function (ContainerBuilder $container) {
                $definition = new Definition(\stdClass::class);
                // property should be overridden by service provider
                $definition->setProperty('parameter', 'remotehost');
                // property should not be "deleted" by service provider
                $definition->setProperty('symfony_defined_parameter', 'foobar');
                $definition->setAutowired(true);
                $container->setDefinition('serviceB', $definition);
            }
        );

        $serviceA = $container->get('serviceA');

        self::assertInstanceOf(\stdClass::class, $serviceA);
        self::assertEquals('remotehost', $serviceA->serviceB->parameter);
        self::assertEquals('foobar', $serviceA->serviceB->symfony_defined_parameter);
        self::assertFalse($container->getDefinition('serviceB')->isAutowired());
    }

    /**
     * @test
     */
    public function exceptionForNonNullableExtensionArgument(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('A registered extension for the service "serviceA" requires the service to be available, which is missing.');

        $this->getContainer([
            TestServiceProviderOverride::class,
        ]);
    }
}
