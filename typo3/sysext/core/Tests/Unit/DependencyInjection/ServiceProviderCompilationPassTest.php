<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\DependencyInjection;

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
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderCompilationPass;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderInterface;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderRegistry;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestServiceProvider;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestServiceProviderFactoryOverride;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestServiceProviderOverride;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestServiceProviderOverride2;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ServiceProviderCompilationPassTest extends UnitTestCase
{
    protected function getServiceProviderRegistry(array $serviceProviders)
    {
        $serviceProviderRegistryProphecy = $this->prophesize(ServiceProviderRegistry::class);
        $serviceProviderRegistryProphecy->getIterator()->will(function () use ($serviceProviders): \Generator {
            foreach ($serviceProviders as $id => $serviceProvider) {
                yield (string)$id => new $serviceProvider;
            }
        });

        foreach ($serviceProviders as $id => $serviceProvider) {
            $packageKey = (string)$id;

            $instance = new $serviceProvider;
            $factories = $instance->getFactories();
            $extensions = $instance->getExtensions();

            $serviceProviderRegistryProphecy->getFactories($id)->willReturn($factories);
            $serviceProviderRegistryProphecy->getExtensions($id)->willReturn($extensions);

            foreach ($factories as $serviceName => $factory) {
                $serviceProviderRegistryProphecy->createService($packageKey, $serviceName, Argument::type(ContainerInterface::class))->will(function ($args) use ($factory) {
                    return $factory($args[2]);
                });
            }
            foreach ($extensions as $serviceName => $extension) {
                $serviceProviderRegistryProphecy->extendService($packageKey, $serviceName, Argument::type(ContainerInterface::class), Argument::cetera())->will(function ($args) use ($extension) {
                    return $extension($args[2], $args[3] ?? null);
                });
            }
        }

        return $serviceProviderRegistryProphecy->reveal();
    }

    protected function getContainer(array $serviceProviders, callable $configure = null)
    {
        static $id = 0;

        $registry = $this->getServiceProviderRegistry($serviceProviders);
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

    public function testSimpleServiceProvider()
    {
        $container = $this->getContainer([
            TestServiceProvider::class
        ]);

        $serviceA = $container->get('serviceA');
        $serviceD = $container->get('serviceD');

        $this->assertInstanceOf(\stdClass::class, $serviceA);
        $this->assertInstanceOf(\stdClass::class, $serviceD);
        $this->assertEquals(42, $container->get('function'));
    }

    public function testServiceProviderOverrides()
    {
        $container = $this->getContainer([
            TestServiceProvider::class,
            TestServiceProviderOverride::class,
            TestServiceProviderOverride2::class
        ]);

        $serviceA = $container->get('serviceA');
        $serviceC = $container->get('serviceC');

        $this->assertInstanceOf(\stdClass::class, $serviceA);
        $this->assertEquals('foo', $serviceA->newProperty);
        $this->assertEquals('bar', $serviceA->newProperty2);
        $this->assertEquals('localhost', $serviceC->serviceB->parameter);
    }

    public function testServiceProviderFactoryOverrides()
    {
        $container = $this->getContainer([
            TestServiceProvider::class,
            TestServiceProviderFactoryOverride::class,
        ]);

        $serviceA = $container->get('serviceA');

        $this->assertInstanceOf(\stdClass::class, $serviceA);
        $this->assertEquals('remotehost', $serviceA->serviceB->parameter);
    }

    public function testServiceProviderFactoryOverridesForSymfonyDefinedServices()
    {
        $container = $this->getContainer(
            [
                TestServiceProvider::class,
                TestServiceProviderFactoryOverride::class,
            ],
            function (ContainerBuilder $container) {
                $definition = new \Symfony\Component\DependencyInjection\Definition('stdClass');
                // property should be overriden by service provider
                $definition->setProperty('parameter', 'remotehost');
                // property should not be "deleted" by service provider
                $definition->setProperty('symfony_defined_parameter', 'foobar');
                $container->setDefinition('serviceB', $definition);
            }
        );

        $serviceA = $container->get('serviceA');

        $this->assertInstanceOf(\stdClass::class, $serviceA);
        $this->assertEquals('remotehost', $serviceA->serviceB->parameter);
        $this->assertEquals('foobar', $serviceA->serviceB->symfony_defined_parameter);
    }

    public function testServiceProviderFactoryOverrideResetsAutowiring()
    {
        $container = $this->getContainer(
            [
                TestServiceProvider::class,
                TestServiceProviderFactoryOverride::class,
            ],
            function (ContainerBuilder $container) {
                $definition = new \Symfony\Component\DependencyInjection\Definition('stdClass');
                // property should be overriden by service provider
                $definition->setProperty('parameter', 'remotehost');
                // property should not be "deleted" by service provider
                $definition->setProperty('symfony_defined_parameter', 'foobar');
                $definition->setAutowired(true);
                $container->setDefinition('serviceB', $definition);
            }
        );

        $serviceA = $container->get('serviceA');

        $this->assertInstanceOf(\stdClass::class, $serviceA);
        $this->assertEquals('remotehost', $serviceA->serviceB->parameter);
        $this->assertEquals('foobar', $serviceA->serviceB->symfony_defined_parameter);
        $this->assertFalse($container->getDefinition('serviceB')->isAutowired());
    }

    public function testExceptionForNonNullableExtensionArgument()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('A registered extension for the service "serviceA" requires the service to be available, which is missing.');

        $container = $this->getContainer([
            TestServiceProviderOverride::class,
        ]);
    }

    public function testExceptionForInvalidFactories()
    {
        $this->expectException(\TypeError::class);

        $registry = new ServiceProviderRegistry([
            new class implements ServiceProviderInterface {
                public function getFactories()
                {
                    return [
                        'invalid' => 2
                    ];
                }
                public function getExtensions()
                {
                    return [];
                }
            }
        ]);
        $container = new ContainerBuilder();
        $registryServiceName = 'service_provider_registry_test';
        $container->addCompilerPass(new ServiceProviderCompilationPass($registry, $registryServiceName));
        $container->compile();
    }
}
