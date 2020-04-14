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

use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderRegistry;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestRegistryServiceProvider;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestStatefulServiceProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ServiceProviderRegistryTest extends UnitTestCase
{
    /**
     * @var PackageManager|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $packageManagerProphecy;

    /**
     * Set up this testcase
     */
    protected function setUp(): void
    {
        $this->packageManagerProphecy = $this->prophesize(PackageManager::class);
        $this->packageManagerProphecy->isPackageActive(Argument::any())->willReturn(false);
        $this->packageManagerProphecy->getActivePackages()->willReturn([]);
    }

    protected function mockPackages($packages)
    {
        $active = [];
        foreach ($packages as $packageKey => $serviceProvider) {
            $this->packageManagerProphecy->isPackageActive($packageKey)->willReturn(true);

            $package = $this->prophesize(Package::class);
            $package->getPackageKey()->willReturn($packageKey);
            $package->getServiceProvider()->willReturn($serviceProvider);

            $this->packageManagerProphecy->getPackage($packageKey)->willReturn($package->reveal());
            $active[] = $package->reveal();
        }

        $this->packageManagerProphecy->getActivePackages()->willReturn($active);
    }

    public function testRegistry()
    {
        $this->mockPackages(['core' => TestRegistryServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        self::assertEquals(new TestRegistryServiceProvider(), $registry->get('core'));
    }

    public function testRegistryCaches()
    {
        $this->mockPackages(['core' => TestRegistryServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        self::assertEquals(new TestRegistryServiceProvider(), $registry->get('core'));
        self::assertSame($registry->get('core'), $registry->get('core'));
    }

    public function testRegistryPassesPackageAsConstructorArgument()
    {
        $this->mockPackages(['core' => TestStatefulServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        self::assertInstanceOf(TestStatefulServiceProvider::class, $registry->get('core'));
        self::assertInstanceOf(Package::class, $registry->get('core')->package);
    }

    public function testGetException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mockPackages(['core' => TestRegistryServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        $registry->get('backend');
    }

    public function testGetServices()
    {
        $this->mockPackages(['core' => TestRegistryServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        $services = $registry->getFactories('core');
        self::assertArrayHasKey('serviceA', $services);

        $services2 = $registry->getFactories('core');

        self::assertSame($services['serviceA'], $services2['serviceA']);
    }

    public function testExtendServices()
    {
        $this->mockPackages(['core' => TestRegistryServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        $services = $registry->getExtensions('core');
        self::assertArrayHasKey('serviceB', $services);

        $services2 = $registry->getExtensions('core');

        self::assertSame($services['serviceB'], $services2['serviceB']);
    }

    public function testGetServiceFactory()
    {
        $this->mockPackages(['core' => TestRegistryServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $service = $registry->createService('core', 'param', $containerProphecy->reveal());

        self::assertEquals(42, $service);
    }

    public function testGetServiceExtension()
    {
        $this->mockPackages(['core' => TestRegistryServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $service = $registry->extendService('core', 'serviceB', $containerProphecy->reveal(), null);

        self::assertInstanceOf(\stdClass::class, $service);
    }

    public function testIterator()
    {
        $packages = [
            'core' => TestRegistryServiceProvider::class,
            'backend' => TestRegistryServiceProvider::class
        ];
        $this->mockPackages($packages);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        $i = 0;
        foreach ($registry as $key => $provider) {
            self::assertEquals(array_keys($packages)[$i], $key);
            self::assertInstanceOf(TestRegistryServiceProvider::class, $registry->get($key));
            $i++;
        }
    }
}
