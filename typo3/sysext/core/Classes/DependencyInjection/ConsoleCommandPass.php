<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\DependencyInjection;

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
        $commandRegistryDefinition = $container->findDefinition(CommandRegistry::class);
        if (!$commandRegistryDefinition) {
            return;
        }

        $unorderedEventListeners = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $container->findDefinition($serviceName)->setPublic(true);
            foreach ($tags as $attributes) {
                if (!isset($attributes['command'])) {
                    continue;
                }

                $commandRegistryDefinition->addMethodCall('addLazyCommand', [
                    $attributes['command'],
                    $serviceName,
                    (bool)($attributes['alias'] ?? false),
                    (bool)($attributes['schedulable'] ?? true)
                ]);
            }
        }
    }
}
