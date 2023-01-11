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

namespace TYPO3\CMS\Webhooks\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Webhooks\Listener\MessageListener;
use TYPO3\CMS\Webhooks\WebhookTypesRegistry;

/**
 * A compiler pass to wire Messages which can be used as "webhook" into the webhook registry when needed.
 *
 * Also, if the Message has an Event name as identifier, we automatically register the MessageListener
 * to the ListenerProvider as well, and the MessageFactory can then automatically create the message for us.
 *
 * What does this mean? If your Message class has one object within the __construct() method,
 * we assume this is an Event class, and we connect to it.
 *
 * @internal
 */
final class WebhookCompilerPass implements CompilerPassInterface
{
    private ContainerBuilder $container;
    public function __construct(
        private readonly string $tagName
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(WebhookTypesRegistry::class)) {
            return;
        }
        $this->container = $container;
        $webhookTypesRegistryDefinition = $container->findDefinition(WebhookTypesRegistry::class);

        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $service = $container->findDefinition($serviceName);
            $description = '';
            $identifier = $serviceName;
            $eventIdentifier = null;
            // see if we should also auto-wire the message to the event, and register our main message listener
            $listenerProviderDefinition = $container->findDefinition(ListenerProvider::class);
            // we can have multiple tags on a service, so we need to loop over them and add all webhook configuration
            // for this message type
            foreach ($tags as $attributes) {
                $description = $attributes['description'] ?? '';
                if (isset($attributes['identifier'])) {
                    $identifier = $attributes['identifier'];
                }
                $method = $attributes['method'] ?? 'createFromEvent';
                $eventIdentifier = $attributes['event'] ?? $this->getParameterType($serviceName, $service, $method);
                if ($eventIdentifier !== null && $eventIdentifier !== false) {
                    $listenerProviderDefinition->addMethodCall(
                        'addListener',
                        [
                            $eventIdentifier,
                            MessageListener::class,
                            '__invoke',
                        ]
                    );
                }
                $webhookTypesRegistryDefinition->addMethodCall('addWebhookType', [$identifier, $description, $serviceName, $method, $eventIdentifier]);
            }
        }
    }

    /**
     * Derives the class type of the first argument of a given method.
     */
    protected function getParameterType(string $serviceName, Definition $definition, string $method = 'createFromEvent'): ?string
    {
        // A Reflection exception should never actually get thrown here, but linters want a try-catch just in case.
        try {
            if (!$definition->isAutowired()) {
                throw new \InvalidArgumentException(
                    sprintf('Service "%s" has webhooks defined but does not declare an event to listen to and is not configured to autowire it from the listener method. Set autowire: true to enable auto-detection of the listener event.', $serviceName),
                    1679613099,
                );
            }
            $params = $this->getReflectionMethod($definition, $method)?->getParameters();
            // Only check if the method has really just one argument
            if ($params === null || count($params) !== 1) {
                return null;
            }
            $rType = $params[0]->getType();
            if (!$rType instanceof \ReflectionNamedType) {
                // Don't connect this webhook message to an event
                return null;
            }
            return $rType->getName();
        } catch (\ReflectionException $e) {
            // Don't autowire this to an event
            return null;
        }
    }

    /**
     * @throws RuntimeException|\ReflectionException
     * This method borrowed very closely from Symfony's AbstractRecursivePass (and the ListenerProviderPass).
     * @see \TYPO3\CMS\Core\DependencyInjection\ListenerProviderPass::getReflectionMethod()
     */
    private function getReflectionMethod(Definition $definition, string $method): ?\ReflectionFunctionAbstract
    {
        if (!$class = $definition->getClass()) {
            return null;
        }

        if (!$r = $this->container->getReflectionClass($class)) {
            return null;
        }

        if (!$r->hasMethod($method)) {
            return null;
        }

        $r = $r->getMethod($method);
        if (!$r->isPublic()) {
            return null;
        }

        return $r;
    }
}
