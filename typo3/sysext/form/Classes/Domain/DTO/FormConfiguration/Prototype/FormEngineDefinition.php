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

use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\ConfigurationValueNormalizationTrait;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\RawConfigurationTrait;

/**
 * Open FormEngine metadata of a finisher or prototype.
 *
 * @internal
 */
final readonly class FormEngineDefinition
{
    use ConfigurationValueNormalizationTrait;
    use RawConfigurationTrait;

    /**
     * @param array<string, mixed> $raw
     * @param array<int|string, mixed> $elements
     * @param array<int, string> $translationFiles
     */
    public function __construct(
        public array $raw,
        public ?string $label,
        public array $elements,
        public array $translationFiles,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        return new self(
            raw: $configuration,
            label: is_string($configuration['label'] ?? null) ? $configuration['label'] : null,
            elements: is_array($configuration['elements'] ?? null) ? $configuration['elements'] : [],
            translationFiles: self::normalizeTranslationFiles($configuration['translationFiles'] ?? null),
        );
    }
}
