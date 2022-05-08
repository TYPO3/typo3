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

namespace TYPO3\CMS\Backend\Module;

/**
 * A simple DTO containing the user specific module settings, e.g. whether the clipboard is shown.
 * The DTO is created in the PSR-15 middleware BackendModuleValidator, in case a backend module
 * is requested and the user has necessary access permissions. The created DTO is then added as
 * attribute to the PSR-7 Request and can be further used in components, such as middlewares or
 * the route target (usually a backend controller).
 *
 * @see BackendModuleValidator
 */
class ModuleData
{
    protected array $properties = [];
    protected string $moduleIdentifier;
    protected array $defaultData = [];

    public function __construct(string $moduleIdentifier, array $data, array $defaultData = [])
    {
        $this->moduleIdentifier = $moduleIdentifier;
        $this->defaultData = $defaultData;
        $this->properties = array_replace_recursive($defaultData, $data);
    }

    public static function createFromModule(ModuleInterface $module, array $data): self
    {
        return new self(
            $module->getIdentifier(),
            $data,
            $module->getDefaultModuleData(),
        );
    }

    public function getModuleIdentifier(): string
    {
        return $this->moduleIdentifier;
    }

    public function get(string $propertyName, mixed $default = null): mixed
    {
        return $this->properties[$propertyName] ?? $default;
    }

    public function has(string $propertyName): bool
    {
        return isset($this->properties[$propertyName]);
    }

    public function set(string $propertyName, mixed $value): void
    {
        $this->properties[$propertyName] = $value;
    }

    /**
     * Cleans a single property by the given allowed list. First fallback
     * is the default data list. If this list does also not contain an
     * allowed value, the first value from the allowed list is taken.
     *
     * @return bool True if something has been cleaned up
     */
    public function clean(string $propertyName, array $allowedValues): bool
    {
        if (!$this->has($propertyName)) {
            throw new \InvalidArgumentException('Property ' . $propertyName . ' can not be cleaned, since it does not exist.', 1644600510);
        }

        if ($allowedValues === []) {
            throw new \InvalidArgumentException('Define at least one allowed value.', 1644600511);
        }

        if (in_array($this->properties[$propertyName], $allowedValues)) {
            // Current value is allowed, nothing to do
            return false;
        }

        if (isset($this->defaultData[$propertyName]) && in_array($this->defaultData[$propertyName], $allowedValues)) {
            // Set property to its default value - if it is allowed
            $this->properties[$propertyName] = $this->defaultData[$propertyName];
        } else {
            // Fall back to the first value of the allow list
            $this->properties[$propertyName] = reset($allowedValues);
        }

        return true;
    }

    /**
     * Cleans up all module data, which are defined in the
     * given allowed data list. Usually called with $MOD_MENU.
     */
    public function cleanUp(array $allowedData, bool $useKeys = true): bool
    {
        $cleanUp = false;
        foreach ($allowedData as $propertyName => $allowedValues) {
            if (is_array($allowedValues)
                && $this->has($propertyName)
                && $this->clean($propertyName, $useKeys ? array_keys($allowedValues) : $allowedValues)
            ) {
                $cleanUp = true;
            }
        }
        return $cleanUp;
    }

    public function toArray(): array
    {
        return $this->properties;
    }
}
