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

namespace TYPO3\CMS\Extensionmanager\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;

/**
 * @internal
 */
final class ExtensionRemotePass implements CompilerPassInterface
{
    private $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container): void
    {
        $remoteRegistryDefinition = $container->findDefinition(RemoteRegistry::class);

        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $definition = $container->findDefinition($id);
            $configuration = [];

            foreach ($tags as $attributes) {
                $configuration['default'] = (bool)($attributes['default'] ?? false);
                $configuration['enabled'] = (bool)($attributes['enabled'] ?? true);
            }

            $remoteRegistryDefinition->addMethodCall('registerRemote', [$definition, $configuration]);
        }
    }
}
