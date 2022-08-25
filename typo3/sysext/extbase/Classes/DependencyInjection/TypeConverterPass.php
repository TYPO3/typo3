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

namespace TYPO3\CMS\Extbase\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Property\Exception\InvalidTypeConverterConfigurationException;
use TYPO3\CMS\Extbase\Property\TypeConverterRegistry;

/**
 * Find all TypeConverters for Extbase's Property Mapper.
 *
 * @internal
 */
final class TypeConverterPass implements CompilerPassInterface
{
    private string $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    /**
     * @throws InvalidTypeConverterConfigurationException
     */
    public function process(ContainerBuilder $container): void
    {
        $typeConverterRegistryDefinition = $container->findDefinition(TypeConverterRegistry::class);

        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $definition = $container->findDefinition($serviceName);
            if (!$definition->isAutoconfigured() || $definition->isAbstract()) {
                continue;
            }

            $definition->setPublic(true);

            foreach ($tags as $attributes) {
                if (!isset($attributes['sources'])) {
                    throw new InvalidTypeConverterConfigurationException(
                        sprintf(
                            'Configuration for TypeConverter "%s" misses the "sources" attribute.',
                            $serviceName
                        ),
                        1638376684
                    );
                }

                $sources = GeneralUtility::trimExplode(',', (string)$attributes['sources'], true);

                if ($sources === []) {
                    throw new InvalidTypeConverterConfigurationException(
                        sprintf(
                            'The sources attribute of the configuration of TypeConverter "%s" contains an empty list.',
                            $serviceName
                        ),
                        1638376687
                    );
                }

                if (!($attributes['target'] ?? false)) {
                    throw new InvalidTypeConverterConfigurationException(
                        sprintf(
                            'Configuration for TypeConverter "%s" misses a valid "target" attribute.',
                            $serviceName
                        ),
                        1638376689
                    );
                }

                $typeConverterRegistryDefinition->addMethodCall('add', [
                    $definition,
                    (int)($attributes['priority'] ?? 10),
                    $sources,
                    $attributes['target'],
                ]);
            }
        }
    }
}
