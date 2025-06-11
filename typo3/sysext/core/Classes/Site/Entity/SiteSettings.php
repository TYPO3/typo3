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

namespace TYPO3\CMS\Core\Site\Entity;

use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\Settings\SettingsInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Entity representing all settings for a site. These settings are not overlaid
 * with TypoScript settings / constants which happens in the TypoScript Parser
 * for a specific page.
 */
final readonly class SiteSettings implements SettingsInterface, \JsonSerializable
{
    /**
     * @internal to be constructed by create() or createFromSettingsTree()
     */
    public function __construct(
        private SettingsInterface $settings,
        private array $settingsTree,
        private array $flattenedArrayValues,
    ) {}

    public function has(string $identifier): bool
    {
        return $this->settings->has($identifier) || array_key_exists($identifier, $this->settingsTree) || array_key_exists($identifier, $this->flattenedArrayValues);
    }

    public function isEmpty(): bool
    {
        return $this->settings->getIdentifiers() === [];
    }

    public function get(string $identifier, mixed $defaultValue = null): mixed
    {
        if ($this->settings->has($identifier)) {
            return $this->settings->get($identifier);
        }
        return $this->flattenedArrayValues[$identifier] ?? $this->settingsTree[$identifier] ?? $defaultValue;
    }

    public function getAll(): array
    {
        return $this->settingsTree;
    }

    public function getMap(): array
    {
        $map = [];
        foreach ($this->settings->getIdentifiers() as $key) {
            $map[$key] = $this->settings->get($key);
        }
        return $map;
    }

    public function getAllFlat(): array
    {
        return [
            ...$this->flattenedArrayValues,
            ...array_filter($this->getMap(), static fn(mixed $value): bool => !is_array($value)),
        ];
    }

    /**
     * @todo Update jsonSerialize() to return settings map and settings tree values, or remove altogether.
     */
    public function jsonSerialize(): mixed
    {
        return json_encode($this->settingsTree);
    }

    public function getIdentifiers(): array
    {
        return $this->settings->getIdentifiers();
    }

    public static function __set_state(array $state): static
    {
        return new static(...$state);
    }

    /**
     * @internal
     */
    public static function create(SettingsInterface $settings): self
    {
        $tree = [];
        $flattenedArrayValues = [];
        foreach ($settings->getIdentifiers() as $key) {
            $value = $settings->get($key);
            $tree = ArrayUtility::setValueByPath($tree, $key, $value, '.');
            if (is_array($value)) {
                foreach (ArrayUtility::flattenPlain($value) as $flatKey => $flatValue) {
                    $flattenedArrayValues[$key . '.' . $flatKey] = $flatValue;
                }
            }
        }

        return new self(
            settings: $settings,
            settingsTree: $tree,
            flattenedArrayValues: $flattenedArrayValues,
        );
    }

    /**
     * @internal
     */
    public static function createFromSettingsTree(array $settingsTree): self
    {
        $flatSettings = $settingsTree === [] ? [] : ArrayUtility::flattenPlain($settingsTree);
        return new self(
            settings: new Settings($flatSettings),
            settingsTree: $settingsTree,
            flattenedArrayValues: [],
        );
    }
}
