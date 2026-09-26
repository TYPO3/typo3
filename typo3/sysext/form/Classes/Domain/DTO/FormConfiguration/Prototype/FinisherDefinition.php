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
 * Identified, open finisher definition from a prototype registry.
 *
 * @internal
 */
final readonly class FinisherDefinition
{
    use RawConfigurationTrait;

    /**
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $identifier,
        public array $raw,
        public ?string $implementationClassName,
        public array $options,
        public FormEditorDefinition $formEditor,
        public ?FormEngineDefinition $formEngine,
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
            options: is_array($configuration['options'] ?? null) ? $configuration['options'] : [],
            formEditor: FormEditorDefinition::fromArray(
                is_array($configuration['formEditor'] ?? null) ? $configuration['formEditor'] : []
            ),
            formEngine: is_array($configuration['FormEngine'] ?? null) ? FormEngineDefinition::fromArray($configuration['FormEngine']) : null,
        );
    }

    /**
     * Resolve finisher-specific translation files, falling back to the prototype configuration.
     *
     * @return array<int, string>
     */
    public function getTranslationFiles(?FormEngineDefinition $prototypeFormEngine): array
    {
        if ($this->formEngine?->has('translationFiles')) {
            return $this->formEngine->translationFiles;
        }
        return $prototypeFormEngine->translationFiles ?? [];
    }
}
