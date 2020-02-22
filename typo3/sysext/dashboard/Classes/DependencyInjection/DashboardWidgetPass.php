<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Dashboard\DependencyInjection;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\WidgetRegistry;

/**
 * @internal
 */
final class DashboardWidgetPass implements CompilerPassInterface
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
    public function process(ContainerBuilder $container): void
    {
        $widgetRegistryDefinition = $container->findDefinition(WidgetRegistry::class);
        if (!$widgetRegistryDefinition) {
            return;
        }

        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $definition = $container->findDefinition($serviceName);
            $definition->setPublic(true);
            // Widgets are handled like prototypes right now (will require state)
            // @todo: Widgets should preferably be services, but that will require WidgetInterface
            // to change
            $definition->setShared(false);
            $className = $definition->getClass() ?? $serviceName;
            foreach ($tags as $attributes) {
                $identifier = $attributes['identifier'] ?? $serviceName;
                $widgetRegistryDefinition->addMethodCall('registerWidget', [
                    $identifier,
                    $serviceName,
                    GeneralUtility::trimExplode(',', $attributes['widgetGroups'] ?? '', true)
                ]);
            }
        }
    }
}
