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

namespace TYPO3\CMS\Lowlevel\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;

/**
 * Receives all services which are tagged as configuration provider, validates
 * their attributes, orders the using the DependencyOrderingService and finally
 * forwards them to the ConfigurationProviderRegistry.
 */
final class ConfigurationModuleProviderPass implements CompilerPassInterface
{
    protected string $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container): void
    {
        $configurationModuleProviderRegistryDefinition = $container->findDefinition(ProviderRegistry::class);
        $providerList = [];

        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $definition = $container->findDefinition($id);
            if (!$definition->isAutoconfigured() || $definition->isAbstract()) {
                continue;
            }

            foreach ($tags as $attributes) {
                if (!isset($attributes['identifier']) || (bool)($attributes['disabled'] ?? false)) {
                    continue;
                }
                $providerList[$attributes['identifier']] = [
                    'definition' => $definition,
                    'attributes' => $attributes,
                    'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                    'after'  => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                ];
            }
        }

        if ($container->has(DependencyOrderingService::class)) {
            $providerList = $container->get(DependencyOrderingService::class)->orderByDependencies($providerList);
        }

        foreach ($providerList as $provider) {
            $configurationModuleProviderRegistryDefinition->addMethodCall(
                'registerProvider',
                [$provider['definition'], $provider['attributes']]
            );
        }
    }
}
