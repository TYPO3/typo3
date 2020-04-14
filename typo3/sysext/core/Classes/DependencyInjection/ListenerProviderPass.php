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
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
final class ListenerProviderPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $tagName;

    /**
     * @param string $tagName
     */
    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $listenerProviderDefinition = $container->findDefinition(ListenerProvider::class);
        if (!$listenerProviderDefinition) {
            return;
        }

        $unorderedEventListeners = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $container->findDefinition($serviceName)->setPublic(true);
            foreach ($tags as $attributes) {
                if (!isset($attributes['event'])) {
                    throw new \InvalidArgumentException(
                        'Service tag "event.listener" requires an event attribute to be defined, missing in: ' . $serviceName,
                        1563217364
                    );
                }

                $eventIdentifier = $attributes['event'];
                $listenerIdentifier = $attributes['identifier'] ?? $serviceName;
                $unorderedEventListeners[$eventIdentifier][$listenerIdentifier] = [
                    'service' => $serviceName,
                    'method' => $attributes['method'] ?? null,
                    'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                    'after' => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                ];
            }
        }

        $dependencyOrderingService = new DependencyOrderingService();
        foreach ($unorderedEventListeners as $eventName => $listeners) {
            // Sort them
            $listeners = $dependencyOrderingService->orderByDependencies($listeners);
            // Configure ListenerProvider factory to include these listeners
            foreach ($listeners as $listener) {
                $listenerProviderDefinition->addMethodCall('addListener', [
                    $eventName,
                    $listener['service'],
                    $listener['method'],
                ]);
            }
        }
    }
}
