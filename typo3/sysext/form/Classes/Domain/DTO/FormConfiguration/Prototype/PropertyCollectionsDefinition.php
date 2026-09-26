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

namespace TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype;

use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\RawConfigurationTrait;

/**
 * Open "formEditor.propertyCollections" section, keyed by collection name
 * (e.g. "validators", "finishers").
 *
 * @implements \IteratorAggregate<string, PropertyCollectionDefinitionCollection>
 * @internal
 */
final readonly class PropertyCollectionsDefinition implements \IteratorAggregate
{
    use RawConfigurationTrait;

    /**
     * @param array<string, PropertyCollectionDefinitionCollection> $collections
     * @param array<int|string, mixed> $raw
     */
    private function __construct(private array $collections, public array $raw) {}

    /**
     * @param array<int|string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        $collections = [];
        foreach ($configuration as $name => $collectionConfiguration) {
            if (is_string($name) && is_array($collectionConfiguration)) {
                $collections[$name] = PropertyCollectionDefinitionCollection::fromArray($collectionConfiguration);
            }
        }
        return new self($collections, $configuration);
    }

    public function get(string $name): PropertyCollectionDefinitionCollection
    {
        return $this->collections[$name] ?? PropertyCollectionDefinitionCollection::fromArray([]);
    }

    /**
     * @return \Traversable<string, PropertyCollectionDefinitionCollection>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->collections;
    }
}
