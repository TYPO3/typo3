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
 * Open form editor definition.
 *
 * @internal
 */
final readonly class EditorDefinition
{
    use RawConfigurationTrait;

    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public array $raw,
        public ?string $identifier,
        public ?string $templateName,
        public ?string $label,
        public ?string $description,
        public ?string $propertyPath,
        public ?bool $doNotSetIfPropertyValueIsEmpty,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        return new self(
            raw: $configuration,
            identifier: is_string($configuration['identifier'] ?? null) ? $configuration['identifier'] : null,
            templateName: is_string($configuration['templateName'] ?? null) ? $configuration['templateName'] : null,
            label: is_string($configuration['label'] ?? null) ? $configuration['label'] : null,
            description: is_string($configuration['description'] ?? null) ? $configuration['description'] : null,
            propertyPath: is_string($configuration['propertyPath'] ?? null) ? $configuration['propertyPath'] : null,
            doNotSetIfPropertyValueIsEmpty: ($configuration['doNotSetIfPropertyValueIsEmpty'] ?? false) === true,
        );
    }
}
