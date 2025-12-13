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

namespace TYPO3\CMS\Fluid\Core\Component;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * @internal May change / vanish any time
 */
#[Autoconfigure(public: true)]
final readonly class ComponentCollectionRegistry
{
    /**
     * @var array<string, DeclarativeComponentCollection>
     */
    private array $componentCollections;

    public function __construct(
        #[Autowire(service: 'cache.fluid_component_definitions')]
        private FrontendInterface $componentDefinitionsCache,
        private EventDispatcherInterface $eventDispatcher,
        #[Autowire(service: 'fluid.component.collections')]
        iterable $componentCollectionsConfig,
    ) {
        $componentCollections = [];
        foreach ($componentCollectionsConfig as $namespace => $config) {
            $componentCollections[$namespace] = $this->createComponentCollectionObject($namespace, $config);
        }
        $this->componentCollections = $componentCollections;
    }

    /**
     * @return array<string, DeclarativeComponentCollection>
     */
    public function getAll(): array
    {
        return $this->componentCollections;
    }

    private function createComponentCollectionObject(string $namespace, array $config): DeclarativeComponentCollection
    {
        if (!isset($config['templatePaths']) || !is_array($config['templatePaths']) || $config['templatePaths'] === []) {
            throw new \RuntimeException(sprintf(
                'Invalid or empty template paths provided for Fluid component collection "%s". At least one template path needs to be specified in Configuration/Fluid/ComponentCollections.php.',
                $namespace,
            ), 1768473237);
        }
        $componentCollection = new DeclarativeComponentCollection(
            $this->componentDefinitionsCache,
            $this->eventDispatcher,
            $namespace,
            $config['templatePaths'],
        );
        if (array_key_exists('templateNamePattern', $config)) {
            $componentCollection = $componentCollection->withTemplateNamePattern($config['templateNamePattern']);
        }
        if (array_key_exists('additionalArgumentsAllowed', $config)) {
            $componentCollection = $componentCollection->withAdditionalArgumentsAllowed($config['additionalArgumentsAllowed']);
        }
        return $componentCollection;
    }
}
