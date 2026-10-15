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
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\Driver\FileStorageDriverFactoryInterface;
use TYPO3\CMS\Core\Resource\Driver\FilesystemAdapterFactoryInterface;

/**
 * Hands the file storage drivers registered with #[AsFileStorageDriver] to the
 * DriverRegistry: driver classes by name, factories as service locator.
 *
 * @internal
 */
final class FileStorageDriverPass implements CompilerPassInterface
{
    public function __construct(private readonly string $tagName) {}

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(DriverRegistry::class)) {
            return;
        }
        $driverClasses = [];
        $driverFactories = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $definition = $container->findDefinition($id);
            if ($definition->isAbstract()) {
                continue;
            }
            $className = $definition->getClass() ?? $id;
            foreach ($tags as $attributes) {
                $identifier = (string)($attributes['identifier'] ?? '');
                if ($identifier === '') {
                    throw new \InvalidArgumentException(
                        'Service "' . $id . '" is tagged as "' . $this->tagName . '" without an identifier.',
                        1792161901
                    );
                }
                if (isset($driverClasses[$identifier]) || isset($driverFactories[$identifier])) {
                    throw new \InvalidArgumentException(
                        'The file storage driver "' . $identifier . '" of service "' . $id . '" is already registered by another service.',
                        1792161902
                    );
                }
                if (is_a($className, DriverInterface::class, true)) {
                    $driverClasses[$identifier] = $className;
                } elseif (is_a($className, FileStorageDriverFactoryInterface::class, true) || is_a($className, FilesystemAdapterFactoryInterface::class, true)) {
                    $driverFactories[$identifier] = new Reference($id);
                } else {
                    throw new \InvalidArgumentException(
                        'Service "' . $id . '" is tagged as "' . $this->tagName . '", but its class "' . $className . '" implements neither '
                        . DriverInterface::class . ', ' . FileStorageDriverFactoryInterface::class . ' nor ' . FilesystemAdapterFactoryInterface::class . '.',
                        1792161903
                    );
                }
            }
        }
        $container->findDefinition(DriverRegistry::class)
            ->setArgument('$driverClasses', $driverClasses)
            ->setArgument('$driverFactories', ServiceLocatorTagPass::register($container, $driverFactories));
    }
}
