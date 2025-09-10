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
    public function __construct(
        protected array $sheets
    ) {}

    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new FlexFieldPropertyNotFoundException('Flex property "' . $id . '" is not available.', 1731962637);
        }

        [$sheetName, $propertyPath] = $this->processId($id);

        if ($sheetName === '' && $this->hasMultipleSheets()) {
            // Get the sheet name for the requested property path - There is one, since has() returned true.
            foreach ($this->sheets as $name => $sheet) {
                if (ArrayUtility::isValidPath($sheet, $propertyPath, '.')) {
                    $sheetName = $name;
                    break;
                }
            }
        }

        $propertyValue = ArrayUtility::getValueByPath($this->sheets[$sheetName], $propertyPath, '.');
        if (is_array($propertyValue)) {
            array_walk_recursive($propertyValue, fn(mixed &$value): mixed => $value = $this->resolveRecordPropertyClosure($propertyPath, $value));
        } else {
            $propertyValue = $this->resolveRecordPropertyClosure($propertyPath, $propertyValue);
        }
        ArrayUtility::setValueByPath($this->sheets[$sheetName], $propertyPath, $propertyValue, '.');
        return $propertyValue;
    }

    public function has(string $id): bool
    {
        [$sheetName, $propertyPath] = $this->processId($id);

        if ($sheetName !== '' && !isset($this->sheets[$sheetName])) {
            // Given sheet name does not exist
            return false;
        }
        if ($this->hasMultipleSheets()) {
            if ($sheetName !== '') {
                return ArrayUtility::isValidPath($this->sheets[$sheetName], $propertyPath, '.');
            }
            // In case no sheet name is given, we try to execute fallback handling
            // by searching for the requested $propertyPath in all sheets.
            $occurences = [];
            foreach ($this->sheets as $sheetName => $sheet) {
                if (ArrayUtility::isValidPath($sheet, $propertyPath, '.')) {
                    $occurences[$sheetName] = $propertyPath;
                }
            }

            if (count($occurences) > 1) {
                // This is a special case, which we handle with an exception to create awareness for the error.
                throw new FlexFieldPropertyException('Given id is ambigious since the field exists in multiple sheets and no sheet is defined.', 1731962638);
            }
            // Whether the requested $propertyPath name exist in a sheet
            return count($occurences) === 1;
        }

        // Standard case, whether the $propertyPath exists in the given sheet name
        return ArrayUtility::isValidPath($this->sheets[$sheetName], $propertyPath, '.');
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

    public function toArray(): array
    {
        return $this->sheets;
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
        return [key($this->sheets), $id];
    }

    protected function resolveRecordPropertyClosure(string $id, mixed $propertyValue): mixed
    {
        if ($propertyValue instanceof RecordPropertyClosure) {
            try {
                $propertyValue = $propertyValue->instantiate();
            } catch (\Exception $e) {
                // Consumers of this method can rely on catching ContainerExceptionInterface
                throw new FlexFieldPropertyException(
                    'An exception occurred while instantiating flex field property "' . $id . '"',
                    1731962735,
                    $e
                );
            }
        }
        return $propertyValue;
    }
}
