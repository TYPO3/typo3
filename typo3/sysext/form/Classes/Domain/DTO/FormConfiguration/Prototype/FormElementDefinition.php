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
 * Identified, open form element definition from a prototype registry.
 *
 * @internal
 */
final readonly class FormElementDefinition
{
    use RawConfigurationTrait;

    /**
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $properties
     * @param array<string, mixed> $renderingOptions
     * @param array<int|string, mixed> $validators
     * @param array<int|string, mixed> $variants
     */
    public function __construct(
        public string $identifier,
        public array $raw,
        public ?string $implementationClassName,
        public ?string $rendererClassName,
        public array $properties,
        public array $renderingOptions,
        public array $validators,
        public array $variants,
        public FormEditorDefinition $formEditor,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(string $identifier, array $configuration): self
    {
        return new self(
            identifier: $identifier,
            raw: $configuration,
            implementationClassName: is_string($configuration['implementationClassName'] ?? null) ? $configuration['implementationClassName'] : null,
            rendererClassName: is_string($configuration['rendererClassName'] ?? null) ? $configuration['rendererClassName'] : null,
            properties: is_array($configuration['properties'] ?? null) ? $configuration['properties'] : [],
            renderingOptions: is_array($configuration['renderingOptions'] ?? null) ? $configuration['renderingOptions'] : [],
            validators: is_array($configuration['validators'] ?? null) ? $configuration['validators'] : [],
            variants: is_array($configuration['variants'] ?? null) ? $configuration['variants'] : [],
            formEditor: FormEditorDefinition::fromArray(
                is_array($configuration['formEditor'] ?? null) ? $configuration['formEditor'] : []
            ),
        );
    }
}
