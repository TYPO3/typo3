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

        $this->assertEquals(new TestRegistryServiceProvider(), $registry->get('core'));
    }

    public function testRegistryCaches()
    {
        $this->mockPackages(['core' => TestRegistryServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        $this->assertEquals(new TestRegistryServiceProvider(), $registry->get('core'));
        $this->assertSame($registry->get('core'), $registry->get('core'));
    }

    public function testRegistryPassesPackageAsConstructorArgument()
    {
        $this->mockPackages(['core' => TestStatefulServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        $this->assertInstanceOf(TestStatefulServiceProvider::class, $registry->get('core'));
        $this->assertInstanceOf(Package::class, $registry->get('core')->package);
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
        $this->assertArrayHasKey('serviceA', $services);

        $services2 = $registry->getFactories('core');

        $this->assertSame($services['serviceA'], $services2['serviceA']);
    }

    public function testExtendServices()
    {
        $this->mockPackages(['core' => TestRegistryServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        $services = $registry->getExtensions('core');
        $this->assertArrayHasKey('serviceB', $services);

        $services2 = $registry->getExtensions('core');

        $this->assertSame($services['serviceB'], $services2['serviceB']);
    }

    public function testGetServiceFactory()
    {
        $this->mockPackages(['core' => TestRegistryServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $service = $registry->createService('core', 'param', $containerProphecy->reveal());

        $this->assertEquals(42, $service);
    }

    public function testGetServiceExtension()
    {
        $this->mockPackages(['core' => TestRegistryServiceProvider::class]);
        $registry = new ServiceProviderRegistry($this->packageManagerProphecy->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $service = $registry->extendService('core', 'serviceB', $containerProphecy->reveal(), null);

        $this->assertInstanceOf(\stdClass::class, $service);
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
            $this->assertEquals(array_keys($packages)[$i], $key);
            $this->assertInstanceOf(TestRegistryServiceProvider::class, $registry->get($key));
            $i++;
        }
    }
}
