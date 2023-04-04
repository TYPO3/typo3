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

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderRegistry;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestRegistryServiceProvider;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\TestStatefulServiceProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ServiceProviderRegistryTest extends UnitTestCase
{
    protected PackageManager&MockObject $packageManagerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->packageManagerMock = $this->createMock(PackageManager::class);
    }

    protected function mockPackage(string $packageKey, string $serviceProvider): void
    {
        $this->packageManagerMock->method('isPackageActive')->with($packageKey)->willReturn(true);
        $package = $this->createMock(Package::class);
        $package->method('getPackageKey')->willReturn($packageKey);
        $package->method('getServiceProvider')->willReturn($serviceProvider);
        $this->packageManagerMock->method('getPackage')->with($packageKey)->willReturn($package);
        $this->packageManagerMock->method('getActivePackages')->willReturn([$package]);
    }

    /**
     * @test
     */
    public function registry(): void
    {
        $this->mockPackage('core', TestRegistryServiceProvider::class);
        $subject = new ServiceProviderRegistry($this->packageManagerMock);
        self::assertEquals(new TestRegistryServiceProvider(), $subject->get('core'));
    }

    /**
     * @test
     */
    public function registryCaches(): void
    {
        $this->mockPackage('core', TestRegistryServiceProvider::class);
        $subject = new ServiceProviderRegistry($this->packageManagerMock);
        self::assertEquals(new TestRegistryServiceProvider(), $subject->get('core'));
        self::assertSame($subject->get('core'), $subject->get('core'));
    }

    /**
     * @test
     */
    public function registryPassesPackageAsConstructorArgument(): void
    {
        $this->mockPackage('core', TestStatefulServiceProvider::class);
        $subject = new ServiceProviderRegistry($this->packageManagerMock);
        self::assertInstanceOf(TestStatefulServiceProvider::class, $subject->get('core'));
        self::assertInstanceOf(Package::class, $subject->get('core')->package);
    }

    /**
     * @test
     */
    public function getException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $subject = new ServiceProviderRegistry($this->packageManagerMock);
        $subject->get('backend');
    }

    /**
     * @test
     */
    public function getServices(): void
    {
        $this->mockPackage('core', TestRegistryServiceProvider::class);
        $subject = new ServiceProviderRegistry($this->packageManagerMock);
        $services = $subject->getFactories('core');
        self::assertArrayHasKey('serviceA', $services);
        $services2 = $subject->getFactories('core');
        self::assertSame($services['serviceA'], $services2['serviceA']);
    }

    /**
     * @test
     */
    public function extendServices(): void
    {
        $this->mockPackage('core', TestRegistryServiceProvider::class);
        $subject = new ServiceProviderRegistry($this->packageManagerMock);
        $services = $subject->getExtensions('core');
        self::assertArrayHasKey('serviceB', $services);
        $services2 = $subject->getExtensions('core');
        self::assertSame($services['serviceB'], $services2['serviceB']);
    }

    /**
     * @test
     */
    public function getServiceFactory(): void
    {
        $this->mockPackage('core', TestRegistryServiceProvider::class);
        $registry = new ServiceProviderRegistry($this->packageManagerMock);
        $containerMock = $this->createMock(ContainerInterface::class);
        $subject = $registry->createService('core', 'param', $containerMock);
        self::assertEquals(42, $subject);
    }

    /**
     * @test
     */
    public function getServiceExtension(): void
    {
        $this->mockPackage('core', TestRegistryServiceProvider::class);
        $subject = new ServiceProviderRegistry($this->packageManagerMock);
        $containerMock = $this->createMock(ContainerInterface::class);
        $service = $subject->extendService('core', 'serviceB', $containerMock);
        self::assertInstanceOf(\stdClass::class, $service);
    }

    /**
     * @test
     */
    public function iterator(): void
    {
        $packages = [
            'core' => TestRegistryServiceProvider::class,
            'backend' => TestRegistryServiceProvider::class,
        ];

        $packageCore = $this->createMock(Package::class);
        $packageCore->method('getPackageKey')->willReturn('core');
        $packageCore->method('getServiceProvider')->willReturn(TestRegistryServiceProvider::class);

        $packageBackend = $this->createMock(Package::class);
        $packageBackend->method('getPackageKey')->willReturn('backend');
        $packageBackend->method('getServiceProvider')->willReturn(TestRegistryServiceProvider::class);

        $this->packageManagerMock->method('getActivePackages')->willReturn([$packageCore, $packageBackend]);

        $subject = new ServiceProviderRegistry($this->packageManagerMock);

        $i = 0;
        foreach ($subject as $key => $provider) {
            self::assertEquals(array_keys($packages)[$i], $key);
            self::assertInstanceOf(TestRegistryServiceProvider::class, $subject->get($key));
            $i++;
        }
    }
}
