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
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * @internal
 */
class ContainerBuilder
{
    /**
     * @var array
     */
    protected $cacheIdentifiers;

    /**
     * @var array
     */
    protected $defaultServices;

    /**
     * @var string
     */
    protected $serviceProviderRegistryServiceName = 'service_provider_registry';

    /**
     * @param array $earlyInstances
     */
    public function __construct(array $earlyInstances)
    {
        $this->defaultServices = $earlyInstances + [ self::class => $this ];
    }

    /**
     * @param PackageManager $packageManager
     * @param FrontendInterface $cache
     * @internal
     */
    public function warmupCache(PackageManager $packageManager, FrontendInterface $cache): void
    {
        $registry = new ServiceProviderRegistry($packageManager);
        $containerBuilder = $this->buildContainer($packageManager, $registry);
        $cacheIdentifier = $this->getCacheIdentifier($packageManager);
        $this->dumpContainer($containerBuilder, $cache, $cacheIdentifier);
    }

    /**
     * @param PackageManager $packageManager
     * @param FrontendInterface $cache
     * @param bool $failsafe
     * @return ContainerInterface
     */
    public function createDependencyInjectionContainer(PackageManager $packageManager, FrontendInterface $cache, bool $failsafe = false): ContainerInterface
    {
        if (!$cache instanceof PhpFrontend) {
            throw new \RuntimeException('Cache must be instance of PhpFrontend', 1582022226);
        }
        $serviceProviderRegistry = new ServiceProviderRegistry($packageManager, $failsafe);

        if ($failsafe) {
            return new FailsafeContainer($serviceProviderRegistry, $this->defaultServices);
        }

        $cacheIdentifier = $this->getCacheIdentifier($packageManager);
        $containerClassName = $cacheIdentifier;

        $hasCache = $cache->requireOnce($cacheIdentifier) !== false;
        if (!$hasCache) {
            $containerBuilder = $this->buildContainer($packageManager, $serviceProviderRegistry);
            $this->dumpContainer($containerBuilder, $cache, $cacheIdentifier);
            $cache->requireOnce($cacheIdentifier);
        }
        $container = new $containerClassName();

        foreach ($this->defaultServices as $id => $service) {
            $container->set('_early.' . $id, $service);
        }

        $container->set($this->serviceProviderRegistryServiceName, $serviceProviderRegistry);

        return $container;
    }

    /**
     * @param PackageManager $packageManager
     * @param ServiceProviderRegistry $registry
     * @return SymfonyContainerBuilder
     */
    protected function buildContainer(PackageManager $packageManager, ServiceProviderRegistry $registry): SymfonyContainerBuilder
    {
        $containerBuilder = new SymfonyContainerBuilder();

        $containerBuilder->addCompilerPass(new ServiceProviderCompilationPass($registry, $this->serviceProviderRegistryServiceName));

        $packages = $packageManager->getActivePackages();
        foreach ($packages as $package) {
            $diConfigDir = $package->getPackagePath() . 'Configuration/';
            if (file_exists($diConfigDir . 'Services.php')) {
                $phpFileLoader = new PhpFileLoader($containerBuilder, new FileLocator($diConfigDir));
                $phpFileLoader->load('Services.php');
            }
            if (file_exists($diConfigDir . 'Services.yaml')) {
                $yamlFileLoader = new YamlFileLoader($containerBuilder, new FileLocator($diConfigDir));
                $yamlFileLoader->load('Services.yaml');
            }
        }
        // Store defaults entries in the DIC container
        // We need to use a workaround using aliases for synthetic services
        // But that's common in symfony (same technique is used to provide the
        // Symfony container interface as well).
        foreach (array_keys($this->defaultServices) as $id) {
            $syntheticId = '_early.' . $id;
            $containerBuilder->register($syntheticId)->setSynthetic(true)->setPublic(true);
            $containerBuilder->setAlias($id, $syntheticId)->setPublic(true);
        }

        // Optional service, set by BootService as back reference to the original bootService
        $containerBuilder->register('_early.boot-service')->setSynthetic(true)->setPublic(true);

        $containerBuilder->compile();

        return $containerBuilder;
    }

    /**
     * @param SymfonyContainerBuilder $containerBuilder
     * @param FrontendInterface $cache
     * @param string $cacheIdentifier
     * @return string
     */
    protected function dumpContainer(SymfonyContainerBuilder $containerBuilder, FrontendInterface $cache, string $cacheIdentifier): string
    {
        $containerClassName = $cacheIdentifier;

        $phpDumper = new PhpDumper($containerBuilder);
        $code = $phpDumper->dump(['class' => $containerClassName]);
        $code = str_replace('<?php', '', $code);
        // We need to patch the generated source code to use GeneralUtility::makeInstanceForDi() instead of `new`.
        // This is ugly, but has to stay, as long as we support SingletonInstances to be created/retrieved
        // through GeneralUtility::makeInstance.
        $code = preg_replace('/new ([^\(\s]+)\(/', '\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility::makeInstanceForDi(\\1::class, ', $code);
        if ($code === null) {
            throw new \RuntimeException('Could not generate container code', 1599767133);
        }
        $code = str_replace(', )', ')', $code);

        $cache->set($cacheIdentifier, $code);

        return $code;
    }

    /**
     * @param PackageManager $packageManager
     * @return string
     * @internal may only be used in this class or in functional tests
     */
    public function getCacheIdentifier(PackageManager $packageManager): string
    {
        $packageManagerCacheIdentifier = $packageManager->getCacheIdentifier() ?? '';
        return $this->cacheIdentifiers[$packageManagerCacheIdentifier] ?? $this->createCacheIdentifier($packageManager, $packageManagerCacheIdentifier);
    }

    /**
     * @param PackageManager $packageManager
     * @param string $additionalIdentifier
     * @return string
     */
    protected function createCacheIdentifier(PackageManager $packageManager, string $additionalIdentifier): string
    {
        return $this->cacheIdentifiers[$additionalIdentifier] = (new PackageDependentCacheIdentifier($packageManager))->withPrefix('DependencyInjectionContainer')->toString();
    }
}
