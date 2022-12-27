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
use TYPO3\CMS\Core\Messenger\HandlersLocatorFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
final class MessageHandlerPass implements CompilerPassInterface
{
    private readonly string $tagName;

    private ContainerBuilder $container;

    private DependencyOrderingService $orderingService;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
        $this->orderingService = new DependencyOrderingService();
    }

    public function process(ContainerBuilder $container): void
    {
        $this->container = $container;

        $handlersLocatorFactory = $container->findDefinition(HandlersLocatorFactory::class);

        foreach ($this->collectHandlers($container) as $message => $handlers) {
            foreach ($this->orderingService->orderByDependencies($handlers) as $handler) {
                $handlersLocatorFactory->addMethodCall('addHandler', [
                    $message,
                    $handler['service'],
                    $handler['method'] ?? '__invoke',
                ]);
            }
        }
    }

    /**
     * Collects all handlers from the container.
     */
    private function collectHandlers(ContainerBuilder $container): array
    {
        $unorderedHandlers = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $service = $container->findDefinition($serviceName);
            $service->setPublic(true);
            foreach ($tags as $attributes) {
                $messageHandler = $attributes['message'] ?? $this->getParameterType($serviceName, $service, $attributes['method'] ?? '__invoke');
                if (!$messageHandler) {
                    throw new \InvalidArgumentException(
                        'Service tag "messenger.message_handler" requires a message attribute to be defined or the method must declare a parameter type.  Missing in: ' . $serviceName,
                        1606732015
                    );
                }

                $messageIdentifier = $attributes['identifier'] ?? $serviceName;
                $unorderedHandlers[$messageHandler][$messageIdentifier] = [
                    'service' => $serviceName,
                    'method' => $attributes['method'] ?? null,
                    'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                    'after' => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                ];
            }
        }
        return $unorderedHandlers;
    }

    /**
     * Derives the class type of the first argument of a given method.
     */
    private function getParameterType(string $serviceName, Definition $definition, string $method = '__invoke'): ?string
    {
        // A Reflection exception should never actually get thrown here, but linters want a try-catch just in case.
        try {
            if (!$definition->isAutowired()) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Service "%s" has message handlers defined but does not declare a message to handle to and is not configured to autowire it from the handle method. Set autowire: true to enable auto-detection of the handled message.',
                        $serviceName
                    ),
                    1606732016,
                );
            }
            $params = $this->getReflectionMethod($serviceName, $definition, $method)->getParameters();
            $rType = count($params) ? $params[0]->getType() : null;
            if (!$rType instanceof \ReflectionNamedType) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Service "%s" registers method "%s" as a message handler, but does not specify a message type and the method does not type a parameter. Declare a class type for the method parameter or specify a message class explicitly',
                        $serviceName,
                        $method
                    ),
                    1606732017,
                );
            }
            return $rType->getName();
        } catch (\ReflectionException $e) {
            // The collectHandlers() method will convert this to an exception.
            return null;
        }
    }

    /**
     * @throws RuntimeException|\ReflectionException
     * This method borrowed very closely from Symfony's AbstractRecursivePass (and the ListenerProviderPass).
     * @see \TYPO3\CMS\Core\DependencyInjection\ListenerProviderPass::getReflectionMethod()
     */
    private function getReflectionMethod(string $serviceName, Definition $definition, string $method): \ReflectionFunctionAbstract
    {
        if (!$class = $definition->getClass()) {
            throw new RuntimeException(sprintf('Invalid service "%s": the class is not set.', $serviceName), 1606732018);
        }

        if (!$r = $this->container->getReflectionClass($class)) {
            throw new RuntimeException(sprintf('Invalid service "%s": class "%s" does not exist.', $serviceName, $class), 1606732019);
        }

        if (!$r->hasMethod($method)) {
            throw new RuntimeException(sprintf('Invalid service "%s": method "%s()" does not exist.', $serviceName, $class !== $serviceName ? $class . '::' . $method : $method), 1606732020);
        }

        $r = $r->getMethod($method);
        if (!$r->isPublic()) {
            throw new RuntimeException(sprintf('Invalid service "%s": method "%s()" must be public.', $serviceName, $class !== $serviceName ? $class . '::' . $method : $method), 1606732021);
        }

        return $r;
    }
}
