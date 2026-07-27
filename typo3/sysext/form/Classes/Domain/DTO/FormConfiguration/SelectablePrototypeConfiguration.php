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

namespace TYPO3\CMS\Form\Domain\DTO\FormConfiguration;

/**
 * Typed representation of a single entry within
 * "formManager.selectablePrototypesConfiguration".
 *
 * @internal
 */
final readonly class SelectablePrototypeConfiguration
{
    /**
     * @param list<NewFormTemplateConfiguration> $newFormTemplates
     */
    public function __construct(
        public string $identifier,
        public ?string $label = null,
        public array $newFormTemplates = [],
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        $newFormTemplates = [];
        foreach ($configuration['newFormTemplates'] ?? [] as $templateConfiguration) {
            if (is_array($templateConfiguration)) {
                $newFormTemplates[] = NewFormTemplateConfiguration::fromArray($templateConfiguration);
            }
        }

        return new self(
            identifier: (string)($configuration['identifier'] ?? ''),
            label: isset($configuration['label']) ? (string)$configuration['label'] : null,
            newFormTemplates: $newFormTemplates,
        );
    }

    /**
     * Check whether the given template path is configured for this prototype.
     */
    public function hasTemplatePath(string $templatePath): bool
    {
        foreach ($this->newFormTemplates as $newFormTemplate) {
            if ($newFormTemplate->templatePath === $templatePath) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reproduce the raw configuration array, e.g. to hand it over to the
     * JavaScript form manager app.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = ['identifier' => $this->identifier];
        if ($this->label !== null) {
            $result['label'] = $this->label;
        }
        $result['newFormTemplates'] = array_map(
            static fn(NewFormTemplateConfiguration $newFormTemplate): array => $newFormTemplate->toArray(),
            $this->newFormTemplates,
        );
        return $result;
    }
}
