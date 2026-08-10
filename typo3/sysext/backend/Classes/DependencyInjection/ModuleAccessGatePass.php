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
use TYPO3\CMS\Backend\Module\ModuleAccessGateInterface;
use TYPO3\CMS\Backend\Module\ModuleAccessGateRegistry;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compiler pass to register and order tagged module access gates.
 *
 * @internal
 */
final readonly class ModuleAccessGatePass implements CompilerPassInterface
{
    public function __construct(private string $tagName) {}

    public function process(ContainerBuilder $container): void
    {
        $registryDefinition = $container->findDefinition(ModuleAccessGateRegistry::class);
        $gates = [];

        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $definition = $container->findDefinition($id);

            if (!$definition->isAutoconfigured() || $definition->isAbstract()) {
                continue;
            }

            foreach ($tags as $attributes) {
                $identifier = $attributes['identifier'] ?? null;
                if (empty($identifier)) {
                    throw new \RuntimeException(
                        sprintf('Module access gate %s must have an identifier', $id),
                        1774436662
                    );
                }

                if (!is_subclass_of($container->getParameterBag()->resolveValue($definition->getClass()), ModuleAccessGateInterface::class)) {
                    throw new \InvalidArgumentException(
                        sprintf('Module access gate "%s" must implement ModuleAccessGateInterface', $identifier),
                        1774436669
                    );
                }

                $gates[$identifier] = [
                    'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                    'after' => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                    'serviceName' => $id,
                ];
            }
        }

        $orderedGates = new DependencyOrderingService()->orderByDependencies($gates);

        $registryDefinition->setArgument(
            '$gates',
            array_map(
                static fn(array $config) => new Reference($config['serviceName']),
                $orderedGates
            )
        );
    }
}
