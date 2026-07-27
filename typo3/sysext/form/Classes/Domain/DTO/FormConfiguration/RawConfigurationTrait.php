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
 * Escape hatch for "open" configuration DTOs.
 *
 * The "prototypes" section of the form YAML configuration is extensible by
 * design: third-party extensions may register arbitrary form element types,
 * inspector editors, finishers, validators, rendering options and custom
 * properties. DTOs modelling that section therefore expose typed accessors for
 * the well-known core keys only, while keeping the untouched raw configuration
 * array reachable through this trait.
 *
 * Consuming DTOs must declare a promoted constructor property `array $raw`
 * holding the original configuration array.
 *
 * @property-read array<string, mixed> $raw
 * @internal
 */
trait RawConfigurationTrait
{
    /**
     * Return the complete raw configuration array this DTO was built from.
     *
     * Use this to reach custom keys that are not (yet) mapped to typed
     * properties, e.g. properties registered by third-party extensions.
     *
     * @return array<string, mixed>
     */
    public function getRaw(): array
    {
        return $this->raw;
    }

    /**
     * Read a value from the raw configuration using a dot-separated path.
     *
     * Example: `$dto->get('formEditor.dynamicJavaScriptModules.app')`.
     */
    public function get(string $path, mixed $default = null): mixed
    {
        $value = $this->raw;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    /**
     * Check whether the given dot-separated path exists in the raw configuration.
     */
    public function has(string $path): bool
    {
        $value = $this->raw;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }
        return true;
    }
}
