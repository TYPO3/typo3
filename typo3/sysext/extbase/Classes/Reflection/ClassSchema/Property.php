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
 * Class TYPO3\CMS\Extbase\Reflection\ClassSchema\Property
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Property
{
    /**
     * @var string
     */
    private $name;

    private array $definition;

    /**
     * @var PropertyCharacteristics
     */
    private $characteristics;

    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->characteristics = new PropertyCharacteristics($definition['propertyCharacteristicsBit']);
        unset($definition['propertyCharacteristicsBit']);

        $defaults = [
            'c' => null, // cascade
            'd' => null, // defaultValue
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
     * Returns the type (string, integer, ...) set by the `@var` doc comment and php property type declarations
     *
     * Returns null if type could not be evaluated
     *
     * @return non-empty-string|null
     *
     * @deprecated since v12, will be removed in v13.
     */
    public function getType(): ?string
    {
        $primaryType = $this->getTypes()[0] ?? null;
        return $primaryType?->getClassName() ?? $primaryType?->getBuiltinType();
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
        return $this->getFilteredTypes(fn (Type $type) => $type->getClassName() !== LazyLoadingProxy::class)[0] ?? null;
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
            fn (Type $type) => in_array((string)$type->getClassName(), [ObjectStorage::class, LazyObjectStorage::class], true)
        );

        return $filteredTypes !== [];
    }

    /**
     * If the property is a collection of one of the types defined in
     * \TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::$collectionTypes,
     * the element type is evaluated and represents the type of collection
     * items inside the collection.
     *
     * Returns null if the property is not a collection and therefore no element type is defined.
     *
     * @return non-empty-string|null
     *
     * @deprecated since v12, will be removed in v13.
     */
    public function getElementType(): ?string
    {
        $primaryType = $this->getPrimaryType();
        if ($primaryType === null) {
            return null;
        }

        if (!$primaryType->isCollection() || $primaryType->getCollectionValueTypes() === []) {
            return null;
        }

        $primaryCollectionValueType = $primaryType->getCollectionValueTypes()[0];
        return $primaryCollectionValueType->getClassName() ?? $primaryCollectionValueType->getBuiltinType();
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

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->definition['d'];
    }

    public function getCascadeValue(): ?string
    {
        return $this->definition['c'];
    }
}
