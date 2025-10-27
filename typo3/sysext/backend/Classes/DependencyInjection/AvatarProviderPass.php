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
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compiler pass to register tagged avatar providers
 *
 * @internal
 */
final readonly class AvatarProviderPass implements CompilerPassInterface
{
    public function __construct(private string $tagName) {}

    public function process(ContainerBuilder $container): void
    {
        $avatarDefinition = $container->findDefinition(Avatar::class);
        $orderedProviders = [];
        $providers = [];

        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $definition = $container->findDefinition($id);

            if (!$definition->isAutoconfigured() || $definition->isAbstract()) {
                continue;
            }

            foreach ($tags as $attributes) {
                $identifier = $attributes['identifier'];
                $providers[$identifier] = [
                    'before' => GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true),
                    'after' => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                    'serviceName' => $id,
                ];
            }
        }

        foreach ((new DependencyOrderingService())->orderByDependencies($providers) as ['serviceName' => $serviceName]) {
            $orderedProviders[] = new Reference($serviceName);
        }

        $avatarDefinition->setArgument('$avatarProviders', $orderedProviders);
    }
}
