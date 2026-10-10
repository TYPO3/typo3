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

namespace TYPO3\CMS\Core\Domain;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Domain\Exception\FlexFieldPropertyException;
use TYPO3\CMS\Core\Domain\Exception\FlexFieldPropertyNotFoundException;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Represents a record's flex form field values.
 *
 * @internal not part of public API, as this needs to be streamlined and proven
 */
class FlexFormFieldValues implements ContainerInterface, \ArrayAccess
{
    /**
     * @var \WeakMap<RecordPropertyClosure, array{0: mixed}>
     */
    private \WeakMap $resolvedRecordPropertyClosures;

    public function __construct(
        protected array $sheets
    ) {
        $this->resolvedRecordPropertyClosures = new \WeakMap();
    }

    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new FlexFieldPropertyNotFoundException('Flex property "' . $id . '" is not available.', 1731962637);
        }

        [$sheetName, $propertyPath] = $this->processId($id);
        if ($sheetName !== '') {
            return $this->getFromSheet($sheetName, $propertyPath);
        }

        // No sheet name given for a multi sheet flex form - has() ensured the property path is unambiguous.
        $propertyValues = [];
        foreach ($this->findSheetsContainingPropertyPath($propertyPath) as $name) {
            $propertyValues[] = $this->getFromSheet($name, $propertyPath);
        }
        return count($propertyValues) === 1 ? $propertyValues[0] : $this->mergePropertyValues($propertyPath, $propertyValues);
    }

    public function has(string $id): bool
    {
        // If there are no sheets, no value can be determined.
        if ($this->sheets === []) {
            return false;
        }

        [$sheetName, $propertyPath] = $this->processId($id);

        if ($sheetName !== '') {
            return isset($this->sheets[$sheetName]) && ArrayUtility::isValidPath($this->sheets[$sheetName], $propertyPath, '.');
        }

        // No sheet name given for a multi sheet flex form, therefore the requested $propertyPath is
        // searched in all sheets. It may exist in multiple sheets only as a group of properties,
        // e.g. "settings" for "settings.storagePid" in one sheet and "settings.limit" in another.
        $sheetNames = $this->findSheetsContainingPropertyPath($propertyPath);
        if (count($sheetNames) > 1) {
            $propertyValues = [];
            foreach ($sheetNames as $name) {
                $propertyValues[] = ArrayUtility::getValueByPath($this->sheets[$name], $propertyPath, '.');
            }
            // Throws in case the property path is ambiguous
            $this->mergePropertyValues($propertyPath, $propertyValues);
        }
        return $sheetNames !== [];
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Not implemented
    }

    public function offsetUnset(mixed $offset): void
    {
        // Not implemented
    }

    /**
     * Getter to be used in fluid for accessing field values
     */
    public function getSheets(): array
    {
        return $this->sheets;
    }

    public function toArray(): array
    {
        return $this->getSheets();
    }

    protected function hasMultipleSheets(): bool
    {
        return count($this->sheets) > 1;
    }

    protected function processId(string $id): array
    {
        if (str_contains($id, '/')) {
            // $id contains a sheet name
            return explode('/', $id, 2);
        }
        if ($this->hasMultipleSheets()) {
            // $id does not contain a sheet name while there are multiple sheets. Therefore, we
            // return an empty sheet name. This allows executing fallback handling in has() and get().
            return ['', $id];
        }

        // In case the $id does not contain a sheet name, but we have a
        // single sheet flex form, we fall back to this name automatically.
        return [(string)key($this->sheets), $id];
    }

    protected function getFromSheet(string $sheetName, string $propertyPath): mixed
    {
        $propertyValue = ArrayUtility::getValueByPath($this->sheets[$sheetName], $propertyPath, '.');
        if (is_array($propertyValue)) {
            array_walk_recursive($propertyValue, fn(mixed &$value): mixed => $value = $this->resolveRecordPropertyClosure($propertyPath, $value));
        } else {
            $propertyValue = $this->resolveRecordPropertyClosure($propertyPath, $propertyValue);
        }
        return $propertyValue;
    }

    /**
     * @return list<string>
     */
    protected function findSheetsContainingPropertyPath(string $propertyPath): array
    {
        $sheetNames = [];
        foreach ($this->sheets as $sheetName => $sheet) {
            if (ArrayUtility::isValidPath($sheet, $propertyPath, '.')) {
                $sheetNames[] = (string)$sheetName;
            }
        }
        return $sheetNames;
    }

    /**
     * Merges the values of a property path, which exists in multiple sheets, recursively.
     * This is only possible for groups of properties (non-empty associative arrays, created
     * from dotted field names) whose properties are distinct. Any other value, e.g. a list
     * or a single value, exists in more than one sheet and is therefore ambiguous.
     */
    protected function mergePropertyValues(string $propertyPath, array $propertyValues): array
    {
        $mergedValues = [];
        foreach ($propertyValues as $propertyValue) {
            if (!is_array($propertyValue) || $propertyValue === [] || array_is_list($propertyValue)) {
                throw new FlexFieldPropertyException(
                    'Given id "' . $propertyPath . '" is ambiguous since the field exists in multiple sheets and no sheet is defined.',
                    1731962638
                );
            }
            foreach ($propertyValue as $key => $value) {
                $mergedValues[$key] = array_key_exists($key, $mergedValues)
                    ? $this->mergePropertyValues($propertyPath . '.' . $key, [$mergedValues[$key], $value])
                    : $value;
            }
        }
        return $mergedValues;
    }

    protected function resolveRecordPropertyClosure(string $id, mixed $propertyValue): mixed
    {
        if ($propertyValue instanceof RecordPropertyClosure) {
            if (isset($this->resolvedRecordPropertyClosures[$propertyValue])) {
                return $this->resolvedRecordPropertyClosures[$propertyValue][0];
            }
            try {
                $resolvedValue = $propertyValue->instantiate();
            } catch (\Exception $e) {
                // Consumers of this method can rely on catching ContainerExceptionInterface
                throw new FlexFieldPropertyException(
                    'An exception occurred while instantiating flex field property "' . $id . '"',
                    1731962735,
                    $e
                );
            }
            $this->resolvedRecordPropertyClosures[$propertyValue] = [$resolvedValue];
            return $resolvedValue;
        }
        return $propertyValue;
    }
}
