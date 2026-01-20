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

namespace TYPO3\CMS\Form\Domain\DTO;

/**
 * FormData - Complete form definition DTO
 * Used for read/write operations with full form structure
 *
 * @internal
 */
final readonly class FormData
{
    public function __construct(
        public string $identifier,
        public string $type,
        public string $name,
        public string $prototypeName,
        public array $renderingOptions,
        public array $finishers,
        public array $renderables,
        public array $variants,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            identifier: $data['identifier'] ?? '',
            type: $data['type'] ?? 'Form',
            name: $data['label'] ?? $data['identifier'] ?? '',
            prototypeName: $data['prototypeName'] ?? 'standard',
            renderingOptions: $data['renderingOptions'] ?? [],
            finishers: $data['finishers'] ?? [],
            renderables: $data['renderables'] ?? [],
            variants: $data['variants'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'type' => $this->type,
            'label' => $this->name,
            'prototypeName' => $this->prototypeName,
            'renderingOptions' => $this->renderingOptions,
            'finishers' => $this->finishers,
            'renderables' => $this->renderables,
            'variants' => $this->variants,
        ];
    }
}
