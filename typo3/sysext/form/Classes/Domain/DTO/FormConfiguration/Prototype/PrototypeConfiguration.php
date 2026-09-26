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
 * Typed representation of a single prototype within the "prototypes" section of
 * the form YAML configuration (e.g. "prototypes.standard").
 *
 * This is an "open" DTO: a prototype is fully extensible by third-party
 * extensions (custom form element types, finishers, validators, rendering
 * options, …). Only the well-known core sub-trees are exposed as typed
 * accessors; everything else stays reachable through
 * {@see RawConfigurationTrait::getRaw()} / ::get().
 *
 * @internal
 */
final readonly class PrototypeConfiguration
{
    use RawConfigurationTrait;

    /**
     * @param array<string, mixed> $raw The complete, untouched prototype configuration array
     * @param FormEditorConfiguration $formEditor Typed "formEditor" sub configuration
     * @param FormElementDefinitionCollection $formElements Typed form element registry
     * @param FinisherDefinitionCollection $finishers Typed finisher registry
     * @param ValidatorDefinitionCollection $validators Typed validator registry
     * @param FormEngineDefinition|null $formEngine Typed prototype-level FormEngine configuration
     */
    public function __construct(
        public array $raw,
        public FormEditorConfiguration $formEditor,
        public FormElementDefinitionCollection $formElements,
        public FinisherDefinitionCollection $finishers,
        public ValidatorDefinitionCollection $validators,
        public ?FormEngineDefinition $formEngine,
    ) {}

    /**
     * Create the DTO from the raw prototype configuration array.
     *
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        return new self(
            raw: $configuration,
            formEditor: FormEditorConfiguration::fromArray(
                is_array($configuration['formEditor'] ?? null) ? $configuration['formEditor'] : []
            ),
            formElements: FormElementDefinitionCollection::fromArray(
                is_array($configuration['formElementsDefinition'] ?? null) ? $configuration['formElementsDefinition'] : []
            ),
            finishers: FinisherDefinitionCollection::fromArray(
                is_array($configuration['finishersDefinition'] ?? null) ? $configuration['finishersDefinition'] : []
            ),
            validators: ValidatorDefinitionCollection::fromArray(
                is_array($configuration['validatorsDefinition'] ?? null) ? $configuration['validatorsDefinition'] : []
            ),
            formEngine: is_array($configuration['formEngine'] ?? null) ? FormEngineDefinition::fromArray($configuration['formEngine']) : null,
        );
    }

    /**
     * Finisher inspector editors, configured on the root "Form" element.
     */
    public function getFinisherPropertyCollections(): PropertyCollectionDefinitionCollection
    {
        return $this->formElements->get('Form')?->formEditor->propertyCollections->get('finishers')
            ?? PropertyCollectionDefinitionCollection::fromArray([]);
    }

    /**
     * Reduce the definition registries to their form editor configuration.
     *
     * @return array<string, array<int|string, mixed>>
     */
    public function getFormEditorDefinitions(): array
    {
        $formEditorDefinitions = [];
        foreach ($this->raw as $key => $configuration) {
            if (self::isRegistryKey($key)) {
                $formEditorDefinitions = self::mergeFormEditorDefinitions($formEditorDefinitions, $key, match ($key) {
                    'formElementsDefinition' => self::reduceTypedDefinitions($this->formElements),
                    'finishersDefinition' => self::reduceTypedDefinitions($this->finishers),
                    'validatorsDefinition' => self::reduceTypedDefinitions($this->validators),
                    default => self::reduceUntypedDefinitions($configuration),
                });
            }
        }
        foreach ($this->formEditor->getRaw() as $key => $configuration) {
            if (self::isRegistryKey($key)) {
                $formEditorDefinitions = self::mergeFormEditorDefinitions($formEditorDefinitions, $key, self::reduceUntypedDefinitions($configuration));
            }
        }
        return $formEditorDefinitions;
    }

    private static function isRegistryKey(int|string $key): bool
    {
        return is_string($key) && str_ends_with($key, 'Definition');
    }

    /**
     * @param array<string, array<int|string, mixed>> $formEditorDefinitions
     * @param array<int|string, mixed> $definitions
     * @return array<string, array<int|string, mixed>>
     */
    private static function mergeFormEditorDefinitions(array $formEditorDefinitions, string $registryKey, array $definitions): array
    {
        if ($definitions !== []) {
            $reducedKey = substr($registryKey, 0, -strlen('Definition'));
            $formEditorDefinitions[$reducedKey] = array_replace($formEditorDefinitions[$reducedKey] ?? [], $definitions);
        }
        return $formEditorDefinitions;
    }

    /**
     * @param iterable<FormElementDefinition|FinisherDefinition|ValidatorDefinition> $definitions
     * @return array<string, array<string, mixed>>
     */
    private static function reduceTypedDefinitions(iterable $definitions): array
    {
        $formEditorDefinitions = [];
        foreach ($definitions as $definition) {
            $formEditorDefinitions[$definition->identifier] = $definition->has('formEditor')
                ? $definition->formEditor->getRaw()
                : $definition->getRaw();
        }
        return $formEditorDefinitions;
    }

    /**
     * Fallback for definition registries without a DTO, e.g. extension-defined ones.
     *
     * @return array<int|string, mixed>
     */
    private static function reduceUntypedDefinitions(mixed $configuration): array
    {
        if (!is_array($configuration)) {
            return [];
        }
        $formEditorDefinitions = [];
        foreach ($configuration as $identifier => $definition) {
            $formEditorDefinitions[$identifier] = is_array($definition) && isset($definition['formEditor'])
                ? $definition['formEditor']
                : $definition;
        }
        return $formEditorDefinitions;
    }
}
