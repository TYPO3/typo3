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

namespace TYPO3\CMS\Backend\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentInterface;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentsRegistry;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compiler pass to register and order tagged sidebar components.
 *
 * Components are ordered by their before/after dependencies.
 *
 * @internal
 */
final readonly class SidebarComponentsPass implements CompilerPassInterface
{
    public function __construct(private string $tagName) {}

    public function process(ContainerBuilder $container): void
    {
        $registryDefinition = $container->findDefinition(SidebarComponentsRegistry::class);

        $components = [];

        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $definition = $container->findDefinition($id);

            if (!$definition->isAutoconfigured() || $definition->isAbstract()) {
                continue;
            }

            foreach ($tags as $attributes) {
                $identifier = $attributes['identifier'] ?? null;
                if (empty($identifier)) {
                    throw new \RuntimeException(
                        sprintf('Sidebar component %s must have an identifier', $id),
                        1765923036
                    );
                }

                if (!is_subclass_of($container->getParameterBag()->resolveValue($definition->getClass()), SidebarComponentInterface::class)) {
                    throw new \InvalidArgumentException(
                        sprintf('Sidebar component "%s" must implement SidebarComponentInterface', $identifier),
                        1734373001
                    );
                }

                $before = GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true);
                $after = GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true);

                $components[$identifier] = [
                    'before' => $before,
                    'after' => $after,
                    'serviceName' => $id,
                ];
            }
        }

        // Order components by dependencies
        $dependencyOrderingService = new DependencyOrderingService();
        $orderedComponents = $dependencyOrderingService->orderByDependencies($components);

        // Build ordered references array
        $registryDefinition->setArgument('$sidebarComponents', array_map(static fn($config) => new Reference($config['serviceName']), $orderedComponents));
    }
}
