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

namespace TYPO3\CMS\Dashboard\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfiguration;

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
        if (!$container->hasDefinition(WidgetRegistry::class)) {
            return;
        }
        $widgetRegistryDefinition = $container->findDefinition(WidgetRegistry::class);

        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $definition = $container->findDefinition($serviceName);
            $definition->setPublic(true);

            foreach ($tags as $attributes) {
                $identifier = $attributes['identifier'] ?? $serviceName;
                $attributes['identifier'] = $identifier;
                $attributes['serviceName'] = $serviceName;
                $attributes = $this->convertAttributes($attributes);

                $configurationServiceName = $this->registerWidgetConfigurationService(
                    $container,
                    $identifier,
                    $attributes
                );
                $definition->setArgument('$configuration', new Reference($configurationServiceName));

                $widgetRegistryDefinition->addMethodCall('registerWidget', [$identifier . 'WidgetConfiguration']);
            }
        }
    }

    private function convertAttributes(array $attributes): array
    {
        $attributes = array_merge([
            'iconIdentifier' => 'content-dashboard',
            'height' => 'small',
            'width' => 'small',
        ], $attributes);

        if (isset($attributes['groupNames'])) {
            $attributes['groupNames'] = GeneralUtility::trimExplode(',', $attributes['groupNames'], true);
        } else {
            $attributes['groupNames'] = [];
        }

        if (isset($attributes['additionalCssClasses'])) {
            $attributes['additionalCssClasses'] = GeneralUtility::trimExplode(' ', $attributes['additionalCssClasses'], true);
        } else {
            $attributes['additionalCssClasses'] = [];
        }

        return $attributes;
    }

    private function registerWidgetConfigurationService(
        ContainerBuilder $container,
        string $widgetIdentifier,
        array $arguments
    ): string {
        $serviceName = $widgetIdentifier . 'WidgetConfiguration';

        $definition = new Definition(
            WidgetConfiguration::class,
            $this->adjustArgumentsForDi($arguments)
        );
        $definition->setPublic(true);
        $container->addDefinitions([$serviceName => $definition]);

        return $serviceName;
    }

    private function adjustArgumentsForDi(array $arguments): array
    {
        foreach ($arguments as $key => $value) {
            $arguments['$' . $key] = $value;
            unset($arguments[$key]);
        }

        return $arguments;
    }
}
