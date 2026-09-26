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
 * Open property collection definition from a form editor configuration.
 *
 * @internal
 */
final readonly class PropertyCollectionDefinition
{
    use RawConfigurationTrait;

    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public array $raw,
        public ?string $identifier,
        public EditorDefinitionCollection $editors,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        return new self(
            raw: $configuration,
            identifier: is_string($configuration['identifier'] ?? null) ? $configuration['identifier'] : null,
            editors: EditorDefinitionCollection::fromArray(
                is_array($configuration['editors'] ?? null) ? $configuration['editors'] : []
            ),
        );
    }
}
