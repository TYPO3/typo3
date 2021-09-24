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
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderManifest;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compiler pass to register tagged MFA providers
 *
 * @internal
 */
final class MfaProviderPass implements CompilerPassInterface
{
    protected string $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container): void
    {
        $mfaProviderRegistryDefinition = $container->findDefinition(MfaProviderRegistry::class);
        $providers = [];

        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $definition = $container->findDefinition($id);
            if (!$definition->isAutoconfigured() || $definition->isAbstract()) {
                continue;
            }

            $definition->setPublic(true);

            foreach ($tags as $attributes) {
                $identifier = $attributes['identifier'] ?? $id;
                $providers[$identifier] = [
                    'title' => $attributes['title'] ?? '',
                    'description' => $attributes['description'] ?? '',
                    'setupInstructions' => $attributes['setupInstructions'] ?? '',
                    'iconIdentifier' => $attributes['icon'] ?? '',
                    'isDefaultProviderAllowed' => (bool)($attributes['defaultProviderAllowed'] ?? true),
                    'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                    'after'  => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                    'serviceName' => $id,
                ];
            }
        }

        foreach ((new DependencyOrderingService())->orderByDependencies($providers) as $identifier => $properties) {
            $manifest = new Definition(MfaProviderManifest::class);
            $manifest->setArguments([
                $identifier,
                $properties['title'],
                $properties['description'],
                $properties['setupInstructions'],
                $properties['iconIdentifier'],
                $properties['isDefaultProviderAllowed'],
                $properties['serviceName'],
                new Reference(ContainerInterface::class),
            ]);
            $manifest->setShared(false);

            $mfaProviderRegistryDefinition->addMethodCall('registerProvider', [$manifest]);
        }
    }
}
