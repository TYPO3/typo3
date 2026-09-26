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
 * Shared open form editor metadata of form elements, finishers and validators.
 *
 * @internal
 */
final readonly class FormEditorDefinition
{
    use RawConfigurationTrait;

    /**
    * @param array<string, mixed> $raw
    * @param array<string, mixed> $predefinedDefaults
     */
    public function __construct(
        public array $raw,
        public ?string $label,
        public ?string $iconIdentifier,
        public ?string $group,
        public ?int $groupSorting,
        public array $predefinedDefaults,
        public EditorDefinitionCollection $editors,
        public PropertyCollectionDefinitionCollection $propertyCollections,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        return new self(
            raw: $configuration,
            label: is_string($configuration['label'] ?? null) ? $configuration['label'] : null,
            iconIdentifier: is_string($configuration['iconIdentifier'] ?? null) ? $configuration['iconIdentifier'] : null,
            group: is_string($configuration['group'] ?? null) ? $configuration['group'] : null,
            groupSorting: is_int($configuration['groupSorting'] ?? null) ? $configuration['groupSorting'] : null,
            predefinedDefaults: is_array($configuration['predefinedDefaults'] ?? null) ? $configuration['predefinedDefaults'] : [],
            editors: EditorDefinitionCollection::fromArray(
                is_array($configuration['editors'] ?? null) ? $configuration['editors'] : []
            ),
            propertyCollections: PropertyCollectionDefinitionCollection::fromArray(
                is_array($configuration['propertyCollections'] ?? null) ? $configuration['propertyCollections'] : []
            ),
        );
    }

    /**
     * @return list<string>
     */
    public function getPropertyGridPropertyPaths(): array
    {
        return $this->editors->getPropertyPathsForTemplate('Inspector-PropertyGridEditor');
    }

    /**
     * @return list<string>
     */
    public function getPropertyGridPropertyPathsForCollection(string $identifier): array
    {
        return $this->propertyCollections->getPropertyPathsForIdentifierAndTemplate($identifier, 'Inspector-PropertyGridEditor');
    }
}
