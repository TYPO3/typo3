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
        protected array $properties
    ) {}

    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new FlexFieldPropertyNotFoundException('Flex property "' . $id . '" is not available.', 1731962637);
        }
        $propertyValue = ArrayUtility::getValueByPath($this->properties, $id, '.');
        if (is_array($propertyValue)) {
            array_walk_recursive($propertyValue, fn(mixed &$value): mixed => $value = $this->resolveRecordPropertyClosure($id, $value));
        } else {
            $propertyValue = $this->resolveRecordPropertyClosure($id, $propertyValue);
        }
        ArrayUtility::setValueByPath($this->properties, $id, $propertyValue, '.');
        return $propertyValue;
    }

    public function has(string $id): bool
    {
        return ArrayUtility::isValidPath($this->properties, $id, '.');
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
        return $this->properties;
    }

    protected function resolveRecordPropertyClosure(string $id, mixed $propertyValue): mixed
    {
        if ($propertyValue instanceof RecordPropertyClosure) {
            try {
                $propertyValue = $propertyValue->instantiate();
            } catch (\Exception $e) {
                // Consumers of this method can rely on catching ContainerExceptionInterface
                throw new FlexFieldPropertyException(
                    'An exception occured while instantiating flex field property "' . $id . '"',
                    1731962735,
                    $e
                );
            }
        }
        return $propertyValue;
    }
}
