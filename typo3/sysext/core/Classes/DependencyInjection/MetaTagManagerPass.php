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

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerInterface;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compiler pass to register tagged meta tag managers, ordered by their
 * "before" and "after" constraints at container compile time.
 *
 * @internal
 */
final class MetaTagManagerPass implements CompilerPassInterface
{
    public function __construct(private readonly string $tagName) {}

    public function process(ContainerBuilder $container): void
    {
        $registryDefinition = $container->findDefinition(MetaTagManagerRegistry::class);
        $managers = [];

        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $definition = $container->findDefinition($id);
            if ($definition->isAbstract()) {
                continue;
            }
            $className = $definition->getClass() ?? $id;
            if (!is_a($className, MetaTagManagerInterface::class, true)) {
                throw new \InvalidArgumentException(
                    'Service "' . $id . '" is tagged as "' . $this->tagName . '", but its class "' . $className . '" does not implement ' . MetaTagManagerInterface::class . '.',
                    1784904501
                );
            }
            foreach ($tags as $attributes) {
                $identifier = (string)($attributes['identifier'] ?? '');
                if ($identifier === '') {
                    throw new \InvalidArgumentException(
                        'Service tag "' . $this->tagName . '" requires the attribute "identifier" to be set. Missing in: ' . $id,
                        1784904502
                    );
                }
                $before = GeneralUtility::trimExplode(',', $attributes['before'] ?? '', true);
                // A manager without ordering constraints is put before the catch-all "generic"
                // manager. Self-references are removed to keep "generic" itself unconstrained.
                $before = array_values(array_diff($before !== [] ? $before : ['generic'], [$identifier]));
                $managers[$identifier] = [
                    'serviceName' => $id,
                    'before' => $before,
                    'after' => GeneralUtility::trimExplode(',', $attributes['after'] ?? '', true),
                ];
            }
        }

        $orderedManagers = [];
        foreach (new DependencyOrderingService()->orderByDependencies($managers) as $identifier => $config) {
            $orderedManagers[$identifier] = new Reference($config['serviceName']);
        }
        $registryDefinition->setArgument('$registeredManagers', new IteratorArgument($orderedManagers));
    }
}
