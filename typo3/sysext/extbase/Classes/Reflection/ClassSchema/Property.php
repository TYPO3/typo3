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

namespace TYPO3\CMS\Extbase\Reflection\ClassSchema;

use Symfony\Component\PropertyInfo\Type;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Property
{
    private array $definition;
    private PropertyCharacteristics $characteristics;

    public function __construct(
        private readonly string $name,
        array $definition
    ) {
        $this->characteristics = new PropertyCharacteristics($definition['propertyCharacteristicsBit']);
        unset($definition['propertyCharacteristicsBit']);

        $defaults = [
            'c' => null, // cascade
            'f' => null, // file upload
            't' => [], // types
            'v' => [], // validators
        ];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($definition[$key])) {
                $definition[$key] = $defaultValue;
            }
        }

        $this->definition = $definition;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the types (string, integer, ...) set by the `@var` doc comment and php property type declarations
     *
     * Returns empty array if types could not be evaluated
     *
     * @return list<Type>
     */
    public function getTypes(): array
    {
        return $this->definition['t'];
    }

    /**
     * Returns the primary type found in a list of types except LazyLoadingProxy
     */
    public function getPrimaryType(): ?Type
    {
        return $this->getFilteredTypes(fn(Type $type) => $type->getClassName() !== LazyLoadingProxy::class)[0] ?? null;
    }

    public function getPrimaryCollectionValueType(): ?Type
    {
        if (!$this->getPrimaryType()->isCollection()) {
            return null;
        }

        return $this->getPrimaryType()->getCollectionValueTypes()[0] ?? null;
    }

    /**
     * @return list<Type>
     */
    public function getFilteredTypes(callable $callback): array
    {
        return array_values(array_filter($this->definition['t'], $callback));
    }

    public function filterLazyLoadingProxyAndLazyObjectStorage(Type $type): bool
    {
        return !in_array((string)$type->getClassName(), [LazyLoadingProxy::class, LazyObjectStorage::class], true);
    }

    public function isObjectStorageType(): bool
    {
        $filteredTypes = $this->getFilteredTypes(
            fn(Type $type) => in_array((string)$type->getClassName(), [ObjectStorage::class, LazyObjectStorage::class], true)
        );

        return $filteredTypes !== [];
    }

    public function isPublic(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::VISIBILITY_PUBLIC);
    }

    public function isProtected(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::VISIBILITY_PROTECTED);
    }

    public function isPrivate(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::VISIBILITY_PRIVATE);
    }

    public function isLazy(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::ANNOTATED_LAZY);
    }

    public function isTransient(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::ANNOTATED_TRANSIENT);
    }

    public function isNullable(): bool
    {
        return $this->getPrimaryType() === null || $this->getPrimaryType()->isNullable();
    }

    public function getValidators(): array
    {
        return $this->definition['v'];
    }

    public function getFileUpload(): ?array
    {
        return $this->definition['f'];
    }

    public function getCascadeValue(): ?string
    {
        return $this->definition['c'];
    }
}
