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

namespace TYPO3\CMS\Core\Schema\Struct;

final class SelectItem implements \ArrayAccess
{
    private array $container = [];
    private const LEGACY_INDEXED_KEYS_MAPPING_TABLE = [
        0 => 'label',
        1 => 'value',
        2 => 'icon',
        3 => 'group',
        4 => 'description',
    ];

    public function __construct(
        private string $type,
        private string $label,
        private int|string|null $value,
        private ?string $icon = null,
        private ?string $group = null,
        private string|array|null $description = null,
        private bool $invertStateDisplay = false,
        private ?string $iconIdentifierChecked = null,
        private ?string $iconIdentifierUnchecked = null,
        private ?string $labelChecked = null,
        private ?string $labelUnchecked = null,
    ) {}

    public static function fromTcaItemArray(array $item, string $type = 'select'): SelectItem
    {
        return new self(
            type: $type,
            label: $item['label'] ?? $item[0],
            value: $item['value'] ?? $item[1] ?? null,
            icon: $item['icon'] ?? $item[2] ?? null,
            group: $item['group'] ?? $item[3] ?? null,
            description: $item['description'] ?? $item[4] ?? null,
            invertStateDisplay: (bool)($item['invertStateDisplay'] ?? false),
            iconIdentifierChecked: $item['iconIdentifierChecked'] ?? null,
            iconIdentifierUnchecked: $item['iconIdentifierUnchecked'] ?? null,
            labelChecked: $item['labelChecked'] ?? null,
            labelUnchecked: $item['labelUnchecked'] ?? null,
        );
    }

    public function toArray(): array
    {
        if ($this->type === 'radio') {
            return [
                'label' => $this->label,
                'value' => $this->value,
            ];
        }

        if ($this->type === 'check') {
            return [
                'label' => $this->label,
                'invertStateDisplay' => $this->invertStateDisplay,
                'iconIdentifierChecked' => $this->iconIdentifierChecked,
                'iconIdentifierUnchecked' => $this->iconIdentifierUnchecked,
                'labelChecked' => $this->labelChecked,
                'labelUnchecked' => $this->labelUnchecked,
            ];
        }

        // Default type=select
        return [
            'label' => $this->label,
            'value' => $this->value,
            'icon' => $this->icon,
            'group' => $this->group,
            'description' => $this->description,
        ];
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function withLabel(string $label): SelectItem
    {
        $clone = clone $this;
        $clone->label = $label;
        return $clone;
    }

    public function getValue(): int|string|null
    {
        return $this->value;
    }

    public function withValue(int|string|null $value): SelectItem
    {
        $clone = clone $this;
        $clone->value = $value;
        return $clone;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function hasIcon(): bool
    {
        return $this->icon !== null;
    }

    public function withIcon(?string $icon): SelectItem
    {
        $clone = clone $this;
        $clone->icon = $icon;
        return $clone;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function hasGroup(): bool
    {
        return $this->group !== null;
    }

    public function withGroup(?string $group): SelectItem
    {
        $clone = clone $this;
        $clone->group = $group;
        return $clone;
    }

    public function getDescription(): string|array|null
    {
        return $this->description;
    }

    public function hasDescription(): bool
    {
        return $this->description !== null;
    }

    public function withDescription(string|array|null $description): SelectItem
    {
        $clone = clone $this;
        $clone->description = $description;
        return $clone;
    }

    public function invertStateDisplay(): bool
    {
        return $this->invertStateDisplay;
    }

    public function getIconIdentifierChecked(): ?string
    {
        return $this->iconIdentifierChecked;
    }

    public function hasIconIdentifierChecked(): bool
    {
        return $this->iconIdentifierChecked !== null;
    }

    public function getIconIdentifierUnchecked(): ?string
    {
        return $this->iconIdentifierUnchecked;
    }

    public function hasIconIdentifierUnchecked(): bool
    {
        return $this->iconIdentifierUnchecked !== null;
    }

    public function getLabelChecked(): ?string
    {
        return $this->labelChecked;
    }

    public function hasLabelChecked(): bool
    {
        return $this->labelChecked !== null;
    }

    public function getLabelUnchecked(): ?string
    {
        return $this->labelUnchecked;
    }

    public function hasLabelUnchecked(): bool
    {
        return $this->labelUnchecked !== null;
    }

    public function isDivider(): bool
    {
        return $this->value === '--div--';
    }

    public function offsetExists(mixed $offset): bool
    {
        if (array_key_exists($offset, self::LEGACY_INDEXED_KEYS_MAPPING_TABLE)) {
            $offset = self::LEGACY_INDEXED_KEYS_MAPPING_TABLE[$offset];
        }
        if (property_exists($this, $offset)) {
            return isset($this->toArray()[$offset]);
        }
        return isset($this->container[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (array_key_exists($offset, self::LEGACY_INDEXED_KEYS_MAPPING_TABLE)) {
            $offset = self::LEGACY_INDEXED_KEYS_MAPPING_TABLE[$offset];
        }
        if (property_exists($this, $offset)) {
            return $this->toArray()[$offset];
        }
        return $this->container[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (array_key_exists($offset, self::LEGACY_INDEXED_KEYS_MAPPING_TABLE)) {
            $offset = self::LEGACY_INDEXED_KEYS_MAPPING_TABLE[$offset];
        }
        if (property_exists($this, $offset)) {
            $this->{$offset} = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (array_key_exists($offset, self::LEGACY_INDEXED_KEYS_MAPPING_TABLE)) {
            $offset = self::LEGACY_INDEXED_KEYS_MAPPING_TABLE[$offset];
        }

        if (property_exists($this, $offset)) {
            $this->{$offset} = null;
        } else {
            unset($this->container[$offset]);
        }
    }
}
