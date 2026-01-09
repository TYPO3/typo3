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

use TYPO3\CMS\Backend\Dto\Settings\EditableSetting;

/**
 * @template T of SettingDefinition|EditableSetting
 * @internal
 */
final readonly class Category implements \JsonSerializable
{
    /**
     * @param list<T> $settings
     * @param list<Category<T>> $categories
     */
    public function __construct(
        public string $key,
        public string $label,
        public ?string $description = null,
        public ?string $icon = null,
        public array $settings = [],
        public array $categories = [],
    ) {}

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
