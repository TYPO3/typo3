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

namespace TYPO3\CMS\Core\DependencyInjection;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * A class that holds the list of service providers of a project.
 * This class is designed so that service provider do not need to be instantiated each time the registry is filled.
 * They can be lazily instantiated if needed.
 * @internal
 */
class ServiceProviderRegistry implements \IteratorAggregate
{
    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @var bool
     */
    private $failsafe;

    /**
     * The array with constructed values.
     *
     * @var array An array<packageKey, ServiceProviderInterface>
     */
    private $instances;

    /**
     * An array of service factories (the result of the call to 'getFactories'),
     * indexed by service provider.
     *
     * @var array An array<packageKey, array<servicename, callable>>
     */
    private $serviceFactories = [];

    /**
     * An array of service extensions (the result of the call to 'getExtensions'),
     * indexed by service provider.
     *
     * @var array An array<packageKey, array<servicename, callable>>
     */
    private $serviceExtensions = [];

    /**
     * Initializes the registry from a list of service providers.
     * This list of service providers can be passed as ServiceProvider instances, class name string,
     * or an array of ['class name', [constructor params...]].
     *
     * @param PackageManager $packageManager
     * @param bool $failsafe
     */
    public function __construct(PackageManager $packageManager, bool $failsafe = false)
    {
        $this->packageManager = $packageManager;
        $this->failsafe = $failsafe;
    }

    /**
     * Whether an id exists.
     *
     * @param string $packageKey Key of the service provider in the registry
     * @return bool true on success or false on failure.
     */
    public function has(string $packageKey): bool
    {
        if (isset($this->instances[$packageKey])) {
            return true;
        }

        if ($this->packageManager->isPackageActive($packageKey)) {
            if ($this->failsafe && $this->packageManager->getPackage($packageKey)->isPartOfMinimalUsableSystem() === false) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Returns service provider by id.
     *
     * @param string $packageKey Key of the service provider in the registry
     * @return ServiceProviderInterface
     */
    public function get(string $packageKey): ServiceProviderInterface
    {
        return $this->instances[$packageKey] ?? $this->create($packageKey);
    }

    /**
     * Returns service provider by id.
     *
     * @param string $packageKey Key of the service provider in the registry
     * @param Package|null $package
     * @return ServiceProviderInterface
     */
    private function create(string $packageKey, Package $package = null): ServiceProviderInterface
    {
        if ($package === null) {
            if (!$this->packageManager->isPackageActive($packageKey)) {
                throw new \InvalidArgumentException('Package ' . $packageKey . ' is not active', 1550351445);
            }
            $package = $this->packageManager->getPackage($packageKey);
        }
        $serviceProviderClassName = $package->getServiceProvider();
        $instance = new $serviceProviderClassName($package);

        if (!$instance instanceof ServiceProviderInterface) {
            throw new \InvalidArgumentException('Service providers need to implement ' . ServiceProviderInterface::class, 1550302554);
        }

        return $this->instances[$packageKey] = $instance;
    }

    /**
     * Returns the result of the getFactories call on service provider whose key in the registry is $packageKey.
     * The result is cached in the registry so two successive calls will trigger `getFactories` only once.
     *
     * @param string $packageKey Key of the service provider in the registry
     * @return array
     */
    public function getFactories(string $packageKey): array
    {
        return $this->serviceFactories[$packageKey] ?? ($this->serviceFactories[$packageKey] = $this->get($packageKey)->getFactories());
    }

    /**
     * Returns the result of the getExtensions call on service provider whose key in the registry is $packageKey.
     * The result is cached in the registry so two successive calls will trigger `getExtensions` only once.
     *
     * @param string $packageKey Key of the service provider in the registry
     * @return array
     */
    public function getExtensions(string $packageKey): array
    {
        return $this->serviceExtensions[$packageKey] ?? ($this->serviceExtensions[$packageKey] = $this->get($packageKey)->getExtensions());
    }

    /**
     * @param string $packageKey Key of the service provider in the registry
     * @param string $serviceName Name of the service to fetch
     * @param ContainerInterface $container
     * @return mixed
     */
    public function createService(string $packageKey, string $serviceName, ContainerInterface $container)
    {
        $factory = $this->getFactories($packageKey)[$serviceName];
        return $factory($container);
    }

    /**
     * @param string $packageKey Key of the service provider in the registry
     * @param string $serviceName Name of the service to fetch
     * @param ContainerInterface $container
     * @param mixed $previous
     *
     * @return mixed
     */
    public function extendService(string $packageKey, string $serviceName, ContainerInterface $container, $previous = null)
    {
        $extension = $this->getExtensions($packageKey)[$serviceName];
        return $extension($container, $previous);
    }

    /**
     * @return \Generator
     */
    public function getIterator(): \Generator
    {
        foreach ($this->packageManager->getActivePackages() as $package) {
            if ($this->failsafe && $package->isPartOfMinimalUsableSystem() === false) {
                continue;
            }
            $packageKey = $package->getPackageKey();
            yield $packageKey => ($this->instances[$packageKey] ?? $this->create($packageKey, $package));
        }
    }
}
