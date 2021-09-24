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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
class ServiceProviderCompilationPass implements CompilerPassInterface
{
    /**
     * @var ServiceProviderRegistry
     */
    private $registry;

    /**
     * @var string
     */
    private $registryServiceName;

    /**
     * @param ServiceProviderRegistry $registry
     * @param string $registryServiceName
     */
    public function __construct(ServiceProviderRegistry $registry, string $registryServiceName = 'service_provider_registry')
    {
        $this->registry = $registry;
        $this->registryServiceName = $registryServiceName;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        // Now, let's store the registry in the container (an empty version of it... it has to be added dynamically at runtime):
        $this->registerRegistry($container);

        foreach ($this->registry as $serviceProviderKey => $serviceProvider) {
            $this->registerFactories($container, $serviceProviderKey);
        }

        foreach ($this->registry as $serviceProviderKey => $serviceProvider) {
            $this->registerExtensions($container, $serviceProviderKey);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function registerRegistry(ContainerBuilder $container): void
    {
        $definition = new Definition(ServiceProviderRegistry::class);
        $definition->setSynthetic(true);
        $definition->setPublic(true);

        $container->setDefinition($this->registryServiceName, $definition);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $serviceProviderKey
     */
    private function registerFactories(ContainerBuilder $container, string $serviceProviderKey): void
    {
        $serviceFactories = $this->registry->getFactories($serviceProviderKey);

        foreach ($serviceFactories as $serviceName => $callable) {
            $this->registerService($container, $serviceName, $serviceProviderKey, $callable);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $serviceProviderKey
     */
    private function registerExtensions(ContainerBuilder $container, string $serviceProviderKey): void
    {
        $serviceExtensions = $this->registry->getExtensions($serviceProviderKey);

        foreach ($serviceExtensions as $serviceName => $callable) {
            $this->extendService($container, $serviceName, $serviceProviderKey, $callable);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $serviceName
     * @param string $serviceProviderKey
     * @param callable $callable
     */
    private function registerService(
        ContainerBuilder $container,
        string $serviceName,
        string $serviceProviderKey,
        callable $callable
    ): void {
        if (!$container->hasDefinition($serviceName)) {
            // Create a new definition
            $factoryDefinition = new Definition();
            $container->setDefinition($serviceName, $factoryDefinition);
        } else {
            // Merge into an existing definition to keep possible addMethodCall/properties configurations
            // (which act like a service extension)
            // Retrieve the existing factory and overwrite it.
            $factoryDefinition = $container->getDefinition($serviceName);
            if ($factoryDefinition->isAutowired()) {
                $factoryDefinition->setAutowired(false);
            }
        }

        $className = $this->getReturnType($this->getReflection($callable), $serviceName) ?? 'object';
        $factoryDefinition->setClass($className);
        $factoryDefinition->setPublic(true);

        $staticallyCallable = $this->getStaticallyCallable($callable);
        if ($staticallyCallable !== null) {
            $factoryDefinition->setFactory($staticallyCallable);
            $factoryDefinition->setArguments([
                new Reference('service_container'),
            ]);
        } else {
            $factoryDefinition->setFactory([ new Reference($this->registryServiceName), 'createService' ]);
            $factoryDefinition->setArguments([
                $serviceProviderKey,
                $serviceName,
                new Reference('service_container'),
            ]);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $serviceName
     * @param string $serviceProviderKey
     * @param callable $callable
     */
    private function extendService(ContainerBuilder $container, string $serviceName, string $serviceProviderKey, callable $callable): void
    {
        $finalServiceName = $serviceName;
        $innerName = null;

        $reflection = $this->getReflection($callable);
        $previousClass = $container->hasDefinition($serviceName) ? $container->getDefinition($serviceName)->getClass() : null;
        $className = $this->getReturnType($reflection, $serviceName) ?? $previousClass ?? 'object';

        $factoryDefinition = new Definition($className);
        $factoryDefinition->setClass($className);
        $factoryDefinition->setPublic(true);

        if ($container->has($serviceName)) {
            [$finalServiceName, $previousServiceName] = $this->getDecoratedServiceName($container, $serviceName);
            $innerName = $finalServiceName . '.inner';

            $factoryDefinition->setDecoratedService($previousServiceName, $innerName);
        } elseif ($reflection->getNumberOfRequiredParameters() > 1) {
            throw new \Exception('A registered extension for the service "' . $serviceName . '" requires the service to be available, which is missing.', 1550092654);
        }

        $staticallyCallable = $this->getStaticallyCallable($callable);
        if ($staticallyCallable !== null) {
            $factoryDefinition->setFactory($staticallyCallable);
            $factoryDefinition->setArguments([
                new Reference('service_container'),
            ]);
        } else {
            $factoryDefinition->setFactory([ new Reference($this->registryServiceName), 'extendService' ]);
            $factoryDefinition->setArguments([
                $serviceProviderKey,
                $serviceName,
                new Reference('service_container'),
            ]);
        }

        if ($innerName !== null) {
            $factoryDefinition->addArgument(new Reference($innerName));
        }

        $container->setDefinition($finalServiceName, $factoryDefinition);
    }

    /**
     * @param callable $callable
     * @return callable|null
     */
    private function getStaticallyCallable(callable $callable): ?callable
    {
        if (is_string($callable)) {
            return $callable;
        }
        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            return $callable;
        }

        return null;
    }

    /**
     * @param \ReflectionFunctionAbstract $reflection
     * @param string $serviceName
     * @return string|null
     */
    private function getReturnType(\ReflectionFunctionAbstract $reflection, string $serviceName): ?string
    {
        if ($reflection->getReturnType() instanceof \ReflectionNamedType) {
            return $reflection->getReturnType()->getName();
        }

        if (class_exists($serviceName, true) || interface_exists($serviceName, true)) {
            return $serviceName;
        }

        return null;
    }

    /**
     * @param callable $callable
     * @return \ReflectionFunctionAbstract
     */
    private function getReflection(callable $callable): \ReflectionFunctionAbstract
    {
        if (is_array($callable) && count($callable) === 2) {
            return new \ReflectionMethod($callable[0], $callable[1]);
        }
        if (is_object($callable) && !$callable instanceof \Closure) {
            return new \ReflectionMethod($callable, '__invoke');
        }

        return new \ReflectionFunction($callable);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $serviceName
     * @return array
     */
    private function getDecoratedServiceName(ContainerBuilder $container, string $serviceName): array
    {
        $counter = 1;
        while ($container->has($serviceName . '_decorated_' . $counter)) {
            $counter++;
        }
        return [
            $serviceName . '_decorated_' . $counter,
            $counter === 1 ? $serviceName : $serviceName . '_decorated_' . ($counter-1),
        ];
    }
}
