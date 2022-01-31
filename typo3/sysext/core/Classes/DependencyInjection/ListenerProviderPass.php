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
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
final class ListenerProviderPass implements CompilerPassInterface
{
    private string $tagName;

    private ContainerBuilder $container;

    private DependencyOrderingService $orderer;

    /**
     * @param string $tagName
     */
    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
        $this->orderer = new DependencyOrderingService();
    }

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        $this->container = $container;

        if (!$container->hasDefinition(ListenerProvider::class)) {
            // If there's no listener provider registered to begin with, don't bother registering listeners with it.
            return;
        }
        $listenerProviderDefinition = $container->findDefinition(ListenerProvider::class);

        $unorderedEventListeners = $this->collectListeners($container);

        foreach ($unorderedEventListeners as $eventName => $listeners) {
            // Configure ListenerProvider factory to include these listeners
            foreach ($this->orderer->orderByDependencies($listeners) as $listener) {
                $listenerProviderDefinition->addMethodCall('addListener', [
                    $eventName,
                    $listener['service'],
                    $listener['method'],
                ]);
            }
        }
    }

    /**
     * Collects all listeners from the container.
     */
    protected function collectListeners(ContainerBuilder $container): array
    {
        $unorderedEventListeners = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $service = $container->findDefinition($serviceName);
            $service->setPublic(true);
            foreach ($tags as $attributes) {
                $eventIdentifier = $attributes['event'] ?? $this->getParameterType($serviceName, $service, $attributes['method'] ?? '__invoke');
                if (!$eventIdentifier) {
                    throw new \InvalidArgumentException(
                        'Service tag "event.listener" requires an event attribute to be defined or the listener method must declare a parameter type.  Missing in: ' . $serviceName,
                        1563217364
                    );
                }

                $listenerIdentifier = $attributes['identifier'] ?? $serviceName;
                $unorderedEventListeners[$eventIdentifier][$listenerIdentifier] = [
                    'service' => $serviceName,
                    'method' => $attributes['method'] ?? null,
                    'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                    'after' => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                ];
            }
        }
        return $unorderedEventListeners;
    }

    /**
     * Derives the class type of the first argument of a given method.
     */
    protected function getParameterType(string $serviceName, Definition $definition, string $method = '__invoke'): ?string
    {
        // A Reflection exception should never actually get thrown here, but linters want a try-catch just in case.
        try {
            if (!$definition->isAutowired()) {
                throw new \InvalidArgumentException(
                    sprintf('Service "%s" has event listeners defined but does not declare an event to listen to and is not configured to autowire it from the listener method. Set autowire: true to enable auto-detection of the listener event.', $serviceName),
                    1623881314,
                );
            }
            $params = $this->getReflectionMethod($serviceName, $definition, $method)->getParameters();
            $rType = count($params) ? $params[0]->getType() : null;
            if (!$rType instanceof \ReflectionNamedType) {
                throw new \InvalidArgumentException(
                    sprintf('Service "%s" registers method "%s" as an event listener, but does not specify an event type and the method does not type a parameter. Declare a class type for the method parameter or specify an event class explicitly', $serviceName, $method),
                    1623881315,
                );
            }
            return $rType->getName();
        } catch (\ReflectionException $e) {
            // The collectListeners() method will convert this to an exception.
            return null;
        }
    }

    /**
     * @throws RuntimeException
     *
     * This method borrowed very closely from Symfony's AbstractRecurisvePass.
     *
     * @return \ReflectionFunctionAbstract
     */
    protected function getReflectionMethod(string $serviceName, Definition $definition, string $method): \ReflectionFunctionAbstract
    {
        if (!$class = $definition->getClass()) {
            throw new RuntimeException(sprintf('Invalid service "%s": the class is not set.', $serviceName), 1623881310);
        }

        if (!$r = $this->container->getReflectionClass($class)) {
            throw new RuntimeException(sprintf('Invalid service "%s": class "%s" does not exist.', $serviceName, $class), 1623881311);
        }

        if (!$r->hasMethod($method)) {
            throw new RuntimeException(sprintf('Invalid service "%s": method "%s()" does not exist.', $serviceName, $class !== $serviceName ? $class . '::' . $method : $method), 1623881312);
        }

        $r = $r->getMethod($method);
        if (!$r->isPublic()) {
            throw new RuntimeException(sprintf('Invalid service "%s": method "%s()" must be public.', $serviceName, $class !== $serviceName ? $class . '::' . $method : $method), 1623881313);
        }

        return $r;
    }
}
