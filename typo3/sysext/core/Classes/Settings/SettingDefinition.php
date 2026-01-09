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

namespace TYPO3\CMS\Core\Settings;

final readonly class SettingDefinition implements \JsonSerializable
{
    /**
     * @param array<int|string, string|int|float|bool> $enum
     * @param list<string> $tags
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $key,
        public string $type,
        public string|int|float|bool|array|object|null $default,
        public string $label,
        public ?string $description = null,
        public bool $readonly = false,
        public array $enum = [],
        public ?string $category = null,
        public array $tags = [],
        public array $options = [],
    ) {}

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }

    public function jsonSerialize(): array
    {
        return [
            ...get_object_vars($this),
            'enum' => (object)$this->enum,
            'options' => (object)$this->options,
        ];
    }
}
