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
use TYPO3\CMS\Core\Console\CommandRegistry;

/**
 * @internal
 */
final class ConsoleCommandPass implements CompilerPassInterface
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
        if (!$container->hasDefinition(CommandRegistry::class)) {
            return;
        }
        $commandRegistryDefinition = $container->findDefinition(CommandRegistry::class);

        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $commandServiceDefinition = $container->findDefinition($serviceName)->setPublic(true);
            $commandName = null;
            $description = null;
            $hidden = false;
            $aliases = [];
            foreach ($tags as $attributes) {
                $command = $attributes['command'] ?? null;
                $description = $attributes['description'] ?? $description;
                $hidden = (bool)($attributes['hidden'] ?? $hidden);
                $schedulable = (bool)($attributes['schedulable'] ?? true);
                $aliasFor = null;
                if ($command === null) {
                    continue;
                }

                $isAlias = $commandName !== null || ($attributes['alias'] ?? false);
                if (!$isAlias) {
                    $commandName = $attributes['command'];
                } else {
                    $aliasFor = $commandName;
                    $aliases[] = $attributes['command'];
                }

                $commandRegistryDefinition->addMethodCall('addLazyCommand', [
                    $command,
                    $serviceName,
                    $description,
                    $hidden,
                    $schedulable,
                    $aliasFor,
                ]);
            }
            $commandServiceDefinition->addMethodCall('setName', [$commandName]);
            if ($description) {
                $commandServiceDefinition->addMethodCall('setDescription', [$description]);
            }
            if ($hidden) {
                $commandServiceDefinition->addMethodCall('setHidden', [true]);
            }
            if ($aliases) {
                $commandServiceDefinition->addMethodCall('setAliases', [$aliases]);
            }
        }
    }
}
