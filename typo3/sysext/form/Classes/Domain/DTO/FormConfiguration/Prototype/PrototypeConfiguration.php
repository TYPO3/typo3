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
 * Typed accessors for "formElementsDefinition", "finishersDefinition",
 * "validatorsDefinition" and "formEngine" are added in the respective work
 * packages.
 *
 * @internal
 */
final readonly class PrototypeConfiguration
{
    use RawConfigurationTrait;

    /**
     * @param array<string, mixed> $raw The complete, untouched prototype configuration array
     * @param FormEditorConfiguration $formEditor Typed "formEditor" sub configuration
     */
    public function __construct(
        public array $raw,
        public FormEditorConfiguration $formEditor,
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
        );
    }
}
