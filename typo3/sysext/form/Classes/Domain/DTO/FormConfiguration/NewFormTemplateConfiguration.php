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
 * "formManager.selectablePrototypesConfiguration.*.newFormTemplates".
 *
 * @internal
 */
final readonly class NewFormTemplateConfiguration
{
    public function __construct(
        public string $templatePath,
        public ?string $label = null,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        return new self(
            templatePath: (string)($configuration['templatePath'] ?? ''),
            label: isset($configuration['label']) ? (string)$configuration['label'] : null,
        );
    }

    /**
     * Reproduce the raw configuration array, e.g. to hand it over to the
     * JavaScript form manager app.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $result = ['templatePath' => $this->templatePath];
        if ($this->label !== null) {
            $result['label'] = $this->label;
        }
        return $result;
    }
}
